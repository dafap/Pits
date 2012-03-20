<?php
/**
 * Application PiTS
 *
 * Passerelle vers le table user
 *
 * @category   pits
 * @package    application
 * @subpackage models
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

include_once 'Zend/Db/Table/Abstract.php';

/**
 * @category   pits
 * @package    application
 * @subpackage models
 * @author     pomirol
 */
class TUser extends Zend_Db_Table_Abstract
{
    /**
     * Nom de la table
     *
     * @var string
     */
    protected $_name = "user";

    /**
     * Nom de la clé primaire
     *
     * @var string
     */
    protected $_primary = "userId";

    /**
     * Nom de la classe des enregistrements
     *
     * @var string
     */
    protected $_rowClass = 'Pits_DbTable_Row_TUser';

    /**
     * Nom de la classe des jeux d'enregistrements
     *
     * @var string
     */
    //    protected $_rowsetClass = 'Pits_Db_Table_Rowset';

    /**
     * Liaisons avec la table villes
     *
     * @var array() unknown_type
     */
    protected $_referenceMap = array(
								'Ville' => array(
											'columns' => 'Commune',
											'refTableClass' => 'TVilles',
    ),
    );
    /**
     * Place 0 dans le champ $bloque de la fiche $userId
     * @param int $userId
     * @return int (1 s'il n'y a pas d'erreur, 0 sinon)
     */
    public function debloque($userId)
    {
        $data = array(
                       'bloque' => 0,
                       'dateLastLogin' => Pits_Format::date("YYYY-MM-dd HH:mm:ss"),
                       'lastIp' => $_SERVER['REMOTE_ADDR'],
                       'temoin' => 'confirme le ' . Pits_Format::date("YYYY-MM-dd HH:mm:ss"),
        );
        $where = $this->getAdapter()->quoteInto('userId = ?', $userId);
        return $this->update($data, $where);
    }
    /**
     * Enregistre le mot de passe. Il est passé en clair et sera enregistré encrypté sha1.
     * @param string $mdp // Mot de passe en clair à enregistrer
     * @param int $userId
     * @return boolean Compte-rendu de l'opération
     */
    public function setMdp($mdp, $userId)
    {
        $data = array('mdp' => sha1($mdp),
                      'dateModif' => Pits_Format::date("YYYY-MM-dd HH:mm:ss"),
                      'lastIp' => $_SERVER['REMOTE_ADDR'],
                      'temoin' => 'oubli mdp le ' . Pits_Format::date("YYYY-MM-dd HH:mm:ss"),);
        $where = $this->getAdapter()->quoteInto('userId = ?', $userId);
        return $this->update($data, $where);
    }
    /**
     * Enregistre les données dateLastLogin et lastIp et déplace les anciennes valeurs
     * dans datePreviousLogin et previousIp
     * @param int $userId
     * @return boolean Compte-rendu de l'opération
     */
    public function infoClient($userId)
    {
        // Préparation des données
        $record = $this->find($userId)->current();
        $data = array(
                       'dateLastLogin' => Pits_Format::date("YYYY-MM-dd HH:mm:ss"),
                       'lastIp' => $_SERVER['REMOTE_ADDR'],
                       'datePreviousLogin' => $record->dateLastLogin,
                       'previousIp' => $record->lastIp,
        );
        // Enregistre les données
        $where = $this->getAdapter()->quoteInto('userId = ?', $userId);
        return $this->update($data, $where);
    }
    public function userInscrits()
    {
        $r = $this->fetchAll('categorie=1');
        return count($r);
        
    }
    public function userBloque()
    {
        $r = $this->fetchAll('categorie=1 And bloque=1');
        return count($r);
    }
    public function userValide()
    {
        $r = $this->fetchAll('categorie=1 And bloque=0');
        return count($r);
    }
    public function userValideSansEnfant()
    {
        $r = $this->fetchAll('categorie=1 And bloque=0');
        return count($r);
    }
}