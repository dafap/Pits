<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * Enregistrement (Row) de base de données
 *
 * @date 28 mai 2010
 *
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

include_once 'Zend/Db/Table.php';
include_once 'Zend/Db/Table/Row.php';
include_once 'Zend/Db/Table/Row/Exception.php';
include_once 'Zend/Cache/Core.php';

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_Db_Table_Row extends Zend_Db_Table_Row
{
    /**
     * Activation / Désactivation de l'autosauvegarde à la destruction
     *
     * @var boolean
     */
    private static $_autoSave = true;

    /**
     * Méthode de manipulation de l'autosauvegarde
     *
     * @param boolean $save
     * @return void
     */
    public static function setAutoSave($save)
    {
        self::$_autoSave = (bool) $save;
    }

    /**
     * Sauvegarde les données dans le cache
     *
     * @return boolean
     */
    public function saveToMemory()
    {
        $cache = Zend_Db_Table::getDefaultMetadataCache();
        if (!$cache instanceof Zend_Cache_Core) {
            throw new Zend_Db_Table_Row_Exception('Pas de cache configuré');
        }
        $cacheId = '';
        foreach ($this->_primary as $primary) {
            $cacheId .= '_' . $this->$primary;
        }
        return $cache->save($this, $this->_tableClass . $cacheId);
    }

    /**
     * Sauvegarde automatiquement l'objet dans le cache si la sauvegarde est activée
     */
    public function __destruct()
    {
        if (!self::$_autoSave || empty($this->_modifiedFields)) {
            return;
        }
        $this->saveToMemory();
    }

    /**
     * Reconnecte automatiquement l'objet à sa table
     *
     * Appelé à la désérialisation de l'objet.
     */
    public function __wakeup()
    {
        $this->setTable(new $this->_tableClass);
    }
}