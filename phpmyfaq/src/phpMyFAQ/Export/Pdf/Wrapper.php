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
 * @copyright 2004-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2004-11-21
 */

namespace phpMyFAQ\Export\Pdf;

use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Date;
use phpMyFAQ\Link;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use TCPDF;

if (!defined('PMF_ROOT_DIR')) {
    define('PMF_ROOT_DIR', __DIR__ . '/../../../');
}

if (!defined('PMF_SRC_DIR')) {
    define('PMF_SRC_DIR', __DIR__ . '/../../');
}

define('K_TCPDF_EXTERNAL_CONFIG', true);

define('K_PATH_URL', '');

/*
 * path to TCPDF
 *
 */
define('K_PATH_MAIN', PMF_SRC_DIR . '/libs/tecnickcom/tcpdf/');

/*
 * path for PDF fonts
 */
define('K_PATH_FONTS', PMF_SRC_DIR . '/fonts/');

/*
 * cache directory for temporary files (full path)
 */
define('K_PATH_CACHE', PMF_ROOT_DIR . '/content/user/images/');

/*
 * cache directory for temporary files (url path)
 */
define('K_PATH_URL_CACHE', K_PATH_CACHE);

/*
 * images directory
 */
define('K_PATH_IMAGES', PMF_ROOT_DIR . '/content/user/images/');

/*
 * blank image
 */
define('K_BLANK_IMAGE', K_PATH_IMAGES . '_blank.png');

/*
 * page format
 */
define('PDF_PAGE_FORMAT', 'A4');

/*
 * page orientation (P=portrait, L=landscape)
 */
define('PDF_PAGE_ORIENTATION', 'P');

/*
 * document creator
 */
define('PDF_CREATOR', 'TCPDF');

/*
 * document author
 */
define('PDF_AUTHOR', 'TCPDF');

/*
 * header title
 */
define('PDF_HEADER_TITLE', 'phpMyFAQ');

/*
 * header description string
 */
define('PDF_HEADER_STRING', 'by phpMyFAQ - www.phpmyfaq.de');

/*
 * image logo
 */
define('PDF_HEADER_LOGO', 'tcpdf_logo.jpg');

/*
 * header logo image width [mm]
 */
define('PDF_HEADER_LOGO_WIDTH', 30);

/*
 * document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch]
 */
define('PDF_UNIT', 'mm');

/*
 * header margin
 */
define('PDF_MARGIN_HEADER', 5);

/*
 * footer margin
 */
define('PDF_MARGIN_FOOTER', 10);

/*
 * top margin
 */
define('PDF_MARGIN_TOP', 27);

/*
 * bottom margin
 */
define('PDF_MARGIN_BOTTOM', 25);

/*
 * left margin
 */
define('PDF_MARGIN_LEFT', 15);

/*
 * right margin
 */
define('PDF_MARGIN_RIGHT', 15);

/*
 * default main font name
 */
define('PDF_FONT_NAME_MAIN', 'arialunicid0');

/*
 * default main font size
 */
define('PDF_FONT_SIZE_MAIN', 10);

/*
 * default data font name
 */
define('PDF_FONT_NAME_DATA', 'arialunicid0');

/*
 * default data font size
 */
define('PDF_FONT_SIZE_DATA', 8);

/*
 * default monospaced font name
 */
define('PDF_FONT_MONOSPACED', 'DejaVuSansMono');

/*
 * ratio used to adjust the conversion of pixels to user units
 */
define('PDF_IMAGE_SCALE_RATIO', 1);

/*
 * magnification factor for titles
 */
define('HEAD_MAGNIFICATION', 1.1);

/*
 * height of cell repect font height
 */
define('K_CELL_HEIGHT_RATIO', 1.25);

/*
 * title magnification respect main font size
 */
define('K_TITLE_MAGNIFICATION', 1.3);

/*
 * reduction factor for small font
 */
