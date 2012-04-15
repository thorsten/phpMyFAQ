<?php
/**
 * This is XML code for OpenSearch
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2006-11-19
 */

define('PMF_ROOT_DIR', __DIR__);
define('IS_VALID_PHPMYFAQ', null);

require PMF_ROOT_DIR . '/inc/Bootstrap.php';
require PMF_ROOT_DIR . '/inc/Link.php';
require 'lang/' . $faqConfig->get('main.language');

$baseUrl   = $faqConfig->get('main.referenceURL');
$searchUrl = $baseUrl . '/index.php?action=search';

$opensearchXml = new XMLWriter();
$opensearchXml->openMemory();
$opensearchXml->setIndent(true);
$opensearchXml->startDocument('1.0', 'utf-8');
$opensearchXml->startElement('OpenSearchDescription');
$opensearchXml->writeAttribute('xmlns', 'http://a9.com/-/spec/opensearch/1.1/');

$opensearchXml->writeElement('ShortName', $faqConfig->get('main.titleFAQ'));
$opensearchXml->writeElement('Description', $faqConfig->get('main.metaDescription'));
$opensearchXml->startElement('Url');
$opensearchXml->writeAttribute('type', 'text/html');
$opensearchXml->writeAttribute('template', $searchUrl . '&search={searchTerms}');
$opensearchXml->endElement();
$opensearchXml->writeElement('Language', $PMF_LANG['metaLanguage']);
$opensearchXml->writeElement('OutputEncoding', 'utf-8');
$opensearchXml->writeElement('Contact', $faqConfig->get('main.administrationMail'));
$opensearchXml->startElement('Image');
$opensearchXml->writeAttribute('height', 16);
$opensearchXml->writeAttribute('width', 16);
$opensearchXml->writeAttribute('type', 'image/png');
$opensearchXml->text($baseUrl . '/assets/img/pmfsearch.png');

$opensearchXml->endDocument();

header('Content-type: text/xml');
print $opensearchXml->outputMemory(true);