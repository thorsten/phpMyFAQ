<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{#phpmyfaq_dlg.title}</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta http-equiv="content-language" content="en">
    <meta name="application-name" content="phpMyFAQ">
    <meta name="copyright" content="(c) 2001-2015 phpMyFAQ Team">
    <script type="text/javascript" src="../../../../assets/js/libs/jquery.min.js"></script>
    <script type="text/javascript" src="../../tiny_mce_popup.js"></script>
    <script type="text/javascript" src="js/dialog.js"></script>
</head>
<body>

<?php

define('PMF_ROOT_DIR', dirname(dirname(dirname(dirname(__DIR__)))));
define('IS_VALID_PHPMYFAQ', null);

require PMF_ROOT_DIR . '/inc/Bootstrap.php';

$user = PMF_User_CurrentUser::getFromCookie($faqConfig);
if (! $user instanceof PMF_User_CurrentUser) {
    $user = PMF_User_CurrentUser::getFromSession($faqConfig);
}

?>

<form onsubmit="phpmyfaqDialog.insert(); return false;" action="post">
    <input type="hidden" name="csrf" id="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>">
    
    <div class="title">{#phpmyfaq_dlg.title}</div>
    <input id="suggestbox" type="text" name="search" value="" size="64" autocomplete="off">
    
    <div id="suggestions">
    
    </div>
    
    <div class="mceActionPanel">
        <input type="button" id="insert" name="insert" value="{#insert}" onclick="phpmyfaqDialog.insert();" />
        <input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
    </div>
</form>

</body>
</html>