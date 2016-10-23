<?php
namespace Notification\NotificationBundle\Services;

class MediaNotificationService {
    
   /**
    * Get user album media comment tagging notification
    * @param int $user_id
    * @return array
    */
    public function _getUserAlbumMediaComment($notification, $users)
    {
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;

             $media_detail = $dm
                 ->getRepository('MediaMediaBundle:UserMedia')
                 ->findOneBy(array('id'=>$item_id));

            if($media_detail)
             {

                 $media_name = $media_detail->getName();
                 $user_id = $media_detail->getUserid();
                 $album_id = $media_detail->getAlbumid();

                 $user_album = $dm
                   ->getRepository('MediaMediaBundle:UserAlbum')
                   ->find($album_id);
                 $photo_info['albumId'] = $user_album->getId();
                 $photo_info['albumTitle'] = $user_album->getAlbumName();
                 $photo_info['userId'] = $user_album->getUserId();
                 $photo_info['albumDesc'] = $user_album->getAlbumDesc();
                 $photo_info["photoId"] = $item_id;
                 $photo_info["owner_id"] = $user_album->getUserId();
                 $photo_info["album_type"] = $message_type;
                 $photo_info['comment_id'] = $comment_id;

                 $response = array('notification_id'=>$notification_id, 
                     'notification_from'=>$notification_from,
                     'message_type' =>$message_type,
                     'message'=>$message,
                     'message_status'=>$message_status,
                     'post_info'=>$photo_info,
                     'is_read'=>(int)$notification->getIsRead(),
                     'create_date'=>$notification->getDate()
                     );
             }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }
   
    /**
     * Get club album media comment notification
     * @param int $user_id
     * @return array
     */
    public function _getClubAlbumMediaComment($notification, $users)
    {
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;

             //get media details
             $media_detail = $dm
                 ->getRepository('UserManagerSonataUserBundle:GroupMedia')
                 ->findOneBy(array('id'=>$item_id));

            if($media_detail)
             {
                 $media_name = $media_detail->getMediaName();
                 $group_id = $media_detail->getGroupId();
                 $album_id = $media_detail->getAlbumid(); 
                 $_club = $dm->getRepository('UserManagerSonataUserBundle:Group')
                         ->find($group_id);
                 $club_album = $dm
                   ->getRepository('UserManagerSonataUserBundle:GroupAlbum')
                   ->find($album_id);
                 $photo_info['albumId'] = $club_album->getId();
                 $photo_info['albumTitle'] = $club_album->getAlbumName();
                 $photo_info['clubId'] = $club_album->getGroupId();
                 $photo_info['owner_id'] = $club_album->getGroupId();
                 $photo_info['album_type'] = $message_type;
                 $photo_info['albumDesc'] = $club_album->getAlbumDesc();
                 $photo_info["status"]=$_club->getGroupStatus();
                 $photo_info["photoId"] = $item_id;
                 $photo_info["clubName"]=$_club->getTitle();
                 $photo_info["comment_id"]= $comment_id;

                 $response = array('notification_id'=>$notification_id, 
                     'notification_from'=>$notification_from,
                     'message_type' =>$message_type,
                     'message'=>$message,
                     'message_status'=>$message_status,
                     'post_info'=>$photo_info,
                     'is_read'=>(int)$notification->getIsRead()
                     ,'create_date'=>$notification->getDate());
             }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }

    /**
     * Get store album media comment notification
     * @param int $user_id
     * @return array
     */
    public function _getStoreAlbumMediaComment($notification, $users)
    {
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
            $em = $this->_getDoctrineManager();
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;

             //get media details
             $media_detail = $em
                 ->getRepository('StoreManagerStoreBundle:StoreMedia')
                 ->find($item_id);

             if($media_detail)
              {
                  $photoId = $media_detail->getId();
                  $media_name = $media_detail->getImageName();
                  $store_id = $media_detail->getStoreId();
                  $store_album_id = $media_detail->getAlbumId();

                 $store = $em
                       ->getRepository('StoreManagerStoreBundle:Store')
                       ->findOneBy(array('id' => $store_id));
                 $storeName = $store->getName();
                 $storeName = !empty($storeName) ? $storeName : $store->getBusinessName();
                 
                  $shop_album = $em
                    ->getRepository('StoreManagerStoreBundle:Storealbum')
                    ->find($store_album_id);

                  $photo_info['albumId'] = $shop_album->getId();
                  $photo_info['albumTitle'] = $shop_album->getStoreAlbumName();
                  $photo_info['shopId'] = $shop_album->getStoreId();
                  $photo_info['shopName'] = $storeName;
                  $photo_info['photoId']=$photoId;
                  $photo_info['albumDesc'] = $shop_album->getStoreAlbumDesc();
                  $photo_info['owner_id'] = $shop_album->getStoreId();
                  $photo_info['album_type']=$message_type;                     
                  $photo_info['comment_id']=$comment_id;  
                  $response = array('notification_id'=>$notification_id, 
                      'notification_from'=>$notification_from,
                      'message_type' =>$message_type,
                      'message'=>$message,
                      'message_status'=>$message_status,
                      'post_info'=>$photo_info,
                      'is_read'=>(int)$notification->getIsRead(),
                      'create_date'=>$notification->getDate());
              }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }

