<?php
/**
 * Filtre pour le nom
 *
 * Met en majuscule et supprime les espaces inutiles
 *
 * @project pits
 * @package package_name
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 23 mai 2011
 * @version $Id: $
 */

class Pits_Filter_FormatNom implements Zend_Filter_Interface
{
    public function filter($value)
    {
        $value = trim(mb_ereg_replace('[ ]{1,}', ' ', mb_strtoupper($value)));
        return $value;
    }
}