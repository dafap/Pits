<?php
/**
 * Configure l'application
 *
 * Mise en place de la session, du cache, de la locale, de la translation, des vues et divers paramètres de configuration ...
 *
 * @project télé-paiement CCM
 * @package application
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 09 sept. 2011
 * @version $Id:$
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    private $_applicationPath;

    protected function _initDefauts()
    {
        mb_internal_encoding('utf-8');
        $frontController = Zend_Controller_Front::getInstance();
        $frontController->setParams($this->getOptions());
        //Zend_Debug::dump($frontController); exit;
    }
    /**
     * Mise en place de l'autoloader
     */
    protected function _initAutoload()
    {
        $loader = new Zend_Loader_Autoloader_Resource($this->getOption('autoloaderresource'));
        $loader->addResourceType('dbtable', 'models/dbtables', 'Model_DbTable')
        ->addResourceType('doc', 'models/doc', 'Document')
        ->addResourceType('form', 'forms', 'Form')
        ->addResourceType('model', 'models','Model')
        ->addResourceType('filter', 'forms/filters', 'Filter')
        ->addResourceType('validator', 'forms/validators', 'Validate');
    }
    protected function _initApplicationPath()
    {
        $this->_applicationPath = $this->getOption('applicationPath');
        Zend_Registry::set('applicationPath', $this->_applicationPath);
        return $this->_applicationPath;
    }
    protected function _initRootPath()
    {
        $this->_rootPath = $this->getOption('rootPath');
        Zend_Registry::set('rootPath', $this->_rootPath);
        return $this->_rootPath;
    }
    protected function _initSite()
    {
        return $this->getOption('site');
    }
    protected function _initOrganisateur()
    {
        return $this->getOption('organisateur');
    }
    protected function _initMail()
    {
        return $this->getOption('mail');
    }
    protected function _initPaybox()
    {
        return $this->getOption('paybox');
    }
    protected function _initMenu()
    {
        $configMenus = new Zend_Config_Ini($this->_applicationPath  . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'menus.ini');
        Zend_Registry::set('menus', $configMenus);
        return $configMenus;
    }
    /**
     * Mise en place de la session. La variable de session est mise dans le Zend_Registry.
     */
    protected function _initSession()
    {
        if (!isset($this->_applicationPath)) {
            $this->_initApplicationPath('applicationPath');
        }
        // Lecture du fichier session.ini
        $configSession = new Zend_Config_Ini($this->_applicationPath . '/configs/session.ini',
        getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production');
        Zend_Session::setOptions($configSession->toArray());
        // surcharge pour tenir compte du chemin 'applicationPath' non connu dans le fichier session.ini (car dépend du serveur hébergeur)
        Zend_Session::setOptions(array('save_path' => $this->_applicationPath . $configSession->save_path));
        Zend_Registry::set('session', $session = new Zend_Session_Namespace($configSession->name));
        Zend_Registry::set('rootPath', $this->getOption('rootPath'));
        return $session;
    }
    /**
     * Mise en place du cache, de la locale et de la translation
     *
     */
    protected function _initCache()
    {
        $options = $this->getOption('cache');
        if (isset($options['backendOptions']['cache_dir']) && !is_dir($options['backendOptions']['cache_dir'])) {
            mkdir($options['backendOptions']['cache_dir']);
        }
        $cache = Zend_Cache::factory($options['frontend'], $options['backend'], $options['frontendOptions'], $options['backendOptions']);
        return $cache;
    }
    /**
     * Mise en place de la traduction
     * - 2 fichiers de traduction :
     *     * le fichier de l'application défini par l'option translate de application.ini
     *     * le fichier des validateurs de formulaires par l'option translateForm de application.ini
     */
    protected function _initTranslate()
    {
        // Cache
        $cache = $this->getResource('cache');
        Zend_Translate::setCache($cache);

        // Options pour Locale et Translate
        $options = $this->getOption('translate');
        // Locale
        $locale = new Zend_Locale($options['locale']);
        Zend_Locale::setCache($cache);

        // Translate
        Zend_Translate::setCache($cache);
        $translate = new Zend_Translate($options);
        Zend_Registry::set('Zend_Translate', $translate);

        // pour les formulaires
        $optionsForm = $this->getOption('translateForm');
        $translator = new Zend_Translate($optionsForm);
        Zend_Validate_Abstract::setDefaultTranslator($translator);

        return $translate;
    }
    /**
     * Mise en place de la base de données
     */
    protected function _initDb()
    {
        try {
            $dbConfig = $this->getOption('database');
            $db = Zend_Db::factory($dbConfig['adapter'], $dbConfig['params']);
            $db->query("SET NAMES 'UTF8'");
            Zend_Db_Table_Abstract::setDefaultAdapter($db);
        } catch (Zend_Db_Exception $e) {
        }
        return $db;
    }
    /**
     * Mise en place des aides d'action
     *
     */
    protected function _initHelperBroker()
    {
        if (is_array($this->getOption('addPrefixHelperBroker'))) {
            foreach ($this->getOption('addPrefixHelperBroker') as $prefix) {
                Zend_Controller_Action_HelperBroker::addPrefix($prefix);
            }
        }

        return true;
    }
    /**
     *
     * Mise en place du partial pour le paginator
     */
    protected function _initPaginator() {
        Zend_Paginator::setDefaultScrollingStyle('Elastic');
        Zend_View_Helper_PaginationControl::setDefaultViewPartial('common/pagination_control.phtml');
    }
    /**
     *
     * Mise en place du service de logs
     *
     */
    protected function _initLog()
    {
        $config = new Zend_Config($this->getOptions());
        $log = new Zend_Log();

        if($config->log->enable == "1" && isset($config->log->dest)){
            if($config->log->filter->enable=="1"){
                switch($config->log->filter->level){
                    case "EMERG":
                        $filter = new Zend_Log_Filter_Priority(Zend_Log::EMERG);
                        break;
                    case "ALERT":
                        $filter = new Zend_Log_Filter_Priority(Zend_Log::ALERT);
                        break;
                    case "CRIT":
                        $filter = new Zend_Log_Filter_Priority(Zend_Log::CRIT);
                        break;
                    case "ERR":
                        $filter = new Zend_Log_Filter_Priority(Zend_Log::ERR);
                        break;
                    case "WARN":
                        $filter = new Zend_Log_Filter_Priority(Zend_Log::WARN);
                        break;
                    case "NOTICE":
                        $filter = new Zend_Log_Filter_Priority(Zend_Log::NOTICE);
                        break;
                    case "INFO":
                        $filter = new Zend_Log_Filter_Priority(Zend_Log::INFO);
                        break;
                    case "DEBUG":
                        $filter = new Zend_Log_Filter_Priority(Zend_Log::DEBUG);
                        break;
                    default:
                        $filter = new Zend_Log_Filter_Priority(Zend_Log::DEBUG);
                        break;
                }
                $log->addFilter($filter);
            }
            $writer = new Zend_Log_Writer_Stream($config->log->dest);
        }else{
            $writer = new Zend_Log_Writer_Null;
        }
        $log->addWriter($writer);
        return $log;
    }
    /**
     * Initialisation de la vue par le choix du docType et de Titre
     *
     */
    protected function _initView()
    {
        $translate = $this->getResource('translate');
        $view = new Zend_View();
        $view->doctype('HTML4_LOOSE');
        $view->headTitle($translate->_('tp'));

        // Ajoutons là au ViewRenderer
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);

        return $view;
    }
}