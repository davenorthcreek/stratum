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
                          'communication'=>'',
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
                          'recommendation'=>'',
						  'referredBy'=>'',
						  'referredByPerson'=>'',
                          'responsiveness'=>'',
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
						  //'references'=>[], //array of CandidateReference objects
                          'files'=>'',
                          'validated'=>''
						  ];

	//OVERRIDE
	public function set($attribute, $value) {
        if (!array_key_exists($attribute, $this->_fields)) {
            $this->_fields[$attribute] = new Attribute();
        }
		if ($attribute == "name") {
			$this->setName($value); //split name
		} else {
            $attr = $this->_fields[$attribute];
            if (!$attr) {
                $attr = new Attribute();
                $this->_fields[$attribute] = $attr;
            }
            $attr->set("value", $value);
			//parent::set($attribute,$value);
		}
		return $this;
	}

    //OVERRIDE
    public function get($attribute) {
        if (!array_key_exists($attribute, $this->_fields)) {
            return null;
        }
        $attr = $this->_fields[$attribute];
        if (!$attr) {
            $attr = new Attribute();
            $this->_fields[$attribute] = $attr;
        }
        return $attr->get("value");
    }

    /**
     * Set Name
     *
     * @param string $name
     * @return prospect
     */
    public function setName($name)
    {
        $attr = $this->_fields["name"];
        if (!$attr) {
            $attr = new Attribute();
            $this->_fields["name"] = $attr;
        }
        $attr->set("value", $name);
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
        $name_attr = $this->_fields["name"];
        if (!$name_attr) {
            $name_attr = new Attribute();
            $this->_fields["name"] = $name_attr;
        }
        $name_attr->set("value", $name);
        //parent::set("name",$name); //no re-setting sub-names
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
        return $form->getWorldAppLabel($bh);
	}

    public function getQuestionMappings($fieldname, $form) {
        return $form->getQuestionMappings($fieldname);
    }


    public function getDateWithFormat($label, $format = "d/m/Y") {
        $date = $this->get($label);
        if (!$date) {
            return "01/01/0001";
        }
        $date = $date / 1000; //int

        $dateObject = new \DateTime();
        $dateObject->setTimeStamp($date);

        $string = $dateObject->format($format);
        $this->log_debug("$label: $string");
        return $string;
    }

    public function getDateOfBirthWithFormat($format = "d/m/Y") {
        return $this->getDateWithFormat("dateOfBirth", $format);
    }

    private function getBullhornFields() {
        $ret = [];
        foreach (array_keys($this->_fields) as $key) {
			//exceptions need to be here
			if ($key == 'userDateAdded' ||
				$key == 'webResponse' ||
                $key == 'validated' ||
                $key == 'files' ||
                //$key == 'specialties' ||
				preg_match('/WithholdingsAmount/', $key)) {
			} else {
                $ret[] = $key;
            }
        }
        return $ret;
    }

	public function getBullhornFieldList() {
		$list = "";
		foreach ($this->getBullhornFields() as $key) {
			$list .= $key.",";
		}
		$list = substr($list, 0, strlen($list)-1); //remove last comma
		return $list;
	}

	public function loadCustomObject($index = 1) {
        $name = "customObject".$index."s";
        $customObject = null;
        //$customObject = $this->get($name);
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
                $this->log_debug("Custom Object attribute found: $attr");
				$there = true;
				if ($m[1] == 'textBlock3') {
					$value = preg_replace("/Additional Candidate Notes: /", "", $value);
                    $separator = "\n\n";
				} else {
                    $separator = ", ";
                }
                $old = $co->get($m[1]);
                if ($old) {
                    $value = $old.$separator.$value;
                }
			    $co->set($m[1], $value);
			}
		}
		if ($there) {
			$this->set($name, $co);
            $this->log_debug("Found a custom object $name");
			return $co;
		} else {
            $this->log_debug("No Custom object with index $index");
            return null;
            //$this->log_debug("creating a new $name ($index) for ".$this->get("id"));
            //$obj = new \Stratum\Model\CustomObject();
            //$this->setCustomObject($index, $obj);
			//return $obj;
		}
	}

    public function setCustomObject($index, \Stratum\Model\CustomObject $object) {
        $name = "customObject".$index."s";
        $this->set($name, $object);
    }

	public function marshalCustomObject($index = 1) {
        $obj = $this->loadCustomObject($index);
        if ($obj && is_a($obj, "\Stratum\Model\CustomObject")) {
            $this->var_debug($obj);
		    return $obj->marshalToArray();
        } else {
            return null;
        }
	}

	public function loadReferences() {
		$references = $this->get("references");
		if ($references) {
			return $references;
		}
        $references = []; //initialize
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
        //$this->var_debug($references);

		foreach ($references as $label=>$ref) {
        	//we need to submit these references as new BH objects
			//then attach their ids to the Candidate within BH
            if (is_a($ref, "\Stratum\Model\CandidateReference")) {
			    $json['references'][$label] = $ref->marshalToJSON();
            }
		}
        //$this->log_debug($json);
		return $json;
	}

	public function marshalToJSON() {
		$json = [];
		$addresses = [];
		$references = [];
        $note = [];
		foreach ($this->expose_bullhorn_set() as $attr=>$value) {
			//now we filter based on what we have vs. what Bullhorn knows
			if ($attr=='customFloat2') {
				//bonus potential
				$number = preg_match("/\s*(\d+)\s*%/", $value, $m);
				if ($number) {
					$value = $m[1];
				}
			}
			if (preg_match("/date/", $attr) && $value) {
				//need to convert to Unix timestamp
                $this->log_debug("$attr: ".$value);
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
					   $attr == 'references' || 			//references are handled separately
					   $attr == 'diploma' ||  //boolean
					   $attr == 'reportToPerson' || //this is for ClientContact
                       $attr == 'NONE'  ||          //Q42 Daily or Hourly rate...
                       $attr == 'specialties' || //handled separately
                       $attr == 'categories' ||  //handled separately
					   $attr == 'employerAtRegistration') { //??
				//skip
			} else if (preg_match("/(recommender\d)_(.*)/", $attr)) {
				//handled in marshalReferences
			} else if (preg_match("/customObject/", $attr)) {
				//handled in marshalCustomObject
			} else if ($attr != "taxID" && preg_match("/ID$/", $attr)) {
				//ID means a secondary object that must be added later
				//skip for now
                //exception - taxID!
			} else if ($attr == "address" || $attr == "secondaryAddress") {
                //handle below
            } else if (preg_match("/(.*ddress)\((.*)\)/", $attr, $m)) {
				//these are address or secondaryAddress fields
				$addrLabel = $m[1];
				$addresses[$addrLabel][$m[2]] = $value;
			} else if ($attr == 'Note') {
                foreach ($value as $val) {
                    $this->log_debug("$attr: $val");
                }
			} else {
				$json[$attr] = $value;
			}
		}
		foreach ($addresses as $label=>$address) {
			//should be secondaryAddress and address
			$json[$label] = $address;
		}
        $addr1 = $this->get("address");
        if (is_array($addr1)) {
            //ignore for now
        } else if ($addr1 && is_a($addr1, "\Stratum\Model\Address")) {
            $jsonAddr1 = $addr1->marshalToJSON();
            $json["address"] = json_decode($jsonAddr1, true);
        }
        $addr2 = $this->get("secondaryAddress");
        if (is_array($addr2)) {
            //ignore for now
        } else if ($addr2 && is_a($addr2, "\Stratum\Model\Address")) {
            $jsonAddr2 = $addr2->marshalToJSON();
            $json["secondaryAddress"] = json_decode($jsonAddr2, true);
        }
		/*
		 *
		 * This section would continually add new custom objects
		 * to the Candidate record
         */
        for ($i=1; $i<=3; $i++) {
		    if ($this->loadCustomObject($i)) {
			//there is something there
			    $json['customObject'.$i.'s'] = $this->marshalCustomObject();
            }
		}


		$encoded = json_encode($json, true);
		$this->log_debug($encoded);
		return $encoded;
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

    public function expose_bullhorn_set() {
        $set = array(); //array of set fields
        foreach ($this->getBullhornFields() as $field) {
            $value = $this->get($field);
            if (!empty($value)) {
                $set[$field] = $value;
            }
        }
        //$this->log_debug(json_encode($set));
        return $set;
    }

    public function exportSummaryToHTML($form) {
        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo "\n\t<h3 class='box-title'>Candidate Data</h3>";
        echo "\n</div>";
        echo "\n<div class='box-body'>\n";
        echo "\n<div class='table-responsive'>";
        echo "\n<table class='table'>\n";
        echo "\n<thead>\n<tr>";
        echo "\n<th><button class='btn btn-secondary btn-sm'>Field Name</button></th>";
        echo "\n<th><label>Value</label></th>\n";
        echo "\n</tr></thead>";
        echo "\n<tbody>";
        $summary = ["id", "firstName", "lastName", "email", "email2", "mobile", "phone", "workPhone", "fax3", "pager", "customTextBlock2"];
        foreach ($summary as $item) {
            $value = '';
            if ($item=="customTextBlock2") {
                $types = $this->get($item);
                $this->log_debug("At Discipline, types is");
                $this->var_debug($types);
                if (is_array($types)) {
                    foreach ($types as $t) {
                        $value .= $t.";";
                    }
                    $value = substr($value, 0, strlen($value)-1); //remove last semi-colon
                } else {
                    $value = $types;
                }
            } else if ($item == "dateOfBirth") {
                $dob = $this->get("dateOfBirth");
                if (is_numeric($dob)) {
                    $value = $this->getDateOfBirthWithFormat("d/m/Y");
                } else {
                    $value = $dob;
                }
            } else {
                $value = $this->get($item);
            }
            //we have value
            $wa = $this->getWorldAppLabel($item, $form);
            //we have human-readable label
            //let's display this!
            echo "\n<tr>";
            //echo "\n<div class='form-group'>";
            echo "\n<td>";
            echo "\n<button class='btn btn-secondary btn-sm'>".$wa."</button>";
            echo "\n</td><td>";
            echo "\n<label>$value</label>\n";
            echo "\n</td></tr>";
            //echo "</div>\n";
        }
        echo "\n</tbody>";
        echo "\n</table>";
        echo "</div>\n"; //table-responsive
        echo "</div>\n"; //box-body
        echo "</div>\n"; //box

    }


    public function exportToHTML($form) {
        echo '<div class="box box-primary collapsed-box">';
        echo '<div class="box-header with-border">';
        echo "\n\t<h3 class='box-title'>Candidate Data</h3>";
        echo "\n\t".'<div class="box-tools pull-right">';
        echo "\n\t\t".'<button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse/Expand"><i class="fa fa-plus"></i></button>';
        echo "\n\t</div>";
        echo "\n</div>";
        echo "\n<div class='box-body' style='display: none;'>\n";
        echo "\n<div class='form-group'>";
        echo "\n<button class='btn btn-info btn-sm'>Bullhorn Fieldname</button>";
        echo "\n<button class='btn btn-secondary btn-sm'>WorldApp (Human-Readable) Name</button>";
        echo "\n<label>Value</label>\n";
        echo "\n</div>\n";
        //loop through form questions instead of candidate fields (which are alphabetical and not Human-Readable)
        foreach ($form->get("questionMappings") as $qmap) {
            $wa = $qmap->get("WorldAppAnswerName");
            $bh = $qmap->get("BullhornField");
            $value = $this->get($bh);
            if (!$value) {
                //no value
                continue;
            }
            $type = $this->get("type");
            if (strpos('Yes', $wa) > 0) {
                preg_match("/(.*)\s(Yes)/", $wa, $m);
                $wa2 = $m[1];
                $yn = $m[2];
                $this->log_debug("Boolean: $wa");
                $this->log_debug("wa2: $wa2");
                $this->log_debug("yn: $yn");
            }
            if (strpos('No', $wa) > 0) {
                preg_match("/(.*)\s(No)/", $wa, $m);
                $wa2 = $m[1];
                $yn = $m[2];
                $this->log_debug("Boolean: $wa");
                $this->log_debug("wa2: $wa2");
                $this->log_debug("yn: $yn");
            }
            if (is_a($value, "\Stratum\Model\ModelObject")) {
                $value->exportToHTML($form);
            } else if (is_array($value)) {
                echo "\n<div class='form-group'>";
                echo "\n<button class='btn btn-info btn-sm'>".$bh."</button>";
                echo "\n<button class='btn btn-secondary btn-sm'>".$wa."</button>";
                //now go through the details
                foreach($value as $index=>$detail) {
                    //$this->log_debug("index is $index");
                    $wa2 = $this->getWorldAppLabel($index, $form);
                    if (!$wa2) {
                        $wa2 = $index;
                    }
                    //$this->log_debug("wa2 is $wa2");
                    $string = '';
                    if (is_array($detail)) {
                        $this->log_debug("detail is an array");
                        $this->var_debug($detail);
                        foreach ($detail as $micro) {
                            if (is_array($micro)) {
                                $this->log_debug("micro is an array");
                                $string .= implode(", ", $micro);
                            }
                            else {
                                $string .= $micro;
                            }
                        }
                    } else {
                        $string = $detail;
                    }
                    //$this->log_debug("String is $string");
                    if ($string) {
                        echo "\n<div class='form-group'>";
                        echo "\n<button class='btn btn-info btn-sm'>".$index."</button>";
                        echo "\n<button class='btn btn-secondary btn-sm'>".$wa2."</button>";
                        echo "\n<label>$string</label>\n";
                        echo "</div>\n";
                    }
                }
            } else {
                echo "\n<div class='form-group'>";
                echo "\n<button class='btn btn-info btn-sm'>".$bh."</button>";
                echo "\n<button class='btn btn-secondary btn-sm'>".$wa."</button>";
                echo "\n<label>$value</label>\n";
                echo "</div>\n";
            }

        }
        //$this->var_debug(array_keys($typeArray));
        /*
        foreach ($this->_fields as $key=>$there) {
            if ($there) {
                if ($key == 'categories') {
                    continue;
                } else if (is_a($there, "\Stratum\Model\ModelObject")) {
                    $there->exportToHTML($form);
                } else if (is_array($there)) {
                    $this->log_debug("There is an array");
                    $wa = $this->getWorldAppLabel($key, $form);
                    echo "\n<div class='form-group'>";
                    echo "\n<button class='btn btn-info btn-sm'>".$key."</button>";
                    echo "\n<button class='btn btn-secondary btn-sm'>".$wa."</button>";


                } else {
                    $wa = $this->getWorldAppLabel($key, $form);
                    echo "\n<div class='form-group'>";
                    echo "\n<button class='btn btn-info btn-sm'>".$key."</button>";
                    echo "\n<button class='btn btn-secondary btn-sm'>".$wa."</button>";
                    echo "\n<label>$there</label>\n";
                    echo "</div>\n";
                }
            }
        }
        */
    }

    public function populateFromData($data) {
		foreach ($data as $key=>$value) {
            if ($key == "customText19") {
                $value = preg_replace("/Equipment Sup./", "Equipment Supplier", $value);
                $value = preg_replace("|PE/IB |","PE / IB / Trading", $value);
                $value = preg_replace("|\*|","Other", $value);
            }
			$this->set($key, $value);
		}
		return $this;
	}


    public function validField($key) {
        return array_key_exists($key, $this->_fields);
    }


    public function expose_set() {
        $set = array(); //array of set fields
        foreach ($this->_fields as $field=>$value) {
            if (!empty($value)) {
                $set[$field] = $this->get($field);
            }
        }
        //$this->log_debug(json_encode($set));
        return $set;
    }

    public function log_this($object) {
        $this->var_debug($object);
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
