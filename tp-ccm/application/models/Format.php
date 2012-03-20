<?php
/**
 * Application Télé_paiement
 * Gestion des télé-paiements par Paybox
 *
 * @date 31 juil. 2010 - rev. 19/09/2011
 *
 * @project    télé-paiement CCM
 * @package    application/models
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    1.0.0
 * @since      Ce fichier est disponible depuis la version 1.0.0
 */

/**
 * Gestion du formatage de données
 *
 * @project    télé-paiement CCM
 * @package    application/models
 * @author     pomirol
 */
class Tp_Model_Format
{
    const NOT_STRING = 'La valeur donnée n\'est pas une chaine de caractères.';
    /**
     * Met en majuscule le premier caractère de chaque morceau et le reste en minuscule.
     * Les morceaux sont séparés par un espace ou un -
     * Supprime les espaces inutiles
     *
     * @param string $data
     * @return string
     */
    public static function prenom($data)
    {
        if (is_string($data)) {
            $reponse = '';
            $morceaux = explode('-', $data);
            $sep = '';
            foreach ($morceaux as $morceau) {
                $elements = explode(' ', $morceau);
                foreach ($elements as $element) {
                    $reponse .= $sep . ucfirst(mb_strtolower($element));
                    $sep = ' ';
                }
                $sep = '-';
            }
            // supprime les espaces multiples puis ceux de début et fin
            $reponse = mb_ereg_replace('[ ]{1,}', ' ', $reponse);
            return trim($reponse);
        } else {
            throw new Tp_Model_Exception(self::NOT_STRING);
        }
    }
    /**
     * Met une donnée au format d'une adresse (chaque morceau est premierMaj et voir liste des mots traités)
     * @param string $data
     * @return string
     */
    public static function adresse($data)
    {
        if (is_string($data)) {
            $minuscules = array(' De ' => ' de ',
                            ' Des ' => ' des ',
                            ' Du ' => ' du ',
                            ' D\'' => ' d\'',
                            ' La ' => ' la ',
                            ' Le ' => ' le ',
                            ' Les '=> ' les ',
                            ' L\'' => ' l\'',
                            ' Lieu Dit ' => ' Lieu-dit ',
                            ' Bis '=> ' bis ', 
                            ' Al ' => ' Allée ',
                            ' Allee '=>' Allée ',
                            ' Av ' => ' Avenue ',
                            ' B P '=> ' BP ',
                            ' Bat '=> ' Bâtiment ',
                            ' Bât '=> ' Bâtiment ',
                            ' Bd ' => ' Boulevard ',
                            ' Ch ' => ' Chemin ',
                            ' Crs '=> ' Cours ',
                            ' Imp '=> ' Impasse ',
                            ' Pas '=> ' Passage ',
                            ' Pl ' => ' Place ',
                            ' Res '=> ' Résidence ',
                            ' Rés '=> ' Résidence ', 
                            ' Rt ' => ' Route ',
                            ' Rte '=> ' Route ',
                            ' St ' => ' Saint ',
                            ' Ste '=> ' Sainte ',
            );
            $reponse = '';
            // remplace les ponctuations et les guillements par des espaces et rajoute un espace au début
            $data = mb_ereg_replace('["`()\[\]\{\},?;.:!-]', ' ', ' ' . $data);
            // met un espace entre chiffres et lettres
            $data = mb_ereg_replace('^[0-9]{1,}', '\\0 ', mb_strtolower($data), 'p');
            // supprime les espaces multiples
            $data = mb_ereg_replace('[ ]{1,}', ' ', $data);
            // premier majuscule
            $morceaux = explode(' ', $data);
            $sep = '';
            foreach ($morceaux as $morceau) {
                $elements = explode('\'', $morceau);
                foreach ($elements as $element) {
                    $reponse .= $sep . ucfirst(mb_strtolower($element));
                    $sep = '\'';
                }
                $sep = ' ';
            }
            foreach ($minuscules as $trouve => $format) {
                $reponse = mb_ereg_replace($trouve, $format, $reponse);
            }
            // supprime l'espace au début (rajouté en entrée de traitement) et éventuellement à la fin
            return trim($reponse);
        } else {
            throw new Tp_Model_Exception(self::NOT_STRING);
        }
    }
    /**
     * Met une date au format désiré. 
     * 
     * Pour $format, on reconnait les abréviations suivantes :
     * - jour : d | dd | ddd | dddd 
     * - mois : M | MM | MMM | MMMM
     * - an   : YY | YYYY
     * - heure  : H | HH (respectivement sur 12 ou 24 heures)
     * - minute : mm
     * - seconde: ss
     * 
     * Pour $maDate, elle est donnée de la façon suivante :
     * - vide ou absente donnera la date du jour
     * - init donnera 1900-01-01 00:00:00 au format demandé
     * - au format FR (separateur /) si la locale est 'fr_FR' 
     * - au format US (separateur -) sinon
     * 
     * @param string $format (par défaut, date complète dd/MM/YYYY HH:mm:ss)
     * @param string $maDate='' 
     * @param string $locale
     * @return string
     */
    public static function date($format = 'dd/MM/YYYY HH:mm:ss', $maDate='', $locale='') {
        if (!empty($maDate)) {
            if (empty($locale)) {
                $pits_date = new Tp_Model_Date($maDate);
            } else {
                $pits_date = new Tp_Model_Date($maDate, $locale);
            }
        } else {
            $pits_date = new Tp_Model_Date();
        }
        return  $pits_date->toString($format);
    }
}