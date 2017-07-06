var REG_INF = 1,
	REG_RESULT = 2,
	NEWLINK = 3,
	GAME_INF = 4,
	USER_INF = 5,
	USERS_INF = 6,
	REQUEST_START = 7,
	MESSAGE = 8;
function $(id) {
	return document.getElementById(id);
}
function getByCls(clsName, oParent) {
	var oParent = oParent || document;
	var tags = oParent.getElementsByTagName('*'); 
	var aResult = new Array();
	for(var i =0; i<tags.length; i++) {
		if(tags[i].className == clsName) {
			aResult.push(tags[i]);
		} else {
			var names = tags[i].className.split(" ");
			for(var j=0; j<names.length; j++) {
				if(names[j] == clsName) {
					aResult.push(tags[i]);
				}
			}
		}
	}
	return aResult;
}
class msgBox {
	constructor(client) {
		this.inputBox = document.createElement('div');
		this.inputBox.id = 'input-box';
		this.inputBox.innerHTML = "<input type = 'text' id = 'answer-input'><input type = 'button' id = 'send-message' value='发送'>";	
			
		this.show(client);
	}
	show(client) {
		document.body.appendChild(this.inputBox);
		this.sendMsg(client);
	}
	sendMsg(client) {
		var manager = client.manager,
			user = client.user,
			msg = $('answer-input'),
			btn = $('send-message');
		msg.oninput = function() {
			if(msg.value == '') {
				btn.style.background = "#abc6f9";
				btn.style.color = "#e8e8e8";
				btn.onclick = null;
			} else {
				btn.style.background = "#61a6f9";
				btn.style.color = "#fff";
				btn.onclick = function() {
					var data = new Object();
					data.type = 1;
					data.userName = user.name;
					data.msgValue = msg.value;
					manager.sendData(MESSAGE,data);
					msg.value=null;
					btn.onclick = null;
					btn.style.background = "#abc6f9";
					btn.style.color = "#e8e8e8";
				}
			}

		}
	}

}
class Manager {
	constructor(url) {
		this.ws = new WebSocket(url);
		this.ws.onclose = function() {
			alert("ws disconnect");
		}
	}
	sendData(type, data) {
		var msg = new Object();
		msg.type = type;
		msg.data = data;
		msg = JSON.stringify(msg);
		console.log("sendMsg: "+msg);
		this.ws.send(msg);
	}
	getData(client) {
		this.ws.onmessage = function(event) {
			var msg = event.data;
			msg = JSON.parse(msg);
			console.log("getMsg: ");
			console.log(msg);

			switch (msg.type) {
				case REG_RESULT:
					if (msg.data == "success")
						client.regDialog.remove();
					break;
				case GAME_INF:
					client.setUsers(msg.users);
					if (msg.state == "over") {
						client.user.gameState = msg.state;
						client.btnChange(msg.state);
						client.sitFn();
					}
					break;
				case USERS_INF:
					client.setUsers(msg.users);
					break;
				case MESSAGE:
					client.showMsg(msg.data);
					break;
				default: 
					break;
			}
		}
	}
}

