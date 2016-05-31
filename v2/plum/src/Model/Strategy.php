<?php
/*
 * Strategy.php
 * Base object for Strategy pattern for merging data from WorldApp / Plum
 *  to Bullhorn data model.
 * Data model for transfer between WorldApp and Bullhorn
 *
 * Copyright 2016
 * @category    Stratum
 * @package     Stratum
 * @copyright   Copyright (c) 2016 North Creek Consulting, Inc. <dave@northcreek.ca>
 *
 */

namespace Stratum\Model;
class Strategy extends ModelObject
{

    /**
     * Array of attributes codes needed for product load
     *
     * @var array of tag/values
     */
    protected $_fields = ['name'=>'',

						  ];


	public function dump() {
		$this->log_debug( "---------------------------");
		$this->log_debug( "Stratum\Model\Strategy:");
		foreach ($this->_fields as $key=>$there) {
			if ($there) {
				$this->log_debug($key.": ");
				$this->var_debug($there);
			}
		}
		$this->log_debug( "---------------------------");
	}

}
