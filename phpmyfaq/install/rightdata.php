<?php
/**
 * Default permission values for every phpMyFAQ instance
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Setup
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2011-03-27
 */

// Main
$mainRights = array(
    //1 => "adduser",
    array(
        'name' => 'adduser',
        'description' => 'Right to add user accounts'
    ),
    //2 => "edituser",
    array(
        'name' => 'edituser',
        'description' => 'Right to edit user accounts'
    ),
    //3 => "deluser",
    array(
        'name' => 'deluser',
        'description' => 'Right to delete user accounts'
    ),
    //4 => "addbt",
    array(
        'name' => 'addbt',
        'description' => 'Right to add faq entries'
    ),
    //5 => "editbt",
    array(
        'name' => 'editbt',
        'description' => 'Right to edit faq entries'
    ),
    //6 => "delbt",
    array(
        'name' => 'delbt',
        'description' => 'Right to delete faq entries'
    ),
    //7 => "viewlog",
    array(
        'name' => 'viewlog',
        'description' => 'Right to view logfiles'
    ),
    //8 => "adminlog",
    array(
        'name' => 'adminlog',
        'description' => 'Right to view admin log'
    ),
    //9 => "delcomment",
    array(
        'name' => 'delcomment',
        'description' => 'Right to delete comments'
    ),
    //10 => "addnews",
    array(
        'name' => 'addnews',
        'description' => 'Right to add news'
    ),
    //11 => "editnews",
    array(
        'name' => 'editnews',
        'description' => 'Right to edit news'
    ),
    //12 => "delnews",
    array(
        'name' => 'delnews',
        'description' => 'Right to delete news'
    ),
    //13 => "addcateg",
    array(
        'name' => 'addcateg',
        'description' => 'Right to add categories'
    ),
    //14 => "editcateg",
    array(
        'name' => 'editcateg',
        'description' => 'Right to edit categories'
    ),
    //15 => "delcateg",
    array(
        'name' => 'delcateg',
        'description' => 'Right to delete categories'
    ),
    //16 => "passwd",
    array(
        'name' => 'passwd',
        'description' => 'Right to change passwords'
    ),
    //17 => "editconfig",
    array(
        'name' => 'editconfig',
        'description' => 'Right to edit configuration'
    ),
    //18 => "addatt", // Duplicate, removed with 2.7.3
    //array(
    //    'name' => 'addatt',
    //    'description' => 'Right to add attachments'
    //),
    //19 => "backup delatt", // Duplicate, removed with 2.7.3
    //array(
    //    'name' => 'delatt',
    //    'description' => 'Right to delete attachments'
    //),
    //20 => "backup",
    array(
        'name' => 'backup',
        'description' => 'Right to save backups'
    ),
    //21 => "restore",
    array(
        'name' => 'restore',
        'description' => 'Right to load backups'
    ),
    //22 => "delquestion",
    array(
        'name' => 'delquestion',
        'description' => 'Right to delete questions'
    ),
    //23 => 'addglossary',
    array(
        'name' => 'addglossary',
        'description' => 'Right to add glossary entries'
    ),
    //24 => 'editglossary',
    array(
        'name' => 'editglossary',
        'description' => 'Right to edit glossary entries'
    ),
    //25 => 'delglossary'
    array(
        'name' => 'delglossary',
        'description' => 'Right to delete glossary entries'
    ),
    //26 => 'changebtrevs'
    array(
        'name' => 'changebtrevs',
        'description' => 'Right to edit revisions'
    ),
    //27 => "addgroup",
    array(
        'name' => 'addgroup',
        'description' => 'Right to add group accounts'
    ),
    //28 => "editgroup",
    array(
        'name' => 'editgroup',
        'description' => 'Right to edit group accounts'
    ),
    //29 => "delgroup",
    array(
        'name' => 'delgroup',
        'description' => 'Right to delete group accounts'
    ),
    //30 => "addtranslation",
    array(
        'name' => 'addtranslation',
        'description' => 'Right to add translation'
    ),
    //31 => "edittranslation",
    array(
        'name' => 'edittranslation',
        'description' => 'Right to edit translations'
    ),
    //32 => "deltranslation",
    array(
        'name' => 'deltranslation',
        'description' => 'Right to delete translations'
    ),
    // 33 => 'approverec'
    array(
        'name' => 'approverec',
        'description' => 'Right to approve records'
    ),
    // 34 => 'addattachment'
    array(
        'name' => 'addattachment',
        'description' => 'Right to add attachments'
    ),
    // 35 => 'editattachment'
    array(
        'name' => 'editattachment',
        'description' => 'Right to edit attachments'
    ),
    // 36 => 'delattachment'
    array(
        'name' => 'delattachment',
        'description' => 'Right to delete attachments'
    ),
    // 37 => 'dlattachment'
    array(
        'name' => 'dlattachment',
        'description' => 'Right to download attachments'
    ),
    // 38 => 'dlattachment'
    array(
        'name' => 'reports',
        'description' => 'Right to generate reports'
    ),
    // 39 => 'addfaq'
    array(
        'name' => 'addfaq',
        'description' => 'Right to add FAQs in frontend'
    ),
    // 40 => 'addquestion'
    array(
        'name' => 'addquestion',
        'description' => 'Right to add questions in frontend'
    ),
    // 41 => 'addcomment'
    array(
        'name' => 'addcomment',
        'description' => 'Right to add comments in frontend'
    ),
    // 42 => 'editinstances'
    array(
        'name' => 'editinstances',
        'description' => 'Right to edit multi-site instances'
    ),
    // 43 => 'addinstances'
    array(
        'name' => 'addinstances',
        'description' => 'Right to add multi-site instances'
    ),
    // 44 => 'delinstances'
    array(
        'name' => 'delinstances',
        'description' => 'Right to delete multi-site instances'
    ),
);