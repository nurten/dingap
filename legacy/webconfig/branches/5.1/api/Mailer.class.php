<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2007 Point Clark Networks.
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

/**
 * Wrapper for the Swift emailer utility.
 *
 * Provides inteface for sending alerts, messaging, reports etc. via email.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

require_once('Engine.class.php');
require_once('File.class.php');
require_once('Network.class.php');
require_once('ConfigurationFile.class.php');
require_once('Hostname.class.php');
require_once('NtpTime.class.php');
require_once('Swift/lib/Swift.php');
require_once('Swift/lib/Swift/File.php');
require_once('Swift/lib/Swift/Connection/SMTP.php');

///////////////////////////////////////////////////////////////////////////////
// E X C E P T I O N  C L A S S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * E-Mailer Utility.
 *
 * Provides inteface for sending alerts, messaging, reports etc. via email.
 *
 * @package Api
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2003-2006, Point Clark Networks
 */

class Mailer extends Engine
{
	///////////////////////////////////////////////////////////////////////////////
	// V A R I A B L E S
	///////////////////////////////////////////////////////////////////////////////

	protected $config = null;
	protected $message = null;
	protected $is_loaded = false;

	const FILE_CONFIG = '/etc/mailer.conf';
	const LOG_TAG = 'mailer';

	///////////////////////////////////////////////////////////////////////////////
	// M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * Mailer constructor.
	 *
	 * @return void
	 */

	function __construct()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__construct();

