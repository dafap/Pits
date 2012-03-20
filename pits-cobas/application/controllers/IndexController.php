<?php
/**
 * Le contrôleur d'entrée anonyme
 *
 * Ce contrôleur donne accès à la création de compte, à la demande d'un nouveau mot de passe et au login
 *
 * @project pits
 * @package application/controllers
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 1 mars 2011
 * @version $Id: $
 */
class IndexController extends Ap_Controller_Action
{
    private $_messageError;
    /**
     * (non-PHPdoc)
     * @see Ap_Controller_Action::init()
     */
    public function init()
    {
        parent::init();
    }

    /**
     * Page d'accueil
     */
    public function indexAction()
    {
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'login');
        $form = new Zend_Form($configForm->ap->login);
        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            $this->login($form->getValues());
        }
        $this->view->messageError =  isset($this->_messageError) ? $this->_messageError : '';
        $this->view->form = $form;
        $this->view->siteOuvert = $this->_siteOuvert;
    }

    /**
     *
     * Logout : destruction de la session et retour à la page d'accueil
     */
    public function logoutAction()
    {
        Zend_Session::destroy();
        $this->_redirect('.');
    }

    /**
     * Oubli du mot de passe
     */
    public function oublimdpAction()
    {
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'oublimdp');
        $form = new Zend_Form($configForm->ap->oublimdp);
        $form->setAction($this->view->link('index', 'oublimdp'));
        $this->view->op = 'form';
        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            $this->view->email = $form->getValue('email');
            // enregistrement et envoi du mot de passe
            if ($this->_setNewPasswordIntoUserAndSendMail($form->getValue('email')))  {
                $this->view->op = 'envoye';
            } else {
                $this->view->op = 'inconnu';
            }
        } else {
            $this->view->form = $form;
        }
    }

    /**
     * Créer un compte
     */
    public function creercompteAction()
    {
        // fiche vide
        $records = new Pits_Model_DbTable_TUser();
        $record = $records->createRow(); 
        // formulaire de saisie
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'creercompte');
        $form = new Pits_Form_User($configForm->ap->creercompte);
        // ajout des villes
        $villes = new Pits_Model_DbTable_TVilles();
        $liste = $villes->zone();
        $form->getElement('Commune')->addMultiOptions($liste);
        // ajout de l'action et initialisation
        $form->setAction($this->view->link('index', 'creercompte'))->setDefaults($record->toArray());
        // traitement de la réponse (il y a un token, donc cela suffit)
        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            // valeurs renvoyées par le formulaire
            $formValues = $form->getValues();
            // suppression des champs de controle
            $record->setFromArray(array_intersect_key($formValues, $record->toArray()));
            // ajout des dates et du témoin pour validation, codage du mdp
            $dateInitiale = Pits_Model_Format::date('YYYY-MM-dd HH:mm:ss', 'init');
            $dateCreation = Pits_Model_Format::date('YYYY-MM-dd HH:mm:ss');
            $record->dateCreation= $dateCreation;
            $record->dateModif = $dateInitiale;
            $record->dateLastLogin = $dateInitiale;
            $record->temoin = md5($dateCreation);
            $mdp = $record->mdp;
            $record->mdp = sha1($mdp);
            // traitement du tableau de valeurs
            $this->view->email = $record->Email;
            try {
                $record->save();
                $this->view->op = 'ok';
                $this->_helper->userMail($mdp, $record->toArray());
            } catch (Exception $e) {
                $this->view->op = 'echec';
            }
        } else {
            $this->view->op = 'form';
            $this->view->form = $form;
        }
    }

    /*
     * Consulter le réglement du service
     */
    public function helpAction()
    {
        $this->view->liste = $this->_helper->ListeDocuments('public/documents');
        $this->view->docPath = $this->getUrlDocs();
    }

    /**
     * Vérifie que cet email est enregistré dans la table User
     * Si oui, il calcule un mot de passe, l'enregistre dans la table Users et envoi un mail
     *
     * Si tout va bien, retourne true, sinon retourne false
     *
     * @param string $email
     * @return boolean
     */
    private function _setNewPasswordIntoUserAndSendMail($email)
    {
        $mdp = $this->_getNewPassword();
        $ok = true;

        $records = new Pits_Model_DbTable_TUser();
        $where = $records->select()->where('bloque=0 And Email = ?', $email);
        $record = $records->fetchRow($where);
        if (is_null($record)) {
            $ok = false;
        } else {
            try {
                $record->setMdp($mdp);
                $this->_helper->userMail($mdp, $record->toArray());
            } catch (Exception $e) {
                $ok = false;
            }
        }
        return $ok;
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
    /**
     * Partie commune à loginAction() et validationAction()
     *
     * @param Pits_Model_DbTable_TUser $record
     */
    private function entrer($record)
    {
        $this->_auth->getStorage()->write($record->toObject(null, 'mdp'));
        Zend_Session::regenerateId();
        switch ($record['categorie']) {
            case 3:
                $forward = 'sadmin';
                break;
            case 2:
                if ($this->_siteOuvert['administration'] == 1) {
                    $forward = 'admin';
                } else {
                    $forward = 'index/logout';
                }
                break;

            default:
                if ($this->_siteOuvert['inscription'] == 1) {
                    $forward = 'parent';
                } else {
                    $forward = 'index/logout';
                }
                break;
        }
        $this->_redirect($forward);
    }
    /**
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
        $dbAuth->getDbSelect()->where('bloque = 0');
        $dbAuth->setCredential($mdp)->setIdentity($email);
        $result = $this->_auth->authenticate($dbAuth);

        $records = new Pits_Model_DbTable_TUser();

        if ($result->isValid()) {
            $where = $records->select()->where('Email = ?', $email);
            $record = $records->fetchRow($where);
            if (!is_null($record)) {
                try {
                    $record->login();
                    $this->entrer($record);
                } catch (Exception $e) {
                    $this->_messageError = $e->getMessage();
                }
            }
        } else {
            $where = $records->select()->where('Email = ?', $email)->where('mdp = ?', sha1($mdp));
            $record = $records->fetchRow($where);
            if (is_null($record)) {
                $this->_messageError = 'L\'authentification a échoué.';
            } else {
                $this->_messageError = 'Ce compte est bloqué. Consultez votre messagerie pour obtenir les instructions nécessaires.';
            }
        }
    }
    /**
     * Validation d'un compte après création
     */
    public function validationAction()
    {
        $email = $this->getRequest()->getParam('e');
        $temoin = $this->getRequest()->getParam('t');
        if (is_null($email) || is_null($temoin)) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $dbAuth = new Zend_Auth_Adapter_DbTable($this->_db, 'user', 'Email', 'temoin');
        $dbAuth->setCredential($temoin)->setIdentity($email);
        $result = $this->_auth->authenticate($dbAuth);
        if ($result->isValid()) {
            $records = new Pits_Model_DbTable_TUser();
            $where = $records->select()->where('Email = ?', $email);
            $record = $records->fetchRow($where);
            if (!is_null($record)) {
                try {
                    $record->debloque();
                    $this->entrer($record);
                } catch (Exception $e) {
                    $this->view->message = $e->getMessage();
                }
            }
        }
    }
    /**
     * Annulation d'un compte après création
     */
    public function annulationAction()
    {
        $email = $this->getRequest()->getParam('e');
        $temoin = $this->getRequest()->getParam('t');
        if (is_null($email) || is_null($temoin)) {
            $this->truandage(__CLASS__, __METHOD__);
        }

        $records = new Pits_Model_DbTable_TUser();
        try {
            if ($records->delete(array('bloque = ?' => 1, 'Email = ?' => $email, 'temoin = ?' => $temoin)) != 0)
            $this->view->ok = true;
        } catch (Exception $e) {
            $this->view->ok = false;
        };
    }
}

