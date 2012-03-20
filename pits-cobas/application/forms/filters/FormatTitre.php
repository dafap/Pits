<?php
/**
* Filtre pour le titre
*
* Reçois M, Mme ou Mlle et rend M., Mme ou Mlle
* 
* @project pits
* @package application/forms/filters
* @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
* @date 23 mai 2011
* @version $Id: $
*/

class Pits_Filter_FormatTitre implements Zend_Filter_Interface
{
    public function filter($value) {
        if ($value == 'M') $value = 'M.';
        return $value;
    }
}