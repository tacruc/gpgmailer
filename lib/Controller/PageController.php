<?php
namespace OCA\GpgMailer\Controller;

use OCA\GpgMailer\Gpg;
use OCP\IConfig;use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Controller;

class PageController extends Controller {
	private $userId;
	private $config;
	private $gpg;

	public function __construct($AppName, IRequest $request, Gpg $gpg, IConfig $config,  $UserId){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
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
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		return new TemplateResponse('gpgmailer', 'index');  // templates/index.php
	}

}
