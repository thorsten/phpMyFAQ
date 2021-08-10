<?php
	
/**
 * This file is intended to make it easier to translate the language files. 
 * It makes visible the strings already translated, the ones that exist on 
 * a file but not on the original, and the ones that are not translated yet.
 *
 * @package   phpMyFAQ
 * @author    Everton Leite <etcholeite@gmail.com>
 */
	
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

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

$PMF_LANG_source = array();
$LANG_CONF_source = array();
$PMF_LANG_target = array();
$LANG_CONF_target = array();
$index_source = array();
$index_target = array();

function render_target_table_line($index, &$PMF_LANG_source, &$PMF_LANG_target, &$LANG_CONF_source, &$LANG_CONF_target) {
	eval('@$source_entry = ' . str_replace('$PMF_LANG', '$PMF_LANG_source', str_replace('$LANG_CONF', '$LANG_CONF_source', $index)) . ';');
	eval('@$target_entry = ' . str_replace('$PMF_LANG', '$PMF_LANG_target', str_replace('$LANG_CONF', '$LANG_CONF_target', $index)) . ';');
	$key_founded = $source_entry !== null;
	$class = $key_founded ? "green" : "yellow";
	echo '
		<tr class="' . $class . '">
			<td class="nowrap key">' . $index . '</td>
			<td style="width: 50%;" class="source">';
	if ($key_founded) {
		echo htmlspecialchars($source_entry);
	} else {
		echo '<em><strong>String not found in source file</strong></em>';
	}
	echo '
			</td>
			<td style="width: 50%">
				<textarea style="width: 100%;" class="target ' . $class . '">' . htmlspecialchars($target_entry) . '</textarea>
			</td>
		</tr>';
}

function render_source_table_line($index, &$PMF_LANG_source, &$PMF_LANG_target, &$LANG_CONF_source, &$LANG_CONF_target) {
	eval('@$source_entry = ' . str_replace('$PMF_LANG', '$PMF_LANG_source', str_replace('$LANG_CONF', '$LANG_CONF_source', $index)) . ';');
	eval('@$target_entry = ' . str_replace('$PMF_LANG', '$PMF_LANG_target', str_replace('$LANG_CONF', '$LANG_CONF_target', $index)) . ';');
	$key_founded = $target_entry !== null;
	if ($key_founded){
		return;
	}
	$class = "red";
	echo '
		<tr class="' . $class . '">
			<td class="nowrap key">' . $index . '</td>
			<td style="width: 50%;" class="source">' . htmlspecialchars($source_entry) . '</td>
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
		$file_source = fopen($source["tmp_name"], "r");
		$file_target = fopen($target["tmp_name"], "r");
		while (($line_source = fgets($file_source)) !== false) {
			if (strpos($line_source, '$PMF_LANG') === 0) {
				eval(str_replace('$PMF_LANG', '$PMF_LANG_source', $line_source));
				$index_source[] = trim(substr($line_source, 0, strpos($line_source, '=')));
			}
			if (strpos($line_source, '$LANG_CONF') === 0) {
				eval(str_replace('$LANG_CONF', '$LANG_CONF_source', $line_source));
				$index_source[] = trim(substr($line_source, 0, strpos($line_source, '=')));
				$line_source = str_replace('$LANG_CONF', '$LANG_CONF_source', $line_source);
				$idx = trim(substr($line_source, 0, strpos($line_source, '=')));
				$val = substr(str_replace("'", "\'", trim(substr($line_source, strpos($line_source, '=') + 1, strlen($line_source)))), 0, -1);
				eval($idx . " = '$val';");
			}
		}
		while (($line_target = fgets($file_target)) !== false) {
			if (strpos($line_target, '$PMF_LANG') === 0) {
				eval(str_replace('$PMF_LANG', '$PMF_LANG_target', $line_target));
				$index_target[] = trim(substr($line_target, 0, strpos($line_target, '=')));
			}
			if (strpos($line_target, '$LANG_CONF') === 0) {
				eval(str_replace('$LANG_CONF', '$LANG_CONF_target', $line_target));
				$index_target[] = trim(substr($line_target, 0, strpos($line_target, '=')));
				$line_target = str_replace('$LANG_CONF', '$LANG_CONF_target', $line_target);
				$idx = trim(substr($line_target, 0, strpos($line_target, '=')));
				$val = substr(str_replace("'", "\'", trim(substr($line_target, strpos($line_target, '=') + 1, strlen($line_target)))), 0, -1);
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
					foreach ($index_target as $index) { 
						render_target_table_line($index, $PMF_LANG_source, $PMF_LANG_target, $LANG_CONF_source, $LANG_CONF_target);
					}
					echo '
						<tr>
							<td colspan="3">&nbsp;</td>
						</tr>
						<tr>
							<th colspan="3">Strings not found on target file</td>
						</tr>
					';
					foreach ($index_source as $index) {
						render_source_table_line($index, $PMF_LANG_source, $PMF_LANG_target, $LANG_CONF_source, $LANG_CONF_target);
					}
				?>
			</tbody>
		</table>
		<br /><br />
		
		<button id="generate">Generate target file content</button>
		<textarea id="output" placeholder="The content of your translated file will appear here"></textarea>
		
		<script>
			$(document).ready(function() {
				$("#generate").on("click", function() {
					var textarea = $("#output");
					var output = '';
					$(".target").each(function(a, target) {
						var value = $.trim($(target).val().replaceAll("\n", ""));
						if (value.length === 0) {
							return;
						}
						var key = $(target).closest("tr").find(".key").html();
						var type = key.indexOf("$PMF_LANG") === 0 ? "PMF_LANG" : "LANG_CONF";
						if (type === "PMF_LANG") {
							value = "'" + $.trim(value.replaceAll("'", "\\'")) + "'";
						}
						output += key + " = " + value + ";\n";
					});
					textarea.val(output);
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

<?php }