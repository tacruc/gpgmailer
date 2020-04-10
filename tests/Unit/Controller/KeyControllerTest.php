<?php

namespace OCA\GpgMailer\Tests\Unit\Controller;


use PHPUnit_Framework_TestCase;

use OCP\AppFramework\Http\TemplateResponse;
use OCA\GpgMailer\Controller\KeyController;


class KeyControllerTest extends PHPUnit_Framework_TestCase {
	private $controller;
	private $userId = 'john';


	public function setUp() {
		$request = $this->getMockBuilder('OCP\IRequest')->getMock();
		$userManager = $this->getMockBuilder('OCP\IUserManager')->getMock();
		$gpg = $this->getMockBuilder('OCA\GpgMailer\Service\Gpg')->getMock();
		$config = $this->getMockBuilder('OCP\IConfig')->getMock();
		$this->controller = new KeyController("gpgmailer",$request, $gpg, $config, $userManager, $this->userId);
	}

	public function testDownloadServerKey() {
		$result = $this->controller->downloadServerKey();

	}

}
