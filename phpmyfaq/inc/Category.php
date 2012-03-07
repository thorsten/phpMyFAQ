<?php
/**
 * The main category class
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Category
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Lars Tiedemann <larstiedemann@yahoo.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Rudi Ferrari <bookcrossers@gmx.de>
 * @copyright 2004-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2004-02-16
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Category
 *
 * @category  phpMyFAQ
 * @package   PMF_Category
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Lars Tiedemann <larstiedemann@yahoo.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Rudi Ferrari <bookcrossers@gmx.de>
 * @copyright 2004-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2004-02-16
 */
class PMF_Category
{
    /**
     *
     * The database handler
     *
     * @var  object  PMF_Db
     */
    private $db = null;

    /**
     * User ID
     *
     * @var integer
     */
    private $user = null;

    /**
     * Groupd
     *
     * @var array
     */
    private $groups = array();

    /**
     *
     * The categories as an array.
     *
     * @var  array
     */
    public $categories = array();

    /**
     * The category names as an array.
     *
     * @var  array
     */
    public $categoryName = array();

    /**
     * The category tree
     *
     * @var  array
     */
    public $catTree = array();

    /**
     * The children nodes
     *
     * @var  array
     */
    private $children = array();

    /**
     * The current language
     *
     * @var  string
     */
    private $language = null;

    /**
     * The lines of tabs
     *
     * @var  array
     */
    private $lineTab = array();

    /**
     * The tree with the tabs
     *
     * @var  array
     */
    public $treeTab = array();

    /**
     * Symbol for each item
     * NOTE: We do not use this currently
     *
     * @var  array
     */
    private $symbols = array(
        'vertical' => '|',
        'plus'     => '+',
        'minus'    => '-',
        'space'    => '&nbsp;',
        'angle'    => '-',
        'medium'   => '|-');

    /**
     * Constructor
     *
     * @param  integer $user     User
     * @param  array   $groups   Group
     * @param  boolean $withperm With or without permission check
     * @return void
     */
    public function __construct($user = null, $groups = null, $withperm = true)
    {
        $this->language   = PMF_Language::$language;
        $this->db         = PMF_Db::getInstance();
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

        $this->lineTab = $this->getOrderedCategories($withperm);
        for ($i = 0; $i < count($this->lineTab); $i++) {
            $this->lineTab[$i]['level'] = $this->levelOf($this->lineTab[$i]['id']);
        }
    }

    /**
     * Returns all categories with ordered category IDs according to the user
     * and group permissions
     *
     * @param  boolean $withperm With or without permission check
     * @return array
     */
    private function getOrderedCategories($withperm = true)
    {
        $where = '';

        if ($withperm) {
            $where = sprintf("
            WHERE
                ( fg.group_id IN (%s)
            OR
                (fu.user_id = %d AND fg.group_id IN (%s)))",
            implode(', ', $this->groups),
            $this->user,
            implode(', ', $this->groups));
        }

        if (isset($this->language) && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $where .= empty($where) ? '
            WHERE' : '
            AND';
            $where .= "
                fc.lang = '".$this->language."'";
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
                fc.id, fc.lang, fc.parent_id, fc.name, fc.description, fc.user_id
            ORDER BY
                fc.id",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $where);

        $result = $this->db->query($query);
        while ($row = $this->db->fetchArray($result)) {
            $this->categoryName[$row['id']] = $row;
            $this->categories[] =& $this->categoryName[$row['id']];
            $this->children[$row['parent_id']][$row['id']] =& $this->categoryName[$row['id']];
        }

        return $this->categories;
    }

    /**
     * Gets the main categories and write them in an array
     *
     * @param  string  $cat       Array of parent category ids
     * @param  boolean $parent_id Only top level categories?
     * @return array
     */
    public function getCategories($categories, $parent_id = true)
    {
        $_query = '';
        $query  = sprintf('
            SELECT
                id, lang, parent_id, name, description, user_id
            FROM
                %sfaqcategories
            WHERE ',
            SQLPREFIX);
            
        if (true == $parent_id) {
            $query .= 'parent_id = 0';
        }
        foreach (explode(',', $categories) as $cats) {
            $_query .= ' OR parent_id = '.$cats;
        }
        if (false == $parent_id && 0 < PMF_String::strlen($_query)) {
            $query .= PMF_String::substr($_query, 4);
        }
        if (isset($this->language) && preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $query .= " AND lang = '".$this->language."'";
        }
        $query .= " ORDER BY id";
        $result = $this->db->query($query);
        while ($row = $this->db->fetchArray($result)) {
            $this->categories[$row['id']] = $row;
        }
        return $this->categories;
    }

    /**
     * Gets all categories and write them in an array
     *
     * @return array
     */
    public function getAllCategories()
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
        while ($row = $this->db->fetchArray($result)) {
            $this->categories[$row["id"]] = $row;
        }
        return $this->categories;
    }

