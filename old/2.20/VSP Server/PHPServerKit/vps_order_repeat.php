<?
/**************************************************************************************************
	Name: VPS Order Repeat
	System: VPS
	Sub-system: Vendor Components
	Description: Script to handle a repeat request
	Version: 1.0
	Date: 21/10/2002
	History:  Version 1.0 - PHP release
**************************************************************************************************/

// *** Include the initialisation files
include ("init-includes.php");

// Set some variables
$TargetURL = $RepeatURL;														// Specified in init-includes.php
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
		  (you must obtain the original ID you provided, the VPSTxID, and the Security Key)
		You will also need an amount to repeat.  You can perform as many repeats as you
		  wish against a single payment
		Generate a unique ID for this repeat transaction.
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

// *********** Generate a random repeat transaction code -- you will need to replace this with your own system
$RepeatVendorTxCode = "testRepeat" . (rand(0,320000) * rand(0,320000));

// Set order description
// If there's an alternate description, use it (truncated to 100 characters) otherwise use default
if ($_POST['Description'] != ''){
	$Description = substr($_POST['Description'],0,100);
} else {
	$Description = $DefaultRepeatDescription;								//  Specified in init-protx.php
}

/**************************************************************************************************
	Set all the required outgoing properties for the initial HTTPS post to the VPS
**************************************************************************************************/

// Create an array of values to send
$data = array (
		'VPSProtocol' => $ProtocolVersion, 							// Protocol version (specified in init-includes.php)
		'TxType' => $_POST['TxType'],														// Transaction type 
		'Vendor' => $Vendor,														// Vendor name (specified in init-protx.php)
		'VendorTxCode' => $RepeatVendorTxCode,					// Unique repeat transaction code (generated by vendor)
		'Amount' => $_POST['Amount'],										// Value of repeat (supplied by vendor)
		'Currency' => $DefaultCurrency,									// Currency of order (default specified in init-protx.php)
		'Description' => $Description,									// Description of order 
		'RelatedVPSTxID' => $row['VPSTxId'],						// Original VPSTxID of order
		'RelatedVendorTxCode' => $row['VendorTxCode'],	// Original VendorTxCode
		'RelatedSecurityKey' => $row['SecurityKey'],		// Original Security Key
		'RelatedTxAuthNo' => $row['TxAuthNo']						// Original Transaction authorisation number
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
	Check the error level and act appropriately
'*************************************************************************************************/

$baseStatus = array_shift(split(" ",$response["Status"]));

switch($baseStatus) {

	case 'OK':
		/**************************************************************************************************
			Repeat transaction successful, so store the AuthCode in your database here.
		**************************************************************************************************/

			/*
				The repeat transaction has been authorised, so update your database to reflect that and
				store the AuthCode and VPSTxID sent by the VPS for this repeat transaction.
				These values are returned in $response['TxAuthNo'] and $response['VPSTxId'] respectively
			*/

		// Write a message to the browser informing the admin of success.
		echo ("
			<HTML>
				<BODY>
			Repeat transaction successful...<BR><BR>
			AuthNo=" . $response['TxAuthNo'] . "<BR>
			VPSTxId=" . $response['VPSTxId'] . "
			<BR><BR>To return to the main screen, click <A HREF='./'>here</A>.
			</BODY>
			</HTML>"
		);

		break; // End case 'OK'


	case 'NOTAUTHED';
		/**************************************************************************************************
			Status was not OK, so whilst communication was successful, something was wrong with the POST
			Display information about the error on screen and update your database with this information
		**************************************************************************************************/

			/*
				The repeat transaction has NOT been authorised, so update your database to reflect that and
			*/

		//Write a message to the browser informing the admin of failure
		echo ("
			<HTML>
				<BODY>
					The repeat transaction was Not Authorised by the bank.
					<BR><BR>To return to the main screen, click <A HREF='./'>here</A>.
				</BODY>
			</HTML>"
		);

		break; // End case 'NOTAUTHED'


	case 'MALFORMED';
		/**************************************************************************************************
			The repeat post sent by your site contained incorrect or unrecognisable data.
			You may wish to update your database to reflect this.
		**************************************************************************************************/

			/*
				The repeat request was malformed.  Update your database to reflect this, or you
				may wish to try to resubmit the request here.
			*/

		//Write a message to the browser informing the admin of failure
		echo ("
			<HTML>
				<BODY>
					The repeat transaction request sent to PROTX was Malformed.<BR><BR>
					Error: " . $response['StatusDetail'] .
					"<BR><BR>To return to the main screen, click <A HREF='./'>here</A>.
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
					The PROTX VPS returned an abort or error message.  The repeat transaction was unsuccessful.
					<BR><BR>To return to the main screen, click <A HREF='./'>here</A>.
				</BODY>
			</HTML>"
		);

		break; // End default

} // END switch($baseStatus)
			
// Close the database	connection
mysql_close($db);

?>