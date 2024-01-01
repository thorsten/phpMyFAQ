<?php

/**
 * This file is intended to make it easier to translate the language files.
 * It makes visible the strings already translated, the ones that exist on
 * a file but not on the original, and the ones that are not translated yet.
 *
 * How to use with the internal webserver of PHP:
 *
 * $ cd ./scripts
 * $ php -S localhost:8000
 *
 * Open the following URL in your browser:
 *
 * http://localhost:8000/langEditor.php
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Everton Leite <etcholeite@gmail.com>
 * @copyright 2021-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2021-08-11
 */

// checking for syntax errors to prevent crashes when exporting the file to the application
if ($_POST && key_exists('syntaxCheck', $_POST) && $_POST['syntaxCheck'] == 1 && key_exists('output', $_POST)) {
    $lines = explode("\n", $_POST['output']);
    foreach ($lines as $line) {
        if (strlen(trim($line)) > 0) {
            echo '<<<<' . $line . '>>>>';
            eval($line);
        }
    }
    echo '{syntaxCheckPassed}';
    exit;
}
?>
<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <title>phpMyFAQ Language Editor</title>
</head>
<body>

  <h1>phpMyFAQ Language Editor</h1>

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
      margin: 10px 0;
    }

    .copy-from-source {
      text-decoration: none;
      background-color: #DDD;
      color: #000;
      padding: 0 3px;
      border: 1px solid #666;
      font-size: 16px;
    }

    #syntax-check-message {
      margin-top: 10px;
    }

    pre {
      white-space: pre-wrap;
    }

    .string-warning {
      background: #ffa500;
    }

    .string-warning > div:before {
      content: '!';
      background: #ba7800;
      color: #FFF;
      font-weight: bold;
      padding: 0 4px;
      margin-right: 2px;
    }
  </style>

<?php

$pmfLangSource = [];
$pmfLangTarget = [];
$langConfSource = [];
$langConfTarget = [];
$indexSource = [];
$indexTarget = [];

/**
 * @param array|string $index
 * @param array $pmfLangSource
 * @param array $pmfLangTarget
 * @param array $langConfSource
 * @param array $langConfTarget
 */
function renderTargetTableLine(
    $index,
    array &$pmfLangSource,
    array &$pmfLangTarget,
    array &$langConfSource,
    array &$langConfTarget
): void {
    eval(
        '@$sourceEntry = ' . str_replace(
            '$PMF_LANG',
            '$pmfLangSource',
            str_replace('$LANG_CONF', '$langConfSource', $index)
        ) . ';'
    );
    eval(
        '@$targetEntry = ' . str_replace(
            '$PMF_LANG',
            '$pmfLangTarget',
            str_replace('$LANG_CONF', '$langConfTarget', $index)
        ) . ';'
    );
    $keyFounded = $sourceEntry !== null;
    $class = $keyFounded ? 'green' : 'yellow';
    echo '
        <tr class="' . $class . '">
            <td class="nowrap key">' . $index . '</td>
            <td style="width: 50%;" class="source">';
    if ($keyFounded) {
        echo '<pre>' . htmlspecialchars($sourceEntry) . '</pre>';
    } else {
        echo '<em><strong>String not found in source file</strong></em>';
    }
    echo '</td>
            <td class="nowrap">' . ($keyFounded ? '<a class="copy-from-source" title="Copy from source" href="#" onclick="copyFromSource(this); return false;">&raquo;</a>' : '') . '</td>
            <td style="width: 50%">
                <div class="string-warning"></div>
                <textarea style="width: 100%;" class="target ' . $class . '">' . htmlspecialchars($targetEntry) . '</textarea>
            </td>
        </tr>';
}

/**
 * @param array|string $index
 * @param array $pmfLangSource
 * @param array $pmfLangTarget
 * @param array $langConfSource
 * @param array $langConfTarget
 */