    /**
     * Builds the category tree
     *
     * @param  integer $id_parent Parent id
     * @param  integer $indent    Indention
     * @return void
     */
    public function buildTree($id_parent = 0, $indent = 0)
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
                if (isset($this->categories[$d])) {
                    foreach ($this->categories[$d] as $key => $value) {
                        $tmp[$key] = $value;
                    }
                    $tmp["indent"] = $indent;
                    $this->catTree[] = $tmp;
                    $this->buildTree($tmp["id"], $indent + 1);
                }
            }
        }
    }

    /**
     * Get the level of the item id
     *
     * @param  integer $id Category id
     * @return integer
     */
    private function levelOf($id)
    {
    	$alreadies = array($id);
        $ret       = 0;
        while ((isset($this->categoryName[$id]['parent_id'])) && ($this->categoryName[$id]['parent_id'] != 0)) {
            $ret++;
            $id = $this->categoryName[$id]['parent_id'];
            
            if (in_array($id, $alreadies)) {
                break;
            } else {
                array_push($alreadies, $id);
            }
        }
        return $ret;
    }

    /**
     * Get the line number where to find the node $id
     *
     * @param  integer $id Category id
     * @return integer
     */
    private function getLine($id)
    {
        for ($i = 0; $i < count($this->lineTab); $i++) {
            if ($this->lineTab[$i]['id'] == $id) {
                return $i;
            }
        }
    }

    /**
     * Transforms the linear array in a 1D array in the order of the tree, with
     * the info
     *
     * @param  integer $id Category id
     * @return void
     */
    public function transform($id)
    {
        $thisParent_id = 0;
        $tree          = array();
        $tabs          = isset($this->children[$id]) ? array_keys($this->children[$id]) : array();
        $num           = count($tabs);

        if ($id > 0) {
            $thisLevel       = $this->categoryName[$id]['level'];
            $thisParent_id   = $this->categoryName[$id]['parent_id'];
            $thisName        = $this->categoryName[$id]['name'];
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

        $ascendants     = $this->getNodes($id);
        $num_ascendants = count($ascendants);

        if ($id > 0) {
            for ($i = 0; $i < $num_ascendants; $i++) {
                $brothers = $this->getBrothers($ascendants[$i]);
                $tree[$i] = ($ascendants[$i] == $brothers[count($brothers) - 1]) ? 'space' : 'vertical';
            }
        }

        if ($id > 0) {
            $this->treeTab[] = array('id'          => $id, 
                                     'symbol'      => $symbol, 
                                     'name'        => $thisName, 
                                     'numChilds'   => count($tabs), 
                                     'level'       => $thisLevel, 
                                     'parent_id'   => $thisParent_id, 
                                     'childs'      => $tabs, 
                                     'tree'        => $tree, 
                                     'description' => $thisdescription);
        }

        foreach ($tabs as $i) {
            $this->transform($i);
        }
    }

    /**
     * Get the line number where to find the node $id in the category tree
     *
     * @param  integer $id Category id
     * @return intger
     */
    private function getLineCategory($id)
    {
        for ($i = 0; $i < count($this->treeTab); $i++) {
            if (isset($this->treeTab[$i]['id']) && $this->treeTab[$i]['id'] == $id) {
                return $i;
            }
        }
    }

    //
    /**
     * List in a array of the $id of the child
     *
     * @param  integer $id Category id
     * @return array
     * @access public
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    public function getChildren($id)
    {
        return isset($this->children[$id]) ? array_keys($this->children[$id]) : array();
    }

    /**
     * list in a array of the $id of the child
     *
     * @param  integer $id Category id
     * @return array
     */
    public function getChildNodes($id)
    {
        $childs = array();

        if (isset($this->children[$id])) {
            foreach(array_keys($this->children[$id]) as $childId) {
                $childs = array_merge($childs, array($childId));
                $childs = array_merge($childs, $this->getChildNodes($childId));
            }
        }

        return $childs;
    }

    /**
     * number of childs of the $id
     *
     * @param  integer $id Category id
     * @return integer
     */
    private function numChilds($id)
    {
        return count($this->getNodes($id));
    }

    /**
     * List in array the root, super-root, ... of the $id
     *
     * @param  integer $id Category id
     * @return array
     */
    private function getNodes($id)
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

    /**
     * Collapse the complete category tree
     *
     * @return void
     */
    public function collapseAll()
    {
        for ($i = 0; $i < count($this->treeTab); $i++) {
            if ($this->treeTab[$i]["symbol"] == "minus") {
                $this->treeTab[$i]["symbol"] = "plus";
            }
        }
    }

    /**
     * expand the node $id
     *
     * @param  integer $id Category id
     * @return void
     */
    public function expand($id)
    {
        $this->treeTab[$this->getLineCategory($id)]["symbol"] = "minus";
    }

    // try to expand from the parent_id to the node $id
    public function expandTo($id)
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

    /**
     * expand the entire tree
     *
     * @return void
     */
    public function expandAll()
    {
        for ($i = 0; $i < count($this->treeTab); $i++) {
            if ($this->treeTab[$i]["symbol"] == "plus") {
                $this->treeTab[$i]["symbol"] = "minus";
            }
        }
    }

    /**
     * Total height of the expanded tree
     *
     * @return integer
     */
    public function height()
    {
        return count($this->treeTab);
    }

    /**
    * print the static tree with the number of records
    *
    * @return string
    */
    public function viewTree()
    {
        global $sids, $PMF_LANG, $plr;
        $totFaqRecords = 0;

        $query = sprintf("
            SELECT
                fcr.category_id AS category_id,
                count(fcr.category_id) AS number
            FROM
                %sfaqcategoryrelations fcr,
                %sfaqdata fd
            WHERE
                fcr.record_id = fd.id
            AND
                fcr.record_lang = fd.lang",
            SQLPREFIX,
            SQLPREFIX);

        if (strlen($this->language) > 0) {
            $query .= sprintf(" AND fd.lang = '%s'",
                $this->language);
        }

        $query .= sprintf("
            AND
                fd.active = 'yes'
            GROUP BY
                fcr.category_id",
            SQLPREFIX,
            SQLPREFIX);
        $result = $this->db->query($query);
        if ($this->db->numRows($result) > 0) {
            while ($row = $this->db->fetchObject($result)) {
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
                $output .= sprintf("\n%s<ul>\n%s<li>",
                    str_repeat("\t", $level + 1),
                    str_repeat("\t", $level + 1));
            } else {
                $output .= str_repeat("\t", $level + 1)."<li>";
            }

            if (0 == $number[$parent] && 0 == $level) {
                $num_entries = '';
            } else {
                $totFaqRecords += $number[$parent];
                $num_entries    = '<span id="rssCategoryLink"> ('.$plr->GetMsg('plmsgEntries',$number[$parent]);
                $num_entries   .= sprintf(
                    ' <a href="feed/category/rss.php?category_id=%d&category_lang=%s" target="_blank"><img id="category_%d_RSS" src="%s/images/feed.png" width="16" height="16" alt="RSS" border="0" /></a>',
                    $parent,
                    $this->language,
                    $parent,
                    PMF_Configuration::getInstance()->get('main.referenceURL')
                );
                $num_entries   .= ')</span>';
            }

            $url              = sprintf('%saction=show&amp;cat=%d', $sids, $parent);
            $oLink            = new PMF_Link(PMF_Link::getSystemRelativeUri() . '?' . $url);
            $oLink->itemTitle = $categoryName;
            $oLink->text      = $categoryName;
            $oLink->tooltip   = $description;
            
            $output .= $oLink->toHtmlAnchor() . $num_entries;
            $open    = $level;
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
     * @param  integer $y ID
     * @return array
     */
    public function getLineDisplay($y)
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
     * @param  integer $l Current line
     * @return integer
     */
    public function getNextLineTree($l)
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
     * @param  integer $id Brothers
     * @return array
     */
    private function getBrothers($id)
    {
        return $this->getChildren($this->categoryName[$id]['parent_id']);
    }

    /**
     * Creates a category link
     *
     * @param  string  $sids         Session id
     * @param  integer $categoryId   Parent category
     * @param  string  $categoryName Category name
     * @param  string  $description  Description
     * @param  boolean $hasChildren  Child categories available
     * @param  boolean $isActive     Sets a link active via CSS
     * @return  string
     */
    public function addCategoryLink($sids, $categoryId, $categoryName, $description, $hasChildren = false, $isActive = false)
    {
        $url              = sprintf('%saction=show&amp;cat=%d', $sids, $categoryId);
        $oLink            = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
        $oLink->id        = 'category_' . $categoryId;
        $oLink->itemTitle = $categoryName;
        $oLink->text      = $categoryName;
        
        if ($hasChildren) {
            $oLink->text .= sprintf(' <img src="%s/images/more.gif" width="11" height="11" alt="%s" style="border: none; vertical-align: middle;" />',
                PMF_Configuration::getInstance()->get('main.referenceURL'),
                $categoryName);
        }
        
        if ($isActive) {
            $oLink->class = 'active';
        }
        
        $oLink->tooltip = $description;
        
        return $oLink->toHtmlAnchor();
    }

    /**
     * Gets the path from root to child as breadcrumbs
     *
     * @param integer $id                Category ID
     * @param string  $separator         Path separator
     * @param boolean $renderAsMicroData Renders breadcrumbs as HTML5 microdata
     * @return string
     */
    public function getPath($id, $separator = ' &raquo; ', $renderAsMicroData = false)
    {
        global $sids;

        $ids = $this->getNodes($id);
        $num = count($ids);

        $temp = $catid = $desc = $breadcrumb = array();

        for ($i = 0; $i < $num; $i++) {
            $t = $this->getLineCategory($ids[$i]);
            if (array_key_exists($t, $this->treeTab)) {
                $temp[]  = $this->treeTab[$this->getLineCategory($ids[$i])]['name'];
                $catid[] = $this->treeTab[$this->getLineCategory($ids[$i])]['id'];
                $desc[]  = $this->treeTab[$this->getLineCategory($ids[$i])]['description'];
            }
        }
        if (isset($this->treeTab[$this->getLineCategory($id)]['name'])) {
            $temp[]  = $this->treeTab[$this->getLineCategory($id)]['name'];
            $catid[] = $this->treeTab[$this->getLineCategory($id)]['id'];
            $desc[]  = $this->treeTab[$this->getLineCategory($id)]['description'];
        }

        // @todo Maybe this should be done somewhere else ...
        if ($renderAsMicroData) {

            foreach ($temp as $k => $category) {
                $url              = sprintf('%saction=show&amp;cat=%d', $sids, $catid[$k]);
                $oLink            = new PMF_Link(PMF_Link::getSystemRelativeUri().'?' . $url);
                $oLink->text      = sprintf('<span itemprop="title">%s</span>', $category);
                $oLink->itemTitle = $category;
                $oLink->tooltip   = $desc[$k];
                $oLink->setItemProperty('url');
                if (0 == $k) {
                    $oLink->setRelation('index');
                }

                $breadcrumb[] = sprintf(
                    '<li itemscope itemtype="http://data-vocabulary.org/Breadcrumb">%s</li>',
                    $oLink->toHtmlAnchor()
                );
            }
            
            $temp = $breadcrumb;
        }
        
        return '<ul class="breadcrumb">'. implode(
            '<span class="divider">' . $separator . '</span>',
            $temp
        ) . '</ul>';
    }

    /**
     * Returns the categories from a record id and language
     *
     * @param  integer $record_id   record id
     * @param  integer $record_lang record language
     * @return array
     */
    public function getCategoryRelationsFromArticle($record_id, $record_lang)
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
        while ($row = $this->db->fetchObject($result)) {
            $categories[] = array(
                'category_id'   => $row->category_id,
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
     * @param integer $article_id Record id
     *
     * @return  array   
     */
    public function getCategoriesFromArticle($articleId)
    {
        $query = sprintf("
            SELECT
                fc.id AS id,
                fc.lang AS lang,
                fc.parent_id AS parent_id,
                fc.name AS name,
                fc.description AS description
            FROM
                %sfaqcategoryrelations fcr,
                %sfaqcategories fc
            WHERE
                fc.id = fcr.category_id
            AND
                fcr.record_id = %d
            AND
                fcr.category_lang = '%s'
            AND
                fc.lang = '%s'",
            SQLPREFIX, 
            SQLPREFIX,
            $articleId,
            $this->language,
            $this->language);
            
        $result = $this->db->query($query);
        $num    = $this->db->numRows($result);
        $this->categories = array();
        if ($num > 0) {
            while ($row = $this->db->fetchArray($result)) {
                $this->categories[] = $row;
            }
        }
        return $this->categories;
    }

    /**
     * Returns the ID of a category that associated with the given article.
     *
     * @param  integer $article_id Record id
     * @return integer
     */
    public function getCategoryIdFromArticle($article_id)
    {
        $cats = $this->getCategoryIdsFromArticle($article_id);
        if (isset($cats[0])) {
            return $cats[0];
        } else {
            return 0;
        }
    }

    /**
     * Returns an array with the IDs of all categories that are associated with
     * the given article.
     *
     * @param  integer $article_id Record id
     * @return array
     */
    public function getCategoryIdsFromArticle($article_id)
    {
        $cats = $this->getCategoriesFromArticle($article_id);
        $arr  = array();
        foreach ($cats as $cat) {
            $arr[] = $cat['id'];
        }
        return $arr;
    }

    /**
     * Returns the admin user of the selected category
     *
     * @param   integer $category_id Category id
     * @return  integer
     * @todo    Return the name, not the ID
     */
    public function getCategoryUser($category_id)
    {
        return $this->categoryName[$category_id]['user_id'];
    }

    /**
     * Adds a new category entry
     *
     * @param  array   $category_data Array of category data
     * @param  integer $parent_id     Parent id
     * @param  integer $id            Category id
     * @return integer
     */
    public function addCategory(Array $category_data, $parent_id = 0, $id = null)
    {
        if (!is_array($category_data)) {
            return false;
        }

        // If we only need a new language, we don't need a new category id
        if (is_null($id)) {
            $id = $this->db->nextId(SQLPREFIX.'faqcategories', 'id');
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
     * @param  array   $category_data Array of category data
     * @return boolean
     */
    public function updateCategory(Array $category_data)
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
     * @param  integer $from Old user id
     * @param  integer $to   New user id
     * @return boolean
     */
    public function moveOwnership($from, $to)
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
     * @param  integer $category_id   Category id
     * @param  string  $category_lang Category language
     * @return boolean
     */
    public function checkLanguage($category_id, $category_lang)
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
        return $this->db->numRows($result);
    }

    /**
     * Swaps two categories
     *
     * @param  integer $category_id_1 First category
     * @param  integer $category_id_2 Second category
     * @return boolean
     */
    public function swapCategories($category_id_1, $category_id_2)
    {
        $temp_cat = rand(200000, 400000);

        $tables = array(
            array('faqcategories'        => 'id'),
            array('faqcategories'        => 'parent_id'),
            array('faqcategoryrelations' => 'category_id'),
            array('faqcategory_group'    => 'category_id'),
            array('faqcategory_user'     => 'category_id'));

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
        
        $tables2 = array(array('faqquestions' => 'category_id'));

        foreach ($tables2 as $pair) {
            foreach ($pair as $_table => $_field) {
                $result = $result && $this->db->query(sprintf("UPDATE %s SET %s = '%d' WHERE %s = '%d'",
                    SQLPREFIX.$_table,
                    $_field,
                    $temp_cat,
                    $_field,
                    $category_id_2));
                $result = $result && $this->db->query(sprintf("UPDATE %s SET %s = '%d' WHERE %s = '%d'",
                    SQLPREFIX.$_table,
                    $_field,
                    $category_id_2,
                    $_field,
                    $category_id_1));
                $result = $result && $this->db->query(sprintf("UPDATE %s SET %s = '%d' WHERE %s = '%d'",
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
     * @param  integer $category_id Category id
     * @param  integer $parent_id   Parent category id
     * @return boolean
     */
    public function updateParentCategory($category_id, $parent_id)
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
     * @param  integer $category_id   Category id
     * @param  string  $category_lang Categiry language
     * @param  boolean $delete_all    Delete all languages?
     * @return boolean
     */
    public function deleteCategory($category_id, $category_lang, $delete_all = false)
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
     * @param  integer $category_id   Category id
     * @param  string  $category_lang Categiry language
     * @param  boolean $delete_all    Delete all languages?
     * @return boolean
     */
    public function deleteCategoryRelation($category_id, $category_lang, $delete_all = false)
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
    public function getCategoryLanguagesTranslated($category_id)
    {
        global $languageCodes;

        $existcatlang = PMF_Utils::languageAvailable($category_id, 'faqcategories');
        $translated   = array();

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
           if ($row = $this->db->fetchArray($result)) {
              $translated[$languageCodes[strtoupper($language)]] = $row['name'].('' == $row['description'] ? '' : '  ('.$row['description'].')');
           }
        }
        ksort($translated);

        return $translated;
    }

    /**
     * Create all languagess which can be used for translation as <option>
     *
     * @param  integer $category_id   Category id
     * @param  string  $selected_lang Selected language
     * @return string
     */
    public function getCategoryLanguagesToTranslate($category_id, $selected_lang)
    {
        $output       = "";
        $existcatlang = PMF_Utils::languageAvailable($category_id, 'faqcategories');

        foreach (PMF_Language::getAvailableLanguages() as $lang => $langname) {
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
     * @return void
     */
    public function getMissingCategories()
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
        while ($row = $this->db->fetchArray($result)) {
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
     * @param  integer $parent_id Parent id
     * @return integer
     */
    public function numParent($parent_id)
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

        return $this->db->numRows($result);
    }

    /**
     * Adds the category permissions for users and groups
     *
     * @param  string  $mode       'group' or 'user'
     * @param  array   $categories ID of the current category
     * @param  integer $id         group ID or user ID
     * @return boolean
     */
    public function addPermission($mode, Array $categories, $id)
    {
        if (!($mode == "user" || $mode == "group")) {
            return false;
        }
        if (!is_array($categories)) {
            return false;
        }

        foreach ($categories as $category_id) {
        	$query = "SELECT * FROM %sfaqcategory_%s WHERE category_id = %d AND %s_id = %d";
        	$query = sprintf($query, SQLPREFIX, $mode, $category_id, $mode, $id);
        	
        	if($this->db->numRows($this->db->query($query))) {
        		continue;
        	}
        	
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
     * @param  string $mode       'group' or 'user'
     * @param  array  $categories ID of the current category
     * @return boolean
     */
    public function deletePermission($mode, $categories)
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
     * @param   string $mode       'group' or 'user'
     * @param   array  $categories Array of category ids
     * @return  array
     */
    public function getPermissions($mode, Array $categories)
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
            implode(', ', $categories));

        $result = $this->db->query($query);
        while ($row = $this->db->fetchObject($result)) {
            $permissions[] = $row->permission;
        }
        return $permissions;
    }

    /**
     * Returns the number of records in each category
     *
     * @return array
     */
    public function getNumberOfRecordsOfCategory()
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
            GROUP BY fcr.category_id",
            SQLPREFIX,
            SQLPREFIX);

        $result = $this->db->query($query);
        if ($this->db->numRows($result) > 0) {
            while ($row = $this->db->fetchObject($result)) {
                $numRecordsByCat[$row->category_id] = $row->number;
            }
        }

        return $numRecordsByCat;
    }

    /**
     * Create a matrix for representing categories and faq records
     *
     * @return array
     */
    public function getCategoryRecordsMatrix()
    {
        $matrix = array();

        $query = sprintf('
            SELECT
                fcr.category_id AS id_cat,
                fd.id AS id
            FROM
                %sfaqdata fd
            INNER JOIN
                %sfaqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.category_lang
            ORDER BY
                fcr.category_id, fd.id',
             SQLPREFIX,
             SQLPREFIX);
        $result = $this->db->query($query);

        if ($this->db->numRows($result) > 0) {
            while ($row = $this->db->fetchObject($result)) {
                $matrix[$row->id_cat][$row->id] = true;
            }
        }

        return $matrix;
    }

    /**
     * Sets language
     *
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }
}
