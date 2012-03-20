<?php
/**
 * Gestion de l'espace de l'administrateur
 *
 * @project télé-paiement CCM
 * @package application/controllers
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 8 sept. 2011
 * @version $Id: $
 */

class AdminController extends Ap_Controller_Action
{
    public function init()
    {
        parent::init();
        // Vérification du User authentifié
        if (!$this->_auth->hasIdentity() || $this->_auth->getIdentity()->categorie < 2) {
            $this->_redirect('index/logout');
        }
        // Mise en place du menu
        $this->setMenu('admin');
    }

    public function indexAction() {
        // nombre de comptes
        $sql = 'SELECT count(t.userId) nbComptes FROM (SELECT DISTINCT userId FROM `factures`) AS t';
        $result = current($this->_db->fetchAll($sql));
        $this->view->nbComptes = $result['nbComptes'];
        // nombre de comptes en débit
        $sql = 'SELECT count(t.userId) nbComptes FROM (SELECT DISTINCT userId FROM `factures` WHERE datePaiement IS NULL) AS t';
        $result = current($this->_db->fetchAll($sql));
        $this->view->nbDebits = $result['nbComptes'];
        // total des factures et des impayés
        $records = new Tp_Model_DbTable_TFactures();
        $this->view->totalFactures = sprintf("%0.2f", $records->totalFactures());
        $this->view->totalImpayes = sprintf("%0.2f", $records->totalImpayes());
        // nombre de paiements Paybox, total des encaissements, date dernier paiement, date dernière extration
        $records = new Tp_Model_DbTable_TPaybox();
        $this->view->nbEncaissements = $records->nbPaybox();
        $this->view->totalEncaissements = sprintf("%0.2f", $records->totalPaybox());
        $this->view->lastPaybox = $records->lastPaybox();
        $this->view->lastExtraction = $records->lastExtraction();
    }
    /**
     *
     * Importation des factures à partir de BdTS (en faisant sauter la BOM si nécessaire)
     */
    public function importfacturesAction()
    {
        define("BOM", "\xEF\xBB\xBF");
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'sql');
        $form = new Zend_Form($configForm->ap->sql);
        $form->setAction($this->view->link('admin', 'importfactures'));
        $form->upload->setDestination($this->_applicationPath . DIRECTORY_SEPARATOR . 'tmp');

