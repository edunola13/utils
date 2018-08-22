<?php
namespace Enola\Rest\Filters;

use Enola\Http\Models\En_Filter;
use Enola\Http\Models\En_HttpRequest,Enola\Http\Models\En_HttpResponse;
use Enola\Lib\JsonWebTokens;

/**
 * Filtro que analiza la autorizacion de los usuarios
 * @author Eduardo Sebastian Nola <edunola13@gmail.com>
 */
class AuthorizationProfile extends En_Filter {
    /**
     * Funcion que realiza el filtro correspondiente
     * @param En_HttpRequest $request
     * @param En_HttpResponse $response
     */
    public function filter(En_HttpRequest $request, En_HttpResponse $response){
        //Tipo por defecto
        $userProfile= 'default';
        $actualProfile= $userProfile;
        if($this->context->getAuthentication() == 'session'){
            if(!$request->session->sessionActive()){
                $request->session->startSession();
            }
            if($request->session->exist('profiles')){
                //Si existe le asigno el tipo correspondiente
                $userProfile= $request->session->get('profiles');
            }
        }else{            
            if($request->getToken()){
                try{
                    JsonWebTokens::setSecret_key($this->context->getContextVar('jwt_secret_key'));
                    JsonWebTokens::check($request->getToken());
                    $data= JsonWebTokens::getData($request->getToken());
                    $userProfile= $data['profiles']; 
                } catch (\Exception $e) {
                    $response->sendApiRestEncode(401, array('error' => 'error-token'));
                    return false;
                    //\\Enola\Support\Error::write_log($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine());
                }                
            }
        }
        
        $auth= \Enola\Support\Authorization\Authorization::getInstance();
        $maps= FALSE;
        if(is_array($userProfile)){
            foreach ($userProfile as $profile) {
                $maps= $auth->profileHasAccessToUrl($profile, $request->uriApp, $request->requestMethod);
                $actualProfile= $profile;
                if($maps){break;}
            }
        }else{
            $actualProfile= $userProfile;
            $maps= $auth->profileHasAccessToUrl($userProfile, $request->uriApp, $request->requestMethod);
        }
        
        if(! $maps){
            //Si el perfil no existe y se esta usando sesiones elimina la sesion
            if($auth->getProfile($actualProfile) == NULL && $this->context->getAuthentication() == 'session'){
                $request->session->deleteSession();
            }            
            //Si no tiene permiso es redireccionado a una url o manejado por un controlador
            if(isset($auth->getProfile($actualProfile)['error-redirect'])){
                $response->redirect($auth->getProfile($actualProfile)['error-redirect']);
            }else if(isset($auth->getProfile($actualProfile)['error-forward'])){
                $this->forward($auth->getProfile($actualProfile)['error-forward']);
            }else{
                $response->sendApiRestEncode(401, array('error' => 'no-permissions'));
            }
            return false;
        }
    }
}