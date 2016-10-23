<?php

namespace Votes\VotesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Utility\UtilityBundle\Utils\Utility;
use Votes\VotesBundle\Utils\MessageFactory as Msg;
use Utility\UtilityBundle\Utils\Response as Resp;
use Votes\VotesBundle\Document\Votes;
use Votes\VotesBundle\Service\VoteNotificationService;

class VotesController extends Controller
{
    protected $allowedItemTypes = array(
        'social_project'=>array(
            'repository'=>'PostFeedsBundle:SocialProject',
            'field'=>'we_want'
        )
    );
    protected $allowedVote = array(
      '0', '1'  
    );


    public function postVotingAction(Request $request){
        $utilityService = $this->_getUtilityService();
        $dm = $this->_getDocumentManager();
        
        $requiredParams = array('session_id','item_type', 'item_id', 'voter_type', 'voter_id');
        if(($result = $utilityService->checkRequest($request, $requiredParams))!==true){
            $this->_response(1001);
        }
        
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $itemType = Utility::getLowerCaseString($data['item_type']);
        
        if(!in_array($data['vote'], $this->allowedVote)){
            $this->_response(1107);
        }
        
        if(!key_exists($itemType, $this->allowedItemTypes)){
            $this->_response(1105);
        }
        
        switch($data['vote']){
            case '0':
                $this->_removeVote($data);
                break;
            case '1':
                $this->_addVote($data);
                break;
        }
        
        
    }
    
    public function postGetVotedUsersAction(Request $request){
        $utilityService = $this->_getUtilityService();
        $dm = $this->_getDocumentManager();
        
        $requiredParams = array('session_id','item_type', 'item_id');
        if(($result = $utilityService->checkRequest($request, $requiredParams))!==true){
            $this->_response(1001);
        }
        
        $data = $utilityService->getDeSerializeDataFromRequest($request);
        $data['item_type'] = Utility::getLowerCaseString($data['item_type']);
        $data['limit_start'] = isset($data['limit_start']) ? (int)$data['limit_start'] : 0;
        $data['limit_size'] = isset($data['limit_size']) ? (int)$data['limit_size'] : 10;
        if(!key_exists($data['item_type'], $this->allowedItemTypes)){
            $this->_response(1105);
        }
        
        try{
            $votes = $dm->getRepository('VotesVotesBundle:Votes')->getVotes($data['item_id'], $data['item_type'], $data['limit_start'], $data['limit_size']);
            $votesCount = $dm->getRepository('VotesVotesBundle:Votes')->getVotesCount($data['item_id'], $data['item_type']);
            $result = array();
            if(!$votes){
                $this->_response(101, array(
                    'item_id'=>$data['item_id'],
                    'item_type'=>$data['item_type'],
                    'total_votes'=>$votesCount,
                    'voters_info'=>array()
                ));
            }
            
            $usersVotedIds = array();
            foreach($votes as $vote){
                if($vote->getVoterType()=='citizen'){
                    $usersVotedIds[] = $vote->getVoterId();
                }
            }
            $userService = $this->_getUserService();
            $usersVoted = $userService->MultipleUserObjectService($usersVotedIds);
            
            $votedMembers = array();
            foreach($votes as $vote){
                switch($vote->getVoterType()){
                    case 'citizen':
                        $voterId = $vote->getVoterId();
                        if(isset($usersVoted[$voterId])){
                            $votedMembers[] = array_merge($usersVoted[$voterId], array('voter_type'=>'citizen'));
                        }
                        break;
                }
            }
            
            $this->_response(101, array(
               'voters_info' => $votedMembers,
                'item_id'=>$data['item_id'],
                'item_type'=>$data['item_type'],
                'total_votes'=>$votesCount
            ));
            
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }
    }
    
