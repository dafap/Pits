<?php
/**
 * Génération de document pour email ou pdf
 *
 * La classe dérivée devra interpréter les bloc §if (condition)§ ... §endif§
 * et charger correctement les listes en définissant la méthode :
 * getFields($liste) qui lit les champs de array $liste dans la base
 *
 * @project pits
 * @package application/models/doc
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 24 mai 2011
 * @version $Id: $
 */

abstract class Pits_Document_Abstract
{
    /**
     * Nom du modèle de document
     * @var string $_filename
     */
    private $_filename;

    /**
     * Ressource représentant le pointeur de fichier modèle de document, ou FALSE
     * @var ressource $_file
     */
    private $_file = false;

    /**
     * Ligne lue dans le fichier $_file, ou null si c'est une ligne d'instruction
     * (conditionnelle, separateur, remarque)
     * @var null ou string $_buffer
     */
    protected $_buffer = null;

    /**
     * Si vrai (TRUE), les lignes suivantes sont dans un bloc §if(condition)§ ... §endif§
     * @var boolean $_bloc_if
     */
    private $_bloc_if = false;

    /**
     * C'est un booléen mis à jour par la méthode getFields()
     * Si on est dans un bloc §if(condition)§ ... §endif§
     * la ligne n'est donnée que si $_condition est vrai (TRUE)
     * @var boolean
     */
    private $_condition = false;

    /**
     * Liste de champs à écrire dans le texte, séparés par le séparateur
     * du type array([nomChamp] => [valeurChamp])
     * @var array(string) $_fields
     */
    private $_fields = array();

    /**
     * Chaîne de caractères séparatrice des champs pour écrire la liste des champs $_fields dans le texte
     * @var string $_separator
     */
    private $_separator = ' ';

    /**
     * A INITIALISER DANS LES CLASSES DERIVEES
     * Liste des champs de la table à traiter
     * @var array
     */
    protected $_tableFields;

    /**
     * A INITIALISER DANS LES CLASSES DERIVEES
     * Liste des valeurs correspondantes dans la table
     * @var array
     */
    protected $_tableValues;

    /**
     * Constructeur qui ouvre le fichier $filename qui se trouve dans le répertoire application/config/
     * Déclenche une exception si le fichier n'est pas trouvé
     * Le include_path doit pointer sur le dossier application
     * @param string $filename
     * @return void
     */
    public function __construct($filename)
    {
        $fc = Zend_Controller_Front::getInstance();
        $applicationPath = $fc->getParam('applicationPath');
        $this->_filename = $filename;
        $this->_file = fopen($applicationPath . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'doc' . DIRECTORY_SEPARATOR . $filename, 'r', true);
        if (!$this->_file) {
            throw new Pits_Document_Exception("Le ficher `$filename` modèle du document n'est pas trouvé dans le répertoire `application/configs/doc`.");
        }
    }

