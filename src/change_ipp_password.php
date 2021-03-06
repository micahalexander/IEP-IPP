<?php

//the authorization level for this page!
$MINIMUM_AUTHORIZATION_LEVEL = 100;  //don't rely on this on this page.

/**
 * change_ipp_password.php --
 *
 * Copyright (c) 2005 Grasslands Regional Division #6
 * All rights reserved
 *
 * Created: March 26, 2006.
 * By: M. Nielsen
 * Modified: February 11, 2007. M. Nielsen
 *
 */

/**
 * Path for IPP required files.
 */

$MESSAGE = "";

define('IPP_PATH','../');

/* eGPS required files. */
require_once(IPP_PATH . 'etc/init.php');
require_once(IPP_PATH . 'include/db.php');
require_once(IPP_PATH . 'include/auth.php');
require_once(IPP_PATH . 'include/log.php');
require_once(IPP_PATH . 'include/user_functions.php');

header('Pragma: no-cache'); //don't cache this page!

if(isset($_POST['LOGIN_NAME']) && isset( $_POST['PASSWORD'] )) {
    if(!validate( $_POST['LOGIN_NAME'] ,  $_POST['PASSWORD'] )) {
        $MESSAGE = $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
        require(IPP_PATH . 'src/login.php');
        exit();
    }
} else {
    if(!validate()) {
        $MESSAGE = $MESSAGE . $error_message;
        IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
        require(IPP_PATH . 'src/login.php');
        exit();
    }
}
//************* SESSION active past here **************************

//check permission levels
if(isset($ippuserid)) $ippuserid=$ippuserid; else $ippuserid="";
if(($_SESSION['egps_username'] != $ippuserid ) && (getPermissionLevel($_SESSION['egps_username']) > $MINIMUM_AUTHORIZATION_LEVEL && !(isLocalAdministrator($_SESSION['egps_username'])))) {
    $MESSAGE = $MESSAGE . "You do not have permission to view this page (IP: " . $_SERVER['REMOTE_ADDR'] . ")";
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
    require(IPP_PATH . 'src/security_error.php');
    exit();
}

$permission_level = getPermissionLevel($_SESSION['egps_username']);

if(isset($_GET['username'])) $ippuserid=addslashes($_GET['username']);
   else if(isset($_POST['username'])) $ippuserid=addslashes($_POST['username']);
   else $ippuserid = $_SESSION['egps_username'];

//we want to run a check to make sure that if we are a local admin that
//we can't access a person not at our school...
if(($_SESSION['egps_username'] != $ippuserid ) && (isLocalAdministrator($_SESSION['egps_username']) && getPermissionLevel($_SESSION['egps_username']) > $MINIMUM_AUTHORIZATION_LEVEL)) {
  //we are a local administrator with no other access rights (ie we're a local admin but not a principal as well)
  $user_query= "SELECT * FROM support_member WHERE egps_username='$ippuserid'";
  $user_result = mysql_query($user_query);
  if(!$user_result) {
    $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$user_query'<BR>";
    $MESSAGE= $MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
  } else {
    if(mysql_num_rows($user_result) <= 0) "IPP Member not found<BR>Query=$user_query";
    $user_row=mysql_fetch_array($user_result);
  }

  $us_query= "SELECT * FROM support_member WHERE egps_username='" . $_SESSION['egps_username'] . "'";
  $us_result = mysql_query($us_query);
  if(!$us_result) {
    $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$us_query'<BR>";
    $MESSAGE= $MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
  } else {
    if(mysql_num_rows($us_result) <= 0) $MESSAGE .= "IPP Member not found<BR>Query=$us_query";
    $us_row=mysql_fetch_array($us_result);
  }

  if($user_row['school_code'] != $us_row['school_code']) {
     $MESSAGE = $MESSAGE . "You do not have permission to view this page. You must be in the same school as this person to edit their information. (" . $user_row['school_code'] . "!=" . $us_row['school_code'] . ")";
     IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
     require(IPP_PATH . 'src/security_error.php');
     exit();
  }
} else {
  //we are either not a local admin, we are this user or we have a permission level above MIN.
  //so make sure we hmmm...
}

//************** validated past here SESSION ACTIVE****************


if(isset($_POST['Update'])) {
   //check for blanks passwords...
   if(addslashes($_POST['pwd1']) == addslashes($_POST['pwd2']) && addslashes($_POST['pwd2']) == "") {
       $MESSAGE .= "Passwords cannot be blank, try again<BR>";
   } {
     if(addslashes($_POST['pwd1']) != addslashes($_POST['pwd2'])) {
       $MESSAGE .= "Passwords do not match, try again<BR>";
     } else {
       $pwd = addslashes($_POST['pwd1']);
       $update_query = "UPDATE users SET unencrypted_password='" . addslashes($pwd) . "', encrypted_password=PASSWORD('" . addslashes($pwd) . "') WHERE login_name=concat('" . $ippuserid ."','$mysql_user_append_to_login') LIMIT 1";
          $update_result = mysql_query($update_query);
          //$MESSAGE .= $update_query . "<BR>";
          if(!$update_result) {
              $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$update_query'<BR>";
              $MESSAGE=$MESSAGE . $error_message;
              IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
          } else {
             //success...
             if(($_SESSION['egps_username'] != $ippuserid )) {
                //header("Location: https://" . $_SERVER['HTTP_HOST']. dirname($_SERVER['PHP_SELF']). "/" . IPP_PATH . "src/main.php" );
                header("Location: " . IPP_PATH . "src/main.php");
                exit();
             } else {
                //header("Location: https://" . $_SERVER['HTTP_HOST']. dirname($_SERVER['PHP_SELF']). "/" . IPP_PATH );
        header("Location: " . IPP_PATH);
                exit();
             }
          }
     }
   }
  //$MESSAGE .= "-->" . $_POST['is_local_ipp_administrator'] . "<--";
}


