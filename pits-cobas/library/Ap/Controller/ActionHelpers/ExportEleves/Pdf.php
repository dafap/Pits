<?php
/**
 * Crée un pdf et le renvoie
 *
 * Appelle un modèle de document Pits_Document_PdfEleve
 *
 * @project pits
 * @package library/ap
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 3 juin 2011
 * @version $Id: $
 */

class Ap_Controller_ActionHelpers_ExportEleves_Pdf extends Ap_Controller_ActionHelpers_ExportEleves_Abstract
{
    /**
     * Exprimées en pts = 1/72 inch soit 0,352778 mm
     * @var integer Marges haut et gauche du document
     */
    private $margeG = 50;
    private $margeH = 70;
    private $hauteurPage;
    private $largeurPage;
    private $_configPdf;

    protected function init()
    {
        $this->_mimetype = 'application/pdf';
        $this->setMimeDisposition('attachment;filename="document.pdf"');
        $this->_configPdf = Zend_Controller_Front::getInstance()->getParam('pdf');
    }
    public function getContent()
    {
        $pdf = new Zend_Pdf();

        /**
         * Calcul de la mise en page
         */
        $pdf->pages[] = ($p = $pdf->newPage(Zend_Pdf_Page::SIZE_A4));
        $this->hauteurPage = $p->getHeight();
        $this->largeurPage = $p->getWidth();
        // logo
        $imagePath = Zend_Registry::get('rootPath') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
        $imageName = $imagePath . $this->_configPdf['logo'];
        $imageLogo = Zend_Pdf_Image::imageWithPath($imageName);
        $p->drawImage($imageLogo,$this->xpos(0), $this->ypos($this->_configPdf['logoy']), $this->xpos($this->_configPdf['logox']), $this->ypos(0));
        // bus
        $imageName = $imagePath . $this->_configPdf['bus'];
        $imageLogo = Zend_Pdf_Image::imageWithPath($imageName);
        $x = $this->largeurPage - 100;
        $p->drawImage($imageLogo,$this->xpos($x - 322), $this->ypos(128), $this->xpos($x), $this->ypos(0));
        // titre en helvetica
        $helvetica = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        $p->setFont($helvetica, 16);
        // annee scolaire (dans config.ini)
        $p->drawText($this->_configPdf['annee_scolaire'], $this->xpos($x - 190), $this->ypos(60));
        $p->drawText('TRANSPORTS SCOLAIRES', $this->xpos($x - 250), $this->ypos(80));
        $p->setFont($helvetica, 14);
        $p->drawText("Justificatif d'inscription", $this->xpos($x - 220), $this->ypos(108));
        // date du justificatif
        $d = new Pits_Model_Date();
        $p->setFont($helvetica, 12);
        $p->drawText($d->toString("d MMMM YYYY"), $this->xpos($x - 100), $this->ypos(0),'UTF-8');
        // texte en caractère à taille fixe
        $courier = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER);
        $p->setFont($courier, 10);
        $doc = new Pits_Document_PdfEleve('pdf_eleve.txt', $this->_eleveIds);
        $doc->run($p, $this->ypos($this->_configPdf['ytexte']), $this->margeG);
         
        /**
         * Création du document
         */
        //        Zend_Debug::dump($p);
        return $pdf->render();
    }
    private function xpos($xzone)
    {
        return $xzone + $this->margeG;
    }
    private function ypos($yzone)
    {
        return $this->hauteurPage - $this->margeH - $yzone;
    }
}