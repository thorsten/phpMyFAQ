<?php
/*
$m = new LTC_Model();
$m = new LTC_Model_Decorator_Id($m);
$m->init();
if (false == ($m instanceof LTC_Model)) {
    trigger_error('$m = new LTC_Model_Decorator_Id(new LTC_Model()); is not an instance of LTC_Model ');
}
$m->newId();

*/

require_once dirname(__FILE__).'/init.inc.php';

class LTC_Model_TestTable
    extends LTC_Model_Abstract
{
    public function init()
    {
        $this->fields = array(
            'testtable_id' => array(
                'primary_key' => true,
                'auto_increment' => false,
                'type' => 'INTEGER',
                'length' => 11,
                'null' => false,
                'default' => null,
            ),
            'name' => array(
                'primary_key' => false,
                'type' => 'VARCHAR',
                'length' => 20,
                'null' => false,
                'default' => null,
            ),
        );
    }

}

// get LTC_Db
$db = LTC_Db::getInstance();

// __construct()
$tt = new LTC_Model_TestTable();

// LTC_Model::getModel()
$tt1 = LTC_Model::getModel('TestTable');
if (print_r($tt, true) == print_r($tt1, true)) {
    trigger_error("LTC_Model::getModel('TestTable') returned the same result as new LTC_Model_TestTable(). init() should have been called");
}

// init()
$tt->init();
if (print_r($tt, true) != print_r($tt1, true)) {
    trigger_error("LTC_Model::getModel('TestTable') did not return the same result as new LTC_Model_TestTable() with init() call. ");
}

// getPrimaryKey()
$pk = $tt->getPrimaryKey();
if ($pk == false) {
    trigger_error("LTC_Model::getPrimaryKey() returned false. ");
}
if (!is_string($pk)) {
    trigger_error("LTC_Model::getPrimaryKey() did not return string. ");
}
if ($pk == false) {
    trigger_error("LTC_Model::getPrimaryKey() returned false. ");
}

// getName()
$tableName = $tt->getName();
if (!is_string($tableName)) {
    trigger_error("LTC_Model::getName() did not return string. " . $tableName);
}
if (strlen($tableName) == 0) {
    trigger_error("LTC_Model::getName() returned empty string. " . $tableName);
}
if ($tableName != 'TestTable') {
    trigger_error("LTC_Model::getName() did not return 'TestTable', but " . $name);
}

// getTableName()
$tableName = $tt->getTableName();
if (!is_string($tableName)) {
    trigger_error("LTC_Model::getTableName() did not return string. " . $tableName);
}
if (strlen($tableName) == 0) {
    trigger_error("LTC_Model::getTableName() returned empty string. " . $tableName);
}
if ($tableName != $db->getTablePrefix() . 'testtable') {
    trigger_error("LTC_Model::getTableName() did not return " . $db->getTablePrefix() . 'testtable' . ", but " . $tableName);
}

// getAllFieldProperties() 
$fieldProps = $tt->getAllFieldProperties();
if (!is_array($fieldProps)) {
    trigger_error("LTC_Model::getAllFieldProperties() did not return array. " . print_r($fieldProps, true));
}
if (count($fieldProps) != 2) {
    trigger_error("LTC_Model::getAllFieldProperties() did not return array with two members. " . print_r($fieldProps, true));
}
if (!isset($fieldProps['testtable_id']) or !isset($fieldProps['name'])) {
    trigger_error("LTC_Model::getAllFieldProperties() does not contain fields 'testtable_id' or 'name'. " . print_r($fieldProps, true));
}

// getFieldProperties() 
$fieldProps = $tt->getFieldProperties('testtable_id');
if (!is_array($fieldProps)) {
    trigger_error("LTC_Model::getFieldProperties('testtable_id') did not return array. " . print_r($fieldProps, true));
}
if (count($fieldProps) <= 0) {
    trigger_error("LTC_Model::getFieldProperties('testtable_id') returned empty array. " . print_r($fieldProps, true));
}
if (!isset($fieldProps['primary_key']) or $fieldProps['primary_key'] != true) {
    trigger_error("LTC_Model::getFieldProperties('testtable_id') does not contain 'primary_key' = TRUE. " . print_r($fieldProps, true));
}

