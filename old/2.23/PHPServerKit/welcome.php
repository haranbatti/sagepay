<?
include("includes.php");

/**************************************************************************************************
* Sage Pay Server PHP Kit Welcome Page
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
* This page displays a welcome message and checks to ensure the user configured settings have been
* set up correctly.  If they haven't it gives help on where to go to set up the site.  If everything
* has been set up, it retrieves some basic information from the database and displays the user settings
* with some helpful information and a proceed button to take the user to the order building page.
***************************************************************************************************/

// Check for the proceed button click, and if so, go to the buildOrder page

if ($_REQUEST["navigate"]=="proceed"){
	ob_flush();
	// Redirect to next page
	redirect("buildOrder.php");
}
elseif ($_REQUEST["navigate"]=="admin"){ 
	ob_flush();
	redirect("orderAdmin.php");
}
?>
<html>
<head>
	<title>Sage Pay Server PHP Welcome Page</title>
	<link rel="STYLESHEET" type="text/css" href="images/serverKitStyle.css">
	<script type="text/javascript" language="javascript" src="scripts/common.js" ></script>
</head>

<body>
    <div id="pageContainer">
        <? include "header.html"; ?>
        <? include "resourceBar.html"; ?>
        <div id="content">
            <div id="contentHeader">Welcome to the Sage Pay Server PHP Kit </div>
            <p>
                If you are viewing this page in your browser at <strong>
                    <? echo strtolower(substr($_SERVER['SERVER_PROTOCOL'],0,strpos($_SERVER['SERVER_PROTOCOL'],"/"))) . "://" .  $_SERVER["SERVER_NAME"] . $_SERVER["PHP_SELF"]; ?>
                </strong> then you have correctly set up your virtual directory.
            </p>
            <?
				// Check the settings in the includes file					
				if ($strVendorName=="[your Sage Pay Vendor Name]" or $strYourSiteFQDN=="http://[your web site]/" or $strDatabasePassword=="[your database user password]")
				{
					echo "<p>You still need to configure the kit for your site, however, by modifying the includes.php file to set your database connections, Sage Pay Vendor Name and full URL to your site. If you've not already got it open, click the readme icon to the left to  find out how to customise the kit for your server.</p>";
					echo "<div class=\"greyHzShadeBar\">&nbsp;</div>";
				}
				else {
            		echo "<div class=\"greyHzShadeBar\">&nbsp;</div>";
					echo "<table class=\"formTable\">";
					echo "<tr><td colspan=\"2\"><div class=\"subheader\">Your current kit set-up</div></td></tr>";
					echo "<tr><td class=\"fieldLabel\">Sage Pay Vendor Name:</td><td>" . $strVendorName . "</td></tr>";
					echo "<tr><td class=\"fieldLabel\">Default Currency:</td><td>" . $strCurrency . "</td></tr>";
					echo "<tr><td class=\"fieldLabel\">Full External URL to this kit:</td><td>" . $strYourSiteFQDN . $strVirtualDir . "</td></tr>";
					if ($strConnectTo!=="LIVE")
						echo "<tr><td class=\"fieldLabel\">Full Internal URL to this kit:</td><td>" . $strYourSiteInternalFQDN . $strVirtualDir . "</td></tr>";
					echo "<tr><td class=\"fieldLabel\">MySQL Database:</td><td>sagepay</td></tr>";
					echo "<tr><td class=\"fieldLabel\">Database User:</td><td>" . $strDatabaseUser . "</td></tr>";
				}
				$iTotal=0;
				$query = "SELECT count(ProductID) as 'Total' FROM tblProducts";
				$rsPrimary=mysql_query($query)
					or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
				$num=mysql_numrows($rsPrimary);
				if (isset($num))
					$iTotal=mysql_result($rsPrimary,0,"Total");
				$rsPrimary = "";
			 ?>
            <tr>
                <td class="fieldLabel">Products in Database:</td>
                <td>
                    <? echo $iTotal ?>
                </td>
            </tr>
            </table>
        	<p>
            <? if ($strConnectTo=="LIVE") {
				echo "<span class=\"warning\">Your kit is pointing at the Live Sage Pay environment.  You should only do this once your have completed testing on both the Simulator AND Test servers, have sent your GoLive request to the technical support team and had confirmation that your account has been set up. <br><br>";
				echo "<strong>You MUST secure this kit using SSL when the pages here are LIVE.<br>";
				echo "<br>Transactions sent to the Live service WILL charge your customers' cards.</strong></span>";
			}
			elseif ($strConnectTo=="TEST")
				echo "Your kit is pointing at the Sage Pay TEST environment.  This is an exact replica of the Live systems except that no banks are attached, so no authorisation requests are sent, nothing is settled and you can use our test card numbers when making payments. You should only use the test environment after you have completed testing using the Simulator AND the Sage Pay support team have mailed you to let you know your account has been created.<br><br><span class=\"warning\"><strong>If you are already set up on Live and are testing additional functionality, DO NOT leave your kit set to Test or you will not receive any money for your transactions!</strong></span>";
			else
				echo "Your kit is currently pointing at the Simulator. This is an Expert System provided by Sage Pay to enable you to build and configure your site correctly, to debug the messages you send to Sage Pay Server and practise handling responses from it.  No customers are charged, no money is moved around.  The Simulator is for development and testing ONLY.";
			?>
			</p>
            <div class="greyHzShadeBar">&nbsp;</div>
            <div class="formFooter">
                <form action="welcome.php" method="POST" name="mainForm">
                    <div style="clear: both;">
                        <input type="hidden" name="navigate" value="" />
                        <a href="javascript:submitForm('mainForm','proceed');" title="Proceed to the next page" style="float:right;">
                            <img src="images/proceed.gif" alt="Proceed to the next page" border="0" />
                        </a>
                        To begin the purchase process, click the Proceed button.
                    </div>
                    <?
					// Check to see if we already have orders.  If so, display the Admin button too.
					$OrderNumber=0;
					$query="SELECT COUNT(VendorTxCode) as 'OrderNumber' FROM tblOrders";
					$rsPrimary = mysql_query($query)
						or die ("Query '$query' failed with error message: \"" . mysql_error () . '"');
					if (isset($rsPrimary))
						$OrderNumber=mysql_result($rsPrimary,0,"OrderNumber");
					if ($OrderNumber>0) {
                    ?>
                    <div style="clear: both;">
                        <a href="javascript:submitForm('mainForm','admin');" title="Go to the Admin page" style="float:right;">
                            <img src="images/admin.gif" alt="Go to the Admin page" border="0" />
                        </a>
                        Alternatively, to administer your existing orders, click the Admin button.
                    </div>
                    <?
					}
						$rsPrimary="";
						mysql_close();
					?>
                </form>
            </div>
		</div>
	</div>
</body>
</html>


