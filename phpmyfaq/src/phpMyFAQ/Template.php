<?php

/**
 * The Template class provides methods and functions for the template parser.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Mergler <jan.mergler@gmx.de>
 * @copyright 2002-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-08-22
 */

namespace phpMyFAQ;

use phpMyFAQ\Template\TemplateNotFoundException;
use phpMyFAQ\Template\TemplateHelper;

/**
 * Class Template
 *
 * @package phpMyFAQ
 */
class Template
{
    /** @var string Name of active template set. */
    private static string $tplSetName;

    /** @var array<string, string> The array with the templates */
    public array $templates = [];

    /** @var array<string, string|null> The rendered HTML output */
    private array $outputs = [];

    /** @var array<string, array<string>> The array with the template blocks array. */
    private array $blocks = [];

    /** @var array<string> Array containing the touched blocks. */
    private array $blocksTouched = [];

    /** @var array<string> Array containing the errors */
    private array $errors = [];

    /**
     * Combine all template files into the main templates array
     *
     * @param array<string, string> $myTemplate Templates
     * @param string                $tplSetName Active template name
     */
    public function __construct(
        array $myTemplate,
        private readonly TemplateHelper $tplHelper,
        string $tplSetName = 'default'
    ) {
        self::$tplSetName = $tplSetName;

        foreach ($myTemplate as $templateName => $filename) {
            try {
                $this->templates[$templateName] = $this->readTemplateFile(
                    'assets/themes/' . $tplSetName . '/templates/' . $filename,
                    $templateName
                );
            } catch (TemplateNotFoundException $e) {
                $this->errors[] = $e->getMessage();
            }
        }
    }

    /**
     * This function reads a template file.
     *
     * @param string $filename Filename
     * @param string $tplName Name of the template
     * @throws TemplateNotFoundException
     */
    protected function readTemplateFile(string $filename, string $tplName): string
    {
        if (file_exists($filename) && is_file($filename)) {
            $tplContent = file_get_contents($filename);
            $this->blocks[$tplName] = $this->readBlocks($tplContent);

            return $tplContent;
        }

        throw new TemplateNotFoundException('Cannot open the file ' . $filename);
    }

    /**
     * This function reads the block.
     *
     * @param string|null $block Block to read
     * @return array<string, string>
     */
    private function readBlocks(?string $block = null): array
    {
        if ($block === null) {
            return [];
        }

        $tmpBlocks = $tplBlocks = [];

        // read all blocks into $tmpBlocks
        Strings::preg_match_all('/\[([[:alpha:]]+)\]\s*[\W\w\s\{\{\}\}\<\>\=\"\/]*?\s*\[\/\1\]/', $block, $tmpBlocks);

        $unblocked = $block;
        if (isset($tmpBlocks)) {
            $blockCount = is_countable($tmpBlocks[0]) ? count($tmpBlocks[0]) : 0;
            for ($i = 0; $i < $blockCount; ++$i) {
                $name = '';

                // find block name
                Strings::preg_match('/\[.+\]/U', $tmpBlocks[0][$i], $name);
                $name = Strings::preg_replace('/[\[\[\/\]]/', '', $name);

                // remove block tags from block
                $res = str_replace('[' . $name[0] . ']', '', (string) $tmpBlocks[0][$i]);
                $res = str_replace('[/' . $name[0] . ']', '', $res);
                $tplBlocks[$name[0]] = $res;

                // unblocked content
                $unblocked = str_replace($tplBlocks[$name[0]], '', $unblocked);
                $unblocked = str_replace('[' . $name[0] . ']', '', $unblocked);
                $unblocked = str_replace('[/' . $name[0] . ']', '', $unblocked);
            }

            $hits = [];
            Strings::preg_match_all('/\{\{.+?\}\}/', $unblocked, $hits);
            $tplBlocks['unblocked'] = $hits[0];
        } else {
            // no blocks defined
            $tplBlocks = $block;
        }

        return $tplBlocks;
    }

