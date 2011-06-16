<?
include("includes.php");
 
/**************************************************************************************************
* Direct PHP Abort Transaction Page
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

* This page builds an ABORT POST first for display on screen, for you to view to contents,
* then POSTs that data to the Gateway to Abort the transaction you selected, displaying the
* results and updating the database accordingly.
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

/** Now to build the Direct POST.  For more details see the Sage Pay Server and Direct Shared Protocols 2.23 **
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
}
else
{
	$strStatus="ERROR";
	$strResult="ERROR : Cannot retrieve the original transaction data from the database.";
}

$strPost="VPSProtocol=" . $strProtocol;
$strPost=$strPost . "&TxType=ABORT";
$strPost=$strPost . "&Vendor=" . $strVendorName;
$strPost=$strPost . "&VendorTxCode=" . $strVendorTxCode;
$strPost=$strPost . "&VPSTxId=" . $strVPSTxId;
$strPost=$strPost . "&SecurityKey=" . $strSecurityKey;
$strPost=$strPost . "&TxAuthNo=" . $strTxAuthNo;


//Check for the proceed button click, and if so, go validate the order
if ($_REQUEST["navigate"]=="proceed")
{
	/** Now POST the data.
	*** Data is posted to strAbortURL which is set depending on whether you are using SIMULATOR, TEST or LIVE **/
	$arrResponse = requestPost($strAbortURL, $strPost);
					
	//Analyse the response from Direct to check that everything is okay
	$strStatus=$arrResponse["Status"];
		
	if ($strStatus == "OK")
	{
		//An OK status means that the transaction has been successfully Aborted **
		$strResult="SUCCESS : The transaction was Aborted successfully.";

		//Update the original transaction to mark that it has been Aborted
		$strSQL="UPDATE tblOrders SET Status='ABORTED - Successful DEFERRED transaction subsequently Aborted'
		WHERE VendorTxCode='" . $strVendorTxCode . "'";
		
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

?>
<html>
<head>
	<title>Sage Pay Direct PHP Kit Abort Transaction Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/directKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Abort Transaction Page</div>
           	<p>
			<? if (strlen($strStatus==0))
			{
				echo "
		  		This page formats an ABORT POST to send to the Direct, to cancel the transaction you selected in the Order Admin area.  The POST is displayed below.  If you wish to go ahead with the Abort, click Proceed, otherwise click Back to go back to the admin area.<br>
				<br>
				The code for this page can be found in the abort.php file.";
			}
	      	else
			{
				echo "
		  		The table below shows the results of the ABORT.  Click Back to return to the Order Admin area.";
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
			?>		  
			<table class="formTable">
				<tr>
					<td colspan="2"><div class="subheader">
					<? if (strlen($strResponse)==0) 
						echo "POST to be Sent to Direct";
					else
						echo "POST Sent to Direct";
					?></div>
					</td>
				</tr>
				<tr>
				  <td colspan="2" style="word-wrap:break-word; word-break: break-all;" class="code"><? echo $strPost ?></td>
				</tr>
				<? if (isset($arrResponse))
				echo" 
				<tr>
				  <td colspan=\"2\"><div class=\"subheader\">Raw Response from Direct</div></td>
				</tr>
				<tr>
				  <td colspan=\"2\" style=\"word-wrap:break-word; word-break: break-all;\" class=\"code\">" . $_SESSION["rawresponse"] . "</td>
				</tr>";
				?>
			</table>
			<div class="greyHzShadeBar">&nbsp;</div>
			<form name="adminform" action="abort.php" method="POST">
			<input type="hidden" name="navigate" value="" />
			<input type="hidden" name="VendorTxCode" value="<? echo $strVendorTxCode; ?>">
			<div class="formFooter">
				<a href="javascript:submitForm('adminform','admin');" title="" style="float: left;"><img src="images/back.gif" alt="Back to Order Admin" border="0"></a>
				<? if (!isset($arrResponse)) { ?>
					<a href="javascript:submitForm('adminform','proceed');" title="Proceed" style="float: right;"><img src="images/proceed.gif" alt="Proceed" border="0"></a>
				<? } ?>
			</div>
			</form>
		</div>
	</div>
</body>
</html>


