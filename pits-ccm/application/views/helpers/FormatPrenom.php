<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 27 juin 2010
 * 
 * @category   pits
 * @package    application
 * @subpackage views/helpers
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

/**
 * @category   pits
 * @package    application
 * @subpackage views/helpers
 * @author     pomirol
 */
class Zend_View_Helper_FormatPrenom extends Zend_View_Helper_Abstract
{
    public function formatPrenom($prenom) {
        $reponse = '';
        $morceaux = explode('-', $prenom);
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
    }
}