<?php
namespace Enola\Helper\ApiRestHelper;
/**
 * Es un Trait que va a permitir que objetos mapeados con doctrine se serialicen a json.
 * Los objetos no inicializados no son cargados, solo su Id.
 * @author Eduardo Sebastian Nola <edunola13@gmail.com>
 * @category Enola\Support
 */
trait JsonHelperDoctrine{
    public function jsonSerialize() {
        $vars = get_object_vars($this);
        unset($vars['__initializer__'], $vars['__isInitialized__'], $vars['__cloner__']);
        foreach ($vars as $key => $var) {
            if(is_object($var) && ($var instanceof \Doctrine\Common\Persistence\Proxy) && !$var->__isInitialized()){
                $vars[$key]= $var->getId();
            }else if($var instanceof  \Doctrine\ORM\PersistentCollection && $var->isInitialized()){
                $list= array();
                foreach ($var as $objeto) {
                    $list[]= $objeto;
                }
                $vars[$key]= $list;                
            }else if($var instanceof  \Doctrine\ORM\PersistentCollection && !$var->isInitialized()){
                $vars[$key]= array();
            }
        }
        return $vars;
    }
}