<?php
/**
* $Id: functions.php,v 1.33 2004-12-25 06:11:14 thorstenr Exp $
*
* This is the main functions file!
*
* @auhtor       Thorsten Rinne <thorsten@phpmyfaq.de>
* @auhtor       Matthias Sommerfeld <phlymail@phlylabs.de>
* @auhtor       Bastian Pöttner <bastian@poettner.net>
* @auhtor       Meikel Katzengreis <meikel@katzengreis.com>
* @since        2001-02-18
* @copyright    (c) 2001-2004 phpMyFAQ Team
*
* Portions created by Matthias Sommerfeld are Copyright (c) 2001-2004 blue
* birdy, Berlin (http://bluebirdy.de). All Rights Reserved.
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

/**
* Function to get a pretty formatted output of a variable
*
* NOTE: Just for debugging!
*
* @param    string
* @return   void
* @access   public
* @since    2004-11-27
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
*/
function pmf_dump($var)
{
    print '<pre>';
    print_r($var);
    print '</pre>';
}

/*
 * This function displays the <select> box for the available languages | @@ Thorsten 2003-12-12
 * Last update: @@ Thorsten 2004-08-21
 */
function selectLanguages($default)
{
    global $languageCodes;
    $search = array("language_", ".php");
    $output = "<select class=\"language\" name=\"language\" size=\"1\">\n";
	if ($dir = @opendir("lang/")) {
        while (FALSE !== ($file = @readdir($dir))) {
            if ($file != "." && $file != ".." && !is_dir($file)) {
                $languageArray[] = strtoupper(str_replace($search, "", trim($file)));
            }
        }
        closedir($dir);
        
        foreach ($languageArray as $cc) {
            $languages[strtolower($cc)] = $languageCodes[$cc];
        }
        
        asort($languages);
        reset($languages);
        
        foreach ($languages as $lang => $cc) {
            $output .= "\t\t<option value=\"".$lang."\"";
            if ($lang == $default) {
            	$output .= " selected=\"selected\"";
            }
            $output .=  ">".$cc."</option>\n";
        }
    } else {
		$output .= "\t\t<option value=\"en\">english</option>";
    }
    $output .= "</select>\n";
    return $output;
}

/*
 * Funktion für Umwandlung der Artikelnamen | @@ Thorsten, 2002-08-28
 * Last Update: @@ Thorsten, 2004-05-07
 */
function getThema($id, $lang)
{
	global $db, $PMF_LANG;
	$query = "SELECT ".SQLPREFIX."faqdata.thema FROM ".SQLPREFIX."faqdata WHERE id = ".$id." AND lang = '".$lang."'";
	$result = $db->query($query);
	if ($db->num_rows($result) > 0) {
		while ($row = $db->fetch_object($result)) {
			$output = $row->thema;
        }
    } else {
		$output = $PMF_LANG["no_cats"];
    }
	return $output;
}

/*
 * Funktion für die Datumsumwandlung nach ISO 8601 | @@ Thorsten, 2001-04-30
 * Last Update: @@ Thorsten, 2004-07-27
 */
function makeDate($date)
{
    global $PMF_CONST;
    $offset = (60 * 60) * ($PMF_CONST["timezone"] / 100);
    $current = strtotime(substr($date,0,4)."-".substr($date,4,2)."-".substr($date,6,2)." ".substr($date,8,2).":".substr($date,10,2));
    $timestamp = $current + $offset;
    return date("Y-m-d H:i", $timestamp);
}

/**
* Returns the number of activated records
*
* @return   int
* @access   public
* @since    2002-08-23
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
*/
function generateNumberOfArticles()
{
	global $db;
	$num = $db->num_rows($db->query("SELECT id FROM ".SQLPREFIX."faqdata WHERE active = 'yes'"));
	if ($num > 0) {
		return $num;
    } else {
        return 0;
    }
}

