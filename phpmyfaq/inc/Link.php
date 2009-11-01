<?php
/**
 * Link management - Functions and Classes
 *
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @since     2005-11-02
 * @copyright 2005-2009 phpMyFAQ Team
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

// {{{ Constants
/**#@+
  * General Link definitions
  */
define('PMF_LINK_AMPERSAND', '&amp;');
define('PMF_LINK_CATEGORY', 'category/');
define('PMF_LINK_CONTENT', 'content/');
define('PMF_LINK_EQUAL', '=');
define('PMF_LINK_FRAGMENT_SEPARATOR', '#');
define('PMF_LINK_HTML_MINUS', '-');
define('PMF_LINK_HTML_UNDERSCORE', '_');
define('PMF_LINK_HTML_SLASH', '/');
define('PMF_LINK_HTML_TARGET_BLANK', '_blank');
define('PMF_LINK_HTML_TARGET_PARENT', '_parent');
define('PMF_LINK_HTML_TARGET_SELF', '_self');
define('PMF_LINK_HTML_TARGET_TOP', '_top');
define('PMF_LINK_NEWS', 'news/');
define('PMF_LINK_SITEMAP', 'sitemap/');
define('PMF_LINK_SLASH', '/');
define('PMF_LINK_SEARCHPART_SEPARATOR', '?');
define('PMF_LINK_TAGS', 'tags/');
/**#@-*/
/**#@+
  * System pages definitions
  */
define('PMF_LINK_INDEX_ADMIN', '/admin/index.php');
define('PMF_LINK_INDEX_HOME', '/index.php');
/**#@-*/
/**#@+
  * System GET keys definitions
  */
define('PMF_LINK_GET_ACTION', 'action');
define('PMF_LINK_GET_ARTLANG', 'artlang');
define('PMF_LINK_GET_CATEGORY', 'cat');
define('PMF_LINK_GET_HIGHLIGHT', 'highlight');
define('PMF_LINK_GET_ID', 'id');
define('PMF_LINK_GET_LANG', 'lang');
define('PMF_LINK_GET_LETTER', 'letter');
define('PMF_LINK_GET_NEWS_ID', 'newsid');
define('PMF_LINK_GET_NEWS_LANG', 'newslang');
define('PMF_LINK_GET_PAGE', 'seite');
define('PMF_LINK_GET_SIDS', 'SIDS');
define('PMF_LINK_GET_TAGGING_ID', 'tagging_id');
define('PMF_LINK_GET_LANGS', 'langs');
/**#@-*/
/**#@+
  * System GET values definitions
  */
define('PMF_LINK_GET_ACTION_ADD', 'add');
define('PMF_LINK_GET_ACTION_ARTIKEL', 'artikel');
define('PMF_LINK_GET_ACTION_ASK', 'ask');
define('PMF_LINK_GET_ACTION_CONTACT', 'contact');
define('PMF_LINK_GET_ACTION_HELP', 'help');
define('PMF_LINK_GET_ACTION_NEWS', 'news');
define('PMF_LINK_GET_ACTION_OPEN', 'open');
define('PMF_LINK_GET_ACTION_SEARCH', 'search');
define('PMF_LINK_GET_ACTION_SITEMAP', 'sitemap');
define('PMF_LINK_GET_ACTION_SHOW', 'show');
/**#@-*/
/**#@+
  * Modrewrite virtual pages: w/o extension due to concatenated parameters
  */
define('PMF_LINK_HTML_CATEGORY', 'category');
define('PMF_LINK_HTML_EXTENSION', '.html');
define('PMF_LINK_HTML_SITEMAP', 'sitemap');
/**#@-*/
/**#@+
  * Modrewrite virtual pages: w/ extension
  */
define('PMF_LINK_HTML_ADDCONTENT', 'addcontent.html');
define('PMF_LINK_HTML_ASK', 'ask.html');
define('PMF_LINK_HTML_CONTACT', 'contact.html');
define('PMF_LINK_HTML_HELP', 'help.html');
define('PMF_LINK_HTML_OPEN', 'open.html');
define('PMF_LINK_HTML_SEARCH', 'search.html');
define('PMF_LINK_HTML_SHOWCAT', 'showcat.html');
/**#@-*/
// }}}

// {{{ Functions
function getLinkHtmlAnchor($url, $text = null, $target = null)
{
    $link = new PMF_Link($url, $text, $target);
    return $link->toHtmlAnchor();
}

function getLinkString($url, $forceNoModrewriteSupport = false, $text = null, $target = null)
{
    $link = new PMF_Link($url, $text, $target);
    return $link->toString($forceNoModrewriteSupport);
}

function getLinkUri($url, $text = null, $target = null)
{
    $link = new PMF_Link($url, $text, $target);
    return $link->toUri();
}
// }}}

