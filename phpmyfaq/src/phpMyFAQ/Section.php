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
 * @copyright 2005-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-07-19
 */

use phpMyFAQ\Configuration;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Section.
 *
 * @category  phpMyFAQ
 * @author    Timo Wolf <amna.wolf@gmail.com>
 * @copyright 2005-2018 phpMyFAQ Team
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
    protected $config;

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
        $id = $this->_config->getDb()->nextId(Db::getTablePrefix() . 'faqsections', 'id');

        $query = sprintf("
            INSERT INTO
                %sfaqsections
            (id, name, description)
                VALUES
            (%d, '%s', '%s')",
            $this->config->getDb()->getTablePrefix(),
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
     * @param string $name Name of the section
     * @param string $description Description of the category
     *
     * @return array
     */
    public function getSection($id)
    {
        $query = sprintf("
            SELECT * 
            FROM %sfaqsections
            WHERE id = %d",
            $this->config->getDb()->getTablePrefix(),
            $id
        );

        $res = $this->config->getDb()->query($query);

        $row = $this->config->getDb()->fetchArray($res);
        
        return $row;
    }

    /**
     * Get all sections.
     *
     * @param string $name Name of the section
     * @param string $description Description of the category
     *
     * @return array
     */
    public function getAllSections()
    {
        $query = sprintf("
            SELECT * 
            FROM %sfaqsections",
            $this->config->getDb()->getTablePrefix(),
        );
        
        $res = $this->config->getDb()->query($query);
        $result = [];
        while($row = $this->config->getDb()->fetchArray($res)){
            $result = $row;
        }

        return $result;
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
            $this->config->getDb()->getTablePrefix(),
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
    public function updateSection($id)
    {

        $delete = sprintf("
            DELETE FROM
                %sfaqsections
            WHERE id = %d
            ",
            $this->config->getDb()->getTablePrefix(),
            $id
        );

        $res = $this->config->getDb()->query($update);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * adds a section - category relation
     *
     * @param int $section_id Id of the section
     * @param int $category_id Id of the category
     *
     * @return bool
     */
    public function addSectionCategory($section_id, $category_id)
    {

        $insert = sprintf("
            INSERT INTO
                %sfaqsection_category
            (section_id, category_id)
                VALUES
            (%d, %d)
            ",
            $this->config->getDb()->getTablePrefix(),
            $section_id,
            $category_id
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
     * @param int $section_id Id of the section
     * @param int $category_id Id of the category
     *
     * @return bool
     */
    public function removeSectionCategory($section_id, $category_id)
    {

        $delete = sprintf("
            DELETE FROM
                %sfaqsection_category
            WHERE 
                section_id = %d AND category_id = %d
            ",
            $this->config->getDb()->getTablePrefix(),
            $section_id,
            $category_id
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
     * @param int $section_id Id of the section
     * @param int $user_id Id of the user
     *
     * @return bool
     */
    public function addSectionuser($section_id, $user_id)
    {

        $insert = sprintf("
            INSERT INTO
                %sfaqsection_user
            (section_id, user_id)
                VALUES
            (%d, %d)
            ",
            $this->config->getDb()->getTablePrefix(),
            $section_id,
            $user_id
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
     * @param int $section_id Id of the section
     * @param int $user_id Id of the user
     *
     * @return bool
     */
    public function removeSectionUser($section_id, $user_id)
    {

        $delete = sprintf("
            DELETE FROM
                %sfaqsection_user
            WHERE 
                section_id = %d AND user_id = %d
            ",
            $this->config->getDb()->getTablePrefix(),
            $section_id,
            $user_id
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
     * @param int $section_id Id of the section
     * @param int $group_id Id of the group
     *
     * @return bool
     */
    public function addSectionCategory($section_id, $group_id)
    {

        $insert = sprintf("
            INSERT INTO
                %sfaqsection_group
            (section_id, group_id)
                VALUES
            (%d, %d)
            ",
            $this->config->getDb()->getTablePrefix(),
            $section_id,
            $group_id
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
     * @param int $section_id Id of the section
     * @param int $group_id Id of the group
     *
     * @return bool
     */
    public function removeSectionCategory($section_id, $group_id)
    {

        $delete = sprintf("
            DELETE FROM
                %sfaqsection_group
            WHERE 
                section_id = %d AND group_id = %d
            ",
            $this->config->getDb()->getTablePrefix(),
            $section_id,
            $group_id
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }
    
    /**
     * adds a section - right relation
     *
     * @param int $section_id Id of the section
     * @param int $right_id Id of the right
     *
     * @return bool
     */
    public function addSectionCategory($section_id, $right_id)
    {

        $insert = sprintf("
            INSERT INTO
                %sfaqsection_right
            (section_id, right_id)
                VALUES
            (%d, %d)
            ",
            $this->config->getDb()->getTablePrefix(),
            $section_id,
            $right_id
        );

        $res = $this->config->getDb()->query($insert);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * removes a section - right relation
     *
     * @param int $section_id Id of the section
     * @param int $right_id Id of the right
     *
     * @return bool
     */
    public function removeSectionCategory($section_id, $right_id)
    {

        $delete = sprintf("
            DELETE FROM
                %sfaqsection_right
            WHERE 
                section_id = %d AND right_id = %d
            ",
            $this->config->getDb()->getTablePrefix(),
            $section_id,
            $right_id
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
     * @param int $section_id Id of the section
     * @param int $news_id Id of the news
     *
     * @return bool
     */
    public function addSectionCategory($section_id, $news_id)
    {

        $insert = sprintf("
            INSERT INTO
                %sfaqsection_news
            (section_id, news_id)
                VALUES
            (%d, %d)
            ",
            $this->config->getDb()->getTablePrefix(),
            $section_id,
            $news_id
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
     * @param int $section_id Id of the section
     * @param int $news_id Id of the news
     *
     * @return bool
     */
    public function removeSectionCategory($section_id, $news_id)
    {

        $delete = sprintf("
            DELETE FROM
                %sfaqsection_news
            WHERE 
                section_id = %d AND news_id = %d
            ",
            $this->config->getDb()->getTablePrefix(),
            $section_id,
            $news_id
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }
}