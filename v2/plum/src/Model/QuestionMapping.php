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
    /*
    public function exportToHTML($configs) {
        //PREFACE
        $type = $this->get("type");
        $id = $this->getBestId();
        $mult = $this->get("multipleAnswers");
        $waan = $this->get("WorldAppAnswerName");
        $file = $this->get("configFile");
        $answers = [];
        if ($mult && ($type != 'choice')) {
            foreach ($this->get("answerMappings") as $q2) {
                $theId = $q2->getBestId();
                $answers[$theId] = $q2;
                if (!$waan) {
                    $waan = $q2->get("WorldAppAnswerName");
                }
            }
        } else {
            $answers[$id] = $this;
        }
        if ($type == 'boolean') {
            //remove trailing yes or no
            $waanLabel = substr($waan, 0, strrpos($waan, ' '));
        } else {
            $waanLabel = $waan;
        }
        $label = $waanLabel;
        $qlabel = preg_replace("/ /", "_", $waan);
        //OUTPUT
        echo "\n<div class='form-group'>";
        echo "\n<button class='btn btn-info btn-sm'>".$label."</button>";
        echo("\n<label for='$qlabel'>$label</label>\n");
        if ($type == 'boolean') {
            //$waan ends with yes or no
            $yn = substr($waan, strrpos($waan, ' '));
            $shorter = substr($waan, 0, strrpos($waan, ' '));
            echo "<label class='radio-inline'><input type='radio' name='$shorter' value='yes'";
            echo ">Yes</label>\n";
            echo "<label class='radio-inline'><input type='radio' name='$shorter' value='no'";
            echo ">No</label>\n";
        } else if ($file) {
            //must look up
            if (array_key_exists($file, $configs)) {
                $configFile = $configs[$file];
                //now render a select form input
                echo "<select class='form-control' id='$qlabel' name='$qlabel'>\n";
                foreach ($configFile as $op) {
                    echo '<option VALUE="'.$op.'">'.$op."\n";
                }
                echo "</select>";
            }
        } else if ($type == 'choice') {
            echo "<select class='form-control' id='$qlabel' name='$qlabel'>\n";
            foreach ($this->get('answerMappings') as $amap) {
                $aval = $amap->get("Value");
                if ($aval) {
                    echo '<option VALUE="'.$aval.'">'.$aval."\n";
                }
            }
            echo "</select>";
        } else {
            echo("<input class='form-control' type='text' >");
        }
        echo "\n</div>\n";
    }
    */

    public function exportQMToHTML($human, $configs, $qbyq, $formResult) {
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
            if ($human == 'Q27') {
                $this->log_debug("Q27 using $qlabel");
            }
            $answermap = $questionMaps[$qlabel];
            $mult = $answermap->get('multipleAnswers');
            $values = $formResult->getValue($qlabel, $q, $answermap, $values);
        }
        if ($human == 'Q27') {
            $this->var_debug($values);
            $answermap->dump();
        }
        foreach ($values as $akey=>$value) {
            if (is_numeric($akey)) {
                $valueMap[$value] = $akey;
            } else {
                $valueMap[$value['value']] = 1;
                if (array_key_exists('combined', $value)) {
                    $mult = true;
                    foreach(explode(', ', $value['combined']) as $theval) {
                        $valueMap[$theval] = 1;
                    }
                }
            }
        }
        if (count($valueMap)>1) {
            $mult = true;
        }
        if ($human == 'Q27') {
            $this->log_debug("ValueMap");
            $this->var_debug($valueMap);
            if ($mult) {
                $this->log_debug("Multiple is true");
            } else {
                $this->log_debug("Multiple is false");
            }
        }
        $val = implode(',', array_keys($valueMap));
        $noAnswer = false;
        $type = $this->get("type");
        $id = $this->getBestId();
        $waan = $this->get("WorldAppAnswerName");
        $file = $this->get("configFile");
        if (array_key_exists($human, $qbyq)) {
            $qanswers = $qbyq[$human]; //an array!
        }
        $qlabel = '';
        if (!$qanswers) {
            $qlabel = $human;
            $noAnswer = true;
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
        if (!$waan) {
            //go one deeper, if it is there
            foreach ($answermap->get("answerMappings") as $q2) {
                $waan = $q2->get("WorldAppAnswerName");
                if ($waan) {
                    break;
                }
            }
        }
        $label = $qlabel;
        $visible = $waan;
        if ($type == 'boolean') {
            //remove trailing yes or no
            $visible = substr($visible, 0, strrpos($visible, ' '));
        }
        $label .= " ".$visible;
        $label = preg_replace("/\s/", "_", $waan);
        //now we repeat for every response in $q
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
                $this->log_debug(strpos($qlabel, 'Q65'));
                if (strpos($qlabel, 'Q65') === 0) {
                    $visible = "Discipline - for display purposes only, will not be changed in Bullhorn";
                }
                echo "\n<div class='form-group'>";
                echo "\n<button class='btn btn-info btn-sm'>".$qlabel."</button>";
                echo("\n<label for='$label'>$visible</label>\n");
                if (strpos($qlabel, 'Q65') === 0) {
                    $visible = "Discipline";
                }
                if ($q && $answermap) {
                    //there is an answer

                    $file = $this->get("configFile");
                    if ($type == 'boolean') {
                        $waan = $answermap->get("WorldAppAnswerName");
                        //$waan ends with yes or no
                        $yn = substr($waan, strrpos($waan, ' '));
                        $shorter = substr($waan, 0, strrpos($waan, ' '));
                        echo "<label class='radio-inline'><input type='radio' name='$shorter' value='yes'";
                        if (strcasecmp($yn, " no")) {
                            echo " CHECKED";
                        }
                        echo ">Yes</label>\n";
                        echo "<label class='radio-inline'><input type='radio' name='$shorter' value='no'";
                        if (strcasecmp($yn, " yes")) {
                            echo " CHECKED";
                        }
                        echo ">No</label>\n";
                    } else if ($file) {
                        //must look up
                        if (array_key_exists($file, $configs)) {
                            $configFile = $configs[$file];
                            //now render a select form input
                            echo "<select class='form-control select2' ";
                            if ($mult) {
                                echo "multiple='multiple'";
                            }
                            echo " id='$label' data-placeholder='$visible' name='$label'";
                            echo " style='width: 100%;'>\n";
                            foreach ($configFile as $op) {
                                echo "<option ";
                                if (array_key_exists($op, $valueMap)) {
                                    echo("SELECTED ");
                                }
                                echo 'VALUE="'.$op.'">'.$op."\n";
                            }
                            echo "</select>";
                        }
                    } else if ($type == 'choice') {
                        echo "<select class='form-control select2' ";
                        if ($mult) {
                            echo "multiple='multiple'";
                        }
                        echo " id='$label' data-placeholder='$visible' name='$label'";
                        echo " style='width: 100%;'>\n";
                        $qmap2 = $questionMaps[$human];
                        foreach ($qmap2->get('answerMappings') as $amap) {
                            $aval = $amap->get("Value");
                            if ($aval) {
                                echo "<option ";
                                if (array_key_exists($aval, $valueMap)) {
                                    echo "SELECTED ";
                                }
                                echo 'VALUE="'.$aval.'">'.$aval."\n";
                            }
                        }
                        echo "</select>";
                    } else {
                        echo("<input class='form-control' type='text' value='".$val."'>");
                    }
                }
                echo "\n</div>\n";
            //}
        }
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
		$this->log_debug("\n".$tab."End of QuestionMapping\n".$tab);
	}

}
