<?php

/**
 * Main PDF class for phpMyFAQ which "just" extends the TCPDF library.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Peter Beauvain <pbeauvain@web.de>
 * @author    Krzysztof Kruszynski <thywolf@wolf.homelinux.net>
 * @copyright 2004-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2004-11-21
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

define('K_TCPDF_EXTERNAL_CONFIG', true);

define('K_PATH_URL', '');

/*
 * path to TCPDF
 *
 */
define('K_PATH_MAIN', PMF_INCLUDE_DIR.'/libs/tcpdf/');

/*
 * path for PDF fonts
 * use K_PATH_MAIN.'fonts/old/' for old non-UTF8 fonts
 */
define('K_PATH_FONTS', K_PATH_MAIN.'fonts/');

/*
 * cache directory for temporary files (full path)
 */
define('K_PATH_CACHE', PMF_ROOT_DIR.'/images/');

/*
 * cache directory for temporary files (url path)
 */
define('K_PATH_URL_CACHE', K_PATH_CACHE);

/*
 * images directory
 */
define('K_PATH_IMAGES', K_PATH_MAIN.'images/');

/*
 * blank image
 */
define('K_BLANK_IMAGE', K_PATH_IMAGES.'_blank.png');

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
define('PDF_FONT_MONOSPACED', 'courier');

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

require K_PATH_MAIN.'/tcpdf.php';

/**
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Peter Beauvain <pbeauvain@web.de>
 * @author    Olivier Plathey <olivier@fpdf.org>
 * @author    Krzysztof Kruszynski <thywolf@wolf.homelinux.net>
 * @copyright 2004-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2004-11-21
 */
class PMF_Export_Pdf_Wrapper extends TCPDF
{
    /**
     * With or without bookmarks.
     *
     * @var bool
     */
    public $enableBookmarks = false;

    /**
     * Full export from admin backend?
     *
     * @var bool
     */
    public $isFullExport = false;

    /**
     * Categories.
     *
     * @var array
     */
    public $categories = [];

    /**
     * The current category.
     */
    public $category = null;

    /**
     * The current faq.
     *
     * @var array
     */
    public $faq = [];

    /**
     * Question.
     *
     * @var string
     */
    private $question = '';

    /**
     * Configuration.
     *
     * @var PMF_Configuration
     */
    protected $_config = null;

    /**
     * Font files.
     *
     * @var array
     */
    private $fontFiles = array(
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
    );

    /**
     * Current font.
     *
     * @var string
     */
    private $currentFont = 'dejavusans';

    /**
     * @var string
     */
    private $customHeader;

    /**
     * @var string
     */
    private $customFooter;

    /**
     * Constructor.
     *
     * @return PMF_Export_Pdf_Wrapper
     */
    public function __construct()
    {
        global $PMF_LANG;

        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $this->setFontSubsetting(false);

        // set image scale factor
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // Check on RTL
        if ('rtl' == $PMF_LANG['dir']) {
            $this->setRTL(true);
        }

        // Set font
        if (array_key_exists($PMF_LANG['metaLanguage'], $this->fontFiles)) {
            $this->currentFont = (string) $this->fontFiles[$PMF_LANG['metaLanguage']];
        }
    }

    /**
     * Setter for the category name.
     *
     * @param string $category Category name
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * Setter for the question.
     *
     * @param string $question Question
     */
    public function setQuestion($question = '')
    {
        $this->question = $question;
    }

    /**
     * Setter for categories array.
     *
     * @param array $categories Categories
     */
    public function setCategories(Array $categories)
    {
        $this->categories = $categories;
    }

    /**
     * @param PMF_Configuration $config
     */
    public function setConfig(PMF_Configuration $config)
    {
        $this->_config = $config;
    }

    /**
     * Sets custom header.
     */
    public function setCustomHeader()
    {
        $this->customHeader = html_entity_decode($this->_config->get('main.customPdfHeader'), ENT_QUOTES, 'utf-8');
    }

