<?php
namespace Utility\ApplaneIntegrationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class FilterDataEvent extends Event
{
    protected $data;

    /**
     * @param string $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }
    

}