<?php

error_reporting(E_ALL);

/**
 * The userdata class provides methods to manage user information.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */

if (0 > version_compare(PHP_VERSION, '4')) {
    die('This file was generated for PHP 4');
}

/**
 * Creates a new user object.
 *
 * A user are recognized by the session-id using getUserBySessionId(), by his
 * using getUserById() or by his nickname (login) using getUserByLogin(). New
 * are created using createNewUser().
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-17
 * @version 0.1
 */
//require_once('PMF/User.php');

/* user defined includes */
// section -64--88-1-10-1860038:10612dd0903:-7fde-includes begin
require_once dirname(__FILE__).'/User.php';
// section -64--88-1-10-1860038:10612dd0903:-7fde-includes end

/* user defined constants */
// section -64--88-1-10-1860038:10612dd0903:-7fde-constants begin
// section -64--88-1-10-1860038:10612dd0903:-7fde-constants end

/**
 * The userdata class provides methods to manage user information.
 *
 * @access public
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */
class PMF_UserData
{
    // --- ATTRIBUTES ---

    /**
     * Short description of attribute db
     *
     * @access private
     * @var object
     */
    var $_db = null;

    /**
     * Short description of attribute data
     *
     * @access private
     * @var array
     */
    var $_data = array();

    /**
     * Short description of attribute user_id
     *
     * @access private
     * @var int
     */
    var $_user_id = 0;

    // --- OPERATIONS ---

    /**
     * Short description of method get
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param mixed
     * @return mixed
     */
    function get($field)
    {
        $returnValue = null;

        // section -64--88-1-5-15e2075:1064c3e1ce5:-7ff3 begin
        // check $field
        $single_return = false;
        if (!is_array($field)) {
        	$single_return = true;
        	$fields = $field;
        }
        else {
        	$fields = implode(', ', $field);
        }
        // get data
		$res = $this->_db->query("
		  SELECT
		    ".$fields."
		  FROM
		    ".PMF_USER_SQLPREFIX."userdata
		  WHERE 
		    user_id = ".$this->_user_id
        ); 
		if ($this->_db->num_rows($res) != 1) 
		    return false;
		$arr = $this->_db->fetch_assoc($res);
		if ($single_return and $field != '*') 
		    return $arr[$field];
		return $arr;
        // section -64--88-1-5-15e2075:1064c3e1ce5:-7ff3 end

        return $returnValue;
    }

    /**
     * Short description of method set
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param mixed
     * @param mixed
     * @return bool
     */
    function set($field, $value = null)
    {
        $returnValue = (bool) false;

        // section -64--88-1-5-15e2075:1064c3e1ce5:-7fef begin
        // check input
        if (!is_array($field))
            $field = array($field);
        if (!is_array($value))
            $value = array($value);
        if (count($field) != count($value)) 
            return false;
        // update data
        for ($i = 0; $i < count($field); $i++) {
        	$this->_data[$field[$i]] = $value[$i];
        }
        return $this->save();
        // section -64--88-1-5-15e2075:1064c3e1ce5:-7fef end

        return (bool) $returnValue;
    }

    /**
     * Short description of method PMF_UserData
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @return void
     */
    function PMF_UserData($db)
    {
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd3 begin
        $this->_db = $db;
        // section -64--88-1-5--735fceb5:106657b6b8d:-7fd3 end
    }

    /**
     * Short description of method __destruct
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function __destruct()
    {
        // section -64--88-1-10-367e1977:106c6a5795d:-7fdd begin
        // section -64--88-1-10-367e1977:106c6a5795d:-7fdd end
    }

    /**
     * Short description of method load
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function load($user_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--7165c41e:106c72278bc:-7fdd begin
        // check user-ID
        $user_id = (int) $user_id;
        if ($user_id <= 0) 
            return false;
        $this->_user_id = $user_id;
        // load data
        $res = $this->_db->query("
          SELECT 
            last_modified, display_name, email
          FROM
            ".PMF_USER_SQLPREFIX."userdata
          WHERE 
            user_id = ".$this->_user_id
        );
        if ($this->_db->num_rows($res) != 1)
        	return false;
        $this->_data = $this->_db->fetch_assoc($res);
        return true;
        // section -64--88-1-10--7165c41e:106c72278bc:-7fdd end

        return (bool) $returnValue;
    }

    /**
     * Short description of method save
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return bool
     */
    function save()
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--7165c41e:106c72278bc:-7fdb begin
        // update data
        $res = $this->_db->query("
          UPDATE
            ".PMF_USER_SQLPREFIX."userdata
          SET
            last_modified = NOW(),
            display_name = '".$this->_data['display_name']."',
            email        = '".$this->_data['email']."'
          WHERE 
            user_id = ".$this->_user_id
        );
        if (!$res) 
            return false;
        return true;
        // section -64--88-1-10--7165c41e:106c72278bc:-7fdb end

        return (bool) $returnValue;
    }

    /**
     * Short description of method add
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function add($user_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--7165c41e:106c72278bc:-7fd4 begin
        // check user-ID
        $user_id = (int) $user_id;
        if ($user_id <= 0) 
            return false;
        $this->_user_id = $user_id;
        // add entry
        $res = $this->_db->query("
          INSERT INTO
            ".PMF_USER_SQLPREFIX."userdata
          SET
            user_id      = ".$this->_user_id.", 
            last_modified = NOW()
        ");
        if (!$res) 
            return false;
        return true;
        // section -64--88-1-10--7165c41e:106c72278bc:-7fd4 end

        return (bool) $returnValue;
    }

    /**
     * Short description of method delete
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function delete($user_id)
    {
        $returnValue = (bool) false;

        // section -64--88-1-10--7165c41e:106c72278bc:-7fce begin
        // check user-ID
        $user_id = (int) $user_id;
        if ($user_id <= 0) 
            return false;
        $this->_user_id = $user_id;
        // delete entry
        $res = $this->_db->query("
          DELETE FROM
            ".PMF_USER_SQLPREFIX."userdata
          WHERE
            user_id = ".$this->_user_id
        );
        if (!$res) 
            return false;
        $this->_data = array();
        return true;
        // section -64--88-1-10--7165c41e:106c72278bc:-7fce end

        return (bool) $returnValue;
    }

} /* end of class PMF_UserData */

?>
