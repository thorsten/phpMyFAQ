<?php

/**
 * Main PDF class for phpMyFAQ which "just" extends the TCPDF library.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Peter Beauvain <pbeauvain@web.de>
 * @author    Krzysztof Kruszynski <thywolf@wolf.homelinux.net>
 * @copyright 2004-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2004-11-21
 */

declare(strict_types=1);

namespace phpMyFAQ\Export\Pdf;

use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Date;
use phpMyFAQ\Export\Pdf\Engine\PdfEngineInterface;
use phpMyFAQ\Export\Pdf\Engine\TcpdfEngine;
use phpMyFAQ\Link\Util\TitleSlugifier;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;

/**
 * Class Wrapper
 *
 * @package phpMyFAQ\Export\Pdf
 */
/* @mago-ignore lint:too-many-methods */
class Wrapper
{
    /**
     * Default left page margin in mm, mirroring the engine's left-margin default.
     */
    private const float MARGIN_LEFT = 15;

    /**
     * Default right page margin in mm, mirroring the engine's right-margin default.
     */
    private const float MARGIN_RIGHT = 15;

    /**
     * With or without bookmarks.
     */
    public bool $enableBookmarks = false;

    /**
     * Full export from admin backend?
     */
    public bool $isFullExport = false;

    /**
     * Categories.
     */
    public array $categories = [];

    /**
     * The current category.
     */
    public int $category;

    /**
     * The current faq.
     */
    public array $faq = [];

    /**
     * Configuration. Optional until injected via setConfig(); rendering requires it.
     */
    protected ?Configuration $config = null;

    /**
     * Returns the configuration or fails loudly when setConfig() was not called
     * before rendering started.
     */
    private function config(): Configuration
    {
        return (
            $this->config ?? throw new \LogicException('Wrapper::setConfig() must be called before rendering a PDF.')
        );
    }

    /**
     * Question.
     */
    private string $question = '';

    /**
     * Font files.
     */
    private array $fontFiles = [
        'zh' => 'arialunicid0',
        'zh_tw' => 'arialunicid0',
        'ja' => 'arialunicid0',
        'ko' => 'arialunicid0',
        'cs' => 'dejavusans',
        'sk' => 'dejavusans',
        'el' => 'arialunicid0',
        'he' => 'arialunicid0',
        'tr' => 'dejavusans',
        'default' => 'dejavusans',
    ];

    /**
     * Current font.
     */
    private string $currentFont = 'dejavusans';

    private string $customHeader = '';

    private string $customFooter = '';

    private readonly PdfEngineInterface $engine;

    /**
     * Constructor.
     */
    public function __construct(?PdfEngineInterface $engine = null)
    {
        $this->engine = $engine ?? new TcpdfEngine();

        // Check on RTL
        if ('rtl' === Translation::get(key: 'direction')) {
            $this->engine->setRtl(true);
        }

        // Set font
        $metaLanguage = (string) (Translation::get(key: 'metaLanguage') ?? '');
        if ($metaLanguage !== '' && array_key_exists($metaLanguage, $this->fontFiles)) {
            $this->currentFont = (string) $this->fontFiles[$metaLanguage];
        }

        // Register render-time callbacks so the engine's Header/Footer/Image hooks
        // call back into this renderer's domain logic.
        $this->engine->onHeader($this->renderHeader(...));
        $this->engine->onFooter($this->renderFooter(...));
        $this->engine->onImageResolve($this->resolveImage(...));
    }

    public function Open(): void
    {
        $this->engine->open();
    }

    public function Output(string $name, string $dest): string
    {
        return $this->engine->output($name, $dest);
    }

    public function AddPage(): void
    {
        $this->engine->addPage();
    }

    public function setPrintHeader(bool $val = true): void
    {
        $this->engine->setPrintHeader($val);
    }

    public function SetDisplayMode(mixed $zoom): void
    {
        $this->engine->setDisplayMode($zoom);
    }

    public function SetMargins(float $left, float $top, float $right = -1): void
    {
        $this->engine->setMargins($left, $top, $right);
    }

    public function SetHeaderMargin(float $margin): void
    {
        $this->engine->setHeaderMargin($margin);
    }