// {{{ Classes
/**
 * PMF_Link Class
 *
 * This class wrap the needs for managing an HTML anchor
 * taking into account also the HTML anchor creation
 * with specific handling for mod_rewrite PMF native support
 */
class PMF_Link
{
    // {{{ Class properties specific to an HTML link anchor
    var $url        = '';
    var $class      = '';
    var $text       = '';
    var $tooltip    = '';
    var $target     = '';
    var $name       = '';
    // }}}
    // {{{ Class properties specific to the SEO/SEF URLs
    var $itemTitle = '';
    // }}}

    function PMF_Link($url, $text = null, $target = null)
    {
        $this->url = $url;
        $this->text = $text;
        if ( (!isset($text)) || (empty($text)) ) {
            $this->title = '';
        }
        $this->target = $target;
        if ( (!isset($target)) || (empty($target)) ) {
            $this->target = '';
        }
        $this->class   = '';
        $this->tooltip = '';
        $this->name    = '';

        $this->itemTitle = '';
    }

    public static function isIISServer()
    {
        return (
               isset($_SERVER['ALL_HTTP'])      // IIS 5.x possible signature
            || isset($_SERVER['COMPUTERNAME'])  // IIS 5.x possible signature
            || isset($_SERVER['APP_POOL_ID'])   // IIS 6.0 possible signature
        );
    }

    function isAdminIndex()
    {
        if (!$this->isSystemLink()) {
            return false;
        }

        return !(false === strpos($this->url, PMF_LINK_INDEX_ADMIN));
    }

    function isHomeIndex()
    {
        if (!$this->isSystemLink()) {
            return false;
        }

        return !(false === strpos($this->url, PMF_LINK_INDEX_HOME));
    }

    function isInternalReference()
    {
        if ($this->isRelativeSystemLink()) {
            return true;
        }
        if (false === strpos($this->url, '#')) {
            return false;
        }

        return (strpos($this->url, '#') == 0);
    }

    function isRelativeSystemLink()
    {
        $slashIdx = strpos($this->url, PMF_LINK_SLASH);
        if (false === $slashIdx) {
            return false;
        }

        return ($slashIdx == 0);
    }

    function isSystemLink()
    {
        // a. Is the url relative, starting with '/'?
        // b. Is the url related to the current running PMF system?
        if ($this->isRelativeSystemLink()) {
            return true;
        }
        // $_SERVER['HTTP_HOST'] is the name of the website or virtual host name
        return !(false === strpos($this->url, $_SERVER['HTTP_HOST']));
    }

    function hasModRewriteSupport()
    {
        $faqconfig = PMF_Configuration::getInstance();
        return $faqconfig->get('main.enableRewriteRules');
    }

    function hasScheme()
    {
        $parsed = parse_url($this->url);

        return (!empty($parsed['scheme']));
    }

    function getSEOItemTitle()
    {
        $itemTitle = trim($this->itemTitle);
        // Lower the case (aesthetic)
        $itemTitle = strtolower($itemTitle);
        // Use '_' for some other characters for:
        // 1. avoiding regexp match break;
        // 2. improving the reading.
        $itemTitle = str_replace(array('-', "'", '/'),
                                 '_', $itemTitle);
        // 1. Remove any CR LF sequence
        // 2. Use a '-' for the words separation
        $itemTitle = preg_replace('/\s/m', '-', $itemTitle);
        // Hack: remove some chars for having a better readable title
        $itemTitle = str_replace(array('+', ',', ';', ':', '.', '?', '!', '"', '(', ')', '[', ']', '{', '}', '<', '>'),
                                 '',
                                 $itemTitle);
        // Hack: move some chars to "similar" but plain ASCII chars
        $itemTitle = str_replace(array('à', 'è', 'é', 'ì', 'ò', 'ù', 'ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'),
                                 array('a', 'e', 'e', 'i', 'o', 'u', 'ae', 'oe', 'ue', 'ss', 'Ae', 'Oe', 'Ue'),
                                 $itemTitle);
        // Clean up
        $itemTitle = preg_replace('/-[\-]+/m', '-', $itemTitle);

        return rawurlencode($itemTitle);
    }

    function getHttpGetParameters()
    {
        $query = $this->getQuery();
        $parameters  = array();

        if (!empty($query))
        {
            $params = explode(PMF_LINK_AMPERSAND, $query);
            foreach ($params as $param)
            {
                if (!empty($param))
                {
                    $couple = explode(PMF_LINK_EQUAL, $param);
                    list($key, $val) = $couple;
                    $parameters[$key] = urldecode($val);
                }
            }
        }

        return $parameters;
    }

    function getPage()
    {
        $page = '';
        if (!empty($this->url)) {
            $parsed = parse_url($this->url);
            // Take the last element
            $page = substr(strrchr($parsed['path'], PMF_LINK_SLASH), 1);
        }

        return $page;
    }

