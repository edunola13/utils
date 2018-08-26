<?php
namespace Enola\Lib\Filters;
use Enola\Http\Models\En_HttpRequest;

interface FilterInterface {
    public function __construct(En_HttpRequest $request, $read = TRUE, $enabledFilters= array(), $enabledFields= array());
    
    public function readParams();
    
    public function addFilter($key, $value, $equal = "=", $valueEnd = NULL);
    public function existFilter($key);
    public function getValueFilter($key);
    public function getFilter($key);
    public function setRealKey($key, $realKey);
    
    function getSearch();

    function getFilters();

    function getSort();
    
    function getGroup();
    
    function getSelect();

    function getPager();

    function getFields();

    function getEnabledFilters();

    function getEnabledFields();

    function getRequest();

    function setSearch($search);

    function setFilters($filters);

    function setSort($sort);
    
    function setGroup($group);
    
    function setSelect($select);

    function setPager($pager);

    function setFields($fields);

    function setEnabledFilters($enabledFilters);

    function setEnabledFields($enabledFields);

    function setRequest(En_HttpRequest $request);
}
