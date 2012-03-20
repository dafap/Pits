<?php
/**
 * Application PiTS
 * Gestion des pr�inscriptions au service de transports scolaires
 *
 * @date 30 juin 2010
 *
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits r�serv�s
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

/**
 * @category   pits
 * @package    library
 * @subpackage pits/Form
 * @author     pomirol
 */
class Pits_Form_Recherche extends Pits_Form_Abstract
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

        // Champ "Nom" est un Text
        $mdpValidators = array(new Pits_Validate_Mdp(7, 16, 2),);
        $critereNom = new Zend_Form_Element_Text('critereNom');
        $critereNom//->addValidators($mdpValidators)
        ->setLabel('Donner le début du nom')
        ->setDescription('(laisser vide si la recherche porte sur l\'email)')
        ->setDecorators($decorators);
        $this->addElement($critereNom);
         
        // Champ "Email" est un Text
        $mdpValidators = array(new Pits_Validate_Mdp(7, 16, 2),);
        $critereEmail = new Zend_Form_Element_Text('critereEmail');
        $critereEmail//->addValidators($mdpValidators)
        ->setLabel('Donner un morceau de l\'email')
        ->setDescription('(laisser vide si la recherche porte sur le nom)')
        ->setDecorators($decorators);
        $this->addElement($critereEmail);

        if ($this->getInitValue('modele') == 'eleve') {
            $encoursRadio = new Zend_Form_Element_Radio('encours');
            $encoursRadio->setDecorators($decorators)
            ->setLabel('Etat des inscriptions ?')
            ->addMultiOptions(array('1' => 'en cours', '0' => 'terminées', '2' => 'toutes'))
            ->setSeparator(' ')
            ->setValue(0);
            $this->addElement($encoursRadio);
        } elseif ($this->getInitValue('modele' == 'user')) {
            ;
        }
        // Bouton "Annuler"
        $decoratorsButton = array(
		    'ViewHelper',
        array('HtmlTag', array('tag' => 'a', 'href' => $this->getCancel())),
        array(array('td' => 'HtmlTag'), array('tag' => 'td', 'class' => 'fonddansform'))
        );
        $this->putsCancelButton('Retour', $decoratorsButton, 80, 'btnliste');

        // Bouton "Rechercher" est un Submit
        $decoratorsButton = array(
		    'ViewHelper',
        array('HtmlTag', array('tag' => 'td', 'class' => 'fonddansform')),
        );
        $this->putsSubmitButton('Rechercher', $decoratorsButton, 0, 'btnliste');

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
}