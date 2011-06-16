<?
include("includes.php");

/**************************************************************************************************
* Sage Pay Direct PHP Transaction Registration Page
***************************************************************************************************

***************************************************************************************************
* Change history
* ==============

* 02/04/2009 - Simon Wolfe - Updated UI for re-brand
* 11/02/2009 - Simon Wolfe - Created for VSP protocol 2.23. Adapted from ASP version.
***************************************************************************************************
* Description
* ===========

* This page handles the PayPal callback POSTs from Sage Pay Direct after the PayPal Authentication, as
* well as the final completion request to accept/cancel the paypal transaction. This page & process
* will only be called upon for transations where the customer has chosen a PayPal payment method and
* your Sage Pay account has this feature enabled. This page should be made externally visible so that 
* Sage Pay Direct servers can send messages to here over either HTTP or HTTPS.
***************************************************************************************************/

// Check for the proceed button click, this will go through to either a success or failure page 
if (($_REQUEST["navigate"]=="proceed")) 
{
	// Retrieve the VPSTxId from the session. We need this to complete the transaction  
	$strVPSTxId = $_SESSION["VPSTxId"];
	if (strlen($strVPSTxId) == 0) 
	{
        errorRedirect("your session timed out");
    }
    else
    {
        // Using the VPSTxId we can retrieve our order transaction amount from our database.
        // This ensures we have the correct amount and its a valid existing order.
        $strSQL = "SELECT Amount FROM tblOrders WHERE VPSTxId = '" . mysql_real_escape_string($strVPSTxId) . "'";
        $rsPrimary = mysql_query($strSQL) or die ("Query '$strSQL' failed with error message: \"" . mysql_error () . '"');
		if ($row = mysql_fetch_array($rsPrimary))
		{
	        $strAmount = $row["Amount"];
	        
	        // The status indicates Ok to try to POST the completion request to Sage Pay Direct 
	        if ($_REQUEST["status"] == "PAYPALOK")
	        {
	            postCompletionRequest();
	        }
	        else
	        {
	            errorRedirect($_SESSION["StatusDetail"]);
	        }
		}
        // else the order was NOT found in the database then we can't complete the transaction  
        else
        {        
            errorRedirect("there is no record of your order in our system"); 
        }
    }
} 
else
{
    // Information is POSTed to this page from Sage Pay Direct. The POST will ALWAYS contain the Status and StatusDetail fields.  
    // We'll extract these first and use them to decide how to respond to the POST. 
    $strStatus = cleanInput($_REQUEST["Status"], "Text");
    $strStatusDetail = cleanInput($_REQUEST["StatusDetail"], "Text");
    $strVPSTxId = cleanInput($_REQUEST["VPSTxId"], "Text");
    $_SESSION["VPSTxId"] = $strVPSTxId;
    $_SESSION["Status"] = $strStatus;
    $_SESSION["StatusDetail"] = $strStatusDetail;

    // If we don't have a VPSTxId and Status from the incoming request POST then we can't complete the transaction  
    if ((strlen($strVPSTxId) == 0) || (strlen($strStatus) == 0))
    {
        errorRedirect("the response from PayPal was invalid");
	}
    else
    {
        // Using the VPSTxId we can retrieve our order transaction amount from our database 
        // This ensures we have the correct amount and its a valid existing order.
        $strSQL = "SELECT * FROM tblOrders WHERE VPSTxId = '" . mysql_real_escape_string($strVPSTxId) . "'";
        $rsPrimary = mysql_query($strSQL) or die ("Query '$strSQL' failed with error message: \"" . mysql_error () . '"');
		if ($row = mysql_fetch_array($rsPrimary))
		{
	        $strAmount = $row["Amount"];
	        $strVendorTxCode = $row["VendorTxCode"];
	        $_SESSION["VendorTxCode"] = $strVendorTxCode;
        }
        
        // If order was NOT found in the database then we can't complete the transaction  
        if (strlen($strAmount) == 0) 
        {
            errorRedirect("there is no record of your order in our system");
		}
        // Check the status returned from Sage Pay Direct and take appropriate actions. 
        // If Status is Ok then you can decide if to proceed or cancel transtion based on results 
        // of the AddressStatus and PayerStatus. If Status is NOT ok then you will not be able to 
        // complete paypal transction. 
        elseif ($strStatus == "PAYPALOK")
		{
            // Great, the customer has completed the paypal checkout successfully. 
            // Extract the values from the response and update our database with the results 
            // from the request and build a completion post request to Sage Pay Direct. 
            $strVPSTxId = cleanInput($_REQUEST["VPSTxId"], "Text");
            $strAddressStatus = cleanInput($_REQUEST["AddressStatus"], "Text");
            $strPayerStatus = cleanInput($_REQUEST["PayerStatus"], "Text");
            $strDeliverySurname = cleanInput($_REQUEST["DeliverySurname"], "Text");
            $strDeliveryFirstnames = cleanInput($_REQUEST["DeliveryFirstnames"], "Text");
            $strDeliveryAddress1 = cleanInput($_REQUEST["DeliveryAddress1"], "Text");
            $strDeliveryAddress2 = cleanInput($_REQUEST["DeliveryAddress2"], "Text");
            $strDeliveryCity = cleanInput($_REQUEST["DeliveryCity"], "Text");
            $strDeliveryPostCode = cleanInput($_REQUEST["DeliveryPostCode"], "Text");
            $strDeliveryCountry = cleanInput($_REQUEST["DeliveryCountry"], "Text");
            $strDeliveryState = cleanInput($_REQUEST["DeliveryState"], "Text");
            $strDeliveryPhone = cleanInput($_REQUEST["DeliveryPhone"], "Text");
            $strCustomerEmail = cleanInput($_REQUEST["CustomerEmail"], "Text");
            $strPayerID = cleanInput($_REQUEST["PayerID"], "Text");

            // Store the details in our database 
            updateDatabase();
        }
        else // "ERROR" "MALFORMED" "INVALID"
		{
            $strPageError = $strStatusDetail;
            
            // Update the status in database 
            updateDatabase;
    	}
	}   
}


