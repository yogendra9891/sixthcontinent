<?php

namespace PostFeeds\PostFeedsBundle\Document;



/**
 * PostFeeds\PostFeedsBundle\Document\SocialProjectCoverImg
 */
class SocialProjectCoverImg
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $project_id
     */
    protected $project_id;

    /**
     * @var string $image_name
     */
    protected $image_name;

    /**
     * @var string $x-cor
     */
    protected $x;

    /**
     * @var string $y-cor
     */
    protected $y;


    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set projectId
     *
     * @param string $projectId
     * @return self
     */
    public function setProjectId($projectId)
    {
        $this->project_id = $projectId;
        return $this;
    }

    /**
     * Get projectId
     *
     * @return string $projectId
     */
    public function getProjectId()
    {
        return $this->project_id;
    }

    /**
     * Set imageName
     *
     * @param string $imageName
     * @return self
     */
    public function setImageName($imageName)
    {
        $this->image_name = $imageName;
        return $this;
    }

    /**
     * Get imageName
     *
     * @return string $imageName
     */
    public function getImageName()
    {
        return $this->image_name;
    }

    /**
     * Set x
     *
     * @param string $x
     * @return self
     */
    public function setX($x)
    {
        $this->x = $x;
        return $this;
    }

    /**
     * Get x
     *
     * @return string $x
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * Set y
     *
     * @param string $y
     * @return self
     */
    public function setY($y)
    {
        $this->y = $y;
        return $this;
    }

    /**
     * Get y
     *
     * @return string $y
     */
    public function getY()
    {
        return $this->y;
    }
    
    /**
     * upload the image for store profile
     * @param int $project_id
     * @param type $file_name
     */
    public function socialCoverImageUpload($file_name)
    {
       // create directory having title of $project_id. 
       // since post id is string so directory name would be string type
        $source= $_FILES['cover_file']['tmp_name'];
        $pre_upload_media_dir =  __DIR__."/../../../../web/uploads/documents/socialproject/original/";
       $upload_media_dir = $pre_upload_media_dir;
        
        // if upload media dir exits then just upload the media otherwise make
        // the folder then upload the media
        if (file_exists($upload_media_dir) && is_dir($upload_media_dir)) { 
          move_uploaded_file($source, $upload_media_dir .$file_name);
          
        }
        else { 
            $destination = \mkdir($pre_upload_media_dir, 0777, true);
            $upload_media_dir = $pre_upload_media_dir;
            move_uploaded_file($source, $upload_media_dir .$file_name);
        }
    }
    public function __construct()
    {
        $this->image_name = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add imageName
     *
     * @param PostFeeds\PostFeedsBundle\Document\MediaFeeds $imageName
     */
    public function addImageName(\PostFeeds\PostFeedsBundle\Document\MediaFeeds $imageName)
    {
        $this->image_name[] = $imageName;
    }

    /**
     * Remove imageName
     *
     * @param PostFeeds\PostFeedsBundle\Document\MediaFeeds $imageName
     */
    public function removeImageName(\PostFeeds\PostFeedsBundle\Document\MediaFeeds $imageName)
    {
        $this->image_name->removeElement($imageName);
    }
}
