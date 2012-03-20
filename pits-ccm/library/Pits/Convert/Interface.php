<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 4 juil. 2010
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
 * Interface permettant de rédiger des convertisseurs
 * 
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
interface Pits_Convert_Interface
{
    /**
     * C'est un singleton
     * @return Pits_Convert_Interface
     */
    public static function getInstance();
    /**
     * Conversion depuis un tableau
     * @param array $array
     * @return string
     */
    public function convertFromArray($array);
}