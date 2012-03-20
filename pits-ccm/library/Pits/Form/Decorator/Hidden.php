<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 4 août 2010
 *
 * @category   pits
 * @package    library
 * @subpackage pits/form/decorator
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    1.0.0
 * @since      Ce fichier est disponible depuis la version 1.0.0
 */

/**
 * Supprime le label et les balise html inutiles
 *
 * @category   pits
 * @package    library
 * @subpackage pits/form/decorator
 * @author     pomirol
 */
class Pits_Form_Decorator_Hidden extends Zend_Form_Decorator_Abstract
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

        $output = '<div>'
        . $label
        . $input
        . $errors
        . $desc
        . '</div>';

        switch ($placement) {
            case (self::PREPEND):
                return $output . $separator . $content;
            case (self::APPEND):
            default:
                return $content . $separator . $output;
        }

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
    public function buildErrors()
    {
        return '';
    }

    public function buildDescription()
    {
        return '';

    }
}