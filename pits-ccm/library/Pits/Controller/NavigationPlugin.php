<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 1 aout 2010
 * 
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    1.0.0
 * @since      Ce fichier est disponible depuis la version 1.0.0
 */
include_once 'Zend/Controller/Plugin/Abstract.php';

/**
 * Classe donnant un comportement attendu aux erreurs d'url
 * Si l'url comporte un controller faux, redirige vers le controller par défaut
 * Si l'url comporte une action fausse, redirige vers l'action par dédaut du controller
 * 
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_Controller_NavigationPlugin extends Zend_Controller_Plugin_Abstract
{
    private $_session;
    public function __construct()
    {
        $this->_session = Zend_Registry::get('session');
    }
    /**
     * Hook à la sortie de la boucle de dispatching pour mémoriser
     * l'URi actuelle en vue de l'utiliser pour redirection sur la 
     * page précédente (Pits_Controller_ActionHelpers_RedirectorToOrigin)
     */
    public function dispatchLoopShutdown()
    {
        $this->_session->requestUri = $this->getRequest()->getRequestUri();
    }    
    /**
     * Gestion des erreurs d'Url
     * @param Zend_Controller_Request_Abstract $request
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $front      = Zend_Controller_Front::getInstance();
        $dispatcher = $front->getDispatcher();
        $class      = $dispatcher->getControllerClass($request);
        if (!Zend_Loader::isReadable($class . '.php')) {
            $class = $dispatcher->getDefaultControllerClass($request);
        }
 
        $r      = new ReflectionClass($class);
        $action = $dispatcher->getActionMethod($request);
 
        if (!$r->hasMethod($action)) {
            $defaultAction  = $dispatcher->getDefaultAction();
            $controllerName = $request->getControllerName();
            $response       = $front->getResponse();
            $baseUrl        = $front->getBaseUrl();
            $response->setRedirect($baseUrl . '/' . $controllerName . '/' . $defaultAction);
            $response->sendHeaders();
            exit;
        }
    }
}