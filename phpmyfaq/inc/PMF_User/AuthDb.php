<?php

error_reporting(E_ALL);

/**
 * manages user authentication with PostgreSQL databases.
 *
 * @author Lars Tiedemann, <php@larstiedemann.de>
 * @package PMF
 */

if (0 > version_compare(PHP_VERSION, '4')) {
    die('This file was generated for PHP 4');
}

/**
 * manages user authentication. 
 *
 * Subclasses of Auth implement authentication functionality with different
 * types. The class AuthLdap for expamle provides authentication functionality
 * LDAP-database access, AuthMysql with MySQL-database access.
 *
 * Authentication functionality includes creation of a new login-and-password
 * deletion of an existing login-and-password combination and validation of
 * given by a user. These functions are provided by the database-specific
 * see documentation of the database-specific authentication classes AuthMysql,
 * or AuthLdap for further details.
 *
 * Passwords are usually encrypted before stored in a database. For
 * and security, a password encryption method may be chosen. See documentation
 * Enc class for further details.
 *
 * Instead of calling the database-specific subclasses directly, the static
 * selectDb(dbtype) may be called which returns a valid database-specific
 * object. See documentation of the static method selectDb for further details.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @since 2005-09-30
 * @version 0.1
 */
require_once('PMF/Auth.php');

/* user defined includes */
// section -64--88-1-10-36edbf7a:106a832a030:-7fdb-includes begin
// section -64--88-1-10-36edbf7a:106a832a030:-7fdb-includes end

/* user defined constants */
// section -64--88-1-10-36edbf7a:106a832a030:-7fdb-constants begin
// section -64--88-1-10-36edbf7a:106a832a030:-7fdb-constants end

/**
 * manages user authentication with PostgreSQL databases.
 *
 * @access public
 * @author Lars Tiedemann, <php@larstiedemann.de>
 * @package PMF
 */
