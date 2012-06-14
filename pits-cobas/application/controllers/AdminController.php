<?php
/**
 * Le contrôleur de l'administrateur
 *
 * L'administrateur a accès au rôle parent
 *
 * @project pits
 * @package application/controllers
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 8 mars 2011
 * @version $Id: $
 */

class AdminController extends Ap_Controller_Action
{
    /**
     * (non-PHPdoc)
     * @see Ap_Controller_Action::init()
     */
    public function init()
    {
        parent::init();
        if (!$this->_auth->hasIdentity() || $this->_auth->getIdentity()->categorie < 2) $this->_redirect('/parent/logout');
    }
    /**
     *
     * Page d'accueil de l'administrateur
     */
    public function indexAction()
    {
        $tuser = new Pits_Model_DbTable_TUser();
        $this->view->nbinscrits = $tuser->inscrits();
        $this->view->nbbloques = $tuser->bloques();

        $televes = new Pits_Model_DbTable_TEleves();
        $this->view->nbUsersAvecEnfants = $televes->nbUsersAvecEnfants();
        $this->view->nbEnfantsInscrits = $televes->nbEnfantsInscrits();
        $this->view->nbEnfantsPreInscrits = $televes->nbEnfantsPreInscrits();
        $dates = $televes->getDatesPourAdmin();
        $this->view->derniereCreation = $dates['derniereCreation'];
        $this->view->derniereModif = $dates['derniereModif'];
        $this->view->derniereExtraction = $dates['derniereExtraction'];

        $tpaybox = new Pits_Model_DbTable_TPaybox();
        $this->view->nbPaybox = $tpaybox->nbPaybox();
        $this->view->totalPaybox = $tpaybox->totalPaybox();
    }
    /**
     *
     * Logout
     */
    public function logoutAction()
    {
        $this->_redirect('/index/logout');
    }
    /**
     *
     * Dresse la liste des utilisateurs, paginée en n comptes
     */
    public function listedesusersAction()
    {
        // formulaire 'critereliste'
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'critereliste');
        $form = new Zend_Form($configForm->ap->critereliste);
        $form->setAction($this->view->link('admin', 'listedesusers'));

        // table TUser
        $usersTable = new Pits_Model_DbTable_TUser();
        $select = $usersTable->select()
        ->where('categorie=1')
        ->order(array('Nom','Prenom'));

        // session
        $mysession = new Zend_Session_Namespace('Admin-ListUser');
        $this->view->mysession = $mysession;

        if ($this->getRequest()->isPost()) {
            $form->setDefaults($_POST);
            // calcul du critère sur le nom
            $critereNom = $this->getRequest()->getParam('critereNom', '');
            $mysession->critereNom = $critereNom;
            if (! empty($critereNom)) {
                $select->where('Nom LIKE ?', $critereNom . '%');
            }
            // calcul du critère sur l'email
            $critereEmail = $this->getRequest()->getParam('critereEmail', '');
            $mysession->critereEmail = $critereEmail;
            if (! empty($critereEmail)) {
                $select->where('Email LIKE ?', '%' . $critereEmail . '%');
            }
        } else {
            $formDefaults = array();
            // si critereNom est en session
            if (isset($mysession->critereNom)) {
                if (! empty($mysession->critereNom)) {
                    $select->where('Nom LIKE ?', $mysession->critereNom . '%');
                }
                $formDefaults['critereNom'] = $mysession->critereNom;
            }
            // si critereEmail est en session
            if (isset($mysession->critereEmail)) {
                if (! empty($mysession->critereEmail)) {
                    $select->where('Email LIKE ?', '%' . $mysession->critereEmail . '%');
                }
                $formDefaults['critereEmail'] = $mysession->critereEmail;
            }
            $form->setDefaults($formDefaults);
        }

