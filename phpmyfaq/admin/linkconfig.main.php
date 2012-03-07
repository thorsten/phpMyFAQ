<?php
/**
 * LinkVerifier configuration.
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * The Initial Developer of the Original Code is released for external use
 * with permission from NetJapan, Inc. IT Administration Group.
 *
 * @category  phpMyFAQ
 * @package   Administraion
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @todo      Move all queries and functions into the class PMF_Linkverifier
 * @copyright 2005-2011 NetJapan, Inc. and phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-11-07
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

// Re-evaluate $user
$user = PMF_User_CurrentUser::getFromSession($faqConfig->get('security.ipCheck'));

// Define number of entries per page
$entriesPerPage = 10;

/**
 * Enumerate linkconfig specific parameters into array
 */
function enumScriptParameters() {
    global $params, $_REQUEST;

    $scriptParameters = array('uin' => false,
                              'action' => 'linkconfig',
                              'sortby' => 'id',
                              'sortorder' => 'DESC',
                              'type' => 'WARN',
                              'page' => '1');

    $params = array();
    foreach ($scriptParameters as $_key => $_default) {
        if (isset($_REQUEST[$_key])) {
            $params[$_key] = $_REQUEST[$_key];
        } else {
            if ($_default !== false) {
                $params[$_key] = $_default;
            }
        }
    }
}

/**
 * Generate URLs with modified parameters
 *
 * @param    mixed   $optparams
 * @return   string  URL to script
 */
function issueURL($optparams = array()) {
    global $params, $_SERVER;

    $_params = array();
    foreach ($params as $_key => $_value) {
        $_params[$_key] = $_value;
    }

    foreach ($optparams as $_key => $_value) {
        if ($_value === false) {
            $_params[$_key] = null;
            unset($_params[$_key]);
        } else {
            $_params[$_key] = $_value;
        }
    }

    $_url = $_SERVER['SCRIPT_NAME'];
    $_separator = '?';

    foreach ($_params as $_key => $_value) {
        $_url .= $_separator.$_key.'='.urlencode($_value);
        $_separator = '&amp;';
    }

    return $_url;
}

/**
 * Show table for list type selection
 */
function showListTypeSelection()
{
    global $params, $PMF_LANG;
?>
        <p>
        <ul>
    <?php
        $_description = '';
        foreach (array('WARN' => 'ad_linkcheck_config_warnlist',
                       'IGNORE' => 'ad_linkcheck_config_ignorelist') as $_type => $_name) {
            if ($params['type'] == $_type) {
                $_description = $PMF_LANG[$_name.'_description'];
            }
            ?>
            <li class="<?php print ($params['type'] == $_type ? 'selected' : 'selectable'); ?>">
                <a href="<?php print issueURL(array('type' => $_type, 'page' => 1)); ?>">
                    <?php print $PMF_LANG[$_name]; ?>
                </a>
            </li>
        <?php
            }
    ?>
        </ul>
        </p>
<?php
    print $_description;
}

// load parameters into array
enumScriptParameters();

?>
        <header>
            <h2><?php print $PMF_LANG['ad_linkcheck_config_title']; ?></h2>
        </header>
<?php
$linkverifier = new PMF_Linkverifier($user->getLogin());
if ($linkverifier->isReady() == false) {
    print $PMF_LANG['ad_linkcheck_config_disabled'];
    return;
}

