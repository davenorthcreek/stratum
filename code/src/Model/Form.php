<?php
/*
 * Form.php
 * Base model for form
 * Data model for transfer between WorldApp and Bullhorn
 * 
 * Copyright 2015
 * @category    Stratum
 * @package     Stratum
 * @copyright   Copyright (c) 2015 North Creek Consulting, Inc. <dave@northcreek.ca>
 * 
 */

namespace Stratum\Model;
class Form extends ModelObject
{
    const XML_PATH_LIST_DEFAULT_SORT_BY     = 'catalog/frontend/default_sort_by';
    
    /**
     * Array of attributes codes needed for product load
     *
     * @var array of tag/values
     */
    protected $_fields = [ //put various fields in here
						  'humanToJson'=>'',
						  'jsonToHuman'=>'',
						  'questionConfig'=>"",   //map of configs
						  'questionMappings'=>"", //by Q#
						  'WAMappings'=>"",       //by WA Answer Name
						  'BHMappings'=>"",        //by BH Field
						  'SNMappings'=>""
						  ];
	
	public function get_question($qId) {
		$questionMappings = $this->get("questionMappings");
		if ($questionMappings) {
			if (array_key_exists($qId, $questionMappings)) {
				return $questionMappings[$qId];
			}
		}
	}
	
