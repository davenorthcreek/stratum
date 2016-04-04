<?php
/*
 * ModelObject.php
 * Base model object
 * Data model for transfer between WorldApp and Bullhorn
 *
 * Copyright 2015
 * @category    Stratum
 * @package     Stratum
 * @copyright   Copyright (c) 2015 North Creek Consulting, Inc. <dave@northcreek.ca>
 *
 */

namespace Stratum\Model;
class ModelObject
{

    /**
     * Array of attributes codes needed for product load
     *
     * @var array of tag/values
     */
    protected $_fields = []; //override as in Candidate

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
		$this->log_debug( $contents );        // log contents of the result of var_dump( $object )
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


	public function get($attribute) {
		if (array_key_exists($attribute, $this->_fields)) {
			return $this->_fields[$attribute];
		} else {
			return null;
		}
	}

	public function set($attribute, $value) {
		if ($attribute == "logger") {
			$this->setLogger($value);
		} else {
			$this->_fields[$attribute] = $value;
		}
		return $this;
	}


    /**
     * Initialize
     *
     */
    public function __construct($fields = array())
    {
		foreach ($fields as $field => $value) {
			$this->set($field, $value);
		}
		return $this;
    }

	public function expose() {
		return get_object_vars($this);
	}

	public function expose_set() {
		$set = array(); //array of set fields
		foreach ($this->_fields as $field=>$value) {
			if (!empty($value)) {
				$set[$field] = $value;
			}
		}
		//$this->log_debug(json_encode($set));
		return $set;
	}

	public function marshalToJSON() {
		$json = [];
		foreach ($this->expose_set() as $attr=>$value) {
			if (is_a($value, "ModelObject")) {
				$json[$attr]['id'] = $value->get("id");
			} else {
				$json[$attr] = $value;
			}
		}
		$encoded = json_encode($json, true);
		return $encoded;
	}

	public function marshalToArray() {
		$json = [];
		foreach ($this->expose_set() as $attr=>$value) {
			//now we filter based on what we have vs. what Bullhorn knows
			if (is_a($value, "\Stratum\Model\ModelObject")) {
				$json[$attr]['id'] = $value->get("id");
			} else {
				$json[$attr] = $value;
			}
		}
		return $json;
	}

	public function populateFromData($data) {
		foreach ($data as $key=>$value) {
			$this->set($key, $value);
		}
		return $this;
	}

	public function compare(\Stratum\Model\ModelObject $other) {
		$same = true;
		foreach ($other->expose_set() as $attr=>$value) {
			$mine = $this->get($attr);
			if ($mine != $value) {
				$same = false;
			}
		}
		if ($same) {
			//should compare the other way, since the comparison skips empty values
			foreach ($this->expose_set() as $attr=>$value) {
				$theirs = $other->get($attr);
				if ($theirs != $value) {
					$same = false;
				}
			}
		}
		return $same;
	}
}
