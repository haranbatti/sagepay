<?
include("includes.php");

/**************************************************************************************************
* Sage Pay Direct PHP Kit Order Failed Page
***************************************************************************************************

***************************************************************************************************
* Change history
* ==============

* 02/04/2009 - Simon Wolfe - Updated UI for re-brand
* 11/02/2009 - Simon Wolfe - Updated for VSP protocol 2.23
* 18/12/2007 - Nick Selby - New PHP version adapted from ASP
****************************************************************************************************
* Description
* ===========

* This is a placeholder for your Failed Order Completion Page.  It retrieves the VendorTxCode
* from the crypt string and displays the transaction results on the screen.  You wouldn't display 
* all the information in a live application, but during development this page shows everything
* sent back in the confirmation screen.
****************************************************************************************************/

// Check for the proceed button click, and if so, go to the buildOrder page

if ($_REQUEST["navigate"]=="proceed") {
	ob_end_flush();
	session_destroy();
	// Redirect to next page
	redirect("welcome.php");
	exit();
}
if ($_REQUEST["navigate"]=="admin"){
	ob_end_flush();
	session_destroy();
	redirect("orderAdmin.php");
	exit();
}

//Now check we have a failure reason code passed to this page
$strVendorTxCode=$_SESSION["VendorTxCode"];
if (strlen($strVendorTxCode)==0){ 
	//No VendorTxCode, so take the customer to the home page
	ob_end_flush();
	session_destroy();
	redirect("welcome.php");
	exit();
}
else
{
	$strSQL = "SELECT * FROM tblOrders where VendorTxCode='" . $strVendorTxCode . "'";
	$rsPrimary = mysql_query($strSQL)
		or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');

	$row = mysql_fetch_array($rsPrimary);
	$strStatus=$row["Status"];
	
	//Work out what to tell the customer
	if (substr($strStatus,0,8)=="DECLINED")
		$strReason="You payment was declined by the bank.  This could be due to insufficient funds, or incorrect card details.";
	elseif (substr($strStatus,0,9)=="MALFORMED" || substr($strStatus,0,7)=="INVALID")
		$strReason="The Sage Pay Payment Gateway rejected some of the information provided without forwarding it to the bank.
		Please let us know about this error so we can determine the reason it was rejected. Please call [your number].";
	elseif (substr($strStatus,0,8)=="REJECTED")
		$strReason="Your order did not meet our minimum fraud screening requirements.
		If you have questions about our fraud screening rules, or wish to contact us to discuss this, please call [your number].";
	elseif (substr($strStatus,5)=="ERROR")
		$strReason="We could not process your order because our Payment Gateway service was experiencing difficulties. You can place the order over the telephone instead by calling [your number].";
	else
		$strReason="The transaction process failed.  We please contact us with the date and time of your order and we will investigate.";
}
?>

