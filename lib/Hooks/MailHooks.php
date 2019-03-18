<?php
/**
 * @copyright Copyright (c) 2019, Arne Hamann <gpgmailer@arne.email>.
 *
 * @author Arne Hamann <gpgmailer@arne.email>
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\GpgMailer\Hooks;

use OCA\GpgMailer\Gpg;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\IConfig;
use OCP\ILogger;


class MailHooks {

	private $config;
	private $gpg;
	private $logger;
	private $mailer;
	private $appName;

	public function __construct(IConfig $config, Gpg $gpg, ILogger $logger, IMailer $mailer, $appName){
		$this->config = $config;
		$this->gpg = $gpg;
		$this->logger = $logger;
		$this->mailer = $mailer;
		$this->appName = $appName;
	}

	public function register() {
		$callback = function ($message) {
			$this->convertGpgMessage($message);
		};
		$this->mailer->listen('\OC\Mail', 'preSendMessage', $callback);
	}

	public function convertGpgMessage(IMessage $message) {
		$debugMode = $this->config->getSystemValue('debug', false);
		$encrypt_fingerprints = [];
		foreach ($message->getTo() as $email => $name) {
			$encrypt_fingerprints[] = $this->gpg->getPublicKeyFromEmail($email);
		}
		foreach ($message->getCc() as $email => $name) {
			$encrypt_fingerprints[] = $this->gpg->getPublicKeyFromEmail($email);
		}
		foreach ($message->getBcc() as $email => $name) {
			$encrypt_fingerprints[] = $this->gpg->getPublicKeyFromEmail($email);
		}

		$sign_fingerprints = [$this->config->getAppValue($this->appName, 'GpgServerKey','')];
		if ($this->countValidFingerprint($encrypt_fingerprints) === sizeof($message->getTo()) + sizeof($message->getCc()) + sizeof($message->getBcc())){
			if($this->countValidFingerprint($sign_fingerprints) > 0) {
				if($debugMode) {
					$sign_fingerprints_text = '';
					foreach ($sign_fingerprints as $sign_fingerprint) {
						$sign_fingerprints_text = $sign_fingerprints_text.",".$sign_fingerprint;
					}
					$encrypt_fingerprints_text = '';
					foreach ($encrypt_fingerprints as $encrypt_fingerprint) {
						$encrypt_fingerprints_text = $encrypt_fingerprints_text . "," . $encrypt_fingerprint;
					}
					$this->logger->debug("GPG Mail encrypt and sign Message with encrypt Keys:".$encrypt_fingerprints_text." and sign Keys:".$sign_fingerprints_text,['app'=>$this->appName]);
				}
				$this->encryptSignMessage($encrypt_fingerprints, $sign_fingerprints, $message);
			} else {
				if($debugMode) {
					$encrypt_fingerprints_text = '';
					foreach ($encrypt_fingerprints as $encrypt_fingerprint) {
						$encrypt_fingerprints_text = $encrypt_fingerprints_text . "," . $encrypt_fingerprint;
					}
					$this->logger->debug("GPG Mail encrypt Message with encrypt Keys:".$encrypt_fingerprints_text, ['app'=>$this->appName]);
				}
				$this->encryptMessage($encrypt_fingerprints, $message);
			}
		}  else {
			if($this->countValidFingerprint($sign_fingerprints) > 0) {
				$sign_fingerprints_text = '';
				foreach ($sign_fingerprints as $sign_fingerprint) {
					$sign_fingerprints_text = $sign_fingerprints_text.",".$sign_fingerprint;
				}
				$this->signMessage($sign_fingerprints, $message);
				$this->logger->debug("GPG Mail sign Message with sign Keys:".$sign_fingerprints_text, ['app'=>$this->appName]);
			} else {
				if($debugMode) {
					$this->logger->debug("GPG Mail no encryption and sign keys avalible keeping plain message:\"".$message->getPlainBody()."\"", ['app'=>$this->appName]);
				}
			}
		}
		return $message;
	}

	private function messageContentToString(IMessage $message) {
		$originalMessage = clone $message->getSwiftMessage();
		$originalMessage->getHeaders()->remove('Message-ID');
		$originalMessage->getHeaders()->remove('Date');
		$originalMessage->getHeaders()->remove('Subject');
		$originalMessage->getHeaders()->remove('MIME-Version');
		$originalMessage->getHeaders()->remove('To');
		$originalMessage->getHeaders()->remove('From');
		$originalMessage->getHeaders()->remove('CC');
		$originalMessage->getHeaders()->remove('Bcc');
		$messageString = $originalMessage->toString();
		$lines = preg_split('/(\r\n|\r|\n)/',trim($messageString));
		$lines_count = count($lines);
		for ($i=0; $i < $lines_count; $i++) {
			$lines[$i] = rtrim($lines[$i])."\r\n";
		}
		// Remove excess trailing newlines (RFC3156 section 5.4)
		return rtrim(implode('',$lines))."\r\n";
	}

	private function countValidFingerprint(array $fingerprints) {
		$int = 0;
		foreach ($fingerprints as $f) {
			if ($this->validFingerprint($f)) {
				$int++;
			}
		}
		return $int;
	}
	private function validFingerprint(string $fingerprint) {
		return $fingerprint !== '';
	}

	private function signMessage(Array $sign_fingerprints, IMessage $message) {
		#Append public Key and Autocrypt
		$keydataRaw = $this->gpg->export($sign_fingerprints[0]);
		$keydata = str_replace('-----END PGP PUBLIC KEY BLOCK-----','',str_replace('-----BEGIN PGP PUBLIC KEY BLOCK-----', '', $keydataRaw));
		$keydata = trim($keydata);
		$swiftmessage = $message->getSwiftMessage();
		$swiftmessage->getHeaders()->addParameterizedHeader('Autocrypt', '' ,['addr' => $message->getFrom()[0], 'prefer-encrypt' => 'mutual', 'keydata' => $keydata] );
		$keyattach = $this->mailer->createAttachment($keydataRaw,"public.asc");
		$message->setSwiftMessage($swiftmessage);
		$message->attach($keyattach);
		$swiftmessage = $message->getSwiftMessage();

		#Sign Message
		$signedBody = $this->messageContentToString($message);
		$signature = $this->gpg->sign($sign_fingerprints, $signedBody);
		$swiftmessage->setEncoder(new \Swift_Mime_ContentEncoder_RawContentEncoder);
		$swiftmessage->setChildren(array());
		$swiftmessage->setBoundary('_=_swift_v4_'.time().'_'.md5(getmypid().mt_rand().uniqid('', true)).'_=_');
		$swiftmessage->getHeaders()->get('Content-Type')->setValue('multipart/signed');
		$swiftmessage->getHeaders()->get('Content-Type')->setParameters(array(
			'micalg' => "pgp-sha256",
			'protocol' => 'application/pgp-signature',
			'boundary' => $swiftmessage->getBoundary(),
		));
		#Becarefull with newlines Spaces and other invisible signs in here
		$body = <<<EOT

This is an OpenPGP/MIME signed message (RFC 4880 and 3156)

--{$this->swiftMessage->getBoundary()}
$signedBody
--{$this->swiftMessage->getBoundary()}
Content-Type: application/pgp-signature; name="signature.asc"
Content-Description: OpenPGP digital signature
Content-Disposition: attachment; filename="signature.asc"

$signature

--{$this->swiftMessage->getBoundary()}--
EOT;
		$swiftmessage->setBody($body);
		$swiftmessage->getHeaders()->removeAll('Content-Transfer-Encoding');
		$message->setSwiftMessage($swiftmessage);
	}

	private function encryptMessage(Array $encrypt_fingerprints, IMessage $message) {
		$encryptedBody = $this->messageContentToString($message);
		$encryptedBody = $this->gpg->encrypt($encrypt_fingerprints,$encryptedBody);
		$swiftmessage=$message->getSwiftMessage();
		$swiftmessage->setChildren(array());
		$swiftmessage->setBoundary('_=_swift_v4_'.time().'_'.md5(getmypid().mt_rand().uniqid('', true)).'_=_');
		$swiftmessage->setEncoder(new \Swift_Mime_ContentEncoder_RawContentEncoder);
		$swiftmessage->getHeaders()->get('Content-Type')->setValue('multipart/encrypted');
		$swiftmessage->getHeaders()->get('Content-Type')->setParameters(array(
			'protocol' => 'application/pgp-encrypted',
			'boundary' => $swiftmessage->getBoundary(),
		));
		#Becarefull with newlines Spaces and other invisible signs in here
		$body = <<<EOT
This is an OpenPGP/MIME encrypted message (RFC 4880 and 3156)
--{$swiftmessage->getBoundary()}
Content-Type: application/pgp-encrypted
Content-Description: PGP/MIME version identification

Version: 1

--{$swiftmessage->getBoundary()}
Content-Type: application/octet-stream; name="encrypted.asc"
Content-Description: OpenPGP encrypted message
Content-ID: <0>
Content-Disposition: inline; filename="encrypted.asc"

$encryptedBody

--{$swiftmessage->getBoundary()}--
EOT;
		$swiftmessage->setBody($body);
		$swiftmessage->getHeaders()->removeAll('Content-Transfer-Encoding');
		$message->setSwiftMessage($swiftmessage);
	}

	private function encryptSignMessage(Array $encrypt_fingerprints, Array $sign_fingerprints, IMessage $message) {

		$signedBody = $this->messageContentToString($message);
		$signature = $this->gpg->sign($sign_fingerprints,$signedBody);

		$swiftMessage = $message->getSwiftMessage();
		$swiftMessage->setEncoder(new \Swift_Mime_ContentEncoder_RawContentEncoder);
		$swiftMessage->setChildren(array());
		$swiftMessage->setBoundary('_=_swift_v4_'.time().'_'.md5(getmypid().mt_rand().uniqid('', true)).'_=_');
		$swiftMessage->getHeaders()->get('Content-Type')->setValue('multipart/signed');
		$swiftMessage->getHeaders()->get('Content-Type')->setParameters(array(
			'micalg' => "pgp-sha256",
			'protocol' => 'application/pgp-signature',
			'boundary' => $swiftMessage->getBoundary(),
		));



		//Swiftmailer is automatically changing content type and this is the hack to prevent it
		#Becarefull with newlines Spaces and other invisible signs in here
		$body = <<<EOT

This is an OpenPGP/MIME signed message (RFC 4880 and 3156)
--{$swiftMessage->getBoundary()}
$signedBody
--{$swiftMessage->getBoundary()}
Content-Type: application/pgp-signature; name="signature.asc"
Content-Description: OpenPGP digital signature
Content-Disposition: attachment; filename="signature.asc"

$signature

--{$swiftMessage->getBoundary()}--
EOT;


		$swiftMessage->getHeaders()->removeAll('Content-Transfer-Encoding');

		$signed = sprintf("%s%s",$swiftMessage->getHeaders()->get('Content-Type')->toString(),$body);
		$encryptedBody = $this->gpg->encrypt($encrypt_fingerprints,$signed);

		$swiftMessage->getHeaders()->get('Content-Type')->setValue('multipart/encrypted');
		$swiftMessage->getHeaders()->get('Content-Type')->setParameters(array(
			'protocol' => 'application/pgp-encrypted',
			'boundary' => $swiftMessage->getBoundary()
		));

		#Becarefull with newlines Spaces and other invisible signs in here
		$body = <<<EOT
This is an OpenPGP/MIME encrypted message (RFC 4880 and 3156)
--{$swiftMessage->getBoundary()}
Content-Type: application/pgp-encrypted
Content-Description: PGP/MIME version identification

Version: 1

--{$swiftMessage->getBoundary()}
Content-Type: application/octet-stream; name="encrypted.asc"
Content-Description: OpenPGP encrypted message
Content-ID: <0>
Content-Disposition: inline; filename="encrypted.asc"

$encryptedBody

--{$swiftMessage->getBoundary()}--
EOT;
		$swiftMessage->setBody($body);
		$swiftMessage->getHeaders()->removeAll('Content-Transfer-Encoding');
		$message->setSwiftMessage($swiftMessage);
	}
}