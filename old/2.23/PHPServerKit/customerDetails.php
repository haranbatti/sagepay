<?
include("includes.php");
session_start(); 

/*************************************************************************************************
* Sage Pay Server PHP Kit Customer Details Page
**************************************************************************************************

**************************************************************************************************
* Change history
* ==============

* 02/04/2009 - Simon Wolfe - Updated UI for re-brand
* 11/02/2009 - Simon Wolfe - Updated for VSP protocol 2.23
* 18/10/2007 - Nick Selby - New kit version
**************************************************************************************************
* Description
* ===========
*
* Asks for customer billing and delivery details, such as name, address and contact details. It 
* checks the session object to autocomplete fields where possible.  If the customer wishes to 
* proceed, the required fields are validated and if everything is okay, the session object is 
* populated and the order confirmation screen displayed.
***************************************************************************************************/

//Check we have a cart in the session.  If not, go back to the buildOrder page to get one
$strCart=$_SESSION['strCart'];
if (strlen($strCart)==0){
	ob_end_flush();
	redirect("buildOrder.php");
}

// Check for the proceed button click, and if so, go validate the order **/
if ($_REQUEST['navigate']=="proceed") {
	// Validate and clean the user input here
	$strBillingFirstnames  = cleaninput($_REQUEST["BillingFirstnames"], "Text");
	$strBillingSurname     = cleaninput($_REQUEST["BillingSurname"], "Text");
	$strBillingAddress1    = cleaninput($_REQUEST["BillingAddress1"], "Text");
	$strBillingAddress2    = cleaninput($_REQUEST["BillingAddress2"], "Text");
	$strBillingCity        = cleaninput($_REQUEST["BillingCity"], "Text");
	$strBillingPostCode    = cleaninput($_REQUEST["BillingPostCode"], "Text");
	$strBillingCountry     = cleaninput($_REQUEST["BillingCountry"], "Text");
	$strBillingState       = cleaninput($_REQUEST["BillingState"], "Text");
	$strBillingPhone       = cleaninput($_REQUEST["BillingPhone"], "Text");
	$strCustomerEMail      = cleaninput($_REQUEST["CustomerEMail"], "Text");
	$strDeliveryFirstnames = cleaninput($_REQUEST["DeliveryFirstnames"], "Text");
	$strDeliverySurname    = cleaninput($_REQUEST["DeliverySurname"], "Text");
	$strDeliveryAddress1   = cleaninput($_REQUEST["DeliveryAddress1"], "Text");
	$strDeliveryAddress2   = cleaninput($_REQUEST["DeliveryAddress2"], "Text");
	$strDeliveryCity       = cleaninput($_REQUEST["DeliveryCity"], "Text");
	$strDeliveryPostCode   = cleaninput($_REQUEST["DeliveryPostCode"], "Text");
	$strDeliveryCountry    = cleaninput($_REQUEST["DeliveryCountry"], "Text");
	$strDeliveryState      = cleaninput($_REQUEST["DeliveryState"], "Text");
	$strDeliveryPhone      = cleaninput($_REQUEST["DeliveryPhone"], "Text");
	
	if ($_REQUEST["IsDeliverySame"]=="YES") 
		$bIsDeliverySame=true;
	else 
		$bIsDeliverySame=false;

	// Validate the compulsory fields 
	if (strlen($strBillingFirstnames)==0) 
		$strPageError="Please enter your Billing First Names(s) where requested below.";
	else if (strlen($strBillingSurname)==0) 
		$strPageError="Please enter your Billing Surname where requested below.";
	else if (strlen($strBillingAddress1)==0) 
		$strPageError="Please enter your Billing Address Line 1 where requested below.";
	else if (strlen($strBillingCity)==0) 
		$strPageError="Please enter your Billing City where requested below.";
	else if (strlen($strBillingPostCode)==0) 
		$strPageError="Please enter your Billing Post Code where requested below.";
	else if (strlen($strBillingCountry)==0) 
		$strPageError="Please select your Billing Country where requested below.";
    else if ((strlen($strBillingState) == 0) and ($strBillingCountry == "US")) 
		$strPageError="Please enter your State code as you have selected United States for billing country.";
	else if ((strlen($strCustomerEMail) > 0) && is_valid_email($strCustomerEMail)==false)
		$strPageError="The email address entered was not valid.";
	else if (($bIsDeliverySame==false) and strlen($strDeliveryFirstnames)==0) 
		$strPageError="Please enter your Delivery First Names(s) where requested below.";
	else if (($bIsDeliverySame==false) and strlen($strDeliverySurname)==0) 
		$strPageError="Please enter your Delivery Surname where requested below.";
	else if (($bIsDeliverySame==false) and strlen($strDeliveryAddress1)==0) 
		$strPageError="Please enter your Delivery Address Line 1 where requested below.";
	else if (($bIsDeliverySame==false) and strlen($strDeliveryCity)==0) 
		$strPageError="Please enter your Delivery City where requested below.";
	else if (($bIsDeliverySame==false) and strlen($strDeliveryPostCode)==0) 
		$strPageError="Please enter your Delivery Post Code where requested below.";
	else if (($bIsDeliverySame==false) and strlen($strDeliveryCountry)==0) 
		$strPageError="Please select your Delivery Country where requested below.";
    else if (($bIsDeliverySame==false) and (strlen($strDeliveryState) == 0) and ($strDeliveryCountry == "US")) 
		$strPageError="Please enter your State code as you have selected United States for delivery country.";
	else {
		//** All validations have passed, so store the details in the session **
	    $_SESSION["strBillingFirstnames"]  = $strBillingFirstnames;
	    $_SESSION["strBillingSurname"]     = $strBillingSurname;
	    $_SESSION["strBillingAddress1"]    = $strBillingAddress1;
	    $_SESSION["strBillingAddress2"]    = $strBillingAddress2;
	    $_SESSION["strBillingCity"]        = $strBillingCity;
	    $_SESSION["strBillingPostCode"]    = $strBillingPostCode;
	    $_SESSION["strBillingCountry"]     = $strBillingCountry;
	    $_SESSION["strBillingState"]       = $strBillingState;
	    $_SESSION["strBillingPhone"]       = $strBillingPhone;
	    $_SESSION["strCustomerEMail"]      = $strCustomerEMail;
	    $_SESSION["bIsDeliverySame"]       = $bIsDeliverySame;
	    
	    if ($bIsDeliverySame == true) {
	    	$_SESSION["strDeliveryFirstnames"] = $strBillingFirstnames;
	        $_SESSION["strDeliverySurname"]    = $strBillingSurname;
	        $_SESSION["strDeliveryAddress1"]   = $strBillingAddress1;
	        $_SESSION["strDeliveryAddress2"]   = $strBillingAddress2;
	        $_SESSION["strDeliveryCity"]       = $strBillingCity;
	        $_SESSION["strDeliveryPostCode"]   = $strBillingPostCode;
	        $_SESSION["strDeliveryCountry"]    = $strBillingCountry;
	        $_SESSION["strDeliveryState"]      = $strBillingState;
	        $_SESSION["strDeliveryPhone"]      = $strBillingPhone;
	    }
	    else
	    {
	    	$_SESSION["strDeliveryFirstnames"] = $strDeliveryFirstnames;
	        $_SESSION["strDeliverySurname"]    = $strDeliverySurname;
	        $_SESSION["strDeliveryAddress1"]   = $strDeliveryAddress1;
	        $_SESSION["strDeliveryAddress2"]   = $strDeliveryAddress2;
	        $_SESSION["strDeliveryCity"]       = $strDeliveryCity;
	        $_SESSION["strDeliveryPostCode"]   = $strDeliveryPostCode;
	        $_SESSION["strDeliveryCountry"]    = $strDeliveryCountry;
	        $_SESSION["strDeliveryState"]      = $strDeliveryState;
	        $_SESSION["strDeliveryPhone"]      = $strDeliveryPhone;
	    }
	    
		// Now go to the order confirmation page
		ob_end_flush();
		redirect("orderConfirmation.php");
	}
}
	
