<?php
/**
 * Gestion de l'espace du super administrateur
 *
 * @project télé-paiement CCM
 * @package application/controllers
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 8 sept. 2011
 * @version $Id: $
 */

class SadminController extends Ap_Controller_Action
{
    /**
     * (non-PHPdoc)
     * @see Ap_Controller_Action::init()
     */
    public function init()
    {
        parent::init();
        if (!$this->_auth->hasIdentity() || $this->_auth->getIdentity()->categorie < 3) $this->_redirect('/login/logout');
        // Mise en place du menu
        $this->setMenu('sadmin');
    }
    public function indexAction() {
        ;
    }
    /**
     *
     * Logout
     */
    public function logoutAction() {
        $this->_redirect('/index/logout');
    }
}