showListTypeSelection();

    $_admin   = (isset($permission['editconfig']) && $permission['editconfig'] ? true : false);
    $rowcount = PMF_Filter::filterInput(INPUT_POST, 'rowcount', FILTER_VALIDATE_INT);
    if (!is_null($rowcount)) {
        for ($i = 0; $i < $rowcount; $i++) {
            // load form posts
            $posts = array();
            foreach (array( 'id'        => false,
                            'url'       => '',
                            'reason'    => '',
                            'enabled'   => 'n',
                            'locked'    => 'n',
                            'chown'     => 'n',
                            'delete'    => 'n'
                        ) as $_key => $_default) {

                if (isset($_POST[$_key][$i])) {
                    $posts[$_key] = $_POST[$_key][$i];
                } else {
                    $posts[$_key] = $_default;
                }
            }

            switch($posts['id']) {
                case 'NEW':
                    $linkverifier->addVerifyRule($params['type'], $posts['url'], $posts['reason']);
                    break;

                default:
                    $query = sprintf(
                                "SELECT
                                    *
                                FROM
                                    %sfaqlinkverifyrules
                                WHERE
                                        type='%s'
                                    AND id=%d",
                                SQLPREFIX,
                                $db->escape($params['type']),
                                $posts['id']
                                );
                    $row = false;
                    $result = $db->query($query);
                    if ($db->numRows($result) > 0) {
                        $row = $db->fetchObject($result);
                        $_owner = ($row->owner == $user->getLogin() ? true : false);
                        // check if chown ?
                        if ((!$_owner) && ($posts['chown'] == 'y') && ($_admin || ($posts['locked'] == 'n'))) {
                            $query = sprintf(
                                        "UPDATE
                                            %sfaqlinkverifyrules
                                        SET
                                            owner = '%s',
                                            dtUpdateDate = '%s'
                                            WHERE id = %d",
                                        SQLPREFIX,
                                        $db->escape($user->getLogin()),
                                        $db->escape(date('YmdHis')),
                                        $posts['id']
                                        );
                            $db->query($query);
                            $_owner = true;
                            break;
                        }
                        // check whether we need to unlock
                        if (($_owner || $_admin) && ($row->locked == 'y') && ($posts['locked'] == 'n')) {
                            $query = sprintf(
                                        "UPDATE
                                            %sfaqlinkverifyrules
                                        SET
                                            locked='n'
                                        WHERE
                                            id=%d",
                                        SQLPREFIX,
                                        $posts['id']
                                        );
                            $db->query($query);
                            break;
                        }
                        // check whether we need to update info
                        if ($_owner) {
                            $query = sprintf(
                                        "UPDATE
                                            %sfaqlinkverifyrules
                                        SET
                                            url='%s',
                                            reason='%s',
                                            enabled='%s',
                                            locked='%s',
                                            dtUpdateDate = '%s'
                                        WHERE
                                            id=%d",
                                        SQLPREFIX,
                                        $db->escape($posts['url']),
                                        $db->escape($posts['reason']),
                                        $db->escape($posts['enabled']),
                                        $db->escape($posts['locked']),
                                        $db->escape(date('YmdHis')),
                                        $posts['id']
                                        );
                            $db->query($query);
                        }
                        // check whethr we need to delete
                        if ($_owner && ($row->locked == 'n') && ($posts['delete'] == 'y')) {
                            $query = sprintf(
                                        "DELETE
                                        FROM
                                            %sfaqlinkverifyrules
                                        WHERE
                                            id = %d",
                                        SQLPREFIX,
                                        $posts['id']
                                        );
                            $db->query($query);
                        }
                    }
            }
        }
    }

    $query = sprintf(
                "SELECT
                    *
                FROM
                    %sfaqlinkverifyrules
                WHERE
                    type = '%s'
                ORDER BY
                    %s %s",
                SQLPREFIX,
                $db->escape($params['type']),
                $params['sortby'],
                $params['sortorder']
                );
    $result = $db->query($query);
    $pages = ceil($db->numRows($result) / $entriesPerPage);
    $page = $params['page'] = max(1,min($pages,$params['page']));
    $result = $db->query($query);