else if ($_REQUEST["navigate"]=="back") {
	ob_end_flush();
	redirect("buildOrder.php");
}

else {
	// Populate customer details from the session if they are there	
    $strBillingFirstnames  = $_SESSION["strBillingFirstnames"];
    $strBillingSurname     = $_SESSION["strBillingSurname"];
    $strBillingAddress1    = $_SESSION["strBillingAddress1"];
    $strBillingAddress2    = $_SESSION["strBillingAddress2"];
    $strBillingCity        = $_SESSION["strBillingCity"];
    $strBillingPostCode    = $_SESSION["strBillingPostCode"];
    $strBillingCountry     = $_SESSION["strBillingCountry"];
    $strBillingState       = $_SESSION["strBillingState"];
    $strBillingPhone       = $_SESSION["strBillingPhone"];
    $strCustomerEMail      = $_SESSION["strCustomerEMail"];
	$bIsDeliverySame       = $_SESSION["bIsDeliverySame"];
    $strDeliveryFirstnames = $_SESSION["strDeliveryFirstnames"];
    $strDeliverySurname    = $_SESSION["strDeliverySurname"];
    $strDeliveryAddress1   = $_SESSION["strDeliveryAddress1"];
    $strDeliveryAddress2   = $_SESSION["strDeliveryAddress2"];
    $strDeliveryCity       = $_SESSION["strDeliveryCity"];
    $strDeliveryPostCode   = $_SESSION["strDeliveryPostCode"];
    $strDeliveryCountry    = $_SESSION["strDeliveryCountry"];
    $strDeliveryState      = $_SESSION["strDeliveryState"];
    $strDeliveryPhone      = $_SESSION["strDeliveryPhone"];
}

