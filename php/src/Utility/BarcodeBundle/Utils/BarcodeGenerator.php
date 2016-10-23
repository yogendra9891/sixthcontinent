<?php
namespace Utility\BarcodeBundle\Utils;
use Utility\BarcodeBundle\Utils\BarcodeType;
use Utility\BarcodeBundle\Generator\BCGColor;
use Utility\BarcodeBundle\Generator\BCGDrawing;
use Utility\BarcodeBundle\Generator\BCGFontFile;

class BarcodeGenerator extends BarcodeType {
    
    /**
     * Resolution
     */
    private $scale;
    
    /**
     * Thikness
     */
    private $thickness;
    
    /**
     * Text and barcode color
     */
    private $foregroundColor = '#000000';
    
    /**
     * Background color
     */
    private $backgroundColor = '#FFFFFF';
    
    /**
     * Font path for barcode
     */
    private $font;
    
    /**
     * Barcode type such as code128, code39 etc.
     */
    private $barcodeType;
    
    /**
     * Barcode format such as png, jpeg, gif or wbmp
     */
    private $format;
    
    /**
     * Text to generate barcode
     */
    private $text;
    
    /**
     * Filename to save barcode
     */
    private $filename = '';
    
    /**
     * Barcode types are allowed to generate
     */
    private $allowedFormats = array(
        'PNG', 'JPEG', 'GIF', 'WBMP'
    );
    
    /**
     * Set Resolution
     * @param int $scale
     */
    public function setScale($scale){
        $this->scale = $scale;
    }
    
    /**
     * Set Thickness or Height
     * @param int $thickness
     */
    public function setThickness($thickness){
        $this->thickness = $thickness;
    }
    
    /**
     * Set Text or barcode color
     * @param string $foregroundColor
     */
    public function setForegroundColor($foregroundColor){
        $this->foregroundColor = $foregroundColor;
    }
    
    /**
     * Set background color
     * @param string $backgroundColor
     */
    public function setBackgroundColor($backgroundColor){
        $this->backgroundColor = $backgroundColor;
    }
    
    /**
     * Set font path to use in barcode text
     * @param string $font
     */
    public function setFont($font){
        $this->font = $font;
    }
    
    /**
     * Set Barcode type such as code128
     * @param string $type
     */
    public function setType($type){
        $this->barcodeType = $type;
    }
    
    /**
     * Set barcode format such as png, gif, jpeg
     * @param string $format
     */
    public function setFormat($format){
        $this->format = $format;
    }
    
    /**
     * Set text to generate barcode
     * @param string $text
     */
    public function setText($text){
        $this->text = $text;
    }
    
    /**
     * Set filename with path to save barcode
     * @param string $filename
     */
    public function setFilename($filename){
        $this->filename = $filename;
    }

    /**
     * Generate barcode
     * @param string $text      Barcode text to generate
     * @param string $type      Barcode type such as code128
     * @param string $format    Barcode format such as png, jpeg
     * @param string $fontPath  Font path to use in barcode text
     * @return string           Base64Encoded string will return
     */
    public function generate($text=null, $type=null, $format=null, $fontPath=null){
        if(isset($text)){
            $this->text = $text;
        }
        if(isset($type)){
            $this->barcodeType = $type;
        }
        if(isset($format)){
            $this->format = $format;
        }
        
        if(isset($fontPath)){
            $this->font = $fontPath;
        }
        return $this->_render();
    }
    
    /**
     * Get barcode object to create image
     * @return object   Barcode object
     */
    private function _getCode(){
        $code = null;
        try{
            $text = $this->text;
            $textColor = new BCGColor($this->foregroundColor);
            $backgroudColor = new BCGColor($this->backgroundColor);
            $fontPath = isset($this->font) ? $this->font : $this->_getDefaultFont();
            $font = new BCGFontFile($fontPath, 18);
            $codeClass = "\\Utility\\BarcodeBundle\\Generator\\".$this->barcodeType;
            $code = new $codeClass();
            if($this->scale){
                $code->setScale($this->scale); // Resolution
            }
            if($this->thickness){
                $code->setThickness($this->thickness); // Thickness
            }
            $code->setForegroundColor($textColor); // Color of bars
            $code->setBackgroundColor($backgroudColor); // Color of spaces
            $code->setFont($font); // Font (or 0)
            $code->parse($text); // Text
        } catch (\Exception $ex) {

        }
        return $code;
    }
    
    /**
     * Render barcode as base64 encoded
     * @return string   Base64Encoded image
     */
    private function _render(){
        $textColor = new BCGColor($this->foregroundColor);
        $backgroudColor = new BCGColor($this->backgroundColor);
        
        /* Here is the list of the arguments
        1 - Filename (empty : display on screen)
        2 - Background color */
        $drawing = new BCGDrawing($this->filename, $backgroudColor);
        
        $drawException = null;
        if(!isset($this->barcodeType)){
            $drawException = 'Unable to generate barcode for unknown type';
        }else{
            if(!($code = $this->_getCode())){
                $drawException = 'Unable to generate barcode';
            }
        }
        
        if(isset($this->format) and !in_array(strtoupper($this->format), $this->allowedFormats)){
            $drawException = $this->format .' format is not allowed.';
        }
        
        ob_start();
        if($drawException) {
            $exception = new \Exception($drawException);
            $drawing->drawException($exception);
        } else {
            $drawing->setBarcode($code);
            $drawing->draw();
        }
        $drawing->finish($this->_getFormat());
        $barcodeImg = ob_get_clean();
        $barcodeImg = base64_encode($barcodeImg);
        return $barcodeImg;
    }
    
    /**
     * Barcode image format
     * @return string
     */
    private function _getFormat(){
        $format = '';
        switch(strtoupper($this->format)){
            case 'PNG':
                $format = BCGDrawing::IMG_FORMAT_PNG;
                break;
            case 'JPEG':
                $format = BCGDrawing::IMG_FORMAT_JPEG;
                break;
            case 'GIF':
                $format = BCGDrawing::IMG_FORMAT_GIF;
                break;
            case 'WBMP':
                $format = BCGDrawing::IMG_FORMAT_WBMP;
                break;
            default:
                $format = BCGDrawing::IMG_FORMAT_PNG;
                break;
        }
        return $format;
    }
    
    /**
     * Get default font for barcode if not provided by user
     * @global object $kernel
     * @return string
     */
    private function _getDefaultFont(){
        global $kernel;
        $fontPath = $kernel->locateResource('@UtilityBarcodeBundle/Resources/font/Arial.ttf');
        return $fontPath;
    }
}
