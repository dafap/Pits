<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 13 juin 2010
 *
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    1.0.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_Controller_ActionHelpers_ListeDocuments extends Zend_Controller_Action_Helper_Abstract
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
            $path = Zend_Registry::get('indexPath');
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