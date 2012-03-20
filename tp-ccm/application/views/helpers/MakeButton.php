<?php
/**
 * Affiche un bouton en créant le lien
 * 
 * Gestion de `hover`, y compris pour IE
 *
 * @date 11 juil. 2010 - rev 20/09/2011
 * 
 * @project   télé-paiement CCM
 * @package    application
 * @subpackage views/helpers
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

/**
 * @category   télé-paiement CCM
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
     * @param string|array('a' => 'classe pour a', 'button' => 'classe pour button') $classe
     * @param string $style
     * @return string
     */
    public function makeButton($name, $id, $lien, $label, $classe='', $style='', $hover = 'hover')
    {
        if (is_array($classe)) {
            $classeA = $classe['a'];
            $classeB = $classe['button'];
        } else {
            $classeA = '';
            $classeB = $classe;
        }
        
        $reponse = '<a href="'. $lien. '" class="' . $classeA . '">' . PHP_EOL;
        $reponse .= '<button name="' . $name .'" id="' . $id . '"';
        if (!empty($classe)) {
            $reponse .= ' class="' . $classeB . '"';
        }
        if (!empty($style)) {
            $reponse .= ' style="' . $style .'"';
        }
        $reponse .= ' onclick="window.location.href=\'' . $lien . '\'"';
        $reponse .= ' onmouseover="jsHover(this,\''. $hover . '\');" ';
        $reponse .= ' onmouseout="jsHover(this,\'' . $classeB . '\');"';
        $reponse .= '>' . $label . '</button></a>' . PHP_EOL;
        return $reponse;
    }
}