class User {
	constructor() {
		this.name = null;
		this.face = Math.round(Math.random()*25);
		this.order = null;
		this.type = 'normal';
		this.state = 'observe';
		this.gameState = 'over';
	}
	getPHPSess() {
		var arr = new Array();
		var reg = new RegExp("(^| )PHPSESSID=([^;]*)(;|$)");
		if(arr = document.cookie.match(reg)) {
			return unescape(arr[2]);
		} else {
			return null;
		}
	}
	getUserInfo() {
		var res = {
			name    : this.name,
			face    : this.face,
			type    : this.type,
			order   : this.order,
			state   : this.state,
			session : this.PHPsession
		}
		return res;
	}
}
class Dialog {
	constructor(title) {
		this.dialog = document.createElement("div");
		this.dialog.innerHTML = "<div id='dialog-title'>"+title+"</div><div id = 'dialog-body'></div>"
		this.dialog.className = "dialog";
		document.body.appendChild(this.dialog);

		var bgBlack = document.createElement('div');
		bgBlack.id = "bg-black";
		document.body.appendChild(bgBlack);	
	}
	remove() {
		document.body.removeChild(this.dialog);
		document.body.removeChild($('bg-black'));
	}
}
class RegDialog extends Dialog {
	constructor(title, manager, user) {
		super(title);
		var This = this;
		$('dialog-body').innerHTML = "<input type='input' id='user-name'><div id='reg-msg'></div><input type='button' value='确 定' id='name-confirm'>"
		this.regName(manager, user);
	}
	regName(manager, user) {
		var This = this;
		$('user-name').oninput = function() {
			if(this.value == '' || this.value.length<1 || this.value.length>6) {
				$('name-confirm').style.color = "#888";
				$('name-confirm').style.bordercolor = "#888";
				if(this.value.length>6)
					$('reg-msg').innerText = '太长了';
				$('name-confirm').onclick = null;
			} else {
				$('name-confirm').style.color = "#eee";
				$('name-confirm').style.bordercolor = "#eee";
				$('reg-msg').innerText = '';
				$('name-confirm').onclick = function() {
					This.waitFn();
					var data = new Object();
					data.name = $('user-name').value;
					data.face = user.face;
					manager.sendData(REG_INF, data);
					user.name = $('user-name').value;
				}
			}
		}

	}
	waitFn() {
		var msg = $('reg-msg');
		var i = 0;
		var timer = setInterval(function() {
			if (i==50) {
				clearInterval(timer);
				msg.innerText = '请重试';
			}
			if (i%4==0) {
				msg.innerText = '连接中';
			} else {
				msg.innerText += '.'; 
			}
			i++;
		}, 200);
	}
}
class Client {
	constructor(url) {
		this.manager = new Manager(url);
		this.user = new User();
		this.gameState = "over";
		this.init();
	}
	showMsg(msg) {
		var msgBoard = $('msgBoard');
		switch (msg.type) {
			case 0:
				var content = '<span style="color: #80aee2">'+msg.msgValue+'</span>成为房主';
				break;
			case 1:
				var content = '<span style="color: #80aee2">'+msg.userName+"</span>: "+msg.msgValue;
				break;
			default:
				break;
		}
		var msg = document.createElement('div');
		msg.innerHTML = content;
		msg.style = null;
		msgBoard.appendChild(msg);
		var right = -50;
		var timer = setInterval(function() {
			if(right > msgBoard.offsetWidth +5) {
				clearInterval(timer);
				msgBoard.removeChild(msg);
			}
			right += 1;
			console.log(right);
			msg.style.right = right+"px";
		},20);

	}
}
class roomClient extends Client {
	constructor(url) {
		super(url);
		this.regDialog = new RegDialog('请设置昵称', this.manager, this.user);
		this.inputBox = new msgBox(this);
	}
	init() {
		var This = this;
		this.manager.ws.onopen = function() {
			This.manager.getData(This);
			var data = new Object();
			data.type = 'room';
			This.manager.sendData(NEWLINK, data);
		}
	}
	sitFn() {
		var This = this,
			seats = getByCls("seat");
		for(var i = 0, len = seats.length; i<len; i++) {
			seats[i].ii = i;
			seats[i].onclick = function() {
				if(this.nextSibling.nextSibling.innerText != "") {
					return 0;
				}
				if(this.ii == 0) {
					var msg = {
						type : 0,
						msgValue : This.user.name
					}
					This.manager.sendData(MESSAGE, msg);
				}
				This.user.order = this.ii;
				This.user.state = 'ready';
				This.btnChange(This.user.gameState);
				This.manager.sendData(USER_INF, This.user.getUserInfo());
			}
		}
	}
	setUsers(userInf) {
		var seats = getByCls('seat'),
			userNum = userInf.length;
		for(var i=0; i<8; i++) {
			for(var j=0; j<userNum; j++) {
				if(i == userInf[j].order) { 
					var face = userInf[j].face,
						posX = face%5*74,
						posY = Math.floor(face/5)*74;
					seats[i].className += " sitted";
					seats[i].style.backgroundPosition = posX + "px " + posY +"px";
					seats[i].nextSibling.nextSibling.innerText = userInf[j].name;
					break;
				} else {
					seats[i].style = null;
					seats[i].className = "seat";
					seats[i].nextSibling.nextSibling.innerText = null;
				}
			}
		}
	}
	btnChange(type) {
		var This = this,
			manager = this.manager;
		switch (type) {
			case "gaming":
				$('start').value = '游戏中';
				$('start').onclick = null;
				break;
			case "over":
				if(This.user.state == 'ready') {
					if(This.user.order == 0) {
						$('start').value = '开始游戏';
						$('start').style = null;
						$('start').onclick = function() {
							This.user.type = 'master';
							manager.sendData(REQUEST_START);
						} 
					}else {
						$('start').value = '等待开始';
						$('start').style.background = '#c2a5f6';
						$('start').style.color= '#e2d5fb';
						$('start').onclick = null;
					}
				} else {
					$('start').value = "请入座";
					$('start').onclick = null;
				}
				break;
			default:
				break;
		}
	}
}