<?php
exit();

#兔卡 20160310

//测试地址本系统(内网/公网)，如果是服务端调用可以用内网地址
http://127.0.0.1/pay/index.php
http://10.51.107.252/pay/index.php
http://123.57.249.74/pay/index.php

//竞价系统测试地址本系统(内网/公网)，如果是服务端调用可以用内网地址
http://10.51.107.252/auction/index.php
http://123.57.249.74/auction/index.php

//用户登录测试地址(内网/公网)，如果是服务端调用可以用内网地址
http://10.51.107.252/user/index.php
http://123.57.249.74/user/index.php

//权限系统测试地址(内网/公网)，如果是服务端调用可以用内网地址
http://10.51.107.252/power_control/index.php
http://123.57.249.74/power_control/index.php

////////////////////////////////////////////////

//正式本系统地址(内网/公网)
http://10.45.36.175/pay/index.php
http://101.201.222.137/pay/index.php

//正式竞价系统地址本系统(内网/公网)，如果是服务端调用可以用内网地址
http://10.45.36.175/auction/index.php
http://101.201.222.137/auction/index.php

//正式用户登录地址(内网/公网)，如果是服务端调用可以用内网地址
http://10.45.36.175/user/index.php
http://101.201.222.137/user/index.php

//正式权限系统地址(内网/公网)，如果是服务端调用可以用内网地址
http://10.45.36.175/power_control/index.php
http://101.201.222.137/power_control/index.php



//协议规则
urlencode的格式用户信息（源格式json的）

//生成 randkey 函数
function encryptMD5($data)
{
    $content = '';
    if (!$data || !is_array($data)) {
        return $content;
    }
    ksort($data);
    foreach ($data as $key => $value) ;
    {
        $content = $content . $key . $value;
    }
    if (!$content) {
        return $content;
    }

    return sub_encryptMD5($content);
}

function sub_encryptMD5($content)
{
    global $RPC_KEY;
    $content = $content . $RPC_KEY;
    $content = md5($content);
    if (strlen($content) > 10) {
        $content = substr($content, 0, 10);
    }
    return $content;
}

//例子
$data = array('mod' => 'Business', 'act' => 'login', 'platform' => 'tocar', 'uid' => '13671301110');
$randkey = encryptMD5($data);
$_REQUEST = array('randkey' => $randkey, 'c_version' => '0.0.1', 'parameter' => json_encode($data));


//获取配置数据（公开权限）OK
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'get_conf'
		platform: 'tocar'
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功
	sub_desc	//sub_code 描述	
	data:
		zhifubao_notify_url: 	http://123.57.249.74/pay/zhifubao_notify.php	//支付宝异步回调服务器地址
		wechat_notify_url:   	http://123.57.249.74/pay/wechat_notify_url.php	//微信异步回调服务器地址

//	获取订单id
request:
	randkey
	c_version
	parameter 
		mod: 'Business'
		act: 'get_out_trade_no'
		platform: 'tocar'
		total_fee:   //金额
		notify_url :  1 //回调 地址url
		call_back_param : "{ "act": "add", "uid": 17701360024, "money": 1100 }" //回传参数
response:
	code //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0 成功
	sub_desc	//sub_code 描述	
	data:
		out_trade_no: 	1453171903596	//订单号
		ailipay_url:

//各个模块 需要实现 的接口  （公开权限）OK
request:
	randkey
	c_version
	parameter
		mod: 'Business'
		act: 'remote_call_back'
		platform : 'tocar'
		out_trade_no : 1453171903596  //订单id
		total_fee: 1100               // 订单支付金额
		call_back_param : '{'act':'add','uid':17701360024,'money':1100}' //回传参数
response:
	code : 0 //是否成功 0成功
	desc	//描述
	sub_code	//出错类型 0=>'成功',sub_code_1'=>'参数错误','sub_code_2'=>'登录错误','sub_code_3'=>'重复操作
	sub_desc	//sub_code 描述
	data: