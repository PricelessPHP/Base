<?php
/**
 * Session Handling
 * MySQL
 *
 * @author      BizLogic <hire@bizlogicdev.com>
 * @copyright   2016 BizLogic
 * @link        http://bizlogicdev.com
 * @link        http://pricelessphp.com
 * @license     GNU Affero General Public License v3
 *
 * @since       Monday, December 19, 2016, 04:20 AM GMT+1 mknox
 * @edited      $Date$ $Author$
 * @version     $Id$
 */

class Session_Db_Mysql extends Db
{
    public function __construct()
    {
        $this->tableName = DB_TABLE_PREFIX . 'user_session';
        parent::__construct( $this->tableName );
    }
    
    /**
     * Ensure that the DB connection 
     * exists
     * 
     * @return  boolean
    */
    public function _open() 
    {
        if( $this->db ) {
            return true;
        }

        return false;
    }
    
    /**
     * Close the DB connection
     * 
     * @return  boolean
    */
    public function _close() 
    {
        // cleanup
        $this->_gc( SITE_SESSION_TIMEOUT + 600 );
        return true;
    }    
    
    /**
     * Read a session from 
     * the DB
     * 
     * @param   string  $sessionId
     * @param   string
    */
    public function _read( $sessionId ) 
    {
        $data = $this->getBy(
            array(
                array(
                    'column'    => 'session_id',
                    'value'     => $sessionId,
                    'operator'  => '='
                )
            )
        );
        
        if( !empty( $data ) ) {
            return $data['data'];
        }

        return '';
    }  
    
    /**
     * Write a session to the DB
     * 
     * @param   string  $sessionId
     * @param   string  $data
     * @return  boolean
    */
    public function _write( $sessionId, $data = '' ) 
    {
        $existingData = $this->getBy(
            array(
                array(
                    'column'    => 'session_id',
                    'value'     => $sessionId,
                    'operator'  => '='
                )
            )
        );
        
        if( !empty( $existingData ) ) {
            $result = $this->updateById( 
                $existingData['id'], 
                array(
                    'user_id'           => userId(),
                    'data'              => $data,
                    'ip'                => getIp(),
                    'date_expiration'   => time() + SITE_SESSION_TIMEOUT,
                    'date_last_update'  => time(),
                    'user_agent'        => $_SERVER['HTTP_USER_AGENT']
                ) 
            );
        } else {
            $result = $this->insert( $sessionId, $data );            
        }
    
        if( $result > 0 ) {
            return true;
        }
         
        return false;
    }    
    
    /**
     * Destroy a session
     * 
     * @param   string  $sessionId
     * @return  boolean
    */
    public function _destroy( $sessionId ) 
    {
        $result = $this->deleteBySessionId( $sessionId );

        if( $result == 0 ) {
            return true;
        }

        return false;
    }    
    
    /**
     * Garbage Collection
     * 
     * @param   int $maxLifetime
     * @return  int
    */
    public function _gc( $maxLifetime = SITE_SESSION_TIMEOUT )
    {
        // Calculate what is to be deemed old
        $old = ( time() - $maxLifetime );
    
        // query
        $sql = "DELETE FROM ";
        $sql .= " `".mysqli_real_escape_string( $this->db, $this->tableName )."` ";
        $sql .= "WHERE `date_expiration` <= ".mysqli_real_escape_string( $this->db, $old )." ";
        
        $res    = mysqli_query( $this->db, $sql ) OR die( '<pre>SQL Error:  '.mysqli_error( $this->db ).'<br>SQL:  '.$sql.'<br>File:  '.__FILE__.'<br>Line:  '.__LINE__ );        
        $result = mysqli_affected_rows( $this->db );
         
        if( $result > 0 ) {
            return true;
        }
    
        return false;
    }    
    
    /**
     * Delete by Session ID
     *
     * @param	stirng	$id
     * @return	boolean
     */
    public function deleteBySessionId( $sessionId = '' )
    {    
        $sql = "DELETE FROM ";
        $sql .= " `".mysqli_real_escape_string( $this->db, $this->tableName )."` ";
        $sql .= "WHERE `session_id` = '".mysqli_real_escape_string( $this->db, $sessionId )."' ";
        $sql .= "LIMIT 1 ";
    
        $res = mysqli_query( $this->db, $sql ) OR die( '<pre>SQL Error:  '.mysqli_error( $this->db ).'<br>SQL:  '.$sql.'<br>File:  '.__FILE__.'<br>Line:  '.__LINE__ );
    
        return mysqli_affected_rows( $this->db );
    }
    
