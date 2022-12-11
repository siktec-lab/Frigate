<?php 

namespace Siktec\Frigate\Models;

/**
 * DataModel
 * a base class for data models
 * @package Siktec\Frigate\Models
 */
abstract class DataModel {

    /**
     * $_db_use_trait
     * the supported trait for database interaction
     * @var string
     */
    static private string $_db_use_trait = "Siktec\\Frigate\\Models\\DbDataTrait";

    /**
     * $keys
     * the keys of the model - this hepls to map the model.
     * @var array
     */
    protected array $keys = [];
    
    /**
     * __construct
     * create a new model will automatically map the keys
     * @return self
     */
    public function __construct() {
        
        //switch inner property to a reflection property:
        $finall_keys = [];
        foreach ($this->keys as $outer_name => $def) {
            if (property_exists($this, $def[0] ?? "")) {
                $finall_keys[$outer_name] = $def;
                $finall_keys[$outer_name][0] = new \ReflectionProperty($this, $def[0]);
                // add output type if not set:
                if (!array_key_exists(1, $def)) {
                    $finall_keys[$outer_name][] = $finall_keys[$outer_name][0]->getType()->getName();
                }
            }
        }
        $this->keys = $finall_keys;
    }
    
    /**
     * prop_outer
     * get the outer name of a inner property
     * @param  string $inner_name
     * @return ?array - the outer name, the inner name, the outer type and the inner type
     */
    public function prop_outer(string $inner_name) : ?array {
        foreach ($this->keys as $outer_name => $def) {
            if ($def[0]->getName() === $inner_name) {
                return [
                    "outer"      => $outer_name,
                    "inner"      => $def[0]->getName(),
                    "outerType"  => $def[1],
                    "innerType"  => $def[0]->getType()->getName()
                ];
            }
        }
        return null;
    }

    /**
     * prop_inner
     * get the inner name of a outer property
     * @param  string $outer_name
     * @return ?array - the inner name, the outer name, the inner type and the outer type
     */
    public function prop_inner(string $outer_name) : ?array {
        if (array_key_exists($outer_name, $this->keys)) {
            return [
                "outer"      => $outer_name,
                "inner"      => $this->keys[$outer_name][0]->getName(),
                "outerType"  => $this->keys[$outer_name][1],
                "innerType"  => $this->keys[$outer_name][0]->getType()->getName()
            ];
        }
        return null;
    }

    /**
     * set 
     * set the internal properties directly with the inner value names
     * @param  array $arr - the array with the inner value names as keys and the values
     * @param  bool $nested = false - if true, the inner models will be auto created if possible
     * @return self
     */
    public function set(array $arr, bool $nested = false) : self {
        foreach ($this->keys as $prop) {
            if (array_key_exists($prop[0]->getName(), $arr)) {
                $value = $this->match_type(
                    to_type  : $prop[0]->getType()->getName(), 
                    value    : $arr[$prop[0]->getName()]
                );
                if (!is_null($value)) {
                    $prop[0]->setValue($this, $value);
                } elseif($nested) {
                    // try to auto set the inner model:
                    $model = $this->_create_inner_model(
                        $prop[0]->getType()->getName(), 
                        $arr[$prop[0]->getName()],
                        "auto_set"
                    );
                    if (!is_null($model)) {
                        $prop[0]->setValue($this, $model);
                    }
                }
            }
        }
        return $this;
    }

    /** 
     * get 
     * return the internal properties with the inner value names
     * @return array
     */
    public function get() : array {
        $data = [];
        foreach ($this->keys as $prop) {
            $data[$prop[0]->getName()] = $prop[0]->getValue($this);
        }
        return $data;
    }
    
