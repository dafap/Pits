<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * Bootstrap de l'application (contrôleur frontal)
 *
 * Fichier unique par lequel passe tout appel à une action de PiTS.
 *
 * @category   pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

// définition de constantes pour l'application
define('APP_MODE', 'dev'); // Les valeurs définies dans config.ini sont 'dev' et 'prod'
define('CACHE_LIFETIME', 3600); // durée de vie du cache à ajuster

/**
 * fonction de renvoi vers une page d'erreur
 * en cas d'erreur bloquante
 */
function bootstrapError($e)
{
    global $log;
    if (isset($log)) {
        $log->info($e);
    } else echo $e;
    exit("Une erreur grave est survenue.");
}

/**
 * Configuration de PHP
 */
//set_exception_handler('bootstrapError');
error_reporting(E_ALL|E_STRICT);
//ini_set('display_errors', false);
iconv_set_encoding("internal_encoding", "UTF-8");
mb_internal_encoding("UTF-8");

// définition des chemins utiles
$indexPath = dirname(__FILE__);
$rootPath = dirname(dirname(__FILE__));
$appPath  = $rootPath . DIRECTORY_SEPARATOR . 'application';
$confPath = $appPath  . DIRECTORY_SEPARATOR . 'config';

// configuration de l'include_path PHP pour l'autoload
set_include_path(get_include_path() .
PATH_SEPARATOR . $appPath .
PATH_SEPARATOR . $appPath  . DIRECTORY_SEPARATOR . 'controllers' .
PATH_SEPARATOR . $appPath  . DIRECTORY_SEPARATOR . 'models' .
PATH_SEPARATOR . $rootPath . DIRECTORY_SEPARATOR . 'library'
);

// inclusion du composant Zend_loader pour les classes oubliées
// et pour les classes passées par leur nom
include 'Zend/Loader/Autoloader.php';

// déclaration du chargement automatique des classes
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);
$autoloader->registerNamespace('Pits_');
$loader = new Zend_Loader_Autoloader_Resource(array('basePath' => $appPath, 'namespace' => 'Pits'));
$loader->addResourceType('dbtable', 'models/dbtables', 'DbTable');

// mise en registre du root de l'application
Zend_Registry::set('rootPath', $rootPath); // dossier qui contient la structure application, library, public, ressources
Zend_Registry::set('indexPath',$indexPath);// dossier public qui doit être dans la zone web (par lien symbolique)
Zend_Registry::set('applicationPath',$appPath);
/* ******************** CACHE **********************************
 * Si nécessaire, création du cache pour les composants ZF l'acceptant
 */
Pits_Cache::setup(CACHE_LIFETIME);
// Cache automatique des fichiers de configuration
$cacheInstance = Pits_Cache::getCacheInstance();
$cacheInstance->setOption('cache_dir', 'C:/tmp/');
Pits_Config::setBackendCache($cacheInstance->getBackend());
/* ********************* CONFIG ********************************
 * Récupération des objets de configuration en utilisant un cache
 */
$configMain    = new Pits_Config_Ini($confPath . DIRECTORY_SEPARATOR . 'config.ini', APP_MODE);
$configSession = new Pits_Config_Ini($confPath . DIRECTORY_SEPARATOR . 'session.ini', APP_MODE);
$configMenus = new Pits_Config_Ini($confPath . DIRECTORY_SEPARATOR . 'menus.ini');
Zend_Registry::set('menus', $configMenus);
Zend_Registry::set('uploadsavepath', $configMain->uploadsavepath);
/* ******************** ERROR_HANDLER **************************
 * Gestion des erreurs sous forme d'exception par la librairie 'package.exceptions.errors.php'
 * La fonction bootstrapError intercepte les erreurs non attrapées et les enregistre dans le
 * fichier log (verbosité en fonction de $configMain->debug)
 */
require_once 'package.exceptions.errors.php';
//$oErr = new exceptionErrorHandler($configMain->debug);

/* ************************** LOG ******************************
 * Paramétrage du fichier log
 * - un niveau USER (8) est défini
 * - il permet de suivre les adresses IP et le type de navigateur des utilisateurs
 */
$errDevice = "php://output";
if ($configMain->logfile != $errDevice) {
    $errDevice = $appPath . $configMain->logfile;
}
$log = new Zend_Log($writer = new Zend_Log_Writer_Stream($errDevice));
$log->setEventItem('user_agent', $_SERVER['HTTP_USER_AGENT']);
$log->setEventItem('client_ip', $_SERVER['REMOTE_ADDR']);
$log->addPriority('USER', 8);
$format = "\nRequête en provenance de %client_ip%\n%user_agent%\n" . Zend_Log_Formatter_Simple::DEFAULT_FORMAT;
$writer->setFormatter(new Zend_Log_Formatter_Simple($format));
Zend_Registry::set('log', $log);

