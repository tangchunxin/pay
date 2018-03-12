<?php

/*
common.class.php define ORM Object and it's factory
*/

/*
* kxFactory:	add memcached support to mysql,dbobj be iHashDB implementation
*
* kxMutiStoreFactory: extend kxFactory,
*				the Array data hold is not stored by one key,every element in the Array have it's own storage,and
*				another key store the index array,normally the element store key prefex
* attention:
* 		false,true,null is reserved in hash db store value
*/

class kxObject
{
	public function before_writeback()
	{
		;
	}
}

class kxMCAction
{
	const SET = 1;
	const GET = 2;
	const DEL = 3;
}

interface iHashDB
{
	// whether connection to db is ok
	public function ok();

	// get values from db
	// if keys is array,return array(keys[0] => value...) else return value
	// value of some key not exist,if keys is array,no value in return array,else return null
	public function get( $server_key, $strkey);

	// set values to db
	// if values is array,otherwise it is the key of strobj
	public function set( $server_key, $strkey, $strobj=null, $timeout=0);

	// set value to db if it not exist
	// return false if it exist else true
	public function setKeep($strkey, $strobj, $timeout=0);

	// delete values from db
	public function del($server_key, $strkey);

	//
	public function get_multi( $server_key, $keys_arr );
	//
	public function set_multi( $server_key, $values, $timeout=0 );

	public function del_multi( $server_key, $keys );

}

class kxMemcache implements iHashDB
{
	private $conn;
	private $ok;


	function __construct($servers)
	{
		$this->conn = new Memcached;
		$this->ok = false;

		$this->conn->setOption(Memcached::OPT_COMPRESSION, true);
		$this->conn->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);	//一致性hash
		$this->conn->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);

		foreach( $servers as $server )
		{
			if( $this->conn->addServer($server[0], $server[1]) !== false )
			{
				$this->ok = true;
			}
		}
	}

	public function ok()
	{
		return $this->ok;
	}

	public function flush()
	{
		if( $this->conn )
		{
			$this->conn->flush();
		}
	}

	public function get($server_key, $strkey)
	{
		if( $this->conn )
		{
			$strobjs = $this->conn->getByKey($server_key, $strkey);
			if( $strobjs !== false )
			{
				return $strobjs;
			}
			else
			{
				return null;
			}
		}
		return null;
	}

	public function  get_multi($server_key, $keys_arr )
	{
		if( $this->conn )
		{
			$objs_arr = $this->conn->getMultiByKey($server_key, $keys_arr);
			if( $objs_arr != null )
			{
				return $objs_arr;
			}
			else
			{
				return null;
			}
		}
		return null;
	}

	public function set_multi( $server_key, $values, $timeout=0 )
	{
		//$values : An array of key/value pairs to store on the server.
		if( is_array($values) )
		{
			return $this->conn->setMultiByKey( $server_key, $values, $timeout);
		}
		return false;
	}

	public function set($server_key, $strkey, $strobj=null, $timeout=0)
	{
		return $this->conn->setByKey($server_key, $strkey, $strobj, $timeout);
	}

	public function setKeep($strkey, $strobj, $timeout=0)
	{
		return $this->conn->add($strkey, $strobj, $timeout);
	}

	public function append($strkey, $strobj, $timeout=0)
	{
		//追加模式不能使用压缩
		$this->conn->setOption(Memcached::OPT_COMPRESSION, false);
		if(!$this->setKeep($strkey, $strobj, $timeout))
		{
			$this->conn->append($strkey, $strobj);
		}
		$this->conn->setOption(Memcached::OPT_COMPRESSION, true);
	}

	public function del_multi( $server_key, $keys )
	{
		if( is_array($keys) )
		{
			return $this->conn->deleteMultiByKey( $server_key, $keys);
		}
		return false;
	}

	public function del($server_key, $strkey )
	{
		return $this->conn->deleteByKey($server_key, $strkey);
	}

	public function get_result()
	{
		return $this->conn->getResultCode();
	}
}

class kxFactory
{
	protected $server_key;
	protected $objkey;
	protected $timeout;
	protected $dbobj;
	protected $obj;

