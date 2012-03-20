<?php 
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 16 mai 2010
 * 
 * @category   pits
 * @package    library
 * @subpackage ap
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

include_once 'Zend/Controller/Action/Helper/Abstract.php';
include_once 'Ap/Controller/ActionHelpers/ExportEleves/Abstract.php';
include_once 'Ap/Controller/Exception.php';

/**
 * @category   pits
 * @package    library
 * @subpackage ap
 * @author     pomirol
 */
class Ap_Controller_ActionHelpers_ExportEleves
extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Construit une classe Ap_Controller_ActionHelpers_ExportEleves_Interface
     * et appelle ses deux méthodes ::getContent() et getMimeType()
     * 
     * @param string $format
     * @param int|array $eleveIds
     * @param optionnel mixted $params
     */
    public function direct($format, $eleveIds, $params = null)
    {
        $exportObject = $this->build($format, $eleveIds, $params);
        
        $this->getResponse()
        ->setBody($exportObject->getContent());

        $this->getResponse()
        ->setHeader('Content-type',$exportObject->getMimeType())
        ->setHeader('Content-disposition',$exportObject->getMimeDisposition());
    }
    
    /**
     * Construit et renvoie l'objet d'exportation demandé
     * 
     * @param chaine $format
     * @param int|array $eleveIds
     * @param mixted $params
     * @return Ap_Controller_ActionHelpers_ExportEleves_{$format}
     * @throws Ap_Controller_Exception
     */
    public function build($format, $eleveIds, $params = null)
    {
        // nom de la classe à instancier
        $classToBuild = 'Ap_Controller_ActionHelpers_ExportEleves_' . ucfirst($format);
        // existance de cette classe
        if (!class_exists($classToBuild)) {
            $msg = "Format d'exportation inconnu";
            throw new Ap_Controller_Exception($msg);
        }
        $classToBuild = new $classToBuild($eleveIds, $params);
        if (!$classToBuild instanceof Ap_Controller_ActionHelpers_ExportEleves_Abstract) {
            $msg = "Classe d'exportation incorrecte";
            throw new Ap_Controller_Exception($msg);
        }
        return $classToBuild;
    }
}