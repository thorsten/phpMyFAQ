<?php
/**
* $Id: Glossary.php,v 1.7 2007-05-03 20:08:33 matteo Exp $
*
* The main glossary class
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @package      phpMyFAQ
* @since        2005-09-15
* @copyright    (c) 2005-2007 phpMyFAQ Team
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
    * @var  object
    */
    var $db;

    /**
    * Language
    *
    * @var  string
    */
    var $language;

    /**
    * Item
    *
    * @var  array
    */
    var $item;

    /**
    * Definition of an item
    *
    * @var
    */
    var $definition;

    /**
    * Constructor
    *
    * @param    object    $db
    * @param    string    $language
    * @return   void
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function PMF_Glossary(&$db, $language)
    {
        $this->db       = &$db;
        $this->language = $language;
    }

    /**
    * Gets all items and definitions from the database
    *
    * @return   array
    * @access   public
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function getAllGlossaryItems()
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
     * insertItemsIntoContent()
     *
     * Fill the passed string with the current Glossary items.
     *
     * @param   string
     * @return  string
     * @access  public
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     * @since   2006-07-02
     */
    function insertItemsIntoContent($content = '')
    {
        if ('' == $content) {
            return '';
        }

        $attributes = array(
            'href', 'src', 'title', 'alt', 'class', 'style', 'id', 'name',
            'face', 'size', 'dir', 'onclick', 'ondblclick', 'onmousedown',
            'onmouseup', 'onmouseover', 'onmousemove', 'onmouseout',
            'onkeypress', 'onkeydown', 'onkeyup'
        );

        foreach($this->getAllGlossaryItems() as $item) {
            $this->definition = $item['definition'];
            $content = preg_replace_callback(
                '/'
                // a. the glossary item could be an attribute name
                .'('.$item['item'].'="[^"]*")|'
                // b. the glossary item could be inside an attribute value
                .'(('.implode('|', $attributes).')="[^"]*'.$item['item'].'[^"]*")|'
                // c. the glossary item could be everywhere as a distinct word
                .'(\s+)('.$item['item'].')(\s+)'
                .'/mis',
                array($this, 'setAcronyms'),
                $content
            );
        }

        return $content;
    }

    /**
     * Callback function for filtering HTML from URLs and images
     *
     * @param   array
     * @access  public
     * @return  string
     * @since   2007-04-24
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     * @author  Matteo Scaramuccia <matteo@scaramuccia.com>
     */
    function setAcronyms($matches)
    {
        $itemAsAttrName = $matches[1];
        $itemInAttrValue = $matches[2]; // $matches[3] is the attribute name
        $prefix = $matches[4];
        $item = $matches[5];
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
    * @param    integer    $id
    * @return   array
    * @access   public
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function getGlossaryItem($id)
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
                'id'            => $row->id,
                'item'          => stripslashes($row->item),
                'definition'    => stripslashes($row->definition));
        }
        return $item;
    }

    /**
    * Inserts an item and definition into the database
    *
    * @param    string    $item
    * @param    string    $item
    * @return   boolean
    * @access   public
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function addGlossaryItem($item, $definition)
    {
        $this->item = $this->db->escape_string($item);
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
            $this->item,
            $this->definition);
        if ($this->db->query($query)) {
            return true;
        }
        return false;
    }

    /**
    * Updates an item and definition into the database
    *
    * @param    integer    $id
    * @param    string     $item
    * @param    string     $definition
    * @return   boolean
    * @access   public
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function updateGlossaryItem($id, $item, $definition)
    {
        $this->item = $this->db->escape_string($item);
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
            $this->item,
            $this->definition,
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
    * @param    integer    $id
    * @return   boolean
    * @access   public
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function deleteGlossaryItem($id)
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
