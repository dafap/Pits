<?php
/**
 * Classe étendant Zend_Db_Table_Row_Abstract pour les passerelles
 *
 *
 * @project télé-paiement CCM
 * @package application/models/dbtables/Row
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 24 mai 2011 - rev. 19/09/2011
 * @version $Id: $
 */

abstract class Tp_Model_DbTable_Row_Abstract extends Zend_Db_Table_Row_Abstract
{
    /**
     * Retourne un objet de type stdClass avec pour propriétés
     * - toutes les colonnes si les paramètres d'appel sont nuls
     * - la colonne ou les citées dans la chaine ou le tableau $returnColumns si non null
     * - toutes les colonnes sauf la colonne ou les citées dans la chaine ou le tableau $omitColumns si non null
     *
     * @param string|array $returnColumns
     * @param string|array $omitColumns
     * @return stdClass|boolean
     */
    public function toObject($returnColumns = null, $omitColumns = null)
    {
        $returnObj = new stdClass();

        foreach ($this->_table->getFields() as $field) {
            if (!is_null($returnColumns)) {
                if (!in_array($field, (array) $returnColumns)) continue;
            } elseif (!is_null($omitColumns)) {
                if (in_array($field, (array) $omitColumns)) continue;
            } 
            $returnObj->{$field} = $this->_data[$field];
        }
        return $returnObj;
    }
    /**
     * Affectation et maj de la table des champs modifiés
     * 
     * @param string $field
     * @param mixted $value
     */
    protected function _setData($field, $value)
    {
        // affectation
        $this->_data[$field] = $value;
        // modified ? la valeur n'existait pas à l'origine ou elle a été modifiée
        $this->_modifiedFields[$field] =  (!array_key_exists($field, $this->_cleanData))
        || ($this->_data[$field] != $this->_cleanData[$field]);
    }
    /**
     * Indique si le Row a été modifié. Retourne un booléen.
     * 
     * @return boolean
     */
    public function isModified() {
        return !empty($this->_modifiedFields);
    }
    /**
     * Indique si le champ a été modifié. Retourne un booléen.
     * 
     * @param string $fieldName
     * @return boolean
     */
        public function isFieldModified($fieldName) 
    {
        return array_key_exists($fieldName, $this->_modifiedFields);
    }
}