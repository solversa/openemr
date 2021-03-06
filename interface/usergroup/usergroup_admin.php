<?php
/**
 * This script Assign acl 'Emergency login'.
 *
 * @package OpenEMR
 * @link    http://www.open-emr.org
 * @author  Roberto Vasquez <robertogagliotta@gmail.com>
 * @author  Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2015 Roberto Vasquez <robertogagliotta@gmail.com>
 * @copyright Copyright (c) 2017 Brady Miller <brady.g.miller@gmail.com>
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once("../globals.php");
require_once("../../library/acl.inc");
require_once("$srcdir/auth.inc");

$alertmsg = '';
$bg_msg = '';
$set_active_msg=0;
$show_message=0;


/* Sending a mail to the admin when the breakglass user is activated only if $GLOBALS['Emergency_Login_email'] is set to 1 */
$bg_count=count($access_group);
$mail_id = explode(".", $SMTP_HOST);
for ($i=0; $i<$bg_count; $i++) {
    if (($_POST['access_group'][$i] == "Emergency Login") && ($_POST['active'] == 'on') && ($_POST['pre_active'] == 0)) {
        if (($_POST['get_admin_id'] == 1) && ($_POST['admin_id'] != "")) {
            $res = sqlStatement("select username from users where id= ? ", array($_POST["id"]));
            $row = sqlFetchArray($res);
            $uname=$row['username'];
            $mail = new MyMailer();
            $mail->From = $GLOBALS["practice_return_email_path"];
            $mail->FromName = "Administrator OpenEMR";
            $text_body  = "Hello Security Admin,\n\n The Emergency Login user ".$uname.
                                              " was activated at ".date('l jS \of F Y h:i:s A')." \n\nThanks,\nAdmin OpenEMR.";
            $mail->Body = $text_body;
            $mail->Subject = "Emergency Login User Activated";
            $mail->AddAddress($_POST['admin_id']);
            $mail->Send();
        }
    }
}

