<?php
namespace Enola\Helper\ApiRestHelper;
use Enola\Common\RtaServices;
use Enola\Http\Models\En_HttpResponse;

class ApiRestResponse{
    public $code= 200;
    public $body= '';
    
    public function __construct($code = 200, $body = '') {        
        $this->code= $code;
        $this->body= $body;
    }
    
    
    public function assignCode($code){
        $this->code= $code;
    }
    public function assignBody($body){
        $this->body= $body;
    }
    public function assignCodeAndBody($code, $body){
        $this->code= $code;
        $this->body= $body;
    }
    public function addElementToBody($key, $element){
        $this->body[$key]= $element;
    }
    
    /**
    * Crea una respuesta API en base a un elemento y sus campos
    *
    * @deprecated
    * @param RtaServices $rtaSer
    * @param $keyElement = null
    * @param array $fields = null
    */
    public function createRta(RtaServices $rtaSer, $keyElement = null, array $fields = null){
        if($rtaSer->auth === false){
            $this->code= 401;
        }else if(!$rtaSer->isOk()){
            $this->code= 400;
        }
        $this->body= $rtaSer->toArray();
        if($keyElement){
            if($rtaSer->getParam($keyElement) != NULL){
                $jsonHelper= new JsonHelper();
                $this->body[$keyElement]= $jsonHelper->object_to_array($rtaSer->getParam($keyElement), $fields);   
            }
        }
    }
    
    /**
    * Crea una respuesta API en base a un elemento y sus campos
    *
    * @param RtaServices $rtaSer
    * @param $keyElement = null
    * @param array $fields = null
    */
    public function createResponse(RtaServices $rtaSer, $keyElement = null, array $fields = null){
        if($rtaSer->auth === false){
            $this->code= 401;
        }else if(!$rtaSer->isOk()){
            $this->code= 400;
        }
        $this->body= $rtaSer->toArray();
        if($keyElement){
            if($rtaSer->getParam($keyElement) != NULL){
                $jsonHelper= new JsonHelper();
                $instance = $rtaSer->getParam($keyElement);
                $this->body[$keyElement]= $jsonHelper->object_to_array($instance, is_null($fields) ? $instance->fields : $fields);   
            }
        }
    }
    
    public function responseElement(En_HttpResponse $response, $element, array $fields){
        if($element === false || ($element instanceof RtaServices && $element->auth === false)){
            $response->sendApiRest(401);
            return;
        }
        if($element == NULL && !is_array($element)){
            $response->sendApiRest(404);
        }else{
            $jsonHelper= new JsonHelper();
            $data= $jsonHelper->object_to_array($element, $fields);
            $response->sendApiRestEncode(200, $data);
        } 
    }
    
    public function responseRta(En_HttpResponse $response, RtaServices $rtaSer){
        if($rtaSer->auth === false){
            $this->code= 401;
        }else if(! $rtaSer->isOk()){
            $this->code= 400;
        }
        $this->body= $rtaSer->toArray();
        $response->sendApiRestEncode($this->code, $this->body);
    }
}