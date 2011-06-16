<?
include("includes.php");
session_start(); 
/**************************************************************************************************
* Server PHP Kit Build Order Page
***************************************************************************************************

***************************************************************************************************
* Change history
* ==============
*
* 10/02/2009 - Simon Wolfe - Updated for protocol 2.23
* 18/10/2007 - Nick Selby - New kit version
***************************************************************************************************
* Description
* ===========
*
* Displays details of the products and allows the user to enter a number of each item to buy.  
* It then validates the selection and forwards the user to the customer details page.
***************************************************************************************************/

// Check for the proceed button click, and if so, go validate the order
if ($_REQUEST["navigate"]=="proceed"){
	/** We need the user to have selected at least one item, so let's see what they've chosen **
	*** by looping through the submitted Quanity fields **/
	$strCart="";
	for ($iLoop=1; $iLoop <= count($arrProducts); $iLoop++) {
		$strQuantity = "Quantity" . $iLoop;
		$strThisQuantity=$_REQUEST[$strQuantity];
		if ($strThisQuantity>0) {
			$strCart=$strCart . $strThisQuantity . ",";
		}
	}
	
	if (strlen($strCart)==0){
		// Nothing was selected, so simply redesiplay the page with an error
		$strPageError="You did not select any items to buy.  Please select at least 1 DVD.";
		$_SESSION["strCart"]="";
	}
	else  { 
		// Save the cart to the session object
		$_SESSION["strCart"]=$strCart;
		// Proceed to the customer details screen
		ob_end_flush();
		redirect("customerDetails.php");
	} 
}
else if ($_REQUEST["navigate"]=="back") {
	ob_end_flush();
	redirect("welcome.php");
}

// If we have a cart in the session, then we'll show the selected items here **
$strCart=$_SESSION["strCart"];

?>
<html>
<head>
	<title>Form PHP Kit Build Order Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/formKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Creating an Example Order</div>
            <p>This page demonstrates how to  create a very simple basket of goods. Use the form below to select the number of each DVD title you wish to buy, then hit Proceed. You have to select at lest 1 DVD to continue. </p>
            <p>The title, price and DVD ID (used to display the correct image) are extracted from a simple array and used to build the table. You can view this code in the buildOrder.php page.</p>
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
                        <td width="15%" align="center">Image</td>
						<td width="55%" align="left">Title</td>
						<td width="20%" align="right">Price</td>
						<td width="10%" align="center">Quantity</td>
					</tr>
					<? 
					for ($iIndex=1; $iIndex <= count($arrProducts); $iIndex++) { 
						$strImageId = "00" . $iIndex; 
						echo "<tr>";
						echo "<td align=\"center\"><img src=\"images/dvd" . substr($strImageId,strlen($strImageId)-2,2) .  ".gif\" alt=\"DVD box\"></td>";
						echo "<td align=\"left\">";
						echo $arrProducts[$iIndex-1][0];
						echo "</td>";
						echo "<td align=\"right\">" . $arrProducts[$iIndex-1][1] . " " . $strCurrency . "</td>";
						echo "<td>";
						echo "<select name=\"Quantity" . $iIndex ."\"";
						echo "size=\"1\">";
						echo "<option value=\"0\">None</option>";
						for ($iLoop=1; $iLoop <= 5; $iLoop++) {
							$strThisItem=$iLoop . " of " . $iIndex;
							echo "<option value=\"" . $strThisItem . "\"";
							// If this is in our cart, show it selected
							if(strstr($strCart, $strThisItem))  
							echo " SELECTED";
							echo ">" . $iLoop . "</option>";
						}
						echo "</select>";
						echo "</tr>";
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