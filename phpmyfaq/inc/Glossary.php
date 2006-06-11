<?php
/**
* $Id: Glossary.php,v 1.1 2006-06-11 14:26:55 matteo Exp $
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
    */
    var $db;
    
    /**
    * Language
    *
    */
    var $language;
    
    /**
    * Item
    *
    */
    var $item;
    
    /**
    * Definition of an item
    *
    */
    var $definition;
    
    
    /**
    * Constructor
    *
    * @param
    * @return
    */
    function PMF_Glossary($db, $language)
    {
        $this->db = $db;
        $this->language = $language;
    }
    //*/
    
    
    
    /**
    * Gets all items and definitions from the database
    *
    * @return   array
    * @access   public
    */
    function getAllGlossaryItems()
    {
        $items = array();
        
        $result = $this->db->query(sprintf("SELECT id, item, definition FROM %sfaqglossary WHERE lang = '%s'", SQLPREFIX, $this->language));
        while ($row = $this->db->fetch_object($result)) {
            $items[] = array('id' => $row->id, 'item' => stripslashes($row->item), 'definition' => stripslashes($row->definition));
        }
        
        return $items;
    }
    //*/
    
    
    
    /**
    * Gets one item and definition from the database
    *
    * @param    integer
    * @return   array
    * @access   public
    */
    function getGlossaryItem($id)
    {
        $item = array();
        
        $result = $this->db->query(sprintf("SELECT id, item, definition FROM %sfaqglossary WHERE id = %d AND lang = '%s'", SQLPREFIX, $id, $this->language));
        while ($row = $this->db->fetch_object($result)) {
            $item = array('id' => $row->id, 'item' => stripslashes($row->item), 'definition' => stripslashes($row->definition));
        }
        
        return $item;
    }
    //*/
        
    
    
    /**
    * Inserts an item and definition into the database
    *
    * @param    string
    * @param    string
    * @return   boolean
    * @access   public
    */
    function addGlossaryItem($item, $definition)
    {
        $this->item = $this->db->escape_string($item);
        $this->definition = $this->db->escape_string($definition);
        
        $query = sprintf("INSERT INTO %sfaqglossary (id, lang, item, definition) VALUES (%d, '%s', '%s', '%s')", SQLPREFIX, $this->db->nextID(SQLPREFIX.'faqglossary', 'id'), $this->language, $this->item, $this->definition);
        if ($this->db->query($query)) {
            return true;
        } else {
            return false;
        }
    }
    //*/
    
    
    
    /**
    * Updates an item and definition into the database
    *
    * @param    integer
    * @param    string
    * @param    string
    * @return   boolean
    * @access   public
    */
    function updateGlossaryItem($id, $item, $definition)
    {
        $this->item = $this->db->escape_string($item);
        $this->definition = $this->db->escape_string($definition);
        
        $query = sprintf("UPDATE %sfaqglossary SET item = '%s', definition = '%s' WHERE id = %d AND lang = '%s'", SQLPREFIX, $this->item, $this->definition, $id, $this->language);
        if ($this->db->query($query)) {
            return true;
        } else {
            return false;
        }
    }
    //*/
    
    
    
    /**
    * Deletes an item and definition into the database
    *
    * @param    integer
    * @return   boolean
    * @access   public
    */
    function deleteGlossaryItem($id)
    {
        $query = sprintf("DELETE FROM %sfaqglossary WHERE id = %d AND lang = '%s'", SQLPREFIX, $id, $this->language);
        if ($this->db->query($query)) {
            return true;
        } else {
            return false;
        }
    }
    //*/
}