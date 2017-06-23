<?php

// Load dependencies specified in composer.json
//require('../vendor/autoload.php');

/**
 * Load database connection information from the environment
 */
extract(parse_url(getenv('DATABASE_URL')), 'db');
$db_path = ltrim($db_path, '/');
//$db_path = substr($db_path, 1);

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
switch ($result['action']) {

	case 'ChuckJokes':
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
		break;

	// case 'RedditJokes':
	// 	/**
	// 	 * Handle the RedditJokes action
	// 	 */

	// 	// Try to do some basic caching.
	// 	session_start();

	// 	if (!isset($_SESSION['dataCache'])) {

	// 		// Load the json data set
	// 		//$file = 'reddit_jokes.json';
	// 		$file = 'reddit_jokes_test.json';
	// 		if (!is_file($file) || !is_readable($file)) {
	// 			leave();
	// 		}

	// 		$json = file_get_contents($file);

	// 		// Store the decoded json
	// 		$_SESSION['dataCache'] = json_decode($json, true);

	// 	}

	// 	// Load into an array
	// 	$array = $_SESSION['dataCache'];

	// 	// Grab a random entry
	// 	$joke = $array[rand(0, count($array) - 1)];

	// 	$text = $joke['title'] . "\n\n" . $joke['body'];
	// 	$speech = $text;
	// 	$displayText = $text;
	// 	break;

	case 'RedditJokes':
		/**
		 * Handle the RedditJokes action
		 */

		// Create a DB connection string
		$conn = "user={$db_user} password={$db_pass} host={$db_host} port={$db_port} dbname={$db_name} sslmode=require";

		// Establish the connection
		$db = pg_connect($conn);

		// // Get a row count
		// $num_rows = pg_query($db, "SELECT COUNT(*) FROM {$db_table}");

		// // Generate a random row number
		// $rand = rand(1, count($num_rows));

		// // Grab the joke from the DB
		// $result = pg_query($db, "SELECT title, body FROM {$db_table} WHERE id='{$rand}' LIMIT 1");

		// Grab a random row from the DB
		$result = pg_query($db, "SELECT title, body FROM {$db_table} OFFSET floor(random()*(SELECT COUNT(*) FROM {$db_table})) LIMIT 1;");

		if (!pg_num_rows($result)) {
			// no row was returned.
			pg_close();
			leave();
		}

		while ($row = pg_fetch_row($result)) {
			$text = $row['title'] . "\n\n" . $row['body'];
			$speech = $text;
			$displayText = $text;
		}

		pg_close();

		break;

	default:
		exit();
}



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

// Log output
error_log($speech);


/**
 * Send the response.
 */
header('Content-type: application/json;charset=utf-8');
echo json_encode($webhook);


function leave() {
	// Send back a response that says "error"
	$speech = "Error!";
	$webhook = new stdClass();
	$webhook->speech = $speech;
	$webhook->displayText = $speech;
	//$webhook->data = new stdClass();
	//$webhook->data->contextOut = Array(
	//		new stdClass()
	//);
	$webhook->source = 'apiai-chuck-norris-jokes';

	// Log output
	error_log($speech);

	/**
	 * Send the response.
	 */
	header('Content-type: application/json;charset=utf-8');
	echo json_encode($webhook);
	exit();
}

//EOF