function postCompletionRequest()
{
    // Here we will proceed with accepting the transaction, however you should check the 
    // Address Status and Payer Status returned from PayPal and before choosing if to  
    // proceed with either accepting or cancelling transactions. 

    // Now to build the Sage Pay Direct POST for the PayPal Completion.  For more details see the Sage Pay Server Protocol 2.23 
    // NB: Fields potentially containing non ASCII characters are URLEncoded when included in the POST 
    $strPost = "VPSProtocol=" . URLEncode($GLOBALS["strProtocol"]);
    $strPost = $strPost . "&TxType=COMPLETE";
    $strPost = $strPost . "&VPSTxId=" . URLEncode($GLOBALS["strVPSTxId"]);
    $strPost = $strPost . "&Amount=" . URLEncode($GLOBALS["strAmount"]);
    $strPost = $strPost . "&Accept=YES";

	/* Send the post to the target URL
	** if anything goes wrong with the connection process:
	** - $arrResponse["Status"] will be 'FAIL';
	** - $arrResponse["StatusDetail"] will be set to describe the problem 
    ** Data is posted to $strPayPalRedirectURL which is set in the includes file. */
	$arrResponse = requestPost($GLOBALS["strPayPalCompletionURL"], $strPost);

	/* Analyse the response from Sage Pay Direct to check that everything is okay
	** Registration results come back in the Status and StatusDetail fields */
	$GLOBALS["strStatus"] = $arrResponse["Status"];
	$GLOBALS["strStatusDetail"] = $arrResponse["StatusDetail"];
    $GLOBALS["strTxAuthNo"] = $arrResponse["TxAuthNo"];

	// Check for network/transport errors
    if ($GLOBALS["strStatus"] == "FAIL")   
    {
	   $GLOBALS["strPageError"]="An Error has occurred whilst trying to post the PayPal completion request to Sage Pay.<BR>
				 Check that you do not have a firewall restricting the POST and that your server 
				 can correctly resolve the address " . $GLOBALS["strPayPalCompletionURL"] . "<BR>
				 The Description given is: " . $GLOBALS["strStatusDetail"];
				 
		// Update our database with the status
		$strSQL="UPDATE tblOrders SET Status='" .  mysql_real_escape_string($GLOBALS["strStatus"]) . " - PAYPAL COMPLETION FAILED: " . mysql_real_escape_string($GLOBALS["strStatusDetail"]) . "'";
		$strSQL=$strSQL . " WHERE VPSTxId='" . mysql_real_escape_string($GLOBALS["strVPSTxId"]) . "'";
		
		mysql_query($strSQL) or die ("Query '$strSQL' failed with error message: \"" . mysql_error () . '"');

	}
    else
    {
        // No transport level errors, so the message got the Sage Pay 
        
		// Update our database with the results from the response POST 
		$strSQL="UPDATE tblOrders SET Status='" .  mysql_real_escape_string($GLOBALS["strStatus"]) . " - " . mysql_real_escape_string($GLOBALS["strStatusDetail"]) . "'";

		if (strlen($GLOBALS["strTxAuthNo"])>0) 
		{ 
			$strSQL=$strSQL . ",TxAuthNo=" . mysql_real_escape_string($GLOBALS["strTxAuthNo"]);
		}
		$strSQL=$strSQL . " WHERE VPSTxId='" . mysql_real_escape_string($GLOBALS["strVPSTxId"]) . "'";
		
		mysql_query($strSQL) or die ("Query '$strSQL' failed with error message: \"" . mysql_error () . '"');

		// Now decide where to redirect the customer 
		if ($GLOBALS["strStatus"]=="OK")
		{	// If a transaction status is OK then we should send the customer to the success page 
			$strRedirectPage="orderSuccessful.php"; 
		}
		else
		{	// The status indicates a failure of one state or another, so send the customer to orderFailed instead 
			errorRedirect($GLOBALS["strStatusDetail"]);
			return;
		}

		ob_end_flush();
		redirect($strRedirectPage);
		exit();
    }
}


