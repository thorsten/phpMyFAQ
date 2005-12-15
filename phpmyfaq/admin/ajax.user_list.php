<?php

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header("HTTP/1.0 401 Unauthorized");
    header("Status: 401 Unauthorized");
    exit();
}

@header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
@header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
@header("Cache-Control: no-store, no-cache, must-revalidate");
@header("Cache-Control: post-check=0, pre-check=0", false);
@header("Pragma: no-cache");
@header("Content-type: text/xml");
@header("Vary: Negotiate,Accept");

require_once(PMF_ROOT_DIR.'/inc/PMF_User/User.php');

$user = new PMF_User();
$userList = $user->getAllUsers();

ob_clean();
?>
<xml>
    <itemlist>
<?php
foreach ($userList as $user_id) {
    $user_object = new PMF_User();
    $user_object->getUserById($user_id);
    $id = $user_object->getUserId();
    $login = $user_object->getLogin();
    $class = "ad_select_user";
?>
        <item>
            <id><?php print $id; ?></id>
            <type><?php print $class; ?></type>
            <name><?php print $login; ?></name>
        </item>
<?php
}
?>
    </itemlist>
</xml>