    /**
     * Destructeur qui ferme le fichier $filename
     * @return void
     */
    public function __destruct()
    {
        if ($this->_file) {
            fclose($this->_file);
            $this->_file = false;
        }
    }
    /**
     * Retourne vrai (TRUE) si on a atteint la fin du fichier $_file, sinon faux (FALSE)
     * Retourne vrai (TRUE) si le fichier n'est pas ouvert
     * @return boolean
     */
    protected function feof()
    {
        if ($this->_file) {
            return feof($this->_file);
        } else {
            return true;
        }
    }
    /**
     * Retourne une ligne lue dans le fichier après interprétation des instructions
     * conditionnelles §if ()§ et §endif§
     * @return null (pour ligne d'instruction conditionnelle) ou string $_buffer
     */
    protected function fgets()
    {
        $this->_buffer = fgets($this->_file);
        // traite les instructions §rem:, §separateur(..)§
        // et les conditionnelles [début et fin de bloc §if(condition)§ ... §endif§]
        if (!$this->_remarque() && !$this->_separateur() && !$this->_conditionnelle()) {
            // si on est dans une ligne à traiter (hors d'un bloc if ou condition vraie pour ce bloc)
            if (!$this->_bloc_if || $this->_condition) {
                // traite l'instruction §liste(champ1,[autrechamp,...])§
                $this->_liste();
                // traite l'instruction §dateFR(champ)§
                $this->_dateFR();
                // traite les champs particuliers
                $this->particularValues();
                // remplace les champs restants
                $this->catchValues();
            } else {
                // on est dans un bloc if et la condition est fausse
                $this->_buffer = null;
            }
        }
        return $this->_buffer;
    }
    /**
     * Si on rencontre une séquence §rem: ... § , on la saute
     */
    private function _remarque()
    {
        $debut = mb_stripos($this->_buffer,'§rem:');
        if ($debut === false) {
            return;
        }
        // il y a une remarque
        $this->_buffer = self::mb_trim($this->_buffer);
        $fin = mb_strpos($this->_buffer,'§', $debut + 5);
        // s'il n'y a pas de balise fin, on la rajoute en fin de ligne
        //echo "<p>début: $debut - fin: " . ($fin ? $fin : 'false') . "</p>";
        if (!$fin) {
            $this->_buffer .= '§';
        }
        // on supprime la remarque
        $this->_buffer = self::mb_trim(preg_replace('#§rem:(.+)§#i','',$this->_buffer));
        //echo "<p>$this->_buffer</p>";
        // si la ligne est vide, on devra la sauter
        if (empty($this->_buffer)) {
            $this->_buffer = null;
        }
        return is_null($this->_buffer);
    }
    /**
     * Retourne la valeur d'un champ de nom §field§
     * @param string $field
     */
    protected function getField($field)
    {
        if (!is_string($field)) {
            throw new Pits_Document_Exception("Le nom champ doit être de type `string`.");
        }
        $field = '#§' . $field . '§#';
        for ($i=0; $i < count($this->_tableFields); $i++) {
            if ($this->_tableFields[$i] == $field) break;
        }
        if ($i == count($this->_tableFields)) {
            throw new Pits_Document_Exception("Le champ `$field` de l'instruction §liste()§ est inconnu.");
        }
        return $this->_tableValues[$i];
    }
    /**
     * Retourne un tableau de valeurs des champs précisés dans la tableau $liste.
     * @param array $liste
     * @return array()
     */
    protected function getFields($liste)
    {
        $res = array();
        foreach ($liste as $field) {
            $res[] = $this->getField($field);
        }
        return $res;
    }
    /**
     * Si on rencontre une séquece §dateFR(...)§
     */
    private function _dateFR()
    {
        if (preg_match('#§dateFR\((.+)\)§#i', $this->_buffer, $matches) == 1) {
            $t = explode(',', $matches[1]);
            if (count($t) != 2) {
                throw new Pits_Document_Exception("Erreur de synthaxe dans l'instruction §dateFR(format,champDate)§.");
            }
            try {
                $laDate = $this->getField($t[1]);
                if (substr($laDate, 0, 10) == '0000-00-00') {
                    $this->_buffer = preg_replace('#§dateFR\((.+)\)§#i', ' ', $this->_buffer);
                } else {
                    $this->_buffer = preg_replace('#§dateFR\((.+)\)§#i',
                    Pits_Model_Format::date($t[0], $laDate),
                    $this->_buffer);
                }
            } catch (Exception $e) {
                $this->_buffer = preg_replace('#§dateFR\((.+)\)§#i',
                Pits_Model_Format::date($t[0], $this->getField($t[1]), 'fr_FR'),
                $this->_buffer);
            }
        }
    }

