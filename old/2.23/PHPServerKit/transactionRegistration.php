<?
include("includes.php");

/**************************************************************************************************
* Sage Pay Server PHP Transaction Registration Page
***************************************************************************************************

***************************************************************************************************
* Change history
* ==============

* 02/04/2009 - Simon Wolfe - Updated UI for re-brand
* 11/02/2009 - Simon Wolfe - Updated for VSP protocol 2.23
* 18/12/2007 - Nick Selby - New PHP version adapted from ASP
***************************************************************************************************
* Description
* ===========

* This page performs 3 main functions:
*	(1) Displays the card details screen for the user to enter their payment method
*	(2) Stores the order details in the database
*	(3) POSTS the information to Sage Pay Server and redirect's the user if 3D-Auth is enabled, otherwise<br>
*		it simply updates the transaction with the success or failure of the transaction.
* If the kit is in SIMULATOR mode, everything is shown on the screen and the user asked to Proceed
* at each stage.  In Test and Live mode, nothing is echoed to the screen and the browser
* is automatically redirected to either the 3D-Authentication, or completion pages.

* This code is all carried out on one page to avoid ever storing card details either in the database
* or the session.  Such storage is not compliant with Visa and MasterCard PCI-DSS rules.
***************************************************************************************************/

// Check we have a cart in the session.  If not, go back to the buildOrder page to get one
$strCart=$_SESSION["strCart"];
if (strlen($strCart)==0) {
	ob_flush();
	redirect("buildOrder.php");
	exit();
}

// Check we have a billing address in the session.  If not, go back to the customerDetails page to get one
if (strlen($_SESSION["strBillingAddress1"])==0){
	ob_flush();
	redirect("customerDetails.php");
	exit();
}

