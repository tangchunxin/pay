<?php

/*
constants.ini.php
*/

class kxConstant
{

	const C_VERSION = '0.0.1';
	const CONF_VERSION = '0.0.1';
	const SECRET = 'Keep it simple stupid!';
	const CDKEY  = 'God bless you!';

	// 设置前端分页变量
	const CNT_PER_PAGE = 20;
}


class kxRespCode
{
	const OK = 0;
	const ERROR = 1;
	const ERROR_MC = 2;
	const ERROR_INIT = 3;
	const ERROR_UPDATE = 4;
	const ERROR_VERIFY = 5;
	const ERROR_ARGUMENT = 6;
	const ERROR_VERSION = 7;
}


class kxSubCode
{
	public $desc = array(
	'Business_get_reder_query' => array('sub_code_1'=>'订单查询失败')
	);
}


