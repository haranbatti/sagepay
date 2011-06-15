<?

/**************************************************************************************************
	Name: VPS Handle PROTX Response
	System: VPS
	Sub-system: Vendor Components
	Description: Script to initiate a transaction with the VPS
	Version: 1.1
	Date: 17/10/2002
	History:  Version 1.1 - PHP release
	History:  Version 1.0 - First ASP release
**************************************************************************************************/

// *** Include the initialisation files
include ("init-includes.php");

$VendorTxCode = $_POST["VendorTxCode"];

/*
/**************************************************************************************************
	You will need to insert code here to retrieve information about the transaction from your database
	using the information passed in the request object from the VPS.
**************************************************************************************************/

	/*
		Open your database here
		Retrieve information for the transaction you are being notified about
		by accessing the transaction where your unique id equals that passed in Request("VendorTxCode")

		You need to retrieve the VPSTxID and Security Key fields passed to you in reponse to the initial HTTPS post.
		You should have stored these in the WebSaveOrder script.  Use these values below in the message for hashing.
	*/
	
	/**********************************************
	Example code for connecting to a MySQL database
	*/

	// Make the connection
	$db = mysql_connect($myHost, $myUser, $myPass);

	// Select the database
	mysql_select_db($myDB,$db);

	// Set the query (SELECT)
	$sql = "SELECT * from $myTable
		WHERE VendorTxCode='$VendorTxCode'
	";

	// Get the query object
	@$result=mysql_query($sql,$db);
	
	// Get the row
	$row=mysql_fetch_array($result);

	/*********************************************/

// Creat an array of values for checking against security key
$data=array();
$data['VPSTxId'] = $row['VPSTxId'];  								// VPS transaction ID (from database)
$data['VendorTxCode'] = $row['VendorTxCode']; 			// Vendor's transaction code (from database)
$data['Status'] = $row['Status']; 									// Status of order (from database)
$data['TxAuthNo'] = $_POST['TxAuthNo']; 						// Transaction authorisation number (POSTed)
$data['Vendor'] = $Vendor; 													// Vendor name (set in init-protx.php)
$data['AVSCV2'] = $_POST['AVSCV2']; 								// Address verficiation response (POSTed)
$data['SecurityKey'] = $row['SecurityKey'];					// Security key (from database)

// Get the first word of the status -- in case it has appended values (eg. REPEATED)
$baseStatus = array_shift(split(" ",$_POST['Status']));