	public function parse_mapping() {
		//load mapping json file
		$mapping_string = file_get_contents("mapping2.json");
		$mapping = json_decode($mapping_string, true);
		//var_dump($mapping);
		$this->set('humanToJson', $mapping);
		$inverted = array_flip($mapping);
		//echo $inverted["Q21741498"];	//returns Q47	
		$this->set('jsonToHuman', $inverted);
		//Load Question Config file
		$answers = [];
		$handle = fopen("QandA.txt", "r");
		$questionMappings = $this->get("questionMappings");
		$waMappings = $this->get("WAMappings");
		$bhMappings = $this->get("BHMappings");
		$snMappings = $this->get("SNMappings");
		if ($handle) {
			$currentQ = "";
			$mapKey = "";
			$choice_flag = false;
			//for recommenders 1 and 2, we need prefixes
			$bullhorn_prefix = "";
			$wa_prefix = "";
			while (($line = fgets($handle)) !== false
					&& !preg_match("/\*\*\*\*\*/", $line)) {
				// process the line read.
				//answerId first, then text value
				$elements = preg_split("/\s+/", $line);
				$first = $elements[0];
				
				if (preg_match("/Q\d+\.A\d+/", $first)) {
					//full line
					$q = new QuestionMapping();
					$q->set("form", $this);
					$q->set("QAId", $elements[0]);
					if ($choice_flag) {
						//this is just one of the options
						$q->set("type", "choice");
						$value = $this->collectMultiWordString($elements, 1);
						$q->set("Value", $value);  //only time answer value is in QandA.txt!
						$q->set("BullhornField", $currentQ->get("BullhornField"));
						$q->set("WorldAppAnswerName", $currentQ->get("WorldAppAnswerName"));
						$waMappings[$currentQ->get("WorldAppAnswerName")][] = $q;
						$bhMappings[$currentQ->get("BullhornField")][] = $q;
					} else {
						$q->set("type", $elements[1]);							
						$q->set("BullhornField", $bullhorn_prefix.$elements[2]);
						$q->set("BullhornFieldType", $elements[3]);
						//re-assemble world app label
						$waName = $this->collectMultiWordString($elements, 4);
						$q->set("WorldAppAnswerName", $wa_prefix.$waName);
						$waMappings[$wa_prefix.$waName][] = $q;
						$bhMappings[$bullhorn_prefix.$elements[2]][] = $q;
					}
					$questionMappings[$first] = $q;
					$currentQ->add_answer($q);
				} else if (preg_match("/Q\d+/", $first)) {
					//store previous question
					if ($currentQ) {
						$choice_flag = false; //reset
						if ($currentQ->get("multipleAnswers")) {
							$currentQ->set("BullhornField", NULL);
							$currentQ->set("WorldAppAnswerName", NULL);
							//have to avoid finding the parent
						}
						$answers[] = $currentQ;
						$questionMappings[$mapKey] = $currentQ;
						//$currentQ->dump();
						$bullhorn_prefix = "";
						$wa_prefix = "";
					}
					//initial mention of this question
					$currentQ = new QuestionMapping();
					$mapKey = $first;
					$currentQ->set("form", $this);
					$currentQ->set("QId", $first);
					if (count($elements)==2) {
						//just the question number, will be a boolean
					} else if ($elements[1]=="List") {
						//select from a list instead of individual answers
						//echo "Looking up answer for ".$first." in file ".$elements[2]."\n";
						$list_file = $elements[2];
						$currentQ->set("configFile", $list_file);
						$currentQ->set("BullhornField", $elements[3]);
						$waName =  $this->collectMultiWordString($elements, 4);
						$currentQ->set("WorldAppAnswerName", $waName);
						$bhMappings[$elements[3]][] = $currentQ;
						$waMappings[$waName][] = $currentQ;
					} else if ($elements[1] == "multiple") {
						if (count($elements) > 3) {
							$bullhorn_prefix = $elements[2]."_";
						}
						if (count($elements) > 4) {
							$wa_prefix = $this->collectMultiWordString($elements, 3)." ";
						}
						//normal multiple-answer question
					} else if ($elements[1] == "choice") {
						//choose one of the following options, like boolean
						$currentQ->set("BullhornField", $elements[2]);
						$currentQ->set("type", "choice");
						$waName = $this->collectMultiWordString($elements, 3);
						$currentQ->set("WorldAppAnswerName", $waName);
						$choice_flag = true;
					} else if ($elements[1] == 'object') {
						$currentQ->set("BullhornField", $elements[2]);
						$currentQ->set("type", "object");
						$waName = $this->collectMultiWordString($elements, 3);
						$currentQ->set("WorldAppAnswerName", $waName);
						$waMappings[$waName][] = $currentQ;
						$bhMappings[$elements[2]][] = $currentQ;
					} else {
						//this is a normal field assigned to a top-level question ID
						$currentQ->set("type", $elements[1]);
						$currentQ->set("BullhornField", $elements[2]);
						$currentQ->set("BullhornFieldType", $elements[3]);
						//re-assemble world app label
						$waName = $this->collectMultiWordString($elements, 4);
						$currentQ->set("WorldAppAnswerName", $waName);
						$waMappings[$waName][] = $currentQ;
						$bhMappings[$elements[2]][] = $currentQ;
					}
				}
			}
			$answers[] = $currentQ;
			$questionMappings[$mapKey] = $currentQ;
			//$currentQ->dump();
			//echo "FINISHED WITH QandA.txt!!!!!!!!!\n\n";
			fclose($handle);
			$this->set("questionMappings", $questionMappings);
		} else {
			// error opening the file.
			die ("Unable to open form input file");
		}
		$configs = $this->get("questionConfig");
		$configs[] = $answers;
		$this->set("questionConfig", $configs);
		$this->set("WAMappings", $waMappings);
		$this->set("BHMappings", $bhMappings);
		$this->set("SNMappings", $snMappings);
		return $this;
	}
	
	private function collectMultiWordString($elements, $index) {
		//re-assemble label
		$waName = $elements[$index];
		for ($i=$index+1; $i<count($elements); $i++) {
			$waName = $waName." ".$elements[$i];
		}
		$waName = trim($waName); //remove trailing space
		return $waName;
	}

		
	public function dump() {
		$this->log_debug("---------------------------");
		$this->log_debug("Stratum\Model\Form");
		foreach ($this->_fields as $key=>$there) {
			if ($there) {
				$this->log_debug($key.": ");
				$this->var_debug($there);
			}
		}
		$this->log_debug("---------------------------");
	}
	
		
		
}


