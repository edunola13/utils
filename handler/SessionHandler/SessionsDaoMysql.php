<?php
namespace Enola\Handler\SessionHandler;
use Enola\Support\DataBaseAR;

/**
 * Clase encargada de consultar y manipular la informacion de sesiones en la base de datos
 */
class SessionsDaoMysql extends DataBaseAR{
    
    public function __construct($conect = FALSE, $nameDB = NULL, $configFile = NULL) {
        parent::__construct($conect, $nameDB, $configFile);
    }
        
    public function sessionData($id, $key){
        $this->select('d.session_id, d.key_data, d.data');
        $this->from('sessions_data d');
        $this->join('sessions s', 'd.session_id = s.session_id');
        $this->where('d.session_id = :id AND d.key_data = :key', array('id' => $id, 'key' => $key));
        return $this->get()->fetch(PDO::FETCH_ASSOC);
    }
    
    public function storeSessionData($data){
        return $this->insert('sessions_data', $data);
    }
    
    public function updateSessionData($id, $key, $data){
        $this->where('session_id = :id AND key_data = :key', array('id' => $id, 'key' => $key));
        return $this->update('sessions_data', $data);
    }
    
    public function deleteSessionData($id){
        $this->where('session_id = :id', array('id' => $id));
        return $this->delete('sessions_data');
    }
    
    public function deleteSessionDataSpecific($id, $key){
        $this->where('session_id = :id AND key_data = :key', array('id' => $id, 'key' => $key));
        return $this->delete('sessions_data');
    }
}
