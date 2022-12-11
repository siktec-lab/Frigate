<?php 

namespace Siktec\Frigate\Models;

use Siktec\Frigate\DataBase\MysqliDb;
use Siktec\Frigate\Models\DbData;

/**
 * DbDataTrait
 * 
 * This trait is used to provide database functionality to a DataModel object
 * 
 * @package Siktec\Frigate\Models
 */
trait DbDataTrait
{

    /**
     * @property DbData _db_data
     * the DbData object which stores the table name, columns map and primary key
     */
    protected DbData $_db_data;

    /**
     * @property ?MysqliDb _db_data
     * the MysqliDb object which is used to access the database
     */
    protected ?MysqliDb $_conn = null;
    
    /**
     * use_db
     * sets the database connection and the DbData object
     * @param  string $table
     * @param  ?MysqliDb $conn
     * @param  string $primary
     * @return void
     */
    public function use_db(string $table, ?MysqliDb $conn = null, string $primary = "") : void {
        $this->_db_data = new DbData($table, $primary);
        if ($conn) {
            $this->set_db_connection($conn);
        }
    }
    
    /**
     * set_db_connection
     * sets/changes the database connection
     * @param  MysqliDb $conn
     * @return self
     */
    public function set_db_connection(MysqliDb $conn) : self
    {
        $this->_conn = $conn;
        return $this;
    }
        
    /**
     * db_value_now
     * returns the current date and time in the database format as a function call
     * @return array
     */
    public function db_value_now() : array {
        return $this->_conn->now();
    }
    
    /**
     * db_value_null
     * returns the null value in the database format
     * @return string
     */
    public function db_value_null() : string {
        return "DBNULL";
    }
    
    /**
     * db_value_inc
     * returns the increment value in the database format as a function call
     * @param  int|float $by
     * @return array
     */
    public function db_value_inc(int|float $by) : array {
        return $this->_conn->inc($by);
    }
    
    /**
     * db_value_dec
     * returns the decrement value in the database format as a function call
     * @param  int|float $by
     * @return array
     */
    public function db_value_dec(int|float $by) : array {
        return $this->_conn->dec($by);
    }
    
    /**
     * set_db_data
     * sets the DbData object from an existing object
     * @param  DbData $db_data
     * @return void
     */
    public function set_db_data(DbData $db_data): void
    {
        $this->_db_data = $db_data;
    }
        
    /**
     * get_db_table
     * returns the table name
     * @return string the table name
     */
    public function get_db_table(): string
    {
        return $this->_db_data->get_table();
    }
        
    /**
     * map_db_column
     * maps a property to a column
     * @param  string $property
     * @param  string $column
     * @param  bool $allow_update
     * @return void
     */
    public function map_db_column(string $property, string $column, bool $allow_update) : void
    {
        $this->_db_data->map_column($property, $column, $allow_update);
    }
        
    /**
     * map_db_columns
     * maps multiple properties to columns
     * @param  array $map
     * @param  bool $default_allow_update
     * @return void
     */
    public function map_db_columns(array $map, bool $default_allow_update) : void
    {
        $this->_db_data->map_columns($map, $default_allow_update);
    }
    
    /**
     * load_from_db
     * loads the data from the database - This will not query the database but use the returned row instead
     * @param  array $row - the row from the database
     * @param  array $args - additional arguments used for user overwrites
     * @return bool true if the data was loaded, false if not
     */
    public function load_db_data(array $row, array ...$args) : bool
    {
        // Load data:
        if ($row) {
            $data = $this->_db_data->translate_data_to_properties($row);
            $this->set($data);
            return true;
        }
        return false;
    }

    /**
     * load_from_db
     * loads the data from the database - this will query the database
     * @param  array|string|int|float $where the where clause single value (primary key) or array of key-value pairs
     * @param  array $args - additional arguments used for user overwrites
     * @return bool true if the data was loaded, false if not
     */
    public function load_from_db(array|string|int|float $where, array ...$args) : bool
    {
        if (!$this->_conn || !$this->_db_data) {
            return false;
        }
        
        // Prepare query:
        $where = is_array($where) ? $where : [ $this->_db_data->primary => $where];
        foreach ($where as $key => $value) {
            $this->_conn->where($key, $value, "=", "AND");
        }

        // Get data:
        try {
        
            $data = $this->_conn->getOne($this->get_db_table()); //
        
        } catch (\Exception $e) {
            return false;
        }

        // Load data:
        return $this->load_db_data($data, ...$args);
    }
    
