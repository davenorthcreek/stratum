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
		/*
		This section replaced by 'Indexed' call
		$candidatesNo  = $bullhornClient->findAssocCandidatesWithNo($cuser, 'No');
		$candidatesRFS = $bullhornClient->findAssocCandidatesWithRFS($cuser, 'RFS');
		$candidatesFC  = $bullhornClient->findAssocCandidatesWithFC($cuser, 'FC');
		$candidatesIC  = $bullhornClient->findAssocCandidatesWithIC($cuser, 'IC');
		*/
		if ($candidates == null) {
			//error condition according to Stratum
		}
		return $candidates;
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

	public function submitPDF(\Stratum\Model\Candidate $candidate) {
		$bullhornClient = $this->getClient();
		$decoded = [];

		//going to use FormResponse (web form) as a template for the pdf.
		//so I will get the section headers - but only display section if
		//there is content.

		$fc = new FormController();
		$form = $fc->setupForm();  //parsed QandA.txt to get questions in order
		$sections = $form->get("sections");  //and sections
		$headers = $form->get("sectionHeaders");  //with appropriate labels
		for ($i = 0; $i < count($sections); $i++) {
			$section = $sections[$i];
			$label = $headers[$i];

			$this->exportSectionToPDF($form, $section, $label, $candidate);
		}
		return [];
	}

	private function exportSectionToPDF($form, $section, $label, $candidate) {
		$questionMaps = $form->get('questionMappings');
        foreach ($section as $qmap) {  //qmaps for each question in a section
            $theId = $qmap->getBestId();
                /******************************
                 first pass, find subquestions
                /**************************** */
            $mult = $qmap->get("multipleAnswers"); //boolean
            $type = $qmap->get("type");
            $this->log_debug("$theId $type");
            if ($type == "boolean") {
                if (array_key_exists($theId, $questionMaps)) {
                    $this->log_debug("using $theId ".$qmap->get("WorldAppAnswerName"));
                    $sectionQs[$theId] = $qmap;
                }
            } else if ($mult && ($type!='choice') && ($type != "list") && ($type != "multichoice")) {
                $this->log_debug("Mult and not choice, multichoice, boolean, or list");
                foreach ($qmap->get("answerMappings") as $q2) {
                    $theId = $q2->getBestId();
                    $sectionQs[$theId] = $q2;
                    $this->log_debug("Setting answer $theId ".$q2->get("value"));
                }
            } else {
                $theId = $qmap->getBestId();
                $sectionQs[$theId] = $qmap;
                $this->log_debug("default case");
            }
        }
        if (array_key_exists("Q3", $sectionQs)) {
            //Q3/5/7 were merged into one Nationality widget
			//just display Nationality once
            unset($sectionQs["Q5"]);
            unset($sectionQs["Q7"]);
        }

		foreach ($sectionQs as $human=>$qmap) {

                /****************************************
                second pass, export to PDF with answers
                ************************************** */
            $retval = $this->exportQMToPDF($qmap, $human, $form, $candidate);
		}
	}

	private function exportQMToPDF($qmap, $human, $form, $candidate) {
		//$qmap->dump();
		$bh = $qmap->get("BullhornField");
		$value = $candidate->get($bh);
		$this->log_debug("exporting $human to PDF: Label ".$qmap->get("WorldAppAnswerName"));
		$this->var_debug($value);
	}

	/* Things I need to not forget to include in PDF:

		$cand_data = $candidate->marshalToJSON();
		$references = $candidate->loadReferences(); //returns an array of CandidateReference objects
		$customObj_from_form = $candidate->loadCustomObject();
		$skills = $candidate->get("skillID");

		$cats = $candidate->get("categories");
		$specs = $candidate->get("specialties");
		$note = $candidate->get("Note"); //has "comments"
		//$this->submit_references($candidate);
		//$this->submit_custom_object($candidate);
		//$bullhornClient->submit_skills($candidate);
		//$bullhornClient->submit_categories($candidate);
		//$bullhornClient->submit_specialties($candidate);
		//$bullhornClient->submit_note($candidate);
	}
	*/
	public function submit_note(\Stratum\Model\Candidate $candidate) {
		$bullhornClient = $this->getClient();

		$bullhornClient->submit_note($candidate);
	}

	function submit_custom_object($candidate) {
		$customObj_from_form = $candidate->loadCustomObject();
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
