<?php 
/**
 * SQLite3 DB Abstraction
 *
 * @author      BizLogic <hire@bizlogicdev.com>
 * @copyright   2016 BizLogic
 * @link        http://bizlogicdev.com
 * @link        http://pricelessphp.com
 * @license     GNU Affero General Public License v3
 *
 * @since       Saturday, January 21, 2017, 19:41 GMT+1
 * @edited      $Date$ $Author$
 * @version     $Id$
 */

class Db_Sqlite3 extends SQLite3
{
    public $db;
    public $tableName;
    
    public function __construct( $filePath, $flags = SQLITE3_OPEN_READWRITE, $encryptionKey = null )
    {
        $this->db = new SQLite3( $filePath, $flags, $encryptionKey );
    }
    
    public function setTableName( $tableName )
    {
        $this->tableName = $this->db->escapeString( $tableName );
    }
    
    public function getTableName()
    {
        return $this->tableName;
    }
    
    public function fetchColumns( $tableName, $returnAllData = false )
    {
        $tableName  = $this->db->escapeString( $tableName );
        $query      = $this->db->query( "PRAGMA table_info(".$tableName.");" );
        $columns    = array();
        
        while ( $res = $query->fetchArray( SQLITE3_ASSOC ) ) {
            if( $returnAllData ) {
                $columns[] = $res;                
            } else {
                $columns[] = $res['name'];
            }           
        }
        
        return $columns;
    }
    
    /**
     * Get by ID
     *
     * @param	int		$id
     * @return	array
    */
    public function getById( $id )
    {
        $data  = array();
        $id    = (int)$id;
    
        if( isZero( $id ) ) {
            return $data;
        }
         
        $sql = "SELECT * FROM ";
        $sql .= " `".$this->db->escapeString( $this->tableName )."` ";
        $sql .= "WHERE `id` = '".$this->db->escapeString( $id ) ."' ";
        $sql .= "LIMIT 1 ";
    
        $res = $this->db->query( $sql ) OR die( '<pre>SQL Error:  '.$this->db->lastErrorMsg().'<br>SQL:  '.$sql.'<br>File:  '.__FILE__.'<br>Line:  '.__LINE__ );
    
        return $res->fetchArray( SQLITE3_ASSOC );
    }
    
    /**
     * Update a record by ID
     *
     * @param	int		$id
     * @return	array
     */
    public function setById( $id = 0, $data = array() )
    {
        return $this->updateById( $id, $data );
    }
    
    /**
     * Update a record by ID
     *
     * @param	int		$id
     * @return	array
     */
    public function updateById( $id = 0, $data = array() )
    {
        if( isZero( $id ) ) {
            return false;
        }
    
        if( empty( $data ) ) {
            return false;
        }
         
        // get column names for filtering
        $columnNames = $this->fetchColumns( $this->tableName );
         
        // filter
        foreach( $data AS $key => $value ) {
            if( !in_array( $key, $columnNames ) ) {
                unset( $data[$key] );
            }
        }
         
        // check after filtering
        if( empty( $data ) ) {
            return false;
        }
    
        $count	= count( $data );
        $i		= 1;
    
        $sql = "UPDATE `".$this->db->escapeString( $this->tableName )."` ";
        $sql .= "SET ";
    
        foreach( $data AS $key => $value ) {
            $sql .= "`".$this->db->escapeString( $key )."` = '".$this->db->escapeString( $value )."' ";
    
            if( $i < $count ) {
                $sql .= ", ";
            }
    
            $i++;
        }
    
        $sql .= "WHERE `id` = '".$this->db->escapeString( (int)$id ) ."' ";
        $sql .= "LIMIT 1 ";
    
        $res = $this->db->query( $sql ) OR die( '<pre>SQL Error:  '.$this->db->lastErrorMsg().'<br>SQL:  '.$sql.'<br>File:  '.__FILE__.'<br>Line:  '.__LINE__ );
    
        return $this->db->changes();
    }
    
