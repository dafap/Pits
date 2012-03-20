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
        $this->view->tp = '2011 (v.1.0)';
        $this->view->zend = Zend_Version::VERSION;
    }
}