        if ($this->getRequest()->isPost() && $form->isValid(array($form->upload->getFileName()))) {
            if ($form->upload->receive()) {

                if ($f = fopen($form->upload->getFileName(), 'r')) {
                    $this->_db->beginTransaction();
                    try {
                        $this->view->message = '';
                        $tmp = '';
                        $debut = true;
                        while (!feof($f)) {
                            $tmp .= trim(fgets($f)) . PHP_EOL;
                            // suppression de la BOM (si nécessaire)
                            if ($debut) {
                                if (substr($tmp, 0, 3) == BOM) {
                                    $tmp = substr($tmp, 3);
                                }
                                $debut = false;
                            }
                            // envoie de la requête
                            if (!empty($tmp) && strpos($tmp, ';')) {
                                $this->view->message .= $tmp . PHP_EOL;
                                $this->_db->query($tmp);
                                $tmp = '';
                            }
                        }
                        // exécution s'il n'y a pas d'erreur
                        $this->_db->commit();
                    } catch (Ap_Exception $e) {
                        $this->_db->rollBack();
                        $this->view->message .= PHP_EOL . 'Le chargement des factures a échoué.';
                        fclose($f);
                        unlink($form->upload->getFileName());
                        return;
                    }
                }
                fclose($f);
                unlink($form->upload->getFileName());
                $this->view->message = PHP_EOL . 'Le lot de factures a été chargé.';
            }
        } else {
            if ($this->getRequest()->isPost()) {
                ;
                $form->addError('Choisissez un fichier en cliquant sur le bouton ou abandonnez.');
            }
            $this->view->form = $form;
        }
    }
    /**
     *
     * Extraction des paiements Paybox (par défaut, pas encore extraits)
     */
    public function extractpayboxAction()
    {
        // ouverture d'une passerelle vers la table paybox
        $records = new Tp_Model_DbTable_TPaybox();
        // dernière date d'extraction
        $depuis = $records->lastExtraction();
        // formulaire de critères
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'extraction');
        $form = new Zend_Form($configForm->ap->extraction);
        $form->setAction($this->view->link('admin', 'extractpaybox'))
        ->setDefaults(array('depuisJ' => Tp_Model_Format::date("dd/MM/YYYY", $depuis),
                            'depuisH' => Tp_Model_Format::date('HH:mm:ss', $depuis)));
        $this->view->form = $form;
        $this->view->termine = false;

        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            // fixe la date à partir de laquelle on va extraire les paiements
            $values = $form->getValues();
            if (!empty($values['depuisJ'])) {
                if (empty($values['depuisH'])) $values['depuisH'] = '00:00:00';
                $depuis = Tp_Model_Format::date("YYYY-MM-dd HH:mm:ss", $values['depuisJ'] . ' ' . $values['depuisH'], 'fr_FR');
            } else {
                $depuis = null;
            }
            // flag d'extraction
            $flag = Tp_Model_Format::date('YYYY-MM-dd HH:mm:ss', 'init');
            // marquage du travail à faire
            $records->marquage($flag, $depuis);
            // nombre de fiches marquées
            $nbmarque = $records->compte($flag);
            if ($nbmarque > 0) {
                // date actuelle pour marquer le travail fait et la DateDepot
                $today = Tp_Model_Format::date('YYYY-MM-dd HH:mm:ss');
                // génération du flux temporaire
                $fd = fopen('php://temp', 'r+');
                $cols = array('CodeEleve', 'DateDepot', 'DatePaiement', 'MontantPaiement', 'RéfModePaiement', 'Banque', 'NomTitulaire', 'NumCarteBancaire', 'AnnéeScolaire', 'Exercice');
                fputcsv($fd, $cols, ';', '"');
                unset($cols);
                // boucle de lecture des paiements paybox
                $select = $records->select()
                ->from($records, array('maref', 'datetrans', 'heuretrans',))
                ->where('dateExtraction = ?', $flag)
                ->order('payboxId');
                for ($bloc = 250, $j = 0; $j < ceil($nbmarque / $bloc); $j++) {
                    $p = $records->fetchAll($select->limit($bloc, $bloc * $j))->toArray();
                    foreach ($p as $paybox) {
                        $complements = $this->complementsPaiements($paybox['maref'], $paybox['datetrans'], $paybox['heuretrans']);
                        foreach ($complements as $complement) {
                            $paiement = array();
                            $paiement['CodeEleve'] = $complement['CodeEleve'];
                            $paiement['DateDepot'] = $today;
                            $paiement['DatePaiement'] = $complement['DatePaiement'];
                            $paiement['MontantPaiement'] = $complement['MontantPaiement'];
                            $paiement['RéfModePaiement'] = 1; // à vérifier dans la table de BdTS
                            $paiement['Banque'] = 'Paybox';
                            $paiement['NomTitulaire'] = $complement['NomTitulaire'];
                            $paiement['NumCarteBancaire'] = $paybox['maref'];
                            $paiement['AnnéeScolaire'] = $complement['AnnéeScolaire'];
                            $paiement['Exercice'] = $complement['Exercice'];
                            // écriture dans le flux
                            fputcsv($fd, $paiement, ';', '"');
                        }
                    }
                }
                rewind($fd);
                // création du fichier csv
                $this->_helper->viewRenderer->setNoRender(true);
                $this->_helper->layout->disableLayout();
                $this->getResponse()->setHeader('Content-type', 'text/csv');
                $this->getResponse()->setHeader('Content-disposition','attachment; filename="telepaiements.csv"');
                $this->getResponse()->setBody(stream_get_contents($fd));
                fclose($fd);
                ;
                // marquage des fiches
                $records->update(array('dateExtraction' => $today), array('dateExtraction = ?' => $flag));
                //Zend_Debug::dump($nbmarque);
                //$this->view->message = 'Extraction terminée.';
                //$this->view->termine = true;
            } else {
                $this->view->message = 'Aucune fiche à extraire avec ces critères.';
                $this->view->termine = true;
            }
        }
    }
    /**
     *
     * Liste des paiements par Paybox, dans l'ordre chronologique inversé
     */
    public function listepayboxAction()
    {
        $s = $this->_db->select()
        ->from(array('f' => 'paybox'), array('Paiement' => 'paiement', 'Carte' => 'carte', 'Date' => 'CONCAT(MID(datetrans,1,2) , "/", MID(datetrans,3,2), "/", MID(datetrans,5,4),  " ", heuretrans)', 'Référence' => 'maref', 'Montant' => 'montant', 'dateExtraction' => 'DATE_FORMAT(dateExtraction, "%d/%m/%Y %H:%i:%s")', 'extrait' => 'IF( dateExtraction IS NULL, 0, 1)',))
        ->join(array('u' => 'user'), 'f.userId = u.userId', array('Client' => 'CONCAT(u.Titre, " ", u.Nom, " ", u.Prenom)',))
        ->order('payboxId DESC');

        $mysession = new Zend_Session_Namespace('admin-listepaybox');
        $this->view->order = $order = $this->getRequest()->getParam('order');
        if (is_null($order)) {
            if (isset($mysession->critereOrder)) {
                $this->view->order = $order = $mysession->critereOrder;
            } else {
                $this->view->order = $order = 't';
            }
        }
        if ($order == 'e') {
            $s->where('NOT (dateExtraction IS NULL)');
            $mysession->critereOrder = 'e';
        } elseif ($order == 'n') {
            $s->where('dateExtraction IS NULL');
            $mysession->critereOrder = 'n';
        } else {
            $mysession->critereOrder = 't';
        }
        $adapter = new Zend_Paginator_Adapter_DbSelect($s);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage(5);
        $paginator->setCurrentPageNumber($this->view->page = $this->getRequest()->getParam('page', 1));
        $this->view->liste = $paginator;
    }
    /**
     *
     * Liste des utilisateurs ayant une facture enregistrée (soldée ou non)
     */
    public function listeuserAction()
    {
        $s = $this->_db->select()
        ->from(array('u' => 'user'), array('id' => 'u.userId', 'Nom' => 'CONCAT(u.Titre, " ", u.Nom, " ", u.Prenom)', 'Email' => 'u.Email',))
        ->join(array('v' => 'villes'), 'u.Commune=v.CodeInsee', array('Commune' => 'v.Nom',))
        ->join(array('f' => 'factures'), 'u.userId=f.userId', array('solde' => 'MIN(IF(datePaiement IS NULL, 0, 1))',))
        ->group('u.userId')
        ->order(array('u.Nom', 'u.Prenom'));

        $mysession = new Zend_Session_Namespace('admin-listeuser');
        $this->view->order = $order = $this->getRequest()->getParam('order');
        if (is_null($order)) {
            if (isset($mysession->critereOrder)) {
                $this->view->order = $order = $mysession->critereOrder;
            } else {
                $this->view->order = $order = 't';
            }
        }
        if ($order == 's') {
            $s->having('solde=1');
            $mysession->critereOrder = 's';
        } elseif ($order == 'n') {
            $s->having('solde=0');
            $mysession->critereOrder = 'n';
        } else {
            $mysession->critereOrder = 't';
        }

        $adapter = new Zend_Paginator_Adapter_DbSelect($s);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage(10);
        $paginator->setCurrentPageNumber($this->view->page = $this->getRequest()->getParam('page', 1));
        $this->view->liste = $paginator;
    }
    /**
     *
     * Détail des sommes dues et payées pour un user
     */
    public function detailuserAction()
    {
        // lecture des paramètres
        $this->view->userId = $userId = (int) $this->getRequest()->getParam('userId', -1);
        if ($userId == -1) $this->truandage(__CLASS__, __METHOD__); // controle indispensable
        //$this->view->page = $page = (int) $this->getRequest()->getParam('page', 1);
        // identité du compte client
        $user = new Tp_Model_DbTable_TUser();
        $this->view->client = $user->identite($userId);
        // liste des factures
        $factures = new Tp_Model_DbTable_TFactures();
        $sF = $factures->select()
        ->from($factures, array('N°' => 'CONCAT(exercice, "-", nFacturation, "-", rangFacture)', 'Année scolaire' => 'anneeScolaire', 'Date' => 'DATE_FORMAT(dateFacturation, "%d/%m/%Y")', 'Enfant' => 'CONCAT(nom, " ", prenom)', 'solde' => 'IF( datePaiement IS NULL, 0, 1)', 'Montant' => 'montant', 'id' => 'factureId',))
        ->where('userId = ?', $userId);
        $this->view->totalF = $factures->total($sF);
        $adapterF = new Zend_Paginator_Adapter_DbTableSelect($sF);
        $paginatorF = new Zend_Paginator($adapterF);
        $paginatorF->setItemCountPerPage(8);
        $paginatorF->setCurrentPageNumber($this->view->pageF = $this->getRequest()->getParam('pageF', 1));
        $this->view->listeF = $paginatorF;
        // liste des paiements
        $paybox = new Tp_Model_DbTable_TPaybox();
        $sP = $paybox->select()
        ->from($paybox, array('Paiement' => 'paiement', 'Carte' => 'carte', 'Date' => 'datetrans', 'Référence' => 'maref', 'Montant' => 'montant', 'dateExtraction' => 'dateExtraction'))
        ->where('userId = ?', $userId);
        $this->view->totalP = sprintf("%0.2f", $paybox->total($sP));
        $adapterP = new Zend_Paginator_Adapter_DbTableSelect($sP);
        $paginatorP = new Zend_Paginator($adapterP);
        $paginatorP->setItemCountPerPage(6);
        $paginatorP->setCurrentPageNumber($this->view->pageP = $this->getRequest()->getParam('pageP', 1));
        $this->view->listeP = $paginatorP;
    }
    /**
     *
     * Liste des factures
     */
    public function listefacturesAction()
    {
        $s = $this->_db->select()
        ->from(array('f' => 'factures'), array('id' => 'f.factureId', 'N°' => 'CONCAT(exercice, "-", nFacturation, "-", rangFacture)', 'Année scolaire' => 'anneeScolaire', 'Date' => 'DATE_FORMAT(dateFacturation, "%d/%m/%Y")', 'Enfant' => 'CONCAT(f.nom, " ", f.prenom)', 'solde' => 'IF( datePaiement IS NULL, 0, 1)', 'Montant' => 'montant'))
        ->join(array('u' => 'user'), 'f.userId = u.userId', array('Client' => 'CONCAT(u.Titre, " ", u.Nom, " ", u.Prenom)',))
        ->order('factureId DESC');

        $mysession = new Zend_Session_Namespace('admin-listefactures');
        $this->view->order = $order = $this->getRequest()->getParam('order');
        if (is_null($order)) {
            if (isset($mysession->critereOrder)) {
                $this->view->order = $order = $mysession->critereOrder;
            } else {
                $this->view->order = $order = 't';
            }
        }
        if ($order == 's') {
            $s->where('NOT (datePaiement IS NULL)');
            $mysession->critereOrder = 's';
        } elseif ($order == 'n') {
            $s->where('datePaiement IS NULL');
            $mysession->critereOrder = 'n';
        } else {
            $mysession->critereOrder = 't';
        }
        $adapter = new Zend_Paginator_Adapter_DbSelect($s);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage(10);
        $paginator->setCurrentPageNumber($this->view->page = $this->getRequest()->getParam('page', 1));
        $this->view->liste = $paginator;
    }
    /**
     *
     * Suppression d'une facture (après confirmation)
     */
    public function supprfactureAction()
    {
        // lecture des paramètres
        $factureId = (int) $this->getRequest()->getParam('factureId', -1);
        if ($factureId == -1) $this->truandage(__CLASS__, __METHOD__); // controle indispensable
        $page = $this->getRequest()->getParam('page', 1);
        $retour = $this->getRequest()->getParam('cancel', "listefactures/page/$page");

        $records = new Tp_Model_DbTable_TFactures();
        $confirme = (int) $this->getRequest()->getPost('confirme', 0);
        if ( $confirme == 1) {
            // suppression de l'élément de facture
            try {
                $records->delete("factureId = $factureId");
            } catch (Tp_Model_Exception $e) {
                throw new Tp_Model_Exception("La facture d'identifiant $factureId n'existe pas.");
            }
            $this->_redirect("admin/$retour");
        } else {
            // demande de confirmation
            // calcul des hiddens du formulaire
            $hiddens = array('factureId' => $factureId, 'page' => $page, 'cancel' => $retour);

            $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'confirmation');
            $form = new Ap_Form($configForm->ap->confirmation);
            $cancel = $form->getElement('cancel');
            $cancel->setOptions(array('onclick' => "pageRetour('admin','" . $retour . "');"));
            $form->setAction($this->view->link('admin', 'supprfacture'))->setHiddens($hiddens);

            $this->view->form = $form;
            $this->view->facture = $r = $records->find($factureId)->current();
            $this->view->user = current($r->findParentTp_Model_DbTable_TUser());
        }
    }
    /**
     *
     * Changement de mot de passe d'un utilisateur par un administrateur
     */
    public function usermodmdpAction()
    {
        // paramètres du formulaire
        $userId = (int) $this->getRequest()->getParam('userId', -1);
        if ($userId == -1) $this->truandage(__CLASS__, __METHOD__); // controle indispensable
        $page = (int) $this->getRequest()->getParam('page', 1);
        // formulaire
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'mdpadmin');
        $form = new Ap_Form($configForm->ap->mdpadmin);
        $cancel = $form->getElement('cancel');
        $actionRetour = "listeuser/page/$page";
        $cancel->setOptions(array('onclick' => "pageRetour('admin','" . $actionRetour . "');"));
        $hiddens = array('userId' => $userId, 'page' => $page);
        $form->setAction($this->view->link('admin', 'usermodmdp'))->setHiddens($hiddens);

        $this->view->retour = false;
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $mdp = $this->getRequest()->getParam('mdp', '');
            if ($mdp != '') {
                $userTable = new Tp_Model_DbTable_TUser();
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
     * Examen des logs des transactions
     */
    public function logpayboxAction()
    {
        $page = $this->getRequest()->getParam('page', 1);
        // formulaire des critères
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'critTrans');
        $form = new Ap_Form($configForm->ap->critTrans);
        $form->getElement('erreur')->addMultiOptions(Tp_Model_Paybox::getErreurListe());
        $tuser = new Tp_Model_DbTable_TUser();
        $form->getElement('userId')->addMultiOptions($tuser->nomsprenoms());

        // configurer l'objet paybox (emplacement des logs)
        $paybox = new Tp_Model_Paybox($this->getFrontController()->getParam('paybox'));

        //critères de recherche
        $criteres = array();
        $mysession = new Zend_Session_Namespace('admin-logpaybox');
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                // lecture de post
                $mysession->depuis = $depuis = $this->getRequest()->getPost('depuis', '');
                $mysession->erreur = $erreur = $this->getRequest()->getPost('erreur', 'tous');
                $mysession->userId = $userId = $this->getRequest()->getPost('userId', 'tous');
            }
        } else {
            // pas de post
            $depuis = $this->value($mysession->depuis, '');
            $erreur = $this->value($mysession->erreur, 'tous');
            $userId = $this->value($mysession->userId, 'tous');
        }
        if (!empty($depuis)) $criteres['depuis'] = $depuis;
        if (isset($erreur) && $erreur != 'tous') $criteres['erreur'] = $erreur;
        if (isset($userId) && $userId != 'tous') $criteres['userId'] = $userId;
        // initialise le formulaire
        $form->setDefaults($criteres);

        $paginator = Zend_Paginator::factory($paybox->getLogs($criteres));
        $paginator->setItemCountPerPage(10);
        $paginator->setCurrentPageNumber($this->view->page = $page);
        $this->view->liste = $paginator;
        $this->view->form = $form;
    }
    /**
     *
     * Gestion des documents dans l'aide en ligne
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
        $form->setAction($this->view->link('admin', 'helpadd'));
        $form->upload->setDestination(Zend_Registry::get('rootPath') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'documents');

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
            $path = Zend_Registry::get('rootPath') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'documents';
            $res = unlink($path . DIRECTORY_SEPARATOR . $this->getRequest()->getParam('d'));
        }
        $this->_redirect('admin/help');
    }
    /**
     *
     * Logout
     */
    public function logoutAction() {
        $this->_redirect('/index/logout');
    }
    /**
     * Si $param est défini, renvoie $param, sinon renvoie default
     *
     * @param mixted $param
     * @param mixted $default
     * @return mixted
     */
    private function value($param, $default)
    {
        if (isset($param)) return $param;
        return $default;
    }
    private function complementsPaiements($maref, $datetrans, $heuretrans)
    {
        $result = array();
        $paybox = new Tp_Model_Paybox(array());
        $paybox->setParams(array('maref' => $maref, 'datetrans' => $datetrans, 'heuretrans' => $heuretrans,));
        $userId = $paybox->getUserIdRef(); // integer
        $factureIds = $paybox->getFactureIdsRef(); // array
        $datePaiement = $paybox->getDatetrans(); // format YYYY-MM-dd HH:mm:ss

        $records = new Tp_Model_DbTable_TUser();
        $titulaire = $records->identite($userId);

        $records = new Tp_Model_DbTable_TFactures();
        foreach ($factureIds as $factureId) {
            $complement = $records->getElementsFacture($factureId);
            $result[] = array('NomTitulaire' => $titulaire,
                              'CodeEleve' => $complement['codeEleve'], 
                              'MontantPaiement' => $complement['montant'], 
                              'AnnéeScolaire' => $complement['anneeScolaire'], 
                              'Exercice' => $complement['exercice'], 
                              'DatePaiement' => $datePaiement,);
        }
        return $result;
    }
}