<?php
/**
* $Id: Glossary.php,v 1.11 2007-08-20 19:30:03 thorstenr Exp $
*
* The main glossary class
*
* @author    Thorsten Rinne <thorsten@phpmyfaq.de>
* @since     2005-09-15
* @copyright 2005 - 2007 phpMyFAQ Team
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
    * @param  object $db       Database object
    * @param  string $language Language
    * @return void
    * @author Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    public function __construct($db, $language)
    {
        $this->db       = $db;
        $this->language = $language;
    }

    /**
    * Gets all items and definitions from the database
    *
    * @return array
    * @access public
    * @author Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    public function getAllGlossaryItems()
    {
        $items = array();

        $result = $this->db->query(sprintf(
            "SELECT
                id, item, definition
            FROM
                %sfaqglossary
            WHERE
                lang = '%s'",
            SQLPREFIX,
            $this->language));
        while ($row = $this->db->fetch_object($result)) {
            $items[] = array(
                'id'            => $row->id,
                'item'          => stripslashes($row->item),
                'definition'    => stripslashes($row->definition));
        }
        return $items;
    }

    /**
     * Fill the passed string with the current Glossary items.
     *
     * @param  string $content Content
     * @return string
     * @access public
     * @author Matteo Scaramuccia <matteo@scaramuccia.com>
     * @since  2006-07-02
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

        foreach($this->getAllGlossaryItems() as $item) {
            $this->definition = $item['definition'];
            $item['item'] = preg_quote($item['item'], '/');
            $content = preg_replace_callback(
                '/'
                // a. the glossary item could be an attribute name
                .'('.$item['item'].'="[^"]*")|'
                // b. the glossary item could be inside an attribute value
                .'(('.implode('|', $attributes).')="[^"]*'.$item['item'].'[^"]*")|'
                // c. the glossary item could be everywhere as a distinct word
                .'(\s+)('.$item['item'].')(\s+)'
                .'/mis',
                array($this, '_setAcronyms'),
                $content);
        }

        return $content;
    }

    /**
     * Callback function for filtering HTML from URLs and images
     *
     * @param  array $matches Matchings
     * @access public
     * @return string
     * @since  2007-04-24
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    private function _setAcronyms($matches)
    {
        $prefix  = $matches[4];
        $item    = $matches[5];
        $postfix = $matches[6];

        if (!empty($item)) {
            return '<acronym class="glossary" title="'.$this->definition.'">'.$prefix.$item.$postfix.'</acronym>';
        }

        // Fallback: the original matched string
        return $matches[0];
    }

    /**
     * Gets one item and definition from the database
     *
     * @param  integer $id Glossary ID
     * @return array
     * @access public
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    public function getGlossaryItem($id)
    {
        $item = array();

        $result = $this->db->query(sprintf(
            "SELECT
                id, item, definition
            FROM
                %sfaqglossary
            WHERE
                id = %d AND lang = '%s'",
            SQLPREFIX,
            (int)$id,
            $this->language));
        while ($row = $this->db->fetch_object($result)) {
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
     * @access public
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    public function addGlossaryItem($item, $definition)
    {
        $this->item       = $this->db->escape_string($item);
        $this->definition = $this->db->escape_string($definition);

        $query = sprintf(
            "INSERT INTO
                %sfaqglossary
            (id, lang, item, definition)
                VALUES
            (%d, '%s', '%s', '%s')",
            SQLPREFIX,
            $this->db->nextID(SQLPREFIX.'faqglossary', 'id'),
            $this->language,
            $this->db->escape_string($this->item),
            $this->db->escape_string($this->definition));

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
     * @access public
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    public function updateGlossaryItem($id, $item, $definition)
    {
        $this->item       = $this->db->escape_string($item);
        $this->definition = $this->db->escape_string($definition);

        $query = sprintf(
            "UPDATE
                %sfaqglossary
            SET
                item = '%s',
                definition = '%s'
            WHERE
                id = %d AND lang = '%s'",
            SQLPREFIX,
            $this->db->escape_string($this->item),
            $this->db->escape_string($this->definition),
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
     * @access public
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    public function deleteGlossaryItem($id)
    {
        $query = sprintf(
            "DELETE FROM
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