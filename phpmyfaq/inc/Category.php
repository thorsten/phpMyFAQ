<?php
/**
 * The main category class
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Category
 * @license    MPL
 * @author     Johannes Schlüter <johannes@schlueters.de>
 * @since      2010-01-04
 * @copyright  2004-2009 phpMyFAQ Team
 * @version    SVN: $Id: Category.php,v 1.56 2008-01-26 01:02:56 thorstenr Exp $
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

/**
 * PMF_Category
 *
 * @package    phpMyFAQ
 * @subpackage PMF_Category
 * @license    MPL
 * @author     Johannes Schlüter <johannes@schlueters.de>
 * @since      2010-01-04
 * @copyright  2004-2009 phpMyFAQ Team
 * @version    SVN: $Id: Category.php,v 1.56 2008-01-26 01:02:56 thorstenr Exp $
 */
class PMF_Category
{
    private $id;
    private $name;
    private $children;
    private $parent_id;

    /**
     * Constructor
     *
     * @param array $data
     */
    public function __construct(array $data, PMF_Category $parent = null)
    {
        $this->id   = $data['id'];
        $this->name = $data['name'];
        $this->children = $data['children'];
        $this->parent_id = $data['parent_id'];
        $this->parent = $parent;
    }

    public function __toString() {
        return $this->name;
    }

    public function getId() {
        return $this->id;
    }

    public function getParent() {
        if (!$this->parent && $this->parent_id) {
            /* TODO: $this->parent = PMF_Category::createById() */
        }

        return $this->parent;
    }

    public function hasChildren() {
        return (bool)$this->children;
    }
}

