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
class BullhornController {
	
	//allow someone to pass in a $logger
	protected $_logger;
	
	public function setLogger($lgr) {
		//$lgr better be a logger of some sort -missing real OOP here
		$this->_logger = $lgr;
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
			echo $str."\n";
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
        /**
         *  debug only 
         * 
		if ($bullhornClient->confirm($candidate)) {
			$this->log_debug("<p>Success!</p>");
		} else {
			$this->log_debug("Failure: please examine log files");
		}
        **/
		return $candidate;
	}
	
	public function submit(\Stratum\Model\Candidate $candidate) {
		$bullhornClient = $this->getClient();
		
		
		$retval = $bullhornClient->submit_candidate($candidate);
		
		if (array_key_exists("changedEntityId", $retval)) {
			//we have a successful submission
			$this->submit_references($candidate);
			$this->submit_custom_object($candidate);
            $bullhornClient->submit_skills($candidate);
		}		
		//returns an array with 'error' or 'id' and other data
		return $retval;
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
			$reference->dump();
		}
	}
	
}
