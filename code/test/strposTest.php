<?php
$mystring = 'HTTP/1.1 400 Bad Request
Date: Wed, 04 Nov 2015 21:24:45 GMT
Server: Apache-Coyote/1.1
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: POST, GET, HEAD, OPTIONS
Access-Control-Allow-Headers: Origin, Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers
Cache-Control: no-store
Pragma: no-cache
Content-Type: application/json;charset=UTF-8
Content-Length: 102
Via: 1.1 default
Connection: close

{
  "error" : "invalid_grant",
  "error_description" : "Invalid, expired, or revoked refresh token."
}
';
$findme   = '{';
$pos = strpos($mystring, $findme);

// Note our use of ===.  Simply == would not work as expected
// because the position of 'a' was the 0th (first) character.
if ($pos === false) {
    echo "The string '$findme' was not found in the string '$mystring'";
} else {
    echo "The string '$findme' was found in the string '$mystring'";
    echo " and exists at position $pos\n";
    $string2 = substr($mystring, $pos);
    echo "The new substring:\n";
    echo $string2."\n";
}
?>
