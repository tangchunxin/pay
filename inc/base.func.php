<?php

/*
base.func.php 提供公用函数
*/

//function check_char($content)
//{
//	$preg = "([\\<>&\"\'\.\*\r\n])+";
//	return !(ereg($preg, $content));
//}


function time4str($itime)
{
	if ($itime) {
		return date('m-d H:i', $itime);
	}
	return false;
}

function time2str($itime)
{
	if ($itime) {
		return date('Y-m-d H:i:s', $itime);
	}
	return false;
}

function time3str($itime)
{
	if ($itime) {
		return date('Y.m.d H:i:s', $itime);
	}
	return false;
}

function time2str_day($itime = 0)
{
	if ($itime) {
		return date('Y.m.d', $itime);
	}
	return date('Y.m.d');
}

function time4str_day($itime = 0)
{
	if ($itime) {
		return date('ymd', $itime);
	}
	return date('ymd');
}

function microtime_float()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

function getkxmonday()
{
	return (int)date("Ymd", mktime(0, 0, 0, date('m'), date('d') - (date('w') == 0 ? 6 : date('w') - 1), date('Y')));
}

function getkxtimeday($itime = 0)
{
	if (!$itime) {
		$itime = time();
	}
	return mktime(0, 0, 0, date('m', $itime), date('d', $itime), date('Y', $itime));
}

function getkxday($itime = null)
{
	if (!$itime) {
		$itime = time();
	}
	return (int)date('Ymd', $itime);
}

function output($response)
{

	header('Cache-Control: no-cache, must-revalidate');
	header("Content-Type: text/json; charset=UTF-8");

	if (isset($_REQUEST['callback']) && $_REQUEST['callback']) {
		echo $_REQUEST['callback'] . '(' . json_encode($response) . ')';
	} else {
		echo json_encode($response);
	}
}

function output_html($html)
{

	header('Cache-Control: no-cache, must-revalidate');
	header("Content-Type: text/html; charset=utf-8");

	echo($html);
}

