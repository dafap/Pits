<?php
/**
 * Les outils pour paybox
 *
 * @project télé-paiement CCM
 * @package application/models/paybox
 * @author  DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date    2 juin 2011
 * @version $Id: $
 */


class Tp_Model_Paybox
{
    /**
     * La config du fichier /application/configs/application.ini
     * @var array()
     */
    private $_config;
    /**
     * Tableau des paramètres getRequest()->getParams() ou null
     * @var array
     */
    private $_params;
    /**
     * Adresse ip du client
     * @var string
     */
    private $_ip;
    /**
     * Décomposition de maref en tableau
     * @var array
     */
    private $_refs;

    public function __construct($config, $params = array(), $ip = '')
    {
        $this->_config = $config;
        $this->setParams($params);
        $this->_ip = $ip;
    }
    /**
     * Iitialise le tableau _params et le tableau _refs
     * @param array $params
     */
    public function setParams($params)
    {
        $this->_params = $params;
        if (array_key_exists('maref', $params)) {
            $this->_refs = explode('|', $params['maref']);
        } else {
            $this->_refs = array();
        }
    }
    /**
     * Renvoie l'idenifiant du service de facturation (TS pour `transports scolaires`)
     * @return string|false
     */
    public function getService()
    {
        if (array_key_exists(0, $this->_refs)) {
            return $this->_refs[0];
        } else {
            return false;
        }
    }
    /**
     * Renvoie le montant en centimes de 'maref'
     * @return int|false
     */
    public function getMontantRef()
    {
        if (array_key_exists(2, $this->_refs)) {
            return $this->_refs[2];
        } else {
            return false;
        }
    }
    /**
     * Renvoie le userId de 'maref'
     * @return int
     */
    public function getUserIdRef()
    {
        if (array_key_exists(3, $this->_refs)) {
            return $this->_refs[3];
        } else {
            return false;
        }
    }
    /**
     * Renvoie le tableau des factureId de 'maref'
     * @return array
     */
    public function getFactureIdsRef()
    {
        if (array_key_exists(4, $this->_refs)) {
            return array_slice($this->_refs, 4);
        } else {
            return false;
        }
    }
    /**
     * Renvoie la date de la transaction
     *  $format est null => au format 'YYYY-MM-dd HH:mm:ss'
     *  $format 'dd/MM/YYYY HH:mm:ss' traité
     *
     * @param null|string $format
     * @return string
     */
    public function getDatetrans($format = null)
    {
        if (array_key_exists('datetrans', $this->_params) && array_key_exists('heuretrans', $this->_params)) {
            $dateP = $this->_params['datetrans'];
            $heureP = $this->_params['heuretrans'];
            $an = substr($dateP, 4);
            $mois = substr($dateP, 2, 2);
            $jour = substr($dateP, 0, 2);
            if (is_null($format)) {
                return  $an . '-' . $mois . '-' . $jour . ' ' . $heureP ;
            } else {
                return Tp_Model_Format::date($format, "$jour/$mois/$an $heureP", 'fr_FR');
            }
        } else {
            if (is_null($format)) {
                $format = 'YYYY-MM-dd HH:mm:ss';
            }
            return Tp_Model_Format::date($format);
        }
    }
    /**
     * Retourne l'identifaint de transaction de paybox
     * @return string
     */
    public function getIdtrans()
    {
        if (array_key_exists('idtrans', $this->_params)) {
            return $this->_params['idtrans'];
        } else {
            return '';
        }
    }
    public static function getErreurListe($erreur = null)
    {
        $res = array(
                'tous'  => 'Toutes les transactions',
                'non'   => 'Toutes les transactions réussies',
                'oui'   => 'Toutes les transactions en échec',
                '00000' => '00000: Opération réussie.',
                '00001' => '00001: La connexion au centre d\'autorisation a échoué.',
                '00003' => '00003: Erreur Paybox',
                '00004' => '00004: Numéro de porteur ou cryptogramme visuel invalide.',
                '00006' => '00006: Accès refusé  site/rang/identifiant incorrects.',
                '00008' => '00008: Date de validité incorrecte.',
                '00009' => '00009: Erreur de création d\'un abonnement.',
                '00010' => '00010: Devise inconnue.',
                '00011' => '00011: Montant incorrect.',
                '00015' => '00015: paiement déjà effectué.',
                '00016' => '00016: Abonné déjà existant.',
                '00021' => '00021: Carte non autorisée.',
                '00029' => '00029: Carte non conforme.',
                '00030' => '00030: Temps d\'attente trop long.',
                '00033' => '00033: Pays non autorisé.',
                '00102' => '00102: Autorisation refusée  contacter l\'émetteur de carte.',
                '00103' => '00103: Autorisation refusée  commerçant invalide.',
                '00104' => '00104: Autorisation refusée  conserver la carte.',
                '00105' => '00105: Autorisation refusée  ne pas honorer.',
                '00107' => '00107: Autorisation refusée  conserver la carte, conditions spéciales.',
                '00108' => '00108: Autorisation refusée  approuver après identification du porteur.',
                '00112' => '00112: Autorisation refusée  transaction invalide.',
                '00113' => '00113: Autorisation refusée  montant invalide.',
                '00114' => '00114: Autorisation refusée  numéro de porteur invalide.',
                '00115' => '00115: Autorisation refusée  émetteur de carte inconnu.',
                '00117' => '00117: Autorisation refusée  annulation client.',
                '00119' => '00119: Autorisation refusée  répéter la transaction ultérieurement.',
                '00120' => '00120: Autorisation refusée  réponse erronée (erreur dans le domaine serveur).',
                '00124' => '00124: Autorisation refusée  mise à jour de fichier non supportée.',
                '00125' => '00125: Autorisation refusée  impossible de localiser l‟enregistrement dans le fichier.',
                '00126' => '00126: Autorisation refusée  enregistrement dupliqué, ancien enregistrement remplacé.',
                '00127' => '00127: Autorisation refusée  erreur en « edit » sur champ de mise à jour fichier.',
                '00128' => '00128: Autorisation refusée  accès interdit au fichier.',
                '00129' => '00129: Autorisation refusée  mise à jour de fichier impossible.',
                '00130' => '00130: Autorisation refusée  erreur de format.',
                '00138' => '00138: Autorisation refusée  nombre d\'essais code confidentiel dépassé.',
                '00141' => '00141: Autorisation refusée  carte perdue.',
                '00143' => '00143: Autorisation refusée  carte volée.',
                '00151' => '00151: Autorisation refusée  provision insuffisante ou crédit dépassé.',
                '00154' => '00154: Autorisation refusée  date de validité de la carte dépassée.',
                '00155' => '00155: Autorisation refusée  code confidentiel erroné.',
                '00156' => '00156: Autorisation refusée  carte absente du fichier.',
                '00157' => '00157: Autorisation refusée  transaction non permise à ce porteur.',
                '00158' => '00158: Autorisation refusée  transaction interdite au terminal.',
                '00159' => '00159: Autorisation refusée  suspicion de fraude.',
                '00160' => '00160: Autorisation refusée  l\'accepteur de carte doit contacter l\'acquéreur.',
                '00161' => '00161: Autorisation refusée  dépasse la limite du montant de retrait.',
                '00163' => '00163: Autorisation refusée  règles de sécurité non respectées.',
                '00168' => '00168: Autorisation refusée  réponse non parvenue ou reçue trop tard.',
                '00175' => '00175: Autorisation refusée  nombre d\'essais code confidentiel dépassé.',
                '00176' => '00176: Autorisation refusée  porteur déjà en opposition, ancien enregistrement conservé.',
                '00190' => '00190: Autorisation refusée  arrêt momentané du système.',
                '00191' => '00191: Autorisation refusée  émetteur de cartes inaccessible.',
                '00194' => '00194: Autorisation refusée  demande dupliquée.',
                '00196' => '00196: Autorisation refusée  mauvais fonctionnement du système.',
                '00197' => '00197: Autorisation refusée  échéance de la temporisation de surveillance globale.',
        );
        if (is_null($erreur)) {
            return $res;
        } elseif (array_key_exists($erreur, $res)) {
            return $res[$erreur];
        } else {
            return false;
        }

    }
    /**
     * Renvoie le message d'erreur
     * @return string
     */
    public function getErreurMsg()
    {
        if (array_key_exists('erreur', $this->_params)) {            
            $result = self::getErreurListe($this->_params['erreur']);
            if (is_array($result)) {
                throw Tp_Model_Exception('Le numéro d\'erreur est null dans ' . __METHOD__);
            }
            if ($result === false) {
                return $this->_params['erreur'] . ': Erreur inconnue';
            } else {
                return $result;
            }
        } else {
            return ': La transaction a échoué';
        }
    }
    /**
     * Renvoi l'url du module Paybox
     * @return string
     */
    public function getUrlModule()
    {
        return $this->_config['urlModule'];
    }
    /**
     * Renvoi un curl_postfield
     *
     * @param int $montant
     * @param string $maref
     * @param string $email
     * @return string
     */
    public function getPostfields($montant, $maref, $email)
    {
        $params = array(
                'PBX_MODE' => '1', 
                'PBX_LANGUE' => $this->_config['PBX_LANGUE'],
                'PBX_SITE' => $this->_config['PBX_SITE'],
                'PBX_RANG' => $this->_config['PBX_RANG'],
                'PBX_IDENTIFIANT' => $this->_config['PBX_IDENTIFIANT'],
                'PBX_DEVISE' => $this->_config['PBX_DEVISE'],
                'PBX_RETOUR' => $this->_config['PBX_RETOUR'],
                'PBX_EFFECTUE' => $this->_config['PBX_EFFECTUE'],
                'PBX_REFUSE' => $this->_config['PBX_REFUSE'],
                'PBX_ANNULE' => $this->_config['PBX_ANNULE'],
                'PBX_REPONDRE_A' => $this->_config['PBX_REPONDRE_A'],
                'PBX_TOTAL' => $montant,
                'PBX_CMD' => $maref,
                'PBX_PORTEUR' => $email,
        );
        if (array_key_exists('PBX_PAYBOX', $this->_config)) {
            $params['PBX_BACKUP2'] = $params['PBX_BACKUP1'] = $params['PBX_PAYBOX'] = $this->_config['PBX_PAYBOX'];
        }
        $curl_postfields = '';
        $sep = '';
        foreach ($params as $key => $value) {
            $curl_postfields .= $sep . $key . '=' . $value;
            $sep = '&';
        }
        return $curl_postfields;
    }
    /**
     * Ouvre un fichier de compte rendu
     *
     * @param string $racine
     * @param string $mode
     * @return file pointer resource
     */
    private function fopen($racine = '', $mode = 'w')
    {
        if (empty($racine)) {
            $racine = $this->_config['retour']['name'];
        }
        $path = Zend_Registry::get('rootPath') . DIRECTORY_SEPARATOR . $this->_config['retour']['path'] . DIRECTORY_SEPARATOR;
        if ($mode == 'w') {
            $j = 0;
            do {
                $nomf = $path . $racine . ++$j . '.' . $this->_config['retour']['ext'];
            } while (file_exists($nomf));
        } else {
            $nomf = $path . $racine . '.' . $this->_config['retour']['ext'];
        }
        $f = fopen($nomf, $mode);
        return $f;
    }
    /**
     * Enregistre le résultat de la transaction dans un fichier de /paybox
     * nom: (du fichier application.ini si ok), ('attaque' si false)
     * @param boolean $ok
     */
    public function log($ok = true)
    {
        if ($ok) {
            $f = $this->fopen();
            foreach ($this->_params as $key => $value) {
                fwrite($f, $key . ': ' . $value . PHP_EOL);
            }
        } else {
            $f = $this->fopen('attaque', 'a');
            fwrite($f, 'ip:' . $this->_ip . ' - date: ' . Tp_Model_Format::date() . PHP_EOL);
        }
        fclose($f);
    }
    /**
     * Retourne vrai si le paiement s'est bien passé
     *
     * @return boolean
     */
    public function isValide()
    {
        $ok = in_array($this->_ip, $this->_config['IP']);
        if ($ok) {
            $ok = array_key_exists('auto', $this->_params)
            && array_key_exists('erreur', $this->_params)
            && array_key_exists('maref', $this->_params)
            && array_key_exists('montant', $this->_params)
            && array_key_exists('sign', $this->_params)
            && $this->_params['erreur'] == '00000'
            && $this->_params['montant'] == $this->getMontantRef();
            if ($ok) {
                // contrôle de la signature
            }
            $this->log();
        } else {
            $this->log(false);
        }
        return $ok;
    }

