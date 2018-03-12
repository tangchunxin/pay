<?php

/*

*/

class Business
{
	private $log = './log/business.log';

	//支付宝
	private function _remote_call_back($params)
	{
		//远程回调
		do {
			if (!isset($params['out_trade_no']) || !$params['out_trade_no']
			|| !isset($params['total_fee']) || !$params['total_fee']
			|| !isset($params['notify_url']) || !$params['notify_url']
			|| !isset($params['call_back_param']) || !$params['call_back_param']
			)
			{
				$return = false; break;
			}

			$data_request = array(
			'mod' => 'Business'
			, 'act' => 'remote_call_back'
			, 'platform' => 'tocar'
			, 'out_trade_no' => $params['out_trade_no']
			, 'total_fee' => $params['total_fee']
			, 'call_back_param' => $params['call_back_param']
			);
			$randkey = encryptMD5($data_request);
			$url = $params['notify_url'] . "?randkey=" . $randkey . "&c_version=0.0.1";
			$result = json_decode(https_request($url, array('parameter' => json_encode($data_request))), true);
			if (!$result || !isset($result['code']) || $result['code'] != 0 || (isset($result['sub_code']) && $result['sub_code'] != 0) )
			{
				logger($this->log, "【_remote_call_back】:\n" . var_export($result, true) . "\n" . __LINE__ . "\n");
				$return = false; break;
			}
			else
			{
				$return = true;
			}

		}while (false);

		return $return;
	}

	//微信
	private function _wx_remote_call_back($params)
	{
		//远程回调
		do {
			if (!isset($params['out_trade_no']) || !$params['out_trade_no']
			|| !isset($params['total_fee']) || !$params['total_fee']
			|| !isset($params['notify_url']) || !$params['notify_url']
			|| !isset($params['call_back_param']) || !$params['call_back_param']
			)
			{
				$return = false; break;
			}

			$data_request = array(
			'mod' => 'Business'
			, 'act' => 'remote_call_back'
			, 'platform' => 'gfplay'
			, 'out_trade_no' => $params['out_trade_no']
			, 'buy_status' => 6         //微信支付
			, 'total_fee' => $params['total_fee']
			, 'call_back_param' => $params['call_back_param']
			);
			$randkey = encryptMD5($data_request);
			$url = $params['notify_url'] . "?randkey=" . $randkey . "&c_version=0.0.1";
			$result = json_decode(https_request($url, array('parameter' => json_encode($data_request))), true);
			if (!$result || !isset($result['code']) || $result['code'] != 0 || (isset($result['sub_code']) && $result['sub_code'] != 0) )
			{
				logger($this->log, "【_remote_call_back】:\n" . var_export($result, true) . "\n" . __LINE__ . "\n");
				$return = false; break;
			}
			else
			{
				$return = true;
			}

		}while (false);

		return $return;
	}

	private function _del_arr_pay()
	{
		//今天是否执行 此操作
		$itime = time();
		$rawsqls = array();
		do {
			$mcobj = getMC();
			$out_trade_no_arr = array();

			$obj_is_del_today_factory = new kxIsDelTodayFactory($mcobj,date("Y-m-d"));
			if($obj_is_del_today_factory->initialize() && $obj_is_del_today_factory->get())
			{
				$obj_is_del_today = $obj_is_del_today_factory->get();
				if($obj_is_del_today->is_del == 0)
				{
					$obj_pay_status_list_factory = new kxPayStatusListFactory($mcobj, 0);
					if ($obj_pay_status_list_factory->initialize() && $obj_pay_status_list_factory->get())
					{
						$obj_pay_multi_factory = new kxPayMultiFactory($mcobj, $obj_pay_status_list_factory);
						if ($obj_pay_multi_factory->initialize())
						{
							$obj_pay_multi = $obj_pay_multi_factory->get();
							foreach ($obj_pay_multi as $obj_pay_multi_item)
							{
								if($obj_pay_multi_item->expire_time < $itime)
								{
									$out_trade_no_arr[] = $obj_pay_multi_item->out_trade_no;
								}
							}
						}
					}
					$obj_is_del_today->is_del = 1;
				}
				elseif($obj_is_del_today->is_del == 1)
				{
					return false;
				}
			}

			if($out_trade_no_arr)
			{
				$rawsqls[] = kxPay::delArrSql(implode(',',$out_trade_no_arr));
			}

			if ($rawsqls && !execute_sql_backend($rawsqls))
			{
				logger($this->log, "【_del_arr_pay】:\n" . var_export($rawsqls, true) . "\n" . __LINE__ . "\n");
				return false;
			}

			$obj_is_del_today_factory->writeback();

		}while(false);

		return true;
	}

