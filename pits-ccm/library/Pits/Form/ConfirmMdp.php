<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 30 juillet 2010
 *
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    1.0.0
 * @since      Ce fichier est disponible depuis la version 1.0.0
 */

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_Form_ConfirmMdp extends Pits_Form_Abstract
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
        // Champ "ancienmdp" est un Password
        $mdpValidators = array(new Pits_Validate_Mdp(7, 16, 2),);
        $ancienmdp = new Zend_Form_Element_Password('ancienmdp');
        $ancienmdp->addValidators($mdpValidators)
        ->setLabel('Donnez l\'ancien mot de passe')
        ->setDecorators($decorators)
        ->setRequired(true);
        $this->addElement($ancienmdp);

        // Champ "nouveaumdp" est un Password
        $mdpValidators = array(new Pits_Validate_Mdp(7, 16, 2),);
        $nouveaumdp = new Zend_Form_Element_Password('nouveaumdp');
        $nouveaumdp->addValidators($mdpValidators)
        ->setLabel('Donnez le nouveau mot de passe')
        ->setDescription('(au moins 7 caractères dont 2 chiffres)')
        ->setDecorators($decorators)
        ->setRequired(true);
        $this->addElement($nouveaumdp);

        // Champ "bismdp" est un Password
        $mdpValidators = array(new Pits_Validate_Mdp(7, 16, 2),);
        $bismdp = new Zend_Form_Element_Password('bismdp');
        $bismdp->addValidators($mdpValidators)
        ->setLabel('Confirmez ce mot de passe')
        ->setDescription('Tapez le à nouveau !')
        ->setDecorators($decorators)
        ->setRequired(true);
        $this->addElement($bismdp);

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
    /**
     * Valide le formulaire
     *
     * @param array $data
     * @return boolean
     */
    public function isValid($data)
    {
        // vérification de l'ancien mdp
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $select = $db->select()
        ->from('user', array('ok' => 'count(userId)'))
        ->where('userId = ?', Zend_Auth::getInstance()->getIdentity()->userId)
        ->where('mdp = ?', sha1($data['ancienmdp']));
        $r = $db->query($select)->fetchAll();
        if ((int) $r[0]['ok'] != 1) {
            $this->getElement('ancienmdp')->addError('Mot de passe incorrect.');
            return false;
        }
        
        // le mot de passe est confirmé
        $this->getElement('nouveaumdp')->addValidator(new Pits_Validate_PasswordMatch($data['bismdp']));
        return parent::isValid($data);
    }

}