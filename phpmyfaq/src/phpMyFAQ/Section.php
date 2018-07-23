<?php

namespace phpMyFAQ;

/**
 * The section class provides sections
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Timo Wolf <amna.wolf@gmail.com>
 * @copyright 2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-07-19
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Db;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Section.
 *
 * @category  phpMyFAQ
 * @author    Timo Wolf <amna.wolf@gmail.com>
 * @copyright 2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-07-19
 */
class Section
{
    /**
     * Configuration object.
     *
     * @var Configuration
     */
    private $config;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Adds a new section entry.
     *
     * @param string $name Name of the section
     * @param string $description Description of the category
     *
     * @return int
     */
    public function addSection($name, $description)
    {
        $id = $this->config->getDb()->nextId(Db::getTablePrefix() . 'faqsections', 'id');

        $query = sprintf("
            INSERT INTO
                %sfaqsections
            (id, name, description)
                VALUES
            (%d, '%s', '%s')",
            Db::getTablePrefix(),
            $id,
            $name,
            $description
        );
        $this->config->getDb()->query($query);

        return $id;
    }

    /**
     * Gets one section by id.
     *
     * @param int $sectionId
     * @return array
     */
    public function getSection($sectionId)
    {
        $query = sprintf("
            SELECT * 
            FROM %sfaqsections
            WHERE id = %d",
            Db::getTablePrefix(),
            $sectionId
        );

        $res = $this->config->getDb()->query($query);

        if ($res) {
            return $this->config->getDb()->fetchArray($res);
        }

        return [];
    }

    /**
     * Get all sections.
     *
     * @return array
     */
    public function getAllSections()
    {
        $query = sprintf('SELECT id, name, description FROM %sfaqsections', Db::getTablePrefix());
        $res = $this->config->getDb()->query($query);

        if ($res) {
            return $this->config->getDb()->fetchAll($res);
        }

        return [];
    }

    /**
     * updates a section entry.
     *
     * @param int $id Id of the section to edit
     * @param string $name Name of the section
     * @param string $description Description of the category
     *
     * @return bool
     */
    public function updateSection($id, $name, $description)
    {
        $update = sprintf("
            UPDATE
                %sfaqsections
            (name, description)
                VALUES
            ('%s', '%s')
            WHERE id = %d
            ",
            Db::getTablePrefix(),
            $name,
            $description,
            $id
        );

        $res = $this->config->getDb()->query($update);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * deletes a section entry.
     *
     * @param int $id Id of the section to edit
     *
     * @return bool
     */
    public function deleteSection($id)
    {
        $delete = sprintf("
            DELETE FROM
                %sfaqsections
            WHERE id = %d
            ",
            Db::getTablePrefix(),
            $id
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        $delete = sprintf("
            DELETE FROM
                %sfaqsection_category
            WHERE section_id = %d
            ",
            Db::getTablePrefix(),
            $id
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        $delete = sprintf("
            DELETE FROM
                %sfaqsection_user
            WHERE section_id = %d
            ",
            Db::getTablePrefix(),
            $id
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        $delete = sprintf("
            DELETE FROM
                %sfaqsection_group
            WHERE section_id = %d
            ",
            Db::getTablePrefix(),
            $id
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        $delete = sprintf("
            DELETE FROM
                %sfaqsection_right
            WHERE section_id = %d
            ",
            Db::getTablePrefix(),
            $id
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        $delete = sprintf("
            DELETE FROM
                %sfaqsection_news
            WHERE section_id = %d
            ",
            Db::getTablePrefix(),
            $id
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * adds a section - category relation
     *
     * @param int $sectionId Id of the section
     * @param int $categoryId Id of the category
     *
     * @return bool
     */
    public function addSectionCategory($sectionId, $categoryId)
    {
        $insert = sprintf("
            INSERT INTO
                %sfaqsection_category
            (sectionId, categoryId)
                VALUES
            (%d, %d)
            ",
            Db::getTablePrefix(),
            $sectionId,
            $categoryId
        );

        $res = $this->config->getDb()->query($insert);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * removes a section - category relation
     *
     * @param int $sectionId Id of the section
     * @param int $categoryId Id of the category
     *
     * @return bool
     */
    public function removeSectionCategory($sectionId, $categoryId)
    {
        $delete = sprintf("
            DELETE FROM
                %sfaqsection_category
            WHERE 
                sectionId = %d AND categoryId = %d
            ",
            Db::getTablePrefix(),
            $sectionId,
            $categoryId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * adds a section - user relation
     *
     * @param int $sectionId Id of the section
     * @param int $userId Id of the user
     *
     * @return bool
     */
    public function addSectionuser($sectionId, $userId)
    {
        $insert = sprintf("
            INSERT INTO
                %sfaqsection_user
            (sectionId, userId)
                VALUES
            (%d, %d)
            ",
            Db::getTablePrefix(),
            $sectionId,
            $userId
        );

        $res = $this->config->getDb()->query($insert);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * removes a section - user relation
     *
     * @param int $sectionId Id of the section
     * @param int $userId Id of the user
     *
     * @return bool
     */
    public function removeSectionUser($sectionId, $userId)
    {
        $delete = sprintf("
            DELETE FROM
                %sfaqsection_user
            WHERE 
                sectionId = %d AND userId = %d
            ",
            Db::getTablePrefix(),
            $sectionId,
            $userId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * adds a section - group relation
     *
     * @param int $sectionId Id of the section
     * @param int $groupId Id of the group
     *
     * @return bool
     */
    public function addSectionGroup($sectionId, $groupId)
    {
        $insert = sprintf("
            INSERT INTO
                %sfaqsection_group
            (sectionId, groupId)
                VALUES
            (%d, %d)
            ",
            Db::getTablePrefix(),
            $sectionId,
            $groupId
        );

        $res = $this->config->getDb()->query($insert);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * removes a section - group relation
     *
     * @param int $sectionId Id of the section
     * @param int $groupId Id of the group
     *
     * @return bool
     */
    public function removeSectionGroup($sectionId, $groupId)
    {
        $delete = sprintf("
            DELETE FROM
                %sfaqsection_group
            WHERE 
                sectionId = %d AND groupId = %d
            ",
            Db::getTablePrefix(),
            $sectionId,
            $groupId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * adds a section - news relation
     *
     * @param int $sectionId Id of the section
     * @param int $newsId Id of the news
     *
     * @return bool
     */
    public function addSectionNews($sectionId, $newsId)
    {
        $insert = sprintf("
            INSERT INTO
                %sfaqsection_news
            (sectionId, newsId)
                VALUES
            (%d, %d)
            ",
            Db::getTablePrefix(),
            $sectionId,
            $newsId
        );

        $res = $this->config->getDb()->query($insert);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * removes a section - news relation
     *
     * @param int $sectionId Id of the section
     * @param int $newsId Id of the news
     *
     * @return bool
     */
    public function removeSectionNews($sectionId, $newsId)
    {
        $delete = sprintf("
            DELETE FROM
                %sfaqsection_news
            WHERE 
                sectionId = %d AND newsId = %d
            ",
            Db::getTablePrefix(),
            $sectionId,
            $newsId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }
}
