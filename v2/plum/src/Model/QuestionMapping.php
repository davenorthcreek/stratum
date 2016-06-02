<?php
/*
 * QuestionMapping.php
 * mapping between WorldApp Question JSON and Bullhorn Candidate Attribute
 * Data model for transfer between WorldApp and Bullhorn
 *
 * Copyright 2015
 * @category    Stratum
 * @package     Stratum
 * @copyright   Copyright (c) 2015 North Creek Consulting, Inc. <dave@northcreek.ca>
 *
 */

namespace Stratum\Model;
class QuestionMapping extends ModelObject
{

    /**
     * Array of attributes codes needed for product load
     *
     * @var array of tag/values
     */
    protected $_fields = [ //put various fields in here
						  'form'=>'',
						  'type'=>'',
						  'QId'=>'',
						  'QAId'=>'',
						  'QACId'=>'',
						  'BullhornField'=>'',
						  'BullhornFieldType'=>'',
						  'configFile'=>'',
						  'WorldAppAnswerName'=>'',
						  'StratumName'=>'',
						  'Value'=>'',
						  'multipleAnswers'=>FALSE,
						  'answerMappings'=>[]
						  ];

	//can be recursive to handle multiple answers

	//multiple Answer Fields (Q2: internal, Q8: Address, Q11 City/2ndary, Q22 Title/Employer) (Q38 Salary) (Q41 Salary) (Q46 AddtnlSalary)
	//	(Q54 idealNextRole) (Q79 CapProjs) (Q95 with A2 admin use only) (Q100, Q101 recommenders)
	//we can have lookups (country lists Q3, Q5, Q7, Q9, Q10)
	//					  (language lists Q12)
	//					  (diploma list Q15)
	//					  (degree list Q17)
	//					  (notice period Q25)
	//					  (FIFO Roster Q29)
	//					  (currency list Q36)
	//					  (mine operations (multi-choice) Q70)
	//					  (Technical Experience (multi-choice) Q71)
	//					  (Project Control Skills (multi-choice) Q80)
	//					  (Q81, Q83, Q88, Q90, Q92, Q96
	//booleans (Q1 tickbox) Q103
	//booleans (Q4, Q6, Q14, Q16, Q39, Q42, Q47 radio button, A1 or A2)
	//Radio Buttons (Q23 status) (Q24 employmentStatus) (Q35 SalaryType) (Q45 Expat/Local) (Q28 Work Pattern) (Q32, Q33 travel) (Q45 Expat/local)
	//multi-choice checkboxes(Q19:Ind. Qual/Memb) (Q26 company experience) (Q55 employPref) (Q56 CompPref) (Q57 MobilityPref) (Q58 RegionPref)
	//						 (Q63 regionExp) (Q64 ClimateExp) (Q65 experience?) (Q78 IndExposure) (Q97)
	//we can have drag/drop multi-choice dropdown (Q20: Pro Qual)
	//we can have related "other" (Q21 tied to Q20 Other) (Q30 tied to Q29 Other) (Q37 tied to Q36 other) (Q62 tied to Q61 other) (Q82->Q81) (Q93->Q92)
	//text Q27
	//section header (null) - shouldn't make it through the JSON Q34 Q40 Q50 Q59 Q66 Q67 Q73 Q74 Q77 Q94 Q99 Q104
	//check box with related answer Q43 day/hour->Q44 rate
	//multi-line text Q48, Q49 Q72 (Q110 hidden)
	//Radio Button Y/N/NA Q51, Q52
	//multi-picker with scale (Q60 career) (Q61 Commodities) (Q68 Expert) (Q75 Mine Geo Skills) (Q76 Mine Engineering)
	//percentage split (Q69 open/underground)
	//Q105 Candidate Reference Number
	//Q106 Q109 hidden boolean
	//Q107 Q108 hidden y/n/other
	//Q111 Hidden Tier dropdown
	//Q112 hidden jtc list
	//Q113 hidden jtc list (suitable)
	//Q114 hidden interviewnotes (multi-line)
	//Q115 full name + checkbox?

