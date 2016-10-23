<?php
/**
 *--------------------------------------------------------------------
 *
 * Argument Exception
 *
 *--------------------------------------------------------------------
 * 
 * @author  Akhtar Khan <er.akhtarkhan@gmail.com>
 */

namespace Utility\BarcodeBundle\Generator;
use Exception;

class BCGArgumentException extends Exception {
    protected $param;

    /**
     * Constructor with specific message for a parameter.
     *
     * @param string $message
     * @param string $param
     */
    public function __construct($message, $param) {
        $this->param = $param;
        parent::__construct($message, 20000);
    }
}
?>