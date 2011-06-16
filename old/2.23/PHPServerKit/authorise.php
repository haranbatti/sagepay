<?
include("includes.php");
 
/**************************************************************************************************
* Server PHP Authorise Transaction Page
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

* This page perform builds a Authorise POST based on the details you enter here.  The contents of the
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
	$strAuthoriseAmount=cleaninput($_REQUEST["AuthoriseAmount"],"Number");
	$strAuthoriseDescription=cleaninput($_REQUEST["AuthoriseDescription"],"Text");
	$strAuthoriseVendorTxCode=cleaninput($_REQUEST["AuthoriseVendorTxCode"],"VendorTxCode");
	
	//Validate the Authorise amount.  It must be a number, greater than 0
	if (strlen($strAuthoriseAmount)==0 || !is_numeric($strAuthoriseAmount))
	{
		$strStatus="ERROR";
		$strResult="ERROR : You need to specify an amount to Authorise.  This can be any amount greater than zero.";
	}
	elseif (strlen($strAuthoriseVendorTxCode)==0)
	{
		$strStatus="ERROR";
		$strResult="ERROR : You need to specify a new VendorTxCode for this Authorise transaction.";
	}
	elseif (strlen($strAuthoriseDescription)==0)
	{
		$strStatus="ERROR";
		$strResult="ERROR : You need to enter a Description of this Authorise transaction.";
	}
	else
	{
		$sngAuthoriseAmount=$strAuthoriseAmount;

		//Now let's check we're not exceeding the amount of the original transaction minus any other Authorises
		$sngAmount=$sngAmount*1.15;
		$sngAmount=$sngAmount-$sngAuthoriseAmount;
		$strSQL="SELECT Amount FROM tblOrders where RelatedVendorTxCode='" . $strVendorTxCode . "' and TxType='AUTHORISE'";
		$rsPrimary = mysql_query($strSQL)
			or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
		
		while ($row = mysql_fetch_array($rsPrimary)) 
		{
			$sngAmount=$sngAmount-$row["Amount"];
		}	
		$rsPrimary="";
		
		if ($sngAuthoriseAmount<=0.0)
		{
			$strStatus="ERROR";
			$strResult="ERROR : You need to specify an amount to Authorise.  This can be any amount greater than zero up to 115% the value of the AUTHENTICATE.";
		}
		elseif ($sngAmount<0.0)
		{
			$strStatus="ERROR";
			$strResult="ERROR : The Authorise would exceed 115% of the Amount of the AUTHENTICATE.  Cannot perform the Authorise.";
		}
		else
		{
			//Build the Authorise message
			$strPost="VPSProtocol=" . $strProtocol;
			$strPost=$strPost . "&TxType=AUTHORISE";
			$strPost=$strPost . "&Vendor=" . $strVendorName;
			$strPost=$strPost . "&VendorTxCode=" . $strAuthoriseVendorTxCode;
			$strPost=$strPost . "&Amount=" . number_format($sngAuthoriseAmount,2);
			$strPost=$strPost . "&Description=" . $strAuthoriseDescription;
			$strPost=$strPost . "&RelatedVPSTxId=" . $strVPSTxId;
			$strPost=$strPost . "&RelatedVendorTxCode=" . $strVendorTxCode;
			$strPost=$strPost . "&RelatedSecurityKey=" . $strSecurityKey;
			$strPost=$strPost . "&RelatedTxAuthNo=" . $strTxAuthNo;
			$strPost=$strPost . "&ApplyAVSCV2=0";
		
			/** Now POST the data.
			*** Data is posted to strAuthoriseURL which is set depending on whether you are using SIMULATOR, TEST or LIVE **/
			
			$arrResponse = requestPost($strAuthoriseURL, $strPost);
					
			//Analyse the response from Server to check that everything is okay
			$arrStatus=split(" ",$arrResponse["Status"]);
			$strStatus=$arrResponse["Status"];
		
			if ($strStatus == "OK")
			{
				//An OK status mean that the transaction has been successfully Authoriseed **
				$strResult="SUCCESS : The transaction was Authorised successfully and a new Authorise transaction was created.";

				//Get the other values from the POST for storage in the database
				$strAuthoriseVPSTxId=$arrResponse["VPSTxId"];
				$strAuthoriseTxAuthNo=$arrResponse["TxAuthNo"];
				$strAuthoriseSecurityKey=$arrResponse["SecurityKey"];
				$strAuthoriseAVSCV2=$arrResponse["AVSCV2"];
				$strAuthoriseAddressResult=$arrResponse["AddressResult"];
				$strAuthorisePostCodeResult=$arrResponse["PostCodeResult"];				
				$strAuthoriseCV2Result=$arrResponse["CV2Result"];				
				
				//Create the new Authorise transaction in the database, linked through to the original transaction **
				$strSQL="INSERT INTO tblOrders(VendorTxCode,TxType,Amount,Currency,VPSTxId,SecurityKey,TxAuthNo,AVSCV2,
				AddressResult,PostCodeResult,CV2Result,RelatedVendorTxCode,Status)VALUES(";
				$strSQL=$strSQL . "'" . $strAuthoriseVendorTxCode . "',";
				$strSQL=$strSQL . "'AUTHORISE',";
				$strSQL=$strSQL . number_format($strAuthoriseAmount,2) . ",";
				$strSQL=$strSQL . "'" . $strCurrency . "',";
				$strSQL=$strSQL . "'" . $strAuthoriseVPSTxId . "',";
				$strSQL=$strSQL . "'" . $strAuthoriseSecurityKey . "',";
				$strSQL=$strSQL . $strAuthoriseTxAuthNo . ",";
				$strSQL=$strSQL . "'" . $strAuthoriseAVSCV2 . "',";
				$strSQL=$strSQL . "'" . $strAuthoriseAddressResult . "',";
				$strSQL=$strSQL . "'" . $strAuthorisePostCodeResult . "',";
				$strSQL=$strSQL . "'" . $strAuthoriseCV2Result . "',";
				$strSQL=$strSQL . "'" . $strVendorTxCode . "',";
				$strSQL=$strSQL . "'AUTHORISED - Authorise transaction taken through Order Admin area.'";
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
	/** Since no buttons have been clicked, generate a random VendorTxCode for this Authorise, based on on the original VendorTxCode **
	*** You can edit this in the boxes provided, but you probably wouldn't in a production environment **/
	$intRandNum = rand(0,32000)*rand(0,32000);
	$strAuthoriseVendorTxCode="AUTH-" . $intRandNum . "-" . substr($strVendorTxCode,0,20);
	$strAuthoriseDescription="AUTHORISE against " . $strVendorTxCode;

	/** We can work out the default amount to Authorise as the original transaction value - the total valuue of all Authorises against it **
	*** If this is Zero, then we can Authorise no more.  Show an error message in these circumstance **/
	$sngAuthoriseAmount=$sngAmount;
	
	$strSQL="SELECT Amount FROM tblOrders where RelatedVendorTxCode='" . $strVendorTxCode . "' and TxType='AUTHORISE'"; 
	$rsPrimary = mysql_query($strSQL)
		or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
	
	while ($row = mysql_fetch_array($rsPrimary)) 
	{
		$sngAuthoriseAmount=$sngAuthoriseAmount-$row["Amount"];
	}
	$rsPrimary = "";

	if ($sngAuthoriseAmount<=0.0)
	{
		$strStatus="ERROR";
		$strResult="You cannot Authorise this transaction.  You've already fully Authorised it.";
	}	

}