/* To refresh and save variables in mail frame */
if (isset($_POST["privatemode"]) && $_POST["privatemode"] =="user_admin") {
    if ($_POST["mode"] == "update") {
        if (isset($_POST["username"])) {
            // $tqvar = addslashes(trim($_POST["username"]));
            $tqvar = trim(formData('username', 'P'));
            $user_data = sqlFetchArray(sqlStatement("select * from users where id= ? ", array($_POST["id"])));
            sqlStatement("update users set username='$tqvar' where id= ? ", array($_POST["id"]));
            sqlStatement("update groups set user='$tqvar' where user= ?", array($user_data["username"]));
            //echo "query was: " ."update groups set user='$tqvar' where user='". $user_data["username"]  ."'" ;
        }

        if ($_POST["taxid"]) {
            $tqvar = formData('taxid', 'P');
            sqlStatement("update users set federaltaxid='$tqvar' where id= ? ", array($_POST["id"]));
        }

        if ($_POST["state_license_number"]) {
            $tqvar = formData('state_license_number', 'P');
            sqlStatement("update users set state_license_number='$tqvar' where id= ? ", array($_POST["id"]));
        }

        if ($_POST["drugid"]) {
            $tqvar = formData('drugid', 'P');
            sqlStatement("update users set federaldrugid='$tqvar' where id= ? ", array($_POST["id"]));
        }

        if ($_POST["upin"]) {
            $tqvar = formData('upin', 'P');
            sqlStatement("update users set upin='$tqvar' where id= ? ", array($_POST["id"]));
        }

        if ($_POST["npi"]) {
            $tqvar = formData('npi', 'P');
            sqlStatement("update users set npi='$tqvar' where id= ? ", array($_POST["id"]));
        }

        if ($_POST["taxonomy"]) {
            $tqvar = formData('taxonomy', 'P');
            sqlStatement("update users set taxonomy = '$tqvar' where id= ? ", array($_POST["id"]));
        }

        if ($_POST["lname"]) {
            $tqvar = formData('lname', 'P');
            sqlStatement("update users set lname='$tqvar' where id= ? ", array($_POST["id"]));
        }

        if ($_POST["job"]) {
            $tqvar = formData('job', 'P');
            sqlStatement("update users set specialty='$tqvar' where id= ? ", array($_POST["id"]));
        }

        if ($_POST["mname"]) {
              $tqvar = formData('mname', 'P');
              sqlStatement("update users set mname='$tqvar' where id= ? ", array($_POST["id"]));
        }

        if ($_POST["facility_id"]) {
              $tqvar = formData('facility_id', 'P');
              sqlStatement("update users set facility_id = '$tqvar' where id = ? ", array($_POST["id"]));
              //(CHEMED) Update facility name when changing the id
              sqlStatement("UPDATE users, facility SET users.facility = facility.name WHERE facility.id = '$tqvar' AND users.id = {$_POST["id"]}");
              //END (CHEMED)
        }

        if ($GLOBALS['restrict_user_facility'] && $_POST["schedule_facility"]) {
            sqlStatement("delete from users_facility
            where tablename='users'
            and table_id= ?
            and facility_id not in (" . implode(",", $_POST['schedule_facility']) . ")", array($_POST["id"]));
            foreach ($_POST["schedule_facility"] as $tqvar) {
                sqlStatement("replace into users_facility set
                facility_id = '$tqvar',
                tablename='users',
                table_id = {$_POST["id"]}");
            }
        }

        if ($_POST["fname"]) {
              $tqvar = formData('fname', 'P');
              sqlStatement("update users set fname='$tqvar' where id= ? ", array($_POST["id"]));
        }

        if (isset($_POST['default_warehouse'])) {
            sqlStatement("UPDATE users SET default_warehouse = '" .
            formData('default_warehouse', 'P') .
            "' WHERE id = '" . formData('id', 'P') . "'");
        }

        if (isset($_POST['irnpool'])) {
            sqlStatement("UPDATE users SET irnpool = '" .
            formData('irnpool', 'P') .
            "' WHERE id = '" . formData('id', 'P') . "'");
        }

        if ($_POST["adminPass"] && $_POST["clearPass"]) {
              require_once("$srcdir/authentication/password_change.php");
              $clearAdminPass=$_POST['adminPass'];
              $clearUserPass=$_POST['clearPass'];
              $password_err_msg="";
              $success=update_password($_SESSION['authId'], $_POST['id'], $clearAdminPass, $clearUserPass, $password_err_msg);
            if (!$success) {
                error_log($password_err_msg);
                $alertmsg.=$password_err_msg;
            }
        }

        $tqvar  = $_POST["authorized"] ? 1 : 0;
        $actvar = $_POST["active"]     ? 1 : 0;
        $calvar = $_POST["calendar"]   ? 1 : 0;

        sqlStatement("UPDATE users SET authorized = $tqvar, active = $actvar, " .
        "calendar = $calvar, see_auth = ? WHERE " .
        "id = ? ", array($_POST['see_auth'], $_POST["id"]));
      //Display message when Emergency Login user was activated
        $bg_count=count($_POST['access_group']);
        for ($i=0; $i<$bg_count; $i++) {
            if (($_POST['access_group'][$i] == "Emergency Login") && ($_POST['pre_active'] == 0) && ($actvar == 1)) {
                $show_message = 1;
            }
        }

        if (($_POST['access_group'])) {
            for ($i=0; $i<$bg_count; $i++) {
                if (($_POST['access_group'][$i] == "Emergency Login") && ($_POST['user_type']) == "" && ($_POST['check_acl'] == 1) && ($_POST['active']) != "") {
                    $set_active_msg=1;
                }
            }
        }

        if ($_POST["comments"]) {
            $tqvar = formData('comments', 'P');
            sqlStatement("update users set info = '$tqvar' where id = ? ", array($_POST["id"]));
        }

        $erxrole = formData('erxrole', 'P');
        sqlStatement("update users set newcrop_user_role = '$erxrole' where id = ? ", array($_POST["id"]));

        if ($_POST["physician_type"]) {
            $physician_type = formData('physician_type');
            sqlStatement("update users set physician_type = '$physician_type' where id = ? ", array($_POST["id"]));
        }

        if ($_POST["main_menu_role"]) {
              $mainMenuRole = filter_input(INPUT_POST, 'main_menu_role');
              sqlStatement("update `users` set `main_menu_role` = ? where `id` = ? ", array($mainMenuRole, $_POST["id"]));
        }

        if ($_POST["erxprid"]) {
            $erxprid = formData('erxprid', 'P');
            sqlStatement("update users set weno_prov_id = '$erxprid' where id = ? ", array($_POST["id"]));
        }
      
        if (isset($phpgacl_location) && acl_check('admin', 'acl')) {
            // Set the access control group of user
            $user_data = sqlFetchArray(sqlStatement("select username from users where id= ?", array($_POST["id"])));
            set_user_aro(
                $_POST['access_group'],
                $user_data["username"],
                formData('fname', 'P'),
                formData('mname', 'P'),
                formData('lname', 'P')
            );
        }
    }
}

/* To refresh and save variables in mail frame  - Arb*/
if (isset($_POST["mode"])) {
    if ($_POST["mode"] == "new_user") {
        if ($_POST["authorized"] != "1") {
            $_POST["authorized"] = 0;
        }

        // $_POST["info"] = addslashes($_POST["info"]);

        $calvar = $_POST["calendar"] ? 1 : 0;

        $res = sqlStatement("select distinct username from users where username != ''");
        $doit = true;
        while ($row = sqlFetchArray($res)) {
            if ($doit == true && $row['username'] == trim(formData('rumple'))) {
                $doit = false;
            }
        }

        if ($doit == true) {
            require_once("$srcdir/authentication/password_change.php");

          //if password expiration option is enabled,  calculate the expiration date of the password
            if ($GLOBALS['password_expiration_days'] != 0) {
                $exp_days = $GLOBALS['password_expiration_days'];
                $exp_date = date('Y-m-d', strtotime("+$exp_days days"));
            }

            $insertUserSQL=
            "insert into users set " .
            "username = '"         . trim(formData('rumple')) .
            "', password = '"      . 'NoLongerUsed'                  .
            "', fname = '"         . trim(formData('fname')) .
            "', mname = '"         . trim(formData('mname')) .
            "', lname = '"         . trim(formData('lname')) .
            "', federaltaxid = '"  . trim(formData('federaltaxid')) .
            "', state_license_number = '"  . trim(formData('state_license_number')) .
            "', newcrop_user_role = '"  . trim(formData('erxrole')) .
            "', physician_type = '"  . trim(formData('physician_type')) .
            "', main_menu_role = '"  . trim(formData('main_menu_role')) .
            "', weno_prov_id = '"  . trim(formData('erxprid')) .
            "', authorized = '"    . trim(formData('authorized')) .
            "', info = '"          . trim(formData('info')) .
            "', federaldrugid = '" . trim(formData('federaldrugid')) .
            "', upin = '"          . trim(formData('upin')) .
            "', npi  = '"          . trim(formData('npi')).
            "', taxonomy = '"      . trim(formData('taxonomy')) .
            "', facility_id = '"   . trim(formData('facility_id')) .
            "', specialty = '"     . trim(formData('specialty')) .
            "', see_auth = '"      . trim(formData('see_auth')) .
            "', default_warehouse = '" . trim(formData('default_warehouse')) .
            "', irnpool = '"       . trim(formData('irnpool')) .
            "', calendar = '"      . $calvar                         .
            "', pwd_expiration_date = '" . trim("$exp_date") .
            "'";

            $clearAdminPass=$_POST['adminPass'];
            $clearUserPass=$_POST['stiltskin'];
            $password_err_msg="";
            $prov_id="";
            $success = update_password(
                $_SESSION['authId'],
                0,
                $clearAdminPass,
                $clearUserPass,
                $password_err_msg,
                true,
                $insertUserSQL,
                trim(formData('rumple')),
                $prov_id
            );
            error_log($password_err_msg);
            $alertmsg .=$password_err_msg;
            if ($success) {
                  //set the facility name from the selected facility_id
                  sqlStatement("UPDATE users, facility SET users.facility = facility.name WHERE facility.id = '" . trim(formData('facility_id')) . "' AND users.username = '" . trim(formData('rumple')) . "'");

                  sqlStatement("insert into groups set name = '" . trim(formData('groupname')) .
                    "', user = '" . trim(formData('rumple')) . "'");

                if (isset($phpgacl_location) && acl_check('admin', 'acl') && trim(formData('rumple'))) {
                              // Set the access control group of user
                              set_user_aro(
                                  $_POST['access_group'],
                                  trim(formData('rumple')),
                                  trim(formData('fname')),
                                  trim(formData('mname')),
                                  trim(formData('lname'))
                              );
                }
            }
        } else {
            $alertmsg .= xl('User', '', '', ' ') . trim(formData('rumple')) . xl('already exists.', '', ' ');
        }

        if ($_POST['access_group']) {
            $bg_count=count($_POST['access_group']);
            for ($i=0; $i<$bg_count; $i++) {
                if ($_POST['access_group'][$i] == "Emergency Login") {
                      $set_active_msg=1;
                }
            }
        }
    } else if ($_POST["mode"] == "new_group") {
        $res = sqlStatement("select distinct name, user from groups");
        for ($iter = 0; $row = sqlFetchArray($res); $iter++) {
            $result[$iter] = $row;
        }

        $doit = 1;
        foreach ($result as $iter) {
            if ($doit == 1 && $iter{"name"} == trim(formData('groupname')) && $iter{"user"} == trim(formData('rumple'))) {
                $doit--;
            }
        }

        if ($doit == 1) {
            sqlStatement("insert into groups set name = '" . trim(formData('groupname')) .
            "', user = '" . trim(formData('rumple')) . "'");
        } else {
            $alertmsg .= "User " . trim(formData('rumple')) .
            " is already a member of group " . trim(formData('groupname')) . ". ";
        }
    }
}

if (isset($_GET["mode"])) {
  /*******************************************************************
  // This is the code to delete a user.  Note that the link which invokes
  // this is commented out.  Somebody must have figured it was too dangerous.
  //
  if ($_GET["mode"] == "delete") {
    $res = sqlStatement("select distinct username, id from users where id = '" .
      $_GET["id"] . "'");
    for ($iter = 0; $row = sqlFetchArray($res); $iter++)
      $result[$iter] = $row;

    // TBD: Before deleting the user, we should check all tables that
    // reference users to make sure this user is not referenced!

    foreach($result as $iter) {
      sqlStatement("delete from groups where user = '" . $iter{"username"} . "'");
    }
    sqlStatement("delete from users where id = '" . $_GET["id"] . "'");
  }
  *******************************************************************/

    if ($_GET["mode"] == "delete_group") {
        $res = sqlStatement("select distinct user from groups where id = ?", array($_GET["id"]));
        for ($iter = 0; $row = sqlFetchArray($res); $iter++) {
            $result[$iter] = $row;
        }

        foreach ($result as $iter) {
            $un = $iter{"user"};
        }

        $res = sqlStatement("select name, user from groups where user = '$un' " .
        "and id != ?", array($_GET["id"]));

        // Remove the user only if they are also in some other group.  I.e. every
        // user must be a member of at least one group.
        if (sqlFetchArray($res) != false) {
              sqlStatement("delete from groups where id = ?", array($_GET["id"]));
        } else {
              $alertmsg .= "You must add this user to some other group before " .
                "removing them from this group. ";
        }
    }
}

$form_inactive = empty($_REQUEST['form_inactive']) ? false : true;

?>
<html>
<head>
<title><?php xl('User / Group', 'e');?></title>
<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">
<link rel="stylesheet" href="<?php echo $GLOBALS['assets_static_relative'];?>/bootstrap-3-3-4/dist/css/bootstrap.css" type="text/css">
<?php if ($_SESSION['language_direction'] == 'rtl') : ?>
    <link rel="stylesheet" href="<?php echo $GLOBALS['assets_static_relative'];?>/bootstrap-rtl-3-3-4/dist/css/bootstrap-rtl.css" type="text/css">
<?php endif; ?>
<link rel="stylesheet" type="text/css" href="<?php echo $GLOBALS['webroot'] ?>/library/js/fancybox/jquery.fancybox-1.2.6.css" media="screen" />
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/dialog.js?v=<?php echo $v_js_includes; ?>"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['assets_static_relative']; ?>/jquery-min-1-3-2/index.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/common.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/fancybox/jquery.fancybox-1.2.6.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery-ui.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['webroot'] ?>/library/js/jquery.easydrag.handler.beta2.js"></script>
<script type="text/javascript">

$(document).ready(function(){

    // fancy box
    enable_modals();

    tabbify();

    // special size for
    $(".iframe_medium").fancybox( {
        'overlayOpacity' : 0.0,
        'showCloseButton' : true,
        'frameHeight' : 450,
        'frameWidth' : 660
    });

    $(function(){
        // add drag and drop functionality to fancybox
        $("#fancy_outer").easydrag();
    });
});

function authorized_clicked() {
 var f = document.forms[0];
 f.calendar.disabled = !f.authorized.checked;
 f.calendar.checked  =  f.authorized.checked;
}

</script>

</head>
<body class="body_top">

<div class="container">
    <div class="row">
        <div class="col-xs-12">
            <div class="page-title">
                <h1><?php xl('User / Groups', 'e');?></h1>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="btn-group">
                <a href="usergroup_admin_add.php" class="iframe_medium btn btn-default btn-add"><?php xl('Add User', 'e'); ?></a>
                <a href="facility_user.php" class="btn btn-default"><?php xl('View Facility Specific User Information', 'e'); ?></a>
            </div>
            <form name='userlist' method='post' style="display: inline;" class="form-inline" class="pull-right" action='usergroup_admin.php' onsubmit='return top.restoreSession()'>
                <div class="checkbox">
                    <label for="form_inactive">
                        <input type='checkbox' class="form-control" id="form_inactive" name='form_inactive' value='1' onclick='submit()' <?php if ($form_inactive) {
                            echo 'checked ';
} ?>>
                        <?php xl('Include inactive users', 'e'); ?>
                    </label>
                </div>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <?php
            if ($set_active_msg == 1) {
                echo "<div class='alert alert-danger'>".xl('Emergency Login ACL is chosen. The user is still in active state, please de-activate the user and activate the same when required during emergency situations. Visit Administration->Users for activation or de-activation.')."</div><br>";
            }

            if ($show_message == 1) {
                echo "<div class='alert alert-danger'>".xl('The following Emergency Login User is activated:')." "."<b>".$_GET['fname']."</b>"."</div><br>";
                echo "<div class='alert alert-danger'>".xl('Emergency Login activation email will be circulated only if following settings in the interface/globals.php file are configured:')." \$GLOBALS['Emergency_Login_email'], \$GLOBALS['Emergency_Login_email_id']</div>";
            }

            ?>
            <table class="table table-striped">
                <thead>
                <tr>
                    <th><?php xl('Username', 'e'); ?></th>
                    <th><?php xl('Real Name', 'e'); ?></th>
                    <th><?php xl('Additional Info', 'e'); ?></th>
                    <th><?php xl('Authorized', 'e'); ?>?</th>
                </tr>
                <tbody>
                    <?php
                    $query = "SELECT * FROM users WHERE username != '' ";
                    if (!$form_inactive) {
                        $query .= "AND active = '1' ";
                    }

                    $query .= "ORDER BY username";
                    $res = sqlStatement($query);
                    for ($iter = 0; $row = sqlFetchArray($res); $iter++) {
                        $result4[$iter] = $row;
                    }

                    foreach ($result4 as $iter) {
                        if ($iter{"authorized"}) {
                            $iter{"authorized"} = xl('yes');
                        } else {
                            $iter{"authorized"} = "";
                        }

                        print "<tr>
                            <td><b><a href='user_admin.php?id=" . $iter{"id"} .
                            "' class='iframe_medium' onclick='top.restoreSession()'>" . $iter{"username"} . "</a></b>" ."&nbsp;</td>
                            <td>" . attr($iter{"fname"}) . ' ' . attr($iter{"lname"}) ."&nbsp;</td>
                            <td>" . attr($iter{"info"}) . "&nbsp;</td>
                            <td align='left'><span>" .$iter{"authorized"} . "&nbsp;</td>";
                        print "<td><!--<a href='usergroup_admin.php?mode=delete&id=" . $iter{"id"} .
                            "' class='link_submit'>[Delete]</a>--></td>";
                        print "</tr>\n";
                    }
                    ?>
                </tbody>
            </table>
            <?php
            if (empty($GLOBALS['disable_non_default_groups'])) {
                $res = sqlStatement("select * from groups order by name");
                for ($iter = 0; $row = sqlFetchArray($res); $iter++) {
                    $result5[$iter] = $row;
                }

                foreach ($result5 as $iter) {
                    $grouplist{$iter{"name"}} .= $iter{"user"} .
                        "(<a class='link_submit' href='usergroup_admin.php?mode=delete_group&id=" .
                        $iter{"id"} . "' onclick='top.restoreSession()'>Remove</a>), ";
                }

                foreach ($grouplist as $groupname => $list) {
                    print "<span class='bold'>" . $groupname . "</span><br>\n<span>" .
                        substr($list, 0, strlen($list)-2) . "</span><br>\n";
                }
            }
            ?>
        </div>
    </div>
</div>
<script language="JavaScript">
<?php
if ($alertmsg = trim($alertmsg)) {
    echo "alert('$alertmsg');\n";
}
?>
</script>
</body>
</html>
