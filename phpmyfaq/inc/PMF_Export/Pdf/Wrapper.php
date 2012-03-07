<?php
/**
 * Main PDF class for phpMyFAQ which "just" extends the TCPDF library
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Export
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Peter Beauvain <pbeauvain@web.de>
 * @author    Krzysztof Kruszynski <thywolf@wolf.homelinux.net>
 * @copyright 2004-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2004-11-21
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

define('K_PATH_URL', '');

/**
 * path to TCPDF
 *
 */
define('K_PATH_MAIN', dirname(dirname(dirname(__FILE__))) . '/libs/tcpdf/');

/**
 * path for PDF fonts
 * use K_PATH_MAIN.'fonts/old/' for old non-UTF8 fonts
 */
define('K_PATH_FONTS', K_PATH_MAIN . 'fonts/');

/**
 * cache directory for temporary files (full path)
 */
define('K_PATH_CACHE', dirname(dirname(dirname(dirname(__FILE__)))) . '/images/');

/**
 * cache directory for temporary files (url path)
 */
define('K_PATH_URL_CACHE', K_PATH_CACHE);

/**
 * images directory
 */
define('K_PATH_IMAGES', K_PATH_MAIN . 'images/');

/**
 * blank image
 */
define('K_BLANK_IMAGE', K_PATH_IMAGES . '_blank.png');

/**
 * page format
 */
define('PDF_PAGE_FORMAT', 'A4');

/**
 * page orientation (P=portrait, L=landscape)
 */
define('PDF_PAGE_ORIENTATION', 'P');

/**
 * document creator
 */
define('PDF_CREATOR', 'TCPDF');

/**
 * document author
 */
define('PDF_AUTHOR', 'TCPDF');

/**
 * header title
 */
define('PDF_HEADER_TITLE', 'phpMyFAQ');

/**
 * header description string
 */
define('PDF_HEADER_STRING', "by phpMyFAQ - www.phpmyfaq.de");

/**
 * image logo
 */
define('PDF_HEADER_LOGO', 'tcpdf_logo.jpg');

/**
 * header logo image width [mm]
 */
define('PDF_HEADER_LOGO_WIDTH', 30);

/**
 *  document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch]
 */
define('PDF_UNIT', 'mm');

/**
 * header margin
 */
define('PDF_MARGIN_HEADER', 5);

/**
 * footer margin
 */
define('PDF_MARGIN_FOOTER', 10);

/**
 * top margin
 */
define('PDF_MARGIN_TOP', 27);

/**
 * bottom margin
 */
define('PDF_MARGIN_BOTTOM', 25);

/**
 * left margin
 */
define('PDF_MARGIN_LEFT', 15);

/**
 * right margin
 */
define('PDF_MARGIN_RIGHT', 15);

/**
 * default main font name
 */
define('PDF_FONT_NAME_MAIN', 'arialunicid0');

/**
 * default main font size
 */
define('PDF_FONT_SIZE_MAIN', 10);

/**
 * default data font name
 */
define('PDF_FONT_NAME_DATA', 'arialunicid0');

/**
 * default data font size
 */
define('PDF_FONT_SIZE_DATA', 8);

/**
 * default monospaced font name
 */
define('PDF_FONT_MONOSPACED', 'courier');

/**
 * ratio used to adjust the conversion of pixels to user units
 */
define('PDF_IMAGE_SCALE_RATIO', 1);

/**
 * magnification factor for titles
 */
define('HEAD_MAGNIFICATION', 1.1);

/**
 * height of cell repect font height
 */
define('K_CELL_HEIGHT_RATIO', 1.25);

/**
 * title magnification respect main font size
 */
define('K_TITLE_MAGNIFICATION', 1.3);

/**
 * reduction factor for small font
 */
define('K_SMALL_RATIO', 2 / 3);

require K_PATH_MAIN . '/tcpdf.php';

/**
 * @category  phpMyFAQ
 * @package   PMF_Export
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Peter Beauvain <pbeauvain@web.de>
 * @author    Olivier Plathey <olivier@fpdf.org>
 * @author    Krzysztof Kruszynski <thywolf@wolf.homelinux.net>
 * @copyright 2004-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2004-11-21
 */
class PMF_Export_Pdf_Wrapper extends TCPDF
{
    /**
    * With or without bookmarks
    *
    * @var boolean
    */
    public $enableBookmarks = false;
    
    /**
     * Full export from admin backend?
     *
     * @var boolean
     */
    public $isFullExport = false;

    /**
     * Categories
     *
     * @var array
     */
    public $categories = array();

    /**
     * The current category
     *
     */
    public $category = null;

    /**
     * The current faq
     *
     * @var array
     */
    public $faq = array();
    
    /**
     * Question
     *
     * @var string
     */
    private $question = '';

    /**
     * Font files
     *
     * @var array
     */
    private $fontFiles = array(
        'zh' => 'arialunicid0',
        'tw' => 'arialunicid0',
        'ja' => 'arialunicid0',
        'ko' => 'arialunicid0',
        'default' => 'helvetica'
    );

    /**
     * Current font
     *
     * @var string
     */
    private $currentFont = 'helvetica';

