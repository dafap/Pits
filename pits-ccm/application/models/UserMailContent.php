<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 7 juin 2010
 *
 * @category   pits
 * @package
 * @subpackage
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

/**
 * @category   pits
 * @package
 * @subpackage
 * @author     pomirol
 */
class UserMailContent extends Pits_Document_Structure
{
    /**
     * Le mot de passe en clair s'il est connu
     * @var string
     */
    private $_mdp;
    public function __construct($filename, $mdp, $user)
    {
        parent::__construct($filename);
        // initialisation du mdp
        $this->_mdp = $mdp;
        // initialisation des tables de conversion des champs pour le mécanisme run
        //$user = Zend_Auth::getInstance()->getIdentity()->toArray();
        foreach ($user as $field => $value) {
            $this->_tableFields[] = "#§" . $field . "§#";
            $this->_tableValues[] = $value;
        }
    }
    public function run()
    {
        $body = '';
        while (!$this->feof()) {
            $tmp = $this->fgets();
            if (!is_null($tmp)) {
                $body .= $tmp;
            }
        }
        return $body;
    }
    /**
     * Traite les champs §mdp§, §bloc§ et §finbloc§
     */
    protected function particularValues()
    {
        $this->_buffer = preg_replace('#§mdp§#i', $this->_mdp, $this->_buffer);
        $url = 'http://' . $_SERVER['HTTP_HOST'] . Zend_Controller_Front::getInstance()->getBaseUrl();
        $this->_buffer = preg_replace('#§url§#i', $url, $this->_buffer);
        if (Pits_Document_Structure::mb_trim($this->_buffer) == '§bloc§') {
            $this->_buffer = '';
        } elseif (Pits_Document_Structure::mb_trim($this->_buffer) == '§finbloc§') {
            $this->_buffer = "\n";
        }
    }
}