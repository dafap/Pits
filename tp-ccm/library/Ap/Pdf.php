<?php
/**
 * Complète la classe Zend_Pdf pour mettre en page 
 * - un texte sur une ligne : drawText
 * - un texte sur plusieurs lignes : drawTextBox
 * - un texte sur plusieurs pages  : createPages
 * - une ligne de tableau avec du texte : drawTableLine
 *
 * @package library/Ap
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 4 oct. 2011
 * @version $Id: $
 */

//require_once 'Zend/Pdf.php';

class Ap_Pdf extends Zend_Pdf
{

    /**
     * L'encodage par défaut
     *
     * @var string
     */
    public static $encoding = 'UTF-8';

    /**
     * Texte aligné à gauche en précisant des coordonnées de l'espace autorisé
     *
     * @var string
     */
    const TEXT_ALIGN_LEFT = 'left';

    /**
     * Texte aligné à droite en précisant des coordonnées de l'espace autorisé
     *
     * @var string
     */
    const TEXT_ALIGN_RIGHT = 'right';

    /**
     * Texte centré horizontalement en précisant des coordonnées de l'espace autorisé
     *
     * @var string
     */
    const TEXT_ALIGN_CENTER = 'center';

    /**
     * Couleur HTML du texte
     * 
     * @var Zend_Pdf_Color
     */
    private $_textColor;
    /**
     * Epaisseur du trait de la bordure des cellules du tableau en points
     *
     * @var integer
     */
    private $_borderSize = 1;
    /**
     * Couleur HTML du trait de la bordure des cellules du tableau
     *
     * @var Zend_Pdf_Color
     */
    private $_borderColor;
    /**
     * Distance en points entre le trait de bordure de la cellule et le texte
     *
     * @var integer
     */
    private $_padding = 3;
    /**
     * Type de bordure et de trame. Peut prendre les valeurs suivantes :
     *   Zend_Pdf_Page::SHAPE_DRAW_STROKE,
     *   Zend_Pdf_Page::SHAPE_DRAW_FILL,
     *   Zend_Pdf_Page::SHAPE_DRAW_FILL_AND_STROKE
     * @var integer
     */
    private $_fillType = Zend_Pdf_Page::SHAPE_DRAW_STROKE;
    /**
     * Couleur de remplissage des cellules si besoin
     *
     * @var Zend_Pdf_Color 
     */
    private $_fillColor = 255;
    /**
     * Décrit le type de pointillé des lignes
     *   array(longueur_visible, longueur_invisible, longueur_visible, longueur_invisible, ...)
     *
     * @var array|Zend_Pdf_Page::LINE_DASHING_SOLID
     */
    private $_lineDashingPattern = Zend_Pdf_Page::LINE_DASHING_SOLID;
    /**
     * Décalage du début du trait visible par rapport au début de la ligne
     *
     * @var numeric
     */
    private $_phase = 0;
    /**
     * Tableau de largeurs des colonnes
     *
     * @var array
     */
    private $_colsWidths = array();
    /**
     * Tableau des alignements des colonnes
     * 
     * @var array
     */
    private $_colsAlign = array();
    /**
     * Marge de droite en points
     *
     * @var integer
     */
    private $_marginRigth = 0;
    /**
     * Extension de la méthode drawText pour gérer les alignements horizontaux
     *
     * @param Zend_Pdf_Page $page La page pdf dans laquelle le texte sera écrit
     * @param string $text        Le texte
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $position       Par défaut, le texte est aligné à gauche
     * @param string $encoding    Si null alors self::$encoding est utilisé (ici UTF-8)
     *
     * @return Zend_Pdf_Page      La page pdf dans laquelle le texte a été écrit
     */
    public static function drawText(Zend_Pdf_Page $page, $text, $x1, $y1, $x2 = null, $position = self::TEXT_ALIGN_LEFT, $encoding = null)
    {
        if ($encoding == null) $encoding = self::$encoding;

        $bottom = $y1; // could do the same for vertical-centering
        switch ($position) {
            case self::TEXT_ALIGN_LEFT :
                $left = $x1;
                break;
            case self::TEXT_ALIGN_RIGHT :
                if (null === $x2) {
                    throw new Ap_Exception ( "Ne peut pas aligner le texte à droite si x2 n'est pas donnée." );
                }
                $textWidth = self::getTextWidth($text, $page);
                $left = $x2 - $textWidth;
                break;
            case self::TEXT_ALIGN_CENTER :
                if (null === $x2) {
                    throw new Ap_Exception ( "Ne peut pas centrer le texte si x2 n'est pas donnée." );
                }
                $textWidth = self::getTextWidth($text, $page);
                $left =  $x1 + ( ($x2 - $x1) - $textWidth ) /2;
                break;
            default :
                throw new Ap_Exception ("Ce type d'alignement est invalide: \"$position\"");
        }

        // display multi-line text
        //Zend_Debug::dump(array($x1, $x2, $textWidth, $text, $left, $y1, $encoding)); exit;
        $page->drawText($text, $left, $y1, $encoding);
        return $page;
    }

