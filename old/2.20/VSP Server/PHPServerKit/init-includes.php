<?
/*****************************************************
 Include this single file at the top of each Web page
 to include all configuration files
*****************************************************/

// ***** DO NOT REMOVE THESE FILES BELOW *****
include ("init-protx.php");
include ("init-functions.php");
include ("init-yoursite.php");
include ("init-dbconnect.php");


// ***** Add additional files here if you wish *****

/************************************************
 Do not modify the lines below.  They set up
 URLs and parameters for the VPS.
************************************************/

//A few standard definitions

// End of line default
$eoln = chr(13) . chr(10);

$ProtocolVersion = "2.20";
$DefaultNotificationURL = "http://" . $ExternalIPAddress . "/" . $DefaultNotificationPath;
$DefaultCompletionURL = "http://" . $InternalIPAddress . "/" . $DefaultOrderCompletePath;
$DefaultTamperURL = "http://" . $InternalIPAddress . "/" . $DefaultOrderTamperPath;
$DefaultNotAuthedURL = "http://" . $InternalIPAddress . "/" . $DefaultNotAuthedPath;
$DefaultAbortURL = "http://" . $InternalIPAddress . "/" . $DefaultAbortPath;
$DefaultErrorURL = "http://" . $InternalIPAddress . "/" . $DefaultErrorPath;


/************************************************
 Information and URLs for the test site
************************************************/
if ($TestSite){
  $Verify=false;
  $PurchaseURL="https://ukvpstest.protx.com/vps200/dotransaction.dll?Service=VendorRegisterTx";
  $RefundURL="https://ukvpstest.protx.com/vps200/dotransaction.dll?Service=VendorRefundTx";
  $ReleaseURL="https://ukvpstest.protx.com/vps200/dotransaction.dll?Service=VendorReleaseTx";
  $RepeatURL="https://ukvpstest.protx.com/vps200/dotransaction.dll?Service=VendorRepeatTx";
}

/************************************************
 Information and URLs for the Live site
************************************************/
if ($LiveSite){
  $Verify=true;
  $PurchaseURL="https://ukvps.protx.com/vps200/dotransaction.dll?Service=VendorRegisterTx";
  $RefundURL="https://ukvps.protx.com/vps200/dotransaction.dll?Service=VendorRefundTx";
  $ReleaseURL="https://ukvps.protx.com/vps200/dotransaction.dll?Service=VendorReleaseTx";
  $RepeatURL="https://ukvps.protx.com/vps200/dotransaction.dll?Service=VendorRepeatTx";
}

?>