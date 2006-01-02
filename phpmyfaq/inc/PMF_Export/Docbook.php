<?php
/**
* $Id: Docbook.php,v 1.2 2006-01-02 16:51:29 thorstenr Exp $
*
* This is the DocBook XML export class for phpMyFAQ content
*
* @author       Sauer <david_sauer@web.de>
* @since        2005-07-21
* @copyright    (c) 2006 phpMyFAQ Team
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
*/

class DocBook_XML_Export
{
  	
	var $xmlContent;
	var $xmlEntities;
	var $info;
	var $output_dir 	 = "../xml/docbook"; 					// Angeben eines Ablagepfades 
	var	$output_file 	 = "../xml/docbook/docbook.xml";		// Angeben einer XML-Datei im Ablagepfad 
	var $part_counter  = 0;
	var $table_counter = 0;
	var $cell_counter  = 0;
	var $parent = 0;
	var $db;
	var $sectcount = 0;
	var $einid = 0;
	
	/**
     * Konstruktor
     *
     * Diese Funktion bindet die Authentifizierung ein
     *
     * @param   string $rowString
     * @return  string $rowString
     * @author  David Sauer <david_sauer@web.de>
     * @since   2005-07-21
     */   
	function DocBook_XML_Export($DB) {
	
		$this->db = db::db_select($DB["type"]);
		$this->db->connect($DB["server"], $DB["user"], $DB["password"], $DB["db"]);
	}
	
	 /**
     * Unterscheidung HTML-Tags
     *
     * Diese Funktion macht eine Unterscheidung von HTML-Tags und Text
     *
     * @param   string $coise
     * @return  string $coise
     * @author  David Sauer <david_sauer@web.de>
     * @since   2005-07-21
     */   
	function TableImageText(&$coise){
	
		while ($coise != ''){
			$coise = trim($coise);
			if(substr($coise,0,6) == substr('<table',0,6)){
				$this->table($coise);
			}	
			elseif(substr($coise,0,8) == substr('<a title=',0,8)){
				$this->image_one($coise);
			}		
			elseif(substr($coise,0,3) == substr('</a>',0,3)){
				return;
			}
			elseif(substr($coise,0,5) == substr('<img src=',0,5)){
				$this->image_two($coise);
			}	
			elseif(substr($coise,0,3) == substr('<tr>',0,3)){
				$this->row($coise);
			}
			elseif(substr($coise,0,4) == substr('</tr>',0,4)){
				return;
			}
			elseif(substr($coise,0,3) == substr('<td>',0,3)){
				$this->cell($coise);
			}
			elseif (substr($coise,0,4)== substr('</td>',0,4)){
				return;
			}
			else{
			    $this->text($coise);
			}
		}
	}

	 /**
     * Transformiert Table-Tags
     *
     * Diese Funktion transformiert Table-Tags und
     * splittet den String und setz ihn nach der Zählung der Spaltung wieder zusammen
     *
     * @param   string $tableString
     * @return  string $tableString
     * @author  David Sauer <david_sauer@web.de>
     * @since   2005-07-21
     */   
	function table(&$tableString){
		$xmlPart 		= array();
		$tableCols    	= array();
		$tableRow      	= array();
		$row_counter 		= 0; 
 	
  		$tableString = substr($tableString,(strpos($tableString,'>'))+1); 
  	
  		if( substr($tableString,0,7) == '<tbody>'){								
  			$xmlPart[$this->part_counter] = $this->xmlContent;
  			$this->xmlContent ='';
  			$this->part_counter++; 
			$this->table_counter++;
 	  	
			isset($tableCols[$this->table_counter])? $tableCols[$this->table_counter] : $tableCols[$this->table_counter]= 0;	
  		
			$tableString = substr($tableString,7);
  		
  			do{	
 				$tableString = substr($tableString,4);
  				$this->row($tableString); 
  				$tableString = substr($tableString,5);
  				$row_counter++;
  			
  				if($row_counter==1){
 					$tableCols[$this->table_counter] += $this->cell_counter;
 				}
	
  				$tableRow[$this->table_counter] = $row_counter;
    		 }
    		 while (substr($tableString,0,8) != '</tbody>');
  		
    		$tableString = substr($tableString,16);
    		$this->part_counter--;	
			$this->xmlContent = $xmlPart[$this->part_counter]
							   .'<para>'
							   .'<informaltable>'
							   .'<tgroup cols="'.$tableCols[$this->table_counter].'">'
							   .'<tbody>'.$this->xmlContent.'</tbody>'
							   .'</tgroup>'
							   .'</informaltable>'
							   .'</para>';
			$tableCols[$this->table_counter] = 0;
			$this->table_counter--;
		}   
	}
	