define('K_SMALL_RATIO', 2 / 3);

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
        'tw' => 'arialunicid0',
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

    private string $customHeader;

    private string $customFooter;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT);

        $this->setFontSubsetting(false);

        // set image scale factor
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set default monospaced font
        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // Check on RTL
        if ('rtl' === Translation::get('direction')) {
            $this->setRTL(true);
        }

        // Set font
        if (array_key_exists(Translation::get('metaLanguage'), $this->fontFiles)) {
            $this->currentFont = (string)$this->fontFiles[Translation::get('metaLanguage')];
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
    public function Header(): void // phpcs:ignore
    {
        // Set custom header and footer
        $this->setCustomHeader();

        $title = array_key_exists($this->category, $this->categories) ? $this->categories[$this->category]['name'] : '';

        $this->SetTextColor(0, 0, 0);
        $this->SetFont($this->currentFont, 'B', 14);

        if (0 < Strings::strlen($this->customHeader)) {
            $this->writeHTMLCell(0, 0, 0, 0, $this->customHeader);
            $this->Ln();
            $this->writeHTMLCell(
                0,
                0,
                0,
                0,
                html_entity_decode((string) $title, ENT_QUOTES, 'utf-8'),
                0,
                0,
                false,
                true,
                'C'
            );
        } else {
            $this->MultiCell(0, 10, html_entity_decode((string) $title, ENT_QUOTES, 'utf-8'), 0, 'C');
            $this->SetMargins(PDF_MARGIN_LEFT, $this->getLastH() + 5, PDF_MARGIN_RIGHT);
        }
    }

    /**
     * Sets custom header.
     */
    public function setCustomHeader(): void
    {
        $this->customHeader = html_entity_decode(
            (string) $this->config->get('main.customPdfHeader'),
            ENT_QUOTES,
            'utf-8'
        );
    }

    /**
     * The footer of the PDF file.
     * @throws Exception
     */
    #[\Override]
    public function Footer(): void // phpcs:ignore
    {
        // Set custom footer
        $this->setCustomFooter();

        $date = new Date($this->config);

        $footer = sprintf(
            $this->config->get('spam.mailAddressInExport') ? '© %d %s <%s> | %s' : '© %d %s %s| %s',
            date('Y'),
            $this->config->get('main.metaPublisher'),
            $this->config->get('spam.mailAddressInExport') ? $this->config->getAdminEmail() : '',
            $date->format(date('Y-m-d H:i'))
        );

        if (0 < Strings::strlen($this->customFooter)) {
            $this->writeHTMLCell(0, 0, null, null, $this->customFooter);
        }

        $currentTextColor = $this->TextColor;
        $this->SetTextColor(0, 0, 0);
        $this->SetY(-25);
        $this->SetFont($this->currentFont, '', 10);
        $this->Cell(
            0,
            10,
            Translation::get('ad_gen_page') . ' ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(),
            0,
            0,
            'C'
        );
        $this->SetY(-20);
        $this->SetFont($this->currentFont, 'B', 8);
        $this->Cell(0, 10, $footer, 0, 1, 'C');
        if (!$this->enableBookmarks) {
            $this->SetY(-15);
            $this->SetFont($this->currentFont, '', 8);
            $baseUrl = 'index.php';
            if ($this->faq !== []) {
                $baseUrl .= '?action=faq&';
                if (array_key_exists($this->category, $this->categories)) {
                    $baseUrl .= 'cat=' . $this->categories[$this->category]['id'];
                } else {
                    $baseUrl .= 'cat=0';
                }

                $baseUrl .= '&id=' . $this->faq['id'];
                $baseUrl .= '&artlang=' . $this->faq['lang'];
            }

            $url = $this->config->getDefaultUrl() . $baseUrl;
            $link = new Link($url, $this->config);
            $link->itemTitle = $this->question;
            $this->Cell(0, 10, 'URL: ' . $link->toString(), 0, 1, 'C', false, $link->toString());
        }

        $this->TextColor = $currentTextColor;
    }

    /**
     * Sets custom footer.
     */
    public function setCustomFooter(): void
    {
        $this->customFooter = $this->config->get('main.customPdfFooter');
    }

    /**
     * Adds a table of content for exports of the complete FAQ.
     */
    public function addFaqToc(): void
    {
        $this->addTOCPage();

        // Title
        $this->SetFont($this->currentFont, 'B', 24);
        $this->MultiCell(0, 0, $this->config->getTitle(), 0, 'C');
        $this->Ln();

        // TOC
        $this->SetFont($this->currentFont, 'B', 16);
        $this->MultiCell(0, 0, Translation::get('msgTableOfContent'), 0, 'C');
        $this->Ln();
        $this->SetFont($this->currentFont, '', 12);

        // Render TOC
        $this->addTOC(1, $this->currentFont, '.', Translation::get('msgTableOfContent'), 'B', [128, 0, 0]);
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
    public function Image(// phpcs:ignore
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
        $alternateImages = []
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
            $alternateImages
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
        $trimmedPath = rtrim(str_replace('\\', '/', $path), '/');
        $trimmedFile = ltrim($file, '/');

        $pos = strpos($trimmedFile, 'content');
        $relativePath = substr($trimmedFile, $pos);

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
        if ($this->config === null) {
            return $html;
        }

        $allowedHosts = $this->config->getAllowedMediaHosts();
        if (empty($allowedHosts) || (count($allowedHosts) === 1 && trim($allowedHosts[0]) === '')) {
            return $html;
        }

        // Pattern to match img tags with src attributes
        $pattern = '/<img\s+[^>]*src\s*=\s*["\']([^"\']+)["\'][^>]*>/i';
        return preg_replace_callback($pattern, function ($matches) use ($allowedHosts) {
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
                if (empty($allowedHost)) {
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
                        $dataUri = "data:{$mimeType};base64,{$base64Image}";
                        return str_replace($imageUrl, $dataUri, $fullMatch);
                    }
                }
            } catch (Exception $e) {
                // If fetching fails, return original
                return $fullMatch;
            }

            return $fullMatch;
        }, $html);
    }

    /**
     * Fetches an external image with appropriate error handling.
     *
     * @param string $url The image URL to fetch
     * @return string|false The image data or false on failure
     */
    private function fetchExternalImage(string $url)
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10, // 10 second timeout
                'user_agent' => 'phpMyFAQ PDF Generator/1.0',
                'follow_location' => true,
                'max_redirects' => 3,
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $imageData = @file_get_contents($url, false, $context);

        // Validate that we actually got image data
        if ($imageData === false || strlen($imageData) === 0) {
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
            'gif' => ["GIF87a", "GIF89a"],
            'webp' => ["RIFF"],
            'bmp' => ["BM"],
        ];

        foreach ($signatures as $format => $sigs) {
            foreach ($sigs as $sig) {
                if (str_starts_with($data, $sig)) {
                    return true;
                }
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
    private function getImageMimeType(string $data)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            // Fallback to header-based detection
            if (str_starts_with($data, "\xFF\xD8\xFF")) {
                return 'image/jpeg';
            } elseif (str_starts_with($data, "\x89PNG\r\n\x1A\n")) {
                return 'image/png';
            } elseif (str_starts_with($data, "GIF87a") || str_starts_with($data, "GIF89a")) {
                return 'image/gif';
            } elseif (str_starts_with($data, "RIFF")) {
                return 'image/webp';
            } elseif (str_starts_with($data, "BM")) {
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
     * @param string $align Allows to center or align the image on the current line
     */
    #[\Override]
    public function WriteHTML(// phpcs:ignore
        $html,
        $ln = true,
        $fill = false,
        $reseth = false,
        $cell = false,
        $align = ''
    ): void {
        // Pre-process HTML content to convert external images to base64
        $processedHtml = $this->convertExternalImagesToBase64($html);

        // Call the parent WriteHTML method with processed content
        parent::WriteHTML($processedHtml, $ln, $fill, $reseth, $cell, $align);
    }
}
