<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 4 juil. 2010
 *
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_Convert_Csv implements Pits_Convert_Interface
{
    /*
     * Retourne l'instance du singleton (créé la première fois)
     */
    public static function getInstance()
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }
    public function convertFromArray($array)
    {
        $retVal = '';
        if (!is_array($array)) {
            throw new Pits_Convert_Csv_Exception('Un tableau est attendu !');
        }

        // Génération du flux temporaire (fichier si taille > 2MB)
        try {
            $fd = fopen('php://temp', 'r+');
            foreach ($array as $item) {
                fputcsv($fd, $item, ';', '"');
            }
            rewind($fd);
        } catch (Exception $e) {
            throw new Pits_Convert_Csv_Exception('Impossible de générer le flux temporaire');
        }

        // Récupération des données et expédition
        try {
        $csvContent = stream_get_contents($fd);
        fclose($fd);
        } catch (Exception $e) {
            throw new Pits_Convert_Csv_Exception('Impossible de lire le flux temporaire');
        }

        return $csvContent;
    }
    private function __clone()
    {
        // rien pour un singleton et déclaré private pour ne pas pouvoir l'utiliser
    }
    private function __construct()
    {
        // rien pour un singleton et déclaré private pour ne pas pouvoir l'utiliser
    }
}