    /**
     * Trace un texte dans une zone en gérant l'intégrité des mots (retour à la ligne s'il le faut)
     *
     * @param Zend_Pdf_Page $page   La page pdf dans laquelle sera écit le texte
     * @param string $text          Le texte
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $position         Par défaut, le texte est aligné à gauche
     * @param float $lineHeight     La hauteur des lignes, par defaut 1.1 = 110% de la taille de la police
     * @param string $encoding      Si null alors self::$encoding est utilisé (ici UTF-8)
     *
     * @return integer bottomPosition  La valeur de la prochaine coordonnée y1 à utiliser
     */
    public static function drawTextBox(Zend_Pdf_Page $page, $text, $x1, $y1, $x2, $position = self::TEXT_ALIGN_LEFT, $lineHeight = 1.1, $encoding = null)
    {
        if ($encoding == null) $encoding = self::$encoding;

        $lines = explode(PHP_EOL, $text);

        $bottom = $y1;
        $lineHeight = $page->getFontSize() * $lineHeight;
        foreach ($lines as $line) {
            preg_match_all('/([^\s]*\s*)/i', $line, $matches);

            $words = $matches[1];

            $lineText = '';
            $lineWidth = 0;
            foreach( $words as $word ){
                $wordWidth = self::getTextWidth($word, $page);

                if ( $lineWidth + $wordWidth < $x2 - $x1) {
                    $lineText .= $word;
                    $lineWidth += $wordWidth;
                } else {
                    self::drawText($page, $lineText, $x1, $bottom, $x2, $position, $encoding);
                    $bottom -= $lineHeight;
                    $lineText = $word;
                    $lineWidth = $wordWidth;
                }
            }

            self::drawText($page, $lineText, $x1, $bottom, $x2, $position, $encoding);
            $bottom -= $lineHeight;
        }

        return $bottom;
    }

    /**
     * Créer des pages à partir d'un texte donné en utilisant le modèle
     *
     * @param Zend_Pdf_Page $template    Le modèle pour la création de pages
     * @param string $text               Le texte
     * @param array $margins             array(top, right, bottom, left) Les marges des pages pour ce document
     * @param align $position            self::TEXT_ALIGN_LEFT
     * @param lineheight $lineHeight     La hauteur des lignes, par defaut 1.1 = 110% de la taille de la police
     * @param string $encoding           Si null alors self::$encoding est utilisé (ici UTF-8)
     * @return array $pages              Tableau des pages
     */
    public static function createPages(Zend_Pdf_Page $template, $text, $margins=array(40,28,40,28), $position = self::TEXT_ALIGN_LEFT, $lineHeight = 1.1, $encoding = null)
    {
        if( $encoding == null ) $encoding = self::$encoding;

        $pages = array();
        $currentPage = null;

        $lines = explode("\n", $text);

        $lineHeight = $template->getFontSize() * $lineHeight;
        $x1 = $margins[1];
        $x2 = $template->getWidth() - $margins[3];
        $y1 = $template->getHeight() - $margins[0] - $lineHeight;
        $y2 = $margins[2];

        $bottom = $y1;
        foreach ($lines as $line) {
            if ($currentPage == null || $bottom <= $y2) {
                $pages[] = $currentPage = new Zend_Pdf_Page($template);
                $currentPage->setFont($template->getFont(), $template->getFontSize());
                $bottom = $y1;
            }

            preg_match_all('/([^\s+\-,.\\/]*[\s+\-,.\\/]*)/i', $line, $matches);

            $words = $matches[1];

            $lineText = '';
            $lineWidth = 0;
            foreach ($words as $word) {
                $wordWidth = self::getTextWidth($word, $currentPage);

                if ($lineWidth + $wordWidth < $x2 - $x1) {
                    $lineText .= $word;
                    $lineWidth += $wordWidth;
                } else {
                    self::drawText($currentPage, $lineText, $x1, $bottom, $x2, $position, $encoding);
                    $bottom -= $lineHeight;
                    $lineText = $word;
                    $lineWidth = $wordWidth;
                }
            }

            self::drawText($currentPage, $lineText, $x1, $bottom, $x2, $position, $encoding);
            $bottom -= $lineHeight;
        }

        return $pages;
    }

