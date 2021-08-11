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
	}
	
	.warning {
		color: red;
		margin: 10px 0px;
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
	echo '
			</td>
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
				eval(str_replace('$LANG_CONF', '$langConfSource', $lineSource));
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
				eval(str_replace('$LANG_CONF', '$langConfTarget', $lineTarget));
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
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<th colspan="3">Strings not found on target file</td>
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
		<textarea id="output" placeholder="The content of your translated file will appear here"></textarea>
		
		<script>
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