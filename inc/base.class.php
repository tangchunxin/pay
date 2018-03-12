<?php

/*
	base.class.php
*/

class kxXMLManager {
	private static $xmlPool = array ();
	
	public static function getXMLByName($name) {
		if (! isset ( self::$xmlPool [$name] )) {
			$xml = @simplexml_load_file ( "./conf/$name.xml" );
			if ($xml !== false)
				self::$xmlPool [$name] = $xml;
		}
		if (isset ( self::$xmlPool [$name] ))
			return self::$xmlPool [$name];
		else
			return false;
	}
	
	public static function getXMLByNameXpath($name, $xpath) {
		$config = self::getXMLByName ( $name );
		if ($config) {
			return $config->xpath ( $xpath );
		}
		
		return false;
	}
	
	public static function getXMLByNameXpathId($name, $xpath, $id) {
		$config = self::getXMLByName ( $name );
		if ($config) {
			$nodes = $config->xpath ( $xpath );
			if ($nodes) {
				foreach ( $nodes as $node ) {
					if (( string ) $node->attributes ()->id == ( string ) $id)
						return $node;
				}
			}
		}
		
		return false;
	}
	
	public static function getXMLsByNameXpathAttribute($name, $xpath, $attrName, $attrVal) {
		$foundNodes = array ();
		$config = self::getXMLByName ( $name );
		if ($config) {
			$nodes = $config->xpath ( $xpath );
			if ($nodes) {
				foreach ( $nodes as $node ) {
					if ($node->attributes ()->$attrName && ( string ) $node->attributes ()->$attrName == ( string ) $attrVal) {
						$foundNodes [] = $node;
					}
				}
			}
		}
		
		return $foundNodes;
	}
	
	public static function getXMLByNameXpathAttribute($name, $xpath, $attrName, $attrVal) {
		$nodes = self::getXMLsByNameXpathAttribute ( $name, $xpath, $attrName, $attrVal );
		
		if (count ( $nodes ) > 0) {
			return $nodes [0];
		}
		
		return false;
	}
	
	//解析xml函数
	public static function getXmlData($name) {
		if ($tmp_xml_obj = kxXMLManager::getXMLByName ( $name )) {
			$arrayCode = kxXMLManager::get_object_vars_final ( $tmp_xml_obj );
			return $arrayCode;
		} else {
			return '';
		}
	}
	
	public static function get_object_vars_final($obj) {
		if (is_object ( $obj )) {
			$obj = get_object_vars ( $obj );
		}
		if (is_array ( $obj )) {
			foreach ( $obj as $key => $value ) {
			    $sub_obj = kxXMLManager::get_object_vars_final ( $value );

				$obj [$key] = $sub_obj;
			}
		}
		return $obj;
	}
}
