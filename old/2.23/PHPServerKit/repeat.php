<?
include("includes.php");
 
/**************************************************************************************************
* Server PHP Repeat Transaction Page
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

* This page perform builds a Repeat POST based on the details you enter here.  The contents of the
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

/** Now to build the Server POST.  For more details see the Server and Direct Shared Protocols 2.23 **
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
	$strRepeatAmount=cleaninput($_REQUEST["RepeatAmount"],"Number");
	$strRepeatDescription=cleaninput($_REQUEST["RepeatDescription"],"Text");
	$strRepeatVendorTxCode=cleaninput($_REQUEST["RepeatVendorTxCode"],"VendorTxCode");
	
	//Validate the Repeat amount.  It must be a number, greater than 0
	if (strlen($strRepeatAmount)==0)
	{
		$strStatus="ERROR";
		$strResult="ERROR : You need to specify an amount to Repeat.  This can be any amount greater than zero.";
	}
	elseif (strlen($strRepeatVendorTxCode)==0)
	{
		$strStatus="ERROR";
		$strResult="ERROR : You need to specify a new VendorTxCode for this Repeat transaction.";
	}
	elseif (strlen($strRepeatDescription)==0)
	{
		$strStatus="ERROR";
		$strResult="ERROR : You need to enter a Description of this Repeat transaction.";
	}
	else
	{
		
		$sngRepeatAmount=$strRepeatAmount;
		
		if ($sngRepeatAmount<=0)
		{
			$strStatus="ERROR";
			$strResult="ERROR : You need to specify an amount to Repeat.  This can be any amount greater than zero.";
		}
		else
		{
			//Build the Repeat message
			$strPost="VPSProtocol=" . $strProtocol;
			$strPost=$strPost . "&TxType=REPEAT";
			$strPost=$strPost . "&Vendor=" . $strVendorName;
			$strPost=$strPost . "&VendorTxCode=" . $strRepeatVendorTxCode;
			$strPost=$strPost . "&Amount=" . number_format($sngRepeatAmount,2);
			$strPost=$strPost . "&Currency=" . $strCurrency;
			$strPost=$strPost . "&Description=" . $strRepeatDescription;
			$strPost=$strPost . "&RelatedVPSTxId=" . $strVPSTxId;
			$strPost=$strPost . "&RelatedVendorTxCode=" . $strVendorTxCode;
			$strPost=$strPost . "&RelatedSecurityKey=" . $strSecurityKey;
			$strPost=$strPost . "&RelatedTxAuthNo=" . $strTxAuthNo;
						
			/** Now POST the data.
			*** Data is posted to strRepeatURL which is set depending on whether you are using SIMULATOR, TEST or LIVE **/
			$arrResponse = requestPost($strRepeatURL, $strPost);
											
			//Analyse the response from Server to check that everything is okay
			$strStatus=$arrResponse["Status"];
		
			if ($strStatus == "OK")
			{
				//An OK status means that the transaction has been successfully Repeated **
				$strResult="SUCCESS : The transaction was REPEATED successfully and a new REPEAT transaction was created.";

				//Get the other values from the POST for storage in the database
				$strRepeatVPSTxId=$arrResponse["VPSTxId"];
				$strRepeatTxAuthNo=$arrResponse["TxAuthNo"];
				$strRepeatSecurityKey=$arrResponse["SecurityKey"];
								
				//Create the new Repeat transaction in the database, linked through to the original transaction **
				$strSQL="INSERT INTO tblOrders(VendorTxCode,TxType,Amount,Currency,VPSTxId,SecurityKey,TxAuthNo,RelatedVendorTxCode,Status)VALUES(";
				$strSQL=$strSQL . "'" . $strRepeatVendorTxCode . "',";
				$strSQL=$strSQL . "'REPEAT',";
				$strSQL=$strSQL . number_format($strRepeatAmount,2) . ",";
				$strSQL=$strSQL . "'" . $strCurrency . "',";
				$strSQL=$strSQL . "'" . $strRepeatVPSTxId . "',";
				$strSQL=$strSQL . "'" . $strRepeatSecurityKey . "',";
				$strSQL=$strSQL . $strRepeatTxAuthNo . ",";
				$strSQL=$strSQL . "'" . $strVendorTxCode . "',";
				$strSQL=$strSQL . "'AUTHORISED - REPEAT transaction taken through Order Admin area.'";
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
	/** Since no buttons have been clicked, generate a random VendorTxCode for this Repeat, based on on the original VendorTxCode **
	*** You can edit this in the boxes provided, but you probably wouldn't in a production environment **/
	$intRandNum = rand(0,32000)*rand(0,32000);
	$strRepeatVendorTxCode="REP-" . $intRandNum . "-" . substr($strVendorTxCode,0,20);
	$strRepeatDescription="REPEAT against " . $strVendorTxCode;
}

