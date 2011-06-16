<?
include("includes.php");

/**************************************************************************************************
* Sage Pay Direct PHP Kit Build Order Page
***************************************************************************************************

***************************************************************************************************
* Change history
* ==============
*
* 11/02/2009 - Simon Wolfe - Updated for VSP protocol 2.23
* 18/12/2007 - Nick Selby - New PHP version adapted from ASP
***************************************************************************************************
* Description
* ===========
*
* Retrieves product information from the database, displays details of the products and allows the
* user to enter a number of each item to buy.  It then validates the selection and forwards the
* user to the customer details page.
**************************************************************************************************/

//Get products database for reference on this page
$query = "SELECT * FROM tblProducts";
$rsPrimary = mysql_query($query)
	or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
$num=mysql_numrows($rsPrimary);
mysql_close();	

// Check for the proceed button click, and if so, go validate the order
if ($_REQUEST["navigate"]=="proceed"){
	/** We need the user to have selected at least one item, so let's see what they've chosen **
	*** by looping through the submitted Quantity fields **/
	$strCart="";
	for ($iLoop=1; $iLoop <= $num; $iLoop++) {
		$strThisQuantity=$_REQUEST["Quantity" . $iLoop];
		if ($strThisQuantity>0)
			$strCart=$strCart . $strThisQuantity . ",";
	}

	if (strlen($strCart)==0){
		// Nothing was selected, so simply redesiplay the page with an error
		$strPageError="You did not select any items to buy.  Please select at least 1 DVD.";
		$_SESSION["strCart"]="";
	}
	
	else  { 
		// Save the cart to the session object
		$_SESSION["strCart"]=$strCart;
		// Proceed to the view shopping basket screen
		ob_end_flush();
		redirect("viewBasket.php");
	} 
}

elseif ($_REQUEST["navigate"]=="back") {
	ob_flush();
	redirect("welcome.php");
}

// If we have a cart in the session, then we'll show the selected items here **
$strCart=$_SESSION["strCart"];

?>
<html>
<head>
	<title>Direct PHP Kit Build Order Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/directKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Creating an Example Order</div>
			<p>This page demonstrates how to retrieve information about products from your database and create a very simple basket of goods. Use the form below to select the number of each DVD title you wish to buy, then hit Proceed. You have to select at least 1 DVD to continue. </p>
			<p>The title, price and DVD ID (used to display the correct image) are extracted from the database to build the table. You can view this code in the buildOrder.php page.</p>
            <div class="greyHzShadeBar">&nbsp;</div>
            <? if (isset($strPageError)) { ?>
            <div class="errorheader">
                <? echo $strPageError ?>
            </div>
            <? } ?>
			<form action="buildOrder.php" method="POST" name="mainForm">
			<input type="hidden" name="navigate" value="" />
			<table class="formTable">
				<tr>
				  <td colspan="4"><div class="subheader">Please select the quantity of each item you wish to buy</div></td>
				</tr>
				<tr class="greybar">
					<td width="20%" align="center">Image</td>
					<td width="50%" align="left">Title</td>
					<td width="20%" align="right">Price</td>
					<td width="10%" align="center">Quantity</td>
				</tr>
				<?
					//Display a table row for each product in database
					$i=0;
					while ($i < $num) {
						$strProductId = mysql_result($rsPrimary,$i,"ProductId");
						$strImageId = "00" . $strProductId; 
						$strPrice = mysql_result($rsPrimary,$i,"Price");
						echo "<tr>";
						echo "<td align=\"center\"><img src=\"images/dvd" . substr($strImageId,strlen($strImage)-2,2) .  ".gif\" alt=\"DVD box\"></td>";
						echo "<td align=\"left\">" . mysql_result($rsPrimary,$i,"Description") . "</td>";
						echo "<td align=\"right\">" . number_format($strPrice,2) . " " . $strCurrency . "</td>";
						echo "<td align=\"center\">";
						echo "<select name=\"Quantity" . $strProductId . "\"";
						echo " size=\"1\">";
						echo "<option value=\"0\">None</option>";
						for ($iLoop=1; $iLoop <= 5; $iLoop++) {
							$strThisItem=$iLoop . " of " . $strProductId;
							echo "<option value=\"" . $strThisItem . "\"";
							// If this is in our cart, show it selected
							if(strstr($strCart,trim($strThisItem)))  
							echo " SELECTED";
							echo ">" . $iLoop . "</option>";
							
						}
						echo "</select>";
						echo "</tr>";
					$i++;	
					}
				?>
			</table>
            <div class="greyHzShadeBar">&nbsp;</div>
            <div class="formFooter">
				<a href="javascript:submitForm('mainForm','back');" title="Go back to the kit home page" style="float: left;"><img src="images/back.gif" alt="Go back to the kit home page" border="0" /></a>
				<a href="javascript:submitForm('mainForm','proceed');" title="Submit the order details" style="float: right;"><img src="images/proceed.gif" alt="Proceed to the next page" border="0" /></a>	
			</div>
			</form>
		</div>
	</div>
</body>
</html>


