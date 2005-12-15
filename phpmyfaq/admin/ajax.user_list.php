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
$data = array(
    'display_name' => 'Name',
    'email' => 'Email',
    'last_modified' => 'Last modified'
);

ob_clean();
?>
<xml>
    <userlist>
        <select_class>ad_select_user</select_class>
<?php
foreach ($userList as $user_id) {
    $user_object = new PMF_User();
    $user_object->getUserById($user_id);
    $user_id = $user_object->getUserId();
    $login = $user_object->getLogin();
    $class = "ad_select_user";
?>
        <user id="<?php print $user_id; ?>">
            <login><?php print $login; ?></login>
            <user_data>
<?php
    $user_data = $user_object->userdata->get(array_keys($data));
    foreach ($user_data as $field => $value) {
        $field_name = $data[$field];
?>

                <item>
                    <name><?php print $field_name; ?></name>
                    <value><?php print $value; ?></value>
                </item>
<?php
    } /* end foreach ($user_data) */
?>
            </user_data>
            <user_rights>
<?php
    $perm = $user_object->perm;
    $all_rights = $perm->getAllRights();
    $user_rights = $perm->getUserRights();
    foreach ($all_rights as $right_id) {
        $right_data = $perm->getRightData($right_id);
        // right is not for users!
        if (!$right_data['for_users'])
            continue;
        $isUserRight = $perm->checkUserRight($user_id, $right_id) ? '1' : '0';
?>
                <right id="<?php print $right_id; ?>">
                    <name><?php print $right_data['name']; ?></name>
                    <description><?php print $right_data['description']; ?></description>
                    <is_user_right><?php print $isUserRight; ?></is_user_right>
                </right>
<?php
    } /* end foreach ($all_rights) */
?>
            </user_rights>
        </user>
<?php
} /* end foreach ($userList) */
?>
    </userlist>
</xml>
