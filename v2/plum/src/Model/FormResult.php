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

	//returns an array of labels and answers
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
				if (!array_key_exists($id, $possibles)) {
                    $id = $q->get("humanQAId");
                }
                if (array_key_exists($id, $possibles)) {
					$qmaps = $possibles[$id];
					foreach ($qmaps as $qm) {
						$qi = $qm->getBestId();
						if ($qi && $id==$qi) {
							$waan = $qm->getWorldAppAnswerName();
							$answers = $this->getValue($qid, $q, $qm, $answers);
						}
                    }
				}
			}
			$this->var_debug($answers);
			return $answers;
		} else {
			//not found in first level search
			$this->log_debug("$key not found in $mapType");
		}
	}

	function getValue($qid, $q, $qmap, $answers) {
		$form = $this->get("form");
		$value = "";
        $answers['valueFound'] = true; //only false in one case so far
		$waan = $qmap->getWorldAppAnswerName();
		$column = "";
		$qac = $q->get("humanQACId");
        if ($qmap->get("type")=="choice") {
			$value = $qmap->get("Value");
        } else if ($qmap->get("type")=="numeric") {
			$value = $qmap->get("Value");
        } else if ($qmap->get("type")=="multichoice") {
			$value = $qmap->get("Value");
		} else if ($q->get("value")) {
			$value = $q->get("value");
		} else if ($qmap->get("type")=="boolean") {
			//extract boolean from WorldAppAnswer
			$yn = $qmap->get("WorldAppAnswerName");
			if (preg_match("/yes/i", $yn)) {
				$value = "Yes";
			} else if (preg_match("/no/i", $yn)) {
				$value = "No";
			}
		} else  if ($qmap->get("type")=="object") {
			$obj = $q->get("objects");
			if (array_key_exists('objectName', $obj)) {
				$value = $obj['objectName'];
			} else if (count($obj) > 1) {
                foreach ($obj as $thisObj) {
                    if (array_key_exists("objectName", $thisObj)) {
                        $value .= $thisObj["objectName"].", ";
                    }
                }
                $value = substr($value, 0, strlen($value)-2); //remove last semi-colon and space
                $answers[$waan]['combined'] = $value;
                $answers[$waan]['value'] = $value;
                return $answers;
            }
		} else if ($q->get("objects") && count($q->get("objects")>1)) {
            return $this->extractObjectValuesFromFile($q, $qmap);
        } else {
			$value = $this->find_answer_in_file($q, $qmap, $qid);
			if (!$value) {
				$this->log_debug("$qid Unable to find a value:");
                $answers['valueFound'] = false;
                $this->var_debug($answers);
				//$qmap->dump();
			}
		}
        if ($value == "Other") {
            if ($q->get("value")) {
                $value = "Other: ".$q->get("value");
            }
        }
        if ($qac) {
			$last_number = preg_match("/\.C(\d+)$/", $qac, $num);
			if ($num[1]) {
				$column = $num[1];
				$answers[$column] = $value;
			}
		} else if (array_key_exists($waan, $answers)) {
            $existing = $answers[$waan]['value'];
            if (is_array($answers[$waan]) && array_key_exists('combined', $answers[$waan])) {
                $existing = $answers[$waan]['combined'];
            }
			$separator = ', ';
			if ($waan == 'Regions/Countries Worked' ||
				$waan == 'Regions/Countries Preferred') {
				$separator = '; ';
			}
            if ($value && strpos($existing, $value)===false) {
			    $answers[$waan]['combined'] = $existing.$separator.$value;
            }
		} else {
			$answers[$waan]['value'] = $value;
		}
		return $answers;
	}

    private function find_answer_in_file($q, $qmap, $qid) {
        $value = '';
        $form = $this->get("form");
        $answerId = $q->get("humanQAId");
        if (!$answerId) {
            $this->log_debug("Going for humanQuestionId");
            //$q->dump();
            $answerId = $q->get("humanQuestionId");
        }
        if (!$answerId) {
            $this->log_debug("Going for QAC");
            //$q->dump();
            $answerId = $q->get("humanQACId");
        }
        $question = $form->get_question($qid);
        $file = $qmap->get("configFile");
        if ($file) {
            $qa = preg_split("/\./", $answerId);
            $aId = $answerId;
            if (count($qa) > 1) {
                $aId = $qa[1];
            } else {
                //could be a list of objects to look up in the file
                $obj = $q->get("objects");
                if ($obj && count($obj)>1) {
                    return $this->extractObjectValuesFromFile($q, $obj, $file);
                }
            }
            $this->parse_option_file($file);
            $configs = $this->get("configs");
            if (array_key_exists($file, $configs)) {
                $as = $configs[$file];
                if (array_key_exists($aId, $as)) {
                    $value = $as[$aId];
                } else {
                    $this->log_debug("answer $aId not found");
                    $this->var_debug($as);
                }
            }
        }
        return $value;
    }

    private function extractObjectValuesFromFile($q, $qmap) {
        $waan = $qmap->get("WorldAppAnswerName");
        $file = $qmap->get("configFile");
        $obj = $q->get("objects");
        $this->parse_option_file($file);
        $configs = $this->get("configs");
        $potential_value = [];
        $value = "";
        if (array_key_exists($file, $configs)) {
            $answers = $configs[$file];
            //now we have to parse the objectNames from the objects in $obj
            foreach ($obj as $thisObj) {
                if (is_array($thisObj)) { //could be answerPresent scalar
                    if (array_key_exists("objectName", $thisObj)) {
                        $potential_value[] = $thisObj["objectName"];
                    }
                }
            }
            //now confirm that the values are in the list
            foreach ($potential_value as $potv) {
                foreach ($answers as $aId=>$a) {
                    if ($potv == $a) {
                        $value .= $a.", ";
                    }
                }
            }
            $value = substr($value, 0, strlen($value)-2); //remove last semi-colon and space
        }
        $the_answers[$waan]['value'] = $value;
        $the_answers[$waan]['combined'] = $value;
        if ($value) {
            $the_answers['valueFound'] = true;
        } else {
            $the_answers['valueFound'] = false;
        }
        return $the_answers;
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
        $fullFileName = base_path()."/storage/app/".$theFileName;
		$handle = fopen($fullFileName, "r");
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
			$this->log_debug("Error opening ".$theFileName);
		}
		$configs[$theFileName] = $answers;
		$this->set("configs", $configs);
		return $this;
	}

    public function exportSectionToHTML($form, $section, $header, $qbyq, $candidate, $columns) {
        $this->log_debug("Starting section with $columns columns");
        $answerPresent = false;
        $sectionQs=null;
        $questionMaps = $form->get('questionMappings');
        $subsectionCounter = 1;
        $subsectionValuesPresent = false;
        $inSubsection = false;
        $currentSubsection = null;
        foreach ($section as $qmap) {
            $theId = $qmap->getBestId();
                /******************************
                 first pass, find subquestions
                /**************************** */
            $mult = $qmap->get("multipleAnswers"); //boolean
            $type = $qmap->get("type");
            $this->log_debug("$theId $type");
            if ($type == "Subsection") {
                $sswaan = $qmap->getWorldAppAnswerName();
                $sectionQs[$sswaan] = $qmap;
                $this->log_debug($sswaan);
                $inSubsection = true;
                $currentSubsection = $qmap;
            } else if ($type == "SubsectionEnd") {
                $sectionQs[$type.$subsectionCounter] = $qmap;
                $this->log_debug("SubsectionEnd".$subsectionCounter);
                if ($subsectionValuesPresent) {
                    $currentSubsection->set("Value", "true");
                }
                $currentSubsection = null; //clear for the next one
                $subsectionValuesPresent = false;
                $subsectionCounter++;
                $inSubsection = false;
            } else if ($type == "boolean") {
                $theId = $qmap->getBestId();
                if (array_key_exists($theId, $questionMaps)) {
                    $this->log_debug("using $theId ".$qmap->getWorldAppAnswerName());
                    $sectionQs[$theId] = $qmap;
                }
            } else if ($mult && ($type!='choice') && ($type != "list") && ($type != "multichoice") && ($type != "numeric")) {
                $this->log_debug("Mult and not choice, multichoice, boolean, or list");
                foreach ($qmap->get("answerMappings") as $q2) {
                    $theId = $q2->getBestId();
                    $sectionQs[$theId] = $q2;
                    $this->log_debug("Setting answer $theId ".$q2->get("value"));
                }
            } else {
                $theId = $qmap->getBestId();
                $sectionQs[$theId] = $qmap;
                $thisWaan = $qmap->getWorldAppAnswerName();
                $this->log_debug("$theId default case $thisWaan");
            }
            if ($type != "Subsection" && $inSubsection && !$subsectionValuesPresent) {
                //only look for values if we haven't found any yet

                //exception - Tier and Job Coding filled from Bullhorn, not in answermap
                $valuewaan = $qmap->getWorldAppAnswerName();
                if (!$valuewaan) {
                    //probably a parent of multiples
                    foreach ($qmap->get("answerMappings") as $qmap_answer) {
                        $valuewaan = $qmap_answer->getWorldAppAnswerName();
                        $answers = $this->findByWorldApp($valuewaan);
                        $subsectionValuesPresent = $answers['valueFound'];
                    }
                } else {
                    $answers = $this->findByWorldApp($valuewaan);
                    //$this->dump();
                    //$this->var_debug($answers);
                    if (array_key_exists('valueFound', $answers)) {
                        $subsectionValuesPresent = $answers['valueFound'];
                    }
                }
                if (!$subsectionValuesPresent) {
                    $subsectionValuesPresent = $qmap->checkforBullhornValue($candidate);
                }
            }
        }
        if (array_key_exists("Q3", $sectionQs)) {
            //merge Q3/5/7 into one Nationality widget
            $q_answers = [];
            if (array_key_exists("Q3", $qbyq)) {
                $q_answers = $qbyq["Q3"];
            }
            if (array_key_exists("Q5", $qbyq)) {
                foreach($qbyq["Q5"] as $theq) {
                    $q_answers[] = $theq;
                }
                unset($qbyq["Q5"]);
            }
            if (array_key_exists("Q7", $qbyq)) {
                foreach($qbyq["Q7"] as $theq) {
                    $q_answers[] = $theq;
                }
                unset($qbyq["Q7"]);
            }
            $qbyq["Q3"] = $q_answers;
            unset($sectionQs["Q5"]);
            unset($sectionQs["Q7"]);
        }
        foreach ($sectionQs as $human=>$qmap) {
                /****************************************
                second pass, export to html with answers
                ************************************** */
            $retval = $qmap->exportQMToHTML($human, $this->get("configs"), $qbyq, $candidate, $this, $columns);
        }
    }

	public function dump() {
		$this->log_debug("---------------------------");
		$this->log_debug("Stratum\Model\FormResult");
		foreach ($this->_fields as $key=>$there) {
			if ($key=="questions") {
				foreach ($there as $q=>$a) {
					$this->log_debug("Question");
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
