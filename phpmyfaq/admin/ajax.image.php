<?php
/**
 * AJAX: handles an image upload from TinyMCE
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @copyright 2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2015-10-10
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

require_once  PMF_INCLUDE_DIR . '/libs/bulletproof/bulletproof.php';

$ajaxAction = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$upload     = PMF_Filter::filterInput(INPUT_GET, 'image', FILTER_VALIDATE_INT);

switch ($ajaxAction) {

    case 'upload':
        $image = new Bulletproof\Image($_FILES);
        $image->setLocation(PMF_ROOT_DIR . '/images');

        if ($image['ikea']) {

            $upload = $image->upload();

            try {
                if ($image->getMime() !== 'png' || $image->getMime() !== 'jpg' || $image->getMime() !== 'gif') {
                    throw new \Exception(' Image should be a PNG, JPG or GIF type ');
                }

                if ($image->getSize() < 1000) {
                    throw new \Exception(' Image size too small ');
                }
                if ($image->upload()) {
                    // handle success
                } else {
                    throw new \Exception($image['error']);
                }

            } catch(\Exception $e) {
                echo $e->getMessage();
            }
        }
        break;
}