    /**
     * Get name of the actual template set.
     */
    public static function getTplSetName(): string
    {
        return self::$tplSetName;
    }

    /**
     * Set the template set name to use.
     */
    public static function setTplSetName(string $tplSetName): void
    {
        self::$tplSetName = $tplSetName;
    }

    /**
     * This function merges two templates.
     *
     * @param string $from Name of the template to include
     * @param string $into Name of the new template
     */
    public function merge(string $from, string $into): void
    {
        $this->outputs[$into] = str_replace('{{ ' . $from . ' }}', $this->outputs[$from], $this->outputs[$into]);
        $this->outputs[$from] = null;
    }

    /**
     * Parses the template.
     *
     * @param string $templateName Name of the template
     * @param array<string, string|null> $templateContent Content of the template
     */
    public function parse(string $templateName, array $templateContent): void
    {
        $filters = [];
        $tmp = $this->templates[$templateName];
        $rawBlocks = $this->readBlocks($tmp);
        $filters[$templateName] = $this->readFilters($tmp);

        // process blocked content
        if (isset($this->blocks[$templateName])) {
            foreach ($rawBlocks as $key => $rawBlock) {
                if (in_array($key, $this->blocksTouched) && $key !== 'unblocked') {
                    $tmp = str_replace($rawBlock, $this->blocks[$templateName][$key], $tmp);
                    $tmp = str_replace('[' . $key . ']', '', $tmp);
                    $tmp = str_replace('[/' . $key . ']', '', $tmp);
                } elseif ($key !== 'unblocked') {
                    $tmp = str_replace($rawBlock, '', $tmp);
                    $tmp = str_replace('[' . $key . ']', '', $tmp);
                    $tmp = str_replace('[/' . $key . ']', '', $tmp);
                }
            }
        }

        // process unblocked content
        if (isset($this->blocks[$templateName]['unblocked'])) {
            $templateContent = $this->checkContent($templateContent);
            // @phpstan-ignore-next-line
            foreach ($this->blocks[$templateName]['unblocked'] as $tplVar) {
                $varName = trim(Strings::preg_replace('/[\{\{\}\}]/', '', $tplVar));
                if (isset($templateContent[$varName])) {
                    $tmp = str_replace($tplVar, $templateContent[$varName], $tmp);
                }
            }
        }

        // process filters
        if (isset($filters[$templateName])) {
            if (count($filters[$templateName])) {
                foreach ($filters[$templateName] as $filter) {
                    $filterMethod = 'render' . ucfirst(key($filter)) . 'Filter';
                    $filteredVar = $this->tplHelper->$filterMethod(current($filter));
                    $tmp = str_replace('{{ ' . current($filter) . ' | ' . key($filter) . ' }}', $filteredVar, $tmp);
                }
            }
        }

        // add magic variables for each template
        $tmp = str_replace('{{ tplSetName }}', self::$tplSetName, $tmp);

        if (isset($this->outputs[$templateName])) {
            $this->outputs[$templateName] .= $tmp;
        } else {
            $this->outputs[$templateName] = $tmp;
        }
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readFilters(?string $template = null): array
    {
        if (is_null($template)) {
            return [];
        }

        $tmpFilter = $tplFilter = [];
        Strings::preg_match_all('/\{\{.+?\}\}/', $template, $tmpFilter);

        if (isset($tmpFilter)) {
            $filterCount = is_countable($tmpFilter[0]) ? count($tmpFilter[0]) : 0;
            for ($i = 0; $i < $filterCount; ++$i) {
                if (str_contains((string) $tmpFilter[0][$i], ' | meta ')) {
                    $rawFilter = str_replace(['{{', '}}'], '', (string) $tmpFilter[0][$i]);
                    [$identifier, $filter] = explode('|', $rawFilter);
                    $tplFilter[] = [trim($filter) => trim($identifier)];
                }
            }
        }

        return $tplFilter;
    }

    /**
     * This function renders the whole parsed templates and outputs it.
     */
    public function render(): string
    {
        $output = '';
        foreach ($this->outputs as $val) {
            $output .= str_replace("\n\n", "\n", $val ?? '');
        }

        return $output;
    }

    /**
     * This function adds two parsed templates.
     *
     * @param string $from Name of the template to add
     * @param string $into Name of the new template
     */
    public function add(string $from, string $into): void
    {
        $this->outputs[$into] .= $this->outputs[$from];
        $this->outputs[$from] = null;
    }

    /**
     * This function processes the block.
     *
     * @param string $templateName Name of the template
     * @param string $blockName Block name
     * @param array<string, mixed>  $blockContent Content of the block
     */
    public function parseBlock(string $templateName, string $blockName, array $blockContent): void
    {
        if (isset($this->blocks[$templateName][$blockName])) {
            $block = $this->blocks[$templateName][$blockName];

            // security check
            $blockContent = $this->checkContent($blockContent);
            foreach ($blockContent as $var => $val) {
                // if array given, multiply block
                if (is_array($val)) {
                    $block = $this->multiplyBlock($this->blocks[$templateName][$blockName], $blockContent);
                    break;
                } else {
                    $block = str_replace('{{ ' . $var . ' }}', $val, $block);
                }
            }

            $this->blocksTouched[] = $blockName;
            $block = str_replace('&acute;', '`', $block);
            $this->blocks[$templateName][$blockName] = $block;
        }
    }

    /**
     * This function checks the content.
     *
     * @param array<string, string|array<string>> $content Content to check
     */
    private function checkContent(array $content): array
    {
        // Security measure: avoid the injection of php/shell-code
        $search = ['#<\?php#i', '#\{$\{#', '#<\?#', '#<\%#', '#`#', '#<script[^>]+php#mi'];
        $phpPattern1 = '&lt;?php';
        $phpPattern2 = '&lt;?';
        $replace = [$phpPattern1, '', $phpPattern2, '', ''];

        foreach ($content as $var => $val) {
            if (is_array($val)) {
                foreach ($val as $key => $value) {
                    $content[$var][$key] = str_replace('`', '&acute;', $value);
                    $content[$var][$key] = Strings::preg_replace($search, $replace, $value);
                }
            } else {
                $content[$var] = str_replace('`', '&acute;', $val ?? '');
                $content[$var] = Strings::preg_replace($search, $replace, $val ?? '');
            }
        }

        return $content;
    }

    /**
     * This function multiplies blocks.
     *
     * @param string $blockName Block name
     * @param array<int, array<string>>  $blockContent Content of block
     */
    private function multiplyBlock(string $blockName, array $blockContent): string
    {
        $replace = $tmpBlock = [];
        $multiplyTimes = 0;

        // create the replacement array
        foreach ($blockContent as $var => $val) {
            if (is_array($val) && !$multiplyTimes) {
                // the first array in $blockContent defines $multiplyTimes
                $multiplyTimes = count($val);
                $replace[$var] = $val;
            } elseif ((is_array($val) && $multiplyTimes)) {
                // check if all further arrays in $blockContent have the same length
                if ($multiplyTimes == count($val)) {
                    $replace[$var] = $val;
                } else {
                    die('Wrong parameter length!');
                }
            } else {
                // multiply strings to $multiplyTimes
                for ($i = 0; $i < $multiplyTimes; ++$i) {
                    $replace[$var][] = $val;
                }
            }
        }

        // do the replacement
        for ($i = 0; $i < $multiplyTimes; ++$i) {
            $tmpBlock[$i] = $blockName;
            foreach ($replace as $var => $val) {
                $tmpBlock[$i] = str_replace('{{ ' . $var . ' }}', $val[$i], $tmpBlock[$i]);
            }
        }

        return implode('', $tmpBlock);
    }
}
