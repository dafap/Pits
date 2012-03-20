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

class Ap_Controller_ActionHelpers_UserDocument_Justificatif extends Ap_Controller_ActionHelpers_UserDocument_Abstract
{
    protected function init()
    {
        $this->_titre = 'Justificatif de paiement';
    }

    protected function destinataire($p, $x1, $y, $x2)
    {

        $dy = (int) ($p->getFontSize() * 1.1);
        $auth = Zend_Auth::getInstance()->getIdentity();
        $l = $auth->Titre . ' ' . $auth->Nom . ' ' . $auth->Prenom;
        Ap_Pdf::drawText($p, $l, $x1, $this->ypos($y), $x2);
        $y += $dy;
        $l = $auth->AdresseL1;
        Ap_Pdf::drawText($p, $l, $x1, $this->ypos($y), $x2);
        $y += $dy;
        $l = $auth->AdresseL2;
        Ap_Pdf::drawText($p, $l, $x1, $this->ypos($y), $x2);
        $y += $dy;
        $records = new Tp_Model_DbTable_TVilles();
        $l = $auth->CodePostal . ' ' . $records->nom($auth->Commune);
        Ap_Pdf::drawText($p, $l, $x1, $this->ypos($y), $x2);
        $y += $dy;

        return $y;
    }

    protected function tableau($p, $x, $y)
    {
        $colsWidths = array(180, 60, 50, 65, 50, 50);
        $colsAligns = array(Ap_Pdf::TEXT_ALIGN_LEFT, Ap_Pdf::TEXT_ALIGN_LEFT, Ap_Pdf::TEXT_ALIGN_LEFT, Ap_Pdf::TEXT_ALIGN_LEFT, Ap_Pdf::TEXT_ALIGN_LEFT, Ap_Pdf::TEXT_ALIGN_RIGHT);
        $pdf = new Ap_Pdf();
        $pdf->setMarginRigth(40);
        $pdf->initBordureTrame();
        $pdf->setColsWidths($colsWidths, $colsAligns);
        // thead
        $y = $pdf->drawTableLine($p, $x, $y, array('référence', 'date', 'heure', 'paiement', 'carte', 'montant'));
        // tbody
        foreach ($this->_liste as $ligne) {
            //Zend_Debug::dump($ligne->toArray()); exit;
            $y = $pdf->drawTableLine($p, $x, $y, array($ligne->maref, $ligne->datetrans, $ligne->heuretrans, $ligne->paiement, $ligne->carte, sprintf('%0.2f', $ligne->montant / 100)));
        }
        // tfoot
        for ($lg = 0, $j = 0; $j < count($colsWidths) - 1; $j++) $lg += $colsWidths[$j];
        $pdf->setColsWidths(array($lg, end($colsWidths)), array(reset($colsAligns), end($colsAligns)));
        $y = $pdf->drawTableLine($p, $x, $y, array('Total des sommes perçues', sprintf('%0.2f', $this->_total)));

        return $this->ypos($y);
    }

    protected function messageAvantT($p, $x1, $y, $x2)
    {
        $p->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 12);
        $auth = Zend_Auth::getInstance()->getIdentity();
        
        $t = 'Nous certifions avoir perçu de ' . $auth->Titre . ' ' . $auth->Nom . ' ' . $auth->Prenom .
        ' les sommes suivantes :';
        return $this->ypos(Ap_Pdf::drawTextBox($p, $t, $x1, $this->ypos($y), $x2));
    }
    
    protected function messageApresT($p, $x1, $y, $x2)
    {
        $p->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 8);
        $t = 'MODES DE REGLEMENT : paiement en ligne par carte bancaire     
';
        return Ap_Pdf::drawTextBox($p, $t, $x1, $this->ypos($y), $x2);
    }
}
