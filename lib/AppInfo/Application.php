<?php
/**
 * @copyright Copyright (c) 2019, Arne Hamann <gpgmailer@arne.email>.
 *
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
namespace OCA\GpgMailer\AppInfo;


use OCP\AppFramework\App;

use OCA\GpgMailer\Hooks\MailHooks;
use OCA\GpgMailer\Gpg;
#use OCP\App as OCPApp;

class Application extends App  {

	public function __construct(array $urlParams=array()){
		parent::__construct('gpgmailer', $urlParams);

		$container = $this->getContainer();

		/**
		 * Controllers
		 */
		$container->registerService('Config', function($c) {
			return $c->query('ServerContainer')->getConfig();
		});

		$container->registerService('Mailer', function($c) {
			return $c->query('ServerContainer')->getMailer();
		});

		$container->registerService('Gpg', function($c) {
			return new Gpg(
				$c->query('Config'),
				$c->query('Defaults'),
				$c->query('Logger'),
				$c->query('UserManager'),
				$c->query('AppName')
			);
		});

		$container->registerService('MailHooks', function($c) {
			return new MailHooks(
				$c->query('Config'),
				$c->query('Gpg'),
				$c->query('Logger'),
				$c->query('Mailer'),
				$c->query('AppName')
			);
		});
	}



	public function registerHooks() {
		$this->getContainer()->query('MailHooks')->register();
	}

}