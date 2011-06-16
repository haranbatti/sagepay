<?
include("includes.php");
 
/**************************************************************************************************
* Sage Pay Direct PHP Kit 3D-Completion Page
***************************************************************************************************
*
***************************************************************************************************
* Change history
* ==============
*
* 11/02/2009 - Simon Wolfe - Updated for VSP protocol 2.23
* 18/12/2007 - Nick Selby - New PHP kit version adapted from ASP
***************************************************************************************************
* Description
* ===========
*
* This page is the 3D-Secure completion page that redeives the MD and PaRes from the Issuing Bank 
* site, POSTs it to Sage Pay, then reads the authorisation response and updates the database accordingly.
***************************************************************************************************/

if ($_REQUEST["navigate"]=="back")
{
	ob_end_flush();
	redirect("orderConfirmation.php");
	exit();
}

if ($_REQUEST["navigate"]=="proceed") {
	//The user wants to proceed to the confirmation page.  Send them there
	redirect($_REQUEST["CompletionURL"]);
	exit();
}

$strCart=$_SESSION["strCart"];

//Otherwise, create the POST for Sage Pay ensuring to URLEncode the PaRes before sending it
$strMD = $_REQUEST["MD"];
$strPaRes=$_REQUEST["PARes"];
$strVendorTxCode=$_SESSION["VendorTxCode"];

// POST for Sage Pay Direct 3D completion page
$strPost = "MD=" . $strMD . "&PARes=" . urlencode($strPaRes);

//Use cURL to POST the data directly from this server to Sage Pay. cURL connection code is in includes.php.
$arrResponse = requestPost($str3DCallbackPage, $strPost);
	  
//Analyse the response from Sage Pay Direct to check that everything is okay
$arrStatus=split(" ",$arrResponse["Status"]);
$strStatus=array_shift($arrStatus);
$arrStatusDetail=split("=",$arrResponse["StatusDetail"]);
$strStatusDetail = array_shift($arrStatusDetail);
		
//Get the results form the POST if they are there
$arrVPSTxId=split(" ",$arrResponse["VPSTxId"]);
$strVPSTxId=array_shift($arrVPSTxId);
$arrSecurityKey=split(" ",$arrResponse["SecurityKey"]);
$strSecurityKey=array_shift($arrSecurityKey);
$arrTxAuthNo=split(" ",$arrResponse["TxAuthNo"]);
$strTxAuthNo=array_shift($arrTxAuthNo);
$arrAVSCV2=split(" ",$arrResponse["AVSCV2"]);
$strAVSCV2=array_shift($arrAVSCV2);
$arrAddressResult=split(" ",$arrResponse["AddressResult"]);
$strAddressResult=array_shift($arrAddressResult);
$arrPostCodeResult=split(" ",$arrResponse["PostCodeResult"]);
$strPostCodeResult=array_shift($arrPostCodeResult);
$arrCV2Result=split(" ",$arrResponse["CV2Result"]);
$strCV2Result=array_shift($arrCV2Result); 
$arr3DSecureStatus=split(" ",$arrResponse["3DSecureStatus"]);
$str3DSecureStatus=array_shift($arr3DSecureStatus);
$arrCAVV=split(" ",$arrResponse["CAVV"]);
$strCAVV=array_shift($arrCAVV);

//Update the database and redirect the user appropriately
if ($strStatus=="OK")
	$strDBStatus="AUTHORISED - The transaction was successfully authorised with the bank.";
elseif ($strStatus=="MALFORMED")
	$strDBStatus="MALFORMED - The StatusDetail was:" . mysql_real_escape_string(substr($strStatusDetail,0,255));
elseif ($strStatus=="INVALID")
	$strDBStatus="INVALID - The StatusDetail was:" . mysql_real_escape_string(substr($strStatusDetail,0,255));
elseif ($strStatus=="NOTAUTHED")
	$strDBStatus="DECLINED - The transaction was not authorised by the bank.";
elseif ($strStatus=="REJECTED")
	$strDBStatus="REJECTED - The transaction was failed by your 3D-Secure or AVS/CV2 rule-bases.";
elseif ($strStatus=="AUTHENTICATED")
	$strDBStatus="AUTHENTICATED - The transaction was successfully 3D-Secure Authenticated and can now be Authorised.";
elseif ($strStatus=="REGISTERED")
	$strDBStatus="REGISTERED - The transaction was could not be 3D-Secure Authenticated, but has been registered to be Authorised.";
elseif ($strStatus=="ERROR")
	$strDBStatus="ERROR - There was an error during the payment process.  The error details are: " . mysql_real_escape_string($strStatusDetail);
