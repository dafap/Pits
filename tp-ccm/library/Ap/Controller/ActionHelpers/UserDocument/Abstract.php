<?php
/**
 * Générateur de factures et de justificatifs en pdf
 *
 * @date 30 septembre 2011
 *
 * @package    libray
 * @subpackage Ap/Controller/ActionHelpers/UserDocument
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2011, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    1.0.0
 * @since      Ce fichier est disponible depuis la version 1.0.0
 */

include_once 'Ap/Controller/ActionHelpers/UserDocument/Interface.php';

/**
 * @package    library
 * @subpackage Ap/Controller/ActionHelpers/UserDocument
 * @author     pomirol
 */
abstract class Ap_Controller_ActionHelpers_UserDocument_Abstract
implements Ap_Controller_ActionHelpers_UserDocument_Interface
{
    const MIME_TYPE = 'application/pdf';
    /**
     * Exprimées en pts = 1/72 inch soit 0,352778 mm
     * @var integer Marges haut et gauche du document
     */
    protected $margeG = 70;
    protected $margeH = 70;
    
    private $_hauteurPage;
    private $_largeurPage;

    protected $_userId;
    protected $_liste;
    protected $_total;
    protected $_titre;
    protected $_configPdf;

    public function __construct($id, $liste, $total)
    {
        $this->_eleveId = $id;
        $this->_liste = $liste;
        $this->_total = $total;
        $this->_configPdf = Zend_Controller_Front::getInstance()->getParam('pdf');
        // surcharger la méthode init pour définir ce qui est à insérer dans le pdf
        $this->init();
    }

    public function getMimeType()
    {
        return self::MIME_TYPE;
    }

    public function getContent()
    {
        $pdf = new Zend_Pdf();

        // Calcul de la mise en page
        $pdf->pages[] = ($p = $pdf->newPage(Zend_Pdf_Page::SIZE_A4));
        $this->_hauteurPage = $p->getHeight();
        $this->_largeurPage = $p->getWidth();
        // logo
        $imagePath = Zend_Registry::get('rootPath') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
        $imageName = $imagePath . $this->_configPdf['logo'];
        $imageLogo = Zend_Pdf_Image::imageWithPath($imageName);
        $logoy = (int) $this->_configPdf['logoy'];
        $p->drawImage($imageLogo,$this->xpos(0), $this->ypos($logoy), $this->xpos($this->_configPdf['logox']), $this->ypos(0));
        // polices utilisées
        $helvetica = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
        // date du document
        $p->setFont($helvetica, 12);
        $y = (int) ($logoy - $p->getFontSize()) / 2;
        $d = new Tp_Model_Date();
        Ap_Pdf::drawText($p,$d->toString("d MMMM YYYY"), $this->margeG, $this->ypos($y), $this->_largeurPage - $this->margeG, Ap_Pdf::TEXT_ALIGN_RIGHT, 'UTF-8');
        // destinataire
        $p->setFont($helvetica, 12);
        $x1 = (int) $this->_largeurPage / 2;
        $x2 = $this->_largeurPage - $this->margeG;
        $y = (int) ($logoy * 1.1);
        $y = $this->destinataire($p, $x1, $y, $x2);
        // titre
        $p->setFont($helvetica, 14);
        $x1 = $this->margeG;
        $y += 30;
        $this->titre($p, $x1, $y, $x2);
        // message avant tableau
        $p->setFont($helvetica, 12);
        $x = 0;
        $y += (int) ($p->getFontSize() * 2 * 1.1);
        $y = $this->messageAvantT($p, $x1, $y, $x2);
        // tableau
        $p->setFont($helvetica, 10);
        $x = 0;
        $y += (int) ($p->getFontSize() * 2 * 1.1);
        $y = $this->tableau($p, $this->xpos($x), $this->ypos($y));
        // message après tableau
        $p->setFont($helvetica, 12);
        $y += (int) ($p->getFontSize() * 2 * 1.1);
        $y = $this->messageApresT($p, $x1, $y, $x2);
        // renvoi du document
        return $pdf->render();
    }

    protected function init()
    {
    }

    protected function destinataire($p, $x1, $y, $x2)
    {
        return $y;
    }

    protected function titre($p, $x1, $y, $x2)
    {
        Ap_Pdf::drawText($p, $this->_titre, $x1, $this->ypos($y), $x2,Ap_Pdf::TEXT_ALIGN_CENTER);
        return $y + (int) $p->getFontSize()* 1.1;
    }

    protected function messageAvantT($p, $x1, $y, $x2)
    {
        return $y;
    }

    protected function tableau($p, $x, $y)
    {
        return $y;
    }

    protected function messageApresT($p, $x1, $y, $x2)
    {
        return $y;
    }

    protected function xpos($xzone)
    {
        return $xzone + $this->margeG;
    }

    protected function ypos($yzone)
    {
        return $this->_hauteurPage - $this->margeH - $yzone;
    }
}