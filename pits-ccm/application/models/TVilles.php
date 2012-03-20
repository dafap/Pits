<?php
/**
 * Application PiTS 
 * 
 * Passerelle vers le table villes
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
include_once 'models/TEtablissements.php';
include_once 'models/TStations.php';

/**
 * @category   pits
 * @package    application
 * @subpackage models
 * @author     pomirol
 */
class TVilles extends Zend_Db_Table_Abstract
{
	/**
	 * Nom de la table
	 * 
	 * @var string
	 */
	protected $_name = "villes";
	
	/**
	 * Nom de la clé primaire
	 * 
	 * @var string
	 */
	protected $_primary = "CodeInsee";
	
    /**
     * Nom de la classe des enregistrements
     *
     * @var string
     */
//    protected $_rowClass = 'Pits_Db_Table_Row';
    
    /**
     * Nom de la classe des jeux d'enregistrements
     *
     * @var string
     */
//    protected $_rowsetClass = 'Pits_Db_Table_Rowset';

	/**
	 * Lecture de la table pour rendre un tableau associatif CodeInsee => Nom
	 * qui va notamment servir à construire les listes déroulantes des formulaires.
	 * @param string where
	 * @return array() value => label
	 */
	public function liste($where = "CCM=1") {
		$result = array();
		$villes = $this->fetchAll($where,'Nom');
		foreach ($villes as $ville) {
			$result[$ville->CodeInsee] = $ville->Nom;
		}
		return $result;
	}
	
	/**
	 * Lecture de la table pour rendre un tableau associatif CodeEN => NomVille-NomEtablissement
	 * qui va notamment servir à construire les listes déroulantes des formulaires.
	 * 
	 * @return array() value => label
	 */
	public function listeEtabs() {
		$result = array();		
		$villes = $this->fetchAll($this->select()->from($this,array('CodeInsee', 'Nom',))->where('Departement = ?', '33'));
		$TEtabs = new TEtablissements();
		foreach ($villes as $ville) {
			$etabs = $TEtabs->getByVille($ville->CodeInsee, 1); // uniquement les établissements visibles
			foreach ($etabs as $etab) {
				$result[$etab->CodeEN] = $ville->Nom . ' - ' . $etab->Nom;
			}
		}
		return $result;
	}
	
	/**
	 * Construction d'un tableau associatif CodeStation => NomVille-NomStation.
	 * Si le paramètre est null, lecture de la table pour renvoyer tous les points d'arrêts
	 * Sinon, condition where
	 * 
	 * @param string(5) (optionnel) CodeInsee
	 * @return array() value => label
	 */
	public function listeStations($CodeInsee = null) {
		$result = array();
		$TStations = new TStations();
		$where = is_null($CodeInsee) ? 'CCM = 1' : $this->select()->where('CodeInsee = ?', $CodeInsee);
		$villes = $this->fetchAll($where, 'Nom');
		foreach ($villes as $ville) {
			$stations = $TStations->getByVille($ville->CodeInsee);
			foreach ($stations as $station) {
				$result[$station->CodeStation] = $ville->Nom . ' - ' . $station->Nom;
			}
		}
		return $result;
	}
}