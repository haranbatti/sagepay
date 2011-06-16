<?
include("includes.php");

/**************************************************************************************************
* Sage Pay Direct PHP Transaction Registration Page
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
*	(3) POSTS the information to Sage Pay Direct and redirect's the user if 3D-Auth is enabled, otherwise<br>
*		it simply updates the transaction with the success or failure of the transaction.
* If the kit is in SIMULATOR mode, everything is shown on the screen and the user asked to Proceed
* at each stage.  In Test and Live mode, nothing is echoed to the screen and the browser
* is automatically redirected to either the 3D-Authentication, or completion pages.

* This code is all carried out on one page to avoid ever storing card details either in the database
* or the session.  Such storage is not compliant with Visa and MasterCard PCI-DSS rules.
***************************************************************************************************/

$strPageState = "Payment";

// Check if request was for a PayPal express checkout
if ($_SESSION["paypalExpress"] == true) 
{
    $isPaypalExpress = true;
    $_SESSION["paypalExpress"] = false;
} 
else
{
    $isPaypalExpress = false;
}
 
// Check we have a cart in the session.  If not, go back to the buildOrder page to get one
$strCart=$_SESSION["strCart"];
if (strlen($strCart)==0) 
{
	ob_end_flush();
	redirect("buildOrder.php");
	exit();
}
// Check we have a billing address in the session if its not a PayPal Express checkout request
elseif (strlen($_SESSION["strBillingAddress1"])==0 && ($isPaypalExpress==false)) 
{
	ob_flush();
	redirect("customerDetails.php");
	exit();
}
// Check for the proceed button click, or if page was redirected here by PayPal Express **
elseif ($_REQUEST["navigate"]=="back")
{
	ob_flush();
	redirect("orderConfirmation.php");
	exit();
}
elseif (($_REQUEST["navigate"]=="proceed") || ($isPaypalExpress==true))
{
    // The user wants to proceed to the confirmation page.  Send them there **
	if ($_REQUEST["PageState"] == "Completion") 
	{
		ob_flush();
		if (strlen($_REQUEST["CompletionURL"]) > 0) {
		    redirect($_REQUEST["CompletionURL"]);
		    exit();
		}
	}
	// The Customer is checking out with the PayPal express payment method
	elseif ($isPaypalExpress == true) 
	{
	    $strCardType = "PAYPAL";
	}
	//The customer wants to take a payment, so validate the payment boxes first
	else
	{	
		// Extract Card Details from the page
		$strCardType=cleanInput($_REQUEST["CardType"],"Text");	
		$strCardHolder=substr($_REQUEST["CardHolder"],0,100);
		$strCardNumber=cleanInput($_REQUEST["CardNumber"],"Number");
		$strStartDate=cleanInput($_REQUEST["StartDate"],"Number");
		$strExpiryDate=cleanInput($_REQUEST["ExpiryDate"],"Number");
		$strIssueNumber=cleanInput($_REQUEST["IssueNumber"],"Number");
		$strCV2=cleanInput($_REQUEST["CV2"],"Number");
		
		// Right then... check em
		if ($strCardType!="PAYPAL") 
		{
			if ($strCardHolder=="")
				$strPageError="You must enter the name of the Card Holder.";
			elseif ($strCardType=="")
				$strPageError="You must select the type of card being used.";
			elseif ($strCardNumber=="" || !is_numeric($strCardNumber))
				$strPageError="You must enter the full card number.";
			elseif ($strStartDate!=="" && (strlen($strStartDate)!=4 || !is_numeric($strStartDate)))
				$strPageError="If you provide a Start Date, it should be in MMYY format, e.g. 1206 for December 2006.";
			elseif ($strExpiryDate=="" || strlen($strExpiryDate)!=4 || !is_numeric($strExpiryDate)) 
				$strPageError="You must provide an Expiry Date in MMYY format, e.g. 1209 for December 2009.";
			elseif (($strIssueNumber!=="") and (!is_numeric($strIssueNumber))) 
				$strPageError="If you provide an Issue number, it should be numeric.";
			elseif (($strCV2=="")||(!is_numeric($strCV2))) {
				$strPageError="You must provide a Card Verification Value. This is the last 3 digits on the signature strip 
				               of your card (or for American Express cards, the 4 digits printed to the right of the main 
				               card number on the front of the card.)";
			}
		}
	}

    if (strlen($strPageError) == 0 ) 
    { 
		/* All required fields are present, so first store the order in the database then format the POST to Sage Pay Direct 
		** First we need to generate a unique VendorTxCode for this transaction
		** We're using VendorName, time stamp and a random element.  You can use different methods if you wish
		** but the VendorTxCode MUST be unique for each transaction you send to Sage Pay Direct */
		$strTimeStamp = date("y/m/d : H:i:s", time());
		$intRandNum = rand(0,32000)*rand(0,32000);
		$strVendorTxCode=cleanInput($strVendorName . "-" . $strTimeStamp . "-" . $intRandNum,"VendorTxCode");
		$_SESSION["VendorTxCode"] = $strVendorTxCode;
			
		/* Calculate the transaction total based on basket contents.  For security
		** we recalculate it here rather than relying on totals stored in the session or hidden fields
		** We'll also create the basket contents to pass to Sage Pay Direct. See the Sage Pay Direct Protocol for
		** the full valid basket format.  The code below converts from our "x of y" style into
		** the Sage Pay system basket format (using a 20% VAT calculation for the tax columns) */
		$sngTotal=0.0;
		$strThisEntry=$strCart;
		$strBasket="";
		$iBasketItems=0;
								
		while (strlen($strThisEntry)>0) {
			// Extract the Quantity and Product from the list of "x of y," entries in the cart
			
			$iQuantity=cleanInput(substr($strThisEntry,0,1),"Number");
			$iProductId=substr($strThisEntry,strpos($strThisEntry,",")-1,1);
			// Add another item to our Sage Pay Direct basket
			$iBasketItems=$iBasketItems+1;
			$strSQL = "SELECT * FROM tblProducts where ProductId=" . $iProductId . "";
			$rsPrimary = mysql_query($strSQL)
				or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
			$row = mysql_fetch_array($rsPrimary); 
			
			$sngTotal=$sngTotal + ($iQuantity * $row["Price"]);
			$strBasket=$strBasket . ":" . $arrProducts[$iProductId-1][0] . ":" . $iQuantity;
			$strBasket=$strBasket . ":" . number_format($row["Price"]/1.2,2); /** Price ex-Vat **/
			$strBasket=$strBasket . ":" . number_format($row["Price"]*1/6,2); /** VAT component **/
			$strBasket=$strBasket . ":" . number_format($row["Price"],2); /** Item price **/
			$strBasket=$strBasket . ":" . number_format($row["Price"]*$iQuantity,2); /** Line total **/	

			$strSQL="";
			$rsPrimary="";
			$row="";
							
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

		/* Now store the order total and order details in your database for use in your own order fulfilment
		** These kits come with a table called tblOrders in which this data is stored
		** accompanied by the tblOrderProducts table to hold the basket contents for each order */
		
		$strPageState="Posted";
		$strSQL="";
		$strSQL="INSERT INTO tblOrders(VendorTxCode, TxType, Amount, Currency, 
		    BillingFirstnames, BillingSurname, BillingAddress1, BillingAddress2, BillingCity, BillingPostCode, BillingCountry, BillingState, BillingPhone, 
		    DeliveryFirstnames,DeliverySurname,DeliveryAddress1,DeliveryAddress2,DeliveryCity,DeliveryPostCode,DeliveryCountry,DeliveryState,DeliveryPhone, 
			CustomerEMail, CardType) VALUES (";

		$strSQL=$strSQL . "'" . mysql_real_escape_string($strVendorTxCode) . "',"; //Add the VendorTxCode generated above
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strTransactionType) . "',"; //Add the TxType from the includes file
		$strSQL=$strSQL . "'" . number_format($sngTotal,2) . "',"; //Add the formatted total amount
		$strSQL=$strSQL . "'" . mysql_real_escape_string($strCurrency) . "',"; //Add the Currency
			
        //** If this is a PaypalExpress checkout method then NO billing and delivery details are available here **
        if ($isPaypalExpress == true) 
        {
            $strSQL=$strSQL . " null, null, null, null, null, null, null, null, null, null, ";
            $strSQL=$strSQL . " null, null, null, null, null, null, null, null, null, 'PAYPAL')";
        }
        else
        {
			// Add the Billing details 
			$strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strBillingFirstnames"]) . "',";   
			$strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strBillingSurname"]) . "',";  
			$strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strBillingAddress1"]) . "',";  
			
			if (strlen($_SESSION["strBillingAddress2"])>0) 
			    $strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strBillingAddress2"]) . "',"; 
			else 
			    $strSQL=$strSQL . "null,";
			
			$strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strBillingCity"]) . "',";  
			$strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strBillingPostCode"]) . "',"; 
			$strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strBillingCountry"]) . "',";  
			
			if (strlen($_SESSION["strBillingState"])>0)  
			    $strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strBillingState"]) . "',"; 
			else 
			    $strSQL=$strSQL . "null,"; 
			
			if (strlen($_SESSION["strBillingPhone"])>0)  
			    $strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strBillingPhone"]) . "',";  
			else 
			    $strSQL=$strSQL . "null,";
				 
			// Add the Delivery details 
			$strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strDeliveryFirstnames"]) . "',"; 
			$strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strDeliverySurname"]) . "',";  
			$strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strDeliveryAddress1"]) . "',"; 
	
			if (strlen($_SESSION["strDeliveryAddress2"])>0) 
			    $strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strDeliveryAddress2"]) . "',";  
			else 
			    $strSQL=$strSQL . "null,";
			
			$strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strDeliveryCity"]) . "',";  
			$strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strDeliveryPostCode"]) . "',"; 
			$strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strDeliveryCountry"]) . "',";  
			
			if (strlen($_SESSION["strDeliveryState"])>0) 
			    $strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strDeliveryState"]) . "',"; 
			else 
			    $strSQL=$strSQL . "null,";   
			
			if (strlen($_SESSION["strDeliveryPhone"])>0) 
			    $strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strDeliveryPhone"]) . "',"; 
			else 
			    $strSQL=$strSQL . "null,"; 
			 
			// Customer email 
			if (strlen($_SESSION["strCustomerEMail"])>0)
			    $strSQL=$strSQL . "'" . mysql_real_escape_string($_SESSION["strCustomerEMail"]) . "',"; 
			else 
			    $strSQL=$strSQL . "null,"; 
			    
			// Card Type
			$strSQL=$strSQL . "'" . mysql_real_escape_string($strCardType) . "')";
			
		}
		
		//Execute the SQL command to insert this data to the tblOrders table
		mysql_query($strSQL) or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
		$strSQL="";
								
		//Now add the basket contents to the tblOrderProducts table, one line at a time
		$strThisEntry=$strCart;
		while (strlen($strThisEntry)>0) 
		{
			// Extract the quantity and Product from the list of "x of y," entries in the cart
			$iQuantity=cleanInput(substr($strThisEntry,0,1),"Number");
			$iProductId=substr($strThisEntry,strpos($strThisEntry,",")-1,1);

			//Look up the current price of the items in the database
			$strSQL = "SELECT Price FROM tblProducts where ProductID=" . $iProductId . "";
			$rsPrimary = mysql_query($strSQL)
				or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');

			while ($row = mysql_fetch_array($rsPrimary))
				$sngPrice=$row["Price"];
			$strSQL="";
			$rsPrimary = "";

			/* Save the basket contents with price included so we know the price at the time of order
			** so that subsequent price changes will not affect the price paid for items in this order */
			$strSQL="INSERT INTO tblOrderProducts(VendorTxCode,ProductID,Price,Quantity) VALUES(";
			$strSQL=$strSQL . "'" .  mysql_real_escape_string($strVendorTxCode) . "',";
			$strSQL=$strSQL . $iProductId . ",";
			$strSQL=$strSQL . number_format($sngPrice,2) . ",";
			$strSQL=$strSQL . $iQuantity . ")";				

			mysql_query($strSQL)
				or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
			
			// Move to the next cart entry, if there is one
			$pos=strpos($strThisEntry,",");
			if ($pos==0) 
				$strThisEntry="";
			else
				$strThisEntry=substr($strThisEntry,$pos+1);
		}

		// Now create the Sage Pay Direct POST

		/* Now to build the Sage Pay Direct POST.  For more details see the Sage Pay Direct Protocol 2.23
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
		
		// Up to 100 chars of free format description
		$strPost=$strPost . "&Description=" . urlencode("The best DVDs from " . $strVendorName);
		
		// Card details. Not required if CardType = "PAYPAL" 
		if ($strCardType != "PAYPAL")
		{ 
			$strPost=$strPost . "&CardHolder=" . $strCardHolder;
			$strPost=$strPost . "&CardNumber=" . $strCardNumber;
			if (strlen($strStartDate)>0) 
				$strPost=$strPost . "&StartDate=" . $strStartDate;
			$strPost=$strPost . "&ExpiryDate=" . $strExpiryDate;
			if (strlen($strIssueNumber)>0) 
				$strPost=$strPost . "&IssueNumber=" . $strIssueNumber;
			$strPost=$strPost . "&CV2=" . $strCV2;
		}
		$strPost=$strPost . "&CardType=" . $strCardType;
		
        // If this is a PaypalExpress checkout method then NO billing and delivery details are supplied 
        if ($isPaypalExpress == false) 
        {
            /* Billing Details 
            ** This section is optional in its entirety but if one field of the address is provided then all non-optional fields must be provided 
            ** If AVS/CV2 is ON for your account, or, if paypal cardtype is specified and its not via PayPal Express then this section is compulsory */
			$strPost=$strPost . "&BillingFirstnames=" . urlencode($_SESSION["strBillingFirstnames"]);
			$strPost=$strPost . "&BillingSurname=" . urlencode($_SESSION["strBillingSurname"]);
			$strPost=$strPost . "&BillingAddress1=" . urlencode($_SESSION["strBillingAddress1"]);
			if (strlen($_SESSION["strBillingAddress2"]) > 0) $strPost=$strPost . "&BillingAddress2=" . urlencode($_SESSION["strBillingAddress2"]);
			$strPost=$strPost . "&BillingCity=" . urlencode($_SESSION["strBillingCity"]);
			$strPost=$strPost . "&BillingPostCode=" . urlencode($_SESSION["strBillingPostCode"]);
			$strPost=$strPost . "&BillingCountry=" . urlencode($_SESSION["strBillingCountry"]);
			if (strlen($_SESSION["strBillingState"]) > 0) $strPost=$strPost . "&BillingState=" . urlencode($_SESSION["strBillingState"]);
			if (strlen($_SESSION["strBillingPhone"]) > 0) $strPost=$strPost . "&BillingPhone=" . urlencode($_SESSION["strBillingPhone"]);

            /* Delivery Details
            ** This section is optional in its entirety but if one field of the address is provided then all non-optional fields must be provided
            ** If paypal cardtype is specified then this section is compulsory */
			$strPost=$strPost . "&DeliveryFirstnames=" . urlencode($_SESSION["strDeliveryFirstnames"]);
			$strPost=$strPost . "&DeliverySurname=" . urlencode($_SESSION["strDeliverySurname"]);
			$strPost=$strPost . "&DeliveryAddress1=" . urlencode($_SESSION["strDeliveryAddress1"]);
			if (strlen($_SESSION["strDeliveryAddress2"]) > 0) $strPost=$strPost . "&DeliveryAddress2=" . urlencode($_SESSION["strDeliveryAddress2"]);
			$strPost=$strPost . "&DeliveryCity=" . urlencode($_SESSION["strDeliveryCity"]);
			$strPost=$strPost . "&DeliveryPostCode=" . urlencode($_SESSION["strDeliveryPostCode"]);
			$strPost=$strPost . "&DeliveryCountry=" . urlencode($_SESSION["strDeliveryCountry"]);
			if (strlen($_SESSION["strDeliveryState"]) > 0) $strPost=$strPost . "&DeliveryState=" . urlencode($_SESSION["strDeliveryState"]);
			if (strlen($_SESSION["strDeliveryPhone"]) > 0) $strPost=$strPost . "&DeliveryPhone=" . urlencode($_SESSION["strDeliveryPhone"]);     
        }
        
		/* For PAYPAL cardtype only: Fully qualified domain name of the URL to which customers are redirected upon 
        ** completion of a PAYPAL transaction. Here we are getting strYourSiteFQDN & strVirtualDir from  
        ** the includes file. Must begin with http:// or https:// */
        if ($strCardType == "PAYPAL") 
        {
        	$strPost = $strPost . "&PayPalCallbackURL=" . urlencode($strYourSiteFQDN . $strVirtualDir . "/paypalCallback.php");
        }

		// Set other optionals
		$strPost=$strPost . "&CustomerEMail=" . urlencode($_SESSION["strCustomerEMail"]);
		$strPost=$strPost . "&Basket=" . urlencode($strBasket); //As created above

		// For charities registered for Gift Aid, set to 1 to makr this as a Gift Aid transaction
		$strPost=$strPost . "&GiftAidPayment=0";
		
		/* Allow fine control over AVS/CV2 checks and rules by changing this value. 0 is Default
		** It can be changed dynamically, per transaction, if you wish.  See the Sage Pay Direct Protocol document */
		if ($strTransactionType!=="AUTHENTICATE") $strPost=$strPost . "&ApplyAVSCV2=0";
	
		// Send the IP address of the person entering the card details
		$strPost=$strPost . "&ClientIPAddress=" . $_SERVER['REMOTE_ADDR'];

		/* Allow fine control over 3D-Secure checks and rules by changing this value. 0 is Default **
		** It can be changed dynamically, per transaction, if you wish.  See the Sage Pay Direct Protocol document */
		$strPost=$strPost . "&Apply3DSecure=0";
		
		/* Send the account type to be used for this transaction.  Web sites should us E for e-commerce **
		** If you are developing back-office applications for Mail Order/Telephone order, use M **
		** If your back office application is a subscription system with recurring transactions, use C **
		** Your Sage Pay account MUST be set up for the account type you choose.  If in doubt, use E **/
		$strPost=$strPost . "&AccountType=E";

		/* The full transaction registration POST has now been built **
		** Send the post to the target URL
		** if anything goes wrong with the connection process:
		** - $arrResponse["Status"] will be 'FAIL';
		** - $arrResponse["StatusDetail"] will be set to describe the problem 
		** Data is posted to strPurchaseURL which is set depending on whether you are using SIMULATOR, TEST or LIVE */
		$arrResponse = requestPost($strPurchaseURL, $strPost);
	
		/* Analyse the response from Sage Pay Direct to check that everything is okay
		** Registration results come back in the Status and StatusDetail fields */
		$strStatus=$arrResponse["Status"];
		$strStatusDetail=$arrResponse["StatusDetail"];
						
		if ($strStatus=="3DAUTH") 
		{
			/* This is a 3D-Secure transaction, so we need to redirect the customer to their bank
			** for authentication.  First get the pertinent information from the response */
			$strMD=$arrResponse["MD"];
			$strACSURL=$arrResponse["ACSURL"];
			$strPAReq=$arrResponse["PAReq"];
			$strPageState="3DRedirect";
		}            
		elseif ($strStatus=="PPREDIRECT") 
		{ 
            /* The customer needs to be redirected to a PayPal URL as PayPal was chosen as a card type or
            ** payment method and PayPal is active for your account. A VPSTxId and a PayPalRedirectURL are
            ** returned in this response so store the VPSTxId in your database now to match to the response
            ** after the customer is redirected to the PayPalRedirectURL to go through PayPal authentication */
            $strPayPalRedirectURL=$arrResponse["PayPalRedirectURL"];
            $strVPSTxId=$arrResponse["VPSTxId"];
            $strPageState="PayPalRedirect";

            // Update the current order in the database to store the newly acquired VPSTxId 
            $strSQL="UPDATE tblOrders SET VPSTxId='" . mysql_real_escape_string($strVPSTxId) . "' WHERE VendorTxCode='" . mysql_real_escape_string($strVendorTxCode) . "'";
			mysql_query($strSQL) or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
			$strSQL="";
            
            // Redirect customer to go through PayPal Authentication
			ob_end_flush();
			redirect($strPayPalRedirectURL);
			exit();
		}
		else
		{
			/* If this isn't 3D-Auth, then this is an authorisation result (either successful or otherwise) **
			** Get the results form the POST if they are there */
			$strVPSTxId=$arrResponse["VPSTxId"];
			$strSecurityKey=$arrResponse["SecurityKey"];
			$strTxAuthNo=$arrResponse["TxAuthNo"];
			$strAVSCV2=$arrResponse["AVSCV2"];
			$strAddressResult=$arrResponse["AddressResult"];
			$strPostCodeResult=$arrResponse["PostCodeResult"];
			$strCV2Result=$arrResponse["CV2Result"];
			$str3DSecureStatus=$arrResponse["3DSecureStatus"];
			$strCAVV=$arrResponse["CAVV"];
					
			// Update the database and redirect the user appropriately
			if ($strStatus=="OK")
				$strDBStatus="AUTHORISED - The transaction was successfully authorised with the bank.";
			elseif ($strStatus=="MALFORMED")
				$strDBStatus="MALFORMED - The StatusDetail was:" . mysql_real_escape_string(substr($strStatusDetail,0,255));
			elseif ($strStatus=="INVALID")
				$strDBStatus="INVALID - The StatusDetail was:" . mysql_real_escape_string(substr($strStatusDetail,0,255));
			elseif ($strStatus=="NOTAUTHED")
				$strDBStatus="DECLINED - The transaction was not authorised by the bank.";
			elseif ($strStatus=="REJECTED")
				$strDBStatus="REJECTED - The transaction was failed by your 3D-Secure or AVS/CV2 rule-bases.";
			elseif ($strStatus=="AUTHENTICATED")
				$strDBStatus="AUTHENTICATED - The transaction was successfully 3D-Secure Authenticated and can now be Authorised.";
			elseif ($strStatus=="REGISTERED")
				$strDBStatus="REGISTERED - The transaction was could not be 3D-Secure Authenticated, but has been registered to be Authorised.";
			elseif ($strStatus=="ERROR")
				$strDBStatus="ERROR - There was an error during the payment process.  The error details are: " . mysql_real_escape_string($strStatusDetail);
			else
				$strDBStatus="UNKNOWN - An unknown status was returned from Sage Pay.  The Status was: " . mysql_real_escape_string($strStatus) . ", with StatusDetail:" . mysql_real_escape_string($strStatusDetail);

			// Update our database with the results from the Notification POST
			$strSQL="UPDATE tblOrders set Status='" . $strDBStatus . "'";
			if (strlen($strVPSTxId)>0) $strSQL=$strSQL . ",VPSTxId='" . mysql_real_escape_string($strVPSTxId) . "'";
			if (strlen($strSecurityKey)>0) $strSQL=$strSQL . ",SecurityKey='" . mysql_real_escape_string($strSecurityKey) . "'";
			if (strlen($strTxAuthNo)>0) $strSQL=$strSQL . ",TxAuthNo=" . mysql_real_escape_string($strTxAuthNo);
			if (strlen($strAVSCV2)>0) $strSQL=$strSQL . ",AVSCV2='" . mysql_real_escape_string($strAVSCV2) . "'";
			if (strlen($strAddressResult)>0) $strSQL=$strSQL . ",AddressResult='" . mysql_real_escape_string($strAddressResult) . "'";
			if (strlen($strPostCodeResult)>0) $strSQL=$strSQL . ",PostCodeResult='" . mysql_real_escape_string($strPostCodeResult) . "'";
			if (strlen($strCV2Result)>0) $strSQL=$strSQL . ",CV2Result='" . mysql_real_escape_string($strCV2Result) . "'";
			if (strlen($strGiftAid)>0) $strSQL=$strSQL . ",GiftAid=" . mysql_real_escape_string($strGiftAid);
			if (strlen($str3DSecureStatus)>0) $strSQL=$strSQL . ",ThreeDSecureStatus='" . mysql_real_escape_string($str3DSecureStatus) . "'";
			if (strlen($strCAVV)>0) $strSQL=$strSQL . ",CAVV='" . mysql_real_escape_string($strCAVV) . "'";
			if (strlen($strDBStatus)>0) $strSQL=$strSQL . ",Status='" . mysql_real_escape_string($strDBStatus) . "'";
			$strSQL=$strSQL . " where VendorTxCode='" . mysql_real_escape_string($strVendorTxCode) . "'";

			mysql_query($strSQL) or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');

			// Work out where to send the customer
			$_SESSION["VendorTxCode"]=$strVendorTxCode;
			if (($strStatus=="OK")||($strStatus=="AUTHENTICATED")||($strStatus=="REGISTERED"))
				$strCompletionURL="orderSuccessful.php";
			else {
				$strCompletionURL="orderFailed.php";
				$strPageError=$strDBStatus;
			}

			// Finally, if we're in LIVE then go stright to the success page
			//In other modes, we allow this page to display and ask for Proceed to be clicked
			if ($strConnectTo=="LIVE") {
				ob_end_flush();
				redirect($strCompletionURL);
				exit();
			}
		}
	}
}

