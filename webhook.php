<?php

/*
function isValidJSON($str) {
	json_decode($str);
	return json_last_error() == JSON_ERROR_NONE;
}

$json_params = file_get_contents("php://input");

if (strlen($json_params) > 0 && is isValidJSON($json_params)) {
	$post = json_decode($json_params);
} else {
	exit();
*/


/**
 * JSON data is POSTed directly, not as a parameter. Retrieve it and decode it.
 */
$_POST = json_decode(file_get_contents('php://input'), true);


/**
 * If there was an error parsing the JSON, we should probably bail here.
 */
if (json_last_error() !== JSON_ERROR_NONE)
	leave();


/**
 * A simple check to see if the JSON data is structured correctly.
 */
if (!isset($_POST['result']) || empty($_POST['result']))
	leave();


/**
 * Get the result object from our JSON. It contains the information we need.
 */
$result = $_POST['result'];


/**
 * Bail out if an action was requested that isn't supported by this webhook.
 */
if ($result['action'] !== 'ChuckJokes')
	leave();


/**
 * Handle the ChuckJokes action
 */

// Web service URL
$url = 'https://api.icndb.com/jokes/random?escape=javascript';

// Set up CURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute the POST request
$data = curl_exec($ch);

// Close the connection
curl_close($ch);

// Parse the response
$response = json_decode($data);

$text = $response->value->joke;
$speech = $text;
$displayText = $text;


/**
 * Format a webhook response object to be returned by the webhook.
 */
$webhook = new stdClass();
$webhook->speech = $speech;
$webhook->displayText = $displayText;
//$webhook->data = new stdClass();
//$webhook->data->contextOut = Array(
//		new stdClass()
//);
$webhook->source = 'apiai-chuck-norris-jokes';


/**
 * Send the response.
 */
header('Content-type: application/json;charset=utf-8');
echo json_encode($webhook);

leave();

function leave() {
	exit();
}

//EOF