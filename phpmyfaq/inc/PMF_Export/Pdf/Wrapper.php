<?php
/**
 * Main PDF class for phpMyFAQ based on FPDF by Olivier Plathey
 *
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 * 
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   PMF_Export
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Peter Beauvain <pbeauvain@web.de>
 * @author    Olivier Plathey <olivier@fpdf.org>
 * @author    Krzysztof Kruszynski <thywolf@wolf.homelinux.net>
 * @copyright 2004-2009 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2004-11-21
 */

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
define('K_PATH_CACHE', K_PATH_MAIN . 'cache/');

/**
 * cache directory for temporary files (url path)
 */
define('K_PATH_URL_CACHE', K_PATH_URL . 'cache/');

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

class PMF_Export_Pdf_Wrapper extends TCPDF
{
    /**
    * <b> and <strong> for bold strings
    *
    * @var string
    */
    private $B;

    /**
    * <i> and <em> for italic strings
    *
    * @var string
    */
    private $I;

    /**
    * <u> for underlined strings
    *
    * @var string
    */
    private $U;

    /**
    * The "src" attribute inside (X)HTML tags
    *
    * @var string
    */
    private $SRC;

    /**
    * The "href" attribute inside (X)HTML tags
    *
    * @var string
    */
    protected $HREF;

    /**
    * <pre> for code examples
    *
    * @var string
    */
    private $PRE;

    /**
    * <div align="center"> for centering text
    *
    * @var string
    */
    private $CENTER;

    /**
    * The border of a table
    *
    * @var integer
    */
    private $tableborder;

    /**
    * The begin of a table
    *
    * @var integer
    */
    private $tdbegin;

    /**
    * The width of a table
    *
    * @var integer
    */
    private $tdwidth;

    /**
    * The heightof a table
    *
    * @var integer
    */
    private $tdheight;

    /**
    * The alignment of a table
    *
    * @var integer
    */
    private $tdalign;

    /**
    * The background color of a table
    *
    * @var integer
    */
    private $tdbgcolor;

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
    * Array with titles
    * 
    * @var array
    */
    protected $outlines = array();

    /**
    * Outline root
    * 
    * @var string
    */
    protected $OutlineRoot;

    /**
     * Supported image MIME types
     * 
     * @var array
     */
    private $mimetypes = array(1 => 'gif', 2 => 'jpg', 3 => 'png');
    
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
     */
    public $faq = null;
    
    /**
     * Question
     * 
     * @var string
     */
    private $question = '';

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
        $this->B = 0;
        $this->I = 0;
        $this->U = 0;
        $this->PRE = 0;
        $this->CENTER = 0;
        $this->SRC = "";
        $this->HREF = "";
        $this->tableborder = 0;
        $this->tdbegin = false;
        $this->tdwidth = 0;
        $this->tdheight = 0;
        $this->tdalign = "L";
        $this->tdbgcolor = false;
        // Check on RTL
        if ('rtl' == $PMF_LANG['dir']) {
            $this->setRTL(true);
        }
    }
    
    /**
     * Setter for the category name
     * 
     * @param string $catgory Category name
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
            $title = $this->categories[$this->category]['name'].': '.$this->question;
        } else {
            $title = $this->question;
        }
        $currentTextColor = $this->TextColor;
        
        $this->SetTextColor(0,0,0);
        $this->SetFont('arialunicid0', 'B', 18);
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
        
        $faqconfig = PMF_Configuration::getInstance();
        
        $currentTextColor = $this->TextColor;
        $this->SetTextColor(0,0,0);
        $this->SetY(-25);
        $this->SetFont('arialunicid0', '', 10);
        $this->Cell(0, 10, $PMF_LANG['ad_gen_page'] . ' ' . $this->PageNo() . ' / ' . $this->getAliasNbPages(), 0, 0, 'C');
        $this->SetY(-20);
        $this->SetFont('arialunicid0', 'B', 8);
        $this->Cell(0, 10, "(c) ".date("Y")." ".$faqconfig->get('main.metaPublisher')." <".$faqconfig->get('main.administrationMail').">",0,1,"C");
        if ($this->enableBookmarks == false) {
            $this->SetY(-15);
            $this->SetFont('arialunicid0', '', 8);
            $baseUrl = '/index.php';
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
            $url = PMF_Link::getSystemScheme().$_SERVER['HTTP_HOST'].$baseUrl;
            $urlObj = new PMF_Link($url);
            $urlObj->itemTitle = $this->question;
            $_url = str_replace('&amp;', '&', $urlObj->toString());
            $this->Cell(0, 10, 'URL: '.$_url, 0, 1, 'C', 0, $_url);
        }
        $this->TextColor = $currentTextColor;
    }

    /**
    * Set the specific style according to the (X)HTML tag
    *
    * @param    string
    * @param    boolean
    * @return   void
    * @access   private
    */
    function SetStyle($tag, $enable)
    {
        $this->$tag += ($enable ? 1 : -1);
        $style = "";
        foreach (array("B", "I", "U") as $s) {
            if ($this->$s > 0) {
                $style .= $s;
            }
        }
        $this->SetFont("", $style);
    }

    /**
    * Adds a image
    *
    * @param    string  path to the image
    * @return   void
    * @access   private
    */
    function AddImage($image)
    {
        // Check, if image is stored locally or not
        if ('http' != PMF_String::substr($image, 0, 4)) {
            // Please note that the image must be accessible by HTTP NOT ONLY by HTTPS
             $image = 'http://' . EndSlash($_SERVER['HTTP_HOST']) . $image; 
        }
        // Set a friendly User Agent
        $ua = ini_get('user_agent');
        ini_set('user_agent', 'phpMyFAQ PDF Builder');
        if (!$info = getimagesize($image)) {
            return;
        }
        
        if ($info[0] > 555 ) {
            $w = $info[0] / 144 * 25.4;
            $h = $info[1] / 144 * 25.4;

        } else {
            $w = $info[0] / 72 * 25.4;
            $h = $info[1] / 72 * 25.4;
        }

        // Check for the fpdf image type support
        if (isset($this->mimetypes[$info[2]])) {
            $type = $this->mimetypes[$info[2]];
        } else {
            return;
        }

        $hw_ratio = $h / $w;
        $this->Write(5,' ');

        if ($info[0] > $this->wPt) {
            $info[0] = $this->wPt - $this->lMargin - $this->rMargin;
            if ($w > $this->w) {
                $w = $this->w - $this->lMargin - $this->rMargin;
                $h = $w*$hw_ratio;
            }
        }

        $x = $this->GetX();

        if ($this->GetY() + $h > $this->h) {
            $this->AddPage();
        }

        $y = $this->GetY();
        $this->Image($image, $x, $y, $w, $h, $type);
        $this->Write(5,' ');
        $y = $this->GetY();
        $this->Image($image, $x, $y, $w, $h, $type);

        if ($y + $h > $this->hPt) {
            $this->AddPage();
        } else {
            if ($info[1] > 20 ) {
                $this->SetY($y+$h);
            }
            $this->SetX($x+$w);
        }

        // Unset the friendly User Agent restoring the original UA
        ini_set('user_agent', $ua);
    }
}
