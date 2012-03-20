<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 11 juil. 2010
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
class Zend_View_Helper_MakeButton extends Zend_View_Helper_Abstract
{
    /**
     * Fabrique un bouton pour accéder à une url
     * 
     * @param string $name
     * @param string $id
     * @param string $lien
     * @param string $label
     * @param string $classe
     * @param string $style
     * @return string
     */
    public function makeButton($name, $id, $lien, $label, $classe='', $style='')
    {
        $reponse = '<a href="'. $lien. '">' . PHP_EOL;
        $reponse .= '<button name="' . $name .'" id="' . $id . '"';
        if (!empty($classe)) {
            $reponse .= ' class="' . $classe . '"';
        }
        if (!empty($style)) {
            $reponse .= ' style="' . $style .'"';
        }
        $reponse .= ' onclick=\'window.location.href="' . $lien . '"\'>' . $label . '</button></a>' . PHP_EOL;
        return $reponse;
    }
}