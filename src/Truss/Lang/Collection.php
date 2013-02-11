<?php

class Collection extends ArrayIterator {
    public function map ($closure=FALSE) {
        $copy = $this->getArrayCopy();
        
        if (is_string($closure)) {
            $copy = array_map(function($value)use($closure){
                $function = function(){};
                
                if (is_object($value) && method_exists($value,$closure)) {
                    $value = $value->$closure();
                }
                else if (is_object($value) && isset($value->$closure) && ($value->$closure instanceof $function)) {
                    $value = $value->$closure->__invoke();
                }
                else if (is_object($value) && isset($value->$closure)) {
                    $value = $value->$closure;
                }
                else {
                    $value = NULL;
                }
                
                return $value;
            },
            $copy);
        }
        else if (is_callable($closure)) {
            $copy = array_map($closure,$copy);
        }
        
        return new Collection($copy);
    }
    
    public function inject ($start,$closure=FALSE) {
        return $start + array_sum($this->map($closure)->getArrayCopy());
    }
    
    public function collect ($closure=FALSE) {
        $copy = $this->getArrayCopy();
        
        if (is_string($closure)) {
            $copy = array_filter($copy,function($value)use($closure){
                $function = function(){};
                
                return
                    (is_object($value) && method_exists($value,$closure) && $value->$closure()) ||
                    (is_object($value) && isset($value->$closure) && ($value->$closure instanceof $function) && $value->$closure->__invoke()) ||
                    (is_object($value) && isset($value->$closure) && $value->$closure);
            });
        }
        else if (is_callable($closure)) {
            $copy = array_filter($copy,$closure);
        }
        
        return new Collection(array_values($copy));
    }
    
    public function reject ($closure=FALSE) {
        $copy = $this->getArrayCopy();
        
        if (is_string($closure)) {
            $copy = array_filter($copy,function($value)use($closure){
                $function = function(){};
                
                return
                    (is_object($value) && method_exists($value,$closure) && !$value->$closure()) ||
                    (is_object($value) && isset($value->$closure) && ($value->$closure instanceof $function) && !$value->$closure->__invoke()) ||
                    (is_object($value) && isset($value->$closure) && !$value->$closure);
            });
        }
        else if (is_callable($closure)) {
            $copy = array_filter($copy,function($value)use($closure){ return !call_user_func($closure,$value); });
        }
        
        return new Collection(array_values($copy));
    }
    
    public function sort ($closure=FALSE) {
        $copy = $this->getArrayCopy();
        
        if (is_callable($closure)) {
            usort($copy,$closure);
        }
        else {
            sort($copy);
        }
        
        return new Collection($copy);
    }
    
    public function unique () {
        return new Collection(array_unique($this->getArrayCopy()));
    }
    
    public function includes ($search) {
        return array_search($search,$this->getArrayCopy()) !== FALSE;
    }
    
    public function flat () {
        $copy = $this->getArrayCopy();
        $flat = array();
        array_walk_recursive($copy,function($value,$key)use(&$flat){ $flat []= $value; });
        return $flat;
    }
    
    function __toString () {
        return print_r($this->getArrayCopy(),TRUE);
    }
}