/**
* This function returns all records from one category
*
* @param    int     the category id
* @return   string
* @access   public
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
* @since    2002-08-27
*/
function printThemes($category)
{
	global $db, $sids, $PMF_LANG, $PMF_CONF;
	$seite = 1;
	$output = "";
    
	if (isset($_REQUEST["seite"])) {
		$seite = $_REQUEST["seite"];
		}
    
	$numResult = $db->query("SELECT id FROM ".SQLPREFIX."faqdata WHERE active = 'yes' AND rubrik = ".$category);
	$num = $db->num_rows($numResult);
	$pages = ceil($num / $PMF_CONF["numRecordsPage"]);
    
	if ($seite == 1) {
		$first = 0;
		}
	else {
		$first = ($seite * $PMF_CONF["numRecordsPage"]) - $PMF_CONF["numRecordsPage"];
		}
    
	$result = $db->query('SELECT '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqdata.thema, '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqvisits.visits FROM '.SQLPREFIX.'faqdata 
LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang LEFT JOIN '.SQLPREFIX.'faqvisits ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqvisits.id AND '.SQLPREFIX.'faqvisits.lang = '.SQLPREFIX.'faqdata.lang WHERE '.SQLPREFIX.'faqdata.active = "yes" AND '.SQLPREFIX.'faqcategoryrelations.category_id ='.$category.' ORDER BY '.SQLPREFIX.'faqdata.id');
	$num = $db->num_rows($result);
	
	if ($num > 0) {
		if ($pages > 1) {
			$output .= "<p><strong>".$PMF_LANG["msgPage"].$seite." ".$PMF_LANG["msgVoteFrom"]." ".$pages.$PMF_LANG["msgPages"]."</strong></p>";
			}
		$output .= "<ul class=\"phpmyfaq_ul\">\n";
		$counter = 0;
		$displayedCounter = 0;
		while (($row = $db->fetch_object($result)) && $displayedCounter < $PMF_CONF['numRecordsPage']) {
			$counter ++;
			if ($counter <= $first) {
				next;
			}
			$displayedCounter++;
			
            if (empty($row->visits)) {
                
				$visits = "0";
            } else {
                
				$visits = $row->visits;
            }
            
            if (isset($PMF_CONF["mod_rewrite"])) {
                
                $output .= "\t<li><a href=\"".$row->rubrik."_".$row->id."_".$row->lang.".html\">".stripslashes($row->thema)."</a> [".$row->lang."]<br /><div class=\"little\">(".$visits." ".$PMF_LANG["msgViews"].")</div></li>\n";
            } else {
                
                $output .= "\t<li><a href=\"".$_SERVER["PHP_SELF"]."?".$sids."action=artikel&amp;cat=".$row->rubrik."&amp;id=".$row->id."&amp;artlang=".$row->lang."\">".stripslashes($row->thema)."</a> [".$row->lang."]<br /><div class=\"little\">(".$visits." ".$PMF_LANG["msgViews"].")</div></li>\n";
            }
        }
        $output .= "</ul>\n";
    } else {
		$output = $PMF_LANG["err_noArticles"];
	}
    
    if ($pages > 1) {
        $output .= "<p align=\"center\"><strong>";
        $previous = $seite - 1; 
        $next = $seite + 1;
        
        if ($previous != 0) {
            
            if (isset($PMF_CONF["mod_rewrite"])) {
                
                $output .= "[ <a href=\"category".$category."_".$previous.".html\">".$PMF_LANG["msgPrevious"]."</a> ]";
            } else {
                
                $output .= "[ <a href=\"".$_SERVER["PHP_SELF"]."?".$sids."action=show&amp;cat=".$category."&amp;seite=".$previous."\">".$PMF_LANG["msgPrevious"]."</a> ]";
            }
        }
        
        $output .= " ";
        
        if ($next <= $pages) {
            
            if (isset($PMF_CONF["mod_rewrite"])) {
                
                $output .= "[ <a href=\"category".$category."_".$next.".html\">".$PMF_LANG["msgNext"]."</a> ]";
            } else {
                
                $output .= "[ <a href=\"".$_SERVER["PHP_SELF"]."?".$sids."action=show&amp;cat=".$category."&amp;seite=".$next."\">".$PMF_LANG["msgNext"]."</a> ]";
            }
        }
        
        $output .= "</strong></p>";
    }
	return $output;
}

/*
 * Check, ob eine weitere Sprache eines Artikels vorhanden ist | @@ Thorsten, 2003-03-17
 * Last Update: @@ Thorsten, 2003-03-17
 */
function check4Language($id)
{
	global $db;
	$result = $db->query("SELECT lang FROM ".SQLPREFIX."faqdata WHERE id = ".$id);
	if ($db->num_rows($result) > 0) {
		while ($row = $db->fetch_object($result)) {
			$output[] = $row->lang;
			}
		}
	return $output;
}

/*
 * Funktion fuer Umwandlung der <br /> in \n | @@ Thorsten, 2001-07-01
 * copyright Johannes Frömter von http://www.koehntopp.de/php
 * Last Update: @@ Thorsten - 2002-09-27
 */
function br2nl($str)
{
	return preg_replace("=<br(>|([\s/][^>]*)>)\r?\n?=i", "\n", $str);
}

/*
 * Funktion für Umwandlung der E-Mailadressen (Spam!) | @@ Thorsten, 2003-04-17
 * Last Update: @@ Thorsten, 2003-09-22
 */
function safeEmail($email)
{
	return str_replace(array('@', '.'), array('_AT_', '_DOT_'), $email);
}

/*
 * Funktion für Reload-Sperre beim Voting | @@ Thorsten, 2003-05-15
 * Last Update: @@ Thorsten, 2003-05-15
 */
function votingCheck($id, $ip)
{
	global $db;
	$timeNow = (time() - 300);
	if ($db->num_rows($db->query("SELECT id FROM ".SQLPREFIX."faqvoting WHERE artikel = ".$id." AND (ip = '".$ip."' AND datum > ".$timeNow.")"))) {
		return FALSE;
		}
	return TRUE;
}

/*
 * Funktion für IP-Bann | @@ Thorsten, 2003-06-06
 * Last Update: @@ Thorsten, 2003-11-22
 */
function IPCheck($ip)
{
	global $PMF_CONF;
	$arrBannedIPs = explode(" ", $PMF_CONF["bannedIP"]);
	foreach ($arrBannedIPs as $oneIP) {
    	if ($oneIP == $ip) {
			return FALSE;
			}
		}
	return TRUE;
}



/******************************************************************************
 * Funktionen für das Usertracking
 ******************************************************************************/

/*
 * Trackt den User und zeichnet die Bewegungen auf | @@ Bastian, 2001-02-18
 * Last Update: @@ Thorsten, 2004-07-18
 */
function Tracking($action, $id)
{
	global $db, $PMF_CONF, $sid;
	if (isset($PMF_CONF["tracking"])) {
		if (isset($_GET["sid"])) {
			$sid = $_GET["sid"];
        }
		if (isset($_COOKIE["sid"])) {
			$sid = $_COOKIE["sid"];
        }
		if ($action == "oldSession") {
			$sid = "";
        }
		if (!isset($sid)) {
            $sid = $db->nextID(SQLPREFIX."faqsessions", "sid");
			$db->query("INSERT INTO ".SQLPREFIX."faqsessions (sid, ip, time) VALUES (".$sid.", '".$_SERVER["REMOTE_ADDR"]."', ".time().")");
        }
		$fp = @fopen("./data/tracking".date("dmY"), "a+b");
        if ($fp) {
    		$flanz = "0";
    		while (!flock($fp, LOCK_EX) && $flanz < 6) {
    			wait(500);
    			$flanz++;
            }
    		if ($flanz >= 6) {
    			fclose($fp);
            } elseif (!empty($_SERVER["HTTP_REFERER"])) {
                if (!isset($_SERVER["QUERY_STRING"])) {
                    $_SERVER["QUERY_STRING"] = "";
                }
    			fputs($fp, $sid.";".str_replace(";", ",",$action).";".$id.";".$_SERVER["REMOTE_ADDR"].";".str_replace(";", ",", $_SERVER["QUERY_STRING"]).";".str_replace(";", ",", $_SERVER["HTTP_REFERER"]).";".str_replace(";", ",", $_SERVER["HTTP_USER_AGENT"]).";".time().";\n");
    			flock($fp, LOCK_UN);
    			fclose($fp);
            }
        }
    }
}

/*
 * A OS independent function like usleep | @@ Thorsten 2004-05-30
 * Last Update: @@ Thorsten 2004-05-30
 */
function wait($usecs)
{
    $temp = gettimeofday();
    $start = (int)$temp["usec"];
    while(1) {
        $temp = gettimeofday();
        $stop = (int)$temp["usec"];
        if ($stop - $start >= $usecs) {
            break;
            }
        }
}

/*
 * Testet ob die SID zu der IP passt |  @@ Bastian, 2001-04-07
 * Last Update: @@ Thorsten, 2003-03-06
 */
function CheckSID($sid, $ip)
{
	global $db;
	if ($db->num_rows($db->query("SELECT sid FROM ".SQLPREFIX."faqsessions WHERE sid = ".$sid." AND ip = '".$ip."' AND time > ".(time()-86400))) < 1) {
		Tracking("oldSession",$sid);
		}
}

/*
 * Funktion für Anzeige von Usern, die online sind | @@ Thorsten, 2001-05-02
 * Last Update: @@ Thorsten, 2004-07-17
 */
function userOnline()
{
	global $db, $PMF_CONF;
    if (isset($PMF_CONF["tracking"])) {
		$timeNow = (time() - 300);
		$result = $db->query("SELECT count(sid) FROM ".SQLPREFIX."faqsessions WHERE time > ".$timeNow." GROUP BY ip");
		if (isset($result)) {
			return $db->num_rows($result);
	        }
		}
    else {
        return 0;
        }
}



/******************************************************************************
 * Funktionen für die Startseite
 ******************************************************************************/

/*
 * Function for generating the FAQ news | @@ Thorsten, 2002-08-23
 * Last Update: @@ Thorsten, 2004-08-11
 */
function generateNews()
{
	global $db, $PMF_LANG, $PMF_CONF;
	$counter = 0;
	$result = $db->query("SELECT datum, header, artikel, link, linktitel, target FROM ".SQLPREFIX."faqnews ORDER BY datum desc");
	$output = "";
	if ($db->num_rows($result) > 0) {
		while (($row = $db->fetch_object($result)) && $counter < $PMF_CONF["numNewsArticles"]) {
			$counter++;
			$output .= "<h3>".$row->header."</h3>\n<div class=\"block\"><span class=\"date\">".makeDate($row->datum)."</span>".stripslashes($row->artikel)."\n";
            if ($row->link != "") {
    		    $output .= "<br />Info: <a href=\"http://".$row->link."\" target=\"_".$row->target."\">".$row->linktitel."</a>\n";
    		    }
		    $output .=  "</div>\n";
		    }
        return $output;
	    }
    else {
        return $PMF_LANG["msgNoNews"];
		}
}

/**
* This function generates the Top Ten with the mosted viewed records
*
* @return   string
* @access   public
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
* @since    2002-05-07
*/
function generateTopTen()
{
	global $db, $sids, $PMF_LANG;
	$result = $db->query('SELECT DISTINCT '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqdata.thema, '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqvisits.visits FROM '.SQLPREFIX.'faqvisits, '.SQLPREFIX.'faqdata LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang WHERE '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqvisits.id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqvisits.lang AND '.SQLPREFIX.'faqdata.active = "yes" ORDER BY '.SQLPREFIX.'faqvisits.visits DESC');
    
	$output = "";
	if ($db->num_rows($result) > 0) {
		$i = 1;
		while (($row = $db->fetch_object($result)) && $i <= 10) {
			$output .= "<tr>\n\t<td>\n";
            
            if (isset($PMF_CONF["mod_rewrite"])) {
                
                $output .= "\t<strong>[".$i.".] ".$row->visits." ".$PMF_LANG["msgViews"].":</strong><br /><a href=\"".$row->category_id."_".$row->id."_".$row->lang.".html\">".stripslashes(makeShorterText($row->thema, 8))."</a>\n";
            } else {
                
                $output .= "\t<strong>[".$i.".] ".$row->visits." ".$PMF_LANG["msgViews"].":</strong><br /><a href=\"".$_SERVER["PHP_SELF"]."?".$sids."action=artikel&amp;cat=".$row->category_id."&amp;id=".$row->id."&amp;artlang=".$row->lang."\">".stripslashes(makeShorterText($row->thema, 8))."</a>\n";
            }
            
			$output .= "\t</td>\n\t</tr>\n";
			$i++;
		}
	} else {
		$output = "<tr><td>".$PMF_LANG["err_noTopTen"]."</td></tr>\n";
	}
	return $output;
}

/**
* This function generates the list with the five latest published records
*
* @return   string
* @access   public
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
* @since    2002-05-07
*/
function generateFiveNewest()
{
	global $db, $sids, $PMF_LANG;
	$result = $db->query('SELECT DISTINCT '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.thema, '.SQLPREFIX.'faqdata.datum, '.SQLPREFIX.'faqvisits.visits FROM '.SQLPREFIX.'faqvisits, '.SQLPREFIX.'faqdata LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang WHERE '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqvisits.id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqvisits.lang AND '.SQLPREFIX.'faqdata.active = "yes" ORDER BY '.SQLPREFIX.'faqdata.datum DESC');
	
	if ($num = $db->num_rows($result) > 0) {
		$output = "";
		$i = 0;
		while (($row = $db->fetch_object($result)) && $i < 5 ) {
			$output .= "\t\t<tr>\n";
			$output .= "\t\t\t<td nowrap=\"nowrap\">".makeDate($row->datum)."</td>\n";
            
            if (isset($PMF_CONF["mod_rewrite"])) {
                
                $output .= "\t\t\t<td><a href=\"".$row->category_id."_".$row->id."_".$row->lang.".html\">".stripslashes(makeShorterText($row->thema, 8))."</a></td>\n";
            } else {
                
                $output .= "\t\t\t<td><a href=\"".$_SERVER["PHP_SELF"]."?".$sids."action=artikel&amp;cat=".$row->category_id."&amp;id=".$row->id."&amp;artlang=".$row->lang."\">".stripslashes(makeShorterText($row->thema, 8))."</a></td>\n";
            }
			$output .= "\t\t\t<td nowrap=\"nowrap\">".$row->visits." ".$PMF_LANG["msgViews"]."</td>\n";
			$output .= "\t\t</tr>\n";
			$i++;
        }
    } else {
		$output = "<tr class=\"fivenewest\"><td>".$PMF_LANG["err_noTopTen"]."</td></tr>\n";
    }
	return $output;
}

/*
 * Kürzt einen String auf eine bestimmte Anzahl von Wörtern | @@ Thorsten - 2002-08-26
 * Last Update: @@ Thorsten, 2004-06-09
 */
function makeShorterText($str, $char)
{
	$str = ereg_replace("[[:space:]]+", " ", $str);
	$arrStr = explode(" ", $str);
	$shortStr = "";
    $num = count($arrStr);
	if ($num > $char) {
		for ($j = 0; $j <= $char; $j++) {
			$shortStr .= $arrStr[$j]." ";
			}
		$shortStr .= "...";
		}
	else {
		$shortStr = $str;
		}
	return $shortStr;
}



/******************************************************************************
 * Funktionen für Artikelseiten
 ******************************************************************************/

/*
 * Funktion für das Zählen der Visits | @@ Bastian, 2001-02-15
 * Last Update: @@ Thorsten, 2004-07-17
 */
function logViews($myid, $lang)
{
	global $db;
	$nVisits = "0";
	$heute = time();
	if ($result = $db->query ("SELECT visits FROM ".SQLPREFIX."faqvisits WHERE id = ".$myid." AND lang = '".$lang."'")) {
		list($nVisits) = $db->fetch_row($result);
		}
	if ($nVisits == "0" || $nVisits == "") {
		$db->query ("INSERT INTO ".SQLPREFIX."faqvisits (id, lang, visits, last_visit) VALUES (".$myid.", '".$lang."', '1', ".$heute.")");
		}
	else {
		
		$db->query ("UPDATE ".SQLPREFIX."faqvisits SET visits = visits+1, last_visit = '".$heute."' WHERE id = ".$myid." AND lang = '".$lang."'");
		}
}

/*
 * Macht an den String nen / dran, falls keiner da ist
 * @@ Bastian, 2002-01-06
 */
function EndSlash($string)
{
	if (substr($string, strlen($string)-1, 1) != "/" ) {
		$string .= "/";
		}
	return $string;	
}

/*
 * Gibt die Votings des Artikels aus | @@ Thorsten - 2002-08-29
 * Last Update: @@ Thorsten, 2004-08-21
 */
function generateVoting($id)
{
	global $db, $PMF_LANG;
	$result = $db->query("SELECT vote, usr FROM ".SQLPREFIX."faqvoting WHERE artikel = ".$id);
	if ($db->num_rows($result) > 0) {
		list($vote, $user) = $db->fetch_row($result);
		return " ".round(($vote/$user),2)." ".$PMF_LANG["msgVoteFrom"]." 5 (".$user." ".$PMF_LANG["msgVotings"].")";
		}
	else {
		return " 0 ".$PMF_LANG["msgVoteFrom"]." 5 (0 ".$PMF_LANG["msgVotings"].")";
		}
}

/*
 * Gibt die Kommentare zum Artikel aus | @@ Thorsten - 2002-08-29
 * Last Update: @@ Thorsten, 2004-10-22
 */
function generateComments($id)
{
	global $db, $PMF_LANG;
	
	$result = $db->query("SELECT usr, email, comment FROM ".SQLPREFIX."faqcomments WHERE id = ".$id);
	$output = "";
	if ($db->num_rows($result) > 0) {
		while ($row = $db->fetch_object($result)) {
			$output .= "<p class=\"comment\">\n";;
			$output .= "<strong>".$PMF_LANG["msgCommentBy"]."<a href=\"mailto:".safeEmail($row->email)."\">".$row->usr."</a>:</strong>\n";
			$output .= "<br />".ereg_replace("&lt;br /&gt;","<br />",stripslashes(htmlspecialchars($row->comment)))."\n</p>";
			}
		}
	return $output;
}

/*
 * Funktion zum Entfernen von <br /> in Abschnitten zwischen <pre> | @@ Meikel Katzengreis, 2003-02-21
 * Last Update: @@ Thorsten, 2003-07-23
 */
function pre_core ($text)
{
	$text = preg_replace("=<br(>|([\s/][^>]*)>)\r?\n?=i", "\n", $text[1]);
    return $text;
}

/*
 * Funktion zum Entfernen von HTML-Tags bis auf <strong>, <em>, <u>, und <a> | @@ Thorsten, 2003-02-23
 * Last Update: @@ Thorsten, 2004-10-31
 */
function safeHTML($html)
{
	$html = stripslashes($html);
	$html = strip_tags($html, "<strong><em><i><u><a><br>");
	return $html;
}

/*
 * Funktion wandelt HTML Entities zurück | @@ Thorsten, 2003-03-27
 * Last Update: @@ Thorsten, 2003-03-27
 */
function unhtmlentities($html)
{
	$translation_table = get_html_translation_table (HTML_ENTITIES);
	$translation_table = array_flip ($translation_table);
	$content = strtr ($html, $translation_table);
	return preg_replace('/\&\#([0-9]+)\;/me', "chr('\\1')", $content);
}

/*
 * Syntax check for e-mails | @@ Thorsten, 2004-01-19
 * Last Update: @@ Thorsten, 2004-06-27
 */
function checkEmail($sender)
{
    global $IDN;
    $sender = $IDN->decode($sender);
    $pattern =  "#^[-!\#$%&\"*+\\./\d=?A-Z^_|'a-z{|}~]+".
                "@".
                "[-!\#$%&\"*+\\./\d=?A-Z^_|'a-z{|}~]+\.".
                "[-!\#$%&\"*+\\./\d=?A-Z^_|'a-z{|}~]+$#";
    if (isset($sender) && preg_match($pattern, $sender)) {
        return TRUE;
        }
    else {
        return FALSE;
        }
}

/*
 * Decode MIME header elements in e-mails | @@ Matthias Sommerfeld
 * (c) 2001-2004 blue birdy, Berlin (http://bluebirdy.de)
 * used with permission
 * Last Update: @@ Thorsten, 2004-07-17
 */
function encode_iso88591($coded = "", $cmode = "g")
{
    if ("g" == $cmode) {
        $coded = str_replace(" ", "_", quoted_printable_encode($coded));
        $zeilen = explode("\r\n", $coded);
        $coded = "";
        foreach ($zeilen as $key => $value) {
            if (!$value) {
                continue;
                }
            if ($key > 0) {
                $coded .= "\t";
                }
            $coded .= '=?iso-8859-1?Q?'.$value.'?='."\r\n";
            }
        return rtrim($coded);
        }
    elseif ("@" == $cmode) {
        $zeilen = explode("\r\n", $coded);
        $coded = "";
        foreach ($zeilen as $key => $value) {
            if (!$value) {
                continue;
                }
            if ($key > 0) {
                $coded .= "\t";
                }
            unset ($words);
            $words = explode(" ", $value, 2);
            foreach ($words as $k => $word) {
                if (preg_match("/[\x80-\xff]/", $word) && preg_match("/\(|\)/", $word)) {
                    $words[$k] = preg_replace
                            ("/^(\()?([^\)]+)(\))?$/ie"
                            ,"'(=?iso-8859-1?Q?'.rtrim(quoted_printable_encode(str_replace(' ', '_', '\\2'))).'?=)'"
                            ,$word
                            );
                    }
                }
            $coded .= join(" ", $words)."\r\n";
            }
        return rtrim($coded);
        }
    else {
        $zeilen = explode("\r\n", $coded);
        $coded = '';
        foreach ($zeilen as $key => $value) {
            if (!$value) {
                continue;
                }
            if ($key > 0) {
                $coded .= "\t";
                }
            unset ($words);
            $words = explode(" ", $value);
            foreach ($words as $k => $word) {
                if (preg_match('/[\x80-\xff]/', $word)) {
                    $words[$k] = '=?iso-8859-1?Q?'.rtrim(quoted_printable_encode($word)).'?=';
                    }
                }
            $coded .= join(' ', $words)."\r\n";
            }
        return rtrim($coded);
        }
}

/*
 * Decode MIME header elements in e-mails | @@ Matthias Sommerfeld
 * (c) 2001-2004 blue birdy, Berlin (http://bluebirdy.de)
 * used with permission
 * Last Update: @@ Thorsten, 2004-07-17
 */
function quoted_printable_encode($return = '')
{
    // Ersetzen der lt. RFC 1521 nötigen Zeichen
    $return = preg_replace('/([^\t\x20\x2E\041-\074\076-\176])/ie', "sprintf('=%2X',ord('\\1'))", $return);
    $return = preg_replace('!=\ ([A-F0-9])!', '=0\\1', $return);
    // Einfügen von QP-Breaks (=\r\n)
    if (strlen($return) > 75) {
        $length = strlen($return); $offset = 0;
        do {
            $step = 76;
            $add_mode = (($offset+$step) < $length) ? 1 : 0;
            $auszug = substr($return, $offset, $step);
            if (preg_match('!\=$!', $auszug))   $step = 75;
            if (preg_match('!\=.$!', $auszug))  $step = 74;
            if (preg_match('!\=..$!', $auszug)) $step = 73;
            $auszug = substr($return, $offset, $step);
            $offset += $step;
            $schachtel .= $auszug;
            if (1 == $add_mode) $schachtel.= '='."\r\n";
            } while ($offset < $length);
        $return = $schachtel;
        }
    $return = preg_replace('!\.$!', '. ', $return);
    return preg_replace('!(\r\n|\r|\n)$!', '', $return)."\r\n";
}


/******************************************************************************
 * Funktionen für den XML-Export
 ******************************************************************************/

/*
 * Gibt die XML-Datei zum Artikel aus | @@ Thorsten - 2002-08-29
 * Last Update: @@ Thorsten, 2004-08-11
 */
function generateXMLExport($id, $lang = "")
{
	global $db, $categories, $PMF_LANG, $PMF_CONF;
	$result = $db->query('SELECT '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.keywords, '.SQLPREFIX.'faqdata.thema, '.SQLPREFIX.'faqdata.content, '.SQLPREFIX.'faqdata.author, '.SQLPREFIX.'faqdata.datum FROM '.SQLPREFIX.'faqdata LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang WHERE id = '.$id.' AND lang = "'.$lang.'" AND active = "yes"');
    
	if ($db->num_rows($result) > 0) {
		while ($row = $db->fetch_object($result)) {
            $xml_content = stripslashes($row->content);
			$xml_rubrik = $categories[$row->category_id];
			$xml_thema = wordwrap($row->thema, 60);
			$xml_keywords = $row->keywords;
			$xml_content = trim(htmlspecialchars(stripslashes(wordwrap($xml_content, 60))));
			if (is_writeable("./xml/")) {
				$xml_fp = @fopen("./xml/article_".$row->id."_".$row->lang.".xml","wb");
				$my_xml_output = "<?xml version=\"1.0\" encoding=\"".$PMF_LANG["metaCharset"]."\" standalone=\"yes\" ?>\n";
				$my_xml_output .= "<!-- XML-Output by phpMyFAQ ".$PMF_CONF["version"]." | Date: ".makeDate(date("YmdHis"))." -->\n";
				$my_xml_output .= "<phpmyfaq xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:NamespaceSchemaLocation=\"http://www.phpmyfaq.de/xml/faqschema.xsd\">\n";
				$my_xml_output .= "\t<article id=\"".$row->id."\">\n";
				$my_xml_output .= "\t<language>".$row->lang."</language>\n";
				$my_xml_output .= "\t<category>".strip_tags(stripslashes($xml_rubrik))."</category>\n";
				if ($xml_keywords) {
					$my_xml_output .= "\t<keywords>".$xml_keywords."</keywords>\n";
					}
				else {
					$my_xml_output .= "\t<keywords />\n";
					}
				$my_xml_output .= "\t<theme>".strip_tags($xml_thema)."</theme>\n";
				$my_xml_output .= "\t<content xmlns=\"http://www.w3.org/TR/REC-html40\">".strip_tags($xml_content)."</content>\n";
				if ($row->author) {
					$my_xml_output .= "\t<author>".$row->author."</author>\n";
					}
				else {
					$my_xml_output .= "\t<author />\n";
					}
				$my_xml_output .= "\t<date>".makeDate($row->datum)."</date>\n";
				$my_xml_output .= "\t</article>\n";
				$my_xml_output .= "</phpmyfaq>";
				@fputs($xml_fp, $my_xml_output);
				@fclose($xml_fp);
				}
			}
		return TRUE;
        }
	return FALSE;
}

/**
* This function generates a plain XHTML file with all entries.
*
* @return   boolean
* @access   public
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
* @since    2004-11-13
*/
function generateXHTMLFile()
{
	global $db, $PMF_CONF, $PMF_LANG;
    
    $tree = new Category();
    $tree->transform(0);
    $old = 0;
    
	$result = $db->query('SELECT '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.thema, '.SQLPREFIX.'faqdata.content, '.SQLPREFIX.'faqdata.author, '.SQLPREFIX.'faqdata.datum FROM '.SQLPREFIX.'faqdata LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang ORDER BY '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.id');
    
    $xhtml = '<?xml version="1.0" encoding="'.$PMF_LANG['metaCharset'].'" ?>';
    $xhtml .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
    $xhtml .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$PMF_LANG['metaLanguage'].'" lang="'.$PMF_LANG['metaLanguage'].'">';
    $xhtml .= '<head>';
    $xhtml .= '    <title>'.$PMF_CONF['title'].'</title>';
    $xhtml .= '    <meta http-equiv="Content-Type" content="application/xhtml+xml; charset='.$PMF_LANG['metaCharset'].'" />';
    $xhtml .= '    <meta name="title" content="'.$PMF_CONF['title'].'" />';
    $xhtml .= '</head>';
    $xhtml .= '<body dir="'.$PMF_LANG['dir'].'">';
    
    if ($db->num_rows($result) > 0) {
        
        while (list($id, $lang, $rub, $thema, $content, $author, $datum) = $db->fetch_row($result)) {
            if ($rub != $old) {
                $xhtml .= '<h1>'.$tree->getPath($rub).'</h1>';
            }
            $xhtml .= '<h2>'.$thema.'</h2>';
            $xhtml .= '<p>'.stripslashes($content).'</p>';
            $xhtml .= '<p>'.$PMF_LANG["msgAuthor"].$author.'<br />';
            $xhtml .= $PMF_LANG["msgLastUpdateArticle"].makeDate($datum).'</p>';
            $xhtml .= '<hr style="width: 90%;" />';
            $old = $rub;
        }
    }
    $xhtml .= '</body>';
    $xhtml .= '</html>';
    
    if ($fp = fopen("../xml/phpmyfaq.html","w")) {
        
        fputs($fp, $xhtml);
		fclose($fp);
    }
    print "<p><a href=\"../xml/phpmyfaq.html\" target=\"_blank\">XHTML File okay!</a></p>";
}

/*
 * Funktion zum Exportieren aller Artikel in eine XML-Datei | @@ Thorsten, 2003-03-03
 * Last Update: @@ Thorsten, 2004-05-07
 */
function generateXMLFile()
{
	global $db, $tree, $PMF_CONF, $PMF_LANG;
	
	$result = $db->query('SELECT '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.thema, '.SQLPREFIX.'faqdata.content, '.SQLPREFIX.'faqdata.author, '.SQLPREFIX.'faqdata.datum FROM '.SQLPREFIX.'faqdata LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang ORDER BY '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.id');
    
	if ($db->num_rows($result) > 0) {
		$my_xml_output = "<?xml version=\"1.0\" encoding=\"".$PMF_LANG["metaCharset"]."\" standalone=\"yes\" ?>\n";
		$my_xml_output .= "<!-- XML-Output by phpMyFAQ ".$PMF_CONF["version"]." | Date: ".makeDate(date("YmdHis"))." -->\n";
		$my_xml_output .= "<phpmyfaq xmlns=\"http://www.phpmyfaq.de/phpmyfaq\">\n";
		$xml_fp = fopen("../xml/phpmyfaq.xml","w");
		while ($row = $db->fetch_object($result)) {
        $xml_content = wordwrap(stripslashes($row->content));
			$xml_rubrik = $tree->categoryName[$row->category_id]["name"];
			$xml_thema = wordwrap($row->thema, 60);
			$xml_content = trim(htmlspecialchars(stripslashes(wordwrap($xml_content, 60))));
			
			if (is_writeable("../xml/")) {
				$my_xml_output .= "\t<article id=\"".$row->id."\">\n";
				$my_xml_output .= "\t<language>".$row->lang."</language>\n";
				$my_xml_output .= "\t<category>".htmlentities(strip_tags($xml_rubrik))."</category>\n";
				if ($xml_keywords) {
					$my_xml_output .= "\t<keywords>".$xml_keywords."</keywords>\n";
					}
				else {
					$my_xml_output .= "\t<keywords />\n";
					}
				$my_xml_output .= "\t<theme>".strip_tags($xml_thema)."</theme>\n";
				$my_xml_output .= "\t<content xmlns=\"http://www.w3.org/TR/REC-html40\">".strip_tags($xml_content)."</content>\n";
				if ($row->author) {
					$my_xml_output .= "\t<author>".$row->author."</author>\n";
					}
				else {
					$my_xml_output .= "\t<author />\n";
					}
				$my_xml_output .= "\t<date>".makeDate($row->datum)."</date>\n";
				$my_xml_output .= "\t</article>\n\n";
				}
			}
		$my_xml_output .= "</phpmyfaq>";
		fputs($xml_fp, $my_xml_output);
		fclose($xml_fp);
		}
	print "<p><a href=\"../xml/phpmyfaq.xml\" target=\"_blank\">XML File okay!</a></p>";
}

/**
* This function generates a plain XHTML file with all entries.
*
* @return   boolean
* @access   public
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
* @author   Johann-Peter Hartmann <hartmann@mayflower.de>
* @since    2004-12-23
*/
function generateDocBookExport()
{
    global $db, $tree, $PMF_CONF, $PMF_LANG;
    
    $output = '<?xml version="1.0"?>
<!DOCTYPE book PUBLIC "-//Norman Walsh//DTD DocBk XML V3.1.4//EN" "http://nwalsh.com/docbook/xml/3.1.4/db3xml.dtd">
<book id="faqname" lang="'.$PMF_LANG['metaLanguage'].'">
    <bookinfo>
        <title>'.$PMF_CONF['title'].'</title>
        <author>
            <firstname></firstname>
            <surname></surname>
        </author>
        <date>'.makeDate('Y-m-d', time()).'</date>
        <abstract>
            <para>'.$PMF_CONF['metaDescription'].'</para>
        </abstract>
    </bookinfo>
';
    
    // get all categories
    $result_chapters = $db->query("SELECT id, name FROM ".SQLPREFIX."faqcategories");
    // get all faqs
    $result_faqs = $db->query("SELECT category_id, record_id, id FROM ".SQLPREFIX."faqcategoryrelations");
    // get all data
    $result_data = $db->query("SELECT * FROM ".SQLPREFIX."faqdata d, ".SQLPREFIX."faqcategoryrelations r where d.id = r.record_id");
    
    // chapters
    $chapters = array();
    while ($row = $db->fetch_object($result_chapters)) {
        $chapters[$row->id]['title'] = $row->name;
        $chapters[$row->id]['faqs'] = array();
    }
    
    // faqs
    while ($row = $db->fetch_object($result_faqs)) {
        $chapters[$row->category_id]['faqs'][$row->id] = array();
    }
    
    // data
    while ($row = $db->fetch_object($result_data)) {
        $chapters[$row->category_id]['faqs'][$row->id][$row->record_id] =
            array(
                'author' => $row->author,
                'date'   => $row->datum,
                'thema'  => $row->thema,
                'para'   => $row->content
            );
    }
    
    // output
    foreach($chapters as $c_key=>$c_value){
        
        $output .= '<chapter id="'.$c_key.'">';
        $output .= "\n";
        $output .= '<title>'.$c_value['title'].'</title>';
        $output .= "\n";
        
        foreach($c_value['faqs'] as $f_id=>$data){
            foreach($data as $d_id=>$posting){
                $output .= '<sect1 id="'.$d_id.'">';
                $output .= "\n";
                
                $output .= '<author>';
                $output .= $posting['author'];
                $output .= '</author>';
                $output .= "\n";
    
                $output .= '<date>';
                $output .= $posting['date'];
                $output .= '</date>';
                $output .= "\n";
                
                $output .= '<title>';
                $output .= $posting['thema'];
                $output .= '</title>';
                $output .= "\n";
    
                $output .= '<para>';
                $output .= $posting['para'];
                $output .= '</para>';
                $output .= "\n";
    
                $output .= '</sect1>';
                $output .= "\n";
            }
        }
            
        $output .= '</chapter>';
        $output .= "</book>";
    }
    
    // write xml file
    $xml_fp = fopen("../xml/docbook.xml","w");
    fputs($xml_fp, $output);
	fclose($xml_fp);
	print "<p><a href=\"../xml/phpmyfaq.xml\" target=\"_blank\">XML DocBook File okay!</a></p>";
}

/**
* The main search function for the full text search
*
* @param    string
* @param    int
* @return   string
* @access   public
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
* @since    2002-09-16
*/
function searchEngine($begriff)
{
	global $db, $sids, $tree, $PMF_LANG, $PMF_CONF;
	$seite = "";
	$output = "";
	
	if (isset($_REQUEST["seite"])) {
		$seite = $_REQUEST["seite"];
    } else {
        $seite = 1;
    }
    
	if (isset($_REQUEST["search"])) {
		$begriff = $_REQUEST["search"];
    } else {
        return $PMF_LANG["err_noArticles"];
    }
    
	$result = $db->search(SQLPREFIX."faqdata", array("id" => NULL, "lang" => NULL, "rubrik" => NULL, "thema" => NULL, "content" => NULL), array("thema", "content", "keywords"), $begriff, array("active"=>"yes"));
	$num = $db->num_rows($result);
	
    $pages = ceil($num / $PMF_CONF["numRecordsPage"]);
	$y = $seite * $PMF_CONF["numRecordsPage"]; 
	$x = $y - $PMF_CONF["numRecordsPage"];
	if ($y > $num) {
		$y = $num;
		}
	
	$result = $db->search(SQLPREFIX."faqdata", array("id" => NULL, "lang" => NULL, "rubrik" => NULL, "thema" => NULL, "content" => NULL), array("thema", "content", "keywords"), $begriff, array("active" => "yes"), $PMF_CONF["numRecordsPage"], $x);
    
    if (0 == $num) {
        
        $keys = preg_split("/\s+/", $begriff);
        $numKeys = count($keys);
        for ($i = 0; $i < $numKeys; $i++) {
            
            if (strlen($where) != 0 ) {
                $where = $where." OR ";
            }
            
            $where = $where.'('.SQLPREFIX.'faqdata.keywords LIKE "%'.$keys[$i].'%") AND '.SQLPREFIX.'faqdata.active = "yes"';
        }
        
        $where = " WHERE (".$where.") AND active = 'yes'";
        $query = 'SELECT '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqcategoryrelations.record_id, '.SQLPREFIX.'faqdata.thema, '.SQLPREFIX.'faqdata.content FROM '.SQLPREFIX.'faqdata LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang '.$where;
        $result = $db->query($query);
        $num = $db->num_rows($result);
    }
    
	if ($num > 0) {
		if ($num == "1") {
			$output .= "<p>".$num.$PMF_LANG["msgSearchAmount"]."</p>\n";
			}
		else {
			$output .= "<p>".$num.$PMF_LANG["msgSearchAmounts"]."</p>\n";
			}
		if ($pages > 1) {
			$output .= "<p><strong>".$PMF_LANG["msgPage"].$seite." ".$PMF_LANG["msgVoteFrom"]." ".$pages.$PMF_LANG["msgPages"]."</strong></p>";
			}
		$output .= "<ul class=\"phpmyfaq_ul\">\n";
	    for ($i = $x; $i < $y ; $i++) {
			list($id, $lang, $rubrik, $thema, $content) = $db->fetch_row($result);
			$rubriktext = $tree->getPath($rubrik);
			$thema = chopString($thema, 15);
            $content = stripslashes(chopString(strip_tags($content), 25));
            $thema = preg_replace('/(((href|src)="[^"]*)?'.$begriff.'(?(1).*"))/mies', "highlight_no_links(\"\\1\")", $thema);
			$content = preg_replace('/(((href|src)="[^"]*)?'.$begriff.'(?(1).*"))/mies', "highlight_no_links(\"\\1\")", $content);
            $output .= "<li><strong>".$rubriktext."</strong>: <a href=\"".$_SERVER["PHP_SELF"]."?".$sids."action=artikel&amp;cat=".$rubrik."&amp;id=".$id."&amp;artlang=".$lang."&amp;highlight=".$begriff."\">".stripslashes($thema)."...</a><br /><div style=\"font-size: 10px;\"><strong>".$PMF_LANG["msgSearchContent"]."</strong> ".stripslashes($content)."...</div><br /></li>\n";
            }
        $output .= "</ul>\n";
        }
    else {
		$output = $PMF_LANG["err_noArticles"];
		}
    
    if ($num > $PMF_CONF["numRecordsPage"]) {
        $output .= "<p align=\"center\"><strong>";
        $vor = $seite - 1; 
        $next = $seite + 1;
        if ($vor != 0) {
            $output .= "[ <a href=\"".$_SERVER["PHP_SELF"]."?".$sids."action=search&amp;&search=".$begriff."&amp;seite=".$vor."\">".$PMF_LANG["msgPrevious"]."</a> ]";
            }
        $output .= " ";
        if ($next <= $pages) {
            $output .= "[ <a href=\"".$_SERVER["PHP_SELF"]."?".$sids."action=search&amp;search=".$begriff."&amp;seite=".$next."\">".$PMF_LANG["msgNext"]."</a> ]";
            }
        $output .= "</strong></p>";
		}
	return $output;
}

/*
 * Funktion für das Herausfiltern von highlighting HTML aus URLs und Images | @@ Matthias Sommerfeld, 2003-07-14
 * Last Update: @@ Thorsten, 2003-12-16
 */
function highlight_no_links($string = "")
{
    if ("" == $string) {
        return "";
        }
    if ("href=" == substr($string, 0, 5) || "src=" == substr($string, 0, 4)) {
        return $string;
    }
    else {
        return "<span class=\"highlight\">".$string."</span>";
    }
}

/*
 * This functions chops a string | @@ Thorsten, 2003-12-16
 * Last Update: @@ Thorsten, 2003-12-16
 */
function chopString($string, $words)
{
    $str = "";
    $pieces = explode(" ", $string);
    $num = count($pieces);
    if ($words > $num) {
        $words = $num;
        }
    for ($i = 0; $i < $words; $i++) {
        $str .= $pieces[$i]." ";
        }
    return $str;
}


/******************************************************************************
 * Funktionen für die Offenen Fragen
 ******************************************************************************/

/*
 * Diese Funktion gibt die offenen Fragen aus | @@ Thorsten, 2002-09-17
 * Last Update: @@ Thorsten, 2004-07-08
 */
function printOpenQuestions()
{
	global $db, $sids, $tree, $PMF_LANG;
	
	$query = "SELECT id,ask_username,ask_usermail,ask_rubrik,ask_content,ask_date FROM ".SQLPREFIX."faqfragen ORDER BY ask_date ASC";
	$result = $db->query($query);
	$output = "";
	if ($db->num_rows($result) > 0) {
		while ($row = $db->fetch_object($result)) {
			$output .= "\t<tr class=\"openquestions\">\n";
			$output .= "\t\t<td valign=\"top\" nowrap=\"nowrap\">".makeDate($row->ask_date)."<br /><a href=\"mailto:".safeEmail($row->ask_usermail)."\">".$row->ask_username."</a></td>\n";
			$output .= "\t\t<td valign=\"top\"><strong>".$tree->categoryName[$row->ask_rubrik]["name"].":</strong><br />".stripslashes($row->ask_content)."</td>\n";
        	$output .= "\t\t<td valign=\"top\"><a href=\"".$_SERVER["PHP_SELF"]."?".$sids."action=add&amp;question=".rawurlencode($row->ask_content)."&amp;cat=".$row->ask_rubrik."\">".$PMF_LANG["msg2answer"]."</a></td>\n";
    		$output .= "\t</tr>\n";
			}
		}
	else {
		$output = "\t<tr>\n\t\t<td colspan=\"3\">".$PMF_LANG["msgNoQuestionsAvailable"]."</td>\n\t</tr>\n";
		}
	return $output;
}




/******************************************************************************
 * Funktionen für die Benutzerauthentifizierung und Rechtevergabe
 ******************************************************************************/

/**
* Administrator logging
*
* @param    string
* @return   void
* @access   public
* @since    2001-02-18
* @author   Bastian Poettner <bastian@poettner.net>
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
*/
function adminlog($text)
{
    global $db, $PMF_CONF, $auth_user;
    if (isset($PMF_CONF["enableadminlog"])) {
        $db->query('INSERT INTO '.SQLPREFIX.'faqadminlog (id, time, usr, text, ip) VALUES ('.$db->nextID(SQLPREFIX.'faqadminlog', 'id').', '.time().', "'.$auth_user.'", "'.nl2br(addslashes($text)).'", "'.$_SERVER["REMOTE_ADDR"].'")');
    }
}

/*
 * Checkt, ob eine SQL-Tabelle leer ist | @@ Thorsten 2002-01-10
 * Last Update: @@ Thorsten, 2003-03-24
 */
function emptyTable($table)
{
	global $db;
	if ($db->num_rows($db->query("SELECT * FROM ".$table)) < 1) {
		return TRUE;
		}
	else {
		return FALSE;
		}
}



/******************************************************************************
 * Funktionen für den Adminbereich
 ******************************************************************************/

/*
 * Function for displaying all languages in <option> | @@ Thorsten, 2004-06-01
 * Last Update: @@ Thorsten, 2004-07-09
 */
function languageOptions($lang = "")
{
    global $languageCodes;
    $output = "";
    foreach ($languageCodes as $key => $value) {
        $output .= "\t<option value=\"".strtolower($key)."\"";
        if (strtolower($key) == $lang) {
            $output .= " selected=\"selected\"";
            }
        $output .= ">".$value."</option>\n";
        }
    return $output;
}

/*
 * Funktion zum generieren vom "Umblättern" | @@ Bastian, 2002-01-03
 * Last Update: @@ Thorsten, 2004-05-07
 */
function PageSpan($code, $start, $end, $akt)
{
	global $PMF_LANG;
	if ($akt > $start) {
		$out = str_replace("<NUM>", $akt-1, $code).$PMF_LANG["msgPreviusPage"]."</a> | ";
		}
	else {
		$out = "";
		}
	for ($h = $start; $h<=$end; $h++) {
		if($h > $start) {
			$out .= ", ";
			}
		if($h != $akt) {
			$out .= str_replace("<NUM>", $h, $code).$h."</a>";
			}
		else {
			$out .= $h;
			}
		}
	if ($akt < $end) {
		$out .= " | ".str_replace("<NUM>", $akt+1, $code).$PMF_LANG["msgNextPage"]."</a>";
		}
		$out = $PMF_LANG["msgPageDoublePoint"].$out;
		return $out;
}

/*
 * Bastelt aus den Dateinamen des Tracking einen Timestamp | @@ Bastian, 2002-01-05
 * Last Update: @@ Thorsten, 2002-09-19
 */
function FileToDate($file)
{
	if (strlen($file) >= 16) {
		$tag = substr($file, 8, 2);
		$mon = substr($file, 10, 2);
		$yea = substr($file, 12, 4);
		$tim = mktime(1, 1, 1, $mon, $tag, $yea);
		return $tim;
		}
	else {
		return -1;
		}
}

/*
 * Bastelt nen Timestamp ausm Datum | @@ Bastian, 2001-04-09
 * Last Update: @@ Thorsten - 2002-09-27
 */
function mkts($datum,$zeit)
{
	if (strlen($datum) > 0) {
		$tag = substr($datum,0,strpos($datum,"."));
		$datum = substr($datum,(strpos($datum,".")+1),strlen($datum));
		$monat = substr($datum,0,strpos($datum,"."));
		$datum = substr($datum,(strpos($datum,".")+1),strlen($datum));
		$jahr = $datum;
	}
	else {
		$tag = date("d");
		$monat = date("m");
		$jahr = date("Y");
	}
	if (strlen($zeit) > 0) {
		$stunde = substr($zeit,0,strpos($zeit,":"));
		$zeit = substr($zeit,(strpos($zeit,":")+1),strlen($zeit));
		$minute = substr($zeit,0,strpos($zeit,":"));
		$zeit = substr($zeit,(strpos($zeit,":")+1),strlen($zeit));
		$sekunde = $zeit;
	}
	else {
		$stunde = date("H");
		$minute = date("i");
		$sekunde = date("s");
	}
	return mktime($stunde, $minute, $sekunde, $monat, $tag, $jahr);
}

/* Creates an array by using one array for keys and another for its values | @@ Thorsten - 2004-07-23
 * Last Update: @@ Thorsten - 2004-07-23
 * This function is included in PHP 5.0.0 and later
 */
if (!function_exists("array_combine")) {
    function array_combine($keys, $values)
    {
        $keys = array_values($keys);
        $values  = array_values($values);
        $combined = array ();
        $num = count($values);
        for ($i = 0; $i < $num; $i++) {
            $combined[$keys[$i]] = $values[$i];
        }
        return $combined;
    }
}



/******************************************************************************
 * Funktionen für das Backup
 ******************************************************************************/

/*
 * Funktion zum Zusammensetzen der zu sichernden SQL Queries | @@ Meikel, 2003-03-24
 * Last Update: @@ Thorsten, 2003-11-13
 */
function build_insert($query, $table)
{
	global $db;
	if (!$result = $db->query($query)) {
		return;
	}
	$ret = array();
	$ret[] = "\n# Table: ".$table;
	while ($row = $db->fetch_assoc ($result)) {
		$p1 = array();
		$p2 = array();
		foreach ($row as $key => $val) {
            $val = safeSQL($val);
			$p1[] = $key;
            if (is_numeric($val)) {
                $p2[] = $val;
            } else {
                $p2[] = "'".$val."'";
            }
		}
		$ret[] = "INSERT INTO ".$table." (".implode(",", $p1).") VALUES (".implode(",", $p2).");";
	}
	return $ret;
}

/*
 * Funktion zum Escapen von SQL Queries | @@ Thorsten, 2003-11-13
 * Last Update: @@ Thorsten, 2004-07-07
 */
function safeSQL($string)
{
    $str = "";
    $length = strlen($string);
    for ($i = 0; $i < $length; $i++) {
        $char = $string[$i];
        switch ($char) {
            case "'":   $str .= "\'"; break;
            case "\\":  $str .= "\\\\"; break;
            case "\n":  $str .= "\\n"; break;
            case "\r":  $str .= "\\r"; break;
            default:    $str .= $char;
            }
        }
    return $str;
}



/******************************************************************************
 * Functions for exporting as PDF
 ******************************************************************************/

/*
 * Funktion wandelt HEX Farbcodes in dezimale RGB Werte um für FPDF-Class | @@ Peter Beauvain, 2004-04-12
 * Last Update: @@ Thorsten, 2004-07-04
 */
function hex2dec($color = "#000000")
{
    $R = substr($color, 1, 2);
    $red = hexdec($R);
    $G = substr($color, 3, 2);
    $green = hexdec($G);
    $B = substr($color, 5, 2);
    $blue = hexdec($B);
    $tbl_color = array();
    $tbl_color['R'] = $red;
    $tbl_color['G'] = $green;
    $tbl_color['B'] = $blue;
    return $tbl_color;
}

// LDAP functions

/**
* Returns the user name from REMOTE_USER
*
* @return   string
* @access   public
* @author   Adam Greene <phpmyfaq@skippy.fastmail.fm>
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
*/
function getShortUserName()
{
    if (isset($_ENV['REMOTE_USER'])) {
        return $_ENV['REMOTE_USER'];
    } elseif (isset($_SERVER['REMOTE_USER'])) {
        return $_SERVER['REMOTE_USER'];
    } else {
        return;
    }
}

/**
* Returns the full user name from LDAP if available
*
* @return   string
* @access   public
* @author   Adam Greene <phpmyfaq@skippy.fastmail.fm>
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
*/
function getFullUserName()
{
    if ($PMF_CONF["ldap_support"] == TRUE) {
        global $ldap;
        return $ldap->ldap_getCompleteName(getShortUserName());
    } else {
        return getShortUserName();
    }
}

/**
* Returns the full user name from LDAP if available
*
* @return   string
* @access   public
* @author   Adam Greene <phpmyfaq@skippy.fastmail.fm>
* @author   Thorsten Rinne <thorsten@phpmyfaq.de>
*/
function getEmailAddress()
{
    if ($PMF_CONF["ldap_support"] == TRUE) {
        global $ldap;
        return $ldap->ldap_getMail(getShortUserName());
    } else {
        return '';
    }
}

?>