    public function SetFooterMargin(float $margin): void
    {
        $this->engine->setFooterMargin($margin);
    }

    public function SetCreator(string $creator): void
    {
        $this->engine->setCreator($creator);
    }

    public function SetTitle(string $title): void
    {
        $this->engine->setTitle($title);
    }

    public function SetAuthor(string $author): void
    {
        $this->engine->setAuthor($author);
    }

    public function SetFont(string $family, string $style = '', float $size = 0): void
    {
        $this->engine->setFont($family, $style, $size);
    }

    public function Ln(?float $h = null): void
    {
        $this->engine->ln($h);
    }

    public function Write(float $h, string $txt): void
    {
        $this->engine->write($h, $txt);
    }

    public function Bookmark(string $txt, int $level = 0, float $y = -1): void
    {
        $this->engine->bookmark($txt, $level, $y);
    }

    /**
     * Setter for the category name.
     *
     * @param int $category Entity name
     */
    public function setCategory(int $category): void
    {
        $this->category = $category;
    }

    /**
     * Setter for the question.
     *
     * @param string $question Question
     */
    public function setQuestion(string $question = ''): void
    {
        $this->question = $question;
    }

    /**
     * Setter for a category array.
     *
     * @param array $categories Categories
     */
    public function setCategories(array $categories): void
    {
        $this->categories = $categories;
    }

    public function setConfig(Configuration $configuration): void
    {
        $this->config = $configuration;
    }

    /**
     * The header of the PDF file.
     */
    public function renderHeader(): void
    {
        // Set a custom header and footer
        $this->setCustomHeader();

        $title = array_key_exists($this->category, $this->categories) ? $this->categories[$this->category]['name'] : '';

        $this->engine->setTextColor(0, 0, 0);
        $this->engine->setFont($this->currentFont, 'B', 14);

        if (0 < Strings::strlen($this->customHeader)) {
            $this->engine->writeHtmlCell(w: 0, h: 0, x: 0, y: 0, html: $this->customHeader);
            $this->engine->ln();
            $this->engine->writeHtmlCell(
                w: 0,
                h: 0,
                x: 0,
                y: 0,
                html: html_entity_decode((string) $title, ENT_QUOTES, encoding: 'utf-8'),
                border: 0,
                ln: 0,
                fill: false,
                reseth: true,
                align: 'C',
            );
            return;
        }

        $this->engine->multiCell(
            w: 0,
            h: 10,
            txt: html_entity_decode((string) $title, ENT_QUOTES, encoding: 'utf-8'),
            border: 0,
            align: 'C',
        );
        $this->engine->setMargins(self::MARGIN_LEFT, $this->engine->getLastH() + 5, self::MARGIN_RIGHT);
    }

    /**
     * Sets custom header.
     */
    public function setCustomHeader(): void
    {
        $this->customHeader = html_entity_decode(
            (string) $this->config()->get(item: 'main.customPdfHeader'),
            ENT_QUOTES,
            encoding: 'utf-8',
        );
    }