	public function add_answer($answer) {
		$answers = $this->get("answerMappings");
		if (count($answers)>0) {
			$this->set("multipleAnswers", TRUE);
			//remove the A1 answers from the parent - no longer relevant
			$this->set("BullhornFieldType", NULL);
			$this->set("QAId", NULL);
			$this->set("Value", NULL);
		} else {
			//so far, single answer, so let's push the A1 answers to the parent
			$this->set("type", $answer->get("type"));
			$this->set("QAId", $answer->get("QAId"));
			$this->set("BullhornFieldType", $answer->get("BullhornFieldType"));
			$this->set("BullhornField", $answer->get("BullhornField"));
			$this->set("WorldAppAnswerName", $answer->get("WorldAppAnswerName"));
			$this->set("Value", $answer->get("Value"));
		}
		$answers[] = $answer;
		$this->set("answerMappings", $answers);
	}

	public function init($question) {
		//$question is a Stratum\Model\Question
		//check_and_add("type", $question);
		//need to load the question index data files from Stratum

	}

	function update($question) {
		//$question is a Stratum\Model\Question
		//need to load the question index data files from Stratum

	}

    public function getBestId() {
        $answerId = $this->get("QACId");
        if (!$answerId) {
            $answerId = $this->get("QAId");
        }
        if (!$answerId) {
            $answerId = $this->get("QId");
        }
        return $answerId;
    }

	function check_and_add($key, $array) {
		if (array_key_exists($key, $array)) {
			$this->set($key, $array[$key]);
		}
		return $this;
	}