?>
<html>
<head>
	<title>Sage Pay Server PHP Kit Customer Details Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/serverKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
    <script type="text/javascript" language="javascript" src="scripts/countrycodes.js"></script>
    <script type="text/javascript" language="javascript" src="scripts/customerDetails.js"></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Gathering Customer Details </div>
			<p>This page is a simple form to allow your customers to provide their name, address and contact details. The form makes some fields compulsory and the code of this page ensures the customer has completed these correctly. The code for this page can be found in customerDetails.php</p>
            <div class="greyHzShadeBar">&nbsp;</div>
            <? 
            if (strlen($strPageError) > 0) 
            { 
			    echo "<div class=\"errorheader\">".$strPageError."</div>";
			}
		    ?>
			<form name="customerform" action="customerDetails.php" method="POST">
			<input type="hidden" name="navigate" value="" />
			<table class="formTable">
				<tr>
				  <td colspan="2"><div class="subheader">Please enter your Billing details below</div></td>
				</tr>
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>First Name(s):</td>
				    <td class="fieldData"><input name="BillingFirstnames" type="text" maxlength="20" value="<? echo $strBillingFirstnames ?>" style="width: 200px;"></td>
                </tr>	
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>Surname:</td>
				    <td class="fieldData"><input name="BillingSurname" type="text" maxlength="20" value="<? echo $strBillingSurname ?>" style="width: 200px;"></td>
                </tr>
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>Address Line 1:</td>
				    <td class="fieldData"><input name="BillingAddress1" type="text" maxlength="100" value="<? echo $strBillingAddress1 ?>" style="width: 400px;"></td>
                </tr>	
				<tr>
					<td class="fieldLabel">Address Line 2:</td>
				    <td class="fieldData"><input name="BillingAddress2" type="text" maxlength="100" value="<? echo $strBillingAddress2 ?>" style="width: 400px;"></td>
                </tr>	
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>City:</td>
				    <td class="fieldData"><input name="BillingCity" type="text" maxlength="40" value="<? echo $strBillingCity ?>" style="width: 200px;"></td>
                </tr>
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>Post/Zip Code:</td>
				    <td class="fieldData"><input name="BillingPostCode" type="text" maxlength="10" value="<? echo $strBillingPostCode ?>" style="width: 80px;"></td>
                </tr>	
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>Country:</td>
				    <td class="fieldData">
				        <select name="BillingCountry" style="width: 200px;">
				            <script type="text/javascript" language="javascript">
				                document.write( getCountryOptionsListHtml( "<? echo $strBillingCountry ?>" ) );
				            </script>
				        </select> 
				    </td>
                </tr>
				<tr>
					<td class="fieldLabel">State Code (U.S. only):</td>
				    <td class="fieldData"><input name="BillingState" type="text" maxlength="2" value="<? echo $strBillingState ?>" style="width: 40px;"> (<span class="warning">*</span>State Code for U.S. customers only)</td>
                </tr>	
				<tr>
					<td class="fieldLabel">Phone:</td>
				    <td class="fieldData"><input name="BillingPhone" type="text" maxlength="20" value="<? echo $strBillingPhone ?>" style="width: 150px;"></td>
                </tr>
				<tr>
					<td class="fieldLabel">e-Mail Address:</td>
				    <td class="fieldData"><input name="CustomerEMail" type="text" size="60" maxlength="255" value="<? echo $strCustomerEMail ?>"></td>
				</tr>
				<tr>
				  <td colspan="2"><div class="subheader">Please enter your Delivery details below</div></td>
				</tr>
				<tr>
					<td class="fieldLabel">Same as Billing Details?:</td>
				    <td class="fieldData"><input name="IsDeliverySame" type="checkbox" value="YES" <? if($bIsDeliverySame) echo "CHECKED"; ?> onClick="IsDeliverySame_clicked();"></td>
				</tr>
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>First Name(s):</td>
				    <td class="fieldData"><input name="DeliveryFirstnames" type="text" maxlength="20" value="<? echo $strDeliveryFirstnames ?>" style="width: 200px;"></td>
                </tr>	
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>Surname:</td>
				    <td class="fieldData"><input name="DeliverySurname" type="text" maxlength="20" value="<? echo $strDeliverySurname ?>" style="width: 200px;"></td>
                </tr>
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>Address Line 1:</td>
				    <td class="fieldData"><input name="DeliveryAddress1" type="text" maxlength="100" value="<? echo $strDeliveryAddress1 ?>" style="width: 400px;"></td>
                </tr>	
				<tr>
					<td class="fieldLabel">Address Line 2:</td>
				    <td class="fieldData"><input name="DeliveryAddress2" type="text" maxlength="100" value="<? echo $strDeliveryAddress2 ?>" style="width: 400px;"></td>
                </tr>	
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>City:</td>
				    <td class="fieldData"><input name="DeliveryCity" type="text" maxlength="40" value="<? echo $strDeliveryCity ?>" style="width: 200px;"></td>
                </tr>
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>Post/Zip Code:</td>
				    <td class="fieldData"><input name="DeliveryPostCode" type="text" maxlength="10" value="<? echo $strDeliveryPostCode ?>" style="width: 80px;"></td>
                </tr>	
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>Country:</td>
				    <td class="fieldData">
				        <select name="DeliveryCountry" style="width: 200px;">
				            <script type="text/javascript" language="javascript">
				                document.write( getCountryOptionsListHtml( "<? echo $strDeliveryCountry ?>" ) );
				            </script>
				        </select>
				    </td>
                </tr>
				<tr>
					<td class="fieldLabel">State Code (U.S. only):</td>
				    <td class="fieldData"><input name="DeliveryState" type="text" maxlength="2" value="<? echo $strDeliveryState ?>" style="width: 40px;"> (<span class="warning">*</span>State Code for U.S. customers only)</td>
                </tr>
				<tr>
					<td class="fieldLabel">Phone:</td>
				    <td class="fieldData"><input name="DeliveryPhone" type="text" maxlength="20" value="<? echo $strDeliveryPhone ?>" style="width: 150px;"></td>
                </tr>
			</table>
            <script type="text/javascript" language="javascript">
                IsDeliverySame_clicked();
            </script>
            <div class="greyHzShadeBar">&nbsp;</div>
            <div class="formFooter">
                <a href="javascript:submitForm('customerform','back');" title="Go back to the place order page" style="float: left;">
                    <img src="images/back.gif" alt="Go back to the previous page" border="0" />
                </a>
                <a href="javascript:submitForm('customerform','proceed');" title="Continue to order confirmation" style="float: right">
                    <img src="images/proceed.gif" alt="Proceed to the next page" border="0" />
                </a>
            </div>
			</form>
		</div>
	</div>
</body>
</html>


