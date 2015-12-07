<?php
/*
 * CustomObject.php
 * Stratum designed a Custom Object to store odd bits of data
 * Data model for transfer between WorldApp and Bullhorn
 * 
 * Copyright 2015
 * @category    Stratum
 * @package     Stratum
 * @copyright   Copyright (c) 2015 North Creek Consulting, Inc. <dave@northcreek.ca>
 * 
 */

namespace Stratum\Model;
class CustomObject extends ModelObject
{
    
    /**
     * Array of attributes codes needed for product load
     *
     * @var array of tag/values
     */
    protected $_fields = ['id'=>'',
						  'address'=>'',
						  'customDate1'=>'',
						  'customDate2'=>'',
						  'customDate3'=>'',
						  'customFloat1'=>'',
						  'customFloat2'=>'',
						  'customFloat3'=>'',
						  'int1'=>'',
						  'int2'=>'',
						  'text1'=>'',
						  'text2'=>'',
						  'text3'=>'',
						  'text4'=>'',
						  'text5'=>'',
						  'text6'=>'',
						  'text7'=>'',
						  'text8'=>'',
						  'text9'=>'',
						  'text10'=>'',
						  'text11'=>'',
						  'text12'=>'',
						  'textBlock1'=>'',
						  'textBlock2'=>'',
						  'textBlock3'=>'',
						  'textBlock4'=>'',
						  'textBlock5'=>'',
						  'dateAdded'=>''
						  ];
	
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
		return [$json];
	}
	
	public function dump() {
		$this->log_debug("---------------------------");
		$this->log_debug("Stratum\Model\CustomObject:");
		foreach ($this->_fields as $key=>$there) {
			if ($there) {
				$this->log_debug($key.": ");
				$this->var_debug($there);
			}
		}
		$this->log_debug("---------------------------");
	}

}


