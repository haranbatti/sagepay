<?
include("includes.php");

/**************************************************************************************************
* Sage Pay Server PHP Kit Order Successful Page
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

* This is a placeholder for your Successful Order Completion Page.  It retrieves the VendorTxCode
* from the crypt string and displays the transaction results on the screen.  You wouldn't display 
* all the information in a live application, but during development this page shows everything
* sent back in the confirmation screen.
****************************************************************************************************/

// Check for the proceed button click, and if so, go to the buildOrder page

if ($_REQUEST["navigate"]=="proceed") {
	ob_flush();
	session_destroy();
	// Redirect to next page
	redirect("welcome.php");
	exit();
}
elseif ($_REQUEST["navigate"]=="admin") {
	ob_flush();
	session_destroy();
	redirect("orderAdmin.php");
	exit();
}

//Now check we have a VendorTxCode passed to this page
$strVendorTxCode=$_GET["VendorTxCode"];
if (strlen($strVendorTxCode)==0) { 
	//No VendorTxCode, so take the customer to the home page
	ob_flush();
	session_destroy();
	redirect("welcome.php");
	exit();
}

//Empty the cart, we're done with it now because the order is successful
$_SESSION["strCart"]="";

?>

<html>
<head>
	<title>Sage Pay Server PHP Kit Order Successful Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/serverKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
    <script type="text/javascript" language="javascript" src="scripts/countrycodes.js"></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Your order has been Successful</div>
			<p>The Sage Pay Server transaction has completed successfully and the customer has been returned to this order completion page<br>
			  <br>
			  The order number, for your customer's reference is: <span class="arrowbullets"><strong><? echo $strVendorTxCode ?></strong></span> <br>
			  <br>
			  They should quote this in all correspondence with you, and likewise you should use this reference when sending queries to Sage Pay about this transaction (along with your Sage Pay Vendor Name).<br>
			  <br>
			  The table below shows everything in the database about this order.  You would not normally show this level of detail to your customers, but it is useful during development.<br>
			  <br>
			  You can customise this page to send confirmation e-mails, display delivery times, present download pages, whatever is appropriate for your application.  The code is in orderSuccessful.php.
			</p>
            <div class="greyHzShadeBar">&nbsp;</div>
		<? if ($strConnectTo!=="LIVE")
		{
			// NEVER show this level of detail when the account is LIVE
			$strSQL="SELECT * FROM tblOrders where VendorTxCode='" . mysql_real_escape_string($strVendorTxCode) . "'";
			//Execute the SQL command
			$rsPrimary = mysql_query($strSQL)
				or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
			$strSQL="";
			$row=mysql_fetch_array($rsPrimary);
		?>
			<table class="formTable">
				<tr>
				  <td colspan="2"><div class="subheader">Order Details stored in your Database</div></td>
				</tr>
				<tr>
					<td class="fieldLabel">VendorTxCode:</td>
					<td class="fieldData"><? echo $strVendorTxCode; ?></td>
				</tr>
				<tr>
					<td class="fieldLabel">Transaction Type:</td>
					<td class="fieldData"><? echo $row["TxType"]; ?></td>
				</tr>
				<tr>
					<td class="fieldLabel">Status:</td>
					<td class="fieldData"><? echo $row["Status"]; ?></td>
				</tr>
				<tr>
					<td class="fieldLabel">Amount:</td>
					<td class="fieldData"><? echo number_format($row["Amount"],2) . " " . $strCurrency; ?></td>
				</tr>
				<tr>
					<td class="fieldLabel">Billing Name:</td>
					<td class="fieldData"><? echo $row["BillingFirstnames"] . " " . $row["BillingSurname"]; ?></td>
				</tr>
				<tr>
					<td class="fieldLabel">Billing Phone:</td>
					<td class="fieldData"><? echo $row["BillingPhone"]; ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">Billing Address:</td>
					<td class="fieldData">
				        <? echo $row["BillingAddress1"] ?><BR>
				        <? if (!isSet($row["BillingAddress2"])) echo $row["BillingAddress2"]. "<BR>"; ?>
				        <? echo $row["BillingCity"] ?>&nbsp;
				        <? if (!isSet($row["BillingState"])) echo $row["BillingState"]; ?><BR>
				        <? echo $row["BillingPostCode"]; ?><BR>
				        <script type="text/javascript" language="javascript">
				            document.write( getCountryName( "<? echo $row["BillingCountry"]; ?>" ));
				        </script>
					</td>
				</tr>
				<tr>
					<td class="fieldLabel">Billing e-Mail:</td>
					<td class="fieldData"><? echo $row["CustomerEMail"] ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">Delivery Name:</td>
					<td class="fieldData"><? echo $row["DeliveryFirstnames"] . " " . $row["DeliverySurname"]; ?></td>
				</tr>
				<tr>
					<td class="fieldLabel">Delivery Address:</td>
					<td class="fieldData">
				        <? echo $row["DeliveryAddress1"]; ?><BR>
				        <? if (!isSet($row["DeliveryAddress2"])) echo $row["DeliveryAddress2"] . "<BR>"; ?>
				        <? echo $row["DeliveryCity"]; ?>&nbsp;
				        <? if (!isSet($row["DeliveryState"])) echo $row["DeliveryState"]; ?><BR>
				        <? echo $row["DeliveryPostCode"]; ?><BR>
				        <script type="text/javascript" language="javascript">
				            document.write( getCountryName( "<? echo $row["DeliveryCountry"]; ?>" ));
				        </script>
					</td>
				</tr>
				<tr>
					<td class="fieldLabel">Delivery Phone:</td>
					<td class="fieldData"><? echo $row["DeliveryPhone"]; ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">VPSTxId:</td>
					<td class="fieldData"><? echo $row["VPSTxID"]; ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">SecurityKey:</td>
					<td class="fieldData"><? echo $row["SecurityKey"]; ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">VPSAuthCode (TxAuthNo):</td>
					<td class="fieldData"><? echo $row["TxAuthNo"]; ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">AVSCV2 Results:</td>
					<td class="fieldData"><?
					    if (!isset($row["AVSCV2"])) {
					    	echo "-"; 
					    } else { 
					    	echo $row["AVSCV2"];
					    }
					    echo " <span class=\"smalltext\">(Address: ";
					    if (!isset($row["AddressResult"])) {
					    	echo "-"; 
					    } else { 
					    	echo $row["AddressResult"];
					    }
					    echo ", Post Code: ";
					    if (!isset($row["PostCodeResult"])) { 
					    	echo "-"; 
					    } else { 
					    	echo $row["PostCodeResult"];
					    }
					    echo ", CV2: ";
					    if (!isset($row["CV2Result"])) {
					    	echo "-";
					    } else {
					    	echo $row["CV2Result"];
					    }
					    echo ")</span>"; ?>
			        </td>
				</tr>
				<tr>
					<td class="fieldLabel">Gift Aid Transaction?:</td>
					<td class="fieldData"><? if ($row["GiftAid"]==1) echo "Yes"; else echo "No"; ?></td>
				</tr>
				<tr>
					<td class="fieldLabel">3D-Secure Status:</td>
					<td class="fieldData"><? echo $row["ThreeDSecureStatus"] ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">CAVV:</td>
					<td class="fieldData"><? echo $row["CAVV"] ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">Card Type:</td>
					<td class="fieldData"><? echo $row["CardType"] ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">Last 4 Digits:</td>
					<td class="fieldData"><? echo $row["Last4Digits"] ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">Address Status:</td>
					<td class="fieldData"><span style="float:right; font-size: smaller;">&nbsp;*PayPal transactions only</span><? echo $row["AddressStatus"] ?></td>
				</tr>
				<tr>
					<td class="fieldLabel">Payer Status:</td>
					<td class="fieldData"><span style="float:right; font-size: smaller;">&nbsp;*PayPal transactions only</span><? echo $row["PayerStatus"] ?></td>
				</tr>
			<?	
			echo
			"
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
			<form name="completionform" action="orderSuccessful.php" method="POST">
			<input type="hidden" name="navigate" value="" />
			<table border="0" width="100%">
				<tr>
					<td colspan="2">Click Proceed to go back to the Home Page to start another transaction, or click Admin to go to the Order Administration example pages where you can view your orders and perform REPEAT payments, REFUNDs, VOIDs and other 
					transaction processing functions. </td>
				</tr>
	            <tr>
		            <td width="50%" align="left"><a href="javascript:submitForm('completionform','admin');" title="Click to go to the order admin screen"><img src="images/admin.gif" alt="Click to go to the order admin screen" border="0"></a></td>
		            <td width="50%" align="right"><a href="javascript:submitForm('completionform','proceed');" title="Click to go back to the welcome page"><img src="images/proceed.gif" alt="Click to go back to the welcome page" border="0"></a></td>
	            </tr>
			</table>
			</form>
		</div>
	</div>
</body>
</html>