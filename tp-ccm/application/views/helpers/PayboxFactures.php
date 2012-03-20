<?php
/**
 * A partir d'une référence Paybox, interroge la table factures et prépare une table html
 *
 * Description longue du fichier s'il y en a une
 *
 * @project télé-paiement CCM
 * @package application/views/helpers
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 8 oct. 2011
 * @version $Id: $
 */

class Zend_View_Helper_PayboxFactures extends Zend_View_Helper_Abstract
{
    private $_paramsCss;
    /**
     * Construit un bloc html qui présente les factures associées à une référence paybox (maref)
     * Le paramètre $paramsCss se présente sous la forme d'un tableau associatif array(balise => class, ...)
     *
     * @param string $maref
     * @param array $paramsCss
     * @return string
     */
    public function payboxFactures($maref, $paramsCss)
    {
        if (!is_array($paramsCss)) {
            throw new Ap_Exception('Les classes css doivent être données dans un tableau associatif dans ' . __METHOD__ . PHP_EOL . var_export($paramsCss, true));
        }
        $this->_paramsCss = $paramsCss;
        // recherche des `factureIds`
        $paybox = new Tp_Model_Paybox(null);
        $paybox->setParams(array('maref' => $maref));
        $factureIds = $paybox->getFactureIdsRef();
        // ouverture des factures
        $records = new Tp_Model_DbTable_TFactures();
        // construction du blocHtml à renvoyer
        $blocHtml = '';
        $init = $this->baliseHtml('div') . $this->baliseHtml('table') . $this->baliseHtml('tbody');
        foreach ($factureIds as $factureId) {
            if (empty($blocHtml)) $blocHtml = $init;
            $record = $records->find($factureId)->current();
            $blocHtml .= $this->baliseHtml('tr');
            $blocHtml .= $this->baliseHtml('td');
            $blocHtml .= $record['exercice'] . '-' . $record['nFacturation'] . '-' . $record['rangFacture'];
            $blocHtml .= $this->baliseFinHtml('td');
            $blocHtml .= $this->baliseHtml('td');
            $blocHtml .= $record['anneeScolaire'];
            $blocHtml .= $this->baliseFinHtml('td');
            $blocHtml .= $this->baliseHtml('td');
            $blocHtml .= empty($record['dateFacturation']) ? '' : Tp_Model_Format::date('dd/MM/YYYY', $record['dateFacturation']);
            $blocHtml .= $this->baliseFinHtml('td');
            $blocHtml .= $this->baliseHtml('td');
            $blocHtml .= $record['nom'] . ' ' . $record['prenom'];
            $blocHtml .= $this->baliseFinHtml('td');
            $blocHtml .= $this->baliseHtml('td');
            $blocHtml .= $record['montant'];
            $blocHtml .= $this->baliseFinHtml('td');
            $blocHtml .= $this->baliseFinHtml('tr');
        }
        return empty($blocHtml) ? '' : $blocHtml . $this->baliseFinHtml('tbody') . $this->baliseFinHtml('table') . $this->baliseFinHtml('div');
    }
    /**
     * Renvoie une balise avec éventuellement sa class css si spécifiée dans le tableau _paramsCss
     * Enter description here ...
     * @param unknown_type $balise
     */
    private function baliseHtml($balise)
    {
        if (array_key_exists($balise, $this->_paramsCss)) {
            return '<' . $balise . ' class="' . $this->_paramsCss[$balise] . '">' . PHP_EOL;
        } else {
            return '<' . $balise . '>' . PHP_EOL;
        }
    }
    private function baliseFinHtml($balise)
    {
        return '</' . $balise . '>' . PHP_EOL;
    }
}