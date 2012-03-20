<?php
/**
 * Application PiTS
 * Formulaire d'envoi d'un nouveau mot de passe
 *
 * @category   pits
 * @package    library/pits
 * @subpackage form
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

include_once 'Zend/Form.php';
include_once 'Zend/Form/Element/Text.php';
include_once 'Zend/Form/Element/Submit.php';
include_once 'Zend/Form/Element/Button.php';
include_once 'Zend/Form/Element/Hash.php';
include_once 'Zend/Validate/Db/RecordExists.php';
include_once 'Zend/Validate/EmailAddress.php';

class Pits_Form_NewPassword extends Pits_Form_Abstract
{
    /**
     * Initialisation du formulaire (méthode obligatoire)
     *
     * input type="select"   : Email
     *
     * @return Zend_Form
     */
    public function init()
    {
        // Mise en place du translator
        $this->putsTranslator();

        // Décorateur pour remplacer les dl dt dd en table, tr, td
        $decorators = array(
		    'ViewHelper',
        array('Errors', array('tag' => 'p', 'class' => 'error')),
        array('Description', array('tag' => 'p', 'class' => 'description')),
        array('HtmlTag', array('tag' => 'td')),
        array('Label', array('tag' => 'th')),
        array(array('tr' => 'HtmlTag'), array('tag' => 'tr'))
        );

        // Champ "Email" est un Text filtré
        $email = new Zend_Form_Element_Text('Email');
        $emailValidators = array(new Zend_Validate_EmailAddress());
        $email->addValidators($emailValidators)
        ->setLabel('Email')
        ->setDecorators($decorators)
        ->setRequired(true);
        $this->addElement($email);
        
        // Champ "Hash token"
        $this->putsHash('token');
        
        // Bouton "Annuler"
        $decoratorsButton = array(
		    'ViewHelper',
        array('HtmlTag', array('tag' => 'a', 'href' => $this->getCancel())),
        array(array('td' => 'HtmlTag'), array('tag' => 'td'))
        );
        $this->putsCancelButton('Abandonner',$decoratorsButton, 80);

        // Bouton "Envoyer le mot de passe" est un Submit
        $decoratorsButton = array(
		    'ViewHelper',
        array('HtmlTag', array('tag' => 'td')),
        );
        $this->putsSubmitButton('Envoyer le mot de passe', $decoratorsButton, 0);

        // Groupe des boutons
        $this->addDisplayGroup(
        array('cancel','submit'),'buttons',array(
        'decorators' => array('FormElements',array(array('tr' => 'HtmlTag'), array('tag' => 'tr'))),
        ));

        // place la balise <table>
        $this->setDecorators(
        array(
		    	'FormElements',
        array('HtmlTag', array('tag' => 'table')),
		    	'Form'
		    	)
		    	);
    }
}