    /**
     * Retourne true si la classe est configurée (lecture de application.ini)
     *
     * @return boolean
     */
    private function isConfig()
    {
        return array_key_exists('retour', $this->_config)
        && array_key_exists('path', $this->_config['retour'])
        && array_key_exists('name', $this->_config['retour'])
        && array_key_exists('ext', $this->_config['retour'])
        ? true
        : false;
    }
    /**
     * Retourne un tableau des logs respectant les critères donnés dans $params.
     * $params est un  tableau associatif :
     * - vide : retourne tous les log
     * - contient :
     *     'depuis' => date à partir de laquelle on veut les résultats
     *     'erreur' => non (pas d'erreur)
     *                 oui (toutes les erreurs)
     *                 n (les transactions ayant retourné ce numéro d'erreur)
     *     'user' => userId (les transactions de cet utilisateur)
     *
     * @param array $params
     * @return array
     */
    public function getLogs($params = array())
    {
        $champs = array('montant', 'datetrans', 'heuretrans', 'erreur', 'maref', );
        if (!$this->isConfig()) {
            throw new Tp_Model_Exception('Appel de ' . __METHOD__ . ' sur une classe ' .__CLASS__ . ' non configurée.');
        }
        $ls = array();
        $path = Zend_Registry::get('rootPath') . DIRECTORY_SEPARATOR . $this->_config['retour']['path'];
        $dir = new DirectoryIterator($path);
        foreach ($dir as $fileInfo) {
            $racine = $this->_config['retour']['name'];
            $ext = $this->_config['retour']['ext'];
            $filename = $fileInfo->getFilename();
            if (preg_match("/^$racine.*\.$ext$/i", $filename) == 0) continue;
            $ls[] = $filename;
        }
        natcasesort($ls);
        $res = array();
        foreach ($ls as $filename) {
            $res2 = array();
            $filename = $path . DIRECTORY_SEPARATOR . $filename;
            $contenu = file($filename);
            foreach ($contenu as $ligne) {
                $morceaux = explode(': ', $ligne); // conserver l'espace après le : à cause des HH:mm:ss
                if (in_array($morceaux[0], $champs)) {
                    $res2[$morceaux[0]] = trim($morceaux[1]);
                }
            }
            $this->setParams($res2);
            // filtres depuis
            if (array_key_exists('depuis', $params)) {
                if (!$this->compareDates($params['depuis'])) continue;
            }
            // filtre erreur
            if (array_key_exists('erreur', $params)) {
                $erreur = $params['erreur'];
                if ($erreur == 'oui') {
                    if ($res2['erreur'] == 0) continue;
                } elseif ($erreur == 'non') {
                    if ($res2['erreur'] != 0) continue;
                } elseif ($erreur != $res2['erreur']) continue;
            }
            // filtre user
            $res2['userId'] = $this->getUserIdRef();
            if (array_key_exists('userId', $params)) {
                $userId = $params['userId'];
                if ($res2['userId'] != $userId) continue;
            }
            // format de la date
            $res2['datetrans'] = $this->getDatetrans('dd/MM/YYYY HH:mm:ss');
            // message d'erreur
            $res2['errmsg'] = $this->getErreurMsg();
            // affectation
            $res[] = $res2;
        }
        return $res;
    }
    /**
     * Retourne true si la date de transaction est postérieure à $dateFr donnée en paramètre.
     *
     * Compare la date de transaction (attributs de la structure) à la date donnée en paramètre
     * $dateFr est au format français DD/MM/YYYY HH:mm:ss (HH:mm:ss optionnels)
     * self::_params['datetrans'] est au format français paybox (DDMMYYYY)
     * self::_params['heuretrans'] est au format français (FF:mm:ss)
     *
     * @param string $dateFr
     * $return boolean
     */
    private function compareDates($dateFr) {
        if (array_key_exists('datetrans', $this->_params) && array_key_exists('heuretrans', $this->_params)) {
            $dateP = $this->_params['datetrans'];
            $heureP = $this->_params['heuretrans'];
            $datePaybox = substr($dateP, 4) . substr($dateP, 2, 2) . substr($dateP, 0, 2) . $heureP; // YYYYMMDDHH:mm:ss
        } else {
            throw new Tp_Model_Exception('La structure ' . __CLASS__ . 'n\'est pas initialisée. Pas de date dans ' . __METHOD__);
        }
        $dateFr = explode(' ', $dateFr);
        $dateD = explode('/', $dateFr[0]);
        $heureD = array_key_exists(1, $dateFr) ? $dateFr[1] : '';
        $depuis = $dateD[2] . $dateD[1] . $dateD[0] . $heureD; // YYYYMMDDHH:mm:ss
        return $datePaybox >= $depuis;
    }
    /**
     * Retourne le contenu du fichier attaque.txt s'il existe
     *
     * @return string
     */
    public function getAttaques()
    {
        if (!$this->isConfig()) {
            throw new Tp_Model_Exception('Appel de ' . __METHOD__ . ' sur une classe ' .__CLASS__ . ' non configurée.');
        }
        $path = Zend_Registry::get('rootPath') . DIRECTORY_SEPARATOR . $this->_config['retour']['path'] . DIRECTORY_SEPARATOR;
        ;
    }
}
?>