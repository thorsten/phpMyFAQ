<?php

/**
 * The phpMyFAQ instances database class with CREATE TABLE statements for SQLite3.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-04-16
 */

namespace phpMyFAQ\Instance\Database;

use phpMyFAQ\Configuration;
use phpMyFAQ\Instance\Database;

/**
 * Class Sqlite3
 *
 * @package phpMyFAQ\Instance\Database
 */
class Sqlite3 extends Database implements Driver
{
    private array $createTableStatements = [
        'faqadminlog' => 'CREATE TABLE %sfaqadminlog (
            id INTEGER NOT NULL,
            time INTEGER NOT NULL,
            usr INTEGER NOT NULL,
            text VARCHAR(8000) NOT NULL,
            ip VARCHAR(64) NOT NULL,
            PRIMARY KEY (id))',

        'faqattachment' => 'CREATE TABLE %sfaqattachment (
            id INTEGER NOT NULL,
            record_id INTEGER NOT NULL,
            record_lang VARCHAR(5) NOT NULL,
            real_hash CHAR(32) NOT NULL,
            virtual_hash CHAR(32) NOT NULL,
            password_hash CHAR(40) NULL,
            filename VARCHAR(255) NOT NULL,
            filesize INTEGER NOT NULL,
            encrypted INTEGER NOT NULL DEFAULT 0,
            mime_type VARCHAR(255) NULL,
            PRIMARY KEY (id))',

        'faqattachment_file' => 'CREATE TABLE %sfaqattachment_file (
            virtual_hash CHAR(32) NOT NULL,
            contents TEXT NOT NULL,
            PRIMARY KEY (virtual_hash))',

        'faqbackup' => 'CREATE TABLE %sfaqbackup (
            id INT(11) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            authkey VARCHAR(255) NOT NULL,
            authcode VARCHAR(255) NOT NULL,
            created timestamp NOT NULL,
            PRIMARY KEY (id))',

        'faqcaptcha' => 'CREATE TABLE %sfaqcaptcha (
            id VARCHAR(6) NOT NULL,
            useragent VARCHAR(255) NOT NULL,
            language VARCHAR(5) NOT NULL,
            ip VARCHAR(64) NOT NULL,
            captcha_time INTEGER NOT NULL,
            PRIMARY KEY (id))',

        'faqcategories' => 'CREATE TABLE %sfaqcategories (
            id INTEGER NOT NULL,
            lang VARCHAR(5) NOT NULL,
            parent_id INTEGER NOT NULL,
            name VARCHAR(255) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            user_id INTEGER NOT NULL,
            group_id INTEGER NOT NULL DEFAULT -1,
            active INTEGER NULL DEFAULT 1,
            image VARCHAR(255) DEFAULT NULL,
            show_home INTEGER DEFAULT NULL,
            PRIMARY KEY (id, lang))',

        'faqcategoryrelations' => 'CREATE TABLE %sfaqcategoryrelations (
            category_id INTEGER NOT NULL,
            category_lang VARCHAR(5) NOT NULL,
            record_id INTEGER NOT NULL,
            record_lang VARCHAR(5) NOT NULL,
            PRIMARY KEY (category_id, category_lang, record_id, record_lang))',

        'faqcategoryrelations_idx' => 'CREATE INDEX idx_records ON %sfaqcategoryrelations (record_id, record_lang)',

        'faqcategory_group' => 'CREATE TABLE %sfaqcategory_group (
            category_id INTEGER NOT NULL,
            group_id INTEGER NOT NULL,
            PRIMARY KEY (category_id, group_id))',

        'faqcategory_news' => 'CREATE TABLE %sfaqcategory_news (
            category_id INTEGER NOT NULL,
            news_id INTEGER NOT NULL,
            PRIMARY KEY (category_id, news_id))',

        'faqcategory_order' => 'CREATE TABLE %sfaqcategory_order (
            category_id INTEGER NOT NULL,
            position INTEGER NOT NULL,
            PRIMARY KEY (category_id))',

        'faqcategory_user' => 'CREATE TABLE %sfaqcategory_user (
            category_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            PRIMARY KEY (category_id, user_id))',

        'faqchanges' => 'CREATE TABLE %sfaqchanges (
            id INTEGER NOT NULL,
            beitrag SMALLINT NOT NULL,
            lang VARCHAR(5) NOT NULL,
            revision_id INTEGER NOT NULL DEFAULT 0,
            usr INTEGER NOT NULL ,
            datum INTEGER NOT NULL,
            what text DEFAULT NULL,
            PRIMARY KEY (id, lang))',

        'faqcomments' => 'CREATE TABLE %sfaqcomments (
            id_comment INTEGER NOT NULL,
            id INTEGER NOT NULL,
            type VARCHAR(10) NOT NULL,
            usr VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            comment text NOT NULL,
            datum VARCHAR(64) NOT NULL,
            helped text DEFAULT NULL,
            PRIMARY KEY (id_comment))',

        'faqconfig' => 'CREATE TABLE %sfaqconfig (
            config_name VARCHAR(255) NOT NULL default \'\',
            config_value TEXT DEFAULT NULL,
            PRIMARY KEY (config_name))',

        'faqdata' => 'CREATE TABLE %sfaqdata (
            id INTEGER NOT NULL,
            lang VARCHAR(5) NOT NULL,
            solution_id INTEGER NOT NULL,
            revision_id INTEGER NOT NULL DEFAULT 0,
            active char(3) NOT NULL,
            sticky INTEGER NOT NULL,
            keywords text DEFAULT NULL,
            thema text NOT NULL,
            content text DEFAULT NULL,
            author VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            comment char(1) default \'y\',
            updated VARCHAR(15) NOT NULL,
            date_start VARCHAR(14) NOT NULL DEFAULT \'00000000000000\',
            date_end VARCHAR(14) NOT NULL DEFAULT \'99991231235959\',
            created DATETIME DEFAULT CURRENT_TIMESTAMP,
            notes text DEFAULT NULL,
            PRIMARY KEY (id, lang))',

        'faqdata_revisions' => 'CREATE TABLE %sfaqdata_revisions (
            id INTEGER NOT NULL,
            lang VARCHAR(5) NOT NULL,
            solution_id INTEGER NOT NULL,
            revision_id INTEGER NOT NULL DEFAULT 0,
            active char(3) NOT NULL,
            sticky INTEGER NOT NULL,
            keywords text DEFAULT NULL,
            thema text NOT NULL,
            content text DEFAULT NULL,
            author VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            comment char(1) default \'y\',
            updated VARCHAR(15) NOT NULL,
            date_start VARCHAR(14) NOT NULL DEFAULT \'00000000000000\',
            date_end VARCHAR(14) NOT NULL DEFAULT \'99991231235959\',
            created DATETIME DEFAULT CURRENT_TIMESTAMP,
            notes text DEFAULT NULL,
            PRIMARY KEY (id, lang, solution_id, revision_id))',

        'faqdata_group' => 'CREATE TABLE %sfaqdata_group (
            record_id INTEGER NOT NULL,
            group_id INTEGER NOT NULL,
            PRIMARY KEY (record_id, group_id))',

        'faqdata_tags' => 'CREATE TABLE %sfaqdata_tags (
            record_id INTEGER NOT NULL,
            tagging_id INTEGER NOT NULL,
            PRIMARY KEY (record_id, tagging_id))',

        'faqdata_user' => 'CREATE TABLE %sfaqdata_user (
            record_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            PRIMARY KEY (record_id, user_id))',

        'faqglossary' => 'CREATE TABLE %sfaqglossary (
            id INTEGER NOT NULL ,
            lang VARCHAR(5) NOT NULL ,
            item VARCHAR(255) NOT NULL ,
            definition text NOT NULL,
            PRIMARY KEY (id, lang))',

        'faqgroup' => 'CREATE TABLE %sfaqgroup (
            group_id INTEGER NOT NULL,
            name VARCHAR(25) NULL,
            description text NULL,
            auto_join INTEGER NULL,
            PRIMARY KEY(group_id))',

        'faqgroup_idx' => 'CREATE UNIQUE INDEX idx_name ON %sfaqgroup (name)',

        'faqgroup_right' => 'CREATE TABLE %sfaqgroup_right (
            group_id INTEGER NOT NULL,
            right_id INTEGER NOT NULL,
            PRIMARY KEY(group_id, right_id))',

        'faqinstances' => 'CREATE TABLE %sfaqinstances (
            id INT NOT NULL,
            url VARCHAR(255) NOT NULL,
            instance VARCHAR(255) NOT NULL,
            comment TEXT NULL,
            created DATETIME NOT NULL,
            modified DATETIME NOT NULL,
            PRIMARY KEY (id))',

        'faqinstances_config' => 'CREATE TABLE %sfaqinstances_config (
            instance_id INT NOT NULL,
            config_name VARCHAR(255) NOT NULL default \'\',
            config_value VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (instance_id, config_name))',

        'faqmeta' => 'CREATE TABLE %sfaqmeta (
            id INT NOT NULL,
            lang VARCHAR(5) DEFAULT NULL,
            page_id VARCHAR(48) DEFAULT NULL,
            type VARCHAR(48) DEFAULT NULL,
            content TEXT NULL,
            PRIMARY KEY (id))',

        'faqnews' => 'CREATE TABLE %sfaqnews (
            id INTEGER NOT NULL,
            lang VARCHAR(5) NOT NULL,
            header VARCHAR(255) NOT NULL,
            artikel text NOT NULL,
            datum VARCHAR(14) NOT NULL,
            author_name  VARCHAR(255) NULL,
            author_email VARCHAR(255) NULL,
            active char(1) default \'y\',
            comment char(1) default \'n\',
            date_start VARCHAR(14) NOT NULL DEFAULT \'00000000000000\',
            date_end VARCHAR(14) NOT NULL DEFAULT \'99991231235959\',
            link VARCHAR(255) DEFAULT NULL,
            linktitel VARCHAR(255) DEFAULT NULL,
            target VARCHAR(255) NOT NULL,
            PRIMARY KEY (id))',

        'faqquestions' => 'CREATE TABLE %sfaqquestions (
            id INTEGER NOT NULL,
            lang VARCHAR(5) NOT NULL,
            username VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            category_id INTEGER NOT NULL,
            question text NOT NULL,
            created VARCHAR(20) NOT NULL,
            is_visible char(1) default \'Y\',
            answer_id INTEGER NOT NULL DEFAULT 0,
            PRIMARY KEY (id))',

        'faqright' => 'CREATE TABLE %sfaqright (
            right_id INTEGER NOT NULL,
            name VARCHAR(50) NULL,
            description text NULL,
            for_users INTEGER NULL DEFAULT 1,
            for_groups INTEGER NULL DEFAULT 1,
            for_sections INTEGER NULL DEFAULT 1,
            PRIMARY KEY (right_id))',

        'faqsearches' => 'CREATE TABLE %sfaqsearches (
            id INTEGER NOT NULL ,
            lang VARCHAR(5) NOT NULL ,
            searchterm VARCHAR(255) NOT NULL ,
            searchdate DATETIME,
            PRIMARY KEY (id, lang))',

        'faqsessions' => 'CREATE TABLE %sfaqsessions (
            sid INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            ip VARCHAR(64) NOT NULL,
            time INTEGER NOT NULL,
            PRIMARY KEY (sid))',

        'faqsessions_idx' => 'CREATE INDEX idx_time ON %sfaqsessions (time)',

        'faqstopwords' => 'CREATE TABLE %sfaqstopwords (
            id INTEGER NOT NULL,
            lang VARCHAR(5) NOT NULL,
            stopword VARCHAR(64) NOT NULL,
            PRIMARY KEY (id, lang))',

        'faqtags' => 'CREATE TABLE %sfaqtags (
            tagging_id INTEGER NOT NULL,
            tagging_name VARCHAR(255) NOT NULL ,
            PRIMARY KEY (tagging_id, tagging_name))',

        'faquser' => 'CREATE TABLE %sfaquser (
            user_id INTEGER NOT NULL,
            login VARCHAR(128) NOT NULL,
            session_id VARCHAR(150) NULL,
            session_timestamp INTEGER NULL,
            ip VARCHAR(15) NULL,
            account_status VARCHAR(50) NULL,
            last_login VARCHAR(14) NULL,
            auth_source VARCHAR(100) NULL,
            member_since VARCHAR(14) NULL,
            remember_me VARCHAR(150) NULL,
            success INT(1) NULL DEFAULT 1,
            is_superadmin INT(1) NULL DEFAULT 0,
            login_attempts INT(1) NULL DEFAULT 0,
            refresh_token TEXT NULL DEFAULT NULL,
            access_token TEXT NULL DEFAULT NULL,
            code_verifier VARCHAR(255) NULL DEFAULT NULL,
            jwt TEXT NULL DEFAULT NULL,
            PRIMARY KEY (user_id))',

        'faquserdata' => 'CREATE TABLE %sfaquserdata (
            user_id INTEGER NOT NULL,
            last_modified VARCHAR(14) NULL,
            display_name VARCHAR(128) NULL,
            email VARCHAR(128) NULL,
            is_visible INT(1) NULL DEFAULT 0,
            twofactor_enabled INT(1) NULL DEFAULT 0,
            secret VARCHAR(128) NULL DEFAULT NULL)',

        'faquserlogin' => 'CREATE TABLE %sfaquserlogin (
            login VARCHAR(128) NOT NULL,
            pass VARCHAR(80) NULL,
            domain VARCHAR(255) NULL,
            PRIMARY KEY (login))',

        'faquser_group' => 'CREATE TABLE %sfaquser_group (
            user_id INTEGER NOT NULL,
            group_id INTEGER NOT NULL,
            PRIMARY KEY (user_id, group_id))',

        'faquser_right' => 'CREATE TABLE %sfaquser_right (
            user_id INTEGER NOT NULL,
            right_id INTEGER NOT NULL,
            PRIMARY KEY (user_id, right_id))',

        'faqvisits' => 'CREATE TABLE %sfaqvisits (
            id INTEGER NOT NULL,
            lang VARCHAR(5) NOT NULL,
            visits INTEGER NOT NULL,
            last_visit INTEGER NOT NULL,
            PRIMARY KEY (id, lang))',

        'faqvoting' => 'CREATE TABLE %sfaqvoting (
            id INTEGER NOT NULL,
            artikel SMALLINT NOT NULL,
            vote SMALLINT NOT NULL,
            usr SMALLINT NOT NULL,
            datum VARCHAR(20) DEFAULT \'\',
            ip VARCHAR(15) DEFAULT \'\',
            PRIMARY KEY (id))',
    ];

    /**
     * Constructor.
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Executes all CREATE TABLE and CREATE INDEX statements.
     *
     *
     */
    public function createTables(string $prefix = ''): bool
    {
        foreach ($this->createTableStatements as $stmt) {
            $result = $this->config->getDb()->query(sprintf($stmt, $prefix));

            if (!$result) {
                return false;
            }
        }

        return true;
    }
}
