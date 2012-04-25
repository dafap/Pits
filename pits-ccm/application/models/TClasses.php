<?php
/**
 * Application PiTS
 *
 * Passerelle vers le table classes
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

class TClasses extends Zend_Db_Table_Abstract
{
    /**
     * Nom de la table
     *
     * @var string
     */
    protected $_name = "classes";

    /**
     * Nom de la clé primaire
     *
     * @var string
     */
    protected $_primary = "CodeClasse";
    
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

    /**
     * Lecture de la table pour rendre un tableau associatif CodeInsee => Nom
     * qui va notamment servir à construire les listes déroulantes des formulaires.
     *
     * @return array
     */
    public function liste() {
        $result = array();
        $classes = $this->fetchAll(1,'Nom');
        foreach ($classes as $classe) {
            $result[$classe->CodeClasse] = $classe->Nom;
        }
        return $result;
    }
    /**
     * Construction d'un tableau serialisé pour JavaScript, à 3 dimensions, structuré de la façon suivante :
     *  - dimension 1: niveau d'établissement dans lequel on trouve cette classe (1: collège, 2: lycée et LP)
     *  - dimension 2: rang séquentiel pour cet établissement
     *  - dimension 3: tableau(CodeClasse, Nom)
     *  
     *  La variable $niveau prend ses valeurs de 1 à 6 (niveau des établissements de Maternelle à Autre)
     *  Le codage du niveau d'une classe est la somme des a.2^0 , b.2^1 , c.2^2 , d.2^3 , e.2^4 , f.2^5
     *  où a, b, c, d, e et f prennent les valeurs 0 ou 1
     *  L'exposant est donc le $niveau - 1. 
     *  Dans le tableau, le premier indice est le niveau de l'établissement
     *  Une même classe se retrouve donc inscrite sur plusieurs niveaux. Exemple : 2nde de niveau 24 sera inscrite
     *  dans $resultat[4][] et $resultat[5][] car 2^(4-1) + 2^(5-1) = 8 + 16 = 24 (4: lycée ; 5: LP)
     *  
     *  @return string
     */
    public function getClassesForJavaScript()
    {
        $classes = $this->fetchAll($this->select()
                                        ->from($this, array('CodeClasse', 'Nom', 'Niveau'))
                                        ->order(array('Niveau', 'Nom'))
                );
        $result = array();
        for ($niveau=1; $niveau<=6; $niveau++) $result[$niveau] = array();
        foreach ($classes as $classe) {
            for ($niveau=1; $niveau<=6; $niveau++) {
                // calcul de la puissance de 2 correspondante au niveau
                $p = 1;
                for ($j=1; $j<$niveau; $j++) $p *= 2;
                // filtre par un "ET" logique sur les bits de l'entier 'classe->Niveau'
                if (((int) $classe->Niveau & $p) != 0 ) {
                    $result[$niveau][] = array($classe->CodeClasse, $classe->Nom);
                }
            }
        }
        return htmlspecialchars(serialize($result), ENT_QUOTES);
    }
}