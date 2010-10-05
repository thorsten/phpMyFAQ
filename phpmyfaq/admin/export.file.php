<?php
/**
 * XML, XML DocBook, XHTML and PDF export - streamer page
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
 * @package   Administration
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-11-02
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

//
// GET Parameters Syntax:
//   export.file.php?
//          type={docbook|pdf|xhtml|xml}
//      [&dispos={inline|attachment}], default: attachment
//       [&catid=NN[&downwards=1]], default: all, downwards
//

$categoryId        = PMF_Filter::filterInput(INPUT_POST, 'catid', FILTER_VALIDATE_INT, 0);
$downwards         = PMF_Filter::filterInput(INPUT_POST, 'downwards', FILTER_VALIDATE_BOOLEAN, false);
$inlineDisposition = PMF_Filter::filterInput(INPUT_POST, 'dispos', FILTER_VALIDATE_BOOLEAN, false);
$type              = PMF_Filter::filterInput(INPUT_POST, 'type', FILTER_SANITIZE_STRING, 'none');

$faq     = new PMF_Faq();
$export  = PMF_Export::create($faq, $category, $type);
$content = $export->generate($categoryId, $downwards);

// Stream the file content
$oHttpStreamer = new PMF_HttpStreamer($type, $content);
if ($inlineDisposition) {
    $oHttpStreamer->send(PMF_HttpStreamer::HTTP_CONTENT_DISPOSITION_INLINE);
} else {
    $oHttpStreamer->send(PMF_HttpStreamer::HTTP_CONTENT_DISPOSITION_ATTACHMENT);
}