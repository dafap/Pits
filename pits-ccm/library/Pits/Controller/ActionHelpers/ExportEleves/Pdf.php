<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 16 mai 2010
 *
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

include_once 'Pits/Controller/ActionHelpers/ExportEleves/Abstract.php';
include_once 'Zend/Pdf.php';
include_once 'Zend/Pdf/Style.php';
include_once 'Zend/Pdf/Color/GrayScale.php';
include_once 'Zend/Pdf/Font.php';
include_once 'Zend/Pdf/Image.php';
include_once 'Zend/Memory/Manager.php';
include_once 'Zend/Registry.php';
include_once 'Pits/Cache.php';

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_Controller_ActionHelpers_ExportEleves_Pdf
extends Pits_Controller_ActionHelpers_ExportEleves_Abstract
{
    const MIME_TYPE = 'application/pdf';
    /**
     * Exprimées en pts = 1/72 inch soit 0,352778 mm
     * @var integer Marges haut et gauche du document
     */
    private $margeG = 50;
    private $margeH = 70;
    private $hauteurPage;
    private $largeurPage;
    public function getMimeType()
    {
        return self::MIME_TYPE;
    }
    public function  getContent()
    {
        //Zend_Debug::dump($this->_eleveId); exit;
        $pdf = new Zend_Pdf();
        // gestion du type pour le 
        if (is_array($this->_eleveId)) {
            $eleves = $this->_eleveId;
        } elseif (is_object($this->_eleveId)) {
            $eleves = $this->_eleveId->toArray();
        } else {
            $eleves = array(array('eleveId' => $this->_eleveId));
        }
        foreach ($eleves as $eleve) {
            $eleveId = $eleve['eleveId'];

            /**
             * Calcul de la mise en page
             */
            $pdf->pages[] = ($p = $pdf->newPage(Zend_Pdf_Page::SIZE_A4));
            $this->hauteurPage = $p->getHeight();
            $this->largeurPage = $p->getWidth();
            // logo
            $indexPath = Zend_Registry::get('indexPath');
            $imagePath = $indexPath . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'logoavecadresse.jpg';
            $imageLogo = Zend_Pdf_Image::imageWithPath($imagePath);
            $p->drawImage($imageLogo,$this->xpos(0), $this->ypos(60), $this->xpos(168), $this->ypos(0));
            // bus
            $imagePath = $indexPath . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'bus460x184.png';
            $imageLogo = Zend_Pdf_Image::imageWithPath($imagePath);
            $x = $this->largeurPage - 100;
            $p->drawImage($imageLogo,$this->xpos($x - 322), $this->ypos(128), $this->xpos($x), $this->ypos(0));
            // titre en helvetica
            $helvetica = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
            $p->setFont($helvetica, 16);
            // annee scolaire (dans config.ini)
            $as = Zend_Controller_Front::getInstance()->getParam('config')->annee->scolaire;
            $p->drawText($as, $this->xpos($x - 190), $this->ypos(60));
            $p->drawText('TRANSPORTS SCOLAIRES', $this->xpos($x - 250), $this->ypos(80));
            $p->setFont($helvetica, 14);
            $p->drawText("Justificatif d'inscription", $this->xpos($x - 220), $this->ypos(108));
            // date du justificatif
            $d = new Pits_Date();
            $p->setFont($helvetica, 12);
            $p->drawText($d->toString("d MMMM YYYY"), $this->xpos($x - 100), $this->ypos(0),'UTF-8');
            // texte en caractère à taille fixe
            //        $fontPath = $indexPath . DIRECTORY_SEPARATOR . 'fonts' . DIRECTORY_SEPARATOR . 'onuava__.ttf';
            //        $onuava = Zend_Pdf_Font::fontWithPath($fontPath);
            $courier = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_COURIER);
            $p->setFont($courier, 10);
            $doc = new PdfEleve('pdf_eleve.txt',$eleveId);
            $doc->run($p, $this->ypos(142), $this->margeG);
        }
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