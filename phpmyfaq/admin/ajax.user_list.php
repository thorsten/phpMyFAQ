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
    $user_data = $user_object->userdata->get(array_keys($data));
    $id = $user_object->getUserId();
    $login = $user_object->getLogin();
    $class = "ad_select_user";
?>
        <user id="<?php print $id; ?>">
            <login><?php print $login; ?></login>
            <user_data>
<?php
    foreach ($user_data as $field => $value) {
        $field_name = $data[$field];
?>

                <item>
                    <name><?php print $field_name; ?></name>
                    <value><?php print $value; ?></value>
                </item>
<?php
    }
?>
                <display_name><?php print $user_data['display_name']; ?></display_name>
                <last_modified><?php print $user_data['last_modified']; ?></last_modified>
                <email><?php print $user_data['email']; ?></email>
            </user_data>
        </user>
<?php
}
?>
    </userlist>
</xml>
