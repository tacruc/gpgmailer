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

namespace OCA\GpgMailer\Settings;

use OCA\GpgMailer\Service\Gpg;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Settings\ISettings;

class Personal implements ISettings {

	/** @var IConfig */
	private $config;


	/** @var Gpg */
	private $gpg;

	/** @var string */
	private $appName;

	/** @var IUserManager */
	private $userManager;

	/** @var string */
	private $userId;

	/** @var IURLGenerator */
	private $url;

	/**
	 * Personal constructor.
	 *
	 * @param IConfig $config
	 * @param IUserManager $userManager
	 * @param Gpg $gpg
	 * @param $userId;
	 * @param $appName
	 * @param IURLGenerator $url
	 */
	public function __construct(IConfig $config,
								Gpg $gpg,
								IUserManager $userManager,
								$userId,
								$appName,
								IURLGenerator $url
	) {
		$this->config = $config;
		$this->gpg = $gpg;
		$this->userId = $userId;
		$this->userManager = $userManager;
		$this->appName = $appName;
		$this->url = $url;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$user = $this->userManager->get($this->userId);
		$fingerprint = $this->gpg->getPublicKeyFromEmail($user->getEMailAddress());
		$parameters = [
			'server_pubkey_url' => $this->url->linkToRouteAbsolute("gpgmailer.key.downloadServerKey"),
			'post_url' => $this->url->linkToRouteAbsolute("gpgmailer.key.uploadUserKey")
		];
		if ($fingerprint !== '') {
			$server_keyinfo = print_r($this->gpg->keyinfo($fingerprint),true);
			$server_pubkey = $this->gpg->export($fingerprint);
			$parameters += [
				'pubkey' => $server_pubkey,
				'keyinfo' => $server_keyinfo
			];
		} else {
			$parameters += [
				'pubkey' => '',
				'keyinfo' => ''
			];
		}



		return new TemplateResponse($this->appName, 'settings/personal', $parameters);
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'security';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 */
	public function getPriority() {
		return 50;
	}

}