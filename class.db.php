<?php


class db {
    
    /**
     * The DSN Prefix
     *
     * @access private
     * @var constant
     */
    private $dsnPrefix = DSN_PREFIX;
    
    /**
     * The database host
     *
     * @access private
     * @var constant
     */
    private $dbhost = DB_HOST;
    
    /**
     * The database name
     *
     * @access private
     * @var constant
     */
    private $dbname = DB_NAME;
    
    /**
     * The database username
     *
     * @access private
     * @var constant
     */
    private $dbuser = DB_USER;
    
    /**
     * The database password
     *
     * @access private
     * @var constant
     */
    private $dbpass = DB_PASS;
    
    /**
     * The sql query statement
     *
     * @access private
     * @var string
     */
    private $sql;
    
    /**
     * Binds a value to a parameter
     *
     * @access private
     * @var array
     */
    private $bind;
    
    /**
     * The db database object
     *
     * @access private
     * @var object
     */
    private $db;
    
    /**
     * Create instance of the db class
     *
     * @access private
     * @var object
     */
    private static $instance = NULL;
    
    /**
     * Current result set
     *
     * @access private
     * @var object
     */
    private $result;
 
    /**
     * Error Message
     *
     * @access private
     * @var string
     */
    private $error;
    
    public function __construct() {
        try {
            $this->db = new \PDO("$this->dsnPrefix:host=$this->dbhost;dbname=$this->dbname", $this->dbuser, $this->dbpass);
            $this->db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->db->query('SET NAMES utf8');
            $this->db->query('SET CHARACTER SET utf8'); 
        } catch(\PDOException $e) {
            $this->error = 'ERROR: ' . $e->getMessage();
            return $this->error;
        }
    }
    
    /**
     * Creates and references the db object.
     *
     * @access public
     * @return object
     */
    public static function inst() {
        if ( !self::$instance )
            self::$instance = new db();
        return self::$instance;
    }
    
    /**
     * Close active connection to dataase.
     *
     * @access public
     * @return bool Always returns true.
     */
    public function close() {
        if ( $this->db )
            $this->db = null;
        return true;
    }
    
    private function cleanup($bind) {
        if(!is_array($bind)) {
            if(!empty($bind))
                $bind = array($bind);
            else
                $bind = array();
        }
        return $bind;
    }
    
    /**
     * Is used by all SQL query methods, but can also be used 
     * for advanced queries.
     *
     * @access public
     * @param $sql (required) The SQL statement to execute.
     * @param $bind (optional) values & parameters key/value array
     * @return mixed
     */
    public function init($sql, $bind='') {
        $this->sql = trim($sql);
        $this->bind = $this->cleanup($bind);
        $this->error = '';

        try {
            $stmt = $this->db->prepare($this->sql);
            if($stmt->execute($this->bind) !== false) {
                if(preg_match("/^(" . implode("|", array("select", "describe", "pragma")) . ") /i", $this->sql))
                    return $stmt->fetchAll(PDO::FETCH_ASSOC);
                elseif(preg_match("/^(" . implode("|", array("delete", "insert", "update")) . ") /i", $this->sql))
                    return $stmt->rowCount();
            }   
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return $this->error;
            return false;
        }
    }
    
    /**
     * Executes query and returns results.
     *
     * @access public
     * @param $sql (required) The SQL statement to execute.
     * @param $bind (optional) values & parameters key/value array
     * @return mixed
     */
    public function query($sql, $bind = false) {
        $this->error = '';
        
        try {
            if($bind !== false) {
                return $this->init($sql, $bind);
            } else {
                $this->result = $this->db->query($sql);
                return $this->result;
            }
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return $this->error;
        }
    }
    
