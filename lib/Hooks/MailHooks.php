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

use OCP\EventDispatcher\IEventDispatcher;
use OCP\Mail\Events\BeforeMessageSent;
use OCP\IConfig;
use OCP\ILogger;
use OCA\GpgMailer\Service\GpgMessageConvertService;


class MailHooks {

	private $config;
	private $gpgMessageConvertService;
	private $logger;
	private $appName;

	public function __construct(IConfig $config, ILogger $logger, IEventDispatcher $dispatcher, GpgMessageConvertService $gpgMessageConvertService, $appName){
		$this->config = $config;
		$this->gpgMessageConvertService = $gpgMessageConvertService;
		$this->logger = $logger;
		$this->appName = $appName;
		$this->dispatcher = $dispatcher;
	}

	public function register() {
		$callback = function (BeforeMessageSent $event) {
			$message = $event->getMessage();
			$this->gpgMessageConvertService->convertGpgMessage($message);
		};
		$this->dispatcher->addListener(BeforeMessageSent::class, $callback);
	}


}