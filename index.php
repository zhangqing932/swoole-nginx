<?php
include 'http.php';
$config = include 'config.php';

class server {
    
    private $server;
    private $config;
    
    public function __construct() {
       $this->config = include 'config.php';
       foreach( $this->config as $key=>$value){
           $this->$key = $value; 
       }
       $this->server = new swoole_websocket_server($this->socket_ip , $this->socket_port);
       $this->server->on('message' , 'OnMessage');                  //接受客户端发送的消息
       $this->server->on('HandShake' , 'OnHandShake');              //握手验证时的业务
       $this->server->on('Open' , 'OnOpen');                        //握手完成时触发
       $this->server->on('Request' , 'OnRequest');                  //http请求
       $this->server->on('Task' , 'OnTask');                        //task任务
       $this->server->on('Finish' , 'OnFinish');                    //task任务 完成
       $this->server->on('Close' , 'OnClose');

       
       $this->server->set([
            'worker_num'        => 4,       //工作进程数量   
            'max_request'       => 1000,    //每个worker只工作50次然后重启
            'dispatch_mode'     => 5,       //用户分配模式
            'daemonize'         =>false, 
            'task_worker_num'   =>16,       //task进程数量
       ]); 
       $this->server->start();
       
       
    }
    /**
     * 按照预定收到的消息一律转发出去交给nginx来处理业务
     * 如果需要登录一个 也设定一个专门的http接受请求来表示登录状态
     * @param swoole_websocket_server $server
     * @param frame $frame
     */
    public function  OnMessage($server,  $frame){
        
       $server->task($frame);
      
    }
    
    /**
     * 接受nginx的消息 ,将消息转发给客户端
     * 但是该消息头需要携带一个固定的标识,方便找到对应的客户端.此标志由websocket server提供
     * @param type $server
     * @param type $request
     */
    public function OnRequest($server, $request){
        
        $json = json_decode($request->rawContent(), true);
        $send_id = $json['client_id'];
        foreach($server->conections as $connection){    //密集运算 可以考虑给swoole增加一个通过uid找fd的方法
            $conectioninfo = $server->connection_info($connection);
            if($conectioninfo['uid'] == $send_id){
               break;
            }
        }
        $server->push($connection , $json['client_data']);  
    }
    
    /**
     * 关闭了fd通知系统解除相关绑定
     * @param type $fd
     * @param type $reset
     */
    public function OnClose( $fd,  $reset = false){
        
        
        
    }
    
    /**
     * 只管开线程发送给nginx 就可以了 其他的不用管
     * @param type $server 
     * @param type $task_id id
     * @param type $reactor_id 收发id
     * @param type $data        数据
     */
    public function OnTask($server, $task_id, $reactor_id, $data){
        $server->bind($data->fd ,$data->fd);
        $post_data = [
            'client_id'     =>  $data->fd,     //要求nginx记住这个fd 如果需要socker server记住nginx的id 需要做一次登录操作获取到nginx上的唯一标记
            'client_data'   =>  $data->data
        ];
        $json = json_decode($post_data);
        $http = new http();
        $this->setUrl($this->nginx);
        $http->setHeader('Content-Type: application/json');
        $http->setBody($json);
        $http->setLine('POST');
        $http->request();   
    }
    /**
     * task 完成时返回
     * @param string $data
     */
    public function OnFinish($data){
        
        
    }
    
    
    /**
     * http请求
     */
    private function http(){
        
        
        
    }
    
    
    
    
}

