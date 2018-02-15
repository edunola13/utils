<?php
namespace Enola\Lib\ApiRestTest;
use Enola\Rest\RestClient;
/**
 * Clase abstracta que representa un escenario de Test
 */
abstract class TestScenario{
    /** Es un conjunto de headers que se van a pasar a cada peticion y se pueden ir modificando. Ahora sirve para el login
     * @var mixes
     */
    public $headers= array();//array('Authorization' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJleHAiOjE1MTQzMTEyMTIsImF1ZCI6ImE4NDcxZGQxYTM5NjdiOGUxZTU2NjI0OTMyZDQ2MTBkZTIwZGI1YjEiLCJkYXRhIjp7InVzZXJfaWQiOjEsInVzZXJfbG9nZ2VkIjpbImFkbWluIl19fQ.6YXM1ImlhTukkAKBarESkUWgQtL9paAJ6tjfZOsrTPs');
    /** Aca vamos a ir definiendo variables del escenario. Por ejemplo si creamos un usuarios, luego guardamos el id y con eso luego podemos hacer la siguiente peticion para
     * modificar, eliminar o lo que corresponda. 
     * @var mixed
     */
    public $vars= array();
    
    protected function preExecute(){
        //La idea es que se sobrescriba, pero no es obligatorio
    }
    public abstract function internalExecute();
    protected function postExecute(){
        //La idea es que se sobrescriba, pero no es obligatorio
    }
    
    protected function executeRequest($name, $completeRequest){
        try{
            if(!$this->conditionIsOk($completeRequest)){
                return new TestResponse($name . ' - No se ejecuto porque no se cumplieron las condiciones', 2);
            }
            
            $request= $completeRequest['request'];
            $expResponse= $completeRequest['expectedResponse'];
            $request['headers']= array_merge($request['headers'], $this->headers);
            $response= RestClient::exec($request);
            $ok= $this->responseIsOk($response, $expResponse);
            
            $testResponse= new TestResponse($name, $ok);
            $testResponse->response= $response;
            $testResponse->request= $request;
            if(!$testResponse->getOk()){                
                $testResponse->expResponse= $expResponse;
                unset($testResponse->expResponse['data']);
            }
            
            return $testResponse;
        } catch (Exception $ex) {
            return new TestResponse($name . ' - Exception Error', 0);
        }
        
    }
    
    protected function conditionIsOk($request){
        if(isset($request['condition'])){
            return call_user_func_array($request['condition'], array());
        }
        return true;
    }
    
    protected function responseIsOk($response, $expResponse){
        if($response['status'] == $expResponse['status']){
            if(isset($expResponse['data'])){
                return call_user_func_array($expResponse['data'], array($response['response']));
            }
            return 1;
        }
        return 0;
    }
    
    protected function isOkResps($reps){
        $lastRep= end($reps);
        return $lastRep->isOk();
    }
    
    public function execute(){
        $reps= $this->preExecute();
        if($reps != null){
            return $reps;
        }
        $reps= $this->internalExecute();
        if($this->isOkResps($reps)){
            $this->postExecute();
        }
        return $reps;
    }
    
    public function setInsideVars($vars= null){
        if($vars == null){
            return;
        }
        foreach ($vars as $key => $value) {
            $this->vars[$key]= $value;
        }
    }
}