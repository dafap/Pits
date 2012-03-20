<?php
/**
 * Passerelle pour la table `user`
 *
 * @project télé-paiement CCM
 * @package application/models/dbtables
 * @author  DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date    20 mai 2011 - rev. 19/09/2011
 * @version $Id: $
 */
require_once 'Row/TUser.php';

class Tp_Model_DbTable_TUser extends Tp_Model_DbTable_Abstract
{
    protected $_name = 'user';
    protected $_primary = 'userId';
    protected $_rowClass = 'Tp_Model_DbTable_Row_TUser';
    /**
     * Liaisons avec la table villes
     *
     * @var array() unknown_type
     */
    protected $_referenceMap = array(
								'Ville' => array(
											'columns' => 'Commune',
											'refTableClass' => 'Tp_Model_DbTable_TVilles',),
    );
    /**
     * Retourne la liste des user répondant au critère indiqué par $where, triée par `Nom`, `Prenom`
     *
     * @param string|array|Zend_Db_Table_Select $where
     * @return array(`userId` => Zend_Db_Table_Row, ...)
     */
    public function liste($where = "1") {
        $result = array();
        $users = $this->fetchAll($where,array('Nom', 'Prenom'));
        foreach ($users as $user) {
            $result[$user->userId] = $user;
        }
        return $result;
    }
    /**
     * Retourne la liste des user de catégorie 1
     * @return array(`userId` => Zend_Db_Table_Row, ...)
     */
    public function user()
    {
        return $this->liste("categorie=1");
    }
    /**
     * Retourne la liste des user de catégorie 2
     * @return array(`userId` => Zend_Db_Table_Row, ...)
     */
    public function admin()
    {
        return $this->liste("categorie=2");
    }
    /**
     * Retourne la liste des user de catégorie 3
     * @return array(`userId` => Zend_Db_Table_Row, ...)
     */
    public function sadmin()
    {
        return $this->liste("categorie=3");
    }
    /**
     * Retourne le nombre d'inscrits de catégorie indiquée (1 par défaut)
     * @param int $categorie
     * @return int
     */
    public function inscrits($categorie = 1)
    {
        $where = $this->select()->from($this, array('nbinscrits' => 'count(*)'))->where('categorie = ?', $categorie);
        $record = $this->fetchAll($where)->current();
        return $record['nbinscrits'];
    }
    /**
     * Retourne le nombre d'utilisateurs bloqués
     * @return int
     */
    public function bloques()
    {
        $where = $this->select()->from($this, array('nbbloques' => 'count(*)'))->where('categorie = ?', 1)->where('bloque = ?', 1);
        $record = $this->fetchAll($where)->current();
        return $record['nbbloques'];
    }
    /**
     * Retourne une chaine composée de 'Titre Nom Prénom'
     * @param int $userId
     * @return string
     */
    public function identite($userId) 
    {
        $record = $this->find($userId)->current();
        return $record->Titre . ' ' . $record->Nom . ' ' . $record->Prenom;
    }
    /**
     * Retourne un tableau des noms et prénoms indexé par userId (pour select par exemple)
     * @return array
     */
    public function nomsprenoms() 
    {
        $where = $this->select()
        ->from($this, array('userId' => 'userId', 'nomprenom' => 'concat(nom, " ", prenom)'))
        ->where('userId IN (SELECT userId FROM factures)')
        ->order('nomprenom');
        $records = $this->fetchAll($where);
        
        $result = array();
        foreach ($records as $record) {
            $result[$record->userId] = $record->nomprenom;
        }
        return $result;
    }
}