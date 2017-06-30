<!DOCTYPE html>
<?php
session_start();
$_SESSION['state'] = 'start';
?>
<html lang = 'zh'>
	<head>
		<meta charset = "utf-8">
		<meta name = "viewport" content = "width=device-width, initial-scale=1.0, user-scalable = no">
		<link rel="stylesheet" href="style/dialog.css" type="text/css">
		<title>长大版你画我猜</title>
		<style>
html,body,div {
	margin: 0;
	padding: 0;
}
a {
	text-decoration: none;
}
.face {
	background: url("images/face.jpg") center center no-repeat;
	background-size: contain;
	height: 200px;
	overflow: hidden;
	width: 100%;
}
.room {
	display: flex;
	padding:10px;
	flex-wrap: wrap;
}
.seat-wrap {
	width: 25%;
}
.seat {
	background: url("images/icon/add.png") center center;
	background-size: 73px 73px;
	border: 1px solid #ddd;
	border-radius: 5px;
	box-sizing: border-box;
	height: 74px;
	margin: 4px auto;
	width: 74px;
}
.sitted {
	background-image: url("images/faces/faces.jpg");
	background-position: 0 0;
	background-size: 370px, 370px;
	border: none;
}
.title {
	color: #8255fb;
	font-size: 25px;
	margin: 80px auto 0;
	text-align: center;
	text-shadow: 1px 1px 3px rgba(0,0,0,0.4);
	width: 100%;
}
.chd {
	color: #666;
	margin: 0 auto;
	text-align: right;
	width: 200px;
}
.btn-panel {
	text-align: center;
}
#start {
	background: #7130e0;
	border: none;
	color: #fff;
	border-radius: 4px;
	height: 40px;
	width: 80%
}
footer {
	color: #ddd;
	margin-top: 100px;
	font-size: 10px;
	text-align: center;
}
.name {
	color: #888;
	height: 20px;
	font-size: 12px;
	line-height: 20px;
	margin: 0 auto 5px;
	text-align: center;
	width: 80px;
}
		</style>
	</head>
	<body>
		<header class="face">
			<div class="title">
				你 画 我 猜
			</div>
			<div class="chd">
				——长大版
			</div>
		</header>
		<div class = 'room'>
			<div class = 'seat-wrap'>
				<div class='seat'></div>
				<div class='name'></div>
			</div>
			<div class = 'seat-wrap'>
				<div class='seat'></div>
				<div class='name'></div>
			</div>
			<div class = 'seat-wrap'>
				<div class='seat'></div>
				<div class='name'></div>
			</div>
			<div class = 'seat-wrap'>
				<div class='seat'></div>
				<div class='name'></div>
			</div>
			<div class = 'seat-wrap'>
				<div class='seat'></div>
				<div class='name'></div>
			</div>
			<div class = 'seat-wrap'>
				<div class='seat'></div>
				<div class='name'></div>
			</div>
			<div class = 'seat-wrap'>
				<div class='seat'></div>
				<div class='name'></div>
			</div>
			<div class = 'seat-wrap'>
				<div class='seat'></div>
				<div class='name'></div>
			</div>
		</div>
		<div class='btn-panel'>
			<input id='start' type = 'button' value='请入座'>
		</div>
		<footer>
			长大版你画我猜<br>2017软件系软件体系结构课程设计-Hozen@live.com
		</footer>
	</body>
	<script type="text/javascript" src = "./script/class.js"></script>
	<script type="text/javascript">
	window.onload = function() {
		var manager = new Manager("ws://localhost:4000");
		var room = new Room(manager);
	}
	</script> 
</html>
