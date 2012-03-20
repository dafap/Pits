<?php
/**
 * Application PiTS
 * Classe des contrôleurs de l'application implémentant un menu
 *
 * @category   pits
 * @package    library/pits
 * @subpackage controller
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

/**
 * @see Zend_Controller_Action
 */
require_once 'Zend/Controller/Action.php';

/**
 * Cette classe implémente un menu qui sera préciser dans la méthode ini() de la classe dérivée
 * par $this->setMenu($string $menu) le menu étant précisé dans application/config/menu.ini
 *
 * Les menus sont lus d'un fichier application/config/menu dans le FrontController de
 * l'application et sont placés dans le registre Zend_Registry
 *
 * @author pomirol
 *
 */
class Pits_Controller_Action extends Zend_Controller_Action
{
    /**
     * Renvoie l'URL de base de l'application
     * @return string
     */
    protected function getBaseUrl()
    {
        return $this->getFrontController()->getBaseUrl();
    }
    protected function getUrlDocs($folder) 
    {
        $url = $this->getBaseUrl();
        $index_php = preg_match('#^(.+)index.php$#', $url, $matches);
        if ($index_php) {
            $url = rtrim($matches[1], '/');
        } 
        return $url . '/' . $folder . '/' ;
    }
    /**
     * Pour _redirect(), supprime la base de l'url obtenue par view->link()
     * 
     * @param string $url
     * @return string
     */
    protected function getRelativeUrl($url) {
        $n = 1;
        $url = str_replace($this->getBaseUrl() . '/', '', $url, $n);
        return $url;
    }
    /**
     * Initialise le menu pour la vue de ce controleur
     *
     * Appel depuis {@link __ini()}
     *
     * @param string $user
     *
     * @return void
     */
    protected function setMenu($nomMenu = null)
    {
        // pour les personnes autentifiées, ajout d'un retour à leur niveau si nécessaire
        if ( Zend_Auth::getInstance()->hasIdentity()) {
            $macategorie = Zend_Auth::getInstance()->getIdentity()->categorie;
            // interprétation du niveau hiérarchique (visiteur < parent < admin < sadmin)
            $niveaux = Zend_Registry::get('menus')->niveaux;
            foreach ($niveaux as $key => $value) {
                $$key = $value;
                if ($value == $macategorie) {
                    $urlAdmin = $this->view->link($key, 'index');
                    $monNiveau = $key;
                }
            }
            if ( $$nomMenu > 0 && $$nomMenu < $macategorie) {
                $menu[$urlAdmin] = "retour $monNiveau";
            }
        }
        // lecture du menu correspondant à $user dans le registre
        $inimenu = Zend_Registry::get('menus')->{$nomMenu};
         
        // transformation en liens pour Zend Framework
        if (!empty($inimenu)) {
            foreach ($inimenu as $action => $label) {
                if ($action == 'url-retour') {
                    $url_retour = $label;
                } elseif ($action == 'urlRetour') {
                    $menu[$url_retour] = $label;
                } else {
                    $action = $this->view->link($this->getRequest()->getParam('controller'),$action);
                    $menu[$action] = $label;
                }
            }
        } else {
            $menu = array();
        }
        // passage du menu à la vue
        $this->view->menu = $menu;
    }
    /**
     * Retourne true si le site est ouvert
     *
     * @return boolean
     */
    protected function isSiteOuvert()
    {
        $config = $this->getFrontController()->getParam('config');
        return $config->site->ouvert == 1;
    }
    /**
     * Retourne l'état des inscriptions pour le site
     *  0 : inscriptions annoncées pour le site à la date du
     *  1 : inscriptions ouvertes
     *  2 : inscriptions closes pour cette année scolaire
     *
     *  @return integer
     */
    protected function getEtatInscriptions()
    {
        $config = $this->getFrontController()->getParam('config');
        return  $config->site->inscriptions->etat;
    }
    /**
     * Retourne la date d'ouverture des inscriptions
     *
     * @return string
     */
    protected function getDateOuvertureInscriptions()
    {
        $config = $this->getFrontController()->getParam('config');
        return $config->site->inscriptions->date;
    }
    /**
     * Retourne l'année scolaire de la configuration du site
     *
     * @return string
     */
    protected function getAnneeScolaire()
    {
        $config = $this->getFrontController()->getParam('config');
        return $config->annee->scolaire;
    }
    /**
     * Retourne l'adresse d'entrée de l'application télé-paiement (tp)
     * par le passage get du couple de paramètres
     *
     * @return string
     */
    protected function getTelepaiementUrl()
    {
        $config = $this->getFrontController()->getParam('config');
        return $config->telepaiement->url;
    }
    /**
     * Retourne l'adresse de fin de session de l'application télé-paiement
     *
     * @return string
     */
    protected function getTelepaiementLogout()
    {
        $config = $this->getFrontController()->getParam('config');
        return $config->telepaiement->logout;
    }
}