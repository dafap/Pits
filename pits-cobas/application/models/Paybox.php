<?php
/**
 * Les outils pour paybox
 *
 * Description longue du fichier s'il y en a une
 *
 * @project pits
 * @package application/models/paybox
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 2 juin 2011
 * @version $Id: $
 */


class Pits_Model_Paybox
{
    /**
     * La config du fichier /application/configs/application.ini
     * @var array()
     */
    private $_config;
    /**
     * Le fichier ouvert dans /paybox pour enregistrer les réponse du service
     * @var file pointer resource
     */
    private $_f;
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
     * Renvoie le montant en centimes de 'maref'
     * @return int
     */
    public function getMontantRef()
    {
        if (array_key_exists(1, $this->_refs)) {
            return $this->_refs[1];
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
        if (array_key_exists(2, $this->_refs)) {
            return $this->_refs[2];
        } else {
            return false;
        }
    }
    /**
     * Renvoie le tableau des eleveId de 'maref'
     * @return array
     */
    public function getEleveIdsRef()
    {
        if (array_key_exists(3, $this->_refs)) {
            return array_slice($this->_refs, 3);
        } else {
            return false;
        }
    }
    /**
     * Renvoie la date de la transaction au format 'YYYY-MM-dd HH:mm:ss'
     * @return string
     */
    public function getDatetrans()
    {
        if (array_key_exists('datetrans', $this->_params) && array_key_exists('heuretrans', $this->_params)) {
            $dateP = $this->_params['datetrans'];
            $heureP = $this->_params['heuretrans'];
            $an = substr($dateP, 4);
            $mois = substr($dateP, 2, 2);
            $jour = substr($dateP, 0, 2);
            return  $an . '-' . $mois . '-' . $jour . ' ' . $heureP;
        } else {
            return Pits_Model_Format::date('YYYY-MM-dd HH:mm:ss');
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
    /**
     * Renvoie le message d'erreur
     * @return string
     */
    public function getErreurMsg()
    {
        if (array_key_exists('erreur', $this->_params)) {
            switch ($this->_params['erreur']) {
                case '00000':
                    return $this->_params['erreur'] . ': Opération réussie.';
                    break;
                case '00001':
                    return $this->_params['erreur'] . ': La connexion au centre d\'autorisation a échoué.';
                    break;
                case '00003':
                    return $this->_params['erreur'] . ': Erreur Paybox';
                    break;
                case '00004':
                    return $this->_params['erreur'] . ': Numéro de porteur ou cryptogramme visuel invalide.';
                    break;
                case '00006':
                    return $this->_params['erreur'] . ': Accès refusé : site/rang/identifiant incorrects.';
                    break;
                case '00008':
                    return $this->_params['erreur'] . ': Date de validité incorrecte.';
                    break;
                case '00009':
                    return $this->_params['erreur'] . ': Erreur de création d\'un abonnement.';
                    break;
                case '00010':
                    return $this->_params['erreur'] . ': Devise inconnue.';
                    break;
                case '00011':
                    return $this->_params['erreur'] . ': Montant incorrect.';
                    break;
                case '00015':
                    return $this->_params['erreur'] . ': paiement déjà effectué.';
                    break;
                case '00016':
                    return $this->_params['erreur'] . ': Abonné déjà existant.';
                    break;
                case '00021':
                    return $this->_params['erreur'] . ': Carte non autorisée.';
                    break;
                case '00029':
                    return $this->_params['erreur'] . ': Carte non conforme.';
                    break;
                case '00030':
                    return $this->_params['erreur'] . ': Temps d\'attente trop long.';
                    break;
                case '00033':
                    return $this->_params['erreur'] . ': Pays non autorisé.';
                    break;
                case '00102':
                    return $this->_params['erreur'] . ': Autorisation refusée : contacter l\'émetteur de carte.';
                    break;
                case '00103':
                    return $this->_params['erreur'] . ': Autorisation refusée : commerçant invalide.';
                    break;
                case '00104':
                    return $this->_params['erreur'] . ': Autorisation refusée : conserver la carte.';
                    break;
                case '00105':
                    return $this->_params['erreur'] . ': Autorisation refusée : ne pas honorer.';
                    break;
                case '00107':
                    return $this->_params['erreur'] . ': Autorisation refusée : conserver la carte, conditions spéciales.';
                    break;
                case '00108':
                    return $this->_params['erreur'] . ': Autorisation refusée : approuver après identification du porteur.';
                    break;
                case '00112':
                    return $this->_params['erreur'] . ': Autorisation refusée : transaction invalide.';
                    break;
                case '00113':
                    return $this->_params['erreur'] . ': Autorisation refusée : montant invalide.';
                    break;
                case '00114':
                    return $this->_params['erreur'] . ': Autorisation refusée : numéro de porteur invalide.';
                    break;
                case '00115':
                    return $this->_params['erreur'] . ': Autorisation refusée : émetteur de carte inconnu.';
                    break;
                case '00117':
                    return $this->_params['erreur'] . ': Autorisation refusée : annulation client.';
                    break;
                case '00119':
                    return $this->_params['erreur'] . ': Autorisation refusée : répéter la transaction ultérieurement.';
                    break;
                case '00120':
                    return $this->_params['erreur'] . ': Autorisation refusée : réponse erronée (erreur dans le domaine serveur).';
                    break;
                case '00124':
                    return $this->_params['erreur'] . ': Autorisation refusée : mise à jour de fichier non supportée.';
                    break;
                case '00125':
                    return $this->_params['erreur'] . ': Autorisation refusée : impossible de localiser l‟enregistrement dans le fichier.';
                    break;
                case '00126':
                    return $this->_params['erreur'] . ': Autorisation refusée : enregistrement dupliqué, ancien enregistrement remplacé.';
                    break;
                case '00127':
                    return $this->_params['erreur'] . ': Autorisation refusée : erreur en « edit » sur champ de mise à jour fichier.';
                    break;
                case '00128':
                    return $this->_params['erreur'] . ': Autorisation refusée : accès interdit au fichier.';
                    break;
                case '00129':
                    return $this->_params['erreur'] . ': Autorisation refusée : mise à jour de fichier impossible.';
                    break;
                case '00130':
                    return $this->_params['erreur'] . ': Autorisation refusée : erreur de format.';
                    break;
                case '00138':
                    return $this->_params['erreur'] . ': Autorisation refusée : nombre d\'essais code confidentiel dépassé.';
                    break;
                case '00141':
                    return $this->_params['erreur'] . ': Autorisation refusée : carte perdue.';
                    break;
                case '00143':
                    return $this->_params['erreur'] . ': Autorisation refusée : carte volée.';
                    break;
                case '00151':
                    return $this->_params['erreur'] . ': Autorisation refusée : provision insuffisante ou crédit dépassé.';
                    break;
                case '00154':
                    return $this->_params['erreur'] . ': Autorisation refusée : date de validité de la carte dépassée.';
                    break;
                case '00155':
                    return $this->_params['erreur'] . ': Autorisation refusée : code confidentiel erroné.';
                    break;
                case '00156':
                    return $this->_params['erreur'] . ': Autorisation refusée : carte absente du fichier.';
                    break;
                case '00157':
                    return $this->_params['erreur'] . ': Autorisation refusée : transaction non permise à ce porteur.';
                    break;
                case '00158':
                    return $this->_params['erreur'] . ': Autorisation refusée : transaction interdite au terminal.';
                    break;
                case '00159':
                    return $this->_params['erreur'] . ': Autorisation refusée : suspicion de fraude.';
                    break;
                case '00160':
                    return $this->_params['erreur'] . ': Autorisation refusée : l\'accepteur de carte doit contacter l\'acquéreur.';
                    break;
                case '00161':
                    return $this->_params['erreur'] . ': Autorisation refusée : dépasse la limite du montant de retrait.';
                    break;
                case '00163':
                    return $this->_params['erreur'] . ': Autorisation refusée : règles de sécurité non respectées.';
                    break;
                case '00168':
                    return $this->_params['erreur'] . ': Autorisation refusée : réponse non parvenue ou reçue trop tard.';
                    break;
                case '00175':
                    return $this->_params['erreur'] . ': Autorisation refusée : nombre d\'essais code confidentiel dépassé.';
                    break;
                case '00176':
                    return $this->_params['erreur'] . ': Autorisation refusée : porteur déjà en opposition, ancien enregistrement conservé.';
                    break;
                case '00190':
                    return $this->_params['erreur'] . ': Autorisation refusée : arrêt momentané du système.';
                    break;
                case '00191':
                    return $this->_params['erreur'] . ': Autorisation refusée : émetteur de cartes inaccessible.';
                    break;
                case '00194':
                    return $this->_params['erreur'] . ': Autorisation refusée : demande dupliquée.';
                    break;
                case '00196':
                    return $this->_params['erreur'] . ': Autorisation refusée : mauvais fonctionnement du système.';
                    break;
                case '00197':
                    return $this->_params['erreur'] . ': Autorisation refusée : échéance de la temporisation de surveillance globale.';
                    break;
                    
                default:
                    return $this->_params['erreur'] . ': Erreur inconnue';
                    break;
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
                'PBX_PAYBOX' => $this->_config['PBX_PAYBOX'],
                'PBX_TOTAL' => $montant,
                'PBX_CMD' => $maref,
                'PBX_PORTEUR' => $email,
        );
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
     * @return file pointer resource
     */
    private function fopen($racine = '')
    {
        if (empty($racine)) {
            $racine = $this->_config['retour']['name'];
        }
        $path = Zend_Registry::get('rootPath') . DIRECTORY_SEPARATOR . $this->_config['retour']['path'] . DIRECTORY_SEPARATOR;
        $j = 0;
        do {
            $nomf = $path . $racine . ++$j . '.' . $this->_config['retour']['ext'];
        } while (file_exists($nomf));
        $this->_f = fopen($nomf, 'w');
        return $this->_f;
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
            $path = Zend_Registry::get('rootPath') . DIRECTORY_SEPARATOR . $this->_config['retour']['path'] . DIRECTORY_SEPARATOR;
            $nomf = $path . 'attaque.txt';
            $f = fopen($nomf, 'a');
            fwrite($f, 'ip:' . $this->_ip . PHP_EOL);
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
}
?>