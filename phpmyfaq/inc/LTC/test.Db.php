<?php

include_once dirname(__FILE__).'/init.inc.php';

// getSupportedDbTypes
$dbTypes = LTC_Db::getSupportedDbTypes();
if (!is_array($dbTypes) or empty($dbTypes)) {
    trigger_error("LTC_Db::getSupportedDbTypes() does not return array. ");
}
if (!isset($dbTypes['pdo'])) {
    trigger_error("LTC_Db::getSupportedDbTypes(): Returned array does not contain 'pdo' key. ");
}
// createDb
$pdo = LTC_Db::createDb('pdo');
if (false == ($pdo instanceof LTC_Db_Interface) or false == ($pdo instanceof LTC_Db_Pdo)) {
    trigger_error(sprintf("LTC_Db::createDb(%s) does not return LTC_Db_Pdo object: %s", $dbTypes['pdo'], print_r($pdo, true)));
}
// setConfig, getConfig
$config = array('myconfig' => 'blub');
$pdo->setConfig($config);
if ($pdo->getConfig() !== $config) {
    trigger_error("LTC_Db::getConfig() did not return array that was set with LTC_Db::setConfig(). ");
}
// getInstance
$db1 = LTC_Db::getInstance();
if (($db1 instanceof LTC_Db_Interface) == false) {
    trigger_error("LTC_Db::getInstance() does not return LTC_Db_Interface instance on first call.");
}
// checking singleton implementation
if (false == ($db1 instanceof LTC_Singleton)) {
    trigger_error("LTC_Db::getInstance() does not return object that implements the LTC_Singleton API.");
}
$db2 = LTC_Db::getInstance();
$db2->setConfig(array('newConfig' => "newconfig"));
if (print_r($db1->getConfig(), true) !== print_r($db2->getConfig(), true)) {
    trigger_error("LTC_Db::getInstance() does not return the same instance when called twice. ");
}


