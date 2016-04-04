<?php
/*
 * Candidate.php
 * Base model for candidate
 * Data model for transfer between WorldApp and Bullhorn
 *
 * Copyright 2015
 * @category    Stratum
 * @package     Stratum
 * @copyright   Copyright (c) 2015 North Creek Consulting, Inc. <dave@northcreek.ca>
 *
 */

namespace Stratum\Model;
class Candidate extends ModelObject
{
    const XML_PATH_LIST_DEFAULT_SORT_BY     = 'catalog/frontend/default_sort_by';

    /**
     * Array of attributes codes needed for product load
     *
     * @var array of tag/values
     */
    protected $_fields = ['name'=>'',
						  'mobile'=>'',
						  'id'=>'',
						  'address'=>'',
						  'businessSectors'=>'',
						  'candidateSource'=>'',
						  'category'=>'',
						  'categories'=>'',
						  'certifications'=>'',
						  'comments'=>'',
						  'companyName'=>'',
						  'companyURL'=>'',
						  'customDate1'=>'',
						  'customDate2'=>'',
						  'customDate3'=>'',
						  'customFloat1'=>'',
						  'customFloat2'=>'',
						  'customFloat3'=>'',
						  'customInt1'=>'',
						  'customInt2'=>'',
						  'customInt3'=>'',
						  'customObject1s'=>null,
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
						  'customTextBlock1'=>'',
						  'customTextBlock2'=>'',
						  'customTextBlock3'=>'',
						  'customTextBlock4'=>'',
						  'customTextBlock5'=>'',
						  'dateAdded'=>'',
						  'dateAvailable'=>'',
						  'dateAvailableEnd'=>'',
						  'dateI9Expiration'=>'',
						  'dateLastComment'=>'',
						  'dateNextCall'=>'',
						  'dateOfBirth'=>'',
						  'dayRate'=>'',
						  'dayRateLow'=>'',
						  'degreeList'=>'',
					      'description'=>'',
						  'desiredLocations'=>'',
						  'disability'=>'',
						  'educationDegree'=>'',
						  'email'=>'',
						  'email2'=>'',
						  'email3'=>'',
						  'employeeType'=>'',
						  'employmentPreference'=>'',
						  'ethnicity'=>'',
						  'experience'=>'',
						  'externalID'=>'',
						  'fax'=>'',
						  'fax2'=>'',
						  'fax3'=>'',
						  'federalAddtionalWitholdingsAmount'=>'',
						  'federalExemptions'=>'',
						  'federalFilingStatus'=>'',
						  'firstName'=>'',
						  'gender'=>'',
						  'hourlyRate'=>'',
						  'hourlyRateLow'=>'',
						  'i9OnFile'=>'',
						  'interviews'=>'',
						  'isDeleted'=>'',
						  'isEditable'=>'',
						  'lastName'=>'',
						  'leads'=>'',
						  'linkedPerson'=>'',
						  'localAddtionalWitholdingsAmount'=>'',
						  'localExemptions'=>'',
						  'localFilingStatus'=>'',
						  'localTaxCode'=>'',
						  'massMailOptOut'=>'',
						  'middleName'=>'',
						  'namePrefix'=>'',
						  'nameSuffix'=>'',
						  'nickName'=>'',
						  'numCategories'=>'',
						  'numOwners'=>'',
						  'occupation'=>'',
						  'owner'=>'',
						  'pager'=>'',
						  'paperWorkOnFile'=>'',
						  'password'=>'',
						  'phone'=>'',
						  'phone2'=>'',
						  'phone3'=>'',
						  'placements'=>'',
						  'preferredContact'=>'',
						  'primarySkills'=>'',
						  'recentClientList'=>'',
						  'referredBy'=>'',
						  'referredByPerson'=>'',
						  'salary'=>'',
						  'salaryLow'=>'',
						  'secondaryAddress'=>'',
						  'secondaryOwners'=>'',
						  'secondarySkills'=>'',
						  'sendouts'=>'',
						  'skillSet'=>'',
						  'smsOptIn'=>'',
						  'source'=>'',
						  'specialties'=>'',
						  'ssn'=>'',
						  'stateAddtionalWitholdingsAmount'=>'',
						  'stateExemptions'=>'',
						  'stateFilingStatus'=>'',
						  'status'=>'',
						  'submissions'=>'',
						  'tasks'=>'',
						  'taxID'=>'',
						  'taxState'=>'',
						  'timeZoneOffsetEST'=>'',
						  'travelLimit'=>'',
						  'type'=>'',
						  'userDateAdded'=>'',
						  'username'=>'',
						  'veteran'=>'',
						  'webResponse'=>'',
						  'willRelocate'=>'',
						  'workAuthorized'=>'',
						  'workPhone'=>'',
						  'references'=>[] //array of CandidateReference objects
						  ];

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
		//$this->log_debug("At getName() in Candidate");
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

	/**
     * Set Mobile
     *
     * @param string mobile phone number as string
     * @return Candidate
     */
    public function setMobile($mobile)
    {
		return $this->set("mobile", $mobile);
	}

	/**
     * Return mobile phone number as string
     *
     * @return string
     */
	public function getMobile()
	{
		return $this->get("mobile");
	}

	public function getWorldAppLabel($bh, $form) {
		$wa = "";
		$mappings = $form->get("BHMappings");
		if (array_key_exists($bh, $mappings)) {
			$this->log_debug("Found $bh");
			$qmaps = $mappings[$bh];
			foreach ($qmaps as $qmap) {
				$wa = $qmap->get("WorldAppAnswerName");
				if ($wa) {
					$this->log_debug("$wa");
				}
			}
		}
		return $wa;
	}

