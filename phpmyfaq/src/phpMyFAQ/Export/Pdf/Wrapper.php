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
use phpMyFAQ\Link;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use TCPDF;

if (!defined(constant_name: 'PMF_ROOT_DIR')) {
    define(constant_name: 'PMF_ROOT_DIR', value: __DIR__ . '/../../../');
}

if (!defined(constant_name: 'PMF_SRC_DIR')) {
    define(constant_name: 'PMF_SRC_DIR', value: __DIR__ . '/../../');
}

define(constant_name: 'K_TCPDF_EXTERNAL_CONFIG', value: true);

define(constant_name: 'K_PATH_URL', value: '');

/*
 * path to TCPDF
 *
 */
define(constant_name: 'K_PATH_MAIN', value: PMF_SRC_DIR . '/libs/tecnickcom/tcpdf/');

/*
 * path for PDF fonts
 */
define(constant_name: 'K_PATH_FONTS', value: PMF_SRC_DIR . '/fonts/');

/*
 * cache directory for temporary files (full path)
 */
define(constant_name: 'K_PATH_CACHE', value: PMF_ROOT_DIR . '/content/user/images/');

/*
 * cache directory for temporary files (url path)
 */
define(constant_name: 'K_PATH_URL_CACHE', value: K_PATH_CACHE);

/*
 * images directory
 */
define(constant_name: 'K_PATH_IMAGES', value: PMF_ROOT_DIR . '/content/user/images/');

/*
 * blank image
 */
define(constant_name: 'K_BLANK_IMAGE', value: K_PATH_IMAGES . '_blank.png');

/*
 * page format
 */
define(constant_name: 'PDF_PAGE_FORMAT', value: 'A4');

/*
 * page orientation (P=portrait, L=landscape)
 */
define(constant_name: 'PDF_PAGE_ORIENTATION', value: 'P');

/*
 * document creator
 */
define(constant_name: 'PDF_CREATOR', value: 'TCPDF');

/*
 * document author
 */
define(constant_name: 'PDF_AUTHOR', value: 'TCPDF');

/*
 * header title
 */
define(constant_name: 'PDF_HEADER_TITLE', value: 'phpMyFAQ');

/*
 * header description string
 */
define(constant_name: 'PDF_HEADER_STRING', value: 'by phpMyFAQ - www.phpmyfaq.de');

/*
 * image logo
 */
define(constant_name: 'PDF_HEADER_LOGO', value: 'tcpdf_logo.jpg');

/*
 * header logo image width [mm]
 */
define(constant_name: 'PDF_HEADER_LOGO_WIDTH', value: 30);

/*
 * document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch]
 */
define(constant_name: 'PDF_UNIT', value: 'mm');

/*
 * header margin
 */
define(constant_name: 'PDF_MARGIN_HEADER', value: 5);

/*
 * footer margin
 */
define(constant_name: 'PDF_MARGIN_FOOTER', value: 10);

/*
 * top margin
 */
define(constant_name: 'PDF_MARGIN_TOP', value: 27);

/*
 * bottom margin
 */
define(constant_name: 'PDF_MARGIN_BOTTOM', value: 25);

/*
 * left margin
 */
define(constant_name: 'PDF_MARGIN_LEFT', value: 15);

/*
 * right margin
 */
define(constant_name: 'PDF_MARGIN_RIGHT', value: 15);

/*
 * default main font name
 */
define(constant_name: 'PDF_FONT_NAME_MAIN', value: 'arialunicid0');

/*
 * default main font size
 */
define(constant_name: 'PDF_FONT_SIZE_MAIN', value: 10);

/*
 * default data font name
 */
define(constant_name: 'PDF_FONT_NAME_DATA', value: 'arialunicid0');

/*
 * default data font size
 */
define(constant_name: 'PDF_FONT_SIZE_DATA', value: 8);

/*
 * default monospaced font name
 */
define(constant_name: 'PDF_FONT_MONOSPACED', value: 'DejaVuSansMono');

/*
 * ratio used to adjust the conversion of pixels to user units
 */
define(constant_name: 'PDF_IMAGE_SCALE_RATIO', value: 1);

