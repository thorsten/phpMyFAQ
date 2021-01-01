<?php

/**
 * The phpMyFAQ instances database class with CREATE TABLE statements for PostgreSQL.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-04-16
 */

namespace phpMyFAQ\Instance\Database;

use phpMyFAQ\Configuration;
use phpMyFAQ\Instance\Database;

/**
 * Class Pgsql
 *
 * @package phpMyFAQ\Instance\Database
 */
class Pgsql extends Database implements Driver
{
    /**
     * @var array
     */
    private $createTableStatements = [
        'faquser_user_id_seq' => 'CREATE SEQUENCE %sfaquser_user_id_seq START WITH 2',

        'faqadminlog' => 'CREATE TABLE %sfaqadminlog (
            id SERIAL NOT NULL,
            time INTEGER NOT NULL,
            usr INTEGER NOT NULL,
            text TEXT NOT NULL,
            ip VARCHAR(64) NOT NULL,
            PRIMARY KEY (id))',

        'faqattachment' => 'CREATE TABLE %sfaqattachment (
            id SERIAL NOT NULL,
            record_id SERIAL NOT NULL,
            record_lang VARCHAR(5) NOT NULL,
            real_hash CHAR(32) NOT NULL,
            virtual_hash CHAR(32) NOT NULL,
            password_hash CHAR(40) NULL,
            filename VARCHAR(255) NOT NULL,
            filesize INTEGER NOT NULL,
            encrypted INTEGER NOT NULL DEFAULT 0,
            mime_type VARCHAR(255) NULL,
            PRIMARY KEY (id))',

        'faqattachment file' => 'CREATE TABLE %sfaqattachment_file (
            virtual_hash CHAR(32) NOT NULL,
            contents BYTEA,
            PRIMARY KEY (virtual_hash))',

        'faqcaptcha' => 'CREATE TABLE %sfaqcaptcha (
            id VARCHAR(6) NOT NULL,
            useragent VARCHAR(255) NOT NULL,
            language VARCHAR(5) NOT NULL,
            ip VARCHAR(64) NOT NULL,
            captcha_time INTEGER NOT NULL,
            PRIMARY KEY (id))',

        'faqcategories' => 'CREATE TABLE %sfaqcategories (
            id SERIAL NOT NULL,
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

        'idx_records' => 'CREATE INDEX idx_records_%s ON %sfaqcategoryrelations
            (record_id, record_lang)',

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
            id SERIAL NOT NULL,
            beitrag SMALLINT NOT NULL,
            lang VARCHAR(5) NOT NULL,
            revision_id INTEGER NOT NULL DEFAULT 0,
            usr INTEGER NOT NULL ,
            datum INTEGER NOT NULL,
            what text DEFAULT NULL,
            PRIMARY KEY (id, lang))',

        'faqcomments' => 'CREATE TABLE %sfaqcomments (
            id_comment SERIAL NOT NULL,
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
            config_value VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (config_name))',

        'faqdata' => 'CREATE TABLE %sfaqdata (
            id SERIAL NOT NULL,
            lang VARCHAR(5) NOT NULL,
            solution_id INTEGER NOT NULL,
            revision_id INTEGER NOT NULL DEFAULT 0,
            active char(3) NOT NULL,
            sticky INTEGER NOT NULL,
            keywords TEXT DEFAULT NULL,
            thema TEXT NOT NULL,
            content TEXT DEFAULT NULL,
            author VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            comment char(1) default \'y\',
            updated VARCHAR(15) NOT NULL,
            links_state VARCHAR(7) DEFAULT NULL,
            links_check_date INTEGER DEFAULT 0 NOT NULL,
            date_start VARCHAR(14) NOT NULL DEFAULT \'00000000000000\',
            date_end VARCHAR(14) NOT NULL DEFAULT \'99991231235959\',
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            notes text DEFAULT NULL,
            PRIMARY KEY (id, lang))',

        'faqdata_revisions' => 'CREATE TABLE %sfaqdata_revisions (
            id SERIAL NOT NULL,
            lang VARCHAR(5) NOT NULL,
            solution_id INTEGER NOT NULL,
            revision_id INTEGER NOT NULL DEFAULT 0,
            active char(3) NOT NULL,
            sticky INTEGER NOT NULL,
            keywords TEXT DEFAULT NULL,
            thema TEXT NOT NULL,
            content TEXT DEFAULT NULL,
            author VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            comment char(1) default \'y\',
            updated VARCHAR(15) NOT NULL,
            links_state VARCHAR(7) DEFAULT NULL,
            links_check_date INTEGER DEFAULT 0 NOT NULL,
            date_start VARCHAR(14) NOT NULL DEFAULT \'00000000000000\',
            date_end VARCHAR(14) NOT NULL DEFAULT \'99991231235959\',
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
            id SERIAL NOT NULL ,
            lang VARCHAR(5) NOT NULL ,
            item VARCHAR(255) NOT NULL ,
            definition text NOT NULL,
            PRIMARY KEY (id, lang))',

        'faqgroup' => 'CREATE TABLE %sfaqgroup (
            group_id SERIAL NOT NULL,
            name VARCHAR(25) NULL,
            description text NULL,
            auto_join INTEGER NULL,
            PRIMARY KEY(group_id))',

        'faqgroup_right' => 'CREATE TABLE %sfaqgroup_right (
            group_id INTEGER NOT NULL,
            right_id INTEGER NOT NULL,
            PRIMARY KEY(group_id, right_id))',

        'faqinstances' => 'CREATE TABLE %sfaqinstances (
            id SERIAL NOT NULL,
            url VARCHAR(255) NOT NULL,
            instance VARCHAR(255) NOT NULL,
            comment TEXT NULL,
            created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            modified TIMESTAMP NOT NULL,
            PRIMARY KEY (id))',

        'faqinstances_config' => 'CREATE TABLE %sfaqinstances_config (
            instance_id INTEGER NOT NULL,
            config_name VARCHAR(255) NOT NULL default \'\',
            config_value VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (instance_id, config_name))',

        'faqnews' => 'CREATE TABLE %sfaqnews (
            id SERIAL NOT NULL,
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

        'faqmeta' => 'CREATE TABLE %sfaqmeta (
            id SERIAL NOT NULL,
            lang VARCHAR(5) DEFAULT NULL,
            page_id VARCHAR(48) DEFAULT NULL,
            type VARCHAR(48) DEFAULT NULL,
            content TEXT NULL,
            PRIMARY KEY (id))',

        'faqquestions' => 'CREATE TABLE %sfaqquestions (
            id SERIAL NOT NULL,
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
            right_id SERIAL NOT NULL,
            name VARCHAR(50) NULL,
            description text NULL,
            for_users INTEGER NULL DEFAULT 1,
            for_groups INTEGER NULL DEFAULT 1,
            for_sections INTEGER NULL DEFAULT 1,
            PRIMARY KEY (right_id))',

        'faqsearches' => 'CREATE TABLE %sfaqsearches (
            id SERIAL NOT NULL ,
            lang VARCHAR(5) NOT NULL ,
            searchterm VARCHAR(255) NOT NULL ,
            searchdate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id, lang))',

        'faqsections' => 'CREATE TABLE %sfaqsections (
            id INTEGER NOT NULL,
            name VARCHAR(255) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (id))',

        'faqsection_category' => 'CREATE TABLE %sfaqsection_category (
            section_id INTEGER NOT NULL,
            category_id INTEGER NOT NULL DEFAULT -1,
            PRIMARY KEY (section_id, category_id))',

        'faqsection_group' => 'CREATE TABLE %sfaqsection_group (
            section_id INTEGER NOT NULL,
            group_id INTEGER NOT NULL DEFAULT -1,
            PRIMARY KEY (section_id, group_id))',

        'faqsection_news' => 'CREATE TABLE %sfaqsection_news (
            section_id INTEGER NOT NULL,
            news_id INTEGER NOT NULL DEFAULT -1,
            PRIMARY KEY (section_id, news_id))',

        'faqsessions' => 'CREATE TABLE %sfaqsessions (
            sid SERIAL NOT NULL,
            user_id INTEGER NOT NULL,
            ip VARCHAR(64) NOT NULL,
            time INTEGER NOT NULL,
            PRIMARY KEY (sid))',

        'faqsessions_idx' => 'CREATE INDEX idx_time_%s ON %sfaqsessions (time)',

        'faqstopwords' => 'CREATE TABLE %sfaqstopwords (
            id SERIAL NOT NULL,
            lang VARCHAR(5) NOT NULL,
            stopword VARCHAR(64) NOT NULL,
            PRIMARY KEY (id, lang))',

        'faqtags' => 'CREATE TABLE %sfaqtags (
            tagging_id SERIAL NOT NULL,
            tagging_name VARCHAR(255) NOT NULL ,
            PRIMARY KEY (tagging_id, tagging_name))',

        'faquser' => 'CREATE TABLE %sfaquser (
            user_id SERIAL NOT NULL,
            login VARCHAR(128) NOT NULL,
            session_id VARCHAR(150) NULL,
            session_timestamp INTEGER NULL,
            ip VARCHAR(15) NULL,
            account_status VARCHAR(50) NULL,
            last_login VARCHAR(14) NULL,
            auth_source VARCHAR(100) NULL,
            member_since VARCHAR(14) NULL,
            remember_me VARCHAR(150) NULL,
            success SMALLINT NULL DEFAULT 1,
            is_superadmin SMALLINT NULL DEFAULT 0,
            login_attempts SMALLINT NULL DEFAULT 0,
            PRIMARY KEY (user_id))',

        'faquserdata' => 'CREATE TABLE %sfaquserdata (
            user_id SERIAL NOT NULL,
            last_modified VARCHAR(14) NULL,
            display_name VARCHAR(128) NULL,
            email VARCHAR(128) NULL,
            is_visible SMALLINT NULL DEFAULT 0)',

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
            id SERIAL NOT NULL,
            lang VARCHAR(5) NOT NULL,
            visits SMALLINT NOT NULL,
            last_visit INTEGER NOT NULL,
            PRIMARY KEY (id, lang))',

        'faqvoting' => 'CREATE TABLE %sfaqvoting (
            id SERIAL NOT NULL,
            artikel INTEGER NOT NULL,
            vote INTEGER NOT NULL,
            usr INTEGER NOT NULL,
            datum VARCHAR(20) DEFAULT \'\',
            ip VARCHAR(15) DEFAULT \'\',
            PRIMARY KEY (id))',
    ];

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Executes all CREATE TABLE and CREATE INDEX statements.
     *
     * @param string $prefix
     *
     * @return bool
     */
    public function createTables(string $prefix = '')
    {
        foreach ($this->createTableStatements as $key => $stmt) {
            if ($key == 'idx_records' || $key == 'faqsessions_idx') {
                $result = $this->config->getDb()->query(sprintf($stmt, $prefix, $prefix));
            } else {
                $result = $this->config->getDb()->query(sprintf($stmt, $prefix));
            }

            if (!$result) {
                return false;
            }
        }

        return true;
    }
}
