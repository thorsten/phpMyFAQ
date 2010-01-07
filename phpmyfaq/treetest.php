<?php
error_reporting(E_ALL);

require "inc/functions.php";
require "inc/Exception.php";
require "inc/Db.php";
require "inc/PMF_DB/Driver.php";
require "inc/Language.php";
require "inc/Category.php";
require "inc/PMF_DB/Resultset.php";
require 'inc/PMF_Category/Abstract.php';
require 'inc/PMF_Category/Tree.php';
require 'inc/PMF_Category/Path.php';
require 'inc/PMF_Category/Tree/DataProvider/Interface.php';
require "inc/PMF_Category/Tree/DataProvider/SingleQuery.php";
require "inc/PMF_Category/Tree/DataProvider/MultiQuery.php";
require 'inc/PMF_Category/Tree/Helper.php';
require 'config/database.php';

define('SQLPREFIX', $DB['prefix']);
$db = PMF_Db::dbSelect($DB['type']);
$db->connect($DB['server'], $DB['user'], $DB['password'], $DB['db']);

$providers = array(new PMF_Category_Tree_DataProvider_SingleQuery(), new PMF_Category_Tree_DataProvider_MultiQuery);

foreach ($providers as $dataProvider) {
    echo "=== ".get_class($dataProvider)." ===\n";

    echo "Complete tree:\n";

    $trii = new PMF_Category_Tree_Helper(new PMF_Category_Tree($dataProvider));
    foreach ($trii as $key => $category) {
        echo str_repeat(' ', $trii->indent), $category." ($key)\n";
    }

    echo "\nOnly part of a tree:\n";

    $ct = new PMF_Category_Tree($dataProvider);
    $path = $dataProvider->getPath(6);
    $trii = new PMF_Category_Tree_Helper(new PMF_Category_Path($ct, $path));
    foreach ($trii as $key => $category) {
        echo str_repeat(' ', $trii->indent), $category." ($key)\n";
    }
}
