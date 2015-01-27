<?php

//----------------------------------------------------------------------------------------
// File: yuduapi.php
// 
// This file contains a PHP client for accessing the Yudu REST API. The entry point is the
// function sendYuduRequest, which builds an API request, sends it, and interprets the
// response.
//----------------------------------------------------------------------------------------

//----------------------------------------------------------------------------------------
// Import: HTTPRequest
// 
// We import the HTTPRequest library that is used to contruct HTTP requests, deprecating 
// warnings that are emitted by the library.
//----------------------------------------------------------------------------------------
 error_reporting(0);
require_once "HTTP/Request.php";

//----------------------------------------------------------------------------------------
// Function: sendYuduRequestInternal
//
// This performs the bulk of the API call, by taking the call parameters
// and construting an HTTP request to perform the required actions.
// 
// Returns:
//  - PHP object representing response for GET requests.
//  - URI location in the case of successful POST or PUT.
//  - Null in the case of a successful DELETE request.
//  
// Throws: YuduApiException if any errors occur.
//----------------------------------------------------------------------------------------
function sendYuduRequestInternal($method, $url, $queryVariables, $postData, $yuduConfig) {
    // Add timestamp to the query variables
    $queryVariables['timestamp'] = time();

    // Add the request signature to the query variables
    $queryVariables['Signature'] = getYuduRequestSignature($method, $url, $queryVariables, $postData, $yuduConfig);

    // Create a new URL for the request, including the new query variables
    $rewritten_url = getYuduRequestUrl($url, $queryVariables, $yuduConfig);
    
    // Create a request object for the required URL
    $request = getYuduRequest($method, $rewritten_url, $queryVariables, $postData, $yuduConfig);

    // Send the API request
    $response = $request->sendRequest();
    
    // Interpret the response
    return interpretYuduResponse($request, $response, $yuduConfig);
}

//----------------------------------------------------------------------------------------
// Function: getYuduRequestSignature
//
// This function calculates the required signature for the API call, returning it as a
// string.
//----------------------------------------------------------------------------------------
function getYuduRequestSignature($method, $url, $queryVariableAssignments, $postData, $yuduConfig)
{
    // Sort the query variables as required for their signature
    ksort($queryVariableAssignments);

    // Build the query string components (without URL encoding)
    foreach ($queryVariableAssignments as $parameter => $value) {
        $queryStringComponents[] = $parameter . '=' . $value;
    }

    // Put our & symbol at the beginning of each of our request variables and put it in a string
    $queryStringToSign = implode('&', $queryStringComponents);

    // The string to be signed consists of the request parameters plus the full request body
    $urlComponents = parse_url($url);
    $stringToSign = $method . $urlComponents["path"] . "?" . $queryStringToSign . $postData;

    // Create the request signature using the HMAC hash.
    writeYuduDebugInfo("String to sign:", $stringToSign, $yuduConfig);
    $signature = base64_encode(hash_hmac('sha256', $stringToSign, $yuduConfig['sharedSecret'], true));
    writeYuduDebugInfo("Signature:", $signature, $yuduConfig);
    
    return $signature;
}

//----------------------------------------------------------------------------------------
// Function: getYuduRequestUrl
//
// This function builds the URL required for sending an API call.
//----------------------------------------------------------------------------------------
function getYuduRequestUrl($url, $queryVariables, $yuduConfig)
{
    // Strip the original URL into its component parts
    $urlComponents = parse_url($url);
    $hostAndPort = $urlComponents['host'];
    if (isset($urlComponents['port']))
    {
        $hostAndPort = $hostAndPort . ":" . $urlComponents['port'];
    }
    // Form the URL using the scheme, host, path and query variables
    $url = $urlComponents['scheme'] . "://" . $hostAndPort . $urlComponents["path"] . "?" . http_build_query($queryVariables);
    writeYuduDebugInfo("Request URL:", $url, $yuduConfig);
    
    return $url;
}

//----------------------------------------------------------------------------------------
// Function: getYuduRequest
//----------------------------------------------------------------------------------------
function getYuduRequest($method, $url, $queryVariables, $postData, $yuduConfig)
{
    // Create new HTTP request to send to the API.
    $request = new HTTP_Request($url);
    
    // Add our public authentication key as an HTTP header.
    $request->addHeader("Authentication", $yuduConfig['key']);

    // Set the request method, body and content type header.
    switch($method) {
        case "GET":
            $request->setMethod(HTTP_REQUEST_METHOD_GET);
            break;
        case "POST":
            $request->setMethod(HTTP_REQUEST_METHOD_POST);
            $request->setBody($postData);
            $request->addHeader("Content-Type", "application/vnd.yudu+xml");
            break;
        case "PUT":
            $request->setMethod(HTTP_REQUEST_METHOD_PUT);
            $request->setBody($postData);
            $request->addHeader("Content-Type", "application/vnd.yudu+xml");
            break;
        case "DELETE":
            $request->setMethod(HTTP_REQUEST_METHOD_DELETE);
            $request->setBody($postData);
            $request->addHeader("Content-Type", "application/vnd.yudu+xml");
            break;
    }
    
    return $request;
}

