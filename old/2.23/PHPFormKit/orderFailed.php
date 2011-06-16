<?
include("includes.php");
session_start(); 

/**************************************************************************************************
* Form PHP Kit Order Successful Page
***************************************************************************************************

***************************************************************************************************
* Change history
* ==============

* 27/05/2009 - Simon Wolfe - Updated for AES encryption and XSS fixes
* 10/02/2009 - Simon Wolfe - Updated for protocol 2.23
* 14/09/2007 - Mat Peck - New kit version
****************************************************************************************************
* Description
* ===========

* This is a placeholder for your Successful Order Completion Page.  It retrieves the VendorTxCode
* from the crypt string and displays the transaction results on the screen.  You wouldn't display 
* all the information in a live application, but during development this page shows everything
* sent back in the confirmation screen.
****************************************************************************************************/

// Check for the proceed button click, and if so, go to the buildOrder page
if ($_REQUEST["navigate"]=="proceed") {
	ob_end_flush();
	// Redirect to next page
	redirect("welcome.php");
}

// Now check we have a Crypt field passed to this page 
$strCrypt=$_REQUEST["crypt"];
if (strlen($strCrypt)==0) {
	ob_end_flush();
	redirect("welcome.php");
}

// Now decode the Crypt field and extract the results
$strDecoded=decodeAndDecrypt($strCrypt);
$values = getToken($strDecoded);
// Split out the useful information into variables we can use
$strStatus=$values['Status'];
$strStatusDetail=$values['StatusDetail'];
$strVendorTxCode=$values["VendorTxCode"];
$strVPSTxId=$values["VPSTxId"];
$strTxAuthNo=$values["TxAuthNo"];
$strAmount=$values["Amount"];
$strAVSCV2=$values["AVSCV2"];
$strAddressResult=$values["AddressResult"];
$strPostCodeResult=$values["PostCodeResult"];
$strCV2Result=$values["CV2Result"];
$strGiftAid=$values["GiftAid"];
$str3DSecureStatus=$values["3DSecureStatus"];
$strCAVV=$values["CAVV"];
$strCardType=$values["CardType"];
$strLast4Digits=$values["Last4Digits"];
$strAddressStatus=$values["AddressStatus"]; // PayPal transactions only
$strPayerStatus=$values["PayerStatus"];     // PayPal transactions only

// Determine the reason this transaction was unsuccessful
if ($strStatus=="NOTAUTHED")
	$strReason="You payment was declined by the bank.  This could be due to insufficient funds, or incorrect card details.";
else if ($strStatus=="ABORT")
	$strReason="You chose to Cancel your order on the payment pages.  If you wish to change your order and resubmit it you can do so here. If you have questions or concerns about ordering online, please contact us at [your number].";
else if ($strStatus=="REJECTED") 
	$strReason="Your order did not meet our minimum fraud screening requirements. If you have questions about our fraud screening rules, or wish to contact us to discuss this, please call [your number].";
else if ($strStatus=="INVALID" or $strStatus=="MALFORMED")
	$strReason="We could not process your order because we have been unable to register your transaction with our Payment Gateway. You can place the order over the telephone instead by calling [your number].";
else if ($strStatus=="ERROR")
	$strReason="We could not process your order because our Payment Gateway service was experiencing difficulties. You can place the order over the telephone instead by calling [your number].";
else
	$strReason="The transaction process failed. Please contact us with the date and time of your order and we will investigate.";