    /**
     * Interprète les lignes §if (...)§ et §endif§ et vide le buffer pour ces lignes
     */
    private function _conditionnelle()
    {
        if (empty($this->_buffer)) return;
        $tmp = $this->mb_trim($this->_buffer);
        if (empty($tmp)) return;
        if ($tmp == '§endif§') {
            $this->_bloc_if = false;
            $this->_condition = false;
            $this->_buffer = null;
        } elseif (preg_match('#^§if(.+)§$#i', $tmp, $matches) == 1) {
            $tmp = $this->mb_trim($matches[1]);
            if (preg_match('#^\((.+)\)$#i', $tmp, $matches) == 1) {
                $this->_bloc_if = true;
                $tmp = $this->mb_trim($matches[1]);
                $res = $this->getFields(array($tmp));
                if (isset($res[0])) {
                    $this->_condition = (bool) $res[0];
                } else {
                    throw new Pits_Document_Exception("\nErreur de lecture du champ `$tmp` dans la méthode getFields() de l'objet Pits_Document_Structure.\n");
                }

                $this->_buffer = null;
            } else {
                throw new Pits_Document_Exception("\nErreur de synthaxe `§if (...)§` dans le fichier `$this->_filename`. \nProblème de parenthèses.\n");
            }
        }
        return is_null($this->_buffer);
    }
    /**
     * Vérifie la présence d'une synthaxe §liste(...)§
     * et la remplace par les valeurs à inclure dans la ligne
     */
    private function _liste()
    {
        if (preg_match('#§liste\((.+)\)§#i', $this->_buffer, $matches) == 1) {
            $tmp = explode(',', $matches[1]);
            $this->_fields = $this->getFields($tmp);
            $this->_buffer = preg_replace('#§liste\((.+)\)§#i', $this->_getListe(), $this->_buffer);
        } else {
            $this->_fields = array();
        }
    }
    /**
     * Crée la chaine de remplacement d'une instruction §liste(...)§
     */
    private function _getListe()
    {
        $tmp = '';
        $sep = false;
        foreach($this->_fields as $key => $value) {
            if ($sep) $tmp .= $this->_separator;
            $tmp .= $value;
            $sep = true;
        }
        return $tmp;
    }
    /**
     * Traite l'instruction §separateur(...)§
     */
    private function _separateur()
    {
        if (preg_match('#§separateur\((.+)\)§#i', $this->_buffer, $matches) == 1) {
            $this->_separator = $matches[1];
            $this->_buffer = null;
        }
        return is_null($this->_buffer);
    }
    /**
     * A SURCHARGER DANS LA CLASSE DERIVEE
     * Pour les valeurs particulières, par exemple les valeurs codées
     */
    protected function particularValues()
    {
    }
    /**
     * Remplace dans le buffer les champs du tableau _tableFields par leur valeur contenue dans _tableValues
     */
    private function catchValues()
    {
        $this->_buffer = preg_replace($this->_tableFields, $this->_tableValues, $this->_buffer);
    }
    /**
     * Fonction qui rogne de chaque bouts (gauche et droite) la chaine de caractère en encodage multi-octet.
     * Généralement, elle se comporte exactement comme trim() ferait : par exemple si on lui fournit 'abc'
     * comme charlist tous les «a», «b» et «c» de la chaîne de caractères, avec, bien sûr, l'intérêt que
     * pouvez mettre des caractères Unicode dans le charlist.
     * Nous utilisons la classe générique PCRE pour rogner de manière unicode donc nous devons échapper les
     * caractères ^, \, - et] qui ont une signification particulière en PCRE.
     * Comme on peut s'y attendre, un \ seul dans le charlist sera interprété comme "anti-slash trim" (et
     * dûment échappé dans un double-\). Dans la plupart des cas, vous pouvez ignorer ce détail.
     * Comme bonus, nous permettons les caractères spéciaux de la classe PCRE (comme '\s') parce qu'ils
     * peuvent être extrêmement utiles lorsqu'on travaille en jeu de caractères multi-octets (UCS).
     * `\pZ`, par exemple, traite tous les caractères 'séparateurs' défini en Unicode.
     * Cela n'aurait pas de sens d'avoir deux caractères ou plus pour coder le même caractère. Par conséquent,
     * nous interprétons un double \ dans la charlist comme un simple \ dans la regex, permettant ainsi de
     * mélanger des caractères normaux et de caractères spéciaux de la classe PCRE.
     * *Attention* en utilisant ce bonus en PHP car PHP aussi interprète le caractère \ dans les chaînes.
     * Aussi, pour spécifier `\\s`dans une regex (qui convertira en caractère spécial `\s`pour rogner),
     * vous devrez mettre *4* `\` dans le code PHP, comme indiqué dans la valeur par défaut de $charlist.
     *
     * @param string
     * @param charlist list of characters to remove from the ends of this string.
     * @param boolean trim the left?
     * @param boolean trim the right?
     * @return String
     */
    public function mb_trim($string, $charlist='\\\\s', $ltrim=true, $rtrim=true)
    {
        $both_ends = $ltrim && $rtrim;

        $char_class_inner = preg_replace(
        array( '/[\^\-\]\\\]/S', '/\\\{4}/S' ),
        array( '\\\\\\0', '\\' ),
        $charlist
        );

        $work_horse = '[' . $char_class_inner . ']+';
        $ltrim && $left_pattern = '^' . $work_horse;
        $rtrim && $right_pattern = $work_horse . '$';

        if($both_ends)
        {
            $pattern_middle = $left_pattern . '|' . $right_pattern;
        }
        elseif($ltrim)
        {
            $pattern_middle = $left_pattern;
        }
        else
        {
            $pattern_middle = $right_pattern;
        }

        return preg_replace("/$pattern_middle/usSD", '', $string);
    }
}