$user_query= "SELECT * FROM support_member WHERE egps_username='$ippuserid'";
$user_result = mysql_query($user_query);
if(!$user_result) {
    $error_message = "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$user_query'<BR>";
    $MESSAGE= $MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
} else {
  if(mysql_num_rows($user_result) <= 0) $MESSAGE .= "IPP Member not found<BR>";
  $user_row=mysql_fetch_array($user_result);
}

$school_query="SELECT * FROM school WHERE 1=1";
$school_result=mysql_query($school_query);

if(!$school_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$school_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
}

$permission_query = "SELECT * FROM permission_levels WHERE 1=1 ORDER BY level DESC ";
$permission_result = mysql_query($permission_query);
if(!$permission_result) {
    $error_message = $error_message . "Database query failed (" . __FILE__ . ":" . __LINE__ . "): " . mysql_error() . "<BR>Query: '$permission_query'<BR>";
    $MESSAGE=$MESSAGE . $error_message;
    IPP_LOG($MESSAGE,$_SESSION['egps_username'],'ERROR');
}

?> 
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
    <META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=iso-8859-1">
    <TITLE><?php echo $page_title; ?></TITLE>
    <style type="text/css" media="screen">
        <!--
            @import "<?php echo IPP_PATH;?>layout/greenborders.css";
        -->
    </style>
    <!-- All code Copyright &copy; 2005 Grasslands Regional Division #6.
         -Concept and Design by Grasslands IPP Focus Group 2005
         -Programming and Database Design by M. Nielsen, Grasslands
          Regional Division #6
         -CSS and layout images are courtesy A. Clapton.
     -->
</HEAD>
    <BODY>
        <table class="shadow" border="0" cellspacing="0" cellpadding="0" align="center">  
        <tr>
          <td class="shadow-topLeft"></td>
            <td class="shadow-top"></td>
            <td class="shadow-topRight"></td>
        </tr>
        <tr>
            <td class="shadow-left"></td>
            <td class="shadow-center" valign="top">
                <table class="frame" width=620px align=center border="0">
                    <tr align="Center">
                    <td><center><img src="<?php echo $page_logo_path; ?>"></center></td>
                    </tr>
                    <tr>
                        <td valign="top">
                        <div id="main">
                        <?php if ($MESSAGE) { echo "<center><table width=\"80%\"><tr><td><p class=\"message\">" . $MESSAGE . "</p></td></tr></table></center>";} ?>

                        <center><table><tr><td><center><p class="header">- Change Password-</p></center></td></tr></table></center>

                        <center>
                        <form enctype="multipart/form-data" action="<?php echo IPP_PATH . "src/change_ipp_password.php"; ?>" method="post">
                        <input type="hidden" name="username" value="<?php echo $user_row['egps_username']; ?>">
                        <table border="0" cellpadding="0" cellspacing="0" width="80%">
                        <tr>
                          <td colspan="3">
                          <p class="info_text">Edit and Click Update</p>
                          </td>
                        </tr>
                        <tr>
                          <td bgcolor="#E0E2F2" align="left">Username:</td><td bgcolor="#E0E2F2"><input type="text" value="<?php echo $user_row['egps_username']; ?>" disabled name="userid" length="30"></td><td align="left" bgcolor="#E0E2F2" rowspan="5">&nbsp;&nbsp;<input type="submit" name="Update" value="Update" tabindex="3"></td>
                        </tr>
                        <tr><td bgcolor="#E0E2F2" align="right">&nbsp;</td><td bgcolor="#E0E2F2">
                        &nbsp;
                        </td></tr>
                        <tr><td bgcolor="#E0E2F2" align="letf">Password </td><td bgcolor="#E0E2F2">
                        <input type="password" name="pwd1" size="30" maxsize="30" tabindex="1">
                        </td>
                        </tr>
                        <tr><td bgcolor="#E0E2F2" align="left">Password (retype)</td><td bgcolor="#E0E2F2">
                        <input type="password" name="pwd2" size="30" maxsize="30" tabindex="2">
                        </td>
                        </tr>
                        <tr>
                        <td colspan="2" bgcolor="#E0E2F2" align="center">&nbsp;</td>
                        </tr>
                        </table>
                        <input type="hidden" name="szBackGetVars" value="<?php echo $szBackGetVars; ?>">
                        </form>
                        </center>

                        </div>
                        </td>
                    </tr>
                </table></center>
            </td>
            <td class="shadow-right"></td>   
        </tr>
        <tr>
            <td class="shadow-left">&nbsp;</td>
            <td class="shadow-center"><table border="0" width="100%"><tr><td width="60"><a href="
            <?php
                echo IPP_PATH . "src/main.php";
            ?>"><img src="<?php echo IPP_PATH; ?>images/back-arrow-white.png" border=0></a></td><td width="60"><a href="<?php echo IPP_PATH . "src/main.php"; ?>"><img src="<?php echo IPP_PATH; ?>images/homebutton-white.png" border=0></a></td><td valign="bottom" align="center">Logged in as: <?php echo $_SESSION['egps_username'];?></td><td align="right"><a href="<?php echo IPP_PATH;?>"><img src="<?php echo IPP_PATH; ?>images/logout-white.png" border=0></a></td></tr></table></td>
            <td class="shadow-right">&nbsp;</td>
        </tr>
        <tr>
            <td class="shadow-bottomLeft"></td>
            <td class="shadow-bottom"></td>
            <td class="shadow-bottomRight"></td>
        </tr>
        </table> 
        <center>System Copyright &copy; 2005 Grasslands Regional Division #6.</center>
    </BODY>
</HTML>