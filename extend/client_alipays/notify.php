<?php
/* *
 *功能：支付宝即时到账交易接口调试入口页面
 *版本：3.4
 *日期：2016-03-08
 *说明：
 *以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 */
if(true)
{
	error_reporting(7);
	error_reporting(E_ALL|E_STRICT);
	ini_set('display_errors', 'on');
}

date_default_timezone_set('Asia/Chongqing');
	
require_once("alipay.config.php");
require_once("lib/alipay_notify.class.php");

require_once("../../inc/base.func.php");

//计算得出通知验证结果
$alipayNotify = new AlipayNotify($alipay_config);
$verify_result = $alipayNotify->verifyNotify();

if($verify_result)
{//验证成功
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//请在这里加上商户的业务逻辑程序代
	
	//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
	
    //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
	
	//商户订单号
	$out_trade_no = $_POST['out_trade_no'];

	//支付宝交易号
	$trade_no = $_POST['trade_no'];

	//交易状态
	$trade_status = $_POST['trade_status'];

    if($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS')
    {
		//判断该笔订单是否在商户网站中已经做过处理
			//如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
			//如果有做过处理，不执行商户的业务程序
			
			$data_request = array();
			$data_request['mod'] = 'Business';
			$data_request['act'] = 'zhifubao_handle';
			$data_request['platform'] = 'tocar';
			$data_request['out_trade_no'] = $out_trade_no;
			$data_request['trade_no'] = $trade_no;
			
			$data_request['total_fee'] = $_POST['total_fee'];
			$data_request['buyer_email'] = $_POST['buyer_email'];
			$data_request['payment_type'] = $_POST['payment_type'];
			$data_request['gmt_create'] = $_POST['gmt_create'];
			$data_request['notify_time'] = $_POST['notify_time'];
			
			$data_request['gmt_payment'] = $_POST['gmt_payment'];
			$data_request['notify_id'] = $_POST['notify_id'];
			
			$randkey = encryptMD5($data_request);
			$url = $PAY_PATH . "?randkey=" . $randkey . "&c_version=0.0.1";
			$result = json_decode(https_request($url, array('parameter' => json_encode($data_request))), true);
			if (!$result || !isset($result['code']) || $result['code'] != 0 || (isset($result['sub_code']) && $result['sub_code'] != 0) )
			{
				logResult(var_export($result,true));
				logResult(__LINE__);
				echo "fail";
			}
			else
			{
				echo "success";
			}
			
		//注意：
		//退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知
		//请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的

        //调试用，写文本函数记录程序运行情况是否正常
        //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
   }
	//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
        
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
else
{
	logResult(__LINE__);
    //验证失败
    echo "failaa";

   
}
?>