<?php
/**
 * $Id: Category.php,v 1.47 2007-03-30 14:13:18 thorstenr Exp $
 *
 * The main category class
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Lars Tiedemann <larstiedemann@yahoo.de>
 * @author      Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author      Rudi Ferrari <bookcrossers@gmx.de>
 * @since       2004-02-16
 * @copyright   (c) 2004-2007 phpMyFAQ Team
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

// {{{ Includes
/**
 * This include is needed for accessing to mod_rewrite support configuration value
 */
require_once('Link.php');
// }}}

class PMF_Category
{
    /**
     *
     * The database handler
     *
     * @var  object  PMF_Db
     */
    var $db = null;

    /**
     * User ID
     *
     * @var integer
     */
    var $user = null;

    /**
     * Groupd
     *
     * @var array
     */
    var $groups = array();

    /**
     *
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
     * @param   string  $language
     * @param   integer $user
     * @param   array   $groups
     * @param   boolean $withperm
     * @return  void
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function PMF_Category ($language = '', $user = null, $groups = null, $withperm = true)
    {
        global $db;

        $this->language   = $language;
        $this->db         = &$db;
        $this->categories = array();

        if (is_null($user)) {
            $this->user  = -1;
        } else {
            $this->user  = $user;
        }
        if (is_null($groups)) {
            $this->groups = array(-1);
        } else {
            $this->groups = $groups;
        }

        $this->lineTab    = $this->getOrderedCategories($withperm);
        for ($i = 0; $i < count($this->lineTab); $i++) {
            $this->lineTab[$i]['level'] = $this->levelOf($this->lineTab[$i]['id']);
        }
    }

    /**
     * Returns all categories with ordered category IDs according to the user
     * and group permissions
     *
     * @param   boolean $withperm
     * @return  array
     * @access  private
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function &getOrderedCategories($withperm = true)
    {
        $where = '';
        if ($withperm) {
            $where = sprintf("
            WHERE
                ( fu.user_id = %d
            OR
                fg.group_id IN (%s) )",
            $this->user,
            implode(', ', $this->groups));
        }
        if (isset($this->language) && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $where .= empty($where) ? '
            WHERE' : '
            AND';
            $where .= "
                lang = '".$this->language."'";
        }

        $query = sprintf("
            SELECT
                fc.id AS id,
                fc.lang AS lang,
                fc.parent_id AS parent_id,
                fc.name AS name,
                fc.description AS description,
                fc.user_id AS user_id
            FROM
                %sfaqcategories fc
            LEFT JOIN
                %sfaqcategory_group fg
            ON
                fc.id = fg.category_id
            LEFT JOIN
                %sfaqcategory_user fu
            ON
                fc.id = fu.category_id%s
            GROUP BY
                id
            ORDER BY
                id",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $where
        );

        $result = $this->db->query($query);
        while ($row = $this->db->fetch_assoc($result)) {
            $this->categoryName[$row['id']] = $row;
            $this->categories[] =& $this->categoryName[$row['id']];
            $this->children[$row['parent_id']][$row['id']] =& $this->categoryName[$row['id']];
        }

        return $this->categories;
    }

    /**
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
        $query = sprintf('
            SELECT
                id, lang, parent_id, name, description, user_id
            FROM
                %sfaqcategories
            WHERE ',
            SQLPREFIX);
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
            $query .= " AND lang = '".$this->language."'";
        }
        $query .= " ORDER BY id";
        $result = $this->db->query($query);
        while ($row = $this->db->fetch_assoc($result)) {
            $this->categories[] = $row;
        }
        return $this->categories;
    }

    /**
     * Gets all categories and write them in an array
     *
     * @return  array
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getAllCategories()
    {
        $query = sprintf("
            SELECT
                id, lang, parent_id, name, description, user_id
            FROM
                %sfaqcategories",
            SQLPREFIX);
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

    /**
    * levelOf()
    *
    * Get the level of the item id
    */
    function levelOf($id)
    {
        $ret = 0;
        while ((isset($this->categoryName[$id]['parent_id'])) && ($this->categoryName[$id]['parent_id'] != 0)) {
            $ret++;
            $id = $this->categoryName[$id]['parent_id'];
        }
        return $ret;
    }

    /**
    * getLine()
    *
    * Get the line number where to find the node $id
    */
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