else
	$strDBStatus="UNKNOWN - An unknown status was returned from Sage Pay.  The Status was: " . mysql_real_escape_string($strStatus) . ", with StatusDetail:" . mysql_real_escape_string($strStatusDetail);
	
$strSQL="UPDATE tblOrders set Status='" . mysql_real_escape_string($strDBStatus) . "'";
if (strlen($strVPSTxId)>0) $strSQL=$strSQL . ",VPSTxId='" . mysql_real_escape_string($strVPSTxId) . "'";
if (strlen($strSecurityKey)>0) $strSQL=$strSQL . ",SecurityKey='" . mysql_real_escape_string($strSecurityKey) . "'";
if (strlen($strTxAuthNo)>0) $strSQL=$strSQL . ",TxAuthNo=" . mysql_real_escape_string($strTxAuthNo);
if (strlen($strAVSCV2)>0) $strSQL=$strSQL . ",AVSCV2='" . mysql_real_escape_string($strAVSCV2) . "'";
if (strlen($strAddressResult)>0) $strSQL=$strSQL . ",AddressResult='" . mysql_real_escape_string($strAddressResult) . "'";
if (strlen($strPostCodeResult)>0) $strSQL=$strSQL . ",PostCodeResult='" . mysql_real_escape_string($strPostCodeResult) . "'";
if (strlen($strCV2Result)>0) $strSQL=$strSQL . ",CV2Result='" . mysql_real_escape_string($strCV2Result) . "'";
if (strlen($strGiftAid)>0) $strSQL=$strSQL . ",GiftAid=" . mysql_real_escape_string($strGiftAid);
if (strlen($str3DSecureStatus)>0) $strSQL=$strSQL . ",ThreeDSecureStatus='" . mysql_real_escape_string($str3DSecureStatus) . "'";
if (strlen($strCAVV)>0) $strSQL=$strSQL . ",CAVV='" . mysql_real_escape_string($strCAVV) . "'";
$strSQL=$strSQL . " where VendorTxCode='" . mysql_real_escape_string($strVendorTxCode) . "'";
							
$rsPrimary = mysql_query($strSQL)
	or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');

$strSQL="";
$rsPrimary="";

//Work out where to send the customer
$_SESSION["VendorTxCode"]=$strVendorTxCode;
if ($strStatus=="OK" || $strStatus=="AUTHENTICATED" || $strStatus=="REGISTERED")
	$strCompletionURL="orderSuccessful.php";
else {
	$strCompletionURL="orderFailed.php";
	$strPageError=$strDBStatus;
}
	
//Finally, if we're in LIVE then go straight to the success page
//In other modes, we allow this page to display and ask for Proceed to be clicked
if ($strConnectTo=="LIVE"){
	ob_end_flush();
	redirect($strCompletionURL);
}


