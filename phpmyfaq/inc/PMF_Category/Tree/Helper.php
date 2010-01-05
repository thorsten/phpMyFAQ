<?php
/**
 * Category tree view implementation based on RecursiveIteratorIterator
 *
 * PHP Version 5.2.0
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
 * 
 * @category  phpMyFAQ
 * @package   PMF_Category
 * @author    Johannes Schlüter <johannes@schlueters.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-05
 */

/**
 * PMF_Category_Tree_Helper
 * 
 * @category  phpMyFAQ
 * @package   PMF_Category
 * @author    Johannes Schlüter <johannes@schlueters.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2001-01-05
 */
class PMF_Category_Tree_Helper extends RecursiveIteratorIterator
{
    /**
     * Indentation
     *
     * @var integer
     */
    public $indent = 0;
    
    /**
     * Constructor
     *
     * @param RecursiveIterator $root Root of the RecursiveIterator object
     */
    public function __construct(RecursiveIterator $root)
    {
        parent::__construct($root, RecursiveIteratorIterator::SELF_FIRST);
    }
    
    /**
     * Begin children
     *
     * @return void
     */
    public function beginChildren()
    {
        $this->indent += 4;
        parent::beginChildren();
    }
    
    /**
     * End children
     *
     * @return void
     */
    public function endChildren()
    {
        $this->indent -= 4;
        parent::endChildren();
    }
}