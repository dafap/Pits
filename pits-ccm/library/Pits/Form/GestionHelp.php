<?php
/**
 * Application PiTS
 * Gestion des pr�inscriptions au service de transports scolaires
 *
 * @date 30 juil. 2010
 *
 * @category   pits
 * @package    library/pits
 * @subpackage form
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits r�serv�s
 * @version    1.0.0
 * @since      Ce fichier est disponible depuis la version 1.0.0
 */

/**
 * @category   pits
 * @package    library/pits
 * @subpackage form
 * @author     pomirol
 */
class Pits_Form_GestionHelp extends Pits_Form_Abstract
{
    /**
     * Pour utiliser cette form préciser
     * - setAction(url)
     * - setMethod(post)
     * - setDestination(path)
     * (non-PHPdoc)
     * @see Zend_Form::init()
     */
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
        // Champ "fileHelp" est un "input file"
        $fileValidators = array(new Zend_Validate_File_Extension(array('doc', 'pdf', 'case' => false)),);
        $fileHelp = new Zend_Form_Element_File('fileHelp');
        $fileHelp->addValidators($fileValidators)
        ->setLabel('Mettre en ligne le fichier d\'aide')
        ->setDescription('Donner le fichier à mettre en ligne dans la page d\'aide (types .doc ou .pdf uniquement)')
        ->setDecorators($decorators);
        // indexPath est une valeur déclarée dans config.ini
        //->setDestination(Zend_Registry::get('indexPath') . DIRECTORY_SEPARATOR . 'documents');
        $this->addElement($fileHelp);

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
    public function setDestination($path)
    {
        $fileHelp = $this->getElement('fileHelp');
        $fileHelp->setDestination($path);
        return $this;
    }
}