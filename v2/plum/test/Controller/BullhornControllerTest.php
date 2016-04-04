<?php

namespace Stratum\Test\Controller;

use Stratum\Controller\BullhornController;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;


class BullhornControllerTest extends \PHPUnit_Framework_TestCase {
	
	protected static $controller;
	
	protected $candidate;
	protected $log;
	
	private function getController() {	
		if (!self::$controller) {
			$this->log->debug("Creating new BullhornController in getController");
			self::$controller = new \Stratum\Controller\BullhornController();
		}
		return self::$controller;
	}
	
	public static function setUpBeforeClass() {
		self::$controller = new \Stratum\Controller\BullhornController();
	}
	
	protected function setUp() {
		$this->log = new Logger('Stratum');
		$this->log->pushHandler(new StreamHandler('src/log/'.date('Y-m-d').'.log', Logger::DEBUG));
		$this->candidate = new \Stratum\Model\Candidate();
		$this->candidate->setLogger($this->log);
		self::$controller->setLogger($this->log);
	}
	
/**
	public function testSearch() {
		$candidates = self::$controller->search("Disney");
		$this->candidate = $candidates[0];
		$this->assertNotNull($this->candidate);
	}
**/
	public function testLoad10809() {
		$this->candidate->set("id", 10809);
		self::$controller->load($this->candidate);
		$this->candidate->dump();
		$this->assertNotNull($this->candidate);
        echo "SkillID field:\n".$this->candidate->get("skillID")."\n";
	}	

/**
	public function testSubmitCandidate() {
		$candidateController = new \Stratum\Controller\CandidateController();
		$candidateController->setLogger($this->log);
		$formController = new \Stratum\Controller\FormController();
		$entityBody = file_get_contents("formInput4.txt");
		$formResult = $formController->parse($entityBody);
		$this->candidate = $candidateController->populate($this->candidate, $formResult);
		$this->candidate->set("id", 10809);  //to match what was already uploaded			
		$result = self::$controller->submit($this->candidate);
		$this->assertTrue(array_key_exists('changedEntityId', $result));
		$this->assertEquals($result['changedEntityId'], $this->candidate->get("id"));
		$search = new \Stratum\Model\Candidate();
		$search->set("id", 10809);
		$search = self::$controller->load($search);
		$search->dump();
		$this->assertTrue($this->candidate->compare($search), "Comparison between loaded and provided Candidate objects");
	}
**/

	public function testLoginDisplayBhRestToken() {
		self::$controller->login_only();
	}

	
}
