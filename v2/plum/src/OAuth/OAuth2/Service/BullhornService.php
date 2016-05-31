<?php

namespace Stratum\OAuth\OAuth2\Service;

use OAuth\OAuth2\Service\AbstractService;
use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Uri\UriInterface;
use \Dotenv\Dotenv;
use \Log;

/**
 * Bullhorn service, based on Dropbox service
 *
 * Dropbox @author Flávio Heleno <flaviohbatista@gmail.com>
 * @link https://www.dropbox.com/developers/core/docs
 *
 * @author David Block dave@northcreek.ca
 * @link http://developer.bullhorn.com/
 */

class BullhornService extends AbstractService
{
    public function __construct(
        CredentialsInterface $credentials,
        ClientInterface $httpClient,
        TokenStorageInterface $storage,
        $scopes = array(),
        UriInterface $baseApiUri = null
    ) {
        parent::__construct($credentials, $httpClient, $storage, $scopes, $baseApiUri);

        if (null === $baseApiUri) {
            $this->baseApiUri = new Uri('http://rest.bullhornstaffing.com/rest-services/');
        }
    }

    public function getAccessTokenWithCodeUri($code, array $additionalParameters = array())
    {
        $parameters = array_merge(
            $additionalParameters,
            array(
				'code'			=> $code,
                'client_id'     => $this->credentials->getConsumerId(),
                'client_secret' => $this->credentials->getConsumerSecret(),
                'redirect_uri'	=> 'http://www.bullhorn.com',
                'grant_type' 	=> 'authorization_code'
            )
        );

        // Build the url
        $url = clone $this->getAccessTokenEndpoint();
        foreach ($parameters as $key => $val) {
            $url->addToQuery($key, $val);
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenUri($refresh_token, array $additionalParameters = array())
    {
        $parameters = array_merge(
            $additionalParameters,
            array(
                'client_id'     => $this->credentials->getConsumerId(),
                'client_secret' => $this->credentials->getConsumerSecret(),
                'refresh_token' => $refresh_token,
                'grant_type' 	  => 'refresh_token',
            )
        );

        $parameters['scope'] = implode(' ', $this->scopes);

        // Build the url
        $url = clone $this->getAccessTokenEndpoint();
        foreach ($parameters as $key => $val) {
            $url->addToQuery($key, $val);
        }

        return $url;
    }

	//curl -v "https://auth.bullhornstaffing.com/oauth/authorize?client_id={client_id}&response_type=code
	//&redirect_uri={optional redirect_uri}&state={recommended state value}&username={username}&password={password}&action=Login"


    /**
     * {@inheritdoc}
     */
    public function getAuthorizationUri(array $additionalParameters = array())
    {
		$directory = __DIR__;
		\Log::debug( "Currently at ".$directory);
		$newdir = preg_replace("|\/src\/OAuth\/OAuth2\/Service|", "", $directory);
		\Log::debug("Now at ".$newdir);

		$dotEnv = new Dotenv($newdir);
		$dotEnv->load();
		$username = getenv('BULLHORN_USERNAME');
		$password = getenv('BULLHORN_PASSWORD');

        $parameters = array_merge(
            $additionalParameters,
            array(
                'client_id'     => $this->credentials->getConsumerId(),
                'response_type'	=> 'code',
                'redirect_uri'	=> 'http://www.bullhorn.com',
                'username'		=> $username,
                'password'      => $password,
                'action'		=> 'Login'
            )
        );

        //$parameters['scope'] = implode(' ', $this->scopes);

        // Build the url
        $url = clone $this->getAuthorizationEndpoint();
        foreach ($parameters as $key => $val) {
            $url->addToQuery($key, $val);
        }

        return $url;
    }

    public function getRestUri($url, $bhToken, array $additionalParameters = array())
    {
        $parameters = array_merge(
			$additionalParameters,
            array(
                'BhRestToken'   => $bhToken
            )
        );

        // Build the url
        $uri = new Uri($url);
        foreach ($parameters as $key => $val) {
            $uri->addToQuery($key, $val);
        }

        return $uri;
    }


    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
        return new Uri('https://auth.bullhornstaffing.com/oauth/authorize');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://auth.bullhornstaffing.com/oauth/token');
    }

    public function getLoginEndpoint() {
		return new Uri('https://rest.bullhornstaffing.com/rest-services/login');
	}

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizationMethod()
    {
        return static::AUTHORIZATION_METHOD_QUERY_STRING;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        $data = json_decode($responseBody, true);

        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth2Token();
        $token->setAccessToken($data['access_token']);

        if (isset($data['refresh_token'])) {
            $token->setRefreshToken($data['refresh_token']);
            unset($data['refresh_token']);
        }

        unset($data['access_token']);

        $token->setExtraParams($data);

        return $token;
    }

    public function putEntity($token, $entityType, \Stratum\Model\ModelObject $entity) {
		$json = $entity->marshalToJSON();


		$returns['error'] = 'Not Implemented yet';
		return $returns;
	}

	public function putToMany($token, $entityType, $id, $toManyEntityName, array $associated) {
		return $success;
	}

	public function postEntity($token, $entityType, $id, \Stratum\Model\ModelObject $entity) {
		$returns['error'] = 'Not Implemented yet';
		return $returns;
	}

	public function deleteEntity($token, $entityType, $id) {
		return $success;
	}

	public function softDeleteEntity($token, $entityType, $id) {
		return $success;
	}

	public function disassociateToMany($token, $entityName, $id, $toManyEntityName, array $associated) {
		return $success;
	}

    public function getLoginUri($token) {
		$uri = $this->getLoginEndpoint();
		$uri->addToQuery("version", "*"); //latest version
		$uri->addToQuery("access_token", $token);
		return $uri;
	}

    public function getFindEntityUri($entityType, $base_url, $session_key, $id, $fieldList) {
        $uri = new Uri($base_url."entity/".$entityType."/".$id);
		$uri->addToQuery("BhRestToken", $session_key);
		$uri->addToQuery("fields", $fieldList);
		return $uri;
    }

    public function getFindUri($base_url, $session_key, $id, $fieldList) {
      Log::debug("Session Key: ".$session_key);
		return $this->getFindEntityUri("Candidate", $base_url, $session_key, $id, $fieldList);
	}

	public function getSearchUri($base_url, $session_key, $query, $count=1) {
		//https://rest.bullhorn.com/rest-services/e999/find?query=smith&countPerEntity=3
		$uri = new Uri($base_url."find");
		$uri->addToQuery("BhRestToken", $session_key);
		$uri->addToQuery("query", $query);
		$uri->addToQuery("countPerEntity", $count);
		return $uri;
	}

    public function getAssocCandidatesUri($base_url, $session_key, $owner_id, $fieldList, $constraint) {
        //https://rest22.bullhornstaffing.com/rest-services/987up/search/Candidate?query=owner.id:10237&fields=firstName,lastName,id,owner&useV2=true
        $uri = new Uri($base_url."search/Candidate");
        $uri->addToQuery("BhRestToken", $session_key);
        if ($constraint) {
		    $uri->addToQuery("query", "owner.id:".$owner_id.' AND isDeleted:false AND status:"New Candidate - To Process" AND preferredContact:'.$constraint);
        } else {
            $uri->addToQuery("query", "owner.id:".$owner_id.' AND isDeleted:false AND status:"New Candidate - To Process"');
        }
        $uri->addToQuery("fields", $fieldList);
        $uri->addToQuery("useV2", "true");
        $uri->addToQuery("count", 500);
        return $uri;
    }

    public function getCorpUserByNameUri($base_url, $session_key, $name, $fieldList) {
        //https://rest22.bullhornstaffing.com/rest-services/987up/query/CorporateUser?where=name='Stratum API'&fields=id,name,username,enabled&count=500
		$uri = new Uri($base_url."query/CorporateUser");
        $uri->addToQuery("BhRestToken", $session_key);
		$uri->addToQuery("where", "name='".$name."'");
        $uri->addToQuery("fields", $fieldList);
        return $uri;
    }
}
