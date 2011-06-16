<?
include("includes.php");
session_start(); 

/*************************************************************************************************
* Form PHP Kit Customer Details Page
**************************************************************************************************

**************************************************************************************************
* Change history
* ==============

* 26/06/2009 - Simon Wolfe - Added input validation functions & adjusted cleanInput
* 27/05/2009 - Simon Wolfe - Updated for AES encryption and XSS fixes
* 10/02/2009 - Simon Wolfe - Updated for protocol 2.23
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
	$strBillingFirstnames  = cleaninput($_REQUEST["BillingFirstnames"], CLEAN_INPUT_FILTER_TEXT);
	$strBillingSurname     = cleaninput($_REQUEST["BillingSurname"], CLEAN_INPUT_FILTER_TEXT);
	$strBillingAddress1    = cleaninput($_REQUEST["BillingAddress1"], CLEAN_INPUT_FILTER_TEXT);
	$strBillingAddress2    = cleaninput($_REQUEST["BillingAddress2"], CLEAN_INPUT_FILTER_TEXT);
	$strBillingCity        = cleaninput($_REQUEST["BillingCity"], CLEAN_INPUT_FILTER_TEXT);
	$strBillingPostCode    = cleaninput($_REQUEST["BillingPostCode"], CLEAN_INPUT_FILTER_TEXT);
	$strBillingCountry     = cleaninput($_REQUEST["BillingCountry"], CLEAN_INPUT_FILTER_TEXT);
	$strBillingState       = cleaninput($_REQUEST["BillingState"], CLEAN_INPUT_FILTER_TEXT);
	$strBillingPhone       = cleaninput($_REQUEST["BillingPhone"], CLEAN_INPUT_FILTER_TEXT);
	$strCustomerEMail      = cleaninput($_REQUEST["CustomerEMail"], CLEAN_INPUT_FILTER_TEXT);
	$strDeliveryFirstnames = cleaninput($_REQUEST["DeliveryFirstnames"], CLEAN_INPUT_FILTER_TEXT);
	$strDeliverySurname    = cleaninput($_REQUEST["DeliverySurname"], CLEAN_INPUT_FILTER_TEXT);
	$strDeliveryAddress1   = cleaninput($_REQUEST["DeliveryAddress1"], CLEAN_INPUT_FILTER_TEXT);
	$strDeliveryAddress2   = cleaninput($_REQUEST["DeliveryAddress2"], CLEAN_INPUT_FILTER_TEXT);
	$strDeliveryCity       = cleaninput($_REQUEST["DeliveryCity"], CLEAN_INPUT_FILTER_TEXT);
	$strDeliveryPostCode   = cleaninput($_REQUEST["DeliveryPostCode"], CLEAN_INPUT_FILTER_TEXT);
	$strDeliveryCountry    = cleaninput($_REQUEST["DeliveryCountry"], CLEAN_INPUT_FILTER_TEXT);
	$strDeliveryState      = cleaninput($_REQUEST["DeliveryState"], CLEAN_INPUT_FILTER_TEXT);
	$strDeliveryPhone      = cleaninput($_REQUEST["DeliveryPhone"], CLEAN_INPUT_FILTER_TEXT);
	
	if ($_REQUEST["IsDeliverySame"]=="YES") 
		$bIsDeliverySame=true;
	else 
		$bIsDeliverySame=false;


	// Validate the fields
	
	$validationResult = ""; //returned reference to a validation result

    if (!isValidNameField($strBillingFirstnames, $validationResult)) {
        $strPageError = getValidationMessage($validationResult, "Billing First Name(s)");
	}
    elseIf (!isValidNameField($strBillingSurname, $validationResult)) {
        $strPageError = getValidationMessage($validationResult, "Billing Surname");
	}
    elseIf (!isValidAddressField($strBillingAddress1, TRUE, $validationResult)) {
        $strPageError = getValidationMessage($validationResult, "Billing Address Line 1");
	}
    elseIf (!isValidAddressField($strBillingAddress2, FALSE, $validationResult)) {
        $strPageError = getValidationMessage($validationResult, "Billing Address Line 2");
	}
    elseIf (!isValidCityField($strBillingCity, $validationResult)) {
        $strPageError = getValidationMessage($validationResult, "Billing City");
	}
    elseIf (!isValidPostcodeField($strBillingPostCode, $validationResult)) {
        $strPageError = getValidationMessage($validationResult, "Billing Post/Zip Code");
	}
    elseIf (strlen($strBillingCountry) == 0) {
        $strPageError = "Please select your Billing Country where requested below.";
	}
    elseIf ((strlen($strBillingState) == 0) && ($strBillingCountry == "US")) {
        $strPageError = "Please select your State code as you have selected United States for billing country.";
	}
    elseIf (!isValidPhoneField($strBillingPhone, $validationResult)) {
        $strPageError = getValidationMessage($validationResult, "Billing Phone");
	}
    elseIf (!isValidEmailField($strCustomerEMail, $validationResult)) {
        $strPageError = getValidationMessage($validationResult, "e-mail Address");
	}
    elseIf ($bIsDeliverySame == FALSE)
    {
        if (!isValidNameField($strDeliveryFirstnames, $validationResult)) {
            $strPageError = getValidationMessage($validationResult, "Delivery First Name(s)");
		}
        elseIf (! isValidNameField($strDeliverySurname, $validationResult)) {
            $strPageError = getValidationMessage($validationResult, "Delivery Surname");
		}
        elseIf (!isValidAddressField($strDeliveryAddress1, True, $validationResult)){
            $strPageError = getValidationMessage($validationResult, "Delivery Address Line 1");
		}
        elseIf (!isValidAddressField($strDeliveryAddress2, False, $validationResult)) {
            $strPageError = getValidationMessage($validationResult, "Delivery Address Line 2");
		}
        elseIf (!isValidCityField($strDeliveryCity, $validationResult)) {
            $strPageError = getValidationMessage($validationResult, "Delivery City");
		}
        elseIf (!isValidPostcodeField($strDeliveryPostCode, $validationResult)) {
            $strPageError = getValidationMessage($validationResult, "Delivery Post/Zip Code");
		}
        elseIf (strlen($strDeliveryCountry) == 0) {
            $strPageError = "Please select your Delivery Country where requested below.";
		}
        elseIf ((strlen($strDeliveryState) == 0) && ($strDeliveryCountry == "US")) {
            $strPageError = "Please enter your State code as you have selected United States for Delivery country.";
		}
        elseIf (!isValidPhoneField($strDeliveryPhone, $validationResult)) {
            $strPageError = getValidationMessage($validationResult, "Delivery Phone");
        }
    }


	if (strlen($strPageError) == 0) 
	{
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
	<title>Form PHP Kit Customer Details Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/formKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
    <script type="text/javascript" language="javascript" src="scripts/countrycodes.js"></script>
    <script type="text/javascript" language="javascript" src="scripts/customerDetails.js"></script>
    <script type="text/javascript" language="javascript" src="scripts/statecodes.js"></script>
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
			    echo "<div class=\"errorheader\">".htmlentities($strPageError)."</div>";
			}
		    ?>
            <form name="customerform" action="customerDetails.php" method="POST">
            <input type="hidden" name="navigate" value="" />
            <table class="formTable">
                <tr>
                    <td colspan="2">
                        <div class="subheader">Please enter your Billing details below</div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldLabel">
                        <span class="warning">*</span>First Name(s):
                    </td>
                    <td class="fieldData">
                        <input name="BillingFirstnames" type="text" maxlength="20" value="<? echo htmlentities($strBillingFirstnames) ?>" style="width: 200px;">
                    </td>
                </tr>
                <tr>
                    <td class="fieldLabel">
                        <span class="warning">*</span>Surname:
                    </td>
                    <td class="fieldData">
                        <input name="BillingSurname" type="text" maxlength="20" value="<? echo htmlentities($strBillingSurname) ?>" style="width: 200px;">
                    </td>
                </tr>
                <tr>
                    <td class="fieldLabel">
                        <span class="warning">*</span>Address Line 1:
                    </td>
                    <td class="fieldData">
                        <input name="BillingAddress1" type="text" maxlength="100" value="<? echo htmlentities($strBillingAddress1) ?>" style="width: 400px;">
                    </td>       
                </tr>	
				<tr>
					<td class="fieldLabel">Address Line 2:</td>
                    <td class="fieldData">
                        <input name="BillingAddress2" type="text" maxlength="100" value="<? echo htmlentities($strBillingAddress2) ?>" style="width: 400px;">
                    </td>
                </tr>	
				<tr>
					<td class="fieldLabel">
                        <span class="warning">*</span>City:</td>
				    <td class="fieldData">
                        <input name="BillingCity" type="text" maxlength="40" value="<? echo htmlentities($strBillingCity) ?>" style="width: 200px;">
                    </td>
                </tr>
				<tr>
					<td class="fieldLabel">
                        <span class="warning">*</span>Post/Zip Code:
                    </td>
				    <td class="fieldData">
                        <input name="BillingPostCode" type="text" maxlength="10" value="<? echo htmlentities($strBillingPostCode) ?>" style="width: 100px;">
                    </td>
                </tr>	
				<tr>
					<td class="fieldLabel">
                        <span class="warning">*</span>Country:</td>
				    <td class="fieldData">
                        <select name="BillingCountry" style="width: 200px;">
				            <script type="text/javascript" language="javascript">
				                document.write( getCountryOptionsListHtml( "<? echo htmlentities($strBillingCountry) ?>" ) );
				            </script>
				        </select> 
				    </td>
                </tr>
				<tr>
					<td class="fieldLabel">State Code (U.S. only):</td>
				    <td class="fieldData">
				        <select name="BillingState" style="width: 200px;">
				            <script type="text/javascript" language="javascript">
				                document.write( getUsStateOptionsListHtml( "<? echo htmlentities($strBillingState) ?>" ) );
				            </script>
				        </select>
				    	&nbsp;(<span class="warning">*</span> for U.S. customers only)
				   	</td>
				</tr>
				<tr>
					<td class="fieldLabel">Phone:</td>
                    <td class="fieldData">
                        <input name="BillingPhone" type="text" maxlength="20" value="<? echo htmlentities($strBillingPhone) ?>" style="width: 200px;">
                    </td>
                </tr>
				<tr>
					<td class="fieldLabel">e-Mail Address:</td>
                    <td class="fieldData">
                        <input name="CustomerEMail" type="text" maxlength="255" value="<? echo htmlentities($strCustomerEMail) ?>"  style="width: 200px;">
                    </td>
				</tr>
				<tr>
				  <td colspan="2">
                      <div class="subheader">Please enter your Delivery details below</div>
                  </td>
				</tr>
				<tr>
					<td class="fieldLabel">Same as Billing Details?:</td>
				    <td class="fieldData">
                        <input name="IsDeliverySame" type="checkbox" value="YES" <? if($bIsDeliverySame) echo "CHECKED"; ?> onClick="IsDeliverySame_clicked();">
                    </td>
				</tr>
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>First Name(s):</td>
				    <td class="fieldData">
                        <input name="DeliveryFirstnames" type="text" maxlength="20" value="<? echo htmlentities($strDeliveryFirstnames) ?>" style="width: 200px;">
                    </td>
                </tr>	
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>Surname:</td>
				    <td class="fieldData">
                        <input name="DeliverySurname" type="text" maxlength="20" value="<? echo htmlentities($strDeliverySurname) ?>" style="width: 200px;">
                    </td>
                </tr>
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>Address Line 1:</td>
				    <td class="fieldData">
                        <input name="DeliveryAddress1" type="text" maxlength="100" value="<? echo htmlentities($strDeliveryAddress1) ?>" style="width: 400px;"></td>
                </tr>	
				<tr>
					<td class="fieldLabel">Address Line 2:</td>
				    <td class="fieldData">
                        <input name="DeliveryAddress2" type="text" maxlength="100" value="<? echo htmlentities($strDeliveryAddress2) ?>" style="width: 400px;">
                    </td>
                </tr>	
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>City:</td>
				    <td class="fieldData">
                        <input name="DeliveryCity" type="text" maxlength="40" value="<? echo htmlentities($strDeliveryCity) ?>" style="width: 200px;">
                    </td>
                </tr>
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>Post/Zip Code:</td>
				    <td class="fieldData">
                        <input name="DeliveryPostCode" type="text" maxlength="10" value="<? echo htmlentities($strDeliveryPostCode) ?>" style="width: 100px;">
                    </td>
                </tr>	
				<tr>
					<td class="fieldLabel"><span class="warning">*</span>Country:</td>
				    <td class="fieldData">
                        <select name="DeliveryCountry" style="width: 200px;">
				            <script type="text/javascript" language="javascript">
				                document.write( getCountryOptionsListHtml( "<? echo htmlentities($strDeliveryCountry) ?>" ) );
				            </script>
				        </select>
				    </td>
                </tr>
				<tr>
					<td class="fieldLabel">State Code (U.S. only):</td>
				    <td class="fieldData">
				        <select name="DeliveryState" style="width: 200px;">
				            <script type="text/javascript" language="javascript">
				                document.write( getUsStateOptionsListHtml( "<? echo htmlentities($strDeliveryState) ?>" ) );
				            </script>
				        </select>
				    	&nbsp;(<span class="warning">*</span> for U.S. customers only)
				   	</td>
                </tr>
                <tr>
                    <td class="fieldLabel">Phone:</td>
				    <td class="fieldData">
                        <input name="DeliveryPhone" type="text" maxlength="20" value="<? echo htmlentities($strDeliveryPhone) ?>" style="width: 200px;">
                    </td>
                </tr>
			</table>
            <script type="text/javascript" language="javascript">
                IsDeliverySame_clicked(true);
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