    function getQuery()
    {
        $query = '';
        if (!empty($this->url)) {
            $parsed = parse_url($this->url);
            if (isset($parsed['query'])) {
                $query = $parsed['query'];
            }
        }

        return $query;
    }

    function getDefaultScheme()
    {
        $scheme = 'http://';
        if ($this->isSystemLink()) {
            $scheme = PMF_Link::getSystemScheme();
        }

        return $scheme;
    }

    public static function getSystemScheme()
    {
        $scheme = 'http'.(    ((!PMF_Link::isIISServer()) && isset($_SERVER['HTTPS']))
                           || ((PMF_Link::isIISServer()) && ('on' == strtolower($_SERVER['HTTPS']))) ? 's' : '').'://';

        return $scheme;
    }

    public static function getSystemRelativeUri($path = null)
    {
        if (isset($path)) {
            return str_replace($path, '', $_SERVER['PHP_SELF']);
        }

        return str_replace('/inc/Link.php', '', $_SERVER['PHP_SELF']);
    }

    public static function getSystemUri($path = null)
    {
        // $_SERVER['HTTP_HOST'] is the name of the website or virtual host name (HTTP/1.1)
        // Precisely, it contains what the user has written in the Host request-header, see below.
        // RFC 2616: The Host request-header field specifies the Internet host and port number of the resource
        //           being requested, as obtained from the original URI given by the user or referring resource

        // Remove any ref to standard ports 80 and 443.
        $pattern[0] = '/:80$/';   // HTTP: port 80
        $pattern[1] = '/:443$/'; // HTTPS: port 443
        $sysUri = PMF_Link::getSystemScheme().preg_replace($pattern, '', $_SERVER['HTTP_HOST']);

        return $sysUri.PMF_link::getSystemRelativeUri($path);
    }

    function toHtmlAnchor()
    {
        // Sanitize the provided url
        $url = $this->toString();
        // Prepare HTML anchor element
        $htmlAnchor = '<a';
        if (!empty($this->class)) {
            $htmlAnchor .= ' class="'.$this->class.'"';
        }
        if (!empty($this->tooltip)) {
            $htmlAnchor .= ' title="'.addslashes($this->tooltip).'"';
        }
        if (!empty($this->name)) {
                $htmlAnchor .= ' name="'.$this->name.'"';
        } else {
            if (!empty($this->url)) {
                $htmlAnchor .= ' href="'.$url.'"';
            }
            if (!empty($this->target)) {
                $htmlAnchor .= ' target="'.$this->target.'"';
            }
        }
        $htmlAnchor .= '>';
        if (
               ('0' == $this->text) // Possible when used w/ Sitemap letter = 0
            || (!empty($this->text))
            ) {
            $htmlAnchor .= $this->text;
        } else {
            if (!empty($this->name)) {
                $htmlAnchor .= $this->name;
            } else {
                $htmlAnchor .= $url;
            }
        }
        $htmlAnchor .= '</a>';

        return $htmlAnchor;
    }

    function appendSids($url, $sids)
    {
        $separator = (false === strpos($url, PMF_LINK_SEARCHPART_SEPARATOR)) ? PMF_LINK_SEARCHPART_SEPARATOR : PMF_LINK_AMPERSAND ;
        return $url.$separator.PMF_LINK_GET_SIDS.'='.$sids;
    }

