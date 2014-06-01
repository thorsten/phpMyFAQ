/**
 * phpMyFAQ Gruntfile.js
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Development
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2013-06-23
 */

/*global module:false, require:false */

module.exports = function(grunt) {

    'use strict';

    // Load all tasks
    require('load-grunt-tasks')(grunt);

    // Project configuration.
    grunt.initConfig({
        // Metadata.
        meta: {
            version: '2.9.0-dev'
        },
        banner: '/*! phpMyFAQ v<%= meta.version %> - http://www.phpmyfaq.de - Copyright (c) 2001 - 2014 Thorsten Rinne and phpMyFAQ Team */\n',
        // Task configuration.
        copy: {
            tinymce: {
                files: [
                    {
                        expand: true,
                        src: 'components/tinymce/js/tinymce/tinymce.full.min.js',
                        flatten: true,
                        dest: 'phpmyfaq/admin/assets/js/editor'
                    },
                    {
                        expand: true,
                        cwd: 'components/tinymce/js/tinymce/plugins/',
                        src: '**',
                        dest: 'phpmyfaq/admin/assets/js/editor/plugins/'
                    },
                    {
                        expand: true,
                        cwd: 'components/tinymce/js/tinymce/skins/',
                        src: '**/*.!(less)',
                        dest: 'phpmyfaq/admin/assets/js/editor/skins/'
                    },
                    {
                        expand: true,
                        cwd: 'components/tinymce/js/tinymce/themes/',
                        src: '**',
                        dest: 'phpmyfaq/admin/assets/js/editor/themes/'
                    }
                ]
            }
        },
        concat: {
            options: {
                banner: '<%= banner %>',
                stripBanners: true
            },
            dist: {
                src: [
                    'components/jquery/jquery.js',
                    'components/bootstrap/js/tooltip.js',
                    'components/bootstrap/js/transition.js',
                    'components/bootstrap/js/alert.js',
                    'components/bootstrap/js/button.js',
                    'components/bootstrap/js/collapse.js',
                    'components/bootstrap/js/dropdown.js',
                    'components/bootstrap/js/modal.js',
                    'components/bootstrap/js/popover.js',
                    'components/bootstrap/js/tab.js',
                    'phpmyfaq/assets/js/autosave.js',
                    'phpmyfaq/assets/js/functions.js'
                ],
                dest: 'phpmyfaq/assets/js/phpmyfaq.js'
            }
        },
        uglify: {
            options: {
                banner: '<%= banner %>'
            },
            frontend: {
                files: { 'phpmyfaq/assets/js/phpmyfaq.min.js': [ '<%= concat.dist.dest %>' ] }
            }
        },
        jshint: {
            jshintrc: '.jshintrc',
            gruntfile: {
                src: 'Gruntfile.js'
            },
            beforeconcat: [
                'phpmyfaq/assets/js/autosave.js',
                'phpmyfaq/assets/js/functions.js'
            ]
        },
        less: {
            development: {
                files: {
                    'phpmyfaq/admin/assets/css/style.css': 'phpmyfaq/admin/assets/less/style.less',
                    //'phpmyfaq/admin/assets/css/style.rtl.css': 'phpmyfaq/admin/assets/less/style.rtl.less',
                    'phpmyfaq/assets/template/default/css/style.css': 'phpmyfaq/assets/template/default/less/style.less'
                    //'phpmyfaq/assets/template/default/css/style.rtl.css': 'phpmyfaq/assets/template/default/less/style.rtl.less'
                }
            }
        },
        cssmin: {
            add_banner: {
                options: {
                    banner: '<%= banner %>',
                    keepSpecialComments: 0
                },
                files: {
                    'phpmyfaq/admin/assets/css/style.min.css': 'phpmyfaq/admin/assets/css/style.css',
                    //'phpmyfaq/admin/assets/css/style.rtl.css': 'phpmyfaq/admin/assets/css/style.rtl.css',
                    'phpmyfaq/assets/template/default/css/style.min.css': ['phpmyfaq/assets/template/default/css/style.css']
                    //'phpmyfaq/assets/template/default/css/style.rtl.min.css': ['phpmyfaq/assets/template/default/css/style.rtl.css']
                }
            }
        },
        watch: {
            gruntfile: {
                files: '<%= jshint.gruntfile.src %>',
                tasks: ['jshint:gruntfile'],
                options: {
                    reload: true
                }
            },
            css: {
                files: ['phpmyfaq/admin/assets/less/*.less', 'phpmyfaq/assets/template/default/less/*.less'],
                tasks: ['less', 'cssmin'],
                options: {
                    livereload: true,
                }
            }
        }
    });

    // Default task.
    grunt.registerTask('default', ['copy', 'jshint', 'concat', 'uglify', 'less', 'cssmin']);

    // Watcher
    grunt.event.on('watch', function(action, filepath, target) {
        grunt.log.writeln(target + ': ' + filepath + ' has ' + action);
    });
};
