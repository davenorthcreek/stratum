<?php
/*
 * CandidateController.php
 * Controller for interactions with candidate
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

class CandidateController
{

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
			\Log::debug( $str);
		}
	}


	public function getIdentity($candidate, $formResult) {
		$name = '';
		//extract reference number, name from form result
		$id = $formResult->findByWorldApp("Candidate Ref Number");
		$candidate->set("name", $this->extractName($formResult));
		if ($id) {
			$candidate->set("id", $id[0]);
		} else {
			//hardcode this
			$candidate->set("id", 10413); //matches Mickey Mouse
		}
		$this->log_debug("Getting identity for ".$candidate->get("name"));
		$this->log_debug("With ID: ".$candidate->get("id"));
		//$candidate->dump();
		return $candidate;
	}

	public function populate($candidate, $formResult) {
		//assumes there is no current data
		//everything should be written over
		$form = $formResult->get("form");
		foreach ($form->get("BHMappings") as $key=>$qmaps) {
			//now all qmaps will be arrays of answers
			$this->collate($candidate, $key, $formResult);
		}
		//$candidate->dump();
		$this->log_debug("Loading custom Object 1");
		$candidate->loadCustomObject(1);
		$this->log_debug("Loading custom Object 3");
		$candidate->loadCustomObject(3);
		$this->log_debug("Loading references");
		$candidate->loadReferences();
		return $candidate;
	}

	private function collate($candidate, $the_key, $formResult) {
		$arr = $formResult->findByBullhorn($the_key);
		$total = "";
		if ($the_key == 'customText20') {
			$pt1 = '';
			$pt2 = '';
			if (array_key_exists('Expected Local Gross Salary', $arr)) {
				$pt1 = $arr['Expected Local Gross Salary']['value'];
			}
			if (array_key_exists('Expected Local Salary Currency', $arr)) {
				$pt2 = $arr['Expected Local Salary Currency']['value'];
			}
			$total = $pt1.' ('.$pt2.')';
			$candidate->set($the_key, $total);
		} else if ($arr) {
			$multiple = false;
			if (count($arr)>1) {
				$multiple = true;
			}
			$separator = ', ';
			$remove = 2;
			if ($the_key == 'customText4' ||
				$the_key == 'categoryID' ||
				$the_key == 'educationDegree' ||
				$the_key == 'customTextBlock1' ||
				$the_key == 'customTextBlock2' ||
				$the_key == 'customText1' ||
				$the_key == 'customText17' ||
				$the_key == 'customText19' ||
				$the_key == 'customTextBlock4' ||
				$the_key == 'customText3' ||
				$the_key == 'degreeList' ||
				$the_key == 'certifications' ||
				$the_key == 'customTextBlock3' ||
				$the_key == 'customText10') {
				$separator = '; ';
			} else if ($the_key == 'specialtyCategoryID' ||
					   $the_key == 'skillID') {
				$separator = "\n";
				$remove = 1;
			} else if ($the_key == 'customObject1.textBlock3') {
				//Additional Candidate Notes
				$separator = "\n\n";
				$remove = 2;
			}
			$keys = array_keys($arr);
			$numeric_keys = array_filter($keys, function($k) {return is_numeric($k);
			});
			if ($numeric_keys) {
				sort($numeric_keys);
				foreach ($numeric_keys as $key) {
					$res = $arr[$key];
					// removed this from the total addition:
					// ($multiple? "$key: ":"").
					$total .= $res.$separator;
				}
			} else {
				foreach ($arr as $key=>$res) {
					$total .= ($multiple? "$key: ":"").$res['value'].$separator;
				}
			}
			//clip the last, trailing comma and space
			$total = substr($total, 0, strlen($total)-$remove);
			$candidate->set($the_key, $total);
		}
	}


	protected function extractName($formResult) {
		$firstName = $formResult->findByWorldApp("First Name");
		$lastName = $formResult->findByWorldApp("Last Name");
		if ($firstName && $lastName) {
			$name = $firstName[0]." ".$lastName[0];
		}
		return $name;
		$candidate->set("name", $name);
	}

}
