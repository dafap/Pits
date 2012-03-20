<?php
/**
 * Gestion de l'espace des visiteurs anonymes
 *
 * @project télé-paiement CCM
 * @package application/controllers
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 8 sept. 2011
 * @version $Id: $
 */

class IndexController extends Ap_Controller_Action
{
    private $_messageError;

    public function init()
     {
         parent::init();
         $this->setMenu('visiteur');     
     }

    public function indexAction()
    {
        // construction du formulaire (dans forms.ini)
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'login');
        $form = new Zend_Form($configForm->ap->login);
        $form->setAction($this->view->link('index', 'index'));
        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            $this->login($form->getValues());
        }
        $this->view->messageError =  isset($this->_messageError) ? $this->_messageError : '';
        $this->view->form = $form;
        // contrôle d'ouverture du site (dans application.ini)
        $this->view->siteOuvertSadmin = $this->_site['ouvert']['sadmin'] == 1;
        $this->view->siteOuvertAdmin = $this->_site['ouvert']['admin'] == 1;
        $this->view->siteOuvertUser = $this->_site['ouvert']['user'] == 1;
        $this->view->siteAnnonceOuverture = $this->_site['annonce']['ouverture'];
        // coordonnées de l'organisateur (dans application.ini)
        $this->view->organisateurNom = $this->_organisateur['nom'];
        $this->view->organisateurAdresse = $this->_organisateur['adresse'];
    }

    public function helpAction() {
        $this->view->liste = $this->_helper->ListeDocuments('public/documents');
        $this->view->docPath = $this->getUrlDocs();
    }

    public function oublimdpAction() {
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'oublimdp');
        $form = new Zend_Form($configForm->ap->oublimdp);
        $form->setAction($this->view->link('index', 'oublimdp'));
        $this->view->op = 'form';
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $this->view->email = $form->getValue('email');
            // enregistrement et envoi du mot de passe
            switch ($this->_setNewPasswordIntoUserAndSendMail($form->getValue('email'))) {
                case 0:
                    $this->view->op = 'envoye';
                    break;
                case 1:
                    $this->view->op = 'change';
                    // coordonnées de l'organisateur (dans application.ini)
                    $this->view->organisateurNom = $this->_organisateur['nom'];
                    $this->view->organisateurAdresse = $this->_organisateur['adresse'];
                    $this->view->messageError = $this->_messageError;
                    break;
                case 2:
                    $this->view->op = 'inconnu';
                    break;
                default:
                    throw new Ap_Exception("Erreur inconnue dans la méthode IndexController::oublimdpAction() !");
                    break;
            }
        } else {
            $this->view->form = $form;
        }
    }

    public function logoutAction() {
        /*$mysession = new Zend_Session_Namespace('User-payer');
        $sessionPits = isset($mysession->sessionPits);
        Zend_Session::destroy();
        if ($sessionPits) {
            $this->_redirect($this->getPitsLogout() . '?u=' . urlencode($this->_helper->ServerUrl()));
        } else {
            $this->_redirect('index/index');
        }*/
        $this->_redirect('login/logout');
    }
    /**
     * Partie commune à loginAction() et validationAction()
     *
     * @param Tp_Model_DbTable_TUser $record
     */
    private function entrer($record)
    {
        $this->_auth->getStorage()->write($record->toObject(null, 'mdp'));
        Zend_Session::regenerateId();
        switch ($record['categorie']) {
            case 3:
                if ($this->_site['ouvert']['admin'] == 1) {
                    $forward = 'sadmin';
                } else {
                    $forward = 'index/logout';
                }
                break;
            case 2:
                if ($this->_site['ouvert']['admin'] == 1) {
                    $forward = 'admin';
                } else {
                    $forward = 'index/logout';
                }
                break;

            default:
                if ($this->_site['ouvert']['user'] == 1) {
                    $forward = 'user';
                } else {
                    $forward = 'index/logout';
                }
                break;
        }
        $this->_redirect($forward);
    }    /**
    * Contrôle de login
    */
    public function login($login)
    {
        $email = $login['email'];
        $mdp = $login['password'];
        if (is_null($email) || is_null($mdp)) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $dbAuth = new Zend_Auth_Adapter_DbTable($this->_db, 'user', 'Email', 'mdp', 'SHA1(?)');
        $dbAuth->setCredential($mdp)->setIdentity($email);
        $result = $this->_auth->authenticate($dbAuth);

        $records = new Tp_Model_DbTable_TUser();

        if ($result->isValid()) {
            $where = $records->select()->where('Email = ?', $email);
            $record = $records->fetchRow($where);
            if (!is_null($record)) {
                try {
                    $record->login();
                    $this->entrer($record);
                } catch (Ap_Exception $e) {
                    $this->_messageError = $e->getMessage();
                }
            }
        } else {
            $this->_messageError = 'L\'authentification a échoué.';
        }
    }
    /**
     * Vérifie que cet email est enregistré dans la table User
     * Si oui, il calcule un mot de passe, l'enregistre dans la table Users et envoi un mail
     *
     * Valeurs de retour :
     * 0 : le mot de passe est changé et le mail est envoyé
     * 1 : le mot de passe est changé mais l'envoi du mail a échoué
     * 2 : le mail est inconnu dans la table user
     *
     * @param string $email
     * @return integer
     */
    private function _setNewPasswordIntoUserAndSendMail($email)
    {
        $mdp = Ap_Methode_Methode::getNewPassword();
        $cr = 0;

        $records = new Tp_Model_DbTable_TUser();
        $where = $records->select()->where('bloque=0 And Email = ?', $email);
        $record = $records->fetchRow($where);
        if (is_null($record)) {
            $cr = 2;
        } else {
            $record->setMdp($mdp);
            try {
                $this->_helper->userMail($mdp, $record->toArray());
            } catch (Ap_Exception $e) {
                $this->_messageError = $e->getMessage();
                $cr = 1;
            }
        }
        return $cr;
    }
    /**
     * Calcule un nouveau mot de passe de 7 caractères
     *
     * @return string
     */
    private function _getNewPassword() {
        $lettres = 'azertyuiopqsdfghjklmwxcvbn&(-_)?!:%+=';
        $mdp = array();
        // nb de chiffres de 2 à 4
        $nbc = rand(2, 4);
        // places des chiffres
        for ($i = 0; $i < $nbc; ) {
            $r = rand(0, 6);
            if (isset($mdp[$r])) continue; // un chiffre est déjà en place
            $mdp[$r] = rand(0, 9);
            $i++;
        }
        for ($r = 0, $l = strlen($lettres)-1; $r < 7; $r++) {
            if (isset($mdp[$r])) continue; // il y a un chiffre
            $mdp[$r] = substr($lettres, rand(0, $l), 1);
        }
        // transforme le tableau en chaine
        return implode('', $mdp);
    }

}