function errorRedirect($strErrorMessage)
{
	// We cannot find a record of this order in the database, or the session has expired and lost our VPSTxId  
	// or the callback post is malformed and the requried information is missing. 
	// We will NOT post a completion message to Sage Pay Direct so this will prevent the authorisation of the transaction. 
	// Redirect customer to our orderFailure page, passing details of the error in the Session so that the 
	// page knows how to respond to the customer. 
	
	$_SESSION["ErrorMessage"] = $strErrorMessage;
    ob_end_flush();
	redirect("orderFailed.php");
	exit();
}


// Updates our database with the results from the PayPal callback response 
function updateDatabase()
{    
	$strSQL="UPDATE tblOrders set Status='" . $GLOBALS["strStatus"] . " - " . $GLOBALS["strStatusDetail"] . "'";
	if (strlen($GLOBALS["strAddressStatus"])>0) $strSQL=$strSQL . ",AddressStatus='" . mysql_real_escape_string($GLOBALS["strAddressStatus"]) . "'";
	if (strlen($GLOBALS["strPayerStatus"])>0) $strSQL=$strSQL . ",PayerStatus='" . mysql_real_escape_string($GLOBALS["strPayerStatus"]) . "'";
	if (strlen($GLOBALS["strPayerID"])>0) $strSQL=$strSQL . ",PayPalPayerID='" . mysql_real_escape_string($GLOBALS["strPayerID"]) . "'";
	if (strlen($GLOBALS["strDeliveryFirstnames"])>0) $strSQL=$strSQL . ",DeliveryFirstnames='" . mysql_real_escape_string($GLOBALS["strDeliveryFirstnames"]) . "'";
	if (strlen($GLOBALS["strDeliverySurname"])>0) $strSQL=$strSQL . ",DeliverySurname='" . mysql_real_escape_string($GLOBALS["strDeliverySurname"]) . "'";
	if (strlen($GLOBALS["strDeliveryAddress1"])>0) $strSQL=$strSQL . ",DeliveryAddress1='" . mysql_real_escape_string($GLOBALS["strDeliveryAddress1"]) . "'";
	if (strlen($GLOBALS["strDeliveryAddress2"])>0) $strSQL=$strSQL . ",DeliveryAddress2='" . mysql_real_escape_string($GLOBALS["strDeliveryAddress2"]) . "'";
	if (strlen($GLOBALS["strDeliveryCity"])>0) $strSQL=$strSQL . ",DeliveryCity='" . mysql_real_escape_string($GLOBALS["strDeliveryCity"]) . "'";
	if (strlen($GLOBALS["strDeliveryPostCode"])>0) $strSQL=$strSQL . ",DeliveryPostCode='" . mysql_real_escape_string($GLOBALS["strDeliveryPostCode"]) . "'";
	if (strlen($GLOBALS["strDeliveryCountry"])>0) $strSQL=$strSQL . ",DeliveryCountry='" . mysql_real_escape_string($GLOBALS["strDeliveryCountry"]) . "'";
	if (strlen($GLOBALS["strDeliveryState"])>0) $strSQL=$strSQL . ",DeliveryState='" . mysql_real_escape_string($GLOBALS["strDeliveryState"]) . "'";
	if (strlen($GLOBALS["strDeliveryPhone"])>0) $strSQL=$strSQL . ",DeliveryPhone='" . mysql_real_escape_string($GLOBALS["strDeliveryPhone"]) . "'";
	if (strlen($GLOBALS["strCustomerEMail"])>0) $strSQL=$strSQL . ",CustomerEMail='" . mysql_real_escape_string($GLOBALS["strCustomerEMail"]) . "'";
    $strSQL=$strSQL . " where VPSTxId='" . mysql_real_escape_string($GLOBALS["strVPSTxId"]) . "'";
	return mysql_query($strSQL) or die ("Query '$strSQL' failed with error message: \"" . mysql_error () . '"');
}

