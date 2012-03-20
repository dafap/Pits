<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 13 juin 2010
 * 
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_Controller_ActionHelpers_UrlBase extends Zend_Controller_Action_Helper_Abstract
{
    public function direct($pathName)
    {
        return 'http://' . $_SERVER['HTTP_HOST'] . 
               Zend_Controller_Front::getInstance()->getBaseUrl() .
               "/$pathName/";
    }
}