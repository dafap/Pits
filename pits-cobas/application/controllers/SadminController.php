<?php
/**
 * Contrôleur du super administrateur
 *
 * Le super administrateur a accès à tous les autres rôles
 *
 * @project pits
 * @package application/controllers
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 6 mars 2011
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
        if (!$this->_auth->hasIdentity() || $this->_auth->getIdentity()->categorie < 3) $this->_redirect('/parent/logout');
    }
    /**
     *
     * Page d'entrée du super administrateur
     */
    public function indexAction()
    {
        $this->view->log = false;
        $configLog = $this->getFrontController()->getParam('log');
        if ($configLog['dest'] != 'php://output') {
            $this->view->log = true;
            $stat = stat($configLog['dest']);
            $this->view->sizeLog = ceil($stat['size'] / 1024);
        }
        // nombre d'inscrits
        $televes = new Pits_Model_DbTable_TEleves();
        $this->view->elevesInscrits = $televes->nbEnfantsInscrits();
        $this->view->elevesPreInscrits = $televes->nbEnfantsPreInscrits();
        unset($televes);
        // nombre d'utilisateurs
        $tuser = new Pits_Model_DbTable_TUser();
        $this->view->nbBloques = $tuser->bloques();
        $this->view->nbParents = $tuser->inscrits();
        $this->view->nbAdmin = $tuser->inscrits(2);
        $this->view->nbSadmin = $tuser->inscrits(3);
        unset($tuser);
        // nombre de paiements
        $tpaybox = new Pits_Model_DbTable_TPaybox();
        $this->view->nbPaybox = $tpaybox->nbPaybox();
        unset($tpaybox);
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
     * Vider le fichier log après téléchargement
     */
    public function razlogAction()
    {
        $configLog = $this->getFrontController()->getParam('log');
        if ($configLog['dest'] != 'php://output') {
            $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'razlog');
            $form = new Zend_Form($configForm->ap->razlog);
            if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
                // on le télécharge
                $this->_helper->viewRenderer->setNoRender(true);
                $this->_helper->layout->disableLayout();
                $this->getResponse()->setHeader('Content-type', 'text/plain; charset=utf_8');
                $this->getResponse()->setHeader('Content-disposition','attachment; filename="pits.log"');
                $this->getResponse()->setBody(file_get_contents($configLog['dest']));
                // on le vide
                $fd = fopen($configLog['dest'], 'w');
                fclose($fd);
            } else {
                $form->setAction($this->view->link('sadmin', 'razlog'))
                ->setDefault('fichierlog', file_get_contents($configLog['dest']));
                $this->view->form = $form;
            }
        } else {
            $this->view->form = 'Configuration de PiTS sans fichier de log';
            $this->view->form .= $this->view->makeButton('cancel', 'cancel', $this->view->link('sadmin', 'index'), 'Retour', 'field_btn_contact');
        }
    }

    /**
     *
     * Gestion des administrateurs (liste, ajout, modification, suppression)
     */
    public function admingestAction()
    {
        // formulaire 'critereliste'
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'critereliste');
        $form = new Zend_Form($configForm->ap->critereliste);
        $form->setAction($this->view->link('sadmin', 'admingest'));
        $form->getElement('cancel')->setOptions(array('onclick' => "pageRetour('sadmin','index');"));

        // table TUser
        $tuser = new Pits_Model_DbTable_TUser();
        $select = $tuser->select()->where('categorie >= ?', 1)->order(array('categorie desc', 'nom'));

        // session
        $mysession = new Zend_Session_Namespace('Sadmin-AdminGest');
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
        $this->view->form = $form;

        // Pagination des élèves
        $adapter = new Zend_Paginator_Adapter_DbTableSelect($select);
        $paginator = new Zend_Paginator($adapter);
        $this->view->admins = $paginator->setItemCountPerPage(10)
        ->setCurrentPageNumber($this->view->page = $this->getRequest()->getParam('page', 1));
    }
    /**
     *
     * Ajout d'un administrateur
     */
    public function adminaddAction()
    {
        $page = $this->getRequest()->getParam('p', 1); // paginateur
        $tuser = new Pits_Model_DbTable_TUser();
        $record = $tuser->createRow(array('categorie' => 2, 'bloque' => 0));
        // formulaire de saisie
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'creercompte');
        $form = new Pits_Form_User($configForm->ap->creercompte);
        $form->setAction($this->view->link('sadmin', 'adminadd'))->setHidden('p', $page);
        // ajout des villes
        $villes = new Pits_Model_DbTable_TVilles();
        $liste = $villes->zone();
        $form->getElement('Commune')->addMultiOptions($liste);
        $form->getElement('cancel')->setOptions(array('onclick' => "pageRetour('sadmin','admingest/page/" . $page . "');"));
        // traitement de la réponse (il y a un token, donc cela suffit)
        $this->view->page = $page;
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            // valeurs renvoyées par le formulaire
            $formValues = $form->getValues();
            // suppression des champs de controle
            $record->setFromArray(array_intersect_key($formValues, $record->toArray()));
            // ajout des dates, du témoin et codage du mdp
            $dateInitiale = Pits_Model_Format::date('YYYY-MM-dd HH:mm:ss', 'init');
            $dateCreation = Pits_Model_Format::date('YYYY-MM-dd HH:mm:ss');
            $record->dateCreation= $dateCreation;
            $record->dateModif = $dateInitiale;
            $record->dateLastLogin = $dateInitiale;
            $record->temoin = 'par sadmin';
            $mdp = $record->mdp;
            $record->mdp = sha1($mdp);
            // traitement du tableau de valeurs
            try {
                $record->save();
                try {
                    $this->_helper->userMail($mdp, $record->toArray(),'mail_user_newadmin.txt');
                    $this->_redirect('/sadmin/admingest/page/' . $page);
                } catch (Exception $e) {
                    $this->view->op = 'email';
                }
            } catch (Exception $e) {
                $this->view->op = 'echec';
            }
        } else {
            $this->view->op = 'form';
            $this->view->form = $form;
        }
    }
    /**
     *
     * Modification d'un administrateur
     */
    public function admineditAction()
    {
        // récupère le userId
        if (($userId = $this->getRequest()->getParam('u', -1)) == -1) { // affectation en même temps
            $this->truandage(__CLASS__, __METHOD__);
        }
        $this->view->page = $page = $this->getRequest()->getParam('p', 1); // paginateur
        // lecture de la fiche
        $tuser = new Pits_Model_DbTable_TUser();
        if (is_null($record = $tuser->find($userId)->current())) { // affectation en même temps
            $this->_redirect('/sadmin/admingest');
        }
        // formulaire
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'creercompte');
        $form = new Pits_Form_User($configForm->ap->creercompte);
        $form->modeModif(array('controller' => 'sadmin', 'action' => 'adminedit'),
        array('controller' => 'sadmin', 'action' => 'admingest/page/' . $page))
        ->setHiddens(array('u' => $userId, 'p' => $page,))
        ->setDefaults($record->toArray());
        // ajout des villes,
        $villes = new Pits_Model_DbTable_TVilles();
        $liste = $villes->zone();
        $form->getElement('Commune')->addMultiOptions($liste);
        // traitement de la réponse (il y a un token, donc cela suffit)
        $this->view->retour = false;
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            // valeurs renvoyées par le formulaire
            $formValues = $form->getValues();
            // suppression des champs de controle
            $values = array_intersect_key($formValues, $record->toArray());
            // traitement du mot de passe
            if (empty($values['mdp'])) {
                unset($values['mdp']);
                $mdp = null;
            } else {
                $mdp = $values['mdp'];
                $values['mdp'] = sha1($mdp);
            }
            // valeurs modifiées
            $record->setFromArray(array_diff_assoc($values, $record->toArray()));
            // faut-il enregistrer ?
            if ($record->isModified()) {
                // ajout des dates et du témoin pour validation, codage du mdp
                $dateToday = Pits_Model_Format::date('YYYY-MM-dd HH:mm:ss');
                $record->dateModif = $dateToday;
                // enregistrement
                try {
                    $record->save();
                    try {
                        $this->_helper->userMail($mdp, $record->toArray());
                        $this->_redirect('/sadmin/admingest/page/' . $page);
                    } catch (Exception $e) {
                        $this->view->form = "La modification est enregistrée mais l'envoi du mail a échoué.";
                        $this->view->retour = true;
                    }
                } catch (Exception $e) {
                    $this->view->form = "La modification n'a pas pu être enregistrée.";
                    $this->view->retour = true;
                }
            } else {
                $this->_redirect('/sadmin/admingest/page/' . $page);
            }
        } else {
            $this->view->form = $form;
        }
    }
    /**
     *
     * Suppression d'un administrateur
     */
    public function adminsupprAction()
    {
        $userId = (int) $this->getRequest()->getParam('u', -1);
        if ($userId == -1) $this->truandage(__CLASS__, __METHOD__); // controle indispensable
        $this->view->page = $page = $this->getRequest()->getParam('p', 1); // paginateur
        $confirme = (int) $this->getRequest()->getParam('confirme', 0);
        // traitement de la demande
        $tuser = new Pits_Model_DbTable_TUser();
        if ($this->getRequest()->isPost() && $confirme == 1) {
            // Tente de supprimer
            try {
                $tuser->delete("userId = $userId");
            } catch (Zend_Exception $e) {
                $this->view->form = "Impossible de supprimer cet utilisateur.";
                $this->view->retour = true;
            }
            $this->_redirect('/sadmin/admingest/page/' . $page);
        } else {
            $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'confirmation');
            $form = new Ap_Form($configForm->ap->confirmation);
            // bouton retour
            $cancel = $form->getElement('cancel');
            $cancel->setOptions(array('onclick' => "pageRetour('sadmin','admingest/page/" . $page . "');"));
            // l'action et les hiddens du formulaire
            $hiddens = array('u' => $userId, 'p' => $page);
            $form->setAction($this->view->link('sadmin', 'adminsuppr'))->setHiddens($hiddens);
            $this->view->form = $form;
            $this->view->retour = false;
        }
    }
    /**
     *
     * Attribuer un mot de passe à un administrateur
     */
    public function adminmdpAction()
    {
        $userId = (int) $this->getRequest()->getParam('u', -1);
        if ($userId == -1) $this->truandage(__CLASS__, __METHOD__); // controle indispensable
        $this->view->page = $page = $this->getRequest()->getParam('p', 1); // paginateur
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'mdpadmin');
        $form = new Ap_Form($configForm->ap->mdpadmin);
        $cancel = $form->getElement('cancel');
        $cancel->setOptions(array('onclick' => "pageRetour('sadmin','admingest/page/" .$page . "');"));
        $hiddens = array('u' => $userId, 'p' => $page);
        $form->setAction($this->view->link('sadmin', 'adminmdp'))->setHiddens($hiddens);

        $this->view->retour = false;
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $mdp = $form->getValue('mdp');
            if ($mdp != '') {
                $userTable = new Pits_Model_DbTable_TUser();
                $record = $userTable->find($userId)->current();
                $record->setMdp($mdp, 'mdp de sadmin'); // 13 caractères maximum
                try {
                    $this->_helper->userMail($mdp, $record->toArray());
                    $this->_redirect('/sadmin/admingest/page/' . $page);
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
     * Abaisser les droits d'un utilisateur
     */
    public function adminmoinsAction()
    {
        if (($userId = $this->getRequest()->getParam('u', -1)) == -1) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $page = $this->getRequest()->getParam('p', 1);
        $tuser = new Pits_Model_DbTable_TUser();
        $record = $tuser->find($userId)->current();
        $record->moins();
        $this->_redirect("/sadmin/admingest/page/$page");
    }
    /**
     *
     * Augmenter les droits d'un utilisateur
     */
    public function adminplusAction()
    {
        if (($userId = $this->getRequest()->getParam('u', -1)) == -1) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $page = $this->getRequest()->getParam('p', 1);
        $tuser = new Pits_Model_DbTable_TUser();
        $record = $tuser->find($userId)->current();
        $record->plus();
        $this->_redirect("/sadmin/admingest/page/$page");
    }
    public function adminvoirficheAction() 
    {
        if (($userId = $this->getRequest()->getParam('u', -1)) == -1) {
            $this->truandage(__CLASS__, __METHOD__);
        }
        $this->view->page = $this->getRequest()->getParam('p', 1);
        $tuser = new Pits_Model_DbTable_TUser();
        $record = $tuser->find($userId)->current();
        $this->view->user = $record;
    }
    /**
     *
     * Vider la table des élèves
     */
    public function razelevesAction()
    {
        if ($this->getRequest()->isPost() && $this->hasPost(array('confirme', 'where',))) {
            $televes = new Pits_Model_DbTable_TEleves();
            if ($this->getRequest()->getParam('where') == 1) {
                $where = $televes->getAdapter()->quoteInto('?', 1);
            } else {
                $where = $televes->getAdapter()->quoteInto('ficheValidee = ?', 0);
            }
            $televes->delete($where);
            $this->_redirect('sadmin');
        } else {
            $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'razeleves');
            $form = new Zend_Form($configForm->ap->razeleves);
            $form->setAction($this->view->link('sadmin', 'razeleves'));
            $this->view->form = $form;
        }
    }

    /**
     *
     * Vider la table des utilisateurs
     */
    public function razusersAction()
    {
        if ($this->getRequest()->isPost() && $this->hasPost(array('confirme', 'where',))) {
            $tuser = new Pits_Model_DbTable_TUser();
            if ($this->getRequest()->getParam('where') == 2) {
                $where = $tuser->getAdapter()->quoteInto('categorie = ?', 1);
            } elseif ($this->getRequest()->getParam('where') == 1) {
                $where = $tuser->getAdapter()->quoteInto('categorie = ? And bloque = ?', 1);
            } else {
                $where = 'categorie = 1 AND userId NOT IN (SELECT userId FROM eleves)';
            }
            try {
                $tuser->delete($where);
                $this->_redirect('sadmin');
            } catch (Zend_Exception $e) {
                if (strpos($e->getMessage(), '1451 Cannot delete or update a parent row')) {
                    $this->view->err = true;
                    $this->view->msg = 'Vous ne pouvez pas vider la table des utilisateurs tant que celles des élèves et des paiements ne sont pas vides.';
                } else {
                    throw new Pits_Model_DbTable_Exception($e->getMessage());
                }
            }

        } else {
            $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'razusers');
            $form = new Zend_Form($configForm->ap->razusers);
            $form->setAction($this->view->link('sadmin', 'razusers'));
            $this->view->form = $form;
            $this->view->err = false;
        }
    }

    /**
     *
     * Vider la table des paiements
     */
    public function razpayboxAction()
    {
        if ($this->getRequest()->isPost() && $this->hasPost(array('confirme',))) {
            $tpaybox = new Pits_Model_DbTable_TPaybox();
            $where = $tpaybox->getAdapter()->quoteInto('1');
            $tpaybox->delete($where);
            $this->_redirect('sadmin');
        } else {
            $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'confirmation');
            $form = new Zend_Form($configForm->ap->confirmation);
            $form->setAction($this->view->link('sadmin', 'razpaybox'));
            $cancel = $form->getElement('cancel');
            $cancel->setOptions(array('onclick' => "pageRetour('sadmin','index');"));
            $envoi = $form->getElement('envoi');
            $envoi->setLabel('Vider la table des paiements');
            $this->view->form = $form;
        }
    }
    /**
     *
     * Exécute les requêtes contenues dans un fichier qu'on télécharge
     */
    public function sqlAction()
    {
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'sql');
        $form = new Zend_Form($configForm->ap->sql);
        $form->setAction($this->view->link('sadmin', 'sql'));
        // s'il y a une réponse 'post'
        if ($this->getRequest()->isPost()) {
            if ($form->isValid(array())) {
                $this->view->forward = $this->view->link('sadmin', 'sql'); //getBaseUrl() . '/sadmin';
                // récupérer le fichier temporaire au bon endroit sous son nom d'origine
                if ($form->upload->receive()) {
                    // traiter le fichier
                    if (!($f = file_get_contents($form->upload->getFileName()))) {
                        $this->view->message = 'Impossible de lire le fichier contenant les requêtes';
                    } else {
                        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
                        try {
                            $this->view->message = $f . "<br />\n";
                            $db->query($f);
                            $this->view->message .= 'La requête a été exécutée avec succès.';
                        } catch (Exception $e) {
                            // appel d'une vue pour la gestion d'erreur
                            $this->view->message .= 'La requête a échoué.';
                        }
                    }
                    // supprimer le fichier
                    unlink($form->upload->getFileName());                    
                } else {
                    // appel d'une vue pour annoncer qu'il n'y a pas de fichier à traiter
                    $this->view->message = 'Le fichier à traiter n\'a pas été trouvé.';
                }
            } // else le fichier reste temporaire et est détruit en fin d'exécution du script
            //$this->view->message = ''
        }
        $this->view->form = $form;;
    }

    /**
     *
     * Décrit les tables de la base pits
     */
    public function decritablesAction()
    {
        $tables = $this->_db->query('SHOW TABLE STATUS;');
        foreach ($tables as $table) {
            $nomtable = $table['Name'];
            $fields = $this->_db->query('SHOW FIELDS FROM ' . $nomtable);
            $metadata[$nomtable]['fields'] = $fields;
            if ($table['Comment'] == 'VIEW') {
                $metadata[$nomtable]['view'] =current(Ap_Methode::queryToArray($this->_db->query('SHOW CREATE VIEW ' . $nomtable)));
            } else {
                // Rows est faux pour la table villes donc on recalcule
                $table['Rows'] = current(current(Ap_Methode::queryToArray($this->_db->query('SELECT count(*) AS nb FROM ' . $nomtable))));
            }
            $metadata[$nomtable]['status'] = $table;
        }
        $this->view->metadata = $metadata;
        //Zend_Debug::dump($metadata);exit;
    }
    /**
     * Accès à la page d'aides et de documents en ligne
     */
    public function helpAction()
    {
        $this->view->liste = $this->_helper->ListeDocuments('public/documents');
        $this->view->docPath = $this->getUrlDocs();
    }
    /**
     * Liste les fichiers de config pour les modifier
     */
    public function editconfigAction()
    {
        $this->view->setTitrePage('Gestion des documents de configuration');
        // Lecture de la liste des documents à partir de $rootPath/application
        $path = Zend_Registry::get('rootPath') . DIRECTORY_SEPARATOR . 'application';
        $this->view->liste = $this->_helper->ListeDocuments('config', $path);
    }
    /**
     * Edite un fichier de config
     */
    public function editfichierAction()
    {
        $this->_helper->redirectorToOrigin->setFlashMessengerNamespace('sadmin');
        // Mettre le nom et le type du document dans la vue
        if (!$this->_hasParam('f')) {
            $this->_helper->redirectorToOrigin('Appel incorrect !');
        }
        $this->view->document = $this->getRequest()->getParam('f');
        if (empty($this->view->document)) {
            $this->_helper->redirectorToOrigin('Appel incorrect !');
        }

        // message d'erreur
        $this->view->messageError = '';
        // titre
        $this->view->setTitrePage('Lecture d\'un document de configuration');

        if (($pos = strrpos($this->view->document,'.')) === false ) {
            $this->_helper->redirectorToOrigin('Extension incorrecte !');
        } elseif ($pos == 0) {
            $this->_helper->redirectorToOrigin('Nom de fichier incorrect !');
        } else {
            $this->view->extension = substr($this->view->document,$pos);
            if ($this->view->extension != '.ini' && $this->view->extension != '.txt') {
                $this->_helper->redirectorToOrigin('Ce type de fichier n\'est pas géré !');
            }
        }

        // charge le fichier
        $document = Zend_Registry::get('rootPath') . DIRECTORY_SEPARATOR . 'application'
        . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $this->view->document;
        if ($this->view->extension == '.ini') {
            /* **********************
             * C'est un fichier ini *
             ********************** */
            $this->view->liste = file($document);
            // une ligne a été modifiée
            if ($this->getRequest()->isPost() && $this->_hasParam('c') && $this->_hasParam('l') && $this->_hasParam('v')) {
                $index = (int)$this->getRequest()->getParam('l') - 1;
                $this->view->liste[$index] = $this->getRequest()->getParam('c') . '= '
                . trim($this->getRequest()->getParam('v')) . PHP_EOL;
                // enregistre le fichier
                try {
                    if (!file_put_contents($document, $this->view->liste, FILE_TEXT)) {
                        $this->view->messageError = "Echec ! Le fichier n'a pas été enregistré.";
                        $this->view->liste = file($document); // relecture pour ré-initialiser les valeurs
                    }
                } catch (Exception $e) {
                    $this->view->messageError = "Echec ! Vous n'avez pas les droits pour modifier ce fichier.";
                    $this->view->liste = file($document); // relecture pour ré-initialiser les valeurs
                }
                $this->view->editligne = 0;
            } else {
                // transmet la liste pour affichage
                $this->view->editligne = $this->_hasParam('l') ? (int)$this->getRequest()->getParam('l') : 0;
            }
        } else {
            /* **********************
             * C'est un fichier txt *
             ********************** */
            if ($this->getRequest()->isPost() && $this->_hasParam('t')) {
                $contenu = $this->getRequest()->getParam('t');
                try {
                    if (!file_put_contents($document, $contenu, FILE_TEXT)) {
                        $this->view->messageError = "Echec ! Le fichier n'a pas été enregistré.";
                    }
                } catch (Exception $e) {
                    $this->view->messageError = "Echec ! Vous n'avez pas les droits pour modifier ce fichier.";
                }
            }
            $this->view->liste = file_get_contents($document);
        }
    }
    public function cleancacheAction()
    {
        $this->_cache->clean(Zend_Cache::CLEANING_MODE_ALL);
        $this->_redirect('sadmin');
    }
    public function testAction()
    {
        $this->view->label = '$url = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();';
        $this->view->value = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
    }
}