    /**
     * Get user album comment notification
     * @param int $user_id
     * @return array
     */
    public function _getUserAlbumComment($notification, $users)
    {
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;

             //get media details
             $media_detail = $dm
                   ->getRepository('MediaMediaBundle:UserAlbum')
                   ->find($item_id);

             $media_details = array();
             if($media_detail){

                     $media_details['albumId'] = $media_detail->getId();
                     $media_details['albumTitle'] = $media_detail->getAlbumName();
                     $media_details['userId'] = $media_detail->getUserId();
                     $media_details['albumDesc'] = $media_detail->getAlbumDesc();
                     $media_details['album_type'] = $message_type;
                     $media_details['owner_id'] = $media_detail->getUserId();
                     $media_details['comment_id'] = $comment_id;
                     $response = array('notification_id'=>$notification_id, 
                         'notification_from'=>$notification_from,
                         'message_type' =>$message_type,
                         'message'=>$message,
                         'message_status'=>$message_status,
                         'post_info'=>$media_details,
                         'is_read'=>(int)$notification->getIsRead(),
                         'create_date'=>$notification->getDate());
             }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }

    /**
     * Get club album comment notification
     * @param int $user_id
     * @return array
     */
    public function _getClubAlbumComment($notification, $users)
    {
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;

             //get media details
             $media_detail = $dm
                   ->getRepository('UserManagerSonataUserBundle:GroupAlbum')
                   ->find($item_id);

             if($media_detail){
                 $_club = $dm->getRepository('UserManagerSonataUserBundle:Group')
                     ->find($media_detail->getGroupId());
                     $media_details = array();
                     $media_details['albumId'] = $media_detail->getId();
                     $media_details['albumTitle'] = $media_detail->getAlbumName();
                     $media_details['clubId'] = $media_detail->getGroupId();
                     $media_details['albumDesc'] = $media_detail->getAlbumDesc();
                     $media_details["status"]=$_club->getGroupStatus();
                     $media_details["clubName"]=$_club->getTitle();
                     $media_details["comment_id"]=$comment_id;

                     $response = array('notification_id'=>$notification_id, 
                         'notification_from'=>$notification_from,
                         'message_type' =>$message_type,
                         'message'=>$message,
                         'message_status'=>$message_status,
                         'post_info'=>$media_details,
                         'is_read'=>(int)$notification->getIsRead(),
                         'create_date'=>$notification->getDate());
             }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }

    /**
     * Get store album comment notification
     * @param int $user_id
     * @return array
     */
    public function _getStoreAlbumComment($notification, $users)
    {
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
            $em = $this->_getDoctrineManager();
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;

             //get media details
             $media_detail = $em
                   ->getRepository('StoreManagerStoreBundle:Storealbum')
                   ->find($item_id);

             if($media_detail){
                 
                 $storeId = $media_detail->getStoreId();
                 $store = $em
                       ->getRepository('StoreManagerStoreBundle:Store')
                       ->findOneBy(array('id' => $storeId));
                 $storeName = $store->getName();
                 $storeName = !empty($storeName) ? $storeName : $store->getBusinessName();
                 
                 $media_details = array();
                 $media_details['albumId'] = $media_detail->getId();
                 $media_details['albumTitle'] = $media_detail->getStoreAlbumName();
                 $media_details['shopId'] = $storeId;
                 $media_details['shopName'] = $storeName;
                 $media_details['albumDesc'] = $media_detail->getStoreAlbumDesc();
                 $media_details['owner_id'] = $media_detail->getStoreId();
                 $media_details['album_type'] = $message_type;
                 $media_details['comment_id']=$comment_id;
                 $response = array('notification_id'=>$notification_id, 
                     'notification_from'=>$notification_from,
                     'message_type' =>$message_type,
                     'message'=>$message,
                     'message_status'=>$message_status,
                     'post_info'=>$media_details,
                     'is_read'=>(int)$notification->getIsRead(),
                     'create_date'=>$notification->getDate());
             }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }

