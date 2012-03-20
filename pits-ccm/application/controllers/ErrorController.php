<?php
/**
 * Application PiTS 
 * Gestion des préinscriptions au service de transports scolaires
 * 
 * Contrôleur d'erreur
 * 
 * Ce contrôleur est appelé par défaut lorsqu'une exception est lancée.
 * 
 * @category   pits
 * @package    application
 * @subpackage controllers
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
*/

include_once 'Zend/Controller/Action.php';
include_once 'Zend/Controller/Exception.php';
include_once 'Zend/Db/Exception.php';
include_once 'Zend/Registry.php';

/**
 * @category   pits
 * @package    application
 * @suboackage controller
 * @author     pomirol
 */
class ErrorController extends Zend_Controller_Action
{
    /**
     * Action lorsqu'une exception est levée dans le système MVC
     * 
     * @param  void
     * @return void
     */
    public function errorAction()
    {
        // récupération du paramètre d'erreur
        $errors = $this->_getParam('error_handler');
        
        // analyse de la provenance de l'erreur
        if ($errors->exception instanceof Zend_Controller_Exception) {
            $log = "notice";
            $this->getResponse()->setHttpResponseCode(404);
            $this->view->setTitrePage("Page introuvable");
            $this->view->message = $this->view->translate("La page que vous demandez n'a pu être trouvée");
        } elseif ($errors->exception instanceof Zend_Db_Exception) {
            $log = "emerg";
            $this->getResponse()->setHttpResponseCode(503);
            $this->view->setTitrePage("Problème de base de données");
            $this->view->message = $this->view->translate("Un Problème de base de données nous empêche de servir votre requête");
        } else {
            $this->getResponse()->setHttpResponseCode(503);
            $log = "alert";
            $this->view->setTitrePage("Erreur de l'application");
            $this->view->message = $this->view->translate("Notre site est momentanément indisponible");
        }
        
        // vide le contenu de la réponse
        $this->_response->clearBody();
        
        // si en mode débug
        if ($this->getInvokeArg('debug') == 1) {

            // écrase le message, affiche l'exception complète
            $this->view->message = $errors->exception;
        }
        
        // enregistrement de l'erreur avec un niveau $log personnalisé
        Zend_Registry::get('log')->$log($errors->exception);
    }
    
    /**
     * Action lors d'un refus des ACLs
     */
    public function unauthorizedAction()
    {
        $this->_response->setHttpResponseCode(403);
        $this->view->setTitrePage("Accès refusé");
    }
}
