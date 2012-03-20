<?php
/**
* Contruction d'un mail pour envoyer à un user
*
* Description longue du fichier s'il y en a une
* 
* @project pits
* @package application/models/doc
* @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
* @date 24 mai 2011
* @version $Id: $
*/

class Pits_Document_UserMailContent extends Pits_Document_Abstract
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
        foreach ($user as $field => $value) {
            if ($field == 'Commune') {
                $value = $this->nomCommune($value);
            }
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
        if (self::mb_trim($this->_buffer) == '§bloc§') {
            $this->_buffer = '';
        } elseif (self::mb_trim($this->_buffer) == '§finbloc§') {
            $this->_buffer = "\n";
        }
    }
    /**
     * Recherche le nom de la commune
     */
    private function nomCommune($codeInsee) {
        $records = new Pits_Model_DbTable_TVilles();
        $record = $records->find($codeInsee)->current();
        return $record->Nom;
    }
}