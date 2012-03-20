<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * Contrôleur de l'application pour les utilisateurs identifiés en tant que parent
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
include_once 'Zend/Date.php';
include_once 'Zend/Registry.php';
include_once 'models/TEleves.php';
include_once 'models/TUser.php';
include_once 'Pits/UserException.php';
include_once 'Pits/Form/CreationParent.php';
include_once 'Pits/Form/InscriptionEleve.php';

/**
 * @category   pits
 * @package    application
 * @suboackage controllersubstr
 * @author     pomirol
 */
class ParentController extends Pits_Controller_Action
{
    /**
     * Passerelle vers la table eleves
     *
     * @var Zend_Db_Table_Abstract (recevra un TEleves par la méthode init())
     */
    private $_elevesTable;
    /**
     *
     * @var Zend_Auth
     */
    private $_auth;

    /**
     * Met en place le menu parent défini dans application/config/menu.ini
     * (non-PHPdoc)
     * @see library/Zend/Controller/Zend_Controller_Action#init()
     */
    public function init()
    {
        $this->setMenu('parent');
        $this->_elevesTable = new TEleves();
        $this->_auth = Zend_Auth::getInstance();

        // Vérification du User authentifié
        if (!$this->_auth->hasIdentity()) {
            $this->_redirect('login/logout');
        }
        // espace de nom pour message de la page parent/index
        $this->_helper->redirectorToOrigin->setFlashMessengerNamespace('parentIndex');
    }

    /**
     * Enregistre dans log et ferme la session
     * @param string $classe
     * @param string $methode
     */
    public function truandage($classe, $methode)
    {
        $log = Zend_Registry::get('log');
        $message = $classe . ' ' . $methode . ' : Truandage de userId = ' . $this->_auth->getIdentity()->Email . PHP_EOL;
        $params = $this->getRequest()->getParams();
        foreach ($params as $key => $value) {
            $message .= "  $key: $value" . PHP_EOL;
        }
        $log->notice($message);
        $this->_redirect('login/logout');
    }

