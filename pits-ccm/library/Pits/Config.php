<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 * 
 * Une classe qui permet de charger des fichiers
 * de configuration tout en gérant le cache
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

include_once 'Zend/Cache/Backend/Interface.php';
include_once 'Zend/Config/Exception.php';
include_once 'Zend/Cache/Frontend/File.php';

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
abstract class Pits_Config
{
/**
     * Support de cache
     * @var Zend_Cache_Backend_Interface
     */
    protected static $_backendCache;
    
    /**
     * Temps de vie du cache
     * @var int
     */
    protected static $_lifeTime = 86400;
    
    /**
     * Objet Zend_Config sous-jascent
     * @var Zend_Config
     */
    protected $_config;
    
    /**
     * Objet de cache de fichiers
     * @var Zend_Cache_Frontend_File
     */
    protected $_cache;
    
    /**
     * Affecte un support de cache
     * 
     * @param $cache support du cache
     */
    public static function setBackendCache(Zend_Cache_Backend_Interface $cache)
    {
        self::$_backendCache = $cache;        
    }
    
    /**
     * Affecte le temps de vie du cache
     * 
     * @param int $lifeTime
     */
    public static function setLifeTime($lifeTime)
    {
        self::$_lifeTime = abs((int)$lifeTime); 
    }
    
    /**
     * Constructeur, identique à celui de Zend_Config_Ini/Xml
     * 
     * @param string $filename fichier config à charger
     * @param string $section section de config à charger
     * @param array $options options de Zend_Config
     */
    public function __construct($filename, $section = null, $options = false)
    {
        $this->_setupCache($filename);
        $thisClass = substr(get_class($this), 0, strpos(get_class($this), '_'));
        $zendClass = str_replace($thisClass, 'Zend', get_class($this));
        if (($self = $this->_cache->load(spl_object_hash($this))) == false) {
            $this->_config = new $zendClass($filename, $section, $options);
            $this->_cache->save($this->_config, spl_object_hash($this));
        } else {
            $this->_config = $self;
        }
    }
    
    /**
     * proxy getter vers l'objet de config sous-jascent
     * Permet à l'objet d'être piloté comme un Zend_Config
     * 
     * @param string $prop
     * @return mixed
     */
    public function __get($prop)
    {
        return $this->_config->get($prop);
    }
    
    /**
     * proxy setter vers l'objet de config sous-jascent
     * Permet à l'objet d'être piloté comme un Zend_Config
     * 
     * @param string $prop
     * @param mixed $val
     */
    public function __set($prop, $val)
    {
        $this->_config->__set($prop, $val);
    }
    
    /**
     * Retourne l'instance Zend_Config sous-jascente
     *
     * @return Zend_Config
     */
    public function getConfigObject()
    {
        return $this->_config;
    }
    
    /**
     * proxy de méthode vers l'objet de config sous-jascent
     * Permet à l'objet d'être piloté comme un Zend_Config
     * 
     * @param string $meth
     * @param mixed $args
     * @return mixed
     */
    public function __call($meth, $args)
    {
        return call_user_func_array(array($this->_config, $meth), $args);
    }
    
    /**
     * Configure le cache
     * 
     * @param string $filename fichier de config à cacher
     */
    protected function _setupCache($filename)
    {
        if (is_null(self::$_backendCache)) {
            throw new Zend_Config_Exception("no backend cache provided to ".get_class($this));
        }
        $options = array('automatic_serialization' => true, 'lifetime' => self::$_lifeTime,
                         'master_file' => $filename);
        $this->_cache = new Zend_Cache_Frontend_File($options);
        $this->_cache->setBackend(self::$_backendCache);
    }
}