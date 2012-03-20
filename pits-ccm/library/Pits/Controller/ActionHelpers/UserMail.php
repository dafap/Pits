<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 6 juin 2010
 *
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_Controller_ActionHelpers_UserMail extends Zend_Controller_Action_Helper_Abstract
{
    /**
     * Indispensable pour une aide d'action
     * Si le mdp est connu, il est donné en clair pour l'envoyer à l'utilisateur
     * @param string ou null $mdp
     */
    public function direct($mdp = null, $compte = null, $bodyFile = '')
    {
        // paramètres de mail
        $fc = Zend_Controller_Front::getInstance();
        $configMail = $fc->getParam('config')->mail;
        // choix du transport
        if ($configMail->transport == 'smtp') {
            $transport = new Zend_Mail_Transport_Smtp('smtp.free.fr');
        } elseif ($configMail->transport == 'sendmail') {
            $transport = new Zend_Mail_Transport_Sendmail();
        } else {
            throw new Exception('Le service d\'envoi de mail n\'est pas bien paramétré.');
        }
        // création du mail
        $mail = new Zend_Mail('UTF-8');
        /*
         * Pour le corps du message, deux cas :
         * - première connexion sur un compte bloqué (bloque == 1)
         * - modification d'identité avec ou sans changement de mdp
         */
        if (is_null($compte)) {
            $compte = Zend_Auth::getInstance()->getIdentity()->toArray();
        }
        $bloque = $compte['bloque'];
        $changeMdp = !is_null($mdp);
        if (empty($bodyFile)) {
            if ($bloque) {
                $bodyFile = 'mail_user_premier.txt';
            } else {
                $bodyFile = 'mail_user_modif.txt';
            }
        }
        $mailuser = new UserMailContent($bodyFile, $changeMdp ? $mdp : '*** inchangé ***', $compte);
        // Crée le body
        $body = $mailuser->run();

        // paramétrage et envoi
        $mail->setBodyText($body, 'UTF-8')
        ->setReplyTo($configMail->replyto->email, $configMail->replyto->name)
        ->setFrom($configMail->from->email, $configMail->from->name)
        ->addTo($to = $compte['Email'], $compte['Prenom'] . ' ' . $compte['Nom'])
        ->setSubject('[CC de Montesquieu] Transports scolaires')
        ->send($transport);

    }
}