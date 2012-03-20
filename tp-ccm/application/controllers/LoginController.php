<?php
/**
 * Procédures spécifiques de login et logout
 *
 * La methode de login ordinaire est dans IndexController.
 * La méthode receptionAction permet de se loger depuis Pits
 * La méthode logout ferme la session de Pits si l'entrée a été faite par reception
 * La méthode killsession ferme la session à la demande de Pits, lors du logout
 *
 * @project télé-paiement CCM
 * @package application/controller
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 11 oct. 2011
 * @version $Id: $
 */

class LoginController extends Ap_Controller_Action
{
    public function init()
    {
        parent::init();
    }
    /**
     *
     * Gestion d'erreur
     */
    public function indexAction()
    {
        $this->_redirect('index/index');
    }
    /**
     *
     * Ouverture de session depuis une autre application
     */
    public function receptionAction()
    {
        $params = $this->getRequest()->getParams();
        if (array_key_exists('u', $params) && array_key_exists('ctrl', $params)) {
            $userId = $params['u'];
            $ctrl = $params['ctrl'];
            $dbAuth = new Zend_Auth_Adapter_DbTable($this->_db, 'user', 'userId', 'ctrl');
            $dbAuth->setIdentity($userId)->setCredential($ctrl);

            $result = $this->_auth->authenticate($dbAuth);
            if ($result->isValid()) {
                $mysession = new Zend_Session_Namespace('User-payer');
                $mysession->sessionPits = true;
                $records = new Tp_Model_DbTable_TUser();
                $where = $records->select()->where('userId = ?', $userId);
                $record = $records->fetchRow($where);
                if (!is_null($record)) {
                    try {
                        $this->_auth->getStorage()->write($record->toObject(null, 'mdp'));
                        $ctrl = Ap_Methode_Methode::getNewpassword();
                        $record->setCtrl($ctrl);
                        $this->_redirect('user/index');
                    } catch (Ap_Exception $e) {
                        $this->_messageError = $e->getMessage();
                    }
                }
                $this->_redirect('index/index');
            }
        } else {
            $this->_redirect('index/index');
        }
    }
    /**
     * Renvoie sur l'url indiquée après destruction de la session
     *
     */
    public function killsessionAction()
    {
        $url = Ap_Methode_Methode::cavoa($this->getRequest()->getParam('u', '/'));
        Zend_Session::destroy();
        $this->_redirect($url);
    }
    /**
     * Procédure de logout
     * 
     */
    public function logoutAction() {
        $mysession = new Zend_Session_Namespace('User-payer');
        $sessionPits = isset($mysession->sessionPits);
        Zend_Session::destroy();
        if ($sessionPits) {
            $this->_redirect($this->getPitsLogout() . '?u=' . Ap_Methode_Methode::avoca($this->_helper->ServerUrl(true)));
        } else {
            $this->_redirect('index/index');
        }
    }

    private  function getPitsLogout() 
    {
        $pits = $this->getFrontController()->getParam('pits');
        return $pits['logout'];
    }
    
}