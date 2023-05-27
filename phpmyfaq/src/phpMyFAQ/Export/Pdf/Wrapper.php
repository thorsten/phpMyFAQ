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
 * @copyright 2004-2023 phpMyFAQ Team
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
define('K_PATH_CACHE', PMF_ROOT_DIR . '/images/');

/*
 * cache directory for temporary files (url path)
 */
define('K_PATH_URL_CACHE', K_PATH_CACHE);

/*
 * images directory
 */
define('K_PATH_IMAGES', K_PATH_MAIN . 'images/');

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
 *  document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch]
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
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $this->setFontSubsetting(false);

        // set image scale factor
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set default monospaced font
        $this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // Check on RTL
        if ('rtl' == Translation::get('dir')) {
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
    public function setQuestion(string $question = '')
    {
        $this->question = $question;
    }

    /**
     * Setter for categories array.
     *
     * @param array $categories Categories
     */
    public function setCategories(array $categories): void
    {
        $this->categories = $categories;
    }

    public function setConfig(Configuration $config): void
    {
        $this->config = $config;
    }

    /**
     * The header of the PDF file.
     */
    public function Header(): void // phpcs:ignore
    {
        // Set custom header and footer
        $this->setCustomHeader();

        if (array_key_exists($this->category, $this->categories)) {
            $title = $this->categories[$this->category]['name'];
        } else {
            $title = '';
        }

        $this->SetTextColor(0, 0, 0);
        $this->SetFont($this->currentFont, 'B', 18);

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
            $this->writeHTMLCell(0, 0, '', '', $this->customFooter);
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
        if ($this->enableBookmarks == false) {
            $this->SetY(-15);
            $this->SetFont($this->currentFont, '', 8);
            $baseUrl = 'index.php';
            if (is_array($this->faq) && !empty($this->faq)) {
                $baseUrl .= '?action=faq&amp;';
                if (array_key_exists($this->category, $this->categories)) {
                    $baseUrl .= 'cat=' . $this->categories[$this->category]['id'];
                } else {
                    $baseUrl .= 'cat=0';
                }
                $baseUrl .= '&amp;id=' . $this->faq['id'];
                $baseUrl .= '&amp;artlang=' . $this->faq['lang'];
            }
            $url = $this->config->getDefaultUrl() . $baseUrl;
            $urlObj = new Link($url, $this->config);
            $urlObj->itemTitle = $this->question;
            $_url = str_replace('&amp;', '&', $urlObj->toString());
            $this->Cell(0, 10, 'URL: ' . $_url, 0, 1, 'C', 0, $_url);
        }
        $this->TextColor = $currentTextColor;
    }

    /**
     * Sets custom footer.
     */
    public function setCustomFooter()
    {
        $this->customFooter = $this->config->get('main.customPdfFooter');
    }

    /**
     * Adds a table of content for exports of the complete FAQ.
     */
    public function addFaqToc()
    {
        $this->addTOCPage();

        // Title
        $this->SetFont($this->currentFont, 'B', 24);
        $this->MultiCell(0, 0, $this->config->getTitle(), 0, 'C', 0, 1, '', '', true, 0);
        $this->Ln();

        // TOC
        $this->SetFont($this->currentFont, 'B', 16);
        $this->MultiCell(0, 0, Translation::get('msgTableOfContent'), 0, 'C', 0, 1, '', '', true, 0);
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
    public function setFaq(array $faq)
    {
        $this->faq = $faq;
    }

    /**
     * Extends the TCPDF::Image() method to handle base64 encoded images.
     *
     * @param string $file Name of the file containing the image or a '@' character followed by the image data
     *                          string. To link an image without embedding it on the document, set an asterisk
     *                          character before the URL (i.e.: '*http://www.example.com/image.jpg').
     * @param string $x Abscissa of the upper-left corner (LTR) or upper-right corner (RTL).
     * @param string $y Ordinate of the upper-left corner (LTR) or upper-right corner (RTL).
     * @param int    $w Width of the image in the page. If not specified or equal to zero, it is automatically
     *                          calculated.
     * @param int    $h Height of the image in the page. If not specified or equal to zero, it is automatically
     *                          calculated.
     * @param string $type Image format. Possible values are (case insensitive): JPEG and PNG (whitout GD library)
     *                          and all images supported by GD: GD, GD2, GD2PART, GIF, JPEG, PNG, BMP, XBM, XPM;. If
     *                          not specified, the type is inferred from the file extension.
     * @param string $link URL or identifier returned by AddLink().
     * @param string $align Indicates the alignment of the pointer next to image insertion relative to image height.
     * @param bool   $resize If true resize (reduce) the image to fit $w and $h (requires GD or ImageMagick library);
     *                          if false do not resize; if 2 force resize in all cases (upscaling and downscaling).
     * @param int    $dpi dot-per-inch resolution used on resize
     * @param string $palign Allows to center or align the image on the current line.
     * @param bool   $ismask true if this image is a mask, false otherwise
     * @param mixed  $imgmask Image object returned by this function or false
     * @param int    $border Indicates if borders must be drawn around the cell.
     * @param mixed  $fitbox If not false scale image dimensions proportionally to fit within the ($w, $h) box.
     *                          $fitbox can be true or a 2 characters string indicating the image alignment inside
     *                          the box. The first character indicate the horizontal alignment (L = left, C =
     *                          center, R = right) the second character indicate the vertical algnment (T = top, M
     *                          = middle, B = bottom).
     * @param bool   $hidden If true do not display the image.
     * @param bool   $fitonpage If true the image is resized to not exceed page dimensions.
     * @param bool   $alt If true the image will be added as alternative and not directly printed (the ID of the
     *                          image will be returned).
     * @param array  $altimgs Array of alternate images IDs. Each alternative image must be an array with two values:
     *                          an integer representing the image ID (the value returned by the Image method) and a
     *                          boolean value to indicate if the image is the default for printing.
     */
    public function Image(// phpcs:ignore
        $file,
        $x = '',
        $y = '',
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
        $altimgs = []
    ): void {
        if (!strpos($file, 'data:image/png;base64,') === false) {
            $file = '@' . base64_decode(
                chunk_split(str_replace(' ', '+', str_replace('data:image/png;base64,', '', $file)))
            );
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
            $altimgs
        );
    }
}
