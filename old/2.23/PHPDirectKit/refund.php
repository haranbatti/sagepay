<?
include("includes.php");
 
/**************************************************************************************************
* Direct PHP Refund Transaction Page
***************************************************************************************************

***************************************************************************************************
* Change history
* ==============

* 02/04/2009 - Simon Wolfe - Updated UI for re-brand
* 11/02/2009 - Simon Wolfe - Updated for protocol 2.23
* 18/12/2007 - Nick Selby - New PHP version adapted from ASP
***************************************************************************************************
* Description
* ===========

* This page perform builds a Refund POST based on the details you enter here.  The contents of the
* POST and the response are then displayed on screen and the database updated accordingly
***************************************************************************************************

*** Check we have a vendortxcode in the session.  If not, or of the user clicked back then return to the orderAdmin screen. **/
$strVendorTxCode=$_REQUEST["VendorTxCode"];
if ($_REQUEST["navigate"]=="admin")
{
	ob_flush();
	redirect("orderAdmin.php");
	exit();
}

$strResult="";
$strPost="";
$rawresponse=$_SESSION["rawresponse"];
echo $output["StatusDetail"];

/** Now to build the Direct POST.  For more details see the Server and Direct Shared Protocols 2.23 **
*** We'll extract the data we need from the database first. 
*** NB: Fields potentially containing non ASCII characters are URLEncoded when included in the POST **/

$strSQL = "SELECT * FROM tblOrders where VendorTxCode='" . $strVendorTxCode . "'";
$rsPrimary = mysql_query($strSQL)
	or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');

$row = mysql_fetch_array($rsPrimary);
$num=mysql_numrows($rsPrimary);

//If record exists
if ($num>0)
{
	$strVPSTxId=$row["VPSTxId"];
	$strSecurityKey=$row["SecurityKey"];
	$strTxAuthNo=$row["TxAuthNo"];
	$sngAmount=$row["Amount"];
}
else
{
	$strStatus="ERROR";
	$strResult="ERROR : Cannot retrieve the original transaction data from the database.";
}
$strSQL="";
$rsPrimary="";

