<?php
namespace Enola\Db\Doctrine;

use Doctrine\ORM\NoResultException;
use Enola\Db\Doctrine\EntityManagerFactoryDoctrine;
use Enola\Lib\Filters\StandardFilter;
use Enola\Db\Exceptions\DoesNotExist;

class DoctrineHelper {
    /** Nombre de la clase del modelo
     * @var string  */
    public $model;
    /** Campo del modelo que funciona como clave
     * @var string  */
    public $pk = 'id';
    /** Campo del modelo que funciona como clave
     * @var string  */
    public $connection_db = null;
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    public $connection;
    
    public $query;
    
    
    public function __construct($model, $options = []) {
        $this->model = $model;
        if (is_array($options)) {
            $this->pk = isset($options['id']) ? $options['id'] : $this->pk;
            $this->connection_db = isset($options['connection']) ? $options['connection'] : $this->connection_db;
        }
    }
    
    /**
     * @return DoctrineHelper
     */
    public static function inst($model, $options = []) {
        return new DoctrineHelper($model, $options);
    }
    /**
     * Retorna la conexion
     * @return \Doctrine\ORM\EntityManager
     */
    public function getConnection() {
        if (! $this->connection) {
            $this->connection = EntityManagerFactoryDoctrine::connection($this->connection_db);
        }
        return $this->connection;
    }
    /**
     * Salve una instancia
     * @param mixed $instance
     */   
    public function save($instance) {
        $this->getConnection()->persist($instance);
        $this->getConnection()->flush($instance);
    }
    /**
     * Remueve una instancia
     * @param mixed $instance
     */
    public function remove($instance) {
        $this->getConnection()->remove($instance);
        $this->getConnection()->flush($instance);
    }
    /**
     * Retorna un QueryBuilder
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function queryBuilderDoctrine() {
        return $this->getConnection()->createQueryBuilder();
    }
    /**
     * Retorna un EnolaQueryBuilder
     * @return EnolaQueryBuilder
     */
    public function queryBuilder() {
        return new EnolaQueryBuilder($this->getConnection(), $this->model);
    }
    
    //
    //QUERY GET FOR SERIALIZER
    /**
     * Retorna un objeto si existe o levanta excepcion en caso contrario
     * @param mixed $key
     * @param string[] $relations
     * @return mixed
     * @throws DoesNotExist
     */
    public function query_get($key, $relations = []) {
        try {
            $query = $this->getConnection()->createQueryBuilder();
            $this->buildSelectForQuery($query, $relations);
            $this->buildFromForQuery($query, $relations);
            $query->where('model.' . $this->pk . ' = :key');
            $query->setParameters(['key' => $key]);
            return $query->getQuery()->getSingleResult();
        }
        catch (NoResultException $e)
        {
            throw new DoesNotExist($e);
        }
    }
    /**
     * Retorna un objeti si existe o null en caso contrario
     * @param mixed $key
     * @param string[] $relations
     * @return mixed
     */
    public function query_get_or_null($key, $relations = []) {
        $query = $this->getConnection()->createQueryBuilder();
        $this->buildSelectForQuery($query, $relations);
        $this->buildFromForQuery($query, $relations);
        $query->where('model.' . $this->pk . ' = :key');
        $query->setParameters(['key' => $key]);
        return $query->getQuery()->getOneOrNullResult();
    }
    
    //
    //QUERY WITH APIPARAMS
    /**
     * Retorna un listado de objetos en base a parametros de entrada
     * @param string[] $relations
     * @param StandardFilter $filters
     * @param string[] $searchs
     * @param boolean $paginate
     * @param boolean $relationManyToMany
     * @return mixed
     */
    public function query_list(array $relations, StandardFilter $filters, $searchs = [], $paginate = true, $relationManyToMany = false) {
        $query = $this->getConnection()->createQueryBuilder();
        $this->buildSelectForQuery($query, $relations);
        $this->buildFromForQuery($query, $relations);
        $this->buildWhereForQuery($query, $filters, $searchs);
        $this->buildSortForQuery($query, $filters);
        if ($paginate) {
            $this->buildPagerForQuery($query, $filters);
        }
        
        if ($relationManyToMany && $paginate) {
            $results = new \Doctrine\ORM\Tools\Pagination\Paginator($query, $fetchJoinCollection = true);
            $values= array();
            foreach ($results as $value) {
                $values[]= $value;
            }
            return $values;
        } else {
            return $query->getQuery()->getResult();
        }
    }
    /**
     * Retorna la cantidad de objetos que aplican al filtro
     * @param string[] $relations
     * @param StandardFilter $filters
     * @param string[] $searchs
     * @return mixed
     */
    public function query_list_pager(array $relations, StandardFilter $filters, $searchs = []) {
        $query = $this->getConnection()->createQueryBuilder();
        $query->select('COUNT(DISTINCT model.id)');
        $this->buildFromForQuery($query, $relations);
        $this->buildWhereForQuery($query, $filters, $searchs);        
        return $query->getQuery()->getSingleScalarResult();
    }
    
