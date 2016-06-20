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
use \Stratum\OAuth\OAuth2\Service\BullhornService;
use \Illuminate\Support\Facades\Storage as Storage;
use Illuminate\Support\Facades\Log as Log;

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
	private $responseHeaders;

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
			\Log::debug($str);
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

		$cred_string = \Storage::get("credentials.json");
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

		$httpClient = new \OAuth\Common\Http\Client\CurlClient();

		$httpClient->setCurlParameters([CURLOPT_HEADER=>true]);
		$httpClient->setTimeout(60);

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

        $no_session_key = true;
		if (!empty($servicesCredentials['bullhorn']['BhRestToken']) &&
            !empty($servicesCredentials['bullhorn']['base_url'])) {
            //there is a BhRestToken in ServicesCredentials
            $this->log_debug("Attempting to use previous session key");
            $this->base_url = $servicesCredentials['bullhorn']['base_url'];
            $test_uri = $bullhornService->getRestUri($this->base_url."entity/Candidate/10809",
                                                     $servicesCredentials['bullhorn']['BhRestToken'],
                                                     ['fields'=>'id']);
            $response = $httpClient->retrieveResponse($test_uri, '',  [], 'GET');
            $decoded = $this->extract_json($response);
            if (array_key_exists("errorCode", $decoded)) {
                $this->log_debug("Unable to use previous session key");
            } else {
                $this->log_debug("Reusing previous session key, saving some time");
                $no_session_key = false;
                $this->session_key = $servicesCredentials['bullhorn']['BhRestToken'];
            }
        }
        if ($no_session_key) {
            if (empty($servicesCredentials['bullhorn']['lastRefreshToken'])) {
                $this->log_debug("Authorize");
                $this->authorize($bullhornService, $httpClient, $servicesCredentials);
            } else {
                $this->log_debug("Get access via refresh");
                $this->load_refresh($bullhornService, $httpClient, $servicesCredentials);
            }
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
		if (!$decoded || array_key_exists("error", $decoded)) {
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
		return json_decode(trim(substr($string, strpos($string, '{'))), true); //}
	}

	private function get_login($ref, $token, $bullhornService, $httpClient, $servicesCredentials) {
		$this->log_debug("At login function with refresh $ref and token $token");
		$servicesCredentials['bullhorn']['lastRefreshToken'] = $ref;
		$login = $bullhornService->getLoginUri($token);
		$response2 = $httpClient->retrieveResponse($login, '', []);
		$decoded2 = $this->extract_json($response2);
		$this->log_debug("Login json decoded:");
		$this->var_debug($decoded2);
		$this->session_key = $decoded2["BhRestToken"];
        $servicesCredentials['bullhorn']['BhRestToken'] = $decoded2["BhRestToken"];
        $servicesCredentials['bullhorn']['base_url'] = $decoded2["restUrl"];
		\Storage::put('credentials.json', json_encode($servicesCredentials));
		$this->base_url = $decoded2["restUrl"];
		$this->log_debug("Successfully logged in to Bullhorn");
	}

	private function authorize($bullhornService, $httpClient, $servicesCredentials) {
		$uri2 = $bullhornService->getAuthorizationUri();
		$this->log_debug("Attempting an authorization");
		$this->var_debug($uri2);
		$authResponse = $httpClient->retrieveResponse($uri2, '', [], 'GET');
		$html_start = strpos($authResponse, '<!DOCTYPE html>');
		$headers = substr($authResponse, 0, $html_start);
		$this->log_debug($headers);
		if (preg_match("|Location: (https?://\S+)|", $headers, $m)) {
			$this->log_debug("Location: ".$m[1]);
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

	public function findCorporateUserByName($name) {
		$bullhornService = $this->service;
		$fieldList = "id,name,username";
		$find_uri = $bullhornService->getCorpUserByNameUri($this->base_url, $this->session_key, $name, $fieldList);
		$this->log_debug("Looking for Corporate user ".$name);
		//$this->var_debug($find_uri);
		$client = $this->httpClient;
		$response = $client->retrieveResponse($find_uri, '', [], 'GET');

		$decoded_user = $this->extract_json($response);
		$cuser = new \Stratum\Model\CorporateUser();
		if (array_key_exists('data', $decoded_user)) {
			foreach ($decoded_user['data'] as $u) {
				$cuser->populateFromData($u);
			}
		} else {
			$this->log_debug("Error Response from Bullhorn:");
			$this->var_debug($decoded_candidates);
		}
		return $cuser;
	}

    public function findCorporateUser($user) {
		//use REST API to look up user
		//based on attributes in $user
		//return result of query

		$bullhornService = $this->service;

		$fieldList = $user->getBullhornFieldList();
		$this->log_debug("Finding CorporateUser ".$user->get("id"));
		$this->log_debug($fieldList);
		$find_uri = $bullhornService->getFindEntityUri("CorporateUser", $this->base_url, $this->session_key, $user->get("id"), $fieldList);
		$this->log_debug("Looking for user ID ".$user->get("id"));
		//$this->var_debug($find_uri);
		$client = $this->httpClient;
		$response = $client->retrieveResponse($find_uri, '', [], 'GET');

		$decoded_user = $this->extract_json($response);

		//all fields from Bullhorn saved in $user
		if (array_key_exists('data', $decoded_user)) {
			$user->populateFromData($decoded_user['data']);
			//$user->dump();
		} else {
			$this->log_debug("Error Response from Bullhorn:");
			$this->var_debug($decoded_user);
		}
		return $user;
	}

	public function findAssocCandidatesIndexed($user) {
		$bullhornService = $this->service;
		$cands = [];

		$id = $user->get("id");

		$find_uri = $bullhornService->getAssocCandidatesUri($this->base_url, $this->session_key, $user->get("id"), "firstName,lastName,id,preferredContact", null);
		$this->log_debug("Looking for Candidates associated with Corporate user ID ".$user->get("id"));
		//$this->var_debug($find_uri);
		$client = $this->httpClient;
		$response = $client->retrieveResponse($find_uri, '', [], 'GET');

		$decoded_candidates = $this->extract_json($response);

		//all fields from Bullhorn saved in $user

		if (array_key_exists('data', $decoded_candidates)) {
			foreach ($decoded_candidates['data'] as $candidate) {
				$cand = new \Stratum\Model\Candidate();
				$cand->populateFromData($candidate);
				$status = $cand->get("preferredContact");
				$cid = $cand->get("id");
				$this->log_debug("Candidate ".$cid." has status ".$status);
				$this->log_debug("Candidate ".$cid." has name ".$cand->getName());
				if ($status=="Phone") {
					//default from Bullhorn, apparently
					$cand->set("preferredContact", "No");
					$this->update_candidate($cand);
					$this->log_debug("Updating status to No");
				}
				$cands[$status][] = $cand;
			}
		} else {
			$this->log_debug("Error Response from Bullhorn:");
			$this->var_debug($decoded_candidates);
		}
		//$this->var_debug($cands);
		return $cands;
	}

	public function findAssocCandidatesWithNo($user) {
		return $this->findAssocCandidates($user, 'No');
	}

	public function findAssocCandidatesWithRFS($user) {
		return $this->findAssocCandidates($user, 'Reg Form Sent');

	}

	public function findAssocCandidatesWithFC($user) {
		return $this->findAssocCandidates($user, 'Form Completed');

	}

	public function findAssocCandidatesWithIC($user) {
		return $this->findAssocCandidates($user, 'Interview Done');
	}

	public function findAssocCandidates($user, $constraint) {
		//use REST API to look up user
		//based on attributes in $user
		//return result of query

		$bullhornService = $this->service;
		$cands = [];

		$id = $user->get("id");

		$find_uri = $bullhornService->getAssocCandidatesUri($this->base_url, $this->session_key, $user->get("id"), "firstName,lastName,id", $constraint);
		$this->log_debug("Looking for Candidates associated with Corporate user ID ".$user->get("id")." and constraint ".$constraint);
		//$this->var_debug($find_uri);
		$client = $this->httpClient;
		$response = $client->retrieveResponse($find_uri, '', [], 'GET');

		$decoded_candidates = $this->extract_json($response);

		//all fields from Bullhorn saved in $user

		if (array_key_exists('data', $decoded_candidates)) {
			foreach ($decoded_candidates['data'] as $candidate) {
				$cand = new \Stratum\Model\Candidate();
				$cand->populateFromData($candidate);
				$cands[] = $cand;
			}
		} else {
			$this->log_debug("Error Response from Bullhorn:");
			$this->var_debug($decoded_candidates);
		}
		return $cands;
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
            $this->load_skills($candidate);
			$this->load_categories($candidate);
			$this->load_specialties($candidate);
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
		$this->log_debug($lkup_decoded);
		if (isset($lkup_decoded['data'][0]['value'])) {
			return $lkup_decoded['data'][0]['value'];
		} else {
			return '';
		}
	}

	public function submit_candidate($candidate) {
		//use REST API to carefully submit all parts of candidate record
		//to Bullhorn - in order so that one-to-many relationships, etc.
		//all work

		$decoded = [];


		//look up country ids from country names in Address fields
		$country_ID = 0;
		$second_ID  = 0;
		$object = false;
		$addr_country = $candidate->get('address(countryID)');
		if (!$addr_country) {
			//look up Address object
			$addr = $candidate->get("address");
			if ($addr) {
				$addr_country = $addr->get("countryID");
				$object = true;
			}
		}
		if ($addr_country) {
			$country_ID = $this->lookup_country($addr_country);
			if ($object) {
				$addr = $candidate->get("address");
				$addr->set("countryID", $country_ID);
				$candidate->set("address", $addr);
			} else {
				$candidate->set('address(countryID)', $country_ID);
			}
		}
		$second_country = $candidate->get('secondaryAddress(countryID)');
		if (!$second_country) {
			//look up Address object
			$addr2 = $candidate->get("secondaryAddress");
			if ($addr2) {
				$second_country = $addr2->get("countryID");
			}
		}
		if ($second_country) {
			if ($second_country == $addr_country) {
				//skip unnecessary duplicate lookup
				$second_ID = $country_ID;
			} else {
				$second_ID = $this->lookup_country($second_country);
			}
			if ($object) {
				$addr2 = $candidate->get("secondaryAddress");
				$addr2->set("countryID", $second_ID);
				$candidate->set("secondaryAddress", $addr2);
			} else {
				$candidate->set('secondaryAddress(countryID)', $second_ID);
			}
		}

		//first, does the candidate have an id?
		$id = $candidate->get("id");
		if ($id) {
			//that means we can update (POST)
			$decoded = $this->update_candidate($candidate);
		} else {
			//no ID means we need to create the candidate (PUT)
			$decoded = $this->create_candidate($candidate);
		}

		return $decoded;
	}

	public function create_candidate($candidate) {
		$cand_data = $candidate->marshalToJSON();
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
		return $decoded;
	}

	public function update_candidate($candidate) {
		$id = $candidate->get("id");
		$cand_data = $candidate->marshalToJSON();
		$post_url = $this->base_url."entity/Candidate/".$id;
		$post_uri = $this->service->getRestUri($post_url, $this->session_key);
		$result = $this->httpClient->retrieveResponse($post_uri, $cand_data, [], 'POST');
		$decoded = $this->extract_json($result);
		$this->log_debug("Update $id has response: ");
		$this->var_debug($decoded);
		if (!array_key_exists('errorMessage', $decoded)) {
			//success
			$this->log_debug("Candidate $id Updated");
		} else {
			$this->log_debug("Candidate $id update failed with problem ".$decoded['errorMessage']);
		}
		return $decoded;
	}

	public function find_candidate_references($candidate) {
		//query/CandidateReference?fields=id%2CreferenceFirstName%2CreferenceLastName%2Ccandidate&where=candidate.id=10809&BhRestToken=7b7be6ef-3ff6-495f-a736-08401587393c
		$id = $candidate->get("id");
		$query_ref_url = $this->base_url."query/CandidateReference";
		$fieldList = 'id,referenceFirstName,referenceLastName,companyName,referenceTitle,referencePhone,referenceEmail,customTextBlock1,isDeleted,candidate';
		$query_ref_uri = $this->service->getRestUri($query_ref_url, $this->session_key, ['fields'=>$fieldList,
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
		//$this->var_debug($body);
		$subm_ref_url = $this->base_url."entity/CandidateReference";
		$subm_ref_uri = $this->service->getRestUri($subm_ref_url, $this->session_key);
		$subm_ref = $this->httpClient->retrieveResponse($subm_ref_uri, json_encode($body), [], 'PUT');
		$subm_ref_decoded = $this->extract_json($subm_ref);
		$this->log_debug("Submitted candidate reference: ");
		$this->var_debug($subm_ref_decoded);
		return $subm_ref_decoded['changedEntityId'];
	}

	public function find_skill($skill_name) {
        $skill_json = \Storage::get("Skills.json");
        $skill_list = json_decode($skill_json, true)['data'];
        $skill = new \Stratum\Model\Skill();
        foreach ($skill_list as $valLabel) {
            if ($valLabel['label'] == $skill_name) {
                $skill->set("id", $valLabel['value']);
                $skill->set("name", $valLabel['label']);
            }
        }
		return $skill;
	}

	public function find_category($skill_name) {
		$skill_json = \Storage::get("Categories.json");
		$skill_list = json_decode($skill_json, true)['data'];
		$skill = new \Stratum\Model\Skill();
		foreach ($skill_list as $valLabel) {
			if ($valLabel['label'] == $skill_name) {
				$skill->set("id", $valLabel['value']);
				$skill->set("name", $valLabel['label']);
			}
		}
		return $skill;
	}

	public function find_specialty($skill_name) {
		$skill_name = preg_replace("/–/", "-", $skill_name);
		$skill_json = \Storage::get("Specialties.json");
		$skill_list = json_decode($skill_json, true)['data'];
		$skill = new \Stratum\Model\Skill();
		foreach ($skill_list as $valLabel) {
			if ($valLabel['label'] == $skill_name) {
				$skill->set("id", $valLabel['value']);
				$skill->set("name", $valLabel['label']);
			}
		}
		return $skill;
	}

    public function load_skills($candidate) {
        $skill_string = "";
        $skill_json = \Storage::get("Skills.json");
        $full_skill_list = json_decode($skill_json, true)['data'];
        //check primarySkills to see what's there
        $skill_ids = $candidate->get("primarySkills");
        foreach ($skill_ids['data'] as $skill_id) {
            foreach ($full_skill_list as $valLabel) {
                if ($skill_id['id'] == $valLabel['value']) {
                    $skill_string .= $valLabel['label']."\n";
                }
            }
        }
        $candidate->set("skillID", rtrim($skill_string));
    }

	public function load_categories($candidate) {
		$skill_string = "";
		$skill_json = \Storage::get("Categories.json");
		$full_skill_list = json_decode($skill_json, true)['data'];
		//check primarySkills to see what's there
		$skill_ids = $candidate->get("categories");
		foreach ($skill_ids['data'] as $skill_id) {
			foreach ($full_skill_list as $valLabel) {
				if ($skill_id['id'] == $valLabel['value']) {
					$skill_string .= $valLabel['label']."\n";
				}
			}
		}
		$candidate->set("categoryID", rtrim($skill_string));
	}

	public function load_specialties($candidate) {
        $skill_string = "";
        $skill_json = \Storage::get("Specialties.json");
        $full_skill_list = json_decode($skill_json, true)['data'];
        //check primarySkills to see what's there
        $skill_ids = $candidate->get("specialties");
        foreach ($skill_ids['data'] as $skill_id) {
            foreach ($full_skill_list as $valLabel) {
                if ($skill_id['id'] == $valLabel['value']) {
                    $skill_string .= $valLabel['label']."\n";
                }
            }
        }
        $candidate->set("specialtyCategoryID", rtrim($skill_string));
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
					$this->delete_custom_object($the, $id);
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

	public function find_note($candidate) {
		//https://rest22.bullhornstaffing.com/rest-services/987up/entity/Candidate/12956/notes?fields=id,comments,action&BhRestToken=
		$id = $candidate->get("id");
		$query_note_url = $this->base_url."entity/Candidate/$id/notes";
		$query_note_uri = $this->service->getRestUri($query_note_url, $this->session_key, ['fields'=>'id,comments,action']);
		$query_note = $this->httpClient->retrieveResponse($query_note_uri, '', [], 'GET');
		$query_note_decoded = $this->extract_json($query_note);
		$this->var_debug($query_note_decoded);
		return $query_note_decoded['data'];
	}

	public function submit_note($candidate) {
		//https://rest9.bullhornstaffing.com/rest-services/{corpToken}/entity/Note?BhRestToken=...
		$id = $candidate->get("id");
		$subm_note_url = $this->base_url."entity/Note";
		$subm_note_uri = $this->service->getRestUri($subm_note_url, $this->session_key);
		//POST BODY: {"customObject2s":[{"id":3649,"int1":7},{"int1":4}]}
		$note = $candidate->get("Note"); //has "comments"
		$note["personReference"]["id"]=$id;
		$body = json_encode($note);
		//$this->log_debug($body);
		$this->log_debug("Submitting this data as a note");
		$subm_note = $this->httpClient->retrieveResponse($subm_note_uri, $body, [], 'PUT');
		$subm_note_decoded = $this->extract_json($subm_note);
		$this->log_debug("Submitted Note");
		$this->var_debug($subm_note_decoded);
		$noteId = $subm_note_decoded["changedEntityId"];
		return $subm_note_decoded;
	}

    public function submit_skills($candidate) {
        //https://rest.bullhorn.com/rest-services/e999/entity/Candidate/3084/primarySkills/964,684,253
        $id = $candidate->get("id");
		$subm_sk_url = $this->base_url."entity/Candidate/$id/primarySkills/";
        $skills = $candidate->get("skillID");
        $skill_objs = [];
        //may not be any
        if ($skills) {
			$this->log_debug("Skills: $skills");
            $skill_list = preg_split("/\n/", $skills);
            $flag = false;
            foreach ($skill_list as $s) {
                $skill = $this->find_skill($s);
				$sid = $skill->get("id");
				if ($sid) {
                	$subm_sk_url .= $sid.",";
                	$flag = true;
				} else {
					$this->log_debug("Unable to find id for |$s|");
				}
            }
            if ($flag) {
                $subm_sk_url = substr($subm_sk_url, 0, strlen($subm_sk_url)-1); //remove last character
				$this->log_debug($subm_sk_url);
            }

			$subm_sk_uri = $this->service->getRestUri($subm_sk_url, $this->session_key);

			$subm_sk = $this->httpClient->retrieveResponse($subm_sk_uri, '', [], 'PUT');
			$subm_sk_decoded = $this->extract_json($subm_sk);
			$this->log_debug("Submitted primarySkills: ");
			$this->var_debug($subm_sk_decoded);
			return $subm_sk_decoded;
		}
    }

	public function submit_categories($candidate) {
        //https://rest.bullhorn.com/rest-services/e999/entity/Candidate/3084/primarySkills/964,684,253
        $id = $candidate->get("id");
		$subm_sk_url = $this->base_url."entity/Candidate/$id/categories/";
        $skills = $candidate->get("categories");
        $skill_objs = [];
        //may not be any
        if ($skills) {
            $flag = false;
            foreach ($skills as $skill) {
				foreach ($skill as $sid=>$val) {
            		$subm_sk_url .= $val.",";
            		$flag = true;
				}
            }
            if ($flag) {
                $subm_sk_url = substr($subm_sk_url, 0, strlen($subm_sk_url)-1); //remove last semi-colon
				$this->log_debug($subm_sk_url);
            }
			$subm_sk_uri = $this->service->getRestUri($subm_sk_url, $this->session_key);

			$subm_sk = $this->httpClient->retrieveResponse($subm_sk_uri, '', [], 'PUT');
			$subm_sk_decoded = $this->extract_json($subm_sk);
			$this->log_debug("Submitted categories: ");
			$this->var_debug($subm_sk_decoded);
			return $subm_sk_decoded;
		}
    }

	public function submit_specialties($candidate) {
		//https://rest.bullhorn.com/rest-services/e999/entity/Candidate/3084/primarySkills/964,684,253
		$id = $candidate->get("id");
		$subm_sk_url = $this->base_url."entity/Candidate/$id/specialties/";
		$skills = $candidate->get("specialties");
		$skill_objs = [];
		//may not be any
		if ($skills) {
            $flag = false;
            foreach ($skills as $skill) {
				foreach ($skill as $sid=>$val) {
            		$subm_sk_url .= $val.",";
            		$flag = true;
				}
            }
			if ($flag) {
				$subm_sk_url = substr($subm_sk_url, 0, strlen($subm_sk_url)-1); //remove last comma
				$this->log_debug($subm_sk_url);
			}

			$subm_sk_uri = $this->service->getRestUri($subm_sk_url, $this->session_key);

			$subm_sk = $this->httpClient->retrieveResponse($subm_sk_uri, '', [], 'PUT');
			$subm_sk_decoded = $this->extract_json($subm_sk);
			$this->log_debug("Submitted Specialties: ");
			$this->var_debug($subm_sk_decoded);
			return $subm_sk_decoded;
		}
	}

	public function submit_files($candidate) {
		//PUT https://rest.bullhornstaffing.com/rest-services/{corpToken}/file/Candidate/$id/raw?externalID=Portfolio&fileType=SAMPLE

		/*
		We use a PUT command to attach a file to a candidate (not just resumes) but use the base64 option instead of the raw file.
		The uri looks like this -> /file/Candidate/{candidateId}. The json included with the body has the following fields:

		fileContent = Base64 string representing the file content
		externalID = portfolio
		filename = file name
		fileType = SAMPLE
		description = file description
		type = type of file (e.g. resume, writing sample, data sheet, etc.)
		contentType = application/msword, application/pdf, etc.

		This option should work fine for attaching your rest results.
		*/

		$filelist = $candidate->get("files");
		$this->log_debug("Going to try to upload files $filelist");

		$files = explode(", ", $filelist);
		$filename = '';
		foreach ($files as $url) {
			$this->log_debug("Going to try to upload file $url");
			$ch = curl_init($url);

			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this,'readHeader'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
			$response = curl_exec($ch);
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$body = substr($response, $header_size);
			curl_close($ch);
			$this->var_debug($this->responseHeaders[$url]);
			if ($this->responseHeaders[$url]) { //may be null if link expired
				$filename = '';
				foreach ($this->responseHeaders[$url] as $header_item) {
					if (preg_match('/filename="(.*?)";/', $header_item, $matches)) {
						$filename = $matches[1];
					}
				}
				$subm_file_decoded = $this->submit_file_as_string($candidate, $filename, $body);
				$this->log_debug("Submitted File $url: ");
				$this->var_debug($subm_file_decoded);
			}
		}
	}

	public function submit_file_as_string($candidate, $filename, $body, $type='To Be Checked') {
		$this->log_debug($filename);
		$file_base64 = base64_encode($body);

		$id = $candidate->get("id");
		$subm_file_url = $this->base_url."file/Candidate/$id";
		$this->log_debug($subm_file_url);
		$size = strlen($body);
		if ($size < 10000) {
			$this->log_debug($body);
			$this->log_debug($file_base64);
		}
		$subm_file_uri = $this->service->getRestUri($subm_file_url, $this->session_key);

		$subm_file = $this->httpClient->retrieveResponse($subm_file_uri,
			json_encode(['fileContent'=>$file_base64,
				'externalID'=>'Portfolio',
				'name'=>$filename,
				'fileType'=>'SAMPLE',
				'description'=>'associated file',
				'type'=>$type
				]),
			[],
			'PUT');
		$subm_file_decoded = $this->extract_json($subm_file);
		return $subm_file_decoded;
	}

	function readHeader($ch, $header) {
        $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $this->responseHeaders[$url][] = $header;
		return strlen($header);
	}


	public function confirm($candidate) {
		//should look up candidate record from Bullhorn
		//and compare data to make sure updates were recorded
		$this->log_debug("At Confirm");
		$there = $this->find($candidate);
		return $same = $candidate->compare($there);
	}

}
