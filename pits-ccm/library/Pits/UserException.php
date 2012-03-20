<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * Signale une erreur utilisateur
 * 
 * @date 23 mai 2010
 * 
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

include_once 'Pits/Exception.php';

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_UserException extends Pits_Exception
{
    /*
     *  TODO il sera bon d'historiser ces erreurs en enregistrant 
     *  - l'adresse IP, 
     *  - le login utilisateur,
     *  - le client utilisé.
     */
}