<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 16 mai 2010
 * 
 * @category   pits
 * @package    application
 * @subpackage models
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

include_once 'Zend/Db/Table/Abstract.php';

/**
 * @category   pits
 * @package    application
 * @subpackage models
 * @author     pomirol
 */
class TEleveslist extends Zend_Db_Table_Abstract
{
	/**
	 * Nom de la table
	 * 
	 * @var string
	 */
    protected $_name = "eleveslist";
	/**
	 * Nom de la clé primaire (fictive pour une vue)
	 * 
	 * @var string
	 */
    protected $_primary = "eleveId";

    /**
     * Nom de la classe des enregistrements
     *
     * @var string
     */
//    protected $_rowClass = 'Pits_Db_Table_Row';
    
    /**
     * Nom de la classe des jeux d'enregistrements
     *
     * @var string
     */
//    protected $_rowsetClass = 'Pits_Db_Table_Rowset';

}