<?php
/**
 * Formulaire d'un élève
 *
 * Utilisé pour création ou modification
 *
 * @project pits
 * @package application/forms
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 25 mai 2011
 * @version $Id: $
 */

class Pits_Form_Eleve extends Zend_Form
{
    /**
     * Représente le tableau des stations serialisé pour JavaScript.
     * C'est un tableau structuré à partir des établissements desservis
     *
     * @var string
     */
    private $_stations;
    /**
     * Représente le tableau des tarifs serialisé pour JavaScript.
     * C'est un tableau structuré à partir des points d'arrêt et des établissements
     *
     * @var string
     */
    private $_tarifs;
    /**
     * Représente le tableau des établissements d'Arcachon serialisé pour JavaScript
     * C'est un simple tableau des codeEN
     *
     * @var string
     */
    private $_ecolesArcachon;
    /**
     * Représente le tableau des stations hors COBAS serialisé pour JavaScript
     * C'est un simple tableau des codeStation
     *
     * @var string
     */
    private $_stationsHorsCobas;

    /**
     * Mutateur pour le select CodeEn
     */
    public function setEtablissements()
    {
        $records = new Pits_Model_DbTable_TVillesecoles();
        $this->getElement('CodeEN')->addMultiOptions($records->liste());

        $records = new Pits_Model_DbTable_TEtablissements();
        $this->_ecolesArcachon = $records->getEcolesArcachonForJavaScript();

        return $this;
    }
    /**
     * Mutateur pour le tableau javascript des stations
     * (format JavaScript nécessite js/arrayPHP2JS.js)
     */
    public function setStations()
    {
        $records = new Pits_Model_DbTable_TStationsdispo();
        $this->_stations = $records->getStationsdispoForJavaScript();

        $records = new Pits_Model_DbTable_TStationsvilles();
        $this->_stationsHorsCobas = $records->getStationsHorsCobasForJavaScript();

        return $this;
    }
    /**
     * Mutateur pour le tableau javascript des tarifs
     * (format JavaScript nécessite js/arrayPHP2JS.js)
     */
    public function setTarifs()
    {
        $records = new Pits_Model_DbTable_TTarifs();
        $this->_tarifs = $records->getTarifsForJavaScript();
        return $this;
    }
    /**
     * Mutateur pour les select CommuneR1 et CommuneR2
     */
    public function setVilles()
    {
        $records = new Pits_Model_DbTable_TVilles();
        $liste = $records->cobas();
        $this->getElement('CommuneR1')->addMultiOptions($liste);
        $liste['00033'] = 'Autre commune de Gironde';
        $liste['00040'] = 'Autre commune des Landes';
        $liste['00000'] = 'Autre commune';
        $this->getElement('CommuneR2')->addMultiOptions($liste);
        return $this;
    }

    /**
     * Met en place l'action du bouton Retour (abandon)
     * Le paramètre $cancel est un tableau de la forme array('controller'=>..., 'action'=>...)
     *
     * @param array $cancel
     */
    public function setActionRetour($cancel)
    {
        $this->getElement('cancel')
        ->setOptions(array('onclick' => "pageRetour('" . $cancel['controller'] . "','" . $cancel['action'] . "');"));
        return $this;
    }

    /**
     * Accesseur au tableau javascript des stations
     * (format JavaScript nécessite js/arrayPHP2JS.js)
     *
     * @return string
     */
    public function getStations()
    {
        return $this->_stations;
    }
    /**
     * Accesseur au tableau javascript des tarifs
     * (format JavaScript nécessite js/arrayPHP2JS.js)
     *
     * @return string
     */
    public function getTarifs()
    {
        return $this->_tarifs;
    }
    /**
     * (non-PHPdoc)
     * @see Zend_Form::isValid()
     */
    public function isValid($data)
    {
        $this->getElement('CodeStation1')->setRegisterInArrayValidator(false);
        $this->getElement('CodeTarif')->setRegisterInArrayValidator(false);
        $this->getElement('CodeStation2')->setRegisterInArrayValidator(false);

        if ($this->getElement('SecondeAdresse')->getValue() == 0) {
            $this->getElement('CommuneR2')->removeValidator("ChoixSelect");
        }
        return parent::isValid($data);
    }

    /**
     * Chargement des fichiers JavaScript en fin de body
     * (voir application/views/layouts/layout.phtml)
     *
     * @param Zend_View_Interface $view
     * @return Pits_Form_Element_Xhtml $this
     */
    public function setView(Zend_View_Interface $view = null)
    {
        parent::setView($view);
        $view->headScript()->setFile('js/arrayPHP2JS.js')
        ->appendScript(
"var serializeStations = new PhpArray2Js('" . $this->_stations . "');
var tabStations = serializeStations.retour();
var serializeTarifs = new PhpArray2Js('" . $this->_tarifs . "');
var tabTarifs = serializeTarifs.retour();
var serializeEcolesArcachon = new PhpArray2Js('" . $this->_ecolesArcachon . "');
var tabEcolesArcachon = serializeEcolesArcachon.retour();
var serializeStationsHorsCobas = new PhpArray2Js('" . $this->_stationsHorsCobas . "');
var tabStationsHorsCobas = serializeStationsHorsCobas.retour();");      
        $view->inlineScript()
        //->appendScript("onchangeCommuneR1(tabStations,document.getElementById('CommuneR1').value);")
        //->appendScript("onchangeCommuneR2(tabStations,document.getElementById('CommuneR2').value);")
        //->appendScript("onchangeCodeEN(tabTypesTarifs,tabTarifs,document.getElementById('CodeEN').value);")
        //->appendScript("onchangeCodeTarif(tabTarifs,document.getElementById('hCodeTarif').value);")
        ->appendScript("montreBloc('gardeAlternee',document.getElementById('hSecondeAdresse').value);")
        ->appendScript("onchangeCodeEN();")
        ->appendScript("dumpTarifs();");
        return $this;
    }
}