<?php
/**
 * This is XML code for OpenSearch
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
 * @copyright 2006-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2006-11-19
 */

define('PMF_ROOT_DIR', dirname(__FILE__));
define('IS_VALID_PHPMYFAQ', null);

require PMF_ROOT_DIR . '/inc/Init.php';
require PMF_ROOT_DIR . '/inc/Link.php';

require_once 'lang/' . $faqconfig->get('main.language');

$baseUrl   = PMF_Link::getSystemUri('/opensearch.php');
$searchUrl = $baseUrl . '/index.php?action=search';
$srcUrl    = $baseUrl;

$opensearchXml = new XMLWriter();
$opensearchXml->openMemory();
$opensearchXml->setIndent(true);
$opensearchXml->startDocument('1.0', 'utf-8');
$opensearchXml->startElement('OpenSearchDescription');
$opensearchXml->writeAttribute('xmlns', 'http://a9.com/-/spec/opensearch/1.1/');

$opensearchXml->writeElement('ShortName', $faqconfig->get('main.titleFAQ'));
$opensearchXml->writeElement('Description', $faqconfig->get('main.metaDescription'));
$opensearchXml->startElement('Url');
$opensearchXml->writeAttribute('type', 'text/html');
$opensearchXml->writeAttribute('template', $searchUrl . '&search={searchTerms}');
$opensearchXml->endElement();
$opensearchXml->writeElement('Language', $PMF_LANG['metaLanguage']);
$opensearchXml->writeElement('OutputEncoding', 'utf-8');
$opensearchXml->writeElement('Contact', $faqconfig->get('main.administrationMail'));
$opensearchXml->startElement('Image');
$opensearchXml->writeAttribute('height', 16);
$opensearchXml->writeAttribute('width', 16);
$opensearchXml->writeAttribute('type', 'image/png');
$opensearchXml->text($baseUrl . '/images/pmfsearch.png');

$opensearchXml->endDocument();

header("Content-type: text/xml");
print $opensearchXml->outputMemory(true);