?>
<html>
<head>
	<title>Form PHP Kit Order Failed Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/formKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Your order has NOT been successful</div>
            <p>
                The Form transaction did not completed successfully and the customer has been returned to this completion page for the following reason: <br>
                <span class="warning"><strong><? echo htmlentities($strReason) ?></strong></span><br>
                <br>
                The order number, for your customer's reference is: <strong><? echo htmlentities($strVendorTxCode) ?></strong><br>
                <br>
                They should quote this in all correspondence with you, and likewise you should use this reference when sending queries to Sage Pay about this transaction (along with your Vendor Name).<br>
                <br>
                The table below shows everything sent back from Form about this order.  You would not normally show this level of detail to your customers, but it is useful during development.  You may wish to store this information in a local database if you have one.<br>
                <br>
                You can customise this page to suggest alternative payment options, direct the customer to call you, or simply present a failure notice, whatever is appropriate for your application.  The code is in orderFailed.php.
            </p>
            <div class="greyHzShadeBar">&nbsp;</div>
            <? if ($strConnectTo!=="LIVE") {
				echo "<table class=\"formTable\">";
				echo "<tr>";
				echo "<td colspan=\"2\"><div class=\"subheader\">Details sent back by Form</div></td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class=\"fieldLabel\">VendorTxCode:</td>";
				echo "<td class=\"fieldData\">" . htmlentities($strVendorTxCode) . "&nbsp;</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class=\"fieldLabel\">Status:</td>";
				echo "<td class=\"fieldData\">" . htmlentities($strStatus) . "&nbsp;</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class=\"fieldLabel\">StatusDetail:</td>";
				echo "<td class=\"fieldData\">" . htmlentities($strStatusDetail) . "&nbsp;</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class=\"fieldLabel\">Amount:</td>";
				echo "<td class=\"fieldData\">" . htmlentities($strAmount) . "&nbsp;" . htmlentities($strCurrency) . "&nbsp;</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class=\"fieldLabel\">VPSTxId:</td>";
				echo "<td class=\"fieldData\">" . htmlentities($strVPSTxId) . "&nbsp;</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class=\"fieldLabel\">VPSAuthCode (TxAuthNo):</td>";
				echo "<td class=\"fieldData\">" . htmlentities($strTxAuthNo) . "&nbsp;</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class=\"fieldLabel\">AVSCV2 Results:</td>";
				echo "<td class=\"fieldData\">" . htmlentities($strAVSCV2) . "<span class=\"smalltext\"> - Address:" . htmlentities($strAddressResult) . ", Post Code:" . htmlentities($strPostCodeResult) . ", CV2:" . htmlentities($strCV2Result) . "</span></td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class=\"fieldLabel\">Gift Aid Transaction?:</td>";
				echo "<td class=\"fieldData\">"; if ($strGiftAid=="1") echo "Yes"; else echo "No";
				echo "</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class=\"fieldLabel\">3D-Secure Status:</td>";
				echo "<td class=\"fieldData\">" . htmlentities($str3DSecureStatus) ."&nbsp;</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class=\"fieldLabel\">CAVV:</td>";
				echo "<td class=\"fieldData\">" . htmlentities($strCAVV) . "&nbsp;</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class=\"fieldLabel\">CardType:</td>";
				echo "<td class=\"fieldData\">" . htmlentities($strCardType) . "</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class=\"fieldLabel\">Last4Digits:</td>";
				echo "<td class=\"fieldData\">" . htmlentities($strLast4Digits) . "</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class=\"fieldLabel\">AddressStatus:</td>";
				echo "<td class=\"fieldData\"><span style=\"float:right; font-size: smaller;\">&nbsp;*PayPal transactions only</span>" . htmlentities($strAddressStatus) . "</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td class=\"fieldLabel\">PayerStatus:</td>";
				echo "<td class=\"fieldData\"><span style=\"float:right; font-size: smaller;\">&nbsp;*PayPal transactions only</span>" . htmlentities($strPayerStatus) . "</td>";
				echo "</tr>";
				echo "</table>";
                echo "<div class=\"greyHzShadeBar\">&nbsp;</div>";
				}
			?>
    <div class="formFooter">
        <form name="completionform" method="POST" action="orderFailed.php">
            <input type="hidden" name="navigate" value="" />
            <div style="float: left">Click Proceed to go back to the Home Page to start another transaction</div>
            <a href="javascript:submitForm('completionform','proceed');" title="Click to go back to the welcome page" style="float: right">
                <img src="images/proceed.gif" alt="Click to go back to the welcome page" border="0" />
            </a>
        </form>
    </div>
</div>
</div>
</body>
</html>