    /**
     * Constructor
     *
     * @param  array  $category    Current category
     * @param  string $thema       The title of the FAQ record
     * @param  array  $categories  The array with all category names
     *
     * @return PMF_Export_Pdf_Wrapper
     */
    public function __construct()
    {
        global $PMF_LANG;
        
        parent::__construct(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $this->setFontSubsetting(false);

        // Check on RTL
        if ('rtl' == $PMF_LANG['dir']) {
            $this->setRTL(true);
        }

        // Set font
        if (array_key_exists($PMF_LANG['metaLanguage'], $this->fontFiles)) {
            $this->currentFont = (string)$this->fontFiles[$PMF_LANG['metaLanguage']];
        }
    }
    
    /**
     * Setter for the category name
     *
     * @param string $category Category name
     *
     * @return void
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }
    
    /**
     * Setter for the question
     *
     * @param string $question Question
     */
    public function setQuestion($question = '')
    {
        $this->question = $question;
    }
    
    /**
     * Setter for categories array
     *
     * @param array $categories Categories
     */
    public function setCategories(Array $categories)
    {
        $this->categories = $categories;
    }
    
    /**
     * The header of the PDF file
     *
     * @return void
     */
    public function Header()
    {
        if (array_key_exists($this->category, $this->categories)) {
            $title = $this->categories[$this->category]['name'] . ': ' . $this->question;
        } else {
            $title = $this->question;
        }

        $title = html_entity_decode($title, ENT_QUOTES, 'utf-8');

        $currentTextColor = $this->TextColor;
        
        $this->SetTextColor(0,0,0);
        $this->SetFont($this->currentFont, 'B', 18);
        $this->MultiCell(0, 9, $title, 0, 'C', 0);
        if ($this->enableBookmarks) {
            $this->Bookmark(PMF_Utils::makeShorterText($this->question, 5));
        }
        
        $this->TextColor = $currentTextColor;
        $this->SetMargins(PDF_MARGIN_LEFT, $this->getLastH() + 5, PDF_MARGIN_RIGHT);
    }

    /**
     * The footer of the PDF file
     *
     * @return void
     */
    public function Footer() 
    {
        global $PMF_LANG;
        
        $faqConfig = PMF_Configuration::getInstance();

        $footer = sprintf(
            '(c) %d %s <%s> | %s',
            date('Y'),
            $faqConfig->get('main.metaPublisher'),
            $faqConfig->get('main.administrationMail'),
            PMF_Date::format(date('Y-m-d H:i'))
        );
        
        $currentTextColor = $this->TextColor;
        $this->SetTextColor(0,0,0);
        $this->SetY(-25);
        $this->SetFont($this->currentFont, '', 10);
        $this->Cell(0, 10, $PMF_LANG['ad_gen_page'] . ' ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'C');
        $this->SetY(-20);
        $this->SetFont($this->currentFont, 'B', 8);
        $this->Cell(0, 10, $footer,0,1,"C");
        if ($this->enableBookmarks == false) {
            $this->SetY(-15);
            $this->SetFont($this->currentFont, '', 8);
            $baseUrl = 'index.php';
            if (is_array($this->faq) && !empty($this->faq)) {
                $baseUrl .= '?action=artikel&amp;';
                if (array_key_exists($this->category, $this->categories)) {
                    $baseUrl .= 'cat=' . $this->categories[$this->category]['id'];
                } else {
                    $baseUrl .= 'cat=0';
                }
                $baseUrl .= '&amp;id='.$this->faq['id'];
                $baseUrl .= '&amp;artlang='.$this->faq['lang'];
            }
            $url    = PMF_Link::getSystemUri('pdf.php') . $baseUrl;
            $urlObj = new PMF_Link($url);
            $urlObj->itemTitle = $this->question;
            $_url = str_replace('&amp;', '&', $urlObj->toString());
            $this->Cell(0, 10, 'URL: '.$_url, 0, 1, 'C', 0, $_url);
        }
        $this->TextColor = $currentTextColor;
    }

    /**
     * Adds a table of content for exports of the complete FAQ
     *
     * @return void
     */
    public function addFaqToc()
    {
        global $PMF_LANG;
        
        $this->addTOCPage();

        // Title
        $this->SetFont($this->currentFont, 'B', 24);
        $this->MultiCell(0, 0, PMF_Configuration::getInstance()->get('main.titleFAQ'), 0, 'C', 0, 1, '', '', true, 0);
        $this->Ln();

        // TOC
        $this->SetFont($this->currentFont, 'B', 16);
        $this->MultiCell(0, 0, $PMF_LANG['msgTableOfContent'], 0, 'C', 0, 1, '', '', true, 0);
        $this->Ln();
        $this->SetFont($this->currentFont, '', 12);

        // Render TOC
        $this->addTOC(1, $this->currentFont, '.', $PMF_LANG['msgTableOfContent'], 'B', array(128,0,0));
        $this->endTOCPage();
    }

    /**
     * Sets the current font for PDF export
     *
     * @param string $currentFont
     */
    public function setCurrentFont($currentFont)
    {
        $this->currentFont = $currentFont;
    }

    /**
     * Returns the current font for PDF export
     *
     * @return string
     */
    public function getCurrentFont()
    {
        return $this->currentFont;
    }

    /**
     * Sets the FAQ array
     *
     * @param array $faq
     */
    public function setFaq(Array $faq)
    {
        $this->faq = $faq;
    }
}
