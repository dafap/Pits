<?php
/**
 * @category   library
 * @package    ap
 * @subpackage form/decorator
 * @author     Alain POMIROL
 * @date       21 août 2010
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
require_once 'Ap/Form/Decorator/Exception.php';
require_once 'Zend/Form/Decorator/Abstract.php';
/**
 * Ce décorateur
 * @author pomirol
 *
 */
class Ap_Form_Decorator_Element extends Zend_Form_Decorator_Abstract
{
    // classes par défaut
    const CLASS_TDTH = 'formtdth';
    const CLASS_TR   = 'formtr';
    const CLASS_DESCRIPTION = 'formdescription'; // s'applique à <p>
    const CLASS_ERROR = 'formerror'; // s'applique à <ul>
    // on garde la méthode standard pour placer les classes de balises input et label
    // les classes des éléments
    private $_classTr;
    private $_classTdTh;
    private $_classDescription;
    private $_classError;
    private $_classLabel;
    private $_classInput;
    /**
     * Donne une classe à la balise <tr>
     * @param string $name
     */
    public function setClassTr($name)
    {
        if (!is_string($name)) {
            throw new Ap_Form_Decorator_Exception('Une chaine est attendue comme classe de la balise <tr>.');
        }
        $this->_classTr = $name;
    }
    public function getClassTr()
    {
        return isset($this->_classTr) ? $this->_classTr : self::CLASS_TR;
    }
    /**
     * Donne une classe aux balises <th> et <td>
     * @param string $name
     */
    public function setClassTdTh($name)
    {
        if (!is_string($name)) {
            throw new Ap_Form_Decorator_Exception('Une chaine est attendue comme classe des balises <th> et <td>.');
        }
        $this->_classTdTh = $name;
    }
    public function getClassTdTh()
    {
        return isset($this->_classTdTh) ? $this->_classTdTh : self::CLASS_TDTH;
    }
    /**
     * Donne une classe à la balise <p> du paragraphe de description
     * @param string $name
     */
    public function setClassDescription($name)
    {
        if (!is_string($name)) {
            throw new Ap_Form_Decorator_Exception('Une chaine est attendue comme classe de la balise <p>.');
        }
        $this->_classDescription = $name;
    }
    public function getClassDescription()
    {
        return isset($this->_classDescription) ? $this->_classDescription : self::CLASS_DESCRIPTION;
    }
    /**
     * Donne une classe à la balise <ul> de la liste des messages d'erreur
     * @param string $name
     */
    public function setClassError($name)
    {
        if (!is_string($name)) {
            throw new Ap_Form_Decorator_Exception('Une chaine est attendue comme classe de la balise <ul>.');
        }
        $this->_classError = $name;
    }
    public function getClassError()
    {
        return isset($this->_classError) ? $this->_classError : self::CLASS_ERROR;
    }
    // Zend_Form_Decorator_Abstract implémente toutes les méthodes de l'interface sauf render().
    /**
     * Définition de la méthode render définissant ce decorator
     */
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

        $output = '<tr class=' . $this->getClassTr() . '>'
        . '<th class="' . $this->getClassTdTh() . '">' . $label . '</th>'
        . '<td class="' . $this->getClassTdTh() . '">'
        . $input
        . $desc
        . $errors
        . '</td>';

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
        $element = $this->getElement();
        $desc    = $element->getDescription();
        if (empty($desc)) {
            return '';
        }
        return '<p class="' . $this->getClassDescription() . '">' . $desc . '</p>';
    }
    public function buildErrors()
    {
        $element  = $this->getElement();
        $messages = $element->getMessages();
        if (empty($messages)) {
            return '';
        }
        return $element->getView()->formErrors($messages, array('class' => $this->getClassError()));
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
        $element = $this->getElement();
        $label = $element->getLabel();
        if ($translator = $element->getTranslator()) {
            $label = $translator->translate($label);
        }
        if ($element->isRequired()) {
            $label .= '*';
        }
        $label .= ':';
        return $element->getView()
                       ->formLabel($element->getName(), $label);
    }
}