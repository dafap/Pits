<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 18 mai 2010
 *
 * @category   pits
 * @package    livrary
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

require_once 'Zend/Validate/Abstract.php';

/**
 * Valide un mot de passe
 * vide ou composé de min à max caractères dont nbDigits chiffres minimum
 * éventuellement égal à mdp1
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_Validate_Mdp extends Zend_Validate_Abstract
{
    const TYPEINVALID = 'typeInvalid';
    const MININVALID  = 'minInvalid';
    const MAXINVALID  = 'maxInvalid';
    const NBDINVALID  = 'nbdInvalid';
    const MDPINVALID  = 'mdpInvalid';
    public   $min;
    public   $max;
    public   $nbDigits;
    public   $mdpIni;
    protected $_messageTemplates = array(
    self::TYPEINVALID => "Type invalide. Doit être une chaîne de caractères.",
    self::MININVALID => "Votre mot de passe doit comporter au moins '%min%' caractères.",
    self::MAXINVALID => "Votre mot de passe doit comporter au plus '%max%' caractères.",
    self::NBDINVALID => "Votre mot de passe doit comporter au moins '%nbd%' chiffres.",
    self::MDPINVALID => "Votre mot de passe doit être différent du mot de passe initial.",
    );
    protected $_messageVariables = array('max' => 'max', 'min' => 'min', 'nbd' => 'nbDigits');

    /**
     * Constructeur qui initialise les paramètres
     * @param array|int options (tableau des 4 paramètres 'min, 'max', 'nbdigits' et 'mdpini' où 
     *   - 'min' => nombre minimum de caractères (0 par défaut)
     *   - 'max' => nombre maximum de caractères (null par défaut donc illimité)
     *   - 'nbdigits' => nombre minimum de chiffres (1 par défaut)
     *   - 'mdpini' => vide ou mdpini à changer (vide par défaut)
     */
    public function __construct($options=array())
    {
        if (is_array($options)) {
            $this->min = array_key_exists('min', $options) ? $options['min'] : 0;
            $this->max = array_key_exists('max', $options) ? $options['max'] : null;
            $this->nbDigits = array_key_exists('nbdigits', $options) ? $options['nbdigits'] : 1;
            $this->mdpIni = array_key_exists('mdpini', $options) ? $options['mdpini'] : '';
        } else {
            throw new Ap_Exception('Paramètres incorrects dans ' . __CLASS__ . '. Doivent être dans un tableau.');
        }
    }
    /**
     * @param chaine $value
     * @return boolean
     */
    public function isValid($value)
    {
        // doit être une chaîne
        if (!is_string($value)) {
            $this->_error(self::TYPEINVALID);
            return false; // ce n'est pas une chaîne
        }
        // doit être comparée à un mdpIni
        if (!empty($this->mdpIni)) {
            if ($value == $this->mdpIni) {
                $this->_error(self::MDPINVALID);
                return false; // il n'a pas été changé
            }
        }
        // nombre de chiffres dans la chaîne
        if (preg_match_all("#\d#",$value,$result) < $this->nbDigits) {
            $this->_error(self::NBDINVALID);
            return false; // trop peu de chiffres
        }
        // longueur de la chaîne
        $length = mb_strlen($value);
        if ($length < $this->min) {
            $this->_error(self::MININVALID);
            return false; // chaîne trop courte
        }
        //
        if (null !== $this->max && $this->max < $length) {
            $this->_error(self::MAXINVALID);
            return false; // chaîne trop longue
        }
        return true;
    }
}