//----------------------------------------------------------------------------------------
// Function: interpretYuduResponse
//----------------------------------------------------------------------------------------
function interpretYuduResponse($request, $response, $yuduConfig)
{

    if (PEAR::isError($response))
    {
        // The HTTP client had a problem communicating with the API.
        writeYuduDebugInfo("Client error:", $response->getMessage(), $yuduConfig);
        throw new YuduApiException($response->getMessage());
    }
    else
    {
        writeYuduDebugInfo("Response code:", $request->getResponseCode(), $yuduConfig);
        writeYuduDebugInfo("Response body:", $request->getResponseBody(), $yuduConfig);

        if ($request->getResponseCode() == 200)
        {
            // 'OK' status code indicates XML body content successfully received.
            // Convert this into a PHP object.
            return simplexml_load_string($request->getResponseBody());
        }
        else if ($request->getResponseCode() == 201) 
        {
            // 'Created' status code incidates an entity has been created at the URI
            // specificed by the location header.
            return $request->getResponseHeader("location");
        }
        else if ($request->getResponseCode() == 204) 
        {
            // 'No Content' status code is returned in the case of entity deletions.
            return;
        }
        else
        {
            // Unknown response code is converted to an API exception.
            $error = simplexml_load_string($request->getResponseBody());
            writeYuduDebugInfo("Error code:", $error->code, $yuduConfig);
            writeYuduDebugInfo("Error detail:", $error->detail, $yuduConfig);
            throw new YuduApiException($error->detail, $error->code);
        }
    }
}

//----------------------------------------------------------------------------------------
// Class: YuduApiException
//
// This class wraps up any error messages received in responses from the Yudu API.
// Errors consist of a message and a message type.
//----------------------------------------------------------------------------------------
class YuduApiException extends Exception {
    private $errorType;
    
    public function __construct($message = "", $errorType = "")
    {
        parent::__construct($message);
        $this->errorType = $errorType;
    }

    public function getErrorType()
    {
        return $this->errorType;
    }
    
    public function __toString()
    {
        return $this->getErrorType() . " : '{$this->message}' in {$this->file}({$this->line})\n"
                                . "{$this->getTraceAsString()}";
    }
}

//----------------------------------------------------------------------------------------
// Function: sendYuduRequest
// 
// Wraps up a call to sendYuduRequestInternal so that a table of debugging information 
// is not interrupted by any exceptions raised.
//----------------------------------------------------------------------------------------
function sendYuduRequest($method, $url, $queryVariables, $postData, $yuduConfig) {
    try
    {
        startYuduDebugInfo($yuduConfig);
        writeYuduDebugInfo("Request url:", $url, $yuduConfig);
        writeYuduDebugInfo("API key:", $yuduConfig['key'], $yuduConfig);
        writeYuduDebugInfo("Shared secret:", $yuduConfig['sharedSecret'], $yuduConfig);
        writeYuduDebugInfo("Post data:", $postData, $yuduConfig);
        
        $ret = sendYuduRequestInternal($method, $url, $queryVariables, $postData, $yuduConfig);
    }
    catch (Exception $e)
    {
        endYuduDebugInfo($yuduConfig);
        throw $e;
    }
    endYuduDebugInfo($yuduConfig);
    return $ret;
}

//----------------------------------------------------------------------------------------
// Functions: startYuduDebugInfo, writeYuduDebugInfo, endYuduDebugInfo
// 
// Utility functions for writing out a table of debugging information for requests.
//----------------------------------------------------------------------------------------
function startYuduDebugInfo($yuduConfig)
{
    if($yuduConfig['debug'])
    {
      ?><div class="output_wrapper"><?php
    }
}

function writeYuduDebugInfo($field, $value, $yuduConfig)
{
    if ($yuduConfig['debug'])
    {
      ?><div class="field_label"><?php echo htmlspecialchars($field); ?></div><div class="field_value"><?php echo htmlspecialchars($value); ?></div><?php
    }
}

function endYuduDebugInfo($yuduConfig)
{
    if($yuduConfig['debug'])
    {
      ?></div><?php
    }
}
?>
