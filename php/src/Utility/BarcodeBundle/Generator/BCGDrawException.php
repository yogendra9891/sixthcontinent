<?php
/**
 *--------------------------------------------------------------------
 *
 * Draw Exception
 *
 *--------------------------------------------------------------------
 * 
 */
namespace Utility\BarcodeBundle\Generator;
use Exception;

class BCGDrawException extends Exception {
    /**
     * Constructor with specific message.
     *
     * @param string $message
     */
    public function __construct($message) {
        parent::__construct($message, 30000);
    }
}
?>