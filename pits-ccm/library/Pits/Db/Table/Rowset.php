<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * Jeu d'enregistrement (Rowset) de base de données
 * 
 * @date 29 mai 2010
 * 
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

include_once 'Zend/Db/Table/Rowset/Abstract.php';

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_Db_Table_Rowset extends Zend_Db_Table_Rowset_Abstract
{
    /**
     * Méthode magique mettant en cache tout appel non existant sur
     * le Rowset, vers les Rows qu'il contient
     */
    public function __call($meth, $args)
    {
        if (method_exists($this->_rowClass, $meth)) {
            foreach ($this as $row) {
                call_user_func_array(array($row, $meth), $args);
            }
        } else {
            trigger_error("Call to undefined method " . get_class($this) . "::$meth()", E_USER_ERROR);
        }
    }
}