    /**
     * Get store album comment rate notification
     * @param int $user_id
     * @return array
     */
    public function _getStoreAlbumCommentRate($notification, $users)
    {
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
            $em = $this->_getDoctrineManager();
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;

             //get media details
             $media_detail = $em
                   ->getRepository('StoreManagerStoreBundle:Storealbum')
                   ->find($item_id);

             if($media_detail){
                 $storeId = $media_detail->getStoreId();
                 $store = $em
                       ->getRepository('StoreManagerStoreBundle:Store')
                       ->findOneBy(array('id' => $storeId));
                 $storeName = $store->getName();
                 $storeName = !empty($storeName) ? $storeName : $store->getBusinessName();
                 
                 $media_details = array();
                 $media_details['albumId'] = $media_detail->getId();
                 $media_details['albumTitle'] = $media_detail->getStoreAlbumName();
                 $media_details['shopId'] = $storeId;
                 $media_details['shopName'] = $storeName;
                 $media_details['albumDesc'] = $media_detail->getStoreAlbumDesc();
                 $media_details['owner_id'] = $media_detail->getStoreId();
                 $media_details['album_type'] = $message_type;
                 $media_details['rate']=$message;
                 $media_details['comment_id']=$comment_id;
                 $response = array('notification_id'=>$notification_id, 
                     'notification_from'=>$notification_from,
                     'message_type' =>$message_type,
                     'message'=>"rate",
                     'message_status'=>$message_status,
                     'post_info'=>$media_details,
                     'is_read'=>(int)$notification->getIsRead(),
                     'create_date'=>$notification->getDate());
             }
        }catch(\Exception $e){
            print_r($e->getMessage());
        }
        return $response;
    }

    /**
     * Get user album comment rate notification
     * @param int $user_id
     * @return array
     */
    public function _getUserAlbumCommentRate($notification, $users)
    {
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
            $em = $this->_getDoctrineManager();
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;

             //get media details
             $media_detail = $dm
                   ->getRepository('MediaMediaBundle:UserAlbum')
                   ->find($item_id);

             $media_details = array();
             if($media_detail){

                     $media_details['albumId'] = $media_detail->getId();
                     $media_details['albumTitle'] = $media_detail->getAlbumName();
                     $media_details['userId'] = $media_detail->getUserId();
                     $media_details['albumDesc'] = $media_detail->getAlbumDesc();
                     $media_details['rate'] = $message;
                     $media_details['album_type'] = $message_type;
                     $media_details['owner_id'] = $media_detail->getUserId();
                     $media_details['comment_id'] = $comment_id;
                     $response = array('notification_id'=>$notification_id, 
                         'notification_from'=>$notification_from,
                         'message_type' =>$message_type,
                         'message'=>"rate",
                         'message_status'=>$message_status,
                         'post_info'=>$media_details,
                         'is_read'=>(int)$notification->getIsRead(),
                         'create_date'=>$notification->getDate());
             }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }

    /**
     * Get club album comment rate notification
     * @param int $user_id
     * @return array
     */
    public function _getClubAlbumCommentRate($notification, $users)
    {
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
            $em = $this->_getDoctrineManager();
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;

             //get media details
             $media_detail = $dm
                   ->getRepository('UserManagerSonataUserBundle:GroupAlbum')
                   ->find($item_id);

             if($media_detail){
                 $_club = $dm->getRepository('UserManagerSonataUserBundle:Group')
                     ->find($media_detail->getGroupId());
                     $media_details = array();
                     $media_details['albumId'] = $media_detail->getId();
                     $media_details['albumTitle'] = $media_detail->getAlbumName();
                     $media_details['clubId'] = $media_detail->getGroupId();
                     $media_details['albumDesc'] = $media_detail->getAlbumDesc();
                     $media_details['rate']=$message;
                     $media_details["status"]=$_club->getGroupStatus();
                     $media_details["clubName"]=$_club->getTitle();
                     $media_details["comment_id"]=$comment_id;

                     $response = array('notification_id'=>$notification_id, 
                         'notification_from'=>$notification_from,
                         'message_type' =>$message_type,
                         'message'=>"rate",
                         'message_status'=>$message_status,
                         'post_info'=>$media_details,
                         'is_read'=>(int)$notification->getIsRead(),
                         'create_date'=>$notification->getDate());
             }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }

    /**
     * Get store album media comment rate notification
     * @param int $user_id
     * @return array
     */
    public function _getStoreAlbumMediaCommentRate($notification, $users)
    {
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
            $em = $this->_getDoctrineManager();
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;

             //get media details
             $media_detail = $em
                 ->getRepository('StoreManagerStoreBundle:StoreMedia')
                 ->find($item_id);

             if($media_detail)
              {
                  $photoId = $media_detail->getId();
                  $media_name = $media_detail->getImageName();
                  $store_id = $media_detail->getStoreId();
                  $store_album_id = $media_detail->getAlbumId();

                  $store = $em
                       ->getRepository('StoreManagerStoreBundle:Store')
                       ->findOneBy(array('id' => $store_id));
                 $storeName = $store->getName();
                 $storeName = !empty($storeName) ? $storeName : $store->getBusinessName();
                 
                  $shop_album = $em
                    ->getRepository('StoreManagerStoreBundle:Storealbum')
                    ->find($store_album_id);

                  $photo_info['albumId'] = $shop_album->getId();
                  $photo_info['albumTitle'] = $shop_album->getStoreAlbumName();
                  $photo_info['shopId'] = $shop_album->getStoreId();
                  $photo_info['shopName'] = $storeName;
                  $photo_info['photoId']=$photoId;
                  $photo_info['albumDesc'] = $shop_album->getStoreAlbumDesc();
                  $photo_info['owner_id'] = $shop_album->getStoreId();
                  $photo_info['rate']=$message;
                  $photo_info['album_type']=$message_type; 
                  $photo_info['comment_id']=$comment_id; 
                  $response = array('notification_id'=>$notification_id, 
                      'notification_from'=>$notification_from,
                      'message_type' =>$message_type,
                      'message'=>"rate",
                      'message_status'=>$message_status,
                      'post_info'=>$photo_info,
                      'is_read'=>(int)$notification->getIsRead(),
                      'create_date'=>$notification->getDate());
              }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }

    /**
     * Get user album media comment rate notification
     * @param int $user_id
     * @return array
     */
    public function _getUserAlbumMediaCommentRate($notification, $users)
    {
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
            $em = $this->_getDoctrineManager();
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;

             //get media details
             $media_detail = $dm
                 ->getRepository('MediaMediaBundle:UserMedia')
                 ->findOneBy(array('id'=>$item_id));


            if($media_detail)
             {

                 $media_name = $media_detail->getName();
                 $user_id = $media_detail->getUserid();
                 $album_id = $media_detail->getAlbumid();

                 $user_album = $dm
                   ->getRepository('MediaMediaBundle:UserAlbum')
                   ->find($album_id);
                 $photo_info['albumId'] = $user_album->getId();
                 $photo_info['albumTitle'] = $user_album->getAlbumName();
                 $photo_info['userId'] = $user_album->getUserId();
                 $photo_info['albumDesc'] = $user_album->getAlbumDesc();
                 $photo_info["photoId"] = $item_id;
                 $photo_info["owner_id"] = $user_album->getUserId();
                 $photo_info["album_type"] = $message_type;
                 $photo_info["comment_id"] = $comment_id;

                 $photo_info['rate'] = $message;
                 $response = array('notification_id'=>$notification_id, 
                     'notification_from'=>$notification_from,
                     'message_type' =>$message_type,
                     'message'=>"rate",
                     'message_status'=>$message_status,
                     'post_info'=>$photo_info,
                     'is_read'=>(int)$notification->getIsRead(),
                     'create_date'=>$notification->getDate());
             }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }

    /**
     * Get club album media comment rate notification
     * @param int $user_id
     * @return array
     */
    public function _getClubAlbumMediaCommentRate($notification, $users)
    {
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
            $em = $this->_getDoctrineManager();
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;

             //get media details
             $media_detail = $dm
                 ->getRepository('UserManagerSonataUserBundle:GroupMedia')
                 ->findOneBy(array('id'=>$item_id));

            if($media_detail)
             {
                 $media_name = $media_detail->getMediaName();
                 $group_id = $media_detail->getGroupId();
                 $album_id = $media_detail->getAlbumid(); 
                 $_club = $dm->getRepository('UserManagerSonataUserBundle:Group')
                         ->find($group_id);
                 $club_album = $dm
                   ->getRepository('UserManagerSonataUserBundle:GroupAlbum')
                   ->find($album_id);
                 $photo_info['albumId'] = $club_album->getId();
                 $photo_info['albumTitle'] = $club_album->getAlbumName();
                 $photo_info['clubId'] = $club_album->getGroupId();
                 $photo_info['owner_id'] = $club_album->getGroupId();
                 $photo_info['album_type'] = $message_type;
                 $photo_info['albumDesc'] = $club_album->getAlbumDesc();
                 $photo_info['rate']=$message;
                 $photo_info["status"]=$_club->getGroupStatus();
                 $photo_info["photoId"] = $item_id;
                 $photo_info["clubName"]=$_club->getTitle();
                 $photo_info["comment_id"]=$comment_id;

                 $response = array('notification_id'=>$notification_id, 
                     'notification_from'=>$notification_from,
                     'message_type' =>$message_type,
                     'message'=>"rate",
                     'message_status'=>$message_status,
                     'post_info'=>$photo_info,
                     'is_read'=>(int)$notification->getIsRead()
                     ,'create_date'=>$notification->getDate());
             }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }
    
    private function getContainer(){
        global $kernel;
        return $kernel->getContainer();
    }
    
    private function _getDoctrineManager(){
        return $this->getContainer()->get('doctrine')->getManager();
    }
    
    public function _getStorePostMediaComment($notification, $users){
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
            $em = $this->_getDoctrineManager();
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;
             $store_id = isset($info['store_id']) ? $info['store_id'] : 0;
            $post_id = isset($info['post_id']) ? $info['post_id'] : 0;
                 
             //get media details
             $media_detail = $dm
                 ->getRepository('StoreManagerPostBundle:StorePostsMedia')
                 ->findOneBy(array('id'=>$item_id));

            if($media_detail)
             {
                 $media_name = $media_detail->getMediaName();
                 $photo_info['postId'] = $post_id;
                 $photo_info['albumId'] = 0;
                 $photo_info['storeId'] = $store_id;
                 $photo_info["photoId"] = $item_id;
                 $photo_info["comment_id"]=$comment_id;
                 $photo_info["photoName"]=$media_name;

                 $response = array('notification_id'=>$notification_id, 
                     'notification_from'=>$notification_from,
                     'message_type' =>$message_type,
                     'message'=>$message,
                     'message_status'=>$message_status,
                     'post_info'=>$photo_info,
                     'is_read'=>(int)$notification->getIsRead()
                     ,'create_date'=>$notification->getDate());
             }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }
    
    /**
     * Get store post media comment rate notification
     * @param int $user_id
     * @return array
     */
    public function _getStorePostMediaCommentRate($notification, $users)
    {
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
            $em = $this->_getDoctrineManager();
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;

             //get media details
             $media_detail = $em
                 ->getRepository('StoreManagerStoreBundle:StorePosts')
                 ->find($item_id);

             if($media_detail)
              {
                  $store_id = $media_detail->getStoreId();

                  $response = array('notification_id'=>$notification_id, 
                      'notification_from'=>$notification_from,
                      'message_type' =>$message_type,
                      'message'=>"rate",
                      'message_status'=>$message_status,
                      'post_info'=>array(
                          'store_id'=>$store_id,
                          'comment_id'=>$comment_id,
                          'post_id'=>$item_id,
                          'rate'=>$message
                      ),
                      'is_read'=>(int)$notification->getIsRead(),
                      'create_date'=>$notification->getDate());
              }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }
    
    public function _getDashboardPostMediaComment($notification, $users){
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
            $em = $this->_getDoctrineManager();
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;
                 
             //get media details
             $media_detail = $dm
                 ->getRepository('DashboardManagerBundle:DashboardPost')
                 ->findOneBy(array('id'=>$item_id));

            if($media_detail)
             {
                 $response = array('notification_id'=>$notification_id, 
                     'notification_from'=>$notification_from,
                     'message_type' =>$message_type,
                     'message'=>$message,
                     'message_status'=>$message_status,
                     'post_info'=>array(
                         'comment_id'=>$comment_id,
                         'post_id'=>$item_id
                     ),
                     'is_read'=>(int)$notification->getIsRead()
                     ,'create_date'=>$notification->getDate());
             }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }
    
    /**
     * Get store post media comment rate notification
     * @param int $user_id
     * @return array
     */
    public function _getDashboardPostMediaCommentRate($notification, $users)
    {
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
            $em = $this->_getDoctrineManager();
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;
      
             //get media details
             $media_detail = $dm
                 ->getRepository('DashboardManagerBundle:DashboardPostMedia')
                 ->find($item_id);
            
             if($media_detail)
              {
                  $post_id = $media_detail->getPostId();  
                  $response = array('notification_id'=>$notification_id, 
                      'notification_from'=>$notification_from,
                      'message_type' =>$message_type,
                      'message'=>"rate",
                      'message_status'=>$message_status,
                      'post_info'=>array(
                          'comment_id'=>$comment_id,
                          'post_id'=>$post_id,
                          'rate'=>$message
                      ),
                      'is_read'=>(int)$notification->getIsRead(),
                      'create_date'=>$notification->getDate());
              }
        }catch(\Exception $e){
           // print_r($e->getMessage());
        }
        return $response;
    }
    
    /**
     * Get store post media rate notification
     * @param int $user_id
     * @return array
     */
    public function _getStorePostMediaRate($notification, $users)
    {
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
            $em = $this->_getDoctrineManager();
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();

             //get media details
             $media_detail = $em
                 ->getRepository('StoreManagerStoreBundle:StorePosts')
                 ->find($item_id);

             if($media_detail)
              {
                  $store_id = $media_detail->getStoreId();

                  $response = array('notification_id'=>$notification_id, 
                      'notification_from'=>$notification_from,
                      'message_type' =>$message_type,
                      'message'=>"rate",
                      'message_status'=>$message_status,
                      'post_info'=>array(
                          'store_id'=>$store_id,
                          'post_id'=>$item_id,
                          'rate'=>$message
                      ),
                      'is_read'=>(int)$notification->getIsRead(),
                      'create_date'=>$notification->getDate());
              }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }
    
    public function _getClubPostMediaComment($notification, $users){
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
            $em = $this->_getDoctrineManager();
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;
             $club_id = isset($info['club_id']) ? $info['club_id'] : 0;
            $post_id = isset($info['post_id']) ? $info['post_id'] : 0;
                 
             //get media details
             $media_detail = $dm
                 ->getRepository('PostPostBundle:PostMedia')
                 ->findOneBy(array('id'=>$item_id));
            $_club = $dm
                 ->getRepository('UserManagerSonataUserBundle:Group')
                 ->findOneBy(array('id'=>$club_id));
            if($media_detail && $_club )
             {

                 $media_name = $media_detail->getMediaName();
                 $photo_info['postId'] = $post_id;
                 $photo_info['albumId'] = 0;
                 $photo_info['clubId'] = $club_id;
                 $photo_info["photoId"] = $item_id;
                 $photo_info["comment_id"]=$comment_id;
                 $photo_info["photoName"]=$media_name;
                 $photo_info['clubStatus']= $_club->getGroupStatus();

                 $response = array('notification_id'=>$notification_id, 
                     'notification_from'=>$notification_from,
                     'message_type' =>$message_type,
                     'message'=>$message,
                     'message_status'=>$message_status,
                     'post_info'=>$photo_info,
                     'is_read'=>(int)$notification->getIsRead()
                     ,'create_date'=>$notification->getDate());
             }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }
    
    /**
     * Get store post media comment rate notification
     * @param int $user_id
     * @return array
     */
    public function _getClubPostMediaCommentRate($notification, $users)
    {
        $response = array();
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
            $em = $this->_getDoctrineManager();
             $from      = $notification->getFrom();
             $notification_id = $notification->getId();
             $notification_from = isset($users[$from]) ? $users[$from] : array();
             $message_type = $notification->getMessageType();
             $message_status = $notification->getMessageStatus();
             $message = $notification->getMessage();
             $item_id = $notification->getItemId();
             $info = $notification->getInfo();
             $comment_id = isset($info['comment_id']) ? $info['comment_id'] : 0;

             //get media details
             $detail = $dm
                 ->getRepository('PostPostBundle:Post')
                 ->find($item_id);

             if($detail)
              {
                  $club_id = $detail->getPostGid();
                  $_club = $dm
                    ->getRepository('UserManagerSonataUserBundle:Group')
                    ->findOneBy(array('id'=>$club_id));
                  
                  $response = array('notification_id'=>$notification_id, 
                      'notification_from'=>$notification_from,
                      'message_type' =>$message_type,
                      'message'=>"rate",
                      'message_status'=>$message_status,
                      'post_info'=>array(
                          'club_id'=>$club_id,
                          'comment_id'=>$comment_id,
                          'post_id'=>$item_id,
                          'rate'=>$message,
                          'clubStatus'=>$_club->getGroupStatus()
                      ),
                      'is_read'=>(int)$notification->getIsRead(),
                      'create_date'=>$notification->getDate());
              }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }
        return $response;
    }
    
    public function _getTaggedInClubPost($notification, $users){

        $response = array(); 
        
        try{
            $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
            $notification_id = $notification->getId();
            $from = $notification->getFrom();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            //get $from user object
            $message_status = $notification->getMessageStatus();
            $message_type = $notification->getMessageType();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            //get media details
             $detail = $dm
                 ->getRepository('PostPostBundle:Post')
                 ->find($item_id);
             if($detail)
              {
                  $club_id = $detail->getPostGid();
                  $_club = $dm
                    ->getRepository('UserManagerSonataUserBundle:Group')
                    ->findOneBy(array('id'=>$club_id));
                  
                  $response = array('notification_id'=>$notification_id, 
                      'notification_from'=>$notification_from,
                      'message_type' =>$message_type,
                      'message'=>$message,
                      'message_status'=>$message_status,
                      'post_info'=>array(
                          'club_id'=>$club_id,
                          'post_id'=>$item_id,
                          'clubStatus'=>$_club->getGroupStatus()
                      ),
                      'is_read'=>(int)$notification->getIsRead(),
                      'create_date'=>$notification->getDate());
              }
        }catch(\Exception $e){
            //print_r($e->getMessage());
        }

        return $response;
   }
   
   /**
    * Get shop album image rating notification
    * @param int $user_id
    * @return array
    */
   public function _getTaggedInShopAlbumImage($notification, $users)
   {
       $response = array();
       try{
           $dm = $this->getContainer()->get('doctrine.odm.mongodb.document_manager');
           $em = $this->_getDoctrineManager();
            $from      = $notification->getFrom();
            $notification_id = $notification->getId();
            $notification_from = isset($users[$from]) ? $users[$from] : array();
            $message_type = $notification->getMessageType();
            $message_status = $notification->getMessageStatus();
            $message = $notification->getMessage();
            $item_id = $notification->getItemId();
            //get media details
            $media_detail = $em
                ->getRepository('StoreManagerStoreBundle:StoreMedia')
                ->find($item_id);
          
            if($media_detail)
             {
                 $photoId = $media_detail->getId();
                 $media_name = $media_detail->getImageName();
                 $store_id = $media_detail->getStoreId();
                 $store_album_id = $media_detail->getAlbumId();

                 $shop_album = $em
                   ->getRepository('StoreManagerStoreBundle:Storealbum')
                   ->find($store_album_id);

                 $photo_info['albumId'] = $shop_album->getId();
                 $photo_info['albumTitle'] = $shop_album->getStoreAlbumName();
                 $photo_info['shopId'] = $shop_album->getStoreId();
                 $photo_info['photoId']=$photoId;
                 $photo_info['albumDesc'] = $shop_album->getStoreAlbumDesc();
                 $photo_info['owner_id'] = $shop_album->getStoreId();
                 $photo_info['album_type']=$message_type;                     
                 $response = array('notification_id'=>$notification_id, 
                     'notification_from'=>$notification_from,
                     'message_type' =>$message_type,
                     'message'=>$message,
                     'message_status'=>$message_status,
                     'post_info'=>$photo_info,
                     'is_read'=>(int)$notification->getIsRead(),
                     'create_date'=>$notification->getDate());
             }
        }catch(\Exception $e){
           //print_r($e->getMessage());
        }
        return $response;
   }
}