    /**
     * load_array
     * load the internal properties from an array - keys are the outer names
     * @param  array $arr - the array with the outer value names as keys and the values
     * @param  bool $nested = false - if true, the inner models will be auto created if possible
     * @return self
     */
    private function load_array(array $arr, bool $nested = false) : self {
        foreach ($this->keys as $name => $prop) {
            if (array_key_exists($name, $arr)) {
                $value = $arr[$name];
                $inner_type = $prop[0]->getType()->getName();
                $to_save = $this->match_type($inner_type, $value);
                if (!is_null($to_save)) {
                    $prop[0]->setValue($this, $to_save);
                } elseif ($nested) {
                    $model = $this->_create_inner_model($inner_type, $value, "auto_load");
                    if (!is_null($model)) {
                        $prop[0]->setValue($this, $model);
                    }
                }
            }
        }
        return $this;
    }
    
        
    /**
     * _create_inner_model
     * create an inner model if possible and call the method $use_method on it
     * @param  string $type - the type of the inner model
     * @param  mixed $args - the arguments for the method $use_method
     * @param  string $use_method - the method to call on the inner model
     * @return mixed - the created model or null
     */
    private function _create_inner_model(string $type, mixed $args, string $use_method = "auto_load") : mixed {

        // if class $inner_type extends has a method auto_load call it:
        if(!is_a($type, DataModel::class, true)) {
            return null;
        }
        
        $ref = new \ReflectionClass($type);
        
        //Make sure that it implements auto_load:
        if (!$ref->hasMethod("auto_load")) {
            return null;
        }

        //Distinguishe between db models and normal models:
        $traits = $ref->getTraitNames();
        if (in_array(self::$_db_use_trait, $traits)) {
            // its a db model so we need a connection:
            if (property_exists($this, "_conn")) {
                $model = $ref->newInstance($this->_conn);
                if ($model->{$use_method}($args)) {
                    return $model;
                }
            }
        } else {
            // its a normal model:
            $model = $ref->newInstance();
            if ($model->{$use_method}($args)) {
                return $model;
            }
        }
        return null;
    }

    /**
     * match_type
     * match the type of the value to the type of the definition
     * @param  string $defs
     * @param  mixed $value
     * @return mixed
     */
    private function match_type(string $to_type, mixed $value) : mixed {
        switch ($to_type) {
            case "str":
            case "string": {
                if (is_array($value)) {
                    return json_encode($value);
                }
                return settype($value, "string") ? $value : null;
            } break;
            case "int":
            case "integer": 
            case "double": 
            case "float":
            case "bool":
            case "boolean": {
                return settype($value, $to_type) ? $value : null;
            } break;
            case "array": {
                    if (is_string($value)) {
                        return json_decode($value, true);
                    }
                    if (is_array($value)) {
                        return $value;
                    }
                    if (is_object($value) && method_exists($value, "to_array")) {
                        return $value->to_array();
                    }
                    if (is_object($value) && method_exists($value, "toArray")) {
                        return $value->toArray();
                    }
            } break;
            case "object": {
                if (is_object($value)) {
                    return $value;
                }
            } break;
            default: {
                
                // Maybe a nested model:
                if (is_object($value)) {
                    // check if object is same class as $to_type or is a subclass of $to_type
                    if (is_a($value, $to_type)) {
                        return $value;
                    }
                }

            } break;
        }
        return null;
    }
    
    /**
     * load_json
     * load the internal properties from an json string - keys are the outer names
     * @param  string $json
     * @param  bool $nested = false - if true, the inner models will be auto created if possible
     * @return self
     */
    private function load_json(string $json, bool $nested = false) : self {
        $arr = @json_decode($json, true);
        return $this->load_array($arr ?: [], $nested);
    }

        
    /**
     * load
     * load the internal properties from an array or json string - keys are the outer names
     * @param  string|array $input
     * @param  bool $nested = false - if true, the inner models will be auto created if possible
     * @return void
     */
    public function load(string|array $input, bool $nested = false) {
        return is_string($input) ? $this->load_json($input, $nested) : $this->load_array($input, $nested);
    }
    
    /**
     * to_array
     * return the internal properties with the outer names as array
     * @return array
     */
    public function to_array() : array {
        $data = [];
        foreach ($this->keys as $name => $prop) {
            // Get the proper casted value:
            $outer_type = $prop[1];
            $data[$name] = $this->match_type($outer_type, $prop[0]->getValue($this));
        }
        return $data;
    }
    
    /**
     * to_json
     * return the internal properties with the outer names as json string
     * @param  bool $pretty
     * @return string
     */
    public function to_json(bool $pretty = true) : string {
        return json_encode($this->to_array(), $pretty ? JSON_PRETTY_PRINT : 0);
    }

    abstract public function normalize() : void;
    abstract public function validate(array &$errors = [], array $args = []);
}