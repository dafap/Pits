<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 3 juil. 2010
 *
 * @category   pits
 * @package    application
 * @subpackage controllers
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

/**
 * @category   pits
 * @package    application
 * @subpackage controllers
 * @author     pomirol
 */
class SadminController extends Pits_Controller_Action
{
    /**
     * Met en place le menu sadmin défini dans application/config/menu.ini
     * (non-PHPdoc)
     * @see library/Zend/Controller/Zend_Controller_Action#init()
     */
    public function init()
    {
        $this->setMenu('sadmin');
        // Vérification du User authentifié
        $this->_auth = Zend_Auth::getInstance();
        if (!$this->_auth->hasIdentity() || $this->_auth->getIdentity()->categorie != 3) {
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

    public function indexAction()
    {
        $config = $this->getFrontController()->getParam('config')->logfile;
        $statLog = stat(Zend_Registry::get('rootPath') . DIRECTORY_SEPARATOR . 'application' . $config);
        $this->view->sizeLog = ceil($statLog['size'] / 1024); // en ko
        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();

        $select = $db->select()->from('eleves', array('nbEleves' => 'count(eleveId)'));
        $eleves = $db->fetchAll($select);
        $this->view->nbEleves = $eleves[0]['nbEleves'];

        $select = $db->select()->from('user', array('nbAdmin' => 'count(userId)'))->where('categorie=2');
        $eleves = $db->fetchAll($select);
        $this->view->nbAdmin = $eleves[0]['nbAdmin'];

        $select = $db->select()->from('user', array('nbParents' => 'count(userId)'))->where('categorie=1');
        $users = $db->fetchAll($select);
        $this->view->nbParents = $users[0]['nbParents'];

        $select = $db->select()->from('user', array('nbUserBloques' => 'count(userId)'))->where('bloque=1');
        $users = $db->fetchAll($select);
        $this->view->nbUserBloques = $users[0]['nbUserBloques'];

        $depuis = date('Ymd', strtotime('-1 year -4 month'));
        $select = $db->select()->from('user', array('nbUserInactifs' => 'count(userId)'))->where('categorie=1')->where('dateLastLogin < ?', $depuis);
        $users = $db->fetchAll($select);
        $this->view->nbUserInactifs = $users[0]['nbUserInactifs'];
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
     * décrit les champs des différentes tables dans un tableau
     */
    public function decritablesAction()
    {
        $table = new TEleves();
        $info = $table->info();
        $metadata['eleves'] = $info['metadata'];

        $table = new TUser();
        $info = $table->info();
        $metadata['user'] = $info['metadata'];

        $table = new TClasses();
        $info = $table->info();
        $metadata['classes'] = $info['metadata'];

        $table = new TEtablissements();
        $info = $table->info();
        $metadata['etablissements'] = $info['metadata'];

        $table = new TRythmesdepaiement();
        $info = $table->info();
        $metadata['rythmesdepaiement'] = $info['metadata'];

        $table = new TStations();
        $info = $table->info();
        $metadata['stations'] = $info['metadata'];

        $table = new TTarifs();
        $info = $table->info();
        $metadata['tarifs'] = $info['metadata'];

        $table = new TVilles();
        $info = $table->info();
        $metadata['villes'] = $info['metadata'];

        $this->view->metadata = $metadata;
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
                    if (!file_put_contents($document, $this->view->liste)) {
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
                    if (!file_put_contents($document, $contenu)) {
                        $this->view->messageError = "Echec ! Le fichier n'a pas été enregistré.";
                    }
                } catch (Exception $e) {
                    $this->view->messageError = "Echec ! Vous n'avez pas les droits pour modifier ce fichier.";
                }
            }
            $this->view->liste = file_get_contents($document);
        }
    }
    /**
     * Formulaire permettant au sadmin de changer son mdp
     */
    public function changemdpAction()
    {
        $form = new Pits_Form_ConfirmMdp(array('cancel' => $this->getBaseUrl() . '/sadmin'));
        $form->setAction($this->view->link('sadmin', 'changemdp'))
        ->setMethod('post');
        $this->view->form = $form;

        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            $mdp = $form->getValue('nouveaumdp');
            $tuser = new TUser();
            $user = $tuser->find(Zend_Auth::getInstance()->getIdentity()->userId)->current();
            $user->mdp = sha1($mdp);
            $user->save();
            $this->_redirect('sadmin');
        }
    }
    /**
     * Remise à zéro du fichier log
     */
    public function razlogAction()
    {
        // s'il y a un fichier log
        if (($config = $this->getFrontController()->getParam('config')->logfile) != 'php://output') {
            if ($this->_hasParam('confirmation')) {
                // on le télécharge
                $logfile = Zend_Registry::get('rootPath') . DIRECTORY_SEPARATOR . 'application' . $config;
                $this->_helper->viewRenderer->setNoRender(true);
                $this->_helper->layout->disableLayout();
                $this->getResponse()->setHeader('Content-type', 'text/plain; charset=utf_8');
                $this->getResponse()->setHeader('Content-disposition','attachment; filename="pits.log"');
                $this->getResponse()->setBody(file_get_contents($logfile));
                // on le vide
                $fd = fopen($logfile, 'w');
                fclose($fd);
            } else {
                // on demande confirmation
                $form = new Pits_Form_Confirmation(array('cancel' => $this->view->link('sadmin', 'index'), 'cancelLabel' => 'Retour', 'submitLabel' => 'Télécharger', 'buttonClass' => ''));
                $this->view->form = $form->setAction($this->view->link('sadmin','razlog'))->setMethod('post');
            }
        } else {
            $this->_redirect('sadmin');
        }
    }
    /**
     * Gestion des administrateurs
     */
    public function admingestAction()
    {
        $this->view->setTitrePage("Gestion des administrateurs");
        $tuser = new TUser();
        $select = $tuser->select()->where('categorie = 2');
        $this->view->admins = $tuser->fetchAll($select);
    }
    /**
     * Ajout d'un administrateur
     */
    public function adminaddAction()
    {
        $tuser = new TUser();
        $admin = $tuser->createRow();
        $form = new Pits_Form_CreationParent(array('modification'=>false, 'cancel'=>$this->view->link('sadmin', 'admingest'),));
        $form->setAction($this->view->link('sadmin','adminadd'))->setMethod('post')->setDefaults($admin->toArray());

        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            $valuespost = $form->getValues();
            // codage du mdp si nécessaire
            if (isset($valuespost['mdp'])) {
                $mdp = $valuespost['mdp'];
                $valuespost['mdp'] = sha1($mdp);
            }
            $admin->setFromArray(array_intersect_key($valuespost, $admin->toArray()));
            $pits_date = new Pits_Date();
            $admin->dateCreation = $pits_date->toString("YYYY-MM-dd HH:mm:ss");
            $admin->categorie = 2;
            $admin->bloque = 0;
            $userId = $admin->save();
            $this->_redirect('sadmin/admingest');
        }
        $this->view->form = $form;
    }
    /**
     * Edition de la fiche d'un administrateur
     */
    public function admineditAction()
    {
        if (!$this->_hasParam('u')) {
            $this->truandage(__CLASS__, __METHOD__);
        } else {
            $userId = $this->getRequest()->getParam('u');
            $tuser = new TUser();
            $admin = $tuser->find($userId)->current();
            $form = new Pits_Form_CreationParent(array(
            'cancel' => 'sadmin/admingest', 
            'hidden' => array('u' => $userId)));
            $form->setAction($this->view->link('sadmin','adminedit'))->setMethod('post')->setDefaults($admin->toArray());

            if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
                $valuespost = $form->getValues();
                // codage du mdp si nécessaire
                if (isset($valuespost['mdp'])) {
                    $mdp = $valuespost['mdp'];
                    $valuespost['mdp'] = sha1($mdp);
                }
                $admin_intersect = array_intersect_key($admin->toArray(), $valuespost);
                $values_intersect = array_intersect_key($valuespost, $admin->toArray());
                // y a-t-il des changements
                $change = false;
                foreach($values_intersect as $key => $value) {
                    $change |= ($admin_intersect[$key] != $value);
                }
                if ($change) {
                    $admin->setFromArray($values_intersect);
                    $admin->dateModif =Pits_Format::date('YYYY-MM-dd HH:mm:ss');
                    $admin->save();
                }
                $this->_redirect('sadmin/admingest');
            }
            $this->view->form = $form;
        }
    }
    /**
     * Suppression d'un administrateur
     */
    public function adminsupprAction()
    {
        if (!$this->_hasParam('u')) {
            $this->truandage(__CLASS__, __METHOD__);
        } else {
            $userId = $this->getRequest()->getParam('u');
            $url = 'sadmin/admingest';
            $usersTable = new TUser();
            $user = $usersTable->find($userId)->current();
            if ($this->_hasParam('confirmation')) {
                // Vérifie qu'il n'a pas d'enfant
                $enfants = $user->findTEleves()->toArray();
                if (!empty($enfants)) {
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
                    $this->_redirect($url);
                }
            } else {
                // prépare la vue
                $form = new Pits_Form_Confirmation(array('cancel'=>$url, 'hidden'=> array('u'=>$userId)));
                $form->setAction($this->view->link('sadmin','adminsuppr'))
                ->setMethod('post');
                $this->view->form = $form;
                $this->view->user = $user;
            }
        }
    }
    /**
     * Changement du mot de passe d'un administrateur
     */
    public function adminmdpAction()
    {
        if (!$this->_hasParam('userId')) {
            $this->truandage(__CLASS__, __METHOD__);
        } else {
            $baseUrl = $this->getBaseUrl();
            $userId = $this->getRequest()->getParam('userId');
            $url = 'sadmin/admingest';
            $form = new Pits_Form_ChangeMdp(array('cancel' => $baseUrl. '/' . $url,));
            $form->setAction($this->view->link('sadmin','adminmdp'))
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
                $this->_redirect('sadmin/admingest');
            }
            $this->view->setTitrePage('Donner un mot de passe');
            $this->view->form = $form;
        }
    }
    /**
     *
     * Passe toutes les fiches élèves à l'état `nonInscrit` = 1, `encours`= 0, `ficheExtraite`= 0, `ficheValidee` = 0 et `ficheModifiee` = 0
     */
    public function desinscrireAction()
    {
        if ($this->_hasParam('confirmation')) {
            $eleves = new TEleves();
            $eleves->desinscrire(); // passe tous les élèves à l'état `nonInscrit` = 1, `encours`= 0, `ficheExtraite`= 0, `ficheValidee` = 0 et `ficheModifiee` = 0
            $this->_redirect('sadmin');
        } else {
            // prépare la vue
            $form = new Pits_Form_Confirmation(array('cancel' => $this->view->link('sadmin', 'index'),));
            $form->setAction($this->view->link('sadmin', 'desinscrire'))
            ->setMethod('post');
            $this->view->form = $form;
        }
    }
    /**
     * On va supprimer les utilisateurs qui ne se sont pas connectés depuis plus d'un an et 4 mois
     */
    public function supprolduserAction()
    {
        if ($this->_hasParam('confirmation')) {
            $result = array('eleves' => 0, 'user' => 0);
            // date - 1 ans et 4 mois
            $depuis = date('Ymd', strtotime('-1 year -4 month'));
            $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
            // supprime les enfants des users concernés
            $tuser = new TUser();
            $users = $tuser->fetchAll($tuser->select()->where('dateLastLogin < ?', $depuis));
            foreach ($users as $user) {
                $where = $db->quoteInto('userId = ?', $user->userId);
                $result['eleves'] += $db->delete('eleves', $where);
            }

            // supprime les users concernés
            $where = $db->quoteInto('dateLastLogin < ?', $depuis);
            $result['user'] = $db->delete('user', $where);

            // renvoie les nombres de fiches supprimées
            $this->view->result = $result;
            $this->view->phase = 2;
        } else {
            $this->view->phase = 1;
            // prépare la vue
            $form = new Pits_Form_Confirmation(array('cancel' => $this->view->link('sadmin', 'index'),));
            $form->setAction($this->view->link('sadmin', 'supprolduser'))
            ->setMethod('post');
            $this->view->form = $form;
        }
    }
    /**
     * On va supprimer les comptes bloqués
     */
    public function suppruserbloqueAction()
    {
        if ($this->_hasParam('confirmation')) {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $where = $db->quoteInto('bloque = ?', 1);
            $this->view->result = $db->delete('user', $where);
            $this->view->phase = 2;
        } else {
            $this->view->phase = 1;
            // prépare la vue
            $form = new Pits_Form_Confirmation(array('cancel' => $this->view->link('sadmin', 'index'),));
            $form->setAction($this->view->link('sadmin', 'suppruserbloque'))
            ->setMethod('post');
            $this->view->form = $form;
        }
    }
    /**
     * Remise à zéro de la table eleves
     */
    public function razelevesAction()
    {
        if ($this->_hasParam('confirmation')) {
            $televes = new TEleves();
            $where = $televes->getAdapter()->quoteInto('1');
            $televes->delete($where);
            $this->_redirect('sadmin');
        } else {
            // prépare la vue
            $form = new Pits_Form_Confirmation(array('cancel'=>$this->view->link('sadmin','index'),));
            $form->setAction($this->view->link('sadmin','razeleves'))
            ->setMethod('post');
            $this->view->form = $form;
        }
    }
    /**
     * Remise à zéro des parents dans la table user.
     * On ne touche pas aux admin et sadmin.
     */
    public function  razuserAction()
    {
        if ($this->_hasParam('confirmation')) {
            $tuser = new TUser();
            $where = $tuser->getAdapter()->quoteInto('categorie=1');
            $tuser->delete($where);
            $this->_redirect('sadmin');
        } else {
            // prépare la vue
            $form = new Pits_Form_Confirmation(array('cancel'=>$this->view->link('sadmin','index'),));
            $form->setAction($this->view->link('sadmin','razuser'))
            ->setMethod('post');
            $this->view->form = $form;
        }
    }
    /**
     * Exécute un fichier de requêtes SQL
     */
    public function sqlAction()
    {
        $form = new Pits_Form_Upload(array('cancel' => $this->getBaseUrl() . '/sadmin',));
        // s'il y a une réponse 'post'
        if ($this->getRequest()->isPost()) {
            if ($form->isValid(array())) {
                $this->view->forward = $this->getBaseUrl() . '/sadmin';
                // récupérer le fichier temporaire au bon endroit sous son nom d'origine
                if ($form->pitsUpload->receive()) {
                    // traiter le fichier
                    if ($f = file_get_contents($form->pitsUpload->getFileName())) {
                        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
                        //$db->beginTransaction();
                        try {
                            $db->query($f);
                            //$db->commit();
                        } catch (Exception $e) {
                            //$db->rollBack();
                            // appel d'une vue pour la gestion d'erreur
                            $this->view->message = 'La requête a échoué.';
                        }
                    }
                    // supprimer le fichier
                    unlink($form->pitsUpload->getFileName());
                    // appel d'une vue pour annoncer le succès
                    $this->view->message = 'La requête a été exécutée avec succès.';
                } else {
                    // appel d'une vue pour annoncer qu'il n'y a pas de fichier à traiter
                    $this->view->message = 'Le fichier à traiter n\'a pas été trouvé.';
                }
            } // else le fichier reste temporaire et est détruit en fin d'exécution du script
            //$this->view->message = ''
        }
        $this->view->form = $form;
    }
    public function razcacheAction()
    {
        Pits_Cache::clean();
        $this->_redirect('sadmin/index');
    }
    /*public function testAction() {
        ;
    }*/
}