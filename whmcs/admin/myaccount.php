<?php 
define("ADMINAREA", true);
require("../init.php");
$aInt = new WHMCS\Admin("My Account", false);
$aInt->title = $aInt->lang("global", "myaccount");
$aInt->sidebar = "config";
$aInt->icon = "home";
$aInt->helplink = "My Account";
$aInt->requiredFiles(array( "ticketfunctions" ));
$action = $whmcs->get_req_var("action");
$errormessage = "";
$twofa = new WHMCS\TwoFactorAuthentication();
$twofa->setAdminID($_SESSION["adminid"]);
if( $whmcs->get_req_var("2fasetup") ) 
{
    $output = "";
    if( $twofa->isActiveAdmins() ) 
    {
        if( $twofa->isEnabled() ) 
        {
            $disabled = $incorrect = false;
            if( $password = $whmcs->get_req_var("pwverify") ) 
            {
                $auth = new WHMCS\Auth();
                $auth->getInfobyID($_SESSION["adminid"]);
                if( $auth->comparePassword($password) ) 
                {
                    $twofa->disableUser();
                    $disabled = true;
                }
                else
                {
                    $incorrect = true;
                }

            }

            if( !$disabled ) 
            {
                $output .= "<p>" . $aInt->lang("twofa", "disableintro") . "</p>";
                if( $incorrect ) 
                {
                    $output .= "<div class=\"errorbox\"><strong>Password Incorrect</strong>" . "<br />Please try again...</div>";
                }

                $output .= "<form onsubmit=\"dialogSubmit();return false\"><input type=\"hidden\" name=\"2fasetup\" value=\"1\" /><p align=\"center\">" . $aInt->lang("fields", "password") . ": <input type=\"password\" name=\"pwverify\" value=\"\" class=\"form-control input-inline input-250\" /><p><p align=\"center\"><input type=\"button\" value=\"" . $aInt->lang("global", "disable") . "\" class=\"btn btn-primary\" onclick=\"dialogSubmit()\" /></p></form>";
            }
            else
            {
                $output .= "<p>" . $aInt->lang("twofa", "disabledconfirmation") . "</p>";
            }

        }
        else
        {
            $modules = $twofa->getAvailableModules();
            if( isset($module) && in_array($module, $modules) ) 
            {
                $output = $twofa->moduleCall("activate", $module);
                if( is_array($output) && isset($output["completed"]) ) 
                {
                    $msg = (isset($output["msg"]) ? $output["msg"] : "");
                    $settings = (isset($output["settings"]) ? $output["settings"] : array(  ));
                    $backupcode = $twofa->activateUser($module, $settings);
                    $output = "";
                    if( $backupcode ) 
                    {
                        $output = "<div align=\"center\"><h2>" . $aInt->lang("twofa", "activationcomplete") . "</h2>";
                        if( $msg ) 
                        {
                            $output .= "<div style=\"margin:20px;padding:10px;background-color:#f7f7f7;border:1px dashed #cccccc;text-align:center;\">" . $msg . "</div>";
                        }

                        $output .= "<h2>" . $aInt->lang("twofa", "backupcodeis") . ":</h2><div style=\"margin:20px auto;padding:10px;width:280px;background-color:#F2D4CE;border:1px dashed #AE432E;text-align:center;font-size:20px;\">" . $backupcode . "</div><p>" . $aInt->lang("twofa", "backupcodeexpl") . "</p>";
                    }
                    else
                    {
                        $output = $aInt->lang("twofa", "activationerror");
                    }

                }

                if( !$output ) 
                {
                    $output .= $aInt->lang("twofa", "generalerror");
                }

            }
            else
            {
                if( $twofa->isForced() ) 
                {
                    $output .= "<div class=\"infobox\">" . $aInt->lang("twofa", "enforced") . "</div>";
                }

                $output .= "<p>" . $aInt->lang("twofa", "activateintro") . "</p>\n        <form><input type=\"hidden\" name=\"2fasetup\" value=\"1\" />";
                if( 1 < count($modules) ) 
                {
                    $output .= "<p>" . $aInt->lang("twofa", "choose") . "</p>";
                    $mod = new WHMCS\Module\Security();
                    $first = true;
                    foreach( $modules as $module ) 
                    {
                        $mod->load($module);
                        $configarray = $mod->call("config");
                        $output .= " &nbsp;&nbsp;&nbsp;&nbsp; <label class=\"radio-inline\"><input type=\"radio\" name=\"module\" value=\"" . $module . "\"" . (($first ? " checked" : "")) . " /> " . ((isset($configarray["FriendlyName"]["Value"]) ? $configarray["FriendlyName"]["Value"] : ucfirst($module))) . "</label><br />";
                        $first = false;
                    }
                }
                else
                {
                    $output .= "<input type=\"hidden\" name=\"module\" value=\"" . $modules[0] . "\" />";
                }

                $output .= "<p align=\"center\"><br /><input type=\"button\" value=\"" . $aInt->lang("twofa", "getstarted") . " &raquo;\" onclick=\"dialogSubmit()\" class=\"btn btn-primary\" /></form>";
            }

        }

    }
    else
    {
        $output .= "Two-Factor Authentication not enabled";
    }

    $aInt->setBodyContent(array( "body" => $output ));
    $aInt->output();
    throw new WHMCS\Exception\ProgramExit();
}

