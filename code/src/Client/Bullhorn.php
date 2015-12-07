<?php

/**
 * Bullhorn client, based on Dropbox client example:
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

use \OAuth\Common\Storage\Session;
use \OAuth\Common\Consumer\Credentials;
use \OAuth\Common\Http\Client\CurlClient;
use \Stratum\OAuth\OAuth2\Service\BullhornService;


namespace Stratum\Client;
class Bullhorn {
	
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

		
		// Session storage
		$storage = new \OAuth\Common\Storage\Session(false);

		$cred_string = file_get_contents("credentials.json");
		//$this->log_debug($cred_string."");
		$servicesCredentials = json_decode($cred_string, true);
		//$this->var_debug($servicesCredentials);

		/**
		 * Create a new instance of the URI class with the current URI, stripping the query string
		 */
		$uriFactory = new \OAuth\Common\Http\Uri\UriFactory();
		//$currentUri = $uriFactory->createFromSuperGlobalArray($_SERVER);
		$currentUri = $uriFactory->createFromAbsolute("http://localhost");
		$currentUri->setQuery('');
		//$this->var_debug($currentUri);
		
		$httpClient = new \OAuth\Common\Http\Client\CurlClient();
		
		$httpClient->setCurlParameters([CURLOPT_HEADER=>true]);
		
		// Setup the credentials for the requests
		$credentials = new \OAuth\Common\Consumer\Credentials(
			$servicesCredentials['bullhorn']['key'],
			$servicesCredentials['bullhorn']['secret'],
			$currentUri->getAbsoluteUri()
		);
		
		// Instantiate the Bullhorn service using the credentials, http client and storage mechanism for the token
		/** @var $bullhornService Bullhorn */
		$this->log_debug("Creating new BullhornService");
		$bullhornService = new \Stratum\OAuth\OAuth2\Service\BullhornService($credentials, $httpClient, $storage, array());
		
		$attempt = 0;
		
		if (empty($servicesCredentials['bullhorn']['lastRefreshToken'])) {
			$this->log_debug("Authorize");
			$this->authorize($bullhornService, $httpClient, $servicesCredentials);
		} else {
			$this->log_debug("Get access via refresh");
			$this->load_refresh($bullhornService, $httpClient, $servicesCredentials);
		}
		$this->service = $bullhornService;
		$this->httpClient = $httpClient;	
	}
	
	private function load_refresh($bullhornService, $httpClient, $servicesCredentials) {
		$refresh = $servicesCredentials['bullhorn']['lastRefreshToken'];
		$uri = $bullhornService->getAccessTokenUri($refresh);
		$this->log_debug("Getting access token with $refresh");
		$response = $httpClient->retrieveResponse($uri, '', []);
		$decoded = $this->extract_json($response);
		$this->var_debug($decoded);
		if (array_key_exists("error", $decoded)) {
			$this->log_debug("Unable to use refresh token, going through authorize");
			$this->authorize($bullhornService, $httpClient, $servicesCredentials);
		} else {
			$this->log_debug("We have a good refresh token");
			$ref = $decoded["refresh_token"];
			$token = $decoded["access_token"];
			$this->get_login($ref, $token, $bullhornService, $httpClient, $servicesCredentials);
		}
	}

	private function load_refresh_with_code($bullhornService, $httpClient, $servicesCredentials) {
		$code = $servicesCredentials['bullhorn']['code'];
		$this->log_debug("Asking for auth token with code $code");
		$uri = $bullhornService->getAccessTokenWithCodeUri($code);
		$response = $httpClient->retrieveResponse($uri, '',  []); //supposed to be POST
		$decoded = $this->extract_json($response);
		$this->var_debug($decoded);
		if (array_key_exists("error", $decoded)) {
			$this->log_debug("Problem with getting access via code $code");
			die ("Problem with getting access via code $code\n");
		} else {
			$this->log_debug("we have a good refresh token with code $code");
			$ref = $decoded["refresh_token"];
			$token = $decoded["access_token"];
			$this->get_login($ref, $token, $bullhornService, $httpClient, $servicesCredentials);
		}
	}
	
	private function extract_json($string) {
		return json_decode(trim(substr($string, strpos($string, '{'))), true);
	}
	
	private function get_login($ref, $token, $bullhornService, $httpClient, $servicesCredentials) {
		$this->log_debug("At login function with refresh $ref and token $token");
		$servicesCredentials['bullhorn']['lastRefreshToken'] = $ref;
		file_put_contents('credentials.json', json_encode($servicesCredentials));
		$login = $bullhornService->getLoginUri($token);
		$response2 = $httpClient->retrieveResponse($login, '', []);
		$decoded2 = $this->extract_json($response2);
		$this->log_debug("Login json decoded:");
		$this->var_debug($decoded2);
		$this->session_key = $decoded2["BhRestToken"];
		$this->base_url = $decoded2["restUrl"];
		$this->log_debug("Successfully logged in to Bullhorn");
	}
	
	private function authorize($bullhornService, $httpClient, $servicesCredentials) {
		$uri2 = $bullhornService->getAuthorizationUri(['password'=>'644_london']);
		$this->log_debug("Attempting an authorization");
		$this->var_debug($uri2);
		$authResponse = $httpClient->retrieveResponse($uri2, '', [], 'GET');
		$html_start = strpos($authResponse, '<!DOCTYPE html>');
		$headers = substr($authResponse, 0, $html_start); 
		$this->log_debug($headers."");
		if (preg_match("|Location: (https?://\S+)|", $headers, $m)) {
			$this->log_debug("Location: ".$m[1]."");
			if (preg_match("|code=(\S+)\&client_id|", $m[1], $n)) {
				$code = urldecode($n[1]);
				$servicesCredentials['bullhorn']['code'] = $code;
				$this->load_refresh_with_code($bullhornService, $httpClient, $servicesCredentials);
			}
		}
		if ($this->base_url) {
			$this->log_debug("We have a login");
		} else {
			$this->log_debug("No login, die");
			die("Unable to login\n\n");
		}
	}
	
	public function getSessionKey() {
		return $this->session_key;
	}
	
	public function search($query) {
		//search based on string provided, may be name or ID
		$bullhornService = $this->service;
		$client = $this->httpClient;
		
		$find_uri = $bullhornService->getSearchUri($this->base_url, $this->session_key, $query);
		//$this->log_debug("Searching for query ".$query."");
		//$this->var_debug($find_uri);
		$response = $client->retrieveResponse($find_uri, '', [], 'GET');
		
		$decoded = $this->extract_json($response);
		
		//all fields from Bullhorn saved in $candidate
		$this->log_debug("Response from Bullhorn:");
		$this->var_debug($decoded);
		//pull out the IDs from the list and stuff them in $ids
		$candidates = [];
		$index = 0;
		if (array_key_exists("data", $decoded)) {
			foreach ($decoded["data"] as $found) {
				if (array_key_exists("entityType", $found) &&
					$found["entityType"] == "Candidate") {
					$candidates[$index] = new \Stratum\Model\Candidate();
					$candidates[$index]->setLogger($this->_logger);
					$candidates[$index]->set("id", $found['entityId']);
					$candidates[$index]->set("name", $found['title']);
					$candidates[$index]->set("byLine", $found['byLine']);
					$candidates[$index]->set("location", $found['location']);
				}
				$index++;
			}
		}
		return $candidates;
	}
	
	public function find($candidate) {
		//use REST API to look up candidate
		//based on attributes in $candidate
		//return result of query 
		
		$bullhornService = $this->service;
		
		$fieldList = $candidate->getBullhornFieldList();
		
		$find_uri = $bullhornService->getFindUri($this->base_url, $this->session_key, $candidate->get("id"), $fieldList);
		$this->log_debug("Looking for Candidate ID ".$candidate->get("id")."");
		//$this->var_debug($find_uri);
		$client = $this->httpClient;
		$response = $client->retrieveResponse($find_uri, '', [], 'GET');
		
		$decoded_cand = $this->extract_json($response);
		
		//all fields from Bullhorn saved in $candidate
		
		if (array_key_exists('data', $decoded_cand)) {
			$candidate->populateFromData($decoded_cand['data']);
			//$candidate->dump();
		} else {
			$this->log_debug("Error Response from Bullhorn:");
			$this->var_debug($decoded_cand);	
		}
		return $candidate;
	}
	
	private function lookup_country($country) {
		if (!$this->base_url) {
			return null;
		}			
		$lkup_url = $this->base_url."options/Country";
		$lkup_uri = $this->service->getRestUri($lkup_url, $this->session_key, ['filter'=>$country]);
		$lkup = $this->httpClient->retrieveResponse($lkup_uri, [], [], 'GET');
		$lkup_decoded = $this->extract_json($lkup);
		$country_ID = $lkup_decoded['data'][0]['value'];
		return $country_ID;
	}		
	
	public function submit_candidate($candidate) {
		//use REST API to carefully submit all parts of candidate record
		//to Bullhorn - in order so that one-to-many relationships, etc.
		//all work
		
		$decoded = [];

		$country_ID = 0;
		$second_ID  = 0;
		
		//look up country ids from country names in Address fields
		$addr_country = $candidate->get('address(countryID)');
		if ($addr_country) {
			$country_ID = $this->lookup_country($addr_country);
			$candidate->set('address(countryID)', $country_ID);
		}
		$second_country = $candidate->get('secondaryAddress(countryID)');
		if ($second_country) {
			if ($second_country == $addr_country) {
				//skip unnecessary duplicate lookup
				$second_ID = $country_ID;
			} else {
				$second_ID = $this->lookup_country($second_country);
			}
			$candidate->set('secondaryAddress(countryID)', $second_ID);
		}
		
		$cand_data = $candidate->marshalToJSON();
				
		//first, does the candidate have an id?
		$id = $candidate->get("id");
		if ($id) {
			//that means we can update (POST)
			$post_url = $this->base_url."entity/Candidate/".$id;
			$post_uri = $this->service->getRestUri($post_url, $this->session_key);
			$result = $this->httpClient->retrieveResponse($post_uri, $cand_data, [], 'POST');
			$decoded = $this->extract_json($result);
			$this->log_debug("Update $id has response: ");
			$this->var_debug($decoded);
			if (!array_key_exists('error', $decoded)) {
				//success
				$this->log_debug("Candidate $id Updated");
			} else {
				$this->log_debug("Candidate $id update failed with problem ".$decoded['error']);
				die ("Candidate $id update failed with problem ".$decoded['error']."\n");
			}
		} else {
			//no ID means we need to create the candidate (PUT)
			$put_url = $this->base_url."/entity/Candidate";
			$put_uri = $this->service->getRestUri($put_url, $this->session_key);
			$result = $this->httpClient->retrieveResponse($put_uri, $cand_data, [], 'PUT');
			$decoded = $this->extract_json($result);
			$this->log_debug("Create new candidate has response: ");
			$this->var_debug($decoded);
			if (array_key_exists('error', $decoded)) {
				$this->log_debug("Candidate creation failed with problem ".$decoded['error']);
				die("Candidate creation failed with problem ".$decoded['error']."\n");
			} else {
				$newId = $decoded['changedEntityId'];
				$candidate->set("id", $newId);
				$this->log_debug("Candidate created with new id $newId");
				$id = $newId;
			}
		}
		
		return $decoded;
	}
	
	public function find_candidate_references($candidate) {
		//query/CandidateReference?fields=id%2CreferenceFirstName%2CreferenceLastName%2Ccandidate&where=candidate.id=10809&BhRestToken=7b7be6ef-3ff6-495f-a736-08401587393c
		$id = $candidate->get("id");
		$query_ref_url = $this->base_url."query/CandidateReference";
		$query_ref_uri = $this->service->getRestUri($query_ref_url, $this->session_key, ['fields'=>'id,referenceFirstName,referenceLastName,isDeleted,candidate', 
			'where'=>'candidate.id='.$id.' AND isDeleted=false']);
		$query_ref = $this->httpClient->retrieveResponse($query_ref_uri, '', [], 'GET');
		$query_ref_decoded = $this->extract_json($query_ref);
		$this->var_debug($query_ref_decoded);
		return $query_ref_decoded['data'];
	}
	
	public function submit_reference($ref, $candidate) {
		//$ref is a CandidateReference object
		$id = $candidate->get("id");
		$body = $ref->marshalToArray();
		$body['candidate'] = [];
		$body['candidate']['id'] = $id;
		$body['candidate']['firstName'] = $candidate->get("firstName");
		$body['candidate']['lastName'] = $candidate->get("lastName");
		$this->var_debug($body);
		$subm_ref_url = $this->base_url."entity/CandidateReference";
		$subm_ref_uri = $this->service->getRestUri($subm_ref_url, $this->session_key);
		$subm_ref = $this->httpClient->retrieveResponse($subm_ref_uri, json_encode($body), [], 'PUT');
		$subm_ref_decoded = $this->extract_json($subm_ref);
		$this->log_debug("Submitted candidate reference: ");
		$this->var_debug($subm_ref_decoded);
		return $subm_ref_decoded['changedEntityId'];
	}
	
	public function find_or_create_skill(\Stratum\Model\Skill $skill) {
		//query/Skill?fields=id,name&where=name='Geo\ -\ Exploration\ Project\ Management'&BhRestToken=
		$name = $skill->get("name");
		$query_ref_url = $this->base_url."query/Skill";
		$query_ref_uri = $this->service->getRestUri($query_ref_url, $this->session_key, ['fields'=>'id,name,isDeleted', 
			'where'=>'name='.$name.' AND isDeleted=false']);
		$query_ref = $this->httpClient->retrieveResponse($query_ref_uri, '', [], 'GET');
		$query_ref_decoded = $this->extract_json($query_ref);
		$data = $query_ref_decoded['data'];
		if ($data) {
			$id = $data[0]['id']; //???
			$skill->set("id", $id);
		} else {
			//there is no skill with that name
			//read-only field via rest api
		}
		$this->var_debug($query_ref_decoded);
		return $skill;
	}
	
	public function find_candidate_skills($candidate) {
		//query/Skill?fields=id%2Cname%2Ccandidate&where=candidate.id=10991&BhRestToken=
		$id = $candidate->get("id");
		$query_ref_url = $this->base_url."query/Skill";
		$query_ref_uri = $this->service->getRestUri($query_ref_url, $this->session_key, ['fields'=>'id,name,isDeleted', 
			'where'=>'name='.$name.' AND isDeleted=false']);
		$query_ref = $this->httpClient->retrieveResponse($query_ref_uri, '', [], 'GET');
		$query_ref_decoded = $this->extract_json($query_ref);
		$this->var_debug($query_ref_decoded);
		return $query_ref_decoded['data'];
	}
	
	public function submit_skill($sk, $candidate) {
		//$sk is a Skill object
		//https://rest.bullhorn.com/e999/entity/Candidate/3084/primarySkills/964,684,253
		$id = $candidate->get("id");
		$sk_id = $sk->get("id");
		$subm_sk_url = $this->base_url."entity/Candidate/$id/primarySkills/$sk_id";
		$subm_sk_uri = $this->service->getRestUri($subm_sk_url, $this->session_key);
		$subm_sk = $this->httpClient->retrieveResponse($subm_sk_uri, '', [], 'PUT');
		$subm_sk_decoded = $this->extract_json($subm_sk);
		$this->log_debug("Submitted candidate $id skill: $sk_id");
		$this->var_debug($subm_sk_decoded);
		return $subm_sk_decoded['changedEntityId'];  //throws error if array key does not exist
	}
	
	function delete_custom_object($id, $candidate_id) {
		//https://rest22.bullhornstaffing.com/rest-services/987up/entity/Candidate/10809/customObject1s/123
		$del_co_url = $this->base_url."entity/Candidate/$candidate_id/customObject1s/$id";
		$del_co_uri = $this->service->getRestUri($del_co_url, $this->session_key);
		$del_co = $this->httpClient->retrieveResponse($del_co_uri, '', [], 'DELETE');
		$del_co_decoded = $this->extract_json($del_co);
		$this->log_debug("Deleted Custom Object $id from Candidate $candidate_id");
		$this->var_debug($del_co_decoded);
		return $del_co_decoded;
	}

	
	public function find_custom_object($candidate) {
		$id = $candidate->get("id");
		$query1_co_url = $this->base_url."query/Candidate/$id";
		$query1_co_uri = $this->service->getFindUri($this->base_url, $this->session_key, $id, 'customObject1s');
		$query1_co = $this->httpClient->retrieveResponse($query1_co_uri, '', [], 'GET');
		$query1_co_decoded = $this->extract_json($query1_co);
		$this->var_debug($query1_co_decoded);
		// a list of IDs
		$ids = $query1_co_decoded['data']['customObject1s']['data'];
		$keep = null;
		if (is_array($ids) && count($ids) > 0) {
			//it should be an array
			//preserve 0
			$keep = $ids[0]['id'];
			foreach ($ids as $discard) {
				$the = $discard['id'];
				if ($the != $keep) {
					$this->delete_custom_object($the);
				}
			}
		}
		if ($keep) {
			//we have found a custom object with id $keep - time to download the details
			//https://rest22.bullhornstaffing.com/rest-services/987up/query/PersonCustomObjectInstance1?where=id=121&fields=*
			$query_co_url = $this->base_url."query/PersonCustomObjectInstance1";
			$query_co_uri = $this->service->getRestUri($query_co_url, $this->session_key, ['fields'=>'*', 
				'where'=>'id='.$keep]);
			$query_co = $this->httpClient->retrieveResponse($query_co_uri, '', [], 'GET');
			$query_co_decoded = $this->extract_json($query_co);
			$this->var_debug($query_co_decoded);
			return $query_co_decoded['data'][0]; //we are sure there is only one record
		}
		return [];
	}
	
	public function submit_custom_object($co, $candidate) {
		//POST https://rest.bullhornstaffing.com/rest-services/{corpToken}/entity/Candidate/32039
		$id = $candidate->get("id");
		$subm_co_url = $this->base_url."entity/Candidate/$id";
		$subm_co_uri = $this->service->getRestUri($subm_co_url, $this->session_key);
		//POST BODY: {"customObject2s":[{"id":3649,"int1":7},{"int1":4}]}
		$co_array = $co->marshalToArray();
		$body = ['customObject1s'=>$co_array];
		$this->log_debug("Submitting this data as a custom object");
		$this->var_debug($body);
		$subm_co = $this->httpClient->retrieveResponse($subm_co_uri, json_encode($body), [], 'POST');
		$subm_co_decoded = $this->extract_json($subm_co);
		$this->log_debug("Submitted customObject1: ");
		$this->var_debug($subm_co_decoded);
		return $subm_co_decoded;
	}

	
	public function confirm($candidate) {
		//should look up candidate record from Bullhorn
		//and compare data to make sure updates were recorded
		$this->log_debug("At Confirm");
		$there = $this->find($candidate);
		return $same = $candidate->compare($there);
	}

}
