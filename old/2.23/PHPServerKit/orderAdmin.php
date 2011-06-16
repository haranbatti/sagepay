<?
include("includes.php");

/**************************************************************************************************
* Sage Pay Server PHP Kit Order Administration Menu
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

* The Order Administration Menu page.  This page provides a link to the back-office features such
* as REFUND and REPEAT that you may wish to carry out from your own server (although they can be
* performed via the My Sage Pay system on the Sage Pay servers if you prefer)
 
* IMPORTANT! Although these pages are provided as part of the kit, they should NOT be hosted in
* the same virtual directory as the order pages.  These perform back office functions that your
* customer should NOT have access too.  You you put these pages in a secure area only accessible
* to your staff.
***************************************************************************************************/

//Check for the back button clicks
if ($_REQUEST["navigate"]=="back") {
	ob_flush();
	redirect("welcome.php");
	exit();
} elseif ($_REQUEST["navigate"]=="release") {
	ob_flush();
	redirect("release.php?VendorTxCode=" . $_REQUEST["VendorTxCode"] );
	exit();
} elseif ($_REQUEST["navigate"]=="abort") {
	ob_flush();
	redirect("abort.php?VendorTxCode=" . $_REQUEST["VendorTxCode"]);
	exit();
} elseif ($_REQUEST["navigate"]=="refund") {
	ob_flush();
	redirect("refund.php?VendorTxCode=" . $_REQUEST["VendorTxCode"]);
	exit();
} elseif ($_REQUEST["navigate"]=="repeat") {
	ob_flush();
	redirect("repeat.php?VendorTxCode=" . $_REQUEST["VendorTxCode"]);
	exit();
} elseif ($_REQUEST["navigate"]=="void") {
	ob_flush();
	redirect("void.php?VendorTxCode=" . $_REQUEST["VendorTxCode"] );
	exit();
} elseif ($_REQUEST["navigate"]=="authorise") {
	ob_flush();
	redirect("authorise.php?VendorTxCode=" . $_REQUEST["VendorTxCode"]);
	exit();
} elseif ($_REQUEST["navigate"]=="cancel") {
	ob_flush();
	redirect("cancel.php?VendorTxCode=" . $_REQUEST["VendorTxCode"]);
	exit();
}
?>
<html>
<head>
	<title>Sage Pay Server PHP Kit Order Administration Menu</title>
	<link rel="STYLESHEET" type="text/css" href="images/serverKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Order Administration Menu </div>
			<p><span class="warning">Although these pages are provided as part of the kit, they should NOT be hosted in the same virtual directory as the order pages.  These perform back office functions that your customers should NOT have access too.  You should put these pages in a secure area only accessible to your staff.</span><br>
			  <br>
			  The table belows lists all your transactions in reverse date order. Click the buttons in the action column of the table to perform the specified function.<br>
			  <br>
			  For a full list of back office functions see the <a href="http://www.sagepay.com/documents/SagePayServerandDirectSharedProtocols.pdf" target="_blank">Server and Direct Shared Protocols</a> document. <br>
			  <br>
			  This code for this simple menu page is in the orderAdmin.asp file. 
			</p>
            <div class="greyHzShadeBar">&nbsp;</div>
			<table class="formTable">
				<tr>
					<td colspan="6"><div class="subheader">Your transactions</div></td>
				</tr>
				<tr class="greybar">
					<td align="left" width="25%">VendorTxCode</td>
					<td align="left" width="10%">TxType</td>
					<td align="right" width="10%">Amount</td>
					<td align="left" width="10%">Date</td>
					<td align="left" width="35%">Status</td>
					<td align="left" width="10%">Actions</td>
				</tr>
				<?
				/**Step through each transaction in our database, display the details
				** and decide which buttons to display **/
				
				$strSQL="SELECT * FROM tblOrders order by lastupdated desc";
				//Execute the SQL command to insert this data to the tblOrders table
				$rsPrimary = mysql_query($strSQL)
					or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
				
				while ($row = mysql_fetch_array($rsPrimary)) 
				{
					echo "
					<form name=\"" . $row["VendorTxCode"] . "\" id=\"" . $row["VendorTxCode"] . "\"  action=\"\" method=\"POST\">
					<input type=\"hidden\" name=\"navigate\" value=\"\" />
					<input type=\"hidden\" name=\"VendorTxCode\" value=\"" . $row["VendorTxCode"] ."\">
					<tr>
						<td class=\"smalltext\" align=\"left\">" . $row["VendorTxCode"] . "</td>
						<td class=\"smalltext\" align=\"left\">" . $row["TxType"] . "&nbsp;</td>
						<td class=\"smalltext\" align=\"right\">" . number_format($row["Amount"],2) . " " . $row["Currency"] . "&nbsp;</td>
						<td class=\"smalltext\" align=\"left\">" . $row["LastUpdated"] . "&nbsp;</td>
						<td class=\"smalltext\" align=\"left\" style=\"word-wrap: break-word; word-break: break-all;\">" . $row["Status"] . "&nbsp;</td>
						<td class=\"smalltext\" align=\"left\">";
							$TxType=$row["TxType"];
							if (isset($row["Status"])) {
								$TxState=$row["Status"];
								$TxState=substr($TxState,0,strpos($TxState," "));
							}
							else {
								$TxState="UNKNOWN";
							}
							
							// Display REFUND, REPEAT and VOID for Authorised PAYMENTS, AUTHORISES, REPEAT and Released DEFERREDs
							if ((($TxType=="PAYMENT" || $TxType=="AUTHORISE" || $TxType=="REPEAT") && $TxState=="AUTHORISED")
								|| ($TxType=="DEFERRED" && $TxState=="RELEASED")) {
								
								echo "<a href=\"javascript:submitForm('" . $row["VendorTxCode"] . "','refund');\" title=\"Refund this transaction\">REFUND</a> ";
								echo "<a href=\"javascript:submitForm('" . $row["VendorTxCode"] . "','repeat');\" title=\"Repeat this transaction\">REPEAT</a> ";
								echo "<a href=\"javascript:submitForm('" . $row["VendorTxCode"] . "','void');\" title=\"Void this transaction\">VOID</a> ";
							}
							
							// Display RELEASE and ABORT for any authorised DEFERRED transaction 
							if ($TxType=="DEFERRED" && $TxState=="AUTHORISED") {
								echo "<a href=\"javascript:submitForm('" . $row["VendorTxCode"] . "','release');\" title=\"Release this transaction\">RELEASE</a> ";
								echo "<a href=\"javascript:submitForm('" . $row["VendorTxCode"] . "','abort');\" title=\"Abort this transaction\">ABORT</a> ";
							}

							// Display AUTHORISE and CANCEL for any AUTHENTICATED or REGISTERED transaction
							if ($TxState=="AUTHENTICATED" || $TxState=="REGISTERED") {
								echo "<a href=\"javascript:submitForm('" . $row["VendorTxCode"] . "','authorise');\" title=\"Authorise this transaction\">AUTHORISE</a> ";
								echo "<a href=\"javascript:submitForm('" . $row["VendorTxCode"] . "','cancel');\" title=\"Cancel this transaction\">CANCEL</a> ";
							}

						echo "&nbsp;	
						</td>
					</tr>
					</form>";
			}
		?>							
			</table>
            <div class="greyHzShadeBar">&nbsp;</div>
			<form action="orderAdmin.php" method="POST" name="form2">
			<input type="hidden" name="navigate" value="" />
			<div class="formFooter">
				<a href="javascript:submitForm('form2','back');" title="Go back to the kit home page"><img src="images/back.gif" alt="Go back to the kit home page" border="0"></a>
			</div>
			</form>
		</div>
	</div>
</body>
</html>