        $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage(10);
        $paginator->setCurrentPageNumber($this->view->page = $this->getRequest()->getParam('page'));
        $this->view->users = $paginator;
        $this->view->form = $form;
    }
    /**
     *
     * Affiche la fiche de l'utilisateur indiqué en GET
     */
    public function userficheAction()
    {
        $userId = $this->getRequest()->getParam('u', -1);
        if (!$userId > 0) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $this->view->page = $this->getRequest()->getParam('p', 1);
        $tuser = new Pits_Model_DbTable_TUser();
        $this->view->user = $tuser->find($userId)->current();
    }
    /**
     *
     * Valide le compte de l'utilisateur indiqué en GET
     */
    public function uservalidcompteAction()
    {
        // paramètres d'appel
        $userId = $this->getRequest()->getParam('u', -1);
        if (!$userId > 0) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $page = $this->getRequest()->getParam('p', 1);
        // table user et sa méthode debloque()
        $usersTable = new  Pits_Model_DbTable_TUser();
        $record = $usersTable->find($userId)->current();
        $record->debloque($userId);
        // envoi du mdp par mail
        $user = $usersTable->find($userId)->current();
        try {
            $this->_helper->userMail('', $user->toArray(), 'mail_user_debloque.txt');
            $this->_redirect("admin/listedesusers/page/$page");
        } catch (Exception $e) {
            $this->view->page = $page;
            $this->view->email = $record->Email;
        }

    }
    /**
     *
     * Supprime le compte de l'utilisateur indiqué en GET
     */
    public function usersupprcompteAction()
    {
        // paramètres du formulaire
        $userId = (int) $this->getRequest()->getParam('u', -1);
        if ($userId == -1) $this->truandage(__CLASS__, __METHOD__); // controle indispensable

        $confirme = (int) $this->getRequest()->getParam('confirme', 0);
        $page = (int) $this->getRequest()->getParam('p', 1);

        // traitement de la demande
        $userTable = new Pits_Model_DbTable_TUser();
        if ($this->getRequest()->isPost() && $confirme == 1) {
            // Tente de supprimer
            try {
                $userTable->delete("userId=$userId");
            } catch (Zend_Exception $e) {
                throw new Ap_Exception("Cet utilisateur n'existe pas.");
            }
            // redirection vers l'appelant
            $this->_redirect("admin/listedesusers/page/$page");
        } else {
            // action pour calcul de l'adresse de retour (bouton abandonner)
            $actionRetour = "listedesusers/page/$page";
            // calcul des hiddens du formulaire
            $hiddens = array('u' => $userId, 'p' => $page);

            $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'confirmation');
            $form = new Ap_Form($configForm->ap->confirmation);
            $cancel = $form->getElement('cancel');
            $cancel->setOptions(array('onclick' => "pageRetour('admin','" . $actionRetour . "');"));
            $form->setAction($this->view->link('admin', 'usersupprcompte'))->setHiddens($hiddens);
            $this->view->form = $form;

            $this->view->user = $userTable->find($userId)->current();
        }
    }
    /**
     *
     * Change le mot de passe de l'utilisateur indiqué en GET
     */
    public function usermodmdpAction()
    {
        // paramètres du formulaire
        $userId = (int) $this->getRequest()->getParam('u', -1);
        if ($userId == -1) $this->truandage(__CLASS__, __METHOD__); // controle indispensable
        $page = (int) $this->getRequest()->getParam('p', 1);
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'mdpadmin');
        $form = new Ap_Form($configForm->ap->mdpadmin);
        $cancel = $form->getElement('cancel');
        $actionRetour = "listedesusers/page/$page";
        $cancel->setOptions(array('onclick' => "pageRetour('admin','" . $actionRetour . "');"));
        $hiddens = array('u' => $userId, 'p' => $page);
        $form->setAction($this->view->link('admin', 'usermodmdp'))->setHiddens($hiddens);

        $this->view->retour = false;
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $mdp = $this->getRequest()->getParam('mdp', '');
            if ($mdp != '') {
                $userTable = new Pits_Model_DbTable_TUser();
                $record = $userTable->find($userId)->current();
                $record->setMdp($mdp, 'mdp par admin '); // 13 caractères maximum
                try {
                    $this->_helper->userMail($mdp, $record->toArray());
                    $this->_redirect("admin/listedesusers/page/$page");
                } catch (Exception $e) {
                    $this->view->form = 'Le mot de passe est changé mais l\'adresse indiquée, <spam style="color:blue; font-weight:bold;">' . $record->Email
                    . '</spam>, ne permet pas d\'envoyer le courrier de confirmation.';
                    $this->view->retour = true;
                }
            } else {
                // si le validateur fonctionne cela ne se produit pas
                $this->view->form = 'Le mot de passe est vide !';
                $this->view->retour = true;
            }
        } else {
            $this->view->form = $form;
        }
    }
    /**
     *
     * Affiche la liste des enfants de l'utilisateur indiqué en GET
     */
    public function uservoirenfantsAction()
    {
        // récupération des paramètres
        $userId = (int) $this->getRequest()->getParam('u', -1);
        $page = $this->getRequest()->getParam('p', 1);
        $retour = $this->getBaseUrl() . "/admin/listedesusers/page/$page";
        if ($userId == -1) {
            ;
        } else {
            $usersTable = new Pits_Model_DbTable_TUser();
            // Cherche ses enfants
            $user = $usersTable->find($userId)->current();
            $enfants = $user->findPits_Model_DbTable_TEleves();
            $televeslist = new Pits_Model_DbTable_TEleveslist();
            // Crée la liste des enfants
            $enfantslist = array();
            foreach ($enfants as $enfant) {
                $enfantslist[] = $televeslist->find($enfant->eleveId)->current();
            }

            // Passe la liste et les paramètres à la vue
            $this->view->urlretour = $retour;
            $this->view->page = $page;
            $this->view->userNom = $user->Nom;
            $this->view->userPrenom = $user->Prenom;
            $this->view->enfants = $enfantslist;
        }
    }
    /**
     *
     * Affiche la liste des documents et en permet la gestion
     */
    public function helpAction()
    {
        $this->view->liste = $this->_helper->ListeDocuments('public/documents');
        $this->view->docPath = $this->getUrlDocs();
    }
    /**
     *
     * Met en ligne un document (formats autorisés : pdf, doc)
     */
    public function helpaddAction()
    {
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'helpadd');
        $form = new Zend_Form($configForm->ap->helpadd);
        $form->upload->setDestination(Zend_Registry::get('rootPath') . DIRECTORY_SEPARATOR . 'public/documents');

        // Enregistrement de l'utilisateur
        if ($this->getRequest()->isPost()) {
            if ($form->isValid(array())) {
                $this->view->forward = $this->getBaseUrl() . '/admin/help';
                // récupérer le fichier temporaire au bon endroit sous son nom d'origine
                if ($form->upload->receive()) {
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
     *
     * Supprime un document du répertoire des documents en ligne
     */
    public function helpsupprAction()
    {
        if ($this->_hasParam('d')) {
            $path = Zend_Registry::get('rootPath') . DIRECTORY_SEPARATOR . 'public/documents';
            $res = unlink($path . DIRECTORY_SEPARATOR . $this->getRequest()->getParam('d'));
        }
        $this->_redirect('admin/help');
    }
    /**
     *
     * Dresse la liste des élèves, paginée en n comptes
     */
    public function listedeselevesAction()
    {
        // formulaire 'critereliste'
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'critereliste');
        $form = new Zend_Form($configForm->ap->critereliste);
        $form->setAction($this->view->link('admin', 'listedeseleves'));

        // table TEleveslist (une view dans MySql)
        $elevesTable = new Pits_Model_DbTable_TEleveslist();
        $select = $elevesTable->select()
        ->where('ficheValidee = 1')
        ->order(array('Nom','Prenom'));

        // session
        $mysession = new Zend_Session_Namespace('Admin-ListEleves');
        $this->view->mysession = $mysession;

        if ($this->getRequest()->isPost()) {
            $form->setDefaults($_POST);
            // calcul du critère sur le nom
            $critereNom = $this->getRequest()->getParam('critereNom', '');
            $mysession->critereNom = $critereNom;
            if (! empty($critereNom)) {
                $select->where('Nom LIKE ?', $critereNom . '%');
            }
            // calcul du critère sur l'email
            $critereEmail = $this->getRequest()->getParam('critereEmail', '');
            $mysession->critereEmail = $critereEmail;
            if (! empty($critereEmail)) {
                $select->where('EmailR1 LIKE ?', '%' . $critereEmail . '%')
                ->orWhere('EmailR2 LIKE ?', '%' . $critereEmail . '%');
            }
        } else {
            $formDefaults = array();
            // si critereNom est en session
            if (isset($mysession->critereNom)) {
                if (! empty($mysession->critereNom)) {
                    $select->where('Nom LIKE ?', $mysession->critereNom . '%');
                }
                $formDefaults['critereNom'] = $mysession->critereNom;
            }
            // si critereEmail est en session
            if (isset($mysession->critereEmail)) {
                if (! empty($mysession->critereEmail)) {
                    $select->where('EmailR1 LIKE ?', '%' . $mysession->critereEmail . '%')
                    ->orWhere('EmailR2 LIKE ?', '%' . $critereEmail . '%');
                }
                $formDefaults['critereEmail'] = $mysession->critereEmail;
            }
            $form->setDefaults($formDefaults);
        }

        // Pagination des élèves
        $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage(10);
        $paginator->setCurrentPageNumber($this->view->page = $this->getRequest()->getParam('page'));
        $this->view->eleves = $paginator;
        $this->view->form = $form;;
    }
    /**
     *
     * Edite la fiche d'un élève pour consultation et modication par un admin
     */
    public function eleveeditAction()
    {
        $eleveId = (int) $this->getRequest()->getParam('e', -1);
        if ($eleveId == -1) $this->truandage(__CLASS__, __METHOD__); // controle indispensable
        $userId = (int) $this->getRequest()->getParam('u', -1);
        $page = (int) $this->getRequest()->getParam('p', 1);
        // retour vers ...
        $retour = $userId == -1 ? "listedeseleves/page/$page" : "uservoirenfants/u/$userId/p/$page";
        // traitement de la demande
        $televes = new Pits_Model_DbTable_TEleves();
        $record = $televes->find($eleveId)->current();
        // édition de la fiche
        if ($this->_helper->EditEleve($televes->famille($record->userId),
        $this->view->link('admin', 'eleveedit', null, "/e/$eleveId/u/$userId/p/$page"), // action du formulaire avec passage des paramètres e, u et p
        array('controller' => 'admin', 'action' => $retour), // bouton cancel (onclick)
        $record
        )) {
            $televes->tarifFamille($record->userId);
            $this->_redirect('admin/' . $retour);
        }
    }
    /**
     *
     * Supprime un élève de la table
     */
    public function elevesupprAction()
    {
        // paramètres du formulaire
        $eleveId = (int) $this->getRequest()->getParam('e', -1);
        if ($eleveId == -1) $this->truandage(__CLASS__, __METHOD__); // controle indispensable

        $confirme = (int) $this->getRequest()->getParam('confirme', 0);
        $userId = (int) $this->getRequest()->getParam('u', -1);
        $page = (int) $this->getRequest()->getParam('p', 1);
        // traitement de la demande
        $elevesTable = new Pits_Model_DbTable_TEleves();
        if ($this->getRequest()->isPost() && $confirme == 1) {
            // action pour calcul de l'adresse de retour (bouton abandonner)
            $actionRetour = $userId > 0 ? "uservoirenfants/p/$page" : "listedeseleves/page/$page";
            if ($userId > 0) $actionRetour .= "/u/$userId";
            // Tente de supprimer
            try {
                $elevesTable->delete("eleveId=$eleveId");
            } catch (Zend_Exception $e) {
                throw new Ap_Exception("Cet élève n'existe pas.");
            }
            // redirection vers l'appelant
            $this->_redirect('admin/' . $actionRetour);
        } else {
            // action pour calcul de l'adresse de retour (bouton abandonner)
            $actionRetour = $userId > 0 ? "uservoirenfants/u/$userId/p/$page" : "listedeseleves/page/$page";
            // calcul des hiddens du formulaire
            $hiddens = array('e' => $eleveId, 'p' => $page);
            if ($userId > 0) $hiddens['u'] = $userId;

            $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'confirmation');
            $form = new Ap_Form($configForm->ap->confirmation);
            $cancel = $form->getElement('cancel');
            $cancel->setOptions(array('onclick' => "pageRetour('admin','" . $actionRetour . "');"));
            $form->setAction($this->view->link('admin', 'elevesuppr'))->setHiddens($hiddens);
            $this->view->form = $form;

            $this->view->eleve = $elevesTable->find($eleveId)->current();
        }
    }
    /**
     *
     * Crée un pdf et l'affiche à l'écran
     */
    public function elevepdfAction()
    {
        $eleveId = (int) $this->getRequest()->getParam('e', -1);
        if ($eleveId == -1) $this->truandage(__CLASS__, __METHOD__); // controle indispensable
        $userId = (int) $this->getRequest()->getParam('u', -1);
        $page = (int) $this->getRequest()->getParam('p', 1);
        // Pas de vue, pas de layout
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        try {
            $this->_helper->exportEleves('pdf', $eleveId);
        } catch (Pits_Document_Exception $e) {
            $this->truandage(__CLASS__, __METHOD__);
        }
    }
    /**
     *
     * Extraction des inscriptions pour transfert dans BdTS
     */
    public function extractionAction()
    {
        /**
         * 1. Définir la vue avec la Form pour saisir les critères
         */
        // Date de la dernière extraction
        $televes = new Pits_Model_DbTable_TEleves();
        $depuis = $televes->lastDateExtraction();
        // Définition du formulaire
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'extraction');
        $form = new Zend_Form($configForm->ap->extraction);
        $form->setAction($this->view->link('admin', 'extraction'))
        ->setDefaults(array('depuisJ' => Pits_Model_Format::date("dd/MM/YYYY", $depuis),
                            'depuisH' => Pits_Model_Format::date('HH:mm:ss', $depuis)));
        $this->view->form = $form;
         
        /**
         * Traitement
         */
        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            // Retrait des informations depuis les données en POST
            $values = $form->getValues();
            // date à partir de laquelle on fait l'extraction
            if (!empty($values['depuisJ'])) {
                if (empty($values['depuisH'])) $values['depuisH'] = '00:00:00';
                $depuis = Pits_Model_Format::date("YYYY-MM-dd HH:mm:ss", $values['depuisJ'] . ' ' . $values['depuisH'], 'fr_FR');
            }
            /**
             * 2. Fixer la dateActuelle
             */
            $televes = new Pits_Model_DbTable_TEleves();
            $select = $televes->select();
            $dateExtraction = Pits_Model_Format::date("YYYY-MM-dd HH:mm:ss");
            /**
             * 3. Marquer la dateExtraction
             */
            if (isset($depuis)) {
                $televes->update(array('dateExtraction' => $dateExtraction),
                array('datePaiement > ?' => $depuis, 'ficheValidee = ?' => 1,));
                $televes->update(array('dateExtraction' => $dateExtraction),
                array('dateModif > ?' => $depuis, 'ficheValidee = ?' => 1,));
            } else {
                $televes->update(array('dateExtraction' => $dateExtraction), array('ficheValidee = ?' => 1,));
            }
            /**
             * 4. Compte le nombre d'élèves à extraire
             */
            $nbEleves = $televes->nbEnfantsAExtraire($dateExtraction);
            /**
             * 5. Génération du flux temporaire (fichier si taille > 2MB)
             */
            $fd = fopen('php://temp', 'r+');
            $cols = $televes->info();
            fputcsv($fd, $cols['cols'], ';', '"');
            unset($cols);
            /**
             * 6. Boucle de requêtes d'extraction
             */
            $where = $select->where('dateExtraction = ?', $dateExtraction);
            for ($bloc = 250, $j = 0; $j < ceil($nbEleves / $bloc); $j++) {
                $eleves = $televes->fetchAll($where->order(array('Nom', 'Prenom'))->limit($bloc, $bloc * $j))->toArray();
                foreach ($eleves as $eleve) {
                    fputcsv($fd, $eleve, ';', '"');
                }
            }
            rewind($fd);
            /**
             * 7. Exportation des données dans un fichier csv
             */
            $this->_helper->viewRenderer->setNoRender(true);
            $this->_helper->layout->disableLayout();
            $this->getResponse()->setHeader('Content-type', 'text/csv');
            $this->getResponse()->setHeader('Content-disposition','attachment; filename="pits-eleves.csv"');
            $this->getResponse()->setBody(stream_get_contents($fd));
            fclose($fd);
            /*
             * 6. Marque les fiches extraites et démarque les fiches modifiées
             */
            $televes->update(array('ficheModifiee' => 0, 'ficheExtraite' => 1),
            array('dateExtraction = ?' => $dateExtraction));
            /**
             * 7. Retourne à admin/index
             */
            //$this->_redirect('/admin');
        }
    }
    /**
     *
     * Importe un fichier provenant de BdTS pour confirmer les inscriptions
     */
    //public function confirmationbdtsAction()
    //{
    // inutile pour COBAS;
    //}
    /**
     *
     * Accès aux statistiques
     */
    public function statistiquesAction()
    {
        // tout est dans la vue (page html)
    }
    /**
     *
     * Statistiques par établissement
     */
    public function statparetabAction()
    {
        $televes = new Pits_Model_DbTable_TEleveslist();
        $this->view->stats =$televes->statparetab();
    }
    /**
     *
     * Statistiques par station
     */
    public function statparstationAction()
    {
        $televes = new Pits_Model_DbTable_TEleveslist();
        $this->view->stats =$televes->statparstation('1');
        $this->view->statsMe =$televes->statparstation('1', "m");
        $this->view->statsSa =$televes->statparstation('1', "s");
        $this->view->stats2 =$televes->statparstation('2');
        $this->view->stats2Me =$televes->statparstation('2', "m");
        $this->view->stats2Sa =$televes->statparstation('2', "s");
    }
    /**
     *
     * Statistiques par commune
     */
    public function statparvilleAction()
    {
        $televes = new Pits_Model_DbTable_TEleveslist();
        $this->view->stats =$televes->statparville();
    }
    /**
     * Affiche le détail des paiements Paybox d'un utilisateur
     */
    public function payboxAction()
    {
        $userId = $this->getRequest()->getParam('u', -1);
        if (!$userId > 0) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $this->view->page = $this->getRequest()->getParam('p', 1);
        $tuser = new Pits_Model_DbTable_TUser();
        $this->view->user = $tuser->find($userId)->current();
    }
    /**
     * Affiche la liste des paiements paybox
     */
    public function listepayboxAction()
    {
        $tpaybox = new Pits_Model_DbTable_TPaybox();
        $select = $tpaybox->select();
        $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage(10);
        $paginator->setCurrentPageNumber($this->view->page = $this->getRequest()->getParam('page', 1));
        $this->view->paybox = $paginator;
    }
    /**
     * Affiche la liste des services et en propose la gestion
     */
    public function listedesservicesAction()
    {
        $tservices = new Pits_Model_DbTable_TServicesplaces();
        $select = $tservices->select();
        $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage(10);
        $paginator->setCurrentPageNumber($this->view->page = $this->getRequest()->getParam('page', 1));
        $this->view->services = $paginator;
    }
    public function servicesplacesAction()
    {
        $this->view->CodeService = $codeService = $this->getRequest()->getParam('s', null);
        if (is_null($codeService)) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $this->view->page = $page = $this->getRequest()->getParam('p', 1);
        if ($this->hasPost(array('NbPlaces', 'BdTS', 's', 'p'))) {
            $tservice = new Pits_Model_DbTable_TServices();
            $record = $tservice->find($codeService)->current();
            $record->NbPlaces = (int) $this->getRequest()->getParam('NbPlaces');
            $record->BdTS = (int) $this->getRequest()->getParam('BdTS');
            try {
                $record->save();
                $this->_redirect("admin/listedesservices/page/$page");
            } catch (Zend_Db_Exception $e) {
                $this->truandage(__CLASS__, __METHOD__);
            }
        }
    }
    public function servicesetablissementsAction()
    {
        $this->view->CodeService = $codeService = $this->getRequest()->getParam('s', null);
        if (is_null($codeService)) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $this->view->page = $this->getRequest()->getParam('p', 1);
        $tservice = new Pits_Model_DbTable_TServices();
        $this->view->service = $service = $tservice->find($codeService)->current();
        $this->view->etablissements = $service->findPits_Model_DbTable_TVillesecolesViaPits_Model_DbTable_TEtablissementsServices('service');
    }
    public function etabajoutAction()
    {
        $this->view->CodeService = $codeService = $this->getRequest()->getParam('s', null);
        if (is_null($codeService)) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $this->view->page = $page = $this->getRequest()->getParam('p', 1);

        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'etabajout');
        $form = new Ap_Form($configForm->ap->etabajout);
        $cancel = $form->getElement('cancel');
        $actionRetour = "servicesetablissements/s/$codeService/p/$page";
        $cancel->setOptions(array('onclick' => "pageRetour('admin','" . $actionRetour . "');"));
        $hiddens = array('s' => $codeService, 'p' => $page);
        $form->setAction($this->view->link('admin', 'etabajout'))->setHiddens($hiddens);
        $tetablissements = new Pits_Model_DbTable_TVillesecoles();
        $form->getElement('CodeEN')->addMultiOptions($tetablissements->liste());

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $values = $form->getValues();
            $codeEN = $values['CodeEN'];
            $tetablissementsServices = new Pits_Model_DbTable_TEtablissementsServices();
            $record = $tetablissementsServices->createRow(array('CodeEN' => $codeEN,
            'CodeService' => $codeService,));
            try {
                $record->save();
                $this->_redirect("admin/servicesetablissements/s/$codeService/p/$page");
            } catch (Zend_Db_Exception $e) {
                if (strpos($e->getMessage(), '1062 Duplicate entry')) {
                    $this->_redirect("admin/servicesetablissements/s/$codeService/p/$page");
                } else {
                    throw new Pits_Model_DbTable_Exception($e->getMessage());
                }
            }
        }
        $this->view->form = $form;
    }
    public function etabsupprAction()
    {
        $this->view->CodeService = $codeService = $this->getRequest()->getParam('s', null);
        $this->view->CodeEN = $codeEN = $this->getRequest()->getParam('e', -1);
        if (is_null($codeService) || $codeEN < 0) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $this->view->page = $page = $this->getRequest()->getParam('p', 1);
        $confirme = (int) $this->getRequest()->getParam('confirme', 0);

        // traitement de la demande
        $tetablisementsServices = new Pits_Model_DbTable_TEtablissementsServices();
        if ($this->getRequest()->isPost() && $confirme == 1) {
            // Tente de supprimer
            try {
                $tetablisementsServices->delete(array('CodeService = ?' => $codeService, 'CodeEN = ?' => $codeEN));
            } catch (Zend_Exception $e) {
                throw new Ap_Exception("Cet établissement n'est desservi par ce circuit.");
            }
            $this->_redirect("admin/servicesetablissements/s/$codeService/p/$page");
        } else {
            $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'confirmation');
            $form = new Ap_Form($configForm->ap->confirmation);
            // bouton retour
            $cancel = $form->getElement('cancel');
            $cancel->setOptions(array('onclick' => "pageRetour('admin','servicesetablissements/s/$codeService/p/$page');"));
            // l'action et les hiddens du formulaire
            $hiddens = array('e' => $codeEN,'s' => $codeService, 'p' => $page);
            $form->setAction($this->view->link('admin', 'etabsuppr'))->setHiddens($hiddens);
            $this->view->form = $form;
        }
    }
    public function servicesstationsAction()
    {
        $this->view->CodeService = $codeService = $this->getRequest()->getParam('s', null);
        if (is_null($codeService)) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $this->view->page = $this->getRequest()->getParam('p', 1);
        $tservice = new Pits_Model_DbTable_TServices();
        $this->view->service = $service = $tservice->find($codeService)->current();
        $this->view->stations = $service->findPits_Model_DbTable_TStationsViaPits_Model_DbTable_TCircuits('service');
    }
    public function stationajoutAction()
    {
        $this->view->CodeService = $codeService = $this->getRequest()->getParam('s', null);
        if (is_null($codeService)) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $this->view->page = $page = $this->getRequest()->getParam('p', 1);

        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'stationajout');
        $form = new Ap_Form($configForm->ap->stationajout);
        $cancel = $form->getElement('cancel');
        $actionRetour = "servicesstations/s/$codeService/p/$page";
        $cancel->setOptions(array('onclick' => "pageRetour('admin','" . $actionRetour . "');"));
        $hiddens = array('s' => $codeService, 'p' => $page);
        $form->setAction($this->view->link('admin', 'stationajout'))->setHiddens($hiddens);
        $tstations = new Pits_Model_DbTable_TStationsvilles();
        $form->getElement('CodeStation')->addMultiOptions($tstations->getListeStations());

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $values = $form->getValues();
            $codeStation = $values['CodeStation'];
            $tcircuits = new Pits_Model_DbTable_TCircuits();
            $circuit = $tcircuits->createRow(array('CodeCircuit' => $tcircuits->nextCodeCircuit(),
            'CodeService' => $codeService,
            'CodeStation' => $codeStation,));
            try {
                $circuit->save();
                $this->_redirect("admin/servicesstations/s/$codeService/p/$page");
            } catch (Zend_Db_Exception $e) {
                if (strpos($e->getMessage(), '1062 Duplicate entry')) {
                    $this->_redirect("admin/servicesstations/s/$codeService/p/$page");
                } else {
                    throw new Pits_Model_DbTable_Exception($e->getMessage());
                }
            }
        }
        $this->view->form = $form;
    }
    public function stationsupprimeAction()
    {
        $this->view->CodeService = $codeService = $this->getRequest()->getParam('s', null);
        $this->view->CodeStation = $codeStation = $this->getRequest()->getParam('a', -1);
        if (is_null($codeService) || $codeStation < 0) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $this->view->page = $page = $this->getRequest()->getParam('p', 1);
        $confirme = (int) $this->getRequest()->getParam('confirme', 0);

        // traitement de la demande
        $tcircuits = new Pits_Model_DbTable_TCircuits();
        if ($this->getRequest()->isPost() && $confirme == 1) {
            // Tente de supprimer
            try {
                $tcircuits->delete(array('CodeService = ?' => $codeService, 'CodeStation = ?' => $codeStation));
            } catch (Zend_Exception $e) {
                throw new Ap_Exception("Cette station n'est pas sur ce circuit.");
            }
            $this->_redirect("admin/servicesstations/s/$codeService/p/$page");
        } else {
            $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'confirmation');
            $form = new Ap_Form($configForm->ap->confirmation);
            // bouton retour
            $cancel = $form->getElement('cancel');
            $cancel->setOptions(array('onclick' => "pageRetour('admin','servicesstations/s/$codeService/p/$page');"));
            // l'action et les hiddens du formulaire
            $hiddens = array('a' => $codeStation,'s' => $codeService, 'p' => $page);
            $form->setAction($this->view->link('admin', 'stationsupprime'))->setHiddens($hiddens);
            $this->view->form = $form;
        }
    }
}