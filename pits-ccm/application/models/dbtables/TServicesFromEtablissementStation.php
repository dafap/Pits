<?php
/**
* Passerelle pour la vue `servicesfrometablissementstation`
*
* Permet l'affectation d'un service lors de l'inscription à partir du CodeEN et du CodeStationR1 ou du CodeStationR2
* 
* @project pits
* @package package_name
* @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
* @date 5 juin 2011
* @version $Id: $
*/

class Pits_DbTable_TServicesFromEtablissementStation extends Zend_Db_Table_Abstract
{
    protected $_name = 'servicesfrometablissementstation';
    protected $_primary = 'CodeStation'; // fictif
    
    /**
     * Retourne le CodeService à partir du CodeEN et du CodeStation s'il y a de la place
     * Retourne null s'il n'y a plus de place
     * 
     * @param string $CodeEN
     * @param int $CodeStation
     * @return string
     */
    public function findService($CodeEN, $CodeStation) 
    {
        if (is_null($CodeStation)) return null;
        $where = $this->select()->from($this, 'CodeService')->where('CodeEN = ?', $CodeEN)->where('CodeStation = ?', $CodeStation);
        $row = $this->fetchAll($where)->current();
        return is_null($row) ? null : $row->CodeService;
    }
}

    