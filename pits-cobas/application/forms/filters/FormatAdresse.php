<?php
/**
 * Filtre pour les lignes d'adresses
 *
 * Met une donnée au format d'une adresse (chaque morceau est premierMaj et voir liste des mots traités)
 *
 * @project pits
 * @package package_name
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 23 mai 2011
 * @version $Id: $
 */

class Pits_Filter_FormatAdresse implements Zend_Filter_Interface
{
    public function filter($value)
    {
        if (is_string($value)) {
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
            $value = mb_ereg_replace('["`()\[\]\{\},?;.:!-]', ' ', ' ' . $value);
            // met un espace entre chiffres et lettres
            $value = mb_ereg_replace('^[0-9]{1,}', '\\0 ', mb_strtolower($value), 'p');
            // supprime les espaces multiples
            $value = mb_ereg_replace('[ ]{1,}', ' ', $value);
            // premier majuscule
            $morceaux = explode(' ', $value);
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
            return $value;
        }
    }
}