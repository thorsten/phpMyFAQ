<?php
/**
* $Id: Glossary.php,v 1.2 2006-06-17 22:04:57 thorstenr Exp $
*
* The main glossary class
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @package      phpMyFAQ
* @since        2005-09-15
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

class PMF_Glossary
{
    /**
    * DB handle
    *
    * @var object
    */
    var $db;

    /**
    * Language
    *
    * @var	string
    */
    var $language;

    /**
    * Item
    *
    * @var	array
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
    * @param	object	$db
    * @param	string	$language
    * @return	void
    * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
    */
    function PMF_Glossary($db, $language)
    {
        $this->db 		= $db;
        $this->language = $language;
    }
    //*/



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
				'id' 			=> $row->id,
				'item' 			=> stripslashes($row->item),
				'definition' 	=> stripslashes($row->definition));
        }
        return $items;
    }
    //*/



    /**
    * Gets one item and definition from the database
    *
    * @param    integer	$id
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
				'id' 			=> $row->id,
				'item' 			=> stripslashes($row->item),
				'definition' 	=> stripslashes($row->definition));
        }
        return $item;
    }
    //*/



    /**
    * Inserts an item and definition into the database
    *
    * @param    string	$item
    * @param    string	$item
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
    //*/



    /**
    * Updates an item and definition into the database
    *
    * @param    integer	$id
    * @param    string	$item
    * @param    string	$definition
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
    //*/



    /**
    * Deletes an item and definition into the database
    *
    * @param    integer	$id
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
    //*/
}