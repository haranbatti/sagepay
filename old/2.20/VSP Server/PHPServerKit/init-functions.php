<?
/*************************************************************
	Various functions
*************************************************************/

/*******************************************************
DEV FUNCTIONS -- REMOVE BEFORE RELEASE
*******************************************************/
// Show an object or array in easy-to-read format
function display($output){
	print "<PRE>";
	print_r($output);
	print "</PRE><BR>";
}
/*******************************************************
/*******************************************************


/*************************************************************
	Send a post request with cURL
		$url = URL to send reuqest to
		$data = POST data to send (in URL encoded Key=value pairs)
*************************************************************/
function requestPost($url, $data){

	// Set a one-minute timeout for this script
	set_time_limit(60);

	// Initialise output variable
	$output = array();

	// Open the cURL session
	$curlSession = curl_init();

	// Set the URL
	curl_setopt ($curlSession, CURLOPT_URL, $url);
	// No headers, please
	curl_setopt ($curlSession, CURLOPT_HEADER, 0);
	// It's a POST request
	curl_setopt ($curlSession, CURLOPT_POST, 1);
	// Set the fields for the POST
	curl_setopt ($curlSession, CURLOPT_POSTFIELDS, $data);
	// Return it direct, don't print it out
	curl_setopt($curlSession, CURLOPT_RETURNTRANSFER,1); 
	// This connection will timeout in 30 seconds
	curl_setopt($curlSession, CURLOPT_TIMEOUT,30); 

	//Send the request and store the result in an array
	$response = split(chr(10),curl_exec ($curlSession));

	// Check that a connection was made
	if (curl_error($curlSession)){
		// If it wasn't...
		$output['Status'] = "FAIL";
		$output['StatusDetail'] = curl_error($curlSession);
	}

	// Close the cURL session
	curl_close ($curlSession);

// Tokenise the response
	for ($i=0; $i<count($response); $i++){
		// Find position of first "=" character
		$splitAt = strpos($response[$i], "=");
		// Create an associative (hash) array with key/value pairs ('trim' strips excess whitespace)
		$output[trim(substr($response[$i], 0, $splitAt))] = trim(substr($response[$i], ($splitAt+1)));
	} // END for ($i=0; $i<count($response); $i++)

	// Return the output
	return $output;

} // END function requestPost(){


/*************************************************************
	Format data for sending in POST request
		$data = data as an associative array
*************************************************************/
function formatData($data){

	// Initialise output variable
	$output = "";

	// Step through the fields
	foreach($data as $key => $value){
		// Stick them together as key=value pairs (url encoded)
		$output .= "&" . $key . "=". urlencode($value);
	} // END foreach($data as $key => $value){

	// Kludge to take out the initial &
	$output = substr($output,1);

	// Return the output
	return $output;

} // END function formatData($data){
?>