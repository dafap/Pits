<?php
/**
 * Extention de Zend_Form pour la gestion des hidden
 *
 * @package library/Ap
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 19 mai 2011
 * @version $Id: $
 */

class Ap_Form extends Zend_Form
{
    /**
     * Place un hidden
     * $value optionnelle, null par défaut
     * 
     * @param string $name
     * @param (string|null|numeric) $value
     * @return object Ap_Form
     * @throws Ap_Form_Exception
     */
    public function setHidden($name, $value=null)
    {
        if (is_string($name) && (is_numeric($value) || is_string($value) || is_null($value))) {
            $hidden = new Zend_Form_Element_Hidden($name);
            $hidden->addPrefixPath('Ap_Form_Decorator', 'Ap/Form/Decorator/', 'decorator')
            ->setDecorators(array('Hidden'));
            if (!is_null($value)) {
                $hidden->setValue($value);
            }
            $this->addElement($hidden);
            return $this;
            
        } else {
            throw new Ap_Form_Exception('Appel incorrect à setHidden() dans Ap_Form');
        }
    }
    /**
     * Place un ou plusieurs hidden
     * Le paramètre $elements est un tableau de la forme array('nom' => 'valeur', ...)
     * 
     * @param array $elements
     * @return object Ap_Form
     */
    public function setHiddens($elements)
    {
        if (is_array($elements)) {
            foreach ($elements as $name => $value) {
                $this->setHidden($name, $value);
            }
        }
        return $this;
    }
}