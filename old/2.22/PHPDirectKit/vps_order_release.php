<?
/**************************************************************************************************
	Name: VPS Order Release
	System: VPS
	Sub-system: Vendor Components
	Description: Script to handle a refund request
	Version: 1.0
	Date: 18/10/2002
	History:  Version 1.0 - First release
**************************************************************************************************/

// *** Include the initialisation files
include ("init-includes.php");

// Set some variables
$TargetURL = $ReleaseURL;														// Specified in init-includes.php

$VerifyServer = $Verify;														// Specified in init-includes.php

/**************************************************************************************************
	You will need to insert code here to retrieve information about the original transaction from
	your database, and generate a completely unique ID number for this transaction
**************************************************************************************************/

	/*
		Open your database here.
		You would normally pass your unique order id for the original transaction to this
		  script and retrieve the id using $_POST or $_GET.
		Retrieve all the information about the original transaction from the database.
		  (you must obtain the original ID you provided, the VPSTxId, and the Security Key)
		You will also need an amount to refund.  You can perform as many partial refunds as you
		  wish against a single payment, but only up to the total value of that payment.
		Many people only offer full refunds, in which case simply obtain the value of the
		  original purchase from your database, and use that as the refund amount.
		Generate a unique ID for this refund transaction.
	*/

		/*
		Example code for connecting to a MySQL database
		*/

		// Make the connection
		$db = mysql_connect($myHost, $myUser, $myPass);

		// Select the database
		mysql_select_db($myDB,$db);

		// 
		$sql = "SELECT * from $myTable
			WHERE VendorTxCode='" . $_POST["VendorTxCode"] . "'
		";

		// Get the query object
		@$result=mysql_query($sql,$db);

		// Get the row
		$row=mysql_fetch_array($result);

/*******************************************/

/**************************************************************************************************
	Set all the required outgoing properties for the initial HTTPS post to the VPS
**************************************************************************************************/

// Create an array of values to send
$data = array (
		'VPSProtocol' => $ProtocolVersion, 							// Protocol version (specified in init-includes.php)
		'TxType' => 'RELEASE',													// Transaction type 
		'Vendor' => $Vendor,														// Vendor name (specified in init-protx.php)
		'VendorTxCode' => $row['VendorTxCode'],					// Unique refund transaction code (generated by vendor)
		'VPSTxId' => $row['VPSTxId'],										// VPSTxId of order
		'SecurityKey' => $row['SecurityKey'],						// Security Key
		'TxAuthNo' => $row['TxAuthNo']									// Transaction authorisation number
	);

// Format values as url-encoded key=value pairs
$data = formatData($data);

/**************************************************************************************************
	Send the post to the target URL
		if anything goes wrong with the connection process: 
		- ErrorLevel will be non-zero;
		- ErrorMessage will be set to describe the problem;
**************************************************************************************************/

$response = requestPost($TargetURL, $data);

/**************************************************************************************************
	Check the Status and act appropriately
'*************************************************************************************************/

// Get the first word of the status -- in case it has appended values (eg. REPEATED)
$baseStatus = array_shift(split(" ",$response["Status"]));

switch($baseStatus) {

	case 'OK':
		/**************************************************************************************************
			Refund successful, so store the AuthCode in your database here.
		**************************************************************************************************/

			/*
				The refund has been authorised, so update your database to reflect that and
				store the AuthCode and VPSTxId sent by the VPS for this refund.
				These values are returned in $response['TxAuthNo'] and $response['VPSTxId'] respectively
			*/

		// Write a message to the browser informing the admin of success.
		echo ("
			<HTML>
				<BODY>
					Release successful.<BR><BR>
					<BR><BR>To return to the main screen, click <A HREF='./'>here</A>.
				</BODY>
			</HTML>"
		);

		break; // End case 'OK'


	case 'INVALID';
		/**************************************************************************************************
			Status was not OK, so whilst communication was successful, something was wrong with the POST
			Display information about the error on screen and update your database with this information
		**************************************************************************************************/

			/*
				The refund has NOT been authorised, so update your database to reflect that and
			*/

		//Write a message to the browser informing the admin of failure
		echo ("
			<HTML>
				<BODY>
					The release request was invalid. <BR>
					Reason: " . $response['StatusDetail'] . "
					<BR><BR>To return to the main screen, click <A HREF='./'>here</A>.
				</BODY>
			</HTML>"
		);

		break; // End case 'INVALID'


	case 'MALFORMED';
		/**************************************************************************************************
			The refund post sent by your site contained incorrect or unrecognisable data.
			You may wish to update your database to reflect this.
		**************************************************************************************************/

			/*
				The refund request was malformed.  Update your database to reflect this, or you
				may wish to try to resubmit the request here.
			*/

		//Write a message to the browser informing the admin of failure
		echo ("
			<HTML>
				<BODY>
					The release request sent to PROTX was Malformed.<BR><BR>
					Reason: " . $response['StatusDetail'] . "
					<BR><BR>To return to the main screen, click <A HREF='./'>here</A>.
				</BODY>
			</HTML>"
		);

		break; // End case 'MALFORMED'

	case 'ABORT':
	case 'ERROR':
	default: // If it's not any of the above
		/**************************************************************************************************
			The VPS returns either ABORT or ERROR if the post was okay but process was interrupted or
			failed.  You may wish to update your database to reflect this.
		**************************************************************************************************/

			/*
				The ABORT or ERROR message only occurs when something goes wrong at the VPS.
				You may wish to mail PROTX support here to inform them, or flag up something for
				an operator at your site.
			*/

		//Write a message to the browser informing the admin of failure
		echo ("
			<HTML>
				<BODY>
					The PROTX VPS returned an error or undef message.  The release was unsuccessful.
					Reason: " . $response['StatusDetail'] . "
					<BR><BR>To return to the main screen, click <A HREF='./'>here</A>.
				</BODY>
			</HTML>"
		);

		break; // End default
	} // END switch($baseStatus)
			
// Close the database	connection
mysql_close($db);

?>