function renderSourceTableLine(
    $index,
    array &$pmfLangSource,
    array &$pmfLangTarget,
    array &$langConfSource,
    array &$langConfTarget
): void {
    eval(
        '@$sourceEntry = ' . str_replace(
            '$PMF_LANG',
            '$pmfLangSource',
            str_replace('$LANG_CONF', '$langConfSource', $index)
        ) . ';'
    );
    eval(
        '@$targetEntry = ' . str_replace(
            '$PMF_LANG',
            '$pmfLangTarget',
            str_replace('$LANG_CONF', '$langConfTarget', $index)
        ) . ';'
    );
    $keyFounded = $targetEntry !== null;
    if ($keyFounded) {
        return;
    }
    $class = 'red';
    echo '
        <tr class="' . $class . '">
            <td class="nowrap key">' . $index . '</td>
            <td style="width: 50%;" class="source"><pre>' . htmlspecialchars($sourceEntry) . '</pre></td>
            <td class="nowrap"><a class="copy-from-source" title="Copy from source" href="#" onclick="copyFromSource(this); return false;">&raquo;</td>
            <td style="width: 50%">
                <div class="string-warning"></div>
                <textarea style="width: 100%;" class="target ' . $class . '"></textarea>
            </td>
        </tr>';
}

if ($_POST) {
    $source = $_FILES['source'];
    $target = $_FILES['target'];

    if ($source === null || $target === null || $source['name'] === '' || $target['name'] === '') {
        echo 'You must select both files first';
    } else {
        $fileSource = fopen($source['tmp_name'], 'r');
        $fileTarget = fopen($target['tmp_name'], 'r');
        while (($lineSource = fgets($fileSource)) !== false) {
            if (strpos($lineSource, '$PMF_LANG') === 0) {
                eval(str_replace('$PMF_LANG', '$pmfLangSource', $lineSource));
                $indexSource[] = trim(substr($lineSource, 0, strpos($lineSource, '=')));
            }
            if (strpos($lineSource, '$LANG_CONF') === 0) {
                //checking for syntax errors on the original file, just to be sure
                echo '<div id="syntax_error">Syntax error on <strong>source</strong> file: <pre>' . $lineSource . '</pre></div>';
                eval(str_replace('$LANG_CONF', '$langConfSource', $lineSource));
                echo '<script> document.querySelector("#syntax_error").remove(); </script>';
                $indexSource[] = trim(substr($lineSource, 0, strpos($lineSource, '=')));
                $lineSource = str_replace('$LANG_CONF', '$langConfSource', $lineSource);
                $idx = trim(substr($lineSource, 0, strpos($lineSource, '=')));
                $val = substr(
                    str_replace(
                        "'",
                        "\'",
                        trim(substr($lineSource, strpos($lineSource, '=') + 1, strlen($lineSource)))
                    ),
                    0,
                    -1
                );
                eval($idx . " = '$val';");
            }
        }
        while (($lineTarget = fgets($fileTarget)) !== false) {
            if (strpos($lineTarget, '$PMF_LANG') === 0) {
                eval(str_replace('$PMF_LANG', '$pmfLangTarget', $lineTarget));
                $indexTarget[] = trim(substr($lineTarget, 0, strpos($lineTarget, '=')));
            }
            if (strpos($lineTarget, '$LANG_CONF') === 0) {
                echo '<div id="syntax_error">Syntax error on <strong>target</strong> file: <pre>' . $lineTarget . '</pre></div>';
                eval(str_replace('$LANG_CONF', '$langConfTarget', $lineTarget));
                echo '<script> document.querySelector("#syntax_error").remove(); </script>';
                $indexTarget[] = trim(substr($lineTarget, 0, strpos($lineTarget, '=')));
                $lineTarget = str_replace('$LANG_CONF', '$langConfTarget', $lineTarget);
                $idx = trim(substr($lineTarget, 0, strpos($lineTarget, '=')));
                $val = substr(
                    str_replace(
                        "'",
                        "\'",
                        trim(substr($lineTarget, strpos($lineTarget, '=') + 1, strlen($lineTarget)))
                    ),
                    0,
                    -1
                );
                eval($idx . " = '$val';");
            }
        }
        ?>
      <div class="warning">
        * All line breaks gonna be removed. Textareas are just to make it easy to see the text.
        &lt;br&gt; tags will be fine. \n will be gone.<br>
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
      <br><br>

      <button id="generate">Generate target file content</button>
      <div id="syntax-check-message"></div>
      <label for="output"></label>
      <textarea
        readonly="readonly"
        id="output"
        placeholder="The content of your translated file will appear here"
      ></textarea>

      <script>
        function copyFromSource (btn) {
          const target = btn.parentElement.nextElementSibling.querySelector('.target');
          const textArea = document.createElement('textarea');
          textArea.innerHTML = btn.parentElement.previousElementSibling.querySelector('pre').innerHTML;
          target.value = textArea.value;
          checkStringWarnings(target);
        }

        function checkStringWarnings (target) {
          if (
            target.classList.contains('yellow') ||
            target.parentElement.parentElement.querySelector('.key').innerHTML.startsWith('$LANG_CONF')
          ) {
            return;
          }
          const targetText = target.value;
          const sourceText = target
            .parentElement
            .previousElementSibling
            .previousElementSibling
            .querySelector('pre')
            .innerHTML;
          const messageEl = target.previousElementSibling;
          messageEl.innerHTML = '';
          if (sourceText.startsWith(' ') && !targetText.startsWith(' ')) {
            messageEl.innerHTML += '<div>Missing space at the beginning</div>';
          }
          if (!sourceText.startsWith(' ') && targetText.startsWith(' ')) {
            messageEl.innerHTML += '<div>Extra space at the beginning</div>';
          }
          if (sourceText.endsWith(' ') && !targetText.endsWith(' ')) {
            messageEl.innerHTML += '<div>Missing space at the end</div>';
          }
          if (!sourceText.endsWith(' ') && targetText.endsWith(' ')) {
            messageEl.innerHTML += '<div>Extra space at the end</div>';
          }
          if (targetText.indexOf('\n') !== -1) {
            messageEl.innerHTML += '<div>Line breaks will be removed</div>';
          }
        }

        const ready = (callback) => {
          if (document.readyState !== 'loading') {
            callback();
          } else {
            document.addEventListener('DOMContentLoaded', callback);
          }
        };

        ready(() => {
          document.querySelectorAll('.target').forEach(target => {
            ['change', 'blur', 'keyup'].forEach(function(event) {
              target.addEventListener(event, () => {
                checkStringWarnings(target);
              });
            });
            checkStringWarnings(target);
          });
          document.querySelector('#generate').addEventListener('click', () => {
            const textarea = document.querySelector('#output');
            const syntaxCheckMessage = document.querySelector('#syntax-check-message');
            let output = '';
            syntaxCheckMessage.innerHTML = '';
            document.querySelectorAll('.target').forEach(target => {
              let value = target.value.replaceAll('\n', '');
              if (value.length === 0) {
                return;
              }
              const key = target.parentElement.parentElement.querySelector('.key').innerHTML;
              const type = key.indexOf('$PMF_LANG') === 0 ? 'PMF_LANG' : 'LANG_CONF';
              if (type === 'PMF_LANG') {
                value = '\'' + value.replaceAll('\'', '\\\'') + '\'';
              } else {
                value = value.trim();
              }
              output += key + ' = ' + value + ';\n';
            });
            textarea.value = output;

            //sending an ajax request with the output to check for possible syntax errors and typos
            fetch('langEditor.php', {
              method: 'POST',
              body: 'syntaxCheck=1&output=' + document.querySelector('#output').value,
              headers: {
                'Content-type': 'application/x-www-form-urlencoded',
              },
            }).then(function(response) {
              response.text().then(function(text) {
                if (text.substr(text.length - 19, 19) === '{syntaxCheckPassed}') {
                  syntaxCheckMessage.style.color = 'green';
                  syntaxCheckMessage.innerHTML = 'No syntax errors found! You are good to go.';
                } else {
                  const lastLineErrorBegin = text.lastIndexOf('<<<<');
                  const lastLineErrorEnd = text.lastIndexOf('>>>>');
                  const line = text.substr(lastLineErrorBegin + 4, lastLineErrorEnd - lastLineErrorBegin - 4);
                  syntaxCheckMessage.style.color = 'red';
                  syntaxCheckMessage.innerHTML = 'Syntax error on the following line: <pre>' + line + '</pre>';
                }
              });

            });
          });
        });
      </script>
        <?php
    }
} else {
    ?>

  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="flag" value="1" >
    <label>Source lang file (EN):
      <input type="file" name="source">
    </label>
    <br>
    <label>Target lang file:
      <input type="file" name="target">
    </label>
    <br><br>
    <input type="submit" value="compare">
  </form>
  
</body>
</html>

    <?php
}
