<?php
/*
 * Question.php
 * Base model for form question dto
 * Data model for transfer between WorldApp and Bullhorn
 * 
 * Copyright 2015
 * @category    Stratum
 * @package     Stratum
 * @copyright   Copyright (c) 2015 North Creek Consulting, Inc. <dave@northcreek.ca>
 * 
 */

namespace Stratum\Model;
class Question extends ModelObject
{
    const XML_PATH_LIST_DEFAULT_SORT_BY     = 'catalog/frontend/default_sort_by';
    
    /**
     * Array of attributes codes needed for product load
     *
     * @var array of tag/values
     */
    protected $_fields = [ //put various fields in here
						  '@type'=>'',
						  'questionId'=>'',
						  'humanQuestionId'=>'',
						  'humanQAId'=>'',
						  'humanQACId'=>'',
						  'weight'=>'',
						  'answerId'=>'',
						  'value'=>'',
						  'scaleId'=>'',
						  'columnNumber'=>'',
						  'objects'=>[] //an interior data model
						  ];

	public function init($theQuestion, $form) {
		$jsonToHuman = $form->get("jsonToHuman");
		$this->check_and_add("@type", $theQuestion);
		$this->check_and_add("questionId", $theQuestion);
		$this->check_and_add("weight", $theQuestion);
		$this->check_and_add("answerId", $theQuestion);
		$this->check_and_add("value", $theQuestion);
		$this->check_and_add("scaleId", $theQuestion);
		$this->check_and_add("columnNumber", $theQuestion);
		$this->check_and_add("objects", $theQuestion);
		$questionId = "";
		$qa = "";
		$qac = "";
		if (array_key_exists('questionId', $theQuestion)) {
			$questionId = $theQuestion['questionId'];
			if (array_key_exists('answerId', $theQuestion)) {
				$answerId = $theQuestion['answerId'];
				$qa = "Q".$questionId.".A".$answerId;
				if (array_key_exists("columnNumber", $theQuestion)) {
					$columnId = $theQuestion['columnNumber'];
					$qac = $qa.".C".$columnId;
				}
			}
		}
		if (array_key_exists("Q".$questionId, $jsonToHuman)) {
			$human = $jsonToHuman["Q".$questionId];
			//echo "Human: ".$human."\n\n";
			$this->set('humanQuestionId', $human);
		}
		if (array_key_exists($qa, $jsonToHuman)) {
			$humanQA = $jsonToHuman[$qa];
			//echo "HumanQA: ".$humanQA."\n\n";
			$this->set('humanQAId', $humanQA);
		}
		if (array_key_exists($qac, $jsonToHuman)) {
			$humanQAC = $jsonToHuman[$qac];
			//echo "HumanQAC: ".$humanQAC."\n\n";
			$this->set('humanQACId', $humanQAC);
		}
		// this seems wrong... no reference to the question here
		if (array_key_exists("answerId", $jsonToHuman)) {
			$aId = $jsonToHuman["answerId"];
			$this->log_debug("$aId: We have an answerId but not a value - must lookup");
			$value = $this->get("value"); //null-protected
		}
	}
	
	function check_and_add($key, $array) {
		if (array_key_exists($key, $array)) {
			$this->set($key, $array[$key]);
		}
		return $this;
	}
	
		
	public function dump() {
		$this->log_debug("---------------------------");
		$this->log_debug("Stratum\Model\Question");
		foreach ($this->_fields as $key=>$there) {
			if ($there) {
				$this->log_debug($key.": ");
				$this->var_debug($there);
			}
		}
		$this->log_debug("---------------------------");
	}
	

	
}


