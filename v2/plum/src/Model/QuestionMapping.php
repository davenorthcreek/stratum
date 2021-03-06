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

use Auth;
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

    private function addOtherWrapper($human, $label, $valueMap) {
        $other = '';
        if (in_array($human, ['Q15', 'Q17', 'Q19', 'Q27', 'Q43', 'Q52', 'Q55', 'Q57', 'Q62',
                          'Q86', 'Q93', 'Q103', 'Q104'])) {
            //need to take care of 'Other'
            $other = "<label class='control-label col-sm-2' for='".$label."[other]'>Other:</label>\n";
            $other.= "<input class='form-control' name='".$label."[Other]' type='text' value='";
            $this->log_debug("Question $human may contain Other");
            $this->var_debug($valueMap);
        }
        return $other;
    }


    public function exportQMToHTML($human, $configs, $qbyq, $candidate, $formResult, $cols) {
        if (in_array($human, ['Q4', 'Q6', 'Q39', 'Q41', 'Q64'])) {
            return;
        }
        $admin_local = Auth::user()->is_admin;
        //$this->log_debug("Exporting ".$this->getWorldAppAnswerName()." to html");
        //subsection header
        if ($this->get("type") == "Subsection") {
            $present = false;
            if ($this->get("Value") == "true") {
                $present = true;
            }
            //$this->dump();
            $label = $this->getWorldAppAnswerName();
            $id = str_replace(" ", "", $label);
            $id = preg_replace("#[\/\(\)-,]#", "", $id);
            $this->log_debug("Subsection $label");
            $this->log_debug("with ID $id");
            echo "<div class='col-xs-12'><div class='panel panel-info'>\n";  //closed in SubsectionEnd below
            echo "<div class='panel-heading'>$label\n";
                echo "<div class='box-tools pull-right'>\n";
                    echo "<button type='button' class='btn btn-box-tool' data-target='#$id'";
                    echo " data-toggle='collapse' title='Collapse/Expand'>\n";
                        echo "<i class='fa fa-arrows'></i>\n";
                    echo "</button>\n";
                echo "</div>\n"; //box-tools
            echo "</div>\n"; //panel-heading
            echo "<div class='panel-body collapse".($present?" in":"")."' id='$id'>\n"; //closed in SubsectionEnd below
            return;
        }
        if ($this->get("type") == "SubsectionEnd") {
            $this->log_debug($human);
            //end of separate panels
            echo "</div>\n</div>\n</div>\n";
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
        if (array_key_exists('valueFound', $values)) {
            unset($values['valueFound']); //flag for section headers -
                                          //we might be able to use this somewhere here
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
            if ($human == "Q93") {
                $this->log_debug("Q Label     : ".$qlabel);
            }
        }
        $answermap = $questionMaps[$qlabel];
        if ($answermap) {
            $waan = $answermap->getWorldAppAnswerName();
        }
        $bh = $this->get("BullhornField");
        if (!$bh && $answermap) {
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
        if ($type == 'numeric') {
            $waan = substr($visible, 0, strpos($visible, ' ')); //only take first word
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
        }
        $visible = preg_replace("/Recommender \d /", "", $visible);
        $qlabel = htmlentities($qlabel, ENT_QUOTES);
        $label = htmlentities($label, ENT_QUOTES);
        $visible = htmlentities($visible, ENT_QUOTES);
        echo "\n<div class='form-group";
        if ($cols==2) {
            echo " col-xs-6";
        }

        echo "'>\n";
        if ($admin_local) {
            echo "\n<button class='btn btn-info btn-sm' style='pointer-events: none;'>".$qlabel."</button>";
        }
        echo "\n<label for='$label'";
        if ($qlabel == "Q113") {
            echo " class='label-danger' ";
        }
        echo ">$visible</label>\n";
        if (strpos($qlabel, 'Q65') === 0) {
            $visible = "Discipline";
        }
        $file = $this->get("configFile");
        if ($type == 'boolean') {
            if ($answermap) {
                $waan = $answermap->get("WorldAppAnswerName");
            } else if ($human == 'Q105') {
                //only if no answermap, use a default
                $waan = 'Admin to Contact References No';
                $answermap = true;
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
            $otherVal = '';
            $other = $this->addOtherWrapper($human, $label, $valueMap);

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
                echo "<option></option>\n"; //empty option
                $first_not_found = [];
                foreach(array_keys($valueMap) as $v) {
                    $first_not_found[$v] = true; //so duplicates are only selected once
                }
                foreach ($configFile as $op) {
                    echo "<option ";
                    if ($valueMap && array_key_exists($op, $valueMap) and $first_not_found[$op]) {
                        echo("SELECTED ");
                        $first_not_found[$op] = false;
                        $this->log_debug("Found $op in select for $human");
                    }
                    $op = htmlentities($op, ENT_QUOTES);
                    echo 'VALUE="'.$op.'">'.$op."</option>\n";
                }
                echo "</select>";
            }
            if ($other) {
                foreach (array_keys($first_not_found) as $answer) {
                    if ($first_not_found[$answer]) {
                        $answer = htmlentities($answer, ENT_QUOTES);
                        $other .= $answer;
                        $this->log_debug("Other value was $answer");
                    }
                }
                $other .= "'>\n";
                echo $other;
            }
        } else if ($type == 'choice' || $type == 'multichoice') {
            $otherVal = '';
            $other = $this->addOtherWrapper($human, $label, $valueMap);
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
            if ($human != "Q57" && $human != "Q62") {
                echo "<option></option>\n"; //empty option
                //empty option messes up "All Listed" option
            }
            $qmap2 = $questionMaps[$human];
            if ($human == "Q103") {
                $my103 = false;
                if ($valueMap) {
                    foreach (array_keys($valueMap) as $vm) {
                        if ($vm) {  // we have anything in there for this qmap
                            //don't overwrite
                            $my103 = true;
                        }
                    }
                }
                if (!$my103) {
                    $valueMap['Yes'] = true;
                }
            }
            if ($human == "Q104") {
                $my104 = false;
                if ($valueMap) {
                    foreach (array_keys($valueMap) as $vm) {
                        if ($vm) {
                            //don't overwrite
                            $my104 = true;
                        }
                    }
                }
                if (!$my104) {
                    $valueMap['No'] = true;
                }
            }
            foreach ($qmap2->get('answerMappings') as $amap) {
                $aval = $amap->get("Value");
                if ($human == "Q23") {
                    $aval = preg_replace("/ \(.*\)/", "", $aval); //everything within parentheses
                }
                if ($human == "Q65") {
                    $aval = preg_replace("/: /", ":", $aval); //remove space after colon
                }
                if ($aval && $aval != "All Listed") { //skip the all listed option
                    echo "<option ";
                    if ($valueMap) {
                        foreach (array_keys($valueMap) as $vm) {
                            if ($human == "Q65") {
                                $vm = preg_replace("/: /", ":", $vm); //remove space after colon
                            }
                            if ($all_listed  || substr($vm, 0, strlen($aval)) === $aval) {
                                $this->log_debug("Found $vm matching $aval in $human");
                                echo "SELECTED ";
                                if ($aval == "Other" && $vm != "All Listed") {
                                    $otherVal = preg_replace("/Other: /", "", $vm);
                                    $otherVal = htmlentities($otherVal, ENT_QUOTES);
                                    $other .= $otherVal;
                                }
                            }
                        }
                    }
                    $aval = htmlentities($aval, ENT_QUOTES);
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
            if ($other) {
                $other .= "'>\n";
                echo $other;
            }
        } else if ($type=='numeric') {
            $qmap2 = $questionMaps[$human];
            $min = 1000000;
            $max = 0;
            foreach ($qmap2->get('answerMappings') as $amap) {
                $aval = $amap->get("Value");
                $min = $aval<$min? $aval : $min;
                $max = $aval>$max? $aval : $max;
            }
            echo("<div class='col-xs-3'>");
            echo("<input class='touchspin$max' type='text' name='$label' id='$label' min='$min' max='$max'>");
            echo("</div>");

        } else if ($human == "Q18"|| $human == "Q111" || $human == "Q112") {
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

    public function checkforBullhornValue($candidate) {
        $bh = $this->get("BullhornField");
        if (in_array($bh, ["skillID", "Note", "customObject1.textBlock3"])) {
            //assume false because these are collections from multiple questions
            return false;
        }
        $valueFromCandidate = $candidate->get($bh);
        if ($valueFromCandidate) {
            $this->log_debug("Looked up $bh in candidate, got $valueFromCandidate, value is present");
            return true;
        } else {
            return false;
        }
    }


    public function getWorldAppAnswerName() {
        //needs to go to children if there is nothing in the parent
        $waan = $this->get("WorldAppAnswerName");
        if (!$waan) {
            $answermap = $this->get("answerMappings");
            if ($answermap) {
                foreach ($answermap as $qmap_child) {
                    $waan2 = $qmap_child->get("WorldAppAnswerName");
                    if ($waan2) {
                        return $waan2;
                    }
                }
                //haven't returned so we have to go deeper
                foreach ($answermap->get("answerMappings") as $qmap_grandchild) {
                    $waan3 = $qmap_grandchild->get("WorldAppAnswerName");
                    if ($waan3) {
                        return $waan3;
                    }
                }
            }
        }
        return $waan;
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
