<?php

class http{
    
    const CRLF      = "\r\n";
    const vsersion  = 'HTTP/1.1';
    private $fd = null; //链接
    private $line = [];
    private $header = [];
    private $body = [];
    private $url = '';
    private $response = '';
    private $errno = -1;
    private $errstr = '';
    
    public function __construct($url) {
        $this->connection($url);
        $this->setHeader('Host: ' . $this->url['host']);
    }
    
    private  function connection($url){
        $this->url = parse_url($url);
        if(!isset($this->url['port'])) {
          $this->url['port'] = 80;
        }
        if(!isset($this->url['query'])) {
          $this->url['query'] = '';
        }
        $this->fd = fsockopen($this->url['host'],$this->url['port'],$this->errno,$this->errstr,3);
    }
    
    public function setHeader($headerline){
        $this->header[] = $headerline;
    }
    
    public function setBody($body){
        $this->body[0]= http_build_query($body);  
    }
    
    public function setLine($method){
       $this->line[0] = $method . ' ' . $this->url['path'] . '?' .$this->url['query'] . ' '. $this->version;
    }
    
    public function get() {
        $this->setLine('GET');
        $this->request();
        return $this->response;
    }
  
    public function post($body = '') {   
      $this->setLine('POST');
      $this->setBody($body);
      $this->setHeader('Content-length: ' . strlen($this->body[0]));
      $this->request();
      return $this->response;
    }
  
    public function request() {
          $request = array_merge($this->line,$this->header,array(''),$this->body,array(''));
          $req = implode(self::CRLF,$request); 
          fwrite($this->fd,$req);
          while(!feof($this->fd)) {
            $this->response .= fread($this->fd,1024);
          }
          $this->close(); 
    }
  
    public function close() {
        
      fclose($this->fd);
      $this->body = [];
      $this->line = [];
      $this->response = '';
      $this->header = [];

    }
    
    
    
}
