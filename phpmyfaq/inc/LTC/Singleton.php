<?php

error_reporting(E_ALL);

/**
 * ltc_model - LTC\interface.Singleton.php
 *
 * $Id: Singleton.php,v 1.1 2007-12-12 18:12:09 lars Exp $
 *
 * This file is part of ltc_model.
 *
 * Automatic generated with ArgoUML 0.24 on 17.06.2007, 14:38:07
 *
 * @author Lars Tiedemann, <php@larstiedemann.de>
 * @package    LTC
 */
 

/**
 * Short description of class LTC_Singleton
 *
 * @access public
 * @author Lars Tiedemann, <php@larstiedemann.de>
 * @package    LTC
 */
interface LTC_Singleton
{
    // --- OPERATIONS ---

    /**
     * Short description of method getInstance
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return LTC_Singleton
     */
    public static function getInstance();

    /**
     * Short description of method init
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    public function init();

} /* end of interface LTC_Singleton */


