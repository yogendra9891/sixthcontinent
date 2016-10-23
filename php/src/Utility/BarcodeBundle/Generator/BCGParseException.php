<?php
/**
 *--------------------------------------------------------------------
 *
 * Parse Exception
 *
 *--------------------------------------------------------------------
 * 
 */
namespace Utility\BarcodeBundle\Generator;
use Exception;

class BCGParseException extends Exception {
    protected $barcode;

    /**
     * Constructor with specific message for a parameter.
     *
     * @param string $barcode
     * @param string $message
     */
    public function __construct($barcode, $message) {
        $this->barcode = $barcode;
        parent::__construct($message, 10000);
    }
}
?>