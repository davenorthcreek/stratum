<?php

namespace Stratum\Test\Controller;

use Stratum\Controller\CandidateController;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

class CandidateControllerTest extends \PHPUnit_Framework_TestCase {
	
	protected $controller;
	protected $candidate;
	protected $formResult;
	protected $log;
	
	protected function setUp() {
		$this->log = new Logger('Stratum');
		$this->log->pushHandler(new StreamHandler('src/log/'.date('Y-m-d').'.log', Logger::DEBUG));
		$this->controller = new \Stratum\Controller\CandidateController();
		$this->candidate = new \Stratum\Model\Candidate();
		$this->candidate->setLogger($this->log);
		$this->controller->setLogger($this->log);
		$formController = new \Stratum\Controller\FormController();
		$entityBody = file_get_contents("formInput2.txt");
		$this->formResult = $formController->parse($entityBody);
		$this->formResult->setLogger($this->log);
		$this->formResult->dump();
		$formController->form->setLogger($this->log);
		$this->candidate = $this->controller->populate($this->candidate, $this->formResult);
	}
	
	//public function testGetIdentity() {
	//	$foundCandidate = $this->controller->getIdentity($this->candidate, $this->formResult);
	//	$this->assertEquals($foundCandidate->getName(), "D B");
	//	//$this->assertNotNull($foundCandidate);
	//}
	
	public function testPopulate() {
		$populated = $this->candidate;
		$populated->dump();
		$this->assertEquals('Canada, United Kingdom', $populated->get("customText9"), "Citizenship");
		$this->assertEquals("David Block", $populated->getName(), "Name");
		$this->assertEquals("25/12/1970", $populated->get("dateOfBirth"), "Date of Birth");
		$this->assertEquals("Yes", $populated->get("confirmAgree"), "ConfirmAgree");
		$this->assertEquals("Yes", $populated->get("additionalCitizenship"), "Additional Citizenship boolean");
		$this->assertEquals('Founder', $populated->get("occupation"), "Job Title");
		$this->assertEquals('Available (not working, immediately available)', $populated->get("status"), "Availability");
		$this->assertEquals("National FIFO", $populated->get("customText6"), "Current Work Pattern");
		$this->assertEquals("1-780-604-2604", $populated->get("workPhone"), "Work Phone Number");
		$this->assertEquals("CAD", $populated->get("customText14"), "Currency");
		$this->assertEquals("Owner (Junior), Consultancy", $populated->get("customText1"), "Company Type Experience");
		$this->assertEquals("French; English; German", $populated->get("customText17"), "Languages (prioritized)");
		$this->assertEquals("Masters - Science", $populated->get("educationDegree"), "Education Completed");
		$this->assertEquals("Trade – Electrical
Economics
Chemical Engineering", $populated->get("specialtyCategoryID"), "Specialty Categories");
		$this->assertEquals("120000 (CAD)", $populated->get("customText20"), "Expected Salary and currency");
		$this->assertEquals(preg_match('/Equivalent Net Salary: 75000, Guaranteed Cash Allowances: 10000/', $populated->get("customTextBlock5")), 1, "Equivalent Net Salary");
		$this->assertEquals(preg_match("/^Geo - Sampling,/", $populated->get("skillID")), 1, "First Geological Skill ID");
		$form = $this->formResult->get("form");
		$wa = $populated->getWorldAppLabel("recommender1_referenceFirstName", $form);
		$this->assertEquals("Recommender 1 Reference First Name", $wa, "Matching bullhorn to WorldApp labels: recommender1_referenceFirstName");
		$custom = $populated->loadCustomObject();
		$this->assertEquals($populated->get("customText15"), $custom->get("int1"), "Size of Project needs to be duplicated to both these fields");
		$this->assertNotNull($custom);
		$this->assertEquals("Yes", $custom->get("text4"), "Would you pass");
		$json = $populated->marshalToJSON();
		$this->assertTrue($this->candidate->compare($populated)); //same object so better be true!
		
	}
}
