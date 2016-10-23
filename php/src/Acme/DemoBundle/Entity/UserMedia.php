<?php

namespace Acme\DemoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * UserMedia
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class UserMedia
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var integer
     *
     * @ORM\Column(name="enabled", type="integer")
     */
    private $enabled;

    /**
     * @var string
     *
     * @ORM\Column(name="provider_name", type="string", length=255)
     */
    private $providerName;

    /**
     * @var integer
     *
     * @ORM\Column(name="provider_status", type="integer")
     */
    private $providerStatus;

    /**
     * @var string
     *
     * @ORM\Column(name="provider_reference", type="string", length=255)
     */
    private $providerReference;

    /**
     * @var string
     *
     * @ORM\Column(name="provider_metadata", type="string", length=255)
     */
    private $providerMetadata;

    /**
     * @var integer
     *
     * @ORM\Column(name="width", type="integer")
     */
    private $width;

    /**
     * @var integer
     *
     * @ORM\Column(name="height", type="integer")
     */
    private $height;

    /**
     * @var string
     *
     * @ORM\Column(name="content_type", type="string", length=64)
     */
    private $contentType;

    /**
     * @var integer
     *
     * @ORM\Column(name="content_size", type="integer")
     */
    private $contentSize;

    /**
     * @var string
     *
     * @ORM\Column(name="copyright", type="string", length=255)
     */
    private $copyright;

    /**
     * @var string
     *
     * @ORM\Column(name="author_name", type="string", length=255)
     */
    private $authorName;

    /**
     * @var string
     *
     * @ORM\Column(name="context", type="string", length=64)
     */
    private $context;

    /**
     * @var integer
     *
     * @ORM\Column(name="cdn_is_flushable", type="integer")
     */
    private $cdnIsFlushable;

    /**
     * @var integer
     *
     * @ORM\Column(name="cdn_status", type="integer")
     */
    private $cdnStatus;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="access_label", type="integer")
     */
    private $accessLabel;

    /**
     * @Assert\File(maxSize="6000000")
     */
    private $file;
    private $temp;
    
  /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $path;
    
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     * @return UserMedia
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    
        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return UserMedia
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return UserMedia
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set enabled
     *
     * @param integer $enabled
     * @return UserMedia
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    
        return $this;
    }

    /**
     * Get enabled
     *
     * @return integer 
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set providerName
     *
     * @param string $providerName
     * @return UserMedia
     */
    public function setProviderName($providerName)
    {
        $this->providerName = $providerName;
    
        return $this;
    }

    /**
     * Get providerName
     *
     * @return string 
     */
    public function getProviderName()
    {
        return $this->providerName;
    }

    /**
     * Set providerStatus
     *
     * @param integer $providerStatus
     * @return UserMedia
     */
    public function setProviderStatus($providerStatus)
    {
        $this->providerStatus = $providerStatus;
    
        return $this;
    }

    /**
     * Get providerStatus
     *
     * @return integer 
     */
    public function getProviderStatus()
    {
        return $this->providerStatus;
    }

    /**
     * Set providerReference
     *
     * @param string $providerReference
     * @return UserMedia
     */
    public function setProviderReference($providerReference)
    {
        $this->providerReference = $providerReference;
    
        return $this;
    }

    /**
     * Get providerReference
     *
     * @return string 
     */
    public function getProviderReference()
    {
        return $this->providerReference;
    }

    /**
     * Set providerMetadata
     *
     * @param string $providerMetadata
     * @return UserMedia
     */
    public function setProviderMetadata($providerMetadata)
    {
        $this->providerMetadata = $providerMetadata;
    
        return $this;
    }

    /**
     * Get providerMetadata
     *
     * @return string 
     */
    public function getProviderMetadata()
    {
        return $this->providerMetadata;
    }

    /**
     * Set width
     *
     * @param integer $width
     * @return UserMedia
     */
    public function setWidth($width)
    {
        $this->width = $width;
    
        return $this;
    }

    /**
     * Get width
     *
     * @return integer 
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param integer $height
     * @return UserMedia
     */
    public function setHeight($height)
    {
        $this->height = $height;
    
        return $this;
    }

    /**
     * Get height
     *
     * @return integer 
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set contentType
     *
     * @param string $contentType
     * @return UserMedia
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    
        return $this;
    }

    /**
     * Get contentType
     *
     * @return string 
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Set contentSize
     *
     * @param integer $contentSize
     * @return UserMedia
     */
    public function setContentSize($contentSize)
    {
        $this->contentSize = $contentSize;
    
        return $this;
    }

    /**
     * Get contentSize
     *
     * @return integer 
     */
    public function getContentSize()
    {
        return $this->contentSize;
    }

    /**
     * Set copyright
     *
     * @param string $copyright
     * @return UserMedia
     */
    public function setCopyright($copyright)
    {
        $this->copyright = $copyright;
    
        return $this;
    }

    /**
     * Get copyright
     *
     * @return string 
     */
    public function getCopyright()
    {
        return $this->copyright;
    }

    /**
     * Set authorName
     *
     * @param string $authorName
     * @return UserMedia
     */
    public function setAuthorName($authorName)
    {
        $this->authorName = $authorName;
    
        return $this;
    }

    /**
     * Get authorName
     *
     * @return string 
     */
    public function getAuthorName()
    {
        return $this->authorName;
    }

    /**
     * Set context
     *
     * @param string $context
     * @return UserMedia
     */
    public function setContext($context)
    {
        $this->context = $context;
    
        return $this;
    }

    /**
     * Get context
     *
     * @return string 
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set cdnIsFlushable
     *
     * @param integer $cdnIsFlushable
     * @return UserMedia
     */
    public function setCdnIsFlushable($cdnIsFlushable)
    {
        $this->cdnIsFlushable = $cdnIsFlushable;
    
        return $this;
    }

    /**
     * Get cdnIsFlushable
     *
     * @return integer 
     */
    public function getCdnIsFlushable()
    {
        return $this->cdnIsFlushable;
    }

    /**
     * Set cdnStatus
     *
     * @param integer $cdnStatus
     * @return UserMedia
     */
    public function setCdnStatus($cdnStatus)
    {
        $this->cdnStatus = $cdnStatus;
    
        return $this;
    }

    /**
     * Get cdnStatus
     *
     * @return integer 
     */
    public function getCdnStatus()
    {
        return $this->cdnStatus;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return UserMedia
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    
        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserMedia
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set accessLabel
     *
     * @param integer $accessLabel
     * @return UserMedia
     */
    public function setAccessLabel($accessLabel)
    {
        $this->accessLabel = $accessLabel;
    
        return $this;
    }

    /**
     * Get accessLabel
     *
     * @return integer 
     */
    public function getAccessLabel()
    {
        return $this->accessLabel;
    }
    
    public function getAbsolutePath()
    {
        return null === $this->path? null : $this->getUploadRootDir().'/'.$this->path;
    }

    public function getWebPath()
    {
        return null === $this->path ? null : $this->getUploadDir().'/'.$this->path;
    }

    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        //echo '__DIR__';die;
        // here 1,2 would be register id.
        $filetype = $this->getFile()->getMimeType(); echo'<br>';
        $filetype = explode('/',$filetype);
        if($filetype[0] == 'image')
            {
              return 'uploads/documents/2/image';
            }
        else {
            
            return 'uploads/documents/2/video';
        }
    }

   /**
     * Sets file.
     *
     * @param UploadedFile $file
     */
 /*   public function setFile(UploadedFile $file = null)
    {
      
        // check if we have an old image path
        if (isset($this->path)) 
            {
            // store the old name to delete after the update
            $this->temp = $this->path;
            $this->path = null;
        } else {
            $this->path = 'initial';
        }
    }
  * */
  
     public function setFile(UploadedFile $file = null)
    {
        $this->file = $file;
    }

    /**
     * Get file.
     *
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
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
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    { 
        
        
        
        if (null === $this->getFile()) {
            return;
        }

        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
       //  $this->getFile()->move($this->getUploadRootDir(), $this->path);
		  $this->getFile()->move($this->getUploadRootDir(),$this->getFile()->getClientOriginalName());
		  // set the path property to the filename where you've saved the file
          $this->path = $this->getFile()->getClientOriginalName();  
        // check if we have an old image
        if (isset($this->temp)) {
            // delete the old image
            unlink($this->getUploadRootDir().'/'.$this->temp);
            // clear the temp image path
            $this->temp = null;
        }
        $this->file = null;
    }
   
/**
 * Called before entity removal
 *
 * @ORM\PreRemove()
 */
public function removeUpload()
{
    if ($file = $this->getAbsolutePath()) {
        unlink($file); 
    }
}
}
