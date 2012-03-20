<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 16 mai 2010
 * 
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

include_once 'Pits/Controller/ActionHelpers/ExportEleves/Abstract.php';
include_once 'Pits/Convert/Csv.php';

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_Controller_ActionHelpers_ExportEleves_Csv
extends Pits_Controller_ActionHelpers_ExportEleves_Abstract
{
    const MIME_TYPE = 'text/csv';
    public function getMimeType()
    {
        return self::MIME_TYPE;
    }
    public function  getContent()
    {
        return Pits_Convert_Csv::getInstance()->convertFromArray($this->_eleveId);
    }
}