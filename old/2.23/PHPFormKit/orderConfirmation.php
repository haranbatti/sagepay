<?
include("includes.php");
session_start(); 
/**************************************************************************************************
* Form PHP Kit Order Confirmation Page
***************************************************************************************************

***************************************************************************************************
* Change history
* ==============
*
* 27/05/2009 - Simon Wolfe - Updated for AES encryption and XSS fixes
* 10/02/2009 - Simon Wolfe - Updated for protocol 2.23
* 18/10/2007 - Nick Selby - New kit version
***************************************************************************************************
* Description
* ===========
*
* Displays a summary of the order items and customer details and builds the Form Crypt field
* that will be sent along with the user to the Sage Pay payment pages.  In SIMULATOR and TEST mode
* the decoded version of this field will be displayed on screen for you to check.
***************************************************************************************************

*** Check we have a cart in the session.  If not, go back to the buildOrder page to get one **/
$strCart=$_SESSION["strCart"];
if (strlen($strCart)==0) {
	ob_end_flush();
	redirect("buildOrder.php");
}

// Check we have a billing address in the session.  If not, go back to the customerDetails page to get one
if (strlen($_SESSION["strBillingAddress1"])==0) {
	ob_end_flush();
	redirect("customerDetails.php");
}

if ($_REQUEST["navigate"]=="back") {
	ob_end_flush();
	redirect("customerDetails.php");
}


//** Gather customer details from the session **
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

/** Okay, build the crypt field for Form using the information in our session **
*** First we need to generate a unique VendorTxCode for this transaction **
*** We're using VendorName, time stamp and a random element.  You can use different methods if you wish **
*** but the VendorTxCode MUST be unique for each transaction you send to Server **/
$strTimeStamp = date("ymdHis", time());
$intRandNum = rand(0,32000)*rand(0,32000);
$strVendorTxCode= $strVendorName . "-" . $strTimeStamp . "-" . $intRandNum;
					
/** Now to calculate the transaction total based on basket contents.  For security **
*** we recalculate it here rather than relying on totals stored in the session or hidden fields **
*** We'll also create the basket contents to pass to Form. See the Form Protocol for **
*** the full valid basket format.  The code below converts from our "x of y" style into **
*** the system basket format (using a 17.5% VAT calculation for the tax columns) **/

$sngTotal=0.0;
$strThisEntry=$strCart;
$strBasket="";
$iBasketItems=0;
							
