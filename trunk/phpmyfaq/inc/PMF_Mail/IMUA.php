<?php
/**
 * MUA (Mail User Agent) interface.
 *
 * @package    phpMyFAQ
 * @subpackage Mail
 * @author     Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @since      2009-09-11
 * @version    SVN: $Id$
 * @copyright  2009 phpMyFAQ Team
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
 * PHP 6 script encoding
 *
 */
declare(encoding='latin1');
 
 /**
  * MUA (Mail User Agent) interface.
  *
  * @package phpMyFAQ 
  * @access public
  */ 
interface PMF_Mail_IMUA
{
    /**
     * Send the message using an e-mail.
     * 
     * @param  string $recipients Recipients of the e-mail as a comma-separated list
     *                            of RFC 2822 compliant elements
     * @param  array  $headers    Headers of the e-mail
     * @param  string $body       Body of the e-mail
     * @return bool True if successful, false otherwise.     
     */
    public function send($recipients, $headers, $body);
}