	function __construct($dbobj, $server_key, $objkey, $timeout=3600)
	{
		$this->dbobj = $dbobj;
		$this->server_key = $server_key;
		$this->objkey = $objkey;
		$this->timeout = $timeout;
	}

	public function get()
	{
		return $this->obj;
	}

	public function set($obj)
	{
		return $this->obj = $obj;
	}

	public function clear()
	{
		if($this->timeout === null)
		{
			return false;
		}
		if($this->dbobj && is_object($this->dbobj)) {
			$this->dbobj->del( $this->server_key, $this->objkey);
		}
		return true;
	}

	public function writeback()
	{
		if($this->timeout === null)
		{
			return false;
		}
		if(is_object($this->obj))
		{
			$this->obj->before_writeback();
		}
		$strobj = igbinary_serialize($this->obj);
		$this->dbobj->set($this->server_key, $this->objkey, $strobj, $this->timeout);
		return true;
	}

	public function initialize()
	{
		$strobj = null;
		if($this->objkey == null || $this->server_key == null){
			//			trace('error', 'kxfactory initialize objkey null');
			return false;
		}
		if($this->timeout !== null)
		{
			$strobj = $this->dbobj->get($this->server_key, $this->objkey);
		}
		if( $strobj === false ){
			return false;
		}
		if( $strobj !== null )
		{
			$obj = igbinary_unserialize($strobj);
			if($obj !== false && $obj !== null)
			{
				$this->obj = $obj;
			}
		}
		else
		{
			$this->obj = $this->retrive();
			if( $this->obj !== null  )
			{
				$this->writeback();
			}
		}
		return ($this->obj !== null );
	}

	// if you want to retrive data from some other place,if it not store in hash db
	// please override retrive function
	public function retrive()
	{
		return null;
	}
}

class kxMutiStoreFactory extends kxFactory
{
	public $key_objfactory = null;	// key list objfactory
	public $key_obj = null;	//key list
	protected $bInitMuti = true;

	public function __construct($dbobj, $server_key, $objkey, $key_objfactory, $key_id=null, $timeout=3600)
	{
		parent::__construct($dbobj, $server_key, $objkey, $timeout);

		if($this->bInitMuti)
		{
			$this->key_objfactory = $key_objfactory;
			$this->key_objfactory->initialize();
			$this->key_obj = $this->key_objfactory->get();
		}
		elseif($key_id)
		{
			$this->key_obj = array($key_id);
		}
		else
		{
			$this->key_obj = null;
		}

		$tmp_arr = null;
		if($this->key_obj && is_array($this->key_obj))
		{
			foreach ($this->key_obj as $item)
			{
				$tmp_arr[] = $this->objkey . '_' . $item;
			}
		}
		$this->key_obj = $tmp_arr;
	}

	public function clear()
	{
		if($this->dbobj && is_object($this->dbobj)) {
			$this->dbobj->del_multi($this->server_key, $this->key_obj);	//用数组做参数，删除多个
		}
		$this->clear_key_list();	//delete key list
	}

	public function clear_key_list()
	{
		if($this->key_objfactory)
		{
			$this->key_objfactory->clear();
		}
	}

	public function initialize()
	{
		if($this->objkey == null || $this->key_obj == null || $this->server_key == null)
		{
			return false;
		}

		if($this->key_obj && is_array($this->key_obj) && $this->server_key)
		{	//key list is array
			$strobj_arr = $this->dbobj->get_multi($this->server_key, $this->key_obj);
			if($strobj_arr && is_array($strobj_arr) && count($this->key_obj) == count($strobj_arr))
			{
				$tmp_arr = null;
				foreach ($strobj_arr as $key=>$item)
				{
					$tmp_arr[$key] = igbinary_unserialize($item);
				}
				$this->obj = $tmp_arr;
			}
			else
			{	//not in cache
				$this->obj = $this->retrive();
				if( $this->obj !== null  )
				{
					if($this->bInitMuti)
					{
						$this->clear();
					}
					$this->writeback();
				}
			}
		}
		else
		{
			return false;
		}

		return ($this->obj !== null );

	}

