<?php
/**
* Description courte du fichier
*
* Description longue du fichier s'il y en a une
* 
* @project project_name
* @package package_name
* @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
* @date 29 sept. 2011
* @version $Id: $
*/

class VersionController extends Zend_Controller_Action
{
    public function indexAction() {
        $this->view->pits = '2010 (rev. mars 2012)';
        $this->view->zend = Zend_Version::VERSION;
    }
    public function phpinfoAction() {
        ;
    }
}