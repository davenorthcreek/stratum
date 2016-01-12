<?php
/*
 * CorporateUserController.php
 * Controller for interactions with CorporateUser
 * for transfer of data between WorldApp and Bullhorn
 * 
 * Copyright 2016
 * @category    Stratum
 * @package     Stratum
 * @copyright   Copyright (c) 2016 North Creek Consulting, Inc. <dave@northcreek.ca>
 * 
 */

namespace Stratum\Controller;
class CorporateUserController
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
			echo $str."\n";
		}
	}
	
    

}