    /**
     * Selects information from the database.
     * 
     * @param $table (required) the name of the table
     * @param $fields (optional) the columns requested, separated by commas
     * @param $where (optional) column = value as a string
     * @param $order (optional) column DIRECTION as a string
     * @param $bind (optional) values & parameters key/value array
     * @return mixed
     */
    public function select($table, $fields = '*', $where = null, $order = null, $bind = '') {
        try {
            $q = "SELECT ".$fields." FROM ".$table;
            if($where != null)
                $q .= " WHERE ".$where;
            if($order != null)
                $q .= " ORDER BY ".$order;
    
            return $this->init($q, $bind);
            
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return $this->error;
        }
    }
    
    /**
     * Insert values into the table
     * 
     * @access public
     * @param $table (required) the name of the table
     * @param $values (required) the values to be inserted
     * @param $fields (optional) if values don't match the number of fields
     * @param $bind (optional) values & parameters key/value array
     * @return mixed
     */
    public function insert($table,$values,$fields = null,$bind = '') {
        try {
            $insert = 'INSERT INTO '.$table;
                if($fields != null) {
                    $insert .= ' ('.$fields.')';
                }

                for($i = 0; $i < count($values); $i++) {
                    if(is_string($values[$i]))
                        $values[$i] = '"'.$values[$i].'"';
                }
            $values = implode(',',$values);
            $insert .= ' VALUES ('.$values.')';

            $ins = $this->init($insert, $bind);

                if($ins) {
                    return true;
                }
                
            } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return $this->error;
        }
    }
    
    /**
     * Updates the database with the values sent
     * 
     * @access public
     * @param $table (required) the name of the table to be updated
     * @param $fields (required) the rows/values in a key/value array
     * @param $where (required) the row/condition in an array (row,condition)
     * @param $bind (optional) values & parameters key/value array
     * @return mixed
     */
     /* Alternative update method just in case. */
    public function update($table,$fields,$where,$bind='') {
        try{
            $q = "UPDATE ".$table." SET ";
            $keys = array_keys($fields);
            for($i = 0; $i < count($fields); $i++) {
                $fields = str_replace('"',"", $fields);
                $q .= $keys[$i].'='.$fields[$keys[$i]];

                // Parse to add commas
                if($i != count($fields)-1) {
                    $q .= ',';
                }
            }
            $q .= " WHERE ". $where;
            
            $upd = $this->init($q, $bind);
            
            var_dump($q);
            
            if($upd) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return $this->error;
        }
    }
    
    public function update($table,$fields,$where,$bind = '') {
        try {
            for($i = 0; $i < count($where); $i++) {
                if($i%2 != 0) {
                    if(is_string($where[$i])) {
                        if($where[($i+1)] != null)
                            $where[$i] = "='".$where[$i]."' AND ";
                        else
                            $where[$i] = "='".$where[$i]."'";
                    }
                }
            }
            $where = implode('',$where);

            $update = 'UPDATE '.$table.' SET ';
            $keys = array_keys($fields);
            for($i = 0; $i < count($fields); $i++) {
                $fields = str_replace('"',"", $fields);
                if(is_string($fields[$keys[$i]])) {
                    $update .= $keys[$i].'='.$fields[$keys[$i]];
                } else {
                    $update .= $keys[$i].'='.$fields[$keys[$i]];
                }

                // Parse to add commas
                if($i != count($fields)-1) {
                    $update .= ',';
                }
            }
            $update .= ' WHERE '.$where;
            $query = $this->init($update, $bind);
            if($query) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return $this->error;
        }
    }

    /**
     * Deletes table or records where condition is true
     * 
     * @access public
     * @param $table (required) the name of the table
     * @param $where (required) condition [column =  value]
     * @param $bind (optional) values & parameters key/value array
     * @return mixed
     */
    public function delete($table,$where,$bind = '') {
        try {
            $q = "DELETE FROM " . $table . " WHERE " . $where;
            
            return $this->init($q, $bind);
            
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return $this->error;
        }
    }
    
    /**
     * Prevents the cloning of the db object.
     *
     * @access private
     * @return void
     */
    private function __clone() {}
    
}