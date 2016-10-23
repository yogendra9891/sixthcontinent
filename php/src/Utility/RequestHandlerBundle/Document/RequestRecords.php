<?php

namespace Utility\RequestHandlerBundle\Document;



/**
 * Utility\RequestHandlerBundle\Document\RequestRecords
 */
class RequestRecords
{
    /**
     * @var MongoId $id
     */
    protected $id;

    /**
     * @var string $response_code
     */
    protected $response_code;

    /**
     * @var string $page_name
     */
    protected $page_name;

    /**
     * @var string $action_name
     */
    protected $action_name;

    /**
     * @var string $request_object
     */
    protected $request_object;

    /**
     * @var string $response_object
     */
    protected $response_object;

    /**
     * @var string $request_content_type
     */
    protected $request_content_type;

    /**
     * @var string $response_content_type
     */
    protected $response_content_type;

    /**
     * @var string $header_str
     */
    protected $header_str;

    /**
     * @var date $created_at
     */
    protected $created_at;


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
     * Set responseCode
     *
     * @param string $responseCode
     * @return self
     */
    public function setResponseCode($responseCode)
    {
        $this->response_code = $responseCode;
        return $this;
    }

    /**
     * Get responseCode
     *
     * @return string $responseCode
     */
    public function getResponseCode()
    {
        return $this->response_code;
    }

    /**
     * Set pageName
     *
     * @param string $pageName
     * @return self
     */
    public function setPageName($pageName)
    {
        $this->page_name = $pageName;
        return $this;
    }

    /**
     * Get pageName
     *
     * @return string $pageName
     */
    public function getPageName()
    {
        return $this->page_name;
    }

    /**
     * Set actionName
     *
     * @param string $actionName
     * @return self
     */
    public function setActionName($actionName)
    {
        $this->action_name = $actionName;
        return $this;
    }

    /**
     * Get actionName
     *
     * @return string $actionName
     */
    public function getActionName()
    {
        return $this->action_name;
    }

    /**
     * Set requestObject
     *
     * @param string $requestObject
     * @return self
     */
    public function setRequestObject($requestObject)
    {
        $this->request_object = $requestObject;
        return $this;
    }

    /**
     * Get requestObject
     *
     * @return string $requestObject
     */
    public function getRequestObject()
    {
        return $this->request_object;
    }

    /**
     * Set responseObject
     *
     * @param string $responseObject
     * @return self
     */
    public function setResponseObject($responseObject)
    {
        $this->response_object = $responseObject;
        return $this;
    }

    /**
     * Get responseObject
     *
     * @return string $responseObject
     */
    public function getResponseObject()
    {
        return $this->response_object;
    }

    /**
     * Set requestContentType
     *
     * @param string $requestContentType
     * @return self
     */
    public function setRequestContentType($requestContentType)
    {
        $this->request_content_type = $requestContentType;
        return $this;
    }

    /**
     * Get requestContentType
     *
     * @return string $requestContentType
     */
    public function getRequestContentType()
    {
        return $this->request_content_type;
    }

    /**
     * Set responseContentType
     *
     * @param string $responseContentType
     * @return self
     */
    public function setResponseContentType($responseContentType)
    {
        $this->response_content_type = $responseContentType;
        return $this;
    }

    /**
     * Get responseContentType
     *
     * @return string $responseContentType
     */
    public function getResponseContentType()
    {
        return $this->response_content_type;
    }

    /**
     * Set headerStr
     *
     * @param string $headerStr
     * @return self
     */
    public function setHeaderStr($headerStr)
    {
        $this->header_str = $headerStr;
        return $this;
    }

    /**
     * Get headerStr
     *
     * @return string $headerStr
     */
    public function getHeaderStr()
    {
        return $this->header_str;
    }

    /**
     * Set createdAt
     *
     * @param date $createdAt
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return date $createdAt
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }
}