$file = new WHMCS\File\Directory($whmcs->get_admin_folder_name() . DIRECTORY_SEPARATOR . "templates");
$adminTemplates = $file->getSubdirectories();
if( $action == "save" ) 
{
    check_token("WHMCS.admin.default");
    if( defined("DEMO_MODE") ) 
    {
        redir("demo=1");
    }

    $newPassword = $whmcs->get_req_var("password");
    $newPassword = ($newPassword ? trim($newPassword) : "");
    $passwordRetype = $whmcs->get_req_var("password2");
    $passwordRetype = ($passwordRetype ? trim($passwordRetype) : "");
    $template = $whmcs->getFromRequest("template");
    $language = $whmcs->getFromRequest("language");
    $firstname = $whmcs->getFromRequest("firstname");
    $lastname = $whmcs->getFromRequest("lastname");
    $email = $whmcs->getFromRequest("email");
    $signature = $whmcs->getFromRequest("signature");
    $notes = $whmcs->getFromRequest("notes");
    $ticketnotify = $whmcs->getFromRequest("ticketnotify");
    if( !$auth instanceof WHMCS\Auth ) 
    {
        $auth = new WHMCS\Auth();
    }

    $currentPasswd = $whmcs->get_req_var("currentPasswd");
    $auth->getInfobyID($_SESSION["adminid"]);
    if( $auth->comparePassword($currentPasswd) ) 
    {
        if( $newPassword != $passwordRetype ) 
        {
            $errormessage = $aInt->lang("administrators", "pwmatcherror");
            $action = "edit";
        }
        else
        {
            if( WHMCS\Database\Capsule::table("tblticketdepartments")->where("email", "=", $email)->count() ) 
            {
                $errormessage = AdminLang::trans("administrators.emailCannotBeSupport");
                $action = "edit";
            }
            else
            {
                $currentDetails = WHMCS\User\Admin::find(WHMCS\Session::get("adminid"));
                if( !in_array($template, $adminTemplates) ) 
                {
                    $template = $adminTemplates[0];
                }

                $language = WHMCS\Language\AdminLanguage::getValidLanguageName($language);
                if( $email != $currentDetails->email ) 
                {
                    $currentDetails->email = $email;
                }

                if( $newPassword ) 
                {
                    $auth->getInfobyID(WHMCS\Session::get("adminid"));
                    if( $auth->generateNewPasswordHashAndStore($newPassword) ) 
                    {
                        $auth->generateNewPasswordHashAndStoreForApi(md5($newPassword));
                        $auth->setSessionVars();
                    }

                }

                $currentDetails->firstName = $firstname;
                $currentDetails->lastName = $lastname;
                $currentDetails->signature = $signature;
                $currentDetails->notes = $notes;
                $currentDetails->template = $template;
                $currentDetails->language = $language;
                $currentDetails->receivesTicketNotifications = ($ticketnotify ?: array(  ));
                $currentDetails->passwordResetKey = "";
                $currentDetails->passwordResetData = "";
                $currentDetails->passwordResetExpiry = "0000-00-00 00:00:00";
                $currentDetails->save();
                unset($_SESSION["adminlang"]);
                logActivity("Administrator Account Modified (" . $firstname . " " . $lastname . ")");
                redir("success=true");
            }

        }

    }
    else
    {
        $errormessage = $aInt->lang("administrators", "currentPassError");
    }

}

