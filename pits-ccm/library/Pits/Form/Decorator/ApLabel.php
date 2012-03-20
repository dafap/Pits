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
require_once 'Zend/Form/Decorator/Label.php';
/**
 * Ce décorateur
 * @author pomirol
 *
 */
class Pits_Form_Decorator_ApLabel extends Zend_Form_Decorator_Label
{
    // Zend_Form_Decorator_Label implémente toutes les méthodes de l'interface.
    // Surcharge de render() pour mettre une classe à la balise <th> ou <dl>
    public function render($content)
    {
        $element = $this->getElement();
        $view    = $element->getView();
        if (null === $view) {
            return $content;
        }

        $label     = $this->getLabel();
        $separator = $this->getSeparator();
        $placement = $this->getPlacement();
        $tag       = $this->getTag();
        $id        = $this->getId();
        $class     = $this->getClass();
        $options   = $this->getOptions();

        if (empty($label) && empty($tag)) {
            return $content;
        }

        if (!empty($label)) {
            $options['class'] = $class;
            $label = $view->formLabel($element->getFullyQualifiedName(), trim($label), $options);
        } else {
            $label = '&nbsp;';
        }

        if (null !== $tag) {
            require_once 'Zend/Form/Decorator/HtmlTag.php';
            $decorator = new Zend_Form_Decorator_HtmlTag();
            $decorator->setOptions(array('tag' => $tag,
                                         'id'  => $this->getElement()->getName() . '-label',));
            if (!is_null($this->getOption('classtag'))) {
                $decorator->setOption('class', $this->getOption('classtag'));
            }
            if (!is_null($this->getOption('rowspan'))) {
                $decorator->setOption('rowspan', $this->getOption('rowspan'));
            }
            $label = $decorator->render($label);
        }

        switch ($placement) {
            case self::APPEND:
                return $content . $separator . $label;
            case self::PREPEND:
                return $label . $separator . $content;
        }
    }
    protected function getReqSuffix()
    {
        return '*';
    }
}