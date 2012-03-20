<?php
/**
 * Procédures de mise à jour des structures de pits-ccmontesquieu
 *
 * Description longue du fichier s'il y en a une
 *
 * @project project_name
 * @package package_name
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 14 avr. 2011
 * @version $Id: $
 */

class MajController extends Pits_Controller_Action
{
    private $_db;

    public function init()
    {
        $this->_db = Zend_Db_Table_Abstract::getDefaultAdapter();
    }
    public function indexAction()
    {

    }
    public function latitudelongitudeAction()
    {
        $this->view->setTitrePage("Maj table stations");
        $select = $this->_db->select()->from('carte_ts_arrets')->joinInner('stationsvilles', 'stationsvilles.nomville=carte_ts_arrets.commune And stationsvilles.nomstation=carte_ts_arrets.nom_arret', 'codestation');
        $this->view->stations = $this->_db->fetchAll($select);
        $stationsTable = new TStations();
        foreach ($this->view->stations as $arret) {
            $id = $arret['CodeStation'];
            $station = $stationsTable->find($id)->current();
            //Zend_Debug::dump($station); exit;
            $station->latitude = $arret['latitude'];
            $station->longitude = $arret['longitude'];
            $station->carte_ts_arrets_id = $arret['id'];
            $station->save();
        }
    }
    /**
     * initialisation des champs nomSA et prenomSA de la table user
     *
     */
    public function nomsaAction()
    {
        $tuser = new TUser();
        $select = $tuser->select()->where('nomSA is NULL');
        $records = $tuser->fetchAll($select);
        foreach ($records as $record) {
            $record->majSA();
        }
    }
    /**
     * Maj des CodeService
     */
    public function majcodeserviceAction
    ()
    {
        include_once 'models/dbtables/TServicesFromEtablissementStation.php';
        $tservices = new Pits_DbTable_TServiceFromEtablissementStation();
        
        set_time_limit(600);
        $televes = new TEleves();
        $records = $televes->fetchAll();
        foreach ($records as $record) {
            $record->setCodeServiceR1($tservices->findService($record->CodeEN, $record->CodeStationR1));
            $record->setCodeServiceR2($tservices->findService($record->CodeEN, $record->CodeStationR2));
        }
    }
}