    private function _addVote($data){
        try{
            $dm = $this->_getDocumentManager();
            
            $itemId = isset($data['item_id']) ? $data['item_id'] : '';
            $voterId = isset($data['voter_id']) ? $data['voter_id'] : '';
            $voterType = isset($data['voter_type']) ? Utility::getLowerCaseString($data['voter_type']) : '';
            $itemType = isset($data['item_type']) ? Utility::getLowerCaseString($data['item_type']) : '';
            $vote = 1;
            $user_id = isset($data['session_id']) ? $data['session_id'] : '';
            
            // check if already voted
            $voted = $dm->getRepository('VotesVotesBundle:Votes')->findOneBy(array(
                        'voter_id'=> (string)$voterId,
                        'voter_type'=> (string)$voterType,
                        'item_id'=> (string)$itemId,
                        'item_type'=> (string)$itemType
                    ));
            
            if($voted and $voted->getVote()){
                $this->_response(1106);
            }
                        
            $votes = $voted ? $voted : new Votes();
            $votes->setItemId($itemId)
                    ->setItemType($itemType)
                    ->setVote($vote)
                    ->setVoterId($voterId)
                    ->setVoterType($voterType)
                    ->setVoterProfileId($user_id);
            $dm->persist($votes);
            $dm->flush();
            
            $votesCount = $dm->getRepository('VotesVotesBundle:Votes')->getVotesCount($itemId, $itemType);
            
            // update related collection/tabel for total vote count
            if(!empty($itemType)){
                $this->_updateTotalVoteCount($votesCount, $itemId, $itemType);
            }
            
            // send notifications
            if(!$voted){
                $voteNotification = new VoteNotificationService();
                $voteNotification->send($voterId, $itemId, $itemType);
            }
            
            $this->_response(101, array(
               'session_id'=>$user_id,
                'item_id'=>$itemId,
                'item_type'=>$itemType,
                'voter_type'=>$voterType,
                'voter_id'=>$voterId,
                'vote'=>$vote,
                'total_votes'=>$votesCount
            ));
        } catch(\Exception $e){
            $this->_response(1035, array('error_code'=>$e->getCode(), 'error_message'=>$e->getMessage()));
        }
        
        $this->_response(1035);
    }
    
    private function _removeVote($data){
        try{
            $dm = $this->_getDocumentManager();
            
            $itemId = isset($data['item_id']) ? $data['item_id'] : '';
            $voterId = isset($data['voter_id']) ? $data['voter_id'] : '';
            $voterType = isset($data['voter_type']) ? Utility::getLowerCaseString($data['voter_type']) : '';
            $itemType = isset($data['item_type']) ? Utility::getLowerCaseString($data['item_type']) : '';
            $vote = 0;
            $user_id = isset($data['session_id']) ? $data['session_id'] : '';
            
            // check if already voted
            $isVoted = $dm->getRepository('VotesVotesBundle:Votes')->isVoted($voterId, $voterType, $itemId, $itemType);
            if(!$isVoted){
                $this->_response(1039);
            }
            
            $dm->getRepository('VotesVotesBundle:Votes')->inActiveVote($voterId, $voterType, $itemId, $itemType);
            
            $votesCount = $dm->getRepository('VotesVotesBundle:Votes')->getVotesCount($itemId, $itemType);
            
            // update related collection/tabel for total vote count
            if(!empty($itemType)){
                $this->_updateTotalVoteCount($votesCount, $itemId, $itemType);
            }
            
            $this->_response(101, array(
               'session_id'=>$user_id,
                'item_id'=>$itemId,
                'item_type'=>$itemType,
                'voter_type'=>$voterType,
                'voter_id'=>$voterId,
                'vote'=>$vote,
                'total_votes'=>$votesCount
            ));
        } catch(\Exception $e){
            $this->_response(1035, array('error_code'=>$e->getCode(), 'error_message'=>$e->getMessage()));
        }
        
        $this->_response(1035);
    }
    
    private function _updateTotalVoteCount($votesCount, $itemId, $itemType){
        try{
            $dm = $this->_getDocumentManager();
            if(isset($this->allowedItemTypes[$itemType]['repository']) and isset($this->allowedItemTypes[$itemType]['field'])){
                $this->container->get('doctrine_mongodb')
                        ->getManager()
                        ->createQueryBuilder($this->allowedItemTypes[$itemType]['repository'])
                        ->update()
                        ->field($this->allowedItemTypes[$itemType]['field'])->set($votesCount)
                        ->field('id')->equals($itemId)
                        ->getQuery()
                        ->execute();
            }
        } catch (\Exception $ex) {
            //var_dump($ex->getTrace());
        }
    }
    
    private function _getUserService() {
        return $this->container->get('user_object.service');
    }
    
    private function _getDocumentManager(){
        return $this->container->get('doctrine.odm.mongodb.document_manager');
    }
    
    private function _getUtilityService(){
        return $this->container->get('store_manager_store.storeUtility');
    }
    
    private function _response($code, $data=array()){
        Utility::createResponse(new Resp(Msg::getMessage($code)->getCode(), Msg::getMessage($code)->getMessage(), $data));
    }
}
