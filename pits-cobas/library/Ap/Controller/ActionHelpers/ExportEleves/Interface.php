<?php
/**
 * Interface des classes appelées par l'aide d'action AP_Controller_ActionHelpers_ExportEleves
 *
 * @project pits
 * @package library/ap
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 3 juin 2011
 * @version $Id: $
 */

interface AP_Controller_ActionHelpers_ExportEleves_Interface
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