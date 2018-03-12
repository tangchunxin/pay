<?php
header("Access-Control-Allow-Origin：*");
header("Access-Control-Allow-Headers DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type");
header("Access-Control-Allow-Methods GET,POST,OPTIONS");
    
require_once('./inc/head.inc.php');
require_once('./inc/base.func.php');
error_reporting(7);
error_reporting(E_ALL|E_STRICT);
ini_set('display_errors', 'on');
$response = array('code' => kxRespCode::OK,'desc' => __LINE__);

//模块名
$modules = array(
	'Business' => './controller/business.php'
);
do {
	$requests = array_merge($_GET, $_POST, $_COOKIE, $_REQUEST );
	
	if( isset($requests['parameter']) )
	{
		$requests['parameter'] = urldecode($requests['parameter']);
		$parameter = json_decode($requests['parameter'], true);

		if( null === $parameter || false === $parameter )
		{
			$response['code'] = kxRespCode::ERROR; $response['desc'] = __LINE__; break;
		}
	}
	else
	{
		var_dump($requests);
		$response['code'] = kxRespCode::ERROR; $response['desc'] = __LINE__; break;
	}

	if( !isset( $parameter ) )
	{
		$response['code'] = kxRespCode::ERROR; $response['desc'] = __LINE__; break;
	}
	$params = array();
	foreach( $parameter as $key => $value )
	{
		$params[$key] = $value;
		if($value == 'undefined')
		{
			$params[$key] = '';
		}
	}

	if( !isset($params['mod']) || !isset($params['act']) )
	{
		$response['code'] = kxRespCode::ERROR;
		$response['desc'] = __LINE__; break;
	}

	if(
		( !isset($requests['c_version']) || $requests['c_version'] != kxConstant::C_VERSION )
		&& isset($params['act']) && $params['act'] != 'get_conf'
		&& (!isset($requests['user_end']) || !$requests['user_end'])
		&& (!isset($requests['randkey']) || !$requests['randkey'])
	)
	{
		$response['code'] = kxRespCode::ERROR_VERSION;
		$response['desc'] = __LINE__; break;
	}

	$bVerified = false;
	//后台模块耦合验证
	if(isset($requests['randkey']) && $requests['randkey'] != '' && encryptMD5($params) == $requests['randkey'])
	{
		$bVerified = true;
	}
	//logger('./log/business.log', "【_del_arr_pay】:\n" . var_export($params, true) . "\n" . __LINE__ . "\n");

	//前台调用接口开放
	$module = $params['mod'];
	$action = $params['act'];

	if(  $module == 'Business' && (
         $action == 'get_conf'
         || $action == 'wx_get_out_trade_no'
        )
	)
	{
		//不需要校验的接口
		$bVerified = true;
	}
	//第三种接口验证
	elseif( isset($requests['user_end']) && $requests['user_end'] )
	{//后台管理协议 使用 key 校验
		if(isset($params['back_key']) && $params['back_key'] && $params['back_key']==$API_KEY)
		{
			$bVerified = true;
		}
	}

	if( !$bVerified )
	{
		$response['code'] = kxRespCode::ERROR_VERIFY; $response['desc'] = __LINE__; break;
	}

	if( !isset($modules[$module]) )
	{
		$response['code'] = kxRespCode::ERROR;$response['desc'] = __LINE__; break;
	}

	require($modules[$module]);

	$obj = new $module();
	if( !method_exists($obj, $action) )
	{
		$response['code'] = kxRespCode::ERROR;$response['desc'] = __LINE__.$action; break;
	}

	$response = $obj->$action($params);

	//	if( kxRespCode::OK == $response['code'] )
	//	{
	//		// 如果操作正确,执行任务,统计更新
	//	}

	if($DEBUG)
	{
		$response['memory_usage'] = memory_get_usage(); 
	}
	
	if( isset($response['sub_code']) && $response['sub_code'] )
	{
		$subCode = new kxSubCode();
		if(isset($subCode->desc[$module.'_'.$action]['sub_code_'.$response['sub_code']]))
		{
			$response['sub_desc'] = $subCode->desc[$module.'_'.$action]['sub_code_'.$response['sub_code']];
		}
	}
	else
	{
		$response['sub_code'] = 0;
	}

	if( isset($module) && $module )
	{
		$response['module'] = $module;
	}

	if( isset($action) && $action )
	{
		$response['action'] = $action;
	}


}while(false);

closeDB();

if(!isset($response['data']['html']))
{
	output($response);
}
else
{
	//	output_html($response['data']['html']);
}
