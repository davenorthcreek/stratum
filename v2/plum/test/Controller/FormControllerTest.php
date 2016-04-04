<?php

namespace Stratum\Test\Controller;

use Stratum\Controller\FormController;

class FormControllerTest extends \PHPUnit_Framework_TestCase {
	
	protected $controller;
	protected $candidate;
	protected $entityBody;
	
	protected function setUp() {
		$this->controller = new \Stratum\Controller\FormController();
		$this->candidate = new \Stratum\Model\Candidate();
		$this->entityBody = file_get_contents("formInput4.txt");
	}
	
	//public function testParse() {
	//	$formResult = $this->controller->parse($this->entityBody);
		
		//var_dump($this->controller->form->get("questionMappings"));
	//	$this->assertNotNull($formResult);
	//}
	
	public function testNationality() {
		$formResult = $this->controller->parse($this->entityBody);
		$formResult->dump();
		$q3Answer = $formResult->findByBullhorn("customText9");
		
		$nation = $q3Answer['Nationality'];
		//$form = $this->controller->form;
		//$q3 = $form->get_question("Q3");
		//$q3->dump();
		//var_dump($q3Answer);
		//$q3 is a Question I think
		//$answer = $q3->get("humanQAId");
		//echo $answer."\n";
		//echo $nation."\n";
		$this->assertEquals("Canada, United Kingdom", $nation, "Nationality");
		$q15Answer = $formResult->findByBullhorn("educationDegree");
		$this->assertEquals("Masters - Science", $q15Answer['Education Completed'], "Education Completed");
	}
	public function testMapQuestions() {
		//$questions = $this->controller->mapQuestions($jsonDecoded, $form);
		
	}
	
	public function testUpdate() {
		
	}
	
}
