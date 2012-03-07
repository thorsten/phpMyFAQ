<?php
/**
 * The main glossary class
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Glossary
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-15
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Glossary
 *
 * @category  phpMyFAQ
 * @package   PMF_Glossary
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-15
 */
class PMF_Glossary
{
    /**
     * DB handle
     *
     * @var PMF_DB
     */
    private $db = null;

    /**
     * Language
     *
     * @var string
     */
    private $language = '';

    /**
     * Item
     *
     * @var array
     */
    private $item = array();

    /**
     * Definition of an item
     *
     * @var string
     */
    private $definition = '';

    /**
    * Constructor
    *
    * @return void
    */
    public function __construct()
    {
        $this->db       = PMF_Db::getInstance();
        $this->language = PMF_Language::$language;
    }

    /**
    * Gets all items and definitions from the database
    *
    * @return array
    */
    public function getAllGlossaryItems()
    {
        $items = array();

        $query = sprintf("
            SELECT
                id, item, definition
            FROM
                %sfaqglossary
            WHERE
                lang = '%s'",
            SQLPREFIX,
            $this->language);
            
        $result = $this->db->query($query);
        
        while ($row = $this->db->fetchObject($result)) {
            $items[] = array(
                'id'         => $row->id,
                'item'       => stripslashes($row->item),
                'definition' => stripslashes($row->definition));
        }
        return $items;
    }

    /**
     * Fill the passed string with the current Glossary items.
     *
     * @param  string $content Content
     * @return string
     */
    public function insertItemsIntoContent($content = '')
    {
        if ('' == $content) {
            return '';
        }

        $attributes = array(
            'href', 'src', 'title', 'alt', 'class', 'style', 'id', 'name',
            'face', 'size', 'dir', 'onclick', 'ondblclick', 'onmousedown',
            'onmouseup', 'onmouseover', 'onmousemove', 'onmouseout',
            'onkeypress', 'onkeydown', 'onkeyup');

        foreach ($this->getAllGlossaryItems() as $item) {
            $this->definition = $item['definition'];
            $item['item']     = preg_quote($item['item'], '/');
            $content          = PMF_String::preg_replace_callback(
                '/'
                // a. the glossary item could be an attribute name
                .'('.$item['item'].'="[^"]*")|'
                // b. the glossary item could be inside an attribute value
                .'(('.implode('|', $attributes).')="[^"]*'.$item['item'].'[^"]*")|'
                // c. the glossary item could be everywhere as a distinct word
                .'(\W+)('.$item['item'].')(\W+)|'
                // d. the glossary item could be at the begining of the string as a distinct word
                .'^('.$item['item'].')(\W+)|'
                // e. the glossary item could be at the end of the string as a distinct word
                .'(\W+)('.$item['item'].')$'
                .'/mis',
                array($this, 'setTooltip'),
                $content); 
        }

        return $content;
    }

    /**
     * Callback function for filtering HTML from URLs and images
     *
     * @param  array $matches Matchings
     *
     * @return string
     */
    public function setTooltip(Array $matches)
    {
        if (count($matches) > 9) {
            // if the word is at the end of the string
            $prefix  = $matches[9];
            $item    = $matches[10];
            $postfix = '';
        } elseif (count($matches) > 7) {
            // if the word is at the begining of the string
            $prefix  = '';
            $item    = $matches[7];
            $postfix = $matches[8];
        } elseif (count($matches) > 4) {
            // if the word is else where in the string
            $prefix  = $matches[4];
            $item    = $matches[5];
            $postfix = $matches[6];
        }
        
        if (!empty($item)) {
            return sprintf('%s<a rel="tooltip" data-original-title="%s">%s</a>%s',
                $prefix,
                $this->definition,
                $item,
                $postfix);
        }
        
        // Fallback: the original matched string
        return $matches[0];
    }

    /**
     * Gets one item and definition from the database
     *
     * @param  integer $id Glossary ID
     * @return array
     */
    public function getGlossaryItem($id)
    {
        $item = array();

        $query = sprintf("
            SELECT
                id, item, definition
            FROM
                %sfaqglossary
            WHERE
                id = %d AND lang = '%s'",
            SQLPREFIX,
            (int)$id,
            $this->language);
            
        $result = $result = $this->db->query($query);
           
        while ($row = $this->db->fetchObject($result)) {
            $item = array(
                'id'         => $row->id,
                'item'       => stripslashes($row->item),
                'definition' => stripslashes($row->definition));
        }
        return $item;
    }

    /**
     * Inserts an item and definition into the database
     *
     * @param  string $item       Item
     * @param  string $definition Definition
     * @return boolean
     */
    public function addGlossaryItem($item, $definition)
    {
        $this->item       = $this->db->escape($item);
        $this->definition = $this->db->escape($definition);

        $query = sprintf("
            INSERT INTO
                %sfaqglossary
            (id, lang, item, definition)
                VALUES
            (%d, '%s', '%s', '%s')",
            SQLPREFIX,
            $this->db->nextId(SQLPREFIX.'faqglossary', 'id'),
            $this->language,
            $this->db->escape($this->item),
            $this->db->escape($this->definition));

        if ($this->db->query($query)) {
            return true;
        }
        return false;
    }

    /**
     * Updates an item and definition into the database
     *
     * @param  integer $id         Glossary ID
     * @param  string  $item       Item
     * @param  string  $definition Definition
     * @return boolean
     */
    public function updateGlossaryItem($id, $item, $definition)
    {
        $this->item       = $this->db->escape($item);
        $this->definition = $this->db->escape($definition);

        $query = sprintf("
            UPDATE
                %sfaqglossary
            SET
                item = '%s',
                definition = '%s'
            WHERE
                id = %d AND lang = '%s'",
            SQLPREFIX,
            $this->db->escape($this->item),
            $this->db->escape($this->definition),
            (int)$id,
            $this->language);

        if ($this->db->query($query)) {
            return true;
        }
        return false;
    }

    /**
     * Deletes an item and definition into the database
     *
     * @param  integer $id Glossary ID
     * @return boolean
     */
    public function deleteGlossaryItem($id)
    {
        $query = sprintf("
            DELETE FROM
                %sfaqglossary
            WHERE
                id = %d AND lang = '%s'",
            SQLPREFIX,
            (int)$id,
            $this->language);

        if ($this->db->query($query)) {
            return true;
        }
        return false;
    }
}