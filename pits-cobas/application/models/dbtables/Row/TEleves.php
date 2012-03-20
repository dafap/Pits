<?php
/**
 * Classe étendant Zend_Db_Table_Row_Abstract pour la passerelle TUser
 *
 * Surcharge la méthode save pour gérer les champs previousIp, datePreviousLogin, nomSA et prenomSA
 *
 * @project pits
 * @package application/models/dbtables/Row
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 25 mai 2011
 * @version $Id: $
 */

class Pits_Model_DbTable_Row_TEleves extends Pits_Model_DbTable_Row_Abstract
{
    /**
     * @param optinnal boolean $nouveau
     *
     * (non-PHPdoc)
     * @see Zend_Db_Table_Row_Abstract::save()
     */
    public function save()
    {
        // $mode : true => création ou false => modif 
        $mode = is_null($this->_data['eleveId']);
        
        $dateToday = Pits_Model_Format::date('YYYY-MM-dd HH:mm:ss');
        if ($mode){
            $dateOrigine = Pits_Model_Format::date('YYYY-MM-dd HH:mm:ss', 'init');
            $this->_setData('dateCreation', $dateToday);
            $this->_setData('dateModif', $dateOrigine);
            $this->_setData('dateExtraction', $dateOrigine);
            $this->_setData('dateValidation', $dateOrigine);
        } else {
            $this->_setData('dateModif',  $dateToday);
            $this->_setData('ficheModifiee', 1);
        }
         
        $saFilter = new Pits_Filter_SansAccent();
        $this->_setData('nomSA', $saFilter->filter($this->_data['Nom']));
        $this->_setData('prenomSA', $saFilter->filter($this->_data['Prenom']));
        $this->_setData('nomR1SA', $saFilter->filter($this->_data['NomR1']));
        $this->_setData('prenomR1SA', $saFilter->filter($this->_data['PrenomR1']));
        $this->_setData('nomR2SA', $saFilter->filter($this->_data['NomR2']));
        $this->_setData('prenomR2SA', $saFilter->filter($this->_data['PrenomR2']));

        return parent::save();
    }
    /**
     * Met à jour le tarif COBAS
     *  CodeTarif = 1 : COBAS normal
     *  CodeTarif = 2 : BOBAS famille
     * @param int $famille
     */
    public function majTarif($famille) 
    {
        if ($famille < 3 && $this->CodeTarif == 2) {
            $this->CodeTarif = 1;
            parent::save();
        } elseif ($famille > 2 && $this->CodeTarif == 1) {
            $this->CodeTarif = 2;
            parent::save();
        }
    }
    public function valide($date, $ref)
    {
        $this->datePaiement = $date;
        $this->refPaiement = $ref;
        $this->ficheValidee = 1;
        parent::save();
    }
    /**
     * Affectation de CodeService1 et enregistrement
     * @param string|null $CodeService
     */
    public function setCodeService1($CodeService) {
        $this->_setData('CodeService1', $CodeService);
        return parent::save();
    }
    /**
     * Affectation de CodeService2 et enregistrement
     * @param string|null $CodeService
     */
    public function setCodeService2($CodeService) {
        $this->_setData('CodeService2', $CodeService);
        return parent::save();
    }
}