<?php
/******************************************************************************
 * Datei:				contact.php
 * Autor:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Datum:				2002-09-16
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

Tracking("contact", 0);

$writeSendAdress = $_SERVER["PHP_SELF"]."?".$sids."action=sendmail";

$tpl->processTemplate ("writeContent", array(
				"msgContact" => $PMF_LANG["msgContact"],
				"msgContactOwnText" => unhtmlentities($PMF_CONF["msgContactOwnText"]),
				"msgContactEMail" => $PMF_LANG["msgContactEMail"],
				"writeSendAdress" => $writeSendAdress,
				"msgNewContentName" => $PMF_LANG["msgNewContentName"],
				"msgNewContentMail" => $PMF_LANG["msgNewContentMail"],
				"msgMessage" => $PMF_LANG["msgMessage"],
				"msgS2FButton" => $PMF_LANG["msgS2FButton"],
				"version" => $PMF_CONF["version"]
				));

$tpl->includeTemplate("writeContent", "index");
?>