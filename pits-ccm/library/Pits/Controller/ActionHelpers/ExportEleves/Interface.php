<?php
/**
 * Application PiTS
 * Gestion des pr�inscriptions au service de transports scolaires
 *
 * @date 16 mai 2010
 * 
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits r�serv�s
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
interface Pits_Controller_ActionHelpers_ExportEleves_Interface
{
    /**
     * Retourne le tyme mime de l'export
     */
    public function getMimeType();
    /**
     * Retourne le contenu complet à exporter
     */
    public function getContent();
}