?>
<html>
<head>
	<title>Direct PHP PayPal Callback Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/directKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
</head>
<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">PayPal Callback Page</div>
		<? 
		if (strlen($strPageError)==0) 
		{
			// There are no errors to display, so show the detail of the POST to Sage Pay Direct
		?>							
			<p>This page shows the results of the PayPal callback POST from Sage Pay Direct after the 
			customer has completed PayPal Authentication. Because you are in SIMULATOR mode, 
			you are seeing this information and having to click Proceed to complete the transaction. 
			In LIVE mode, this would happen invisibly without user involvement, and the customer 
			would simply be informed about the outcome of the transaction completion.</p>
		<?
		} 
		else 
		{
			// An error occurred during transaction registration.  Show the details here
		?>
			 <p>A problem occurred whilst attempting to handle the PayPal callback response or completion request.
			 The details of the error are shown below. This information is provided for your own debugging 
			 purposes and especially once LIVE you should avoid displaying this level of detail to your customers. 
			 Instead you should modify the paypalCallback.php page to automatically handle these errors and 
			 redirect your customer appropriately (e.g. to an error reporting page, or alternative customer 
			 services number to offline payment) </p>			
		<? 
		}
		?>
			<div class="greyHzShadeBar">&nbsp;</div>
			<div class="<?
				    if ($strStatus=="PAYPALOK")
						echo "infoheader";
					else 
						echo "errorheader";
					?>" align="center">Sage Pay Direct returned a Status of <? echo $strStatus ?><br>
				<span class="warning"><? echo $strPageError ?></span>
			</div>
		<? 
		if ($strConnectTo != "LIVE") 
		{ 
			// NEVER show this level of detail when the account is LIVE
		?>
			<table class="formTable">
				<tr>
				  <td colspan="2"><div class="subheader">Reply from Sage Pay Direct</div></td>
				</tr>
				<tr>
					<td class="fieldLabel">Status:</td>
					<td class="fieldData"><? echo $strStatus ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">StatusDetail:</td>
					<td class="fieldData"><? echo $strStatusDetail ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">VPSTxId:</td>
					<td class="fieldData"><? echo $strVPSTxId ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">AddressStatus:</td>
					<td class="fieldData"><? echo $strAddressStatus ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">PayerStatus:</td>
					<td class="fieldData"><? echo $strPayerStatus ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">DeliverySurname:</td>
					<td class="fieldData"><? echo $strDeliverySurname ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">DeliveryFirstnames:</td>
					<td class="fieldData"><? echo $strDeliveryFirstnames ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">DeliveryAddress1:</td>
					<td class="fieldData"><? echo $strDeliveryAddress1 ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">DeliveryAddress2:</td>
					<td class="fieldData"><? echo $strDeliveryAddress2 ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">DeliveryCity:</td>
					<td class="fieldData"><? echo $strDeliveryCity ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">DeliveryPostCode:</td>
					<td class="fieldData"><? echo $strDeliveryPostCode ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">DeliveryCountry:</td>
					<td class="fieldData"><? echo $strDeliveryCountry ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">DeliveryState:</td>
					<td class="fieldData"><? echo $strDeliveryState ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">DeliveryPhone:</td>
					<td class="fieldData"><? echo $strDeliveryPhone ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">CustomerEmail:</td>
					<td class="fieldData"><? echo $strCustomerEmail ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">PayerID:</td>
					<td class="fieldData"><? echo $strPayerID ?>&nbsp;</td>
				</tr>
				<tr>
				  <td colspan="2"><div class="subheader">Order Details stored in your Database</div></td>
				</tr>
				<tr>
					<td class="fieldLabel">VendorTxCode:</td>
					<td class="fieldData"><? echo $strVendorTxCode ?></td>
				</tr>
				<tr>
					<td class="fieldLabel">Order Total:</td>
					<td class="fieldData"><? echo number_format($strAmount,2) . " " . $strCurrency ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel" valign="top">Basket Contents:</td>
					<td class="fieldData">
						<table width="100%" style="border-collapse: collapse;">
							<tr class="greybar">
								<td width="10%" align="right">Quantity</td>
								<td width="20%" align="center">Image</td>
								<td width="50%" align="left">Title</td>
							    <td width="20%" align="right">Price</td>
							</tr>
							<? 
							//Extract the details of the basket for this order from the database
							$strSQL="SELECT op.Price,op.Quantity,p.* FROM tblOrderProducts op
									inner join tblProducts p on op.ProductId=p.ProductId
									where op.VendorTxCode='" . mysql_real_escape_string($strVendorTxCode) . "'";
							
							$rsPrimary = mysql_query($strSQL)
								or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
																			
							while ($row = mysql_fetch_array($rsPrimary)) 
							{
								$strProductId = $row["ProductId"];										
								$strImageId = "00" . $strProductId;
								echo "
								<tr>
										<td align=\"right\">" . $row["Quantity"] . "</td>
										<td align=\"center\"><img src=\"images/dvd" . substr($strImageId,strlen($strImage)-2,2) .  "small.gif\" alt=\"DVD box\"></td>
										<td align=\"left\">" . $row["Description"] . "</td>
										<td align=\"right\">" . number_format($row["Price"],2) . " " . $strCurrency . "</td>
								</tr>";
							}										
							?>
						</table>
					</td>
				</tr>
			</table>
			<div class="greyHzShadeBar">&nbsp;</div>
			<?
			} 
			?>
			<div class="formFooter">
				<form name="mainForm" action="paypalCallback.php" method="POST">
				<input type="hidden" name="navigate" value="proceed">
				<input type="hidden" name="status" value="<? echo $strStatus  ?>" />
				<a href="javascript:submitForm('mainForm','proceed');" title="Proceed to complete the order"><img src="images/proceed.gif" alt="Proceed" border="0" align="right" /></a>
				</form>
			</div>
		</div>
	</div>
</body>
</html>