	/**
     * Transformation TR-Tags
     *
     * Diese Funktion transformiert TR-Tags und
     * zählt die Spalten
     *
     * @param   string $rowString
     * @return  string $rowString
     * @author  David Sauer <david_sauer@web.de>
     * @since   2005-07-21
     */   
	function row(&$rowString){
		
		$this->cell_counter = 0;
		$this->xmlContent .= '<row>';
		
		while(substr($rowString,0,5)!= '</tr>'){
			$this->cell_counter++;
			$this->cell($rowString);
		}
		
		$this->xmlContent .= '</row>';
		
	}
	
	/**
     * Transformation TD-Tags
     *
     * Diese Funktion transformiert TD-Tags 
     *
     * @param   string $cellString
     * @return  string $cellString
     * @author  David Sauer <david_sauer@web.de>
     * @since   2005-07-21
     */   
	function cell(&$cellString){
	
		$cellString = trim($cellString);
		$cellString = substr($cellString,(strpos($cellString,'>')+1),strlen($cellString));
		$this->xmlContent .='<entry>';
		$this->TableImageText($cellString);
		$this->xmlContent .='</entry>';
		$cellString = substr($cellString,5);
			
	}
	
	/**
     * Bild-Auswertung und Transformation 
     *
     * Diese Funktion selektiert die Bildquelle basierend auf <a title...Tag und
     * transformiert diese
     *
     * @param   string $imageString
     * @return  string $imageString
     * @author  David Sauer <david_sauer@web.de>
     * @since   2005-07-21
     */   
	function image_one(&$imageString){
		
		$selectimg 	= (strpos($imageString,'selectImage')+12);
  		$source 	= substr($imageString,$selectimg);
  		$imgsource 	= substr($source,0,strpos($source,','));
  		$imgleng 	= (strlen($imgsource)-2);
  		$imgsource 	= substr($imgsource,1,$imgleng);
  		$imgsize    = substr($source,$imgleng+$imgleng+6);
 		$imgwidth   = ltrim(substr($imgsize,0,strpos($imgsize,',')));
  	
  		$text = strpos($imageString,'" />')+4;  
  	
  		if(substr($imageString,$text,strpos(substr($imageString,$text),'</a>'))!= ''){
  			$this->info_image($imageString);
  		}
  	
  		$this->xmlContent .='<para>'
  		 	. '<mediaobject>'
            . '<textobject><phrase><!-- insert here the a/@title attribute value --></phrase></textobject>'
  			. '<imageobject>'
  		 	. '<imagedata fileref="http://'.$_SERVER['SERVER_NAME'].'/phpmyfaq/images'.$imgsource.'" width="'.$imgwidth.'" />'
  		 	. '</imageobject>'
  		 	. '</mediaobject>'
  		 	. '</para>';
  		$imageString= substr($imageString,(strpos($imageString,"</a>")+4));
  	}
	
  	/**
     * Bildinformation
     *
     * Diese Funktion transformiert Bildinformationen
     *
     * @param   string $infoImage
     * @return  string $infoImage
     * @author  David Sauer <david_sauer@web.de>
     * @since   2005-07-21
     */   
	function info_image(&$infoImage){
  		$text = strpos($infoImage,'" />')+4;
		$infoImage .='<objectinfo>'
  				  . '<title>'.substr($infoImage,$text,strpos(substr($infoImage,$text),'</a>')).'</title>'
  				  . '</objectinfo>';
	}
	
	/**
     * Bild-Auswertung und Transformation 
     *
     * Diese Funktion selektiert die Bildquelle basierend auf <img...Tag und
     * transformiert diese
     *
     * @param   string $imageString
     * @return  string $imageString
     * @author  David Sauer <david_sauer@web.de>
     * @since   2005-07-21
     */   
	function image_two(&$imageString){
	
		$img_end   = preg_match('/\"(\/(.*?))\"/',$imageString,$matches);
		$source = $matches[1];
		$this->xmlContent .='<para>'
  		 				  .'<mediaobject>'
  		 				  .'<imageobject>'
  		 			  	  .'<imagedata fileref="http://localhost'.$source.'" />'
  		 				  .'</imageobject>'
  		 				  .'</mediaobject>'
  		 				  .'</para>';
  		 $imageString = ltrim(substr($imageString,(strpos($imageString,'/>'))+2));
  	}
	
  	/**
     * schreiben eines Textes
     *
     * Diese Funktion schreibt einen Text
     *
     * @param   string $textString
     * @return  string $textString
     * @author  David Sauer <david_sauer@web.de>
     * @since   2005-07-21
     */   
	function text(&$textString){
		
		if(preg_match('/((<)(.*)(>)?)/',$textString,$matches)){
			
			if(preg_match('/^((?!<)(.*?))((<(\/?(.*)?)>)+)/',$textString,$matches) == true){
				$textStringPart = substr($textString,0,strlen($matches[1]));
				$textString = substr($textString,strlen($matches[1]));
			} else {
				$match = preg_match('/^(<\/?(.*)>)/',$textString,$matchesRemainder);
                if ($match) {
				$textString = substr($textString,strlen($matchesRemainder[0]));
				$textStringPart = '';
                } else {
                    $textStringPart = '';
                    $textString = substr($textString,strlen($textString));
                }
			}
		}
		else{
			$textStringPart = $textString;
			$textString = substr($textString,strlen($textString));
		}
        if (!empty($textStringPart)) {
		$this->xmlContent .='<para>';
		$this->xmlContent .= $textStringPart;
		$this->xmlContent .='</para>';
	}
	}
	