?>
<html>
<head>
	<title>Sage Pay Server PHP Kit Repeat Transaction Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/serverKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Repeat Transaction Page</div>
            <p>
			<? if (strlen($strStatus==0))
			{
			echo "
		  	This page formats a REPEAT POST to send to the Server, to Repeat against the transaction you selected in the Order Admin area.  The details are displayed below.  If you wish to go ahead with the Repeat, check the Repeat Amount and click Proceed, otherwise click Back to go Back to the admin area.<br>
			<br>
			The code for this page can be found in the repeat.php file.";
			}
	      	else
			{
			echo "
		  	The tables below show the results of the Repeat POST.  Click Back to return to the Order Admin area.";
		  	}
			?>
		 	</p>
			<div class="greyHzShadeBar">&nbsp;</div>		
			<? 
			if (strlen($strStatus)>0)
			{
			echo "
			<div class=\""; if ($strStatus=="OK") echo "infoheader"; else echo "errorheader"; echo "\">
				Server returned a Status of " . $strStatus . "<br>
				<span class=\"warning\">" . $strResult . "</span>
			</div>";
			}
					  
			if (strlen($strResponse)<>0)
			{
			echo " 
			<table class=\"formTable\">
				<tr>
					<td colspan=\"2\"><div class=\"subheader\">POST Sent to Server</div></td>
				</tr>
				<tr>
				  <td colspan=\"2\" style=\"word-wrap:break-word; word-break: break-all;\" class=\"code\">" . $strPost . "</td>
				</tr>
				<tr>
				  	<td colspan=\"2\"><div class=\"subheader\">Raw Response from Server</div></td>
				</tr>
				<tr>
				  <td colspan=\"2\" style=\"word-wrap:break-word; word-break: break-all;\" class=\"code\">" . $strResponse . "</td>
				</tr>
			</table>
			<div class=\"greyHzShadeBar\">&nbsp;</div>
			<form name=\"adminform\" action=\"repeat.php\" method=\"POST\">
			<input type=\"hidden\" name=\"navigate\" value=\"\" />
			<div class=\"formFooter\">
				<a href=\"javascript:submitForm('adminform','admin');\" title=\"\"  style=\"float: left;\"><img src=\"images/back.gif\" alt=\"Back to Order Admin\" border=\"0\"></a>
			</div>
			</form>";				
			}
			else
			{
			echo "
			<form name=\"adminform\" action=\"repeat.php\" method=\"POST\">
			<input type=\"hidden\" name=\"VendorTxCode\" value = \"" . $strVendorTxCode . "\"> 
			<input type=\"hidden\" name=\"navigate\" value=\"\" />
			<table class=\"formTable\">
				<tr>
					<td colspan=\"2\"><div class=\"subheader\">REPEAT the following transaction</div></td>
				</tr>
				<tr>
					<td colspan=\"2\">
						<p>You've chosen to Repeat the transaction shown
						below. You must specify the REPEAT details in the boxes below. You can REPEAT for any amount in 
						any currency your account can support (NB:these kits use only your default currency).</p></td>
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
					<td class=\"fieldLabel\">Repeat VendorTxCode:</td>
					<td class=\"fieldData\"><input type=\"text\" name=\"RepeatVendorTxCode\" size=\"40\" value=\"" . $strRepeatVendorTxCode . "\"></td>
				</tr>
				<tr>
					<td class=\"fieldLabel\">Repeat Description:</td>
					<td class=\"fieldData\"><input type=\"text\" name=\"RepeatDescription\" size=\"50\" value=\"" . $strRepeatDescription . "\"></td>
				</tr>
				<tr>
					<td class=\"fieldLabel\">Repeat Amount:</td>
					<td class=\"fieldData\"><input type=\"text\" name=\"RepeatAmount\" size=\"10\" value=\"" . number_format($sngRepeatAmount,2) ."\"></td>
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


