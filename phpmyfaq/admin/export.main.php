<?php
/**
 * $Id: export.main.php,v 1.3 2004-11-05 23:07:27 thorstenr Exp $
 *
 * File:				export.main.php
 * Description:			RSS and FAQ export - main page
 * Author:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Contributor:         Peter Beauvain <pbeauvain@web.de>
 * Date:				2003-04-17
 * Last change:			2004-11-05
 * Copyright:           (c) 2001-2004 phpMyFAQ Team
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
 ******************************************************************************/
?>
	<h2><?php print $PMF_LANG["ad_menu_export"]; ?></h2>
<?php
if (isset($_REQUEST["submit"])) {
	$submit = $_REQUEST["submit"];
	}

if (isset($submit[0])) {
	// XML export
    
}

if (isset($submit[1])) {
	// XHTML export
    
}

if (isset($submit[2])) {
	// Full PDF Export
	define("FPDF_FONTPATH", PMF_ROOT_DIR."/font/");
	require (PMF_ROOT_DIR."/inc/fpdf.php");
	$tree = new Category();
	$arrRubrik = array();
	$arrThema = array();
	$arrContent = array();
	
    class PDF_Bookmark extends FPDF
    {
        var $outlines = array();
        var $OutlineRoot;
        
        function Bookmark($txt, $level = 0, $y = 0)
        {
            if ($y == -1) {
                $y = $this->GetY();
                }
            $this->outlines[] = array("t" => $txt, "l" => $level, "y" => $y, "p" => $this->PageNo());
        }
        
        function _putbookmarks()
        {
            $nb = count($this->outlines);
            if ($nb == 0) {
                return;
                }
            $lru = array();
            $level = 0;
            foreach ($this->outlines as $i=>$o) {
                if ($o['l'] > 0) {
                    $parent = $lru[$o['l']-1];
                    $this->outlines[$i]['parent'] = $parent;
                    $this->outlines[$parent]['last'] = $i;
                    if ($o['l'] > $level) {
                        $this->outlines[$parent]['first'] = $i;
                        }
                    }
                else
                    $this->outlines[$i]['parent']=$nb;
                if($o['l']<=$level and $i>0)
                {
                    //Set prev and next pointers
                    $prev=$lru[$o['l']];
                    $this->outlines[$prev]['next']=$i;
                    $this->outlines[$i]['prev']=$prev;
                }
                $lru[$o['l']]=$i;
                $level=$o['l'];
            }
            //Outline items
            $n=$this->n+1;
            foreach($this->outlines as $i=>$o)
            {
                $this->_newobj();
                $this->_out('<</Title '.$this->_textstring($o['t']));
                $this->_out('/Parent '.($n+$o['parent']).' 0 R');
                if(isset($o['prev']))
                    $this->_out('/Prev '.($n+$o['prev']).' 0 R');
                if(isset($o['next']))
                    $this->_out('/Next '.($n+$o['next']).' 0 R');
                if(isset($o['first']))
                    $this->_out('/First '.($n+$o['first']).' 0 R');
                if(isset($o['last']))
                    $this->_out('/Last '.($n+$o['last']).' 0 R');
                $this->_out(sprintf('/Dest [%d 0 R /XYZ 0 %.2f null]',1+2*$o['p'],($this->h-$o['yes'])*$this->k));
                $this->_out('/Count 0>>');
                $this->_out('endobj');
            }
            //Outline root
            $this->_newobj();
            $this->OutlineRoot=$this->n;
            $this->_out('<</Type /Outlines /First '.$n.' 0 R');
            $this->_out('/Last '.($n+$lru[0]).' 0 R>>');
            $this->_out('endobj');
        }
    
    function _putresources()
    {
        parent::_putresources();
        $this->_putbookmarks();
    }
    
    function _putcatalog()
    {
        parent::_putcatalog();
        if(count($this->outlines)>0)
        {
            $this->_out('/Outlines '.$this->OutlineRoot.' 0 R');
            $this->_out('/PageMode /UseOutlines');
        }
    }
    }


class PDF extends PDF_Bookmark {
	var $B;
	var $I;
	var $U;
	var $SRC;
	var $HREF;
	var $PRE;
	var $CENTER;
	var $issetfont;
	var $issetcolor;
	
	// PDF-Header
	function Header() {
		global $rubrik, $thema, $tree;
		$title = stripslashes($tree->categoryName[$rubrik]["name"]).": ".stripslashes($thema);
		$this->SetFont("Arial", "B", 14);
		$this->SetDrawColor(182,195,203);
		$this->SetFillColor(239,239,239);
		$this->SetTextColor(0,0,0);
		$this->SetLineWidth(1);
		$this->MultiCell(0, 6, $title, 1, 1, "C", 1);
		$this->Ln(8);
		$this->Bookmark(makeShorterText($thema, 5));
		
		}
	
	// PDF-Footer
	function Footer() {
		global $PMF_LANG, $PMF_CONF, $rubrik;
    	$this->SetY(-25);
    	$this->SetFont("Arial", "I", 10);
    	$this->SetTextColor(0,0,0);
    	$this->Cell(0, 10, $PMF_LANG["ad_gen_page"]." ".$this->PageNo()."/{nb}",0,0,"C");
		$this->SetY(-20);
    	$this->SetFont("Arial", "B", 8);
		$this->Cell(0, 10, "(c) ".date("Y")." ".$PMF_CONF["metaPublisher"]." ",0,1,"C");
	}
	

function txtentities($html){
    $trans = get_html_translation_table(HTML_ENTITIES);
    $trans = array_flip($trans);
    return strtr($html, $trans);
}

	
	// PDF Konstruktor
	function PDF ($orientation = "P", $unit = "mm", $format = "A4") {
		$this->FPDF($orientation, $unit, $format);
		$this->B = 0;
		$this->I = 0;
		$this->U = 0;
		$this->PRE = 0;
		$this->CENTER = 0;
		$this->SRC = "";
		$this->HREF = "";
		$this->issetfont=false;
    		$this->issetcolor=false;
		}
	
	// HTML-Parser
	function WriteHTML($html) {
		//$html = str_replace('&quot;','"',$html);
		//$html = str_replace('&lt;','‹',$html); //sonst wird HTML-CODE innerhalb des [pre] Tags im PDF unterschlagen
		//$html = str_replace('&gt;','›',$html); // '›' = chr155,'‹' = chr139 habe keine besseren Zeichen gefunden
		//$html = str_replace('&nbsp;',' ',$html);
		//$html = str_replace('&amp;','&',$html);
		$html = str_replace("\n", "<br />", $html);
		$html = str_replace('images/','../images/',$html);    
		$a = preg_split("/<(.*)>/U", $html, -1, PREG_SPLIT_DELIM_CAPTURE);
		foreach($a as $i => $e) {
			if ($i % 2 == 0) {
				if ($this->HREF) {
					$this->PutLink($this->HREF,$e);
				        }
				
				elseif ($this->SRC) {
					$this->AddImage($this->SRC);
                                        $this->SRC = "";
                                     }
				elseif ($this->CENTER) {
					$this->MultiCell(0, 1, $e, 0, "L");
					}
            	else {
                	$this->Write(5,$e);
					}
        		}
        	else {
				if ($e{0} == "/") {
					$this->CloseTag(strtoupper(substr($e,1)));
					}
				else {
					$a2 = explode(" ",$e);
					$tag = strtoupper(array_shift($a2));
					$attr = array();
					foreach ($a2 as $v) {
						if (ereg('^([^=]*)=["\']?([^"\']*)["\']?$',$v,$a3)) {
                        	$attr[strtoupper($a3[1])]=$a3[2];
							}
						}
                	$this->OpenTag($tag,$attr);
            		}
        		}
    		}
		}
	
	function OpenTag($tag, $attr) {
		
		if ($tag == "B" or $tag == "I" or $tag == "U") {
			$this->SetStyle($tag,true);
			}
		/* Text in blau, Courier wenn CODE */
		if ($tag == "PRE") {
			$this->SetFont("Courier", "", 10);
			$this->SetTextColor(0,0,255);
			}
		if ($tag == "A") {
			$this->HREF = $attr["HREF"];
			}
		if ($tag =="FONT") {
                if (isset($attr['COLOR']) and $attr['COLOR']!='') {
                $color=hex2dec($attr['COLOR']);
                $this->SetTextColor($color['R'],$color['G'],$color['B']);
                $this->issetcolor=true;
            			}
		}
		
		if ($tag == "IMG") {
			$this->SRC = $attr["SRC"];
			}
		if ($tag == "DIV") {
            if ($attr["ALIGN"] != "justify") {
                $this->CENTER = $attr["ALIGN"];
                }
			}
         if ($tag == "UL") {
            $this->SetLeftMargin($this->lMargin+10);
            }
         if ($tag == "LI") {
            $this->Ln();
            $this->SetX($this->GetX()-10);
            $this->Cell(10,5,chr(149),0,0,'C');
            }
		if ($tag == "BR") {
			$this->Ln(5);
			}
		}
	
	function CloseTag($tag) {
		
		if ($tag == "B" or $tag == "I" or $tag == "U") {
			$this->SetStyle($tag,false);
			}
		if ($tag == "PRE") {
			$this->SetFont("Arial", "", 12);
			$this->SetTextColor(0,0,0);
			}
		if ($tag == "A") {
			$this->HREF = "";
			}
		if ($tag == "DIV") {
			$this->CENTER = "";
		}
		 if($tag=='FONT'){
        if ($this->issetcolor==true) {
            $this->SetTextColor(0);
        }
        if ($this->issetfont) {
            $this->SetFont('arial');
            $this->issetfont=false;
        }
    }
         if ($tag == "UL") {
            $this->SetLeftMargin($this->lMargin-10);
            $this->Ln();
            }
		}
	
	function SetStyle($tag,$enable) {
		
		$this->$tag += ($enable ? 1 : -1);
		$style = "";
		foreach (array("B", "I", "U") as $s) {
			if ($this->$s > 0) {
				$style .= $s;
				}
			}
		$this->SetFont("", $style);
			}
	
	function PutLink($URL, $txt) {
		$this->SetTextColor(0, 0, 255);
		$this->SetStyle("U", true);
		$this->Write(5, $txt, $URL);
		$this->SetStyle("U", false);
		$this->SetTextColor(0);
		}
	
	function AddImage($image) {
		$image = dirname(PMF_ROOT_DIR).$image;
        $info = GetImageSize("$image");
        if ($info[0] > 555 ){
        $w = $info[0] / 144 * 25.4;
        $h = $info[1] / 144 * 25.4;
        }
        else {
        $w = $info[0] / 72 * 25.4;
        $h = $info[1] / 72 * 25.4;
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
	 $this->Image($image, $x, $y, $w, $h);
         $this->Write(5,' ');
		//$this->Ln();
	
		$y = $this->GetY();
        $this->Image($image, $x, $y, $w, $h);
        if ($y + $h > $this->hPt) {
            $this->AddPage();
            }
        else {
         if ( $info[1] > 20 ) {
           
            $this->SetY($y+$h);
            }
            $this->SetX($x+$w);
            
        }
       
		}
	
	}

	
	$result = $db->query("SELECT rubrik, thema, content, datum, author FROM ".SQLPREFIX."faqdata WHERE active = 'yes' ORDER BY rubrik, id");
	if ($db->num_rows($result) > 0) {
		$i = 0;
		while ($row = $db->fetch_object($result)) {
			$arrRubrik[$i] = $row->rubrik;
			$arrThema[$i] = stripslashes($row->thema);
			$arrContent[$i] = $row->content;
			$arrDatum[$i] = $row->datum;
			$arrAuthor[$i] = $row->author;
			$i++;
			}
		}
	
	$pdf = new PDF();
	$pdf->Open();
	$pdf->AliasNbPages();
	$pdf->SetDisplayMode("real"); 
	
	foreach ($arrContent as $key => $value) {
		$rubrik = $arrRubrik[$key];
		$thema = $arrThema[$key];
		$date =  $arrDatum[$key];
		$author = $arrAuthor[$key];
		$pdf->AddPage();
		$pdf->SetFont("Arial", "", 12);
    	$pdf->WriteHTML(unhtmlentities(stripslashes($value)));
    	}
	
	$pdfFile = PMF_ROOT_DIR."/pdf/faq.pdf";
	$pdf->Output($pdfFile);
	
	print "<p>".$PMF_LANG["ad_export_full_faq"]."<a href=\"../pdf/faq.pdf\" target=\"_blank\">".$PMF_CONF["title"]."</a></p>";
    }
if (!emptyTable(SQLPREFIX."faqdata")) {
?>
	<form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="post">
	<input type="hidden" name="aktion" value="export" />
	<p><strong>XML export</strong></p>
    <p align="center"><input class="submit" type="submit" name="submit[0]" value="XML export" /></p>
	<p><strong>XHTML export</strong></p>
    <p align="center"><input class="submit" type="submit" name="submit[1]" value="XHTML export" /></p>
	<p><strong><?php print $PMF_LANG["ad_export_pdf"]; ?></strong></p>
    <p align="center"><input class="submit" type="submit" name="submit[2]" value="<?php print $PMF_LANG["ad_export_generate_pdf"]; ?>" /></p>
	</form>
<?php
    }
else {
    print $PMF_LANG["err_noArticles"];
    }