?>
<html>
<head>
	<title>Direct PHP Kit 3D-Secure Completion Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/directKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">3D-Secure Completion Page</div>
			<?
			if (strlen($strPageError)==0)
			{ 
			//There are no errors to display, so show the detail of the POST to Sage Pay Direct							
			echo
			"<p>This page shows the contents of the POST sent to Sage Pay Direct (based on your selections on the previous screens)
			and the response sent back by the system. Because you are in SIMULATOR mode, you are seeing this information
			and having to click Proceed to continue to the payment pages. In LIVE mode, the POST and redirect 
			happen invisibly, with no information sent to the browser and no user involvement.</p>";
			}
			else
			{
			//An error occurred during transaction registration. Show the details here
			echo
			"<p>A problem occurred whilst attempting to register this transaction with the Sage Pay systems.
			The details of the error are shown below. This information is provided for your own debugging 
			purposes and especially once LIVE you should avoid displaying this level of detail to your customers. 
			Instead you should modify the transactionRegistration.php page to automatically handle these errors and 
			redirect your customer appropriately (e.g. to an error reporting page, or alternative customer 
			services number to offline payment)</p>";
			}
			
			echo 
			"<div class=\"greyHzShadeBar\">&nbsp;</div>
			<div class=\"";
			  	if ($strStatus=="OK" || $strStatus=="AUTHENTICATED" || $strStatus=="REGISTERED")
					echo "infoheader"; 
				else 
					echo "errorheader";
			  	echo
				"\">Sage Pay Direct returned a Status of " . $strStatus . "<br>
				<span class=\"warning\" >" . $strPageError . "</span>
			</div>";
			  
			if ($strConnectTo!=="LIVE") { 
			//NEVER show this level of detail when the account is LIVE%>
			echo "
			<table class=\"formTable\">
			<tr>
			  <td colspan=\"2\"><div class=\"subheader\">Post Sent to Sage Pay Direct</div></td>
			</tr>
			<tr>
			  <td colspan=\"2\" class=\"code\" style=\"word-break: break-all; word-wrap: break-word;\">" . $strPost . "</td>
			</tr>
			<tr>
			  <td colspan=\"2\"><div class=\"subheader\">Reply from Sage Pay Direct</div></td>
			</tr>   
			<tr>
			  <td colspan=\"2\" class=\"code\" style=\"word-break: break-all; word-wrap: break-word;\">" . $_SESSION["rawresponse"] . "</td>
			</tr>
			<tr>
			  <td colspan=\"2\"><div class=\"subheader\">Order Details stored in your Database</div></td>
			</tr>
			<tr>
				<td class=\"fieldLabel\">VendorTxCode:</td>
				<td class=\"fieldData\">" . $strVendorTxCode ."</td>
			</tr>
			<tr>
				<td class=\"fieldLabel\">VPSTxId:</td>
				<td class=\"fieldData\">" . $strVPSTxId . "</td>
			</tr>";
			if (strlen($strSecurityKey)>0) {
			echo "
			<tr>
				<td class=\"fieldLabel\">SecurityKey:</td>
				<td class=\"fieldData\">" . $strSecurityKey . "</td>
			</tr>";
			}
			if (strlen($strTxAuthNo)>0) {
			echo "
			<tr>
				<td class=\"fieldLabel\">TxAuthNo:</td>
				<td class=\"fieldData\">" . $strTxAuthNo . "</td>
			</tr>";
			}
			if (strlen($str3DSecureStatus)>0) {
			echo "
			<tr>
				<td class=\"fieldLabel\">3D-Secure Status:</td>
				<td class=\"fieldData\">" . $str3DSecureStatus . "</td>
			</tr>";
			}
			if (strlen($strCAVV)>0) {
			echo "
			<tr>
				<td class=\"fieldLabel\">CAVV:</td>
				<td class=\"fieldData\">" . $strCAVV . "</td>
			</tr>";
			}
			echo
			"
			<tr>
				<td class=\"fieldLabel\">Basket Contents:</td>
				<td class=\"fieldData\">
					<table width=\"100%\" style=\"border-collapse: collapse;\">
						<tr class=\"greybar\">
							<td width=\"10%\" align=\"right\">Quantity</td>
							<td width=\"30%\" align=\"center\">Image</td>
							<td width=\"60%\" align=\"left\">Title</td>
						</tr>";
			
						
						$strThisEntry=$strCart;
						while (strlen($strThisEntry)>0)
						{
							// Extract the quantity and Product from the list of "x of y," entries in the cart
							$iQuantity=cleanInput(substr($strThisEntry,0,1),"Number");
							$iProductId=substr($strThisEntry,strpos($strThisEntry,",")-1,1);
							//Get product details from database
							$strSQL = "SELECT * FROM tblProducts where ProductId=" . $iProductId . "";
							$rsPrimary = mysql_query($strSQL)
								or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
							$row = mysql_fetch_array($rsPrimary);
															
							$sngTotal=$sngTotal + ($iQuantity * $row["Price"]);
							$strImageId = "00" . $iProductId;
							
							echo "<tr>";
							echo "<td align=\"right\">" . $iQuantity . "</td>";
							echo "<td align=\"center\"><img src=\"images/dvd" . substr($strImageId,strlen($strImageId)-2,2) .  "small.gif\" alt=\"DVD box\"></td>";
							echo "<td align=\"left\">" . $row["Description"] ."</td>";
							echo "</tr>";
								
							// Move to the next cart entry, if there is one
							$pos=strpos($strThisEntry,",");
							if ($pos==0) 
								$strThisEntry="";
							else
								$strThisEntry=substr($strThisEntry,$pos+1);
						}
						echo
						"	
					</table>
				</td>
			</tr>
			</table>	
			<div class=\"greyHzShadeBar\">&nbsp;</div>					
			<form name=\"customerform\" method=\"POST\">
			<input type=\"hidden\" name=\"navigate\" value=\"\" />
			<input type=\"hidden\" name=\"CompletionURL\" value=\"" . $strCompletionURL . "\">
			<input type=\"hidden\" name=\"PageState\" value=\"Completion\">
			<div class=\"formFooter\">
			<a href=\"javascript:submitForm('customerform','back');\" title=\"Go back to the order confirmation page\" style=\"float: left;\"><img src=\"images/back.gif\" alt=\"Go back to the order confirmation page\" border=\"0\"></a>
			<a href=\"javascript:submitForm('customerform','proceed');\" title=\"Proceed to the completion screens\" style=\"float: right;\"><img src=\"images/proceed.gif\" alt=\"Proceed to the completion screens\" border=\"0\"></a>
			</div>
			</form>";
			}
			$strSQL="";
			$rsPrimary="";
			mysql_close();
			?>
		</div>
	</div>
</body>
</html>


