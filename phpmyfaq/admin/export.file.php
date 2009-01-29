<?php
/**
 * XML, XML DocBook, XHTML and PDF export - streamer page
 *
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Matteo Scaramuccia <matteo@scaramuccia.com>
 * @since      2005-11-02
 * @copyright  2005-2009 phpMyFAQ Team
 * @version    SVN: $Id$
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

include '../inc/Export.php';

//
// GET Parameters Syntax:
//   export.file.php?
//          type={docbook|pdf|xhtml|xml}
//      [&dispos={inline|attachment}], default: attachment
//       [&catid=NN[&downwards=1]], default: all, downwards
//

$catid             = PMF_Filter::filterInput(INPUT_GET, HTTP_PARAMS_GET_CATID, FILTER_VALIDATE_INT, 0);
$downwards         = PMF_Filter::filterInput(INPUT_GET, HTTP_PARAMS_GET_DOWNWARDS, FILTER_VALIDATE_BOOLEAN, false);
$inlineDisposition = PMF_Filter::filterInput(INPUT_GET, HTTP_PARAMS_GET_DISPOSITION, FILTER_VALIDATE_BOOLEAN, false);
$type              = PMF_Filter::filterInput(INPUT_GET, HTTP_PARAMS_GET_TYPE, FILTER_SANITIZE_STRING, EXPORT_TYPE_NONE);

// Prepare the file content to be streamed
switch ($type) {
    case EXPORT_TYPE_DOCBOOK:
        $content = PMF_Export::getDocBookExport($catid, $downwards);
        break;
    case EXPORT_TYPE_PDF:
        $content = PMF_Export::getPDFExport($catid, $downwards);
        break;
    case EXPORT_TYPE_XHTML:
        $content = PMF_Export::getXHTMLExport($catid, $downwards);
        break;
    case EXPORT_TYPE_XML:
        $content = PMF_Export::getXMLExport($catid, $downwards);
        break;
    // In this case no default statement is required:
    // the one above is just for clean coding style
    default:
        break;
}

// Stream the file content
$oHttpStreamer = new PMF_HttpStreamer($type, $content);
if ($inlineDisposition) {
    $oHttpStreamer->send(HTTP_CONTENT_DISPOSITION_INLINE);
}
else {
    $oHttpStreamer->send(HTTP_CONTENT_DISPOSITION_ATTACHMENT);
}