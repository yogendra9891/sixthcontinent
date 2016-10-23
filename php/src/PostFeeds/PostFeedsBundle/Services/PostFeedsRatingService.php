<?php

namespace PostFeeds\PostFeedsBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use FOS\UserBundle\Model\UserInterface;
use PostFeeds\PostFeedsBundle\Document\PostFeeds;
use Utility\UtilityBundle\Utils\Utility;
use PostFeeds\PostFeedsBundle\Document\TaggingFeeds;
use PostFeeds\PostFeedsBundle\Document\ShopTagFeeds;
use PostFeeds\PostFeedsBundle\Document\ClubTagFeeds;
use PostFeeds\PostFeedsBundle\Document\UserTagFeeds;
use PostFeeds\PostFeedsBundle\Document\CommentFeeds;
use PostFeeds\PostFeedsBundle\Document\RatingFeeds;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

// service method class for seller user handling.
class PostFeedsRatingService {

    protected $em;
    protected $dm;
    protected $container;
    
    /**
     * initialize the parameters
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(EntityManager $em, DocumentManager $dm, Container $container) {
        $this->em = $em;
        $this->dm = $dm;
        $this->container = $container;
    }

   
    /**
    * Create subscription log
    * @param string $monolog_req
    * @param string $monolog_response
    */
    public function __createLog($monolog_req, $monolog_response = array())
    {
        $applane_service = $this->container->get('appalne_integration.callapplaneservice');
        $handler = $this->container->get('monolog.logger.postfeedsrating_log');
        $applane_service->writeAllLogs($handler, $monolog_req, $monolog_response);  
        return true;
    }
    
