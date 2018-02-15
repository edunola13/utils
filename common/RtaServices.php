<?php
namespace Enola\Common;

/**
 * Esta clase sirve para tener una respuesta estandar de los servicios internos de una aplicacion
 */
class RtaServices{
    /** Indica si hubo autorizacion
     * @var bool
     */
    public $auth= true;
    /** Indica si el estado es correcto o no
     * @var bool 
     */
    public $state;
    /** Indica el codigo de respuesta del servicio. Puede ser null
     * @var string
     */
    public $code;
    /** Indica el msj de respuesta del servicio. Puede ser null
     * @var string
     */
    public $msj;
    /** Indica los parametros de respuesta del servicio.
     * @var mixed[]
     */
    public $params;
    
    public function __construct($state, $code, $msj, $params) {
        $this->state= $state;
        $this->code= $code;
        $this->msj= $msj;
        $this->params= $params;
    }
    
    public function isOk(){
        return $this->state;
    }
    
    public function getParam($name){
        if(isset($this->params[$name])){
            return $this->params[$name];
        }
        return NULL;
    }
    
    public function toArray(){
        return array('state' => $this->state, 'code' => $this->code, 'msj' => $this->msj);
    }
}
