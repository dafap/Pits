<?php
/**
* Aide de vue pour l'application PiTS
*
* Retourne la liste des erreurs sous forme d'une chaine (une erreur par ligne)
* 
* @project pits
* @package package_name
* @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
* @date 12 mai 2011
* @version $Id: $
*/

class Zend_View_Helper_ErrorMessage
{
    /**
     * Récupère les messages dans le flashMessenger 
     * au namespace indiqué
     * 
     * @param string $errorMessage
     * @return string
     */
    public function errorMessage($errorMessage)
    {
        $messages = Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->setNamespace($errorMessage)->getMessages();
        $result = '';
        $sep = '';
        foreach ($messages as $message) {
            $result .= $message . $sep;
            $sep = '<br />';
        }
        return $result;
    }
}