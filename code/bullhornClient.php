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

use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Client\CurlClient;
use Stratum\OAuth\OAuth2\Service\Bullhorn;

/**
 * Bootstrap the example
 */
require_once __DIR__ . '/bootstrap.php';

// Session storage
$storage = new Session();

$cred_string = file_get_contents("credentials.json");
$servicesCredentials = json_decode($cred_string, true);
echo $cred_string,"<br>";
var_dump ($servicesCredentials);
echo "<br>";

//if we have a refresh-token, we don't need to ask for a new accesstoken.
//if (!empty($servicesCredentials['bullhorn']['refreshToken']) {
		

//}
// Setup the credentials for the requests
$credentials = new Credentials(
    $servicesCredentials['bullhorn']['key'],
    $servicesCredentials['bullhorn']['secret'],
    $currentUri->getAbsoluteUri()
);
$httpClient = new CurlClient();

// Instantiate the Bullhorn service using the credentials, http client and storage mechanism for the token
/** @var $bullhornService Bullhorn */
$bullhornService = new Bullhorn($credentials, $httpClient, $storage, array());

//figure out the correct logic to determine how to get to the actual candidate records
if (!empty($_GET['code'])) {
    // This was a callback request from Bullhorn, get the token
    $token = $bullhornService->requestAccessToken($_GET['code']);

    // ask for the Candidate we got from WorldApp
    $result = json_decode($bullhornService->request('/account/info'), true);

    //this is where the logic comes
    // Show some of the resultant data
    echo 'Your unique Bullhorn user id is: ' . $result['uid'] . ' and your name is ' . $result['display_name'];

} elseif (!empty($_GET['go']) && $_GET['go'] === 'go') {
    $url = $bullhornService->getAuthorizationUri();
    header('Location: ' . $url);
} else {
    $url = $currentUri->getRelativeUri() . '?go=go';
    echo "<a href='$url'>Login with Dropbox!</a>";
}