    /**
     * Insert a new record
     *
     * @param  string   $sessionId
     * @param  string   $data
     * @return mixed
     */
    public function insert( $sessionId = '', $data = '' )
    {
        $userId = userId();
    
        // start the query
        $sql = "INSERT INTO `".$this->tableName."` ( ";
    
        if( $userId > 0 ) {
            $sql .= "`user_id`, ";
        }
    
        $sql .= "`session_id`, ";
        $sql .= "`data`, ";
        $sql .= "`date_last_update`, ";
        $sql .= "`date_created`, ";
        $sql .= "`date_expiration`, ";
        $sql .= "`ip`, ";
        $sql .= "`user_agent` ";
        $sql .= " ) VALUES ( ";
    
        if( $userId > 0 ) {
            $sql .= " '".mysqli_real_escape_string( $this->db, $userId )."', ";
        }
    
        $sql .= " '".mysqli_real_escape_string( $this->db, $sessionId )."', ";
        $sql .= " '".mysqli_real_escape_string( $this->db, $data )."', ";
        $sql .= " '".mysqli_real_escape_string( $this->db, time() )."', ";
        $sql .= " '".mysqli_real_escape_string( $this->db, time() )."', ";
        $sql .= " '".mysqli_real_escape_string( $this->db, time() + SESSION_TIMEOUT )."', ";
        $sql .= " '".mysqli_real_escape_string( $this->db, getIp() )."', ";
        $sql .= " '".mysqli_real_escape_string( $this->db, $_SERVER['HTTP_USER_AGENT'] )."' ";
        $sql .= "); ";
    
        $res = mysqli_query( $this->db, $sql ) OR die( '<pre>SQL Error:  '.mysqli_error( $this->db ).'<br>SQL:  '.$sql.'<br>File:  '.__FILE__.'<br>Line:  '.__LINE__ );
    
        return mysqli_affected_rows( $this->db );
    }    
    
    /**
     * Replace by Session ID
     *
     * @param  string   $sessionId
     * @param  string   $data
     * @return mixed
     */
    public function replaceBySessionId( $sessionId = '', $data = '' )
    {   
        $userId = userId();
        
        // start the query
        $sql = "REPLACE INTO `".$this->tableName."` ( ";
        
        if( $userId > 0 ) {
            $sql .= "`user_id`, ";            
        }
        
        $sql .= "`session_id`, ";
        $sql .= "`data`, ";
        $sql .= "`date_created`, ";
        $sql .= "`date_last_update`, ";
        $sql .= "`date_expiration`, ";
        $sql .= "`ip`, ";
        $sql .= "`user_agent` ";
        $sql .= " ) VALUES ( ";
        
        if( $userId > 0 ) {
            $sql .= " '".mysqli_real_escape_string( $this->db, $userId )."', ";             
        }
        
        $sql .= " '".mysqli_real_escape_string( $this->db, $sessionId )."', "; 
        $sql .= " '".mysqli_real_escape_string( $this->db, $data )."', ";
        $sql .= " '".mysqli_real_escape_string( $this->db, time() )."', ";
        $sql .= " '".mysqli_real_escape_string( $this->db, time() )."', ";
        $sql .= " '".mysqli_real_escape_string( $this->db, time() + SITE_SESSION_TIMEOUT )."', ";
        $sql .= " '".mysqli_real_escape_string( $this->db, getIp() )."', ";
        $sql .= " '".mysqli_real_escape_string( $this->db, $_SERVER['HTTP_USER_AGENT'] )."' ";        
        $sql .= "); ";

        $res = mysqli_query( $this->db, $sql ) OR die( '<pre>SQL Error:  '.mysqli_error( $this->db ).'<br>SQL:  '.$sql.'<br>File:  '.__FILE__.'<br>Line:  '.__LINE__ );
    
        return mysqli_affected_rows( $this->db );
    } 
    
}