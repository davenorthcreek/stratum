<?php
/*
 * FormResult.php
 * Base model for form results (list of answers to Questions in a Form)
 * Data model for transfer between WorldApp and Bullhorn
 * 
 * Copyright 2015
 * @category    Stratum
 * @package     Stratum
 * @copyright   Copyright (c) 2015 North Creek Consulting, Inc. <dave@northcreek.ca>
 * 
 */

namespace Stratum\Model;
class FormResult extends ModelObject
{
    const XML_PATH_LIST_DEFAULT_SORT_BY     = 'catalog/frontend/default_sort_by';
    
    /**
     * Array of attributes codes needed for product load
     *
     * @var array of tag/values
     */
    protected $_fields = [ //put various fields in here
						  "form"=>"",
						  "respondentId"=>'',
						  "email"=>'',
						  "surveyId"=>'',
						  "completed"=>'',
						  "submitDateTime"=>'',
						  "responseLabel"=>'',
						  "response"=>[], //array of text
						  "questions"=>[], //an array of Question objects
						  "configs"=>[]
						 ];
	
	public function init($jsonDecoded, $form) {
		$this->set("form", $form);
		$this->set("respondentId", $jsonDecoded["respondentId"]);
		$this->set("email", $jsonDecoded["email"]);
		$this->set("surveyId", $jsonDecoded["surveyId"]);
		$this->set("completed", $jsonDecoded["completed"]);
		$this->set("submitDateTime", $jsonDecoded["submitDateTime"]);
		$this->set("responseLabel", $jsonDecoded["responseLabel"]);
		$this->set("response", $jsonDecoded["response"]); //all questions answered in "response" array
	}
	
	//returns an array of QuestionMapping objects
	private function examine_mappings($key, $mapType) {
		$questions = $this->get("questions");
		$form = $this->get("form");
		$mappings = $form->get($mapType);
		$possibles = [];
		//first loop through the qmappings to find the correct question
		//based on $mappings, may contain T and F or multiple choices
		if (array_key_exists($key, $mappings)) {
			$qmaps = $mappings[$key];
			foreach ($qmaps as $qmap) {
				$qid = $qmap->get("QId");
				if (!$qid) {
					$qid = $qmap->get("QAId");
				}
				if ($qid) {
					$possibles[$qid][] = $qmap;
				}
			}
			//now that we have the list of possible matching qmappings,
			//loop through the answers provided to match answers to values
			$answers = [];
			foreach ($questions as $q) {
				$id = $q->get("humanQuestionId");
				if (array_key_exists($id, $possibles)) {
					$qmaps = $possibles[$id];
					foreach ($qmaps as $qm) {
						$qi = $qm->get("QId");
						if ($qi && $id==$qi) {
							$waan = $qm->get("WorldAppAnswerName");
							$answers = $this->getValue($qid, $q, $qm, $answers);
						} else {
							$qi = $qm->get("QAId");
							if ($qi && $id==$qi) {
								$waan = $qm->get("WorldAppAnswerName");
								$answers = $this->getValue($qid, $q, $qm, $answers);
							}
						}
					}
				} else {
					$id = $q->get("humanQAId");
					if (array_key_exists($id, $possibles)) {
						$qmaps = $possibles[$id];
						foreach ($qmaps as $qm) {
							$qi = $qm->get("QId");
							if ($qi && $id==$qi) {
								$waan = $qm->get("WorldAppAnswerName");
								$answers = $this->getValue($qid, $q, $qm, $answers);
							} else {
								$qi = $qm->get("QAId");
								if ($qi && $id==$qi) {
									$waan = $qm->get("WorldAppAnswerName");
									$answers = $this->getValue($qid, $q, $qm, $answers);
								}
							}
						}
					}
				}
			}
			//var_dump($answers);
			return $answers;
		} else {
			//not found in first level search
			$this->log_debug("$key not found in $mapType");
		}
	}
	
