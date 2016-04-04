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
		$entityBody = file_get_contents("formInput4.txt");
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
		$this->assertEquals('Entrepreneur', $populated->get("occupation"), "Job Title");
		$this->assertEquals('Active (considering suitable roles)', $populated->get("status"), "Availability");
		$this->assertEquals("National Residential", $populated->get("customText6"), "Current Work Pattern");
		$this->assertEquals("1-780-604-2602", $populated->get("workPhone"), "Work Phone Number");
		$this->assertEquals("CAD", $populated->get("customText14"), "Currency");
		$this->assertEquals("Owner (Mid Tier), Consultancy", $populated->get("customText1"), "Company Type Experience");
		$this->assertEquals("English; Russian; French", $populated->get("customText17"), "Languages (prioritized)");
		$this->assertEquals("Masters - Science", $populated->get("educationDegree"), "Education Completed");
        echo $populated->get("specialtyCategoryID")."\n";
		//$this->assertEquals("Logistics / Supply Chain
// Chemical Engineering", $populated->get("specialtyCategoryID"), "Specialty Categories");
		$this->assertEquals("120000 (CAD)", $populated->get("customText20"), "Expected Salary and currency");
        echo $populated->get("customTextBlock5")."\n";
		$this->assertEquals(preg_match('/Equivalent Net Salary: 60000, Guaranteed Cash Allowances: 10000/', $populated->get("customTextBlock5")), 1, "Equivalent Net Salary");
        echo $populated->get("skillID")."\n";
		$this->assertEquals(preg_match("/Geo - Sampling/", $populated->get("skillID")), 1, "Geological Skill ID");
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