    /**
     * Affichage de la liste des élèves inscrits par ce parent
     *
     * @param  void
     * @return void
     */
    public function indexAction()
    {
        // Titre de la page
        $this->view->setTitrePage("Transports scolaires - Espace parent");
        // lecture des enfants de ce parent dans la table de la db
        // - d'abord les élèves inscrits
        $this->view->elevesInscrits  = $this->_elevesTable->getInscrits((int) $this->_auth->getIdentity()->userId);
        // - ensuite les élèves en cours d'inscription
        $this->view->eleves  = $this->_elevesTable->getEncours((int) $this->_auth->getIdentity()->userId);
        // liste des enfants non inscrits
        if ($this->view->eleves->count() == 0 && $this->view->elevesInscrits->count() == 0) {
            $this->view->existsNonInscrits  = $this->_elevesTable->nbNonInscrits((int) $this->_auth->getIdentity()->userId) != 0;
        }
        $this->view->anneeScolaire = $this->getAnneeScolaire();
    }
    /**
     * logoutAction() appelle LoginController::logout()
     */
    public function logoutAction()
    {
        $this->_redirect('login/logout');
    }
    /**
     * Affichage et traitement des onglets d'inscription :
     * onglet 1. Phase d'inscription
     * onglet 2. Phase de contrôle
     * onglet 3. Phase de validation
     */
    public function inscrireAction()
    {
        $this->view->phase = $this->getRequest()->getParam('phase', 1);
        if ($this->view->phase == 4 && $this->getRequest()->isPost()) {
            $this->_elevesTable->valideEnfants((int) $this->_auth->getIdentity()->userId);
            $this->_redirect('parent/index');
        } elseif (! in_array($this->view->phase, array(1, 2, 3))) {
            $this->truandage(__CLASS__, __METHOD__);
        } else {
            $this->view->setTitrePage("Transports scolaires - Procédure d'inscription");
            // liste des enfants non inscrits
            $this->view->elevesNonInscrits  = $this->_elevesTable->getNonInscrits((int) $this->_auth->getIdentity()->userId);
            // liste des enfants en cours d'inscription
            $this->view->eleves = $this->_elevesTable->getEncours((int) $this->_auth->getIdentity()->userId);
            // liste des enfants inscrits
            $this->view->elevesInscrits = $this->_elevesTable->getInscrits((int) $this->_auth->getIdentity()->userId);
            if ($this->view->phase == 3) {
                $validForm = new Zend_Config_Ini(Zend_Registry::get('applicationPath') . '/config/forms.ini', 'confirmation');
                $form = new Zend_Form($validForm->ap->confirmation);
                $btn = $form->getElement('precedent');
                $btn->setOptions(array('onclick' => 'window.location.href="' . $this->view->link('parent', 'inscrire', null, array('phase' => 2,)) . '"', ));
                $btn = $form->getElement('cancel');
                $btn->setOptions(array('onclick' => 'window.location.href="' . $this->view->link('parent', 'index') . '"', ));
                $form->setAction($this->view->link('parent', 'inscrire', null, array('phase' => 4)));
                $this->view->form = $form;
            }
        }
        $this->view->anneeScolaire = $this->getAnneeScolaire();
    }
    /**
     * Affichage et traitement du formulaire d'inscription
     *
     * Doit recevoir les paramètres forward et cancel de la forme controller/action?forward=value1&cancel=value2...
     *
     * @param void
     * @return void
     */
    public function addneweleveAction()
    {
        $forward = $this->getRequest()->getParam('forward', null); // interprétation des url : controller/action?param=...
        $cancel = $this->getRequest()->getParam('cancel', null);
        if (is_null($forward) || is_null($cancel)) {
            Zend_Debug::dump($cancel);exit;
            $this->truandage(__CLASS__, __METHOD__);
        }
        // passage de _auth à la view
        $this->view->auth = $this->_auth;

        // Titre de la page
        $this->view->setTitrePage("Transports scolaires");

        if ($this->getEtatInscriptions() == 1) {
            // initialisation des valeurs à partir de l'utilisateur authentifié
            $eleve = $this->_elevesTable->createRow();
            $eleve->userId = $this->_auth->getIdentity()->userId;
            $eleve->SecondeAdresse = 0;
            $eleve->TitreR1 = $this->_auth->getIdentity()->Titre;
            $eleve->NomR1 = $this->_auth->getIdentity()->Nom;
            $eleve->PrenomR1 = $this->_auth->getIdentity()->Prenom;
            $eleve->AdressR1L1 = $this->_auth->getIdentity()->AdresseL1;
            $eleve->AdressR1L2 = $this->_auth->getIdentity()->AdresseL2;
            $eleve->CommuneR1 = $this->_auth->getIdentity()->Commune;
            $eleve->CodePostalR1 = $this->_auth->getIdentity()->CodePostal;
            $eleve->EmailR1 = $this->_auth->getIdentity()->Email;
            $eleve->TelephoneR1 = $this->_auth->getIdentity()->Telephone;
            $eleve->TelephoneR1c = $this->_auth->getIdentity()->TelephoneC;

            // formulaire d'inscription
            $initForm = array(
                'forward' => $forward,
                'cancel'  => $this->getBaseUrl() . '/' . $cancel,
            );
            $this->_helper->editEleve($this->view,
            $eleve,
            $this->view->link('parent', 'addneweleve', null, array('cancel' => $cancel,)),
            $initForm);
        } else {
            $urlRetour = $this->view->link('parent', 'index');
            $btn = $this->view->MakeButton('cancel', 'cancel', $urlRetour, 'Retour');
            $this->view->form = "<p>La période d'inscription est close.</p>" . $btn;
        }
        $this->view->anneeScolaire = $this->getAnneeScolaire();
    }
    /**
     * Affichage et traitement du formulaire d'un élève inscrit pour modification
     *
     * Doit recevoir les paramètres forward et cancel de la forme controller/action?forward=value1&cancel=value2...
     *
     * @param void
     * @return void
     */
    public function editeleveAction()
    {
        $eleveId = (int) $this->getRequest()->getParam('elv', -1);
        $isUpdate = $eleveId > 0;
        $forward = $this->getRequest()->getParam('forward', null); // interprétation des url : controller/action?param=...
        $cancel = $this->getRequest()->getParam('cancel', null);
        if ($eleveId <= 0 || is_null($forward) || is_null($cancel)) {
            $this->truandage(__CLASS__, __METHOD__);
        }

        // passage de _auth à la view
        $this->view->auth = $this->_auth;

        // Vérification de la présence du paramètre elv
        if ($isUpdate) {
            // Vérification de la propriété de l'enfant
            $eleve = $this->_elevesTable->find($eleveId)->current();
            if (!is_null($eleve) && $eleve->userId == $this->_auth->getIdentity()->userId) {
                // traitement du format de dateN
                $eleve->DateN = Pits_Format::date("dd/MM/YYYY", $eleve->DateN);

                // Titre de la page
                $this->view->setTitrePage("Transports scolaires");

                // formulaire d'inscription
                $initForm = array(
                'forward' => $forward,
                'cancel'  => $this->getBaseUrl() . '/' . $cancel,
                );
                $this->_helper->editEleve($this->view,
                $eleve,
                $this->view->link('parent', 'editeleve', null, array('elv' => $eleveId, 'cancel' => $cancel,)),
                $initForm);
            } else {
                $this->truandage(__CLASS__, __METHOD__);
            }
        }
        $this->view->anneeScolaire = $this->getAnneeScolaire();
    }
    public function suppreleveAction()
    {
        // Vérification de la présence des paramètres elv, cancel et forward
        $eleveId = (int) $this->getRequest()->getParam('elv', -1);
        $existsElv = $eleveId > 0;
        $forward = $this->getRequest()->getParam('forward', null); // interprétation des url : controller/action?param=...
        $cancel = $this->getRequest()->getParam('cancel', null);
        if ($eleveId <= 0 || is_null($forward) || is_null($cancel)) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        // Vérification de la propriété de l'enfant
        $eleve = $this->_elevesTable->find($eleveId)->current();
        if (!is_null($eleve) && $eleve->userId == $this->_auth->getIdentity()->userId) {
            if ($this->_hasParam('confirmation')) {
                // Tente de supprimer
                try {
                    $this->_elevesTable->delete("eleveId=$eleveId");
                } catch (Zend_Exception $e) {
                    throw new Pits_UserException("Cet élève n'existe pas.");
                }
                // Retour à la liste
                $this->_redirect(urldecode($forward));
            } else {
                // prépare la vue
                $initForm = array(
                				'forward' => $forward,
                				'cancel'  => $this->getBaseUrl() . '/' . $cancel,
                				'hidden'=> array('elv'=>$eleveId)
                );
                $form = new Pits_Form_Confirmation($initForm);
                $form->setAction($this->view->link('parent','suppreleve', null, array('cancel' => $cancel,)))
                ->setMethod('post');
                $this->view->form = $form;
                $this->view->eleve = $eleve;
            }
        } else {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $this->view->anneeScolaire = $this->getAnneeScolaire();
    }
    /**
     * L'action 'justificatifs' va créer un pdf contenant les justificatifs des enfants de la liste 'inscriptions en cours'
     * - créer le pdf
     * - l'envoyer par mail à l'utilisateur authentifié
     * - l'afficher à l'écran
     */
    public function justificatifsAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        // liste à traiter
        $eleves = $this->_elevesTable
        ->fetchAll($this->_elevesTable->select()
        ->where('userId = ?', (int) $this->_auth->getIdentity()->userId)
        ->where('nonInscrit = 0')
        ->where('encours = 1')
        ->order('prenomSA'));
        if ($eleves->count() > 0) {
            $this->_helper->exportEleves('mail', $eleves);
        }
    }
    /**
     * L'action 'mail' va :
     * - créer un pdf
     * - l'adresser par mail à l'utilisateur qui réalise l'inscription
     * - afficher le pdf à l'écran
     */
    public function maileleveAction()
    {
        // Titre de la page
        $this->view->setTitrePage("Transports scolaires");
        // Vérification de la présence du paramètre elv
        if ($this->_hasParam('elv')) {
            $eleveId = (int) $this->getRequest()->getParam('elv');
            // Vérification de la propriété de l'enfant
            $eleve = $this->_elevesTable->find($eleveId)->current();
            if (!is_null($eleve) && $eleve->userId == $this->_auth->getIdentity()->userId) {
                $this->_helper->viewRenderer->setNoRender(true);
                $this->_helper->layout->disableLayout();
                $this->_helper->exportEleves('mail', $eleveId);
            } else {
                $this->truandage(__CLASS__, __METHOD__);
            }
        } else {
            $this->truandage(__CLASS__, __METHOD__);
        }
    }
    /**
     * Affichage et traitement du formulaire de modification du compte du parent
     *
     * @param void
     * @return void
     */
    public function modifcompteAction()
    {
        $usersTable = new TUser();
        // Récupération des paramètres de modification du compte en session
        $user = $usersTable->find((int)$this->_auth->getIdentity()->userId)->current();
         
        // Titre de la page
        $this->view->setTitrePage("Transports scolaires - modification du compte");

        // formulaire d'inscription
        $form = new Pits_Form_CreationParent(array('cancel' => $this->view->link('parent', 'index'),));
        $form->setAction($this->view->link('parent','modifcompte'))
        ->setMethod('post')
        ->setDefaults($user->toArray());
         
        // Enregistrement de l'utilisateur
        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            // Retrait des informations depuis les données en POST
            $userpost = $form->getValues();
            if (isset($userpost['mdp'])) {
                $mdp = $userpost['mdp'];
                $userpost['mdp'] = sha1($mdp);
            }
            // valeurs du formulaires à enregistrer dans la table
            $userpostIntersect = array_intersect_key($userpost, $user->toArray());
            // valeurs de la table présentes dans le formulaire
            $userIntersect = array_intersect_key($user->toArray(), $userpost);
            // Y a-t-il des changements à enregistrer dans la table ?
            $change = false;
            $changeMdp = false;
            foreach ($userpostIntersect as $key => $value) {
                if ($userIntersect[$key] != $value) {
                    $change = true;
                    if ($key == 'mdp') $changeMdp = true;
                }
            }
            if ($change) {
                $user->setFromArray($userpostIntersect);
                // date de modif
                $user->dateModif = Pits_Format::date('YYYY-MM-dd HH:mm:ss');
                // Enregistre la fiche dans la table user
                $user->save();
                // Mise à jour du user en session
                $user = $usersTable->find((int)$this->_auth->getIdentity()->userId)->current();
                unset($user->mdp);
                // format des dates
                $user->dateCreation = Pits_Format::date('dd/MM/YYYY HH:mm:ss', $user->dateCreation);
                $user->dateModif = Pits_Format::date('dd/MM/YYYY HH:mm:ss', $user->dateModif);
                $user->datePreviousLogin = Pits_Format::date('dd/MM/YYYY HH:mm:ss', $user->datePreviousLogin);
                // décodage de la commune
                $user->Commune = $user->findParentTVilles()->Nom;
                // écriture dans l'objet Zend_Auth
                $this->_auth->getStorage()->write($user);
                // Envoi d'un email donnant la fiche du user - On envoie le mdp s'il est changé
                if ($changeMdp) {
                    $this->_helper->userMail($mdp);
                } else {
                    $this->_helper->userMail();
                }
            }
            // Redirection vers la parent/index
            $this->_redirect('parent');
        }

        // Assignation du formulaire dans la vue pour l'affichage
        $this->view->form = $form;
    }
    /**
     * Accès à la page d'aide
     *
     * @param  void
     * @return void
     */
    public function helpAction()
    {
        $this->view->setTitrePage("Transports scolaires - Documentation");
        // Lecture de la liste des documents
        $this->view->liste = $this->_helper->ListeDocuments('documents');
        $this->view->urlBase = $this->getUrlDocs('documents');
    }
    /**
     * Appelle le module de paiement en passant les parametres d'authentification en get
     *
     * @param void
     * @return void
     */
    public function payerAction()
    {
        $mysession = new Zend_Session_Namespace('Parent-payer');
        $mysession->sessionTp = true;
        $tuser = new TUser();
        $record = $tuser->find($this->_auth->getIdentity()->userId)->current();
        $ctrl = Pits_Methode_Methode::getNewPassword();
        $record->setCtrl($ctrl);
        $this->_redirect($this->getTelepaiementUrl() . '/u/' . $this->_auth->getIdentity()->userId. '/ctrl/' . md5($ctrl));
    }
}
