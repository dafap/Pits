<?php
/**
 * Description courte du fichier
 *
 * Description longue du fichier s'il y en a une
 *
 * @project project_name
 * @package package_name
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 30 sept. 2011
 * @version $Id: $
 */

class Ap_Controller_ActionHelpers_UserDocument extends Zend_Controller_Action_Helper_Abstract
{
    public function direct($format, $userId, $liste, $total)
    {
        $exportObject = $this->_build($format, $userId, $liste, $total);

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
    private function _build($format, $userId, $liste, $total)
    {
        // nom de la classe à instancier
        $classToBuild = 'Ap_Controller_ActionHelpers_UserDocument_' . ucfirst($format);
        // existance de cette classe
        if (!class_exists($classToBuild)) {
            $msg = "Format d'exportation inconnu";
            throw new Ap_Controller_Exception($msg);
        }
        $classToBuild = new $classToBuild($userId, $liste, $total);
        if (!$classToBuild instanceof Ap_Controller_ActionHelpers_UserDocument_Abstract) {
            $msg = "Classe d'exportation incorrecte";
            throw new Ap_Controller_Exception($msg);
        }
        return $classToBuild;
    }
}