    /**
     * Update by column & value
     *
     * @param	string		$id
     * @return	array
     */
    public function updateBy( $name, $value, $limit = 1 )
    {
        $limit = (int)$limit;
    
        // get column names for filtering
        $columnNames = $this->fetchColumns( $this->tableName );
    
        // filter
        if( !in_array( $name, $columnNames ) ) {
            return false;
        }
    
        $sql = "UPDATE `".$this->db->escapeString( $this->tableName )."` ";
        $sql .= "SET ";
        $sql .= " `".$this->db->escapeString( $name )."` ";
        $sql .= " = '".$this->db->escapeString( $value )."' ";
        $sql .= "WHERE `".$this->db->escapeString( $name )."` ";
        $sql .= " '".$this->db->escapeString( $value )."' ";
         
        if( $limit >= 1 ) {
            $sql .= "LIMIT ";
            $sql .= $this->db->escapeString( $limit );
        }
    
        $res = $this->db->query( $sql ) OR die( '<pre>SQL Error:  '.$this->db->lastErrorMsg().'<br>SQL:  '.$sql.'<br>File:  '.__FILE__.'<br>Line:  '.__LINE__ );
    
        return $this->db->changes();
    }
    
    /**
     * Get records
     *
     * @param	int		$limit
     * @param	int		$offset
     * @param	array	$orderBy
     * @return	array
     */
    public function get( $limit = null, $offset = null, $orderBy = array( ) )
    {
        $data  = array();
         
        $query = "SELECT * FROM ";
        $query .= " `".$this->db->escapeString( $this->tableName )."` ";
    
        if( !empty( $orderBy ) ) {
            $count 	= count( $orderBy );
            $i		= 0;
            	
            $query .= "ORDER BY ";
            foreach( $orderBy AS $orderKey => $orderValue ) {
                $i++;
                $query .= "`".$orderKey."` ".$orderValue." ";
    
                if( $i < $count ) {
                    $query .= ", ";
                }
            }
        }
    
        $limit	= (int)$limit;
        $offset = (int)$offset;
    
        if( ( $limit > 0 ) AND ( $offset > 0 ) ) {
            $query .= "LIMIT ".$limit." OFFSET ".$offset." ";
        } else if( $limit > 0 ) {
            $query .= "LIMIT ".$limit;
        }
    
        $res = $this->db->query( $query ) OR die( '<pre>SQL Error:  '.$this->db->lastErrorMsg().'<br>SQL:  '.$query.'<br>File:  '.__FILE__.'<br>Line:  '.__LINE__ );
    
        while( $row = $res->fetchArray( SQLITE3_ASSOC ) ) {
            $data[] = $row;
        }
    
        return $data;
    }
    
    public function count()
    {
        $query = "SELECT COUNT(*) AS `count` FROM ";
        $query .= " `".$this->db->escapeString( $this->tableName )."` ";
    
        $res	= $this->db->query( $query ) OR die( '<pre>SQL Error:  '.$this->db->lastErrorMsg().'<br>SQL:  '.$query.'<br>File:  '.__FILE__.'<br>Line:  '.__LINE__ );
        $data	= $res->fetchArray( SQLITE3_ASSOC );
    
        return $data['count'];
    }
    
    public function countBy( $column, $value )
    {
        $column = $this->db->escapeString( $column );
        $value	= $this->db->escapeString( $value );
    
        $query = "SELECT COUNT(*) AS `count` FROM ";
        $query .= " `".$this->db->escapeString( $this->tableName )."` ";
        $query .= "WHERE `".$column."` = '".$value."' ";
    
        $res	= $this->db->query( $query ) OR die( '<pre>SQL Error:  '.$this->db->lastErrorMsg().'<br>SQL:  '.$query.'<br>File:  '.__FILE__.'<br>Line:  '.__LINE__ );
        $data	= $res->fetchArray( SQLITE3_ASSOC );
    
        return $data['count'];
    }
    
