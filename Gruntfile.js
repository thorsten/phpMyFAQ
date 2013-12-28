/*global module:false*/
module.exports = function(grunt) {

    // Project configuration.
    grunt.initConfig({
        // Metadata.
        meta: {
            version: '2.9'
        },
        banner: '/*! phpMyFAQ v2.9 - http://www.phpmyfaq.de - Copyright (c) 2001 - 2013 Thorsten Rinne and phpMyFAQ Team */\n',
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
            dist: {
                src: '<%= concat.dist.dest %>',
                dest: 'phpmyfaq/assets/js/phpmyfaq.min.js'
            }
        },
        jshint: {
            options: {
                curly: true,
                eqeqeq: true,
                immed: true,
                latedef: true,
                newcap: true,
                noarg: true,
                sub: true,
                undef: true,
                unused: true,
                boss: true,
                eqnull: true,
                browser: true,
                globals: {
                    "jQuery": true
                }
            },
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
                    "phpmyfaq/admin/assets/css/style.css": "phpmyfaq/admin/assets/less/style.less",
                    "phpmyfaq/admin/assets/css/style.rtl.css": "phpmyfaq/admin/assets/less/style.rtl.less",
                    "phpmyfaq/assets/template/default/css/style.css": "phpmyfaq/assets/template/default/less/style.less",
                    "phpmyfaq/assets/template/default/css/style.rtl.css": "phpmyfaq/assets/template/default/less/style.rtl.less"
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
                    "phpmyfaq/admin/assets/css/style.css": "phpmyfaq/admin/assets/css/style.css",
                    "phpmyfaq/admin/assets/css/style.rtl.css": "phpmyfaq/admin/assets/css/style.rtl.css",
                    "phpmyfaq/assets/template/default/css/style.min.css": ["phpmyfaq/assets/template/default/css/style.css"],
                    "phpmyfaq/assets/template/default/css/style.rtl.min.css": ["phpmyfaq/assets/template/default/css/style.rtl.css"]
                }
            }
        },
        watch: {
            gruntfile: {
                files: '<%= jshint.gruntfile.src %>',
                tasks: ['jshint:gruntfile']
            },
            lib_test: {
                files: '<%= jshint.lib_test.src %>',
                tasks: ['jshint:lib_test', 'qunit']
            }
        }
    });

    // These plugins provide necessary tasks.
    grunt.loadNpmTasks('grunt-bower-task');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-csslint');

    // Default task.
    grunt.registerTask('default', ['jshint', 'concat', 'uglify', 'less', 'cssmin']);

};
