<?
/**************************************************************************************************
* Sage Pay Server PHP Kit Includes File
***************************************************************************************************

***************************************************************************************************
* Change history
* ==============
*
* 02/04/2009 - Simon Wolfe - Updated UI for re-brand
* 11/02/2009 - Simon Wolfe - Updated for VSP protocol 2.23
* 18/12/2007 - Nick Selby - New PHP version adapted from ASP
***************************************************************************************************
* Description
* ===========
*
* Page with no visible content, but defines the constants and functions used in other pages in the
* kit.  It also opens connections to the database and defines record sets for later use.  It is
* included at the top of every other page in the kit and is paried with the closedown scipt.
***************************************************************************************************/
ob_start(); //start output buffering
session_start(); //enable sessions

/***************************************************************************************************
* Values for you to update
***************************************************************************************************/

$strConnectTo="SIMULATOR"; 	/** Set to SIMULATOR for the Sage Pay Simulator expert system, TEST for the Test Server **
							*** and LIVE in the live environment **/

$strDatabaseUser="sagepayUser"; // Change this if you created a different user name to access the database
$strDatabasePassword="[your database user password]"; // Set the password for the above user here
$strDatabase="sagepay"; // Change this if you created a different database name

$strVirtualDir="SagePayServerKit"; // Change if you've created a Virtual Directory in IIS with a different name
	
/** IMPORTANT.  Set the strYourSiteFQDN value to the Fully Qualified Domain Name of your server. **
** This should start http:// or https:// and should be the name by which our servers can call back to yours **
** i.e. it MUST be resolvable externally, and have access granted to the Sage Pay servers **
** examples would be https://www.mysite.com or http://212.111.32.22/ **
** NOTE: You should leave the final / in place. **/
$strYourSiteFQDN="http://[your web site]/"; 

/** At the end of a Sage Pay Server transaction, the customer is redirected back to the completion page **
** on your site using a client-side browser redirect. On live systems, this page will always be **
** referenced using the strYourSiteFQDN value above.  During development and testing, however, it **
** is often the case that the development machine sits behind the same firewall as the server **
** hosting the kit, so your browser might not be able resolve external IPs or dns names. **
** e.g. Externally your server might have the IP 212.111.32.22, but behind the firewall it **
** may have the IP 192.168.0.99.  If your test machine is also on the 192.168.0.n network **
** it may not be able to resolve 212.111.32.22. **
** Set the strYourSiteInternalFQDN to the internal Fully Qualified Domain Name by which **
** your test machine can reach the server (in the example above you'd use http://192.168.0.99/) **
** If you are not on the same network as the test server, set this value to the same value **
** as strYourSiteFQDN above. **
** NOTE: You should leave the final / in place. **/
$strYourSiteInternalFQDN="http://[your web site]/";

$strVendorName="[your Sage Pay Vendor Name]"; // Set this value to the Vendor Name assigned to you by Sage Pay or chosen when you applied
$strCurrency="GBP"; // Set this to indicate the currency in which you wish to trade. You will need a merchant number in this currency
$strTransactionType="PAYMENT"; // This can be DEFERRED or AUTHENTICATE if your Sage Pay account supports those payment types
$strPartnerID=""; /** Optional setting. If you are a Sage Pay Partner and wish to flag the transactions with your unique partner id set it here. **/

/**************************************************************************************************
* Global Definitions for this site
***************************************************************************************************/

//Open the VPS database
mysql_connect(localhost,$strDatabaseUser,$strDatabasePassword); //Change 'localhost' if your database is hosted externally
@mysql_select_db($strDatabase) or die("Unable to select database");

$strProtocol="2.23";

if ($strConnectTo=="LIVE")
{
  $strAbortURL="https://live.sagepay.com/gateway/service/abort.vsp";
  $strAuthoriseURL="https://live.sagepay.com/gateway/service/authorise.vsp";
  $strCancelURL="https://live.sagepay.com/gateway/service/cancel.vsp";
  $strPurchaseURL="https://live.sagepay.com/gateway/service/vspserver-register.vsp";
  $strRefundURL="https://live.sagepay.com/gateway/service/refund.vsp";
  $strReleaseURL="https://live.sagepay.com/gateway/service/release.vsp";
  $strRepeatURL="https://live.sagepay.com/gateway/service/repeat.vsp";
  $strVoidURL="https://live.sagepay.com/gateway/service/void.vsp";
}
elseif ($strConnectTo=="TEST")
{
  $strAbortURL="https://test.sagepay.com/gateway/service/abort.vsp";
  $strAuthoriseURL="https://test.sagepay.com/gateway/service/authorise.vsp";
  $strCancelURL="https://test.sagepay.com/gateway/service/cancel.vsp";
  $strPurchaseURL="https://test.sagepay.com/gateway/service/vspserver-register.vsp";
  $strRefundURL="https://test.sagepay.com/gateway/service/refund.vsp";
  $strReleaseURL="https://test.sagepay.com/gateway/service/abort.vsp";
  $strRepeatURL="https://test.sagepay.com/gateway/service/repeat.vsp";
  $strVoidURL="https://test.sagepay.com/gateway/service/void.vsp";
}
else
{
  $strAbortURL="https://test.sagepay.com/simulator/VSPServerGateway.asp?Service=VendorAbortTx";
  $strAuthoriseURL="https://test.sagepay.com/simulator/VSPServerGateway.asp?Service=VendorAuthoriseTx";
  $strCancelURL="https://test.sagepay.com/simulator/VSPServerGateway.asp?Service=VendorCancelTx";
  $strPurchaseURL="https://test.sagepay.com/simulator/VSPServerGateway.asp?Service=VendorRegisterTx";
  $strRefundURL="https://test.sagepay.com/simulator/VSPServerGateway.asp?Service=VendorRefundTx";
  $strReleaseURL="https://test.sagepay.com/simulator/VSPServerGateway.asp?Service=VendorReleaseTx";
  $strRepeatURL="https://test.sagepay.com/simulator/VSPServerGateway.asp?Service=VendorRepeatTx";
  $strVoidURL="https://test.sagepay.com/simulator/VSPServerGateway.asp?Service=VendorVoidTx";
}