//Check for the proceed button click, and if so, go validate the order
if ($_REQUEST["navigate"]=="proceed")
{
	$strRefundAmount=cleaninput($_REQUEST["RefundAmount"],"Number");
	$strRefundDescription=cleaninput($_REQUEST["RefundDescription"],"Text");
	$strRefundVendorTxCode=cleaninput($_REQUEST["RefundVendorTxCode"],"VendorTxCode");
	
	//Validate the Refund amount.  It must be a number, greater than 0
	if (strlen($strRefundAmount)==0)
	{
		$strStatus="ERROR";
		$strResult="ERROR : You need to specify an amount to Refund.  This can be any amount greater than zero.";
	}
	elseif (strlen($strRefundVendorTxCode)==0)
	{
		$strStatus="ERROR";
		$strResult="ERROR : You need to specify a new VendorTxCode for this Refund transaction.";
	}
	elseif (strlen($strRefundDescription)==0)
	{
		$strStatus="ERROR";
		$strResult="ERROR : You need to enter a Description of this Refund transaction.";
	}
	else
	{
		
		$sngRefundAmount=$strRefundAmount;

		//Now let's check we're not exceeding the amount of the original transaction minus any other refunds
		$sngAmount=$sngAmount-$sngRefundAmount;
		$strSQL="SELECT Amount FROM tblOrders where RelatedVendorTxCode='" . $strVendorTxCode . "' and TxType='REFUND'";
		$rsPrimary = mysql_query($strSQL)
			or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
		
		while ($row = mysql_fetch_array($rsPrimary)) 
		{
			$sngAmount=$sngAmount-$row["Amount"];
		}	
		$rsPrimary="";
		
		if ($sngRefundAmount<=0.0)
		{
			$strStatus="ERROR";
			$strResult="ERROR : You need to specify an amount to Refund.  This can be any amount greater than zero.";
		}
		elseif ($sngAmount<0.0)
		{
			$strStatus="ERROR";
			$strResult="ERROR : The Refund would exceed the Amount of the original transaction.  Cannot perform the refund.";
		}
		else
		{
			//Build the Refund message
			$strPost="VPSProtocol=" . $strProtocol;
			$strPost=$strPost . "&TxType=REFUND";
			$strPost=$strPost . "&Vendor=" . $strVendorName;
			$strPost=$strPost . "&VendorTxCode=" . $strRefundVendorTxCode;
			$strPost=$strPost . "&Amount=" . number_format($sngRefundAmount,2);
			$strPost=$strPost . "&Currency=" . $strCurrency;
			$strPost=$strPost . "&Description=" . $strRefundDescription;
			$strPost=$strPost . "&RelatedVPSTxId=" . $strVPSTxId;
			$strPost=$strPost . "&RelatedVendorTxCode=" . $strVendorTxCode;
			$strPost=$strPost . "&RelatedSecurityKey=" . $strSecurityKey;
			$strPost=$strPost . "&RelatedTxAuthNo=" . $strTxAuthNo;
		
			/** Now POST the data.
			*** Data is posted to strRefundURL which is set depending on whether you are using SIMULATOR, TEST or LIVE **/
			$arrResponse = requestPost($strRefundURL, $strPost);
					
			//Analyse the response from Direct to check that everything is okay
			$strStatus=$arrResponse["Status"];
		
			if ($strStatus == "OK")
			{
				//An OK status mean that the transaction has been successfully Refunded **
				$strResult="SUCCESS : The transaction was REFUNDed successfully and a new Refund transaction was created.";

				//Get the other values from the POST for storage in the database
				$strRefundVPSTxId=$arrResponse["VPSTxId"];
				$strRefundTxAuthNo=$arrResponse["TxAuthNo"];
				$strRefundSecurityKey=$arrResponse["SecurityKey"];
								
				//Create the new Refund transaction in the database, linked through to the original transaction **
				$strSQL="INSERT INTO tblOrders(VendorTxCode,TxType,Amount,Currency,VPSTxId,SecurityKey,TxAuthNo,RelatedVendorTxCode,Status)VALUES(";
				$strSQL=$strSQL . "'" . $strRefundVendorTxCode . "',";
				$strSQL=$strSQL . "'REFUND',";
				$strSQL=$strSQL . number_format($strRefundAmount,2) . ",";
				$strSQL=$strSQL . "'" . $strCurrency . "',";
				$strSQL=$strSQL . "'" . $strRefundVPSTxId . "',";
				$strSQL=$strSQL . "'" . $strRefundSecurityKey . "',";
				$strSQL=$strSQL . $strRefundTxAuthNo . ",";
				$strSQL=$strSQL . "'" . $strVendorTxCode . "',";
				$strSQL=$strSQL . "'AUTHORISED - REFUND transaction taken through Order Admin area.'";
				$strSQL=$strSQL . ")";
				
				$rsPrimary = mysql_query($strSQL)
					or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
				$strSQL="";
				$rsPrimary="";
			}

			else
			{
				//All other Statuses are errors of one form or another.  Display them on the screen with no database updates **
				$strStatusDetail = $arrResponse["StatusDetail"];
				$strResult=$strStatus . " : " . $strStatusDetail;
			}
		}
	}
}
else
{
	/** Since no buttons have been clicked, generate a random VendorTxCode for this Refund, based on on the original VendorTxCode **
	*** You can edit this in the boxes provided, but you probably wouldn't in a production environment **/
	$intRandNum = rand(0,32000)*rand(0,32000);
	$strRefundVendorTxCode="REF-" . $intRandNum . "-" . substr($strVendorTxCode,0,20);
	$strRefundDescription="Refund against " . $strVendorTxCode;

	/** We can work out the default amount to refund as the original transaction value - the total valuue of all refunds against it **
	*** If this is Zero, then we can refund no more.  Show an error message in these circumstance **/
	$sngRefundAmount=$sngAmount;
	
	$strSQL="SELECT Amount FROM tblOrders where RelatedVendorTxCode='" . $strVendorTxCode . "' and TxType='REFUND'"; 
	$rsPrimary = mysql_query($strSQL)
		or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
	
	while ($row = mysql_fetch_array($rsPrimary)) 
	{
		$sngRefundAmount=$sngRefundAmount-$row["Amount"];
	}
	$rsPrimary = "";

	if ($sngRefundAmount<=0.0)
	{
		$strStatus="ERROR";
		$strResult="You cannot REFUND this transaction.  You've already fully refunded it.";
	}	

}

