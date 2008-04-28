<?php

require_once dirname(dirname(dirname(__FILE__))) . '/init.inc.php';
$db = LTC_Db::getInstance();

class LTC_Model_HasAndBelongsToMany1
    extends LTC_Model
{
    public function init()
    {
        $this->fields['id1'] = array(
            'primary_key'    => true,
            'auto_increment' => true,
            'type'           => 'int',
            'length'         => 10,
            'null'           => false,
            'default'        => 0,
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

class LTC_Model_HasAndBelongsToMany2
    extends LTC_Model
{
    public function init()
    {
        $this->fields['id2'] = array(
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
$m1 = LTC_Model::getModel('HasAndBelongsToMany1');
$m2 = LTC_Model::getModel('HasAndBelongsToMany2');
$m  = LTC_Model::HasAndBelongsToMany($m1, $m2);
$m->install();

// insert separate entries first
// insert1
$id1 = $m1->insert(array('field1' => 'field1'));
if (!is_int($id1) or $id1 <= 0) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::insert(array('field1' => 'field1')) did not return integer value. ");
}
// insert2
$id2 = $m2->insert(array('field2' => 'field2'));
if (!is_int($id2) or $id2 <= 0) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::insert(array('field2' => 'field2')) did not return integer value. ");
}

// find one
$find1 = $m1->find(array('id1' => $id1));
// entry does not belong to anyone, so nothing is found
$find = $m->find(array('id1' => $id1));
if (!is_array($find) or !empty($find)) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find(array('id1' => $id1)) does not return empty array. ");
}
if (count($find1) !== 1) {
    trigger_error("LTC_Model::find1(array('id1' => $id1)) does not contain exactly one results set. ");
}
if (!isset($find1[0]['field1']) or $find1[0]['field1'] != 'field1') {
    trigger_error("LTC_Model::find(array('id1' => $id1)) does not contain 'field1'. ");
}

// associate the model
$assoc = $m->associate($id1, $id2);
if ($assoc !== true) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::associate($id1, $id2) did not return boolean true. ");
}
$check = $m2->find(array('id2' => $id2));
if (!is_array($check) or count($check) !== 1) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::associate($id1, $id2) did not build association. ");
}
if (!isset($check[0]['id2']) or $check[0]['id2'] != $id2) {
    trigger_error("LTC_Model::find(array('id2' => $id2)) did not return correctly associated entry. ");
}

// find both
$find1 = $m1->find(array('id1' => $id1));
$find2 = $m2->find(array('id2' => $id2));
// find associated
$find = $m->find(array('id1' => $id1));
if (count($find1) !== 1 or $find1[0]['id1'] != $id1 or $find1[0]['field1'] != 'field1') {
    trigger_error("LTC_Model::find(array('id1' => $id1)) does return expected result. ");
}
if (count($find2) !== 1 or $find2[0]['id2'] != $id2 or $find2[0]['field2'] != 'field2') {
    trigger_error("LTC_Model::find(array('id2' => $id2)) does return expected result. ");
}
if (!is_array($find)) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find(array('id1' => $id1)) does not return array. ");
}
if (count($find) !== 1) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find(array('id1' => $id1)) does not return single row. ");
}
if (!isset($find[0]['field1']) or !isset($find[0]['field1']) or !isset($find[0]['id1']) or !isset($find[0]['id2'])) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find(array('id1' => $id1)) does not return full result set. ");
}
$find12 = array_merge($find1[0], $find2[0]);
$find = $find[0];
sort($find);
sort($find12);
if (print_r($find, true) != print_r($find12, true)) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find(array('id1' => $id1)) does not return full result set with correct values. ");
}

// associate another entry
$id2 = $m2->insert(array('field2' => 'another field'));
if ($id2 !== 2) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::insert(array('field2' => 'another field')) does not return (int) 2. ");
}
$find2 = $m2->find(array('id2' => $id2));
if ($find2 == false or empty($find2)) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find(array('id2' => $id2)) did not return a result. ");
}
if (!isset($find2[0]['field2']) or $find2[0]['field2'] !== 'another field') {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find(array('id2' => $id2)) did not return expected result. ");
}
$assoc = $m->associate($id1, $id2);
if ($assoc !== true) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::associate($id1, $id2) did not return boolean true. ");
}
/*$check = $m2->find(array('id2' => $id2));
if (!is_array($check) or count($check) !== 1) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::associate($id1, $id2) did not build association. ");
}
if (!isset($check[0]['id1']) or $check[0]['id1'] != $id1) {
    trigger_error("LTC_Model::find(array('id2' => $id2)) did not return correctly associated entry. ");
}*/

