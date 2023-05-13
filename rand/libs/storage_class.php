<?php
class storage{
    private static $data = [];
    private static $instance = null;

    private function __construct(){
        // Prohit object creation
    }

    public static function init(){
        if(self::$instance === null) self::$instance = new Static();
        return self::$instance;
    }
    public function __set($index, $data){
        self::$data[$index] = $data;
    }
    public function __get($index){
        if(isset(self::$data[$index])) return self::$data[$index];
        else return null;
    }
    public function append($index, $data){
        if(isset(self::$data[$index])){
            $type = strtolower(gettype(self::$type));
            if($type == 'null') self::$data[$index] = $data;
            elseif($type == 'int' or $type == 'float' or $type == 'string') self::$data[$index] += $data;
            elseif($type == 'array') self::$data[$index][] = self::$data[$index];
            else return; // ($type == 'object' or $type == 'resource' or $type == 'bool')
        }
        else self::$data[$index] = $data;
    }
}