    public function getDateWithFormat($label, $format = "d/m/Y") {
        $date = $this->get($label) / 1000; //int

        $dateObject = new \DateTime();
        $dateObject->setTimeStamp($date);

        $string = $dateObject->format($format);
        $this->log_debug("$label: $string");
        return $string;
    }

    public function getDateOfBirthWithFormat($format = "d/m/Y") {
        return $this->getDateWithFormat("dateOfBirth", $format);
    }

	public function getBullhornFieldList() {
		$list = "";
		foreach (array_keys($this->_fields) as $key) {
			//exceptions need to be here
			if ($key == 'userDateAdded' ||
				$key == 'webResponse' ||
				preg_match('/WithholdingsAmount/', $key)) {
			} else {
				$list .= $key.",";
			}
		}
		$list = substr($list, 0, strlen($list)-1); //remove last comma
		return $list;
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
				if ($m[1] == 'textBlock3') {
					$value = preg_replace("/Additional Candidate Notes: /", "", $value);
				}
				$co->set($m[1], $value);
			}
		}
		if ($there) {
			$this->set($name, $co);
            $this->log_debug("Found a custom object $name");
			return $co;
		} else {
            $this->log_debug("Still nothing there after traversing candidate ".$this->get("id"));
			return null;
		}
	}

	public function marshalCustomObject() {
		return $this->loadCustomObject()->marshalToArray();
	}

	public function loadReferences() {
		$references = $this->get("references");
		if ($references) {
			return $references;
		}
		//so there is nothing in that empty array
		foreach ($this->expose_set() as $attr=>$value) {
			//now we filter based on what we have vs. what Bullhorn knows
			if (preg_match("/(recommender\d)_(.*)/", $attr, $m)) {
				$ref = null;
				if (array_key_exists($m[1], $references)) {
					$ref = $references[$m[1]];
				} else {
					$ref = new \Stratum\Model\CandidateReference();
					$ref->setLogger($this->_logger);
					$references[$m[1]] = $ref;
					$this->log_debug("Creating ".$m[1]);
				}
				$ref->set($m[2], $value);
			}
		}
		$this->set("references", $references);
		return $references;
	}

	public function marshalReferences() {
		$json = [];
		$references = $this->loadReferences();
		foreach ($references as $label=>$ref) {
			//we need to submit these references as new BH objects
			//then attach their ids to the Candidate within BH

			$json['references'][$label] = $ref->marshalToJSON();
		}
		return $json;
	}

	public function marshalToJSON() {
		$json = [];
		$addresses = [];
		$references = [];
		foreach ($this->expose_set() as $attr=>$value) {
			//now we filter based on what we have vs. what Bullhorn knows
			if ($attr=='customFloat2') {
				//bonus potential
				$number = preg_match("/\s*(\d+)\s*%/", $value, $m);
				if ($number) {
					$value = $m[1];
				}
			}
			if ($attr=='dateOfBirth' && $value) {
				//need to convert to Unix timestamp
        $this->log_debug("Date of Birth: ".$value);
				$date = \DateTime::createFromFormat("d/m/Y", $value);
        if (!$date) {
          //assume we're going the other way
          $date = \DateTime::createFromFormat('U', ($value/1000));
          if ($date) {
              $value = $date->format("d/m/Y");
          } else { //no value, no date
              $value = '';
          }
        } else {
          //no, we want the Unix timestamp
				  $stamp = $date->format('U') * 1000;
				  $value = $stamp;
        }
			}
			if (is_a($value, "ModelObject")) {
				$json[$attr]['id'] = $value->get("id");
			} else if ($attr == 'confirmAgree' ||			//boolean
					   $attr == 'additionalCitizenship' ||	//boolean
					   $attr == 'anotherCitizenship' ||		//boolean
					   $attr == 'provideNetAfterTax' ||		//boolean
					   $attr == 'contactRecommendersConsent' || //boolean - recorded where?
					   $attr == 'idealNextRole' ||  		//??
					   $attr == 'references' || 			//references are handled separately
					   $attr == 'diploma' ||  //must be added to CandidateEducation somehow
					   $attr == 'reportToPerson' || //this is for ClientContact
                       $attr == 'NONE'  ||          //Q42 Daily or Hourly rate...
					   $attr == 'employerAtRegistration') { //??
				//skip
			} else if (preg_match("/(recommender\d)_(.*)/", $attr)) {
				//handled in marshalReferences
			} else if (preg_match("/customObject/", $attr)) {
				//handled in marshalCustomObject
			} else if (preg_match("/ID$/", $attr)) {
				//ID means a secondary object that must be added later
				//skip for now
			} else if (preg_match("/(.*ddress)\((.*)\)/", $attr, $m)) {
				//these are address or secondaryAddress fields
				$addrLabel = $m[1];
				$addresses[$addrLabel][$m[2]] = $value;
			} else if ($attr == 'Note') {
				//$json['Additional Candidate Note'] = $value;
			} else if ($attr == 'status') {
				//was too long in test
				$value = substr($value, 0, strpos($value, "(")-1);
				$json[$attr] = $value;
			} else {
				$json[$attr] = $value;
			}
		}
		foreach ($addresses as $label=>$address) {
			//should be secondaryAddress and address
			$json[$label] = $address;
		}
		/**
		 *
		 * This section would continually add new custom objects
		 * to the Candidate record
		if ($this->loadCustomObject()) {
			//there is something there
			$json['customObject1s'] = $this->marshalCustomObject();
		}
		**/
		$encoded = json_encode($json, true);
		$this->log_debug($encoded);
		return $encoded;
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
		$this->log_debug("Comparing candidates");
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

	public function dump() {
		$this->log_debug("---------------------------");
		$this->log_debug("Stratum\Model\Candidate:");
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