// find both associations
$find = $m->find(array('id1' => $id1));
if (count($find) !== 2) {
    trigger_error("LTC_Model::find(array('id1' => $id1) did not return two results. ");
}                                
if (!isset($find[0]['id1'])     or
    !isset($find[0]['id2'])     or
    !isset($find[0]['field1'])  or
    !isset($find[0]['field2'])  or 
    !isset($find[1]['id1'])     or
    !isset($find[1]['id2'])     or
    !isset($find[1]['field1'])  or
    !isset($find[1]['field2']) 
) {
    trigger_error("LTC_Model::find(array('id1' => $id1) did not return two complete results. ");
}

/*
// update
$update = $m->update(array('id' => $id), array('field1' => 'field1_2', 'field2' => 'field2_2'));
if ($update !== true) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::update(array('id' => $id), array('field1' => 'field1_2', 'field2' => 'field2_2')) did not return boolean TRUE. ");
}

// delete
$delete = $m->delete(array('id' => $id));
if ($delete !== true) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::delete() does not return boolean true. ");
}

// clear
$id = $m->insert(array('field1' => 'field1', 'field2' => 'field2'));
$clear = $m->clear();
if ($clear !== true) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::clear() does not return boolean true. ");
}

// find with options
$id = 0;
$id = $m->insert(array('field1' => 'a', 'field2' => 'z'));
$id = $m->insert(array('field1' => 'b', 'field2' => 'z'));
$id = $m->insert(array('field1' => 'c', 'field2' => 'x'));
$id = $m->insert(array('field1' => 'd', 'field2' => 'x'));
if ($id != 4) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::insert() did not work properly. " . $db->error());
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
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find() did not return array. " . $db->error());
}
if (count($find) != 4) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find() did not return all entries. " . $db->error());
}

// findAll
$findAll = $m->findAll();
if (!is_array($findAll)) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::findAll() did not return array. " . $db->error());
}
if (print_r($findAll, true) !== print_r($find, true)) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::findAll() did not return expected result. " . $db->error());
}

// find with limit and offset option
$findLimit = $m->find(1, array(), array('limit' => 2));
if (!is_array($findLimit)) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find() with limit option did not return array. " . $db->error());
}
if (count($findLimit) != 2) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find() with limit option did not return array with 2 elements. " . $db->error());
}
if ($findLimit[0]['field1'] != 'a' or $findLimit[1]['field1'] != 'b') {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find() with limit did not return correct results. " . $db->error());
}
$findLimit = $m->find(1, array(), array('limit' => 2, 'offset' => 1));
if ($findLimit[0]['field1'] != 'b' or $findLimit[1]['field1'] != 'c') {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find() with limit and offset did not return correct results. " . $db->error());
}

// find with order_by and order_reverse option
$findOrder = $m->find(1, array(), array('order_by' => 'field2'));
if (!is_array($findOrder)) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find() with order_by option did not return array. " . $db->error());
}
if (count($findOrder) != 4) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find() with order_by option did not return array with 4 elements. " . $db->error());
}
if ($findOrder[0]['field2'] != 'x' or $findOrder[1]['field2'] != 'x' or $findOrder[2]['field2'] != 'z' or $findOrder[3]['field2'] != 'z') {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find() with order_by option did not sort entries. " . $db->error());
}
if ($m->find(1, array(), array('order_by' => 'field1', 'order_reverse' => true)) != array_reverse($findAll, false)) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find() with order_reverse option did not sort results descending. " . $db->error());
}

// group_by option
$findGroup = $m->find(1, array(), array('group_by' => 'field1'));
if (print_r($findGroup, true) !== print_r($findAll, true)) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find() with group_by option did not return full dataset. " . $db->error());
}
$findGroup = $m->find(1, array(), array('group_by' => 'field2', 'order_by' => array('field1', 'field2')));
if (count($findGroup) != 2) {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find() with group_by option did not return 2 result sets. " . $db->error());
}
if ($findGroup[0]['field1'] != 'a' or 
    $findGroup[0]['field2'] != 'z' or 
    $findGroup[1]['field1'] != 'c' or 
    $findGroup[1]['field2'] != 'x') {
    trigger_error("LTC_Model_Decorator_HasAndBelongsToMany::find() with group_by option did not sort entries. " . $db->error());
}*/

// uninstall model 
$m->uninstall();