function encryptMD5($data)
{
	$content = '';
	if (!$data || !is_array($data)) {
		return $content;
	}
	ksort($data);
	foreach ($data as $key => $value)
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

//function decryptRandAuth($authKey, $data)
//{
//	$data = handleDecrypt(base64_decode($data), $authKey);
//	$content = '';
//	for( $i=0; $i<strlen($data); $i++ )
//	{
//		$md5 = $data[$i];
//		$content .= $data[++$i] ^ $md5;
//	}
//	return $content;
//}
//
//function encryptRandAuth($authKey, $data)
//{
//	$encrypt_key = md5(date("md"));
//	$ctr = 0;
//	$content = '';
//	for( $i=0;$i<strlen($data);$i++ )
//	{
//		$ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
//		$content .= $encrypt_key[$ctr].($data[$i] ^ $encrypt_key[$ctr++]);
//	}
////	$length = strlen($content) ;
//	$content = handleDecrypt($content, $authKey);
//	$content = base64_encode($content);
//	if( strlen($content) > 15 )
//	{
//		$content = substr($content, 6, 9);
//	}
//	else if( strlen($content) > 9 )
//	{
//		$content = substr($content, 0, 9);
//	}
//	return $content;
//}
//
//
//function handleDecrypt($data, $key)
//{
//	$encrypt_key = md5($key);
//	$ctr = 0;
//	$content = '';
//	for( $i=0; $i<strlen($data); $i++ )
//	{
//		$ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
//		$content .= $data[$i] ^ $encrypt_key[$ctr++];
//	}
//	return $content;
//}


function https_request($url, $data = null)
{
	$output = '';
	try{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		if (!empty($data)) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($curl);
		curl_close($curl);
	}catch (Exception $e)
	{
		$e->getMessage();
	}
	return $output;
}


// 打印log
function logger($file, $word)
{
	$fp = fopen($file, "a");
	flock($fp, LOCK_EX);
	fwrite($fp, "执行日期：" . strftime("%Y-%m-%d %H:%M:%S", time()) . "\n" . $word . "\n\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}


function get_key($arr, $val)
{
	if (!is_array($arr)) {
		return false;
	}
	foreach ($arr as $arr_item) {
		if (isset($arr_item['min']) && isset($arr_item['max'])) {
			if ($val >= $arr_item['min'] && ($arr_item['max'] == -1 || $val < $arr_item['max'])) {
				return $arr_item['key'];
			}
		}
	}
	return false;
}

function get_name($arr, $key)
{
	foreach ($arr as $arr_item) {
		if (isset($arr_item['key']) && $arr_item['key'] == $key && isset($arr_item['name'])) {
			return $arr_item['name'];
		}
	}
	return '';
}

function get_age($year, $month)
{
	$itime = time();
	$now_year = intval(date('Y', $itime));
	$now_month = intval(date('m', $itime));
	$diff_y = $now_year - $year;
	$diff_m = $now_month - $month;
	if ($diff_y >= 0 && $diff_m >= 0) {
		return round($diff_y + $diff_m/12, 1);
	} elseif ($diff_y > 0 && $diff_m < 0) {
		return round($diff_y - 1 + ($diff_m+12)/12, 1);
	} else {
		return 0;
	}
}

function get_random_id()
{
	global $_SGLOBAL;
	return ( time() . (intval($_SGLOBAL['m_secend'] * 1000)) );
}

//微信客服消息
function wx_send($wx_openid, $message)
{
	//test
	//$wx_openid = 'oDxxxuIQxSH5louOzk5yeqdX_khE';
	if (!$wx_openid || !$message) {
		return false;
	}
	$mcobj = getMC();
	$obj_token_factory = new kxWXTokenFactory($mcobj);
	if (!$obj_token_factory->initialize()) {
		return false;
	}
	$obj_token = $obj_token_factory->get();
	if (!$obj_token->access_token) {
		return false;
	}

	$wx_result = kxWXToken::send_message($wx_openid, $message, $obj_token->access_token);

	if (isset($wx_result->errcode) && $wx_result->errcode != 0) {
		logger('./log/business.log', "【wx_send】:\n" . var_export($wx_result, true) . "\n" . __LINE__ . "\n");
	}
	return $wx_result;
}


//发短信函数 阿里大鱼
function sms_cz_alidayu($templateCode, $sms_param, $phone, $signname = "活动验证")
{
	$gearmanjson = array
	(
	'template_code'=>$templateCode
	, 'sms_param'=>$sms_param
	, 'phone'=>$phone
	, 'signname'=>$signname
	);
	try
	{
		$client= new GearmanClient();
		$client->addServer('127.0.0.1', 4730);
		$client->doBackground('sms_cz', json_encode($gearmanjson));
	}catch(Exception $e){
		logger('./log/sms.log', "【Exception】:\n" . var_export($e, true) . "\n" . __LINE__ . "\n");
	}
}

//发短信函数
function sms_cz($phone, $message)
{
	$target = "http://sms.chanzor.com:8001/sms.aspx";
	//替换成自己的测试账号,参数顺序和wenservice对应
	$post_data = "action=send&userid=&account=tukakeji&password=155210&mobile=$phone&sendTime=&content=" . rawurlencode($message);
	//$binarydata = pack("A", $post_data);
	$gets = Post($post_data, $target);
	$start = strpos($gets, "<?xml");
	$data = substr($gets, $start);
	$xml = simplexml_load_string($data);
	//var_dump(json_decode(json_encode($xml),TRUE));
	//请自己解析$gets字符串并实现自己的逻辑
	//<State>0</State>表示成功,其它的参考文档

	$arr = kxXMLManager::get_object_vars_final($xml);
	logger('./log/business.log', "【sms_cz】:\n" . var_export($message . "\n" . $phone, true) . "\n" . __LINE__ . "\n");
	return $arr;
}

//畅卓短信接口
function Post($data, $target)
{
	$url_info = parse_url($target);
	$httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
	$httpheader .= "Host:" . $url_info['host'] . "\r\n";
	$httpheader .= "Content-Type:application/x-www-form-urlencoded\r\n";
	$httpheader .= "Content-Length:" . strlen($data) . "\r\n";
	$httpheader .= "Connection:close\r\n\r\n";
	//$httpheader .= "Connection:Keep-Alive\r\n\r\n";
	$httpheader .= $data;

	$fd = fsockopen($url_info['host'], 80);
	fwrite($fd, $httpheader);
	$gets = "";
	while (!feof($fd)) {
		$gets .= fread($fd, 128);
	}
	fclose($fd);
	return $gets;
}

function cmp_car_model($a, $b)
{
	if ($a->order == $b->order) {
		return 0;
	}
	if ($a->order < $b->order) {
		return -1;
	} else if ($a->order > $b->order) {
		return 1;
	}
	return 0;
}

function cmp_tech($a, $b)
{
	if ($a->update_time == $b->update_time) {
		return 0;
	}
	if ($a->update_time < $b->update_time) {
		return -1;
	} else if ($a->update_time > $b->update_time) {
		return 1;
	}
	return 0;
}

function get_id_list($sql)
{
	$return_list = array();

	if(!$sql)
	{
		return $return_list;
	}

	$records = query_sql_backend($sql);
	if($records)
	{
		while (($row = $records->fetch_row()) != false)
		{
			$return_list[] = $row[0];
		}
		$records->free();
		unset($records);
	}
	return $return_list;
}