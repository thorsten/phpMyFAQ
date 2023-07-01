<?php

/**
 * The phpMyFAQ instances database class with CREATE TABLE statements for MySQL, MariaBD, Percona Server and Galera
 * Cluster for MySQL.
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
 * @since     2015-04-06
 */

namespace phpMyFAQ\Instance\Database;

use phpMyFAQ\Configuration;
use phpMyFAQ\Instance\Database;

/**
 * Class Mysqli
 *
 * @package phpMyFAQ\Instance\Database
 */
class Mysqli extends Database implements Driver
{
    private array $createTableStatements = [
        'faqadminlog' => 'CREATE TABLE %sfaqadminlog (
            id INT(11) NOT NULL,
            time INT(11) NOT NULL,
            usr INT(11) NOT NULL,
            text TEXT NOT NULL,
            ip VARCHAR(64) NOT NULL,
            PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqattachment' => 'CREATE TABLE %sfaqattachment (
            id INT(11) NOT NULL,
            record_id INT(11) NOT NULL,
            record_lang VARCHAR(5) NOT NULL,
            real_hash CHAR(32) NOT NULL,
            virtual_hash CHAR(32) NOT NULL,
            password_hash CHAR(40) NULL,
            filename VARCHAR(255) NOT NULL,
            filesize INT(11) NOT NULL,
            encrypted INT(11) NOT NULL DEFAULT 0,
            mime_type VARCHAR(255) NULL,
            PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqattachment_file' => 'CREATE TABLE %sfaqattachment_file (
            virtual_hash CHAR(32) NOT NULL,
            contents BLOB NOT NULL,
            PRIMARY KEY (virtual_hash)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqbackup' => 'CREATE TABLE %sfaqbackup (
            id INT(11) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            authkey VARCHAR(255) NOT NULL,
            authcode VARCHAR(255) NOT NULL,
            created timestamp NOT NULL,
            PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqcaptcha' => 'CREATE TABLE %sfaqcaptcha (
            id VARCHAR(6) NOT NULL,
            useragent VARCHAR(255) NOT NULL,
            language VARCHAR(5) NOT NULL,
            ip VARCHAR(64) NOT NULL,
            captcha_time INT(11) NOT NULL,
            PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqcategories' => 'CREATE TABLE %sfaqcategories (
            id INT(11) NOT NULL,
            lang VARCHAR(5) NOT NULL,
            parent_id INTEGER NOT NULL,
            name VARCHAR(255) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            user_id INT(11) NOT NULL,
            group_id INT(11) NOT NULL DEFAULT -1,
            active INT(11) NULL DEFAULT 1,
            image VARCHAR(255) DEFAULT NULL,
            show_home INT(1) DEFAULT NULL,
            PRIMARY KEY (id, lang)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqcategory_news' => 'CREATE TABLE %sfaqcategory_news (
            category_id INT(11) NOT NULL,
            news_id INT(11) NOT NULL,
            PRIMARY KEY (category_id, news_id)) ENGINE = InnoDB',

        'faqcategoryrelations' => 'CREATE TABLE %sfaqcategoryrelations (
            category_id INT(11) NOT NULL,
            category_lang VARCHAR(5) NOT NULL,
            record_id INT(11) NOT NULL,
            record_lang VARCHAR(5) NOT NULL,
            PRIMARY KEY (category_id, category_lang, record_id, record_lang),
            KEY idx_records (record_id, record_lang)) 
            DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqcategory_group' => 'CREATE TABLE %sfaqcategory_group (
            category_id INT(11) NOT NULL,
            group_id INT(11) NOT NULL,
            PRIMARY KEY (category_id, group_id)) 
            DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqcategory_user' => 'CREATE TABLE %sfaqcategory_user (
            category_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            PRIMARY KEY (category_id, user_id)) 
            DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqcategory_order' => 'CREATE TABLE %sfaqcategory_order (
            category_id int(11) NOT NULL,
            position int(11) NOT NULL,
            PRIMARY KEY (category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqchanges' => 'CREATE TABLE %sfaqchanges (
            id INT(11) NOT NULL,
            beitrag SMALLINT NOT NULL,
            lang VARCHAR(5) NOT NULL,
            revision_id INT(11) NOT NULL DEFAULT 0,
            usr INT(11) NOT NULL ,
            datum INT(11) NOT NULL,
            what text DEFAULT NULL,
            PRIMARY KEY (id, lang)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqcomments' => 'CREATE TABLE %sfaqcomments (
            id_comment INT(11) NOT NULL,
            id INT(11) NOT NULL,
            type VARCHAR(10) NOT NULL,
            usr VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            comment text NOT NULL,
            datum VARCHAR(64) NOT NULL,
            helped text DEFAULT NULL,
            PRIMARY KEY (id_comment)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqconfig' => 'CREATE TABLE %sfaqconfig (
            config_name VARCHAR(255) NOT NULL default \'\',
            config_value TEXT DEFAULT NULL,
            PRIMARY KEY (config_name)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqdata' => 'CREATE TABLE %sfaqdata (
            id INT(11) NOT NULL,
            lang VARCHAR(5) NOT NULL,
            solution_id INT(11) NOT NULL,
            revision_id INT(11) NOT NULL DEFAULT 0,
            active char(3) NOT NULL,
            sticky INT(11) NOT NULL,
            keywords TEXT DEFAULT NULL,
            thema TEXT NOT NULL,
            content LONGTEXT DEFAULT NULL,
            author VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            comment char(1) default \'y\',
            updated VARCHAR(15) NOT NULL,
            date_start VARCHAR(14) NOT NULL DEFAULT \'00000000000000\',
            date_end VARCHAR(14) NOT NULL DEFAULT \'99991231235959\',
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            notes TEXT DEFAULT NULL,
            FULLTEXT (keywords,thema,content),
            PRIMARY KEY (id, lang)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqdata_revisions' => 'CREATE TABLE %sfaqdata_revisions (
            id INT(11) NOT NULL,
            lang VARCHAR(5) NOT NULL,
            solution_id INT(11) NOT NULL,
            revision_id INT(11) NOT NULL DEFAULT 0,
            active char(3) NOT NULL,
            sticky INT(11) NOT NULL,
            keywords TEXT DEFAULT NULL,
            thema TEXT NOT NULL,
            content LONGTEXT DEFAULT NULL,
            author VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            comment char(1) default \'y\',
            updated VARCHAR(15) NOT NULL,
            date_start VARCHAR(14) NOT NULL DEFAULT \'00000000000000\',
            date_end VARCHAR(14) NOT NULL DEFAULT \'99991231235959\',
            created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            notes text DEFAULT NULL,
            PRIMARY KEY (id, lang, solution_id, revision_id)) 
            DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqdata_group' => 'CREATE TABLE %sfaqdata_group (
            record_id INT(11) NOT NULL,
            group_id INT(11) NOT NULL,
            PRIMARY KEY (record_id, group_id))',

        'faqdata_tags' => 'CREATE TABLE %sfaqdata_tags (
            record_id INT(11) NOT NULL,
            tagging_id INT(11) NOT NULL,
            PRIMARY KEY (record_id, tagging_id))',

        'faqdata_user' => 'CREATE TABLE %sfaqdata_user (
            record_id INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            PRIMARY KEY (record_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqglossary' => 'CREATE TABLE %sfaqglossary (
            id INT(11) NOT NULL ,
            lang VARCHAR(5) NOT NULL ,
            item VARCHAR(255) NOT NULL ,
            definition text NOT NULL,
            PRIMARY KEY (id, lang)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqgroup' => 'CREATE TABLE %sfaqgroup (
            group_id INT(11) NOT NULL,
            name VARCHAR(25) NULL,
            description text NULL,
            auto_join INT(11) NULL,
            PRIMARY KEY(group_id),
            KEY idx_name (name)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqgroup_right' => 'CREATE TABLE %sfaqgroup_right (
            group_id INT(11) NOT NULL,
            right_id INT(11) NOT NULL,
            PRIMARY KEY(group_id, right_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqinstances' => 'CREATE TABLE %sfaqinstances (
            id INT NOT NULL,
            url VARCHAR(255) NOT NULL,
            instance VARCHAR(255) NOT NULL,
            comment TEXT NULL,
            created TIMESTAMP DEFAULT \'1977-04-07 14:47:00\',
            modified DATETIME NOT NULL,
            PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqinstances_config' => 'CREATE TABLE %sfaqinstances_config (
            instance_id INT NOT NULL,
            config_name VARCHAR(255) NOT NULL default \'\',
            config_value VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (instance_id, config_name))
            DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqmeta' => 'CREATE TABLE %sfaqmeta (
            id INT(11) unsigned NOT NULL,
            lang VARCHAR(5) DEFAULT NULL,
            page_id VARCHAR(48) DEFAULT NULL,
            type VARCHAR(48) DEFAULT NULL,
            content TEXT NULL,
            PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqnews' => 'CREATE TABLE %sfaqnews (
            id INT(11) NOT NULL,
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
            PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqquestions' => 'CREATE TABLE %sfaqquestions (
            id INT(11) NOT NULL,
            lang VARCHAR(5) NOT NULL,
            username VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            category_id INT(11) NOT NULL,
            question text NOT NULL,
            created VARCHAR(20) NOT NULL,
            is_visible char(1) default \'Y\',
            answer_id INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqright' => 'CREATE TABLE %sfaqright (
            right_id INT(11) NOT NULL,
            name VARCHAR(50) NULL,
            description text NULL,
            for_users INT(11) NULL DEFAULT 1,
            for_groups INT(11) NULL DEFAULT 1,
            for_sections INT(11) NULL DEFAULT 1,
            PRIMARY KEY (right_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqsearches' => 'CREATE TABLE %sfaqsearches (
            id INT(11) NOT NULL ,
            lang VARCHAR(5) NOT NULL ,
            searchterm VARCHAR(255) NOT NULL ,
            searchdate TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id, lang)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqsessions' => 'CREATE TABLE %sfaqsessions (
            sid INT(11) NOT NULL,
            user_id INT(11) NOT NULL,
            ip VARCHAR(64) NOT NULL,
            time INT(11) NOT NULL,
            PRIMARY KEY (sid)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqsessions_idx' => 'CREATE INDEX idx_time ON %sfaqsessions (time)',

        'faqstopwords' => 'CREATE TABLE %sfaqstopwords (
            id INT(11) NOT NULL,
            lang VARCHAR(5) NOT NULL,
            stopword VARCHAR(64) NOT NULL,
            PRIMARY KEY (id, lang)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqtags' => 'CREATE TABLE %sfaqtags (
            tagging_id INT(11) NOT NULL,
            tagging_name VARCHAR(255) NOT NULL ,
            PRIMARY KEY (tagging_id, tagging_name))
            DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faquser' => 'CREATE TABLE %sfaquser (
            user_id INT(11) NOT NULL,
            login VARCHAR(128) NOT NULL,
            session_id VARCHAR(150) NULL,
            session_timestamp INT(11) NULL,
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
            PRIMARY KEY (user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faquserdata' => 'CREATE TABLE %sfaquserdata (
            user_id INT(11) NOT NULL,
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
            PRIMARY KEY (login)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faquser_group' => 'CREATE TABLE %sfaquser_group (
            user_id INT(11) NOT NULL,
            group_id INT(11) NOT NULL,
            PRIMARY KEY (user_id, group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faquser_right' => 'CREATE TABLE %sfaquser_right (
            user_id INT(11) NOT NULL,
            right_id INT(11) NOT NULL,
            PRIMARY KEY (user_id, right_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqvisits' => 'CREATE TABLE %sfaqvisits (
            id INT(11) NOT NULL,
            lang VARCHAR(5) NOT NULL,
            visits INT(11) NOT NULL,
            last_visit INT(11) NOT NULL,
            PRIMARY KEY (id, lang)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',

        'faqvoting' => 'CREATE TABLE %sfaqvoting (
            id INT(11) NOT NULL,
            artikel INT(11) NOT NULL,
            vote INT(11) NOT NULL,
            usr INT(11) NOT NULL,
            datum VARCHAR(20) DEFAULT \'\',
            ip VARCHAR(15) DEFAULT \'\',
            PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB',
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
                echo sprintf($stmt, $prefix);
                echo $this->config->getDb()->error();

                return false;
            }
        }

        return true;
    }
}
