<?php
/*
 * WorldappController.php
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

class WorldappController {

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

	protected $worldappClient;

	private function getClient() {
		if (!$this->worldappClient) {
			$this->log_debug("Creating new WorldApp client");
			$this->worldappClient = new \Stratum\Client\Worldapp();
			$this->worldappClient->setLogger($this->_logger);
			$this->worldappClient->init();
		}
		return $this->worldappClient;
	}

	public function login_only() {
		$worldappClient = $this->getClient();
		//echo $worldappClient->getSessionKey();
	}

	public function getForms() {
		$worldappClient = $this->getClient();
		return $worldappClient->getForms();
	}

    public function find_form_by_name($name) {
        $theForm = null;
		$worldappClient = $this->getClient();
		$forms = $worldappClient->getForms();
		foreach ($forms as $form) {
			if ($form->name == $name) {
				//this is the form we want
                $theForm = $form;
			}
		}
		return $theForm;
	}

	public function find_active_form() {
        return $this->find_form_by_name('Registration Form - Stratum International');
	}

	public function find_form_by_id($formID) {
		$worldappClient = $this->getClient();
		return $worldappClient->getForm($formID);
	}

	public function get_questions($formID, $withAnswer = true) {
		$worldappClient = $this->getClient();
		return $worldappClient->getQuestions($formID, $withAnswer);
	}

	public function get_question_by_id($formID, $qid, $withAnswer = true) {
		$worldappClient = $this->getClient();
		return $worldappClient->getQuestion($qid, $withAnswer);
	}

	public function get_response($qid, $respondentId) {
		$worldappClient = $this->getClient();
		return $worldappClient->getResponse($qid, $respondentId);
	}

	public function sendUrlWithAutofill($formId, $email, $autofill) {
		$worldappClient = $this->getClient();
		$this->log_debug("Sending Form to ".$email);
		return $worldappClient->sendUrlWithAutofillByEmail($formId, $email, $autofill);
	}

    public function getEmailTemplate($formId) {
        return $this->getClient()->getEmailTemplate($formId);
    }

    public function setEmailTemplate($template) {
		$this->log_debug("Setting template to:");
		$this->log_debug($template['content']);
        return $this->getClient()->setEmailTemplate($template);
    }
}
