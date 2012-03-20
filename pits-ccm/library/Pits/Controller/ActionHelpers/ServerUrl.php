<?php
/**
 * Renvoie l'adresse complète avec le nom de domaine devant
 *
 * Description longue du fichier s'il y en a une
 *
 * @package library/Ap/Controller/ActionHelpers
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 12 oct. 2011
 * @version $Id: $
 */

class Pits_Controller_ActionHelpers_ServerUrl extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Pattern Strategy
     * 
     * @param string $message
     * @return void
     */
    public function direct($requestUri = null)
    {
        //$server = $this->getFrontController()->getRequest()->getServer();
        //$baseUrl = $this->getFrontController()->getBaseUrl();
        $controllerName = $this->getFrontController()->getRequest()->getControllerName();
        $actionName = $this->getFrontController()->getRequest()->getActionName();
        switch (true) {
            case (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] === true)):
            case (isset($_SERVER['HTTP_SCHEME']) && ($_SERVER['HTTP_SCHEME'] == 'https')):
            case (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] == 443)):
                $scheme = 'https';
                break;
            default:
            $scheme = 'http';   
        }
        if (isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } else if (isset($_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT'])) {
            $name = $_SERVER['SERVER_NAME'];
            $port = $_SERVER['SERVER_PORT'];

            if (($scheme == 'http' && $port == 80) ||
                ($scheme == 'https' && $port == 443)) {
                $host = $name;
            } else {
                $host = $name . ':' . $port;
            }
        }
        if ($requestUri === true) {
            $path = $_SERVER['REQUEST_URI'];
        } else if (is_string($requestUri)) {
            $path = $requestUri;
        } else {
            $path = '';
        }

        return $scheme . '://' . $host . $path;
        
        //Zend_Debug::dump(array('server' => $server, 'baseUrl' => $baseUrl, 'controllerName' => $controllerName, 'actionName' => $actionName)); exit;
    }    
}