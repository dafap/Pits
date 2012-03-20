<?php
/**
* Classe abstaite de ce répertoire
* 
* Définie un constructeur par défaut
* 
* @project pits
* @package library/ap
* @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
* @date 3 juin 2011
* @version $Id: $
*/

abstract class Ap_Controller_ActionHelpers_ExportEleves_Abstract
implements Ap_Controller_ActionHelpers_ExportEleves_Interface
{
    /**
     * C'est un eleveId ou un tableau d'eleveId (selon le besoin)
     * @var int | array
     */
    protected $_eleveIds;
    /**
     * Paramètres éventuellements passés à cette classe (à décrire dans la classe concernée)
     * @var mixted
     */
    protected  $_params;
    /**
     * MIME_TYPE
     * @var string
     */
    protected $_mimetype;
    /**
     * MIME_DISPOSITION
     * @var string
     */
    protected $_mimedisposition;
    /**
     * Initialise le ou les identifiants des élèves à traiter
     * 
     * @param int|array $param
     */
    public function __construct($elevesIds, $params) 
    {
        $this->_eleveIds = $elevesIds;
        $this->_params = $params;
        $this->setMimeDisposition();
        $this->init();
    }
    public function getMimeType()
    {
        return $this->_mimetype;
    }
    public function getMimeDisposition() 
    {
        return $this->_mimedisposition;
    }
    /**
     * Disposition prend les valeurs 'attachment' ou 'inline' (par défaut)
     * 
     * @param string $disposition
     */
    public function setMimeDisposition($disposition = 'inline') {
        $this->_mimedisposition = $disposition;
    }
}