    /**
     * The footer of the PDF file.
     * @throws Exception
     */
    public function renderFooter(): void
    {
        // Set a custom footer
        $this->setCustomFooter();

        $date = new Date($this->config());

        $footer = sprintf(
            $this->config()->get(item: 'spam.mailAddressInExport') ? '© %d %s <%s> | %s' : '© %d %s %s| %s',
            date(format: 'Y'),
            $this->config()->get(item: 'main.metaPublisher'),
            $this->config()->get(item: 'spam.mailAddressInExport') ? $this->config()->getAdminEmail() : '',
            $date->format(date(format: 'Y-m-d H:i')),
        );

        if (0 < Strings::strlen($this->customFooter)) {
            $this->engine->writeHtmlCell(w: 0, h: 0, x: null, y: null, html: $this->customFooter);
        }

        $currentTextColor = $this->engine->getTextColor();
        $this->engine->setTextColor(0, 0, 0);
        $this->engine->setY(-25);
        $this->engine->setFont($this->currentFont, '', 10);
        $this->engine->cell(
            w: 0,
            h: 10,
            txt: Translation::get(key: 'ad_gen_page') . ' ' . $this->engine->getAliasNumPage() . ' / '
                . $this->engine->getAliasNbPages(),
            border: 0,
            ln: 0,
            align: 'C',
        );
        $this->engine->setY(-20);
        $this->engine->setFont($this->currentFont, 'B', 8);
        $this->engine->cell(w: 0, h: 10, txt: $footer, border: 0, ln: 1, align: 'C');
        if (!$this->enableBookmarks) {
            $this->engine->setY(-15);
            $this->engine->setFont($this->currentFont, '', 8);
            $baseUrl = $this->config()->getDefaultUrl() . 'content';
            if ($this->faq !== []) {
                if (array_key_exists($this->category, $this->categories)) {
                    $baseUrl .= '/' . $this->categories[$this->category]['id'];
                }

                $baseUrl .= '/' . $this->faq['id'];
                $baseUrl .= '/' . $this->faq['lang'];
                $baseUrl .= '/' . TitleSlugifier::slug($this->question) . '.html';
            }

            $this->engine->cell(
                w: 0,
                h: 10,
                txt: 'URL: ' . $baseUrl,
                border: 0,
                ln: 1,
                align: 'C',
                fill: false,
                link: $baseUrl,
            );
        }

        $this->engine->setTextColorRaw($currentTextColor);
    }

    /**
     * Sets custom footer.
     */
    public function setCustomFooter(): void
    {
        $this->customFooter = $this->config()->get(item: 'main.customPdfFooter') ?? '';
    }

    /**
     * Adds a table of content for exports of the complete FAQ.
     */
    public function addFaqToc(): void
    {
        $this->engine->addTocPage();

        // Title
        $this->engine->setFont($this->currentFont, 'B', 24);
        $this->engine->multiCell(w: 0, h: 0, txt: $this->config()->getTitle(), border: 0, align: 'C');
        $this->engine->ln();

        // TOC
        $this->engine->setFont($this->currentFont, 'B', 16);
        $this->engine->multiCell(w: 0, h: 0, txt: Translation::get(key: 'msgTableOfContent'), border: 0, align: 'C');
        $this->engine->ln();
        $this->engine->setFont($this->currentFont, '', 12);

        // Render TOC
        $this->engine->addToc(
            page: 1,
            numbersfont: $this->currentFont,
            filler: '.',
            tocName: Translation::get(key: 'msgTableOfContent'),
            style: 'B',
            color: [128, 0, 0],
        );
        $this->engine->endTocPage();
    }

    /**
     * Returns the current font for PDF export.
     */
    public function getCurrentFont(): string
    {
        return $this->currentFont;
    }

    /**
     * Sets the FAQ array.
     */
    public function setFaq(array $faq): void
    {
        $this->faq = $faq;
    }

    /**
     * Resolves an image source for the engine to draw, converting local and data-URI
     * images to embedded base64 data where possible. This is necessary as the
     * underlying PDF library does not support external images from self-signed
     * certificates.
     *
     * Returns [resolvedFile, resolvedType] to draw, or null to skip the image.
     *
     * @param string $file Name of the file containing the image or a '@' character followed by the image data
     *                     string. To link an image without embedding it on the document, set an asterisk
     *                     character before the URL (i.e.: '*http://www.example.com/image.jpg').
     * @param string $type Image format inferred from the file extension if not specified.
     * @return array{0: string, 1: string}|null
     */
    private function resolveImage(string $file, string $type): ?array
    {
        // Pass through raw image data ('@' prefix) and non-embedded links ('*' prefix)
        // without filesystem lookup.
        if ($file !== '' && ($file[0] === '@' || $file[0] === '*')) {
            return [$file, $type];
        }

        if (str_starts_with($file, 'data:')) {
            if (preg_match('#^data:[^;]+;base64,(.+)$#', $file, $matches)) {
                $decoded = base64_decode($matches[1], strict: true);
                if ($decoded !== false && $this->checkBase64Image($decoded)) {
                    return ['@' . $decoded, $type];
                }
            }

            return null;
        }

        $path = parse_url($file, PHP_URL_PATH);
        if ($path === false || $path === null || $path === '') {
            return null;
        }

        // URL-decode the file path to handle filenames with spaces and other special characters
        $path = urldecode($path);

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $resolvedPath = $this->concatenatePaths(PMF_ROOT_DIR, $path);
        if ($resolvedPath === '' || !$this->isWithinRoot($resolvedPath)) {
            return null;
        }

        if (!is_file($resolvedPath) || !is_readable($resolvedPath)) {
            return null;
        }

        $data = file_get_contents($resolvedPath);
        if ($data === false) {
            return null;
        }

        if ($this->checkBase64Image($data)) {
            return ['@' . $data, $type];
        }

        return [$path, $type];
    }

