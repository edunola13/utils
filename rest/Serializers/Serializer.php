<?php
namespace Enola\Rest\Serializers;

use Enola\Support\DependencyEngine\Reflection;
use Enola\Lib\Validation\ValidationFields;
use Enola\Rest\Exceptions\ValidationException;

abstract class Serializer implements SerializerInterface {
    /** Nombre de la clase del modelo
     * @var string  */
    public $model = '';
    /** Campos del modelo a utilizar
     * @var string[] */
    public $fields = [];
    /** De los campos arriba cuales son relaciones con sus propios serializers y configuracion
     * @var mixed[]
     */
    public $relations = [];
    /** Validaciones para los campos
     * @var string[] */
    public $validations = [];
    /** Infica si la actualizacion es parcial o completa
     * @var boolean */
    public $partial = false;
    /** Campos de solo escritura
     * @var string[] */
    public $write_only = [];
    /** Campos de solo lectura
     * @var string[] */
    public $read_only = [];
    
    /** Data que proviene del usuario
     * @var mixed[] */
    public $data = [];
    /** Errores generados por la validacion
     * @var string[] */
    public $errors = [];
    /** El locale para los mensajes de validacion
     * @var string */
    public $locale = null;
    /** La ubicacion de los archivos de i18n de los mensajes de error
     * @var string */
    public $pathLocale = PATHAPP . 'src/content/messages';    
    
    /** Instancia sobre la que se esta trabajando
     * @var mixed */
    public $instance = null;
    /** Resultado a serializar
     * @var mixed */
    public $result = null;
    
    public function __construct($options = []) {
        if (is_array($options)) {
            $this->instance = isset($options['instance']) ? $options['instance'] : null;
            $this->result = isset($options['result']) ? $options['result'] : null;
            $this->data = isset($options['data']) ? $options['data'] : null;
            $this->partial = isset($options['partial']) ? $options['partial'] : null;
        }
    }
    
    /**
     * Seteo los datos a serializar
     * @param mixed $result
     * @return $this
     */    
    public function setResult($result) {
        $this->result = $result;
        return $this;
    }
    
    /**
     * Consigue una instancia del modelo
     * @param midex $key
     * @return mixed
     */
    public function getInstance($key, $throw = true) {
        if ($throw) {
            $this->instance = $this->model::db()->query_get($key);
        } else{
            $this->instance = $this->model::db()->query_get_or_null($key);
        }
        return $this->instance;
    }
    /**
     * Setea la instancia que se va a trabajar
     */
    public function setInstance($instance) {
        $this->instance = $instance;
    }
    
    /**
     * Setea los datos a validar e insertar en la instancia
     * No los carga en la instancia actual
     * @param array $data
     */   
    public function setData($data) {
        $this->data = $data;
    }
    
    /**
     * Indica si los datos cargados son validos
     * @return boolean
     */
    public function isValid() {
        $this->errors = [];
        $validation = new ValidationFields($this->locale);
        $validation->dir_content= $this->pathLocale;
        foreach ($this->validations as $key => $rules) {
            $field = isset($this->data[$key]) ? $this->data[$key] : NULL;
            if ($this->partial) {
                $allRules = explode('|', $rules);
                $pos = array_search('required', $allRules);
                if ($pos !== false) {
                    unset($allRules[$pos]);
                }
                $rules = implode('|', $allRules);
            }
            if ($rules) {
                $validation->add_rule($key, $field, $rules);
            }
            
            if ($field && isset($this->relations[$key])) {
                $relationConf = $this->getSerializer($key);
                
                if ($relationConf['write_mode'] == 'create' && $relationConf['many'] == false) {
                    $serializer = $this->getSerializerInstance($key);
                    $serializer->setData($field);
                    $serializer->partial = $this->partial;
                    if (! $serializer->isValid()) {
                        $this->errors = array_merge($this->errors, [$key => $serializer->errors]);
                    }                    
                }
            }
        }
        if(! $validation->validate() || count($this->errors)){
            //Consigo los errores y retorno FALSE
            $this->errors = array_merge($this->errors, $validation->error_messages());
            return FALSE;
        }else{
            return TRUE;            
        }
    }    
    /**
     * En este metodo se deben realizar las validaciones especificas del modelo
     * En caso de ocurrir un error de validacion se debe levantar una excepcion de tipo ValidationException
     * @throws ValidationException
     */
    protected function validateBeforeSave() {        
    }
    
    /**
     * Carga los datos ingresados en la instancia actual
     */
    public function loadData() {
        $reflection = new Reflection($this->instance);
        $data = array_filter($this->data, function($k) { 
            return in_array($k, $this->fields) && !in_array($k, $this->read_only) && !isset($this->relations[$k]);
        }, ARRAY_FILTER_USE_KEY );
        $reflection->setProperties($data);
        
        $dataR = array_filter($this->data, function($k) { 
            return in_array($k, $this->fields) && !in_array($k, $this->read_only) && isset($this->relations[$k]);
        }, ARRAY_FILTER_USE_KEY );
        foreach ($dataR as $key => $value) {            
            $relationConf = $this->getSerializer($key);            
            if ($relationConf['write_mode'] == 'pk' && $relationConf['many'] == false) {
                $serializer = $this->getSerializerInstance($key);
                $relation = $serializer->getInstance($value, false);
                if ($relation == null) {
                    throw new ValidationException([$key => 'No result was found for query although at least one row was expected']);
                }
                $reflection->setProperty($key, $relation);
            }
            if ($relationConf['write_mode'] == 'pk' && $relationConf['many'] == true) {
                $serializer = $this->getSerializerInstance($key);
                $relations = [];
                foreach ($value as $pk) {
                    $relation = $serializer->getInstance($pk, false);
                    if ($relation == null) {
                        throw new ValidationException([$key => 'No result was found for query although at least one row was expected']);
                    }
                    $relations[] = $relation;
                }
                $reflection->setProperty($key, $relations);
            }
            if ($relationConf['write_mode'] == 'create' && $relationConf['many'] == false) {
                $serializer = $this->getSerializerInstance($key);
                $serializer->setData($value);
                
                $relation = $reflection->getProperty($key);
                if (! $relation) {
                    $relation = $serializer->create();
                } else {
                    $serializer->instance = $relation;
                    $serializer->updateAndSave();
                }
                $reflection->setProperty($key, $relation);
            }
        }
    }
    
