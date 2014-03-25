/*global module:false*/
module.exports = function(grunt) {

    // Load all tasks
    require('load-grunt-tasks')(grunt);

    // Project configuration.
    grunt.initConfig({
        // Metadata.
        meta: {
            version: '2.9'
        },
        banner: '/*! phpMyFAQ v2.9 - http://www.phpmyfaq.de - Copyright (c) 2001 - 2014 Thorsten Rinne and phpMyFAQ Team */\n',
        // Task configuration.
        bower: {
            install: {
                // just run 'grunt bower:install' and you'll see files from your Bower packages in lib directory
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
                tasks: ['jshint:gruntfile']
            },
            css: {
                files: ['phpmyfaq/admin/assets/less/style.less', 'phpmyfaq/assets/template/default/less/style.less'],
                tasks: ['less', 'cssmin']
            }
        }
    });

    // Default task.
    grunt.registerTask('default', ['jshint', 'concat', 'uglify', 'less', 'cssmin']);

    // Watcher
    grunt.event.on('watch', function(action, filepath, target) {
        grunt.log.writeln(target + ': ' + filepath + ' has ' + action);
    });
};
