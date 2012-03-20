<?php
/**
 * Description courte du fichier
 *
 * Description longue du fichier s'il y en a une
 *
 * @project pits
 * @package package_name
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 5 mars 2011
 * @version $Id: $
 */

/**
 * Classe abstraite pour les Controllers de l'application
 * @author pomirol
 */
abstract class Ap_Controller_Action extends Zend_Controller_Action
{
    protected $_siteOuvert;
    protected $_bootstrap;
    protected $_db;
    protected $_session;
    protected $_cache;
    protected $_log;
    protected $_translate;
    protected $_auth;
    protected $_redirector;
    protected $_applicationPath;
    /**
     * initialise l'attribut de session, donne une instance de Zend_Ldap et de Zend_Auth
     */
    public function init()
    {
        // force la baseUrl pour le site de la COBAS
        $url = $this->getFrontController()->getBaseUrl();
        if (!preg_match('#^(.+)index.php$#', $url)) $this->_redirect(DIRECTORY_SEPARATOR . 'index.php');
        
        // les paramètres du boostrap
        $this->_bootstrap = $this->getInvokeArg('bootstrap');
        $this->_siteOuvert = $this->_bootstrap->getResource('ouverture');
        $this->_db = $this->_bootstrap->getResource('db');
        $this->_session = $this->_bootstrap->getResource('session');
        $this->_cache = $this->_bootstrap->getResource('cache');
        $this->_log = $this->_bootstrap->getResource('log');
        $this->_translate = $this->_bootstrap->getResource('translate');
        // autres paramètres
        $this->_redirector = $this->_helper->getHelper('Redirector');
        $this->_auth = Zend_Auth::getInstance();
        $this->_applicationPath = Zend_Registry::get('applicationPath');    
        // pour le bouton d'aide de la vue
        $this->view->controller =  $this->getRequest()->getControllerName();  
    }
    /**
     * Renvoie l'URL de base de l'application
     * @return string
     */
    protected function getBaseUrl()
    {
        return $this->getFrontController()->getBaseUrl();
    }
    protected function getUrlDocs() 
    {
        $url = $this->getBaseUrl();
        $index_php = preg_match('#^(.+)index.php$#', $url, $matches);
        if ($index_php) {
            $url = $matches[1];
        } 
        return $url . DIRECTORY_SEPARATOR . 'documents';
    }
    /**
     * Vérifie que chaque valeur de $array est une clé de $_POST
     * @param array $array
     * @return boolean
     */
    protected function hasPost($array) {
        global $_POST;
        if (!is_array($array)) {
            throw new Ap_Exception("Paramètre incorrect ! La méthode hasPost accepte un tableau comme paramètre.");
        }
        $ok = true;
        foreach ($array as $item) {
            $ok &= isset($_POST[$item]);
        }
        return $ok;
    }
    /**
     * Enregistre dans log et ferme la session
     * @param string $classe
     * @param string $methode
     */
    public function truandage($classe, $methode)
    {
        try {
            $message = $classe . ' ' . $methode . ' : Truandage de userId = ' . $this->_auth->getIdentity()->Email . PHP_EOL;
            $params = $this->getRequest()->getParams();
            foreach ($params as $key => $value) {
                $message .= "  $key: $value" . PHP_EOL;
            }
            $this->_log->notice($message);
        } catch (Zend_Exception $e) {
            ; // ne rien dire
        }

        $this->_redirect('index/logout');
    }
    /**
     * Pour debbugage
     *
     * @param mixted $obj
     * @param string $label
     * @param boolean $exit
     */
    protected function var_dump($obj='', $label='', $exit=TRUE)
    {
        if ($obj == '') {
            $obj = $this;
            $label = 'this';
        }
        echo '<hr />';
        echo '<pre>';
        echo $label . ':' . PHP_EOL;
        echo var_dump($obj);
        echo '</pre>';
        echo '<hr />';
        if ($exit) exit;
    }
}