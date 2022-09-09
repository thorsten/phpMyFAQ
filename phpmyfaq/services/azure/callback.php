<?php

use phpMyFAQ\Auth\AuthAzureActiveDirectory;

//
// Prepend and start the PHP session
//
define('PMF_ROOT_DIR', dirname(dirname(__DIR__)));
define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require PMF_ROOT_DIR . '/src/Bootstrap.php';
require PMF_CONFIG_DIR . '/azure.php';

var_dump($_GET['code']);
