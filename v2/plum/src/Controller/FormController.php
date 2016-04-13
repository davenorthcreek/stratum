<?php
/*
 * FormController.php
 * Controller for interactions with WorldApp form data
 * for transfer of data between WorldApp and Bullhorn
 *
 * Copyright 2015
 * @category    Stratum
 * @package     Stratum
 * @copyright   Copyright (c) 2015 North Creek Consulting, Inc. <dave@northcreek.ca>
 *
 */

namespace Stratum\Controller;


use Log;

class FormController {

	public $form;

	protected $jsonDecoded;

	public function parse($entityBody) {
		if (substr_compare($entityBody, 'data=%7B', 0)) {
			$entityDecoded = urldecode($entityBody);
		} else {
			$entityDecoded = $entityBody; //already decoded
		}
		//string starts with "data="
		$entity2 = substr($entityDecoded, 5);
		$this->jsonDecoded = json_decode($entity2, true);
		$this->setupForm();
		$formResult = new \Stratum\Model\FormResult();
		$formResult->init($this->jsonDecoded, $this->form);
		$questions = $this->mapQuestions($this->jsonDecoded, $this->form);
		$formResult->set("questions", $questions);
		//$formResult->dump();
		return $formResult;
	}

	public function setupForm() {
		$this->form = new \Stratum\Model\Form();
		$this->form->parse_mapping();
		return $this->form;
	}

	public function mapQuestions($jsonDecoded, $form) {
		$questions = [];
		$index = 0;
		foreach($jsonDecoded["response"] as $theQuestion) {
			//echo "Question #".++$index."\n\n";
			//var_dump($theQuestion);
			$question = new \Stratum\Model\Question();
			$question->init($theQuestion, $form);
			//$form = $form->updateMapping($question);
			$questions[] = $question;
		}
		return $questions;
	}


}
