<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 5 juil. 2010
 *
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_Form_ExtractionDepuis extends Pits_Form_Abstract
{
    public function init()
    {
        // Mise en place du translator
        $this->putsTranslator();
        $this->addElementPrefixPath('Pits_Form_Decorator', 'Pits/Form/Decorator/', 'decorator');

        // Décorateur pour remplacer les dl dt dd en table, tr, td
        $decorators = array(
		    'ViewHelper',
        array('Errors', array('tag' => 'p', 'class' => 'error')),
        array('Description', array('tag' => 'p', 'class' => 'description')),
        array('HtmlTag', array('tag' => 'td', 'class' => 'fonddansform')),
        array('ApLabel', array('tag' => 'td', 'classtag' => 'fonddansform')),
        array(array('tr' => 'HtmlTag'), array('tag' => 'tr'))
        );
        
        // Champ "depuisJ" est un Text filtré
        $dateDepuisJ = new Zend_Form_Element_Text('depuisJ');
        $dateDepuisJ->addValidators(array(new Zend_Validate_Date('DD/MM/YYYY', 'fr-FR')))
        ->setLabel('Depuis quelle date ?')
        ->setDescription("L'extraction prendra toutes les fiches créées\nou modifiées à partir de cette date.\nLaisser vide pour prendre toutes les fiches.")
        ->setRequired(false)
        ->setErrorMessages(array("La date est au format JJ/MM/AAAA"))
        ->setDecorators($decorators);
        $this->addElement($dateDepuisJ);
        
        // Champ "depuisH" est un Text filtré
        $dateDepuisH = new Zend_Form_Element_Text('depuisH');
        $dateDepuisH->addValidators(array(new Zend_Validate_Date('HH:mm:ss', 'fr-FR')))
        ->setLabel('Après quelle heure ?')
        ->setDescription("Complément horaire pour la date indiquée.\nVous pouvez préciser une heure au format HH:MM:SS ou laisser la zone vide pour prendre la journée entière.")
        ->setRequired(false)
        ->setErrorMessages(array("L'horaire est au format HH:MM:SS"))
        ->setDecorators($decorators);
        $this->addElement($dateDepuisH);
        
        // Boutons radio pour sélectionner le type de fiches à extraire (Encours)
        $encoursRadio = new Zend_Form_Element_Radio('encours');
        $encoursRadio
        ->setDecorators($decorators)
        ->setLabel('Etat des inscriptions ?')
        ->addMultiOptions(array('1' => 'en cours', '0' => 'terminées', '2' => 'toutes'))
        ->setSeparator(' ')
        ->setValue(0);
        $this->addElement($encoursRadio);
        
        // Bouton "Annuler"
        $decoratorsButton = array(
		    'ViewHelper',
        array('HtmlTag', array('tag' => 'a', 'href' => $this->getCancel())),
        array(array('td' => 'HtmlTag'), array('tag' => 'td', 'class' => 'fonddansform'))
        );
        $this->putsCancelButton('Retour', $decoratorsButton, 80, 'btnliste');

        // Bouton "Extraire" est un Submit
        $decoratorsButton = array(
		    'ViewHelper',
        array('HtmlTag', array('tag' => 'td', 'class' => 'fonddansform')),
        );
        $this->putsSubmitButton('Extraire', $decoratorsButton, 0, 'btnliste');

        // Groupe des boutons
        $this->addDisplayGroup(
        array('cancel','submit'),'buttons',array(
        'decorators' => array('FormElements',array(array('tr' => 'HtmlTag'), array('tag' => 'tr'))),
        ));

        // place la balise <table>
        $this->setDecorators(
        array('FormElements',
              array('HtmlTag', array('tag' => 'table', 'class' => 'toute')),
		      'Form'));
    }
    /**
     * Initialise les champs DepuisJ et DepuisH à partir d'une date donnée au format MySQL (ISO_9075)
     * @param string $dateIso9075
     */
    public function setDateInitiale($dateIso9075) 
    {
        $this->setDefaults(array('depuisJ' => Pits_Format::date("dd/MM/YYYY", $dateIso9075),
                            'depuisH' => Pits_Format::date('HH:mm:ss', $dateIso9075)));
        return $this;
    }
}