    /* @mago-expect lint:no-error-control-operator - probing whether bytes decode as an image; failure is the negative answer */
    private function checkBase64Image(string $base64): bool
    {
        $img = @imagecreatefromstring($base64);
        if (!$img) {
            return false;
        }

        $info = getimagesizefromstring($base64);

        return $info && $info[0] > 0 && $info[1] > 0 && array_key_exists('mime', $info);
    }

    public function concatenatePaths(string $path, string $file): string
    {
        $trimmedPath = rtrim(str_replace(search: '\\', replace: '/', subject: $path), characters: '/');
        $trimmedFile = ltrim(str_replace(search: '\\', replace: '/', subject: $file), characters: '/');

        // Local images served by phpMyFAQ always live under the "content" directory.
        // If the path does not reference it, refuse to resolve it rather than letting
        // an attacker-controlled path escape the web root.
        $pos = strpos($trimmedFile, needle: 'content/');
        if ($pos === false) {
            return '';
        }

        $relativePath = substr($trimmedFile, $pos);

        return $trimmedPath . DIRECTORY_SEPARATOR . $relativePath;
    }

    /**
     * Ensures a resolved filesystem path stays inside the phpMyFAQ web root,
     * preventing path traversal (e.g. "../../../etc/passwd") in image sources.
     */
    private function isWithinRoot(string $resolvedPath): bool
    {
        $realPath = realpath($resolvedPath);
        $realRoot = realpath(PMF_ROOT_DIR);

        if ($realPath === false || $realRoot === false) {
            return false;
        }

        return $realPath === $realRoot || str_starts_with($realPath, $realRoot . DIRECTORY_SEPARATOR);
    }

    /**
     * Converts external images from allowed hosts to base64 data URIs in HTML content.
     * This enables TCPDF to display external images that would otherwise fail due to SSL/certificate issues.
     *
     * @param string $html The HTML content to process
     * @return string The processed HTML content with external images converted to base64
     */
    public function convertExternalImagesToBase64(string $html): string
    {
        if (!$this->config instanceof Configuration) {
            return $html;
        }

        $allowedHosts = $this->config->getAllowedMediaHosts();
        if ($allowedHosts === [] || count($allowedHosts) === 1 && trim($allowedHosts[0]) === '') {
            return $html;
        }

        // Pattern to match img tags with src attributes
        $pattern = '/<img\s+[^>]*src\s*=\s*["\']([^"\']+)["\'][^>]*>/i';
        return preg_replace_callback(
            $pattern,
            function (array $matches) use ($allowedHosts): string {
                $fullMatch = $matches[0];
                $imageUrl = $matches[1];
                // Parse the URL to get the host
                $parsedUrl = parse_url($imageUrl);
                if (!$parsedUrl || !array_key_exists('host', $parsedUrl)) {
                    return $fullMatch; // Return original if URL is malformed
                }

                $host = $parsedUrl['host'];
                // Check if the host is in the allowed list
                $isAllowed = false;
                foreach ($allowedHosts as $allowedHost) {
                    $allowedHost = trim($allowedHost);
                    if ($allowedHost === '') {
                        continue;
                    }

                    if ($allowedHost === '0') {
                        continue;
                    }

                    // Allow exact match or subdomain match
                    if ($host === $allowedHost || str_ends_with($host, '.' . $allowedHost)) {
                        $isAllowed = true;
                        break;
                    }
                }

                if (!$isAllowed) {
                    return $fullMatch; // Return original if host not allowed
                }

                // Try to fetch the image and convert to base64
                try {
                    $imageData = $this->fetchExternalImage($imageUrl);
                    if ($imageData !== false) {
                        $base64Image = base64_encode($imageData);
                        $mimeType = $this->getImageMimeType($imageData);
                        if ($mimeType && $base64Image) {
                            $fmt = 'data:%s;base64,%s';
                            $dataUri = sprintf($fmt, $mimeType, $base64Image);
                            return str_replace($imageUrl, $dataUri, $fullMatch);
                        }
                    }
                } catch (Exception) {
                    // If fetching fails, return the original
                    return $fullMatch;
                }

                return $fullMatch;
            },
            $html,
        ) ?? '';
    }