    /**
     * Sets custom footer.
     */
    public function setCustomFooter()
    {
        $this->customFooter = $this->_config->get('main.customPdfFooter');
    }

    /**
     * The header of the PDF file.
     */
    public function Header()
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

        if (0 < PMF_String::strlen($this->customHeader)) {
            $this->writeHTMLCell(0, 0, '', '', $this->customHeader);
            $this->Ln();
            $this->writeHTMLCell(0, 0, '', '', html_entity_decode($title, ENT_QUOTES, 'utf-8'), 0, 0, false, true, 'C');
        } else {
            $this->MultiCell(0, 10, html_entity_decode($title, ENT_QUOTES, 'utf-8'), 0, 'C', 0);
            $this->SetMargins(PDF_MARGIN_LEFT, $this->getLastH() + 5, PDF_MARGIN_RIGHT);
        }
    }

    /**
     * The footer of the PDF file.
     */
    public function Footer()
    {
        global $PMF_LANG;

        // Set custom footer
        $this->setCustomFooter();

        $date = new PMF_Date($this->_config);

        $footer = sprintf(
            '(c) %d %s <%s> | %s',
            date('Y'),
            $this->_config->get('main.metaPublisher'),
            $this->_config->get('main.administrationMail'),
            $date->format(date('Y-m-d H:i'))
        );

        if (0 < PMF_String::strlen($this->customFooter)) {
            $this->writeHTMLCell(0, 0, '', '', $this->customFooter);
        }

        $currentTextColor = $this->TextColor;
        $this->SetTextColor(0, 0, 0);
        $this->SetY(-25);
        $this->SetFont($this->currentFont, '', 10);
        $this->Cell(0, 10, $PMF_LANG['ad_gen_page'].' '.$this->getAliasNumPage().' / '.$this->getAliasNbPages(), 0, 0, 'C');
        $this->SetY(-20);
        $this->SetFont($this->currentFont, 'B', 8);
        $this->Cell(0, 10, $footer, 0, 1, 'C');
        if ($this->enableBookmarks == false) {
            $this->SetY(-15);
            $this->SetFont($this->currentFont, '', 8);
            $baseUrl = 'index.php';
            if (is_array($this->faq) && !empty($this->faq)) {
                $baseUrl .= '?action=artikel&amp;';
                if (array_key_exists($this->category, $this->categories)) {
                    $baseUrl .= 'cat='.$this->categories[$this->category]['id'];
                } else {
                    $baseUrl .= 'cat=0';
                }
                $baseUrl .= '&amp;id='.$this->faq['id'];
                $baseUrl .= '&amp;artlang='.$this->faq['lang'];
            }
            $url = $this->_config->getDefaultUrl().$baseUrl;
            $urlObj = new PMF_Link($url, $this->_config);
            $urlObj->itemTitle = $this->question;
            $_url = str_replace('&amp;', '&', $urlObj->toString());
            $this->Cell(0, 10, 'URL: '.$_url, 0, 1, 'C', 0, $_url);
        }
        $this->TextColor = $currentTextColor;
    }

    /**
     * Adds a table of content for exports of the complete FAQ.
     */
    public function addFaqToc()
    {
        global $PMF_LANG;

        $this->addTOCPage();

        // Title
        $this->SetFont($this->currentFont, 'B', 24);
        $this->MultiCell(0, 0, $this->_config->get('main.titleFAQ'), 0, 'C', 0, 1, '', '', true, 0);
        $this->Ln();

        // TOC
        $this->SetFont($this->currentFont, 'B', 16);
        $this->MultiCell(0, 0, $PMF_LANG['msgTableOfContent'], 0, 'C', 0, 1, '', '', true, 0);
        $this->Ln();
        $this->SetFont($this->currentFont, '', 12);

        // Render TOC
        $this->addTOC(1, $this->currentFont, '.', $PMF_LANG['msgTableOfContent'], 'B', array(128, 0, 0));
        $this->endTOCPage();
    }

    /**
     * Sets the current font for PDF export.
     *
     * @param string $currentFont
     */
    public function setCurrentFont($currentFont)
    {
        $this->currentFont = $currentFont;
    }

    /**
     * Returns the current font for PDF export.
     *
     * @return string
     */
    public function getCurrentFont()
    {
        return $this->currentFont;
    }

    /**
     * Sets the FAQ array.
     *
     * @param array $faq
     */
    public function setFaq(Array $faq)
    {
        $this->faq = $faq;
    }

    /**
     * Extends the TCPDF::Image() method to handle base64 encoded images.
     *
     * @param string $file      Name of the file containing the image or a '@' character followed by the image data
     *                          string. To link an image without embedding it on the document, set an asterisk character
     *                          before the URL (i.e.: '*http://www.example.com/image.jpg').
     * @param string $x         Abscissa of the upper-left corner (LTR) or upper-right corner (RTL).
     * @param string $y         Ordinate of the upper-left corner (LTR) or upper-right corner (RTL).
     * @param int    $w         Width of the image in the page. If not specified or equal to zero, it is automatically
     *                          calculated.
     * @param int    $h         Height of the image in the page. If not specified or equal to zero, it is automatically
     *                          calculated.
     * @param string $type      Image format. Possible values are (case insensitive): JPEG and PNG (whitout GD library)
     *                          and all images supported by GD: GD, GD2, GD2PART, GIF, JPEG, PNG, BMP, XBM, XPM;. If not
     *                          specified, the type is inferred from the file extension.
     * @param string $link      URL or identifier returned by AddLink().
     * @param string $align     Indicates the alignment of the pointer next to image insertion relative to image height.
     * @param bool   $resize    If true resize (reduce) the image to fit $w and $h (requires GD or ImageMagick library);
     *                          if false do not resize; if 2 force resize in all cases (upscaling and downscaling).
     * @param int    $dpi       dot-per-inch resolution used on resize
     * @param string $palign    Allows to center or align the image on the current line.
     * @param bool   $ismask    true if this image is a mask, false otherwise
     * @param mixed  $imgmask   Image object returned by this function or false
     * @param int    $border    Indicates if borders must be drawn around the cell.
     * @param mixed  $fitbox    If not false scale image dimensions proportionally to fit within the ($w, $h) box.
     *                          $fitbox can be true or a 2 characters string indicating the image alignment inside the
     *                          box. The first character indicate the horizontal alignment (L = left, C = center,
     *                          R = right) the second character indicate the vertical algnment (T = top, M = middle,
     *                          B = bottom).
     * @param bool   $hidden    If true do not display the image.
     * @param bool   $fitonpage If true the image is resized to not exceed page dimensions.
     * @param bool   $alt       If true the image will be added as alternative and not directly printed (the ID of the
     *                          image will be returned).
     * @param array  $altimgs   Array of alternate images IDs. Each alternative image must be an array with two values:
     *                          an integer representing the image ID (the value returned by the Image method) and a
     *                          boolean value to indicate if the image is the default for printing.
     *
     * @return image information
     */
    public function Image($file, $x = '', $y = '', $w = 0, $h = 0, $type = '', $link = '', $align = '', $resize = false, $dpi = 300, $palign = '', $ismask = false, $imgmask = false, $border = 0, $fitbox = false, $hidden = false, $fitonpage = false, $alt = false, $altimgs = [])
    {
        if (!strpos($file, 'data:image/png;base64,') === false) {
            $file = '@'.base64_decode(
                chunk_split(str_replace(' ', '+', str_replace('data:image/png;base64,', '', $file)))
            );
        }

        parent::Image($file, $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border, $fitbox, $hidden, $fitonpage, $alt, $altimgs);
    }
}
