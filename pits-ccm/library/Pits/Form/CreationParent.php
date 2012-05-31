<?php
/**
 * Application PiTS
 * Formulaire de création d'un compte parent
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
include_once 'Zend/Form/Element/Submit.php';
include_once 'Zend/Form/Element/Button.php';
include_once 'Zend/Form/Element/Password.php';
include_once 'Zend/Form/Element/Hash.php';
include_once 'Zend/Registry.php';
include_once 'Zend/Translate.php';
include_once 'Zend/Validate/Abstract.php';
include_once 'Zend/Validate/StringLength.php';
include_once 'Zend/Validate/EmailAddress.php';
include_once 'Zend/Validate/Db/NoRecordExists.php';
include_once 'Pits/Validate/PasswordMatch.php';

class Pits_Form_CreationParent extends Pits_Form_Abstract
{
    /**
     * Initialisation du formulaire (méthode obligatoire)
     *
     * input type="select"   : Titre
     * input type="text"     : Nom, Prénom, Adresse ligne1, Adresse ligne 2
     * input type="select"   : Villes
     * input type="text"     : Code postal, Téléphone, Tel portable, Email
     * input type="password" : Mot de passe
     *
     * @return Zend_Form
     */
    public function init()
    {
        // Pour récupérer la liste des Villes dans $villeSelect
        $villes = new TVilles();

        // Mise en place du translator
        $this->putsTranslator();

        // Décorateur pour remplacer les dl dt dd en table, tr, td
        $decorators = array(
		    'ViewHelper',
        array('Errors', array('tag' => 'p', 'class' => 'error')),
        array('Description', array('tag' => 'p', 'class' => 'description')),
        array('HtmlTag', array('tag' => 'td')),
        array('Label', array('tag' => 'th')),
        array(array('tr' => 'HtmlTag'), array('tag' => 'tr'))
        );

        // Champ "Titre" est un Select dont les Options sont M., Mme ou Mlle (valeur et libellé)
        $titreSelect = new Zend_Form_Element_Select('Titre');
        $titreSelect->setMultiOptions(array('M.' => 'M.', 'Mme' => 'Mme', 'Mlle' => 'Mlle'))
        ->setLabel('Titre')
        ->setDecorators($decorators)
        ->setRequired(true);
        $this->addElement($titreSelect);

        // Champ "Nom" est un Text filtré
        $nom = new Zend_Form_Element_Text('Nom');
        $nomValidators = array(new Zend_Validate_StringLength(0, 30));
        $nom->addValidators($nomValidators)
        ->setLabel('Nom')
        ->setDecorators($decorators)
        ->setRequired(true);
        $this->addElement($nom);

        // Champ "Prénom" est un Text filtré
        $prenom = new Zend_Form_Element_Text('Prenom');
        $prenom->addValidators($nomValidators)
        ->setLabel('Prénom')
        ->setDecorators($decorators)
        ->setRequired(true);
        $this->addElement($prenom);

        // Champ "Adresse ligne 1" est un Text filtré
        $adresseL1 = new Zend_Form_Element_Text('AdresseL1');
        $adresseL1->addValidators($nomValidators)
        ->setLabel('Adresse de facturation')
        ->setDecorators($decorators)
        ->setRequired(true);
        $this->addElement($adresseL1);

        // Champ "Adresse ligne 2" est un Text filtré
        $adresseL2 = new Zend_Form_Element_Text('AdresseL2');
        $adresseL2->addValidators($nomValidators)
        ->setLabel('Complément d\'adresse')
        ->setDecorators($decorators)
        ->setRequired(false);
        $this->addElement($adresseL2);

        // Champ "Code postal" est un Text filtré
        $codePostal = new Zend_Form_Element_Text('CodePostal');
        $codePostalValidators = array(new Zend_Validate_StringLength(5,5));
        $codePostal->addValidators($codePostalValidators)
        ->setLabel('Code postal')
        ->setDecorators($decorators)
        ->setRequired(true);
        $this->addElement($codePostal);

        // Champ "Ville" est un Select dont les Options sont dans $villesOptions
        $villeSelect = new Zend_Form_Element_Select('Commune');
        $villeSelect->setMultiOptions(array('0' => '-- Choisissez la commune ---'))
        ->addMultiOptions($liste = $villes->liste('CCM=1'))  // Or HorsCCM=1')) // villes desservies
        ->addValidators(array(new Pits_Validate_ChoixSelect(0, array_keys($liste))))
        ->setLabel('Commune')
        ->setDecorators($decorators)
        ->setRequired(true);
        $this->addElement($villeSelect);

        // Champ "Téléphone" est un Text filtré
        $telephone = new Zend_Form_Element_Text('Telephone');
        $telephoneValidators = array(new Zend_Validate_StringLength(0,10));
        $telephone->addValidators($telephoneValidators)
        ->setLabel('Téléphone')
        ->setDecorators($decorators)
        ->setRequired(false);
        $this->addElement($telephone);

        // Champ "Téléphone portable" est un Text filtré
        $telephoneC = new Zend_Form_Element_Text('TelephoneC');
        $telephoneC->addValidators($telephoneValidators)
        ->setLabel('Tél portable')
        ->setDecorators($decorators)
        ->setRequired(false);
        $this->addElement($telephoneC);

        // Champ "Email" est un Text filtré
        $email = new Zend_Form_Element_Text('Email');
        $emailValidators = array(new Zend_Validate_EmailAddress(),
        new Zend_Validate_Db_NoRecordExists('user','Email'));
        $email->addValidators($emailValidators)
        ->setLabel('Email')
        ->setDecorators($decorators)
        ->setRequired(true);
        $this->addElement($email);

        // Champ "Mot de passe" est un Password
        $required = !$this->_isModification();
        $mdpValidators = array(new Pits_Validate_Mdp(7, 16, 2),);
        $mdp = new Zend_Form_Element_Password('mdp');
        $mdp->addValidators($mdpValidators)
        ->setLabel('Donner un mot de passe')
        ->setDescription('(au moins 7 caractères dont 2 chiffres)')
        ->setDecorators($decorators)
        ->setRequired($required);
        $this->addElement($mdp);

        // Champ "Mot de passe (bis)" est un Password qui doit être le même que le précédent
        $mdpbis = new Zend_Form_Element_Password('mdpbis');
        $mdpbis->setLabel('Confirmez le mot de passe')
        ->setDecorators($decorators)
        ->setRequired($required);
        $this->addElement($mdpbis);

        // Jeton anti-CSRF
        $this->putsHash('token');

        // Hidden(s) éventuel si nécessaire
        $hiddens = $this->getInitValue('hidden');
        if (!is_null($hiddens)) {
            if (is_array($hiddens)) {
                foreach ($hiddens as $nom => $valeur) {
                    $this->putsHidden($nom, $valeur);
                }
            } elseif (is_string($hiddens)) {
                $this->putsHidden($hiddens);
            }
        }

        // Bouton "Annuler"
        $decoratorsButton = array(
		    'ViewHelper',
        array('HtmlTag', array('tag' => 'a', 'href'=> $this->getCancel())),
        array(array('td' => 'HtmlTag'), array('tag' => 'td'))
        );
        $this->putsCancelButton('Abandonner', $decoratorsButton, 80);

        // Bouton "Enregistrer le compte" est un Submit
        $decoratorsButton = array(
		    'ViewHelper',
        array('HtmlTag', array('tag' => 'td')),
        );
        $this->putsSubmitButton('Enregistrer le compte', $decoratorsButton);

        // Groupe des boutons
        $this->addDisplayGroup(
        array('cancel','submit'),'buttons',array(
        'decorators' => array('FormElements',array(array('tr' => 'HtmlTag'), array('tag' => 'tr'))),
        ));

        // place la balise <table>
        $this->setDecorators(
        array(
		    	'FormElements',
        array('HtmlTag', array('tag' => 'table')),
		    	'Form'
		    	)
		    	);
    }
    /**
     * Valide le formulaire en regardant
     *
     * @param array $data
     * @return boolean
     */
    public function isValid($data)
    {
        // le mot de passe est confirmé
        $this->getElement('mdp')->addValidator(new Pits_Validate_PasswordMatch($data['mdpbis']));
        // en modification, le email est inchangé
        if ($this->getElement('Email')->getValue() == $data['Email']) {
            $this->getElement('Email')->removeValidator("Zend_Validate_Db_NoRecordExists");
        }
        return parent::isValid($data);
    }
    /**
     * Soit 'modification' est défini dans le tableau initArray et on retourne sa valeur booleenne
     * Soit on retourne la valeur de hasIdentity()
     */
    private function _isModification()
    {
        $modif = $this->getInitValue('modification');
        if (!is_null($modif)) {
            // 'modification' est défini dans _initArray
            return 0 != (int) $modif;
        } else {
            // 'modification' n'est pas défini
            return Zend_Auth::getInstance()->hasIdentity();
        }
    }
    /**
     * Renvoie les données formatées avec ou sans le mdp selon le besoin
     * 
     * @param  bool $suppressArrayNotation
     * @return array
     */
    public function getValues($suppressArrayNotation = false) {
        $values = parent::getValues($suppressArrayNotation);
        // codage du mdp si nécessaire
        if (empty($values['mdp'])) {
            unset($values['mdp']);
        }
        // format des données
        foreach ($values as $colonne => $valeur) {
            $c6 = mb_substr($colonne,0,6);
            $c3 = mb_substr($colonne,0,3);
            if ( $c6 == 'Prenom') {
                // le `Prenom`,
                if (!empty($valeur)) {
                    $values[$colonne] = Pits_Format::prenom($valeur);
                }
            } elseif ($c6 == 'Adress') {
                // les `AdresseL1`, `AdresseL2`
                if (!empty($valeur)) {
                    $values[$colonne] = Pits_Format::adresse($valeur);
                }
            } elseif ($c3 == 'Nom') {
                // le `Nom`
                if (!empty($valeur)) {
                    $values[$colonne] = mb_strtoupper(trim($valeur));
                }
            }
        }
        return $values;
    }
}