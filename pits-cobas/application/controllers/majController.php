<?php
/**
 * Description courte du fichier
 *
 * Description longue du fichier s'il y en a une
 *
 * @project pits
 * @package package_name
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 17 juin 2011
 * @version $Id: $
 */

class majController extends Ap_Controller_Action
{
    /**
     * (non-PHPdoc)
     * @see Ap_Controller_Action::init()
     */
    public function init()
    {
        parent::init();
        if (!$this->_auth->hasIdentity() || $this->_auth->getIdentity()->categorie < 3) $this->_redirect('/parent/logout');
    }

    public function indexAction() {
        ;
    }
    public function servicesAction() {
        $televes = new Pits_Model_DbTable_TEleves();
        $tservices = new Pits_Model_DbTable_TServicesFromEtablissementStation();
        $records = $televes->fetchAll();
        foreach ($records as $record) {
            $record->setCodeService1($tservices->findService($record->CodeEN, $record->CodeStation1));
            $record->setCodeService2($tservices->findService($record->CodeEN, $record->CodeStation2));
        }
        $this->_redirect('/maj/index');
    }
}