    function toString($forceNoModrewriteSupport = false)
    {
        $url = $this->toUri();
        // Check mod_rewrite support and 'rewrite' the passed (system) uri
        // according to the rewrite rules written in .htaccess
        if ((!$forceNoModrewriteSupport) && ($this->hasModRewriteSupport())) {
            if ($this->isHomeIndex()) {
                $getParams = $this->getHttpGetParameters();
                if (isset($getParams[PMF_LINK_GET_ACTION])) {
                    // Get the part of the url 'till the '/' just before the pattern
                    $url = substr($url, 0, strpos($url, PMF_LINK_INDEX_HOME) + 1);
                    // Build the Url according to .htaccess rules
                    switch($getParams[PMF_LINK_GET_ACTION]) {
                        case PMF_LINK_GET_ACTION_ADD:
                            $url .= PMF_LINK_HTML_ADDCONTENT;
                            break;
                        case PMF_LINK_GET_ACTION_ARTIKEL:
                            $url .= PMF_LINK_CONTENT.$getParams[PMF_LINK_GET_CATEGORY].PMF_LINK_HTML_SLASH.$getParams[PMF_LINK_GET_ID].PMF_LINK_HTML_SLASH.$getParams[PMF_LINK_GET_ARTLANG].PMF_LINK_SLASH.$this->getSEOItemTitle().PMF_LINK_HTML_EXTENSION;
                            if (isset($getParams[PMF_LINK_GET_HIGHLIGHT])) {
                                $url .= PMF_LINK_SEARCHPART_SEPARATOR.PMF_LINK_GET_HIGHLIGHT.'='.$getParams[PMF_LINK_GET_HIGHLIGHT];
                            }
                            break;
                        case PMF_LINK_GET_ACTION_ASK:
                            $url .= PMF_LINK_HTML_ASK;
                            break;
                        case PMF_LINK_GET_ACTION_CONTACT:
                            $url .= PMF_LINK_HTML_CONTACT;
                            break;
                        case PMF_LINK_GET_ACTION_HELP:
                            $url .= PMF_LINK_HTML_HELP;
                            break;
                        case PMF_LINK_GET_ACTION_OPEN:
                            $url .= PMF_LINK_HTML_OPEN;
                            break;
                        case PMF_LINK_GET_ACTION_SEARCH:
                            if (!isset($getParams[PMF_LINK_GET_ACTION_SEARCH]) && isset($getParams[PMF_LINK_GET_TAGGING_ID])) {
                                $url .= PMF_LINK_TAGS.$getParams[PMF_LINK_GET_TAGGING_ID];
                                if (isset($getParams[PMF_LINK_GET_PAGE])) {
                                    $url .= PMF_LINK_HTML_SLASH.$getParams[PMF_LINK_GET_PAGE];
                                }
                                $url .= PMF_LINK_SLASH.$this->getSEOItemTitle().PMF_LINK_HTML_EXTENSION; 
                            } elseif (isset($getParams[PMF_LINK_GET_ACTION_SEARCH])) {
                                $url .= PMF_LINK_HTML_SEARCH;
                                $url .= PMF_LINK_SEARCHPART_SEPARATOR.PMF_LINK_GET_ACTION_SEARCH.'='.$getParams[PMF_LINK_GET_ACTION_SEARCH];
                                if (isset($getParams[PMF_LINK_GET_PAGE])) {
                                    $url .= PMF_LINK_AMPERSAND.PMF_LINK_GET_PAGE.'='.$getParams[PMF_LINK_GET_PAGE];
                                }
                            }
                            if (isset($getParams[PMF_LINK_GET_LANGS])) {
                                $url .= PMF_LINK_AMPERSAND.PMF_LINK_GET_LANGS.'='.$getParams[PMF_LINK_GET_LANGS];
                            }
                            break;
                        case PMF_LINK_GET_ACTION_SITEMAP:
                            if (isset($getParams[PMF_LINK_GET_LETTER])) {
                                $url .= PMF_LINK_SITEMAP.$getParams[PMF_LINK_GET_LETTER].PMF_LINK_HTML_SLASH.$getParams[PMF_LINK_GET_LANG].PMF_LINK_HTML_EXTENSION;
                            } else {
                                $url .= PMF_LINK_SITEMAP.'A'.PMF_LINK_HTML_SLASH.$getParams[PMF_LINK_GET_LANG].PMF_LINK_HTML_EXTENSION;
                            }
                            break;
                        case PMF_LINK_GET_ACTION_SHOW:
                            if (    !isset($getParams[PMF_LINK_GET_CATEGORY])
                                 || (isset($getParams[PMF_LINK_GET_CATEGORY]) && (0 == $getParams[PMF_LINK_GET_CATEGORY]))
                                ) {
                                $url .= PMF_LINK_HTML_SHOWCAT;
                            }
                            else {
                                $url .= PMF_LINK_CATEGORY.$getParams[PMF_LINK_GET_CATEGORY];
                                if (isset($getParams[PMF_LINK_GET_PAGE])) {
                                    $url .= PMF_LINK_HTML_SLASH.$getParams[PMF_LINK_GET_PAGE];
                                }
                                $url .= PMF_LINK_HTML_SLASH.$this->getSEOItemTitle().PMF_LINK_HTML_EXTENSION;
                            }
                            break;
                        case PMF_LINK_GET_ACTION_NEWS:
                            $url .= PMF_LINK_NEWS.$getParams[PMF_LINK_GET_NEWS_ID].PMF_LINK_HTML_SLASH.$getParams[PMF_LINK_GET_NEWS_LANG].PMF_LINK_SLASH.$this->getSEOItemTitle().PMF_LINK_HTML_EXTENSION;
                            break;
                        default:
                            break;
                    }
                    if (isset($getParams[PMF_LINK_GET_SIDS])) {
                        $url = $this->appendSids($url, $getParams[PMF_LINK_GET_SIDS]);
                    }
                }
            }
        }

        return $url;
    }

    function toUri()
    {
        $url = $this->url;
        if (!empty($url)) {
            if ((!$this->hasScheme()) && (!$this->isInternalReference())) {
                // Manage an URI without a Scheme BUT NOT those that are 'internal' references
                $url = $this->getDefaultScheme().$this->url;
            }
        }

        return $url;
    }
}