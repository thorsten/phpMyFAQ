<?php

namespace phpMyFAQ\Search;

/**
 * Implements result sets for phpMyFAQ search classes.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2010-06-06
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Class Resultset.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2010-06-06
 */
class Resultset
{
    /**
     * @var Configuration
     */
    protected $_config = null;

    /**
     * "Raw" search result set without permission checks and with possible
     * duplicates.
     *
     * @var array
     */
    protected $rawResultset = [];

    /**
     * "Reviewed" search result set with checked permissions and without
     * duplicates.
     *
     * @var array
     */
    protected $reviewedResultset = [];

    /**
     * Ordering of result set.
     *
     * @var string
     */
    protected $ordering;

    /**
     * Number of search results.
     *
     * @var int
     */
    protected $numberOfResults = 0;

    /**
     * User object.
     *
     * @var User
     */
    protected $user = null;

    /**
     * FaqHelper object.
     *
     * @var Faq
     */
    protected $faq = null;

    /**
     * Constructor.
     *
     * @param CurrentUser   $user   User object
     * @param Faq           $faq    FaqHelper object
     * @param Configuration $config Configuration object
     */
    public function __construct(CurrentUser $user, Faq $faq, Configuration $config)
    {
        $this->user = $user;
        $this->faq = $faq;
        $this->_config = $config;
    }

    /**
     * Check on user and group permissions and on duplicate FAQs.
     *
     * @param array $resultSet Array with search results
     */
    public function reviewResultset(Array $resultSet)
    {
        $this->setResultset($resultSet);

        $duplicateResults = [];

        if ('medium' === $this->_config->get('security.permLevel')) {
            $currentGroupIds = $this->user->perm->getUserGroups($this->user->getUserId());
        } else {
            $currentGroupIds = [-1];
        }

        foreach ($this->rawResultset as $result) {
            $permission = false;
            // check permission for sections
            if ('large' === $this->_config->get('security.permLevel')) {
                // @todo Add code for section permissions
                $permission = true;
            }

            // check permissions for groups
            if ('medium' === $this->_config->get('security.permLevel')) {
                $groupPermissions = $this->faq->getPermission('group', $result->id);
                if (is_array($groupPermissions)) {
                    foreach ($groupPermissions as $groupPermission) {
                        if (in_array($groupPermission, $currentGroupIds)) {
                            $permission = true;
                        }
                    }
                }
            }
            // check permission for user
            if ('basic' === $this->_config->get('security.permLevel')) {
                $userPermission = $this->faq->getPermission('user', $result->id);
                if (in_array(-1, $userPermission) || in_array($this->user->getUserId(), $userPermission)) {
                    $permission = true;
                } else {
                    $permission = false;
                }
            }

            // check on duplicates
            if (!isset($duplicateResults[$result->id])) {
                $duplicateResults[$result->id] = 1;
            } else {
                ++$duplicateResults[$result->id];
                continue;
            }

            if (!isset($result->score)) {
                $result->score = $this->getScore($result);
            }

            if ($permission) {
                $this->reviewedResultset[] = $result;
            }
        }

        $this->setNumberOfResults($this->reviewedResultset);
    }

    /**
     * Sets the "raw" search results.
     *
     * @param array $resultSet Array with search results
     */
    public function setResultset(Array $resultSet)
    {
        $this->rawResultset = $resultSet;
    }

    /**
     * Returns the "reviewed" search results.
     *
     * @return array
     */
    public function getResultset()
    {
        return $this->reviewedResultset;
    }

    /**
     * Sets the number of search results.
     *
     * @param array
     */
    public function setNumberOfResults(Array $resultSet)
    {
        $this->numberOfResults = count($resultSet);
    }

    /**
     * Returns the number search results.
     *
     * @return int
     */
    public function getNumberOfResults()
    {
        return $this->numberOfResults;
    }

    /**
     * @param \stdClass $object
     *
     * @return float
     */
    public function getScore(\stdClass $object)
    {
        $score = 0;

        if (isset($object->relevance_thema)) {
            $score += $object->relevance_thema;
        }

        if (isset($object->relevance_content)) {
            $score += $object->relevance_thema;
        }

        if (isset($object->relevance_keywords)) {
            $score += $object->relevance_keywords;
        }

        return round($score/3*100);
    }
}
