<?php

namespace StoreManager\StoreBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * StoreMedia
 */
class StoreMedia {

    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $storeId;
    
    /**
     * @var integer
     */
    private $albumId;
    
    /**
     * @var integer
     */
    private $mediaStatus = 1;

    /**
     * @var string
     */
    private $imageName;
    

    /**
     * @var boolean
     */
    private $isFeatured;

    /**
     * @var \DateTime
     */

    private $createdAt;

     /**
     * @var string
     */
    private $x;
     /**
     * @var string
     */
    private $y;
    
    private $temp;
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set storeId
     *
     * @param integer $storeId
     * @return StoreMedia
     */
    public function setStoreId($storeId) {
        $this->storeId = $storeId;

        return $this;
    }
    
    /**
     * Get storeId
     *
     * @return integer 
     */
    public function getStoreId() {
        return $this->storeId;
    }
    
    /**
     * Get albumId
     *
     * @return integer 
     */
    public function getAlbumId() {
        return $this->albumId;
    }

     /**
     * Set albumId
     *
     * @param integer $albumId
     * @return StoreMedia
     */
    public function setAlbumId($albumId) {
        $this->albumId = $albumId;
        return $this;
    }

    /**
     * Set imageName
     *
     * @param string $imageName
     * @return StoreMedia
     */
    public function setImageName($imageName) {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * Get imageName
     *
     * @return string 
     */
    public function getImageName() {
        return $this->imageName;
    }

    /**
     * Set isFeatured
     *
     * @param boolean $isFeatured
     * @return StoreMedia
     */
    public function setIsFeatured($isFeatured) {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    /**
     * Get isFeatured
     *
     * @return boolean 
     */
    public function getIsFeatured() {
        return $this->isFeatured;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return StoreMedia
     */
    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * Get mediaStatus
     *
     * @return integer 
     */
    public function getMediaStatus() {
        return $this->mediaStatus;
    }

     /**
     * Set mediaStatus
     *
     * @param integer $mediaStatus
     * @return StoreMedia
     */
    public function setMediaStatus($mediaStatus) {
        $this->mediaStatus = $mediaStatus;
        return $this;
    }
    
        /**
     * Set x
     *
     * @param string $x
     * @return StoreMedia
     */
    public function setX($x) {
        $this->x = $x;

        return $this;
    }

    /**
     * Get x
     *
     * @return string 
     */
    public function getX() {
        return $this->x;
    }
     /**
     * Set y
     *
     * @param string $y
     * @return StoreMedia
     */
    public function setY($y) {
        $this->y = $y;

        return $this;
    }

    /**
     * Get y
     *
     * @return string 
     */
    public function getY() {
        return $this->y;
    }
    
    /**
     * @Assert\File(maxSize="60000000000000000000000000000000")
     */
    private $file;

    /**
     * Sets file.
     *
     * @param UploadedFile $file
     */
    public function setFile(UploadedFile $file = null) {
        $this->file = $file;
        // check if we have an old image path
        if (isset($this->path)) {
            // store the old name to delete after the update
            $this->temp = $this->path;
            $this->path = null;
        } else {
            $this->path = 'initial';
        }
    }
    
    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        if (null !== $this->getFile()) {
            // do whatever you want to generate a unique name
            $filename = sha1(uniqid(mt_rand(), true));
            $this->path = $filename.'.'.$this->getFile()->guessExtension();
        }
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $path;
    
    public function getAbsolutePath() {
        return null === $this->path ? null : $this->getUploadRootDir() . '/' . $this->path;
    }

    public function getWebPath() {
        return null === $this->path ? null : $this->getUploadDir() . '/' . $this->path;
    }

    protected function getUploadRootDir() {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    protected function getUploadDir() {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads/documents';
    }
    
     public function upload($storeId, $key, $file_name)
    { 
       // create directory having title of postId. 
       // since post id is string so directory name would be string type
        $source= $_FILES['storefile']['tmp_name'][$key];
        $pre_upload_media_dir =  __DIR__."/../../../../web/uploads/documents/store/gallery";
        $upload_media_dir = $pre_upload_media_dir.'/'.$storeId.'/';
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) { 
          move_uploaded_file($source, $upload_media_dir . $file_name);
        } 
        else { 
            $destination = \mkdir($upload_media_dir, 0777, true);
            $upload_media_dir = $upload_media_dir.'/';
            move_uploaded_file($source, $upload_media_dir . $file_name);
            
        }
    }
    
    
     public function albummediaupload($store_id,$key,$file_name,$album_id)
    {
        $source= $_FILES['store_media']['tmp_name'][$key];
        //$file_name = $_FILES['user_media']['name'][$key];
        $pre_upload_media_dir =  __DIR__."/../../../../web/uploads/documents/stores/gallery/";
        if($album_id){
           $upload_media_dir = $pre_upload_media_dir.$store_id.'/original/'.$album_id.'/';  
        } else {
         $upload_media_dir = $pre_upload_media_dir.$store_id.'/original/';
        }
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) { 
          move_uploaded_file($source, $upload_media_dir .$file_name);
        } 
        else { 
            $destination = \mkdir($pre_upload_media_dir.$store_id.'/original/'.$album_id, 0777, true);
            move_uploaded_file($source, $upload_media_dir .$file_name);
        }
    }

    /**
     * upload the image for store profile
     * @param int $store_id
     * @param type $file_name
     */
    public function stroeProfileImageUpload($store_id,$file_name, $isArrayCheck=false)
    {
        if($isArrayCheck==true){
            $source= gettype($_FILES['store_media']['tmp_name'])=='array' ? $_FILES['store_media']['tmp_name'][0] : $_FILES['store_media']['tmp_name'];
        }else{
           $source =  $_FILES['store_media']['tmp_name'];
        }
        //$file_name = $_FILES['user_media']['name'][$key];
        $pre_upload_media_dir =  __DIR__."/../../../../web/uploads/documents/stores/gallery/";
        $upload_media_dir = $pre_upload_media_dir.$store_id.'/original/';
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) { 
          move_uploaded_file($source, $upload_media_dir .$file_name);
        } else { 
            $destination = \mkdir($pre_upload_media_dir.$store_id.'/original/', 0777, true);
            move_uploaded_file($source, $upload_media_dir .$file_name);
        }
    }
    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if ($file = $this->getAbsolutePath()) {
            unlink($file);
        }
    }

    /**
     * @var int
     */
    private $image_type = 0;


    /**
     * Set image_type
     *
     * @param integer $imageType
     * @return StoreMedia
     */
    public function setImageType($imageType)
    {
        $this->image_type = $imageType;
    
        return $this;
    }

    /**
     * Get image_type
     *
     * @return integer 
     */
    public function getImageType()
    {
        return $this->image_type;
    }
}