// LTC_Model::install()
$install = $tt->install();
if (!is_bool($install)) {
    trigger_error("LTC_Model::install() did not return boolean. ");
}
if ($install == false) {
    trigger_error("LTC_Model::install() returned false. ");
}
if ($db->error() != false) {
    trigger_error("LTC_Db::error() did not return false. Something went wrong while LTC_Model::install() was performed. ");
}

// LTC_Model::clear()
$clear = $tt->clear();
if (!is_bool($clear)) {
    trigger_error("LTC_Model::clear() did not return boolean. ");
}
if ($clear !== false) {
    trigger_error("LTC_Model::clear() did not return false. ");
}

// LTC_Model::insert() and find()
$id = $tt->insert(array(
    'testtable_id' => null,
    'name'         => 'entry 1', 
));
if ($id !== false) {
    trigger_error("LTC_Model::insert(array) did not return false. ".$db->error());
}
$find = $tt->find(array('testtable_id' => 1));
if (is_array($find) and count($find) > 0) {
    trigger_error("LTC_Model::find('testtable_id' => 1) returned an array. " . print_r($find, true));
}
$id = $tt->insert(array(
    'testtable_id' => 999,
    'name'         => 'entry 999', 
));
if ($id === 0) {
    trigger_error("LTC_Model::insert(array) returned int 0. ".$db->error());
}
if ($id == false) {
    trigger_error("LTC_Model::insert(array) returned false. ".$db->error());
}
if ($id != 999) {
    trigger_error("LTC_Model::insert(array) did not return 99. ".$db->error());
}
$find = $tt->find(array('testtable_id' => 999));
if (!is_array($find) or count($find) == 0) {
    trigger_error("LTC_Model::find('testtable_id' => 999) did not return an array. " . print_r($find, true));
}
if (count($find) != 1) {
    trigger_error("LTC_Model::find() did not return a single row. ");
}
if (!isset($find[0]['name']) or !isset($find[0]['testtable_id'])) {
    trigger_error("LTC_Model::find() does not contain all fields. ");
}
if ($find[0]['testtable_id'] != 999 or $find[0]['name'] != 'entry 999') {
    trigger_error("LTC_Model::find() did not contain the expected row. ");
}

// insert() with auto_increment
class LTC_Model_TestTableWithAutoIncrement
    extends LTC_Model_TestTable
{
    public function init()
    {
        parent::init();
        $this->name = 'testtable';
        $this->fields['testtable_id']['auto_increment'] = true;
    }

}
$tt = LTC_Model::getModel('TestTableWithAutoIncrement');
$id = $tt->insert(array(
    'testtable_id' => null,
    'name'         => 'entry 1', 
));
if ($id == false) {
    trigger_error("LTC_Model::insert(array) returned false. ".$db->error());
}
if ($id !== 1) {
    trigger_error("LTC_Model::insert(array) did not return (int) 1.".$db->error());
}

// find
$expected = array(
    'testtable_id' => 1,
    'name'         => 'entry 1',
);
$result = $tt->find(array('testtable_id' => 1));
if (print_r($expected, true) !== print_r($result[0], true)) {
    trigger_error("LTC_Model::find(array('testtable_id' => 1)) did not return expected " . print_r($expected, true) . ", but " . print_r($result[0], true));
}

// findById()
$found = $tt->findById(1);
if (!is_array($found)) {
    trigger_error("LTC_Model::findById(1) did not return array. " . print_r($found, true));
}
if (print_r($found, true) != print_r($expected, true)) {
    trigger_error("LTC_Model::findById(1) did not return expected result. " . print_r($found, true));
}

// updateById()
$updated = $tt->updateById(1, array('name' => 'entry 1 updated'));
if (!is_bool($updated)) {
    trigger_error("LTC_Model::update() does not return boolean value. ");
}
if ($updated != true) {
    trigger_error("LTC_Model::update() did not return true. ");
}

// LTC_Model::clear()
$clear = $tt->clear();
if (!is_bool($clear)) {
    trigger_error("LTC_Model::clear() did not return boolean. ");
}
if ($clear !== true) {
    trigger_error("LTC_Model::clear() did not return true. ");
}

// LTC_Model::uninstall()
$uninstall = $tt->uninstall();

// delete testtable_id from sequencetable
$seq = LTC_Model::getModel('SequenceTable');
$seq->delete(array('key_name' => $tt->getPrimaryKey()));

//trigger_error($db->getLog());

