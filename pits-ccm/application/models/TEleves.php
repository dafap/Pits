<?php
/**
 * Application PiTS
 *
 * Passerelle vers le table eleves
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
class TEleves extends Zend_Db_Table_Abstract
{
    /**
     * Nom de la table
     *
     * @var string
     */
    protected $_name = "eleves";

    /**
     * Nom de la clé primaire
     *
     * @var string
     */
    protected $_primary = "eleveId";

    /**
     * Nom de la classe des enregistrements
     *
     * @var string
     */
    protected $_rowClass = 'Pits_DbTable_Row_TEleves';

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
								'etablissement' => array(
											'columns' => 'CodeEN',
											'refTableClass' => 'TEtablissements',
    ),
								'classe' => array(
											'columns' =>'Classe',
											'refTableClass' =>'TClasses',
    ),
								'stationR1' => array(
											'columns' =>'CodeStation',
											'refTableClass' =>'TStations',
    ),
								'stationR2' => array(
											'columns' =>'CodeStationR2',
											'refTableClass' =>'TStations',
    ),
								'user' => array(
											'columns' =>'userId',
											'refTableClass' =>'TUser',
    ),
								'tarif' => array(
											'columns' =>'CodeTarif',
											'refTableClass' =>'TTarifs',
    ),
								'villeR2' => array(
											'columns' =>'CommuneR2',
											'refTableClass' =>'TVilles',
    ),
    );

    public function nbEnfantsInscrits()
    {
        $select = $this->select()->from($this, 'eleveId')->where('nonInscrit = ?', 0)->where('encours = ?', 0);
        $r = $this->fetchAll($select);
        return count($r);
    }
    public function nbEnfantsEncours()
    {
        $select = $this->select()->from($this, 'eleveId')->where('nonInscrit = ?', 0)->where('encours = ?', 1);
        $r = $this->fetchAll($select);
        return count($r);
    }
    public function nbUserAvecEnfants()
    {
        $select = $this->select()->from($this, 'userId')->where('nonInscrit = ?', 0)->group('userId');
        $r = $this->fetchAll($select);
        return count($r);
    }
    /**
     * Reçoit les champs filtrés de Nom, Prenom et DateN du formulaire Pits_Form_InscriptionEleve
     *
     * @param string $nom
     * @param string $prenom
     * @param string $dateN
     */
    public function dejaInscrit($nom, $prenom, $dateN)
    {
        $saFilter = new Pits_Filter_SansAccent();
        $nomSA = $saFilter->filter($nom);
        $prenomSA = $saFilter->filter($prenom);
        $select = $this->select()
        ->from($this, array('present' => 'count(userId)'))
        ->where('Nom = ?', $nomSA)
        ->where('Prenom = ?', $prenomSA)
        ->where('DateN = ?', Pits_Format::date('YYYY-MM-dd', $dateN, 'fr_FR'));
        $r = $this->fetchAll($select);
        return $r[0]['present'] != 0;
    }
    /**
     * Valide les enfants du userId qui sont en cours d'inscription (encours=1 et nonInscrit=0)
     * Retourne le nombre de fiche validées
     *
     * @param int $userId
     * @return int
     */
    public function valideEnfants($userId)
    {
        $where = $this->getAdapter()->quoteInto('userId = ?', $userId)
        . $this->getAdapter()->quoteInto(' AND encours = ?', 1)
        . $this->getAdapter()->quoteInto(' AND nonInscrit = ?', 0);
        $data = array('encours' => 0,);
        return $this->update($data, $where);
    }
    /**
     *
     * Renvoie la liste des enfants inscrit du userId sous la forme d'un Zend_Db_Table_Rowset_Abstract
     * @param int $userId
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getInscrits($userId)
    {
        return $this->fetchAll($this->select()->where('userId = ?', $userId)->where('encours = ?', 0)->where('nonInscrit = ?', 0)->order('prenomSA'));
    }
    /**
     * Renvoie le nombre d'enfants connus d'un userId (inscrits ou non inscrits)
     * 
     * @param unknown_type $userId
     */
    public function nbConnus($userId) 
    {
        $select = $this->select()
        ->from($this, array('nb' => 'count(eleveId)'))
        ->where('userId = ?', $userId);
        $r = $this->fetchAll($select);
        return $r[0]['nb'];
    }
    /**
     * Renvoie le nombre d'enfants inscrits (inscription terminée ou en cours) pour un userId
     *
     * @param int $userId
     */
    public function nbInscrits($userId)
    {
        $select = $this->select()
        ->from($this, array('nb' => 'count(eleveId)'))
        ->where('userId = ?', $userId)
        ->where('nonInscrit = ?', 0);
        $r = $this->fetchAll($select);
        return $r[0]['nb'];
    }
    /**
     * Renvoie le nombre d'enfants encours d'inscription pour un userId
     *
     * @param $userId
     */
    public function nbEncours($userId)
    {
        $select = $this->select()
        ->from($this, array('nb' => 'count(eleveId)'))
        ->where('userId = ?', $userId)
        ->where('nonInscrit = ?', 0)
        ->where('encours = ?', 1);
        $r = $this->fetchAll($select);
        return $r[0]['nb'];
    }
    /**
     *
     * Renvoie la liste des enfants non inscrit du userId sous la forme d'un Zend_Db_Table_Rowset_Abstract
     * @param int $userId
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getNonInscrits($userId)
    {
        return $this->fetchAll($this->select()->where('userId = ?', $userId)->where('nonInscrit = ?', 1)->order('prenomSA'));
    }
    /**
     * Renvoie le nombre d'enfants non inscrits pour un userId donné
     *
     * @param int $userId
     */
    public function nbNonInscrits($userId)
    {
        return $this->getNonInscrits($userId)->count();
    }
    /**
     *
     * Renvoie la liste des enfants encours d'inscription du userId sous la forme d'un Zend_Db_Table_Rowset_Abstract
     * @param int $userId
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getEncours($userId)
    {
        return $this->fetchAll($this->select()->where('userId = ?', $userId)->where('encours = ?', 1)->where('nonInscrit = ?', 0)->order('prenomSA'));
    }
    /**
     * Renvoie la date de la dernière extraction au format de MySql (ISO_9075)
     *
     * @return string
     */
    public function getDateLastExtraction()
    {
        $select = $this->select()
        ->from($this, array('dateLastExtraction' => 'max(dateExtraction)',));
        $r = $this->fetchAll($select);
        return $r[0]['dateLastExtraction'];
    }
    /**
     * Compte le nombre d'enfants ayant ladate pour dateExtraction
     *
     * @param string $ladate
     * @return int
     */
    public function nbEnfantsExtraits($ladate)
    {
        $select = $this->select()
        ->from($this, array('nb' => 'count(eleveId)'))
        ->where('dateExtraction = ?', $ladate);
        $r = $this->fetchAll($select);
        return $r[0]['nb'];
    }
    /**
     * Méthode privée pour construire la clause where de la méthode extraction()
     *
     * @param string $champDate
     * @param string(date conforme) $depuis
     * @param int $encours
     * @return string
     */
    private function clause_where($champDate, $depuis, $encours)
    {
        $where = $this->getAdapter()->quoteInto($champDate . ' > ?', $depuis);
        switch ($encours) {
            case 0:
                $where .= ' AND encours = 0 AND nonInscrit = 0';
                break;
            case 1:
                $where .= ' AND encours = 1 AND nonInscrit = 0';
                break;
            default:
                $where .= ' AND nonInscrit = 0';
                break;
        }
        return $where;
    }
    /**
     * Renvoie la liste des élèves à extraire
     * - depuisJ contient la date à partir de laquelle doit se faire l'extraction
     * - depuisH précise éventuellement l'heure
     * - si encours == 0 on extrait uniquement les inscriptions terminées
     *              == 1 on extrait uniquement les inscriptions en cours
     *              == 2 on extrait toutes les inscriptions
     * Si depuisJ est vide ('') l'extraction porte sur tous les élèves (inscrits et éventuellement encours)
     *
     * Renvoie le nombre d'enfants extraits
     *
     * @param resource $flux
     * @param string $depuisJ
     * @param string $depuisH
     * @param int $encours
     * @return int
     */
    public function extraction($flux, $depuisJ, $depuisH, $encours)
    {
        $depuis = Pits_Format::date("YYYY-MM-dd HH:mm:ss", $depuisJ . ' ' . $depuisH, 'fr_FR');
        // verouiller la table
        $this->getAdapter()->query(new Zend_Db_Expr('LOCK TABLE eleves WRITE;'));
        // marquer la date d'extraction (now)
        $now = Pits_Format::date("YYYY-MM-dd HH:mm:ss"); // date actuelle
        //  - dans les fiches créées antérieurement
        $data = array('dateExtraction' => $now,);
        $nb =  $this->update($data, $this->clause_where('dateCreation', $depuis, $encours));
        //  - dans les fiches modifiées antérieurement
        $nb += $this->update($data, $this->clause_where('dateModif', $depuis, $encours));
        // compte le nombre d'élèves à extraire (ce nombre est inférieur ou égal à nb)
        $nbEnfants = $this->nbEnfantsExtraits($now);
        // envoi de la ligne d'en-tête dans le flux
        $cols = $this->info('cols');
        fputcsv($flux, $cols, ';', '"');
        unset($cols);
        // envoi des données
        $where = $this->select()->where('dateExtraction = ?', $now);
        for ($bloc = 250, $j = 0; $j < ceil($nbEnfants / $bloc); $j++) {
            $eleves = $this->fetchAll($where->order(array('Nom', 'Prenom'))->limit($bloc, $bloc * $j))->toArray();
            foreach ($eleves as $eleve) {
                fputcsv($flux, $eleve, ';', '"');
            }
        }
        rewind($flux);
        // marque les fiches extraites et démarque les fiches mofifiées
        $this->update(array('ficheModifiee' => 0, 'ficheExtraite' => 1),
        array('dateExtraction = ?' => $now));
        // déverrouille la table
        $this->getAdapter()->query(new Zend_Db_Expr('UNLOCK TABLES;'));
        // renvoie le compte rendu
        return $nbEnfants;
    }
    /**
     *
     * Passe tous les élèves à l'état `nonInscrit` = 1
     */
    public function desinscrire()
    {
        $data = array ('nonInscrit' => 1,);
        $this->update($data, '');
    }
    /**
     *
     * Passe tous les élèves à l'état `encours` = 0
     */
    public function nonencours()
    {
        $data = array('encours' => 0,);
        $this->update($data, '');
    }
    /**
     * Couleur définit l'état d'un userId de la manière suivante :
     * - noir : nbConnus = 0
     * - rouge: nbInscrits = 0
     * - jaune: nbEncours > 0
     * - vert : nbEncours = 0
     * et renvoie le nombre d'enfants connus
     * 
     * @return array
     */
    public function couleur($userId) 
    {
        if (($nb = $this->nbConnus($userId)) == 0) { // affectation et comparaison
            return array('couleur' => 'noir', 'connus' => $nb);
        }
        if ($this->nbInscrits($userId) == 0) {
            return array('couleur' => 'rouge', 'connus' => $nb);
        }
        if ($this->nbEncours($userId) == 0) {
            return array('couleur' => 'vert', 'connus' => $nb);;
        }
        return array('couleur' => 'jaune', 'connus' => $nb);;
    }
    /**
     * Ma couleur définit l'état d'un eleveId de la manière suivante :
     * - rouge : nonIncrit = 1
     * - jaune : encours = 1
     * - vert  : encours = 0
     * @param int $eleveId
     */
    public function macouleur($eleveId) 
    {
        $eleve = $this->find($eleveId)->current();
        return $eleve->nonInscrit == 1 ? 'rouge' : ($eleve->encours == 1 ? 'jaune' : 'vert');
    }
}