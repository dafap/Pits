<?php
/**
 * Formulaire d'un utilisateur
 *
 * Mise en place du validateur vérifiant si les champs mdp et mdpbis sont égaux
 *
 * @project télé-paiement CCM
 * @package application/forms
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 20 sept. 2011
 * @version $Id: $
 */

class Tp_Form_User extends Zend_Form
{
    /**
     * Valide le formulaire en regardant le mot de passe bis
     *
     * @param array $data
     * @return boolean
     */
    public function isValid($data)
    {
        // le mot de passe est confirmé
        $mdp = $data['mdpbis'];           
        $this->getElement('mdp')->addValidator(new Tp_Validate_PasswordMatch($data['mdpbis']));
        return parent::isValid($data);
    }
}