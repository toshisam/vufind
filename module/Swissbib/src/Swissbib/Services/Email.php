<?php
/**
 * Service for sending e-mail.
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category Swissbib_VuFind2
 * @package  Services
 * @author   Simone Cogno <scogno@snowflake.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace Swissbib\Services;

use Zend\Di\ServiceLocator;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Mime;
use Zend\Mail\Message;
use Zend\ServiceManager\ServiceManager;
use Zend\Mail\Transport\Sendmail as SendmailTransport;

class Email implements ServiceLocatorAwareInterface
{
    /** @var  ServiceLocatorInterface $serviceLocator */
    protected $serviceLocator;
    /** @var  array $config */
    protected $config;

    /**
     * Email constructor.
     */
    public function __construct($config)
    {
        $this->config = $config['swissbib'];
    }


    /**
     * Set serviceManager instance
     *
     * @param ServiceLocatorInterface $serviceLocator ServiceLocatorInterface
     *
     * @return void
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /**
     * Retrieve serviceManager instance
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * Send e-mail with attachment.
     *
     * @param string $to The recipient of the e-mail
     * @param string $textMail Text of the e-mail
     * @param string $attachmentFilePath File path of the file to attach
     */
    public function sendMail($to, $textMail, $attachmentFilePath)
    {
        $mimeMessage = $this->createMimeMessage($textMail, $attachmentFilePath);
        $this->sendMailWithAttachment($to, $mimeMessage, 'National licence user export');
    }

    /**
     * Create mime message with email text and attached file.
     *
     * @param string $textMail Email text
     * @param string $attachmentFilePath Attachment file path
     * @return Mime\Message
     */
    public function createMimeMessage($textMail, $attachmentFilePath = null, $contentType = null)
    {
        if (empty($contentType)) {
            $contentType = Mime\Mime::TYPE_HTML;
        }

        $mimeMessage = new Mime\Message();

        // first create the parts
        $text = new Mime\Part();
        $text->type = $contentType;
        $text->charset = 'utf-8';
        $text->setContent($textMail);

        if (!empty($attachmentFilePath)) {
            //Get the attached file reference
            $fileContent = fopen($attachmentFilePath, 'r') or die("Unable to open file!");
            $attachment = new Mime\Part($fileContent);
            $attachment->type = 'text/csv';
            $attachment->filename = 'user_export.csv';
            $attachment->disposition = Mime\Mime::DISPOSITION_ATTACHMENT;
            // Setting the encoding is recommended for binary data
            $attachment->encoding = Mime\Mime::ENCODING_BASE64;
            // then add them to a MIME message
            $mimeMessage->setParts(array($text, $attachment));
        } else {
            // then add it to MIME message
            $mimeMessage->setParts(array($text));
        }

        return $mimeMessage;
    }

    /**
     * Send e-mail with defined mime message (text and attached file).
     *
     * @param string $to
     * @param Mime\Message $mimeMessage
     * @param string $subject
     * @throws \Exception
     */
    public function sendMailWithAttachment($to, $mimeMessage, $subject)
    {
        if (empty($to)) {
            throw new \Exception("Impossible to send the e-mail: recipient not given");
        }
        // and finally we create the actual email
        $message = new Message();
        $message->setBody($mimeMessage);
        $message->addTo($to)
            ->addFrom($this->config['email_service']['default_email_address_from'])
            ->setSubject($subject);
        $transport = new SendmailTransport();
        $transport->send($message);
    }

    /**
     * Send the account extension e-mail to a specific user.
     *
     * @param string $to User e-mail that the e-mail will be sent to.
     */
    public function sendAccountExtensionEmail($to)
    {
        $sl = $this->getServiceLocator();
        $vhm = $sl->get('viewhelpermanager');
        $url = $vhm->get('url');
        $link = $this->config['national_licence_service']['base_domain_path'] . $url('national-licences', array('action' => 'extend-account'), array('force_canonical' => true));
        echo $link;
        $textMail = '<p>You acccount expires in 10 days. Please&nbsp;<a href="' . $link . '">click here</a> to extend you account duration.</p>' .
            '<p>Best regards,</p>' .
            '<p>Swissbib</p>';
        $mimeMessage = $this->createMimeMessage($textMail, null, Mime\Mime::TYPE_HTML);
        $this->sendMailWithAttachment($to, $mimeMessage, 'Account extension');
    }

}