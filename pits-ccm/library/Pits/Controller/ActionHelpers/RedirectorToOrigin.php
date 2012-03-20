<?php
/**
 * Aide d'action permettant la redirection vers la page précédente
 * 
 * @package pits
 * @subpackage controller
 */

include_once 'Zend/Controller/Action/Helper/Abstract.php';
include_once 'Zend/Controller/Action/HelperBroker.php';

class Pits_Controller_ActionHelpers_RedirectorToOrigin extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Pattern Strategy
     * 
     * @param string $message
     * @return void
     */
    public function direct($message = null)
    {
        // Insertion du message dans le flash messenger
        if (!is_null($message)) {
            Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage($message);
        }

        // Redirection
        if (!isset(Zend_Registry::get('session')->requestUri)) {
            $gotoUrl = $this->getFrontController()->getBaseUrl();
        } else {
            $gotoUrl = Zend_Registry::get('session')->requestUri;
        }
        Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->setCode(303)->gotoUrl($gotoUrl, array("prependBase" => false));
    }

    /**
     * Attribue un namespace au flashmessenger
     *
     * @param string $namespace
     * @return Zfbook_Controller_ActionHelpers_RedirectorToOrigin
     */
    public function setFlashMessengerNamespace($namespace)
    {
        Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->setNamespace($namespace);
        return $this;
    }
}