	///////////////////////////////////////////////////////////////////////////////
	////////////////////////////公开权限///////////////////////////////////////

	//	读取配置信息（公开权限）
	public function get_conf()
	{
		global $ZHIFUBAO_NOTIFY;
		global $WEIXIN_NOTIFY;
		$response = array('code' => kxRespCode::OK, 'desc' => __LINE__, 'sub_code' => 0);
		//$rawsqls = array();
		//$itime = time();
		$data = array();

		do {
			$data['zhifubao_notify'] = $ZHIFUBAO_NOTIFY;
			$data['weixin_notify'] = $WEIXIN_NOTIFY;
			$response['data'] = $data;
		} while (false);

		return $response;
	}

	//支付宝获取订单号
	public function get_out_trade_no($params)
	{
		global $ZHIFUBAO_NOTIFY, $WEIXIN_NOTIFY;

		$response = array('code' => kxRespCode::OK, 'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$ailipay_url = '';
		do {
			//参数校验
			if (empty($params['notify_url'])
			|| empty($params['call_back_param'])
			|| empty($params['total_fee'])
			)
			{
				$response['code'] = kxRespCode::ERROR; $response['desc'] = __line__; break;
			}

			$data['zhifubao_notify'] = $ZHIFUBAO_NOTIFY;
			$data['weixin_notify'] = $WEIXIN_NOTIFY;

			//删除过期&&没有支付的订单
			$this->_del_arr_pay();

			$obj_pay = new kxPay();
			$obj_pay->notify_url = $params['notify_url'];
			$obj_pay->call_back_param = $params['call_back_param'];
			$obj_pay->status = 0;
			$obj_pay->notify_status = 0;
			$obj_pay->expire_time = $itime + 6048000;
			$obj_pay->init_time = $itime;
			$obj_pay->update_time = $itime;

			$rawsqls[] = $obj_pay->getInsertSql();

			$error = '';
			if ($rawsqls && !($result = execute_sql_backend($rawsqls,$error)))
			{
				logger($this->log, "【rawsqls】:\n" . var_export($error, true) . "\n" . __LINE__ . "\n");
				$response['code'] = kxRespCode::ERROR; $response['desc'] = __line__; break;
			}
			elseif($rawsqls && $result[0]['insert_id'])
			{
				$data['out_trade_no'] = $result[0]['insert_id'];
			}
			else
			{
				$response['code'] = kxRespCode::ERROR_UPDATE; $response['desc'] = __line__; break;
			}

            //支付宝//////////////////////
            if(!empty($params['total_fee']) && !empty($data['out_trade_no']))
            {
	            $params['out_trade_no'] = $data['out_trade_no'];
	            $result = make_alipay_url($params);
	            if(!$result )
	            {
	            	logger($this->log, "【make_alipay_url】:\n" . var_export($result, true) . "\n" . __LINE__ . "\n");
					$return = false; break;
	            }
            	$data['ailipay_url'] = $result;
            }
            //微信///////////////////////
			$response['data'] = $data;
		} while (false);

		return $response;
	}

	//支付宝  回调处理
	public function zhifubao_handle($params)
	{
		$response = array('code' => kxRespCode::OK, 'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();

		do {
			$mcobj = getMC();
			$obj_pay_multi_factory = new kxPayMultiFactory($mcobj,null,$params['out_trade_no']);

			if($obj_pay_multi_factory->initialize() && $obj_pay_multi_factory->get())
			{
				$obj_pay_multi = $obj_pay_multi_factory->get();
				if($obj_pay_multi && is_array($obj_pay_multi))
				{
					$obj_pay = current($obj_pay_multi);
					if($obj_pay->status == 0)
					{
						if($this->_remote_call_back(array('out_trade_no'=>$params['out_trade_no'],'total_fee'=>$params['total_fee'],'notify_url'=>$obj_pay->notify_url ,'call_back_param'=>$obj_pay->call_back_param )))
						{
							$obj_pay->total_fee = $params['total_fee'];
							$obj_pay->status = 1;
							$obj_pay->notify_status = 1;

							$obj_pay->third_trade_no = $params['trade_no'];
							$obj_pay->third_party_type = 1;
							$obj_pay->third_notify_info = json_encode($params);
							$obj_pay->update_time = $itime;

							$rawsqls[] = $obj_pay->getUpdateSql();
						}
						else
						{
							logger($this->log, "【ERROR】:\n" . var_export("remote_call_back fail:".$params, true) . "\n" . __LINE__ . "\n");
							$response['code'] = kxRespCode::ERROR; $response['desc'] = __line__; break;
						}
					}
					elseif($obj_pay->status == 1)
					{
						$response['code'] = kxRespCode::OK; $response['desc'] = __line__; break;
					}
				}
				else
				{
					$response['code'] = kxRespCode::ERROR; $response['desc'] = __line__; break;
				}
			}
			else
			{
				$response['code'] = kxRespCode::ERROR; $response['desc'] = __line__; break;
			}

			if ($rawsqls && !execute_sql_backend($rawsqls))
			{
				logger($this->log, "【rawsqls】:\n" . var_export($rawsqls, true) . "\n" . __LINE__ . "\n");
				$response['code'] = kxRespCode::ERROR; $response['desc'] = __line__; break;
			}
			$obj_pay_multi_factory->writeback();

		} while (false);

		return $response;
	}

	////////////微信支付 开始///////////////////

	//微信获取订单号
	public function wx_get_out_trade_no($params)
	{
		//global $WEIXIN_NOTIFY;

		$response = array('code' => kxRespCode::OK, 'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$ailipay_url = '';
		do {
			//参数校验
			if (empty($params['notify_url'])
			|| empty($params['call_back_param'])
			|| empty($params['total_fee'])
			|| empty($params['openId'])
			|| empty($params['wxpay'])
			)
			{
				$response['code'] = kxRespCode::ERROR; $response['desc'] = __line__; break;
			}

			require("./extend/".$params['wxpay']."/lib/WxPay.Data.php");
			require("./extend/".$params['wxpay']."/lib/WxPay.Api.php");
			require("./extend/".$params['wxpay']."/lib/WxPay.JsApiPay.php");
			//$data['weixin_notify'] = $WEIXIN_NOTIFY;

			//删除过期&&没有支付的订单
			$this->_del_arr_pay();

			$obj_pay = new kxPay();
			$obj_pay->notify_url = $params['notify_url'];
			$obj_pay->call_back_param = $params['call_back_param'];
			$obj_pay->status = 0;
			$obj_pay->notify_status = 0;
			$obj_pay->expire_time = $itime + 6048000;
			$obj_pay->init_time = $itime;
			$obj_pay->update_time = $itime;

			$rawsqls[] = $obj_pay->getInsertSql();

			$error = '';
			if ($rawsqls && !($result = execute_sql_backend($rawsqls,$error)))
			{
				logger($this->log, "【rawsqls】:\n" . var_export($error, true) . "\n" . __LINE__ . "\n");
				$response['code'] = kxRespCode::ERROR; $response['desc'] = __line__; break;
			}
			elseif($rawsqls && $result[0]['insert_id'])
			{
				$data['out_trade_no'] = $result[0]['insert_id'];
			}
			else
			{
				$response['code'] = kxRespCode::ERROR_UPDATE; $response['desc'] = __line__; break;
			}

            //支付宝//////////////////////

            //微信///////////////////////

            $call_back_param = json_decode($params['call_back_param'], true);

            if(isset($params['type']) && $params['type'] == 2)
            {
	            $obj_pay_order = new WxPayUnifiedOrder();

				$obj_pay_order->SetBody("游戏房卡");//body商品描述
				$obj_pay_order->SetAttach($params['call_back_param']);      //attach附加数据
				$obj_pay_order->SetOut_trade_no($data['out_trade_no']);     //out_trade_no商户订单号
				$obj_pay_order->SetTotal_fee($params['total_fee'] );         //total_fee金额
				$obj_pay_order->SetTime_start(date("YmdHis")); //time_start订单生成时间

				$obj_pay_order->SetTime_expire(date("YmdHis", time() + 600));//time_expire订单失效时间
				if(!empty($params['trade_type_app']))  //APP 支付需要用到
				{
					$obj_pay_order->SetTrade_type("APP");                 //trade_type设置取值如下：JSAPI，NATIVE，APP，详细说明见参数规定
					//$obj_pay_order->SetOpenid($params['openId']);
				}
				else
				{
					$obj_pay_order->SetTrade_type("JSAPI");                 //trade_type设置取值如下：JSAPI，NATIVE，APP，详细说明见参数规定
					$obj_pay_order->SetOpenid($params['openId']);
				}
				$obj_pay_order->SetProduct_id($call_back_param['aid']);//product_id
				$result_wxpayapi = WxPayApi::unifiedOrder($obj_pay_order);
				logger($this->log, "【data】:\n" . var_export($result_wxpayapi, true) . "\n" . __LINE__ . "\n");

	           //生产jsapi的参数 和签名
	            $obj_jsapi = new JsApiPay();

	            $UnifiedOrderResult['appid'] = $result_wxpayapi['appid'];
	            $UnifiedOrderResult['prepay_id'] = $result_wxpayapi['prepay_id'];

	            //$result = $obj_jsapi->GetJsApiParameters($UnifiedOrderResult);
	            if(!empty($params['trade_type_app']))  //APP 支付需要用到
				{
	            	$result = $obj_jsapi->GetAppParameters($UnifiedOrderResult);
				}
				else
				{
					$result = $obj_jsapi->GetJsApiParameters($UnifiedOrderResult);
				}

            }
			$data =  $result;
			$response['data'] = $data;
			//logger($this->log, "【data】:\n" . var_export($data, true) . "\n" . __LINE__ . "\n");

		} while (false);

		return $response;
	}

	//微信查询支付订单
	// public function get_reder_query($params)
	// {
	// 	$response = array('code' => kxRespCode::OK, 'desc' => __LINE__, 'sub_code' => 0);
	// 	$rawsqls = array();
	// 	$itime = time();
	// 	$data = array();

	// 	do {
	// 		//参数校验
	// 		if (empty($params['transaction_id'])
	// 		)
	// 		{
	// 			$response['code'] = kxRespCode::ERROR; $response['desc'] = __line__; break;
	// 		}

	// 	    if(isset($params["transaction_id"]) && $params["transaction_id"] != "")
	// 	    {
	// 			$transaction_id = $params["transaction_id"];
	// 			$input = new WxPayOrderQuery();
	// 			$input->SetTransaction_id($transaction_id);

	// 			$result = WxPayApi::orderQuery($input);
	// 		}

	// 		// if(isset($params["out_trade_no"]) && $params["out_trade_no"] != "")
	// 		// {
	// 		// 	$out_trade_no = $params["out_trade_no"];
	// 		// 	$input = new WxPayOrderQuery();
	// 		// 	$input->SetOut_trade_no($out_trade_no);
	// 		// 	$result = WxPayApi::orderQuery($input);
	// 		// }

	//        	if($result['result'] == 'FAIL' || isset($result['err_code']) || isset($result['err_code_des']))
	//        	{
	// 			logger($this->log, "【rawsqls】:\n" . var_export($result, true) . "\n" . __LINE__ . "\n");
	//        		$response['sub_code'] = 1; $response['desc'] = __line__;
	//        	}

	//         $data['result'] = json_encode($result);

	// 		$response['data'] = $data;
	// 	} while (false);

	// 	return $response;
	// }

	//微信回调处理
	public function weixin_handle($params)
	{
		$response = array('code' => kxRespCode::OK, 'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		//logger($this->log, "【rawsqls】:\n" . var_export($params, true) . "\n" . __LINE__ . "\n");
		do {
			$mcobj = getMC();
			$obj_pay_multi_factory = new kxPayMultiFactory($mcobj,null,$params['out_trade_no']);

			if($obj_pay_multi_factory->initialize() && $obj_pay_multi_factory->get())
			{
				$obj_pay_multi = $obj_pay_multi_factory->get();
				if($obj_pay_multi && is_array($obj_pay_multi))
				{
					$obj_pay = current($obj_pay_multi);
					if($obj_pay->status == 0)
					{
						if($this->_wx_remote_call_back(array('out_trade_no'=>$params['out_trade_no'],'total_fee'=>$params['total_fee'],'notify_url'=>$obj_pay->notify_url ,'call_back_param'=>$obj_pay->call_back_param )))
						{
							$obj_pay->total_fee = $params['total_fee'];
							$obj_pay->status = 1;
							$obj_pay->notify_status = 1;

							$obj_pay->third_trade_no = $params['trade_no'];
							$obj_pay->third_party_type = 2;
							$obj_pay->third_notify_info = json_encode($params);
							$obj_pay->update_time = $itime;

							$rawsqls[] = $obj_pay->getUpdateSql();
						}
						else
						{
							logger($this->log, "【ERROR】:\n" . var_export("_wx_remote_call_back fail:".$params, true) . "\n" . __LINE__ . "\n");
							$response['code'] = kxRespCode::ERROR; $response['desc'] = __line__; break;
						}
					}
					elseif($obj_pay->status == 1)
					{
						$response['code'] = kxRespCode::OK; $response['desc'] = __line__; break;
					}
				}
				else
				{
					$response['code'] = kxRespCode::ERROR; $response['desc'] = __line__; break;
				}
			}
			else
			{
				$response['code'] = kxRespCode::ERROR; $response['desc'] = __line__; break;
			}

			if ($rawsqls && !execute_sql_backend($rawsqls))
			{
				logger($this->log, "【rawsqls】:\n" . var_export($rawsqls, true) . "\n" . __LINE__ . "\n");
				$response['code'] = kxRespCode::ERROR; $response['desc'] = __line__; break;
			}
			$obj_pay_multi_factory->writeback();

		} while (false);

		return $response;
	}

	//微信检查  订单号是否交易成功
	public function judge_out_trade_no($params)
	{
		$response = array('code' => kxRespCode::OK, 'desc' => __LINE__, 'sub_code' => 0);
		$rawsqls = array();
		$itime = time();
		$data = array();
		$ailipay_url = '';
		do {
			//参数校验
			if (empty($params['out_trade_no']))
			{
				$response['code'] = kxRespCode::ERROR; $response['desc'] = __line__; break;
			}
			$mcobj = getMC();

		 	$obj_pay_multi_factory = new kxPayMultiFactory($mcobj,null,$params['out_trade_no']);
			if($obj_pay_multi_factory->initialize() && $obj_pay_multi_factory->get())
			{
				$obj_pay_multi = $obj_pay_multi_factory->get();
				if($obj_pay_multi && is_array($obj_pay_multi))
				{
					$obj_pay = current($obj_pay_multi);
					if($obj_pay->status != 0 )
					{
						$response['code'] = 1; $response['desc'] = __line__; break;
					}
				}
				else
				{
					$response['code'] = kxRespCode::ERROR; $response['desc'] = __line__; break;
				}
			}
			else
			{
				$response['code'] = kxRespCode::ERROR; $response['desc'] = __line__; break;
			}

			$response['data'] = $data;
		} while (false);

		return $response;
	}

}

