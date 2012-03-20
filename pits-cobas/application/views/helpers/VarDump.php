<?php
/**
 * Affiche un var_dump dans la zone #debug définie dans la css
 *
 * Mise en page d'un Zend_Debug::dump() avec arrêt des scripts si $stop est true
 *
 * @project pits
 * @package views/helpers
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 25 fevr. 2011
 * @version $Id:$
 */
class Zend_View_Helper_VarDump extends Zend_View_Helper_Abstract
{
    /**
     * Aide de vue - exit() si $stop = true
     * 
     * @param mixted $var
     * @param string $label
     * @param boolean $stop
     */
    public function VarDump($var, $label='', $stop=false)
    {
        $content = '<div id="debug1"><b>' . $label . ' &#9654;</b><div id="size10">';
        $content .= Zend_Debug::dump($var, '', false);
        $content .= '</div></div>';
        if ($stop) {
            echo $content;
            exit;
        }
        return $content;
    }
}
