<?
include("includes.php");
session_start(); 
/**************************************************************************************************
* Sage Pay Direct PHP Kit Order Confirmation Page
***************************************************************************************************

***************************************************************************************************
* Change history
* ==============
*
* 02/04/2009 - Simon Wolfe - Updated UI for re-brand
* 11/02/2009 - Simon Wolfe - Updated for VSP protocol 2.23
* 18/12/2007 - Nick Selby - New PHP version adapted from ASP
***************************************************************************************************
* Description
* ===========
*
* Displays a summary of the order items and customer details and builds the Sage Pay Direct POST data
* that will be sent along with the user to the Sage Pay payment pages.  In SIMULATOR and TEST mode
* the decoded version of this field will be displayed on screen for you to check.
***************************************************************************************************/

// Check we have a cart in the session.  If not, go back to the buildOrder page to get one
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

// Check for the proceed button click, and if so, go validate the order
if ($_REQUEST["navigate"]=="proceed") {
	ob_flush();
	redirect("transactionRegistration.php");
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

?>
<html>
<head>
	<title>Direct PHP Kit Order Confirmation Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/directKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
    <script type="text/javascript" language="javascript" src="scripts/countrycodes.js"></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Order Confirmation Page</div>
			<p>This page summarises the order details and customer information gathered on the previous screens. It is always a good idea to show your customers a page like this to allow them to go back and edit either basket or contact details.<br>
			  <br>
			  The page also shows how to calculate the order total using the basket contents and the database (you should never store order totals in the session or in hidden fields, since these can be modified by the user). The code for this page can be found in orderConfirmation.php.<BR><BR>
			  <? if ($strConnectTo=="SIMULATOR")
			  	echo
				"Because you are using Sage Pay Simulator, clicking Proceed will show you the contents of the POST sent to Sage Pay Direct, and the reply sent back.  This will illustrate how your system should register details with the real Sage Pay Direct systems.  When you are in Test or Live modes, this page will not display results or wait for your input, it will simply redirect the customer to the payment pages, or handle any registration errors sent back by Sage Pay Direct.";
			  	else
				echo
				"Since you are in " . $strConnectTo . " mode, clicking Proceed will register your transaction with Sage Pay Direct and automatically redirect you to the payment page, or handle any registration errors.  The code to do this can be found in transactionRegistration.php";
			  ?>
          	</p>
            <div class="greyHzShadeBar">&nbsp;</div>
			<table class="formTable">
				<tr>
				  <td colspan="5"><div class="subheader">Your Basket Contents</div></td>
				</tr>
				<tr class="greybar">
					<td width="20%" align="center">Image</td>
					<td width="42%" align="left">Title</td>
					<td width="15%" align="right">Price</td>
					<td width="8%" align="right">Quantity</td>
					<td width="15%" align="right">Total</td>
				</tr>
				
				<?
				// Step through the basket contents and display the order
				$sngTotal=0.0;
				$strThisEntry=$strCart;
											
				while (strlen($strThisEntry)>0)
				{
					// Extract the quantity and Product from the list of "x of y," entries in the cart
					$iQuantity=cleanInput(substr($strThisEntry,0,1),"Number");
					$iProductId=substr($strThisEntry,strpos($strThisEntry,",")-1,1);
					//Get product details from database
					$query = "SELECT * FROM tblProducts where ProductID=" . $iProductId . "";
					$rsPrimary = mysql_query($query)
						or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
					$row = mysql_fetch_array($rsPrimary); 
													
					$sngTotal=$sngTotal + ($iQuantity * $row["Price"]);
					$strImageId = "00" . $iProductId;
					
					echo "<tr>";
					echo "<td align=\"center\"><img src=\"images/dvd" . substr($strImageId,strlen($strImageId)-2,2) .  "small.gif\" alt=\"DVD box\"></td>";
					echo "<td align=\"left\">" . $row["Description"] ."</td>";
					echo "<td align=\"right\">" . $row["Price"] . " " . $strCurrency . "</td>";
					echo "<td align=\"right\">" . $iQuantity . "</td>";
					echo "<td align=\"right\">" . number_format($iQuantity * $row["Price"],2) . " " . $strCurrency . "</td>";
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
					<td align="right"><? echo number_format(1.50,2) . " " . $strCurrency ?></td>
				</tr>
				<tr>
					<td colspan="4" align="right"><strong>Total:</strong></td>
					<td align="right"><strong><? echo number_format($sngTotal,2) . " " . $strCurrency ?></strong></td>
				</tr>
			</table>
			<table class="formTable">
				<tr>
				  <td colspan="2"><div class="subheader">Your Billing Details</div></td>
				</tr>
				<tr>
					<td class="fieldLabel">Name:</td>
					<td class="fieldData"><? echo $strBillingFirstnames ?>&nbsp;<? echo $strBillingSurname ?></td>
				</tr>
				<tr>
					<td class="fieldLabel">Address Details:</td>
					<td class="fieldData">
					    <? echo $strBillingAddress1  ?><BR>
					    <? if (strlen($strBillingAddress2)>0) echo $strBillingAddress2 . "<BR>"; ?>
					    <? echo $strBillingCity  ?>&nbsp;
					    <? if (strlen($strBillingState)>0) echo $strBillingState; ?><BR>
					    <? echo $strBillingPostCode;  ?><BR>
					    <script type="text/javascript" language="javascript">
					        document.write( getCountryName( "<? echo $strBillingCountry; ?>" ));
					    </script>
					</td>
				</tr>
				<tr>
					<td class="fieldLabel">Phone Number:</td>
					<td class="fieldData"><? echo $strBillingPhone; ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="fieldLabel">e-Mail Address:</td>
					<td class="fieldData"><? echo $strCustomerEMail; ?>&nbsp;</td>
				</tr>
			</table>
			<table class="formTable">
				<tr>
				  <td colspan="2"><div class="subheader">Your Delivery Details</div></td>
				</tr>
				<tr>
					<td class="fieldLabel">Name:</td>
					<td class="fieldData"><? echo $strDeliveryFirstnames; ?>&nbsp;<? echo $strDeliverySurname; ?></td>
				</tr>
				<tr>
					<td class="fieldLabel">Address Details:</td>
					<td class="fieldData">
					    <? echo $strDeliveryAddress1  ?><BR>
					    <? if (strlen($strDeliveryAddress2)>0) echo $strDeliveryAddress2 . "<BR>"; ?>
					    <? echo $strDeliveryCity; ?>&nbsp;
					    <? if (strlen($strDeliveryState)>0) echo $strDeliveryState; ?><BR>
					    <? echo $strDeliveryPostCode; ?><BR>
					    <script type="text/javascript" language="javascript">
					        document.write( getCountryName( "<? echo $strDeliveryCountry;  ?>" ));
					    </script>
					</td>
				</tr>
				<tr>
					<td class="fieldLabel">Phone Number:</td>
					<td class="fieldData"><? echo $strDeliveryPhone; ?>&nbsp;</td>
				</tr>
			</table>
            <div class="greyHzShadeBar">&nbsp;</div>
            <div class="formFooter">
			 	<form name="customerform" action="orderConfirmation.php" method="POST">
			 	<input type="hidden" name="navigate" value="" />
				<a href="javascript:submitForm('customerform','back');" title="Go back" style="float: left"><img src="images/back.gif" alt="Go back to the previous page" border="0" /></a>
				<a href="javascript:submitForm('customerform','proceed');" title="Continue" style="float: right"><img src="images/proceed.gif" alt="Proceed to the next page" border="0" /></a>
				</form>
			</div>
		</div>
	</div>
</body>
</html>