// Check for the proceed button click, and if so, go validate the order
if ($_REQUEST["navigate"]=="proceed")
{
	ob_flush();
	redirect(cleaninput($_REQUEST["NextURL"],"Text"));
	exit();
}
elseif ($_REQUEST["navigate"]=="back")
{
	ob_flush();
	redirect("orderConfirmation.php");
	exit();
}
else
{
	/** First we need to generate a unique VendorTxCode for this transaction
	** We're using VendorName, time stamp and a random element.  You can use different methods if you wish
	** but the VendorTxCode MUST be unique for each transaction you send to Sage Pay Server */
	$strTimeStamp = date("y/m/d : H:i:s", time());
	$intRandNum = rand(0,32000)*rand(0,32000);
	$strVendorTxCode=cleanInput($strVendorName . "-" . $strTimeStamp . "-" . $intRandNum,"VendorTxCode");
	
	/** Now to calculate the transaction total based on basket contents.  For security **
	** we recalculate it here rather than relying on totals stored in the session or hidden fields **
	** We'll also create the basket contents to pass to Sage Pay Server. See the Sage Pay Server Protocol for **
	** the full valid basket format.  The code below converts from our "x of y" style into **
	** the Sage Pay system basket format (using a 20% VAT calculation for the tax columns) **/
	$sngTotal=0.0;
	$strThisEntry=$strCart;
	$strBasket="";
	$iBasketItems=0;
									
	while (strlen($strThisEntry)>0)
	{
		// Extract the Quantity and Product from the list of "x of y," entries in the cart
		
		$iQuantity=cleanInput(substr($strThisEntry,0,1),"Number");
		$iProductId=substr($strThisEntry,strpos($strThisEntry,",")-1,1);
		// Add another item to our Sage Pay Server basket
		$iBasketItems=$iBasketItems+1;
		
		$strSQL="SELECT * FROM tblProducts where ProductID='" . $iProductId . "'";
		$rsPrimary = mysql_query($strSQL)
			or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
		$row = mysql_fetch_array($rsPrimary);
		$rsPrimary="";
		$strSQL="";
		
		$strSecurityKey=$row["SecurityKey"];
						
		$sngTotal=$sngTotal + ($iQuantity * $row["Price"]);
		$strBasket=$strBasket . ":" . $row["Description"] . ":" . $iQuantity;
		$strBasket=$strBasket . ":" . number_format($row["Price"]/1.2,2); /** Price ex-Vat **/
		$strBasket=$strBasket . ":" . number_format($row["Price"]*1/6,2); /** VAT component **/
		$strBasket=$strBasket . ":" . number_format($row["Price"],2); /** Item price **/
		$strBasket=$strBasket . ":" . number_format($row["Price"]*$iQuantity,2); /** Line total **/			
						
		// Move to the next cart entry, if there is one
		$pos=strpos($strThisEntry,",");
		if ($pos==0) 
			$strThisEntry="";
		else
			$strThisEntry=substr($strThisEntry,strpos($strThisEntry,",")+1);
	}
									
	// We've been right through the cart, so add delivery to the total and the basket
	$sngTotal=$sngTotal+1.50;
	$strBasket=$iBasketItems+1 . $strBasket . ":Delivery:1:1.50:---:1.50:1.50";

	// Gather customer details from the session
	$strCustomerEMail      = $_SESSION["strCustomerEMail"];
	$strBillingFirstnames  = $_SESSION["strBillingFirstnames"];
	$strBillingSurname     = $_SESSION["strBillingSurname"];
	$strBillingAddress1    = $_SESSION["strBillingAddress1"];
	$strBillingAddress2    = $_SESSION["strBillingAddress2"];
	$strBillingCity        = $_SESSION["strBillingCity"];
	$strBillingPostCode    = $_SESSION["strBillingPostCode"];
	$strBillingCountry     = $_SESSION["strBillingCountry"];
	$strBillingState       = $_SESSION["strBillingState"];
	$strBillingPhone       = $_SESSION["strBillingPhone"];
	$bIsDeliverySame       = $_SESSION["bIsDeliverySame"];
	$strDeliveryFirstnames = $_SESSION["strDeliveryFirstnames"];
	$strDeliverySurname    = $_SESSION["strDeliverySurname"];
	$strDeliveryAddress1   = $_SESSION["strDeliveryAddress1"];
	$strDeliveryAddress2   = $_SESSION["strDeliveryAddress2"];
	$strDeliveryCity       = $_SESSION["strDeliveryCity"];
	$strDeliveryPostCode   = $_SESSION["strDeliveryPostCode"];
	$strDeliveryCountry    = $_SESSION["strDeliveryCountry"];
	$strDeliveryState      = $_SESSION["strDeliveryState"];
	$strDeliveryPhone      = $_SESSION["strDeliveryPhone"];

	/* Now to build the Sage Pay Server POST.  For more details see the Sage Pay Server Protocol 2.23
	** NB: Fields potentially containing non ASCII characters are URLEncoded when included in the POST */
	$strPost="VPSProtocol=" . $strProtocol;
	$strPost=$strPost . "&TxType=" . $strTransactionType; //PAYMENT by default.  You can change this in the includes file
	$strPost=$strPost . "&Vendor=" . $strVendorName;
	$strPost=$strPost . "&VendorTxCode=" . $strVendorTxCode; //As generated above
	
	// Optional: If you are a Sage Pay Partner and wish to flag the transactions with your unique partner id, it should be passed here
    if (strlen($strPartnerID) > 0)
            $strPost=$strPost . "&ReferrerID=" . URLEncode($strPartnerID);  //You can change this in the includes file

	$strPost=$strPost . "&Amount=" . number_format($sngTotal,2); //Formatted to 2 decimal places with leading digit but no commas or currency symbols **
	$strPost=$strPost . "&Currency=" . $strCurrency;
	
	//Up to 100 chars of free format description
	$strPost=$strPost . "&Description=" . urlencode("The best DVDs from " . $strVendorName);
	
	/* The Notification URL is the page to which Sage Pay Server calls back when a transaction completes
	** You can change this for each transaction, perhaps passing a session ID or state flag if you wish */
	$strPost=$strPost . "&NotificationURL=" . $strYourSiteFQDN . $strVirtualDir . "/notificationPage.php";
	
	// Billing Details:
	$strPost=$strPost . "&BillingFirstnames=" . $strBillingFirstnames;
	$strPost=$strPost . "&BillingSurname=" . $strBillingSurname;
	$strPost=$strPost . "&BillingAddress1=" . $strBillingAddress1;
	if (strlen($strBillingAddress2) > 0) $strPost=$strPost . "&BillingAddress2=" . $strBillingAddress2;
	$strPost=$strPost . "&BillingCity=" . $strBillingCity;
	$strPost=$strPost . "&BillingPostCode=" . $strBillingPostCode;
	$strPost=$strPost . "&BillingCountry=" . $strBillingCountry;
	if (strlen($strBillingState) > 0) $strPost=$strPost . "&BillingState=" . $strBillingState;
	if (strlen($strBillingPhone) > 0) $strPost=$strPost . "&BillingPhone=" . $strBillingPhone;
	
	// Delivery Details:
	$strPost=$strPost . "&DeliveryFirstnames=" . $strDeliveryFirstnames;
	$strPost=$strPost . "&DeliverySurname=" . $strDeliverySurname;
	$strPost=$strPost . "&DeliveryAddress1=" . $strDeliveryAddress1;
	if (strlen($strDeliveryAddress2) > 0) $strPost=$strPost . "&DeliveryAddress2=" . $strDeliveryAddress2;
	$strPost=$strPost . "&DeliveryCity=" . $strDeliveryCity;
	$strPost=$strPost . "&DeliveryPostCode=" . $strDeliveryPostCode;
	$strPost=$strPost . "&DeliveryCountry=" . $strDeliveryCountry;
	if (strlen($strDeliveryState) > 0) $strPost=$strPost . "&DeliveryState=" . $strDeliveryState;
	if (strlen($strDeliveryPhone) > 0) $strPost=$strPost . "&DeliveryPhone=" . $strDeliveryPhone;

	// Set other optionals
	$strPost=$strPost . "&CustomerEMail=" . urlencode($strCustomerEMail);
	$strPost=$strPost . "&Basket=" . urlencode($strBasket); //As created above

	/** For charities registered for Gift Aid, set to 1 to display the Gift Aid check box on the payment pages **/
	$strPost=$strPost . "&AllowGiftAid=0";
	
	/* Allow fine control over AVS/CV2 checks and rules by changing this value. 0 is Default
	** It can be changed dynamically, per transaction, if you wish.  See the Sage Pay Server Protocol document */
	if ($strTransactionType!=="AUTHENTICATE")
		$strPost=$strPost . "&ApplyAVSCV2=0";
	
	/* Allow fine control over 3D-Secure checks and rules by changing this value. 0 is Default **
	** It can be changed dynamically, per transaction, if you wish.  See the Sage Pay Server Protocol document */
	$strPost=$strPost . "&Apply3DSecure=0";
		
	// Optional setting for Profile can be used to set a simpler payment page. See protocol guide for more info. **
	$strPost=$strPost . "&Profile=NORMAL"; //NORMAL is default setting. Can also be set to LOW for the simpler payment page version.

	/* The full transaction registration POST has now been built **
	** Send the post to the target URL
	** if anything goes wrong with the connection process:
	** - $arrResponse["Status"] will be 'FAIL';
	** - $arrResponse["StatusDetail"] will be set to describe the problem 
	** Data is posted to strPurchaseURL which is set depending on whether you are using SIMULATOR, TEST or LIVE */
	$arrResponse = requestPost($strPurchaseURL, $strPost);

	/* Analyse the response from Sage Pay Server to check that everything is okay
	** Registration results come back in the Status and StatusDetail fields */
	$strStatus=$arrResponse["Status"];
	$strStatusDetail=$arrResponse["StatusDetail"];
	
	/** Caters for both OK and OK REPEATED if the same transaction is registered twice **/
	if (substr($strStatus,0,2)=="OK")
	{
		/** An OK status mean that the transaction has been successfully registered **
		** Your code needs to extract the VPSTxId (Sage Pay's unique reference for this transaction) **
		** and the SecurityKey (used to validate the call back from Sage Pay later) and the NextURL **
		** (the URL to which the customer's browser must be redirected to enable them to pay) **/
		$strVPSTxId=$arrResponse["VPSTxId"];
		$strSecurityKey=$arrResponse["SecurityKey"];
		$strNextURL=$arrResponse["NextURL"];
		
		/** Now store the VPSTxId, SecurityKey, VendorTxCode, order total and order details in **
		** your database for use both at Notification stage, and your own order fulfilment **
		** These kits come with a table called tblOrders in which this data is stored **
		** accompanied by the tblOrderProducts table to hold the basket contents for each order **/	
		$strSQL="INSERT INTO tblOrders(VendorTxCode, TxType, Amount, Currency, 
		    BillingFirstnames, BillingSurname, BillingAddress1, BillingAddress2, BillingCity, BillingPostCode, BillingCountry, BillingState, BillingPhone, 
		    DeliveryFirstnames,DeliverySurname,DeliveryAddress1,DeliveryAddress2,DeliveryCity,DeliveryPostCode,DeliveryCountry,DeliveryState,DeliveryPhone, 
			CustomerEMail, VPSTxId, SecurityKey) VALUES (";

		$strSQL=$strSQL . "'" . mysql_real_escape_string($strVendorTxCode) . "',"; //Add the VendorTxCode generated above
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strTransactionType) . "',"; //Add the TxType from the includes file
		$strSQL=$strSQL . "'" . number_format($sngTotal,2) . "',"; //Add the formatted total amount
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strCurrency) . "',"; //Add the Currency

		// Add the Billing details 
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strBillingFirstnames) . "',";   
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strBillingSurname) . "',";  
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strBillingAddress1) . "',";  
		
		if (strlen($strBillingAddress2)>0) 
		    $strSQL=$strSQL . "'" . mysql_real_escape_string($strBillingAddress2) . "',"; 
		else 
		    $strSQL=$strSQL . "null,";
		
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strBillingCity) . "',";  
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strBillingPostCode) . "',"; 
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strBillingCountry) . "',";  
		
		if (strlen(strBillingState)>0)  
		    $strSQL=$strSQL . "'" . mysql_real_escape_string($strBillingState) . "',"; 
		else 
		    $strSQL=$strSQL . "null,"; 
		
		if (strlen($strBillingPhone)>0)  
		    $strSQL=$strSQL . "'" . mysql_real_escape_string($strBillingPhone) . "',";  
		else 
		    $strSQL=$strSQL . "null,";
			 
		// Add the Delivery details 
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strDeliveryFirstnames) . "',"; 
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strDeliverySurname) . "',";  
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strDeliveryAddress1) . "',"; 

		if (strlen($strDeliveryAddress2)>0) 
		    $strSQL=$strSQL . "'" . mysql_real_escape_string($strDeliveryAddress2) . "',";  
		else 
		    $strSQL=$strSQL . "null,";
		
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strDeliveryCity) . "',";  
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strDeliveryPostCode) . "',"; 
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strDeliveryCountry) . "',";  
		
		if (strlen($strDeliveryState)>0) 
		    $strSQL=$strSQL . "'" . mysql_real_escape_string($strDeliveryState) . "',"; 
		else 
		    $strSQL=$strSQL . "null,";   
		
		if (strlen($strDeliveryPhone)>0) 
		    $strSQL=$strSQL . "'" . mysql_real_escape_string($strDeliveryPhone) . "',"; 
		else 
		    $strSQL=$strSQL . "null,"; 
		 
		// Customer email 
		if (strlen($strCustomerEMail)>0)
		    $strSQL=$strSQL . "'" . mysql_real_escape_string($strCustomerEMail) . "',"; 
		else 
		    $strSQL=$strSQL . "null,"; 

		/** Now save the fields returned from the Sage Pay System and extracted above **/
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strVPSTxId) . "',"; //Save the Sage Pay System's unique transaction reference
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strSecurityKey) . "')"; //Save the MD5 Hashing security key, used in notification

		/** Execute the SQL command to insert this data to the tblOrders table **/

		$rsPrimary = mysql_query($strSQL)
			or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
		$rsPrimary="";
		$strSQL="";
		
		/** Now add the basket contents to the tblOrderProducts table, one line at a time **/
		$strThisEntry=$strCart;
		
		while (strlen($strThisEntry)>0)
		{
			// Extract the Quantity and Product from the list of "x of y," entries in the cart
			$iQuantity=cleanInput(substr($strThisEntry,0,1),"Number");
			$iProductId=substr($strThisEntry,strpos($strThisEntry,",")-1,1);
			
			//Look up the current price of the items in the database
			$strSQL = "SELECT Price FROM tblProducts where ProductId=" . $iProductId . "";
			$rsPrimary = mysql_query($strSQL)
				or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
								
			while ($row = mysql_fetch_array($rsPrimary))
				$sngPrice=$row["Price"];
			$strSQL="";
			$rsPrimary = "";
						
			/** Save the basket contents with price included so we know the price at the time of order **
			** so that subsequent price changes will not affect the price paid for items in this order **/
			$strSQL="INSERT INTO tblOrderProducts(VendorTxCode,ProductId,Price,Quantity)
			VALUES('" . mysql_real_escape_string($strVendorTxCode) . "'," . $iProductId . ","
			. number_format($sngPrice,2) . "," . $iQuantity . ")";				
			$rsPrimary = mysql_query($strSQL)
				or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
			
			$rsPrimary="";
			$strSQL="";
			
			// Move to the next cart entry, if there is one
			$pos=strpos($strThisEntry,",");
			if ($pos==0) 
				$strThisEntry="";
			else
				$strThisEntry=substr($strThisEntry,strpos($strThisEntry,",")+1);
		}
		
		/** Finally, if we're not in Simulator Mode, redirect the page to the NextURL **
		** In Simulator mode, we allow this page to display and ask for Proceed to be clicked **/
		if ($strConnectTo!=="SIMULATOR")
		{
			ob_flush();
			redirect($strNextURL);
			exit();			
		}
	}	
	elseif ($strStatus=="MALFORMED")
	{	
		/** A MALFORMED status occurs when the POST sent above is not correctly formatted **
		** or is missing compulsory fields.  You will normally only see these during **
		** development and early testing **/
		$strPageError="Sage Pay returned an MALFORMED status. The POST was Malformed because \"" . $strStatusDetail . "\"";		
	}
	elseif ($strStatus=="INVALID")
	{
		/** An INVALID status occurs when the structure of the POST was correct, but **
		** one of the fields contains incorrect or invalid data.  These may happen when live **
		** but you should modify your code to format all data correctly before sending **
		** the POST to Sage Pay Server **/
		$strPageError="Sage Pay returned an INVALID status. The data sent was Invalid because \"" . $strStatusDetail . "\"";
	}
	else
	{
		/** The only remaining status is ERROR **
		** This occurs extremely rarely when there is a system level error at Sage Pay **
		** If you receive this status the payment systems may be unavailable **<br>
		** You could redirect your customer to a page offering alternative methods of payment here **/
		$strPageError="Sage Pay returned an ERROR status. The description of the error was \"" . $strStatusDetail . "\"";
	}
}
?>

