<?php
/*
 * BullhornController.php
 * Controller for interacting with Bullhorn
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

class BullhornController {

	//allow someone to pass in a $logger
	protected $_logger;

	public function setLogger($lgr) {
		//$lgr better be a logger of some sort -missing real OOP here
		$this->_logger = $lgr;
	}

	function var_debug($object=null) {
		ob_start();                    // start buffer capture
		var_dump( $object );           // dump the values
		$contents = ob_get_contents(); // put the buffer into a variable
		ob_end_clean();                // end capture
		$this->log_debug( $contents ); // log contents of the result of var_dump( $object )
	}


	protected function log_debug($str) {
		if (!is_null($this->_logger)) {
			$e = debug_backtrace(true, 2);
			//$this->_logger->debug(var_dump($e[0]));
			$result = date("Ymd H:i:s");
			$result .= ":";
			$result .= $e[1]["line"];
			$result .= ":";
			$result .= $e[1]['function'];
			$result .= ': '.$str;
			$this->_logger->debug($result);
		} else {  //no logger configured
			\Log::debug( $str);
		}
	}

	protected $bullhornClient;

	private function getClient() {
		if (!$this->bullhornClient) {
			$this->log_debug("Creating new Bullhorn client");
			$this->bullhornClient = new \Stratum\Client\Bullhorn();
			$this->bullhornClient->setLogger($this->_logger);
			$this->bullhornClient->init();
		}
		return $this->bullhornClient;
	}

	public function login_only() {
		$bullhornClient = $this->getClient();
		echo $bullhornClient->getSessionKey();
	}

	public function search($search_term) {
		$bullhornClient = $this->getClient();
		$candidateController = new \Stratum\Controller\CandidateController();
		$candidateController->setLogger($this->_logger);

		$candidates = $bullhornClient->search($search_term);
		//$ids is an array of IDs returned by the search

		return $candidates;
	}

    public function loadCorporateUser(\Stratum\Model\CorporateUser $user) {
		$bullhornClient = $this->getClient();
		$userController = new \Stratum\Controller\CorporateUserController();
		$userController->setLogger($this->_logger);

		//load $user based on ID currently in $user
		$user = $bullhornClient->findCorporateUser($user);
		if ($user == null) {
			//error condition according to Stratum
			//re-initialize
			$user = new \Stratum\Model\CorporateUser();
			$user->setLogger($this->_logger);
		}
		return $user;
	}

	public function findCorporateUserByName($name) {
		$bullhornClient = $this->getClient();
		$user = $bullhornClient->findCorporateUserByName($name);
		return $this->loadCorporateUser($user);
	}

	public function updateCandidateStatus($candidate, $status) {
		$bullhornClient = $this->getClient();
		$dummy = new \Stratum\Model\Candidate();
		$dummy->set("id", $candidate->get("id"));
		$dummy->set("preferredContact", $status);
		$decoded = $bullhornClient->update_candidate($dummy);
	}

	public function findAssocCandidates(\Stratum\Model\CorporateUser $cuser) {
		$bullhornClient = $this->getClient();

		$id = $cuser->get("id");
		if (!$id) {
			return null;
		}
		$candidates = $bullhornClient->findAssocCandidatesIndexed($cuser);
		if ($candidates == null) {
			//error condition according to Stratum
		}
		return $candidates;
	}

	public function loadFully(\Stratum\Model\Candidate $candidate) {
		$candidate = $this->load($candidate);
		$this->log_debug("We have the candidate loaded - now the extras");
		if ($candidate->get("id")) {
			$bullhornClient = $this->getClient();
			$this->log_debug("Loading notes");
			$candidate->set("notes", $bullhornClient->find_note($candidate));
			$this->log_debug("Loading references");
			$raw_references = $bullhornClient->find_candidate_references($candidate);
			$ref_objs = [];
			foreach($raw_references as $raw_ref) {
				$ref_obj = new \Stratum\Model\CandidateReference();
				$ref_obj->populateFromData($raw_ref);
				$ref_objs[] = $ref_obj;
			}
			$candidate->set("references", $ref_objs);
			$this->log_debug("Loading custom object 1");
			$raw_obj = $bullhornClient->find_custom_object($candidate);
			$co = new \Stratum\Model\CustomObject();
			$co->populateFromData($raw_obj);
			$candidate->set("customObject1s", $co);
		}
		return $candidate;
	}

	public function load(\Stratum\Model\Candidate $candidate) {
		$bullhornClient = $this->getClient();
		$candidateController = new \Stratum\Controller\CandidateController();
		$candidateController->setLogger($this->_logger);

		//load $candidate based on ID currently in $candidate
		$candidate = $bullhornClient->find($candidate);
		if ($candidate == null) {
			//error condition according to Stratum
			//re-initialize
			$candidate = new \Stratum\Model\Candidate();
			$candidate->setLogger($this->_logger);
		}

		return $candidate;
	}

	public function submit_files(\Stratum\Model\Candidate $candidate) {
		$bullhornClient = $this->getClient();
		$bullhornClient->submit_files($candidate);
	}

	public function submit_file_as_string($candidate, $filename, $body, $type) {
		$bullhornClient = $this->getClient();
		$decoded = $bullhornClient->submit_file_as_string($candidate, $filename, $body, $type);
		$this->log_debug($decoded);
	}

	public function submit(\Stratum\Model\Candidate $candidate) {
		$bullhornClient = $this->getClient();

		$retval = $bullhornClient->submit_candidate($candidate);

		if (array_key_exists("changedEntityId", $retval)) {
			//we have a successful submission
			$this->submit_references($candidate);
			$this->submit_custom_object($candidate);
            $bullhornClient->submit_skills($candidate);
			$bullhornClient->submit_categories($candidate);
			$bullhornClient->submit_specialties($candidate);
			$bullhornClient->submit_note($candidate);
		}
		//returns an array with 'error' or 'id' and other data
		return $retval;
	}

	public function submitPDF(\Stratum\Model\Candidate $bhcandidate,
							  \Stratum\Model\Candidate $wacandidate,
							  \Stratum\Model\Candidate $rqcandidate
							 ) {
		$this->log_debug("At submitPDF");

		$candidates['bh'] = $bhcandidate;
		$candidates['wa'] = $wacandidate;
		$candidates['rq'] = $rqcandidate;

		//going to use FormResponse (web form) as a template for the pdf.
		//so I will get the section headers - but only display section if
		//there is content.

		$formResult = $wacandidate->get("formResult");
		$form = $formResult->get("form");	      //parsed QandA.txt to get questions in order
		$sections = $form->get("sections");       //and sections
		$headers = $form->get("sectionHeaders");  //with appropriate labels
		$sectionData = [];
		for ($i = 0; $i < count($sections); $i++) {
			$section = $sections[$i];
			$label = $headers[$i];
			$retval = $this->exportSectionToPDF($form, $section, $label, $candidates);
			if ($retval) {
				$sectionData[$label] = $retval;
			}
		}
		$sectionData['Skills'] = $this->convertSkillsForPDF($candidates);
		$sectionData['Recommenders'] = $this->convertReferencesForPDF($candidates);
		$sectionData['Additional Tab'] = $this->convertCustomObjForPDF($candidates, $form);
		$sectionData['Notes'] = $this->convertNotesForPDF($candidates);
		return $sectionData;
	}

	private function exportSectionToPDF($form, $section, $label, $candidates) {
		$retVal = [];
		$questionMaps = $form->get('questionMappings');
        foreach ($section as $qmap) {  //qmaps for each question in a section
            $theId = $qmap->getBestId();
                /******************************
                 first pass, find subquestions
                /**************************** */
            $mult = $qmap->get("multipleAnswers"); //boolean
            $type = $qmap->get("type");
            if ($type == "boolean") {
                if (array_key_exists($theId, $questionMaps)) {
                    $sectionQs[$theId] = $qmap;
                }
            } else if ($mult && ($type!='choice') && ($type != "list") && ($type != "multichoice")) {
                foreach ($qmap->get("answerMappings") as $q2) {
                    $theId = $q2->getBestId();
                    $sectionQs[$theId] = $q2;
                }
            } else {
                $theId = $qmap->getBestId();
                $sectionQs[$theId] = $qmap;
            }
        }
        if (array_key_exists("Q3", $sectionQs)) {
            //Q3/5/7 were merged into one Nationality widget
			//just display Nationality once
            unset($sectionQs["Q5"]);
            unset($sectionQs["Q7"]);
        }

		//make a cache of longest value to put at end - usually the same value repeated
		$too_long = 70;  //less than 70 is no bother
		$combine_anyway = array("Net Salary", "Gross Salary",
		                        "Education Completed: Degree", "Education Completed: Diploma");
		$put_at_end = [];

		foreach ($sectionQs as $human=>$qmap) {

                /****************************************
                second pass, export to PDF with answers
                ************************************** */
            $value = $this->exportQMToPDF($qmap, $human, $form, $candidates);
			if ($value && ($value['bhvalue'] || $value['wavalue'] || $value['rqvalue'])) {
				$wa = $value['wa'];
				$plum_value = $value['rqvalue'];
				if ($plum_value &&
				   (strlen($plum_value) >= $too_long || in_array($wa, $combine_anyway))
				    ) {
					$put_at_end[$plum_value][] = $value;
				} else {
					$retVal[$wa]['Question'][] = $value['wa'];
					$retVal[$wa]['Bullhorn'] = $value['bhvalue'];
					$retVal[$wa]['WorldApp'] = $value['wavalue'];
					$retVal[$wa]['Plum'] = $value['rqvalue'];
					$retVal[$wa]['repeat'] = 1;
				}
			}
		}
		foreach ($put_at_end as $plum=>$values) {
			$count = count($values);
			$first = true;
			foreach ($values as $value) {
				$wa = $value['wa'];
				$retVal[$wa]['Question'][] = $value['wa'];
				$retVal[$wa]['Bullhorn'] = $value['bhvalue'];
				$retVal[$wa]['WorldApp'] = $value['wavalue'];
				if ($first) {
					$first = false;
					$retVal[$wa]['Plum'] = $value['rqvalue'];
					$retVal[$wa]['repeat'] = $count;
				}
			}
		}
		return $retVal;
	}

	private function exportQMToPDF($qmap, $human, $form, $candidates) {
		$bh = $qmap->get("BullhornField");
		$wa = $qmap->get("WorldAppAnswerName");
		$yn = '';
		if (!$bh) {
            foreach ($qmap->get("answerMappings") as $q2) {
                $bh = $q2->get("BullhornField");
                if ($bh) {
					if (!$wa) {
						$wa = $q2->get("WorldAppAnswerName");
					}
                    break;
                }
            }
        }
		if (strpos($bh, 'customObject')===0 || $bh == 'Note') {
			return null;
		}
		if ($bh == "customTextBlock2" && $qmap->get("type") == "Text") {
			return null; //only need one 'Discipline' field
		}
		foreach ($candidates as $src=>$candidate) {
			if ($src == 'wa') {
				$fr = $candidate->get("formResult");
				$value = $fr->findByWorldApp($wa);
				$this->log_debug("looking up $wa for PDF");
				$this->var_debug($value);
			} else {
				$value = $candidate->get($bh);
			}
			$result_split = [];
			$val_condensed = $candidate->get_a_string($value);
			$value_split = preg_split("/[,;]\s/", $val_condensed);
	        foreach ($value_split as $val) {
	            if (!in_array($val, $result_split)) {
	                $result_split[] = $val;
	            }
	        }
	        $value = implode(', ', $result_split);
			$ret[$src.'value'] = $value;
		}
		//special fields (for formatting)
		if ($bh == 'skillID' && $ret['rqvalue']) {
			//overwrite if there is something there
			$ret['rqvalue'] = 'See Skills Section below';
		}
		if ($bh == 'specialtyCategoryID') {
			$ret['rqvalue'] = $ret['wavalue'];
		}
		if ($bh == 'files') {
			$url = $ret['wavalue'];
			$url = preg_replace("|file|", "file<br>", $url);
			$ret['wavalue'] = $url;
		}
		if ($qmap->get("type") == 'boolean') {
			$shorter = substr($wa, 0, strrpos($wa, ' '));
			$wa = $shorter;
		}
		$ret['bh'] = $bh;
		$ret['wa'] = $wa;
		return $ret;
	}

	private function convertSkillsForPDF($candidates) {
		//going to discard all but rq candidate (Plum)
		$candidate = $candidates['rq'];
		$skills = $candidate->get("skillID");
		$skill_output = "";
		if ($skills) {
			foreach (preg_split("/\n/", $skills) as $skill) {
				$skill_output .= $skill."<br>\n";
			}
		}
		$ret['List of Skills']['Question'][] = "List of Skills";
		$ret['List of Skills']['Bullhorn'] = '';
		$ret['List of Skills']['WorldApp'] = '';
		$ret['List of Skills']['Plum'] = $skill_output;
		$ret['List of Skills']['repeat'] = 1;
		return $ret;
	}

	private function convertReferencesForPDF($candidates) {
		$bh_refs = $candidates['bh']->get("references");
		$wa_refs = $candidates['wa']->get("references"); //keyed by 'recommenderX'
		$plum_refs = $candidates['rq']->get("references");
		$this->var_debug($bh_refs);
		$this->var_debug($wa_refs);
		$this->var_debug($plum_refs);
		//sort by email (probably not repeated)
		$by_email = [];
		for ($i = 0; $i<count($bh_refs); $i++) {
			$bh_r = $bh_refs[$i];
			$bh_email = $bh_r->get("referenceEmail");
			$by_email[$bh_email]['bh'] = $bh_r;
		}
		for ($i = 0; $i<count($plum_refs); $i++) {
			$plum = $plum_refs[$i];
			$pl_email = $plum->get("referenceEmail");
			$by_email[$pl_email]['rq'] = $plum;
		}
		$index = 0;
		for ($i = 0; $i<count($wa_refs); $i++) {
			$index++;
			$wa_r = $wa_refs['recommender'.$index];
			$wa_email = $wa_r->get("referenceEmail");
			$by_email[$wa_email]['wa'] = $wa_r;
		}
		$refData = [];
		$index = 0;
		foreach($by_email as $email=>$refs) {
			$index++;
			$refData["firstName$index"]['Question'][] = "Recommender $index First Name";
			$refData["lastName$index"]['Question'][] = "Recommender $index Last Name";
			$refData["employer$index"]['Question'][] = "Recommender $index Company / Employer";
			$refData["title$index"]['Question'][] = "Recommender $index Job Title";
			$refData["phone$index"]['Question'][] = "Recommender $index Phone Number";
			$refData["email$index"]['Question'][] = "Recommender $index Email";
			$refData["relationship$index"]['Question'][] = "Recommender $index Your Relationship with the Recommender";
			if (array_key_exists("bh", $refs)) {
				$ref = $refs['bh'];
				$refData["firstName$index"]['Bullhorn'] = $ref->get("referenceFirstName");
				$refData["lastName$index"]['Bullhorn'] = $ref->get("referenceLastName");
				$refData["employer$index"]['Bullhorn'] = $ref->get("companyName");
				$refData["title$index"]['Bullhorn'] = $ref->get("referenceTitle");
				$refData["phone$index"]['Bullhorn'] = $ref->get("referencePhone");
				$refData["email$index"]['Bullhorn'] = $ref->get("referenceEmail");
				$refData["relationship$index"]['Bullhorn'] = $ref->get("customTextBlock1");
			} else {
				$refData["firstName$index"]['Bullhorn'] = '';
				$refData["lastName$index"]['Bullhorn'] = '';
				$refData["employer$index"]['Bullhorn'] = '';
				$refData["title$index"]['Bullhorn'] = '';
				$refData["phone$index"]['Bullhorn'] = '';
				$refData["email$index"]['Bullhorn'] = '';
				$refData["relationship$index"]['Bullhorn'] = '';
			}

			if (array_key_exists("rq", $refs)) {
				$plum = $refs['rq'];
				$refData["firstName$index"]['Plum'] = $plum->get("referenceFirstName");
				$refData["lastName$index"]['Plum'] = $plum->get("referenceLastName");
				$refData["employer$index"]['Plum'] = $plum->get("companyName");
				$refData["title$index"]['Plum'] = $plum->get("referenceTitle");
				$refData["phone$index"]['Plum'] = $plum->get("referencePhone");
				$refData["email$index"]['Plum'] = $plum->get("referenceEmail");
				$refData["relationship$index"]['Plum'] = $plum->get("customTextBlock1");
			} else {
				$refData["firstName$index"]['Plum'] = '';
				$refData["lastName$index"]['Plum'] = '';
				$refData["employer$index"]['Plum'] = '';
				$refData["title$index"]['Plum'] = '';
				$refData["phone$index"]['Plum'] = '';
				$refData["email$index"]['Plum'] = '';
				$refData["relationship$index"]['Plum'] = '';
			}
			if (array_key_exists("wa", $refs)) {
				$wa_r = $refs["wa"];
				$refData["firstName$index"]['WorldApp'] = $wa_r->get("referenceFirstName");
				$refData["lastName$index"]['WorldApp'] = $wa_r->get("referenceLastName");
				$refData["employer$index"]['WorldApp'] = $wa_r->get("companyName");
				$refData["title$index"]['WorldApp'] = $wa_r->get("referenceTitle");
				$refData["phone$index"]['WorldApp'] = $wa_r->get("referencePhone");
				$refData["email$index"]['WorldApp'] = $wa_r->get("referenceEmail");
				$refData["relationship$index"]['WorldApp'] = $wa_r->get("customTextBlock1");
			} else {
				$refData["firstName$index"]['WorldApp'] = '';
				$refData["lastName$index"]['WorldApp'] = '';
				$refData["employer$index"]['WorldApp'] = '';
				$refData["title$index"]['WorldApp'] = '';
				$refData["phone$index"]['WorldApp'] = '';
				$refData["email$index"]['WorldApp'] = '';
				$refData["relationship$index"]['WorldApp'] = '';
			}
			$refData["firstName$index"]['repeat'] = 1;
			$refData["lastName$index"]['repeat'] = 1;
			$refData["employer$index"]['repeat'] = 1;
			$refData["title$index"]['repeat'] = 1;
			$refData["phone$index"]['repeat'] = 1;
			$refData["email$index"]['repeat'] = 1;
			$refData["relationship$index"]['repeat'] = 1;
		}
		return $refData;
	}

	private function convertCustomObjForPDF($candidates, $form) {
		$customObj['bh'] = $candidates['bh']->get("customObject1s");
		$customObj['wa'] = $candidates['wa']->loadCustomObject(1);
		$customObj['rq'] = $candidates['rq']->get("customObject1s");
		foreach($customObj as $src=>$obj) {
			$json[$src] = $obj->marshalToArray();
		}
		$objData = [];
		foreach ($json['wa'][0] as $key=>$attr) {
			if (in_array($key, ["dateAdded", "dateLastModified"])) {
				continue;
			}
			$fieldName = "customObject1.".$key;
			$qmappings = $form->get("BHMappings");
			if (array_key_exists($fieldName, $qmappings)) {
				$qmap = $qmappings[$fieldName][0];
				$wa = $qmap->get("WorldAppAnswerName");
				$objData[$key]['Question'][] = $wa;
			} else {
				continue; //skip ID and person
			}
			$objData[$key]['Bullhorn'] = $customObj['bh']->get_a_string($attr);
			//worldapp
			$wa_obj = $json['wa'][0];
			$wa_attr = '';
			if (array_key_exists($key, $wa_obj)) {
				$wa_attr = $wa_obj[$key];
			}
			$objData[$key]['WorldApp'] = $customObj['bh']->get_a_string($wa_attr);
			//request
			$rq_obj = $json['rq'][0];
			$rq_attr = '';
			if (array_key_exists($key, $rq_obj)) {
				$rq_attr = $rq_obj[$key];
			}
			$objData[$key]['Plum'] = $customObj['bh']->get_a_string($rq_attr);
			$objData[$key]['repeat'] = 1;
		}
		return $objData;
	}

	private function convertNotesForPDF($candidates) {
		$notes['bh'] = $candidates['bh']->get("notes");
		$notes['rq'] = $candidates['rq']->get("Note"); //conversion interview
		$wavalue = $candidates['wa']->get("Note"); //just Availability value here
		$noteData = [];
		//Conversion Interview
		$rqvalue = $notes['rq']['comments'];
		$bhvalue = '';
		foreach ($notes['bh'] as $note) {
			if ($note['action'] == "Conversion Interview") {
				$bhvalue = $note['comments'];
			}
		}
		// no WorldApp value for this field
		$noteData['Conversion Interview']['Question'][] = "From Consultant's Confirmation Page";
		$noteData['Conversion Interview']['Bullhorn'] = $bhvalue;
		$noteData['Conversion Interview']['WorldApp'] = '';
		$noteData['Conversion Interview']['Plum'] = $rqvalue;
		$noteData['Conversion Interview']['repeat'] = 1;

		//Availability
		$bhvalue2 = '';
		foreach ($notes['bh'] as $note) {
			if ($note['action'] == "Availability") {
				$bhvalue2 = $note['comments'];
			}
		}
		$noteData['Availability']['Question'][] = 'Call Availability';
		$noteData['Availability']['Bullhorn'] = $bhvalue2;
		$noteData['Availability']['WorldApp'] = $wavalue;
		$noteData['Availability']['Plum'] = ''; //WorldApp value not edited
		$noteData['Availability']['repeat'] = 1;

		//Reg Form sent
		//only in Bullhorn, kicks off the whole Plum process
		$bhvalue3 = '';
		foreach ($notes['bh'] as $note) {
			if ($note['action'] == "Reg Form Sent") {
				$bhvalue3 = $note['comments'];
			}
		}
		$noteData['Reg Form Sent']['Question'][] = 'Email Template in Plum';
		$noteData['Reg Form Sent']['Bullhorn'] = $bhvalue3;
		$noteData['Reg Form Sent']['WorldApp'] = '';
		$noteData['Reg Form Sent']['Plum'] = '';
		$noteData['Reg Form Sent']['repeat'] = 1;
		return $noteData;
	}

	public function submit_note(\Stratum\Model\Candidate $candidate) {
		$bullhornClient = $this->getClient();

		$bullhornClient->submit_note($candidate);
	}

	function submit_custom_object($candidate) {
		$customObj_from_form = $candidate->get("customObject1s");
		$this->log_debug("looking up custom object");
		$custom_data = $this->getClient()->find_custom_object($candidate);
		$customObject_bh = new \Stratum\Model\CustomObject();
		$customObject_bh->setLogger($this->_logger);
		$customObject_bh->populateFromData($custom_data);
		$id = $customObject_bh->get("id");
		$customObject_bh->set("dateAdded", ""); //dateAdded is automatically put there by Bullhorn
		if ($id) {
			$customObj_from_form->set("id", $id);
		}
		if ($customObject_bh->compare($customObj_from_form)) {
			$this->log_debug("Custom objects are equal: no update required");
		} else {
			$this->log_debug("Custom object needs update");
			$this->getClient()->submit_custom_object($customObj_from_form, $candidate);
		}
	}


	function submit_references($candidate) {
		$bullhornClient = $this->getClient();

		//now we update the references
		$references = $candidate->loadReferences(); //returns an array of CandidateReference objects
		$this->log_debug("looking up candidate references");
		$ref_data = $bullhornClient->find_candidate_references($candidate); //an array of ref data (if exists)
		foreach ($references as $reference) {
			$ref_fname = $reference->get("referenceFirstName");
			$ref_lname = $reference->get("referenceLastName");
			$found = false;
			foreach ($ref_data as $rd) {
				if ($ref_fname == $rd['referenceFirstName'] &&
					$ref_lname == $rd['referenceLastName']) {
					$found = true;
					$reference->set("id", $rd['id']);
				}
			}
			if (!$found) {
				$newRefId = $bullhornClient->submit_reference($reference, $candidate);
				if ($newRefId) {
					$reference->set("id", $newRefId);
				}
			}
			//$reference->dump();
		}
	}

}