    /**
     * reload_from_db
     * reloads the data from the database this must be primary key based
     * @return array[int, string] 1 if the data was loaded, >0 if not, error message if any
     */
    public function reload_from_db(array ...$args) : array
    {
        if (!$this->_conn || !$this->_db_data) {
            return [-1, "No connection or no db data"];
        }
        
        // Prepare query:
        if ($this->_db_data->primary) {
            // Primary key is set use it:
            $field = $this->_db_data->translate_column($this->_db_data->primary);
            $this->_conn->where($this->_db_data->primary, $this->{$field});
        } else {
            return [-1, "No primary key set"];
        }

        // Get data:
        try {
            
            // Query:
            $data = $this->_conn->getOne($this->get_db_table());
            
            // Load data:
            $load = $this->load_db_data($data, ...$args);

            return $load ? [1, ""] : [-1, "No data found"];
            
        } catch (\Exception $e) {
            return [-1, $e->getMessage()];
        }
    }

    /**
     * save_to_db
     * saves the data to the database - this will create a new record.
     * @return array[int, string] id if the data was created, >0 if not, error message if any
     */
    public function save_to_db(bool $auto_primary = true, array $apply_function = [], array ...$args) : array
    {
        if (!$this->_conn || !$this->_db_data) {
            return [-1, "No database connection or no database config."];
        }

        // Prepare data:
        $data = $this->_db_data->translate_data_to_columns($this->get());
        $data = $this->_db_data->filter_updatable_data($data);
        
        // apply functions:
        foreach ($apply_function as $property => $apply) {
            $column = $this->_db_data->translate_property($property);
            if (array_key_exists($column, $data)) {
                $data[$column] = $apply;
            }
        }

        // Save data:
        try {
            if ($this->_conn->insert($this->get_db_table(), $data)) {
                $id = $this->_conn->getInsertId();

                // Set primary key only if auto_primary is true:
                if ($this->_db_data->primary) {
                    $field = $this->_db_data->translate_column($this->_db_data->primary);
                    $this->{$field} = $id;
                }
                return [$id, ""];
            } else {
                return [-1, "Error while saving data."];
            }
        } catch (\Exception $e) {
            return [-1, $e->getMessage()];
        }
    }
    
    /**
     * delete_from_db
     * deletes the data from the database - this must be primary key based
     * @return array[int, string] 1 if the data was deleted, >=0 if not, error message if any
     */
    public function delete_from_db(array ...$args) : array
    {
        if (!$this->_conn || !$this->_db_data) {
            return [-1, "No database connection or no database config."];
        }
        
        // Prepare query:
        if ($this->_db_data->primary) {
            // Primary key is set use it:
            $field = $this->_db_data->translate_column($this->_db_data->primary);
            $this->_conn->where($this->_db_data->primary, $this->{$field});
        } else {
            // loop through all properties and add them to the where clause
            foreach ($this->_db_data->get_columns() as $property => $column) {
                $this->_conn->where($column[0], $this->{$property}, "=", "AND");
            }
        }
        // Delete data:
        try {
            $exec = $this->_conn->delete($this->get_db_table(), 1);
            return [
                $exec ? 1 : 0,
                $exec ? "" : "Nothing deleted."
            ];
        } catch (\Exception $e) {
            return [-1, $e->getMessage()];
        }
    }
    
    /**
     * update_on_db
     * updates the data on the database - this must be primary key based
     * @param  array $apply_function db array func to apply to the data use the property name as key
     * @return array[int, string] 1 if the data was updated, >=0 if not, error message if any
     */
    public function update_on_db(array $apply_function = [], array ...$args) : array
    {
        if (!$this->_conn || !$this->_db_data) {
            return false;
        }
        
        // Prepare data:
        $data = $this->_db_data->translate_data_to_columns($this->get());
        $data = $this->_db_data->filter_updatable_data($data);

        // apply functions:
        foreach ($apply_function as $property => $apply) {
            $column = $this->_db_data->translate_property($property);
            if (array_key_exists($column, $data)) {
                $data[$column] = $apply;
            }
        }

        // Prepare query:
        if ($this->_db_data->primary) {
            // Primary key is set use it:
            $field = $this->_db_data->translate_column($this->_db_data->primary);
            $this->_conn->where($this->_db_data->primary, $this->{$field});
        } else {
            return [-1, "No primary key set."];
        }

        // Update data:
        try {
            $exec = $this->_conn->update($this->get_db_table(), $data, 1);
            return [
                $exec ? 1 : 0,
                $exec ? "" : "Nothing updated."
            ];
        } catch (\Exception $e) {
            return [-1, $e->getMessage()];
        }
    }
}