while (strlen($strThisEntry)>0) {
	// Extract the Quantity and Product from the list of "x of y," entries in the cart
	$iQuantity=cleanInput(substr($strThisEntry,0,1), CLEAN_INPUT_FILTER_NUMERIC);
	$iProductId=substr($strThisEntry,strpos($strThisEntry,",")-1,1);
	// Add another item to our Form basket
	$iBasketItems=$iBasketItems+1;
		
	$sngTotal=$sngTotal + $iQuantity * $arrProducts[$iProductId-1][1];
	$strBasket=$strBasket . ":" . $arrProducts[$iProductId-1][0] . ":" . $iQuantity;
	$strBasket=$strBasket . ":" . number_format($arrProducts[$iProductId-1][1] / 1.2,2); /** Price ex-Vat **/
	$strBasket=$strBasket . ":" . number_format($arrProducts[$iProductId-1][1] / 6,2); /** VAT component **/
	$strBasket=$strBasket . ":" . number_format($arrProducts[$iProductId-1][1],2); /** Item price **/
	$strBasket=$strBasket . ":" . number_format($arrProducts[$iProductId-1][1]*$iQuantity,2); /** Line total **/			
						
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
	
// Now to build the Form crypt field.  For more details see the Form Protocol 2.23 
$strPost="VendorTxCode=" . $strVendorTxCode; /** As generated above **/

// Optional: If you are a Sage Pay Partner and wish to flag the transactions with your unique partner id, it should be passed here
if (strlen($strPartnerID) > 0)
    $strPost=$strPost . "&ReferrerID=" . $strPartnerID;

$strPost=$strPost . "&Amount=" . number_format($sngTotal,2); // Formatted to 2 decimal places with leading digit
$strPost=$strPost . "&Currency=" . $strCurrency;
// Up to 100 chars of free format description
$strPost=$strPost . "&Description=The best DVDs from " . $strVendorName;

/* The SuccessURL is the page to which Form returns the customer if the transaction is successful 
** You can change this for each transaction, perhaps passing a session ID or state flag if you wish */
$strPost=$strPost . "&SuccessURL=" . $strYourSiteFQDN . $strVirtualDir . "/orderSuccessful.php";

/* The FailureURL is the page to which Form returns the customer if the transaction is unsuccessful
** You can change this for each transaction, perhaps passing a session ID or state flag if you wish */
$strPost=$strPost . "&FailureURL=" . $strYourSiteFQDN . $strVirtualDir . "/orderFailed.php";

// This is an Optional setting. Here we are just using the Billing names given.
$strPost=$strPost . "&CustomerName=" . $strBillingFirstnames . " " . $strBillingSurname;

/* Email settings:
** Flag 'SendEMail' is an Optional setting. 
** 0 = Do not send either customer or vendor e-mails, 
** 1 = Send customer and vendor e-mails if address(es) are provided(DEFAULT). 
** 2 = Send Vendor Email but not Customer Email. If you do not supply this field, 1 is assumed and e-mails are sent if addresses are provided. **/
if ($bSendEMail == 0)
    $strPost=$strPost . "&SendEMail=0";
else {
    
    if ($bSendEMail == 1) {
    	$strPost=$strPost . "&SendEMail=1";
    } else {
    	$strPost=$strPost . "&SendEMail=2";
    }
    
    if (strlen($strCustomerEMail) > 0)
        $strPost=$strPost . "&CustomerEMail=" . $strCustomerEMail;  // This is an Optional setting
    
    if (($strVendorEMail <> "[your e-mail address]") && ($strVendorEMail <> ""))
	    $strPost=$strPost . "&VendorEMail=" . $strVendorEMail;  // This is an Optional setting

    // You can specify any custom message to send to your customers in their confirmation e-mail here
    // The field can contain HTML if you wish, and be different for each order.  This field is optional
    $strPost=$strPost . "&eMailMessage=Thank you so very much for your order.";
}

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


$strPost=$strPost . "&Basket=" . $strBasket; // As created above 

// For charities registered for Gift Aid, set to 1 to display the Gift Aid check box on the payment pages
$strPost=$strPost . "&AllowGiftAid=0";
	
/* Allow fine control over AVS/CV2 checks and rules by changing this value. 0 is Default 
** It can be changed dynamically, per transaction, if you wish.  See the Server Protocol document */
if ($strTransactionType!=="AUTHENTICATE")
	$strPost=$strPost . "&ApplyAVSCV2=0";
	
/* Allow fine control over 3D-Secure checks and rules by changing this value. 0 is Default 
** It can be changed dynamically, per transaction, if you wish.  See the Form Protocol document */
$strPost=$strPost . "&Apply3DSecure=0";

// Encrypt the plaintext string for inclusion in the hidden field
$strCrypt = encryptAndEncode($strPost);

?>
<html>
<head>
	<title>Form PHP Kit Order Confirmation Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/formKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
    <script type="text/javascript" language="javascript" src="scripts/countrycodes.js"></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Order Confirmation Page  </div>
            <p>
                This page summarises the order details and customer information gathered on the previous screens.
                It is always a good idea to show your customers a page like this to allow them to go back and edit
                either basket or contact details.<br>
                <br>
                This page also creates the Form crypt field and creates a form to POST this information to
                the Sage Pay Gateway when the Proceed button is clicked. The code for this page can be found in
                orderConfirmation.php.<BR>
                <BR>
            <? if ($strConnectTo!=="LIVE") { ?>
                Because you are in <? echo $strConnectTo ?> mode, the unencrypted contents of the crypt field are also
                displayed below, allowing you to check the contents. When you are in Live mode, you will only
                see the order confirmation boxes.
            <? } else {?>
                Since you are in LIVE mode, clicking Proceed will register your transaction with Form
                and automatically redirect you to the payment page, or handle any registration errors. 
                The code to do this can be found in transactionRegistration.php";
            <?}?>
            </p>
            <div class="greyHzShadeBar">&nbsp;</div>
            <table class="formTable">
				<tr>
				  <td colspan="5">
                      <div class="subheader">Your Basket Contents</div>
                  </td>
				</tr>
				<tr class="greybar">
					<td width="17%" align="center">Image</td>
					<td width="45%" align="left">Title</td>
					<td width="15%" align="right">Price</td>
					<td width="8%" align="right">Quantity</td>
					<td width="15%" align="right">Total</td>
				</tr>
				<? 
				// Step through the basket contents and display the order
				$sngTotal=0.0;
				$strThisEntry=$strCart;
											
				while (strlen($strThisEntry)>0) {
					// Extract the quantity and Product from the list of "x of y," entries in the cart
					$iQuantity=cleanInput(substr($strThisEntry,0,1), CLEAN_INPUT_FILTER_NUMERIC);
					$iProductId=substr($strThisEntry,strpos($strThisEntry,",")-1,1);
					$strImageId = "00" . $iProductId;
					
					$sngTotal=$sngTotal + ($iQuantity * $arrProducts[$iProductId-1][1]);
					
					echo "<tr>";
					echo "<td align=\"center\"><img src=\"images/dvd" . htmlentities(substr($strImageId,strlen($strImageId)-2,2)) .  "small.gif\" alt=\"DVD box\"></td>";
					echo "<td align=\"left\">" . htmlentities($arrProducts[$iProductId-1][0]) ."</td>";
					echo "<td align=\"right\">" . htmlentities($arrProducts[$iProductId-1][1] . " " . $strCurrency) . "</td>";
					echo "<td align=\"right\">" . htmlentities($iQuantity) . "</td>";
					echo "<td align=\"right\">" . htmlentities(number_format($iQuantity * $arrProducts[$iProductId-1][1],2) . " " . $strCurrency) . "</td>";
					echo "</tr>";
						
					// Move to the next cart entry, if there is one
					$pos=strpos($strThisEntry,",");
					if ($pos==0) 
						$strThisEntry="";
					else
						$strThisEntry=substr($strThisEntry,$pos+1);

				}
				// We've been right through the cart, so add the delivery column, then display the total
				$sngTotal=$sngTotal + 1.50;	
				?>
				<tr>
					<td colspan="4" align="right">Delivery:</td>
					<td align="right"><? echo number_format(1.50,2) . " " . $strCurrency  ?></td>
				</tr>
				<tr>
					<td colspan="4" align="right"><strong>Total:</strong></td>
					<td align="right"><strong><? echo number_format($sngTotal,2) . " " . $strCurrency  ?></strong></td>
				</tr>
		    </table>
			<table class="formTable">
                <tr>
				  <td colspan="2">
                      <div class="subheader">Your Billing Details</div>
                  </td>
				</tr>
				<tr>
					<td class="fieldLabel">Name:</td>
                    <td class="fieldData">
                        <? echo htmlentities($strBillingFirstnames) ?>&nbsp;<? echo htmlentities($strBillingSurname) ?></td>
				</tr>
				<tr>
					<td class="fieldLabel">Address Details:</td>
                    <td class="fieldData">
                        <? echo htmlentities($strBillingAddress1)  ?><BR>
					    <? if (strlen($strBillingAddress2)>0) echo htmlentities($strBillingAddress2) . "<BR>"; ?>
					    <? echo htmlentities($strBillingCity)  ?>&nbsp;
					    <? if (strlen($strBillingState)>0) echo htmlentities($strBillingState); ?><BR>
					    <? echo htmlentities($strBillingPostCode);  ?><BR>
					    <script type="text/javascript" language="javascript">
					        document.write( getCountryName( "<? echo htmlentities($strBillingCountry); ?>" ));
					    </script>
					</td>
				</tr>
				<tr>
					<td class="fieldLabel">Phone Number:</td>
                    <td class="fieldData">
                        <? echo htmlentities($strBillingPhone); ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">e-Mail Address:</td>
                    <td class="fieldData">
                        <? echo htmlentities($strCustomerEMail); ?>&nbsp;</td>
				</tr>
			</table>
			<table class="formTable">
                <tr>
				  <td colspan="2">
                      <div class="subheader">Your Delivery Details</div>
                  </td>
				</tr>
				<tr>
					<td class="fieldLabel">Name:</td>
                    <td class="fieldData">
                        <? echo htmlentities($strDeliveryFirstnames); ?>&nbsp;<? echo htmlentities($strDeliverySurname); ?></td>
				</tr>
				<tr>
					<td class="fieldLabel">Address Details:</td>
                    <td class="fieldData">
                        <? echo htmlentities($strDeliveryAddress1)  ?><BR>
					    <? if (strlen($strDeliveryAddress2)>0) echo htmlentities($strDeliveryAddress2) . "<BR>"; ?>
					    <? echo htmlentities($strDeliveryCity); ?>&nbsp;
					    <? if (strlen($strDeliveryState)>0) echo htmlentities($strDeliveryState); ?><BR>
					    <? echo htmlentities($strDeliveryPostCode); ?><BR>
					    <script type="text/javascript" language="javascript">
					        document.write( getCountryName( "<? echo htmlentities($strDeliveryCountry);  ?>" ));
					    </script>
					</td>
				</tr>
				<tr>
					<td class="fieldLabel">Phone Number:</td>
                    <td class="fieldData">
                        <? echo htmlentities($strDeliveryPhone); ?>&nbsp;</td>
				</tr>
			</table>
		  	<? if ($strConnectTo!=="LIVE") { 
			echo
				"<table class=\"formTable\">
					<tr>
						<td><div class=\"subheader\">Your Form Crypt Post Contents</div></td>
					</tr>
					<tr>
						<td><p>The text below shows the unencrypted contents of the Form
				  		Crypt field.  This will not be displayed in LIVE mode.  If you wish to view the encrypted and encoded
						contents view the source of this page and scroll to the bottom.  You'll find the submission FORM there.</p>
					</tr>
				  	<tr>
				  		<td style=\"word-wrap:break-word; word-break: break-all\" width=\"600\" class=\"code\">" . htmlentities($strPost) . "</td>
					</tr>
				</table>";
			}
			?>
            <div class="greyHzShadeBar">&nbsp;</div>
            <div class="formFooter">
			<table border="0" width="100%">
				<tr>
					<td width="50%" align="left">
					<form name="customerform" action="orderConfirmation.php" method="POST">
					<input type="hidden" name="navigate" value="" />
					<a href="javascript:submitForm('customerform','back');" title="Go back to the customer details page"><img src="images/back.gif" alt="Go back to the previous page" border="0" /></a>
					</form>
					</td>
					<td width="50%" align="right">
					<!-- ************************************************************************************* -->
					<!-- This form is all that is required to submit the payment information to the system -->
					<form action="<? echo $strPurchaseURL ?>" method="POST" id="SagePayForm" name="SagePayForm"> 
					<input type="hidden" name="navigate" value="" />
					<input type="hidden" name="VPSProtocol" value="<? echo $strProtocol ?>">
					<input type="hidden" name="TxType" value="<? echo $strTransactionType ?>">
					<input type="hidden" name="Vendor" value="<? echo $strVendorName ?>">
					<input type="hidden" name="Crypt" value="<? echo $strCrypt ?>">
					<a href="javascript:SagePayForm.submit();" title="Proceed to Form registration"><img src="images/proceed.gif" alt="Proceed to Form registration" border="0"></a>
					</form>
					<!-- ************************************************************************************* -->
					</td>
				</tr>
			</table>
            </div>
		</div>
	</div>
</body>
</html>
