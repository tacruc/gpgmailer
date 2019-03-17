<?php
namespace OCA\GpgMailer\Controller;

use OCA\GpgMailer\Gpg;
use OCP\IConfig;use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Controller;
use OCP\IUserManager;

class KeyController extends Controller {
	private $userId;
	private $userManager;
	private $config;
	private $gpg;

	public function __construct($AppName, IRequest $request, Gpg $gpg, IConfig $config, IUserManager $userManager,  $UserId){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->userManager = $userManager;
		$this->gpg = $gpg;
		$this->config = $config;
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

	public function uploadUserKey($keydata) {
		$email = $this->userManager->get($this->userId)->getEMailAddress();
		$fingerprint = $this->gpg->import($keydata,  $this->userId);
		$keyinfo = $this->gpg->keyinfo($fingerprint,  $this->userId);
		$key_for_email = false;
		foreach ($keyinfo[0]['uids'] as $uid) {
			if ($uid['email'] === $email) {
				$key_for_email = true;
				break;
			}
		}

		if ($key_for_email) {
			$this->gpg->import($keydata);
			return $fingerprint;
		}

	}

}
