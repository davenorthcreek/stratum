<?php
/*
 * Skill.php
 * Base model for Candidate primary and secondary Skills
 * Data model for transfer between WorldApp and Bullhorn
 * 
 * Copyright 2015
 * @category    Stratum
 * @package     Stratum
 * @copyright   Copyright (c) 2015 North Creek Consulting, Inc. <dave@northcreek.ca>
 * 
 */

namespace Stratum\Model;
class Skill extends ModelObject
{
    
    /**
     * Array of attributes codes needed for product load
     *
     * @var array of tag/values
     */
    protected $_fields = ['id'=>'', 
						  'enabled'=>'',
						  'categories'=>'',
						  'name'=>'',
						  'isDeleted'=>''
						  ];

	
	public function dump() {
		$this->log_debug( "---------------------------");
		$this->log_debug( "Stratum\Model\Skill:");
		foreach ($this->_fields as $key=>$there) {
			if ($there) {
				$this->log_debug($key.": ");
				$this->var_debug($there);
			}
		}
		$this->log_debug( "---------------------------");
	}

}


