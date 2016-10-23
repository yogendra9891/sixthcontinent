<?php

namespace Dashboard\DashboardManagerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use UserManager\Sonata\UserBundle\UserManagerSonataUserBundle;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Dashboard\DashboardManagerBundle\Document\DashboardComments;
use Dashboard\DashboardManagerBundle\Document\DashboardCommentsMedia;
use UserManager\Sonata\UserBundle\Entity\UserConnection;
use UserManager\Sonata\UserBundle\Entity\UserMultiProfile;
use UserManager\Sonata\UserBundle\Entity\UserActiveProfile;
use StoreManager\StoreBundle\Entity\Store;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Dashboard\DashboardManagerBundle\Document\DashboardPost;
use Dashboard\DashboardManagerBundle\Document\DashboardPostMedia;


class RatingController extends Controller
{
        
    /**
     * Insert/Update Rating for Post 
     * @param object request
     * @return json string
     */
    public function postInsertpostratingsAction(Request $request) 
    {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        //check required params
        $required_params =  array('user_id','post_id','rating');
        $this->checkRequiredParams($de_serialize, $required_params);
        
        //validating params
        $requited_fields = array('user_id','post_id','rating' );
        foreach($requited_fields as $field)
        {
            if($de_serialize[$field] == '')
            {
                $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
            
        }
        
        $valid_rating = array(1,2,3,4,5);
        if(!in_array( $de_serialize['rating'] ,$valid_rating))
        {
            $res_data = array('code' => '130', 'message' => 'INVALID_RATING', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                   ->find($de_serialize['post_id']);

        if (!$post) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        
        //check if there are no votes for that post
        if($post->getUserVotes() && count($post->getUserVotes()) )
        {
            $user_rating = $post->getUserVotes(); 
            
            //check if user already voted for particular post 
            if(array_key_exists($de_serialize['user_id'], $user_rating)) {
               $vote_sum = (int) $post->getVoteSum();
               $count = (int) $post->getVoteCount();
               $old_rating = (int) $user_rating[$de_serialize['user_id']];
               $new_rating = (int) $de_serialize['rating'];

               //updating user new rate
               $user_rating[$de_serialize['user_id']] = (int) $de_serialize['rating'];

               //calculating new average and total vote sum
               $new_vote_sum = $vote_sum +( $new_rating - $old_rating ); 
               $new_avg = round((float) $new_vote_sum/$count, 2);

               $post->setVoteSum($new_vote_sum);
               $post->setAvgRating($new_avg);

            } else {

               $vote_sum = (int) $post->getVoteSum();
               $count = (int) $post->getVoteCount();
               $new_rating = (int) $de_serialize['rating'];

               //updating user new rate
               $user_rating[$de_serialize['user_id']] = (int) $de_serialize['rating'];
               $count = $count + 1;
               
               //calculating new average and total vote sum
               $new_vote_sum = $vote_sum + $new_rating; 
               $new_avg = round((float) $new_vote_sum/($count), 2);

               $post->setVoteSum($new_vote_sum);
               $post->setAvgRating($new_avg);
               $post->setVoteCount($count);
            }

        } else {

            //updating user new rate
            $user_rating[$de_serialize['user_id']] = (int) $de_serialize['rating'];
            $count = 1 ;
            $new_avg = round($user_rating[$de_serialize['user_id']],2);
            $new_vote_sum = $user_rating[$de_serialize['user_id']];
            
                    
            $post->setVoteSum( $new_vote_sum );
            $post->setAvgRating( $new_avg );
            $post->setVoteCount( $count );
        }
              
        $post->setUserVotes($user_rating);
        $dm->persist($post);
        $dm->flush();
        
        $data = array(
          "new_average"=>$new_avg,  
          "vote_count"=>$count  
        );
        
        $res_data = array('code' => 101, 'message' => 'POST_VOTES_UPDATED', 'data' => $data );
        echo json_encode($res_data);
        exit;
                
    }
    
    
    /**
     * Remove Rating for Post per user 
     * @param object request
     * @return json string
     */
    public function postRemovepostratingsAction(Request $request) 
    {
        //Code start for getting the request
        $freq_obj = $request->get('reqObj');
        $fde_serialize = $this->decodeObjectAction($freq_obj);

        if (isset($fde_serialize)) {
            $de_serialize = $fde_serialize;
        } else {
            $de_serialize = $this->getAppData($request);
        }
        
        //check required params
        $required_params =  array('user_id','post_id');
        $this->checkRequiredParams($de_serialize, $required_params);
        
        //validating params
        $requited_fields = array('user_id','post_id');
        foreach($requited_fields as $field)
        {
            if($de_serialize[$field] == '')
            {
                $res_data = array('code' => '130', 'message' => 'INCOMPLETE_DATA', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
        }
        
        $dm = $this->get('doctrine.odm.mongodb.document_manager'); //getting doctrine mongo odm object.
        $post = $dm->getRepository('DashboardManagerBundle:DashboardPost')
                   ->find($de_serialize['post_id']);

        if (!$post) {
            $res_data = array('code' => 302, 'message' => 'RECORD_DOES_NOT_EXISTS', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        
        if($post->getUserVotes() && count($post->getUserVotes()))
        {
            $user_votes = $post->getUserVotes();
            if(array_key_exists( $de_serialize['user_id'], $user_votes))
            {
                //initializing values
                $user_rating = (int) $user_votes[ $de_serialize['user_id'] ];
                $post_votes_sum =  (int) $post->getVoteSum();
                $vote_count = (int) $post->getVoteCount();
                
                unset($user_votes[ $de_serialize['user_id'] ]); 
                               
                //changing values
                $vote_count = $vote_count - 1;
                $post_votes_sum = $post_votes_sum - $user_rating;
                
                if($vote_count != 0)
                {
                    $average_rating = round((float)$post_votes_sum / $vote_count, 2);
                } else {
                    $average_rating = 0;
                }
                
                
                $post->setVoteSum( $post_votes_sum );
                $post->setAvgRating( $average_rating );
                $post->setVoteCount( $vote_count );
                $post->setUserVotes( $user_votes );
                
                $dm->persist($post);
                $dm->flush();
                
                $data = array(
                    "post_rating"=>$average_rating,
                    "vote_count"=>$vote_count              
                );
                
                $res_data = array('code' => '101', 'message' => 'RATTING_REMOVED', 'data' => $data);
                echo json_encode($res_data);
                exit;
                
            } else {
                $res_data = array('code' => '100', 'message' => 'ACTION_NOT_ALLOWED', 'data' => array());
                echo json_encode($res_data);
                exit;
            }
        } else {
            $res_data = array('code' => '100', 'message' => 'ACTION_NOT_ALLOWED', 'data' => array());
            echo json_encode($res_data);
            exit;
        }
        
    }
    
    
    /**
     * Decoding the json string to object
     * @param json string $encode_object
     * @return object $decode_object
     */
    public function decodeObjectAction($encode_object) {
        $serializer = new Serializer(array(), array(
            'json' => new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            'xml' => new \Symfony\Component\Serializer\Encoder\XmlEncoder()
        ));
        $decode_object = $serializer->decode($encode_object, 'json');
        return $decode_object;
    }
    
    /**
     * method for decoding the raw data.
     * @param type $request
     * @return type
     */
    public function getAppData(Request $request) {
        $content = $request->getContent();
        $dataer = (object) $this->decodeObjectAction($content);
        $app_data = $dataer->reqObj;
        $req_obj = $app_data;
        return $req_obj;
    }
    
    /**
    * Checking Required Params In json
    * @param $user_params array json array send by user
    * @param $required_params array required params array
    */
    public function checkRequiredParams($user_params, $required_params) {
        
        foreach($required_params as $param){
            if (!array_key_exists($param, $user_params)) {   
                $final_data = array('code' => 130, 'message' => 'PARAMS_MISSING', 'data' => array());
                echo json_encode($final_data);
                exit; 
            }  
        }
    }
}