/* ************************* SESSION ***********************************
 * Configuration puis ouverture de la session
 */
// Configuration (impérativement avant son démarrage)
Zend_Session::setOptions($configSession->toArray());
// surcharge pour prendre en compte le chemin $appPath de l'application
Zend_Session::setOptions(array('save_path' => $appPath . $configSession->save_path));
// Partage (et création ou restauration) de l'objet de session dans le registre
// Ce premier appel à new Zend_Session_Namespace démarre la session PHP
Zend_Registry::set('session', $session = new Zend_Session_Namespace($configSession->name));

/* ************************** LOCALE ********************************
 * locale pour la gestion de la langue
 */
$locale = new Zend_Locale(); // locale par défaut : navigateur utilisé, sinon machine hôte
Zend_Registry::set('Zend_Locale', $locale);
Zend_Locale::setCache($cacheInstance);
/* ************************** DATES *********************************
 *
 */
date_default_timezone_set('Europe/Paris');
// Zen_Date n'est plus utilisée et est remplacée par Pits_Date, bien plus légère
//Zend_Date::setOptions(array('cache' => $cacheInstance));
/* ************************** DATABASE ******************************
 *
 */
try {
    $db = Zend_Db::factory($configMain->database);
    $db->query("SET NAMES 'UTF8'");

    // Passage de la connexion à toutes les classes passerelles
    Zend_Db_Table_Abstract::setDefaultAdapter($db);
} catch (Zend_Db_Exception $e) {
    // on passe l'exception sous silence, elle sera gérée dans le système MVC plus tard
}
// activation du cache des méta données des passerelles
Zend_Db_Table_Abstract::setDefaultMetadataCache($cacheInstance);
/* ************************** ACL ***********************************
 * Créer les ACL en session afin de
 * - permettre à un utilisateur de créer un compte
 * - permettre à un parent ayant un compte de s'identifier pour modification ou complément
 */

/* ************************* PAGINEUR *******************************
 * Pour les listes d'utilisateur et d'élèves
 */
Zend_View_Helper_PaginationControl::setDefaultViewPartial('common/pagination_control.phtml');

/* ************************** MVC ***********************************
 * Configuration
 */
// Configuration du contrôleur frontal
$frontController = Zend_Controller_Front::getInstance();
$frontController->setControllerDirectory($appPath . '/controllers');
$frontController->throwExceptions(false); // par défaut

// propagation de paramètres dans le système MVC
$frontController->setParam('debug', $configMain->debug);
$frontController->setParam('locale', $locale);
$frontController->setParam('config', $configMain);

// enregistrement du plugin de sauvegarde de la page précédente et de gestion des erreurs d'Url
$frontController->registerPlugin(new Pits_Controller_NavigationPlugin);

// Ajout du chemin des aides d'action dans le gestionnaire d'aides MVC
Zend_Controller_Action_HelperBroker::addPrefix('Pits_Controller_ActionHelpers');

// Configuration d'un en-tête de réponse HTTP global
$response = new Zend_Controller_Response_Http();
$response->setRawHeader('Content-type: text/html; charset=utf-8');

// passage de la réponse configurée au système MVC
$frontController->setResponse($response);

/* TODO
 // récupération du routeur
 $router = $frontController->getRouter();

 // définition et ajout de routes contact
 $router->addConfig($configRoutes->getConfigObject(), 'routes');
 */

/* ************************** LAYOUTS *******************************
 * Configuration
 */
Zend_Layout::startMvc(array('layoutPath' => $appPath . '/views/layouts'));

/* ************************** TRANSLATE *****************************
 * Si nécessaire, prévoir un translate CSV
 */
Zend_Translate::setCache($cacheInstance);
$translator = new Zend_Translate(
    array('adapter' => 'array', 
		  'content' => $rootPath . DIRECTORY_SEPARATOR . 'resources/languages/fr/Zend_Validate.php',
		  'locale' => 'fr_FR',));
Zend_Validate_Abstract::setDefaultTranslator($translator);

/* ************************** FORM **********************************
 *
 */

/* ************************** VIEW **********************************
 * Configuration de la vue
 */
$view = new Zend_View();
$view->setEncoding('UTF-8');
$view->strictVars((bool) $configMain->debug);
// Récupération de l'aide de rendu automatique de vues : viewRenderer
$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
// Passage de notre vue à ViewRenderer
$viewRenderer->setView($view);
/* ************************** DISPATCH ******************************
 *
 */
try {
    $frontController->dispatch();
} catch (Zend_Exception $e) {
    $log->crit($e);
}