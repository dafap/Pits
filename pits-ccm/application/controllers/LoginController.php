<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * Contrôleur gérant l'authentification
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

include_once 'Zend/Auth.php';
include_once 'Zend/Auth/Adapter/DbTable.php';
include_once 'Zend/Db/Table/Abstract.php';
include_once 'Zend/Registry.php';
include_once 'Zend/Session.php';
include_once 'Zend/Validate.php';
include_once 'Zend/Validate/NotEmpty.php';
include_once 'Zend/Validate/EmailAddress.php';
include_once 'Zend/Validate/StringLength.php';
include_once 'Pits/Controller/Action.php';

/**
 * @category   pits
 * @package    application
 * @suboackage controller
 * @author     pomirol
 */
class LoginController extends Pits_Controller_Action
{
    /**
     * Instance de Zend_Auth
     *
     * @var Zend_Auth
     */
    private $_auth;
    /**
     * Appelé par le constructeur, intencie l'attribut $_auth
     * (non-PHPdoc)
     * @see library/Zend/Controller/Zend_Controller_Action#init()
     */
    public function init()
    {
        $this->_auth = Zend_Auth::getInstance();
    }
    /**
     * Affiche le formulaire de login ou le message de bienvenue
     *
     * @param  void
     * @return void
     */
    public function indexAction()
    {
        if ($this->_auth->hasIdentity()) {
            $this->_redirect((int) $this->_auth->getIdentity()->categorie == 2 ? 'admin' : ($this->_auth->getIdentity()->categorie == 3 ? 'sadmin' : 'parent'));
        } else {
            $this->_redirect('/');;
        }
    }
    /**
     * Identification
     * Redirige l'utilisateur vers son controleur/indexAction
     * @return void
     */
    public function loginAction()
    {
        // attribut du namespace dans le flashmessenger pour le message d'erreur éventuel
        $this->_helper->redirectorToOrigin->setFlashMessengerNamespace('loginForm');

        // il faut que les variables post du formulaire existent
        if (!$this->_request->isPost() ||
        !$this->_request->getPost('email') ||
        !$this->_request->getPost('passwd')) {
            $this->_helper->redirectorToOrigin('Veuillez donner un email et un mot de passe');
        }
        // création des validateurs
        $validator = new Zend_Validate();
        $validator->addValidator(new Zend_Validate_NotEmpty());
        $validator->addValidator(new Zend_Validate_StringLength(4));
        $validatorLogin = clone $validator;
        $validatorLogin->addValidator(new Zend_Validate_EmailAddress());

        // validation des paramètres d'entrée
        if (!$validatorLogin->isValid($this->_request->getPost('email')) ||
        !$validator->isValid($this->_request->getPost('passwd'))) {
            $this->_helper->redirectorToOrigin('Email ou mot de passe incorrect');
        }

        // création de l'authentificateur
        $db         = Zend_Db_Table_Abstract::getDefaultAdapter();
        // sans cryptage du mdp
        //$dbAdapteur = new Zend_Auth_Adapter_DbTable($db, 'user', 'email', 'mdp');
        // avec crtyptage du mdp
        $dbAdapteur = new Zend_Auth_Adapter_DbTable($db, 'user', 'email', 'mdp', 'SHA1(?)');
        $dbAdapteur->getDbSelect()->where('bloque=0');
        // passage des paramètres à l'authentification
        $dbAdapteur->setCredential($this->_request->getPost('passwd'))
        ->setIdentity($this->_request->getPost('email'));

        // authentification
        $result = $this->_auth->authenticate($dbAdapteur);

        // si l'authentification est OK, écriture en session de l'user (sauf mdp)
        if ($result->isValid()) {
            $authUser = $dbAdapteur->getResultRowObject(null, 'mdp');
            // on enregistre les données de l'utilisateur (date et adresse ip)
            $tuser = new TUser();
            $tuser->infoClient($authUser->userId);
            // décodage de la commune
            $user = $tuser->find($authUser->userId)->current();
            $authUser->Commune = $user->findParentTVilles()->Nom;
            // format des dates
            $authUser->dateCreation = Pits_Format::date('dd/MM/YYYY HH:mm:ss', $authUser->dateCreation);
            $authUser->dateModif = Pits_Format::date('dd/MM/YYYY HH:mm:ss', $authUser->dateModif);
            $authUser->datePreviousLogin = Pits_Format::date('dd/MM/YYYY HH:mm:ss', $authUser->datePreviousLogin);
            // écriture dans l'objet Zend_Auth
            $this->_auth->getStorage()->write($authUser);
            // regénération de l'id de session (évite les fixations de session)
            Zend_Session::regenerateId();
            $this->_redirect($authUser->categorie==2 ? 'admin' : ($authUser->categorie==3 ? 'sadmin' : 'parent'));
        } else {
            $this->_helper->redirectorToOrigin('Email ou mot de passe incorrect');
        }
    }
    /**
     * Cette action est appelée par le lien adressé par mail pour valider l'inscription
     * - effectue un login en initialisant l'attribut _auth comme l'action login
     * - debloque la ligne d'enregistrement en mettant le champ `bloque`à 0
     * - redirige l'utilisateur vers son controleur/indexAction
     * @return void
     */
    public function validationAction()
    {
        // récupérations des paramètres get transmis par l'url
        $params = $this->getRequest()->getParams();
        $isValidation = isset($params['e']) && isset($params['t']);
        if ($isValidation) {
            $email = $params['e'];
            $temoin = $params['t'];
            // passage des paramètres à l'authentification
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $dbAdapteur = new Zend_Auth_Adapter_DbTable($db, 'user', 'email', 'temoin');
            $dbAdapteur->setCredential($temoin)->setIdentity($email);
            $result = $this->_auth->authenticate($dbAdapteur);
            if ($result->isValid()) {
                $authUser = $dbAdapteur->getResultRowObject(null, 'mdp');
                $authUser->bloque = 0;
                $this->_auth->getStorage()->write($authUser);
                // on débloque la fiche en mettant le champ bloque à 0
                $tuser = new TUser();
                $tuser->debloque($authUser->userId);
                // regénération de l'id de session (évite les fixations de session)
                Zend_Session::regenerateId();
                Zend_Registry::set('session',$session);
                $this->_redirect($authUser->categorie==1 ? 'parent' : 'admin');
            } else {
                $this->render('validationrefus');
            }
        } else {
            $this->render('validationrefus');
        }
    }
    /**
     * Cette action est appelée par le lien adressé par email pour annuler la demande d'inscription.
     * Elle supprime la demande de la base.
     * @return void
     */
    public  function annulationAction()
    {
        // récupération des paramètres get transmis par l'url
        $params = $this->getRequest()->getParams();
        $email = $params['e'];
        $temoin = $params['t'];
        // suppression de la table user
        $tuser = new TUser();
        try {
            if ($tuser->delete('bloque = 1 And Email = \'' . $email . '\' And temoin = \'' . $temoin . '\'') == 0) {
                $view = 'annulationrefus';
            } else {
                $view = 'annulation';
            }
        } catch (Zend_Exception $e) {
            throw new Pits_UserException("Cet utilisateur n'existe pas.");
        }
        $this->render($view);
    }
    /**
     * Déconnexion de l'utilisateur
     * La session est totalement détruite afin de détruire aussi les acls
     *
     * @return void
     */
    public function logoutAction()
    {
        $mysession = new Zend_Session_Namespace('Parent-payer');
        $sessionTp = isset($mysession->sessionTp);
        Zend_Session::destroy();
        if ($sessionTp) {
            //$this->_redirect($this->getTelepaiementLogout() . '?u=' . urlencode($this->getBaseUrl(). '/login/logout'));
            $this->_redirect($this->getTelepaiementLogout() . '?u=' . Pits_Methode_Methode::avoca($this->_helper->ServerUrl(true)));
        } else {
            $this->_redirect('/');
        }
    }
    public function killsessionAction() 
    {
        $url = Pits_Methode_Methode::cavoa($this->getRequest()->getParam('u', '/'));
        Zend_Session::destroy();
        $this->_redirect($url);
    }
}