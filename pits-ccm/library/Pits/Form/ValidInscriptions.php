<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 24 juil. 2010
 *
 * @category   pits
 * @package    library
 * @subpackage pits/form
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.5.0
 * @since      Ce fichier est disponible depuis la version 0.5.0
 */

/**
 * @category   pits
 * @package    library
 * @subpackage pits/form
 * @author     pomirol
 */
class Pits_Form_ValidInscriptions extends Pits_Form_Abstract
{
    public function init()
    {
        // Mise en place du translator
        $this->putsTranslator();

        // Décorateur pour remplacer les dl dt dd en table, tr, td
        $decorators = array(
		    'File',
        array('Errors', array('tag' => 'p', 'class' => 'error')),
        array('Description', array('tag' => 'p', 'class' => 'description')),
        array('HtmlTag', array('tag' => 'td')),
        array('Label', array('tag' => 'th')),
        array(array('tr' => 'HtmlTag'), array('tag' => 'tr'))
        );
        // Champ "pitsValidation" est un "input file"
        $fileValidators = array(new Pits_Validate_ControleFileValidation(),);
        $pitsValidation = new Zend_Form_Element_File('pitsValidation');
        $pitsValidation->addValidators($fileValidators)
        ->setLabel('Transmettre le fichier de validation')
        ->setDescription('(Donner le fichier créé par le logiciel PiTS2BdTS)')
        ->setDecorators($decorators)
        // uploadsavepath est une valeur déclarée dans config.ini
        ->setDestination(Zend_Registry::get('uploadsavepath'));
        $this->addElement($pitsValidation);

        // Bouton "Annuler"
        $decoratorsButton = array(
		    'ViewHelper',
        array('HtmlTag', array('tag' => 'a', 'href' => $this->getCancel())),
        array(array('td' => 'HtmlTag'), array('tag' => 'td'))
        );
        $this->putsCancelButton('Abandonner', $decoratorsButton, 80, '');

        // Bouton "Enregistrer" est un Submit
        $decoratorsButton = array(
		    'ViewHelper',
        array('HtmlTag', array('tag' => 'td')),
        );
        $this->putsSubmitButton('Enregistrer', $decoratorsButton, 0, '');

        // Groupe des boutons
        $this->addDisplayGroup(
        array('cancel','submit'),'buttons',array(
        'decorators' => array('FormElements',array(array('tr' => 'HtmlTag'), array('tag' => 'tr'))),
        ));

        // place la balise <table>
        $this->setDecorators(
        array('FormElements',
        array('HtmlTag', array('tag' => 'table')),
		      'Form'));
    }
}