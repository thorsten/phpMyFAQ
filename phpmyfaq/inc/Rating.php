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
 * @copyright 2007-2010 phpMyFAQ Team
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
 * @copyright 2007-2010 phpMyFAQ Team
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
     * @var string
     */
    private $language;

    /**
     * Database type
     *
     * @var string
     */
    private $type;

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
     * Voting Data
     * 
     * @var array
     */
    public $votingData = null;
    
    /**
     * Constructor
     *
     * @since   2007-03-31
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function __construct()
    {
        global $DB, $PMF_LANG, $plr;

        $this->db       = PMF_Db::getInstance();
        $this->language = PMF_Language::$language;
        $this->type     = $DB['type'];
        $this->pmf_lang = $PMF_LANG;
        $this->plr      = $plr;
    }
    

    /**
     * Creates a new entry
     *
     * @param array   $data Array of data
     * 
     * @return boolean
     * @throws PMF_Exception
     */
    public function create(Array $data)
    {
        if (is_null($data['id'])) {
            $votingId = $this->db->nextID(SQLPREFIX.'faqvoting', 'id');
        }
        
        $query = sprintf("
            INSERT INTO
                %sfaqvoting
            VALUES
                (%d, %d, %d, 1, %d, '%s')",
            SQLPREFIX,
            $votingId,
            $data['record_id'],
            $data['vote'],
            $data['date'],
            $data['user_ip']);
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        }
        
        return $result;
    }
    

    /**
     * Updates an existing entry
     *
     * @param integer $id   ID
     * @param array   $data Array of data
     * 
     * @return boolean
     * @throws PMF_Exception
     */
    public function update($recordId, Array $data)
    {
        $query = sprintf("
            UPDATE
                %sfaqvoting
            SET
                vote    = vote + %d,
                usr     = usr + 1,
                datum   = %d,
                ip      = '%s'
            WHERE
                artikel = %d",
            SQLPREFIX,
            $data['vote'],
            $data['date'],
            $data['user_ip'],
            $recordId);
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        }
        
        return $result;
    }
    

    /**
     * Fetches one entry
     *
     * @param integer $recordId Record ID
     * 
     * @return array
     * @throws PMF_Exception
     */
    public function fetch($recordId)
    {
        $query = sprintf("
            SELECT
                id,
                artikel as record_id,
                vote as sumVotings,
                usr as numVotings,
                datum as date,
                ip
            FROM
                %sfaqvoting
            WHERE
                artikel = %d",
            SQLPREFIX,
            (int)$recordId);
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        } else {
            $this->votingData = array_shift($this->db->fetchAll($result));
        }
        
        return $this->votingData;
    }
    
    /**
     * Fetches all entries, if parameter = null, otherwise all from the given
     * array like array(1, 2, 3)
     *
     * @param array $ids Array of IDs
     * 
     * @return array
     * @throws PMF_Exception
     */
    public function fetchAll(Array $ids = null)
    {
        $ratings = array();
        $query   = sprintf("
            SELECT
                id,
                artikel as record_id,
                vote as sumVotings,
                usr as numVotings,
                datum as date,
                ip
            FROM
                %sfaqvoting
            WHERE
                1=1",
            SQLPREFIX);
        
        if (!is_null($ids)) {
            $query .= sprintf("
            AND 
                id IN (%s)",
            implode(', ', $ids));
        }
        
        $result = $this->db->query($query);
        
        if (!$result) {
            throw new PMF_Exception($this->db->error());
        } else {
            $ratings = $this->db->fetchAll($result);
        }
        
        return $ratings;
    }
    
    
    
    
    /**
     * Returns all ratings of FAQ records
     *
     * @return  array
     * @access  public
     * @since   2007-03-31
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    public function getAllRatings()
    {
        $ratings = array();

        switch($this->type) {
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
               'usr'         => $row->usr);
        }

        return $ratings;
    }
}
