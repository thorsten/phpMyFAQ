<?php
/**
 * Generates a graphical bar.
 *
 * @package    phpMyFAQ 
 * @subpackage Administration
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2003-10-26
 * @copyright  2003-2009 phpMyFAQ Team
 * @version    SVN: $Id$
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

define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));

require_once PMF_ROOT_DIR . '/inc/Init.php';
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

$image           = @imagecreate (50, 15) or die ("Sorry, but phpMyFAQ cannot initialize a new image stream.");
$backgroundColor = imagecolorallocate ($image, 211, 211, 211);
$textColor       = imagecolorallocate ($image, 0, 0, 0);
$num             = PMF_Filter::filterInput(INPUT_GET, 'num', FILTER_VALIDATE_FLOAT);

if (!is_null($num)) {
    $num = round($num * 20);
    if ($num < 25) {
        $textColor = imagecolorallocate ($image, 255, 255, 255);
        $barColor  = imagecolorallocate ($image, 255, 0, 0);
        imagefilledrectangle ($image, 0, 0, round(($num/100)*50), 15, $barColor);
    } elseif ($num > 75) {
        $textColor = imagecolorallocate ($image, 255, 255, 255);
        $barColor  = imagecolorallocate ($image, 0, 128, 0);
        imagefilledrectangle ($image, 0, 0, round(($num/100)*50), 15, $barColor);
    } elseif ($num <= 75 && $num >= 25) {
        $textColor = imagecolorallocate ($image, 255, 255, 255);
        $barColor  = imagecolorallocate ($image, 150, 150, 150);
        imagefilledrectangle ($image, 0, 0, round(($num/100)*50), 15, $barColor);
    }
    imagestring ($image, 2, 1, 1, $num . '%', $textColor);
} else {
    imagestring ($image, 1, 5, 5, 'n/a', $textColor);
}

header ('Content-type: image/png');
imagepng($image);