    // list in a array of the $id of the child
    function getChildNodes($id)
    {
        $childs = array();

        if (isset($this->children[$id])) {
            foreach(array_keys($this->children[$id]) as $childId) {
                $childs = array_merge($childs, array($childId));
                $childs = array_merge($childs, $this->getChildNodes($childId));
            }
        }

        return($childs);
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
        global $sids, $PMF_LANG;
        $totFaqRecords = 0;

        $query = sprintf("
            SELECT
                %sfaqcategoryrelations.category_id AS category_id,
                count(%sfaqcategoryrelations.category_id) AS number
            FROM
                %sfaqcategoryrelations,
                %sfaqdata
            WHERE
                %sfaqcategoryrelations.record_id = %sfaqdata.id
            AND
                %sfaqcategoryrelations.record_lang = %sfaqdata.lang",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX);

        if (strlen($this->language) > 0) {
            $query .= sprintf(" AND %sfaqdata.lang = '%s'",
                SQLPREFIX,
                $this->language);
        }

        $query .= sprintf("
            AND
                %sfaqdata.active = 'yes'
            GROUP BY
                %sfaqcategoryrelations.category_id",
            SQLPREFIX,
            SQLPREFIX);
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
            $level = $this->treeTab[$y]['level'];
            $leveldiff = $open - $level;

            if ($leveldiff > 1) {
                $output .= '</li>';
                for ($i = $leveldiff; $i > 1; $i--) {
                    $output .= sprintf("\n%s</ul>\n%s</li>\n",
                        str_repeat("\t", $level + $i + 1),
                        str_repeat("\t", $level + $i));
                }
            }

            if (!isset($number[$parent])) {
                $number[$parent] = 0;
            }

            if ($level < $open) {
                if (($level - $open) == -1) {
                    $output .= '</li>';
                }
                $output .= sprintf("\n%s</ul>\n%s</li>\n",
                    str_repeat("\t", $level + 2),
                    str_repeat("\t", $level + 1));
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
                $totFaqRecords += $number[$parent];
                $num_entries = sprintf(' (%d %s)', $number[$parent], $PMF_LANG['msgEntries']);
            }

            $url = sprintf('%saction=show&amp;cat=%d',
                        $sids,
                        $parent
                    );
            $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
            $oLink->itemTitle = $categoryName;
            $oLink->text = $categoryName;
            $oLink->tooltip = $description;
            $output .= $oLink->toHtmlAnchor().$num_entries;

            $open = $level;
        }

        if (isset($level) && $level > 0) {
            $output .= str_repeat("</li>\n\t</ul>\n\t", $level);
        }

        $output .= "\t</li>\n";
        $output .= "\t</ul>\n";
        $output .= '<span id="totFaqRecords" style="display: none;">'.$totFaqRecords."</span>\n";
        return $output;
    }

    /**
     * Returns the three parts of a line to display: last part of tree,
     * category name, and id of the root node
     *
     * @param     integer
     * @return    array
     * @access    private
     * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getLineDisplay($y)
    {
        $ret[0] = $this->symbols[$this->treeTab[$y]["symbol"]];
        $ret[1] = $this->treeTab[$y]["name"];
        $ret[2] = $this->treeTab[$y]["id"];
        $ret[3] = $this->treeTab[$y]["description"];
        return $ret;
    }

    /**
     * Gets the next line in the array treeTab, depending of the
     * collapse/expand node
     *
     * @param     integer
     * @return    integer
     * @access    private
     * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
     */
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
     * Gets the list of the brothers of $id (include $id)
     *
     * @param   integer
     * @return  array
     * @access  public
     */
    function getBrothers($id)
    {
        $ret = $this->getChildren($this->categoryName[$id]['parent_id']);
        return $ret;
    }