    /**
     * Get by Columns & Values
     *
     * @param	array	$attribues
     * @param	int		$limit
     * @param	int		$offset
     * @param	array	$orderBy
     * @return	array
     */
    public function getBy( $attributes = array(), $limit = 1, $offset = 0, $orderBy = array() )
    {
        if( empty( $attributes ) OR !is_array( $attributes ) ) {
            return false;
        }
    
        // escape
        foreach( $attributes AS $key => $value ) {
            if( !is_integer( $value ) ) {
                $attributes[$key] = $this->db->escapeString( $value );
            } else {
                $attributes[$key] = (int)$value;
            }
        }
    
        $i 			= 0;
        $count		= count( $attributes );
    
        $query = "SELECT * FROM `".$this->db->escapeString( $this->tableName )."` ";
        $query .= "WHERE ";
        foreach( $attributes AS $key => $value ) {
            $i++;
            $query .= "`".$key."` = '".$value."' ";
            if( $i < $count ) {
                $query .= " AND ";
            }
        }
    
        if( !empty( $orderBy ) ) {
            $count 	= count( $orderBy );
            $i		= 0;
    
            $query .= "ORDER BY ";
            foreach( $orderBy AS $orderKey => $orderValue ) {
                $i++;
    
                $query .= "`".$this->db->escapeString( $orderKey )."` ";
                $query .= $this->db->escapeString( $orderValue )." ";
    
                if( $i < $count ) {
                    $query .= ", ";
                }
            }
        }
    
        $limit	= (int)$limit;
        $offset = (int)$offset;
        $data	= array();
    
        if( ( $limit > 0 ) AND ( $offset >= 0 ) ) {
            $query .= "LIMIT ".$limit." OFFSET ".$offset." ";
        } elseif ( $limit == 1 ) {
            $query .= "LIMIT 1 ";
        }
    
        $res = $this->db->query( $query ) OR die( '<pre>SQL Error:  '.$this->db->lastErrorMsg().'<br>SQL:  '.$query.'<br>File:  '.__FILE__.'<br>Line:  '.__LINE__ );
        while( $row = $res->fetchArray( SQLITE3_ASSOC ) ) {
            $data[] = $row;
        }
    
        $data = ( ( $limit == 1 ) AND isset( $data[0] ) ) ? $data[0] : $data;
    
        return $data;
    }
    
    /**
     * Get all records
     *
     * @param	array	$orderBy
     * @return	array
     */
    public function getAll( $orderBy = array() )
    {
        return $this->get( null, null, $orderBy );
    }
    
    /**
     * Insert
     *
     * @param   array	$data
     * @return  mixed
     */
    public function insert( $data = array() )
    {
        if( empty( $data ) ) {
            return false;
        }
    
        // get column names for filtering
        $columnNames = $this->fetchColumns( $this->tableName );
    
        // filter
        foreach( $data AS $key => $value ) {
            if( !in_array( $key, $columnNames ) ) {
                unset( $data[$key] );
            }
        }
    
        // check after filtering
        if( empty( $data ) ) {
            return false;
        }
    
        $count	= count( $data );
        $i		= 0;
    
        // start the query
        $sql = "INSERT INTO `".$this->tableName."` ( ";
    
        foreach( $data AS $key => $value ) {
            $i++;
            $comma = ( $i < $count ) ? ', ' : ' ';
            $key = $this->db->escapeString( $key );
            $sql .= "`".$this->db->escapeString( $key )."` ".$comma;
        }
    
        $sql .= " ) VALUES ( ";
    
        $i = 0;
        foreach( $data AS $key => $value ) {
            $i++;
            $comma = ( $i < $count ) ? ', ' : ' ';
            $value = $this->db->escapeString( $value );
            $sql .= "'".$this->db->escapeString( $value )."' ".$comma;
        }
    
        $sql .= ");";
        $res = $this->db->query( $sql ) OR die( '<pre>SQL Error:  '.$this->db->lastErrorMsg().'<br>SQL:  '.$sql.'<br>File:  '.__FILE__.'<br>Line:  '.__LINE__ );
    
        return $this->db->lastInsertRowID();
    }
    
    /**
     * Delete by columns & values
     *
     * @param	array	$attributes
     * @param	int		$limit
     * @return	int
     */
    public function deleteByAttr( $attributes = array(), $limit = 1 )
    {
        if( empty( $attributes ) OR !is_array( $attributes ) ) {
            return false;
        }
    
        // escape
        foreach( $attributes AS $key => $value ) {
            $attributes[$key] = $this->db->escapeString( $value );
        }
    
        $limit = (int)$limit;
         
        $sql = "DELETE FROM ";
        $sql .= " `".$this->db->escapeString( $this->tableName )."` ";
        $sql .= "WHERE `".$this->db->escapeString( $name )."` ";
         
        foreach( $attributes AS $key => $value ) {
            $i++;
            $query .= "`".$key."` = '".$value."' ";
            if( $i < $count ) {
                $query .= " AND ";
            }
        }
         
        if( $limit >= 1 ) {
            $sql .= "LIMIT ";
            $sql .= " = '".$this->db->escapeString( $limit ) ."' ";
        }
    
        $res = $this->db->query( $sql ) OR die( '<pre>SQL Error:  '.$this->db->lastErrorMsg().'<br>SQL:  '.$sql.'<br>File:  '.__FILE__.'<br>Line:  '.__LINE__ );
    
        return $this->db->changes();
    }
    
