<?php
/**
 * Configure l'application
 *
 * Mise en place du cache, de la locale, de la translation, des vues, du nombre de liens dans le footer ...
 *
 * @project pits
 * @package views/helpers
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 25 fevr. 2011
 * @version $Id:$
 */
class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initOuverture()
    {
        return $this->getOption('siteOuvert');
    }
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
    /**
     * Mise en place de la session. La variable de session et APPLICATION_PATH sont mis dans le Zend_Registry.
     */
    protected function _initSession()
    {
        $applicationPath = $this->getOption('applicationPath');
        // Lecture du fichier session.ini
        $configSession = new Zend_Config_Ini($applicationPath . '/configs/session.ini',
        getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production');
        Zend_Session::setOptions($configSession->toArray());
        // surcharge pour tenir compte du chemin 'applicationPath' non connu dans le fichier session.ini (car dépend du serveur hébergeur)
        Zend_Session::setOptions(array('save_path' => $applicationPath . $configSession->save_path));
        Zend_Registry::set('session', $session = new Zend_Session_Namespace($configSession->name));
        Zend_Registry::set('applicationPath', $applicationPath);
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
            $dbConfig = $this->getOption('db');
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
        $view->headTitle($translate->_('PiTS'));

        // Ajoutons là au ViewRenderer
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);

        return $view;
    }
    /**
     * Initialisation du nombre de liens dans le footer
     *
     */
    protected function _initFooter()
    {
        Zend_Registry::set('footerNbOptions', (int)current($this->getOption('footer')));
    }
    /**
     * Pour debbugage
     *
     * @param mixted $obj
     * @param string $label
     * @param boolean $exit
     */
    private function var_dump($obj='', $label='', $exit=TRUE)
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

