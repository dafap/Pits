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

include_once 'Zend/Controller/Action/Helper/Abstract.php';
include_once 'Pits/Controller/ActionHelpers/ExportEleves/Abstract.php';
include_once 'Pits/Controller/Exception.php';

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_Controller_ActionHelpers_ExportEleves
extends Zend_Controller_Action_Helper_Abstract
{
    public function direct($format, $eleveId)
    {
        $exportObject = $this->_build($format, $eleveId);
        
        $this->getResponse()
        ->setBody($exportObject->getContent());

        $this->getResponse()
        ->setHeader('Content-type',$exportObject->getMimeType());
    }
    
    /**
     * Construit l'objet d'exportation demandé
     * 
     * @param chaine $format
     * @return Pits_Controller_ActionHelpers_ExportEleves_{$format}
     */
    private function _build($format, $eleveId)
    {
        // nom de la classe à instancier
        $classToBuild = 'Pits_Controller_ActionHelpers_ExportEleves_' . ucfirst($format);
        // existance de cette classe
        if (!class_exists($classToBuild)) {
            $msg = "Format d'exportation inconnu";
            throw new Pits_Controller_Exception($msg);
        }
        $classToBuild = new $classToBuild($eleveId);
        if (!$classToBuild instanceof Pits_Controller_ActionHelpers_ExportEleves_Abstract) {
            $msg = "Classe d'exportation incorrecte";
            throw new Pits_Controller_Exception($msg);
        }
        return $classToBuild;
    }
}