    /**
     * Renvoie la longueur en points du texte donné
     *
     * @param string                               $text
     * @param Zend_Pdf_Resource_Font|Zend_Pdf_Page $resource
     * @param string|null                          $encoding	UTF-8 par défaut
     * @return double
     */
    public static function getTextWidth($text, $resource, $encoding = null)
    {
        if ($encoding == null) $encoding = self::$encoding;

        if ($resource instanceof Zend_Pdf_Page) {
            $font = $resource->getFont();
            $fontSize = $resource->getFontSize();
        } elseif ($resource instanceof Zend_Pdf_Resource_Font) {
            $font = $resource;
            if ($fontSize === null ) throw new Ap_Exception('La taille de la police est inconnue.');
        }

        if (!$font instanceof Zend_Pdf_Resource_Font) {
            throw new Ap_Exception('La ressource donnée est invalide.');
        }

        $drawingText = iconv ('', $encoding, $text);
        $characters = array();
        for ($i = 0; $i < strlen ($drawingText); $i ++) {
            $characters[] = ord($drawingText[$i]);
        }
        $glyphs = $font->glyphNumbersForCharacters($characters);
        $widths = $font->widthsForGlyphs($glyphs);
        $textWidth = (array_sum($widths) / $font->getUnitsPerEm()) * $fontSize;
        return $textWidth;
    }