<html>
<head>
	<title>Direct Kit Order Failed Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/directKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
	<script type="text/javascript" language="javascript" src="scripts/countrycodes.js" ></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Your order has NOT been successful</div>
			<p>Your transaction was not successful for the following reason:<br>
			  <br>
			  <span class="warning"><strong><? echo $strReason ?></strong></span><br> 
			</p>
		<? 
		if (strlen($strVendorTxCode)>0)
		{
			echo "
			<p>The order number, for your customer's reference is: <strong>" . $strVendorTxCode . "</strong><br>
			  <br>
			  They should quote this in all correspondence with you, and likewise you should use this reference when sending queries to Sage Pay about this transaction (along with your Sage Pay Vendor Name).<br>
			  <br>
			  The table below shows everything in the database about this order.  You would not normally show this level of detail to your customers, but it is useful during development.<br>
			  <br>
			You can customise this page to offer alternative payment methods, links to customer support numbers, help and advice for online shopper, whatever is appropriate for your application.  The code is in orderFailed.php.
			</p>";
		}
		echo "
            <div class=\"greyHzShadeBar\">&nbsp;</div>";
				
		if ($strConnectTo!=="LIVE")
		{
			// NEVER show this level of detail when the account is LIVE
			$strSQL="SELECT * FROM tblOrders where VendorTxCode='" . mysql_real_escape_string($strVendorTxCode) . "'";
			//Execute the SQL command
			$rsPrimary = mysql_query($strSQL)
				or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
			$strSQL="";
			$row=mysql_fetch_array($rsPrimary);
										
			echo "
				<table class=\"formTable\">
					<tr>
						<td colspan=\"2\"><div class=\"subheader\">Order Details stored in your Database</div></td>
					</tr>
					<tr>
						<td class=\"fieldLabel\">VendorTxCode:</td>
						<td class=\"fieldData\">" . $strVendorTxCode . "</td>
					</tr>
					<tr>
						<td class=\"fieldLabel\">Transaction Type:</td>
						<td class=\"fieldData\">" . $row["TxType"] . "</td>
					</tr>
					<tr>
						<td class=\"fieldLabel\">Status:</td>
						<td class=\"fieldData\">" . $row["Status"] . "</td>
					</tr>
					<tr>
						<td class=\"fieldLabel\">Amount:</td>
						<td class=\"fieldData\">" . number_format($row["Amount"],2) . "&nbsp;" . $strCurrency . "</td>
					</tr>";
		?>
			    	<tr>
					    <td class="fieldLabel">Billing Name:</td>
					    <td class="fieldData"><? echo $row["BillingFirstnames"] . " " . $row["BillingSurname"] ?>&nbsp;</td>
				    </tr>
				    <tr>
					    <td class="fieldLabel">Billing Phone:</td>
					    <td class="fieldData"><? echo $row["BillingPhone"] ?>&nbsp;</td>
				    </tr>
				    <tr>
					    <td class="fieldLabel">Billing Address:</td>
					    <td class="fieldData">
				            <? echo $row["BillingAddress1"] ?><BR>
				            <? if (isset($row["BillingAddress2"]))  echo $row["BillingAddress2"] . "<BR>" ?>
				            <? echo $row["BillingCity"] ?>&nbsp;
				            <? if (isset($row["BillingState"]))  echo $row["BillingState"] ?><BR>
				            <? echo $row["BillingPostCode"] ?><BR>
				            <script type="text/javascript" language="javascript">
				                document.write( getCountryName( "<?  echo $row["BillingCountry"] ?>" ));
				            </script>
					    </td>
				    </tr>
				    <tr>
					    <td class="fieldLabel">Billing e-Mail:</td>
					    <td class="fieldData"><? echo $row["CustomerEMail"] ?>&nbsp;</td>
				    </tr>
				    <tr>
					    <td class="fieldLabel">Delivery Name:</td>
					    <td class="fieldData"><? echo $row["DeliveryFirstnames"] . " " . $row["DeliverySurname"] ?>&nbsp;</td>
				    </tr>
				    <tr>
					    <td class="fieldLabel">Delivery Address:</td>
					    <td class="fieldData">
				            <? echo $row["DeliveryAddress1"] ?><BR>
				            <? if (isset($row["DeliveryAddress2"])) echo $row["DeliveryAddress2"] . "<BR>" ?>
				            <? echo $row["DeliveryCity"] ?>&nbsp;
				            <? if (isset($row["DeliveryState"])) echo $row["DeliveryState"] ?><BR>
				            <? echo $row["DeliveryPostCode"] ?><BR>
				            <script type="text/javascript" language="javascript">
				                document.write( getCountryName( "<?  echo $row["DeliveryCountry"] ?>" ));
				            </script>
					    </td>
				    </tr>
				    <tr>
					    <td class="fieldLabel">Delivery Phone:</td>
					    <td class="fieldData"><? echo $row["DeliveryPhone"] ?>&nbsp;</td>
				    </tr>
		<?
		echo "
					<tr>
						<td class=\"fieldLabel\">VPSTxId:</td>
						<td class=\"fieldData\">" . $row["VPSTxId"] . "&nbsp;</td>
					</tr>
					<tr>
						<td class=\"fieldLabel\">SecurityKey:</td>
						<td class=\"fieldData\">" . $row["SecurityKey"] . "&nbsp;</td>
					</tr>
					<tr>
						<td class=\"fieldLabel\">VPSAuthCode (TxAuthNo):</td>
						<td class=\"fieldData\">" . $row["TxAuthNo"] . "&nbsp;</td>
					</tr>
					<tr>
						<td class=\"fieldLabel\">AVSCV2 Results:</td>
						<td class=\"fieldData\">" . $row["AVSCV2"] . "<span class=\"smalltext\"> - Address:" . $row["AddressResult"] . " 
						, Post Code:" . $row["PostCodeResult"] . ", CV2:" . $row["CV2Result"] . "</span></td>
					</tr>
					<tr>
						<td class=\"fieldLabel\">Gift Aid Transaction?:</td>
						<td class=\"fieldData\">";
						if ($row["GiftAid"]==1) { echo "Yes"; } else { echo "No"; } 
		echo "
						</td>
					</tr>
					<tr>
						<td class=\"fieldLabel\">3D-Secure Status:</td>
						<td class=\"fieldData\">" . $row["ThreeDSecureStatus"] . "&nbsp;</td>
					</tr>
					<tr>
						<td class=\"fieldLabel\">CAVV:</td>
						<td class=\"fieldData\">" . $row["CAVV"] . "&nbsp;</td>
					</tr>
					<tr>
						<td class=\"fieldLabel\">Card Type:</td>
						<td class=\"fieldData\">" . $row["CardType"] . "&nbsp;</td>
					</tr>
				    <tr>
					    <td class=\"fieldLabel\">Address Status:</td>
					    <td class=\"fieldData\"><span style=\"float:right; font-size: smaller;\">&nbsp;*PayPal transactions only</span>" . $row["AddressStatus"] . "</td>
				    </tr>
				    <tr>
					    <td class=\"fieldLabel\">Payer Status:</td>
					    <td class=\"fieldData\"><span style=\"float:right; font-size: smaller;\">&nbsp;*PayPal transactions only</span>" . $row["PayerStatus"] . "</td>
				    </tr>
				    <tr>
					    <td class=\"fieldLabel\">PayerID:</td>
					    <td class=\"fieldData\"><span style=\"float:right; font-size: smaller;\">&nbsp;*PayPal transactions only</span>" . $row["PayPalPayerID"] . "</td>
				    </tr>
					<tr>
						<td class=\"fieldLabel\">Basket Contents:</td>
						<td class=\"fieldData\">
							<table width=\"100%\" style=\"border-collapse: collapse;\">
								<tr class=\"greybar\">
									<td width=\"10%\" align=\"right\">Quantity</td>
									<td width=\"20%\" align=\"center\">Image</td>
									<td width=\"50%\" align=\"left\">Title</td>
									<td width=\"20%\" align=\"right\">Price</td>
								</tr>";

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
								echo "
							</table>
						</td>
					</tr>
				</table>
            	<div class=\"greyHzShadeBar\">&nbsp;</div>";
			}
		?>
			<form name="completionform" action="orderFailed.php" method="POST">
			<input type="hidden" name="navigate" value="" />
			<table border="0" width="100%">
				<tr>
					<td colspan="2">Click Proceed to go back to the Home Page to start another transaction, or click Admin to go to the Order Administration example pages where you can view your orders and perform REPEAT payments, REFUNDs, VOIDs and other transaction processing functions. </td>
				</tr>
				<tr>
					<td width="50%" align="left">
					    <a href="javascript:submitForm('completionform','admin');" title="Admin"><img src="images/admin.gif" alt="Admin" border="0" /></a></td>
					<td width="50%" align="right">
                        <a href="javascript:submitForm('completionform','proceed');" title="Proceed"><img src="images/proceed.gif" alt="Proceed" border="0" /></a></td>
				</tr>
			</table>
			</form>
		</div>
	</div>
</body>
</html>


