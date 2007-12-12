<?php

/**
 * File description
 *
 * license text
 * 
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @version    $Id: Exception.php,v 1.1 2007-12-12 18:12:09 lars Exp $
 * @copyright  Copyright 2007 Lars Tiedemann
 * @since      litecoms-0.0.1
 */

/**
 * Class description
 *
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @package    LTC
 * @since      litecoms-0.0.1
 */
class LTC_Exception 
    extends Exception
{
 
    /**
     * Returns a formated string for error
     * message display.
     *
     * @access public
     * @return string full error message
     */
    public function __toString()
    {
        return sprintf(
            'Error %d: %s @ %s in %s on line %s',
            $this->getCode(),
            $this->getMessage(),
            '', //$this->getTraceAsString(),
            $this->getFile(),
            $this->getLine()
        );
    }
}
