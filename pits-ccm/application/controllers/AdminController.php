<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * Contrôleur de l'application pour les utilisateurs identifiés en tant qu'administrateur
 *
 * @category   pits
 * @package    application
 * @subpackage controllers
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

include_once 'Pits/Controller/Action.php';


/**
 * @category   pits
 * @package    application
 * @suboackage controller
 * @author     pomirol
 */
class AdminController extends Pits_Controller_Action
{
    public function testAction() {
        $this->view->content = 'à affecter';
    }
    /**
     * Met en place le menu admin défini dans application/config/menu.ini
     * (non-PHPdoc)
     * @see library/Zend/Controller/Zend_Controller_Action#init()
     */
    public function init()
    {
        $this->setMenu('admin');
        // Vérification du User authentifié
        $this->_auth = Zend_Auth::getInstance();
        if (!$this->_auth->hasIdentity() || $this->_auth->getIdentity()->categorie < 2) {
            Zend_Session::destroy();
            $this->_helper->redirectorToOrigin();
        }
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
     * Affichage de la page d'accueil
     *
     * @param  void
     * @return void
     */
    public function indexAction()
    {
        $this->view->setTitrePage("Administration du service");
        $this->view->dbusr = new TUser();
        $this->view->dbelv = new TEleves();
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        //$sql = 'SELECT count(*) As NbElv FROM `eleves` INNER JOIN stations ON eleves.CodeStationR1=stations.CodeStation WHERE garder=0';
        //$r =$db->fetchAll($sql);
        //$this->view->nbInscritsMauvaisArrets = $r[0]['NbElv'];
        $sql = 'SELECT max(dateCreation) derniereCreation, max(dateModif) derniereModif, max(dateExtraction) derniereExtraction FROM eleves';
        $r = $db->fetchAll($sql);
        $this->view->derniereCreation = Pits_Format::date("dd/MM/YYYY HH:mm:ss", $r[0]['derniereCreation']);
        $this->view->derniereModif = Pits_Format::date("dd/MM/YYYY HH:mm:ss", $r[0]['derniereModif']);
        $this->view->derniereExtraction = Pits_Format::date("dd/MM/YYYY HH:mm:ss", $r[0]['derniereExtraction']);
        // détruire critereNom et critereEmail de la session si ils existent
    }
    /**
     * logoutAction() appelle LoginController::logout()
     */
    public function logoutAction()
    {
        // détruire critereNom et critereEmail de la session si ils existent
        $this->_redirect('login/logout');
    }

    /**
     * Affiche la liste des utilisateurs
     */
    public function listusersAction()
    {
        if ($this->getRequest()->isPost()) {
            // parametres passés par le formulaire de recherche : 'post'
            $critereNom = isset($_POST['critereNom']) ? $_POST['critereNom'] : '';
            $critereEmail = isset($_POST['critereEmail']) ? $_POST['critereEmail'] : '';
        }
        // page de retour pour le formulaire (champs 'hidden')
        $form = new Pits_Form_Recherche(array('cancel' => $this->view->link('admin', 'index'), ));
        $form->setAction($this->view->link('admin','listusers'))
        ->setMethod('post');

        $usersTable = new TUser();
        $select = $usersTable->select()
        ->where('categorie=1')
        ->order(array('Nom','Prenom'));
        $mysession = new Zend_Session_Namespace('Admin-ListUser');
        if ($this->getRequest()->isPost()) {
            $form->setDefaults($_POST);
            if (isset($critereNom)) {
                $select->where('Nom LIKE ?', $critereNom . '%');
                // mettre critereNom en session
                $mysession->critereNom = $critereNom;
            }
            if (isset($critereEmail)) {
                $select->where('Email LIKE ?', '%' . $critereEmail . '%');
                // mettre critereEmail en session
                $mysession->critereEmail = $critereEmail;
            }
        } else {
            $formDefaults = array();
            // si critereNom est en session
            if (isset($mysession->critereNom)) {
                $select->where('Nom LIKE ?', $mysession->critereNom . '%');
                $formDefaults['critereNom'] = $mysession->critereNom;
            }
            // si critereEmail est en session
            if (isset($mysession->critereEmail)) {
                $select->where('Email LIKE ?', '%' . $mysession->critereEmail . '%');
                $formDefaults['critereEmail'] = $mysession->critereEmail;
            }
            $form->setDefaults($formDefaults);
        }
        // ancienne version 2010
        //$users = $usersTable->fetchAll($select)->toArray();
        // Gestion de la pagination
        //$paginator = Zend_Paginator::factory($users);
        // nouvelle version 2011
        $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage(10);
        $paginator->setCurrentPageNumber($this->view->page = $this->getRequest()->getParam('page'));
        $this->view->users = $paginator;
        $this->view->form = $form;
    }

    
    /**
     * Affiche la liste des élèves
     */
    public function listelevesAction()
    {
        if ($this->getRequest()->isPost()) {
            // parametres passés par le formulaire de recherche : 'post'
            $critereNom = $this->getRequest()->getParam('critereNom', '');
            $critereEmail = $this->getRequest()->getParam('critereEmail', '');
            $critereType = $this->getRequest()->getParam('encours', 0); // inscription terminée par défaut
        }
        $form = new Pits_Form_Recherche(array('cancel' => $this->view->link('admin', 'index'), 'modele' => 'eleve'));
        $form->setAction($this->view->link('admin','listeleves'))
        ->setMethod('post');

        $elevesTable = new TEleveslist();
        $select = $elevesTable->select()->where('nonInscrit = ?', 0)
        ->order(array('Nom','Prenom'));
        //(version 2011)  ->limit(500);
        $mysession = new Zend_Session_Namespace('Admin-ListEleves');
        if ($this->getRequest()->isPost()) {
            $form->setDefaults($_POST);
            if (isset($critereNom)) {
                $select->where('Nom LIKE ?', $critereNom . '%');
                // mettre critereNom en session
                $mysession->critereNom = $critereNom;
            }
            if (isset($critereEmail)) {
                $select->where('EmailR1 LIKE ?', '%' . $critereEmail . '%');
                // mettre critereEmail en session
                $mysession->critereEmail = $critereEmail;
            }
            if ($critereType < 2) {
                $select->where('encours = ?', $critereType);
            }
            $mysession->critereType = $critereType;
        } else {
            $formDefaults = array();
            // si critereNom est en session
            if (isset($mysession->critereNom)) {
                $select->where('Nom LIKE ?', $mysession->critereNom . '%');
                $formDefaults['critereNom'] = $mysession->critereNom;
            }
            // si critereEmail est en session
            if (isset($mysession->critereEmail)) {
                $select->where('EmailR1 LIKE ?', '%' . $mysession->critereEmail . '%');
                $formDefaults['critereEmail'] = $mysession->critereEmail;
            }
            // si critereType est en session
            if (isset($mysession->critereType)) {
                if ($mysession->critereType < 2) $select->where('encours = ?', $mysession->critereType);
                $formDefaults['encours'] = $mysession->critereType;
            }
            $form->setDefaults($formDefaults);
        }
        //$eleves = $elevesTable->fetchAll($select)->toArray();
        // Gestion de la pagination
        //$paginator = Zend_Paginator::factory($eleves);
        // nouvelle version 2011
        $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage(10);
        $params = $this->getRequest()->getParams();
        $page = isset($params['page']) ? $params['page'] : 1;
        $paginator->setCurrentPageNumber($this->view->page = $page);
        $this->view->eleves = $paginator;
        $this->view->form = $form;
    }

    /**
     * Affiche la liste des documents et en permet la gestion
     */
    public function helpAction()
    {
        $this->view->setTitrePage('Gestion des documents de l\'aide en ligne');
        // Lecture de la liste des documents
        $this->view->liste = $this->_helper->ListeDocuments('documents');
        $this->view->urlBase = $this->getUrlDocs('documents');
    }

    public function supprhelpAction()
    {
        if ($this->_hasParam('d')) {
            $path = Zend_Registry::get('indexPath') . DIRECTORY_SEPARATOR . 'documents';
            $res = unlink($path . DIRECTORY_SEPARATOR . $this->getRequest()->getParam('d'));
        }
        $this->_redirect('admin/help');
    }
    /**
     * Met en ligne un document (formats autorisés : pdf, doc)
     */
    public function addhelpAction()
    {
        $form = new Pits_Form_GestionHelp(array('cancel' => $this->view->link('admin', 'help'),));
        $form->setAction($this->view->link('admin','addhelp'))
        ->setMethod('post')
        ->setDestination(Zend_Registry::get('indexPath') . DIRECTORY_SEPARATOR . 'documents');
         
        // Enregistrement de l'utilisateur
        if ($this->getRequest()->isPost()) {
            if ($form->isValid(array())) {
                $this->view->forward = $this->view->link('admin', 'help');
                // récupérer le fichier temporaire au bon endroit sous son nom d'origine
                if ($form->fileHelp->receive()) {
                    // appel d'une vue pour annoncer le succès
                    $this->view->message = 'Le fichier est en ligne.';
                } else {
                    // appel d'une vue pour annoncer que le fichier n'a pas été mis en ligne
                    $this->view->message = 'La mise en ligne a échoué.';
                }
            } // else le fichier temporaire est détruit en fin d'exécution du script
        }
        $this->view->form = $form;
    }

    /**
     * Extraction d'une liste d'élèves pour BdTS
     *
     * Ce controleur affiche une Form pour saisir la date [depuis] à partir de laquelle on veut
     * extraire les élèves (on pourait également préciser ici la liste des champs à extraire)
     * On précise si on veut prendre les fiches créées, les fiches modifiées ou les deux
     *         et si on prend les fiches encours ou non
     * On appelle la méthode TEleves::extraction()
     * On récupère le flux et on l'envoie dans la réponse html
     */
    public function extractionAction()
    {
        /**
         * Initialiser la Form pour saisir les critères
         */
        // titre de la vue
        $this->view->setTitrePage('Extraction d\'élèves');
        // Passerelle ves la table élèves
        $televes = new TEleves();
        // Date de la dernière extraction
        $dateLastExtraction = $televes->getDateLastExtraction();        
        // Définition du formulaire
        $form = new Pits_Form_ExtractionDepuis(array('cancel' => $this->view->link('admin', 'index'),));
        $form->setAction($this->view->link('admin', 'extraction'))
        ->setMethod('post')
        ->setDateInitiale($dateLastExtraction);
        $this->view->form = $form;
        /**
         * Traitement des critères envoyés
         */
        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            // Retrait des informations depuis les données en POST
            $depuisJ = $this->getRequest()->getParam('depuisJ', '01/01/1900');
            $depuisH = $this->getRequest()->getParam('depuisH', '00:00:00');
            $encours = $this->getRequest()->getParam('encours', 0); // par défaut, on prend les inscriptions terminées
            // ouverture d'un flux temporaire
            $fd = fopen('php://temp', 'r+');
            // lancement de l'extraction
            $televes->extraction($fd, $depuisJ, $depuisH, $encours);
            // envoi des données dans un fichier csv
            $this->_helper->viewRenderer->setNoRender(true);
            $this->_helper->layout->disableLayout();
            $this->getResponse()->setHeader('Content-type', 'text/csv');
            $this->getResponse()->setHeader('Content-disposition','attachment; filename="pits-eleves.csv"');
            $this->getResponse()->setBody(stream_get_contents($fd));
            fclose($fd);
        }
    }
    /**
     * Marque la fiche comme non bloquée
     */
    public function debloqueAction()
    {
        $usersTable = new  TUser();
        $params = $this->getRequest()->getParams();
        $isUpdate = isset($params['u']);
        if ($isUpdate) {
            $userId = (int) $params['u'];
            $usersTable->debloque($userId);
        }
        // envoi du mdp par mail
        $user = $usersTable->find($userId)->current();
        $this->_helper->userMail($mdp, $user->toArray(), 'mail_user_debloque.txt');

        $url = 'admin/listusers';
        if (isset($params['p'])) {
            $url .= '/page/' . $params['p'];
        }
        $this->_redirect($url);
    }
    /**
     * Propose de changer le mot de passe
     */
    public function changemdpAction()
    {
        $baseUrl = $this->getBaseUrl();
        if ($this->getRequest()->isPost()) {
            $userId = isset($_POST['userId']) ? (int) $_POST['userId'] : -1;
            $forward = isset($_POST['forward']) ? $_POST['forward'] : 'index';
            $url = $this->getBaseUrl() . '/' . $forward;
        } else {
            $params = $this->getRequest()->getParams();
            $userId = isset($params['u']) ? (int) $params['u'] : -1;
            $page = isset($params['p']) ? $params['p'] : 1;
            $url = $this->view->link('admin', 'listusers', null, array('page' => $page,));
            $forward = $this->getRelativeUrl($url);
        }
        if ($userId == -1) {
            // erreur : pas de fiche sélectionnée
            Zend_Auth::getInstance()->clearIdentity();
            $this->_redirect('index');
        } else {
            $form = new Pits_Form_ChangeMdp(array('cancel' => $url, 'forward' => $forward,));
            $form->setAction($this->view->link('admin','changemdp'))
            ->setMethod('post')
            ->setDefaults(array('userId' => $userId,));

            if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
                $mdp = $_POST['mdp'];
                $usersTable = new TUser();
                $usersTable->setMdp($mdp, $userId);
                // envoi du mdp par mail
                $user = $usersTable->find($userId)->current();
                $this->_helper->userMail($mdp, $user->toArray());
                // retour à l'origine de l'appel
                $this->_redirect($forward);
            }
            $this->view->setTitrePage('Donner un mot de passe');
            $this->view->form = $form;
        }
    }
    public function suppruserAction()
    {
        // Récupération des paramètres du formulaire de modification
        $userId = $this->getRequest()->getParam('u', -1);
        $page = $this->getRequest()->getParam('p', 1);
        $url = 'admin/listusers/page/' . $page;
        $url = $this->view->link('admin', 'listusers', null, array('page' => $page,));
        if ($userId == -1) {
            $this->truandage(__CLASS__, __METHOD__);
        } else {
            // charge le user
            $usersTable = new TUser();
            $user = $usersTable->find($userId)->current();
            if ($this->_hasParam('confirmation')) {
                // Vérifie qu'il n'a pas d'enfant
                $enfants = $user->findTEleves()->toArray();
                if (! empty($enfants)) {
                    $this->view->url = $url;
                    $this->view->user = $user;
                    $this->view->enfants = $enfants;
                } else {
                    // Tente de supprimer
                    try {
                        $usersTable->delete("userId=$userId");
                    } catch (Zend_Exception $e) {
                        throw new Pits_UserException("Cet utilisateur n'existe pas.");
                    }
                    // Retour à la liste
                    $this->_redirect($this->getRelativeUrl($url));
                }
            } else {
                // prépare la vue
                $form = new Pits_Form_Confirmation(array('cancel'=>$url, 'hidden'=> array('u'=>$userId, 'p'=>$page)));
                $form->setAction($this->view->link('admin','suppruser'))
                ->setMethod('post');
                $this->view->form = $form;
                $this->view->user = $user;
            }
        }
    }
    public function suppreleveAction()
    {
        // Récupération des paramètres du formulaire de modification
        $params = $this->getRequest()->getParams();
        $eleveId = isset($params['e']) ? (int) $params['e'] : -1;
        $page = isset($params['p']) ? $params['p'] : 1;
        if (isset($params['u'])) {
            $url = $this->view->link('admin', 'voirenfants', null, array('u' => $params['u'], 'p' => $page));
        } else {
            $url = $this->view->link('admin', 'listeleves', null, array('page' => $page));
        }
        if ($eleveId == -1) {
            $this->truandage(__CLASS__, __METHOD__);
        } else {
            $elevesTable = new TEleves();
            if ($this->_hasParam('confirmation')) {
                // Tente de supprimer
                try {
                    $elevesTable->delete("eleveId=$eleveId");
                } catch (Zend_Exception $e) {
                    throw new Pits_EleveException("Cet élève n'existe pas.");
                }
                // Retour à la liste
                $this->_redirect($this->getRelativeUrl($url));
            } else {
                // définit le formulaire
                $hiddens = array('e'=>$eleveId, 'p'=>$page,);
                if (isset($params['u'])) {
                    $hiddens['u'] = $params['u'];
                }
                $form = new Pits_Form_Confirmation(array('cancel'=>$url, 'hidden'=> $hiddens));
                $form->setAction($this->view->link('admin','suppreleve'))
                ->setMethod('post');
                // charge l'eleve
                $this->view->eleve = $elevesTable->find($eleveId)->current();
                $this->view->form = $form;
            }
        }
    }
    /**
     * Voir les enfants inscrits par un utilisateur
     */
    public function voirenfantsAction()
    {
        // Récupération des paramètres du formulaire de modification
        $params = $this->getRequest()->getParams();
        $userId = isset($params['u']) ? (int) $params['u'] : -1;
        $page = isset($params['p']) ? $params['p'] : 1;
        $listuser = $this->view->link('admin', 'listusers', null, array('page' => $page));
        if ($userId == -1) {
            $this->truandage(__CLASS__, __METHOD__);
        } else {
            $usersTable = new TUser();
            // Cherche ses enfants
            $user = $usersTable->find($userId)->current();
            $enfants = $user->findTEleves();
            $televeslist = new TEleveslist();
            // Crée la liste des enfants
            $enfantslist = array();
            foreach ($enfants as $enfant) {
                $enfantslist[] = $televeslist->find($enfant->eleveId)->current();
            }
            // Passe la liste et les paramètres à la vue
            $this->view->listusers = $listuser;
            $this->view->page = $page;
            $this->view->userNom = $user->Nom;
            $this->view->userPrenom = $user->Prenom;
            $this->view->enfants = $enfantslist;
        }
    }
    /**
     * Affichage et traitement du formulaire d'un élève inscrit pour modification par un admin
     *
     * @param void
     * @return void
     */
    public function editeleveAction()
    {
        // Récupération des paramètres du formulaire de modification
        $params = $this->getRequest()->getParams();
        $isUpdate = isset($params['elv']);
        $voirenfants = isset($params['u']) && isset($params['p']);
        $listeleves = isset($params['page']);
        if ($isUpdate && ($voirenfants || $listeleves)) {
            if ($voirenfants) {
                $action  = '/admin/editeleve/elv/' . $params['elv'] . '/u/' . $params['u'] . '/p/' . $params['p'];
                $forward = 'admin/voirenfants/u/' . $params['u'] . '/p/' . $params['p'];
            } else {
                $action  = '/admin/editeleve/elv/' . $params['elv'] . '/page/' . $params['page'];
                $forward = 'admin/listeleves/page/' . $params['page'];
            }
            // initialisation à partir de la table eleve de la base
            $elevesTable = new TEleves();
            $eleve = $elevesTable->find((int) $params['elv'])->current();

            // traitement du format de dateN
            $eleve->DateN = Pits_Format::date("dd/MM/YYYY", $eleve->DateN);

            // formulaire d'inscription
            $initForm = array(
                'forward' => $forward,
                'cancel'  => $this->getBaseUrl() . '/' . $forward,
            );
            $this->_helper->editEleve($this->view,
            $eleve,
            $this->getBaseUrl() . $action,
            $initForm);
        } else {
            $this->truandage(__CLASS__, __METHOD__);
        }
    }
    /**
     * L'action 'pdf' va :
     * - créer un pdf
     * - l'afficher à l'écran
     */
    public function pdfeleveAction()
    {
        // Vérification de la présence du paramètre elv
        if ($this->_hasParam('elv')) {
            $eleveId = (int) $this->getRequest()->getParam('elv');
            // Chargement de la fiche
            $elevesTable = new TEleves();
            $eleve = $elevesTable->find($eleveId)->current();
            if (!is_null($eleve)) {
                $this->_helper->viewRenderer->setNoRender(true);
                $this->_helper->layout->disableLayout();
                $this->_helper->exportEleves('pdf', $eleveId);
            } else {
                $this->truandage(__CLASS__, __METHOD__);
            }
        } else {
            $this->truandage(__CLASS__, __METHOD__);
        }
    }

    public function statistiquesAction()
    {
    }
    /**
     * Statistiques par établissement
     */
    public function statparetabAction()
    {
        $filtre = $this->getRequest()->getParam('filtre', '');
        if ($filtre == 'inscrit') {
            $where = 'nonInscrit = 0 And encours = 0';
        } elseif ($filtre == 'encours') {
            $where = 'nonInscrit = 0 And encours = 1';
        } else {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $select = $db->select()
        ->from('eleveslist', array('ville' => 'VilleEcole', 'ecole' => 'NomEcole', 'nb' => 'count(eleveId)'))
        ->where($where)
        ->group(array('VilleEcole', 'NomEcole'));
        $res = $db->query($select);
        $this->view->stats = $res->fetchAll();
    }
    /**
     * Statistiques par station
     */
    public function statparstationAction()
    {
        $filtre = $this->getRequest()->getParam('filtre', '');
        if ($filtre == 'inscrit') {
            $where = 'nonInscrit = 0 And encours = 0';
        } elseif ($filtre == 'encours') {
            $where = 'nonInscrit = 0 And encours = 1';
        } else {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $select = $db->select()
        ->from('eleveslist', array('ville' => 'VilleStationR1', 'station' => 'NomStationR1', 'nb' => 'count(eleveId)'))
        ->where($where)
        ->group(array('VilleStationR1', 'NomStationR1'));
        $res = $db->query($select);
        $this->view->stats = $res->fetchAll();
    }
    /**
     * Statistiques par commune
     */
    public function statparvilleAction()
    {
        $filtre = $this->getRequest()->getParam('filtre', '');
        if ($filtre == 'inscrit') {
            $where = 'nonInscrit = 0 And encours = 0';
        } elseif ($filtre == 'encours') {
            $where = 'nonInscrit = 0 And encours = 1';
        } else {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $select = $db->select()
        ->from('eleveslist', array('ville' => 'NomVilleR1', 'nb' => 'count(eleveId)'))
        ->where($where)
        ->group('NomVilleR1');
        $res = $db->query($select);
        $this->view->stats = $res->fetchAll();
    }
    public function validinscriptionsAction()
    {
        $form = new Pits_Form_ValidInscriptions(array('cancel' => $this->view->link('admin', 'index'),));
        $form->setAction($this->view->link('admin','validinscriptions'))
        ->setMethod('post');
         
        // Enregistrement de l'utilisateur
        if ($this->getRequest()->isPost()) {
            if ($form->isValid(array())) {
                $this->view->forward = $this->view->link('admin', 'index');
                // récupérer le fichier temporaire au bon endroit sous son nom d'origine
                if ($form->pitsValidation->receive()) {
                    // traiter le fichier
                    if ($f = fopen($form->pitsValidation->getFileName(), 'r'))
                    {
                        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
                        $db->beginTransaction();
                        try {
                            while (!feof($f)) {
                                $tmp = trim(fgets($f));
                                if (!empty($tmp)) $db->query($tmp);
                            }
                            $db->commit();
                        } catch (Exception $e) {
                            $db->rollBack();
                            // appel d'une vue pour la gestion d'erreur
                            $this->view->message = 'La validation des inscriptions a échoué.';
                        }
                    }
                    // supprimer le fichier
                    fclose($f);
                    unlink($form->pitsValidation->getFileName());
                    // appel d'une vue pour annoncer le succès
                    $this->view->message = 'Les inscriptions ont été validées.';
                } else {
                    // appel d'une vue pour annoncer qu'il n'y a pas de fichier à traiter
                    $this->view->message = 'Le fichier à traiter n\'a pas été trouvé.';
                }
            } // else le fichier reste temporaire et est détruit en fin d'exécution du script
            //$this->view->message = ''
        }
        $this->view->form = $form;
    }
    public function relanceuserAction()
    {
        // 1. utilisateurs concernés
        $chsql = "SELECT DISTINCT user.NomSA, user.PrenomSA, user.TelephoneC FROM `user` LEFT JOIN eleves ON user.userId=eleves.userId  WHERE eleves.userId IS NULL AND categorie=1 AND TelephoneC<>'';";
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $records = $db->fetchAll($chsql);
        // 2. Génération du flux temporaire (fichier si taille > 2MB)
        $fd = fopen('php://temp', 'r+');
        $cols = array('Nom', 'Prenom', 'Telephone');
        fputcsv($fd, $cols, ';', '"');
        unset($cols);
         // 3. Extraction dans le flux
        foreach ($records as $record) {
            fputcsv($fd, $record, ';', '"');
        }
        rewind($fd);
        // 4. Exportation des données dans un fichier csv
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        $this->getResponse()->setHeader('Content-type', 'text/csv');
        $this->getResponse()->setHeader('Content-disposition','attachment; filename="pits-parents.csv"');
        $this->getResponse()->setBody(stream_get_contents($fd));
        fclose($fd);
    }
    /**
     * Liste des établissements pour gérer la visibilité dans les listes déroulantes 
     */
    public function listeetablissementsAction() 
    {
        $etablissements = new TEtablissements();
        $this->view->liste = $etablissements->liste();
    }
    /**
     * Change l'état de visibilité de l'établissement
     * et retourne sur la fiche
     */
    public function montrercacherAction() 
    {
        $c = $this->getRequest()->getParam('c', -1);
        if ($c == -1) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $etablissements = new TEtablissements();
        $etablissement = $etablissements->find($c)->current();
        if (! is_null($etablissement)) {
            $etablissement->visible = 1 - $etablissement->visible;
            $etablissement->save();
        }
        $this->_redirect('admin/listeetablissements');
    }
}