    /**
     * Initialise la bordure et la trame du tableau
     *
     * @param integer $borderSize         Epaisseur du trait (1 pt par défaut)
     * @param Zend_Pdf_Color $borderColor Couleur du trait (noir par défaut)
     * @param integer $padding            Espace entre le trait et le texte (3 pts par défaut)
     * @param integer $fillType           Indique si on trace le contour ou si on remplit (contour par défaut). Prend ses valeur dans
     *                                    Zend_Pdf_Page::SHAPE_DRAW_STROKE, SHAPE_DRAW_FILL, SHAPE_DRAW_FILL_AND_STROKE
     * @param Zend_Pdf_Color $fillColor   Couleur du remplissage (pas de couleur par défaut)
     * @param array|0 $lineDashingPattern Le pattern décrit les pointillés array(longueur_visible, longueur_invisible, longueur_visible, longueur_invisible, ...)
     *                                    (ligne continue par défaut)
     * @param numeric $phase              Décalage par rapport au début de la ligne (pas de décalage par défaut)
     */
    public function initBordureTrame($textColor = 'black', $borderSize = 1, $borderColor = 'black', $padding = 3, $fillType = Zend_Pdf_Page::SHAPE_DRAW_STROKE, $fillColor = 'white', $lineDashingPattern = Zend_Pdf_Page::LINE_DASHING_SOLID, $phase = 0)
    {
        $this->_textColor = new Zend_Pdf_Color_Html($textColor);
        $this->_borderSize = $borderSize;
        $this->_borderColor = new Zend_Pdf_Color_Html($borderColor);
        $this->_padding = $padding;
        $this->_fillType = $fillType;
        $this->_fillColor = new Zend_Pdf_Color_Html($fillColor);
        $this->_lineDashingPattern = $lineDashingPattern;
        $this->_phase = $phase;
    }
    /**
     * Initialise les largeurs de colonnes
     *
     * @param array $colsWidth
     * @param array $colsAlign
     */
    public function setColsWidths($colsWidth, $colsAlign)
    {
        $this->_colsWidths = $colsWidth;
        $this->_colsAlign = $colsAlign;
    }
    /**
     * Initialise la marge de droite en points
     *
     * @param integer $width
     */
    public function setMarginRigth($width)
    {
        $this->_marginRigth = $width;
    }
    /**
     * Trace une ligne d'un tableau et y place du texte
     *
     * @param Zend_Pdf_Page $page        La page pdf dans laquelle sera tracé le tableau
     * @param integer $x                 Le tableau des coordonnées de la ligne
     * @param integer $y                 L'ordonnée de la ligne (base)
     * @param array $colsText            Le tableau de textes à placer dans les colonnes
     * @param array $colsWidths          Tableau des largeurs des colonnes en points
     * @param lineheight $lineHeight     La hauteur des lignes, par defaut 1.1 = 110% de la taille de la police
     * @param string $encoding           Si null alors self::$encoding est utilisé (ici UTF-8)
     *
     * @return integer                   La valeur de la prochaine coordonnée y à utiliser
     */
    public function drawTableLine(Zend_Pdf_Page $page, $x, $y, $colsText, $colsWidths=null, $lineHeight = 1.1, $encoding = null)
    {
        if ($encoding == null) $encoding = self::$encoding;

        if (!is_array($colsWidths) && !is_null($colsWidths)) {
            throw new Ap_Exception("Le paramètre de largeurs des colonnes est incorrect." . PHP_EOL . var_export($colsWidths, true));
        }
        if (!is_null($colsWidths)) {
            $this->setColsWidths($colsWidths);
        }
        // contrôle des largeurs de colonnes
        $nbCols = count($colsText);
        if (empty($this->_colsWidths)) {
            $width = (int) (($page->getWidth() - $x - $this->_marginRigth) / $nbCols);
            for ($j=0; $j<$nbCols; $j++) $this->_colsWidths[] = $width;
        } elseif ($nbCols > count($this->_colsWidths)) {
            throw new Ap_Exception("Le nombre de colonnes de la ligne donnée est incorrect.");
        }
        // préparation du contenu des cellules ($cellsText) et calcul de la hauteur de la ligne ($hCell)
        $lineHeight = $page->getFontSize() * $lineHeight;
        $hCell = 0;
        $cellsText = array();
        $rang = -1;
        foreach ($colsText as $colText) {
            $rang++;
            $widthZone = $this->_colsWidths[$rang] - 2 * $this->_padding; // largeur maxi de la zone d'écriture
            $cellsText[$rang] = array();
            $h = 2 * $this->_padding;
            $lines = explode(PHP_EOL, $colText);
            foreach ($lines as $line) {
                preg_match_all('/([^\s]*\s*)/i', $line, $matches);
                $words = $matches[1];
                $lineText = '';
                $lineWidth = 0;
                foreach( $words as $word ){
                    $wordWidth = self::getTextWidth($word, $page);
                    if ( $lineWidth + $wordWidth < $widthZone) {
                        $lineText .= $word;
                        $lineWidth += $wordWidth;
                    } else {
                        $cellsText[$rang][] = $lineText;
                        $h += $lineHeight;
                        $lineText = $word;
                        $lineWidth = $wordWidth;
                    }
                }
                $cellsText[$rang][] = $lineText;
                $h += $lineHeight;
            }
            if ($hCell < $h) $hCell = $h;
        }
        // trace des rectangles
        $page->setFillColor($this->_fillColor);
        $page->setLineColor($this->_borderColor);
        $page->setLineWidth($this->_borderSize);
        $page->setLineDashingPattern($this->_lineDashingPattern, $this->_phase);
        
        $bottom = $y;
        $top = $bottom - $hCell;
        $x1 = $x;
        //Zend_Debug::dump(array($x1, $bottom, $x2, $top, $this->_fillType)); exit;
        foreach ($this->_colsWidths as $width) {
            $x2 = $x1 + $width;
            $page->drawRectangle($x1, $bottom, $x2, $top, $this->_fillType);
            $x1 = $x2;
        }
        // trace le texte
        $page->setFillColor($this->_textColor);
        $x2 = $x;
        $rang = -1;
        foreach ($cellsText as $cellText) {
            $rang++;
            $x1 = $x2 + $this->_padding;
            $x2 += $this->_colsWidths[$rang];
            $position = $this->_colsAlign[$rang];
            $bottom = $y - $lineHeight;
            foreach ($cellText as $lineText) {
                self::drawText($page, $lineText, $x1, $bottom, $x2 - $this->_padding, $position, $encoding);
                $bottom -= $lineHeight;
            }
            //$x1 = $x + $this->_colsWidths[$rang] + $this->_padding;
        }
        // renvoie la prochaine valeur de $y
        return $top;
    }
}