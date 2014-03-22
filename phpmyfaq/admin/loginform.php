<?php
/**
 * The login form
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alexander M. Turek <me@derrabus.de>
 * @copyright 2005-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2013-02-05
 */

?>

    <header>
        <h2>phpMyFAQ Login</h2>
    </header>
<?php
if (isset($error) && 0 < strlen($error)) {
    $message = sprintf(
        '<p class="alert alert-error">%s%s</p>',
        '<a class="close" data-dismiss="alert" href="#">&times;</a>',
        $error
    );
} else {
    $message = sprintf('<p>%s</p>', $PMF_LANG['ad_auth_insert']);
}
if ($action == 'logout') {
    $message = sprintf(
        '<p class="alert alert-success">%s%s</p>',
        '<a class="close" data-dismiss="alert" href="#">&times;</a>',
        $PMF_LANG['ad_logout']
    );
}

if (isset($_SERVER['HTTPS']) || !$faqConfig->get('security.useSslForLogins')) {
    ?>

    <?php print $message ?>

    <form class="form-horizontal" action="<?php echo $faqSystem->getSystemUri($faqConfig) ?>admin/index.php" method="post" accept-charset="utf-8">

    <div class="control-group">
        <label class="control-label" for="faqusername"><?php print $PMF_LANG["ad_auth_user"]; ?></label>
        <div class="controls">
            <input type="text" name="faqusername" id="faqusername" required="required" />
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="faqpassword"><?php print $PMF_LANG["ad_auth_passwd"]; ?></label>
        <div class="controls">
            <input type="password" name="faqpassword" id="faqpassword" required="required" />
        </div>
    </div>

    <div class="control-group">
        <div class="controls">
            <label class="checkbox">
                <input type="checkbox" id="faqrememberme" name="faqrememberme" value="rememberMe">
                <?php print $PMF_LANG['rememberMe'] ?>
            </label>
        </div>
    </div>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">
            <?php print $PMF_LANG["ad_auth_ok"]; ?>
        </button>
    </div>
<?php
} else {
    printf('<p><a href="https://%s%s">%s</a></p>',
        $_SERVER['HTTP_HOST'],
        $_SERVER['REQUEST_URI'],
        $PMF_LANG['msgSecureSwitch']);
}
?>
</form>
