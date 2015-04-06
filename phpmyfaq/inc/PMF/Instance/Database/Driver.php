<?php
/**
 * Created by PhpStorm.
 * User: thorsten
 * Date: 06.04.15
 * Time: 12:48
 */

interface PMF_Instance_Database_Driver
{
    /**
     * Executes all CREATE TABLE and CREATE INDEX statements
     *
     * @param string $prefix
     *
     * @return boolean
     */
    public function createTables($prefix = '');
}
