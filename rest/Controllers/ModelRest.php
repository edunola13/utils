<?php
namespace Enola\Rest\Controllers;

use Enola\Http\Models\En_Controller, Enola\Http\Models\En_HttpRequest, Enola\Http\Models\En_HttpResponse;
use Enola\Lib\Filters\StandardFilter;
use Enola\Lib\Pagination\StandardPagination;

use Enola\Rest\Exceptions\ValidationException;
use Enola\Db\Exceptions\DoesNotExist;

class ModelRest extends En_Controller implements ModelRestInterface {
    /** Nombre de la clase del modelo del endpoint
     * @var string  */
    public $model = '';
    /** Nombre de la clase del serializar
     * @var \Gelou\Serializers\SerializerInterface
     */
    public $serializer_class = '';
    /** Campos sobre los que se puede filtrar
     * @var string[] */
    public $filters = [];
    /** Campos sobre los que se puede buscar
     * @var string[] */
    public $searchs = [];
    /** Indica si esta habilitado el paginado
     * @var boolean */
    public $paginate = false;
    /** Indica si hay relaciones muchos a muchos
     * @var boolean */
    public $relationManyToMany = false;
    /** Indica las relaciones eager 
     * @var string[] */
    public $relationsEager = [];
    
    
    public function list_all (En_HttpRequest $request, En_HttpResponse $response){
        $result = $this->getList($request);        
        $serializer = $this->getSerializer($request, ['result' => $result]);        
        $response->sendApiRestEncode(En_HttpResponse::HTTP_OK, $serializer->serialize());
    }
    
    public function retrieve (En_HttpRequest $request, En_HttpResponse $response){
        try 
        {
            $object = $this->getObject($request, $this->getUriParam('pk'));
            $serializer = $this->getSerializer($request, ['result' => $object]);
            $response->sendApiRestEncode($response::HTTP_OK, $serializer->serialize());
        }
        catch (DoesNotExist $e) 
        {
            $response->sendApiRest($response::HTTP_NOT_FOUND);
        }
    }
        
    public function create (En_HttpRequest $request, En_HttpResponse $response) {
        try 
        {
            $serializer = $this->getSerializer($request, ['data' => $request->getBody()]);
            if ($serializer->isValid()) {
                $serializer->save();
                $response->sendApiRestEncode($response::HTTP_CREATED, $serializer->serialize());
            } else {
                $response->sendApiRestEncode($response::HTTP_BAD_REQUEST, $serializer->errors);
            }
        }
        catch (ValidationException $e) 
        {
            $response->sendApiRestEncode($response::HTTP_BAD_REQUEST, $e->getError());
        }
    }
    
    public function update (En_HttpRequest $request, En_HttpResponse $response) {
        try 
        {
            $object = $this->getObject($request, $this->getUriParam('pk'));
            $serializer = $this->getSerializer($request, ['instance' => $object, 'data' => $request->getBody()]);
            if ($serializer->isValid()) {
                $serializer->save();
                $response->sendApiRestEncode($response::HTTP_CREATED, $serializer->serialize());
            } else {
                $response->sendApiRestEncode($response::HTTP_BAD_REQUEST, $serializer->errors);
            }
        }
        catch (ValidationException $e) 
        {
            $response->sendApiRestEncode($response::HTTP_BAD_REQUEST, $e->getError());
        }
        catch (DoesNotExist $e) 
        {
            $response->sendApiRest($response::HTTP_NOT_FOUND);
        }
    }
    
    public function partial_update (En_HttpRequest $request, En_HttpResponse $response) {
        try 
        {
            $object = $this->getObject($request, $this->getUriParam('pk'));
            $serializer = $this->getSerializer($request, ['instance' => $object, 'data' => $request->getBody(), 'partial' => true]);
            if ($serializer->isValid()) {
                $serializer->save();
                $response->sendApiRestEncode($response::HTTP_CREATED, $serializer->serialize());
            } else {
                $response->sendApiRestEncode($response::HTTP_BAD_REQUEST, $serializer->errors);
            }
        }
        catch (ValidationException $e) 
        {
            $response->sendApiRestEncode($response::HTTP_BAD_REQUEST, $e->getError());
        }
        catch (DoesNotExist $e) 
        {
            $response->sendApiRest($response::HTTP_NOT_FOUND);
        }
    }
    
    public function destroy (En_HttpRequest $request, En_HttpResponse $response) {
        try 
        {
            $object = $this->getObject($request, $this->getUriParam('pk'));
            $serializer = $this->getSerializer($request, ['instance' => $object]);
            $serializer->delete();
            $response->sendApiRest($response::HTTP_NO_CONTENT);
        }
        catch (ValidationException $e) 
        {
            $response->sendApiRestEncode($response::HTTP_BAD_REQUEST, $e->getError());
        }
        catch (DoesNotExist $e) 
        {
            $response->sendApiRest($response::HTTP_NOT_FOUND);
        }
    }
    
    //
    //UTILS
    /**
     * Retorna el nombre de clase del serializer correspondiente
     * @param En_HttpRequest $request
     * @return string
     */
    protected function getSerializerClass($request) {
        return $this->serializer_class;
    }
    /**
     * Retorna la instancia de una clase serializer
     * @param En_HttpRequest $request
     * @param mixed $options
     * @return mixed
     */
    protected function getSerializer($request, $options = []) {
        $class = $this->getSerializerClass($request);
        return new $class($options);
    }
    /**
     * Retorna la relaciones eager a cargar
     * @param En_HttpRequest $request
     * @return string[]
     */
    protected function getRelations($request) {
        return $this->relationsEager;
    }
    /**
     * Retorna StandardFilter con todos los parametros cargados
     * @param En_HttpRequest $request
     * @return StandardFilter
     */
    protected function getFilter($request) {        
        return new StandardFilter($request, true, $this->filters);
    }
    /**
     * Retorna la lista de objetos a devolver con o sin paginacion
     * @param En_HttpRequest $request
     * @return mixed[]
     */
    protected function getList($request) {
        $filter = $this->getFilter($request);
        $relations = $this->getRelations($request);
        $result = null;
        if ($this->paginate) {
            $result['results'] = $this->model::db()->query_list($relations, $filter, $this->searchs, true, $this->relationManyToMany);
            $count = $this->model::db()->query_list_pager($relations, $filter, $this->searchs);
            $limit = $filter->getPager() ? $filter->getPager()['per_page'] : $count;
            $page = $filter->getPager() ? $filter->getPager()['page'] : 1;
            $result['pager'] = new StandardPagination((int)$limit, $count, (int)$page);
        } else {
            $result = $this->model::db()->query_list($relations, $filter, $this->searchs, false);
        }
        return $result;
    }
    /**
     * Retorna el objeto a devolver
     * @param En_HttpRequest $request
     * @param mixed $pk
     * @return mixed
     */
    protected function getObject($request, $pk) {
        return $this->model::db()->query_get($pk, $this->getRelations($request));
    }
}