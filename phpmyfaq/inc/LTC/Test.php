<?php

/**
 * Test
 *
 * @access public
 * @author Lars Tiedemann, <php@larstiedemann.de>
 */
class LTC_Test
{
    // --- ATTRIBUTES ---
    
    /**
     * Test cases
     *
     * @access public
     * @var array
     */
    public $testCases = array();

    // --- OPERATIONS ---
    
    /**
     * Constructor
     *
     * @access public 
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    public function __construct()
    {
    }
    
    /**
     * Destructor
     *
     * @access public 
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    public function __destruct()
    {
    }
    
    /**
     * Returns true if the given filename is a valid test case. 
     * Test cases for other files are named FILENAME.test.php
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string file
     * @return bool
     */
    public function isTestCase($file)
    {
        $basename = basename($file, '.php');
        if (substr($basename, -5, 5) == '.test') {
            return true;          
        }
        if (strpos($basename, 'test.') === 0) {
            return true;
        }
        return false;
    }
    
    /**
     * Returns an array with the filepaths to all test case files 
     * underneath the given directory. 
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string path to a directory
     * @param bool subdirectories are considered if set true
     * @return void
     */
    public function getAllTestCases($directory, $recurse = true)
    {
        if (!is_dir($directory))
            return false;
        $filesInDir = scandir($directory);
        foreach ($filesInDir as $fileInDir) {
            if ($fileInDir == '.' or $fileInDir == '..') {
                continue;
            }
            // recurse
            if (is_dir($directory.'/'.$fileInDir) and $recurse) {
                $this->getAllTestCases($directory.'/'.$fileInDir, $recurse);
            }
            // add to testCases
            if ($this->isTestCase($fileInDir)) {
                $testCase = new LTC_TestCase($directory.'/'.$fileInDir);
                $this->testCases[count($this->testCases)] = $testCase;
            }
        }
    }
    
    /**
     * Performs the tests according to the test cases.
     *
     * @access public 
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    public function test()
    {
        foreach ($this->testCases as $testCase) {
            $testCase->test();
        }
    }
    
} /* end of class Test */


/**
 * TestCase
 *
 * @access public
 * @author Lars Tiedemann, <php@larstiedemann.de>
 */
class LTC_TestCase
{
    // --- ATTRIBUTES ---
    
    /**
     * Filename of the test case file
     *
     * @access public
     * @var string file name
     */
    public $fileName = '';
    
    // --- OPERATIONS ---
    
    /**
     * Constructor
     *
     * @access public 
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string filename of the test case file
     * @return void
     */
    public function __construct($testCaseFile)
    {
        if (is_readable($testCaseFile))
            $this->fileName = $testCaseFile;
    }
    
    /**
     * Destructor
     *
     * @access public 
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    public function __destruct()
    {
    }
    
    /**
     * Runs the test. 
     *
     * @access public 
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return bool
     */
    public function test()
    {
        echo 'TEST '.$this->fileName.'...';
        set_error_handler('LTC_TestCaseErrorHandler', E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE);
        ob_start();
        include $this->fileName;
        $output = ob_get_flush();
        $returnValue = ($output == '' ? true : false);
        if ($returnValue) 
            echo '<b style="color: green; ">OK</b><br />'."\n";
        restore_error_handler();
        return $returnValue;
    }

} /* end of class TestCase */


/**
 * Error handler
 *
 * @access public
 * @author Lars Tiedemann, <php@larstiedemann.de>
 */
function LTC_TestCaseErrorHandler($errno, $message, $file, $line, $vars)
{   
    // timestamp for the error entry
    $time = date("Y-m-d H:i:s (T)");
    $errorTypes = array (
        E_ERROR           => "<span style=\"color: #FF0000; \">Error</span>",
        E_WARNING         => "<span style=\"color: #FFFF00; \">Warning</span>",
        E_PARSE           => "<span style=\"\">Parsing Error</span>",
        E_NOTICE          => "<span style=\"color: #0000FF; \">Notice",
        E_CORE_ERROR      => "<span style=\"\">Core Error</span>",
        E_CORE_WARNING    => "<span style=\"\">Core Warning</span>",
        E_COMPILE_ERROR   => "<span style=\"\">Compile Error</span>",
        E_COMPILE_WARNING => "<span style=\"\">Compile Warning</span>",
        E_USER_ERROR      => "<span style=\"color: #FF0000; text-decoration: underline; \">User Error</span>",
        E_USER_WARNING    => "<span style=\"color: #FF8000; text-decoration: underline; \">User Warning</span>",
        E_USER_NOTICE     => "<span style=\"color: #0000FF; text-decoration: underline; \">User Notice</span>",
        E_STRICT          => "<span style=\"\">Runtime Notice</span>"
    );
    $error = '';
    $error .= "\n<div>".$errorTypes[$errno]." ".$message."\n<pre style=\"font-size: smaller; \">IN ".$file." ON LINE ".$line." VARS ".print_r($vars, true)."</pre></div>\n";
    echo $error;
}


/**
 * Run the test
 */
//ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.dirname(dirname(__FILE__)));
$recurse = !isset($_GET['recurse']) ? false : (bool) $_GET['recurse']; 

if (isset($_GET['dir'])) {
    $dir = dirname(__FILE__).'/'.$_GET['dir'];
    if (is_dir($dir)) {
        $test = new LTC_Test();
        $test->getAllTestCases($dir, $recurse);
        $test->test();        
    }
}

