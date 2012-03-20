<?php
/**
 * Application PiTS
 * Gestion des préinscriptions au service de transports scolaires
 *
 * @date 16 mai 2010
 *
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     Alain POMIROL
 * @copyright  Copyright (c) 2010, Alain Pomirol (dafap@free.fr) - Tous droits réservés
 * @version    0.1.0
 * @since      Ce fichier est disponible depuis la version 0.1.0
 */

include_once 'Pits/Controller/ActionHelpers/ExportEleves/Abstract.php';

/**
 * @category   pits
 * @package    library
 * @subpackage pits
 * @author     pomirol
 */
class Pits_Controller_ActionHelpers_ExportEleves_Mail
extends Pits_Controller_ActionHelpers_ExportEleves_Abstract
{
    const MIME_TYPE = 'application/pdf';
    //const MIME_TYPE = 'text/html; charset=utf-8';
    public function getMimeType()
    {
        return self::MIME_TYPE;
    }
    public function  getContent()
    {
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
        // pièce jointe
        $pdfExportPlugin = new Pits_Controller_ActionHelpers_ExportEleves_Pdf($this->_eleveId);
        $pdfDocument = $pdfExportPlugin->getContent();
        $att = $mail->createAttachment($pdfDocument);
        $att->type = $pdfExportPlugin->getMimeType();
        $att->filename = 'inscription.pdf';
        // body
        $fbody = fopen('config' . DIRECTORY_SEPARATOR . 'mail_eleve.txt', 'r', true);
        if (!$fbody) {
            throw new Exception("Le ficher `mail_eleve.txt` modèle de l'email n'est pas trouvé dans le répertoire `application/config/`.");
        }
        $body = '';
        while (!feof($fbody)) {
            $body .= fgets($fbody);
        }
        // paramétrage et envoi
        $mail->setBodyText($body, 'UTF-8')
        ->setReplyTo($configMail->replyto->email, $configMail->replyto->name)
        ->setFrom($configMail->from->email, $configMail->from->name)
        ->addTo($to = Zend_Auth::getInstance()->getIdentity()->Email,
                Zend_Auth::getInstance()->getIdentity()->Prenom . ' ' .
                Zend_Auth::getInstance()->getIdentity()->Nom)
        ->setSubject('[CC de Montesquieu] inscriptions aux transports scolaires')
        ->send($transport);
        // compte-rendu
        //return 'Email envoyé à ' . $to;
        return $pdfDocument;
    }
}