?>
<html>
<head>
	<title>Direct PHP Kit Transaction Registration Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/directKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Transaction Registration Page</div>
		<? 
		if ($strPageState=="Payment") 
		{
			//We need the customer to enter their card details, so display an entry page for them
			if (strlen($strPageError)>0) {
			?>
			<div class="errorheader">Input Validation Error<br>
			  	<span class="warning"><? echo $strPageError ?></span>
			</div>
			<?
			}
			?>
			<form name="storeform" method="post" action="transactionRegistration.php">
			<input type="hidden" name="navigate" value="" />
			<input type="hidden" name="PageState" value="CardDetails">
			<table class="formTable">
				<tr>
					<td colspan="2"><div class="subheader">Enter Card Details</div></td>
				</tr>
				<tr> 
					<td class="fieldLabel">Card Type:</td>
					<td class="fieldData">
						<SELECT NAME="CardType" onchange="cardTypeChanged(this);">
						<option value="VISA" <?    if ($strCardType=="VISA")    echo " SELECTED "; ?> >VISA Credit</option>
						<option value="DELTA" <?   if ($strCardType=="DELTA")   echo " SELECTED "; ?> >VISA Debit</option>
						<option value="UKE" <?     if ($strCardType=="UKE")     echo " SELECTED "; ?> >VISA Electron</option>
						<option value="MC" <?      if ($strCardType=="MC")      echo " SELECTED "; ?> >MasterCard</option>
						<option value="MAESTRO" <? if ($strCardType=="MAESTRO") echo " SELECTED "; ?> >Maestro</option>
						<option value="AMEX" <?    if ($strCardType=="AMEX")    echo " SELECTED "; ?> >American Express</option>
						<option value="DC" <?      if ($strCardType=="DC")      echo " SELECTED "; ?> >Diner's Club</option>
						<option value="JCB" <?     if ($strCardType=="JCB")     echo " SELECTED "; ?> >JCB Card</option>
						<option value="LASER" <?   if ($strCardType=="LASER")   echo " SELECTED "; ?> >Laser</option>
						<option value="PAYPAL" <?  if ($strCardType=="PAYPAL")  echo " SELECTED "; ?> >PayPal</option>
						</SELECT>
						<script>
						    function cardTypeChanged(selectObject) 
						    {
						        if(selectObject.value=='PAYPAL') {
						            var sDisabledBGColour = "#DDDDDD";
						            document.storeform.CardHolder.value='';
						            document.storeform.CardHolder.style.background=sDisabledBGColour;
			                        document.storeform.CardHolder.disabled = true;
						            document.storeform.CardNumber.value='';
						            document.storeform.CardNumber.style.background=sDisabledBGColour;
			                        document.storeform.CardNumber.disabled = true;
						            document.storeform.StartDate.value='';
						            document.storeform.StartDate.style.background=sDisabledBGColour;
			                        document.storeform.StartDate.disabled = true;
						            document.storeform.ExpiryDate.value='';
						            document.storeform.ExpiryDate.style.background=sDisabledBGColour;
			                        document.storeform.ExpiryDate.disabled = true;
						            document.storeform.IssueNumber.value='';
						            document.storeform.IssueNumber.style.background=sDisabledBGColour;
			                        document.storeform.IssueNumber.disabled = true;
						            document.storeform.CV2.value='';
						            document.storeform.CV2.style.background=sDisabledBGColour;
			                        document.storeform.CV2.disabled = true;
						            alert('You just selected a payment method of PayPal so card details will not be required here.\n\nAfter clicking \'Proceed\' you will be securely redirected to the PayPal website to authorise your details.');
			                    } else {
			                        document.storeform.CardHolder.disabled = false;
						            document.storeform.CardHolder.style.background = "";
			                        document.storeform.CardNumber.disabled = false;
						            document.storeform.CardNumber.style.background = "";
			                        document.storeform.StartDate.disabled = false;
						            document.storeform.StartDate.style.background = "";
			                        document.storeform.ExpiryDate.disabled = false;
						            document.storeform.ExpiryDate.style.background = "";
			                        document.storeform.IssueNumber.disabled = false;
						            document.storeform.IssueNumber.style.background = "";
			                        document.storeform.CV2.disabled = false;
						            document.storeform.CV2.style.background = "";
						        }
						    }
						</script>
				  	   	&nbsp;<font size="1">(Edit to those card you can accept)</font>
					</td>
				</tr>
				<tr> 
					<td class="fieldLabel">Card Holder Name:</td>
					<td class="fieldData"><input name="CardHolder" type="text" value="<? echo $strCardHolder ?>" size="25" maxlength="50"></td>
				</tr>
				<tr> 
					<td class="fieldLabel">Card Number:</td>
			  		<td class="fieldData"><input name="CardNumber" type="text" value="<? echo $strCardNumber ?>" size="25" maxlength="24">
					&nbsp;<font size="1">(With no spaces or separators)</font></td>
				</tr>
				<tr> 
					<td class="fieldLabel">Start Date:</td>
			  		<td class="fieldData"><input name="StartDate" type="text" value="<? echo $strStartDate ?>" size="5" maxlength="4">
				   	&nbsp;<font size="1">(Where available. Use MMYY format  e.g. 0207)</font></td>
				</tr>
				<tr> 
					<td class="fieldLabel">Expiry Date:</td>
			  		<td class="fieldData"><input name="ExpiryDate" type="text" value="<? echo $strExpiryDate ?>" size="5" maxlength="4">
				   	&nbsp;<font size="1">(Use MMYY format with no / or - separators e.g. 1109)</font></td>
				</tr>
				<tr> 
					<td class="fieldLabel">Issue Number:</td>
			  		<td class="fieldData"><input name="IssueNumber" type="text" value="<? echo $strIssueNumber ?>" size="5" maxlength="2">
				   	&nbsp;<font size="1">(Older Switch cards only. 1 or 2 digits 
				  	as printed on the card)</font></td>
				</tr>
				<tr> 
					<td class="fieldLabel">Card Verification Value:</td>
			  		<td class="fieldData"><input name="CV2" type="text" value="" size="5" maxlength="4">
				   	&nbsp;<font size="1">(Additional 3 digits on card signature strip, 4 on Amex cards)</font></td>
				</tr>
			</table>
            <div class="greyHzShadeBar">&nbsp;</div>
			<div class="formFooter">
				<a href="javascript:submitForm('storeform','back');" title="Go back to the order confirmation page" style="float: left;"><img src="images/back.gif" alt="Go back to the order confirmation page" border="0"></a>
				<a href="javascript:submitForm('storeform','proceed');" title="Proceed to the completion screens" style="float: right;"><img src="images/proceed.gif" alt="Proceed to the completion screens" border="0"></a>
			</div>
			</form><?
		}						
		elseif ($strPageState=="3DRedirect") 
		{ 
			//A 3D-Auth response has been returned, so show the bank page inline if possible, or redirect to it otherwise
			?>
            <table class="formTable">
            	<tr>
					<td><div class="subheader">3D-Secure Authentication with your Bank</div></td>
              	</tr>
              	<tr>
                	<td>
						<table class="formTable">
							<tr>
								<td width="80%">
									<p>To increase the security of Internet transactions Visa and Mastercard have introduced 3D-Secure (like an online version of Chip and PIN). <br>
							  			<br>
						    			You have chosen to use a card that is part of the 3D-Secure scheme, so you will need to authenticate yourself with your bank in the section below.
						    		</p>
						    	</td>
								<td width="20%" align="center"><img src="images/vbv_logo_small.gif" alt="Verified by Visa"><BR><BR><img src="images/mcsc_logo.gif" alt="MasterCard SecureCode"></td>
							</tr>
						</table>
						<div class="greyHzShadeBar">&nbsp;</div>
					</td>
              	</tr>
			  	<tr>
                	<td valign="top">
                	<?
					// Attempt to set up an inline frame here.  If we can't, set up a standard full page redirection
					$_SESSION["MD"]=$strMD;
					$_SESSION["PAReq"]=$strPAReq;
					$_SESSION["ACSURL"]=$strACSURL;
					$_SESSION["VendorTxCode"]=$strVendorTxCode;
					
					?>
					<IFRAME SRC="3DRedirect.php" NAME="3DIFrame" WIDTH="100%" HEIGHT="500" FRAMEBORDER="0">
					<!--Non-IFRAME browser support-->
					<SCRIPT LANGUAGE="Javascript"> function OnLoadEvent() { document.form.submit(); }</SCRIPT>
					<html><head><title>3D Secure Verification</title></head>
						<body OnLoad="OnLoadEvent();">
						<FORM name="form" action="<? echo $strACSURL ?>" method="POST">
						<input type="hidden" name="PaReq" value="<? echo $strPAReq ?>"/>
						<input type="hidden" name="TermUrl" value="<? echo $strYourSiteFQDN . $strVirtualDir . "/3DCallback.php?VendorTxCode=" . $strVendorTxCode ?>"/>
						<input type="hidden" name="MD" value="<? echo $strMD ?>"/>

					<NOSCRIPT> 
					<center><p>Please click button below to Authenticate your card</p><input type="submit" value="Go"/></p></center>
					</NOSCRIPT>
					</form></body></html>
					</IFRAME>
					</td>
			  	</tr>
			</table>
            <div class="greyHzShadeBar">&nbsp;</div><?
		}
		else // The customer has already entered their card details and we're displaying the result
		{
			if (strlen($strPageError)==0) 
			{ 
				//There are no errors to display, so show the detail of the POST to Sage Pay Direct							
		  		echo
				"<p>This page shows the contents of the POST sent to Sage Pay Direct (based on your selections on the previous screens)
		   		and the response sent back by the system. Because you are in SIMULATOR mode, you are seeing this information
		    	and having to click Proceed to continue to the payment pages. In LIVE mode, the POST and redirect 
				happen invisibly, with no information sent to the browser and no user involvement.</p>";
			}
			else 
			{
				//An error occurred during transaction registration. Show the details here
				echo
				"<p>A problem occurred whilst attempting to register this transaction with the Sage Pay systems.
			 	The details of the error are shown below. This information is provided for your own debugging 
			 	purposes and especially once LIVE you should avoid displaying this level of detail to your customers. 
			 	Instead you should modify the transactionRegistration.php page to automatically handle these errors and 
			 	redirect your customer appropriately (e.g. to an error reporting page, or alternative customer 
			 	services number to offline payment)</p>";
			}
			?>
			<div class="greyHzShadeBar">&nbsp;</div>
			<div class="<?
				  	if ($strStatus=="OK" || $strStatus=="AUTHENTICATED" || $strStatus=="REGISTERED")
						echo "infoheader"; 
					else 
						echo "errorheader";
					?>">Sage Pay Direct returned a Status of <? echo $strStatus ?><br>
				<span class="warning" ><? echo $strPageError ?></span>
			</div>
			<?			  
			if ($strConnectTo!=="LIVE") 
			{ 
				//NEVER show this level of detail when the account is LIVE 
			?>
			<table class="formTable">
				<tr>
				  <td colspan="2"><div class="subheader">Post Sent to Sage Pay Direct</div></td>
				</tr>
				<tr>
				  <td colspan="2" class="code" style="word-break: break-all; word-wrap: break-word;"><? echo $strPost ?></td>
				</tr>
				<tr>
				  <td colspan="2"><div class="subheader">Reply from Sage Pay Direct</div></td>
				</tr>
				<tr>
				  <td colspan="2" class="code" style="word-break: break-all; word-wrap: break-word;"><? echo $_SESSION["rawresponse"] ?></td>
				</tr>
				<tr>
				  <td colspan="2"><div class="subheader">Order Details stored in your Database</div></td>
				</tr>
				<tr>
					<td class="fieldLabel">VendorTxCode:</td>
					<td class="fieldData"><? echo $strVendorTxCode ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">VPSTxId:</td>
					<td class="fieldData"><? echo $strVPSTxId ?>&nbsp;</td>
				</tr>
				<?
				if (strlen($strSecurityKey)>0) 
				{
				?>
				<tr>
					<td class="fieldLabel">SecurityKey:</td>
					<td class="fieldData"><? echo $strSecurityKey ?>&nbsp;</td>
				</tr>
				<?
				}
				if (strlen($strTxAuthNo)>0) 
				{
				?>
				<tr>
					<td class="fieldLabel">TxAuthNo:</td>
					<td class="fieldData"><? echo $strTxAuthNo ?>&nbsp;</td>
				</tr>
				<?
				}
				if (strlen($str3DSecureStatus)>0) 
				{
				?>
				<tr>
					<td class="fieldLabel">3D-Secure Status:</td>
					<td class="fieldData"><? echo $str3DSecureStatus ?>&nbsp;</td>
				</tr>
				<?
				}
				if (strlen($strCAVV)>0) 
				{
				?>
				<tr>
					<td class="fieldLabel">CAVV:</td>
					<td class="fieldData"><? echo $strCAVV ?>&nbsp;</td>
				</tr>
				<?
				}
				?>
				<tr>
					<td class="fieldLabel">Order Total:</td>
					<td class="fieldData"><? echo number_format($sngTotal,2) ?></td>
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
								
							$strSQL = "";
							$rsPrimary = "";	
							?>	
						</table>
					</td>
				</tr>
			</table>
			<div class="greyHzShadeBar">&nbsp;</div>
			<form name="customerform" action="transactionRegistration.php" method="POST">
			<input type="hidden" name="navigate" value="" />
			<input type="hidden" name="CompletionURL" value="<? echo $strCompletionURL ?>">
			<input type="hidden" name="PageState" value="Completion">
			<div class="formFooter">
				<a href="javascript:submitForm('customerform','back');" title="Go back to the order confirmation page" style="float: left;"><img src="images/back.gif" alt="Go back to the order confirmation page" border="0"></a>
				<a href="javascript:submitForm('customerform','proceed');" title="Proceed to the completion screens" style="float: right;"><img src="images/proceed.gif" alt="Proceed to the completion screens" border="0"></a>
			</div>
			</form>
			<?
			}
		}
		?>
		</div>
	</div>
</body>
</html>




