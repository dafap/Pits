<?php
/**
 * Application PiTS
 * Gestion des pr�inscriptions au service de transports scolaires
 *
 * @date 27 juin 2010
 *
 * @category   pits
 * @package    application
 * @subpackage views/helpers
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits r�serv�s
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

/**
 * @category   pits
 * @package    application
 * @subpackage views/helpers
 * @author     pomirol
 */
class Zend_View_Helper_FormatAdresse extends Zend_View_Helper_Abstract
{
    public function formatAdresse($adresse)
    {
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
        $adresse = mb_ereg_replace('["`()\[\]\{\},?;.:!-]', ' ', ' ' . $adresse);
        // met un espace entre chiffres et lettres
        $adresse = mb_ereg_replace('^[0-9]{1,}', '\\0 ', mb_strtolower($adresse), 'p');
        // supprime les espaces multiples
        $adresse = mb_ereg_replace('[ ]{1,}', ' ', $adresse);
        // premier majuscule
        $morceaux = explode(' ', $adresse);
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
    }
}