?>
        <form method="post" action="<?php print issueURL(); ?>">
        <table class="list" id="configuration" style="width: 100%">
            <thead>
            <tr>
                <?php foreach (array("id", "url", "reason", "owner") as $_key): ?>
                <th class="<?php print ($params['sortby'] == $_key ? 'selected' : 'selectable'); ?>">
                    <a href="<?php print issueURL(array('sortby' => $_key, 'sortorder' => (($params['sortby'] == $_key) && ($params['sortorder'] == 'DESC') ? 'ASC' : 'DESC'))); ?>">
                    <?php print $PMF_LANG['ad_linkcheck_config_th_'.$_key]; ?>
                    </a>
                </th>
                <?php endforeach; ?>
            <th><?php print $PMF_LANG["ad_gen_delete"]; ?></th>
            </tr>
            </thead>
            <tbody>
    <?php
        $id = 0;
        $icurrent = 0;
        $istart = ($params['page'] - 1) * $entriesPerPage;
        $iend = $istart + $entriesPerPage;
        while ($row = $db->fetchObject($result) && $iend >= $icurrent && $istart <= $icurrent++) {
            $_owner = ($row->owner == $user->getLogin() ? true : false);
    ?>
            <tr>
                <!-- ID and Enable/Disable -->
                <input type="hidden" name="id[<?php print $id; ?>]" value="<?php print $row->id; ?>">
                <td><input type="checkbox" name="enabled[<?php print $id; ?>]" value="y" <?php print ($row->enabled == 'y' ? 'checked' : ''); ?> title="<?php print $PMF_LANG['ad_linkcheck_config_th_enabled']; ?>" <?php print ($_owner ? '' : 'disabled'); ?> >
                #<?php print $row->id; ?></td>

                <!-- URL to match -->
                <td><input type="text" name="url[<?php print $id; ?>]"  value="<?php print PMF_String::htmlspecialchars($row->url); ?>"  <?php print ($_owner ? '' : 'disabled'); ?>  ></td>

                <!-- Reason to warn/ignore -->
                <td><input type="text" name="reason[<?php print $id; ?>]" value="<?php print PMF_String::htmlspecialchars($row->reason); ?>"  <?php print ($_owner ? '' : 'disabled'); ?>  ></td>

                <!-- Lock entry / chown entry -->
                <td>
                <?php
                if ($row->locked == 'y') {
                    if ($_owner || $_admin) { ?>
                        <input type="checkbox" name="locked[<?php print $id; ?>]" value="y" checked title="<?php print $PMF_LANG['ad_linkcheck_config_th_locked']; ?>">
                    <?php } ?>
                    <img src="images/locked.png" />
                <?php } else { ?>
                    <input type="checkbox" name="<?php print ($_owner ? 'locked' : 'chown').'['.$id.']'; ?>" value="y" title="<?php print ($_owner ? $PMF_LANG['ad_linkcheck_config_th_locked'] : $PMF_LANG['ad_linkcheck_config_th_chown']); ?>">
                    <img src="images/<?php print ($_owner ? 'locked.png' : 'chown.png'); ?>" />
                <?php } ?>
                <?php print $row->owner; ?></td>

                <?php if ($_owner && ($row->locked == 'n')) { ?>
                <!-- Delete Entry -->
                <td><input type="checkbox" value="y" name="delete[<?php print $id; ?>]" /></td>
                <?php } ?>
            </tr>

        <?php
        $id++;
        }

        for ($i = 0; $i < 3; $i++) {
            ?>
            <tr>
                <input type="hidden" name="id[<?php print $id; ?>]" value="NEW" />
                <td>NEW</td>
                <td><input type="text" name="url[<?php print $id; ?>]" value="" /></td>
                <td><input type="text" name="reason[<?php print $id; ?>]" value="" /></td>
                <td><?php print($user->getLogin()); ?></td>
                <td>&nbsp;</td>
            </tr>
    <?php
        $id++;
        }

        // Handle submission and page listing
    ?>
            </tbody>
            <tfoot>
            <tr>
                <td>
                    <input type="hidden" name="rowcount" value="<?php print $id; ?>" />
                    <input class="btn-primary" type="submit" value="<?php print $PMF_LANG["ad_gen_save"]; ?>" name="submit" />
                </td>
                <td colspan="4">
                    <?php print $PMF_LANG["ad_gen_page"].' '.$page.(($pages > 0 )? ' '.$PMF_LANG["ad_gen_of"].' '.$pages : ''); ?>
                    <?php
                    if ($page > 1){
                        print sprintf(
                                ' | <a href="%s">%s</a>',
                                issueURL(array('page' => $page - 1)),
                                $PMF_LANG['ad_gen_lastpage']
                                );
                    }
                    for ($i = 1; $i <= $pages; $i++) {
                        print ($i == 1 ? ' | ' : ', ');
                        if ($i != $page) {
                            print sprintf(
                                '<a href="%s">%s</a>',
                                issueURL(array('page' => $i)),
                                $i
                                );
                        } else {
                            print $i;
                        }
                    }

                    if ($page < $pages) {
                        print sprintf(
                                ' | <a href="%s">%s</a>',
                                issueURL(array('page' => $page + 1)),
                                $PMF_LANG['ad_gen_nextpage']
                                );
                    }
                    ?>
                </td>
            </tr>
            </tfoot>
        </table>
        </form>