<?php
namespace Enola\Rest\Filters;

use Enola\Http\Models\En_HttpRequest, Enola\Http\Models\En_HttpResponse, Enola\Http\Models\En_Filter;

class ApiRestCors extends En_Filter{
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }
    /**
     * Funcion que realiza el filtro correspondiente
     * @param En_HttpRequest $request
     * @param En_HttpResponse $response
     */
    public function filter(En_HttpRequest $request, En_HttpResponse $response){
        //Habilita el Cross Domain        
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, PATCH, OPTIONS');
        if($request->requestMethod == "OPTIONS"){
            header('Access-Control-Allow-Headers: origin, content-type, accept, authorization');
            exit;
        }
        if($this->context->getAuthentication() == 'session'){
            $sessionId= NULL;
            if($request->getHeader('Authorization') != NULL){
                $sessionId= $request->getHeader('Authorization');
            }
            $request->session->startSession($sessionId);
        }
    }
}