    public function exportQMToHTML($human, $configs, $qbyq, $formResult) {
        if ($human == "Q4" || $human == "Q6" || $human == "Q39" || $human == "Q41" || $human == "Q64") {
            return;
        }
        $form = $this->get('form');
        $questionMaps = $form->get('questionMappings');
        $mult = false;
        $valueMap = [];
        $answermap=null;
        $qanswers = [];
        if (array_key_exists($human, $qbyq)) {
            $qanswers = $qbyq[$human]; //an array!
        }
        $values = [];
        foreach ($qanswers as $q) {
            $qlabel = $q->get("humanQACId");
            if (!$qlabel || !array_key_exists($qlabel, $questionMaps)) {
                $qlabel = $q->get("humanQAId");
            }
            if (!$qlabel || !array_key_exists($qlabel, $questionMaps)) {
                $qlabel = $q->get("humanQuestionId");
            }
            $answermap = $questionMaps[$qlabel];
            $mult = $answermap->get('multipleAnswers');
            $values = $formResult->getValue($qlabel, $q, $answermap, $values);
        }
        foreach ($values as $akey=>$value) {
            if (is_numeric($akey)) {
                $valueMap[$value] = $akey;
            } else {
                $valueMap[$value['value']] = 1;
                if (array_key_exists('combined', $value)) {
                    $this->log_debug("combined $akey");
                    $mult = true;
                    $separator = ", ";
                    if ($akey == 'Regions/Countries Worked' ||
        				$akey == 'Regions/Countries Preferred') {
                        $this->log_debug("Regions question: $akey");
                        $this->var_debug($value);
        				$separator = '; ';
        			}
                    foreach(explode($separator, $value['combined']) as $theval) {
                        $valueMap[$theval] = 1;
                    }
                }
            }
        }
        if (count($valueMap)>1) {
            $mult = true;
        }
        $val = htmlentities(implode(',', array_keys($valueMap)), ENT_QUOTES);
        $type = $this->get("type");
        if ($type == "multichoice") {
            $mult = true;
        }
        $id = $this->getBestId();
        $qlabel = '';
        if (!$qanswers) {
            $qlabel = $human;
            //there doesn't have to be an answer to every question
        } else {
            $qlabel = $qanswers[0]->get("humanQACId");
            if (!$qlabel || !array_key_exists($qlabel, $questionMaps)) {
                $qlabel = $qanswers[0]->get("humanQAId");
            }
            if (!$qlabel || !array_key_exists($qlabel, $questionMaps)) {
                $qlabel = $qanswers[0]->get("humanQuestionId");
            }
            //$this->log_debug("Q Label     : ".$qlabel);
        }
        $answermap = $questionMaps[$qlabel];
        $waan = $answermap->get("WorldAppAnswerName");
        $bh = $this->get("BullhornField");
        if (!$bh) {
            $bh = $answermap->get("BullhornField");
            if (!$bh) {
                foreach ($answermap->get("answerMappings") as $q2) {
                    $bh = $q2->get("BullhornField");
                    if ($bh) {
                        break;
                    }
                }
            }
        }
        if (!$waan) {
            //go one deeper, if it is there
            foreach ($answermap->get("answerMappings") as $q2) {
                $waan = $q2->get("WorldAppAnswerName");
                if ($waan) {
                    break;
                }
            }
        }
        if ($bh) {
            $label = $bh;
        } else {
            $label = $qlabel;
        }
        $visible = $waan;
        if ($type == 'boolean') {
            //remove trailing yes or no
            $visible = substr($visible, 0, strrpos($visible, ' '));
        }
        $visible = preg_replace("/Additional Candidate Notes: /", "", $visible);
        //going to put both bullhorn and worldapp in the label
        $label .= "*".$waan."[]";

        //now we repeat for every response in $q
        $answermap = null;
        if ($qanswers) {
            //foreach ($qanswers as $q) {
            //now have to look at $answermap again, based on THIS $qanswer
            $qlabel = $q->get("humanQACId");
            if (!$qlabel || !array_key_exists($qlabel, $questionMaps)) {
                $qlabel = $q->get("humanQAId");
            }
            if (!$qlabel || !array_key_exists($qlabel, $questionMaps)) {
                $qlabel = $q->get("humanQuestionId");
            }
            $answermap = $questionMaps[$qlabel];
            if (strpos($qlabel, 'Q65') === 0) {
                $visible = "Discipline - for display purposes only, will not be changed in Bullhorn";
            }
        }
        echo "\n<div class='form-group'>";
        echo "\n<button class='btn btn-info btn-sm'>".$qlabel."</button>";
        echo("\n<label for='$label'>$visible</label>\n");
        if (strpos($qlabel, 'Q65') === 0) {
            $visible = "Discipline";
        }
        $file = $this->get("configFile");
        if ($type == 'boolean') {
            if ($answermap) {
                $waan = $answermap->get("WorldAppAnswerName");
            }
            //$waan ends with yes or no
            $yn = substr($waan, strrpos($waan, ' '));
            $shorter = substr($waan, 0, strrpos($waan, ' '));
            echo "<label class='radio-inline'><input type='radio' name='$label' value='yes'";
            if ($answermap && strcasecmp($yn, " no")) {
                echo " CHECKED";
            }
            echo ">Yes</label>\n";
            echo "<label class='radio-inline'><input type='radio' name='$label' value='no'";
            if ($answermap && strcasecmp($yn, " yes")) {
                echo " CHECKED";
            }
            echo ">No</label>\n";
        } else if ($file) {

            //may have to create configFile entry
            if (!array_key_exists($file, $configs)) {
                $this->log_debug("looking up $file");
                $configs = $this->parse_option_file($file, $configs);
            }
            //must look up
            if (array_key_exists($file, $configs)) {
                $configFile = $configs[$file];
                //now render a select form input
                echo "<select class='form-control select2' ";
                //if ($mult) {
                    echo "multiple='multiple'";
                //}
                echo " id='$label' data-placeholder='$visible' name='$label'";
                echo " style='width: 100%;'";
                echo ">\n";
                //if (!$mult) {
                //    echo "<option></option>\n";
                //}
                $flag = [];
                foreach(array_keys($valueMap) as $v) {
                    $flag[$v] = true;
                }
                $this->var_debug($flag);
                foreach ($configFile as $op) {
                    echo "<option ";
                    if ($valueMap && array_key_exists($op, $valueMap) and $flag[$op]) {
                        echo("SELECTED ");
                        $flag[$op] = false;
                        $this->log_debug("Found $op in select for $human");
                    }
                    echo 'VALUE="'.$op.'">'.$op."</option>\n";
                }
                echo "</select>";
                $this->var_debug($flag);
            }
        } else if ($type == 'choice' || $type == 'multichoice') {
            $all_listed = false;
            foreach (array_keys($valueMap) as $vm) {
                if ($vm == "All Listed") {
                    $all_listed = true;
                }
            }
            echo "<select class='form-control select2";
            if ($human == "Q57" || $human == "Q62") {
                echo " $human";
            }
            echo "' ";
            if ($type == 'multichoice') {
                echo "multiple='multiple'";
            }
            echo " id='$label' data-placeholder='$visible' name='$label'";
            echo " style='width: 100%;'";
            echo ">\n";
            $qmap2 = $questionMaps[$human];
            if ($human == "Q103") {
                $my103 = false;
                if ($valueMap) {
                    foreach (array_keys($valueMap) as $vm) {
                        if ($vm) {
                            //don't overwrite
                            $my103 = true;
                        }
                    }
                }
                if (!$my103) {
                    $valueMap['No'] = true;
                }
            }
            foreach ($qmap2->get('answerMappings') as $amap) {
                $aval = $amap->get("Value");
                if ($human == "Q23") {
                    $aval = preg_replace("/ \(.*\)/", "", $aval); //everything within parentheses
                }
                if ($aval && $aval != "All Listed") { //skip the all listed option
                    echo "<option ";
                    if ($valueMap) {
                        foreach (array_keys($valueMap) as $vm) {
                            if ($all_listed  || substr($vm, 0, strlen($aval)) === $aval) {
                                $this->log_debug("Found $vm matching $aval in $human");
                                echo "SELECTED ";
                            }
                        }
                    }
                    echo 'VALUE="'.$aval.'">'.$aval."</option>\n";
                }
            }
            echo "</select>";
            if ($human == "Q57" || $human == "Q62") {
                echo '<input type="checkbox" id="'.$human.'_checkbox"';
                if ($all_listed) {
                    echo ' CHECKED';
                }
                echo ' >Select All';
            }
        } else if ($human == "Q110" || $human == "Q111") {
            echo("<textarea class='form-control' name='$label' rows='4' placeholder='Enter...'>$val</textarea>");
        } else if ($human == "Q99") {
            //list of files, not very helpful
            $file_list = explode("," , $val);
            $val = "Candidate uploaded ".count($file_list)." files to WorldApp.";
            echo("\n<label>$val</label>\n");
            $file_count = 1;
            foreach ($file_list as $file_url) {
                echo "<div><a href='$file_url' target='_blank'>File ".$file_count++."</a></div>\n";
            }
        } else {
            echo("<input class='form-control' name='$label' type='text' value='".$val."'>");
        }
            //}
        echo "\n</div>\n";
            //}
        //}
    }