    //
    //UTILS FOR BUILD QUERIES WITH API PARAMS
    /**
     * Construye el select para una query builder
     * @param \Doctrine\ORM\QueryBuilder $query
     * @param string[] $relations
     */
    public function buildSelectForQuery($query, $relations = []) {
        $select = 'model';
        /*$rels = array_filter($this->relations, function($k) {
            $relationConf = $this->getSerializer($k);
            return $relationConf['many'] || ! $relationConf['only_pk'];
        }, ARRAY_FILTER_USE_KEY);
        $rels = array_keys($rels);*/
        if (count($relations)) {
            $select .= ', ' . implode(', ', $relations);
        }      
        $query->select($select);
    }
    /**
     * Construye el from para una query builder
     * @param \Doctrine\ORM\QueryBuilder $query
     * @param string[] $relations
     */
    public function buildFromForQuery($query, $relations = []) {
        $query->from($this->model, 'model');
        /*foreach ($this->relations as $alias => $value) {
            $relationConf = $this->getSerializer($alias);
            if ($relationConf['only_pk'] && ! $relationConf['many']) {
                continue;
            }
            $query->leftJoin('model.' . $alias, $alias);
            //PUEDEN CONTINUAR LAS RELACIONES DEL OTRO SERIALIZER
        }*/
        foreach ($relations as $alias) {
            $query->leftJoin($this->getAttrWithAlias($alias), $alias);
        }
    }
    /**
     * Construye el where para una query builder
     * @param \Doctrine\ORM\QueryBuilder $query
     * @param StandardFilter $filters
     * @param string[] $searchs
     */
    public function buildWhereForQuery($query, $filters, $searchs) {
        $values = [];
        
        if ($filters->getSearch()) {
            $dql = '';
            $first = true;
            foreach ($searchs as $attr) {
                if (! $first) {
                    $dql  .= ' OR ';
                }
                $dql .= $this->getAttrWithAlias($attr) . ' LIKE :_q_';
                $first = false;
            }
            $query->where($dql);
            $values = ['_q_' => '%'.$filters->getSearch().'%'];
        }
        
        if ($filters->getFilters()) {
            foreach ($filters->getFilters() as $filter) {
                $this->addFilters($query, $filter);                
            }
            $values = array_merge($values, $this->valuesFilter($filters->getFilters()));
        }
        
        $query->setParameters($values);
    }
    /**
     * Funcion que aplica los filtros
     * @param \Doctrine\ORM\QueryBuilder $query
     * @param mixed $filter
     */
    public function addFilters($query, $filter){
        //$key= isset($filter['realKey']) ? str_replace('_', '.', $filter['realKey']) : str_replace('_', '.', $filter['key']);
        $key = isset($filter['realKey']) ? $this->getAttrWithAlias($filter['realKey']) : $this->getAttrWithAlias($filter['key']);
        //Si se manda valor null creo con is y is not null
        if ($filter['value'] === 'null') {
            $query->andWhere($key . ($filter['operation'] == '!' ? ' is not null': ' is null'));
        }
        //Si el where no fue creado arriba lo creo ($filter['value'] != 'null' OR IS_ARRAY)
        if (is_array($filter['value'])) {
            $query->andWhere($key. ' IN (:' .  $filter['key'] . ')');
        } 
        else if ($filter['value'] !== 'null') {
            if (!isset($filter['valueEnd'])) {
                $query->andWhere($key . ' ' . $this->getOperation($filter['operation']) . ' :' . $filter['key']);                
            } else {            
                $query->andWhere($key." BETWEEN :".$filter['key']."1" . " AND :".$filter['key']."2");
            }
        }
    }
    /**
     * Construye el sort de una query builder
     * @param \Doctrine\ORM\QueryBuilder $query
     * @param StandardFilter $filters
     */
    public function buildSortForQuery($query, $filters) {
        if ($filters->getSort()) {
            foreach ($filters->getSort() as $sort) {
                //VER EL TEMA DE QUE CUANDO ORDENA POR USERNAME EN ESTE CASO HAY QUE AGREGARLE EL "model.username"
                $query->orderBy($this->getAttrWithAlias($sort['value']), ($sort['asc'] ? 'ASC' : 'DESC'));
            }
        }
    }
    /**
     * Construye el pager de una query builder
     * @param \Doctrine\ORM\QueryBuilder $query
     * @param StandardFilter $filters
     */
    public function buildPagerForQuery($query, $filters) {
        if ($filters->getPager()) {
            $query->setMaxResults($filters->getPager()['per_page']);
            $query->setFirstResult(($filters->getPager()['page'] - 1) * $filters->getPager()['per_page']);
        }
    }
    
    /**
     * Retorna los valores de los filtros
     * @param mixed[] $filters
     * @return mixed[]
     */
    public function valuesFilter($filters){
        $values = [];
        foreach ($filters as $filter) {
            if($filter['value'] === 'null'){
                continue;
            }
            if(!isset($filter['valueEnd'])){
                $values[$filter['key']]= $filter['value'];
            }else{
                $values[$filter['key'] . '1']= $filter['value'];
                $values[$filter['key'] . '2']= $filter['valueEnd'];
            }
        }
        return $values;
    }
    /**
     * Retorna el nombre completo de la relacion
     * @param srting $name
     * @return srting
     */
    public function getAttrWithAlias($name) {
        if (strpos($name, '__') === false) {
            return 'model.' . $name;
        } else {
            return str_replace('__', '.', $name);
        }
    }
    /**
     * Retorna la operacion en base a un codigo
     * @param srting $opCode
     * @return string
     */
    protected function getOperation($opCode){
        switch ($opCode) {
            case '=':
                return '=';
            case '!':
                return '!=';
            case '>':
                return '>';
            case '<':
                return '<';
            default:
                return '=';
        }
    }
    
}