/*
 * magnification factor for titles
 */
define(constant_name: 'HEAD_MAGNIFICATION', value: 1.1);

/*
 * height of cell respect font height
 */
define(constant_name: 'K_CELL_HEIGHT_RATIO', value: 1.25);

/*
 * title magnification respect main font size
 */
define(constant_name: 'K_TITLE_MAGNIFICATION', value: 1.3);

/*
 * reduction factor for a small font
 */
define(constant_name: 'K_SMALL_RATIO', value: 2 / 3);

/**
 * Class Wrapper
 *
 * @package phpMyFAQ\Export\Pdf
 */
class Wrapper extends TCPDF
{
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
     * Configuration.
     */
    protected ?Configuration $config = null;

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

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT);

        $this->setFontSubsetting(enable: false);

        // set image scale factor
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set default monospaced font
        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // Check on RTL
        if ('rtl' === Translation::get(key: 'direction')) {
            $this->setRTL(enable: true);
        }

        // Set font
        if (array_key_exists(Translation::get(key: 'metaLanguage'), $this->fontFiles)) {
            $this->currentFont = (string) $this->fontFiles[Translation::get(key: 'metaLanguage')];
        }
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
    #[\Override]
    public function Header(): void
    {
        // Set a custom header and footer
        $this->setCustomHeader();

        $title = array_key_exists($this->category, $this->categories) ? $this->categories[$this->category]['name'] : '';

        $this->SetTextColor(col1: 0, col2: 0, col3: 0);
        $this->SetFont($this->currentFont, style: 'B', size: 14);

        if (0 < Strings::strlen($this->customHeader)) {
            $this->writeHTMLCell(w: 0, h: 0, x: 0, y: 0, html: $this->customHeader);
            $this->Ln();
            $this->writeHTMLCell(
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
        } else {
            $this->MultiCell(
                w: 0,
                h: 10,
                txt: html_entity_decode((string) $title, ENT_QUOTES, encoding: 'utf-8'),
                border: 0,
                align: 'C',
            );
            $this->SetMargins(PDF_MARGIN_LEFT, $this->getLastH() + 5, PDF_MARGIN_RIGHT);
        }
    }

    /**
     * Sets custom header.
     */
    public function setCustomHeader(): void
    {
        $this->customHeader = html_entity_decode(
            (string) $this->config->get(item: 'main.customPdfHeader'),
            ENT_QUOTES,
            encoding: 'utf-8',
        );
    }

    /**
     * The footer of the PDF file.
     * @throws Exception
     */
    #[\Override]
    public function Footer(): void
    {
        // Set a custom footer
        $this->setCustomFooter();

        $date = new Date($this->config);

        $footer = sprintf(
            $this->config->get(item: 'spam.mailAddressInExport') ? '© %d %s <%s> | %s' : '© %d %s %s| %s',
            date(format: 'Y'),
            $this->config->get(item: 'main.metaPublisher'),
            $this->config->get(item: 'spam.mailAddressInExport') ? $this->config->getAdminEmail() : '',
            $date->format(date(format: 'Y-m-d H:i')),
        );

        if (0 < Strings::strlen($this->customFooter)) {
            $this->writeHTMLCell(w: 0, h: 0, x: null, y: null, html: $this->customFooter);
        }

        $currentTextColor = $this->TextColor;
        $this->SetTextColor(col1: 0, col2: 0, col3: 0);
        $this->SetY(-25);
        $this->SetFont($this->currentFont, style: '', size: 10);
        $this->Cell(
            w: 0,
            h: 10,
            txt: Translation::get(key: 'ad_gen_page') . ' ' . $this->getAliasNumPage() . ' / '
                . $this->getAliasNbPages(),
            border: 0,
            ln: 0,
            align: 'C',
        );
        $this->SetY(-20);
        $this->SetFont($this->currentFont, style: 'B', size: 8);
        $this->Cell(w: 0, h: 10, txt: $footer, border: 0, ln: 1, align: 'C');
        if (!$this->enableBookmarks) {
            $this->SetY(-15);
            $this->SetFont($this->currentFont, style: '', size: 8);
            $baseUrl = 'index.php';
            if ($this->faq !== []) {
                $baseUrl .= '?action=faq&';
                $baseUrl .= 'cat=0';
                if (array_key_exists($this->category, $this->categories)) {
                    $baseUrl .= 'cat=' . $this->categories[$this->category]['id'];
                }

                $baseUrl .= '&id=' . $this->faq['id'];
                $baseUrl .= '&artlang=' . $this->faq['lang'];
            }

            $url = $this->config->getDefaultUrl() . $baseUrl;
            $link = new Link($url, $this->config);
            $link->setTitle($this->question);
            $this->Cell(
                w: 0,
                h: 10,
                txt: 'URL: ' . $link->toString(),
                border: 0,
                ln: 1,
                align: 'C',
                fill: false,
                link: $link->toString(),
            );
        }

        $this->TextColor = $currentTextColor;
    }

    /**
     * Sets custom footer.
     */
    public function setCustomFooter(): void
    {
        $this->customFooter = $this->config->get(item: 'main.customPdfFooter') ?? '';
    }

    /**
     * Adds a table of content for exports of the complete FAQ.
     */
    public function addFaqToc(): void
    {
        $this->addTOCPage();

        // Title
        $this->SetFont($this->currentFont, style: 'B', size: 24);
        $this->MultiCell(w: 0, h: 0, txt: $this->config->getTitle(), border: 0, align: 'C');
        $this->Ln();

        // TOC
        $this->SetFont($this->currentFont, style: 'B', size: 16);
        $this->MultiCell(w: 0, h: 0, txt: Translation::get(key: 'msgTableOfContent'), border: 0, align: 'C');
        $this->Ln();
        $this->SetFont($this->currentFont, style: '', size: 12);

        // Render TOC
        $this->addTOC(
            page: 1,
            numbersfont: $this->currentFont,
            filler: '.',
            toc_name: Translation::get(key: 'msgTableOfContent'),
            style: 'B',
            color: [128, 0, 0],
        );
        $this->endTOCPage();
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
     * Extends the TCPDF::Image() method to convert all images to base64 encoded images.
     * This is necessary as TCPDF does not support external images from self-signed certificates.
     *
     * @param string $file Name of the file containing the image or a '@' character followed by the image data
     *                          string. To link an image without embedding it on the document, set an asterisk
     *                          character before the URL (i.e.: '*http://www.example.com/image.jpg').
     * @param float|null $x Abscissa of the upper-left corner (LTR) or upper-right corner (RTL).
     * @param float|null $y Ordinate of the upper-left corner (LTR) or upper-right corner (RTL).
     * @param float    $w Width of the image in the page. If not specified or equal to zero, it is automatically
     *                          calculated.
     * @param float    $h Height of the image in the page. If not specified or equal to zero, it is automatically
     *                          calculated.
     * @param string $type Image format. Possible values are (case-insensitive): JPEG and PNG (without a GD library)
     *                          and all images supported by GD: GD, GD2, GD2PART, GIF, JPEG, PNG, BMP, XBM, XPM;. If
     *                          not specified, the type is inferred from the file extension.
     * @param string $link URL or identifier returned by AddLink().
     * @param string $align Indicates the alignment of the pointer next to image insertion relative to image height.
     * @param bool   $resize If true resizes (reduce) the image to fit $w and $h (requires a GD or ImageMagick library);
     *                          if false do not resize; if two force resize in all cases (upscaling and downscaling).
     * @param int    $dpi dot-per-inch resolution used on resize
     * @param string $palign Allows centering or aligning the image on the current line.
     * @param bool   $ismask true if this image is a mask, false otherwise
     * @param mixed  $imgmask Image object returned by this function or false
     * @param int   $border Indicates if borders must be drawn around the cell.
     * @param mixed $fitbox If not, false scale image dimensions proportionally to fit within the ($w, $h) box.
     *                          $fitbox can be true or a 2-character string indicating the image alignment inside
     *                          the box. The first character indicates the horizontal alignment (L = left, C =
     *                          center, R = right) the second character indicates the vertical algnment (T = top, M
     *                          = middle, B = bottom).
     * @param bool  $hidden If true, do not display the image.
     * @param bool  $fitonpage If true, the image is resized to not exceed page dimensions.
     * @param bool  $alt If true, the image will be added as alternative and not directly printed (the ID of the
     *                          image will be returned).
     * @param array $alternateImages Array of alternate images IDs. Each alternative image must be an array with
     *                               two values:
     *                               an integer representing the image ID (the value returned by the Image method) and a
     *                               boolean value to indicate if the image is the default for printing.
     */
    #[\Override]
    /* @mago-ignore lint:excessive-parameter-list */
    public function Image(
        $file,
        $x = null,
        $y = null,
        $w = 0,
        $h = 0,
        $type = '',
        $link = '',
        $align = '',
        $resize = false,
        $dpi = 300,
        $palign = '',
        $ismask = false,
        $imgmask = false,
        $border = 0,
        $fitbox = false,
        $hidden = false,
        $fitonpage = false,
        $alt = false,
        $alternateImages = [],
    ): void {
        // Pass through raw image data ('@' prefix), non-embedded links ('*' prefix),
        // and data URIs without filesystem lookup.
        if (is_string($file) && $file !== '' && ($file[0] === '@' || $file[0] === '*')) {
            parent::Image(
                $file,
                $x,
                $y,
                $w,
                $h,
                $type,
                $link,
                $align,
                $resize,
                $dpi,
                $palign,
                $ismask,
                $imgmask,
                $border,
                $fitbox,
                $hidden,
                $fitonpage,
                $alt,
                $alternateImages,
            );
            return;
        }

        if (is_string($file) && str_starts_with($file, 'data:')) {
            if (preg_match('#^data:[^;]+;base64,(.+)$#', $file, $matches)) {
                $decoded = base64_decode($matches[1], strict: true);
                if ($decoded !== false && $this->checkBase64Image($decoded)) {
                    parent::Image(
                        '@' . $decoded,
                        $x,
                        $y,
                        $w,
                        $h,
                        $type,
                        $link,
                        $align,
                        $resize,
                        $dpi,
                        $palign,
                        $ismask,
                        $imgmask,
                        $border,
                        $fitbox,
                        $hidden,
                        $fitonpage,
                        $alt,
                        $alternateImages,
                    );
                    return;
                }
            }
            return;
        }

        $file = parse_url((string) $file, PHP_URL_PATH);
        if ($file === false || $file === null || $file === '') {
            return;
        }

        // URL-decode the file path to handle filenames with spaces and other special characters
        $file = urldecode($file);

        $type = pathinfo($file, PATHINFO_EXTENSION);
        $resolvedPath = $this->concatenatePaths(PMF_ROOT_DIR, $file);
        if ($resolvedPath === '' || !$this->isWithinRoot($resolvedPath)) {
            return;
        }

        if (!is_file($resolvedPath) || !is_readable($resolvedPath)) {
            return;
        }

        $data = file_get_contents($resolvedPath);
        if ($data === false) {
            return;
        }

        if (!$this->checkBase64Image($data)) {
            return;
        }

        $file = '@' . $data;

        parent::Image(
            $file,
            $x,
            $y,
            $w,
            $h,
            $type,
            $link,
            $align,
            $resize,
            $dpi,
            $palign,
            $ismask,
            $imgmask,
            $border,
            $fitbox,
            $hidden,
            $fitonpage,
            $alt,
            $alternateImages,
        );
    }

    private function checkBase64Image(string $base64): bool
    {
        set_error_handler(static fn(): bool => true, E_WARNING);
        try {
            $img = imagecreatefromstring($base64);
            if (!$img) {
                return false;
            }

            $info = getimagesizefromstring($base64);
        } finally {
            restore_error_handler();
        }

        return $info && $info[0] > 0 && $info[1] > 0 && isset($info['mime']);
    }

    public function concatenatePaths(string $path, string $file): string
    {
        $trimmedPath = rtrim(str_replace(search: '\\', replace: '/', subject: $path), characters: '/');
        $trimmedFile = ltrim(str_replace(search: '\\', replace: '/', subject: $file), characters: '/');

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
                if (!$parsedUrl || !isset($parsedUrl['host'])) {
                    return $fullMatch; // Return original if URL is malformed
                }

                $host = $parsedUrl['host'];
                // Check if the host is in the allowed list
                if (!$this->isHostAllowed($host, $allowedHosts)) {
                    return $fullMatch; // Return original if host not allowed
                }

                // Try to fetch the image and convert to base64
                try {
                    $imageData = $this->fetchExternalImage($imageUrl, $allowedHosts);
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
     * Checks whether a host is covered by the configured media host allowlist.
     *
     * Matches an exact hostname or any subdomain of an allowed host. Empty
     * entries and the disabled sentinel "0" are ignored.
     *
     * @param string   $host         The hostname to check
     * @param string[] $allowedHosts The configured allowlist
     * @return bool True if the host is allowed
     */
    private function isHostAllowed(string $host, array $allowedHosts): bool
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return false;
        }

        foreach ($allowedHosts as $allowedHost) {
            $allowedHost = strtolower(trim($allowedHost));
            if ($allowedHost === '' || $allowedHost === '0') {
                continue;
            }

            // Allow exact match or subdomain match
            if ($host === $allowedHost || str_ends_with($host, '.' . $allowedHost)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fetches an external image, following redirects manually so that the media
     * host allowlist is re-applied to every redirect destination. Delegating
     * redirects to the PHP stream wrapper would let an allowed origin redirect
     * the server-side request to a disallowed host (SSRF, CWE-918).
     *
     * @param string   $url          The image URL to fetch
     * @param string[] $allowedHosts The configured allowlist
     * @return string|false The image data or false on failure
     */
    private function fetchExternalImage(string $url, array $allowedHosts): false|string
    {
        $maxRedirects = 3;
        $currentUrl = $url;

        for ($hop = 0; $hop <= $maxRedirects; ++$hop) {
            $parsedUrl = parse_url($currentUrl);
            if ($parsedUrl === false || !isset($parsedUrl['scheme'], $parsedUrl['host'])) {
                return false;
            }

            // Only permit HTTP(S); reject file://, php://, data://, etc.
            $scheme = strtolower($parsedUrl['scheme']);
            if ($scheme !== 'http' && $scheme !== 'https') {
                return false;
            }

            // Re-apply the allowlist to the current hop, not just the first URL.
            if (!$this->isHostAllowed($parsedUrl['host'], $allowedHosts)) {
                return false;
            }

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10, // 10-second timeout
                    'user_agent' => 'phpMyFAQ PDF Generator/1.0',
                    'follow_location' => 0, // do not let the wrapper follow redirects
                    'max_redirects' => 1,
                    'ignore_errors' => true, // read the body of 3xx/4xx responses
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);

            // Clear any headers left over from a previous hop so a failed
            // connection cannot be misread using stale redirect headers.
            // The legacy variable is only ever named indirectly: PHP 8.5 emits a
            // compile-time deprecation for every literal $http_response_header
            // in the source, even inside branches that never run.
            $legacyHeaderVariable = 'http_response_header';
            if (function_exists('http_clear_last_response_headers')) {
                http_clear_last_response_headers();
            } else {
                unset(${$legacyHeaderVariable});
            }

            $responseHeaders = [];
            $body = @file_get_contents($currentUrl, use_include_path: false, context: $context);

            if (function_exists('http_get_last_response_headers')) {
                // PHP 8.5+: replaces the deprecated predefined variable.
                $responseHeaders = http_get_last_response_headers() ?? [];
            } else {
                // PHP < 8.5: the HTTP wrapper populates the variable in the local scope.
                $legacyHeaders = ${$legacyHeaderVariable} ?? null;
                if (is_array($legacyHeaders)) {
                    $responseHeaders = $legacyHeaders;
                }
            }

            [$statusCode, $location] = $this->parseHttpResponse($responseHeaders);

            // Handle redirects explicitly.
            if ($statusCode >= 300 && $statusCode < 400) {
                if ($location === null || $hop === $maxRedirects) {
                    return false; // no target, or redirect budget exhausted
                }

                $nextUrl = $this->resolveRedirectUrl($currentUrl, $location);
                if ($nextUrl === null) {
                    return false;
                }

                $currentUrl = $nextUrl;
                continue;
            }

            // Validate that we actually got image data
            if ($body === false || $body === '') {
                return false;
            }

            // Quick validation that this looks like image data
            if (!$this->validateImageData($body)) {
                return false;
            }

            return $body;
        }

        return false;
    }

    /**
     * Extracts the HTTP status code and Location header from a raw response
     * header list as produced by $http_response_header.
     *
     * @param string[] $headers Raw response header lines
     * @return array{0: int, 1: string|null} Status code and Location value
     */
    private function parseHttpResponse(array $headers): array
    {
        $statusCode = 0;
        $location = null;

        foreach ($headers as $header) {
            // A new status line resets the parse for the current response
            // (relevant if the wrapper ever surfaces multiple response blocks).
            if (preg_match('#^HTTP/\d(?:\.\d)?\s+(\d{3})#i', $header, $matches)) {
                $statusCode = (int) $matches[1];
                $location = null;
                continue;
            }

            if (stripos($header, 'Location:') === 0) {
                $location = trim(substr($header, strlen('Location:')));
            }
        }

        return [$statusCode, $location];
    }

    /**
     * Resolves a (possibly relative) Location header against the current URL.
     * Returns null when the result cannot be turned into an absolute HTTP(S) URL.
     *
     * @param string $baseUrl  The URL that produced the redirect
     * @param string $location The raw Location header value
     * @return string|null The absolute redirect target, or null on failure
     */
    private function resolveRedirectUrl(string $baseUrl, string $location): ?string
    {
        $location = trim($location);
        if ($location === '') {
            return null;
        }

        $target = parse_url($location);
        if ($target === false) {
            return null;
        }

        // Absolute URL with its own scheme and host.
        if (isset($target['scheme'], $target['host'])) {
            return $location;
        }

        $base = parse_url($baseUrl);
        if ($base === false || !isset($base['scheme'], $base['host'])) {
            return null;
        }

        $authority = $base['scheme'] . '://' . $base['host'];
        if (isset($base['port'])) {
            $authority .= ':' . $base['port'];
        }

        // Scheme-relative ("//host/path") is handled by the absolute branch above;
        // here we resolve absolute-path and relative-path references.
        if (str_starts_with($location, '/')) {
            return $authority . $location;
        }

        $basePath = $base['path'] ?? '/';
        $basePath = substr($basePath, 0, (int) strrpos($basePath, '/') + 1);
        if ($basePath === '') {
            $basePath = '/';
        }

        return $authority . $basePath . $location;
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
        finfo_close($finfo);

        // Ensure it's actually an image MIME type
        if ($mimeType && str_starts_with($mimeType, 'image/')) {
            return $mimeType;
        }

        return false;
    }

    /**
     * Override TCPDF's WriteHTML method to pre-process external images.
     * This method converts external images from allowed hosts to base64 data URIs
     * before passing the content to TCPDF for rendering.
     *
     * @param string $html HTML content to write
     * @param bool $ln If true, the position after the call will be moved to the next line
     * @param bool $fill Indicates if the background must be painted (true) or transparent (false)
     * @param bool $reseth If true, reset the last cell height
     * @param bool $cell If true, add the current left/right/top/bottom cell margins to the coordinates
     * @param string $align Allows centering or align the image on the current line
     */
    #[\Override]
    /* @mago-ignore lint:excessive-parameter-list */
    public function WriteHTML(
        // phpcs:ignore
        $html,
        $ln = true,
        $fill = false,
        $reseth = false,
        $cell = false,
        $align = '',
    ): void {
        // Pre-process HTML content to convert external images to base64
        $processedHtml = $this->convertExternalImagesToBase64($html);

        // Call the parent WriteHTML method with processed content
        parent::WriteHTML($processedHtml, $ln, $fill, $reseth, $cell, $align);
    }
}