WHMCS\Session::release();
$result = select_query("tbladmins", "tbladmins.*,tbladminroles.name,tbladminroles.supportemails", array( "tbladmins.id" => $_SESSION["adminid"] ), "", "", "", "tbladminroles ON tbladminroles.id=tbladmins.roleid");
$data = mysql_fetch_array($result);
$supportEmailsEnabled = (bool) $data["supportemails"];
if( !$errormessage ) 
{
    $firstname = $data["firstname"];
    $lastname = $data["lastname"];
    $email = $data["email"];
    $signature = $data["signature"];
    $notes = $data["notes"];
    $template = $data["template"];
    $language = $data["language"];
    $ticketnotifications = $data["ticketnotifications"];
    $ticketnotify = explode(",", $ticketnotifications);
}
else
{
    if( !is_array($ticketnotify) ) 
    {
        $ticketnotify = array(  );
    }

}

$username = $data["username"];
$adminrole = $data["name"];
$language = WHMCS\Language\AdminLanguage::getValidLanguageName($language);
ob_start();
$infobox = "";
if( defined("DEMO_MODE") ) 
{
    infoBox("Demo Mode", "Actions on this page are unavailable while in demo mode. Changes will not be saved.");
}

if( $whmcs->get_req_var("success") ) 
{
    infoBox($aInt->lang("administrators", "changesuccess"), $aInt->lang("administrators", "changesuccessinfo2"));
}

if( !empty($errormessage) ) 
{
    infoBox($aInt->lang("global", "validationerror"), $errormessage, "error");
}

echo $infobox;
echo "\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "?action=save\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "username");
echo "</td><td class=\"fieldarea\"><b>";
echo $username;
echo "</b></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("administrators", "role");
echo "</td><td class=\"fieldarea\"><strong>";
echo $adminrole;
echo "</strong></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "firstname");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"firstname\" class=\"form-control input-250\" value=\"";
echo $firstname;
echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "lastname");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"lastname\" class=\"form-control input-250\" value=\"";
echo $lastname;
echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "email");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"email\" class=\"form-control input-400\" value=\"";
echo $email;
echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("administrators", "ticketnotifications");
echo "</td><td class=\"fieldarea\">\n";
if( !$supportEmailsEnabled ) 
{
    echo "<div class=\"alert alert-warning top-margin-10 bottom-margin-10\"><i class=\"fa fa-warning\"></i> &nbsp; " . $aInt->lang("administrators", "ticketNotificationsUnavailable") . "</div>";
}

echo "<div class=\"row\">\n    <div class=\"col-sm-10 col-sm-offset-1\">\n        <div class=\"row\">";
$nodepartments = true;
$supportdepts = getAdminDepartmentAssignments();
foreach( $supportdepts as $deptid ) 
{
    $deptname = get_query_val("tblticketdepartments", "name", array( "id" => $deptid ));
    if( $deptname ) 
    {
        echo "<div class=\"col-sm-6\">\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"ticketnotify[]\" value=\"" . $deptid . "\"" . ((in_array($deptid, $ticketnotify) ? " checked" : "")) . (($supportEmailsEnabled ? "" : " disabled")) . " />\n                " . $deptname . "\n            </label>\n        </div>";
        $nodepartments = false;
    }

}
if( $nodepartments ) 
{
    echo "<div class=\"col-xs-12\">" . $aInt->lang("administrators", "nosupportdeptsassigned") . "</div>";
}

