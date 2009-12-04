<?php
/**
 * The main glossary class
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Glossary
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2005-09-15
 * @copyright  2005-2009 phpMyFAQ Team
 * @version    SVN: $Id$
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

/**
 * PMF_Glossary
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Glossary
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2005-09-15
 * @copyright  2005-2009 phpMyFAQ Team
 * @version    SVN: $Id$
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
        
        while ($row = $this->db->fetch_object($result)) {
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
     * @return string
     */
    private function _setAcronyms(Array $matches)
    {
    	if (count($matches) > 4) {
            $prefix  = $matches[4];
            $item    = $matches[5];
            $postfix = $matches[6];
    	}

        if (!empty($item)) {
            return sprintf($prefix .'<acronym class="glossary" title="%s">%s</acronym>'. $postfix,
                $this->definition,
                $item); 
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
     */
    public function addGlossaryItem($item, $definition)
    {
        $this->item       = $this->db->escape_string($item);
        $this->definition = $this->db->escape_string($definition);

        $query = sprintf("
            INSERT INTO
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
     */
    public function updateGlossaryItem($id, $item, $definition)
    {
        $this->item       = $this->db->escape_string($item);
        $this->definition = $this->db->escape_string($definition);

        $query = sprintf("
            UPDATE
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