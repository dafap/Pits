<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * Contrôleur principal de l'application
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

include_once 'Pits/Controller/Action.php';
include_once 'models/TUser.php';
include_once 'Pits/Form/CreationParent.php';
include_once 'Pits/Form/NewPassword.php';

/**
 * @category   pits
 * @package    application
 * @suboackage controller
 * @author     pomirol
 */
class IndexController extends Pits_Controller_Action
{
    /**
     * Met en place le menu visiteur défini dans application/config/menu.ini
     * (non-PHPdoc)
     * @see library/Zend/Controller/Zend_Controller_Action#init()
     */
    public function init()
    {
        $this->setMenu('visiteur');
    }

    /**
     * Affichage de la page d'accueil
     *
     * @param  void
     * @return void
     */
    public function indexAction()
    {
        $this->view->setTitrePage("Transports scolaires");
        $this->view->siteOuvert = $this->isSiteOuvert();
        $this->view->etatInscriptions = $this->getEtatInscriptions();
        $this->view->dateOuvertureInscriptions = $this->getDateOuvertureInscriptions();
        $this->view->anneeScolaire = $this->getAnneeScolaire();
    }
    /**
     * Création d'un compte pour les parents
     *
     * @param  void
     * @return void
     */
    public function creationAction()
    {
        $records = new TUser();
        // Récupération de l'enregistrement à éditer si nécessaire
        $params = $this->getRequest()->getParams();
        $isUpdate = isset($params['edit']);
        $this->view->setTitrePage('Création d\'un compte');
        $record = $records->createRow();
        // formulaire de création du compte
        $form = new Pits_Form_CreationParent(array('cancel' => $this->getBaseUrl() . '/index',));
        $form->setAction($this->view->link('index','creation'))
        ->setMethod('post')
        ->setDefaults($record->toArray());
         
        // Création du compte
        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            // Retrait des informations depuis les données en POST
            $values = $form->getValues();
            if (isset($values['mdp'])) {
                $mdp = $values['mdp'];
                $values['mdp'] = sha1($mdp);
            }
            $record->setFromArray(array_intersect_key($values, $record->toArray()));
            // Place le md5 de la date
            $dateCreation = Pits_Format::date("YYYY-MM-dd HH:mm:ss");
            $record->dateCreation = $dateCreation;
            $record->temoin = md5($dateCreation);
            $record->bloque = 1;
            // Enregistrement dans la base
            $record->save();
            // Envoi d'un email
            $this->_helper->userMail($mdp, $record->toArray());
            // Retour à la page d'accueil via un message d'explication
            Zend_Registry::set('email_mdp', $record->Email);
            $this->render('creationmsg');
        }
        if (Zend_Registry::isRegistered('Pits_Validate_Mdp')) {
            $mdp = Zend_Registry::set('Pits_Validate_Mdp');
            Zend_Debug::dump($mdp);
        }
        // Assignation du formulaire dans la vue pour l'affichage
        $this->view->form = $form;
    }
    /**
     * Procédure qui permet de se faire renvoyer un mot de passe sur un email déjà connu
     *
     * @param  void
     * @return void
     */
    public function passwdAction()
    {
        // Titre de la page
        $this->view->setTitrePage("Renvoi d'un mot de passe");

        //formulaire de renvoi de mot de passe
        $form = new Pits_Form_NewPassword(array('cancel' => $this->getBaseUrl() . '/index'));
        $form->setAction($this->view->link('index','passwd'))
        ->setMethod('post');

        // Préparation d'un nouveau mot de passe et de son envoi par email
        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            // Retrait des informations depuis les données en POST
            $values = $form->getValues();
            // calcul du mot de passe à 7 caractères
            $mdp = Pits_Methode_Methode::getNewPassword();
            // enregistrement du mot de passe
            $usersTable = new TUser();
            $where = $usersTable->select()->where('bloque=0 And Email = ?', $values['Email']);
            $user = $usersTable->fetchRow($where);
            if (is_null($user)) {
                $this->view->ok = false;
            } else {
                $this->view->ok = $usersTable->setMdp($mdp, $user->userId);
                // envoi du mot de passe
                $this->_helper->userMail($mdp, $user->toArray());
                // Retour à la page d'entrée
                Zend_Registry::set('email_mdp', $user->Email);
            }
            $this->render('creationmdp');
        } else {
            // Assignation du formulaire dans la vue pour l'affichage
            $this->view->form = $form;
        }
    }
    /**
     * Accès à la page d'aide
     *
     * @param  void
     * @return void
     */
    public function helpAction()
    {
        $this->view->setTitrePage("Documentation");
        // Lecture de la liste des documents
        $this->view->liste = $this->_helper->ListeDocuments('documents');
        $this->view->urlBase = $this->getUrlDocs('documents');
    }
}