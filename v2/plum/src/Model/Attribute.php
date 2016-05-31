<?php
/*
 * Attribute.php
 * Base model for Candidate attributes
 * Allows tracking of source and destination for any datum
 *
 * Copyright 2016
 * @category    Stratum
 * @package     Stratum
 * @copyright   Copyright (c) 2016 North Creek Consulting, Inc. <dave@northcreek.ca>
 *
 */

namespace Stratum\Model;
class Attribute extends ModelObject
{

    /**
     * Array of attributes codes needed for product load
     *
     * @var array of tag/values
     */
    protected $_fields = ['name'=>'',
						  'question'=>'',
						  'questionMapping'=>'',
						  'value'=>'',
						  'htmlStrategy'=>'',
                          'bullhornField'=>'',
                          'worldAppField'=>'',
                          'bullhornStrategy'=>'',
                          'worldAppStrategy'=>''
						  ];


	public function dump() {
		$this->log_debug( "---------------------------");
		$this->log_debug( "Stratum\Model\Attribute:");
		foreach ($this->_fields as $key=>$there) {
			if ($there) {
				$this->log_debug($key.": ");
				$this->var_debug($there);
			}
		}
		$this->log_debug( "---------------------------");
	}

}
