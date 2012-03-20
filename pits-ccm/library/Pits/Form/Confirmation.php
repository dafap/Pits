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
 * Ce formulaire de confirmation va passer gérer les paramètres cancel, forward, hidden, hash, 
 * buttonClass, cancelLabel et submitLabel
 * Il renvoie par un hidden un paramètre confirmation de valeur 1
 * - cancel est unique
 * - forward est unique mais optionnel
 * - hidden est optionnel, unique ou multiple. Il est donné dans un tableau 'nom_hidden' => 'valeur_hidden'
 * - hash est unique mais optionnel
 * - buttonClass est optionnel, 'btnliste' par défaut
 * - cancelLabel est optionnel, 'Abandonner' par défaut
 * - submitLabel est optionnel, 'Confirmer' par défaut
 * Exemple d'appel :
 *  $form = new Pits_Form_Confirmation(array('cancel'=>$url, 'hidden'=>array('a'=>$a,'b'=>$b), 'hash'=>true));
 *
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_Form_Confirmation extends Pits_Form_Abstract
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

        // Jeton anti-CSRF si 'hash' est défini et a la valeur true
        $hash = $this->getInitValue('hash');
        if (!is_null($hash) && $hash) {
            $this->putsHash('token');
        }

        // Le forward
        $this->putsForward();

        // Les hiddens
        $this->putsHidden('confirmation', 1);
        $hiddens = $this->getInitValue('hidden');
        if (is_array($hiddens)) {
            foreach ($hiddens as $nom => $valeur) {
                $this->putsHidden($nom, $valeur);
            }
        }

        // Classe des boutons
        $buttonClass = $this->getInitValue('buttonClass');
        if (is_null($buttonClass)) $buttonClass = 'btnliste';
        
        // Bouton "Abandonner"
        $decoratorsButton = array(
		    'ViewHelper',
        array('HtmlTag', array('tag' => 'a', 'href' => $this->getCancel())),
        array(array('td' => 'HtmlTag'), array('tag' => 'td'))
        );
        $cancelLabel = $this->getInitValue('cancelLabel');
        if (is_null($cancelLabel)) $cancelLabel = 'Abandonner';
        $this->putsCancelButton($cancelLabel, $decoratorsButton, 80, $buttonClass);

        // Bouton "Confirmer" est un Submit
        $decoratorsButton = array(
		    'ViewHelper',
        array('HtmlTag', array('tag' => 'td')),
        );
        $submitLabel = $this->getInitValue('submitLabel');
        if (is_null($submitLabel)) $submitLabel = 'Confirmer';
        $this->putsSubmitButton($submitLabel, $decoratorsButton, 0, $buttonClass);

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