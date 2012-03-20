<?php
/**
 * Affichage d'une liste
 *
 * Cette aide d'action permet de dessiner une liste à partir d'un tableau associatif
 *  $this->view->liste =array('alias' => array() , 'data' => array(array()), 'separator' => string)
 * - les alias sont les titres des colonnes
 * - les data sont dans un tableau à deux dimensions. Si une cellule contient un tableau, il sera transformé en chaine en
 *   utilisant le séparateur 'separator' => string. S'il n'y a pas de separator, <br /> est utilisé.
 *
 * @project pits
 * @package application/controllers
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 1 mars 2011
 * @version $Id: $
 */

class Zend_View_Helper_MakeListe extends Zend_View_Helper_Abstract
{
    /**
     * buffer de construction de la liste
     * @var string
     */
    private $_flux;
    /**
     * Drapeau pour afficher ou non le nombre de lignes de la liste
     * @var boolean
     */
    private $_afficheNbLignes;
    /**
     * Drapeau pour indiquer la présence de coches et pour indiquer la présence des 2 cotés
     */
    private $_hasCoche;
    private $_synchro;
    /**
     * Colonnes 'uid' ou 'dn' (invisibles) ou colonne index
     */
    private $_uid; // colonne à sauter dans l'affichage de la ligne
    protected $_index; // colonne index pour les liens
    /**
     * Styles des objets
     */
    private $_styleButtons;
    private $_styleTable;

    /**
     * tableau associatif des actions possibles dans la liste (coche, ajout, modif, suppr ...)
     * classées par position : 'agauche','adroite','poursel','enbas'
     * pour 'coche' il s'agit de l'action de la balise <form>
     * pour les autres ce sont des liens sur des scripts
     * @var array
     */
    protected $actions = array();