    /**
     * Delete by column & value
     *
     * @param  name    $string
     * @param  mixed   $value
     * @param  int     $limit
     * @return int
     */
    public function deleteBy( $name, $value, $limit = 1 )
    {
        $limit = (int)$limit;
         
        $sql = "DELETE FROM ";
        $sql .= " `".$this->db->escapeString( $this->tableName )."` ";
        $sql .= "WHERE `".$this->db->escapeString( $name )."` ";
        $sql .= " = '".$this->db->escapeString( $value ) ."' ";
         
        if( $limit >= 1 ) {
            $sql .= "LIMIT ";
            $sql .= " = '".$this->db->escapeString( $limit ) ."' ";
        }
    
        $res = $this->db->query( $sql ) OR die( '<pre>SQL Error:  '.$this->db->lastErrorMsg().'<br>SQL:  '.$sql.'<br>File:  '.__FILE__.'<br>Line:  '.__LINE__ );
    
        return $this->db->changes();
    }
    
    /**
     * Delete by ID
     *
     * @param	integer $id
     * @return	integer
     */
    public function deleteById( $id )
    {
        $sql = "DELETE FROM `".$this->db->escapeString( $this->tableName )."` ";
        $sql .= "WHERE `id` = '".$this->db->escapeString( $id )."' ";

        $res = $this->db->query( $sql ) OR die( $this->db->lastErrorMsg()."\n".$sql );
    
        return $this->db->changes();
    }
    
    /**
     * Delete by UUID
     *
     * @param	string	$uuid
     * @return	int
     */
    public function deleteByUUID( $uuid )
    {
        return $this->deleteBy( 'uuid', $uuid, 1 );
    }
    
    /**
     * Set by UUID
     *
     * @param	string	$uuid
     * @param	$data	array
     * @return	boolean
     */
    public function setByUUID( $uuid, $data = array() )
    {
        if( empty( $data ) ) {
            return false;
        }
         
        // get column names for filtering
        $columnNames = $this->fetchColumns( $this->tableName );
         
        // filter
        foreach( $data AS $key => $value ) {
            if( !in_array( $key, $columnNames ) ) {
                unset( $data[$key] );
            }
        }
         
        // check after filtering
        if( empty( $data ) ) {
            return false;
        }
    
        $count	= count( $data );
        $i		= 1;
    
        $sql = "UPDATE `".$this->db->escapeString( $this->tableName )."` ";
        $sql .= " SET ";
    
        foreach( $data AS $key => $value ) {
            $sql .= "`".$this->db->escapeString( $key )."` = '".$this->db->escapeString( $value )."' ";
    
            if( $i < $count ) {
                $sql .= ", ";
            }
    
            $i++;
        }
    
        $sql .= "WHERE `uuid` = '".$this->db->escapeString( $uuid ) ."' ";
        $sql .= "LIMIT 1 ";
    
        $res = $this->db->query( $sql ) OR die( '<pre>SQL Error:  '.$this->db->lastErrorMsg().'<br>SQL:  '.$sql.'<br>File:  '.__FILE__.'<br>Line:  '.__LINE__ );
    
        return $this->db->changes();
    }
    
    /**
     * Get by UUID
     *
     * @param	string	$uuid
     * @return	array
     */
    public function getByUUID( $uuid )
    {
        $data = $this->getBy(
            array( 'uuid' => $uuid )
            );
         
        if( !empty( $data ) ) {
            return $data['uuid'];
        }
    
        return array();
    }
    
    /**
     * Get name by ID
     *
     * @param	int		$id
     * @return	string
     */
    public function getNameById( $id )
    {
        $data = $this->getById( $id );
        if( !empty( $data ) ) {
            return $data['name'];
        }
    
        return '';
    }
    
    /**
     * Get UUID by ID
     *
     * @param	int		$id
     * @return	string
     */
    public function getUUIDById( $id = 0 )
    {
        $data = $this->getById( $id );
        if( !empty( $data ) ) {
            return $data['uuid'];
        }
    
        return '';
    }    
    
}
