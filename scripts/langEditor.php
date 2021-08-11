<?php
	
 /**
 * This file is intended to make it easier to translate the language files. 
 * It makes visible the strings already translated, the ones that exist on 
 * a file but not on the original, and the ones that are not translated yet.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Everton Leite <etcholeite@gmail>
 * @copyright 2012-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2021-08-11
 */

//checking for sintax errors to prevent crashes when exporting the file to the application
if ($_POST && key_exists("sintaxCheck", $_POST) && $_POST["sintaxCheck"] == 1 && key_exists("output", $_POST)) {
	echo "check!!";
	$lines = explode("\n", $_POST["output"]);
	foreach ($lines as $line) {
		if (strlen(trim($line)) > 0) {
			echo "<<<<" . $line . ">>>>";
			eval($line);
		}
	}
	echo "{sintaxCheckPassed}";
	exit;
}
?>

<style>
	body {
		font-family: sans-serif;
		font-size: 12px;
	}
	
	td, th {
		font-size: 12px;
	}
	
	th {
		background: #DDD;
	}
	
	.nowrap {
		white-space: nowrap;
		width: 1px;
	}
	
	.red {
		background: #ec9b9b;
	}
	
	.yellow {
		background: #fff175;
	}
	
	.green {
		background: #86f486;
	}
	
	#output {
		width: 100%;
		height: 500px;
		display: block;
		margin-top: 10px;
		background-color: #EEE;
		color: #666;
	}
	
	.warning {
		color: red;
		margin: 10px 0px;
	}
	
	.copy_from_source {
		text-decoration: none;
		background-color: #DDD;
		color: #000;
		padding: 0px 3px;
		border: 1px solid #666;
		font-size: 16px;
	}
	
	#sintax_check_message {
		margin-top: 10px;
	}
</style>

<?php

$pmfLangSource = [];
$langConfSource = [];
$pmfLangTarget = [];
$langConfTarget= [];
$indexSource= [];
$indexTarget = [];

function renderTargetTableLine($index, &$pmfLangSource, &$pmfLangTarget, &$langConfSource, &$langConfTarget) {
	eval('@$sourceEntry = ' . str_replace('$PMF_LANG', '$pmfLangSource', str_replace('$LANG_CONF', '$langConfSource', $index)) . ';');
	eval('@$targetEntry = ' . str_replace('$PMF_LANG', '$pmfLangTarget', str_replace('$LANG_CONF', '$langConfTarget', $index)) . ';');
	$keyFounded = $sourceEntry !== null;
	$class = $keyFounded ? "green" : "yellow";
	echo '
		<tr class="' . $class . '">
			<td class="nowrap key">' . $index . '</td>
			<td style="width: 50%;" class="source">';
	if ($keyFounded) {
		echo htmlspecialchars($sourceEntry);
	} else {
		echo '<em><strong>String not found in source file</strong></em>';
	}
	echo '</td>
			<td class="nowrap"><a class="copy_from_source" title="Copy from source" href="#" onclick="copyFromSource(this); return false;">&raquo;</td>
			<td style="width: 50%">
				<textarea style="width: 100%;" class="target ' . $class . '">' . htmlspecialchars($targetEntry) . '</textarea>
			</td>
		</tr>';
}

function renderSourceTableLine($index, &$pmfLangSource, &$pmfLangTarget, &$langConfSource, &$langConfTarget) {
	eval('@$sourceEntry = ' . str_replace('$PMF_LANG', '$pmfLangSource', str_replace('$LANG_CONF', '$langConfSource', $index)) . ';');
	eval('@$targetEntry = ' . str_replace('$PMF_LANG', '$pmfLangTarget', str_replace('$LANG_CONF', '$langConfTarget', $index)) . ';');
	$keyFounded = $targetEntry !== null;
	if ($keyFounded){
		return;
	}
	$class = "red";
	echo '
		<tr class="' . $class . '">
			<td class="nowrap key">' . $index . '</td>
			<td style="width: 50%;" class="source">' . htmlspecialchars($sourceEntry) . '</td>
			<td class="nowrap"><a class="copy_from_source" title="Copy from source" href="#" onclick="copyFromSource(this); return false;">&raquo;</td>
			<td style="width: 50%">
				<textarea style="width: 100%;" class="target ' . $class . '"></textarea>
			</td>
		</tr>';
}

