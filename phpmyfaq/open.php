<?php
/******************************************************************************
 * Datei:				open.php
 * Autor:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Datum:				2002-09-17
 * Letzte Änderung:		2004-02-19
 * Copyright:           (c) 2001-2004 Thorsten Rinne
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
 ******************************************************************************/

Tracking("antworten", 0);

$tpl->processTemplate ("writeContent", array(
				"msgOpenQuestions" => $PMF_LANG["msgOpenQuestions"],
				"msgQuestionText" => $PMF_LANG["msgQuestionText"],
				"msgDate_User" => $PMF_LANG["msgDate_User"],
				"msgQuestion2" => $PMF_LANG["msgQuestion2"],
				"printOpenQuestions" => printOpenQuestions()
				));

$tpl->includeTemplate("writeContent", "index");
?>