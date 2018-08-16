<?php
namespace Enola\Rest\Exceptions;

use Exception;

/**
 * Excepcion que se levanta cuando ocurre un error de validacion
 */
class ValidationException extends Exception
{
    /**
     * Error de validacion (key => value)
     * @var mixed
     */
    protected $error;
    
    public function __construct($error) {
        $this->error = $error;
        parent::__construct('Error de validacion');
    }
    
    /**
     * return mixed
     */
    public function getError(){
        return $this->error;
    }
}
