<?php

require_once dirname(dirname(dirname(__FILE__))) . '/init.inc.php';
$db = LTC_Db::getInstance();

class LTC_Model_TestModel1
    extends LTC_Model
{
    public function init()
    {
        $this->fields['id'] = array(
            'primary_key'    => true,
            'auto_increment' => true,
            'type'           => 'int',
            'length'         => 10,
            'null'           => false,
            'default'        => null,
            // optional
            //'unique'         => true,
            //'index'          => null,
        );
        $this->fields['field1'] = array(
            'primary_key'    => false,
            'auto_increment' => false,
            'type'           => 'text',
            'length'         => 50,
            'null'           => false,
            'default'        => '',
            // optional
            //'unique'         => true,
            //'index'          => null,
        );
    }    
}

class LTC_Model_TestModel2
    extends LTC_Model
{
    public function init()
    {
        $this->fields['id'] = array(
            'primary_key'    => true,
            'auto_increment' => false,
            'type'           => 'int',
            'length'         => 10,
            'null'           => false,
            'default'        => null,
            // optional
            //'unique'         => true,
            //'index'          => null,
        );
        $this->fields['field2'] = array(
            'primary_key'    => false,
            'auto_increment' => false,
            'type'           => 'text',
            'length'         => 50,
            'null'           => false,
            'default'        => '',
            // optional
            //'unique'         => true,
            //'index'          => null,
        );
    }
}

// install model with association
$m1 = LTC_Model::getModel('TestModel1');
$m2 = LTC_Model::getModel('TestModel2');
$m  = LTC_Model::hasOne($m1, $m2);
$m->install();

// insert
$id = $m->insert(array('field1' => 'field1', 'field2' => 'field2'));
if (!is_int($id) or $id <= 0) {
    trigger_error("LTC_Model_Decorator_HasOne::insert(array('field1' => 'field1', 'field2' => 'field2')) did not return integer value. ");
}

// find
$find = $m->find(array('id' => $id));
if (!is_array($find)) {
    trigger_error("LTC_Model_Decorator_HasOne::find(array('id' => $id)) does not return array. ");
}
if (count($find) !== 1) {
    trigger_error("LTC_Model_Decorator_HasOne::find(array('id' => $id)) does not contain exactly one results set. ");
}
if (!isset($find[0]['field1']) or $find[0]['field1'] != 'field1') {
    trigger_error("LTC_Model_Decorator_HasOne::find(array('id' => $id)) does not contain 'field1'. ");
}
if (!isset($find[0]['field2']) or $find[0]['field2'] != 'field2') {
    trigger_error("LTC_Model_Decorator_HasOne::find(array('id' => $id)) does not contain 'field2'. ");
}

// update
$update = $m->update(array('id' => $id), array('field1' => 'field1_2', 'field2' => 'field2_2'));
if ($update !== true) {
    trigger_error("LTC_Model_Decorator_HasOne::update(array('id' => $id), array('field1' => 'field1_2', 'field2' => 'field2_2')) did not return boolean TRUE. ");
}

// delete
$delete = $m->delete(array('id' => $id));
if ($delete !== true) {
    trigger_error("LTC_Model_Decorator_HasOne::delete() does not return boolean true. ");
}

// clear
$id = $m->insert(array('field1' => 'field1', 'field2' => 'field2'));
$clear = $m->clear();
if ($clear !== true) {
    trigger_error("LTC_Model_Decorator_HasOne::clear() does not return boolean true. ");
}

