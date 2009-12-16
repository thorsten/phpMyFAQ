<?php
/**
 * This is XML code for OpenSearch
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
 * @copyright 2006-2009 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2006-11-19
 */

define('PMF_ROOT_DIR', dirname(__FILE__));

require PMF_ROOT_DIR . '/inc/Init.php';
require PMF_ROOT_DIR . '/inc/Link.php';

$baseUrl   = PMF_Link::getSystemUri('/opensearch.php');
$searchUrl = $baseUrl . '/index.php?action=search';
$srcUrl    = $baseUrl;

$opensearch     = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<OpenSearchDescription xmlns=\"http://a9.com/-/spec/opensearch/1.1/\">
<ShortName>".$faqconfig->get('main.titleFAQ')."</ShortName>
<Description>".$faqconfig->get('main.metaDescription')."</Description>
<Url type=\"text/html\" template=\"".$search_url."&amp;search={searchTerms}\" />
<Language>".$PMF_LANG['metaLanguage']."</Language>
<OutputEncoding>utf-8</OutputEncoding>
<Contact>".$faqconfig->get('main.administrationMail')."</Contact>
<Image height=\"16\" width=\"16\" type=\"image/png\">".$baseUrl."/images/pmfsearch.png</Image>
</OpenSearchDescription>";

header("Content-type: text/xml");
print $opensearch;