		require_once(GlobalGetLanguageTemplate(__FILE__));
	}

	/** Send a plain text message.
	 *
	 * @return void
     * @throws ValidationException, EngineException
	 */

	function Send()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (! $this->is_loaded)
			$this->_LoadConfig();

		# Create a recipient list
		$recipient_list = new Swift_RecipientList();

		# Swift mailer logs a warning message if we don't set this
		$ntptime = new NtpTime();
		date_default_timezone_set($ntptime->GetTimeZone());

		# Validation
		# ----------
		
		if ($this->message['recipient'] == null || empty($this->message['recipient'])) {
			throw new ValidationException(MAILER_LANG_RECIPIENT_NOT_SET);
		} else {
			foreach ($this->message['recipient'] as $address) {
				if (!$this->IsValidEmail($address['address']))
					throw new ValidationException(MAILER_LANG_RECIPIENT . " - " . LOCALE_LANG_INVALID);
			}
		}

		# Sender
		if ($this->GetSender() != null && $this->GetSender() != "") {
			$address = $this->_ParseEmailAddress($this->GetSender());
			$this->message['sender']['address'] = $address['address'];
			$this->message['sender']['name'] = $address['name'];
		} else {
			// Fill in default
			$hostname = new Hostname();
			$this->message['sender']['address'] = "root@" . $hostname->Get();
		}

		# ReplyTo
		if (!isset($this->message['replyto']) || $this->message['replyto'] == null || empty($this->message['replyto'])) {
			// Set to Sender
			$this->message['replyto'] = $this->message['sender']['address'];
		}

		try {
			$smtp = new Swift_Connection_SMTP(
				$this->config['host'], intval($this->config['port']), intval($this->config['ssl'])
			);
			if ($this->config['username'] != null && !empty($this->config['username'])) {
				$smtp->setUsername($this->config['username']);
				$smtp->setpassword($this->config['password']);
			}
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		try {
			$swift = new Swift($smtp);
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_INFO);
		}

		# Set Subject
		$message = new Swift_Message($this->message['subject']);

		# Set Body
		if (isset($this->message['body']))
			$message->setBody($this->message['body']);

		if (isset($this->message['parts'])) {
			foreach ($this->message['parts'] as $msgpart) {
				if (isset($msgpart['filename'])) {
					if (isset($msgpart['data'])) {
						# Data in variable
						$part = new Swift_Message_Attachment(
							$msgpart['data'], basename($msgpart['filename']), $msgpart['type'],
							$msgpart['encoding'], $msgpart['disposition']
						);
					} else {
						# Data as file
						try {
							$file = new Swift_File($msgpart['filename']);
						} catch (Swift_FileException $e) {
							throw new FileNotFoundException(FILE_LANG_ERRMSG_NOTEXIST . basename($msgpart['filename']));
						}
						$part = new Swift_Message_Attachment(
							$file, basename($msgpart['filename']), $msgpart['type'],
							$msgpart['encoding'], $msgpart['disposition']
						);
					}
				} else if (isset($msgpart['disposition']) && strtolower($msgpart['disposition']) == 'inline') {
					$part = new Swift_Message_Attachment(
						$msgpart['data'], null, $msgpart['type'], $msgpart['encoding'], $msgpart['disposition']
					);
				} else {
					$part = new Swift_Message_Part(
						$msgpart['data'], $msgpart['type'], $msgpart['encoding'], $msgpart['charset']
					);
				}
				if (isset($msgpart['Content-ID']))
					$part->headers->set("Content-ID", $msgpart['Content-ID']);
				$message->attach($part);
			}
		}

		# Override date
		if (isset($this->message['date']))
			$message->SetDate($this->message['date']);

		# Set Custom headers
		# Set a default 'pcn-archive-ignore' flag so messages sent from Mailer do not get archived
		if (isset($this->message['headers'])) {
			$ignore_set = false;
			while ($header = current($this->message['headers'])) {
				if (key($header) == 'pcn-archive-ignore')
					$ignore_set = true;
				$message->headers->Set(key($header), $header[key($header)]);
				next($this->message['headers']);
			}
			if ($ignore_set)
				$message->headers->Set('pcn-archive-ignore', 'true');
		} else {
			$message->headers->Set('pcn-archive-ignore', 'true');
		}

		# Set To
		foreach ($this->message['recipient'] as $recipient) {
			$addy = new Swift_Address($recipient['address']);
			if (isset($recipient['name']))
				$addy->setName($recipient['name']);
            $recipient_list->addTo($addy);
		}
		# Set CC 
		if (isset($this->message['cc'])) {
			foreach ($this->message['cc'] as $cc) {
				$addy = new Swift_Address($cc['address']);
				if (isset($cc['name']))
					$addy->setName($cc['name']);
            	$recipient_list->addCc($addy);
			}
		}
		# Set BCC 
		if (isset($this->message['bcc'])) {
			foreach ($this->message['bcc'] as $bcc) {
				$addy = new Swift_Address($bcc['address']);
				if (isset($bcc['name']))
					$addy->setName($bcc['name']);
				$recipient_list->addBCc($addy);
			}
		}
		# Set sender
		$sender = new Swift_Address($this->message['sender']['address']);
		if (isset($this->message['sender']['name']))
			$sender->setName($this->message['sender']['name']);

		# Set reply to
		$message->setReplyTo($this->message['replyto']);

		if ($swift->send($message, $recipient_list, $sender)) {
			$this->_LogSendSuccess();
			$swift->disconnect();
			$this->Clear();
		} else {
			$swift->disconnect();
			$this->Clear();
			throw new EngineException(MAILER_LANG_ERRMSG_SEND_FAILED, COMMON_WARNING);
		}
	}

	/** Override the date header.
	 *
     * @param integer $timestamp a UNIX timestamp
	 * @return void
     * @throws ValidationException
	 */

	function OverrideDate($timestamp)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->message['date'] = $timestamp;
	}

	/** Add a custom header to email.
	 *
     * @param String $key
     * @param String $value
	 * @return void
     * @throws ValidationException
	 */

	function AddCustomHeader($key, $value)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validation
		// ----------
		# TODO
		
		$this->message['headers'][] = Array($key => $value);
	}

	/** Add an email to the send-to (recipient) address field.
	 *
     * @param mixed $recip a string or array (address, name) representing a recipient's email address
	 * @return void
     * @throws ValidationException
	 */

	function AddRecipient($recip)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$address = $this->_ParseEmailAddress($recip);

		// Validation
		// ----------
		
		if (!$this->IsValidEmail($address['address']))
			throw new ValidationException(MAILER_LANG_RECIPIENT . " - " . LOCALE_LANG_INVALID);

		$this->message['recipient'][] = $address;
	}

	/** Adds a recipient the CC address field.
	 *
     * @param mixed $recip a string or array (address, name) representing a CC email address
	 * @return void
     * @throws ValidationException
	 */

	function AddCc($cc)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!isset($cc) || $cc == null || empty($cc))
			return;

		$address = $this->_ParseEmailAddress($cc);

		// Validation
		// ----------
		
		if (!$this->IsValidEmail($address['address']))
			throw new ValidationException(MAILER_LANG_CC . " - " . LOCALE_LANG_INVALID);

		$this->message['cc'][] = $address;
	}

	/** Adds a recipient the BCC address field.
	 *
     * @param mixed $recip a string or array (address, name) representing a BCC email address
	 * @return void
     * @throws ValidationException
	 */

	function AddBcc($bcc)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		if (!isset($bcc) || $bcc == null || empty($bcc))
			return;

		$address = $this->_ParseEmailAddress($bcc);

		// Validation
		// ----------
		
		if (!$this->IsValidEmail($address['address']))
			throw new ValidationException(MAILER_LANG_BCC . " - " . LOCALE_LANG_INVALID);

		$this->message['bcc'][] = $address;
	}

	/** Set the sender email address field.
	 *
     * @param mixed $sender a string or array (address, name) representing the sender's email address
	 * @return void
     * @throws ValidationException
	 */

	function SetSender($sender)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$address = $this->_ParseEmailAddress($sender);

		// Validation
		// ----------
		
		if (!$this->IsValidEmail($address['address']))
			throw new ValidationException(MAILER_LANG_SENDER . " - " . LOCALE_LANG_INVALID);

		$this->_SetParameter('sender', $sender);
	}

	/** Set the ReplyTo field.
	 *
     * @param mixed $replyto a string or array (address, name) representing the reply to email address
	 * @return void
     * @throws ValidationException
	 */

	function SetReplyTo($replyto)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$address = $this->_ParseEmailAddress($replyto);

		// Validation
		// ----------
		
		if (!$this->IsValidEmail($address['address']))
			throw new ValidationException(MAILER_LANG_REPLYTO . " - " . LOCALE_LANG_INVALID);

		$this->message['replyto'] = $address['address'];
	}

	/** Set the message subject.
	 *
     * @param string $subject the message subject field
	 * @return void
     * @throws ValidationException
	 */

	function SetSubject($subject)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validation
		// ----------
		
		if (!$this->IsValidSubject($subject))
			throw new ValidationException(MAILER_LANG_SUBJECT . " - " . LOCALE_LANG_INVALID);

		$this->message['subject'] = $subject;
	}

	/** Set the message body.
	 *
     * @param string $body the message body
	 * @return void
     * @throws ValidationException
	 */

	function SetBody($body)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validation
		// ----------
		
		if (!$this->IsValidBody($body))
			throw new ValidationException(MAILER_LANG_BODY . " - " . LOCALE_LANG_INVALID);

		$this->message['body'] = $body;
	}

	/** Set the message body.
	 *
     * @param string $body the message body
	 * @return void
     * @throws ValidationException
	 */

	function SetHtmlBody($html)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validation
		// ----------
		
		if (!$this->IsValidHtmlBody($html))
			throw new ValidationException(MAILER_LANG_HTML_BODY . " - " . LOCALE_LANG_INVALID);

		$html = array($html, "text/html");
		$this->message['parts'][] = $html;
	}

	/** Set a message part.
	 *
     * @param array $part the message part
	 * @return void
     * @throws ValidationException
	 */

	function SetPart($part)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$this->message['parts'][] = $part;
	}

	/** Set the message attachments to be sent.
	 *
     * @param array $attachments associative array containing the message attachments to be included
	 * Array ('data', 'filename', 'type', 'encoding', 'disposition')
	 * @return void
     * @throws ValidationException
	 */

	function SetAttachments($attachments)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validation
		// ----------
		
		foreach ($attachments as $attachment) {
			if (!$this->IsValidAttachment($attachment)) {
				$errors = $this->GetValidationErrors();
				throw new ValidationException($errors[0]);
			}
			if (!isset($attachment['type']))
				$attachment['type'] = "application/octet-stream";
			if (!isset($attachment['encoding']))
				$attachment['encoding'] = "base64";
			if (!isset($attachment['disposition']))
				$attachment['disposition'] = "attachment";
			$this->message['parts'][] = $attachment;
		}
	}

	/** Set the hostname for the SMTP server.
	 *
     * @param string $host the SMTP hostname
	 * @return void
     * @throws ValidationException
	 */

	function SetSmtpHost($host)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validation
		// ----------
		
		if (!$this->IsValidSmtpHost($host)) {
			$errors = $this->GetValidationErrors();
			throw new ValidationException($errors[0]);
		}

		$this->_SetParameter('host', $host);
	}

	/** Set the port for the SMTP server.
	 *
     * @param string $host the SMTP port
	 * @return void
     * @throws ValidationException
	 */

	function SetSmtpPort($port)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validation
		// ----------
		
		if (!$this->IsValidSmtpPort($port)) {
			$errors = $this->GetValidationErrors();
			throw new ValidationException($errors[0]);
		}

		$this->_SetParameter('port', $port);
	}

	/** Set the SSL type for the SMTP server.
	 *
     * @param string $ssl the encryption protocol to use (none, SSL, TLS)
	 * @return void
     * @throws ValidationException
	 */

	function SetSmtpSsl($ssl)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validation
		// ----------
		
		if (!$this->IsValidSmtpSsl($ssl))
			throw new ValidationException(MAILER_LANG_SSL_TLS . " - " . LOCALE_LANG_INVALID);

		$this->_SetParameter('ssl', $ssl);
	}

	/** Set the username to authenticate to the SMTP server.
	 *
     * @param string $username the username of a valid SMTP account on the server
	 * @return void
     * @throws ValidationException
	 */

	function SetSmtpUsername($username)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validation
		// ----------
		
		if (!$this->IsValidSmtpUsername($username))
			throw new ValidationException(MAILER_LANG_USERNAME . " - " . LOCALE_LANG_INVALID);

		$this->_SetParameter('username', $username);
	}

	/** Set the password to authenticate to the SMTP server.
	 *
     * @param string $password the password of a valid SMTP account on the server
	 * @return void
     * @throws ValidationException
	 */

	function SetSmtpPassword($password)
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		// Validation
		// ----------
		
		if (!$this->IsValidSmtpPassword($password))
			throw new ValidationException(MAILER_LANG_PASSWORD . " - " . LOCALE_LANG_INVALID);

		$this->_SetParameter('password', $password);
	}

	/**
     * Returns SMTP host.
     *
     * @return string host
     * @throws EngineException
     */

    function GetSmtpHost()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_LoadConfig();

        return $this->config['host'];
    }

	/**
     * Returns SMTP port.
     *
     * @return string port
     * @throws EngineException
     */

    function GetSmtpPort()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_LoadConfig();

        return $this->config['port'];
    }

	/**
     * Returns SMTP ssl.
     *
     * @return string ssl
     * @throws EngineException
     */

    function GetSmtpSsl()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_LoadConfig();

        return $this->config['ssl'];
    }

	/**
     * Returns SMTP username.
     *
     * @return string username
     * @throws EngineException
     */

    function GetSmtpUsername()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_LoadConfig();

        return $this->config['username'];
    }

	/**
     * Returns SMTP password.
     *
     * @return string password
     * @throws EngineException
     */

    function GetSmtpPassword()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_LoadConfig();

        return $this->config['password'];
    }

	/**
     * Returns sender address.
     *
     * @return string sender address
     * @throws EngineException
     */

    function GetSender()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_LoadConfig();

        return $this->config['sender'];
    }

	/** Get the SSL type options for the SMTP server.
	 *
	 * @return array
	 */

	function GetSslOptions()
	{
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		$options = array(
			Swift_Connection_SMTP::ENC_OFF=>MAILER_LANG_SSL_NONE,
			Swift_Connection_SMTP::ENC_SSL=>MAILER_LANG_SSL,
			Swift_Connection_SMTP::ENC_TLS=>MAILER_LANG_TLS
		);
		return $options;
	}

	/**
     * Returns a record of the previous transaction.
     *
	 * @deprecated
     * @return string password
     */

    function GetTransaction()
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		return "";
    }

	/** Executes a test to see if mail can be sent through the SMTP server.
	 *
	 * @param string @email a valid email to send test to
	 * @return bool
     * @throws ValidationException, EngineException
	 */

	function TestRelay($email)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);


		$this->AddRecipient($email);
		$this->SetSubject(MAILER_LANG_TEST_EMAIL);
		$this->SetBody(MAILER_LANG_TEST_SUCCESS);
		$this->Send();
	}

	/** Clears data structures.
	 *
	 * @return void
	 */

	function Clear()
	{
		unset($this->message);
	}

	///////////////////////////////////////////////////////////////////////////////
	// P R I V A T E   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
	 * @access private
	 */

	function __destruct()
	{
		if (COMMON_DEBUG_MODE)
			$this->Log(COMMON_DEBUG, 'called', __METHOD__, __LINE__);

		parent::__destruct();
	}

	/**
     * Load configuration.
     *
     * @access private
     * @return void
     * @throws EngineException
     */

    private function _LoadConfig()
    {
		if (COMMON_DEBUG_MODE)
			self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$configfile = new ConfigurationFile(self::FILE_CONFIG);

		try {
			$this->config = $configfile->Load();
		} catch (Exception $e) {
			throw new EngineException($e->GetMessage(), COMMON_WARNING);
		}

		$this->is_loaded = true;
    }

	/**
     * Sets a parameter in the config file.
     *
     * @access private
     * @param string $key name of the key in the config file
     * @param string $value value for the key
     * @return void
     * @throws EngineException
     */

    function _SetParameter($key, $value)
    {
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        try {
            $file = new File(self::FILE_CONFIG);
            $match = $file->ReplaceLines("/^$key\s*=\s*/", "$key = $value\n");
            if (!$match)
                $file->AddLines("$key = $value\n");
        } catch (Exception $e) {
            throw new EngineException($e->GetMessage(), COMMON_WARNING);
        }

        $this->is_loaded = false;
    }

	/**
     * Log a successul send event.
     *
     * @access private
     * @return void
     * @throws EngineException
     */

	function _LogSendSuccess()
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$list = "";
		foreach ($this->message['recipient'] as $recipient)
			$list .= $recipient['address'] . ",";

		$list = substr($list, 0, strlen($list) - 1);
		Logger::Syslog(self::LOG_TAG, "Message sent - Subj: " . $this->message['subject'] . ", To: " . $list);
	}

	/**
     * Parse an email address.
     *
     * @param mixed $raw  the email address (as a string or array of parts)
     * @access private
     * @return array
     * @throws EngineException
     */

	function _ParseEmailAddress($raw)
	{
        if (COMMON_DEBUG_MODE)
            self::Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$address = Array();
		if (! is_array($raw))
			$address[0] = $raw;
		else
			$address = $raw;

		$match = null;

		# Format Some Guy <someguy@domain.com>
		if (eregi("^(.*) +<(.*)>$", $address[0], $match)) {
			$address[0] = $match[2];
			$address[1] = $match[1];
		}

		# Format <someguy@domain.com> Some Guy
		if (eregi("^<(.*)> +(.*)$", $address[0], $match)) {
			$address[0] = $match[2];
			$address[1] = $match[1];
		}

		# Format someguy@domain.com Some Guy
		if (eregi("^([a-z0-9\._-]+@+[a-z0-9\._-]+\.+[a-z]{2,4}) +(.*)$", $address[0], $match)) {
			$address[0] = $match[1];
			$address[1] = $match[2];
		}

		# Format Some Guy someguy@domain.com
		if (eregi("^(.*) +([a-z0-9\._-]+@+[a-z0-9\._-]+\.+[a-z]{2,4})$", $address[0], $match)) {
			$address[0] = $match[2];
			$address[1] = $match[1];
		}

		# Remove any <>
		$address[0] = ereg_replace("\<|\>", "", $address[0]);
		if (isset($address[1]))
			$address[1] = ereg_replace("\<|\>", "", $address[1]);

		# Check if array is reversed
		if (isset($address[1]) && isset($address[0]) &&
			$this->IsValidEmail($address[1]) && ! $this->IsValidEmail($address[0])
		) {
			$temp = $address;
			$address[0] = $temp[1];
			$address[1] = $temp[0];
		}

		$email = Array('address' => $address[0], 'name' => isset($address[1]) ? $address[1] : null);
		return $email;
	}

	///////////////////////////////////////////////////////////////////////////////
	// V A L I D A T I O N   M E T H O D S
	///////////////////////////////////////////////////////////////////////////////

	/**
     * Validation routine for the SMTP host.
     *
     * @param string $host SMTP hostname
     * @return boolean true if host is valid
     */

    function IsValidSmtpHost($host)
    {
        if (COMMON_DEBUG_MODE)
            $this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$network = new Network();

        if (! $network->IsValidHostname($host))
            throw new ValidationException(implode($network->GetValidationErrors(true)));
        return true;
    }

	/**
     * Validation routine for the SMTP port.
     *
     * @param string $port SMTP port
     * @return boolean true if port is valid
     */

    function IsValidSmtpPort($port)
    {
        if (COMMON_DEBUG_MODE)
            $this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$network = new Network();

        if (! $network->IsValidPort($port))
            throw new ValidationException(implode($network->GetValidationErrors(true)));
        return true;
    }

	/**
     * Validation routine for the SMTP SSL type.
     *
     * @param string $ssl SMTP SSL
     * @return boolean true if SSL flag is valid
     */

    function IsValidSmtpSsl($ssl)
    {
        if (COMMON_DEBUG_MODE)
            $this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		$options = $this->GetSslOptions();
		foreach ($options as $key=>$value) {
			if ($key == $ssl)
				return true;
		}
		return false;
    }

	/**
     * Validation routine for the SMTP username.
     *
     * @param string $username SMTP username
     * @return boolean true if username is valid
     */

    function IsValidSmtpUsername($username)
    {
        if (COMMON_DEBUG_MODE)
            $this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		# Username not required
		if (!isset($username) || $username == "" || $username == null)
			return true;

		if (preg_match("/^([a-z0-9_\@\-\.\$]+)$/", $username))
			return true;
		return false;
    }

	/**
     * Validation routine for the SMTP password.
     *
     * @param string $password SMTP password
     * @return boolean true if password is valid
     */

    function IsValidSmtpPassword($password)
    {
        if (COMMON_DEBUG_MODE)
            $this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		# Any invalid conditions here?
		return true;
    }

	/**
     * Validation routine for an email address field.
     *
     * @param string $address email address
     * @return boolean true if email is valid
     */

    function IsValidEmail($address)
    {
        if (COMMON_DEBUG_MODE)
            $this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if(!eregi("^[a-z0-9\._-]+@+[a-z0-9\._-]+\.+[a-z]{2,4}$", $address))
			return false;
        return true;
    }

	/**
     * Validation routine for the subject of the email.
     *
     * @param string $subject  the email subject
     * @return boolean true if subject is valid
     */

    function IsValidSubject($subject)
    {
        if (COMMON_DEBUG_MODE)
            $this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if(eregi("\n", $subject))
			return false;
        return true;
    }

	/**
     * Validation routine for the body of the email.
     *
     * @param string $body  the email body
     * @return boolean true if body is valid
     */

    function IsValidBody($body)
    {
        if (COMMON_DEBUG_MODE)
            $this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        return true;
    }

	/**
     * Validation routine for the HTML body of the email.
     *
     * @param string $html the email HTML body
     * @return boolean true if HTML body is valid
     */

    function IsValidHtmlBody($html)
    {
        if (COMMON_DEBUG_MODE)
            $this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

        return true;
    }

	/**
     * Validation routine for attachments to be sent with e-mail.
     *
     * @param array $attachment - array["/tmp/temp.exe", "temp.exe", "application/octet-stream"]
     * @return boolean true if email is valid
     */

    function IsValidAttachment($attachment)
    {
        if (COMMON_DEBUG_MODE)
            $this->Log(COMMON_DEBUG, "called", __METHOD__, __LINE__);

		if (!is_array($attachment)) {
			$this->AddValidationError(MAILER_LANG_ATTACHMENT . " - " . LOCALE_LANG_INVALID, __METHOD__ ,__LINE__);
			return false;
		}

		// Verify file exists, if data is not in array
		if (isset($attachment['data']))
			return true;
	
		$file = new File($attachment['filename'], true);
		if (!$file->Exists()) {
			$this->AddValidationError(FILE_LANG_ERRMSG_NOTEXIST . $file->GetFilename(), __METHOD__ ,__LINE__);
			return false;
		}
        return true;
    }
}

// vim: syntax=php ts=4
?>
