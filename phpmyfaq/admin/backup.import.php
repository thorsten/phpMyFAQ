<?php
/******************************************************************************
 * File:				backup.import.php
 * Description:			import the backup
 * Authors:				Thorsten Rinne <thorsten@rinne.info>
 * Date:				2003-02-24
 * Last change:			2004-07-23
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
if ($permission["restore"]) {
?>
	<h2><?php print $PMF_LANG["ad_csv_rest"]; ?></h2>
<?php
    if (isset($_FILES["userfile"]["type"]) && ($_FILES["userfile"]["type"] == "application/octet-stream" || $_FILES["userfile"]["type"] == "text/plain")) {
    	$ok = 1;
    	$fp = fopen($_FILES["userfile"]["tmp_name"], "r");
    	$dat = fgets($fp, 65536);
    	
    	if (substr($dat, 0, 5) != "# pmf") {
    		print $PMF_LANG["ad_csv_no"];
    		$ok = 0;
    		}
    	else {
    		$dat = substr($dat, 7, strlen($dat)-6);
    		$tbl = explode(" ", $dat);
    		for ($h = 0; $h <= 10; $h++) {
    			if (isset($tbl[$h])) {
    				$mquery[] = "DELETE FROM ".trim($tbl[$h]);
    				}
    			}
    		$ok = 1;
    		}
    	
    	if ($ok == 1) {
    		print "<p>".$PMF_LANG["ad_csv_prepare"]."</p>\n";
            $dat = trim($dat);
            while (($dat = fgets($fp, 65536))) {
    			if (substr($dat, 0, 1) != "#") {
    				$mquery[] = trim(substr($dat, 0, -1));
    				}
    			}
    		fclose($fp);
    		
    		$k = 0;
    		$g = 0;
    		print "<p>".$PMF_LANG["ad_csv_process"]."</p>\n";
    		flush();
    		$anz = count($mquery);
    		$kg = "";
    		for ($i = 0; $i < $anz; $i++) {
    			$kg = $db->query($mquery[$i]);
    			if (!$kg) {
    				print "<div style=\"font-size: 9px;\"><b>Query</b>: \"".$mquery[$i]."\" <span style=\"color: red;\">failed (Reason: ".$db->error().")</span></div>\n";
    				}
    			else {
    				print "<div style=\"font-size: 9px;\"><b>Query</b>: <!-- \"".$mquery[$i]."\" --> <span style=\"color: green;\">okay</span></div>\n";
    				}
    			flush();
    			}
    		print "<p>".$i." ".$PMF_LANG["ad_csv_of"]." ".$anz." ".$PMF_LANG["ad_csv_suc"]."</p>\n";
    		}
    	}
    else {
    	print "<p>".$PMF_LANG["ad_csv_no"]."</p>";
    	}
	}
else {
	print $PMF_LANG["err_NotAuth"];
	}
?>
