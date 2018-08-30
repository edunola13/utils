<?php
namespace Enola\Db\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;

class EnolaQueryBuilder extends QueryBuilder {
    /** Nombre de la clase del modelo
     * @var string  */
    public $model;
    
    public function __construct(EntityManagerInterface $em, $model) {
        parent::__construct($em);
        $this->model = $model;
    }
    
    public function getResult() {
        return $this->getQuery()->getResult();
    }
    
    public function getSingleResult($hydrationMode = null) {
        return $this->getQuery()->getSingleResult($hydrationMode);
    }
    
    public function getOneOrNullResult() {
        return $this->getQuery()->getOneOrNullResult();
    }
    
    public function getScalarResult() {
        return $this->getQuery()->getScalarResult();
    }
    
    public function getSingleScalarResult() {
        return $this->getQuery()->getSingleScalarResult();
    }
    
    /**
     * Arma el select y el from de la consulta en base a las relaciones
     * @param type $relations
     * @return EnolaQueryBuilder
     */
    public function with($relations = []) {
        $this->buildSelectForQuery($relations);
        $this->buildFromForQuery($relations);
        return $this;
    }
    /**
     * Funcion que aplica los filtros
     * @param mixed $filters
     * @return EnolaQueryBuilder
     */
    public function filters($filters){
        foreach ($filters as $filter) {
            $key = $this->getAttrWithAlias($filter['f'], true);
            //Si se manda valor null creo con is y is not null
            if ($filter['va'] === null) {
                $this->andWhere($key . ($filter['op'] == '!' ? ' is not null': ' is null'));
            }
            //Si el where no fue creado arriba lo creo ($filter['value'] != 'null' OR IS_ARRAY)
            if (is_array($filter['va'])) {
                $this->andWhere($key. ' IN (:' .  $filter['f'] . ')');
            } 
            else if ($filter['va'] !== null) {
                if (!isset($filter['vaE'])) {
                    $this->andWhere($key . ' ' . $this->getOperation($filter['op']) . ' :' . $filter['f']);                
                } else {            
                    $this->andWhere($key." BETWEEN :".$filter['f']."1" . " AND :".$filter['f']."2");
                }
            }
        }
        $this->setInternalParameters($filters);
        return $this;
    }
    
    /**
     * Construye el select para una query builder
     * @param string[] $relations
     */
    protected function buildSelectForQuery($relations = []) {
        $select = 'model';
        if (count($relations)) {
            $select .= ', ' . implode(', ', $relations);
        }      
        $this->select($select);
    }
    /**
     * Construye el from para una query builder
     * @param string[] $relations
     */
    protected function buildFromForQuery($relations = []) {
        $this->from($this->model, 'model');
        foreach ($relations as $alias) {
            $this->leftJoin($this->getAttrWithAlias($alias), $alias);
        }
    }    
    /**
     * Retorna los valores de los filtros
     * @param mixed[] $filters
     */
    protected function setInternalParameters($filters){
        foreach ($filters as $filter) {
            if($filter['va'] === null){
                continue;
            }
            if(!isset($filter['vaE'])){
                $this->setParameter($filter['f'], $filter['va']);
            }else{
                $this->setParameter($filter['f'] . '1', $filter['va']);
                $this->setParameter($filter['f'] . '2', $filter['vae']);
            }
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
    /**
     * Retorna el nombre completo de la relacion
     * @param srting $name
     * @params boolean $replaceOnlyTheLast
     * @return srting
     */
    public function getAttrWithAlias($name, $replaceOnlyTheLast = false) {
        if (strpos($name, '__') === false) {
            return 'model.' . $name;
        } else {
            if ($replaceOnlyTheLast) {
                $pos = strrpos($name, '__');
                if ($pos !== false) {
                    return substr_replace($name, '.', $pos, strlen('__'));
                }
                return $name;
            }
            return str_replace('__', '.', $name);
        }
    }
}
