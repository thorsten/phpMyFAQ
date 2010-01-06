<?php
error_reporting(E_ALL);
define('SQLPREFIX' ,'');
define('PMF_ROOT_DIR', __DIR__);

$sql_type = 'mysql';
$sql_server = 'localhost';
$sql_user = 'root';
$sql_password = 'filter';
$sql_db = 'prospero';

require PMF_ROOT_DIR . "/inc/functions.php";
require PMF_ROOT_DIR . "/inc/Exception.php";
require PMF_ROOT_DIR . "/inc/Db.php";
require PMF_ROOT_DIR . "/inc/PMF_DB/Driver.php";
require PMF_ROOT_DIR . "/inc/Language.php";
require PMF_ROOT_DIR . "/inc/CategoryNew.php";
require PMF_ROOT_DIR . "/inc/PMF_DB/Resultset.php";
require PMF_ROOT_DIR . '/inc/PMF_Category/Abstract.php';
require PMF_ROOT_DIR . '/inc/PMF_Category/Tree.php';
require PMF_ROOT_DIR . '/inc/PMF_Category/Path.php';
require PMF_ROOT_DIR . '/inc/PMF_Category/Tree/DataProvider/Interface.php';
require PMF_ROOT_DIR . "/inc/PMF_Category/Tree/DataProvider/SingleQuery.php";
require PMF_ROOT_DIR . "/inc/PMF_Category/Tree/DataProvider/MultiQuery.php";

require PMF_ROOT_DIR . '/inc/PMF_Category/Tree/Helper.php';

$db = PMF_Db::dbSelect($sql_type);
$db->connect($sql_server, $sql_user, $sql_password, $sql_db);

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
