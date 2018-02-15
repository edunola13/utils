<?php
namespace Enola\Lib\ApiRestTest;
/**
 * Clase que representa un request del escenario. Indica si es correcto, incorrecto y demas.
 */
class TestResponse{
    /** Indica el nombre del request
     * @var string */
    public $name;
    /** Indica el estado. 0= Mal, 1= Bien, 2= No se ejecuto por la condicion 
     * @var int */
    public $ok;
    /** Indica el request
     * @var mixed */
    public $request;
    /** Indica el response
     * @var mixed */
    public $response;
    /** Indica la respuesta esperada
     * @var mixed */
    public $expResponse;
    
    public function __construct($name, $ok) {
        $this->name= $name;
        $this->ok= $ok;
    }
    
    function isFullOk(){
        return $this->ok == 1;
    }
    function isOk(){
        return $this->ok != 0;
    }
    
    function getName() {
        return $this->name;
    }

    function getOk() {
        return $this->ok;
    }

    function getResponse() {
        return $this->response;
    }

    function getExpResponse() {
        return $this->expResponse;
    }

    function setName($name) {
        $this->name = $name;
    }

    function setOk($ok) {
        $this->ok = $ok;
    }

    function setResponse($response) {
        $this->response = $response;
    }

    function setExpResponse($expResponse) {
        $this->expResponse = $expResponse;
    }
    
    function getRequest() {
        return $this->request;
    }

    function setRequest($request) {
        $this->request = $request;
    }
}