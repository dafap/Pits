<?php
/**
 * Application PiTS
 * Formulaire d'inscription d'un élève
 *
 * @category   pits
 * @package    library/pits
 * @subpackage form
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

include_once 'Zend/Auth.php';
include_once 'Zend/Form.php';
include_once 'Zend/Form/Element/Select.php';
include_once 'Zend/Form/Element/Text.php';
include_once 'Zend/Form/Element/Radio.php';
include_once 'Zend/Form/Element/Hidden.php';
include_once 'Zend/Form/Element/Submit.php';
include_once 'Zend/Form/Element/Button.php';
include_once 'Zend/Form/Element/Password.php';
include_once 'Zend/Form/Element/Hash.php';
include_once 'Zend/Registry.php';
include_once 'Zend/Translate.php';
include_once 'Zend/Validate/Abstract.php';
include_once 'Zend/Validate/Date.php';
include_once 'Zend/Validate/StringLength.php';
include_once 'Zend/Validate/EmailAddress.php';
include_once 'Zend/Validate/Db/NoRecordExists.php';
include_once 'Pits/Validate/PasswordMatch.php';
include_once 'Pits/Validate/ChoixSelect.php';
include_once 'models/TClasses.php';
include_once 'models/dbtables/TStationsdispo.php';
include_once 'models/TTarifs.php';
include_once 'models/TEtablissements.php';
include_once 'models/TVilles.php';

class Pits_Form_InscriptionEleve extends Pits_Form_Abstract
{
    private $_stations;

    private $_tarifs;

    private $_etablissements;

    public $message;

    /**
     * Initialisation du formulaire (méthode obligatoire)
     *
     * input type="text"     : Nom, Prénom, DateN
     * input type="select"   : Etablissement, Regime, Classe, CodeStation, CodeTarif
     * input type="radio"    : "Cet enfant a un autre responsable légal à une adresse différente"
     * input type="select"   : TitreR2
     * input type="text"     : NomR2, PrenomR2, AdressR2L1, AdressR2L2, CodePostalR2
     * input type="select"   : CommuneR2 (sur Villes)
     * input type="text"     : TelephoneR2, TelephoneR2c
     * input type="select"   : CodeStationR2
     *
     * @return Zend_Form
     */
    public function init()
    {
        // Récupération de l'élève si nécessaire
        //if (Zend_Registry::isRegistered('eleve')) {
        //    $eleve = Zend_Registry::get('eleve');
        //}
        
        // Mise en place du translator
        $this->putsTranslator();

        // Récupérer les données du responsable en session
        $session = Zend_Registry::get('session');

        // Pour récupérer la liste des Classes dans $classeSelect
        $classes = new TClasses();
        
        // Pour récupérer le tableau sérialisé des stations
        $stations = new Pits_DbTable_TStationsdispo();
        $this->_stations = $stations->getStationsForJavaScript();
        // Tableau de CodeStation permis pour le validateur InArray
        $ArrayCodeStationPermis = $stations->getArrayCodeStationPermis();

        // Pour récupérer le tableau sérialisé des tarifs
        $tarifs = new TTarifs();
        $this->_tarifs = $tarifs->getTarifsForJavaScript();
        
        // Pour récupérer les établissements et leurs TypeTarif
        $etabs = new TEtablissements();
        $this->_etablissements = $etabs->getTypeTarifForJavaScript();

        // Pour récupérer la liste des Villes dans $villeSelect
        // la liste des stations dans $stationSelect
        // et la liste des établissements dans $etabSelect
        $villes = new TVilles();
        
        // Décorateur pour remplacer les dl dt dd en table, tr, td
        $decorators = array(
		    'ViewHelper',
        array('Errors', array('tag' => 'p', 'class' => 'error')),
        array('Description', array('tag' => 'p', 'class' => 'description')),
        array('HtmlTag', array('tag' => 'td')),
        array('Label', array('tag' => 'th')),
        array(array('tr' => 'HtmlTag'), array('tag' => 'tr'))
        );
        $decotarif = array(
		    'ViewHelper',
        array('Errors', array('tag' => 'p', 'class' => 'error')),
        array('Description', array('tag' => 'p', 'class' => 'description')),
        array('HtmlTag', array('tag' => 'spam', 'id' => 'spamCodeTarif')),
        array(array('td' =>'HTmlTag'), array('tag' => 'td')),
        array('Label', array('tag' => 'th')),
        array(array('tr' => 'HtmlTag'), array('tag' => 'tr'))
        );
        $nomValidators = array(new Zend_Validate_StringLength(0, 30));

        
        /****************
         * Partie ELEVE
         */
        // Primary key en hidden pour le validateur : 0 si nouveau, initialisé si modif
        $this->putsHidden('pk',0);

        // Champ "Nom" est un Text filtré
        $nom = new Zend_Form_Element_Text('Nom');
        $nom->addValidators($nomValidators)
        ->setLabel('Nom')
        ->setRequired(true)
        ->addPrefixPath('Pits_Filter', 'Pits/Filter/', 'filter')
        ->addFilter('FormatNom')
        ->setDecorators($decorators);
        $this->addElement($nom);

        // Champ "Prenom" est un Text filtré
        $prenom = new Zend_Form_Element_Text('Prenom');
        $prenom->addValidators($nomValidators)
        ->setLabel('Prénom')
        ->setRequired(true)
        ->addPrefixPath('Pits_Filter', 'Pits/Filter/', 'filter')
        ->addFilter('FormatPrenom')
        ->setDecorators($decorators);
        $this->addElement($prenom);

        // Champ "DateN" est un Text filtré
        $dateN = new Zend_Form_Element_Text('DateN');
        $dateN->addValidators(array(new Pits_Validate_Date()))
        ->setLabel('Date de naissance')
        ->setRequired(true)
        ->setDecorators($decorators);
        $this->addElement($dateN);
        
        // Champ "Etablissement" est un Select dont les options ont été construites plus haut
        $etabSelect = new Zend_Form_Element_Select('CodeEN');
        $etabSelect->setMultiOptions(array('-1' => '--- Choisissez l\'établissement scolaire ---'))
        ->addMultiOptions($villes->listeEtabs())
        ->addValidators(array(new Pits_Validate_ChoixSelect(-1)))
        ->setLabel('Etablissement scolaire fréquenté')
        ->setRequired(true)
        ->setDecorators($decorators)
        ->setAttrib('onchange',"onchangeCodeEN(tabTypesTarifs, tabTarifs, this.value);");
        $this->addElement($etabSelect);
        
        /*
         * Champ "Regime" est un Select dont les options sont
         * 1 => Externe ou Demi-pensionnaire,
         * 2 => Interne
         */
        $regimeSelect = new Zend_Form_Element_Select('Regime');
        $regimeSelect->setMultiOptions(array('0' => 'Externe/DP', '-1' => 'Interne'))
        ->setLabel('Régime')
        ->setRequired(true)
        ->setDecorators($decorators);
        $this->addElement($regimeSelect);

        // Champ "Classe" est un Select dont les options ont été construites plus haut
        $classeSelect = new Zend_Form_Element_Select('Classe');
        $classeSelect->setMultiOptions(array('-1' => '--- Choisissez la classe ---'))
        ->addMultiOptions($classes->liste())
        ->addValidators(array(new Pits_Validate_ChoixSelect(-1)))
        ->setLabel('Classe')
        ->setRequired(true)
        ->setDecorators($decorators);
        $this->addElement($classeSelect);
        
        /***************************
         * Partie RESPONSABLE LEGAL
         */
        // Champ "TitreR1" est un Select dont les options sont M., Mme ou Mlle
        $titreR1Select = new Zend_Form_Element_Select('TitreR1');
        $titreR1Select->setMultiOptions(array('M.' => 'M.', 'Mme' => 'Mme', 'Mlle' => 'Mlle'))
        ->setLabel('Responsable légal')
        ->setRequired(true)
        ->setDecorators($decorators);
        $this->addElement($titreR1Select);

        // Champ "NomR1" est un Text(30) filtré
        $nomR1 = new Zend_Form_Element_Text('NomR1');
        $nomR1->addValidators($nomValidators)
        ->setLabel('Nom du responsable')
        ->setRequired(true)
        ->addPrefixPath('Pits_Filter', 'Pits/Filter/', 'filter')
        ->addFilter('FormatNom')
        ->setDecorators($decorators);
        $this->addElement($nomR1);

        // Champ "PrenomR1" est un Text(30) filtré
        $prenomR1 = new Zend_Form_Element_Text('PrenomR1');
        $prenomR1->addValidators($nomValidators)
        ->setLabel('Prénom du responsable')
        ->setRequired(true)
        ->addPrefixPath('Pits_Filter', 'Pits/Filter/', 'filter')
        ->addFilter('FormatPrenom')
        ->setDecorators($decorators);
        $this->addElement($prenomR1);

        // Champ "AdressR1L1" est un Text(30) filtré
        $adressR1L1 = new Zend_Form_Element_Text('AdressR1L1');
        $adressR1L1->addValidators($nomValidators)
        ->setLabel('Adresse')
        ->setRequired(false)
        ->addPrefixPath('Pits_Filter', 'Pits/Filter/', 'filter')
        ->addFilter('FormatAdresse')
        ->setDecorators($decorators);
        $this->addElement($adressR1L1);

        // Champ "AdressR1L2" est un Text(30) filtré
        $adressR1L2 = new Zend_Form_Element_Text('AdressR1L2');
        $adressR1L2->addValidators($nomValidators)
        ->setLabel('Complément d\'adresse')
        ->setRequired(false)
        ->addPrefixPath('Pits_Filter', 'Pits/Filter/', 'filter')
        ->addFilter('FormatAdresse')
        ->setDecorators($decorators);
        $this->addElement($adressR1L2);

        // Champ "CodePostalR1" est un Text(5) filtré
        $codePostalR1 = new Zend_Form_Element_Text('CodePostalR1');
        $codePostalValidators = array(new Zend_Validate_StringLength(0,5), new Zend_Validate_Digits());
        $codePostalR1->addValidators($codePostalValidators)
        ->setLabel('Code postal')
        ->setRequired(true)
        ->setDecorators($decorators);
        $this->addElement($codePostalR1);

        // Champ "CommuneR1" est un Select dont les options ont été construites plus haut
        $villeR1Select = new Zend_Form_Element_Select('CommuneR1');
        $villeR1Select->addValidators(array(new Pits_Validate_ChoixSelect(-1)))
        ->setMultiOptions(array('-1' => '-- Choisissez la commune ---'))
        ->addMultiOptions($villes->liste('CCM=1')) // Or HorsCCM=1'))
        ->setLabel('Commune')
        ->setRequired(true)
        ->setDecorators($decorators)
        ->setAttrib('onchange',"onchangeCommuneR1(tabStations, this.value);");
        $this->addElement($villeR1Select);

        // Champ "EmailR1" est un Text(80) filtré
        $emailR1 = new Zend_Form_Element_Text('EmailR1');
        $emailValidators = array(new Zend_Validate_EmailAddress());
        $emailR1->addValidators($emailValidators)
        ->setLabel('Email')
        ->setRequired(false)
        ->setDecorators($decorators);
        $this->addElement($emailR1);

        // Champ "TelephoneR1" est un Text(10) filtré
        $telephoneR1 = new Zend_Form_Element_Text('TelephoneR1');
        $telephoneValidators = array(new Zend_Validate_StringLength(0,10));
        $telephoneR1->addValidators($telephoneValidators)
        ->setLabel('Téléphone')
        ->setRequired(false)
        ->setDecorators($decorators);
        $this->addElement($telephoneR1);

        // Champ "TelephoneR1c" est un Text(10) filtré
        $telephoneR1c = new Zend_Form_Element_Text('TelephoneR1c');
        $telephoneR1c->addValidators($telephoneValidators)
        ->setLabel('Tél portable')
        ->setRequired(false)
        ->setDecorators($decorators);
        $this->addElement($telephoneR1c);

        /****************************
         * Partie TRANSPORT SCOLAIRE
         */
        // Champ "CodeStationR1" est un Select dont les options ont été construites plus haut
        $codeStationR1Select = new Zend_Form_Element_Select('CodeStationR1');
        $codeStationR1Select-> setMultiOptions(array('-1' => '--- Choisissez d\'abord la commune ---'))
        ->setLabel('Point d\'arrêt')
        ->setRequired(true)
        ->addValidator('InArray',true,array($ArrayCodeStationPermis))
        ->setDecorators($this->spamDecorators('spamCodeStationR1'));
        $this->addElement($codeStationR1Select);
        /*
         * Champ "CodeTarif" est un Select initialisé à partir de la commune et de l'établissement
         * (ce champ ne peut être mis en place que si on utilise Ajax)
         */
        $tarifSelect = new Zend_Form_Element_Select('CodeTarif');
        $tarifSelect->setMultiOptions(array('-1' => '--- Choisissez d\'abord l\'établissement ---'))
        ->setLabel('Tarif')
        ->setRequired(true)
        ->addValidator('InArray',true,array($tarifs->getArrayCodeTarifPermis()))
        ->setDecorators($this->spamDecorators('spamCodeTarif'));
        $this->addElement($tarifSelect);

        /******************************
         * Partie PRELEVEMENT BANCAIRE
         */
        $ribBanque = new Zend_Form_Element_Text('RibBanque');
        $ribBanque->setValidators($codePostalValidators)
        ->setLabel('RIB: Code banque')
        ->setRequired(false)
        ->setDecorators($decorators);
        $this->addElement($ribBanque);

        $ribAgence = new Zend_Form_Element_Text('RibAgence');
        $ribAgence->setValidators($codePostalValidators)
        ->setLabel('RIB: Code agence')
        ->setRequired(false)
        ->setDecorators($decorators);
        $this->addElement($ribAgence);

        $ribCompte = new Zend_Form_Element_Text('RibCompte');
        $ribCompte->setValidators(array(new Zend_Validate_StringLength(0,11),))
        ->setLabel('RIB: Numéro de compte')
        ->setRequired(false)
        ->setDecorators($decorators);
        $this->addElement($ribCompte);

        $ribCle = new Zend_Form_Element_Text('RibCle');
        $ribCle->setValidators(array(new Zend_Validate_StringLength(0,2), new Zend_Validate_Digits()))
        ->setLabel('RIB: Clé RIB')
        ->setRequired(false)
        ->setDecorators($decorators);
        $this->addElement($ribCle);
         
        $ribDom = new Zend_Form_Element_Text('RibDom');
        $ribDom->setValidators(array(new Zend_Validate_StringLength(0,24)))
        ->setLabel('RIB: Domiciliation')
        ->setRequired(false)
        ->setDecorators($decorators);
        $this->addElement($ribDom);

        $ribTit = new Zend_Form_Element_Text('RibTit');
        $ribTit->setValidators(array(new Zend_Validate_StringLength(0,24)))
        ->setLabel('RIB: Titulaire du compte')
        ->setRequired(false)
        ->setDecorators($decorators);
        $this->addElement($ribTit);

        /**
         * Un groupe de données bancaires
         */
        $this->addDisplayGroup(
        array('RibBanque', 'RibAgence', 'RibCompte', 'RibCle', 'RibDom', 'RibTit'),
        'rib',
        array(
        'decorators' => array('FormElements', array(array('tbody' => 'HtmlTag',), array('tag' => 'tbody', 'id' => 'rib', 'style'=>'display: none')),)
        )
        );

        /*************************
         * Partie GARDE ALTERNEE
         */
        /*
         * Bouton radio "Cet enfant a un autre responsable légal à une adresse différente"
         * qui active le bas du formulaire.
         * Par défaut elle est décochée.
         */
        $r2Radio = new Zend_Form_Element_Radio('SecondeAdresse');
        $r2Radio->setDecorators($decorators)
        ->setLabel('Cas de garde alternée : cet élève est-il amené à se rendre à une adresse différente desservie par la Communauté de Commune de Montesquieu ?')
        ->addMultiOptions(array('1' => 'Oui', '0' => 'Non',))
        ->setSeparator('')
        ->setAttrib('onclick',"montreBloc('gardeAlternee',this.value)");
        $this->addElement($r2Radio);

        // Champ "TitreR2" est un Select dont les options sont M., Mme ou Mlle
        $titreR2Select = new Zend_Form_Element_Select('TitreR2');
        $titreR2Select->setMultiOptions(array('M.' => 'M.', 'Mme' => 'Mme', 'Mlle' => 'Mlle'))
        ->setLabel('Titre')
        ->setRequired(false)
        ->setDecorators($decorators);
        $this->addElement($titreR2Select);

        // Champ "NomR2" est un Text(30) filtré
        $nomR2 = new Zend_Form_Element_Text('NomR2');
        $nomR2->addValidators($nomValidators)
        ->setLabel('Nom du second responsable')
        ->setRequired(false)
        ->addPrefixPath('Pits_Filter', 'Pits/Filter/', 'filter')
        ->addFilter('FormatNom')
        ->setDecorators($decorators);
        $this->addElement($nomR2);

        // Champ "PrenomR2" est un Text(30) filtré
        $prenomR2 = new Zend_Form_Element_Text('PrenomR2');
        $prenomR2->addValidators($nomValidators)
        ->setLabel('Prénom du second responsable')
        ->setRequired(false)
        ->addPrefixPath('Pits_Filter', 'Pits/Filter/', 'filter')
        ->addFilter('FormatPrenom')
        ->setDecorators($decorators);
        $this->addElement($prenomR2);

        // Champ "AdressR2L1" est un Text(30) filtré
        $adressR2L1 = new Zend_Form_Element_Text('AdressR2L1');
        $adressR2L1->addValidators($nomValidators)
        ->setLabel('Adresse du second responsable')
        ->setRequired(false)
        ->addPrefixPath('Pits_Filter', 'Pits/Filter/', 'filter')
        ->addFilter('FormatAdresse')
        ->setDecorators($decorators);
        $this->addElement($adressR2L1);

        // Champ "AdressR2L2" est un Text(30) filtré
        $adressR2L2 = new Zend_Form_Element_Text('AdressR2L2');
        $adressR2L2->addValidators($nomValidators)
        ->setLabel('Complément d\'adresse')
        ->setRequired(false)
        ->addPrefixPath('Pits_Filter', 'Pits/Filter/', 'filter')      
        ->addFilter('FormatAdresse')
        ->setDecorators($decorators);
        $this->addElement($adressR2L2);

        // Champ "CodePostalR2" est un Text(5) filtré
        $codePostalR2 = new Zend_Form_Element_Text('CodePostalR2');
        $codePostalValidators = array(new Zend_Validate_StringLength(0,5));
        $codePostalR2->addValidators($codePostalValidators)
        ->setLabel('Code postal')
        ->setRequired(false)
        ->setDecorators($decorators);
        $this->addElement($codePostalR2);

        // Champ "CommuneR2" est un Select dont les options ont été construites plus haut
        $villeR2Select = new Zend_Form_Element_Select('CommuneR2');
        $villeR2Select->setMultiOptions(array('-1' => '--- Choisissez une commune ---'))
        ->addMultiOptions($villes->liste('CCM=1 Or HorsCCM=1'))
        ->setLabel('Commune')
        ->setRequired(false)
        ->setDecorators($decorators)
        ->setAttrib('onchange',"onchangeCommuneR2(tabStations, this.value);");
        $this->addElement($villeR2Select);

        // Champ "EmailR2" est un Text(80) filtré
        $emailR2 = new Zend_Form_Element_Text('EmailR2');
        $emailValidators = array(new Zend_Validate_EmailAddress());
        $emailR2->addValidators($emailValidators)
        ->setLabel('Email')
        ->setRequired(false)
        ->setDecorators($decorators);
        $this->addElement($emailR2);

        // Champ "TelephoneR2" est un Text(10) filtré
        $telephoneR2 = new Zend_Form_Element_Text('TelephoneR2');
        $telephoneValidators = array(new Zend_Validate_StringLength(0,10));
        $telephoneR2->addValidators($telephoneValidators)
        ->setLabel('Téléphone')
        ->setRequired(false)
        ->setDecorators($decorators);
        $this->addElement($telephoneR2);

        // Champ "TelephoneR2c" est un Text(10) filtré
        $telephoneR2c = new Zend_Form_Element_Text('TelephoneR2c');
        $telephoneR2c->addValidators($telephoneValidators)
        ->setLabel('Tél portable')
        ->setRequired(false)
        ->setDecorators($decorators);
        $this->addElement($telephoneR2c);

        /*
         * Champ "CodeStationR2" est un Select dont les options doivent être calculées en
         * fonction de CommuneR2 (javascript).
         */
        $ArrayCodeStationPermis[] = '-1'; // option permise car saisie non obligatoire
        $codeStationR2Select = new Zend_Form_Element_Select('CodeStationR2');
        $codeStationR2Select->setMultiOptions(array('-1' => '--- Choisissez d\'abord la commune ---'))
        ->setLabel('Point d\'arrêt')
        ->setRequired(false)
        ->addValidator('InArray',true,array($ArrayCodeStationPermis))
        ->setDecorators($this->spamDecorators('spamCodeStationR2'));
        $this->addElement($codeStationR2Select);

        /**
         * Un groupe de garde alternée
         */
        $this->addDisplayGroup(
        array('TitreR2', 'NomR2', 'PrenomR2', 'AdressR2L1', 'AdressR2L2', 'CodePostalR2', 'CommuneR2', 'EmailR2', 'TelephoneR2', 'TelephoneR2c', 'CodeStationR2'),
        'gardeAlternee',
        array(
        'decorators' => array('FormElements', array(array('tbody' => 'HtmlTag',), array('tag' => 'tbody', 'id' => 'gardeAlternee', 'style'=>'display: none')),)
        )
        );

        // Bouton "Annuler"
        $decoratorsButton = array(
		    'ViewHelper',
        array('HtmlTag', array('tag' => 'a', 'href'=> $this->getCancel())),
        array(array('td' => 'HtmlTag'), array('tag' => 'td'))
        );
        $this->putsCancelButton('Abandonner', $decoratorsButton, 80);

        // Bouton "Créer le compte" est un Submit
        $decoratorsButton = array(
		    'ViewHelper',
        array('HtmlTag', array('tag' => 'td')),
        );
        $this->putsSubmitButton('Enregistrer', $decoratorsButton);

        // Groupe des boutons
        $this->addDisplayGroup(
        array('cancel','submit'),'buttons',array(
        'decorators' => array('FormElements',array(array('tr' => 'HtmlTag'), array('tag' => 'tr'))),
        ));

        // Jeton anti-CSRF
        $this->putsHash('token')
        // Hidden pour les valeurs par défaut de CodeStationR1, CodeStationR2, CodeTarif et SecondeAdresse
        ->putsHidden('hCodeStationR1', $this->getInitValue('hCodeStationR1')) //$eleve->CodeStationR1)
        ->putsHidden('hCodeStationR2', $this->getInitValue('hCodeStationR2')) //$eleve->CodeStationR2)
        ->putsHidden('hCodeTarif', $this->getInitValue('hCodeTarif')) //$eleve->CodeTarif)
        ->putsHidden('hSecondeAdresse', $this->getInitValue('hSecondeAdresse')) //$eleve->SecondeAdresse)
        ->putsForward();

        // place la balise <table>
        $this->setDecorators(
        array('FormElements',
        array('HtmlTag', array('tag' => 'table')),
		      'Form'));
    }
    /**
     * Place un couple de balises <div id="$id"> </div> dans le bloc <td> </td>
     * Remarque : les balises <spam> fonctionnent sous ff mais pas sous ie.
     *
     * @param chaîne $id (idElementHTML)
     */
    private function spamDecorators($id)
    {
        return array(
		    'ViewHelper',
        array('Errors', array('tag' => 'p', 'class' => 'error')),
        array('Description', array('tag' => 'p', 'class' => 'description')),
        array('HtmlTag', array('tag' => 'div', 'id' => $id)),
        array(array('td' =>'HTmlTag'), array('tag' => 'td')),
        array('Label', array('tag' => 'th')),
        array(array('tr' => 'HtmlTag'), array('tag' => 'tr'))
        );
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
        ->appendFile('js/formElv.js')
        ->appendScript(
"var stations = new PhpArray2Js('" . $this->_stations . "');
var tabStations = stations.retour();
var typestarifs = new PhpArray2Js('" . $this->_etablissements . "');
var tabTypesTarifs = typestarifs.retour();
var tarifs = new PhpArray2Js('" . $this->_tarifs . "');
var tabTarifs = tarifs.retour();");
        $view->inlineScript()
        ->appendScript("onchangeCommuneR1(tabStations,document.getElementById('CommuneR1').value);")
        ->appendScript("onchangeCommuneR2(tabStations,document.getElementById('CommuneR2').value);")
        ->appendScript("onchangeCodeEN(tabTypesTarifs,tabTarifs,document.getElementById('CodeEN').value);")
        ->appendScript("onchangeCodeTarif(tabTarifs,document.getElementById('hCodeTarif').value);")
        ->appendScript("montreBloc('gardeAlternee',document.getElementById('hSecondeAdresse').value);");
        return $this;
    }

    /**
     * Valide le formulaire
     *
     * @param  array $data
     * @return boolean
     */
    public function isValid($data)
    {
        $valid = parent::isValid($data);
        if ($valid) {
            // ici, il faut que Nom, Prenom et DateN soient valides
            $this->getElement('Nom')->addValidator(new Pits_Validate_ElvDejaInscrit($data['pk'], $data['Prenom'], $data['DateN']));
            $valid = $this->getElement('Nom')->isValid($data['Nom']);
            if ($valid && $data['SecondeAdresse'] == '1') {
                $valid = $data['CommuneR2'] != '-1';
            }
        }
        return $valid;
    }
}