?>
<html>
<head>
	<title>Sage Pay Direct PHP Kit Refund Transaction Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/directKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Refund Transaction Page</div>
            <p>
			<? if (strlen($strStatus==0))
			{
			echo "
			This page formats a REFUND POST to send to the Direct, to refund against the transaction you selected in the Order Admin area.  The details are displayed below.  If you wish to go ahead with the Refund, check the Refund Amount and click Proceed, otherwise click Back to go Back to the admin area.<br>
			<br>
			The code for this page can be found in the refund.php file.";
			}
	      	else
			{
			echo "
			The tables below show the results of the REFUND POST.  Click Back to return to the Order Admin area.";
		  	}
			?>
			</p>
			<div class="greyHzShadeBar">&nbsp;</div>
			<? 
			if (strlen($strStatus)>0)
			{
			echo "
			<div class=\""; if ($strStatus=="OK") echo "infoheader"; else echo "errorheader"; echo "\">
				Direct returned a Status of " . $strStatus . "<br>
				<span class=\"warning\">" . $strResult . "</span>
			</div>";
			}
					  
			if (isset($arrResponse))
			{
			echo " 
			<table class=\"formTable\">
				<tr>
					<td colspan=\"2\"><div class=\"subheader\">POST Sent to Direct</div></td>
				</tr>
				<tr>
				  <td colspan=\"2\" style=\"word-wrap:break-word; word-break: break-all;\" class=\"code\">" . $strPost . "</td>
				</tr>
				<tr>
				  	<td colspan=\"2\"><div class=\"subheader\">Raw Response from Direct</div></td>
				</tr>
				<tr>
				  <td colspan=\"2\" style=\"word-wrap:break-word; word-break: break-all;\" class=\"code\">" . $_SESSION["rawresponse"] . "</td>
				</tr>
			</table>
			<div class=\"greyHzShadeBar\">&nbsp;</div>
			<form name=\"adminform\" action=\"refund.php\" method=\"POST\">
			<input type=\"hidden\" name=\"navigate\" value=\"\" />
			<div class=\"formFooter\">
				<a href=\"javascript:submitForm('adminform','admin');\" title=\"\"  style=\"float: left;\"><img src=\"images/back.gif\" alt=\"Back to Order Admin\" border=\"0\"></a>
			</div>
			</form>";				
			}
			else
			{
			echo "
			<form name=\"adminform\" action=\"refund.php\" method=\"POST\">
			<input type=\"hidden\" name=\"navigate\" value=\"\" />
			<input type=\"hidden\" name=\"VendorTxCode\" value=\"" . $strVendorTxCode . "\">
			<table class=\"formTable\">
				<tr>
					<td colspan=\"2\"><div class=\"subheader\">REFUND the following transaction</div></td>
				</tr>
				<tr>
					<td colspan=\"2\"><p>You've chosen to Refund the transaction shown
					below. You must specify the Refund details in the boxes below. You can Refund for any amount up
					to and including the amount of the original transaction (minus any other refunds you've done against
					this transaction).</p></td>
				</tr>
				<tr>
					<td class=\"fieldLabel\">VendorTxCode:</td>
					<td class=\"fieldData\">" . $strVendorTxCode . "</td>
				</tr>
				<tr>
					<td class=\"fieldLabel\">VPSTxId:</td>
					<td class=\"fieldData\">" . $strVPSTxId . "</td>
				</tr>
				<tr>
					<td class=\"fieldLabel\">SecurityKey:</td>
					<td class=\"fieldData\">" . $strSecurityKey . "</td>
				</tr>
				<tr>
					<td class=\"fieldLabel\">TxAuthNo:</td>
					<td class=\"fieldData\">" . $strTxAuthNo . "</td>
				</tr>
				<tr>
					<td class=\"fieldLabel\">Refund VendorTxCode:</td>
					<td class=\"fieldData\"><input type=\"text\" name=\"RefundVendorTxCode\" size=\"40\" value=\"" . $strRefundVendorTxCode . "\"></td>
				</tr>
				<tr>
					<td class=\"fieldLabel\">Refund Description:</td>
					<td class=\"fieldData\"><input type=\"text\" name=\"RefundDescription\" size=\"50\" value=\"" . $strRefundDescription . "\"></td>
				</tr>
				<tr>
					<td class=\"fieldLabel\">Refund Amount:</td>
					<td class=\"fieldData\"><input type=\"text\" name=\"RefundAmount\" size=\"10\" value=\"" . number_format($sngRefundAmount,2) ."\"></td>
				</tr>
			</table>
			<div class=\"greyHzShadeBar\">&nbsp;</div>
			<div class=\"formFooter\">
				<a href=\"javascript:submitForm('adminform','admin');\" title=\"\"  style=\"float: left;\"><img src=\"images/back.gif\" alt=\"Back to Order Admin\" border=\"0\"></a>
				<a href=\"javascript:submitForm('adminform','proceed');\" title=\"Proceed\"  style=\"float: right;\"><img src=\"images/proceed.gif\" alt=\"Proceed\" border=\"0\"></a>
			</div>
			</form>";
			}
			?>					
		</div>
	</div>
</body>
</html>


