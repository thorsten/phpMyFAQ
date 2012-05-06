/**
 * TinyMCE plugin for inserting internal FAQ links from a suggest search
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
 * 
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-08-02
 */

(function() {
    // Load plugin specific language pack
    tinymce.PluginManager.requireLangPack('phpmyfaq');

    tinymce.create('tinymce.plugins.phpmyfaqPlugin', {
        init : function(ed, url) {
            ed.addCommand('mcePhpmyfaq', function() {
                ed.windowManager.open({
                    file   : url + '/dialog.html',
                    width  : 480 + parseInt(ed.getLang('phpmyfaq.delta_width', 0)),
                    height : 320 + parseInt(ed.getLang('phpmyfaq.delta_height', 0)),
                    inline : 1
                }, {
                    plugin_url : url, // Plugin absolute URL
                    some_custom_arg : 'custom arg' // Custom argument
                });
            });

            // Register phpmyfaq button
            ed.addButton('phpmyfaq', {
                title : 'phpmyfaq.desc',
                cmd   : 'mcePhpmyfaq',
                image : url + '/img/phpmyfaq.gif'
            });

            // Add a node change handler, selects the button in the UI when a image is selected
            ed.onNodeChange.add(function(ed, cm, n) {
                cm.setActive('phpmyfaq', n.nodeName == 'IMG');
            });
        },

        /**
         * Returns information about the plugin as a name/value array.
         * The current keys are longname, author, authorurl, infourl and version.
         *
         * @return {Object} Name/value array containing information about the plugin.
         */
        getInfo : function() {
            return {
                longname  : 'phpMyFAQ plugin',
                author    : 'Thorsten Rinne',
                authorurl : 'http://www.phpmyfaq.de',
                infourl   : 'http://www.phpmyfaq.de',
                version   : "1.0"
            };
        }
    });

    // Register plugin
    tinymce.PluginManager.add('phpmyfaq', tinymce.plugins.phpmyfaqPlugin);
})();