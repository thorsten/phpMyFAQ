<?php
/******************************************************************************
 * File:				stat.bar.php
 * Description:			generates a graphical bar
 * Author:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Date:				2003-10-26
 * Last change:			2003-10-26
 * Copyright:           (c) 2001-2004 Thorsten Rinne
* 
* The contents of this file are subject to the Mozilla Public License
* Version 1.1 (the "License"); you may not use this file except in
* compliance with the License. You may obtain a copy of the License at
* http://www.mozilla.org/MPL/
* 
* Software distributed under the License is distributed on an "AS IS"
* basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
* License for the specific language governing rights and limitations
* under the License.
*/

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

header ("Content-type: image/png");
$image = @imagecreate (50, 15) or die ("Sorry, but phpMyFAQ cannot initialize new GD image stream.");
$backgroundColor = imagecolorallocate ($image, 211, 211, 211);
$textColor = imagecolorallocate ($image, 0, 0, 0);

if (isset($_REQUEST["num"]) && $_REQUEST["num"] != "") {
    $num = $_REQUEST["num"];
    $num = ceil(($num - 1) * 25);
    if ($num < 25) {
        $textColor = imagecolorallocate ($image, 255, 0, 0);
        }
    elseif ($num > 75) {
        $textColor = imagecolorallocate ($image, 255, 255, 255);
        $barColor = imagecolorallocate ($image, 0, 128, 0);
        imagefilledrectangle ($image, 0, 0, round(($num/100)*50), 15, $barColor);
        }
    elseif ($num <= 75 AND $num >= 25) {
        $barColor = imagecolorallocate ($image, 150, 150, 150);
        imagefilledrectangle ($image, 0, 0, round(($num/100)*50), 15, $barColor);
        }
    imagestring ($image, 2, 1, 1, $num."%", $textColor);
    }
else {
    imagestring ($image, 1, 5, 5, "n/a", $textColor);
    }

ImagePNG ($image);
?>

