<?php
/**
 * The main Rating class
 * 
 * PHP Version 5.2
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
 * @package   PMF_Rating
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2007-03-31
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Rating
 * 
 * @category  phpMyFAQ
 * @package   PMF_Rating
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2007-03-31
 */
class PMF_Rating
{
    /**
     * DB handle
     *
     * @var PMF_Db
     */
    private $db;

    /**
     * Language
     *
     * @var PM_Language
     */
    private $language;

    /**
     * Language strings
     *
     * @var  string
     */
    private $pmf_lang;

    /**
     * Plural form support
     *
     * @var  PMF_Language_Plurals
     */
    private $plr;

    /**
     * Constructor
     *
     * @param PMF_DB_Driver $database Database connection
     * @param PMF_Language  $language Language object
     *
     * @return PMF_Rating
     */
    function __construct(PMF_DB_Driver $database, PMF_Language $language)
    {
        global $PMF_LANG, $plr;

        $this->db       = $database;
        $this->language = $language;
        $this->pmf_lang = $PMF_LANG;
        $this->plr      = $plr;
    }

    /**
     * Returns all ratings of FAQ records
     *
     * @return  array
     */
    public function getAllRatings()
    {
        $ratings = array();

        switch($this->db->getType()) {
            case 'mssql':
            // In order to remove this MS SQL 2000/2005 "limit" below:
            //   The text, ntext, and image data types cannot be compared or sorted, except when using IS NULL or LIKE operator.
            // we'll cast faqdata.thema datatype from text to char(2000)
            // Note: the char length is simply an heuristic value
            // Doing so we'll also need to trim $row->thema to remove blank chars when it is shorter than 2000 chars
                $query = sprintf("
                    SELECT
                        fd.id AS id,
                        fd.lang AS lang,
                        fcr.category_id AS category_id,
                        CAST(fd.thema as char(2000)) AS question,
                        (fv.vote / fv.usr) AS num,
                        fv.usr AS usr
                    FROM
                        %sfaqvoting fv,
                        %sfaqdata fd
                    LEFT JOIN
                        %sfaqcategoryrelations fcr
                    ON
                        fd.id = fcr.record_id
                    AND
                        fd.lang = fcr.record_lang
                    WHERE
                        fd.id = fv.artikel
                    GROUP BY
                        fd.id,
                        fd.lang,
                        fd.active,
                        fcr.category_id,
                        CAST(fd.thema as char(2000)),
                        fv.vote,
                        fv.usr
                    ORDER BY
                        fcr.category_id",
                    SQLPREFIX,
                    SQLPREFIX,
                    SQLPREFIX);
                break;

             default:
                $query = sprintf("
                    SELECT
                        fd.id AS id,
                        fd.lang AS lang,
                        fcr.category_id AS category_id,
                        fd.thema AS question,
                        (fv.vote / fv.usr) AS num,
                        fv.usr AS usr
                    FROM
                        %sfaqvoting fv,
                        %sfaqdata fd
                    LEFT JOIN
                        %sfaqcategoryrelations fcr
                    ON
                        fd.id = fcr.record_id
                    AND
                        fd.lang = fcr.record_lang
                    WHERE
                        fd.id = fv.artikel
                    GROUP BY
                        fd.id,
                        fd.lang,
                        fd.active,
                        fcr.category_id,
                        fd.thema,
                        fv.vote,
                        fv.usr
                    ORDER BY
                        fcr.category_id",
                    SQLPREFIX,
                    SQLPREFIX,
                    SQLPREFIX);
                break;
        }

        $result = $this->db->query($query);
        while ($row = $this->db->fetchObject($result)) {
            $ratings[] = array(
               'id'          => $row->id,
               'lang'        => $row->lang,
               'category_id' => $row->category_id,
               'question'    => $row->question,
               'num'         => $row->num,
               'usr'         => $row->usr
            );
        }

        return $ratings;
    }

    /**
     * Calculates the rating of the user votings
     *
     * @param integer $id
     *
     * @return  string
     */
    function getVotingResult($id)
    {
        $query = sprintf(
            'SELECT
                (vote/usr) as voting, usr
            FROM
                %sfaqvoting
            WHERE
                artikel = %d',
            SQLPREFIX,
            $id);
       $result = $this->db->query($query);
       if ($this->db->numRows($result) > 0) {
            $row = $this->db->fetchObject($result);
            return sprintf(' %s ('.$this->plr->GetMsg('plmsgVotes',$row->usr).')',
                round($row->voting, 2));
       } else {
            return '0 ('.$this->plr->GetMsg('plmsgVotes',0).')';
       }
    }
}
