<?php 
class db{
    // Internal properties
    private static $instance = null;
    private static $conn = null;
    private $driver;
    private static $error;
    private static $qry;
    private $data = [];
    private static $config;
    
    public function __construct($config){
        // No go please
    }
    public static function get_connection($config){
        self::$config = $config;
        if($config) $config = (array) $config; // Arrayify object, if any
        if (self::$instance === null) {
            self::$instance = new static(null);
            $options = [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, 
                        PDO::MYSQL_ATTR_FOUND_ROWS => true,
                        PDO::ATTR_EMULATE_PREPARES => true
                    ];
            try{
                if($config['driver'] == 'mysql'){
                    $connstr = $config['driver'].':host='.$config['host'].';dbname='.$config['db_name'];
                    self::$conn = new PDO($connstr,  $config['username'], $config['password'], $options);
                }
                elseif($config['driver'] == 'postgre'){
                    // coming soon
                }
                elseif($config['driver'] == 'sqlite'){
                    //coming soon
                }
            }
            catch(PDOException $e){
                if(self::$qry == null) $index = 'connection';
                else $index = md5(self::$qry);
                self::$error[$index]  = ['message'=>$e->getMessage(), 'query'=>self::$qry]; // Save error
            }
        }
        return self::$instance;
    }
    public function query($qry, $data = null){ //TO DO: check query for security breach
        self::$qry = $qry;
        if(is_array($data)) $this->data = $data;
        return $this->bind_data();
        //return $ret ? $this->guess_return_value($qry, $ret) : null;
    }
    /*
    *** Start select query ***
    @params = $table, $columns
    return this instance object
    chainable
    */
    public function select($table, $columns = '*'){
        self::$qry = "SELECT $columns FROM $table";
        return $this;
    }
    public function get_nearest_distance($table, $opts, $columns = '*'){
        if(!isset($opts['dst_unit'])) $opts['dst_unit'] = 6371;
        $hs = '
        (
            '.$opts['dst_unit'].' *  
            acos(cos(radians('.$opts['ref_lat'].')) *   
            cos(radians('.$opts['lat_col'].')) *   
            cos(radians('.$opts['lon_col'].') -   
            radians('.$opts['ref_lon'].')) +   
            sin(radians('.$opts['ref_lat'].')) *   
            sin(radians('.$opts['lat_col'].')))  
        ) AS distance';
        $columns .= ', '.$hs;
        self::$qry = "SELECT $columns FROM $table";
        return $this;
    }
    public function select_distance($table, $opts, $columns = '*'){
        return self::get_nearest_distance($table, $opts, $columns);
    }
    public function get_nearest_distance_built_in($table, $opts, $columns = '*'){
        if(strtolower(static::$config->driver) == "mysql") {
            return self::get_nearest_distance($table, $opts, $columns);
        }
        extract($opts);
        if($dst_unit == 6371 or $dst_unit == .001) $dst_unit = '0.001';
        $hs = "(ST_Distance_Sphere( point ($ref_lat, $ref_lon),  point($lon_col, $lat_col)) * $dst_unit) AS distance";
        $columns .= ', '.$hs;
        self::$qry = "SELECT $columns FROM $table";
        return $this;
    }
    public function insert($table, $data){
        $this->insert_update($table, $data, 'insert')->bind_data();
        return self::$conn->lastInsertId();
    }
    public function update($table, $data){
        $this->insert_update($table, $data, 'update');
        return $this;
    }
    public function replace($table, $data){
        //self::$error = null; // workaround
        $keys = array_keys($data);
        $columns = implode(", ", $keys);
        $values = implode(", :",$keys);
        self::$qry = "REPLACE INTO $table ($columns) values(:$values)";
        $this->data = $data;
        return $this->bind_data();
    }
    private function insert_update($table, $data, $mode){
        $keys = array_keys($data);
        if(strtolower($mode) === 'insert'){
            $columns = implode(", ", $keys);
            $values = implode(", :",$keys);
            self::$qry = "INSERT INTO $table ($columns) values(:$values)";
        }
        elseif(strtolower($mode) === 'update'){
            $tmp = '';
            foreach($keys as $k) $tmp .= $k.'=:'.$k.', ';
            $tmp = trim($tmp,', ');
            self::$qry = "UPDATE $table SET $tmp";
        }
        else throw new Exception('Unknown query mode!');
        $this->data = $data;
        return $this;
    }
    public function delete($table){
        self::$qry = "DELETE FROM $table";
        return $this;
    }
    public function join($table, $ON, $type = 'INNER'){
        self::$qry .= " $type JOIN $table ON $ON ";
        return $this;
    }
    private function make_condition($options, $type, $data){
        $whr = '';
        if(is_array($options)){
            foreach($options as $k=>$v){
                if(is_null($v)) {
                    $whr .= " $k IS NULL";
                    continue;
                }
                $kx = $k;
                if(isset($this->data[':'.$kx])){
                    $rand = rand(11, 99); // supposed to be truly random without reoccurance (to do)
                    $kx = $k.$rand;
                } 
                $whr .= " $k=:$kx ".chr(0xA0);
                if(!empty($data)) $v = $data[$k];
                $this->data[':'.$kx]=$v;
            }
            $whr = str_replace(chr(0xA0),'AND',rtrim($whr,chr(0xA0)));
        }
        elseif(is_string($options) or is_int($options)){
            $whr .= $options;//addslashes($options);
            if(!empty($data)){
                foreach($data as $k=>$v){
                    if(stripos(':'.$k,$whr) != -1)$this->data[':'.$k]=$v;
                }
            }
        }
        $whr = trim($whr);
        self::$qry .= " $type $whr";
        return $this;
    }
    public function where($options, $data = []){
        return $this->make_condition($options, 'WHERE', $data);
    }
    public function or($options, $data = []){
        return $this->make_condition($options, 'OR', $data);
    }
    public function and($options, $data = []){
        return $this->make_condition($options, 'AND', $data);
    }
    public function having($condition){
        self::$qry .= ' HAVING '.rtrim($condition);
        return $this;
    }
    public function order_by($col, $order="DESC"){
        self::$qry.= " ORDER BY ". $col. " ". $order;
        return $this;
    }
    public function group_by($col){
        self::$qry.= " GROUP BY $col";
        return $this;
    }
    public function limit($count, $start = 0){
       self::$qry.= " LIMIT ".$start.','.$count;
        return $this;
    }
    public function fetch(){
        $then = $this->bind_data();
        return $then ? $then->fetch(PDO::FETCH_ASSOC) : null;
    }
    public function getQuery(){
        return self::$qry;
    }
    public function error($show_last = true){
        if($show_last && self::$qry){
            $query_hash = md5(self::$qry);
            if($query_hash && isset(self::$error[$query_hash])) return self::$error[$query_hash];
        }
        return self::$error;
    }
    public function fetchAll(){
        $then = $this->bind_data();
        return $then ? $then->fetchAll(PDO::FETCH_ASSOC) : null;
    }
    public function commit(){
        return $this->bind_data();
    }
    private function bind_data(){
        if(self::$conn === null) return;
        try{
            $opts = array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY);
            $stmt = self::$conn->prepare(self::$qry, $opts);
            foreach($this->data as $key=>$value){
                switch($value){
                    case is_int($value):	$type = PDO::PARAM_INT;
                    case is_bool($value):	$type = PDO::PARAM_BOOL;
                    case is_null($value):	$type = PDO::PARAM_NULL;
                    /*case is_blob($value):	$type = PDO::PARAM_LOB;*/
                    default: $type = PDO::PARAM_STR;
                }
                $stmt->bindValue($key, $value, $type);
            }
            //print_r($this->data);
            $this->data = []; // Data already sent, clean the variable for later use
            $stmt->execute();
        }
        catch(PDOException $e){
            self::$error[md5(self::$qry)] = ['message'=>$e->getMessage(), 'query'=>self::$qry];
            return null;
        }
        return $stmt;
    }
    private function guess_return_value($qry, $stmt){
        if(stripos($qry,'insert') !== false || stripos($qry,'replace') !== false) $method = 'last_insert_id';
		elseif(stripos($qry,'select') !== false || stripos($qry,'PRAGMA') !== false) $method = 'fetch';
        else $method = 'rowCount';
        return $stmt->$method(PDO::FETCH_ASSOC);
    }
    public function end_query($qry, $stmt = null){
        if($stmt == null) $stmt = self;
        return self::guess_return_value($qry, $stmt);
    }
}