<?php
namespace OCA\GpgMailer\Controller;

use OCA\GpgMailer\Service\Gpg;
use OCP\IConfig;use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Controller;
use OCP\IUserManager;
use OCP\ILogger;

class KeyController extends Controller {
	private $userId;
	private $userManager;
	private $config;
	private $gpg;
	private $logger;

	public function __construct($AppName, IRequest $request, Gpg $gpg, IConfig $config, IUserManager $userManager,  $UserId, ILogger $logger){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->userManager = $userManager;
		$this->gpg = $gpg;
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 *
	 * @NoAdminRequired
	 * @PublicPage:
	 * @NoCSRFRequired:
	 */

	public function downloadServerKey() {
		$fingerprint = $this->config->getAppValue($this->appName, 'GpgServerKey', '');
		if ($fingerprint !== '') {
			$server_pubkey = $this->gpg->export($fingerprint);
			return new DataDownloadResponse($server_pubkey, 'public.asc', 'application/pgp-keys');
		} else {
			return new NotFoundResponse();
		}

	}

	/**
	 * @NoAdminRequired
	 */
	public function uploadUserKey($keydata) {
		$this->logger->debug("User Key uploaded",["app"=> $this->appName]);
		$email = $this->userManager->get($this->userId)->getEMailAddress();
		if (strlen($keydata) === 0){
			return $this->deleteUserKey();
		}
		$fingerprint = $this->gpg->import($keydata,  $this->userId);
		if ($fingerprint) {
			$fingerprint = $fingerprint['fingerprint'];
			$this->logger->debug("Imported Key with fingerprint: ".$fingerprint." into the user keyring", ["app"=> $this->appName]);
			$keyinfo = $this->gpg->keyinfo($fingerprint, $this->userId);
			$key_for_email = false;
			foreach ($keyinfo[0]['uids'] as $uid) {
				if ($uid['email'] === $email) {
					$key_for_email = true;
					break;
				}
			}

			if ($key_for_email) {
				$this->logger->debug("Imported Key with fingerprint: ".$fingerprint." into the system keyring", ["app"=> $this->appName]);
				$this->gpg->import($keydata);
				return new DataResponse(['message'=>"Imported public key"]);
			} else {
				return new DataResponse(['message'=>"Key is not for your email"]);
			}
		}
		return new DataResponse(['message'=>"Key import  Failed"]);
	}

	private function deleteUserKey(){
		$email = $this->userManager->get($this->userId)->getEMailAddress();
		$fingerprint = $this->gpg->getPublicKeyFromEmail($email);

		if ($this->gpg->deletekey($fingerprint)){
			$this->logger->debug("Deleted Key with fingerprint: ".$fingerprint." from the system keyring", ["app"=> $this->appName]);
		};

		if ($this->gpg->deletekey($fingerprint, $this->userId)){
			$this->logger->debug("Deleted Key with fingerprint: ".$fingerprint." from the user keyring", ["app"=> $this->appName]);
		}
		return new DataResponse(['message'=>"Deleted Public Key"]);
	}

}
