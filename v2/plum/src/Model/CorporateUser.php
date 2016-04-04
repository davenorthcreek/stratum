<?php
/*
 * CorporateUser.php
 * Base model for CorporateUser (owner of Candidate)
 * Data model for transfer between WorldApp and Bullhorn
 *
 * Copyright 2016
 * @category    Stratum
 * @package     Stratum
 * @copyright   Copyright (c) 2016 North Creek Consulting, Inc. <dave@northcreek.ca>
 *
 */

namespace Stratum\Model;
class CorporateUser extends ModelObject
{

    /**
     * Array of attributes codes needed for product load
     *
     * @var array of tag/values
     */
    protected $_fields = ['name'=>'',
						  'mobile'=>'',
						  'id'=>'',
						  'address'=>'',
						  'companyName'=>'',
						  'customDate1'=>'',
						  'customDate2'=>'',
						  'customDate3'=>'',
						  'customFloat1'=>'',
						  'customFloat2'=>'',
						  'customFloat3'=>'',
						  'customInt1'=>'',
						  'customInt2'=>'',
						  'customInt3'=>'',
						  'customText1'=>'',
						  'customText2'=>'',
						  'customText3'=>'',
						  'customText4'=>'',
						  'customText5'=>'',
						  'customText6'=>'',
						  'customText7'=>'',
						  'customText8'=>'',
						  'customText9'=>'',
						  'customText10'=>'',
						  'customText11'=>'',
						  'customText12'=>'',
						  'customText13'=>'',
						  'customText14'=>'',
						  'customText15'=>'',
						  'customText16'=>'',
						  'customText17'=>'',
						  'customText18'=>'',
						  'customText19'=>'',
						  'customText20'=>'',
						  'dateLastComment'=>'',
					      'departmentIdList'=>'',
						  'departments'=>'',
						  'email'=>'',
						  'email2'=>'',
						  'email3'=>'',
						  'emailNotify'=>'',
						  'emailSignature'=>'',
						  'enabled'=>'',
						  'externalEmail'=>'',
						  'fax'=>'',
						  'fax2'=>'',
						  'fax3'=>'',
						  'firstName'=>'',
						  'inboundEmailEnabled'=>'',
						  'isDayLightSavings'=>'',
						  'isDeleted'=>'',
						  'isLockedOut'=>'',
						  'isOutboundFaxEnabled'=>'',
						  'jobAssignments'=>'',
						  'lastName'=>'',
						  'loginRestrictions'=>'',
						  'massMailOptOut'=>'',
						  'masterUserID'=>'',
						  'middleName'=>'',
                          'name'=>'',
						  'namePrefix'=>'',
						  'nameSuffix'=>'',
						  'nickName'=>'',
						  'occupation'=>'',
						  'pager'=>'',
						  'personSubtype'=>'',
						  'phone'=>'',
						  'phone2'=>'',
						  'phone3'=>'',
						  'primaryDepartment'=>'',
						  'reportToPerson'=>'',
						  'smsOptIn'=>'',
						  'status'=>'',
						  'taskAssignments'=>'',
						  'timeZoneOffsetEST'=>'',
						  'userType'=>'',
						  'username'=>''
						  ];
    private $assocCandidates = [];

	//OVERRIDE
	public function set($attribute, $value) {
		if ($attribute == "name") {
			$this->setName($value); //split name
		} else {
			parent::set($attribute,$value);
		}
		return $this;
	}

    /**
     * Set Name
     *
     * @param string $name
     * @return prospect
     */
    public function setName($name)
    {
		$this->_fields["name"] = $name;
		$name_split = preg_split('#\s+#', $name, null, PREG_SPLIT_NO_EMPTY);
		$this->log_debug(json_encode($name_split));
		if (!empty($name_split[0])) {
			$this->set("firstName", $name_split[0]);
		}
		if (count($name_split) >= 3) {
			//there is at least one middle name
			$this->set("lastName", $name_split[count($name_split) - 1]);
			$middleName = implode(" ", array_slice($name_split, 1, count($name_split)-2));
			$this->set("middleName", $middleName);
		} else if (count($name_split) == 2) {
			$this->set("lastName", $name_split[1]);
		}
		return $this;
	}

