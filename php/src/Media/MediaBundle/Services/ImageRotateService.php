<?php
namespace Media\MediaBundle\Services;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

// service method class for user object.
class ImageRotateService
{

    /**
     * 
     */
    public function __construct()
    {
        
    }
   
   /**
    * Rotate the image if it get orientation
    * @param string $source_image_path
    */
   public function ImageRotateService($source_image_path)
   {
            
       $exif = @exif_read_data($source_image_path);
    if (!empty($exif['Orientation'])) {
        $image = imagecreatefromjpeg($source_image_path);
        switch ($exif['Orientation']) {
            case 3:
                $image = imagerotate($image, 180, 0);
                break;

            case 6:
                $image = imagerotate($image, -90, 0);
                break;

            case 8:
                $image = imagerotate($image, 90, 0);
                break;
        }

        imagejpeg($image, $source_image_path, 90);
    }
   }
}