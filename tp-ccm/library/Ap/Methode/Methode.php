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

class Ap_Methode_Methode
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
    /**
     * Calcule un nouveau mot de passe de 7 caractères
     *
     * @return string
     */
    public static function getNewPassword() 
    {
        $lettres = 'azertyuiopqsdfghjklmwxcvbn&(-_)?!:%+=';
        $mdp = array();
        // nb de chiffres de 2 à 4
        $nbc = rand(2, 4);
        // places des chiffres
        for ($i = 0; $i < $nbc; ) {
            $r = rand(0, 6);
            if (isset($mdp[$r])) continue; // un chiffre est déjà en place
            $mdp[$r] = rand(0, 9);
            $i++;
        }
        for ($r = 0, $l = strlen($lettres)-1; $r < 7; $r++) {
            if (isset($mdp[$r])) continue; // il y a un chiffre
            $mdp[$r] = substr($lettres, rand(0, $l), 1);
        }
        // transforme le tableau en chaine
        return implode('', $mdp);
    }
    /**
     * Encodage d'une chaine pour les URL afin de faire disparaitre http:
     * 
     * @param string $param
     * @return string
     */
    public static function avoca($param) 
    {
        $encode = array();
        $t = ord('K') - ord('A');
        for ($j = 0; $j < 26; $j++) {
            $k = $j + ord('A');
            $v = $k + $t;
            if ($v > ord('Z')) $v = $v - ord('Z') + ord('A') - 1;
            $encode[chr($k)] = chr($v);
        }
        for ($j = 0; $j < 26; $j++) {
            $k = $j + ord('a');
            $v = $k + $t;
            if ($v > ord('z')) $v = $v - ord('z') + ord('a') - 1;
            $encode[chr($k)] = chr($v);
        }
        $result = array();
        foreach (str_split(urlencode($param)) as $car) {
            $result[] = array_key_exists($car, $encode) ? $encode[$car] : $car;
        }
        return implode('', $result);
    }
    /**
     * Méthode réciproque pour décoder la chaine encodée par la méthode avoca
     * 
     * @param string $param
     * @return string
     */
    public static function cavoa($param) 
    {
        $encode = array();
        $t = ord('K') - ord('A');
        for ($j = 0; $j < 26; $j++) {
            $k = $j + ord('A');
            $v = $k + $t;
            if ($v > ord('Z')) $v = $v - ord('Z') + ord('A') - 1;
            $encode[chr($v)] = chr($k);
        }
        for ($j = 0; $j < 26; $j++) {
            $k = $j + ord('a');
            $v = $k + $t;
            if ($v > ord('z')) $v = $v - ord('z') + ord('a') - 1;
            $encode[chr($v)] = chr($k);
        }
        $result = array();
        foreach (str_split($param) as $car) {
            $result[] = array_key_exists($car, $encode) ? $encode[$car] : $car;
        }
        return urldecode(implode('', $result));
    }
}