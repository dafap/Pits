<?php
/**
 * Application Tp
 * Gestion des télé-paiements
 *
 * @date 19 sept 2011
 *
 * @project    télé-paiement CCM
 * @package    application/models
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010-2011, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    1.0.0
 * @since      Ce fichier est disponible depuis la version 0.2.0
 */

/**
 * @category   tp
 * @package    application/models
 * @author     pomirol
 */
class Tp_Model_Date
{
    protected  $an;
    protected  $mois;
    protected  $jour;
    protected  $heure;
    protected  $minute;
    protected  $seconde;
    private $_timestamp;
    private $_heure12; // heure sur 12
    private $_njour; // numéro du jour dans la semaine (dimanche = 0)
    private $_error; // booleen
    private $_errorMessage;
    private $_timeOnly;

    /**
     * Construit un objet Tp_Model_Date
     * - $locale ne peut prendre que la valeur vide (US) ou 'fr_FR'
     * - par défaut, $sepdate prend la valeur - (US) ou / (FR)
     * - par défaut, $septime prend la valeur :
     * - par défaut, $time prend la faleur false
     *
     * Si $maDate est vide, la date courante est prise en compte
     * Si $maDate contient 'init', la date du 01/01/1900 00:00:00 est traitée
     *
     * Un contrôle de validité est réalisé et à la construction de Tp_Model_Date mais devra être demandé
     * par les méthode isDate(), isTime() ou isError()
     *
     * Si $time == true, seule la validité isTime() est controlée
     *
     * @param string $maDate
     * @param string $locale
     * @param string $sepdate
     * @param string $septime
     * @param boolean $time
     */
    public function __construct($maDate='', $locale='', $sepdate='', $septime=':', $time=false)
    {
        if (!empty($locale) && $locale != 'fr_FR') {
            throw new Tp_Model_Exception('Cette locale n\'est pas gérée par Tp_Model_Date');
        } elseif (empty($septime)) {
            throw new Tp_Model_Exception('Le séparateur de temps ne peut pas être vide dans Tp_Model_Date');
        } elseif ($septime == ' ' || $sepdate == ' ') {
            throw new Tp_Model_Exception('Les séparateurs de date et temps ne peuvent pas être un espace dans Tp_Model_Date');
        }
        $this->_timeOnly = $time;
        if ($maDate == '') {
            $maDate = date("Y-m-d H:i:s");
            $this->_error = !$this->decoupeDateUS($maDate, '-', ':');
        } elseif ($maDate == 'init') {
            $maDate = "1900-01-01 00:00:00";
            $this->_error = !$this->decoupeDateUS($maDate, '-', ':');
        } else {
            if ($locale == 'fr_FR') {
                if (empty($sepdate)) $sepdate = '/'; // par défaut
                $this->_error = !$this->decoupeDateFR($maDate, $sepdate, $septime);
            } else {
                if (empty($sepdate)) $sepdate = '-'; // par défaut
                $this->_error = !$this->decoupeDateUS($maDate, $sepdate, $septime);
            }
        }

        if ($this->_timeOnly) {
            if ($this->isTime()) {
                $this->_error = false;
                $this->_errorMessage = '';
            } else {
                $this->_error = true;
                $this->_errorMessage = $maDate . ' n\'est pas une heure valide';
            }
        } else {
            if ($this->isDate() && $this->isTime()) {
                $this->_timestamp = mktime($this->heure,$this->minute,$this->seconde, $this->mois,$this->jour,$this->an);
                $this->_njour = (int) strftime("%w", $this->_timestamp);
                $this->_heure12 = strftime("%I", $this->_timestamp);
                $this->_error = false;
                $this->_errorMessage = '';
            } else {
                if (!$this->_error) {
                    $this->_error = true;
                    $this->_errorMessage = $maDate . ' n\'est pas une date valide';
                }
            }
        }
    }
    /**
     * Pour le constructeur
     * @param string $monTime
     * @param string $septime
     */
    private function decoupeTime($monTime, $septime) {
        if (empty($monTime)) {
            $this->heure = '00';
            $this->minute = '00';
            $this->seconde = '00';
        } else {
            $monTime = explode($septime, $monTime);
            if (empty($monTime) || !is_numeric($monTime[0]) || count($monTime) > 3) {
                $format = 'heures' . $septime . 'minutes' . $septime . 'secondes';
                $this->_errorMessage = "Format incorrect : format $format attendu !";
                return false;
            }
            $this->heure = substr('00' . $monTime[0],-2);
            if (isset($monTime[1])) {
                $this->minute = substr('00' . $monTime[1],-2);
            } else $monTime[1] = '00';
            if (isset($monTime[2])) {
                $this->seconde = substr('00' . $monTime[2],-2);
            } else $monTime[2] = '00';
        }
        return true;
    }
    /**
     * Pour le constructeur
     * @param string $maDate
     * @param string $sepdate
     * @param string $septime
     * @return boolean
     */
    private function decoupeDateUS($maDate, $sepdate, $septime)
    {
        $tDateTime = explode(' ', $maDate);
        // date
        $tDateTime[0] = explode($sepdate, $tDateTime[0]);
        if (count($tDateTime[0]) == 3) {
            $this->an = $tDateTime[0][0];
            $this->mois = substr('00' . $tDateTime[0][1],-2);
            $this->jour = substr('00' . $tDateTime[0][2],-2);
        } else {
            $format = 'an' . $sepdate . 'mois' . $sepdate . 'jour';
            $this->_errorMessage = "Format incorrect : format $format attendu !";
            return false;
        }
        // time
        return $this->decoupeTime(count($tDateTime) == 1 ? '' : $tDateTime[1], $septime);
    }
    /**
     * Pour le constructeur
     * @param string $maDate
     * @param string $sepdate
     * @param string $septime
     */
    private function decoupeDateFR($maDate, $sepdate, $septime)
    {
        if ($this->_timeOnly) {
            return $this->decoupeTime($maDate, $septime);
        } else {
            $tDateTime = explode(' ', $maDate);
            // date
            $tDateTime[0] = explode($sepdate, $tDateTime[0]);
            if (count($tDateTime[0]) == 3) {
                $this->jour = substr('00' . $tDateTime[0][0],-2);
                $this->mois = substr('00' . $tDateTime[0][1],-2);
                $this->an = $tDateTime[0][2];
            } else {
                $format = 'jour' . $sepdate . 'mois' . $sepdate . 'an';
                $this->_errorMessage = "Format incorrect : format $format attendu !";
                return false;
            }
            // time
            return $this->decoupeTime(count($tDateTime) == 1 ? '' : $tDateTime[1], $septime);
        }
    }
    /**
     * Année bissextile
     * @return boolean
     */
    private function isLeapYear()
    {
        return $this->an % 400 == 0 || ($this->an % 4 == 0 && $this->an % 100 != 0);
    }
    /**
     * Contrôle de la date
     * @return boolean
     */
    public function isDate()
    {
        if ($this->_error) return false;

        $nbj = array(1 => 31, 2 => 28, 3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 =>31);
        $ok = is_numeric($this->an . $this->mois . $this->jour)
        && (int) $this->mois > 0 && (int) $this->mois < 13 && (int) $this->jour > 0;
        if ($ok) {
            if ($this->isLeapYear()) $nbj[2] = 29;
        }
        return $ok && $this->jour <= $nbj[(int)$this->mois];
    }
    /**
     * Contrôle du temps
     * @return boolean
     *
     */
    public function isTime()
    {
        return !$this->_error
        && is_numeric($this->heure . $this->minute . $this->seconde)
        && (int) $this->heure <=24 && (int) $this->heure >= 0
        && (int) $this->minute <= 60 && (int) $this->minute >= 0
        && (int) $this->seconde <= 60 && (int) $this->seconde >= 0;
    }
    /**
     * Retourne l'erreur
     * @return boolean
     */
    public function isError()
    {
        return $this->_error;
    }
    /**
     * Retourne le message d'erreur
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }
    /**
     * Retourne la date formatée
     * @param string $format
     */
    public function toString($format)
    {
        if ($this->_error) throw new Tp_Model_Exception($this->_errorMessage);
        try {
            $j = array("dimanche", "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi");
            $j3  = array("dim", "lun", "mar", "mer", "jeu", "ven", "sam");
            $m = array("janvier", "février", "mars", "avril", "mai", "juin", "juillet", "août", "septembre", "octobre", "novembre", "décembre");
            $m3 = array("jan.", "fév.", "mars", "avr.", "mai", "juin", "juil.", "août", "sept.", "oct.", "nov.", "déc.");
            $result = preg_replace(
            array('#dddd#','#ddd#','#dd#','#d#','#MMMM#','#MMM#','#MM#','#M#','#YYYY#','#YY#','#HH#','#H#','#mm#','#ss#',),
            array('j4','j3',$this->jour,'j1', 'm4','m3',$this->mois,'m1', $this->an,'a2', $this->heure,'h12', $this->minute, $this->seconde,),
            $format);
            $nmois = ((int) $this->mois) - 1;
            $result = preg_replace(
            array('#j4#','#j3#','#j1#','#m4#','#m3#','#m1#','#a2#','#h12#',),
            array($j[$this->_njour], // jour nom complet
            $j3[$this->_njour], // jour nom en 3 lettres
            (int) $this->jour, // jour sans le zéro
            $m[$nmois], // mois nom complet
            $m3[$nmois], // mois nom abrégé
            (int) $this->mois, // mois sans le zéro
            mb_substr($this->an,-2), // 2 chiffres
            $this->_heure12,),
            $result);
            return $result;

        } catch (Exception $e) {
            throw new Tp_Model_Exception("Erreur de format d\'une date : $format !");
        }
    }
}