    /**
     * Carga los datos pasados a mano en la instancia actual
     */
    public function loadExtraData($data) {
        $reflection = new Reflection($this->instance);
        $reflection->setProperties($data);        
    }
    
    /**
     * Crea una nueva instancia del modelo sin guardarla
     * @return mixed
     */
    public function create() {
        $this->instance = new $this->model();
        $this->loadData();
        return $this->instance;
    }    
    /**
     * Crea una nueva instancia del modelo guardandola
     * @return mixed
     */
    public function createAndSave($data = []) {
        $this->create();
        $this->loadExtraData($data);
        $this->validateBeforeSave();
        $this->instance->save();
        $this->setResult($this->instance);
        return $this->instance;
    }
    
    /**
     * Actualiza una instancia sin guardarla
     * @return mixed
     */
    public function update() {
        $this->loadData();
        return $this->instance;
    }
    /**
     * Actualiza una instancia guardandola
     * @return mixed
     */
    public function updateAndSave($data = []) {
        $this->update();
        $this->loadExtraData($data);
        $this->validateBeforeSave();
        $this->instance->save();
        $this->setResult($this->instance);
        return $this->instance;
    }
        
    public function save($data = []) {
        if ($this->instance) {
            return $this->updateAndSave($data);
        } else {
            return $this->createAndSave($data);
        }
    }
    
    public function delete() {
        $this->instance->destroy();
    }
    
    //
    //METODOS PARA SERIALIZAR
    
    /**
     * Parsea los objeto/s a array
     * @return array
     */
    public function serialize() {
        if (is_array($this->result) && isset($this->result['results'])) {
            $this->result['results'] = $this->serializeObject($this->result['results']);
            $serialize = $this->result;
        } else {
            $serialize = $this->serializeObject($this->result);
        }
        return $serialize;
    }
    
    /**
     * Transforma un objeto en un array en base al parametro $fields que indica que atributos del objeto mapear
     * @param Object $object
     * @param mixed $fields
     * @return mixed
     */   
    public function serializeObject($object) {
        $array = array();
        if (is_array($object) || $object instanceof \Traversable) {
            $array= array();
            foreach ($object as $var) {
                $array[]= $this->serializeObjectInternal($var);
            }
        }else if (! is_null($object)) {        
            $array= $this->serializeObjectInternal($object);
        }else {
            $array= NULL;
        }
        return $array;        
    }
    /**
     * Este es un metodo interno que transforma un objeto en un array en base al parametro $fields que indica 
     * que atributos del objeto mapear. Utilizado por object_to_array
     * @param Object $object
     * @param mixed $fields
     * @return mixed
     */ 
    protected function serializeObjectInternal($object) {
        $var = array();
        $reflection = new Reflection($object);
        foreach ($this->fields as $value) {
            if (in_array($value, $this->write_only)) {
                break;
            }
            if(isset($this->relations[$value])){
                $relationConf = $this->getSerializer($value);
                if ($relationConf['only_pk']) {
                    $relation = $reflection->getProperty($value);
                    if ($relationConf['many'] == true) {
                        $ids = [];
                        foreach ($relation as $v) {
                            $ids[] = $v->getId();
                        }                        
                        $var[$value] = $ids;
                        //
                        //NO ANDA
                    } else {
                        $reflectionRel = new Reflection($relation);
                        $serializer = $this->getSerializerInstance($value);
                        $var[$value] = $reflectionRel->getProperty($serializer->model::$pk);                   
                    }
                } else {
                    $serializer = $this->getSerializerInstance($value);
                    $var[$value] = $serializer->serializeObject($reflection->getProperty($value));
                    //$var[$key]= $this->object_to_array($reflection->getProperty($key), $fields[$key]);
                }
            }else{
                $var[$value] = $reflection->getProperty($value);
            }
        }
        return $var;
    }
    
    //
    //METODOS PARA SERIALIZER DE RELACIONES
    
    /**
     * Retorna la configuracion de una relacion
     * @param string $key
     * @return array
     */
    protected function getSerializer($key) {
        return array_merge(['serializer_class' => '', 'only_pk' => false, 'many' => false, 'write_mode' => 'pk'],  $this->relations[$key]);
    }
    /**
     * Retorna una instancia de un serializer para una relacion
     * @param type $key
     * @return Serializer
     */
    protected function getSerializerInstance($key) {
        if (! isset($this->relations[$key]['serializer'])) {
            $this->relations[$key]['serializer'] = new $this->relations[$key]['serializer_class']();
        }
        return $this->relations[$key]['serializer'];
    }
}