    /**
     * function for adding rate
     * @param type $post_obj
     * @param type $data
     * @param type $type
     * @return boolean
     */
    public function addRating($post_obj, $data, $type=null) {
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsRatinService] and function [addRating] with data :'.Utility::encodeData($data), array()); 
        $dm = $this->dm;
        $user_id = $data['user_id'];
        $element_id = $data['element_id'];
        $rate = (isset($data['rate'])) ? $data['rate'] : array();
        $user_service = $this->container->get('user_object.service');
        $user_info = $user_service->UserObjectService($user_id);
        $time = new \DateTime("now");
        $rated_obj = $post_obj->getRate();
        $already_rate_obj = (object)array();
        if($rated_obj) {
            foreach($rated_obj as $rated_rec) {
           
                if($rated_rec->getUserId() == $user_id) {
                    
                    $vote_sum = (int) $post_obj->getVoteSum();
                    $count = (int) $post_obj->getVoteCount();
                    $old_rating = (int) $rated_rec->getRate();
                 
                    $new_rating = (int) $rate;
                    //calculating new average and total vote sum;
                    $new_vote_sum = $vote_sum +( $new_rating - $old_rating ); 
                    
                    $new_avg = round((float) $new_vote_sum/$count, 2);                  
                    $post_obj->setVoteSum($new_vote_sum);
                    $post_obj->setAvgRating($new_avg);
                    $post_obj->setVoteCount($count);
                    $dm->persist($post_obj); //storing the post data.
                    $dm->flush();
                    $rated_rec->setRate($rate);
                    $rated_rec->setUpdatedAt($time);
                    try{ 
                        $dm->persist($rated_rec); //storing the post data.
                        $dm->flush();
                        $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsRatingService] and function [addRating] with SUCCESS'); 
                        return $post_obj;
                    }catch (Exception $ex) {
                        $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingService] and function [addRating] with exception :'.$ex->getMessage(), array());            
                        return false;
                    }

                }
            }
        }
        
        if(count($rated_obj) == 0) { 
            $count = 1 ;
            $new_avg = round($rate,2);
            $post_obj->setVoteSum( $rate );
            $post_obj->setAvgRating( $new_avg );
            $post_obj->setVoteCount( $count );
        }else {
            $vote_sum = (int) $post_obj->getVoteSum();
            $count = (int) $post_obj->getVoteCount() +1;
            $old_rating = (int) $rated_rec->getRate();
            $new_rating = (int) $rate;
            //calculating new average and total vote sum
            $new_vote_sum = $vote_sum + $new_rating; 
            $new_avg = round((float) $new_vote_sum/$count, 2);
            $post_obj->setVoteSum($new_vote_sum);
            $post_obj->setAvgRating($new_avg);
            $post_obj->setVoteCount($count);
        }
        
        $rating = new RatingFeeds();
        $rating->setUserId($user_id);
        $rating->setUserInfo($user_info);
        $rating->setItemId($element_id);
        $rating->setRate($rate);
        $rating->setCreatedAt($time);
        $rating->setUpdatedAt($time);
        $post_obj->addRate($rating);
        try{         
            $dm->persist($post_obj); //storing the post data.
            $dm->flush();
            //mark the post as is_rate true
            $this->markFeedRate($post_obj, 1); //mark post as rated           
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsRatingService] and function [addRating] with SUCCESS'); 
            return $post_obj;
        } catch (Exception $ex) {
           
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingService] and function [addRating] with exception :'.$ex->getMessage(), array());            
            return false;
        }
    }
    
    /**
     * 
     * @return type
     */
    protected function getPostFeedsService() {
        return $this->container->get('post_feeds.postFeeds');
    }
    
    /**
     * Mark post is_comment status
     * @param $element_obj
     * @param int $status
     * @return boolean
     */
    public function markFeedRate($element_obj, $status)
    {
        $dm = $this->dm;
        $element_obj->setIsRate($status);
        try{
            $dm->persist($element_obj); //storing the post data.
            $dm->flush();
        } catch (Exception $ex) {
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsRatingService] and function [markPostRate] with exception :'.$ex->getMessage(), array());            
        }
        return true;
    }
     /**
     * creating the ACL 1
     * for the entity for a user
     * @param object $sender_user
     * @param object $dashboard_comment_entity
     * @return none
     */
    public function updateAclAction($user_id, $object_entity) {
        $userManager = $this->getUserManager();
        $sender_user = $userManager->findUserBy(array('id' => $user_id));
        $aclProvider = $this->container->get('security.acl.provider');
        $objectIdentity = ObjectIdentity::fromDomainObject($object_entity);
        $acl = $aclProvider->createAcl($objectIdentity);

        // retrieving the security identity of the currently logged-in user
        $securityIdentity = UserSecurityIdentity::fromAccount($sender_user);
        $builder = new MaskBuilder();
        $builder->add('view')
                ->add('edit')
                ->add('create')
                ->add('delete');
        $mask = $builder->get();
        // grant owner access
        $acl->insertObjectAce($securityIdentity, $mask);
        $aclProvider->updateAcl($acl);
        return true;
    }
    
     /**
     * function for delete rate
     * @param type $post_obj
     * @param type $data
     * @param type $type
     * @return boolean
     */
    public function deleteRating($post_obj, $data, $type=null) {
       
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsRatinService] and function [deleteRating] with data :'.Utility::encodeData($data), array()); 
        $dm = $this->dm;
        $user_id = $data['user_id'];
        $element_id = $data['element_id'];
        $user_service = $this->container->get('user_object.service');
        $user_info = $user_service->UserObjectService($user_id);
        $rated_obj = $post_obj->getRate();
        $already_rate_obj = (object)array();
        if($rated_obj) {
            foreach($rated_obj as $rated_rec) {           
                if($rated_rec->getUserId() == $user_id) {                      
                    $rate_id = $rated_rec->getId();
                    try{               
                        $return_obj = $this->removeRate($post_obj,$rated_rec);
                        $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsRatingService] and function [deleteRating] with SUCCESS'); 
                        return $return_obj;
                    }catch (Exception $ex) {
                        $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingService] and function [deleteRating] with exception :'.$ex->getMessage(), array());            
                        return false;
                    }

                }
            }
        }//end
        
        $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsRatingService] and function [deleteRating] with SUCCESS'); 
        return $post_obj;
        
    }
    
    /**
     * remove the rate
     * @param type $elt_obj
     * @param type $ref_obj
     * @return boolean
     */
    public function removeRate($elt_obj,$ref_obj) {
        $data = array();
        $this->__createLog('Entering in class [PostFeeds\PostFeedsBundle\Service\PostFeedsRatinService] and function [removeRate] with data :'.Utility::encodeData($data), array()); 
        $dm = $this->dm;
        try{   
           
            $old_vote_sum = (int) $elt_obj->getVoteSum() ;
            $old_count = (int) $elt_obj->getVoteCount();
            $old_rating = (int) $ref_obj->getRate();
            
            $new_vote_sum = $old_vote_sum - $old_rating; 
            $new_count = $old_count-1;
            $new_avg = round((float) $new_vote_sum/$new_count, 2);
            $elt_obj->setVoteSum($new_vote_sum);
            $elt_obj->setAvgRating($new_avg);
            $elt_obj->setVoteCount($new_count);
            
            $elt_obj->removeRate($ref_obj);
            $dm->persist($elt_obj);
            $dm->flush();
            $rate_after_del = $elt_obj->getRate();
            if(count($rate_after_del) == 0) {
                $elt_obj->setIsRate(0);
                $dm->persist($elt_obj);
                $dm->flush();
            }
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Service\PostFeedsRatingService] and function [removeRate] with SUCCESS'); 
            return $elt_obj;
        }catch (Exception $ex) {
            $this->__createLog('Exiting from class [PostFeeds\PostFeedsBundle\Controller\PostFeedsRatingService] and function [removeRate] with exception :'.$ex->getMessage(), array());            
            return false;
        }
    }
    
    /**
     * 
     * @param array $users_rated
     */
    public function getRatedUsers($users_rated)
    {
       $user_ids = array();
       $postFeedsService = $this->container->get('post_feeds.postFeeds');
       foreach($users_rated as $user_rated){
           $user_ids[] = $user_rated->getUserId();
       }
       $users_info = $postFeedsService->getMultipleUserObjects(Utility::getUniqueArray($user_ids)); //get user info
       return $users_info;
    }
}
 

