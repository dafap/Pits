<?php
/**
 * Générateur de factures et de justificatifs en pdf
 *
 * @date 30 septembre 2011
 * 
 * @package    libray
 * @subpackage Ap/Controller/ActionHelpers/UserDocument
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2011, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    1.0.0
 * @since      Ce fichier est disponible depuis la version 1.0.0
 */

/**
 * @package    library
 * @subpackage Ap/Controller/ActionHelpers/UserDocument
 * @author     pomirol
 */
interface Ap_Controller_ActionHelpers_UserDocument_Interface
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