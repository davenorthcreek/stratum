<?php

namespace Stratum\Test\Controller;

use Stratum\Controller\WorldappController;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;


class WorldappControllerTest extends \PHPUnit_Framework_TestCase {
	
	protected static $controller;
	
	protected $candidate;
	protected $log;
	
	private function getController() {	
		if (!self::$controller) {
			$this->log->debug("Creating new WorldappController in getController");
			self::$controller = new \Stratum\Controller\WorldappController();
		}
		return self::$controller;
	}
	
	public static function setUpBeforeClass() {
		self::$controller = new \Stratum\Controller\WorldappController();
	}
	
	protected function setUp() {
		$this->log = new Logger('Stratum');
		$this->log->pushHandler(new StreamHandler('src/log/'.date('Y-m-d').'.log', Logger::DEBUG));
		$this->candidate = new \Stratum\Model\Candidate();
		$this->candidate->setLogger($this->log);
		self::$controller->setLogger($this->log);
	}
	
	
	public function testGetActiveForm() {
		$form = self::$controller->find_active_form();
		echo "Active form:\nName: ".$form->name."\nID: ".$form->id."\n";
		$form2 = self::$controller->find_form_by_id($form->id);
		echo "Looked up by id:\nName: ".$form2->name."\nID: ".$form2->id."\n";
		
        /**
        foreach ($forms as $form) {
			echo "Found Form ".$form->id." with name ".$form->name."\n";
		}
        **/
        
		$questions = self::$controller->get_questions($form->id);
		echo "Found ".count($questions)." questions\n";
		$qid = "21741440";
		$question = self::$controller->get_question_by_id($form->id, $qid);
		echo "Here is question $qid:\n";
		echo $question->text."\n";
		echo "The Answers:\n";
		foreach ($question->answers as $an) {
			echo "\tID: ".$an->answerId."\n";
			echo "\t".$an->title."\n";
			echo "\tWeight: ".$an->weight."\n";
			echo "\tMandatory? ".($an->mandatory?"true":"false")."\n\n";
		}
		
		/**
		 * <answers>dave@blockhousehold.net</answers>
            	<answers>dave@northcreek.ca</answers>
            	<answers>1-780-604-2602</answers>
            	<answers>1-780-604-2602</answers>
            	<answers>1-780-604-2602</answers>
            	<answers>1</answers>
            	<answers>davidablock</answers>
            	<questionId>21741451</questionId>
            	* **/
		$autofill = ['21741440'=>['123','David','Block','1/1/1970','Married'],
					 '21741491'=>['EX:Geology'],
					 '21741451'=>['dave@blockhousehold.net',
								  'dave@northcreek.ca',
								  '1-780-604-2602',
								  '1-780-604-2602',
								  '1-780-604-2602',
								  '',
								  'davidablock']];
		$email='dave@northcreek.ca';
		$send = self::$controller->sendUrlWithAutofill($form->id, $email, $autofill);
		var_dump($send);
	}
	
	
}
