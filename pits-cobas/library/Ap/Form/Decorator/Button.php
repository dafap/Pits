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
require_once 'Ap/Form/Decorator/Abstract.php';
/**
 * Ce décorateur
 * @author pomirol
 *
 */
class Ap_Form_Decorator_Button extends Zend_Form_Decorator_Abstract
{
    // Zend_Form_Decorator_Abstract implémente toutes les méthodes de l'interface sauf render().
    public function render($content)
    {
        
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
        
    }
    public function buildLabel()
    {
        return '';
    }
}