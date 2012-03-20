<?php
/**
 * Contrôleur des parents
 *
 * Gestion des actions disponibles pour un parent d'élève
 *
 * @project pits
 * @package application/controllers
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 17 mars 2011
 * @version $Id: $
 */

class ParentController extends Ap_Controller_Action
{
    /**
     * (non-PHPdoc)
     * @see Ap_Controller_Action::init()
     */
    public function init()
    {
        parent::init();
    }
    /**
     * Vérifie si l'utilisateur est authentifié et le déloge s'il ne l'est pas
     */
    private function _verifAuth()
    {
        if (!$this->_auth->hasIdentity()) $this->_redirect('/parent/logout');
    }
    /**
     *
     * Page d'entrée de l'espace parents
     */
    public function indexAction()
    {
        $this->_verifAuth();
        //Zend_Debug::dump($this->_auth->getIdentity()); exit;
        $elevesTable = new Pits_Model_DbTable_TEleveslist();
        $this->view->eleves = $elevesTable
        ->fetchAll($elevesTable->select()
        ->where('userId = ?', (int) $this->_auth->getIdentity()->userId)
        , 'Prenom');
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'paybox');
        $paybox = new Zend_Form($configForm->ap->paybox);
        $paybox->setAction($this->view->link('parent', 'payer'));
        $this->view->paybox = $paybox;
    }
    /**
     * Inscription d'un élève
     */
    public function inscriptioneleveAction()
    {
        $this->_verifAuth();
        // enregistrement initialisé
        $records = new Pits_Model_DbTable_TEleves();
        $record = $records->createRow($this->_initR1());
        $record->SecondeAdresse = 0;
        $nbEnfants = $records->famille() + 1;
        // édition de la fiche
        if ($this->_helper->EditEleve($nbEnfants,
        $this->view->link('parent', 'inscriptioneleve'), // action du formulaire
        array('controller' => 'parent', 'action' => 'index'), // bouton cancel (onclick)
        $record
        )) {
            // recalcul du tarif famille (si nécessaire)
            $records->tarifFamille();
            $this->_redirect('parent/index');
        }
    }
    /**
     * Modification d'un élève
     */
    public function editeleveAction()
    {
        $this->_verifAuth();
        // récupération de l'enregistrement
        if (($eleveId = $this->getRequest()->getParam('e', -1)) == -1) {
            $this->_redirect('/parent/index');
        }
        $records = new Pits_Model_DbTable_TEleves();
        if (is_null(!$record = $records->find($eleveId, true))) $this->_redirect('/parent/index');
        $record = $record->current();

        $nbEnfants = $records->famille();
        // édition de la fiche
        if ($this->_helper->EditEleve($nbEnfants,
        $this->view->link('parent', 'editeleve') . '/e/' . $eleveId, // action du formulaire avec passage du paramètre e
        array('controller' => 'parent', 'action' => 'index'), // bouton cancel (onclick)
        $record
        )) {
            $records->tarifFamille();
            $this->_redirect('parent/index');
        }
    }
    /**
     * Suppression d'un élève
     * // Pour debugage
     if (!isset($this->eleve)) {
     $this->eleve = new ArrayObject(array('eleveId'=>1,'Nom'=>'Pomirol','Prenom'=>'Tilo','DateN'=>'01/26/2011','Tarif'=>77.00,'ficheValidee'=>0,'ficheExtraite'=>0), ArrayObject::ARRAY_AS_PROPS);
     }

     */
    public function suppreleveAction()
    {
        $this->_verifAuth();
        // récupération de l'enregistrement
        if (($eleveId = $this->getRequest()->getParam('e', -1)) == -1) {
            $this->_redirect('/parent/index');
        }
        $records = new Pits_Model_DbTable_TEleves();
        if (is_null(!$record = $records->find($eleveId, true))) $this->_redirect('/parent/index');
        $record = $record->current();
        // contrôle si la fiche n'a pas été validée
        if ($record->ficheValidee) {
            $this->_redirect('/parent/index');
        }
        $this->view->eleve = $record;

        if ($this->getRequest()->isPost() && $this->getRequest()->getParam('confirme', -1) == 1) {
            // confirmation
            $record->delete();
            $records->tarifFamille();
            $this->_redirect('/parent/index');
        } else {
            // damande de confirmation
            $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'confirmation');
            $form = new Zend_Form($configForm->ap->confirmation);
            $form->setAction($this->view->link('parent', 'suppreleve') . '/e/' . $eleveId)
            ->getElement('cancel')->setOptions(array('onclick' => "pageRetour('parent','index');"));
            $this->view->form = $form;
        }
    }
    /**
     * Edition d'un justificatif
     */
    public function maileleveAction()
    {
        $this->_verifAuth();
        // récupération de l'enregistrement
        if (($eleveId = $this->getRequest()->getParam('e', -1)) == -1) {
            $this->_redirect('/parent/index');
        }
        $records = new Pits_Model_DbTable_TEleves();
        if (is_null(!$record = $records->find($eleveId, true))) $this->_redirect('/parent/index');
        $record = $record->current();

        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        // appel du mail avec, en 3e paramètre, le pdf à joindre
        $this->_helper->exportEleves('mail', $eleveId, $this->_helper->exportEleves->build('pdf', $eleveId));
    }

    /**
     * Valider et payer
     */
    public function payerAction()
    {
        $this->_verifAuth();
        // calcul du montant à payer et de la référence (date | montant | userId | liste des eleveId séparés par des |)
        $elevesTable = new Pits_Model_DbTable_TEleveslist();
        $calcul = $elevesTable->montantRef((int) $this->_auth->getIdentity()->userId);
        $montant = $calcul['montant'];
        $maref = $calcul['maref'];

        // ouvre un objet paybox
        $paybox = new Pits_Model_Paybox($this->getFrontController()->getParam('paybox'));

        // lancer l'appel à cgi-bin/modulev2
        $ch = curl_init($paybox->getUrlModule());
        // calcul des options de CURL
        $options = array(
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $paybox->getPostfields($montant, $maref, $this->_auth->getIdentity()->Email),
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        );
        curl_setopt_array($ch, $options);
        if (($this->view->content = curl_exec($ch)) === false) {
            $this->view->err     = curl_errno($ch);
            $this->view->errmsg  = curl_error($ch) ;
            $this->view->header  = curl_getinfo($ch);
        } else {
        }
        curl_close($ch);
    }
    /**
     * Action accessible uniquement par les serveurs de paybox
     * Pas de vérification d'authentification. Les contrôles sont fait par la classe Pits_Model_Paybox
     */
    public function valideinscriptionAction()
    {
        // désactive le layout (page HTML vide)
        $this->_helper->layout->disableLayout();
        // récupère les paramètres
        $ip = $this->getRequest()->getServer('REMOTE_ADDR');
        $params = $this->getRequest()->getParams();

        // ouvre un objet paybox
        $paybox = new Pits_Model_Paybox($this->getFrontController()->getParam('paybox'), $params, $ip);

        // analyse la requête et log
        if ($paybox->isValide())
        {
            // valide l'inscription des élèves
            $televes = new Pits_Model_DbTable_TEleves();
            $televes->validePaiement($paybox->getEleveIdsRef(), $paybox->getDatetrans(), $paybox->getIdtrans());
            // enregistre le paiement du parent dans la table paybox
            $tpaybox = new Pits_Model_DbTable_TPaybox();
            $record = $tpaybox->createRow($params);
            $record->userId = $paybox->getUserIdRef();
            $record->save();
        }

    }
    /**
     * Retour de paybox lorsque cela s'est bien passé
     */
    public function merciAction()
    {
        $this->_redirect('/parent/index');
    }
    /**
     * Retour de paybox en cas d'abandon ou d'erreur
     *
     */
    public function regretAction()
    {
        $this->_verifAuth();
        $params = $this->getRequest()->getParams();
        $paybox = new Pits_Model_Paybox($this->getFrontController()->getParam('paybox'), $params);
        $this->view->erreurmsg = $paybox->getErreurMsg();
    }
    /**
     * Accès à la page d'aides et de documents en ligne
     */
    public function helpAction()
    {
        $this->_verifAuth();
        $this->view->liste = $this->_helper->ListeDocuments('public/documents');
        $this->view->docPath = $this->getUrlDocs();
    }
    /**
     * Modifier son compte d'utilisateur
     */
    public function modifcompteAction()
    {
        $this->_verifAuth();
        // fiche du user
        $records = new Pits_Model_DbTable_TUser();
        $record = $records->find($this->_auth->getIdentity()->userId)->current();

        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'creercompte');
        $form = new Pits_Form_User($configForm->ap->creercompte);
        // adaptation du formulaire en mode modification et valeurs initiales
        $form->modeModif(array('controller' => 'parent', 'action' => 'modifcompte'),
        array('controller' => 'parent', 'action' => 'index'))
        ->setDefaults(Ap_Methode::objToArray($this->_auth->getIdentity()));

        // ajout des villes,
        $villes = new Pits_Model_DbTable_TVilles();
        $liste = $villes->zone();
        $form->getElement('Commune')->addMultiOptions($liste);
        //Zend_Debug::dump(get_class_methods(get_class($form))); exit;

        // traitement de la réponse (il y a un token, donc cela suffit)
        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
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
                    $this->_auth->getStorage()->write($record->toObject(null, 'mdp'));
                    $this->_helper->userMail($mdp); // utilise la variable de session
                    $this->view->op = 'ok';
                } catch (Exception $e) {
                    $this->view->op = 'echec';
                }
                //Zend_Debug::dump('modifié');
            }
            $this->_redirect('/parent/index');
        }
        $this->view->form = $form;
    }
    /**
     *
     * Logout
     */
    public function logoutAction()
    {
        $this->_redirect('/index/logout');
    }
    private function _initR1()
    {
        // valeurs initiales de l'utilisateur
        $identity = $this->_auth->getIdentity();

        return array(
        'userId' => $identity->userId,
        'TitreR1' => $identity->Titre,
        'NomR1' => $identity->Nom,
        'PrenomR1' => $identity->Prenom,
        'AdressR1L1' => $identity->AdresseL1,
        'AdressR1L2' => $identity->AdresseL2,
        'CodePostalR1' => $identity->CodePostal,
        'CommuneR1' => $identity->Commune,
        'TelephoneR1' => $identity->Telephone,
        'TelephoneR1c' => $identity->TelephoneC,
        'EmailR1' => $identity->Email
        );
    }

}