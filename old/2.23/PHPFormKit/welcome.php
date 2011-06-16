<?
include("includes.php");
session_start(); 

/**************************************************************************************************
* Form PHP Kit Welcome Page
***************************************************************************************************

***************************************************************************************************
* Change history
* ==============
*
* 10/02/2009 - Simon Wolfe - Updated for protocol 2.23
* 18/10/07 - Nick Selby - New kit version
***************************************************************************************************
* Description
* ===========
*
* This page displays a welcome message and checks to ensure the user configured settings have been
* set up correctly.  If they haven't it gives help on where to go to set up the site.  If everything
* has been set up, it displays the user settings with some helpful information and a proceed button 
* to take the user to the order building page.
***************************************************************************************************/

//Check for the proceed button click, and if so, go to the buildOrder page
if ($_REQUEST["navigate"]=="proceed"){
	ob_end_flush();
	// Redirect to next page
	redirect("buildOrder.php");
}

?>
<html>
<head>
	<title>Form PHP Kit Welcome Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/formKitStyle.css">	
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
</head>
<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Welcome to the Sage Pay Form PHP Kit </div>
            <p>
                If you are viewing this page in your browser at <strong>
                    <? echo strtolower(substr($_SERVER['SERVER_PROTOCOL'],0,strpos($_SERVER['SERVER_PROTOCOL'],"/"))) . "://" .  $_SERVER["SERVER_NAME"] . $_SERVER["PHP_SELF"]; ?>
                </strong> then you have correctly set up your virtual directory.
            </p>
            <div class="greyHzShadeBar">&nbsp;</div>
            <table class="formTable">
                <tr>
                    <td colspan="2">
                        <div class="subheader">Your current kit set-up</div>
                    </td>
                </tr>
                <tr>
                    <td class="fieldLabel">Vendor Name:</td>
                    <td class="fieldData">
                        <? echo $strVendorName ?>
                    </td>
                </tr>
                <tr>
                    <td class="fieldLabel">Default Currency:</td>
                    <td class="fieldData">
                        <? echo $strCurrency ?>
                    </td>
                </tr>
                <tr>
                    <td class="fieldLabel">Full URL to this kit:</td>
                    <td class="fieldData">
                        <? echo $strYourSiteFQDN;  echo $strVirtualDir ?>
                    </td>
                </tr>
            </table>
            <p>
            <? if ($strConnectTo=="LIVE") 
				echo "<span class=\"warning\">Your kit is pointing at the Live Sage Pay environment.  You should only do this once your have completed testing on both the Simulator AND Test servers, have sent your GoLive request to the technical support team and had confirmation that your account has been set up. <br><br><strong>Transactions sent to the Live service WILL charge your customers' cards.</strong></span>"; 
				else if ($strConnectTo=="TEST") 
				echo "Your kit is pointing at the Sage Pay TEST environment.  This is an exact replica of the Live systems except that no banks are attached, so no authorisation requests are sent, nothing is settled and you can use our test card numbers when making payments. You should only use the test environment after you have completed testing using the Simulator AND the Sage Pay support team have mailed you to let you know your account has been created.<br><br><span class=\"warning\"><strong>If you are already set up on Live and are testing additional functionality, DO NOT leave your kit set to Test or you will not receive any money for your transactions!</strong></span>";
				else
				echo "Your kit is currently pointing at the Simulator. This is an Expert System provided by Sage Pay to enable you to build and configure your site correctly, to debug the messages you send to Server and practise handling responses from it.  No customers are charged, no money is moved around.  The Simulator is for development and testing ONLY.";
			?>
            </p>
            <div class="greyHzShadeBar">&nbsp;</div>
            <div class="formFooter">
                <form action="welcome.php" method="POST" name="mainForm">
                    <input type="hidden" name="navigate" value="" />
                    <a href="javascript:submitForm('mainForm','proceed');" title="Proceed to the next page" style="float: right;">
                        <img src="images/proceed.gif" alt="Proceed to the next page" border="0" />
                    </a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>