if ($_POST) {
	$source = $_FILES["source"];
	$target = $_FILES["target"];

	if ($source === null || $target === null || $source["name"] === "" || $target["name"] === "") {
		echo 'You must select both files first';
	} else {
		$fileSource = fopen($source["tmp_name"], "r");
		$fileTarget = fopen($target["tmp_name"], "r");
		while (($lineSource = fgets($fileSource)) !== false) {
			if (strpos($lineSource, '$PMF_LANG') === 0) {
				eval(str_replace('$PMF_LANG', '$pmfLangSource', $lineSource));
				$indexSource[] = trim(substr($lineSource, 0, strpos($lineSource, '=')));
			}
			if (strpos($lineSource, '$LANG_CONF') === 0) {
				//checking for sintax errors on the original file, just to be sure
				echo '<div id="sintax_error">Sintax error on <strong>source</strong> file: <pre>'. $lineSource. '</pre></div>';
				eval(str_replace('$LANG_CONF', '$langConfSource', $lineSource));
				echo '<script> document.getElementById("sintax_error").remove(); </script>';
				$indexSource[] = trim(substr($lineSource, 0, strpos($lineSource, '=')));
				$lineSource = str_replace('$LANG_CONF', '$langConfSource', $lineSource);
				$idx = trim(substr($lineSource, 0, strpos($lineSource, '=')));
				$val = substr(str_replace("'", "\'", trim(substr($lineSource, strpos($lineSource, '=') + 1, strlen($lineSource)))), 0, -1);
				eval($idx . " = '$val';");
			}
		}
		while (($lineTarget = fgets($fileTarget)) !== false) {
			if (strpos($lineTarget, '$PMF_LANG') === 0) {
				eval(str_replace('$PMF_LANG', '$pmfLangTarget', $lineTarget));
				$indexTarget[] = trim(substr($lineTarget, 0, strpos($lineTarget, '=')));
			}
			if (strpos($lineTarget, '$LANG_CONF') === 0) {
				echo '<div id="sintax_error">Sintax error on <strong>target</strong> file: <pre>'. $lineTarget . '</pre></div>';
				eval(str_replace('$LANG_CONF', '$langConfTarget', $lineTarget));
				echo '<script> document.getElementById("sintax_error").remove(); </script>';
				$indexTarget[] = trim(substr($lineTarget, 0, strpos($lineTarget, '=')));
				$lineTarget = str_replace('$LANG_CONF', '$langConfTarget', $lineTarget);
				$idx = trim(substr($lineTarget, 0, strpos($lineTarget, '=')));
				$val = substr(str_replace("'", "\'", trim(substr($lineTarget, strpos($lineTarget, '=') + 1, strlen($lineTarget)))), 0, -1);
				eval($idx . " = '$val';");
			}
		}
?>
		<div class="warning">
			* All line breaks gonna be removed. Textareas are just to make it easy to see the text. br tags will be fine. \n will be gonne.<br />
			* Strings will always be trimmed.
		</div>
		<table style="width: 100%">
			<thead>
				<tr>
					<th>Key</th>
					<th>Source string</th>
					<th></th>
					<th>Target string</th>
				</tr>
			</thead>
			<tbody>
				<?php 
					foreach ($indexTarget as $index) { 
						renderTargetTableLine($index, $pmfLangSource, $pmfLangTarget, $langConfSource, $langConfTarget);
					}
					echo '
						<tr>
							<td colspan="4">&nbsp;</td>
						</tr>
						<tr>
							<th colspan="4">Strings not found on target file</td>
						</tr>
					';
					foreach ($indexSource as $index) {
						renderSourceTableLine($index, $pmfLangSource, $pmfLangTarget, $langConfSource, $langConfTarget);
					}
				?>
			</tbody>
		</table>
		<br /><br />
		
		<button id="generate">Generate target file content</button>
		<div id="sintax_check_message"></div>
		<textarea readonly="readonly" id="output" placeholder="The content of your translated file will appear here"></textarea>
		
		<script>
			function copyFromSource(btn) {
				btn.parentElement.nextElementSibling.querySelector(".target").value = btn.parentElement.previousElementSibling.innerHTML;
			}
		
			var ready = (callback) => {
				if (document.readyState != "loading") {
					callback();
				} else {
					document.addEventListener("DOMContentLoaded", callback);
				}
			}

			ready(() => { 
				document.querySelector("#generate").addEventListener("click", (e) => { 
					var textarea = document.querySelector("#output");
					var output = '';
					var sintaxCheckMessage = document.querySelector("#sintax_check_message");
					sintaxCheckMessage.innerHTML = "";
					document.querySelectorAll(".target").forEach(target => {
						var value = target.value.replaceAll("\n", "").trim();
						if (value.length === 0) {
							return;
						}
						var key = target.parentElement.parentElement.querySelector(".key").innerHTML;
						var type = key.indexOf("$PMF_LANG") === 0 ? "PMF_LANG" : "LANG_CONF";
						if (type === "PMF_LANG") {
							value = "'" + value.replaceAll("'", "\\'").trim() + "'";
						}
						output += key + " = " + value + ";\n";
					})
					textarea.value = output;

					//sending an ajax request with the output to check for possible sintax errors and typos
					var xhttp = new XMLHttpRequest();
					xhttp.onreadystatechange = function() {
						if (this.readyState == 4 && this.status == 200) {
							if (this.responseText.substr(this.responseText.length - 19, 19) === "{sintaxCheckPassed}") {
								sintaxCheckMessage.style.color = "green";
								sintaxCheckMessage.innerHTML = 'No sintax errors found! You are good to go.';
							} else {
								var lastLineErrorBegin = this.responseText.lastIndexOf("<<<<");
								var lastLineErrorEnd = this.responseText.lastIndexOf(">>>>");
								var line = this.responseText.substr(lastLineErrorBegin + 4, lastLineErrorEnd - lastLineErrorBegin - 4);
								sintaxCheckMessage.style.color = "red";
								sintaxCheckMessage.innerHTML = 'Sintax error on the following line: <pre>' + line + '</pre>';
							}
						}
					};
					xhttp.open("POST", "langEditor.php", true);
					xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
					xhttp.send("sintaxCheck=1&output=" + document.getElementById("output").value);
				});
			});
		</script>
<?php
	}
} else {
?>

<form method="post" enctype="multipart/form-data">
	<input type="hidden" name="flag" value="1" />
	<label>Source lang file (EN):
		<input type="file" name="source" />
	</label>
	<br />
	<label>Target lang file:
		<input type="file" name="target" />
	</label>
	<br /><br />
	<input type="submit" value="compare" />
</form>

<?php 
}