<?php
/**
 * Passerelle pour la table `eleves`
 *
 * @project pits
 * @package application/models/dbtables
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 22 mai 2011
 * @version $Id: $
 */

class Pits_Model_DbTable_TEleves extends Zend_Db_Table_Abstract
{
    protected $_name = 'eleves';
    protected $_primary = 'eleveId';
    protected $_rowClass = 'Pits_Model_DbTable_Row_TEleves';
	/**
	 * Liaisons avec la table villes
	 * 
	 * @var array() unknown_type
	 */
	protected $_referenceMap = array(
								'etablissement' => array(
											'columns' => 'CodeEN',
											'refTableClass' => 'Pits_Model_DbTable_TEtablissements',
											),
								'stationR1' => array(
											'columns' =>'CodeStation1',
											'refTableClass' =>'Pits_Model_DbTable_TStations',
											),
								'stationR2' => array(
											'columns' =>'CodeStation2',
											'refTableClass' =>'Pits_Model_DbTable_TStations',
											),
								'tarif' => array(
											'columns' =>'CodeTarif',
											'refTableClass' =>'Pits_Model_DbTable_TTarifs',
											),
								'user' => array(
											'columns' =>'userId',
											'refTableClass' =>'Pits_Model_DbTable_TUser',
											),
								'villeR1' => array(
											'columns' =>'CommuneR1',
											'refTableClass' =>'Pits_Model_DbTable_TVilles',
											),
								'villeR2' => array(
											'columns' =>'CommuneR2',
											'refTableClass' =>'Pits_Model_DbTable_TVilles',
											),
								);
    
    /**
     * Méthode find avec controle éventuel d'authentification
     * $param : paramètre ordinaire de find
     * $auth  : booléen qui force le contrôle si true (false par défaut)
     * (non-PHPdoc)
     * @see Zend_Db_Table_Abstract::find()
     */
    public function find($param, $auth = false) 
    {
        $record = parent::find($param);
        if (is_null($record) || ($auth && $record->current()->userId != Zend_Auth::getInstance()->getIdentity()->userId)) {
            return null;
        }
        return $record;
    }
    /**
     * Renvoie le nombre d'enfant de l'utilisateur (authentifié par défaut)
     * @return int
     */
    public function famille($userId = -1)
    {
        if ($userId == -1) {
            $userId = Zend_Auth::getInstance()->getIdentity()->userId;
        }
        // ici, on compte même les fiches validées par un paiement
        return (int) $this->fetchAll($this->select()
        ->from($this, array('nbEnfants' => 'COUNT(`eleveId`)'))
        ->where('userId = ?', $userId)
        )->current()
        ->nbEnfants;
    }
    /**
     * Recalcul du tarif COBAS si nécessaire
     *
     * @param int $userId
     */
    public function tarifFamille($userId = -1)
    {
        if ($userId = -1) {
            $userId = Zend_Auth::getInstance()->getIdentity()->userId;
        }
        $select = $this->select()
        ->where('CodeTarif <= 2') // tarifs COBAS 1: normal ; 2: famille
        ->where('userId = ?', $userId)
        ->where('ficheValidee = 0'); // fiches non validées par un paiement
        $records = $this->fetchAll($select);
        //$famille = count($records);
        $famille = $this->famille();
        foreach ($records as $enfant) {
            $enfant->majTarif($famille);
        }
    }
    /**
     * Valide les fiches des élèves de la référence
     *
     * @param array $eleveIds
     * @param string $datetrans
     * @param string $idtrans
     */
    public function validePaiement($eleveIds, $datetrans, $idtrans)
    {
        for ($j=0; $j < count($eleveIds); $j++) {
            $eleve = $this->find($eleveIds[$j])->current();
            $eleve->valide($datetrans, $idtrans);
        }
    }
    public function getDatesPourAdmin() 
    {
        $where = $this->select()
        ->from($this, array('derniereCreation' => 'max(dateCreation)', 
                            'derniereModif' => 'max(dateModif)', 
                            'derniereExtraction' => 'max(dateExtraction)'));
        $record = $this->fetchAll($where)->current();
        return array(
           'derniereCreation' => Pits_Model_Format::date("dd/MM/YYYY HH:mm:ss", $record['derniereCreation']),
           'derniereModif' => Pits_Model_Format::date("dd/MM/YYYY HH:mm:ss", $record['derniereModif']),
           'derniereExtraction' => Pits_Model_Format::date("dd/MM/YYYY HH:mm:ss", $record['derniereExtraction']),
        );
    }
    public function nbUsersAvecEnfants() 
    {
        $where = $this->select()->from($this, 'userId')->group('userId');
        return count($this->fetchAll($where));
    }
    public function nbEnfantsInscrits() 
    {
        $where = $this->select()->from($this, array('nbInscrits' => 'count(*)'))->where('ficheValidee = ?', 1);
        $record = $this->fetchAll($where)->current();
        return $record['nbInscrits'];
    }
    public function nbEnfantsPreInscrits() 
    {
        $where = $this->select()->from($this, array('nbPreInscrits' => 'count(*)'))->where('ficheValidee = ?', 0);
        $record = $this->fetchAll($where)->current();
        return $record['nbPreInscrits'];
    }
    public function nbEnfantsAExtraire($dateExtraction) 
    {
        $where = $this->select()->from($this, array('nbEleves' => 'count(*)'))->where('dateExtraction = ?', $dateExtraction);
        $record = $this->fetchAll($where)->current();
        return (int) $record->nbEleves;
    }
    /**
     * Donne la date de la dernière extraction
     * 
     * @return string
     */
    public function lastDateExtraction() 
    {
        $select = $this->select()->from($this, array('lastExtraction' => 'max(dateExtraction)',));
        $record = $this->fetchAll($select)->current();
        return $record->lastExtraction;
    }
}