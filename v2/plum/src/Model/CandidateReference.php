<?php
/*
 * CandidateReference.php
 * Base model for candidate reference
 * Data model for transfer between WorldApp and Bullhorn
 * 
 * Copyright 2015
 * @category    Stratum
 * @package     Stratum
 * @copyright   Copyright (c) 2015 North Creek Consulting, Inc. <dave@northcreek.ca>
 * 
 */

namespace Stratum\Model;
class CandidateReference extends ModelObject
{
    const XML_PATH_LIST_DEFAULT_SORT_BY     = 'catalog/frontend/default_sort_by';
    
    /**
     * Array of attributes codes needed for product load
     *
     * @var array of tag/values
     */
    protected $_fields = ['name'=>'', 
						  'referenceFirstName'=>'',
						  'referenceLastName'=>'',
						  'id'=>'',
						  'companyName'=>'',
						  'referenceTitle'=>'',
						  'referencePhone'=>'',
						  'referenceEmail'=>'',
						  'customTextBlock1'=>'',
						  'isDeleted'=>''
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
	
	public function getWorldAppLabel($bh, $form) {
		$wa = "";
		$mappings = $form->get("BHMappings");
		if (array_key_exists($bh, $mappings)) {
			$this->log_debug( "Found $bh");
			$qmaps = $mappings[$bh];
			foreach ($qmaps as $qmap) {
				$wa = $qmap->get("WorldAppAnswerName");
				if ($wa) {
					$this->log_debug( "$wa");
				}
			}
		}
		return $wa;
	}
	
	public function getBullhornFieldList() {
		$list = "";
		foreach (array_keys($this->_fields) as $key) {
			//exceptions need to be here
			if ($key == 'customObject' ||
				$key == 'userDateAdded' ||
				$key == 'webResponse' ||
				preg_match('/WithholdingsAmount/', $key)) {
			} else {
				$list .= $key.",";
			}
		}
		$list = substr($list, 0, strlen($list)-1); //remove last comma 
		return $list;
	}
	
	public function populateFromData($data) {
		foreach ($data as $key=>$value) {
			$this->set($key, $value);
		}
		return $this;
	}	
	
	public function marshalToJSON() {
		$json = [];
		foreach ($this->expose_set() as $attr=>$value) {
			//now we filter based on what we have vs. what Bullhorn knows
			$json[$attr] = $value;
		}
		$encoded = json_encode($json, true);
		//$this->var_debug($encoded);
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
	
	public function dump() {
		$this->log_debug( "---------------------------");
		$this->log_debug( "Stratum\Model\CandidateReference:");
		foreach ($this->_fields as $key=>$there) {
			if ($there) {
				$this->log_debug($key.": ");
				$this->var_debug($there);
			}
		}
		$this->log_debug( "---------------------------");
	}

}


