<?php

namespace SixthContinent\SixthContinentConnectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TamoilExportCounter
 */
class TamoilExportCounter
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $counter;


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
     * Set counter
     *
     * @param integer $counter
     * @return TamoilExportCounter
     */
    public function setCounter($counter)
    {
        $this->counter = $counter;
    
        return $this;
    }

    /**
     * Get counter
     *
     * @return integer 
     */
    public function getCounter()
    {
        return $this->counter;
    }
}
