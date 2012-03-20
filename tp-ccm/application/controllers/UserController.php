<?php
/**
 * Espace user permettant de payer et d'effectuer le suivi des compte-rendus de Paybox
 *
 * @project télé-paiement CCM
 * @package application/controllers
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 8 sept. 2011
 * @version $Id: $
 */

class UserController extends Ap_Controller_Action
{
    private $_messageError;

    public function init()
    {
        parent::init();
        $this->setMenu('user');
    }
    /**
     *
     * Page d'entrée de l'espace utilisateur
     */
    public function indexAction() {
        $this->_verifAuth();
        $records = new Tp_Model_DbTable_TFactures();
        // déjà payé
        $select = $records->select()
        ->where('userId = ?', $this->_auth->getIdentity()->userId)
        ->where('NOT (datePaiement IS NULL)');
        $this->view->liste1 = $records->fetchall($select);
        $this->view->total1 = $records->total($select);
        // à payer
        $select = $records->select()
        ->where('userId = ?', $this->_auth->getIdentity()->userId)
        ->where('datePaiement IS NULL');
        $this->view->liste2 = $records->fetchall($select);
        $this->view->total2 = $records->total($select);
        // formulaire : bouton pour appeler le traitement d'un paiement par méthode post
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'paybox');
        $paybox = new Zend_Form($configForm->ap->paybox);
        $paybox->setAction($this->view->link('user', 'payer'));
        $this->view->paybox = $paybox;
    }
    /**
     *
     * Logout
     */
    public function logoutAction() {
        $this->_redirect('/index/logout');
    }
    /**
     * Vérifie si l'utilisateur est authentifié et le déloge s'il ne l'est pas
     */
    private function _verifAuth()
    {
        if (!$this->_auth->hasIdentity()) $this->_redirect('/index/logout');
    }
    /**
     *
     * Edite une facture
     */
    public function factureAction() {
        $this->_verifAuth();
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();   
             
        $records = new Tp_Model_DbTable_TFactures();
        $select = $records->select()
        ->where('userId = ?', $this->_auth->getIdentity()->userId)
        ->where('datePaiement IS NULL');
        $liste = $records->fetchall($select);
        $total = $records->total($select);       
        $this->_helper->userDocument('facture', $this->_auth->getIdentity()->userId, $liste, $total);
    }
    /**
     *
     * Edite un justificatif de paiement
     * - avec la mention acquitée si le paiement est enregistré
     */
    public function justificatifAction() {
        $this->_verifAuth();
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();        

        $records = new Tp_Model_DbTable_TPaybox();
        $select = $records->select()
        ->where('userId = ?', $this->_auth->getIdentity()->userId);
        $liste = $records->fetchall($select);
        $total = $records->total($select);
        $this->_helper->userDocument('justificatif', $this->_auth->getIdentity()->userId, $liste, $total);
    }
    /**
     *
     * Payer sa facture
     */
    public function payerAction()
    {
        $this->_verifAuth();

        // calcul du montant à payer et de la référence (service | date | montant | userId | liste des factureId séparés par des |)
        $records = new Tp_Model_DbTable_TFactures();
        $calcul = $records->montantRef((int) $this->_auth->getIdentity()->userId, 'TS');
        $montant = $calcul['montant'];
        $maref = $calcul['maref'];

        // ouvre un objet paybox
        $paybox = new Tp_Model_Paybox($this->getFrontController()->getParam('paybox'));

        // lancer l'appel à cgi-bin/modulev2
        $ch = curl_init($paybox->getUrlModule());
        //Zend_Debug::dump($paybox->getUrlModule(), 'module');
        // calcul des options de CURL
        $options = array(
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $paybox->getPostfields($montant, $maref, $this->_auth->getIdentity()->Email),
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        );
        //Zend_Debug::dump($options, 'options');
        curl_setopt_array($ch, $options);
        if (($this->view->content = curl_exec($ch)) === false) {
            $this->view->voir    = true;
            $this->view->err     = curl_errno($ch);
            $this->view->errmsg  = curl_error($ch) ;
            $this->view->header  = curl_getinfo($ch);
        } else {
        }
        curl_close($ch);
    }
    /**
     *
     * paybox.PBX_EFFECTUE
     */
    public function merciAction() {
        $this->_redirect('/user/index');
    }
    /**
     *
     * paybox.PBX_REFUSE
     */
    public function refuseAction() {
        $this->_verifAuth();
        $params = $this->getRequest()->getParams();
        $paybox = new Tp_Model_Paybox($this->getFrontController()->getParam('paybox'), $params);
        $this->view->erreurmsg = $paybox->getErreurMsg();
    }
    /**
     *
     * paybox.PBX_ANNULE
     */
    public function annuleAction() {
        $this->_verifAuth();
        $params = $this->getRequest()->getParams();
        $paybox = new Tp_Model_Paybox($this->getFrontController()->getParam('paybox'), $params);
        $this->view->erreurmsg = $paybox->getErreurMsg();
    }
    /**
     *
     * paybox.PBX_REPONDRE_A
     * Attention ! pas de verification d'authentification car c'est Paybox qui appelle
     * On vérifie si l'adresse IP est authorisée
     */
    public function valideAction() {
        // désactive le layout (page HTML vide)
        $this->_helper->layout->disableLayout();
        // récupère les paramètres
        $ip = $this->getRequest()->getServer('REMOTE_ADDR');
        $params = $this->getRequest()->getParams();

        // ouvre un objet paybox
        $paybox = new Tp_Model_Paybox($this->getFrontController()->getParam('paybox'), $params, $ip);
        // analyse la requête et log
        if ($paybox->isValide())
        {
            // aiguillage sur la méthode de validation en fonction du service appelant
            $this->_log->log($paybox->getService(), 6);
            switch ($paybox->getService()) {
                case 'TS':
                    $this->valideTS($paybox, $ip, $params);
                    break;

                default:
                    ;
                    break;
            }
        }
    }
    /**
     *
     * Procédure de validation pour les transports scolaires
     *
     * @param Pits_Model_Paybox $paybox
     * @param string $ip
     * @param array $params
     */
    private function valideTS($paybox, $ip, $params) {
        // valide le paiement de la facture
        $records = new Tp_Model_DbTable_TFactures();
        $records->validePaiement($paybox->getFactureIdsRef(), $paybox->getDatetrans(), $paybox->getIdtrans());
        // enregistre le paiement du parent dans la table paybox
        $tpaybox = new Tp_Model_DbTable_TPaybox();
        $record = $tpaybox->createRow($params);
        $record->userId = $paybox->getUserIdRef();
        $record->save();
    }
    /**
     *
     * Accès à la page d'aide
     */
    public function helpAction() {
        $this->_verifAuth();
        $this->view->liste = $this->_helper->ListeDocuments('public/documents');
        $this->view->docPath = $this->getUrlDocs();
    }
    /**
     *
     * Accès à la vue de son propre compte. Pas de modif possible.
     */
    public function voircompteAction()
    {
        $this->_verifAuth();
        $records = new Tp_Model_DbTable_TUser();
        $select = $records->select()->where("Email = ?", $this->_auth->getIdentity()->Email);
        $this->view->record = $records->fetchRow($select);
    }
    /**
     *
     * Formulaire de modification de son mdp
     */
    public function modifmdpAction()
    {
        $this->_verifAuth();
        $configForm = new Zend_Config_Ini($this->_applicationPath . '/configs/forms.ini', 'usermdp');
        $form = new Tp_Form_User($configForm->ap->usermdp);
        $form->setAction($this->view->link('user', 'modifmdp'));
        $this->view->op = 'form';
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $mdp = $form->getValue('mdp');
            $records = new Tp_Model_DbTable_TUser();
            $select = $records->select()->where("Email = ?", $this->_auth->getIdentity()->Email);
            $record = $records->fetchRow($select);
            $record->setMdp($mdp);
            $this->view->op = 'change';
            try {
                $this->_helper->userMail($mdp);
            } catch (Exception $e) {
                $this->view->op = 'nomail';
            }
        } else {
            $this->view->form = $form;
        }
    }
}