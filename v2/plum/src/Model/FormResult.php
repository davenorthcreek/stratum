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
				if (!array_key_exists($id, $possibles)) {
                    $id = $q->get("humanQAId");
                }
                if (array_key_exists($id, $possibles)) {
					$qmaps = $possibles[$id];
					foreach ($qmaps as $qm) {
						$qi = $qm->getBestId();
						if ($qi && $id==$qi) {
							$waan = $qm->get("WorldAppAnswerName");
							$answers = $this->getValue($qid, $q, $qm, $answers);
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

	function getValue($qid, $q, $qmap, $answers) {
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
			if (array_key_exists('objectName', $obj)) {
				$value = $obj['objectName'];
			}
		} else {
			$value = $this->find_answer_in_file($q, $qmap, $qid);
			if (!$value) {
				$this->log_debug("Unable to find a value:");
				$qmap->dump();
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
			$answers[$waan]['combined'] = $existing.$separator.$value;
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
        if ($file) {
            $qa = preg_split("/\./", $answerId);
            $aId = $answerId;
            if (count($qa) > 1) {
                $aId = $qa[1];
            } else {
                $q->dump();
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

    public function exportToHTML($form) {
        //$this->dump(); //list of questions with answers (not mappings)
        $form = $this->get('form');
        //$form->dump(); //mappings
        $questionMaps = $form->get('questionMappings');
        $questions = $this->get('questions');
        $qbyq = [];
        foreach ($questions as $q1) {
            $qbyq[$q1->get("humanQuestionId")][] = $q1;
            $qbyq[$q1->get('humanQAId')][] = $q1;
        }
        $sections = $form->get("sections");
        $headers = $form->get("sectionHeaders");
        //expand/collapse all button
        
        for ($i = 0; $i < count($sections); $i++) {
            //foreach ($form->get("sections") as $section) {
            $section = $sections[$i];
            $label = $headers[$i];
            echo '<div class="box box-primary collapsed-box">';
            echo '<div class="box-header with-border">';
            echo "\n\t<h3 class='box-title'>$label</h3>";
            echo "\n\t".'<div class="box-tools pull-right">';
            echo "\n\t\t".'<button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse/Expand"><i class="fa fa-plus"></i></button>';
            echo "\n\t</div>";
            echo "\n</div>";
            echo "\n<div class='box-body' style='display: none;'>\n";
            $sectionQs=null;
            foreach ($section as $qmap) {
                /******************************
                 first pass, find subquestions
                /**************************** */
                $mult = $qmap->get("multipleAnswers"); //boolean
                $type = $qmap->get("type");
                if ($mult && ($type!='choice')) {
                    foreach ($qmap->get("answerMappings") as $q2) {
                        $theId = $q2->getBestId();
                        $sectionQs[$theId] = $q2;
                    }
                } else {
                    $theId = $qmap->getBestId();
                    $sectionQs[$theId] = $qmap;
                }
            }
            foreach ($sectionQs as $human=>$qmap) {

                /****************************************
                 second pass, export to html with answers
                 ************************************** */
                 $qmap->exportQMToHTML($human, $this->get("configs"), $qbyq, $this);
            }
            echo "\n</div>\n";
            echo '<div class="box-footer"></div><!-- /.box-footer-->';
            echo '</div><!-- /.box -->';
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