// find with options
$id = 0;
$id = $m->insert(array('field1' => 'a', 'field2' => 'z'));
$id = $m->insert(array('field1' => 'b', 'field2' => 'z'));
$id = $m->insert(array('field1' => 'c', 'field2' => 'x'));
$id = $m->insert(array('field1' => 'd', 'field2' => 'x'));
if ($id != 4) {
    trigger_error("LTC_Model_Decorator_HasOne::insert() did not work properly. " . $db->error());
}
$whereAll = sprintf(
    '%s.%s=%s.%s',
    $m1->getTableName(),
    $m1->getPrimaryKey(),
    $m2->getTableName(),
    $m2->getPrimaryKey()
);
$find = $m->find($whereAll, array(), array());
if (!is_array($find)) {
    trigger_error("LTC_Model_Decorator_HasOne::find() did not return array. " . $db->error());
}
if (count($find) != 4) {
    trigger_error("LTC_Model_Decorator_HasOne::find() did not return all entries. " . $db->error());
}

// findAll
$findAll = $m->findAll();
if (!is_array($findAll)) {
    trigger_error("LTC_Model_Decorator_HasOne::findAll() did not return array. " . $db->error());
}
if (print_r($findAll, true) !== print_r($find, true)) {
    trigger_error("LTC_Model_Decorator_HasOne::findAll() did not return expected result. " . $db->error());
}

// find with limit and offset option
$findLimit = $m->find(1, array(), array('limit' => 2));
if (!is_array($findLimit)) {
    trigger_error("LTC_Model_Decorator_HasOne::find() with limit option did not return array. " . $db->error());
}
if (count($findLimit) != 2) {
    trigger_error("LTC_Model_Decorator_HasOne::find() with limit option did not return array with 2 elements. " . $db->error());
}
if ($findLimit[0]['field1'] != 'a' or $findLimit[1]['field1'] != 'b') {
    trigger_error("LTC_Model_Decorator_HasOne::find() with limit did not return correct results. " . $db->error());
}
$findLimit = $m->find(1, array(), array('limit' => 2, 'offset' => 1));
if ($findLimit[0]['field1'] != 'b' or $findLimit[1]['field1'] != 'c') {
    trigger_error("LTC_Model_Decorator_HasOne::find() with limit and offset did not return correct results. " . $db->error());
}

// find with order_by and order_reverse option
$findOrder = $m->find(1, array(), array('order_by' => 'field2'));
if (!is_array($findOrder)) {
    trigger_error("LTC_Model_Decorator_HasOne::find() with order_by option did not return array. " . $db->error());
}
if (count($findOrder) != 4) {
    trigger_error("LTC_Model_Decorator_HasOne::find() with order_by option did not return array with 4 elements. " . $db->error());
}
if ($findOrder[0]['field2'] != 'x' or $findOrder[1]['field2'] != 'x' or $findOrder[2]['field2'] != 'z' or $findOrder[3]['field2'] != 'z') {
    trigger_error("LTC_Model_Decorator_HasOne::find() with order_by option did not sort entries. " . $db->error());
}
if ($m->find(1, array(), array('order_by' => 'field1', 'order_reverse' => true)) != array_reverse($findAll, false)) {
    trigger_error("LTC_Model_Decorator_HasOne::find() with order_reverse option did not sort results descending. " . $db->error());
}

// group_by option
$findGroup = $m->find(1, array(), array('group_by' => 'field1'));
if (print_r($findGroup, true) !== print_r($findAll, true)) {
    trigger_error("LTC_Model_Decorator_HasOne::find() with group_by option did not return full dataset. " . $db->error());
}
$findGroup = $m->find(1, array(), array('group_by' => 'field2', 'order_by' => array('field1', 'field2')));
if (count($findGroup) != 2) {
    trigger_error("LTC_Model_Decorator_HasOne::find() with group_by option did not return 2 result sets. " . $db->error());
}
if ($findGroup[0]['field1'] != 'a' or 
    $findGroup[0]['field2'] != 'z' or 
    $findGroup[1]['field1'] != 'c' or 
    $findGroup[1]['field2'] != 'x') {
    trigger_error("LTC_Model_Decorator_HasOne::find() with group_by option did not sort entries. " . $db->error());
}

// uninstall model 
$m->uninstall();
