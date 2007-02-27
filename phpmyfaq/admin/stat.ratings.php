<?php
/**
 * $Id: stat.ratings.php,v 1.16 2007-02-27 22:27:12 matteo Exp $
 *
 * The page with the ratings of the votings
 *
 * @author       Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since        2003-02-24
 * @copyright    (c) 2001-2007 phpMyFAQ Team
 * 
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 * 
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission["viewlog"]) {
    $category = new PMF_Category('', $current_admin_user, $current_admin_groups, false);
?>
    <h2><?php print $PMF_LANG["ad_rs"] ?></h2>
    <table class="list">
<?php
        $query = '';
        switch($DB["type"]) {
            case 'mssql':
            // In order to remove this MS SQL 2000/2005 "limit" below:
            //   The text, ntext, and image data types cannot be compared or sorted, except when using IS NULL or LIKE operator.
            // we'll cast faqdata.thema datatype from text to char(2000)
            // Note: the char length is simply an heuristic value
            // Doing so we'll also need to trim $row->thema to remove blank chars when it is shorter than 2000 chars
                $query = '
                    SELECT '.SQLPREFIX.'faqdata.id AS id,
                           '.SQLPREFIX.'faqdata.lang AS lang,
                           '.SQLPREFIX.'faqcategoryrelations.category_id AS category_id,
                           cast('.SQLPREFIX.'faqdata.thema as char(2000)) AS thema,
                           ('.SQLPREFIX.'faqvoting.vote / '.SQLPREFIX.'faqvoting.usr) AS num,
                           '.SQLPREFIX.'faqvoting.usr AS usr
                    FROM
                           '.SQLPREFIX.'faqvoting,
                           '.SQLPREFIX.'faqdata
                    LEFT JOIN
                           '.SQLPREFIX.'faqcategoryrelations
                        ON      '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id
                            AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang
                    WHERE
                           '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqvoting.artikel
                    GROUP BY
                           '.SQLPREFIX.'faqdata.id,
                           '.SQLPREFIX.'faqdata.lang,
                           '.SQLPREFIX.'faqdata.active,
                           '.SQLPREFIX.'faqcategoryrelations.category_id,
                           cast('.SQLPREFIX.'faqdata.thema as char(2000)),
                           '.SQLPREFIX.'faqvoting.vote,
                           '.SQLPREFIX.'faqvoting.usr
                    ORDER BY
                           '.SQLPREFIX.'faqcategoryrelations.category_id';
                break;
            default:
                $query = '
                    SELECT '.SQLPREFIX.'faqdata.id AS id,
                           '.SQLPREFIX.'faqdata.lang AS lang,
                           '.SQLPREFIX.'faqcategoryrelations.category_id AS category_id,
                           '.SQLPREFIX.'faqdata.thema AS thema,
                           ('.SQLPREFIX.'faqvoting.vote / '.SQLPREFIX.'faqvoting.usr) AS num,
                           '.SQLPREFIX.'faqvoting.usr AS usr
                    FROM
                           '.SQLPREFIX.'faqvoting,
                           '.SQLPREFIX.'faqdata
                    LEFT JOIN
                           '.SQLPREFIX.'faqcategoryrelations
                        ON     '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id
                           AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang
                    WHERE
                           '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqvoting.artikel
                    GROUP BY
                           '.SQLPREFIX.'faqdata.id,
                           '.SQLPREFIX.'faqdata.lang,
                           '.SQLPREFIX.'faqdata.active,
                           '.SQLPREFIX.'faqcategoryrelations.category_id,
                           '.SQLPREFIX.'faqdata.thema,
                           '.SQLPREFIX.'faqvoting.vote,
                           '.SQLPREFIX.'faqvoting.usr
                    ORDER BY
                           '.SQLPREFIX.'faqcategoryrelations.category_id';
            break;
        }
    $result = $db->query($query);

    $anz = $db->num_rows($result);
    $old = "";
    while ($row = $db->fetch_object($result)) {
        if ($row->category_id != $old) {
?>
    <tr>
        <th colspan="5" class="list"><strong><?php print $category->categoryName[$row->category_id]["name"]; ?></strong></th>
    </tr>
<?php
        }
?>
    <tr>
        <td class="list"><?php print $row->id; ?></td>
        <td class="list"><?php print $row->lang; ?></td>
        <td class="list"><a href="../index.php?action=artikel&amp;cat=<?php print $row->category_id;?>&amp;id=<?php print $row->id;?>&amp;artlang=<?php print $row->lang; ?>" title="<?php print htmlspecialchars(trim($row->thema), ENT_QUOTES, $PMF_LANG['metaCharset']); ?>"><?php print makeShorterText(PMF_htmlentities(trim($row->thema), ENT_NOQUOTES, $PMF_LANG['metaCharset']), 14); ?></a></td>
        <td class="list"><?php print $row->usr; ?></td>
        <td class="list" style="background-color: #d3d3d3;"><img src="stat.bar.php?num=<?php print $row->num; ?>" border="0" alt="<?php print round($row->num * 20); ?> %" width="50" height="15" title="<?php print round($row->num * 20); ?> %" /></td>
    </tr>
<?php
        $old = $row->category_id;
    }
    if ($anz > 0) {
?>
    <tr>
        <td colspan="5" class="list"><span style="color: green; font-weight: bold;"><?php print $PMF_LANG["ad_rs_green"] ?></span> <?php print $PMF_LANG["ad_rs_ahtf"] ?>, <span style="color: red; font-weight: bold;"><?php print $PMF_LANG["ad_rs_red"] ?></span> <?php print $PMF_LANG["ad_rs_altt"] ?></td>
    </tr>
<?php
    } else {
?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" class="list"><?php print $PMF_LANG["ad_rs_no"] ?></td>
        </tr>
    </tfoot>
<?php
    }
?>
    </table>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}
