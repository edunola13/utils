<?php
namespace Enola\Rest\Controllers;

use Enola\Http\Models\En_HttpRequest, Enola\Http\Models\En_HttpResponse;

interface ModelRestInterface {
    
    public function list_all (En_HttpRequest $request, En_HttpResponse $response);
    public function retrieve (En_HttpRequest $request, En_HttpResponse $response);
    public function create (En_HttpRequest $request, En_HttpResponse $response);
    public function update (En_HttpRequest $request, En_HttpResponse $response);
    public function partial_update (En_HttpRequest $request, En_HttpResponse $response);
    public function destroy (En_HttpRequest $request, En_HttpResponse $response);
    
}