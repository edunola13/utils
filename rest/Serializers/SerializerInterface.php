<?php
namespace Enola\Rest\Serializers;

interface SerializerInterface {    
    public function __construct($options = []);
    
    /**
     * Seteo los datos a serializar
     * @param mixed $result
     * @return $this
     */    
    public function setResult($result);
    
    /**
     * Consigue una instancia del modelo
     * @param midex $key
     * @return mixed
     */
    public function getInstance($key, $throw = true);    
    /**
     * Setea la instancia que se va a trabajar
     */
    public function setInstance($instance);
    
    /**
     * Setea los datos a validar e insertar en la instancia
     * No los carga en la instancia actual
     * @param array $data
     */   
    public function setData($data);
    
    /**
     * Indica si los datos cargados son validos
     * @return boolean
     */
    public function isValid();
    
    /**
     * Carga los datos ingresados en la instancia actual
     */
    public function loadData();
    
    
    /**
     * Crea una nueva instancia del modelo sin guardarla
     * @return mixed
     */
    public function create();  
    /**
     * Crea una nueva instancia del modelo guardandola
     * @return mixed
     */
    public function createAndSave();
    /**
     * Actualiza una instancia sin guardarla
     * @return mixed
     */
    public function update();
    /**
     * Actualiza una instancia guardandola
     * @return mixed
     */
    public function updateAndSave();
    /**
     * Guarda una instancia
     */
    public function save();
    /**
     * Elimina una instancia
     */
    public function delete();
    
    //
    //METODOS PARA SERIALIZAR
    
    /**
     * Parsea los objeto/s a array
     * @return array
     */
    public function serialize();
    /**
     * Transforma un objeto en un array en base al parametro $fields que indica que atributos del objeto mapear
     * @param Object $object
     * @param mixed $fields
     * @return mixed
     */   
    public function serializeObject($object);
}
