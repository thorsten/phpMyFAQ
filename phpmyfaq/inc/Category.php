<?php
/**
* $Id: Category.php,v 1.1 2006-06-11 14:26:55 matteo Exp $
*
* The main category class
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Lars Tiedemann <larstiedemann@yahoo.de>
* @package      Category
* @since        2004-02-16
* @copyright    (c) 2004-2006 phpMyFAQ Team
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

class PMF_Category
{
    /**
    * The database handler
    *
    * @var  object
    */
    var $db = null;

    /**
    * The categories as an array.
    *
    * @var  array
    */
    var $categories = array();

    /**
    * The category names as an array.
    *
    * @var  array
    */
    var $categoryName = array();

    /**
    * The category tree
    *
    * @var  array
    */
    var $catTree = array();

    /**
    * The children nodes
    *
    * @var  array
    */
    var $children = array();

    /**
    * The current language
    *
    * @var  string
    */
    var $language = null;

    /**
    * The lines of tabs
    *
    * @var  array
    */
    var $lineTab = array();

    /**
    * The tree with the tabs
    *
    * @var  array
    */
    var $treeTab = array();

    /**
    * Symbol for each item
    * NOTE: We do not use this currently
    *
    * @var  array
    */
    var $symbols = array(
        'vertical' => '|',
        'plus'     => '+',
        'minus'    => '-',
        'space'    => '&nbsp;',
        'angle'    => '-',
        'medium'   => '|-');

    /**
    * Constructor
    *
    * @param    string
    * @return   void
    * @access   public
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function PMF_Category ($language = '')
    {
        global $db;

        $this->language   = $language;
        $this->db         = $db;
        $this->categories = array();
        $this->lineTab    = $this->getOrderedCategories();

        for ($i = 0; $i < count($this->lineTab); $i++) {
			$this->lineTab[$i]['level'] = $this->levelOf($this->lineTab[$i]['id']);
        }
    }

    /**
     * getOrderedCategories()
     *
     * Gets all categories and write them in an array with ordered IDs
     *
     * @return  array
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function &getOrderedCategories()
    {
        $query = "SELECT id, lang, parent_id, name, description FROM ".SQLPREFIX."faqcategories";
        if (isset($this->language) && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $query .= " WHERE lang = '".$this->language."'";
        }
        $query .= ' ORDER BY id';
        $result = $this->db->query($query);
        while ($row = $this->db->fetch_assoc($result)) {
            $this->categoryName[$row['id']] = $row;
            $this->categories[] =& $this->categoryName[$row['id']];
            $this->children[$row['parent_id']][$row['id']] =& $this->categoryName[$row['id']];
        }
        return $this->categories;
    }

    /**
     * getCategories()
     *
     * Gets the main categories and write them in an array
     *
     * @param   integer $cat
     * @param   boolean
     * @return  array
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getCategories($cat, $parent_id = true)
    {
        $_query = '';
        $query = 'SELECT id, lang, parent_id, name, description FROM '.SQLPREFIX.'faqcategories WHERE ';
        if (true == $parent_id) {
            $query .= 'parent_id = 0';
        }
        foreach (explode(';', $cat) as $cats) {
            $_query .= ' OR parent_id = '.$cats;
        }
        if (false == $parent_id && 0 < strlen($_query)) {
            $query .= substr($_query, 4);
        }
        if (isset($this->language) && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $query .= ' AND lang = "'.$this->language.'"';
        }
        $query .= " ORDER BY id";
        $result = $this->db->query($query);
        while ($row = $this->db->fetch_assoc($result)) {
            $this->categories[] = $row;
        }
        return $this->categories;
    }

    /**
     * getAllCategories()
     *
     * Gets all categories and write them in an array
     *
     * @return  array
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getAllCategories()
    {
        $query = "SELECT id, lang, parent_id, name, description FROM ".SQLPREFIX."faqcategories";
        if (isset($this->language) && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $query .= " WHERE lang = '".$this->language."'";
        }
        $result = $this->db->query($query);
        while ($row = $this->db->fetch_assoc($result)) {
            $this->categories[$row["id"]] = $row;
        }
        return $this->categories;
    }

    /* buld the tree in an array */
    function buildTree($id_parent = 0, $indent = 0)
    {
        $tt = array();
        $x = 0;
        $loop = 0;
        foreach ($this->categories as $n) {
            if (isset($n['parent_id']) && $n['parent_id'] == $id_parent) {
                $tt[$x++] = $loop;
            }
            $loop++;
        }
        if ($x != 0) {
            foreach ($tt as $d) {
                $tmp = array();
                foreach ($this->categories[$d] as $key => $value) {
                    $tmp[$key] = $value;
                }
                $tmp["indent"] = $indent;
                $this->catTree[] = $tmp;
                $this->buildTree($tmp["id"], $indent+1);
            }
        } else {
            return 0;
        }
    }

    /* get the level of the item id */
	function levelOf($id)
    {
        $ret = 0;
        while ((isset($this->categoryName[$id]['parent_id'])) && ($this->categoryName[$id]['parent_id'] != 0)) {
            $ret++;
            $id = $this->categoryName[$id]['parent_id'];
        }
        return $ret;
    }

	// Get the line number where to find the node $id
	function getLine($id)
    {
        for ($i = 0; $i < count($this->lineTab); $i++) {
            if ($this->lineTab[$i]['id'] == $id) {
                return $i;
            }
        }
	}

	/**
	* transform()
	*
	* Transforms the linear array in a 1D array in the order of the tree, with the info
	*
	* @param   integer     $id
	* @return  void
	* @access  public
	* @author  Thorsten Rinne <thorsten@phpmyfaq.de>
	* @since   2004-02-16
	*/
	function transform($id)
    {
        $thisParent_id = 0;
        $tree = array();
		$tabs = isset($this->children[$id]) ? array_keys($this->children[$id]) : array();
		$num = count($tabs);

        if ($id > 0) {
            $thisLevel = $this->categoryName[$id]['level'];
            $thisParent_id = $this->categoryName[$id]['parent_id'];
            $thisName = $this->categoryName[$id]['name'];
            $thisdescription = $this->categoryName[$id]['description'];
        }

		if ($num > 0) {
			$symbol = 'minus';
        } else {
			$temp = isset($this->children[$thisParent_id]) ? array_keys($this->children[$thisParent_id]) : array();
			if (isset($temp[count($temp)-1])) {
			    $symbol = ($id == $temp[count($temp)-1]) ? 'angle' : 'medium';
			}
		}

        $ascendants = $this->getNodes($id);
        $num_ascendants = count($ascendants);

        if ($id > 0) {
            for ($i = 0; $i < $num_ascendants; $i++) {
                $freres = $this->getBrothers($ascendants[$i]);
                $tree[$i] = ($ascendants[$i] == $freres[count($freres)-1]) ? 'space' : 'vertical';
            }
        }

		if ($id > 0) {
            $this->treeTab[] = array('id' => $id, 'symbol' => $symbol, 'name' => $thisName, 'numChilds' => count($tabs), 'level' => $thisLevel, 'parent_id' => $thisParent_id, 'childs' => $tabs, 'tree' => $tree, 'description' => $thisdescription);
        }

		foreach ($tabs as $i ) {
			$this->transform($i);
        }
	}

	// get the id of the parent_id, i.e. where parent_id == 0
	function getParent_id()
    {
		for ($i = 0; $i < count($this->lineTab); $i++) {
			if ($this->lineTab[$i]['parent_id'] == 0) {
				return $this->lineTab[$i]['id'];
            }
        }
	}

	// get the line number where to find the node $id in the category tree
	function getLineCategory($id)
    {
        for ($i = 0; $i < count($this->treeTab); $i++) {
			if (isset($this->treeTab[$i]['id']) && $this->treeTab[$i]['id'] == $id) {
                return $i;
            }
        }
	}

	// list in a array of the $id of the child
	function getChildren($id)
    {
		return isset($this->children[$id]) ? array_keys($this->children[$id]) : array();
	}

	// number of childs of the $id
	function numChilds($id)
    {
		return count($this->getNodes($id));
	}

	// list in array the root, super-root, ... of the $id
	function getNodes($id)
    {
        if (($id > 0) && (isset($this->categoryName[$id]['level']))) {
            $thisLevel = $this->categoryName[$id]['level'];
            $temp = array();
    		for ($i = $thisLevel; $i > 0; $i--) {
    		    $id = $this->categoryName[$id]['parent_id'];
                array_unshift($temp, $id);
            }
            return $temp;
        }
	}

	// collapse the node $id
	function collapse($id)
    {
        if (isset($this->treeTab[$this->getLineCategory($id)]['symbol'])) {
            $this->treeTab[$this->getLineCategory($id)]['symbol'] = 'plus';
        }
	}

	// collapse the complete category tree
	function collapseAll()
    {
		for ($i = 0; $i < count($this->treeTab); $i++) {
			if ($this->treeTab[$i]["symbol"] == "minus") {
				$this->treeTab[$i]["symbol"] = "plus";
            }
        }
	}

	// expand the node $id
	function expand($id)
    {
		$this->treeTab[$this->getLineCategory($id)]["symbol"] = "minus";
	}

	// try to expand from the parent_id to the node $id
	function expandTo($id)
    {
		$this->collapseAll();
		$ascendants = $this->getNodes($id);
		$ascendants[] = $id;
		for ($i = 0; $i < count($ascendants); $i++) {
            $numChilds = 0;
            if (isset($this->treeTab[$this->getLineCategory($ascendants[$i])]["numChilds"])) {
                $numChilds = $this->treeTab[$this->getLineCategory($ascendants[$i])]["numChilds"];
                if ($numChilds > 0) {
                    $this->expand($ascendants[$i]);
                } else {
                    $i = count($ascendants);
                }
            }
		}
	}

	// expand the entire tree
	function expandAll()
    {
		for ($i = 0; $i < count($this->treeTab); $i++) {
			if ($this->treeTab[$i]["symbol"] == "plus") {
				$this->treeTab[$i]["symbol"] = "minus";
            }
        }
	}


	// width of the expanded tree
	function width()
    {
		for ($x = -1, $i = 0; $i < count($this->treeTab) ; $i = $this->getNextLineTree($i)) {
			$x = max($x,$this->treeTab[$i]["level"]);
        }
		return $x;
	}

	// total height of the expanded tree
	function height()
    {
		return count($this->treeTab);
	}

	/**
    * print the static tree with the number of records
    *
    * @return   string
    * @access   public
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
	function viewTree()
    {
        global $sids, $PMF_LANG, $PMF_CONF;

        $query = "SELECT ".SQLPREFIX."faqcategoryrelations.category_id AS category_id, count(".SQLPREFIX."faqcategoryrelations.category_id) AS number FROM ".SQLPREFIX."faqcategoryrelations, ".SQLPREFIX."faqdata WHERE ".SQLPREFIX."faqcategoryrelations.record_id = ".SQLPREFIX."faqdata.id";

        if (strlen($this->language) > 0) {
            $query .= sprintf(" AND %sfaqdata.lang = '%s'", SQLPREFIX, $this->language);
        }

        $query .= " AND ".SQLPREFIX."faqdata.active = 'yes' GROUP BY ".SQLPREFIX."faqcategoryrelations.category_id";

        $result = $this->db->query($query);
	    if ($this->db->num_rows($result) > 0) {
		    while ($row = $this->db->fetch_object($result)) {
                $number[$row->category_id] = $row->number;
            }
        }
        $output = "<ul>\n";
        $open = 0;
		$this->expandAll();

        for ($y = 0 ;$y < $this->height(); $y = $this->getNextLineTree($y)) {

            list($symbol, $categoryName, $parent, $description) = $this->getLineDisplay($y);
            $level = $this->treeTab[$y]["level"];
            $leveldiff = $open - $level;

            if ($leveldiff > 1) {
                for ($i = $leveldiff; $i > 1; $i--) {
                    $output .= "</li>\n".str_repeat("\t", $level+2)."</ul>\n".str_repeat("\t", $level+1)."</li>\n";
                }
            }

            if (!isset($number[$parent])) {
                $number[$parent] = 0;
            }

            if ($level < $open) {
                if (($level - $open) == -1) {
                    $output .= '</li>';
                }
                $output .= "\n".str_repeat("\t", $level+2)."</ul>\n".str_repeat("\t", $level+1)."</li>\n";
            } elseif ($level == $open && $y != 0) {
                $output .= "</li>\n";
            }

            if ($level > $open) {
                $output .= "\n".str_repeat("\t", $level+1)."<ul>\n".str_repeat("\t", $level+1)."<li>";
            } else {
                $output .= str_repeat("\t", $this->treeTab[$y]["level"]+1)."<li>";
            }

            if (0 == $number[$parent] && 0 == $level) {
                $num_entries = '';
            } else {
                $num_entries = sprintf(' (%d %s)', $number[$parent], $PMF_LANG['msgEntries']);
            }

            if (isset($PMF_CONF['mod_rewrite']) && $PMF_CONF['mod_rewrite'] == "TRUE") {
                $output .= sprintf('<a title="%s" href="category%d.html">%s</a>%s', addslashes($description), $parent, $categoryName, $num_entries);
            } else {
                $output .= sprintf('<a title="%s" href="?%saction=show&amp;cat=%d">%s</a>%s', addslashes($description), $sids,  $parent, $categoryName, $num_entries);
            }

            $open = $level;
        }

        if (isset($level) && $level > 0) {
            $output .= str_repeat("</li>\n\t</ul>\n\t", $level);
        }

        $output .= "\t</li>\n";
        $output .= "\t</ul>\n";
        return $output;
	}

    // return the three parts of a line to display: last part of tree, category name, and id of the root node
	function getLineDisplay($y)
    {
		$ret[0] = $this->symbols[$this->treeTab[$y]["symbol"]];
		$ret[1] = $this->treeTab[$y]["name"];
		$ret[2] = $this->treeTab[$y]["id"];
        $ret[3] = $this->treeTab[$y]["description"];
		return $ret;
	}

    // get the next line in the array treeTab, depending of the collapse/expand node
	function getNextLineTree($l)
    {
		if ($this->treeTab[$l]["symbol"] != "plus") {
			return $l + 1;
        } else {
			for ($i = $l + 1; $i < $this->height(); $i++) {
				if ($this->treeTab[$i]["level"]<=$this->treeTab[$l]["level"]) {
					return $i;
                }
            }
        }
		return $this->height();
	}

	/**
    * get the list of the brothers of $id (include $id)
    *
    * @param    integer
    * @return   array
    * @access   public
    */
	function getBrothers($id)
    {
		$ret = $this->getChildren($this->categoryName[$id]['parent_id']);
		return $ret;
	}

    /**
    * Get all categories in <option> tags
    *
    * @param    mixed
    * @return   string
    * @access   public
    */
    function printCategoryOptions($catID = "")
    {
        $categories = "";

        if (!is_array($catID)) {
            $catID = array(array('category_id' => $catID, 'category_lang' => ''));
        }

        $i = 0;
        foreach ($this->catTree as $cat) {
            $indent = '';
            for ($j = 0; $j < $cat['indent']; $j++) {
                $indent .= '....';
            }
            $categories .= "\t<option value=\"".$cat['id']."\"";

            if (0 == $i && count($catID) == 0) {
                $categories .= " selected=\"selected\"";
            } else {
                foreach ($catID as $categoryid) {
                    if ($cat['id'] == $categoryid['category_id']) {
                        $categories .= " selected=\"selected\"";
                    }
                }
            }

            $categories .= ">";
            $categories .= $indent.$cat['name']."</option>\n";
            $i++;
        }
        return $categories;
    }


    /**
    * Get all categories in a unordered list
    *
    * @param    mixed
    * @return   string
    * @access   public
    */
    function printCategoryList($catID = "")
    {
        $categories = '<ul>';

        if (!is_array($catID)) {
            $catID = array(array('category_id' => $catID, 'category_lang' => ''));
        }

        $i = 0;
        foreach ($this->catTree as $cat) {
            $indent = '';
            for ($j = 0; $j < $cat['indent']; $j++) {
                $indent .= '....';
            }
            $categories .= "\t<li>";
            $categories .= $indent.$cat['name'];
            $i++;
        }
        $categories .= '</ul>';
        return $categories;
    }


    /**
    * Displays the main navigation
    *
    * @param    int
    * @return   string
    * @access   public
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function printCategories($activeCat = 0)
    {
        global $sids, $PMF_LANG, $PMF_CONF;
        $open = 0;
        $output = "";
        if ($this->height() > 0) {

            for ($y = 0 ;$y < $this->height(); $y = $this->getNextLineTree($y)) {

                list($symbol, $categoryName, $parent, $description) = $this->getLineDisplay($y);

                if ($activeCat == $parent) {
                    $a = " class=\"active\"";
                } else {
                    $a = "";
                }

                $level = $this->treeTab[$y]["level"];
                $leveldiff = $open - $level;

                if ($leveldiff > 1) {
                    for ($i = $leveldiff; $i > 1; $i--) {
                        $output .= "</li>\n".str_repeat("\t", $level + 2)."</ul>\n".str_repeat("\t", $level + 1)."</li>\n";
                    }
                }

                if ($level < $open) {
                    if (($level - $open) == -1) {
                        $output .= '</li>';
                    }
                    $output .= "\n".str_repeat("\t", $level + 2)."</ul>\n".str_repeat("\t", $level + 1)."</li>\n";
                } elseif ($level == $open && $y != 0) {
                    $output .= "</li>\n";
                }

                if ($level > $open) {
                    $output .= "\n".str_repeat("\t", $level +1 )."<ul class=\"subcat\">\n".str_repeat("\t", $level + 1)."<li>";
                } else {
                    $output .= str_repeat("\t", $this->treeTab[$y]["level"] + 1)."<li>";
                }

                if (isset($this->treeTab[$y]['symbol']) && $this->treeTab[$y]['symbol'] == 'plus') {
                    if (isset($PMF_CONF['mod_rewrite']) && $PMF_CONF['mod_rewrite'] == "TRUE") {
            		    $output .= "<a title=\"".addslashes($description)."\" href=\"category".$parent.".html\"".$a.">".$categoryName." <img src=\"images/more.gif\" width=\"11\" height=\"11\" alt=\"".$categoryName."\" style=\"border: none; vertical-align: middle;\" /></a>";
                    } else {
                        $output .= "<a title=\"".addslashes($description)."\" href=\"".$_SERVER["PHP_SELF"]."?".$sids."action=show&amp;cat=".$parent."\"".$a.">".$categoryName." <img src=\"images/more.gif\" width=\"11\" height=\"11\" alt=\"".$categoryName."\" style=\"border: none; vertical-align: middle;\" /></a>";
                    }
                } else {
            		if ($this->treeTab[$y]["symbol"] == "minus") {
                        if (isset($PMF_CONF['mod_rewrite']) && $PMF_CONF['mod_rewrite'] == "TRUE") {
                            $output .= "<a title=\"".addslashes($description)."\" href=\"category".$this->treeTab[$y]["parent_id"].".html\"".$a.">".$categoryName."</a>";
                        } else {
            			    $output .= "<a title=\"".addslashes($description)."\" href=\"".$_SERVER["PHP_SELF"]."?".$sids."action=show&amp;cat=".$this->treeTab[$y]["parent_id"]."\"".$a.">".$categoryName."</a>";
                        }
                    } else {
            			if (isset($PMF_CONF['mod_rewrite']) && $PMF_CONF['mod_rewrite'] == "TRUE") {
                            $output .= "<a title=\"".addslashes($description)."\" href=\"category".$parent.".html\"".$a.">".$categoryName."</a>";
                        } else {
            			    $output .= "<a title=\"".addslashes($description)."\" href=\"".$_SERVER["PHP_SELF"]."?".$sids."action=show&amp;cat=".$parent."\"".$a.">".$categoryName."</a>";
                        }
                    }
                }
                $open = $level;
            }
            if ($open > 0) {
                $output .= str_repeat("</li>\n\t</ul>\n\t", $open);
            }
            $output .= "</li>";
            return $output;

        } else {
            $output = '<li><a href="#">'.$PMF_LANG['no_cats'].'</a></li>';
        }
        return $output;
    }

    /* checks if a category is a child node */
    function isChild($catID, $rootCatID)
    {
        // FIXME: rewrite without using the database
        $query = "SELECT parent_id FROM ".SQLPREFIX."faqcategories WHERE id = ".$rootCatID;
        $result = $this->db->query($query);
        while ($row = $this->db->fetch_object($result)) {
            if ($row->parent_id == $catID) {
                return FALSE;
            } else {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * getPath()
     *
     * Gets the path from root to child as breadcrumb
     *
     * @param   integer $id
     * @param   string  $separator
     * @param   bool    $showlinks
     * @return  string
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getPath($id, $separator = ' &raquo; ', $showlinks = false)
    {
        global $sids, $PMF_CONF;

        $ids = $this->getNodes($id);
        $num = count($ids);

        $temp = array();
        $catid = array();
        $desc = array();
        $breadcrumb = array();

        for ($i = 0; $i < $num; $i++) {
            $temp[] = $this->treeTab[$this->getLineCategory($ids[$i])]['name'];
            $catid[] = $this->treeTab[$this->getLineCategory($ids[$i])]['id'];
            $desc[] = $this->treeTab[$this->getLineCategory($ids[$i])]['description'];
        }
        if (isset($this->treeTab[$this->getLineCategory($id)]['name'])) {
            $temp[] = $this->treeTab[$this->getLineCategory($id)]['name'];
            $catid[] = $this->treeTab[$this->getLineCategory($id)]['id'];
            $desc[] = $this->treeTab[$this->getLineCategory($id)]['description'];
        }

        foreach ($temp as $k => $category) {
            if (isset($PMF_CONF['mod_rewrite']) && $PMF_CONF['mod_rewrite'] == true) {
                $breadcrumb[] = sprintf('<a title="%s" href="category%s.html">%s</a>', $desc[$k], $catid[$k], $category);
            } else {
                $breadcrumb[] = sprintf('<a title="%s" href="?%saction=show&amp;cat=%s">%s</a>', $desc[$k], $sids, $catid[$k], $category);
            }
        }

        if ($showlinks) {
            return implode($separator, $breadcrumb);
        } else {
            return implode($separator, $temp);
        }
	}

    /**
    * getCategoriesFromArticle
    *
    * Returns all categories that are related to the given article-id and
    * the current language $this->language in an unsorted array which consists
    * of associative arrays with the keys 'name', 'id', 'lang',
    * 'parent_id' and 'description'.
    *
    * @access   public
    * @param    integer   $article_id
    * @return   array   array(array('name'=>string,'id'=>int,'lang'=>string,'parent_id'=>int,'description'=>string),...)
    * @author   Lars Tiedemann <larstiedemann@yahoo.de>
    */
    function getCategoriesFromArticle($article_id) {
        $rel = SQLPREFIX."faqcategoryrelations";
        $cat = SQLPREFIX."faqcategories";
        $query = "SELECT ".$cat.".id AS id, ".$cat.".lang AS lang, ".$cat.".parent_id AS parent_id, ".$cat.".name AS name, ".$cat.".description AS description FROM ".$rel.", ".$cat." WHERE ".$cat.".id=".$rel.".category_id AND ".$rel.".record_id = ".$article_id." AND ".$rel.".category_lang = '".$this->language."'";
        $result = $this->db->query($query);
        $num = $this->db->num_rows($result);
        $this->categories = array();
        if ($num > 0) {
            while ($row = $this->db->fetch_assoc($result)) {
                $this->categories[] = $row;
            }
        }
        return $this->categories;
    }

    /**
    * getCategoryIdFromArticle
    *
    * Returns the ID of a category that associated with the given article.
    *
    * @access   public
    * @param    integer    $article_id
    * @return   integer
    * @author   Lars Tiedemann <larstiedemann@yahoo.de>
    */
    function getCategoryIdFromArticle($article_id) {
        $cats = $this->getCategoryIdsFromArticle($article_id);
        if (isset($cats[0])) {
            return $cats[0];
        } else {
            return null;
        }
    }

    /**
    * getCategoryIdsFromArticle
    *
    * Returns an array with the IDs of all categories that are associated with
    * the given article.
    *
    * @access   public
    * @param    integer   $article_id
    * @return   array
    * @author   Lars Tiedemann <larstiedemann@yahoo.de>
    */
    function getCategoryIdsFromArticle($article_id) {
        $cats = $this->getCategoriesFromArticle($article_id);
        $arr = array();
        foreach ($cats as $cat) {
            $arr[] = $cat['id'];
        }
        return $arr;
    }

    /* destructor */
    function __destruct()
    {

    }
}
