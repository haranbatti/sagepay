<?
include("includes.php");

/**************************************************************************************************
* Sage Pay Direct PHP Kit View Basket Page
***************************************************************************************************

***************************************************************************************************
* Change history
* ==============
*
* 02/04/2009 - Simon Wolfe - Updated UI for re-brand
* 11/02/2009 - Simon Wolfe - Created for VSP protocol 2.23 to include PayPal express button
***************************************************************************************************
* Description
* ===========
*
* Displays a summary of the order items and gives checkout choices to either your checkout 
* or PayPal Express
***************************************************************************************************/

//Check we have a cart in the session.  If not, go back to the buildOrder page to get one
$strCart=$_SESSION["strCart"];
if (strlen($strCart)==0) {
	ob_end_flush();
	redirect("buildOrder.php");
}

// Check for the BACK button click 
if ($_REQUEST["navigate"]=="back") {
	ob_end_flush();
	redirect("buildOrder.php");
}

// Check for the proceed button click, and if so, go validate the order 
if ($_REQUEST["navigate"]=="proceed") {
	ob_end_flush();
	redirect("customerDetails.php");
}

// Check for "paypalExpress" button click, and if so, go transactionRegistration page 
if ($_REQUEST["navigate"]=="paypalExpress") {
	$_SESSION["paypalExpress"] = True;
	ob_end_flush();
	redirect("transactionRegistration.php");
}

?>
<html>
<head>
	<title>Direct PHP Kit View Shopping Basket Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/directKitStyle.css">
    <script type="text/javascript" language="javascript" src="scripts/common.js"></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">View Shopping Basket Page</div>
			<p>This page shows a basic view of the customer's shopping basket contents and presents two different checkout methods.</p>
			<p>Clicking proceed will take the customer through your screens to collect their billing, contact and delivery details.</p>
			<p>If you have enabled PayPal on your account you could also offer the PayPal Express checkout option as a means of providing 
			PayPal as an alternative payment method. Clicking this option will take the customer immediately to PayPal who will collect the 
			customer's delivery details on your behalf. PayPal can also be offered as an alternative payment method later on in your checkout 
			screens after you have collected the customer's billing, contact and delivery details.
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
			<div class="greyHzShadeBar">&nbsp;</div>
            <form name="customerform" action="viewBasket.php" method="POST">
			<input type="hidden" name="navigate" value="" /> 
			<div class="formFooter">
                <a href="javascript:submitForm('customerform','back');" title="Go back to the customer details page" style="float: left"><img src="images/back.gif" alt="Go back to the customer details page" border="0" /></a>
            	<a href="javascript:submitForm('customerform','proceed');" title="Proceed to Sage Pay Direct registration" style="float: right"><img src="images/proceed.gif" alt="Proceed to Sage Pay Direct registration" border="0" /></a>
            </div>
			<!-- The code below is for the PayPal express button if you wish to offer this payment method and your account has PayPal enabled.
			     Visit www.PayPal.com to ensure you have the latest code if you wish to offer PayPal express 
			 -->
			<div class="formFooter">
			    <a href="javascript:submitForm('customerform','paypalExpress');" title="Checkout and pay with PayPal Express" style="float: right"><img src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" border="0" align="left" style="margin-right:7px;"></a>
		    </div>
		    <!-- End of PayPal express code -->
            </form>
		</div>
	</div>
</body>
</html>
