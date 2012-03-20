<?php
/**
 * Tableau des fichiers d'un répertoire
 *
 * @date 13 juin 2010
 * @date  5 mars 2011 - modification du path par défaut
 *
 * @project    télé-paiement CCM
 * @package    library/Ap
 * @subpackage Controller/ActionHelpers
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    1.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 * @version $Id: $
 */

/**
 * @project    télé-paiement CCM
 * @author     pomirol
 */
class Ap_Controller_ActionHelpers_ListeDocuments extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Renvoie un tableau de noms des fichiers du répertoire
     * @param string $dirName
     * @param string $path
     * @return array of string
     */
    public function direct($dirName, $path=null) {
        $liste = array();
        if (is_null($path)) {
            $path = Zend_Registry::get('rootPath'); // modifié le 5 mars 2011
        }
        $iterator = new DirectoryIterator($path . DIRECTORY_SEPARATOR . $dirName);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile()) {
                $liste[] = $fileinfo->getFilename();
            }
        }
        return $liste;
    }
}