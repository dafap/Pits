<?php
/**
 * Classe étendant Zend_Db_Table_Row_Abstract pour la passerelle TUser
 *
 * Surcharge la méthode save pour gérer les champs previousIp, datePreviousLogin, nomSA et prenomSA
 *
 * @project pits
 * @package application/models/dbtables/Row
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 23 mai 2011
 * @version $Id: $
 */

class Pits_DbTable_Row_TUser extends Pits_DbTable_Row_Abstract
{
    /**
     * Enregistre datelastLogin et lastIp
     */
    public function login() 
    {
        $today = Pits_Format::date('YYYY-MM-dd HH:mm:ss');
        $this->_setData('dateLastLogin', $today);
        $this->_setData('lastIp', $_SERVER['REMOTE_ADDR']);
        return $this->save();
    }
    /**
     * Marque la fiche débloqués
     */
    public function debloque()
    {
        $today = Pits_Format::date('YYYY-MM-dd HH:mm:ss');
        $this->_setData('bloque', 0);
        $this->_setData('lastIp', $_SERVER['REMOTE_ADDR']);
        $this->_setData('dateLastLogin', $today);
        $this->_setData('temoin', 'debloque le ' . $today);
        return $this->save();
    }
    /**
     * Enregistre un mot de passe
     * 
     * @param string $mdp
     */
    public function setMdp($mdp)
    {
        $today = Pits_Format::date('YYYY-MM-dd HH:mm:ss');
        $this->_setData('mdp', sha1($mdp));
        $this->_setData('lastIp', $_SERVER['REMOTE_ADDR']);
        $this->_setData('dateModif', $today);
        $this->_setData('temoin', 'oubli mdp le ' . $today);
        return $this->save(false);
    }
    /**
     * Enregistre un controle
     * 
     * @param string $ctrl
     */
    public function setCtrl($ctrl)
    {
        $today = Pits_Format::date('YYYY-MM-dd HH:mm:ss');
        $this->_setData('ctrl', md5($ctrl));
        $this->_setData('lastIp', $_SERVER['REMOTE_ADDR']);
        $this->_setData('dateModif', $today);
        //$this->_setData('temoin', 'accès tp le ' . $today);
        return $this->save(false);
    }
    /**
     * Pour setMdp(), l'utilisateur n'est pas logé, $login vaut false
     *
     * (non-PHPdoc)
     * @see Zend_Db_Table_Row_Abstract::save()
     */
    public function save($login = true)
    {
        if (array_key_exists('lastIp', $this->_cleanData)){
            $this->_setData('previousIp',  $this->_cleanData['lastIp']);
        }
        if ($login) {
            if (array_key_exists('dateLastLogin', $this->_cleanData)) {
                $this->_setData('datePreviousLogin', $this->_cleanData['dateLastLogin']);
            } else {
                $this->_setData('datePreviousLogin', Pits_Format::date('YYYY-MM-dd HH:mm:ss', 'init'));
            }
        }
        $saFilter = new Pits_Filter_SansAccent();
        $this->_setData('nomSA', $saFilter->filter($this->_data['Nom']));
        $this->_setData('prenomSA', $saFilter->filter($this->_data['Prenom']));

        return parent::save();
    }
    /**
     * Initialisation des champs nomSA et prenomSA
     * 
     */
    public function majSA() {
        $saFilter = new Pits_Filter_SansAccent();
        $this->_setData('nomSA', $saFilter->filter($this->_data['Nom']));
        $this->_setData('prenomSA', $saFilter->filter($this->_data['Prenom']));
        
        return parent::save();
    }
}