<?php
/**
* Rassemble des méthodes statiques générales
*
* 
* @project pits
* @package package_name
* @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
* @date 25 mai 2011
* @version $Id: $
*/

class Ap_Methode
{
    /**
     * Transforme un objet en tableau associatif
     * 
     * @param object|array $obj
     * @return array
     */
    public static function objToArray($obj)
    {
        $array = is_object($obj) ? get_object_vars($obj) : $obj;
        foreach ($array as $key => $value) {
            $value = (is_array($value) || is_object($value)) ? self::objToArray($value) : $value;
            $array[$key] = $value;
        }
        return $array;
    }
    /**
     * Transforme le résultat d'une requête en tableau associatif
     * 
     * @param Zend_Db_Statement_Interface $query   
     * @return array
     */
    public static function queryToArray($query) 
    {
        $array = array();
        if ($query instanceof Zend_Db_Statement_Interface) {
            foreach ($query as $key => $value) {
                $array[$key] = is_object($value) ? self::queryToArray($value) : $value;
            }
        }
        return $array;
    }
}