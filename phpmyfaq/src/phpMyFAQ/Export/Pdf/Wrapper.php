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
        $file = parse_url($file, PHP_URL_PATH);

        // URL-decode the file path to handle filenames with spaces and other special characters
        $file = urldecode($file);

        $type = pathinfo($file, PATHINFO_EXTENSION);
        $data = file_get_contents($this->concatenatePaths(PMF_ROOT_DIR, $file));

        if ($this->checkBase64Image($data)) {
            $file = '@' . $data;
        }

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
        $img = imagecreatefromstring($base64);
        if (!$img) {
            return false;
        }

        $info = getimagesizefromstring($base64);

        return $info && $info[0] > 0 && $info[1] > 0 && isset($info['mime']);
    }

    public function concatenatePaths(string $path, string $file): string
    {
        $trimmedPath = rtrim(str_replace(search: '\\', replace: '/', subject: $path), characters: '/');
        $trimmedFile = ltrim($file, characters: '/');

        $pos = strpos($trimmedFile, needle: 'content');
        $relativePath = substr($trimmedFile, (int) $pos);

        return $trimmedPath . DIRECTORY_SEPARATOR . $relativePath;
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
        )
        ?? '';
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
