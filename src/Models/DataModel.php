<?php 

namespace Siktec\Frigate\Models;

abstract class DataModel {

    protected array $keys = [];
    
    /**
     * set 
     * set the internal properties directly
     *
     * @param  array $arr
     * @return self
     */
    public function set(array $arr) : self {
        foreach ($this->keys as $prop) {
            if (
                array_key_exists($prop[1], $arr) && 
                property_exists($this, $prop[1])
            ) {
                $this->{$prop[1]} = $this->match_type($prop[0], $arr[$prop[1]]);
            }
        }
        return $this;
    }

    private function load_array(array $arr) : self {
        foreach ($this->keys as $name => $prop) {
            if (
                array_key_exists($name, $arr) && 
                property_exists($this, $prop[1])
            ) {
                $this->{$prop[1]} = $this->match_type($prop[0], $arr[$name]);
            }
        }
        return $this;
    }

    private function match_type(string $defs, mixed $value) : mixed {
        $type = explode(":", $defs, 2);
        switch ($type[0]) {
            case "string": {
                if (is_array($value)) {
                    return json_encode($value);
                }
                return settype($value, $type[0]) ? $value : null;
            } break;
            case "integer": 
            case "double": 
            case "boolean": {
                return settype($value, $type[0]) ? $value : null;
            } break;
            case "object": {
                
            } break;
            case "array": {
                    if (is_string($value)) {
                        return json_decode($value, true);
                    }
                    if (is_array($value)) {
                        return $value;
                    }
            } break;
        }
        return null;
    }

    private function load_json(string $json) : self {
        $arr = @json_decode($json, true);
        return $this->load_array($arr ?: []);
    }

    public function load(string|array $input) {
        return is_string($input) ? $this->load_json($input) : $this->load_array($input);
    }

    public function to_array() : array {
        $data = [];
        foreach ($this->keys as $name => $prop) {
            if ( property_exists($this, $prop[1])) {
                $data[$name] = property_exists($this, $prop[1]) ? $this->match_type($prop[2], $this->{$prop[1]}) : null;
            }
        }
        return $data;
    }

    public function to_json(bool $pretty = true) : string {
        return json_encode($this->to_array(), $pretty ? JSON_PRETTY_PRINT : 0);
    }

    abstract public function normalize() : void;
    abstract public function validate(array &$errors = []);
}