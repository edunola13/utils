<?php
namespace Enola\Db\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Enola\EnolaContext;

class EntityManagerFactoryDoctrine {
    protected static $instances;
    protected static $connections;
    
    protected function __construct($actualDb = NULL) {
        $context = EnolaContext::getInstance();

        $configDb = $context->readConfigurationFile("database");
        if($actualDb == NULL)$actualDb= $configDb['actual-db'];
        //$actualDb= $configDb['actual-db'];
        $doctrineDb= $configDb[$actualDb];        
        //The connection configuration
        $this->dbParams = array(
            'driver'   => $doctrineDb['driverbd'],
            'host'     => $doctrineDb['hostname'],
            'user'     => $doctrineDb['user'],
            'password' => $doctrineDb['pass'],
            'dbname'   => $doctrineDb['database'],            
            'charset'  => $doctrineDb['charset']
        );
        
        if ($context->getEnvironment() == 'development') {
            $cache = new \Doctrine\Common\Cache\ArrayCache;
        } else {
            $cache = new \Doctrine\Common\Cache\ArrayCache;
//            $memcached = new Memcached();
//            $memcached->addServer('127.0.0.1', 11211);
//            $cache = new \Doctrine\Common\Cache\MemcachedCache();
//            $cache->setMemcached($memcached);
        }
        //Creacion configuracion
        $this->config = new Configuration;        
        //Indicamos la carpeta o carpetas de los modelos
        $this->config->addEntityNamespace('DocMod', 'Gelou\Models');
        $driverImpl = $this->config->newDefaultAnnotationDriver($context->getPathApp() . "src/models");
        //Indicamos para que cachee el tema de la informacion indicada a traves de anotaciones
        $this->config->setMetadataDriverImpl($driverImpl);
        $this->config->setMetadataCacheImpl($cache);
        //Indicamos cache para las consultas DQL
        $this->config->setQueryCacheImpl($cache);
        //Indicamos la carpeta de los proxys y su namespace
        $this->config->setProxyDir($context->getPathApp() . "src/proxies");
        $this->config->setProxyNamespace('Gelou\Proxies');
        //En base al ambiente indicamos si se generan o no los proxys
        if ($context->getEnvironment() == 'development') {
            //$this->config->setAutoGenerateProxyClasses(true);
            $this->config->setAutoGenerateProxyClasses(true);
        } else {
            $this->config->setAutoGenerateProxyClasses(false);
        }
    }

    public static function getInstance($actualDb = NULL){
        if(! isset(EntityManagerFactoryDoctrine::$instances[$actualDb])){
            EntityManagerFactoryDoctrine::$instances[$actualDb] = new EntityManagerFactoryDoctrine($actualDb);
        }
        return EntityManagerFactoryDoctrine::$instances[$actualDb];
    }

    public static function connection($actualDb = NULL) {
        $instance = EntityManagerFactoryDoctrine::getInstance($actualDb);
        if (! isset(EntityManagerFactoryDoctrine::$connections[$actualDb])) {
            EntityManagerFactoryDoctrine::$connections[$actualDb] = EntityManager::create($instance->dbParams, $instance->config);
        }
        return EntityManagerFactoryDoctrine::$connections[$actualDb];
    }
    
    public static function newConnection($actualDb = NULL) {
        $instance = EntityManagerFactoryDoctrine::getInstance($actualDb);
        return EntityManager::create($instance->dbParams, $instance->config);
    }
}