	private function getValue($qid, $q, $qmap, $answers) {
		$form = $this->get("form");
		$value = "";
		$waan = $qmap->get("WorldAppAnswerName");
		$column = "";
		$qac = $q->get("humanQACId");
		if ($q->get("value")) {
			$value = $q->get("value");
		} else if ($qmap->get("type")=="boolean") {
			//extract boolean from WorldAppAnswer
			$yn = $qmap->get("WorldAppAnswerName");
			if (preg_match("/yes/i", $yn)) {
				$value = "Yes";
			} else if (preg_match("/no/i", $yn)) {
				$value = "No";
			}
		} else if ($qmap->get("type")=="choice") {
			$value = $qmap->get("Value");
		} else if ($qmap->get("type")=="object") {
			$obj = $q->get("objects");
			$this->var_debug($obj);
			if (array_key_exists('objectName', $obj)) {
				$value = $obj['objectName'];
			}
		} else {
			$answerId = $q->get("humanQAId");
			if (!$answerId) {
				$this->log_debug("Going for humanQuestionId");
				$q->dump();
				$answerId = $q->get("humanQuestionId");
			}
			if (!$answerId) {
				$this->log_debug("Going for QAC");
				$q->dump();
				$answerId = $q->get("humanQACId");
			}
			$question = $form->get_question($qid);
			$file = $qmap->get("configFile");
			$qa = preg_split("/\./", $answerId);
			$aId = $qa[1];
			if ($file) {
				$this->parse_option_file($file);
				$configs = $this->get("configs");
				if (array_key_exists($file, $configs)) {
					$as = $configs[$file];
					//$this->log_debug($file." was loaded into memory");
					if (array_key_exists($aId, $as)) {
						$value = $as[$aId];
					} else {
						$this->log_debug("answer $aId not found");
						$this->var_debug($as);
					}
				}
			}
			if (!$value) {
				$this->log_debug("Unable to find a value:");
				$q->dump();
				$qmap->dump();
				die("Unable to find a value");
			}
		}
		if (array_key_exists($waan, $answers)) {
			$existing = $answers[$waan];
			$separator = ', ';
			if ($waan == 'Regions/Countries Worked' ||
				$waan == 'Regions/Countries Preferred') {
				$separator = '; ';
			}
			$answers[$waan] = $existing.$separator.$value;
		} else {
			$answers[$waan] = $value;
		}
		if ($qac) {
			$last_number = preg_match("/\.C(\d+)$/", $qac, $num);
			if ($num[1]) {
				$column = $num[1];
				$answers[$column] = $value;
			}
		}
		return $answers;
	}
	
	public function findByWorldApp($key) {
		return $this->examine_mappings($key, "WAMappings");
	}
	
	public function findByBullhorn($key) {
		return $this->examine_mappings($key, "BHMappings");
	}
	
	public function findByQId($key) {
		return $this->examine_mappings($key, "questionMappings");
	}
	
	public function findByStratumName($key) {
		return $this->examine_mappings($key, "SNMappings");
	}	
	
	public function parse_option_file($theFileName) {
		$configs = $this->get("configs");
		if (array_key_exists($theFileName, $configs)) {
			return $this;
		}
		//load provided txt file
		$answers = [];
		$handle = fopen($theFileName, "r");
		if ($handle) {
			while (($line = fgets($handle)) !== false) {
				// process the line read.
				//answerId first, then text value
				$keyvalue = preg_split("/[\s]+/", $line, 2);
				$answers[$keyvalue[0]]=trim($keyvalue[1]);
				//$this->log_debug("Answer: ".$keyvalue[0]." Value: ".$keyvalue[1]."");
			}
			fclose($handle);
		} else {
			$this->log_debug("Error opening ".$file."");
		}
		$configs[$theFileName] = $answers;
		$this->set("configs", $configs);
		return $this;
	}
	
	public function dump() {
		$this->log_debug("---------------------------");
		$this->log_debug("Stratum\Model\FormResult");
		foreach ($this->_fields as $key=>$there) {
			if ($key=="questions") {
				foreach ($there as $q=>$a) {
					$this->log_debug("Question ");
					$this->log_debug($a->get("humanQuestionId")." ");
					$this->log_debug($a->get("humanQAId")." ");
					$this->log_debug($a->get("humanQACId")."");
					$val = $a->get("value");
					if ($val) {
						$this->log_debug("Value: $val");
					}
					$obj = $a->get("objects");
					if ($obj) {
						$obj_str = json_encode($obj, true);
						$this->log_debug("Objects: $obj_str");
					}
				}
			} else if ($key=="response") {
				$this->log_debug("response exists");
			} else if ($key=="configs") {
				$this->log_debug("configs exists");
			} else if ($key=="form") {
				$this->log_debug("form exists");
			} else if ($there) {
				$this->log_debug($key.": ");
				$this->var_debug($there);
			}
		}
		$this->log_debug("---------------------------");
	}
	
}
