<?php
/**
* Envoi d'un mail à un utilisateur
*
* Description longue du fichier s'il y en a une
* 
* @project pits (et tp)
* @package library/AP/Controller/ActionHelpers
* @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
* @date 24 mai 2011 - rev. 19/09/2011
* @version $Id: $
*/

class Ap_Controller_ActionHelpers_UserMail extends Zend_Controller_Action_Helper_Abstract
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
        $configMail = $fc->getParam('mail');
        // choix du transport
        if ($configMail['transport'] == 'smtp') {
            $transport = new Zend_Mail_Transport_Smtp($configMail['host']);
        } elseif ($configMail['transport'] == 'sendmail') {
            $transport = new Zend_Mail_Transport_Sendmail();
        } else {
            throw new Ap_Exception('Le service d\'envoi de mail n\'est pas bien paramétré.');
        }
        // création du mail
        $mail = new Zend_Mail('UTF-8');
        /*
         * Pour le corps du message, deux cas :
         * - première connexion sur un compte bloqué (bloque == 1)
         * - modification d'identité avec ou sans changement de mdp
         */
        if (is_null($compte)) {
            $compte = Ap_Methode_Methode::objToArray(Zend_Auth::getInstance()->getIdentity());
        }
        $changeMdp = !is_null($mdp);
        if (empty($bodyFile)) {
                $bodyFile = 'mail_user_modif.txt';
        }
        $mailuser = new Tp_Document_UserMailContent($bodyFile, $changeMdp ? $mdp : '*** inchangé ***', $compte);
        // Crée le body
        $body = $mailuser->run();

        // paramétrage et envoi
        $mail->setBodyText($body, 'UTF-8')
        ->setReplyTo($configMail['replyto']['email'], $configMail['replyto']['name'])
        ->setFrom($configMail['from']['email'], $configMail['from']['name'])
        ->addTo($to = $compte['Email'], $compte['Prenom'] . ' ' . $compte['Nom'])
        ->setSubject($configMail['subject'])
        ->send($transport);

    }
}