/**************************************************************************************************
* Useful functions for all pages in this kit
**************************************************************************************************/

//Function to redirect browser
function redirect($url)
{
   if (!headers_sent())
		header('Location: '.$url);
   else
   {
		echo '<script type="text/javascript">';
    	echo 'window.location.href="'.$url.'";';
       	echo '</script>';
       	echo '<noscript>';
       	echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
       	echo '</noscript>';
   }
}

// Filters unwanted characters out of an input string.  Useful for tidying up FORM field inputs
function cleanInput($strRawText,$strType)
{

	if ($strType=="Number") {
		$strClean="0123456789.";
		$bolHighOrder=false;
	}
	else if ($strType=="VendorTxCode") {
		$strClean="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
		$bolHighOrder=false;
	}
	else {
  		$strClean=" ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789.,'/{}@():?-_&£$=%~<>*+\"";
		$bolHighOrder=true;
	}
	
	$strCleanedText="";
	$iCharPos = 0;
		
	do
	{
    	// Only include valid characters
		$chrThisChar=substr($strRawText,$iCharPos,1);
			
		if (strspn($chrThisChar,$strClean,0,strlen($strClean))>0) { 
			$strCleanedText=$strCleanedText . $chrThisChar;
		}
		else if ($bolHighOrder==true) {
				// Fix to allow accented characters and most high order bit chars which are harmless 
				if (bin2hex($chrThisChar)>=191) {
					$strCleanedText=$strCleanedText . $chrThisChar;
				}
			}
			
		$iCharPos=$iCharPos+1;
		}
	while ($iCharPos<strlen($strRawText));
		
  	$cleanInput = ltrim($strCleanedText);
	return $cleanInput;

}


// Function to check validity of email address entered in form fields
function is_valid_email($email) {
  $result = TRUE;
  if(!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $email)) {
    $result = FALSE;
  }
  return $result;
}

/*************************************************************
	Send a post request with cURL
		$url = URL to send request to
		$data = POST data to send (in URL encoded Key=value pairs)
*************************************************************/
function requestPost($url, $data){
	// Set a one-minute timeout for this script
	set_time_limit(60);

	// Initialise output variable
	$output = array();

	// Open the cURL session
	$curlSession = curl_init();

	// Set the URL
	curl_setopt ($curlSession, CURLOPT_URL, $url);
	// No headers, please
	curl_setopt ($curlSession, CURLOPT_HEADER, 0);
	// It's a POST request
	curl_setopt ($curlSession, CURLOPT_POST, 1);
	// Set the fields for the POST
	curl_setopt ($curlSession, CURLOPT_POSTFIELDS, $data);
	// Return it direct, don't print it out
	curl_setopt($curlSession, CURLOPT_RETURNTRANSFER,1); 
	// This connection will timeout in 30 seconds
	curl_setopt($curlSession, CURLOPT_TIMEOUT,30); 
	//The next two lines must be present for the kit to work with newer version of cURL
	//You should remove them if you have any problems in earlier versions of cURL
    curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 1);

	//Send the request and store the result in an array
	
	$rawresponse = curl_exec($curlSession);
	//Store the raw response for later as it's useful to see for integration and understanding 
	$_SESSION["rawresponse"]=$rawresponse;
	//Split response into name=value pairs
	$response = split(chr(10), $rawresponse);
	// Check that a connection was made
	if (curl_error($curlSession)){
		// If it wasn't...
		$output['Status'] = "FAIL";
		$output['StatusDetail'] = curl_error($curlSession);
	}

	// Close the cURL session
	curl_close ($curlSession);

	// Tokenise the response
	for ($i=0; $i<count($response); $i++){
		// Find position of first "=" character
		$splitAt = strpos($response[$i], "=");
		// Create an associative (hash) array with key/value pairs ('trim' strips excess whitespace)
		$output[trim(substr($response[$i], 0, $splitAt))] = trim(substr($response[$i], ($splitAt+1)));
	} // END for ($i=0; $i<count($response); $i++)

	// Return the output
	return $output;
} // END function requestPost()

?>