<?php
error_reporting(E_ERROR);
require_once __DIR__.DIRECTORY_SEPARATOR."lib/WxPay.Api.php";
require_once __DIR__.DIRECTORY_SEPARATOR."lib/WxPay.Notify.php";
require_once("../../inc/base.func.php");
require_once("../../config.php");

class PayNotifyCallBack extends WxPayNotify
{
	public $log = "../../log/business.log";
	//public $log = __DIR__.DIRECTORY_SEPARATOR.'../log/pay.log';
	//查询订单
	public function Queryorder($transaction_id)
	{
		$input = new WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = WxPayApi::orderQuery($input);
		//Log::DEBUG("query:" . json_encode($result));
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return true;
		}
		return false;
	}
	
	//重写回调处理函数
	public function NotifyProcess($data, &$msg)
	{
		//logger($this->log, "【msg】:\n" . var_export($data, true) . "\n" . __LINE__ . "\n");
		$notfiyOutput = array();
		
		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			logger($this->log, "【msg】:\n" . var_export($msg, true) . "\n" . __LINE__ . "\n");
			return false;
		}
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
			logger($this->log, "【msg】:\n" . var_export($msg, true) . "\n" . __LINE__ . "\n");
			return false;
		}
		////////////////自己的逻辑 给玩家充值////////////////////
		else 
		{
			//验证订单是否已经处理
			if(!$this->judge_out_trade_no($data))
			{
				logger($this->log, "【msg】:\n" . var_export($this->judge_out_trade_no($data), true) . "\n" . __LINE__ . "\n");
				return true;
			}

			//微信回调处理 给自己充值
			if(!$this->weixin_handle($data))
			{
				logger($this->log, "【msg】:\n" . var_export($this->_weixin_handle($data), true) . "\n" . __LINE__ . "\n");
				return false;
			}			
		}

		return true;
	}


	//判断微信支付回调是否已经处理了
	public function judge_out_trade_no($data)
	{
		//global $API_KEY;
		//远程回调
		do {

			$data_request = array();
			$data_request['mod'] = 'Business';
			$data_request['act'] = 'judge_out_trade_no';
			//$data_request['back_key'] = $API_KEY;
			$data_request['platform'] = 'tocar';
				
			$data_request['out_trade_no'] =  $data["out_trade_no"];       //商户订单号

			$randkey = encryptMD5($data_request);  //引入自己的 base.func.php
			$url = WxPayConfig::PAY_PATH . "?randkey=" . $randkey . "&c_version=0.0.1";
			$result = json_decode(https_request($url, array('parameter' => json_encode($data_request))), true);
			if (!$result || !isset($result['code']) || $result['code'] != 0 || (isset($result['sub_code']) && $result['sub_code'] != 0) )
			{
				logger($this->log, "【_remote_call_back】:\n" . var_export($result, true) . "\n" . __LINE__ . "\n");
				$return = false; 
			}
			else
			{
				$return = true;
			}			

		}while (false);

		return $return;
	}

	//微信支付回调给代理 充值
	public function weixin_handle($data)
	{
		//远程回调
		do {

			$data_request = array();
			$data_request['mod'] = 'Business';
			$data_request['act'] = 'weixin_handle';
			$data_request['platform'] = 'tocar';
				
			$data_request['out_trade_no'] =  $data["out_trade_no"];       //商户订单号
			$data_request['trade_no'] = $data["transaction_id"];            //支付宝订单号		
			$data_request['total_fee'] = $data['total_fee']/100; //订单金额
			         
			$randkey = encryptMD5($data_request);  //引入自己的 base.func.php
			$url = WxPayConfig::PAY_PATH . "?randkey=" . $randkey . "&c_version=0.0.1";
			$result = json_decode(https_request($url, array('parameter' => json_encode($data_request))), true);
			if (!$result || !isset($result['code']) || $result['code'] != 0 || (isset($result['sub_code']) && $result['sub_code'] != 0) )
			{
				logger($this->log, "【_remote_call_back】:\n" . var_export($result, true) . "\n" . __LINE__ . "\n");
				$return = false; 
			}
			else
			{
				$return = true;
			}			

		}while (false);

		return $return;
	}

}

$notify = new PayNotifyCallBack();

$notify->Handle(false);