	/**
     * Schreibt transformierten String
     *
     * Diese Funktion schreibt den transformierten String in eine Datei
     *
     * @author  David Sauer <david_sauer@web.de>
     * @since   2005-07-21
     */   
	function write_file(){
		
		if(!is_dir($this->output_dir)){
			mkdir ($this->output_dir, 0777);
		}
		$fp = fopen($this->output_file,'a');									// Erzeugen u öffnen einer XML-Datei zum Schreiben
   		fwrite($fp,$this->xmlContent);													// Schreiben des $XML-Inhaltes
    	fclose($fp);	
    	$this->xmlContent= '';
    }
	
	/**
     * Wandlung Timestamp 
     *
     * Diese Funktion wandelt einen Timestamp in ein Datum
     *
     * @param   string $date
     * @return  string $timestamp
     * @author  David Sauer <david_sauer@web.de>
     * @since   2005-07-21
     */   
	function aktually_date($date){
		
		$offset = 0;
 		$current = strtotime(
 					 substr($date,0,4)."-"
 					.substr($date,4,2)."-"
 					.substr($date,6,2)." "
 					.substr($date,8,2).":"
 					.substr($date,10,2)
 					);
    	$timestamp = $current + $offset;
    	
    return date("Y-m-d H:i", $timestamp);	
	}
	
	/**
     * Löscht gleichnamiges File
     *
     * Diese Funktion löscht ein gleichnamiges File
     * 
     *
     * @param   string $imageString
     * @return  string $imageString
     * @author  David Sauer <david_sauer@web.de>
     * @since   2005-07-21
     */   
	function delete_file(){
		
		if(is_file($this->output_file)){
			unlink($this->output_file);
		}
	}
	

	/**
     * Rubrikendarstellung durch Rekursion 
     *
     * Selektion der Rubriken anhand Parent-ID 
     * und Darstellung mittels durch Rekursion
     *
     * @param   string $parent
     * @return  string $parent
     * @author  David Sauer <david_sauer@web.de>
     * @since   2005-07-21
     */   
	function recursive_category($parent){
	
		$sql = sprintf("SELECT id, name 
        	           FROM 
            	       		%sfaqcategories 
                	   WHERE
                   			 parent_id=%d 
                   		ORDER BY 
                   			 id 
                   		ASC", SQLPREFIX, $parent
   						);
   		$rubrik = $this->db->query($sql);
   		
		if ($this->db->num_rows($rubrik) > 0) {
  			
			while ($row = $this->db->fetch_object($rubrik)) {						
		 		$this->sectcount++;
		   	 
			 	$this->xmlContent.='<sect'.$this->sectcount.'>'
       	 		     			 . '<title>'.$row->name.'</title>'
       	 		  				 . '<para/>';    
         	 	$this ->recursive_article($row->id);
        	 	$this->recursive_category($row->id);
        	 	$this->xmlContent .='</sect'.$this->sectcount.'>';
        	 	$this->sectcount--;
        	} 
    	}
	} 

	/**
     * Artikeldarstellung durch Rekursion 
     *
     * Selektion der Artikel anhand Parent-ID 
     * und Darstellung mittels Rekursion
     *
     * @param   string $parent
     * @return  string $parent
     * @author  David Sauer <david_sauer@web.de>
     * @since   2005-07-21
     */   
	function recursive_article(&$parent){
		
		
		$sql = sprintf('SELECT '.SQLPREFIX.'faqdata.id AS id, '.SQLPREFIX.'faqdata.lang AS lang, '.SQLPREFIX.'faqdata.thema AS thema, '.SQLPREFIX.'faqdata.content AS content FROM '.SQLPREFIX.'faqdata LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang WHERE '.SQLPREFIX.'faqdata.active = \'yes\' AND '.SQLPREFIX.'faqcategoryrelations.category_id ='.$parent.' ORDER BY '.SQLPREFIX.'faqdata.id');
  	    $xmlQuery = $this->db->query( $sql );

  		while($xmlObject = $this->db->fetch_object($xmlQuery)){
   			$this->xmlContent .='<simplesect>'
  						      . '<title>'.$xmlObject->thema.'</title>';
  			$xmlEntry = $xmlObject->content;
  		
  				while($xmlEntry != ''){
  					$xmlEntry = $this->TableImageText(trim(ereg_replace('<br />','',$xmlEntry)));
  				}
  				
  		$this->xmlContent .='</simplesect>';     
   		}		
	}
}