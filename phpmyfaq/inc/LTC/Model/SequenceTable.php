<?php

/**
 * File description
 *
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: SequenceTable.php,v 1.1 2007-12-12 18:12:09 lars Exp $
 * @copyright  Copyright 2007 Lars Tiedemann
 * @since      17.08.2007
 */

/**
 * The sequence table is used to store ID values (integer values) for all 
 * models to emulate auto_increment option even if the database driver does not
 * support auto_increment. 
 * 
 * The database table sequencetable consists of two columns: `key_name` and 
 * `key_value`. key_name contains the name of the ID field of a model and 
 * key_value contains the current ID used in that model. On each insert(), 
 * key_value is incremented and the new ID is assigned to the newly inserted 
 * data set. 
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @package    LTC
 * @subpackage Model
 * @since      litecoms-0.0.1
 */
class LTC_Model_SequenceTable
    extends LTC_Model_Abstract
{

    /**
     * Initializes the model. 
     *
     * @access public
     * @return void
     */
    public function init()
    {
        $this->fields['key_name'] = array(
            'primary_key'    => true,
            'auto_increment' => false,
            'type'           => 'text',
            'length'         => 50,
            'null'           => false,
            'default'        => null,
            // optional
            //'unique'         => true,
            //'index'          => null,
        );
        $this->fields['key_value'] = array(
            'primary_key'    => false,
            'auto_increment' => false,
            'type'           => 'int',
            'length'         => 10,
            'null'           => false,
            'default'        => 0,
            // optional
            //'unique'         => false,
            //'index'          => null,
        );
    }
    
}


