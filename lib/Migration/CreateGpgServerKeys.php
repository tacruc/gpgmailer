<?php
/**
 * @copyright Copyright (c) 2017 Arne Hamann <kontakt+github@arne.email>
 *
 * @author Arne Hamann <kontakt+github@arne.email>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\GpgMailer\Migration;

use OCA\GpgMailer\Service\Gpg;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\ILogger;
use OCP\IConfig;


class CreateGpgServerKeys implements IRepairStep {
	private $appName;
	/** @var IConfig */
	private $config;
	/** @var ILogger */
	private $logger;
	/** @var Gpg */
	private $gpg;
	/**
	 * @param IConfig $config
	 * @param Defaults $defaults
	 * @param ILogger $logger
	 * @param IUserManager $userManager
	 */
	public function __construct() {
		$this->appName = "gpgmailer";
		$this->logger = \OC::$server->getLogger();
		$this->config = \OC::$server->getConfig();
		$this->gpg = new Gpg($this->config, \OC::$server->query('Defaults'), $this->logger, \OC::$server->query('UserManager'), $this->appName);

	}
	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'Create server GPG key pair';
	}
	/**
	 * {@inheritdoc}
	 */
	public function run(IOutput $output) {
		$fingerprint = $this->config->getAppValue($this->appName,'GpgServerKey','');
		if($fingerprint === ''){
			$fingerprint = $this->gpg->generateKey();
			$this->logger->info("Created server GPG key pair ".$fingerprint, ['app' => $this->appName]);
		} else {
			$keys = $this->gpg->keyinfo($fingerprint);
			if ($keys === FALSE || $keys === []) {
				$fingerprint = $this->gpg->generateKey();
				$this->logger->info("Created server GPG key pair ".$fingerprint, ['app' => $this->appName]);
			}
		}
		$keys = $this->gpg->keyinfo($fingerprint);
		if ($keys === FALSE || $keys === []) {
			$this->logger->error("Creating server GPG key pair failed. Emails are not going to be signed, expect keys are server keys imported manually", ['app' => $this->appName]);
		}
	}
}
