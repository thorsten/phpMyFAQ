/**
 * TinyMCE v4 plugin to upload images
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-10-18
 */

/*global tinymce:false, $:false */

tinymce.PluginManager.add('imageupload', function(editor, url) {
    'use strict';

    function pmfImageUpload () {
        editor.windowManager.open({
            title: 'Upload an image',
            file : url + '/../../../../../image.upload.php',
            width : 320,
            height: 120,
            buttons: [
                {
                    text: 'Close',
                    onclick: 'close'
                }
            ]
        });
    }

    editor.addButton('imageupload', {
        tooltip: 'Upload an image',
        icon : 'image',
        onclick: pmfImageUpload
    });

    editor.addMenuItem('imageupload', {
        text: 'Upload image',
        icon : 'image',
        context: 'insert',
        onclick: pmfImageUpload
    });
});