// Reply according to the value of $Status
switch($baseStatus){

	// If the transaction was authorised ok
	case 'OK':
		/**************************************************************************************************
			Check the MD5 Hash Value sent back in the signature, to confirm the validity of the post
		**************************************************************************************************/

		// Compare the incoming signature to the calculated signature sent with the post
		if (strtolower($_POST['VPSSignature']) == md5(join("", $data))){

			/**************************************************************************************************
				The Hash Value and VPS Signature match, so reply to the VPS with a redirect URL 
				for the completion page.  You will need to add code here to store the Auth Code
				in your database.
			**************************************************************************************************/

				/*
					Update the transaction record referenced by $_POST['VendorTxCode'] in your database
					to reflect that it has been Authorised and store the Auth COde sent back in $_POST['TxAuthNo']

					You may also wish to e-mail the customer here to confirm the order
				*/

				/**********************************************
				Example code for connecting to a MySQL database
				*/

				// Set the query (SELECT)
				$sql = "UPDATE $myTable
					SET 
						TxAuthNo=" . $_POST['TxAuthNo'] . "
					WHERE VendorTxCode='$VendorTxCode'
				";

				// Get the query object
				$result=mysql_query($sql,$db);

				/**********************************************/


			// Construct a response for the PROTX VPS
			$response = 
				"RedirectURL=" . $DefaultCompletionURL . 							// URL to continue to (specified in init-protx.php)
				"?VPSTxID=" . $row['VPSTxId'] .												// Any other fields to be sent to the redirect URL...
				"&VendorTxCode=" . $row['VendorTxCode'] . $eoln .			// ...these will arrive as URL (GET) name=value fields
				"Status=OK" . $eoln . 																// Tell VPS that everything is ok
				"StatusDetail="																				// Just for completeness
			;

			// Send the response back to the VPS, which will then redirect to the URL given above
			echo ($response);

		} else {

			/**************************************************************************************************
				The Hash Value and VPS Signature DO not match, the order may have been tampered with 
				so redirect the user to a page explaining this.  
				You may wish to add code here to flag this in your database
			**************************************************************************************************/

			/*
				Update the transaction record referenced by $_POST['VendorTxCode'] in your database
				to reflect that it has been Tampered with.
			*/

			//Construct a reponse for the PROTX VPS.
			$response = 
				"Status=INVALID" . $eoln .										// Respond with INVALID, because the message may have been tampered with
				"RedirectURL=" . $DefaultTamperURL . $eoln .	// Send a redirect URL to an "Order Tampered with" page (specified in init-protx.php)
				"StatusDetail=MD5 codes did not match"				// Human-readable error message
			;

			// Send the response back to the VPS, which will then redirect to the URL given above
			echo($response);

		} // END if (strtolower($_POST['VPSSignature']) == md5(join("", $data)))

		// End case 'OK'
		break;
		

	// If the transaction was not authorised
	case 'NOTAUTHED':

		/**************************************************************************************************
			The bank has not Authorised this request.  Inform the user of this.
			It is a good idea to add code here to update your database to reflect this.
		***************************************************************************************************/

			/*
				Update the transaction record referenced by $_POST['VendorTxCode'] in your database
				to reflect that it has not been Authorised
			*/

		//Construct a reponse for the PROTX VPS.
		$response = 
			"Status=OK" . $eoln .																	// The status is OK because the message was received correctly despite the lack of authorisation
			"RedirectURL=" . $DefaultNotAuthedURL . 							// Send a redirect URL to an "Order Not Authorised" page (specified in init-protx.php)
			"?VPSTxID=" . $row['VPSTxId'] .												// Any other fields to be sent to the redirect URL...
			"&VendorTxCode=" . $row['VendorTxCode'] . $eoln .			// ...these will arrive as URL (GET) name=value fields
			"StatusDetail="																				// Just for completeness
		;

		// Send the response back to the VPS, which will then redirect to the URL given above
		echo($response);

		// End case 'NOTAUTHED'
		break;

	
	// If the process timed out or the user aborted
	case 'ABORT':

		/**************************************************************************************************
			The process either timed out, or more likely, the user clicked cancel
			It is a good idea to add code here to update your database to reflect this.
		**************************************************************************************************/

			/*
				Update the transaction record referenced by $_POST['VendorTxCode'] in your database
				to reflect that the user aborted the transaction at PROTX
			*/

		//Construct a reponse for the PROTX VPS.
		$response = 
			"Status=OK" . $eoln .																	// The status is OK because the message was received correctly despite the abort
			"RedirectURL=" . $DefaultAbortURL . $eoln .						// Send a redirect URL to an "Order Aborted" page (specified in init-protx.php)
			"StatusDetail="																				// Just for completeness
		;

		// Send the response back to the VPS, which will then redirect to the URL given above
		echo($response);

		// End case 'ABORT'
		break;

	case 'ERROR':

		/**************************************************************************************************
			Something has gone very wrong at the PROXT VPS.  You should never receive this message
			but trap for it anyway and update your database with an error flag on this order.
		**************************************************************************************************/

			/*
				Update the transaction record referenced by $_POST['VendorTxCode'] in your database
				to reflect that an error occurred at the PROTX site
			*/

		//Construct a reponse for the PROTX VPS.
		$response = 
			"Status=ERROR" . $eoln .															// The status ERROR in reponse also
			"RedirectURL=" . $DefaultErrorURL . $eoln .						// Send a redirect URL to an "Error" page (specified in init-protx.php)
			"StatusDetail="																				// Just for completeness
		;

		// Send the response back to the VPS, which will then redirect to the URL given above
		echo($response);

		// End case 'ERROR'
		break;
	
	case 'FAIL':
	default:

		/**************************************************************************************************
			Connection to protx could not be made (timed out) or other problem
		**************************************************************************************************/

			/*
				Update the transaction record referenced by $_POST['VendorTxCode'] in your database
				to reflect that an error occurred
			*/

			// There's no point in sending error notification since it won't go anywhere

		// End case 'FAIL'/default
		break;

} // END switch($Status)

// Close the database connection
mysql_close($db);

?>