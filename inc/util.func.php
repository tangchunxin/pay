<?php

/*
util.func.php
*/

$gCache = array();

function getMC()
{
	//单例
	global $MC_SERVERS,$gCache;

	if( !isset($gCache['mcobj']) )
	{
		$mcobj = new kxMemcache($MC_SERVERS);
		$gCache['mcobj'] = $mcobj;
	}

	return  $gCache['mcobj'];
}

function get_client_ip()
{
	$s_client_ip = '';

	if ($_SERVER['HTTP_X_REAL_IP'])
	{
		$s_client_ip = $_SERVER['HTTP_X_REAL_IP'];
	}
	elseif ($_SERVER['REMOTE_ADDR'])
	{
		$s_client_ip = $_SERVER['REMOTE_ADDR'];
	}
	elseif (getenv('REMOTE_ADDR'))
	{
		$s_client_ip = getenv('REMOTE_ADDR');
	}
	elseif (getenv('HTTP_CLIENT_IP'))
	{
		$s_client_ip = getenv('HTTP_CLIENT_IP');
	}
	else
	{
		$s_client_ip = 'unknown';
	}
	return $s_client_ip;
}

function getDB()
{
	//单例
	global $DB_HOST, $DB_USERNAME, $DB_PASSWD, $DB_DBNAME, $gCache, $DB_PORT ;

	if( !isset($gCache['mysqli']) )
	{
		$gCache['mysqli'] = @mysqli_connect($DB_HOST, $DB_USERNAME, $DB_PASSWD, $DB_DBNAME,$DB_PORT);
		if(!$gCache['mysqli']->ping())
		{
			@$gCache['mysqli']->close();
			if (!$gCache['mysqli']->real_connect($DB_HOST, $DB_USERNAME, $DB_PASSWD, $DB_DBNAME, $DB_PORT))
			{
				return false;
			}
		}
		$gCache['mysqli']->query("set names 'utf8'");
		mb_internal_encoding('utf-8');
	}

	return  $gCache['mysqli'];
}

function closeDB()
{
	global $gCache;
	if(isset($gCache['mysqli']))
	{
		@$gCache['mysqli']->close();
	}
}

function execute_sql_backend($rawsqls, &$error = '')
{
	$result_arr = false;
	$is_rollback = false;

	if(!$rawsqls || !is_array($rawsqls))
	{
		return $result_arr;
	}

	$db_connect = getDB();
	$db_connect->autocommit(false);
	foreach ($rawsqls as $item_sql)
	{
		$result = null;
		$result = $db_connect->query($item_sql);
		if(!$result)
		{
			$error = $db_connect->error;
			if($db_connect->rollback())
			{
				$is_rollback = true;
			}
			else
			{
				$db_connect->rollback();
				$is_rollback = true;
			}
			$result_arr = false;
			break;
		}
		if($db_connect->insert_id)
		{
			$result_arr[] = array('result'=>$result, 'insert_id'=>$db_connect->insert_id);
		}
		else
		{
			$result_arr[] = array('result'=>$result);
		}
	}

	if(!$is_rollback)
	{
		$db_connect->commit();
	}
	$db_connect->autocommit(true);

	return $result_arr;
}

function query_sql_backend($rawsql)
{
	$db_connect = getDB();

	$result = $db_connect->query($rawsql);

	return $result;
}

/*
* @inout $weights : array(1=>20, 2=>50, 3=>100);
* @putput array
*/
function w_rand($weights)
{

	$r = mt_rand(1, array_sum($weights));

	$offset = 0;
	foreach ( $weights as $k => $w )
	{
		$offset += $w;
		if ($r <= $offset)
		{
			return $k;
		}
	}

	return null;
}

function my_addslashes($str)
{
	$str = str_replace(array("\r\n", "\r", "\n"), '', $str);
	return addslashes(stripcslashes($str));
}
