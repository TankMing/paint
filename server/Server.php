<?php
define(REG_INF, 1);
define(REG_RESULT, 2);
define(NEWLINK, 3);
define(GAME_INF, 4);
define(USER_INF, 5);
define(USERS_INF, 6);
define(REQUEST_START, 7);
define(MESSAGE, 8);
define(MESSAGE, 8);
define(GAME_START, 9);
define(RESPONSE_INF, 10);
define(SUBJECT, 11);
define(CLINET, 12);
define(STYLE, 1001);
define(START, 1002);
define(DRAWING, 1003);
define(CLEAR, 1004);
define(UNDO, 1005);
define(REDO, 1006);
define(SAVE, 1007);
define(REQUEST_PAINT, 1008);
class Subject {
    private $subjects = array(
	'饼干', '笔记本', '矿泉水', '手机', '微软', '鼠标', '飞机', '程序员', '狗', '雪人', '蒙多',
	'钓鱼岛', '扑克牌', '王炸', '鼠标垫', '机械键盘', '油烟机', '护士', '医生', '演员', '电影',
	'天使', '猴子', '齐天大圣', '定海神针', '白日做梦', '梦游', '僵尸', '杀手', '世界杯', '泾渭分明',
	'望其项背', '鸡鸣狗盗', '中国梦', '蹦极', '鬼屋', '电影院', '狗头', '鳄鱼', '小鱼人', '武器',
	'螃蟹', '螳螂', '教学楼', '宿舍楼', '华表', '天安门', '青蛙', '蝉', '厕所', '啤酒', '法拉利'
    );
    function getArr($num) {
	$res = array();
	$tmp = array_rand($this->subjects, $num);
	foreach($tmp as $val) {
	    $res[] = $this->subjects[$val];
	}
	return $res;
    }
}
class Server {
    private $master;
    private $subjects;
    private $subject;
    private $sockets = array();
    private $gameState = "over";
    private $gameTime = null;

    function __construct($addr, $port) {

	$this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)
	    or die("socket_create() failed");

	socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1)
	    or die("socket_option() failed");

	socket_bind($this->master, $addr, $port)
	    or die("socket_bind() failed");

	socket_listen($this->master, 9)
	    or die("socket_listen() failed");

	$this->sockets[0] = ['socket' => $this->master, 'type' => 'master', 'state' => 'online'];

	echo "Socket initialize success.\n";
	while(true) {
	    $read = array();
	    foreach($this->sockets as $socket) {
		if($socket['state'] == 'offline') {
		    continue;
		}
		$read[] = $socket['socket'];
	    }
	    $write = NULL;
	    $except = NULL;
	    socket_select($read, $write, $except, NULL);

	    foreach ($read as $socket) {
		if ($socket == $this->master) {
		    $client = socket_accept($socket);
		    if($client < 0) {
			echo "socket_accpet failed\n";
			continue;
		    } else {
			$this->connect($client);
			echo "A client have connected\n";
			continue;
		    }
		} else {
		    $bytes = socket_recv($socket, $buffer, 2048, 0);
		    echo "message length:$bytes bytes\n";
		    if($bytes < 9) {
			$this->disconnect($socket);
			continue;
		    } else {
			if($this->sockets[(int)$socket]['handshake']) {
			    $buffer = $this->decode($buffer);
			    echo "get message: $buffer\n";
			    $this->analyseMsg($socket, $buffer);
			} else {
			    echo "dohandshake\n";
			    $this->handShake($socket, $buffer);
			}
		    }
		}
	    }
	}
    }

    function analyseMsg($socket, $buffer) {
	$buffer = json_decode($buffer);
	$data = isset($buffer->data) ? $buffer->data : null;
	$msg = (object)array();
	switch($buffer->type) {
	case REG_INF:
	    $this->sockets[(int)$socket]['userName'] = $data->name;
	    $this->sockets[(int)$socket]['userFace'] = $data->face;
	    $this->sockets[(int)$socket]['userState'] = 'register';
	    $msg->type = REG_RESULT;
	    $msg->data = "success";
	    $this->send($socket, $msg); 
	    break;
	case NEWLINK:
	    $this->newlink($socket);
	    break;
	case USER_INF:
	    $this->updateUser($socket, $data);
	    $msg->type = USERS_INF;
	    $msg->users = $this->getUserInfo();
	    $this->broadcast($msg);
	    break;
	case REQUEST_START:
	    $msg->type = GAME_START;
	    if ($this->tryStart()) {
		$msg->data = 1;
		$this->gameState = "gaming";
		$this->gameTime = time();
		$subjects = new Subject();
		$this->subjects = $subjects->getArr(count($this->getSoekcts('gaming')));
		$this->subject = array_pop($this->subjects);
		$this->broadcast($msg);
	    } else {
		$msg->data = 0; 
		$this->send($socket, $msg);
	    }
	    break;
	case MESSAGE:
	    $this->broadcast($buffer);
	    break;
	}
    }
    function newlink($socket) {
	//新连接接入时，先广播游戏信息，包括游戏状态和玩家信息
	$msg = (object)array();
	$msg->type = GAME_INF;
	$msg->state = $this->gameState;
	$msg->users = $this->getUserInfo();
	$this->broadcast($msg);
	//再分别发送客户单端类型
	$msg->type = CLINET;
	unset($msg->state);
	unset($msg->users);
	$msg->client = $this->sockets[(int)$socket]['client'];
	$this->send($socket, $msg);
	//发送题目信息
	if($this->gameState == 'gaming') {
	    $msg->type = SUBJECT;
	    if($this->sockets[(int)$socket]['client'] == 'painter') {
		$msg->data = $this->subject;
		$this->send($socket, $msg);
	    } else if ($this->sockets[(int)$socket]['client'] == 'answerer') {
		$msg->data = count($this->subject);
		$this->send($socket, $msg);
	    }
	}
    }
    function releaseSubject() {
	$msg = (object)array();
	$msg->type = SUBJECT;
	$msg->data = array_pop($this->subjects);
	$this->broadcast($msg, 'painter');
	$msg->data = count($msg->data);
	$this->broadcast($msg, 'answerer');
    }
    function updateUser($socket, $data) {
	$this->sockets[(int)$socket]['userName'] = $data->name;
	$this->sockets[(int)$socket]['userOrder'] = $data->order;
	$this->sockets[(int)$socket]['userFace'] = $data->face;
	$this->sockets[(int)$socket]['userState'] = $data->state;
    }
    /*
     * 接到客户端游戏开始请求后，检查是否可以开始
     * 若准备状态玩家个数大于2则可以开始游戏并置状态为gaming，否则不予开始
     * 返回bool值表示是否允许开始
     */
    function tryStart() {
	$i = 0;
	$arr = array();
	foreach ($this->sockets as $socket) {
	    if ($socket['socket'] == $this->master) continue;
	    if ($socket['userState'] == 'ready') {
		$arr[] = $socket['socket'];
		$i++;
	    }
	}
	if ($i < 2) {
	    return false;
	} else {
	    foreach ($arr as $index) {
		$this->sockets[(int)$index]['userState'] = 'gaming';
		if($this->sockets[(int)$index]['userOrder'] == 0) {
		    $this->sockets[(int)$index]['client'] = 'painter';
		} else {
		    $this->sockets[(int)$index]['client'] = 'answerer';
		}
	    }
	    return true;
	}
    }
    /*
     * 获取某一状态的所有连接
     * 状态
     * 返回该状态的所有连接
     */
    function getSoekcts($state = 'all') {
	$res = array();
	if($state == 'all') {
	    foreach($this->sockets as $socket) {
		if($socket['type'] == 'master') continue;
		if($socket['state'] == 'offline') continue;
		$res[] = $socket;
	    }
	    return $res;
	} else {
	    foreach($this->sockets as $socket) {
		if($socket['type'] == 'master') continue;
		if($socket['state'] == 'offline') continue;
		if($socket['userState'] == $state) {
		    $res[] = $socket;
		}
	    }
	    return $res;
	}
    }
    /*
     * 获取用户信息
     * p1: socket套接字的值， 默认为'all'，返回所有用户信息；否则返回指定套接字用户的信息
     */
    function getUserInfo($socket='all') {
	$res = array();
	if ($socket === 'all') {
	    $i = 0;
	    foreach ($this->sockets as $socket) {
		if ($socket['socket'] == $this->master) continue;
		if (@$socket['userState'] == 'ready' || @$socket['userState'] == 'gaming'){
		    $res[$i]['name'] = $socket['userName'];
		    $res[$i]['face'] = $socket['userFace'];
		    $res[$i]['order'] = $socket['userOrder'];
		    $res[$i]['state'] = $socket['state'];
		    $res[$i]['type'] = $socket['client'];
		    $i++;
		}
	    }	
	} else {
	    $socketInfo = $this->sockets[(int)$socket];
	    foreach($socketInfo as $key=>$value) {
		if(substr($key,0,4) == 'user') {
		    $res[$key] = $value;			
		}
	    }
	}
	return $res;
    }
    /*
     * 初始化新的套接字，设置相关信息，存入对象属性
     * p1: 需要设置的套接字
     */
    function connect($socket) {
	socket_getpeername($socket, $ip, $port);
	$socket_info = [
	    'socket' => $socket,
	    'type' => 'client',
	    'handshake' => false,
	    'ip' => $ip,
	    'client' => 'unknow',
	    'state' => 'online',
	    'port' => $port,
	    'userState' => 'observer'
	];
	$this->sockets[(int)$socket] = $socket_info;
    }
    /*
     * 解码webSocket帧
     * return： 解码后的数据
     */
    function decode($buffer) {
	$len = $masks = $data = $decoded = null;
	$len = ord($buffer[1]) & 127;
	if ($len == 126) {
	    $masks = substr($buffer, 4, 4);
	    $data = substr($buffer, 8);
	} else if ($len == 127) {
	    $masks = substr($buffer, 10, 4);
	    $data = substr($buffer, 14);
	} else {
	    $masks = substr($buffer, 2, 4);
	    $data = substr($buffer, 6);
	}
	for($index = 0; $index < strlen($data); $index++) {
	    $decoded .= $data[$index] ^ $masks[$index % 4];
	}
	return $decoded;
    }
    /*
     * 接受http请求并解析，发送websocket连接响应，完成websocket连接的建立
     */
    function handShake($socket, $buffer) {

	$key = null;
	$session = null;

	preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $buffer, $match);
	$key = $match[1];
	preg_match("/Cookie: PHPSESSID=(.*)\r\n/", $buffer, $match);
	$session = $match[1];

	$mask = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
	$acceptKey  = base64_encode(sha1($key.$mask, true));
	$upgrade = "HTTP/1.1 101 Switching Protocols\r\n".
	    "Upgrade: webSocket\r\n".
	    "Connection: Upgrade\r\n".
	    "Sec-WebSocket-Accept: ".$acceptKey."\r\n".
	    "\r\n";
	socket_write($socket, $upgrade, strlen($upgrade));
	$this->sockets[(int)$socket]['handshake'] = true;
	$this->checkSession($socket, $session);
	$this->sockets[(int)$socket]['session'] = $session;
    }
    /*
     * 对比$socket客户端和已有连接的phpsession值，判断是否为重连socket，以应对较差的客户端网络环境
     * p1: 新接入的套接字, p2: 新接入的客户端session
     */
    function checkSession($socket, $session) {
	$isNew = true;
	foreach ($this->sockets as $element) {
	    if($element['socket'] == $this->master) continue;
	    if(@$element['session'] == $session) {
		echo "one client have reclient\n";
		foreach($element as $key=>$value) {
		    if($key=='userName' || $key=='userFace' || $key=='userOrder' || $key=='userState' || $key=='client')
			$this->sockets[(int)$socket][$key] = $value;
		}
		unset($this->sockets[(int)$element['socket']]);
		$isNew = false;
		break;
	    }
	}
	$this->responseLink($socket, $isNew);
    }
    /*
     * 判断新连接是否为全新连接后返回相应数据，用以客户端是否需要弹出注册界面
     */
    function responseLink($socket, $isNew) {
	$msg = (object)array();
	$msg->type = RESPONSE_INF;
	if($isNew) {
	    $msg->isNew = 1;	
	} else {
	    $msg->isNew = 0;	
	    $msg->user = $this->getUserInfo($socket);
	}
	$this->send($socket, $msg);
    }
    function recoveryInf($socket) {

    }
    /*
     * 客户端断开websocket连接时调用, 改变连接状态和玩家状态，以备重连
     */
    function disconnect($socket) {
	$msg = (object)array();
	$this->sockets[(int)$socket]['state'] = "offline";
	echo $this->sockets[(int)$socket]['userState']."\n";
	//准备状态掉线时将状态重置为已注册，避免占空座位。而游戏中状态不重置，以备重连。
	if($this->sockets[(int)$socket]['userState'] == 'ready') {
	    $this->sockets[(int)$socket]['userState'] = 'register';
	}
	echo $this->sockets[(int)$socket]['userState']."\n";
	$msg->type = USERS_INF;
	$msg->users = $this->getUserInfo();
	$this->broadcast($msg);
	echo "one client have disconnect\n";
    }
    /*
     * 向客户端广播消息
     * p1:广播消息 p2:客户端类型 p3:玩家状态
     * 优先判断客户端类型
     */
    function broadcast($data, $client="all", $userState='all') {
	$sockets = $this->sockets;
	echo "$client, $userState\n";
	foreach ($this->sockets as $socket) {
	    if($socket['socket'] == $this->master) continue;
	    if($socket['state'] == 'offline') continue;
	    if($client == "all") {
		if($userState == 'all') {
		    $this->send($socket['socket'], $data);
		    continue;
		} else if($socket['userState'] == $userState) {
		    $this->send($socket['socket'], $data);	
		    continue;
		}
	    } else if ($socket["client"] == $client) {
		$this->send($socket['socket'], $data);
		continue;
	    }
	}
    }
    /*
     * 将消息编码成WebSocket帧
     * p1: 需要编码的数据
     * 返回编码后的数据帧
     */
    function encode($buffer) {
	$len = strlen($buffer);
	if($len <= 125) {
	    return "\x81".chr($len).$buffer;
	} else if ($len<=65535) {
	    return "\x81".chr(126).pack("n",$len).$buffer;
	} else {
	    return "\x81".chr(127).pack("xxxxN", $len).$buffer;
	}
    }
    function send($socket, $data) {
	if(is_array($data) || is_object($data)) {
	    $data= json_encode($data);
	}
	echo "send: $data\n";
	$data= $this->encode($data);	
	socket_write($socket, $data, strlen($data));
    }
}
$ws = new Server('localhost', 4000);

