<?php
namespace Enola\Helper\ApiRestHelper;
use Enola\Http\Models\En_HttpRequest;

class ApiRestParams {
    protected $search= NULL;
    protected $filters= array();
    protected $sort= NULL;
    protected $group= NULL;
    protected $pager= NULL;
    protected $select= NULL;
    protected $fields= array();
    
    protected $enabledFilters;
    protected $enabledFields;
    /**
     * @var En_HttpRequest 
     */
    protected $request;
    
    public function __construct(En_HttpRequest $request, $read = TRUE, $enabledFilters= array(), $enabledFields= array()) {
        $this->request= $request;
        $this->enabledFilters= $enabledFilters;
        $this->enabledFields= $enabledFields;
        if($read){ $this->readParams(); }
    }
    
    public function readParams(){
        //SEARCH
        $this->search= $this->request->getParam('q');
        //FILTERS
        foreach ($this->enabledFilters as $keyFilter) {
            $keyFilter= str_replace('.', '_', $keyFilter);      
            if($this->request->getParam($keyFilter) != NULL){
                $filter= $this->request->getParam($keyFilter);
                if(is_array($filter)){
                    $this->filters[]= array('key' => $keyFilter, 'value' => $filter, 'valueEnd' => null, 'operation' => 'i'/*DE IN*/, 'realKey' => null);
                }else{
                    $newValue= explode('^', $filter);
                    $operation= '=';
                    $valueEnd= NULL;
                    if(count($newValue) > 1){
                        //$equal= 3;
                        $filter= $newValue[0];
                        $valueEnd= $newValue[1];
                    }
                    $op= substr($filter, 0, 1);
                    if($op == '=' || $op == '!' || $op == '<' || $op == '>'){
                        $operation= substr($filter, 0, 1);
                        $filter= substr($filter, 1);
                    }
                    $this->filters[]= array('key' => $keyFilter, 'value' => $filter, 'valueEnd' => $valueEnd, 'operation' => $operation, 'realKey' => null);
                }
            }
        }
        //SORT
        if($this->request->getParam('sort')){
            $this->sort= array();
            $sorts= explode(',', $this->request->getParam('sort'));
            foreach ($sorts as $sort) {
                $desc= strpos($sort, '-');
                if($desc !== FALSE){
                    $desc= TRUE;
                    $sort= substr($sort, 1);
                }else{
                    $desc= FALSE;
                }
                $this->sort[]= array('value' => $sort, 'asc' => !$desc);
            }
        }
        //PAGER
        if($this->request->getParam('page') && $this->request->getParam('per_page')){
            $this->pager= array('page' => $this->request->getParam('page'), 'per_page' => $this->request->getParam('per_page'));
        }
        //GROUP
        if($this->request->getParam('group')){
            $this->group= $this->request->getParam('group');
        }
        //SELECT
        if($this->request->getParam('select')){
            $this->select= $this->request->getParam('select');
        }
        //FIELDS
        if($this->request->getParam('fields')){
            $fields= explode(',', $this->request->getParam('fields'));
            $this->fields= array();
            foreach ($this->enabledFields as $key => $field) {
                if(is_array($field) && in_array($key, $fields)){
                    $this->fields[$key]= $field;
                }else if(in_array($field, $fields)){
                    $this->fields[]= $field;
                }
            }
        }else{
            $this->fields= $this->enabledFields;
        }
    }   
    
    public function addFilter($key, $value, $equal = "=", $valueEnd = NULL){
        $this->filters[]= array('key' => $key, 'value' => $value, 'valueEnd' => $valueEnd, 'operation' => $equal);
    }
    public function existFilter($key){
        foreach ($this->filters as $value) {
            if($value['key'] == $key){
                return TRUE;
            }
        }
        return FALSE;
    }
    public function getValueFilter($key){
        $value= NULL;
        foreach ($this->filters as $valueFil) {
            if($valueFil['key'] == $key){
                $value= $valueFil['value'];
            }
        }
        return $value;
    }
    public function getFilter($key){
        foreach ($this->filters as $value) {
            if($value['key'] == $key){
                return $value;
            }
        }
        return null;
    }
    public function setRealKey($key, $realKey){
        foreach ($this->filters as &$value) {
            if($value['key'] == $key){
                $value['realKey']= $realKey;
            }
        }
    }
    
    function getSearch() {
        return $this->search;
    }

    function getFilters() {
        return $this->filters;
    }

    function getSort() {
        return $this->sort;
    }
    
    function getGroup(){
        return $this->group;
    }
    
    function getSelect(){
        return $this->select;
    }

    function getPager() {
        return $this->pager;
    }

    function getFields() {
        return $this->fields;
    }

    function getEnabledFilters() {
        return $this->enabledFilters;
    }

    function getEnabledFields() {
        return $this->enabledFields;
    }

    function getRequest() {
        return $this->request;
    }

    function setSearch($search) {
        $this->search = $search;
    }

    function setFilters($filters) {
        $this->filters = $filters;
    }

    function setSort($sort) {
        $this->sort = $sort;
    }
    
    function setGroup($group){
        $this->group= $group;
    }
    
    function setSelect($select){
        $this->select= $select;
    }

    function setPager($pager) {
        $this->pager = $pager;
    }

    function setFields($fields) {
        $this->fields = $fields;
    }

    function setEnabledFilters($enabledFilters) {
        $this->enabledFilters = $enabledFilters;
    }

    function setEnabledFields($enabledFields) {
        $this->enabledFields = $enabledFields;
    }

    function setRequest(En_HttpRequest $request) {
        $this->request = $request;
    }
}