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

class Ap_Controller_ActionHelpers_UserDocument_Facture extends Ap_Controller_ActionHelpers_UserDocument_Abstract
{
    protected function init()
    {
        $this->_titre = 'Récapitulatif de factures';
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
        $pdf = new Ap_Pdf();
        $pdf->setMarginRigth(40);
        $pdf->initBordureTrame();
        $pdf->setColsWidths(array(80, 50, 270, 65), array(Ap_Pdf::TEXT_ALIGN_LEFT, Ap_Pdf::TEXT_ALIGN_LEFT, Ap_Pdf::TEXT_ALIGN_LEFT, Ap_Pdf::TEXT_ALIGN_RIGHT));
        // thead
        $y = $pdf->drawTableLine($p, $x, $y, array('Année scolaire', 'Exercice', 'Libellé', 'Montant'));
        // tbody
        foreach ($this->_liste as $ligne) {
            //Zend_Debug::dump($ligne->toArray()); exit;
            $y = $pdf->drawTableLine($p, $x, $y, array($ligne->anneeScolaire, $ligne->exercice, $ligne->libelleFacture . ' ' . $ligne->nom . ' ' . $ligne->prenom, $ligne->montant));
        }
        // tfoot
        $pdf->setColsWidths(array(400, 65), array(Ap_Pdf::TEXT_ALIGN_LEFT, Ap_Pdf::TEXT_ALIGN_RIGHT));
        $y = $pdf->drawTableLine($p, $x, $y, array('Total à payer', $this->_total));

        return $y;
    }

    protected function messageApresT($p, $x1, $y, $x2)
    {
        $p->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), 8);
        $t = 'MODES DE REGLEMENT
- paiement en ligne par carte bancaire 
- en espèces à la Communauté de Communes de Montesquieu
- par un moyen bancaire à l\'ordre du Trésor Public       
';
        return Ap_Pdf::drawTextBox($p, $t, $x1, $this->margeH, $x2);
    }
}