<html>
<head>
	<title>Sage Pay Server PHP Kit Transaction Registration Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/serverKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Transaction Registration Page</div>
            <p>
			<? if (strlen($strPageError)==0)
			{
				//There are no errors to display, so show the detail of the POST to Sage Pay Server
				echo "This page shows the contents of the POST sent to Sage Pay Server (based on your selections on the previous screens) 
				and the response sent back by the system. Because you are in SIMULATOR mode, you are seeing this information 
				and having to click Proceed to continue to the payment pages. In TEST and LIVE modes, the POST and redirect happen 
				invisibly, with no information sent to the browser and no user involvement.";
			}
			else
			{
				//An error occurred during transaction registration.  Show the details here
				echo "A problem occurred whilst attempting to register this transaction with the Sage Pay systems. The details of 
				the error are shown below. This information is provided for your own debugging purposes and especially once 
				LIVE you should avoid displaying this level of detail to your customers. Instead you should modify the 
				transactionRegistration.asp page to automatically handle these errors and redirect your customer 
				appropriately (e.g. to an error reporting page, or alternative customer services number to offline payment).";
			}
			?>
			</p>
            <div class="greyHzShadeBar">&nbsp;</div>
			<div class="<? if ($strStatus=="OK") echo "infoheader"; else echo "errorheader"; ?>">
				Sage Pay Server returned a Status of <? echo $strStatus ?><br>
				<span class="warning" ><? echo $strPageError ?></span>
			</div>	
		  	<?
		  	if ($strConnectTo!=="LIVE")
			{ 
				//NEVER show this level of detail when the account is LIVE
				echo "
				<table class=\"formTable\">
					<tr>
				  		<td colspan=\"2\"><div class=\"subheader\">Post Sent to Sage Pay Server</div></td>
					</tr>
					<tr>
				  		<td colspan=\"2\" class=\"code\" style=\"word-break: break-all; word-wrap: break-word;\">" . $strPost . "</td>
					</tr>
					<tr>
				  		<td colspan=\"2\"><div class=\"subheader\">Reply from Sage Pay Server</div></td>
					</tr>
					<tr>
				  		<td colspan=\"2\" class=\"code\" style=\"word-break: break-all; word-wrap: break-word;\">" . $_SESSION["rawresponse"] . "</td>
					</tr>";
				if ($strStatus=="OK")
				{
					echo"
					<tr>
				  		<td colspan=\"2\"><div class=\"subheader\">Order Details stored in your Database</div></td>
					</tr>
					<tr>
						<td class=\"fieldLabel\">VendorTxCode:</td>
						<td class=\"fieldData\">" . $strVendorTxCode . "</td>
					</tr>
					<tr>
						<td class=\"fieldLabel\">VPSTxId:</td>
						<td class=\"fieldData\">" . $strVPSTxId . "</td>
					</tr>
					<tr>
						<td class=\"fieldLabel\">SecurityKey:</td>
						<td class=\"fieldData\">" . $strSecurityKey . "</td>
					</tr>
					<tr>
						<td class=\"fieldLabel\">Order Total:</td>
						<td class=\"fieldData\">" .  number_format($sngTotal,2) . "</td>
					</tr>
					<tr>
						<td class=\"fieldLabel\">Basket Contents:</td>
						<td class=\"fieldData\">
							<table width=\"100%\" style=\"border-collapse: collapse;\">
								<tr class=\"greybar\">
									<td width=\"10%\" align=\"right\">Quantity</td>
									<td width=\"30%\" align=\"center\">Image</td>
									<td width=\"60%\" align=\"left\">Title</td>
								</tr>";

								$strThisEntry=$strCart;
							
								while (strlen($strThisEntry)>0)
								{
									/** Extract the quantity anf Product from the list of "x of y," entries in the cart **/
									$iQuantity=cleanInput(substr($strThisEntry,0,1),"Number");
									$iProductId=substr($strThisEntry,strpos($strThisEntry,",")-1,1);
									$strImageId = "00" . $iProductId;
									
									//Get product details from database
									$strSQL = "SELECT * FROM tblProducts where ProductID=" . $iProductId . "";
									$rsPrimary = mysql_query($strSQL)
										or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
									$row = mysql_fetch_array($rsPrimary);
																			
								echo "
								<tr>
									<td align=\"right\">" . $iQuantity . "</td>
									<td align=\"center\"><img src=\"images/dvd" . substr($strImageId,strlen($strImage)-2,2) .  "small.gif\" alt=\"DVD box\"></td>
									<td align=\"left\">" . $row["Description"] . "</td>
								</tr>";
																												
								// Move to the next cart entry, if there is one
								$pos=strpos($strThisEntry,",");
								if ($pos==0) 
									$strThisEntry="";
								else
									$strThisEntry=substr($strThisEntry,strpos($strThisEntry,",")+1);
								}
								echo "	
							</table>
						</td>
					</tr>
				</table>
            	<div class=\"greyHzShadeBar\">&nbsp;</div>";
				}
								  
				if (strlen($strPageError)==0)
				{
				echo " 
            	<div class=\"formFooter\">
					<form name=\"customerform1\" action=\"transactionRegistration.php\" method=\"POST\">
					<input type=\"hidden\" name=\"navigate\" value=\"\" />
					<input type=\"hidden\" name=\"NextURL\" value=\"" . $strNextURL . "\">
					<a href=\"javascript:submitForm('customerform1','back');\" title=\"Go back to the Order Administration screen\" style=\"float: left;\"><img src=\"images/back.gif\" alt=\"Go back to the Order Administration screen\" border=\"0\"></a>
					<a href=\"javascript:submitForm('customerform1','proceed');\" title=\"Proceed to Payment Pages\" style=\"float: right;\"><img src=\"images/proceed.gif\" alt=\"Proceed to Payment Pages\" border=\"0\"></a>
					</form>
				</div>";
				}
				else
				{
				echo "
				<form name=\"customerform2\" action=\"transactionRegistration.php\" method=\"POST\">
				<input type=\"hidden\" name=\"navigate\" value=\"\" />
				<div class=\"formFooter\">
					<a href=\"javascript:submitForm('customerform2','back');\" title=\"Go back to the order confirmation page to correct submission errors\" style=\"float: right;\"><img src=\"images/back.gif\" alt=\"Go back to the order confirmation page to correct submission errors\" border=\"0\"></a>
				</div>
				</form>";
				}
			}
			?>
		</div>
	</div>
</body>
</html>


