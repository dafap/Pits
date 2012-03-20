<?php
/**
 * Formulaire d'un utilisateur
 *
 * Utilisé pour création ou modification du compte
 *
 * @project pits
 * @package application/forms
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 23 mai 2011
 * @version $Id: $
 */

class Pits_Form_User extends Ap_Form
{
    private $_modif = false;
    /**
     * Valide le formulaire en regardant le mot de passe bis et en modifiant les validators d'Email en modification
     *
     * @param array $data
     * @return boolean
     */
    public function isValid($data)
    {
        // le mot de passe est confirmé
        $mdp = $data['mdpbis'];
        if (!$this->_modif || !empty($mdp)) {
            $this->getElement('mdp')->addValidator(new Pits_Validate_PasswordMatch($data['mdpbis']));
        }
        // en modification, si le email est inchangé alors il est bon
        if ($this->getElement('Email')->getValue() == $data['Email']) {
            $this->getElement('Email')->removeValidator("Db_NoRecordExists");
        }
        return parent::isValid($data);
    }
    /**
     * Initialise le formulaire pour le mode modification
     *
     * Les paramètres $action et $retour sont des tableaux array('controller'=> ..., 'action'=> ...)
     * pour les boutons valider et abandonner
     *
     * @param array $action
     * @param array $retour
     */
    public function modeModif($action, $retour)
    {
        $this->_modif = true;
        // mdp non obligatoire en modification
        $this->getElement('mdp')->setRequired(false);
        $this->getElement('mdpbis')->setRequired(false);
        // action
        $this->setAction($this->getView()->link($action['controller'], $action['action']));
        // retour
        $this->getElement('cancel')
        ->setOptions(array('onclick' => "pageRetour('" . $retour['controller'] . "','" . $retour['action'] . "');"));

        return $this;
    }
}