class PMF_AuthDb
    extends PMF_Auth
{
    // --- ATTRIBUTES ---

    /**
     * Short description of attribute db
     *
     * @access private
     * @var mixed
     */
    var $_db = null;

    /**
     * Short description of attribute tablename
     *
     * @access private
     * @var string
     */
    var $_tablename = '';

    /**
     * Short description of attribute login_column
     *
     * @access private
     * @var string
     */
    var $_login_column = '';

    /**
     * Short description of attribute password_column
     *
     * @access private
     * @var string
     */
    var $_password_column = '';

    // --- OPERATIONS ---

    /**
     * Short description of method PMF_AuthDb
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function PMF_AuthDb()
    {
        // section -64--88-1-10-36edbf7a:106a832a030:-7fd8 begin
        // section -64--88-1-10-36edbf7a:106a832a030:-7fd8 end
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
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fdd begin
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fdd end
    }

    /**
     * Short description of method add
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param string
     * @return void
     */
    function add($login, $pass)
    {
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fdb begin
        if (!$this->_db()) 
        	return false;
        // check if $login already exists
        if ($this->checkLogin($login) > 0) {
        	$this->errors[] = PMF_USERERROR_ADD . PMF_USERERROR_LOGIN_NOT_UNIQUE;
        	return false;
        }
		// add user account to authentication table        
        $add = "
		  INSERT INTO 
		    ".$this->_tablename()."
		  SET
		    ".$this->_login_column()   ." = '".$login."',
		    ".$this->_password_column()." = '".$this->_enc_container->encrypt($pass)."' 
		";
        $add = $this->_db->query($add);
        $error = $this->_db->error();
		if (strlen($error) > 0) {
		    $this->errors[] = PMF_USERERROR_ADD . 'error(): ' . $error;
			return false;
		} 
		if (!$add) {
			$this->errors[] = PMF_USERERROR_ADD;
			return false;
		}
		return true;
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fdb end
    }

    /**
     * Short description of method changePassword
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param string
     * @return void
     */
    function changePassword($login, $pass)
    {
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fd9 begin
        if (!$this->_db()) 
        	return false;
        $change = "
          UPDATE
            ".$this->_tablename()."
          SET
            ".$this->_password_column()." = '".$this->_enc_container->encrypt($pass)."'
          WHERE
            ".$this->_login_column()." = '".$login."'
        ";
        $change = $this->_db->query($change);
        $error = $this->_db->error();
		if (strlen($error) > 0) {
		    $this->errors[] =  PMF_USERERROR_CHANGE . 'error(): ' . $error;
			return false;
		} 
		if (!$change) {
			$this->errors[] =  PMF_USERERROR_CHANGE;
			return false;
		}
		return true;
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fd9 end
    }

    /**
     * Short description of method delete
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return void
     */
    function delete($login)
    {
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fd7 begin
        if (!$this->_db()) 
        	return false;
        $delete = "
          DELETE FROM
            ".$this->_tablename()."
          WHERE
            ".$this->_login_column()." = '".$login."'
        ";
        $delete = $this->_db->query($delete);
        $error = $this->_db->error();
		if (strlen($error) > 0) {
		    $this->errors[] = PMF_USERERROR_DELETE . 'error(): ' . $error;
			return false;
		} 
		if (!$delete) {
			$this->errors[] = PMF_USERERROR_DELETE;
			return false;
		}
		return true;
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fd7 end
    }

    /**
     * Short description of method checkPassword
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @param string
     * @return void
     */
    function checkPassword($login, $pass)
    {
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fd5 begin
        if (!$this->_db()) 
        	return false;
        $check = "
          SELECT 
            ".$this->_login_column().",
            ".$this->_password_column()."
          FROM
            ".$this->_tablename()."
          WHERE
            ".$this->_login_column." = '".$login."' 
        ";
        $check = $this->_db->query($check);
        $error = $this->_db->error();
		if (strlen($error) > 0) {
		    $this->errors[] = PMF_USER_NOT_FOUND . 'error(): ' . $error;
			return false;
		} 
        $num_rows = $this->_db->num_rows($check);
		if ($num_rows < 1) {
			$this->errors[] = PMF_USER_NOT_FOUND;
		    return false;
		}
		// if login not unique, raise an error, but continue
		if ($num_rows > 1) {
			$this->errors[] = PMF_USERERROR_LOGIN_NOT_UNIQUE;
		}
		// if multiple accounts are ok, just 1 valid required
		while ($user = $this->_db->fetch_assoc($check)) {
		    if ($user[$this->_password_column] == $this->_enc_container->encrypt($pass)) {
			    return true;
			    break;
		    }
		}
		$this->errors[] = PMF_USERERROR_INCORRECT_PASSWORD;
		return false;
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fd5 end
    }

    /**
     * establishes a connection to a MySQL server using the given parameters. 
     *
     * Selects the database and tries to connect to the specified MySQL server.
     * the MySQL link resource on success, otherwise false.
     *
     * Error messages are added to the array errors.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @param string
     * @param string
     * @param string
     * @return void
     */
    function connect($db, $table = '', $login_column = '', $password_column = '')
    {
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fd3 begin
        $this->_db($db);
        $this->_tablename($table);
        $this->_login_column($login_column);
        $this->_password_column($password_column);
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fd3 end
    }

    /**
     * Short description of method checkLogin
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return void
     */
    function checkLogin($login)
    {
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fd1 begin
        if (!$this->_db()) 
        	return false;
        $check = "
          SELECT 
            ".$this->_login_column."
          FROM
            ".$this->_tablename()."
          WHERE
            ".$this->_login_column()." = '".$login."'
        ";
        $check = $this->_db->query($check);
        $error = $this->_db->error();
		if (strlen($error) > 0) {
		    $this->errors[] = $error;
			return (int) 0;
		} 
        return $this->_db->num_rows($check);
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fd1 end
    }

    /**
     * Short description of method tablename
     *
     * @access private
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return string
     */
    function _tablename($table = '')
    {
        $returnValue = (string) '';

        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fc8 begin
        if ($table != '') { 
        	$old_table = $this->_tablename;
		    $this->_tablename = $table;
		    return $old_table;
        }
        if (!$this->_tablename) {
        	$this->errors[] = PMF_UNDEFINED_PARAMETER."PMF_AuthMysql->_tablename";
        	$this->_tablename = '';
        }
        return (string) $this->_tablename;
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fc8 end

        return (string) $returnValue;
    }

    /**
     * Short description of method login_column
     *
     * @access private
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return string
     */
    function _login_column($login_column = '')
    {
        $returnValue = (string) '';

        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fc4 begin
        if ($login_column != '') { 
        	$old_login_column = $this->_login_column;
		    $this->_login_column = $login_column;
		    return $old_login_column;
        }
        if (!$this->_login_column) {
        	$this->errors[] = PMF_UNDEFINED_PARAMETER."PMF_AuthMysql->_login_column";
        	$this->_login_column = '';
        }
        return (string) $this->_login_column;
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fc4 end

        return (string) $returnValue;
    }

    /**
     * Short description of method password_column
     *
     * @access private
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return string
     */
    function _password_column($password_column = '')
    {
        $returnValue = (string) '';

        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fc1 begin
        if ($password_column != '') { 
        	$old_password_column = $this->_password_column;
		    $this->_password_column = $password_column;
		    return $old_password_column;
        }
        if (!$this->_password_column) {
        	$this->errors[] = PMF_UNDEFINED_PARAMETER."PMF_AuthMysql->_password_column";
        	$this->_password_column = '';
        }
        return (string) $this->_password_column;
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fc1 end

        return (string) $returnValue;
    }

    /**
     * Short description of method db
     *
     * @access private
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @return object
     */
    function _db($db = null)
    {
        $returnValue = null;

        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fba begin
        if ($db !== null) {
        	$old_db = $this->_db;
        	if (!PMF_User::checkDb($db)) {
        		$this->errors[] = PMF_USERERROR_NO_DB;
			    return false;
        	}
			$this->_db = $db;
        	return $old_db;
        }
        if (!PMF_User::checkDb($this->_db)) {
        	$this->_db = null;
        	$this->errors[] = PMF_USERERROR_NO_DB;
        	return false;
        }
        return $this->_db;
        // section -64--88-1-10--64a6e3e6:106b6331e8d:-7fba end

        return $returnValue;
    }

} /* end of class PMF_AuthDb */

?>