?>
<html>
<head>
	<title>Sage Pay Server PHP Kit Authorise Transaction Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/serverKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Authorise Transaction Page</div>
            <p>
			<? if (strlen($strStatus==0))
			{
				echo "
		  		This page formats an AUTHORISE POST to send to the Server, to Authorise against the AUTHENTICATED transaction you selected in the Order Admin area.  The details are displayed below.  If you wish to go ahead with the Authorise, check the Authorise Amount and click Proceed, otherwise click Back to go Back to the admin area.<br>
				<br>
				The code for this page can be found in the authorise.php file.";
			}
	      	else
			{
				echo "
		  		The tables below show the results of the AUTHORISE POST.  Click Back to return to the Order Admin area.";
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
					  
			if (isset($arrResponse))
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
				  <td colspan=\"2\" style=\"word-wrap:break-word; word-break: break-all;\" class=\"code\">" . $_SESSION["rawresponse"] . "</td>
				</tr>
			</table>
			<div class=\"greyHzShadeBar\">&nbsp;</div>
			<form name=\"adminform\" action=\"authorise.php\" method=\"POST\">
			<input type=\"hidden\" name=\"navigate\" value=\"\" />
			<div class=\"formFooter\">
				<a href=\"javascript:submitForm('adminform','admin');\" title=\"\"  style=\"float: left;\"><img src=\"images/back.gif\" alt=\"Back to Order Admin\" border=\"0\"></a>
			</div>
			</form>";				
			}
			else
			{
			echo "
			<form name=\"adminform\" action=\"authorise.php\" method=\"POST\">
			<input type=\"hidden\" name=\"navigate\" value=\"\" />
			<input type=\"hidden\" name=\"VendorTxCode\" value=\"" . $strVendorTxCode . "\">
			<table class=\"formTable\">
				<tr>
					<td colspan=\"2\"><div class=\"subheader\">Authorise the following transaction</div></td>
				</tr>
				<tr>
					<td colspan=\"2\"><p>You've chosen to Authorise the transaction shown
					below. You must specify the Authorise details in the boxes below. You can Authorise for any amount up
					to and including 115% the amount of the original transaction (minus any other Authorises you've done against
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
					<td class=\"fieldLabel\">Authorise VendorTxCode:</td>
					<td class=\"fieldData\"><input type=\"text\" name=\"AuthoriseVendorTxCode\" size=\"40\" value=\"" . $strAuthoriseVendorTxCode . "\"></td>
				</tr>
				<tr>
					<td class=\"fieldLabel\">Authorise Description:</td>
					<td class=\"fieldData\"><input type=\"text\" name=\"AuthoriseDescription\" size=\"50\" value=\"" . $strAuthoriseDescription . "\"></td>
				</tr>
				<tr>
					<td class=\"fieldLabel\">Authorise Amount:</td>
					<td class=\"fieldData\"><input type=\"text\" name=\"AuthoriseAmount\" size=\"10\" value=\"" . number_format($sngAuthoriseAmount,2) ."\"></td>
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