	/**
     * Return name
     *
     * @return string
     */
	public function getName()
	{
		$this->log_debug("At getName() in CorporateUser");
		$name = $this->get("name");
		if ($name) {
			return $name;
		}
		$first = $this->get("firstName");
		$middle = $this->get("middleName");
		$last = $this->get("lastName");
		$name .= $first;
		if ($middle) {
			$name .= " $middle";
		}
		if ($last) {
			$name .= " $last";
		}
		parent::set("name",$name); //no re-setting sub-names
		return $name;
	}

	public function getBullhornFieldList() {
		$list = "";
		foreach (array_keys($this->_fields) as $key) {
			//exceptions need to be here
			if ($key == 'userDateAdded' ||
				$key == 'webResponse' ) {
			} else {
				$list .= $key.",";
			}
		}
		$list = substr($list, 0, strlen($list)-1); //remove last comma
		return $list;
	}


    public function getDateWithFormat($label, $format = "d/m/Y") {
        $date = $this->get($label) / 1000; //int

        $dateObject = new \DateTime();
        $dateObject->setTimeStamp($date);

        $string = $dateObject->format($format);
        $this->log_debug("$label: $string");
        return $string;
    }

	public function loadCustomObject($index = 1) {
        $name = "customObject".$index."s";
		$customObject = $this->get($name);
		if ($customObject) {
			return $customObject;
		}
        $this->log_debug("nothing there in $name");
		$there = false; //are there any relevant fields in the form submission?
		$co = new \Stratum\Model\CustomObject();
		$co->setLogger($this->_logger);
		foreach ($this->expose_set() as $attr=>$value) {
			//now we filter based on what we have vs. what Bullhorn knows
			if (preg_match("/customObject".$index."\.(.*)/", $attr, $m)) {
				$there = true;
				$co->set($m[1], $value);
			}
		}
		if ($there) {
			$this->set($name, $co);
            $this->log_debug("Found a custom object $name");
			return $co;
		} else {
            $this->log_debug("Still nothing there after traversing CorporateUser ".$this->get("id"));
			return null;
		}
	}

	public function marshalCustomObject($index=1) {
		return $this->loadCustomObject($index)->marshalToArray();
	}

	private function get_a_string($thing) {
		$new_string = $thing; //not a reference
		if (is_array($thing)) {
			$new_array = [];
			foreach ($thing as $subthing) {
				$new_array[] = $this->get_a_string($subthing);
			}
			$new_string = implode(', ', $new_array);
		}
		if (is_a($thing, "\Stratum\Model\ModelObject")) {
			$new_string = get_class($thing);
			$this->log_debug("Found an object $new_string");
		}
		$new_string = trim($new_string);
		return $new_string;
	}


	public function compare(\Stratum\Model\ModelObject $other) {
		$same = true;
		$this->log_debug("Comparing CorporateUsers");
		foreach ($other->expose_set() as $attr=>$value) {
			$mine = $this->get_a_string($this->get($attr));
			$value = $this->get_a_string($value);
			if ($mine != $value) {
				$this->log_debug("$attr: $mine != $value");
				$same = false;
			}
		}
		if ($same) {
			$this->log_debug( "They match!");
		} else {
			$this->log_debug( "Not a match");
		}
		return $same;
	}

    public function getAssocCandidates() {
        return $this->assocCandidates;
    }

    public function setAssocCandidates($list) {
        $this->assocCandidates = $list;
    }

	public function dump() {
		$this->log_debug("---------------------------");
		$this->log_debug("Stratum\Model\CorporateUser:");
		foreach ($this->_fields as $key=>$there) {
			if ($there) {
				$this->log_debug($key.": ");
				if (is_a($there, "\Stratum\Model\ModelObject")) {
					$there->dump();
				} else {
					$this->var_debug($there);
				}
			}
		}
		$this->log_debug("---------------------------");
	}

}
