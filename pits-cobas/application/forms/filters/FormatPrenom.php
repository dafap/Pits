<?php
/**
 * Filtre pour le prénom
 *
 * Met en majuscule le premier caractère de chaque morceau et le reste en minuscule.
 * Les morceaux sont séparés par un espace ou un -
 * Supprime les espaces inutiles
 *
 * @project pits
 * @package package_name
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 23 mai 2011
 * @version $Id: $
 */

class Pits_Filter_FormatPrenom implements Zend_Filter_Interface
{
    public function filter($value)
    {
        if (is_string($value)) {
            $reponse = '';
            $morceaux = explode('-', $value);
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
            $value = trim(mb_ereg_replace('[ ]{1,}', ' ', $reponse));
            //return trim($reponse);
        } //else {
            return $value;
        //}
    }
}