<?php

class JConfig {

    public $path;

    public $data = array();
    
    /**
    data structure
    array($section => array ($key => (string,num,array) $value)))
    */
    
    public function __construct($file) {
        if (is_file($file)) {
            $this->read($file);
        }
        else {
            touch($file);
        }
        $this->path = $file;
    }
    
    public function get($section, $key = false) {
        $result = $key ? $this->data[$section][$key] : $this->data[$section];
        return $result;
    }
    
    public function set($section, $key, $value) {
        $this->$data[$section][$key] = $value;
    }
    
    public function read($file) {
        $data = json_decode(file_get_contents($file), true);
        if(! empty($data)) {
            foreach ($data as $section => $arr) {
                $this->data[$section] = $arr;
            }
        }
    }
    
    public function write() {
        $content = json_encode($this->data);
        file_put_contents($this->path, $content);
    }

}

