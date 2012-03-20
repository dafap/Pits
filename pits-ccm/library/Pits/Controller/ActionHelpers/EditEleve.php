<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 10 juil. 2010
 *
 * @category   pits
 * @package    library
 * @subpackage pits/controller/actionhelpers
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.2.0
 * @since      Ce fichier est disponible depuis la version 0.2.0
 */
//include_once 'models/dbtables/TServicesFromEtablissementStation.php';

/**
 * Aide d'action factorisant le traitement du formulaire Pits_Form_InscriptionEleve
 *
 * @category   pits
 * @package    library
 * @subpackage pits/controller/actionhelpers
 * @author     pomirol
 */
class Pits_Controller_ActionHelpers_EditEleve extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Partie commune à addneweleveAction() et editeleveAction()
     * - $init est un tableau associatif qui définit les valeurs des clés suivantes :
     *   'hCodeStationR2', 'hCodeStationR2', 'hCodeTarif', 'hSecondeAdresse', 'forward' et 'cancel'
     * A noter que :
     * - forward est  de type string et contient l'URL (utilisation de l'action helper gotoUrl() )
     *   du type controleur/action/param1/valeur1/param2/valeur2...
     * - cancel est de type string, passé à la form par le constructeur dans $init (voir Pits_Form_Abstract)
     *   et correspond à l'URL appelée par onclick="window.location.href='URL'" (doit donc contenir baseUrl())  
     *
     * @param Zend_View $view
     * @param Zend_Db_Table_Row_Abstract $eleve
     * @param string  $formAction
     * @param array() $init
     */
    public function direct($view, $eleve, $formAction, $init)
    {
        // complément pour les champs hidden
        $init['hCodeStationR1']  = $eleve->CodeStationR1;
        $init['hCodeStationR2']  = $eleve->CodeStationR2;
        $init['hCodeTarif']      = $eleve->CodeTarif;
        $init['hSecondeAdresse'] = $eleve->SecondeAdresse;

        // formulaire d'inscription
        $form = new Pits_Form_InscriptionEleve($init);
        $form->setAction($formAction)
        ->setMethod('post')
        ->setDefaults($eleve->toArray())
        ->setDefault('pk', $eleve->eleveId); // 0 pour insert ; différent de 0 pour update
        
        // Enregistrement de l'élève
        if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
            // Retrait des informations depuis les données en POST
            $elevepost = $form->getValues();
            $forward = urldecode($elevepost['forward']);
            if ($elevepost['SecondeAdresse'] == 0) {
                $elevepost['TitreR2'] = null;
                $elevepost['CommuneR2'] = null;
                $elevepost['CodeStationR2'] = null;
                $elevepost['CodeServiceR2'] = null;
            } elseif ($elevepost['CodeStationR2'] == -1) {
                $elevepost['CodeStationR2'] = null;
                $elevepost['CodeServiceR2'] = null;
            }
            
            /******** Ajout du 7 mars 2012 pour gérer les reprises d'inscription ********/
            // pour un nouvel inscrit ou une reprise d'inscription => inscrit et encours            
            if (is_null($eleve->nonInscrit) || $eleve->nonInscrit) {
                $change = true;
                $elevepost['nonInscrit'] = 0; 
                $elevepost['encours'] = 1;
            } else {
                $change = false;
            }
            /****** fin de l'ajout ******************************************************/        
            /*
             * elevepostIntersect et $eleveintersect sont deux tableaux qui ont les mêmes clés (key)
             * elevepostIntersect prend ses valeurs dans elevepost
             * eleveIntersect prend ses valeurs dans eleve cad dans la table eleves de la bd
             */
            $elevepostIntersect = array_intersect_key($elevepost, $eleve->toArray());
            // Vérification d'un changement :
            $eleveIntersect = array_intersect_key($eleve->toArray(), $elevepost);
            // comparaison entre $elevepostIntersect et $eleveIntersect qui ont les mêmes keys
            foreach ($elevepostIntersect as $key => $value) {
                if ($eleveIntersect[$key] != $value) {
                    $change = true;
                }
            }
            // on enregistre si c'est nécessaire
            if ($change) {
                // traitement du format de dateN si nécessaire
                $dateN = $elevepostIntersect['DateN'];
                $elevepostIntersect['DateN'] = Pits_Format::date("YYYY-MM-dd", $dateN, 'fr_FR');
                // mise à jour de l'objet $eleve
                $eleve->setFromArray($elevepostIntersect);
                // le champs CodeStationR1 a-t-il changé ?
                if ($majServiceR1 = $eleve->isFieldModified('CodeStationR1')) { // affectation simultanée
                    $eleve->CodeServiceR1 = null; // on libère la place pour ré-affectation, nécessaire si changement de station sur le même circuit
                }
                // le champs CodeStationR2 a-t-il changé ?
                if ($majServiceR2 = $eleve->isFieldModified('CodeStationR2')) { // affectation simultanée
                    $eleve->CodeServiceR2 = null; // on libère la place pour ré-affectation, nécessaire si changement de station sur le même circuit
                }               
                // enregistrement des changements (voir Pits_DbTable_Row_TEleves)
                $eleve->save();
                $tservices = new Pits_DbTable_TServicesFromEtablissementStation();
                if ($majServiceR1) {
                    $eleve->setCodeServiceR1($tservices->findService($eleve->CodeEN, $eleve->CodeStationR1));
                }
                if ($majServiceR2) {
                    $eleve->setCodeServiceR2($tservices->findService($eleve->CodeEN, $eleve->CodeStationR2));
                }
            }
            // Redirection vers la parent/index
            $this->_redirect($forward);
        }
        
        // Assignation du formulaire dans la vue pour l'affichage
        //Zend_Debug::dump($form); exit;
        $view->form = $form;
    }
    /**
     * Pour utiliser l'aide d'action redirector
     *
     * @param string $url
     */
    private function _redirect($url)
    {
        Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->gotoUrl($url);
    }
}