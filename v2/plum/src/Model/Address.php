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
class Address extends ModelObject
{
    protected $_fields = ['address1'=>'',
						  'address2'=>'',
						  'city'=>'',
						  'state'=>'',
						  'zip'=>'',
						  'countryID'=>'',
						  'countryName'=>''
						  ];


	public function populateFromData($data) {
		foreach ($data as $key=>$value) {
			$this->set($key, $value);
		}
		return $this;
	}


	public function dump() {
		$this->log_debug( "---------------------------");
		$this->log_debug( "Stratum\Model\Address:");
		foreach ($this->_fields as $key=>$there) {
			if ($there) {
				$this->log_debug( $key.": ");
				$this->var_debug($there);
			}
		}
		$this->log_debug( "---------------------------");
	}

}
