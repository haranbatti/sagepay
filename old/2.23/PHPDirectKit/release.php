<?
include("includes.php");

/**************************************************************************************************
* Sage Pay Direct PHP Release Transaction Page
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

* This page perform builds a RELEASE POST based on the details you enter here.  The contents of the
* POST and the response are then displayed on screen and the database updated accordingly
***************************************************************************************************/

//Check we have a vendortxcodee session.  If not, or of the user clicked back then return to the orderAdmin screen.
$strVendorTxCode=$_REQUEST["VendorTxCode"];
if ($_REQUEST["navigate"]=="admin")
{
	ob_flush();
	session_destroy();
	redirect("orderAdmin.php");
	exit();
}

$strResult="";
$strPost="";

/** Now to build the Sage Pay Direct POST.  For more details see the Server and Direct Shared Protocols 2.23 **
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

// Check for the proceed button click, and if so, go validate the order
if ($_REQUEST["navigate"]=="proceed")
{  

	//Validate the release amount.  It must be a number, greater than 0 and less than the original auth amount of the DEFERRED **
	$strReleaseAmount=cleaninput($_REQUEST["ReleaseAmount"],"Number");
	
	if (strlen($strReleaseAmount)==0 || !is_numeric($strReleaseAmount))
	{
		$strStatus="ERROR";
		$strResult="ERROR : You need to specify an amount to release which is less than or equal to the amount originally authorised.";
	}
	else
	{
		$sngReleaseAmount=$strReleaseAmount;
		
		if (($sngReleaseAmount<=0) || ($sngReleaseAmount>$sngAmount))
		{
			$strStatus="ERROR";
			$strResult="ERROR : You need to specify an amount to release which is less than or equal to the amount originally authorised.";
		}
		else
		{
			//Build the RELEASE message
			$strPost="VPSProtocol=" . $strProtocol;
			$strPost=$strPost . "&TxType=RELEASE";
			$strPost=$strPost . "&Vendor=" . $strVendorName;
			$strPost=$strPost . "&VendorTxCode=" . $strVendorTxCode;
			$strPost=$strPost . "&VPSTxId=" . $strVPSTxId;
			$strPost=$strPost . "&SecurityKey=" . $strSecurityKey;
			$strPost=$strPost . "&TxAuthNo=" . $strTxAuthNo;
			$strPost=$strPost . "&ReleaseAmount=" . number_format($sngReleaseAmount,2);
		
			/** Now POST the data.
			*** Data is posted to strReleaseURL which is set depending on whether you are using SIMULATOR, TEST or LIVE **/
			
			$arrResponse = requestPost($strReleaseURL, $strPost);
					
			//Analyse the response from Direct to check that everything is okay
			$strStatus=$arrResponse["Status"];
		
			if ($strStatus == "OK")
			{
				//An OK status mean that the transaction has been successfully Refunded
				$strResult="SUCCESS : The transaction was RELEASED successfully.";

				//Update the original transaction to mark that it has been Releaseed. Update the amount if necessary
				if ($sngAmount==$sngReleaseAmount)
				{
					$strSQL="UPDATE tblOrders SET Status='RELEASED - Successful DEFERRED transaction subsequently Released'
					WHERE VendorTxCode='" . $strVendorTxCode . "'";
				}
				else
				{
					$strSQL="UPDATE tblOrders SET Status='RELEASED - Successful DEFERRED transaction, 
					Released for lower amount (original Auth was for: " . number_format($sngAmount,2) . "
					Amount=" . number_format($sngReleaseAmount,2) . ")' WHERE VendorTxCode='" . $strVendorTxCode . "'";
				}
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

?>
<html>
<head>
	<title>Sage Pay Direct PHP Kit Release Transaction Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/directKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
</head>
<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Release Transaction Page</div>
            <p>
			<? if (strlen($strStatus)==0)
			{
				echo " 
		  		This page formats an Release POST to send to the Direct, to cancel the DEFERRED transaction you selected in the Order Admin area.  The POST is displayed below.  If you wish to go ahead with the Release, check the ReleaseAmount and click Proceed, otherwise click Back to go Back to the admin area.<br>
				<br>
				The code for this page can be found in the release.php file.";
			}
	      	else
			{
				echo "
		  		The tables below show the results of the Release POST.  Click Back to return to the Order Admin area.";
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
				<form name=\"adminform\" action=\"release.php\" method=\"POST\">
				<input type=\"hidden\" name=\"navigate\" value=\"\" />
				<div class=\"formFooter\">
					<a href=\"javascript:submitForm('adminform','admin');\" title=\"\"  style=\"float: left;\"><img src=\"images/back.gif\" alt=\"Back to Order Admin\" border=\"0\"></a>
				</div>
				</form>";
			}		
			else
			{
				echo "
				<form name=\"adminform\" action=\"release.php\" method=\"POST\">
				<input type=\"hidden\" name=\"VendorTxCode\" value=\"" . $strVendorTxCode . "\">
				<input type=\"hidden\" name=\"navigate\" value=\"\" />
				<table class=\"formTable\">
					<tr>
						<td colspan=\"2\"><div class=\"subheader\">RELEASE the following DEFERRED transaction</div></td>
					</tr>
					<tr>
						<td colspan=\"2\">
							<p>You've chosen to RELEASE the DEFERRED transaction shown
							below. You must specify a ReleaseAmount. This can be anything up to and including the full value of the
							original DEFERRED transaction (the default value in the box below). You can only RELEASE once, not
							multiple times, so DEFERRED cannot be used for part shipments (use AUTHENTICATE and AUTHORISE
							if you need that functionality).</p></td>
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
						<td class=\"fieldLabel\">ReleaseAmount:</td>
						<td class=\"fieldData\"><input type=\"text\" name=\"ReleaseAmount\" size=\"10\" value=\"" . number_format($sngAmount,2) . "\"></td>
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


