<?php
/**
* Exportation au format csv
* 
* @project pits
* @package library/ap
* @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
* @date 3 juin 2011
* @version $Id: $
*/

class Ap_Controller_ActionHelpers_ExportEleves_Csv extends Ap_Controller_ActionHelpers_ExportEleves_Abstract
{
    protected function init() 
    {
        $this->_mimetype = 'text/csv';
    }
    public function getContent() 
    {
        ;
    }
}