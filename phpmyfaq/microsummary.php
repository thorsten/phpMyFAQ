<?php
/**
 * Microsummary backend
 * 
 * @todo Rewrite using XMLWriter
 *
 * PHP Version 5.2
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
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2006-09-05
 */

header("Expires: Tue, 07 Apr 1977 14:47:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: text/xml");
header("Vary: Negotiate,Accept");

require_once 'config/constants.php';
require_once 'inc/Filter.php';
require_once 'inc/Link.php';

//
// Found an action reference?
//
$action = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING, 'main');

if (!isset($allowedVariables[$action])) {
    $action = "main";
}

//
// Define what are the actions for which a microsummary generator (microsummary.php) is defined
//
$microRules = array(
    'artikel' => 'phpMyFAQ Faq Records',
    'main'    => 'phpMyFAQ Homepage',
    'news'    => 'phpMyFAQ Latest News',
    'open'    => 'phpMyFAQ Open Questions',
    'show'    => 'phpMyFAQ Categories',
    'ask'     => 'phpMyFAQ Ask Question',
    'search'  => 'phpMyFAQ Search',
    'sitemap' => 'phpMyFAQ Sitemap');

print '<?xml version="1.0" encoding="UTF-8"?>';
?>
<generator xmlns="http://www.mozilla.org/microsummaries/0.1"
           name="<?php print($microRules[$action]); ?>">
  <template>
    <transform xmlns="http://www.w3.org/1999/XSL/Transform" version="1.0">
      <output method="text"/>
<?php
switch($action) {
    case 'main': // Home Page: Last News
?>
      <template match="/">
        <value-of select="id('header')/h1/a"/>
        <text>: </text>
        <value-of select="id('news')/h3[1]/a[1]"/>
      </template>
<?php
        break;
    case 'news': // News Record: Update date
?>
      <template match="/">
        <value-of select="id('news_header')"/>
        <text> - </text>
        <value-of select="id('newsLastUpd')"/>
      </template>
<?php
        break;
    case 'artikel': // Faq Record: Popularity (== #visits)
?>
      <template match="/">
        <text><value-of select="id('popularity')/text()"/> - </text>
        <value-of select="id('main')/h2[2]"/>
      </template>
<?php
        break;
    case 'show': // Category Record: Number of Faq
?>
      <template match="/">
        <text>#<value-of select="id('totFaqRecords')/text()"/> </text>
        <value-of select="id('main')/h2"/>
      </template>
<?php
        break;
    case 'open': // Open questions: Last question
?>
      <template match="/">
        <value-of select="id('main')/table/tbody/tr[last()]/td[2]"/>
      </template>
<?php
        break;
    default;
?>
      <template match="/">
        <value-of select="id('header')/h1/a"/>
      </template>
<?php
        break;
}
?>
      <pages>
        <include><?php print(PMF_Link::getSystemUri('/microsummary.php')); ?>/*</include>
      </pages>
    </transform>
  </template>
</generator>
