<?php

define('CTTEXT','text/html');
define('CTMULTI','multipart/form-data');
define('CTJSON','application/json');
define('CTURL','application/x-www-form-urlencoded');



trait Tools {
    
    public function __get($key) {
        if (property_exists (__CLASS__,$key)) {
            return $this->{$key};
            }
    }
    
    public function __set($key,$value) {
        if (property_exists (__CLASS__,$key)) {
            $this->{$key} = $value;
        }
    }
}

class Command {

    use Tools;

    private $url;
    private $method;
    private $protocol;
    
    public function __construct($url='',$method='GET',$protocol='1.1') {
        $this->url = $url;
        $this->method = $method;
        $this->protocol = $protocol;
    }
}

class Headers {

    use Tools;

    private $items = array();
    
    public function get_item($section,$key) {
        if (array_key_exists($section,$this->items)) {
            return ($this->items[$section][$key]?:NULL);
        }
    }
    
    public function set_item($section,$key,$value) {
        if (!array_key_exists($section,$this->items)) {
            $this->items[$section]=NULL; //section créée si inexistante
        }
        $this->items[$section][$key] = $value; 
    }
    
    public function add_item($section,$key,$value) {
        if (!isset($this->items[$section][$key])) {
            $this->set_item($section,$key,$value);
            }
        else {
            if (is_array($this->items[$section][$key])) {
                $this->items[$section][$key][]=$value;
            }
            else {
                print("can't redefine ! use set method<br>");
            }
        }
    }
    
    public function remove_item($section,$key,$value=NULL) {
        if (!is_array($this->items[$section][$key])) {
            unset($this->items[$section][key]);
            }
        else {
            if (!is_null($value)) {
                unset($this->items[$section][$key][$value]);
            }
            else {
                print("can't remove array ! add key parameter<br>");
            }
        }
    }
    
    public function add_arr_item($arr) {
        foreach ($arr as $section => $a) {
            foreach ($a as $key => $value) {
                $this->add($section,$key,$value);
            }
        }
    }
    
    public function set_arr_item($arr) {
        $this->items = $arr;
    }
}

class Content {

    use Tools;

    private $data = array();
    private $type = CTURL;
    
    public function add_data($key,$value) {
        $this->data[$key]=$value;
    }
    
    public function remove_data($section,$key) {
        unset($this->data[key]);
    }
    
    public function add_arr_data($arr) {
        foreach ($arr as $key => $value) {
            $this->add($key,value);
        }
    }
}


class Request {

    public $command;
    public $headers;
    public $content;
    
    public function __construct($command=false,$headers=false,$content=false) {
    
        $this->command = $command?:new Command();
        $this->headers = $headers?:new Headers();
        $this->content = $content?:new Content();
    
    }
    
    public function __clone() {
        $this->command = clone $this->command;
        $this->headers = clone $this->headers;
        $this->content = clone $this->content;
    }  
    
    
    public function stream() {
        $stream = clone $this->headers;
          
        $stream->set_item('http','method',$this->command->method);    
        $stream->set_item('http','protocol_version',floatval($this->command->protocol));
        $stream->add_item('http','header','Content-Type: '.$this->content->type); #, 'Content-Length: '.strlen($content)));
        $stream->set_item('http','content',$this->content->data);
        
        return $stream->items;
        
    }
    
    public function send() {
        $context = stream_context_create($this->stream());
        
        $response = json_decode(file_get_contents($this->command->url,false,$context),true);
        return $response;
    }
}
        
    
    