	public function writeback($id=null)
	{
		// 如果是初始化所有对象,则分别写回
		$tmp_arr = array();
		foreach( $this->obj as $key => $obj )
		{
			if(is_object($obj))
			{
				$obj->before_writeback();
			}
			if( $id !== null && $key !== $this->objkey . '_' .$id )
			continue;
			$tmp_arr[$key] = igbinary_serialize($obj);
		}
		if($tmp_arr)
		{
			$this->dbobj->set_multi($this->server_key, $tmp_arr, $this->timeout );
		}
		unset($tmp_arr);
	}

	public function retrive()
	{
		return array();
	}
}

class kxListFactory extends kxFactory
{
	public $sql='';
	public $list_key;
	public $id_arr;

	public function __construct($dbobj, $key, $timeout = null, $id_multi_str = '' )
	{
		$this->list_key = $key;
		if($id_multi_str)
		{
			$this->id_arr = explode(',', $id_multi_str);
		}
		parent::__construct($dbobj, $this->list_key, $this->list_key, $timeout);
	}

	public function retrive()
	{
		$list_arr = array();
		$records = null;
		if($this->id_arr && is_array($this->id_arr))
		{
			return $this->id_arr;
		}
		else
		{
			if($this->sql)
			{
				$records = query_sql_backend($this->sql);
			}

			if ( $records )
			{
				while ( ($row = $records->fetch_row()) != false )
				{
					$list_arr[] = $row[0];
				}
				$records->free();
				unset($records);
				return $list_arr;
			}
		}

		return $list_arr;
	}
}

/////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////
class kxPay extends kxObject {
	const TABLE_NAME = 'pay';

	public $out_trade_no;	//订单号
	public $total_fee = 0;	//订单金额
	public $notify_url = '';	//回调url
	public $call_back_param = '';	//用于模块回传参数
	public $status = 0;	//状态 0 未支付 1 支付成功

	public $notify_status = 0;	//回调通知状态 0 未通知 1 通知成功 2通知失败
	public $third_trade_no = '';	//第三方交易号
	public $third_party_type = 0;	//第三方支付平台类别： 1 支付宝 2 微信 3 银联 等

	public $third_notify_info = '';	//第三方回调信息
	public $expire_time = 0;	//订单过期时间

	public $init_time = 0;	//创建时间
	public $update_time = 0;	//更新时间

	public function getUpdateSql() {
		return "update `pay` SET
            `total_fee`=".$this->total_fee."
            , `notify_url`='".my_addslashes($this->notify_url)."'
            , `call_back_param`='".my_addslashes($this->call_back_param)."'
            , `status`=".intval($this->status)."

            , `notify_status`=".intval($this->notify_status)."
            , `third_trade_no`='".my_addslashes($this->third_trade_no)."'
            , `third_party_type`=".intval($this->third_party_type)."
            , `third_notify_info`='".my_addslashes($this->third_notify_info)."'
            , `expire_time`=".intval($this->expire_time)."

            , `init_time`=".intval($this->init_time)."
            , `update_time`=".intval($this->update_time)."

            where `out_trade_no`=".intval($this->out_trade_no)."";
	}

	public function getInsertSql() {
		return "insert into `pay` SET

            `out_trade_no`=".intval($this->out_trade_no)."
            , `total_fee`=".$this->total_fee."
            , `notify_url`='".my_addslashes($this->notify_url)."'
            , `call_back_param`='".my_addslashes($this->call_back_param)."'
            , `status`=".intval($this->status)."

            , `notify_status`=".intval($this->notify_status)."
            , `third_trade_no`='".my_addslashes($this->third_trade_no)."'
            , `third_party_type`=".intval($this->third_party_type)."
            , `third_notify_info`='".my_addslashes($this->third_notify_info)."'
            , `expire_time`=".intval($this->expire_time)."

            , `init_time`=".intval($this->init_time)."
            , `update_time`=".intval($this->update_time)."
            ";
	}

	public function getDelSql() {
		return "delete from `pay`
            where `out_trade_no`=".intval($this->out_trade_no)."";
	}

	public static function delArrSql($ids) {
		return "delete from `pay` where `out_trade_no`in (".$ids.")";
	}