    public function parse_option_file($theFileName, $configs) {
		if (array_key_exists($theFileName, $configs)) {
			return $configs;
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
		return $configs;
	}


	public function dump($recursion = 0) {
		$tab = "";
		for ($i=0; $i<$recursion; $i++) {
			$tab .= "----";
		}
		$this->log_debug($tab."dumping QuestionMapping");
		$this->log_debug($tab."Type:         ".$this->get('type'));
		$this->log_debug($tab."QId:          ".$this->get('QId'));
		$this->log_debug($tab."QAId:         ".$this->get('QAId'));
		$this->log_debug($tab."QACId:        ".$this->get('QACId'));
		$this->log_debug($tab."BullhField:   ".$this->get('BullhornField'));
		$this->log_debug($tab."BullhornFT:   ".$this->get('BullhornFieldType'));
		$this->log_debug($tab."configFile:   ".$this->get('configFile'));
		$this->log_debug($tab."WorldAppAns:  ".$this->get('WorldAppAnswerName'));
		$this->log_debug($tab."StratumName:  ".$this->get('StratumName'));
		$this->log_debug($tab."Value:        ".$this->get('Value'));
		$this->log_debug($tab."multAnswers:  ".($this->get('multipleAnswers')?"TRUE":"FALSE"));
		$mult = $this->get("answerMappings");
		$recursion++; //one level deeper
		foreach ($mult as $sub) {
			$this->log_debug("Sub Question ".$sub->get("QAId"));
			$sub->dump($recursion);
		}
		$this->log_debug($tab."End of QuestionMapping");
	}

}