    /**
     * Get all categories in <option> tags
     *
     * @param   mixed
     * @return  string
     * @access  public
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
        $output = '';

        if ($this->height() > 0) {
            for ($y = 0 ;$y < $this->height(); $y = $this->getNextLineTree($y)) {
                list($symbol, $categoryName, $parent, $description) = $this->getLineDisplay($y);

                if ($activeCat == $parent) {
                    $a = ' class="active"';
                } else {
                    $a = '';
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
                    $output .= $this->addCategoryLink($sids, $parent, $categoryName, $description, true);
                } else {
                    if ($this->treeTab[$y]['symbol'] == 'minus') {
                        $name = ($this->treeTab[$y]['parent_id'] == 0) ? $categoryName : $this->categoryName[$this->treeTab[$y]['id']]['name'];
                        $output .= $this->addCategoryLink($sids, $this->treeTab[$y]['parent_id'], $name, $description);
                    } else {
                        $output .= $this->addCategoryLink($sids, $parent, $categoryName, $description);
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

    /**
     * Private method to create a category link
     *
     * @param   string  $sids
     * @param   integer $parent
     * @param   string  $categoryName
     * @param   string  $description
     * @param   boolean $hasChildren
     * @return  string
     * @access  private
     * @since   2007-02-10
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function addCategoryLink($sids, $parent, $categoryName, $description, $hasChildren = false)
    {
        $url = sprintf('%saction=show&amp;cat=%d', $sids, $parent);
        $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
        $oLink->itemTitle = $categoryName;
        $oLink->text = $categoryName;
        if ($hasChildren) {
            $oLink->text .= sprintf(' <img src="images/more.gif" width="11" height="11" alt="%s" style="border: none; vertical-align: middle;" />',
                $categoryName);
        }
        $oLink->tooltip = $description;
        return $oLink->toHtmlAnchor();
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
            $url = sprintf('%saction=show&amp;cat=%d',
                        $sids,
                        $catid[$k]
                    );
            $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
            $oLink->itemTitle = $category;
            $oLink->text = $category;
            $oLink->tooltip = $desc[$k];
            $breadcrumb[] = $oLink->toHtmlAnchor();
        }

        if ($showlinks) {
            return implode($separator, $breadcrumb);
        } else {
            return implode($separator, $temp);
        }
    }

    /**
     * Returns the categories from a record id and language
     *
     * @param   integer $record_id
     * @param   integer $record_lang
     * @return  array
     * @access  public
     * @since   2006-11-12
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getCategoryRelationsFromArticle($record_id, $record_lang)
    {
        $categories = array();

        $query = sprintf("
            SELECT
                category_id, category_lang
            FROM
                %sfaqcategoryrelations
            WHERE
                record_id = %d
            AND
                record_lang = '%s'",
            SQLPREFIX,
            $record_id,
            $record_lang);
        $result = $this->db->query($query);
        while ($row = $this->db->fetch_object($result)) {
            $categories[] = array(
                'category_id' => $row->category_id,
                'category_lang' => $row->category_lang);
        }

        return $categories;
    }

    /**
     * Returns all categories that are related to the given article-id and
     * the current language $this->language in an unsorted array which consists
     * of associative arrays with the keys 'name', 'id', 'lang',
     * 'parent_id' and 'description'.
     *
     * @access  public
     * @param   integer   $article_id
     * @return  array   array(array('name'=>string,'id'=>int,'lang'=>string,'parent_id'=>int,'description'=>string),...)
     * @author  Lars Tiedemann <larstiedemann@yahoo.de>
     */
    function getCategoriesFromArticle($article_id)
    {
        $rel = SQLPREFIX."faqcategoryrelations";
        $cat = SQLPREFIX."faqcategories";
        $query = sprintf("
            SELECT
                %s.id AS id,
                %s.lang AS lang,
                %s.parent_id AS parent_id,
                %s.name AS name,
                %s.description AS description
            FROM
                %s,
                %s
            WHERE
                %s.id = %s.category_id
            AND
                %s.record_id = %d
            AND
                %s.category_lang = '%s'
            AND
                %s.lang = '%s'",
            $cat, $cat, $cat, $cat, $cat,
            $rel, $cat,
            $cat, $rel,
            $rel, $article_id,
            $rel, $this->language,
            $cat, $this->language);
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
    function getCategoryIdFromArticle($article_id)
    {
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
    function getCategoryIdsFromArticle($article_id)
    {
        $cats = $this->getCategoriesFromArticle($article_id);
        $arr = array();
        foreach ($cats as $cat) {
            $arr[] = $cat['id'];
        }
        return $arr;
    }

    /**
     * Returns the admin user of the selected category
     *
     * @param   integer $category_id
     * @return  integer $user_id
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @todo    Return the name, not the ID
     */
    function getCategoryUser($category_id)
    {
        return $this->categoryName[$category_id]['user_id'];
    }

    /**
     * Adds a new category entry
     *
     * @param   array   $category_data
     * @param   integer $parent_id
     * @param   integer $id
     * @return  integer
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function addCategory($category_data, $parent_id = 0, $id = null)
    {
        if (!is_array($category_data)) {
            return false;
        }

        // If we only need a new language, we don't need a new category id
        if (is_null($id)) {
            $id = $this->db->nextID(SQLPREFIX.'faqcategories', 'id');
        }

        $query = sprintf("
            INSERT INTO
                %sfaqcategories
            (id, lang, parent_id, name, description, user_id)
                VALUES
            (%d, '%s', %d, '%s', '%s', %d)",
            SQLPREFIX,
            $id,
            $category_data['lang'],
            $parent_id,
            $category_data['name'],
            $category_data['description'],
            $category_data['user_id']);
        $this->db->query($query);

        return $id;
    }

    /**
     * Updates an existent category entry
     *
     * @param   array   $category_data
     * @return  boolean
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function updateCategory($category_data)
    {
        if (!is_array($category_data)) {
            return false;
        }

        $query = sprintf("
            UPDATE
                %sfaqcategories
            SET
                name = '%s',
                description = '%s',
                user_id = %d
            WHERE
                id = %d
            AND
                lang = '%s'",
            SQLPREFIX,
            $category_data['name'],
            $category_data['description'],
            $category_data['user_id'],
            $category_data['id'],
            $category_data['lang']);
        $this->db->query($query);

        return true;
    }

    /**
     * Move the categories ownership, if any.
     *
     * @param   integer $from
     * @param   integer $to
     * @return  boolean
     * @access  public
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     * @since   2006-08-20
     */
    function moveOwnership($from, $to)
    {
        if (!is_numeric($from) || !is_numeric($to)) {
            return false;
        }

        $query = sprintf("
            UPDATE
                %sfaqcategories
            SET
                user_id = %d
            WHERE
                user_id = %d",
            SQLPREFIX,
            $to,
            $from
            );
        $this->db->query($query);

        return true;
    }

    /**
     * Checks wether a language is already defined for a category id
     *
     * @param   integer $category_id
     * @param   string  $category_lang
     * @return  boolean
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function checkLanguage($category_id, $category_lang)
    {
        $query = sprintf("
            SELECT
                lang
            FROM
                %sfaqcategories
            WHERE
                id = %d
            AND
                lang = '%s'",
            SQLPREFIX,
            $category_id,
            $category_lang);

        $result = $this->db->query($query);
        return $this->db->num_rows($result);
    }

    /**
     * Swaps two categories
     *
     * @param   integer $category_id_1
     * @param   integer $category_id_2
     * @return  boolean
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function swapCategories($category_id_1, $category_id_2)
    {
        $temp_cat = rand(200000, 400000);

        $tables = array(
            array('faqcategories' => 'id'),
            array('faqcategories' => 'parent_id'),
            array('faqcategoryrelations' => 'category_id'),
            array('faqquestions' => 'ask_rubrik'),
            array('faqcategory_group' => 'category_id'),
            array('faqcategory_user' => 'category_id'));

        $result = true;
        foreach ($tables as $pair) {
            foreach ($pair as $_table => $_field) {
                $result = $result && $this->db->query(sprintf("UPDATE %s SET %s = %d WHERE %s = %d",
                    SQLPREFIX.$_table,
                    $_field,
                    $temp_cat,
                    $_field,
                    $category_id_2));
                $result = $result && $this->db->query(sprintf("UPDATE %s SET %s = %d WHERE %s = %d",
                    SQLPREFIX.$_table,
                    $_field,
                    $category_id_2,
                    $_field,
                    $category_id_1));
                $result = $result && $this->db->query(sprintf("UPDATE %s SET %s = %d WHERE %s = %d",
                    SQLPREFIX.$_table,
                    $_field,
                    $category_id_1,
                    $_field,
                    $temp_cat));
            }
        }

        return $result;
    }

    /**
     * Updates the parent category
     *
     * @param   integer $category_id
     * @param   integer $parent_id
     * @return  boolean
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function updateParentCategory($category_id, $parent_id)
    {
        if ((!is_numeric($category_id) || !is_numeric($parent_id)) && $category_id != $parent_id) {
            return false;
        }

        $query = sprintf("
            UPDATE
                %sfaqcategories
            SET
                parent_id = %d
            WHERE
                id = %d",
            SQLPREFIX,
            $parent_id,
            $category_id);
        $this->db->query($query);

        return true;
    }

    /**
     * Deletes a category
     *
     * @param   integer $category_id
     * @param   string  $category_lang
     * @param   bool    $delete_all languages?
     * @return  boolean
     * @access  public
     * @since   2006-09-11
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function deleteCategory($category_id, $category_lang, $delete_all = false)
    {
        $query = sprintf("
            DELETE FROM
                %sfaqcategories
            WHERE
                id = %d",
            SQLPREFIX,
            $category_id);
        if (!$delete_all) {
           $query .= " AND lang = '".$category_lang."'";
        }
        $this->db->query($query);

        return true;
    }
    /**
     * Deletes a category relation
     *
     * @param   integer $category_id
     * @param   string  $category_lang
     * @param   bool    $delete_all
     * @return  boolean
     * @access  public
     * @since   2006-09-11
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */

    function deleteCategoryRelation($category_id, $category_lang, $delete_all = false)
    {
        $query = sprintf("
            DELETE FROM
                %sfaqcategoryrelations
            WHERE
                category_id = %d",
            SQLPREFIX,
            $category_id);
        if (!$delete_all) {
           $query .= " AND category_lang = '".$category_lang."'";
        }
        $this->db->query($query);

        return true;
    }

    /**
     * Create array with translated categories
     *
     * @param   integer  $category_id
     * @return  array
     * @access  public
     * @since   2006-09-10
     * @author  Rudi Ferrari <bookcrossers@gmx.de>
     */
    function getCategoryLanguagesTranslated($category_id)
    {
        global $languageCodes;
        $existcatlang = check4Language($category_id, 'faqcategories');
        $translated = array();

        foreach ($existcatlang as $language) {
           $query = sprintf("
               SELECT
                  name, description
               FROM
                   %sfaqcategories
               WHERE
                   id = %d
               AND
                   lang = '%s'",
               SQLPREFIX,
               $category_id,
               $language);
           $result = $this->db->query($query);
           if ($row = $this->db->fetch_assoc($result)) {
              $translated[$languageCodes[strtoupper($language)]] = $row['name'].('' == $row['description'] ? '' : '  ('.$row['description'].')');
           }
        }
        ksort($translated);

        return $translated;
    }

    /**
     * Create all languagess which can be used for translation as <option>
     *
     * @param   integer  $category_id
     * @param   string   $selected_lang
     * @return  string
     * @access  public
     * @since   2006-09-10
     * @author  Rudi Ferrari <bookcrossers@gmx.de>
     */
    function getCategoryLanguagesToTranslate($category_id, $selected_lang)
    {
        global $languageCodes;
        $output = "";
        $existcatlang = check4Language($category_id, 'faqcategories');

        foreach (getAvailableLanguages() as $lang => $langname) {
           if (!in_array(strtolower($lang),$existcatlang)) {
              $output .= "\t<option value=\"".strtolower($lang)."\"";
              if ($lang == $selected_lang) {
                 $output .= " selected=\"selected\"";
              }
              $output .=  ">".$langname."</option>\n";
           }
        }

        return $output;
    }

    /**
     * Gets all categories which are not translated in actual language
     * to add in this->categories (used in admin section)
     *
     * @access  public
     * @since   2006-09-16
     * @author  Rudi Ferrari <bookcrossers@gmx.de>
     */
    function getMissingCategories()
    {
        $query = sprintf("
            SELECT
                id, lang, parent_id, name, description, user_id
            FROM
                %sfaqcategories",
            SQLPREFIX);
        if (isset($this->language) && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $query .= " WHERE lang != '".$this->language."'";
        }
        $query .= ' ORDER BY id';
        $result = $this->db->query($query);
        while ($row = $this->db->fetch_assoc($result)) {
            if (!array_key_exists($row['id'],$this->categoryName)) {
               $this->categoryName[$row['id']] = $row;
               $this->categories[] =& $this->categoryName[$row['id']];
               $this->children[$row['parent_id']][$row['id']] =& $this->categoryName[$row['id']];
            }
        }
    }

    /**
     * Get number of nodes at the same parent_id level
     *
     * @param   integer  $parent_id
     * @return  integer
     * @access  public
     * @since   2006-10-10
     * @author  Rudi Ferrari <bookcrossers@gmx.de>
     */
    function numParent($parent_id)
    {
        $query = sprintf("
            SELECT distinct
                id
            FROM
                %sfaqcategories
            WHERE
                parent_id = %d",
            SQLPREFIX,
            $parent_id);
        $result = $this->db->query($query);

        return $this->db->num_rows($result);
    }

    /**
     * Adds the category permissions for users and groups
     *
     * @param   string  $mode           'group' or 'user'
     * @param   array   $categories     ID of the current category
     * @param   integer $id             group ID or user ID
     * @return  boolean
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function addPermission($mode, $categories, $id)
    {
        if (!($mode == "user" || $mode == "group")) {
            return false;
        }
        if (!is_array($categories) && !is_int($id)) {
            return false;
        }

        foreach ($categories as $category_id) {
            $query = sprintf("
                INSERT INTO
                    %sfaqcategory_%s
                (category_id, %s_id)
                    VALUES
                (%d, %d)",
                SQLPREFIX,
                $mode,
                $mode,
                $category_id,
                $id);

            $this->db->query($query);
        }

        return true;
    }

    /**
     * Deletes the category permissions for users and groups
     *
     * @param   string  $mode           'group' or 'user'
     * @param   array   $categories     ID of the current category
     * @return  boolean
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function deletePermission($mode, $categories)
    {
        if (!($mode == "user" || $mode == "group")) {
            return false;
        }
        if (!is_array($categories)) {
            return false;
        }

        foreach ($categories as $category_id) {
            $query = sprintf("
                DELETE FROM
                    %sfaqcategory_%s
                WHERE
                    category_id = %d",
                SQLPREFIX,
                $mode,
                $category_id);
            $this->db->query($query);
        }

        return true;
    }

    /**
     * Returns the category permissions for users and groups
     *
     * @param   string  $mode           'group' or 'user'
     * @param   integer $categories
     * @return  array
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getPermissions($mode, $categories)
    {
        $permissions = array();
        if (!($mode == "user" || $mode == "group")) {
            return false;
        }
        if (!is_array($categories)) {
            return false;
        }

        $query = sprintf("
            SELECT
                %s_id AS permission
            FROM
                %sfaqcategory_%s
            WHERE
                category_id IN (%s)",
            $mode,
            SQLPREFIX,
            $mode,
            implode(', ', $categories)
            );

        $result = $this->db->query($query);
        while ($row = $this->db->fetch_object($result)) {
            $permissions[] = $row->permission;
        }
        return $permissions;
    }

    /**
     * Returns the number of records in each category
     *
     * @param   string  $active
     * @return  array
     * @access  public
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getNumberOfRecordsOfCategory($active = 'yes')
    {
        $numRecordsByCat = array();

        $query = sprintf("
            SELECT
                fcr.category_id AS category_id,
                COUNT(fcr.record_id) AS number
            FROM
                %sfaqcategoryrelations fcr, %sfaqdata fd
            WHERE
                fcr.record_id = fd.id
            AND
                fcr.record_lang = fd.lang
            AND
                fd.active = '%s'
            GROUP BY fcr.category_id",
            SQLPREFIX,
            SQLPREFIX,
            $active);

        $result = $this->db->query($query);
        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetch_object($result)) {
                $numRecordsByCat[$row->category_id] = $row->number;
            }
        }

        return $numRecordsByCat;
    }

    /**
     * Create a matrix for representing categories and faq records
     *
     * @return  array   $matrix
     * @access  public
     * @since   2007-02-18
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getCategoryRecordsMatrix()
    {
        $matrix = array();

        $query = sprintf('
            SELECT
                %sfaqcategoryrelations.category_id AS id_cat,
                %sfaqdata.id AS id
            FROM
                %sfaqdata
            INNER JOIN
                %sfaqcategoryrelations
            ON
                %sfaqdata.id = %sfaqcategoryrelations.record_id
            AND
                %sfaqdata.lang = %sfaqcategoryrelations.category_lang
            ORDER BY
                %sfaqcategoryrelations.category_id, %sfaqdata.id',
             SQLPREFIX,
             SQLPREFIX,
             SQLPREFIX,
             SQLPREFIX,
             SQLPREFIX,
             SQLPREFIX,
             SQLPREFIX,
             SQLPREFIX,
             SQLPREFIX,
             SQLPREFIX);
        $result = $this->db->query($query);

        if ($this->db->num_rows($result) > 0) {
            while ($row = $this->db->fetch_object($result)) {
                $matrix[$row->id_cat][$row->id] = true;
            }
        }

        return $matrix;
    }
}
