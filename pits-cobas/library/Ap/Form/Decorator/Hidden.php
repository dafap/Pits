<?php
/**
 * @category   library
 * @package    ap
 * @subpackage form/decorator
 * @author     Alain POMIROL
 * @date       4 août 2010
 * @version    1.0.0
 * @since      Ce fichier est disponible depuis la version 1.0.0
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits reserves
 *
 * This file is part of SARAPIS project.
 * SARAPIS is free software; you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation;
 * either version 3 of the License, or (at your option) any later version.
 * SARAPIS is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
 * PURPOSE. See the GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License along with SARAPIS;
 * if not, , see <http://www.gnu.org/licenses/>
 */
require_once 'Zend/Form/Decorator/Abstract.php';
/**
 * Ce décorateur supprime label, errors, description et toutes les balises (dl, dd, dh ou table, tr td)
 * @author pomirol
 *
 */
class Ap_Form_Decorator_Hidden extends Zend_Form_Decorator_Abstract
{
    // Zend_Form_Decorator_Abstract implémente toutes les méthodes de l'interface sauf render().
    public function render($content)
    {
        $element = $this->getElement();
        if (!$element instanceof Zend_Form_Element) {
            return $content;
        }
        if (null === $element->getView()) {
            return $content;
        }

        $separator = $this->getSeparator();
        $placement = $this->getPlacement();
        $label     = $this->buildLabel();
        $input     = $this->buildInput();
        $errors    = $this->buildErrors();
        $desc      = $this->buildDescription();

        $output = '<div id="hidden-'.$element->getName() . '">'
        . $label
        . $input
        . $desc
        . $errors
        . '</div>';

        switch ($placement) {
            case (self::PREPEND):
                return $output . $separator . $content;
            case (self::APPEND):
            default:
                return $content . $separator . $output;
        }
    }
    public function buildDescription()
    {
        return '';
    }
    public function buildErrors()
    {
        return '';
    }
    public function buildInput()
    {
        $element = $this->getElement();
        $helper  = $element->helper;
        return $element->getView()->$helper(
        $element->getName(),
        $element->getValue(),
        $element->getAttribs(),
        $element->options
        );
    }
    public function buildLabel()
    {
        return '';
    }
}