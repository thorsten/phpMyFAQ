<?php
error_reporting(E_ALL);
const SQLPREFIX = '';
const PMF_ROOT_DIR = __DIR__;

$sql_type = 'mysql';
$sql_server = 'localhost';
$sql_user = 'root';
$sql_password = '';
$sql_db = 'pmf_tree_test_20100104';

require PMF_ROOT_DIR . "/inc/functions.php";
require PMF_ROOT_DIR . "/inc/Exception.php";
require PMF_ROOT_DIR . "/inc/Db.php";
require PMF_ROOT_DIR . "/inc/PMF_DB/Driver.php";
require PMF_ROOT_DIR . "/inc/PMF_DB/Resultset.php";
require PMF_ROOT_DIR . "/inc/Language.php";
require PMF_ROOT_DIR . "/inc/Category.php";
require PMF_ROOT_DIR . "/inc/PMF_Category/Abstract.php";
require PMF_ROOT_DIR . "/inc/PMF_Category/Interface.php";
require PMF_ROOT_DIR . "/inc/PMF_Category/Node.php";
require PMF_ROOT_DIR . "/inc/PMF_Category/Tree/DataProvider.php";
require PMF_ROOT_DIR . "/inc/PMF_Category/Tree/DataProvider/SingleQuery.php";
require PMF_ROOT_DIR . "/inc/PMF_Category/Tree/DataProvider/MultiQuery.php";
require PMF_ROOT_DIR . "/inc/PMF_Category/Tree.php";

$db = PMF_Db::dbSelect($sql_type);
$db->connect($sql_server, $sql_user, $sql_password, $sql_db);

class PMF_CategoryPathFilterIterator extends RecursiveFilterIterator {
    private $path = array();
    public function __construct(PMF_Category_Tree $it, array $path) {
        parent::__construct($it);
        $this->path = $path;
    }
    public function getChildren() {
        return new self($this->getInnerIterator()->getChildren(), $this->path);
    }

    public function accept() {
        $parent = $this->getInnerIterator()->current()->getParent();
        /* if the parent is NULL we're on root level */
        return (!$parent || in_array($parent->getId(), $this->path));
    }
}

class Test_RII extends RecursiveIteratorIterator {
	public $indent = 0;

	public function __construct(RecursiveIterator $root) {
		parent::__construct($root, RecursiveIteratorIterator::SELF_FIRST);
	}

	public function beginChildren() {
		$this->indent += 4;
		parent::beginChildren();
	}

	public function endChildren() {
		$this->indent -= 4;
		parent::endChildren();
	}
}

$providers = array(new PMF_Category_Tree_DataProvider_SingleQuery(), new PMF_Category_Tree_DataProvider_MultiQuery);

foreach ($providers as $dataProvider) {
    echo "=== ".get_class($dataProvider)." ===\n";

    echo "Complete tree:\n";

    $trii = new Test_RII(new PMF_Category_Tree($dataProvider));
    foreach ($trii as $key => $category) {
	    echo str_repeat(' ', $trii->indent), $category." ($key)\n";
    }

    echo "\nOnly part of a tree:\n";

    $ct = new PMF_Category_Tree($dataProvider);
    $path = $dataProvider->getPath(6);
    $trii = new Test_RII(new PMF_CategoryPathFilterIterator($ct, $path));
    foreach ($trii as $key => $category) {
	    echo str_repeat(' ', $trii->indent), $category." ($key)\n";
    }
}

