<?php

/**
 * Worldapp client, based on Dropbox client example:
 * Example of retrieving an authentication token of the Dropbox service
 *
 * PHP version 5.4
 *
 * @author     Flávio Heleno <flaviohbatista@gmail.com>
 * @copyright  Copyright (c) 2012 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 *
 * @author David Block dave@northcreek.ca
 */

namespace Stratum\Client;

use \OAuth\Common\Storage\Session;
use \OAuth\Common\Consumer\Credentials;
use \Dotenv\Dotenv;
use SoapClient;

class Worldapp {
	
	function var_debug($object=null) {
		ob_start();                    // start buffer capture
		var_dump( $object );           // dump the values
		$contents = ob_get_contents(); // put the buffer into a variable
		ob_end_clean();                // end capture
		$this->log_debug( $contents );        // log contents of the result of var_dump( $object )
	}
	
	//allow someone to pass in a $logger
	protected $_logger;
	
	public function setLogger($lgr) {
		//$lgr better be a logger of some sort -missing real OOP here
		$this->_logger = $lgr;
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
			echo $str."\n";
		}
	}

	protected $storage;
	private $service;
	private $httpClient;
	private $access;
	private $base_url;
	private $session_key;
	
	/**
     * Initialize 
     *
     */
    public function __construct($fields = array()) {
	}
	
	public function init() {
			//want logging, need to wait until after construction so logging can be set up
		error_reporting(E_ALL);
		ini_set('display_errors', 1);

		/**
		 * Setup the timezone
		 */
		ini_set('date.timezone', 'America/Edmonton');

	
		//$this->service = $bullhornService;
		//$this->httpClient = $httpClient;	
	}
	
	private function getClient($wsdl) {
		$directory = __DIR__;
		//echo "Currently at ".$directory."\n";
		$newdir = preg_replace("|\/src\/Client|", "", $directory);
		//echo "Now at ".$newdir."\n";
		
		$dotEnv = new Dotenv($newdir);
		//Dotenv::load(__DIR__);
		$dotEnv->load();
		$username =  getenv('WORLDAPP_USERNAME');
		$password =  getenv('WORLDAPP_PASSWORD');
		
		$client = new SoapClient("$newdir/wsdl/$wsdl.wsdl", 
						array('login'          => $username,
                              'password'       => $password,
                              'trace'          => true
                              ));
        return $client;
	}
	
	public function getForms() {
		
		$client = $this->getClient("FormDesignManagementService");
		$accountID = getenv('ACCOUNT_ID');
                              
        $requestPayloadString = ['accountId'=>$accountID];
        
        $objResponse = $client->getForms($requestPayloadString);
        $forms = $objResponse->return;
        
        return $forms;
	}
	
	public function getForm($id) {
		
		$client = $this->getClient("FormDesignManagementService");
                              
        $requestPayloadString = ['formId'=>$id];
        
        $objResponse = $client->getForm($requestPayloadString);
        
        $form = $objResponse->return;

        return $form;
	}
	
	public function getQuestions($formID, $withAnswer) {
		
		$client = $this->getClient("FormDesignManagementService");
		
		$objResponse = $client->getQuestions(['formId'=>$formID,
											  'withAnswer'=>$withAnswer
											 ]);
		$questions = $objResponse->return;
		
		return $questions;
	}	
	
	public function getQuestion($questionID, $withAnswer) {
		
		$client = $this->getClient("FormDesignManagementService");
		
		$objResponse = $client->getQuestion(['questionId'=>$questionID,
											  'withAnswer'=>$withAnswer
											 ]);

		$question = $objResponse->return;

		return $question;
	}	
	
	public function sendUrlWithAutofillByEmail($formId, $email, $autofill) {
		
		$client = $this->getClient("LaunchManagementService");
		
		$items = [];
		foreach ($autofill as $qid=>$list) {
			$items[] = ['questionId'=>$qid,
						'answers'=>$list
					   ];
		}
		try {
			$objResponse = $client->sendUniqueUrlWithAutofillByEmail(
						['formId'=>$formId,
						 'urlType'=>'REGULAR',
						 'autofillDataList'=>
							['email'=>$email,
							 'items'=>$items
							]
						]);
			echo "response: ".$client->__getLastResponse()."\n";
			return $objResponse;
		} catch (\Exception $ex) {
			echo "response: ".$client->__getLastResponse()."\n";
			//var_dump($ex);
			//var_dump($ex->detail);	
			echo "request : ".$client->__getLastRequest()."\n";
		}
	}
    
    public function getEmailTemplate($formId) {
        
        $client = $this->getClient("LaunchManagementService");
		
        $objResponse = $client->getFormEmailTemplate(
                        ['formId' => $formId
                        ]);
        //echo "Response: ".$client->getLastResponse()."\n";
        return $objResponse->return;
    }
    
    public function setEmailTemplate($template) {
        
        $client = $this->getClient("LaunchManagementService");
        
        /**
         * 
         *  The e-mail message template, that is used for distributing unique URLs.
            Can contain the following information:

                formId - the id of the form;
                from - indicates the 'from' address for your e-mail message;
                replyTo - indicates an e-mail address the reply e-mail messages of the respondents will be sent to;
                undeliveredTo - indicates the 'undeliveredTo' address for your e-mail message;
                subject - indicates the subject of your e-mail message;
                content - indicates the body content of the e-mail message; 
        **/
        
        $objResponse = $client->setFormEmailTemplate(
                        ['template' => 
                            ['formId'   =>  $template['formId'],
                             'from'     =>  $template['from'],
                             'replyTo'  =>  $template['replyTo'],
                             'subject'  =>  $template['subject'],
                             'content'  =>  $template['content']
                            ]
                        ]);

        //echo "Response: ".$client->getLastResponse()."\n";
        return $objResponse;
        
    }

}
