<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Utility\UtilityBundle\Utils;

/**
 * The class object represent custom application level response.
 *
 * @author ddeffolap294
 */
class Response {

    //put your code here

    private $code;
    private $message;
    private $data;
    private $dataInfo;

    public function __construct($code = 0, $message = "", $data = array(), $dataInfo = array()) {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
        $this->dataInfo = $dataInfo;
    }

    public function getCode() {
        return $this->code;
    }

    public function getMessage() {
        return $this->message;
    }

    public function getData() {
        return $this->data;
    }

    public function getDataInfo() {
        return $this->dataInfo;
    }

    public function setCode($code) {
        $this->code = $code;
    }

    public function setMessage($message) {
        $this->message = $message;
    }

    public function setData($data) {
        $this->data = $data;
    }

    public function setDataInfo($dataInfo) {
        $this->dataInfo = $dataInfo;
    }

    public function __toString() {

        $resp = array("code" => $this->getCode(), 'message' => $this->getMessage(), 'data' => $this->getData() , 'dataInfo' => $this->getDataInfo());
        return Utility::encodeData($resp);
    }

}
