<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 30 mai 2010
 *
 * @category   pits
 * @package    application
 * @subpackage models
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

/**
 * @category   pits
 * @package    application
 * @subpackage models
 * @author     pomirol
 */
class Pits_Document_PdfEleve extends Pits_Document_Abstract
{
    /**
     * Passerelle utilisée
     * @var Zend_Db_Table_Abstract
     */
    private $_tEleveslist;
    /**
     * Primary key
     * @var int
     */
    private $_eleveId;
    /**
     * Rowset
     * @var Zend_Db_Table_Rowset_Abstract
     */
    private $_elevesRowset;
    /**
     * Row
     * @var Zend_Db_Table_Row_Abstract
     */
    private $_eleveRow;
    /**
     * Constructeur qui initialise
     * - le fichier modèle de document (dans application/config)
     * - le eleveId
     * et qui met en place la passerelle pour la vue Mysql eleveslist
     *
     * @param string $filename
     * @param integer $eleveId
     */
    public function __construct($filename, $eleveId){
        parent::__construct($filename);
        $this->_eleveId = (int) $eleveId;
        $this->_tEleveslist = new Pits_Model_DbTable_TEleveslist();
    }
    public function run($p, $y, $margeG)
    {
        // Lecture de l'élève
        $this->_eleveRow = $this->_tEleveslist
        ->find($this->_eleveId)
        ->current();
        if (is_null($this->_eleveRow)) {
            throw new Pits_Document_Exception('Cet élève n\'existe pas.');
        }
        // construction des tableaux de champs et de valeurs pour le remplacement dans fgets()
        foreach ($this->_eleveRow->toArray() as $field => $value) {
            $this->_tableFields[] = "#§" . $field . "§#";
            $this->_tableValues[] = $value;
        }
        // Taille des caractères de l'écriture dans la page pdf
        $fontSize = $p->getFontSize();
        $centre = false;
        // Traitement du fichier modèle de document
        while (!$this->feof()) {
            $this->_buffer = Pits_Document_Abstract::mb_trim($this->fgets(), '\\\\s', false);
            if (!empty($this->_buffer)) {
                if ($this->_buffer == '§bloc§') {
                    $y -= (int) $fontSize * 0.4;
                } elseif ($this->_buffer == '§finbloc§') {
                    $y -= (int) $fontSize * 0.6;
                } elseif ($this->_buffer == '§hr§') {
                    $p->drawLine($margeG, $y, $p->getWidth() - 2 * $margeG, $y);
                    $y -= (int) $fontSize * 1.2;
                } elseif ($this->_buffer == '§centre§') {
                    $centre = true;
                } elseif ($this->_buffer == '§fincentre§') {
                    $centre = false;
                } else {
                    if ($centre) {
                        $x = (int) ($p->getWidth() - 7 / 12 * $fontSize * mb_strlen($this->_buffer, 'UTF-8')) / 2;
                    } else {
                        $x = $margeG;
                    }
                    $p->drawText($this->_buffer, $x, $y, 'UTF-8');

                    $y -= (int) $fontSize * 1.2;
                }
            }
        }
        return $y;
    }
    /**
     * Remplace le champ §regime§ par sa valeur litérale (0 = demi-pensionnaire ; 1 = interne)
     */
    protected function particularValues()
    {
        if (empty($this->_buffer)) return;

        if (preg_match('#§regime§#i', $this->_buffer, $matches) == 1) {
            $regime = $this->getFields(array('Regime'));
            $this->_buffer = preg_replace('#§regime§#i', $regime[0] ? 'interne' : 'externe/demi-pensionnaire', $this->_buffer);
        }
    }
}