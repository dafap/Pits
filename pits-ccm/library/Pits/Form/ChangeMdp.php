<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 24 juin 2010
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
class Pits_Form_ChangeMdp extends Pits_Form_Abstract
{
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
        // Champ "Mot de passe" est un Password
        $mdpValidators = array(new Pits_Validate_Mdp(7, 16, 2),);
        $mdp = new Zend_Form_Element_Text('mdp');
        $mdp->addValidators($mdpValidators)
        ->setLabel('Donner un mot de passe')
        ->setDescription('(au moins 7 caractères dont 2 chiffres)')
        ->setDecorators($decorators)
        ->setRequired(true);
        $this->addElement($mdp);

        // Champ "userid" est un Hidden
        $this->putsHidden('userId');

        // Champ "url" est un Hidden
        $this->putsForward();

        // Jeton anti-CSRF
        $this->putsHash('token');

        // Bouton "Annuler"
        $decoratorsButton = array(
		    'ViewHelper',
        array('HtmlTag', array('tag' => 'a', 'href' => $this->getCancel())),
        array(array('td' => 'HtmlTag'), array('tag' => 'td'))
        );
        $this->putsCancelButton('Abandonner', $decoratorsButton, 80, 'btnliste');

        // Bouton "Enregistrer" est un Submit
        $decoratorsButton = array(
		    'ViewHelper',
        array('HtmlTag', array('tag' => 'td')),
        );
        $this->putsSubmitButton('Enregistrer', $decoratorsButton, 0, 'btnliste');

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