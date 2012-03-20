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

class TestController extends Ap_Controller_Action
{
    public function indexAction() {
        $s = 'http://www.dafap.dev/ccm/tp/index.php/test';
        $s1 = Ap_Methode_Methode::avoca($s);
        $s2 = Ap_Methode_Methode::cavoa($s1);
        Zend_Debug::dump(array($s, $s1, $s2));exit;
    }
    public function testAction() {
        $this->_helper->ServerUrl();     
    }
}