echo "</div>\n    </div>\n</div></div>\n</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("administrators", "supportsig");
echo "</td><td class=\"fieldarea\"><textarea name=\"signature\" rows=\"4\" class=\"form-control\">";
echo $signature;
echo "</textarea></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("global", "mynotes");
echo "</td><td class=\"fieldarea\"><textarea name=\"notes\" rows=\"4\" class=\"form-control\">";
echo $notes;
echo "</textarea></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "template");
echo "</td><td class=\"fieldarea\"><select name=\"template\" class=\"form-control select-inline\">";
foreach( $adminTemplates as $temp ) 
{
    echo "<option value=\"" . $temp . "\"";
    if( $temp == $template ) 
    {
        echo " selected";
    }

    echo ">" . ucfirst($temp) . "</option>";
}
echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("global", "language");
echo "</td><td class=\"fieldarea\"><select name=\"language\" class=\"form-control select-inline\">";
foreach( WHMCS\Language\AdminLanguage::getLanguages() as $lang ) 
{
    echo "<option value=\"" . $lang . "\"";
    if( $lang == $language ) 
    {
        echo " selected=\"selected\"";
    }

    echo ">" . ucfirst($lang) . "</option>";
}
echo "</select></td></tr>\n";
if( $twofa->isActiveAdmins() ) 
{
    echo "<tr>\n    <td class=\"fieldlabel\">" . $aInt->lang("twofa", "title") . "</td>\n    <td class=\"fieldarea\">\n        <input type=\"checkbox\"" . (($twofa->isEnabled() ? " checked" : "")) . " class=\"twofa-toggle-switch\" /> &nbsp;";
    if( $twofa->isEnabled() ) 
    {
        echo "<a href=\"myaccount.php?2fasetup=1\" class=\"open-modal twofa-config-link\" data-modal-title=\"" . $aInt->lang("twofa", "disable", 1) . "\">" . $aInt->lang("twofa", "disableclickhere") . "</a>";
    }
    else
    {
        echo "<a href=\"myaccount.php?2fasetup=1\" class=\"open-modal twofa-config-link\" data-modal-title=\"" . $aInt->lang("twofa", "enable", 1) . "\">" . $aInt->lang("twofa", "enableclickhere") . "</a>";
    }

    echo "</td>\n</tr>";
}

echo "</table>\n\n<p>";
echo $aInt->lang("administrators", "entertochange");
echo "</p>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "password");
echo "</td><td class=\"fieldarea\"><input type=\"password\" name=\"password\" class=\"form-control input-250\" autocomplete=\"off\"></td></tr>\n<tr><td class=\"fieldlabel\" >";
echo $aInt->lang("fields", "confpassword");
echo "</td><td class=\"fieldarea\"><input type=\"password\" name=\"password2\" class=\"form-control input-250\" autocomplete=\"off\"></td></tr>\n</table>\n\n<p>\n    ";
echo $aInt->lang("administrators", "confirmAdminPasswd");
echo "</p>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"20%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "confpassword");
echo "</td>\n        <td class=\"fieldarea\">\n            <input type=\"password\" name=\"currentPasswd\" class=\"form-control input-250\" autocomplete=\"off\" required>\n        </td>\n    </tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("global", "savechanges");
echo "\" class=\"btn btn-primary\">\n    <input type=\"reset\" value=\"";
echo $aInt->lang("global", "cancelchanges");
echo "\" class=\"btn btn-default\" />\n</div>\n\n</form>\n\n";
$aInt->jquerycode = "\njQuery(\".twofa-toggle-switch\").bootstrapSwitch(\n    {\n        \"size\": \"mini\",\n        \"onColor\": \"success\",\n        \"onSwitchChange\": function(event, state)\n        {\n            \$(\".twofa-config-link\").click();\n        }\n    }\n);";
if( $whmcs->get_req_var("2faenforce") ) 
{
    $aInt->jquerycode .= "\$(\".twofa-config-link\").click();";
}

$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();