	public function before_writeback() {
		parent::before_writeback();
		return true;
	}

}

class kxPayListFactory extends kxListFactory {
	public $key = 'pay_pay_list_';
	public function __construct($dbobj, $third_trade_no = null, $id_multi_str='') {
		//$id_multi_str 是用,分隔的字符串
		if($third_trade_no) {
			$this->key = $this->key.$third_trade_no;
			$this->sql = "select `out_trade_no` from `pay` where third_trade_no='".my_addslashes($third_trade_no)."'";
			parent::__construct($dbobj, $this->key);
			return true;
		}
		elseif ($id_multi_str) {
			$this->key = $this->key.md5($id_multi_str);
			parent::__construct($dbobj, $this->key, null, $id_multi_str);
			return true;
		}
		return false;
	}
}

class kxPayStatusListFactory extends kxListFactory
{
	public $key = 'pay_pay_status_list_';
	public function __construct($dbobj, $status = null)
	{
		$this->key = $this->key.$status;
		if($status !== null)
		{
			$this->sql = "select `out_trade_no` from `pay` where status='".intval($status)."'";
		}
		parent::__construct($dbobj, $this->key);
		return true;
	}
}

class kxPayMultiFactory extends kxMutiStoreFactory {
	public $key = 'pay_pay_multi_';
	private $sql;

	public function __construct($dbobj, $key_objfactory=null, $out_trade_no=null, $key_add='') {
		if( !$key_objfactory && !$out_trade_no ){
			return false;
		}
		$this->key = $this->key.$key_add;
		$ids = '';
		if($key_objfactory) {
			if($key_objfactory->initialize()) {
				$key_obj = $key_objfactory->get();
				$ids = implode(',', $key_obj);
			}
		}
		$fields = "
            `out_trade_no`
            , `total_fee`
            , `notify_url`
            , `call_back_param`
            , `status`

            , `notify_status`
            , `third_trade_no`
            , `third_party_type`
            , `third_notify_info`
            , `expire_time`

            , `init_time`
            , `update_time`
            ";

		if( $out_trade_no != null ) {
			$this->bInitMuti = false;
			$this->sql = "select $fields from pay where `out_trade_no`=".intval($out_trade_no)."";
		}
		else{
			$this->sql = "select $fields from pay ";
			if($ids){
				$this->sql = $this->sql." where `out_trade_no` in ($ids) ";
			}
		}
		parent::__construct($dbobj, $this->key, $this->key, $key_objfactory, $out_trade_no);
		return true;
	}

	public function retrive() {
		$records = query_sql_backend($this->sql);
		if( !$records ) {
			return null;
		}

		$objs = array();
		while ( ($row = $records->fetch_row()) != false ) {
			$obj = new kxPay;

			$obj->out_trade_no = intval($row[0]);
			$obj->total_fee = $row[1];
			$obj->notify_url = ($row[2]);
			$obj->call_back_param = ($row[3]);
			$obj->status = intval($row[4]);

			$obj->notify_status = intval($row[5]);
			$obj->third_trade_no = ($row[6]);
			$obj->third_party_type = intval($row[7]);
			$obj->third_notify_info = ($row[8]);
			$obj->expire_time = intval($row[9]);

			$obj->init_time = intval($row[10]);
			$obj->update_time = intval($row[11]);

			$obj->before_writeback();
			$objs[$this->key.'_'.$obj->out_trade_no] = $obj;
		}
		$records->free();
		unset($records);
		return $objs;
	}
}

////////////////////////////////////////////////////////////////////////////
class kxIsDelToday extends kxObject
{
	public $is_del = 0;	    //0 未执行 1 执行
}

class kxIsDelTodayFactory extends kxFactory{
	const objkey = 'is_del_today_';
	public function __construct($dbobj, $date, $timeout = 86400)
	{
		$objkey    = self::objkey.$date;
		parent::__construct($dbobj, $objkey, $objkey,$timeout);
		return true;
	}

    public function retrive()
    {
        $obj = new kxIsDelToday;
        $obj->is_del = 0;
        return $obj;
    }
}