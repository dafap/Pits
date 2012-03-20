<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 24 juil. 2010
 *
 * @category   pits
 * @package    library
 * @subpackage pits/Validate
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

/**
 * @category   pits
 * @package    library
 * @subpackage pits/Validate
 * @author     pomirol
 */
class Pits_Validate_ControleFileValidation extends Zend_Validate_Abstract
{
    const INVALID       = 'invalidContent';
    const NOT_FOUND     = 'fileNotFound';
    const DEFAULT_MODEL = 'UPDATE eleves SET CodeStationR1=216, CodeServiceR1=6, CodeStationR2=Null, CodeServiceR2=Null, ficheValidee=1, dateValidation="2011-06-27 16:29:44" WHERE eleveId=1;';
    
    /**
     * @var array Error message templates
     */
    protected $_messageTemplates = array(
    self::INVALID => "Les lignes du fichier transmis ne sont pas conformes au modèle attendu.",
    self::NOT_FOUND => "File '%value%' could not be found",
    );

    private $_ctrl;
    /**
     * Initialise le tableau de contrôle
     * Une ligne modèle doit être donnée. Les valeurs numériques n'ont pas d'importance. 
     * On retire la valeur null si nécessaire.
     * 
     * @param string $ligneModele
     */
    public function __construct($ligneModele = '')
    {
        if (empty($ligneModele)) {
            $ligneModele = self::DEFAULT_MODEL;
        }
        // utilisation de trim($ligneModele) pour compatibilité avec la méthode ctrlLigne()
        $ligneModele = trim($ligneModele);
        // on retire la valeur null pour ne pas perturber les stats des lettres 'n', 'u' et 'l'
        $ligneModele = str_ireplace('null', '', $ligneModele);
        // tableau des stratistiques pour les caractères
        $this->_ctrl = count_chars($ligneModele,0);
    }
    /**
     * Renvoie True si la ligne est du bon modèle, sinon False
     * @param string $ligne
     * @return boolean
     */
    protected function ctrlLigne($ligne)
    {
        $ok = true;
        // on utilise trim($ligne) en raison du retour de ligne éventuel du à fgets()
        $ligne = trim($ligne);
        // on retire la valeur null (éventuellement pour CodeStationR2)
        $ligne = str_ireplace('null', '', $ligne);
        // on contrôle les statistiques des caractères utilisés
        foreach (count_chars($ligne, 1) as $octet => $nb) {
            // on contrôle tout ce qui n'est pas numérique dans la ligne
            if ($octet < 48 || $octet > 57) {
                $ok &= ($nb == $this->_ctrl[$octet]);
            }
        }
        return $ok;
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Retourne true si et seulement si chaque ligne est du modèle proposé
     * (Le modèle est installé par le constructeur)
     *
     * @param  string $value (Nom du fichier temporaire)
     * @return boolean
     */
    public function isValid($value)
    {
        // Ouvre le fichier
        $f = @fopen($value, 'r');
        if ($f) {
            while (!feof($f)) {
                if (!$this->ctrlLigne(fgets($f))) {
                    fclose($f);
                    $this->_error(self::INVALID);
                    return false;
                }
            }
        } else {
            $this->_error(self::NOT_FOUND, $value);
            return false;
        }
        fclose($f);
        return true;
    }
}