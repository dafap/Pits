<?php
/**
 * Application PiTS
 * Gestion des pr�inscriptions au service de transports scolaires
 *
 * @date 16 mai 2010
 * 
 * @category   pits
 * @package    libray
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits r�serv�s
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

include_once 'Pits/Controller/ActionHelpers/ExportEleves/Interface.php';

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
abstract class Pits_Controller_ActionHelpers_ExportEleves_Abstract 
implements Pits_Controller_ActionHelpers_ExportEleves_Interface
{
    /**
     * C'est un eleveId (pour maileleve et pdfeleve) ou un tableau (pour csv)
     * @var unknown_type
     */
    protected $_eleveId;
    
    public function __construct($id)
    {
        $this->_eleveId =$id;
    }
}