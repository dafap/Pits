<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 10 juil. 2010
 *
 * @category   pits
 * @package    library
 * @subpackage pits/form
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

/**
 * @category   pits
 * @package    library
 * @subpackage pits/form
 * @author     pomirol
 */
abstract class Pits_Form_Abstract extends Zend_Form
{
    /**
     * Url de base de l'application
     * @var string
     */
    private $_baseUrl;
    /**
     * Valeurs initiales à passer au formulaire
     * @var array()
     */
    private $_initArray;
    /**
     * Initialise des attributs avant de créer le formulaire
     *
     * @param array() $urlArray
     * @param array() ou Zend_Config $options
     */
    public function __construct($initArray=null, $options=null) {
        if (is_array($initArray)) {
            $this->_initArray = $initArray;
        }
        $this->_baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        parent::__construct($options);
    }
    /**
     * Renvoie l'url de base de l'application
     *
     * @return string
     */
    protected function getBaseUrl()
    {
        return $this->_baseUrl;
    }
    /**
     * Renvoie la valeur initiale de la clé $key
     *
     * @param string $key
     * @return unknown_type
     */
    protected function getInitValue($key)
    {
        $key = (string) $key;
        if (!isset($this->_initArray[$key])) {
            return null;
        }

        return $this->_initArray[$key];
    }
    /**
     * Renvoie l'attribut 'cancel' de _initArray[]. C'est l'url associée au bouton 'cancel'
     *
     * @return string
     */
    protected function getCancel()
    {
        $url = $this->getInitValue('cancel');
        if (is_null($url)) $url = $this->_baseUrl;
        return urldecode($url);
    }
    /**
     * Place dans le formulaire un hidden dont le nom est $name sans décorateur
     *
     * @param string $name
     * @return Zend_Form
     */
    public function putsHidden($name, $value=null)
    {
        $hidden = new Zend_Form_Element_Hidden($name);
        $hidden->addPrefixPath('Pits_Form_Decorator', 'Pits/Form/Decorator/', 'decorator')
        ->setDecorators(array('Hidden'));
        if (!is_null($value)) {
            $hidden->setValue($value);
        }
        $this->addElement($hidden);
        return $this;
    }
    /**
     * Place dans le formulaire un hidden dont le nom est 'forward' et la valeur est $url ou _attrib('forward')
     * @param string $url
     * @return Zend_Form
     */
    public function putsForward($url=null)
    {
        if (is_null($url)) {
            $url = $this->getInitValue('forward');
        }
        if (empty($url)) return $this;
        return $this->putsHidden('forward', $url);
    }
    /**
     * Place un bouton submit
     * @param string $label
     * @param array() $decorators
     * @param integer $marginLeft
     * @param string $class
     * @return Zend_Form
     */
    protected function putsSubmitButton($label, $decorators, $marginLeft=0, $class='')
    {
        $attribs = array();
        if (!empty($class)) {
            $attribs['class'] = $class;
        }
        $submitButton = new Zend_Form_Element_Submit('submit');
        $submitButton->setLabel($label)
        ->setDecorators($decorators)
        ->setAttribs($attribs)
        ->style = 'margin-left: '. $marginLeft . 'px;';
        $this->addElement($submitButton);

        return $this;
    }
    /**
     * Place dans le formulaire un bouton 'cancel' dont le Label est $name et l'url est _attrib('cancel')
     *
     * @param string $name
     * @param array() $decorators
     * @param integer $marginLeft (en px)
     * @param string $class
     * @return Zend_Form
     */
    protected function putsCancelButton($name, $decorators, $marginLeft=0, $class='')
    {
        $attribs = array('onclick' => 'window.location.href="' . $this->getCancel() . '"');
        if (!empty($class)) {
            $attribs['class'] = $class;
        }
        $cancelButton = new Zend_Form_Element_Button('cancel');
        $cancelButton->setLabel($name)
        ->setDecorators($decorators)
        ->setAttribs($attribs)
        ->style = 'margin-left: '. $marginLeft . 'px;';
        $this->addElement($cancelButton);

        return $this;
    }
    /**
     * Place dans le formulaire un Hash élément sans décorateur dont le nom est $name
     *
     * @param string $name
     * @return Zend_Form
     */
    public function putsHash($name)
    {
        $decoratorsHidden = array('Viewhelper',array('HtmlTag', array()),);
        $token = new Zend_Form_Element_Hash($name, array('salt' => 'unique'));
        $token->setDecorators($decoratorsHidden);
        $this->addElement($token);

        return $this;
    }
    /**
     * Mise en place du translator
     *
     * @return Zend_Form
     */
    protected function putsTranslator() {
        // Récupération du root de l'application
        $rootPath = Zend_Registry::get('rootPath');

        // Mise en place du translator
        $translator = new Zend_Translate(
            'array',
        $rootPath.'/resources/languages',
            'fr',
        array('scan' => Zend_Translate::LOCALE_DIRECTORY));
        Zend_Validate_Abstract::setDefaultTranslator($translator);

        return $this;
    }
}