<?php
/**
 * Exportation au format mail
 *
 * @project pits
 * @package library/ap
 * @author DAFAP Informatique - Alain Pomirol (dafap@free.fr)
 * @date 3 juin 2011
 * @version $Id: $
 */

/**
 * Cette classe recevra éventuellement la pièce jointe en dernier paramètre du constructeur
 * 
 * @author pomirol
 *
 */
class Ap_Controller_ActionHelpers_ExportEleves_Mail extends Ap_Controller_ActionHelpers_ExportEleves_Abstract
{
    private $_configMail;
    private $_fbody;
    protected function init()
    {
        $this->_mimetype = 'application/pdf';
        $this->_configMail = Zend_Controller_Front::getInstance()->getParam('mail');
        $modele = Zend_Registry::get('applicationPath') . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR . 'doc';
        $modele .= DIRECTORY_SEPARATOR . 'mail_eleve.txt';
        $this->_fbody = fopen($modele, 'r');
        if (! $this->_fbody) {
            throw new Ap_Controller_Exception("Le ficher `mail_eleve.txt` modèle de l'email n'est pas trouvé dans le répertoire `application/configs/doc/`.");
        }
    }
    public function getContent()
    {
        // paramètres de mail
        $transport = $this->getTransport();
        // création du mail
        $mail = new Zend_Mail('UTF-8');
        // pièce jointe
//        Zend_Debug::dump($this->_params);exit;
        $att = $mail->createAttachment($this->_params->getContent(), $this->_params->getMimeType(), 
        Zend_Mime::DISPOSITION_INLINE , Zend_Mime::ENCODING_BASE64,'inscription.pdf');
        // body
        $body = '';
        while (!feof($this->_fbody)) {
            $body .= fgets($this->_fbody);
        }
        // paramétrage et envoi
        $mail->setBodyText($body, 'UTF-8')
        ->setReplyTo($this->_configMail['replyto']['email'], $this->_configMail['replyto']['name'])
        ->setFrom($this->_configMail['from']['email'], $this->_configMail['from']['name'])
        ->addTo($to = Zend_Auth::getInstance()->getIdentity()->Email,
                Zend_Auth::getInstance()->getIdentity()->Prenom . ' ' .
                Zend_Auth::getInstance()->getIdentity()->Nom)
        ->setSubject($this->_configMail['subject'])
        ->send($transport);
        
        return $this->_params->getContent();
    }
    /**
     * Retourne le bon transport
     *
     * @return Zend_Mail_Transport_Abstract
     * @throws Ap_Controller_Exception
     */
    private function getTransport()
    {
        if ($this->_configMail['transport'] == 'smtp') {
            return new Zend_Mail_Transport_Smtp($this->_configMail['host']);
        } elseif ($this->_configMail['transport'] == 'sendmail') {
            return new Zend_Mail_Transport_Sendmail();
        } else {
            throw new Ap_Controller_Exception('Le service d\'envoi de mail n\'est pas bien paramétré.');
        }
    }
}