    /**
     * Dessine la liste avec ses groupes de boutons et ses lignes de messages
     * Par défaut,
     * - la liste n'affiche rien si elle est vide,
     * - la liste ne permet pas d'éditer une ligne.
     * Pour éditer une ligne, il faut surcharger la méthode editLigne()
     *
     * @param array $agauche
     * @param array $adroitre
     * @param array $poursel
     * @param array $enbas
     * @param boolean $afficheNbLignes (true par défaut)
     * @param boolean $listeNonVide (true par défaut)
     * @param integer|null $idEditLigne (null par défaut)
     * @param string $styleTable
     * @param string $styleButtons
     * @return string
     */
    public function makeListe($agauche, $adroitre, $poursel, $enbas,
    $afficheNbLignes = true, $listeNonVide=true, $idEditLigne=null,
    $styleTable = 'liste', $styleButtons = 'bouton')
    {
        $this->init($agauche, $adroitre, $poursel, $enbas, $afficheNbLignes, $styleTable, $styleButtons);
        // s'il faut une liste non vide et qu'elle est vide
        if ($listeNonVide && $this->isVide()) return '';
        // avant la liste et le groupe de boutons
        fputs($this->_flux, $this->audessusListe());
        // groupe de boutons au dessus de la liste (ajout, retour, ...)
        fputs($this->_flux, $this->groupeButtonsEnbas());
        // affiche éventuellement le nombre de lignes
        if ($this->_afficheNbLignes) {
            fputs($this->_flux, $this->nbLignes());
        }
        // s'il y a des coches à gauche il faut définir un formulaire
        $ag = $ad = 0;
        $this->_synchro = false;
        if (array_key_exists('coche', $this->actions['agauche'])) {
            $ag = 1;
            $actionForm = $this->actions['agauche']['coche'];
        }
        if (array_key_exists('coche', $this->actions['adroite'])) {
            $ad = 1;
            $actionForm = $this->actions['adroite']['coche'];
        }
        // s'il y a des coches à gauche ou à droite
        if ($ag==1 || $ad==1) {
            $this->_hasCoche = true;
            $this->_synchro = $ag == $ad; // coches à synchroniser 'gauche' et 'droite'
            fputs($this->_flux, PHP_EOL . '<form name="liste" method="post" action="' . $actionForm . '">');
            // Mettre les boutons 'poursel'
            fputs($this->_flux, $this->groupeButtonsPoursel());
        }
        // Entre les boutons de sélection du haut et le tableau, balise <table>
        fputs($this->_flux, $this->hautTableau());
        // Constitution du tableau
        fputs($this->_flux, '<table class="' . $this->_styleTable . ' ' . $this->_styleButtons . '">' . PHP_EOL);
        // en-tête du tableau
        fputs($this->_flux, $this->makeTHead());
        // corps du tableau
        fputs($this->_flux, $this->makeTBody($idEditLigne));
        // Fin du tableau principal
        fputs($this->_flux, '</table>');
        // Entre les boutons de sélection du bas et le tableau, balise </table>
        fputs($this->_flux, $this->basTableau());
        // s'il y a des coches à gauche ou à droite
        if ($this->_hasCoche) {
            // Remettre les boutons 'poursel'
            fputs($this->_flux, $this->groupeButtonsPoursel());
            // Hidden pour connaitre l'action 'op' dans un setHiddenBeforeSubmit()
            fputs($this->_flux, $this->view->formHidden('op'));
        }
        // groupe de boutons au dessous de la liste (idem au dessus)
        fputs($this->_flux, $this->groupeButtonsEnbas());
        // après la liste et le groupe de boutons
        fputs($this->_flux, $this->audessousListe());

        // récupération des données et envoi
        rewind($this->_flux);
        $content = stream_get_contents($this->_flux);
        fclose($this->_flux);
        return  $content;
    }
    /**
     * Contrôle la structure de view->liste
     * @return boolean
     */
    private function hasListe()
    {
        return isset($this->view->liste)
        && array_key_exists('data', $this->view->liste)
        && array_key_exists('alias', $this->view->liste)
        && is_array($this->view->liste['data'])
        && is_array($this->view->liste['alias']);
    }
    /**
     * Initialise la structure de liste. Les tableaux liste et alias doivent
     * être définis dans view
     *
     * @param array $agauche
     * @param array $adroitre
     * @param array $poursel
     * @param array $enbas
     * @param boolean $nbLignes (true par défaut)
     * @param string $styleTable ('liste' par défaut)
     * @param string $styleButtons ('bouton' par défaut
     */
    private function init($agauche, $adroite, $poursel, $enbas, $afficheNbLignes = true, $styleTable = 'liste', $styleButtons = 'bouton')
    {
        if (!$this->hasListe()) {
            throw new Sarapis_Exception('Aide de vue makeListe::init() - Il faut définir liste dans view sous la forme array(\'data\' => array(), \'alias\' => array()) !');
        }
        $this->_afficheNbLignes = $afficheNbLignes;
        $this->_styleButtons = $styleButtons;
        $this->_styleTable = $styleTable;

        $this->_hasCoche = false;

        $this->actions = array('agauche' => array(), 'adroite' => array(), 'poursel' => array(), 'enbas' => array());
        if (is_array($agauche)) {
            $this->actions['agauche'] = $agauche;
        }
        if (is_array($adroite)) {
            $this->actions['adroite'] = $adroite;
        }
        if (is_array($poursel)) {
            $this->actions['poursel'] = $poursel;
        }
        if (is_array($enbas)) {
            $this->actions['enbas'] = $enbas;
        }
        try {
            $this->_flux = fopen('php://temp', 'r+');
        } catch (Exception $e) {
            throw new Sarapis_Exception('Impossible de générer le flux temporaire.');
        }
    }
    /**
     * Si true, makeListe affichera le nombre de lignes de la liste
     *
     * @param boolean $ouinon
     */
    public function setNbLignes($ouinon)
    {
        $this->_afficheNbLignes = $ouinon;
    }
    /**
     * Place un bloc html avant la liste et ses boutons ('enbas' qui sont aussi en haut)
     * Par défaut, n'affiche rien
     */
    protected function audessusListe()
    {
        $flux = '';
        if (isset($this->view->msgAuDessusListe)) {
            $flux = '<p class="audessus">' . $this->view->msgAuDessusListe . '</p>';
        }
        return $flux;
    }
    /**
     * Place un bloc html après la liste et ses boutons 'enbas'
     * Par défaut, n'affiche rien
     */
    protected function audessousListe()
    {
        $flux = '';
        if (isset($this->view->msgAuDessousListe)) {
            $flux = '<p class="audessous">' . $this->view->msgAuDessousListe . '</p>';
        }
        return $flux;
    }
    /**
     * Place un bloc html juste avant la balise <table>
     * Par défaut, n'affiche rien
     */
    protected function hautTableau()
    {
        $flux = '';
        if (isset($this->view->msgHautTableau)) {
            $flux = '<p class="hauttableau">' . $this->view->msgHautTableau . '</p>';
        }
        return $flux;
    }
    /**
     * Place un bloc html juste après la balise </table>
     * Par défaut, n'affiche rien
     */
    protected function basTableau()
    {
        $flux = '';
        if (isset($this->view->msgBasTableau)) {
            $flux = '<p class="bastableau">' . $this->view->msgBasTableau . '</p>';
        }
        return $flux;
    }
    /**
     * Place le bloc de boutons définis dans l'attribut $action['enbas']
     */
    protected function groupeButtonsEnbas()
    {
        $enbas = $this->actions['enbas'];
        if (empty($enbas)) return;
        $flux = '<div class="bthautbas">' . PHP_EOL;
        $flux .= '<table class="sansbord ' . $this->_styleButtons . '">';
        $sep = PHP_EOL . '<tr>' . PHP_EOL . '<td>' . PHP_EOL;
        foreach ($enbas as $key => $value) {
            $flux .= $sep;
            $flux .= $this->view->lien($this->_styleButtons, $key, $value);
            $sep = PHP_EOL . '</td>'. PHP_EOL . '<td>' . PHP_EOL;
        }
        $flux .= PHP_EOL . '</td>' . PHP_EOL . '</tr>' . PHP_EOL . '</table>' . PHP_EOL . '</div>';
        return $flux;
    }
    /**
     * Place le bloc de boutons définis dans l'attribut $action['poursel']
     */
    protected function groupeButtonsPoursel()
    {
        $actions = $this->actions['poursel'];
        $flux = PHP_EOL . '<table class="sansbord ' . $this->_styleButtons . '">' . PHP_EOL . '<tr>' . PHP_EOL . '<td>';
        $flux .= $this->view->lien($this->_styleButtons, 'Tout cocher', ' ', "javascript:setCheckboxes('liste','coche[]',true);return false;");
        // sur 2 lignes pour 'Tout cocher' et 'Tout décocher'
        $sep = '</td>' . PHP_EOL . '<td rowspan="2">&nbsp;</td>' . PHP_EOL . '<td rowspan="2">';
        foreach ($actions as $key => $value) {
            $flux .= $sep;
            $flux .= $this->view->lien($this->_styleButtons, $value, "javascript:setHiddenBeforeSubmit('liste','op','" . $key . "')");
            $sep = PHP_EOL . '</td><td rowspan="2">';
        }
        $flux .= PHP_EOL . '</td>' . PHP_EOL . '</tr>' . PHP_EOL;
        $flux .= '<tr>' . PHP_EOL . '<td>';
        $flux .= $this->view->lien($this->_styleButtons, 'Tout d&eacute;cocher', ' ', "javascript:setCheckboxes('liste','coche[]',false);return false;");
        $flux .= PHP_EOL . '</td>' . PHP_EOL . '</tr>';
        $flux .= PHP_EOL . '</table>';
        return $flux;
    }
    /**
     * Place le bloc de boutons définis dans l'attribut $action['agauche'] ou $action['adroite']
     *
     * @param string $pos (prend les valeurs 'agauche' ou 'adroite')
     * @param integer $numLigne
     */
    protected function groupeButtonsLigne($pos, $numLigne)
    {
        if (!is_string($pos) || ($pos != 'agauche' && $pos != 'adroite') || !is_int($numLigne)) {
            throw new Sarapis_Exception('Appel incorrect de makeListe::groupeButtonsLigne()');
        }
        static $g=0, $d=0;
        $flux = '';
        foreach ($this->actions[$pos] as $key => $value) {
            if ($key == 'coche') {
                if ($pos == 'agauche') {
                    $h1 = 'cocheg' . $g++;
                    $h2 = 'coched' . $d;
                } else {
                    $h1 = 'coched' . $d++;
                    $h2 = 'cocheg' . ($g-1);
                }
                //Zend_Debug::dump($h1, 'h1'); exit;
                $flux .= '<td>';
                $flux .= $this->view->formCheckbox('coche[]', $this->view->liste['data'][$numLigne][$this->_index], array('id' => $h1, 'onclick' => "cocheSynchro('liste','$h1','$h2')"));
                $flux .= '</td>' . PHP_EOL;
            } else {
                $flux .= '<td>';
                $flux .= $this->view->lien($this->_styleButtons, $key, $this->makeHref($value, $numLigne, $pos));
                $flux .= '</td>' . PHP_EOL;
            }
        }
        return $flux;
    }
    /**
     * Construit l'url à appeler pour un lien d'une ligne
     * A surcharger si nécessaire
     *
     * @param string $urlBase
     * @param integer $numLigne
     * @param string $pos (prend les valeurs 'agauche' ou 'adroite')
     * @return string
     */
    protected function makeHref($urlBase, $numLigne, $pos)
    {
        return $urlBase . $this->view->liste['data'][$numLigne][$this->_index];
    }
    protected function makeTHead()
    {
        $flux = '<thead>' . PHP_EOL . '<tr>' . PHP_EOL;
        $sep = '<th>';
        // colonnes des actions de gauche
        for ($j=0; $j < count($this->actions['agauche']); $j++) {
            $flux .= $sep;
            $sep = '</th>' . PHP_EOL . '<th>';
        }
        // colonnes des champs à afficher (tableau alias)
        // Si un champ porte les noms 'uid' ou 'dn' il sert de paramètre pour les actions,
        // si non, c'est le premier champ, numéro 0, qui servira de paramètre.
        // Les champs 'uid' et 'dn' ne sont pas affichés alors que le champ 0 l'est.
        // Il est possible de dupliquer un champ avec un alias. Ainsi, on peut
        // avoir un champ 'uid' ou 'dn' et le même sous un autre nom.
        $this->_uid = -1; // rang du champ uid ou dn
        $this->_index = 0;
        for ($j = 0; $j < count($this->view->liste['alias']); $j++, next($this->view->liste['alias'])) {
            if (current($this->view->liste['alias']) == 'uid' || current($this->view->liste['alias']) == 'dn') {
                $this->_uid = $this->_index = key($this->view->liste['alias']);
            } else {
                $flux .= $sep . current($this->view->liste['alias']);
                $sep = '</th>'. PHP_EOL . '<th>';
            }
        }
        // colones des actions de droite
        for ($j=0; $j < count($this->actions['adroite']); $j++) {
            $flux .= $sep;
            $sep = '</th>' . PHP_EOL . '<th>';
        }
        $flux .= '</th>' . PHP_EOL . '</tr>' . PHP_EOL . '</thead>' . PHP_EOL;
        return $flux;
    }
    protected function makeTBody($idEditLigne = null)
    {
        $flux = '<tbody>' . PHP_EOL;
        for ($j = 0; $j < count($this->view->liste['data']); $j++) {
            $flux .= '<tr class="' . ($j % 2 ? 'odd' : 'even') . '">' . PHP_EOL;
            if (!is_null($idEditLigne) && $this->view->liste['data'][$this->_index] == $idEditLigne) {
                $flux .= $this->editLigne($j);
            } else {
                $flux .= $this->makeLigne($j);
            }
            $flux .= '</tr>' . PHP_EOL;
        }
        $flux .= '</tbody>' . PHP_EOL;
        return $flux;
    }
    /**
     * Construit une ligne en édition. A surcharger car, par défaut, ne fait rien.
     */
    protected function editLigne($numLigne)
    {
        return $this->makeLigne($numLigne);
    }
    /**
     * Construit une ligne en lecture avec ses boutons
     */
    protected function makeLigne($numLigne)
    {
        $separator = array_key_exists('separator',$this->view->liste) ? $this->view->liste['separator'] : '<br />';
        // colonnes des actions de gauche
        $flux = $this->groupeButtonsLigne('agauche', $numLigne);
        // colonnes des champs à afficher
        for ($j = 0; $j < count($this->view->liste['data'][$numLigne]); $j++, next($this->view->liste['data'][$numLigne])) {
            if (key($this->view->liste['data'][$numLigne]) == $this->_uid) continue; // champ invisible
            $flux .= '<td>';
            if (is_array(current($this->view->liste['data'][$numLigne]))) {
                $sep = '';
                foreach (current($this->view->liste['data'][$numLigne]) as $value) {
                    if (is_array($value)) {
                        foreach ($value as $v1) {
                            $flux .= $sep . $v1;
                            $sep = $separator;
                        }
                    } else {
                        $flux .= $sep . $value;
                        $sep = $separator;
                    }
                }
            } else {
                $flux .= current($this->view->liste['data'][$numLigne]);
            }
            $flux .= '</td>' . PHP_EOL;
        }
        // colonnes des actions de droite
        $flux .= $this->groupeButtonsLigne('adroite', $numLigne);
        return $flux;
    }
    /**
     * Vérifie si la liste est vide
     */
    protected function isVide()
    {
        return count($this->view->liste['data']) == 0;
    }
    /**
     *
     */
    protected function nbLignes()
    {
        $n = count($this->view->liste['data']);
        return '<b>Cette liste comporte ' . $n . ' ligne' . ($n > 1 ? 's' : '') . '</b><br />' . PHP_EOL;
    }
}