    /**
     * Fetches an external image with the appropriate error handling.
     *
     * @param string $url The image URL to fetch
     * @return string|false The image data or false on failure
     */
    private function fetchExternalImage(string $url): false|string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10, // 10-second timeout
                'user_agent' => 'phpMyFAQ PDF Generator/1.0',
                'follow_location' => true,
                'max_redirects' => 3,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $imageData = file_get_contents($url, use_include_path: false, context: $context);

        // Validate that we actually got image data
        if ($imageData === false || $imageData === '') {
            return false;
        }

        // Quick validation that this looks like image data
        if (!$this->validateImageData($imageData)) {
            return false;
        }

        return $imageData;
    }

    /**
     * Validates that the given data appears to be a valid image.
     *
     * @param string $data The image data to validate
     * @return bool True if data appears to be a valid image
     */
    private function validateImageData(string $data): bool
    {
        if (strlen($data) < 10) {
            return false; // Too small to be a real image
        }

        // Check for common image file signatures
        $signatures = [
            'jpeg' => ["\xFF\xD8\xFF"],
            'png' => ["\x89PNG\r\n\x1A\n"],
            'gif' => ['GIF87a', 'GIF89a'],
            'webp' => ['RIFF'],
            'bmp' => ['BM'],
        ];

        foreach ($signatures as $signature) {
            foreach ($signature as $sig) {
                if (!str_starts_with($data, $sig)) {
                    continue;
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Determines the MIME type of image data.
     *
     * @param string $data The image data
     * @return string|false The MIME type or false if not determined
     */
    private function getImageMimeType(string $data): string|false
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            // Fallback to header-based detection
            if (str_starts_with($data, "\xFF\xD8\xFF")) {
                return 'image/jpeg';
            }

            if (str_starts_with($data, "\x89PNG\r\n\x1A\n")) {
                return 'image/png';
            }

            if (str_starts_with($data, 'GIF87a') || str_starts_with($data, 'GIF89a')) {
                return 'image/gif';
            }

            if (str_starts_with($data, 'RIFF')) {
                return 'image/webp';
            }

            // Fallback to header-based detection
            if (str_starts_with($data, 'BM')) {
                return 'image/bmp';
            }

            return false;
        }

        $mimeType = finfo_buffer($finfo, $data);

        // Ensure it's actually an image MIME type
        if ($mimeType && str_starts_with($mimeType, 'image/')) {
            return $mimeType;
        }

        return false;
    }

    /**
     * Writes HTML content, pre-processing external images to base64 data URIs.
     * This method converts external images from allowed hosts to base64 data URIs
     * before passing the content to the engine for rendering.
     *
     * @param string $html HTML content to write
     * @param bool $ln If true, the position after the call will be moved to the next line
     * @param bool $fill Indicates if the background must be painted (true) or transparent (false)
     * @param bool $reseth If true, reset the last cell height
     * @param bool $cell If true, add the current left/right/top/bottom cell margins to the coordinates
     * @param string $align Allows centering or align the image on the current line
     */
    public function WriteHTML(
        string $html,
        bool $ln = true,
        bool $fill = false,
        bool $reseth = false,
        bool $cell = false,
        string $align = '',
    ): void {
        // Pre-process HTML content to convert external images to base64, then delegate.
        $this->engine->writeHtml($this->convertExternalImagesToBase64($html), $ln, $fill, $reseth, $cell, $align);
    }
}
