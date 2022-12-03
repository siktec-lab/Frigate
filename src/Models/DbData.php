<?php 

namespace Siktec\Frigate\Models;

/**
 * DbData
 * this class is used to store data that defines a table mapping for a database in DataModel
 * 
 * @package Siktec\Frigate\Models
 */
class DbData
{
    /**
     * The name of the table
     * @property string $table_name
     */
    public string $table    = "";

    /**
     * The columns mapping for the table - this dynamically defined
     * @property array $columns
     */
    public array  $columns  = [];

    /**
     * The name of the primary key column
     * @property string $primary_key
     */
    public string $primary  = "";
    
    /**
     * __construct the DbData object - which is used to store the table name, columns map and primary key
     *
     * @param  string $table the table name
     * @param  string $primary the primary key if none only limited functionality is available
     * @return DbData
     */
    public function __construct(string $table, string $primary = "")
    {
        $this->table = $table;
        $this->primary = $primary;
    }
        
    /**
     * get_table
     * returns the table name
     *
     * @return string the table name
     */
    public function get_table(): string
    {
        return $this->table;
    }
        
    /**
     * get_columns
     * returns the columns map
     * example: [
     *  "property" => ["column", boolean(is_updatable)], 
     *  ... 
     * ]
     * @return array
     */
    public function get_columns(): array
    {
        return $this->columns;
    }
    
    /**
     * map_column
     * maps a property to a column
     * @param  string $property
     * @param  string $column
     * @param  bool $allow_update
     * @return void
     */
    public function map_column(string $property, string $column, bool $allow_update) : void
    {
        $this->columns[$property] = [$column, $allow_update];
    }
    
    /**
     * map_columns
     * maps multiple properties to columns
     * @param  array $map
     * @param  bool $default_allow_update
     * @return void
     */
    public function map_columns(array $map, bool $default_allow_update = true) : void
    {
        foreach ($map as $prop => $column) {
            if (is_string($column)) {
                $this->map_column($prop, $column, $default_allow_update);
            } elseif (is_array($column)) {
                $this->map_column($prop, $column[0], $column[1] ?? $default_allow_update);
            }
        }
    }
        
    /**
     * column_is_updatable
     * checks if a column is updatable
     * @param  ?string $property
     * @param  ?string $column
     * @return bool  true if the column is updatable
     */
    public function column_is_updatable(?string $property = null, ?string $column = null) : bool
    {
        $which = $property ?? $this->translate_column( $column ?? "" );
        if ($which)
            return $this->columns[$which][1] ?? false;
        return false;
    }
    
    /**
     * translate_property
     * translates a property to a column name
     * @param  string $property
     * @return ?string the column name or null if not found
     */
    public function translate_property(string $property) : ?string
    {
        return $this->columns[$property][0] ?? null;
    }
    
    /**
     * translate_column
     * translates a column name to a property name
     * @param  string $column
     * @return ?string the property name or null if not found
     */
    public function translate_column(string $column) : ?string
    {
        foreach ($this->columns as $from => $data) {
            if ($data[0] === $column) {
                return $from;
            }
        }
        return null;
    }
    
    /**
     * translate_data_to_properties
     * translates a data array from column names to property names (keys are translated)
     * @param  array $data
     * @return array the translated data
     */
    public function translate_data_to_properties(array $data) : array
    {
        $result = [];
        foreach ($data as $column => $value) {
            $from = $this->translate_column($column);
            if ($from) {
                $result[$from] = $value;
            }
        }
        return $result;
    }
    
    /**
     * translate_data_to_columns
     * translates a data array from property names to column names (keys are translated)
     * @param  array $data
     * @return array the translated data
     */
    public function translate_data_to_columns(array $data) : array {
        $result = [];
        foreach ($data as $from => $value) {
            $column = $this->translate_property($from);
            if ($column) {
                $result[$column] = $value;
            }
        }
        return $result;
    }
    
    /**
     * filter_updatable_data
     * filters a data array to only contain updatable columns
     * @param  array $data
     * @return array the filtered columns array
     */
    public function filter_updatable_data(array $data) : array {
        $result = [];
        foreach ($data as $column => $value) {
            if ($this->column_is_updatable(null, $column)) {
                $result[$column] = $value;
            }
        }
        return $result;
    }
}