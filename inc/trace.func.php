<?php
/**
 * @file trace.func.php
 * Output driver for writing trace messages into a text file.
 */

//////////////////////////////////////////////////////////////////////////////

define('TRACE_FORMAT',     "#%s %s [%s]");
define('TRACE_ID',         substr(uniqid(''), 5));
define('TRACE_PATH',       S_ROOT.'/../log/');
define('TRACE_FILE',       TRACE_PATH.'trace.log');
define('TRACE_MAX_STRING', 200);
/**
 *
 */
function trace($type, $msg) {
  $type = sprintf('%-7s', strtoupper($type));
  $time = _trace_format_timestamp();
  $header = sprintf(TRACE_FORMAT, TRACE_ID, $time, $type) . ' ';

  if (!is_array($msg)) {
    $output = $header . $msg;
  }
  else {
    $output = array($header . array_shift($msg));
    foreach ($msg as $line) {
      $output[] = str_repeat(' ', strlen($header)) . $line;
    }
    $output = implode("\n", $output);
  }

  if (($file = fopen(TRACE_FILE, 'ab'))) {
    fwrite($file, $output . "\n");
    fclose($file);
  }
}
function _trace_format_timestamp() {
  list($usec, $time) = explode(' ', microtime());
  return sprintf('%s.%s', strftime('%Y-%m-%d %H:%M:%S', (int)$time), substr($usec, 2, 6));
}
//////////////////////////////////////////////////////////////////////////////
// TRACE HELPERS

function trace_format_php($value) {
  switch (gettype($value)) {
    case 'NULL':
      return 'NULL';
    case 'boolean':
      return $value ? 'TRUE' : 'FALSE';
    case 'integer':
    case 'double':
      return (string)$value;
    case 'string':
      if (TRACE_MAX_STRING > 0 && strlen($value) > TRACE_MAX_STRING) {
        $suffix = TRUE;
        $value = substr($value, 0, TRACE_MAX_STRING);
      }
      $value = str_replace(array("'", "\n", "\r", "\t"), array("\\'", '\n', '\r', '\t'), $value);
      return "'$value'" . (!empty($suffix) ? '...' : '');
    case 'object':
      $class = get_class($value);
      $value = (array)$value;
      // fall through
    case 'array':
      if (array_keys($value) === range(0, sizeof($value) - 1)) {
        $array = array_map('trace_format_php', array_values($value));
      }
      else {
        $array = array();
        foreach ($value as $k => $v) {
          $array[] = trace_format_php($k) . ' => ' . trace_format_php($v);
        }
      }
      $array = implode(', ', $array);
      return (isset($class) ? "($class)" : '') . 'array(' . $array . ')';
    case 'resource':
      return 'resource(' . get_resource_type($value) . ')';
    default:
      return '<unknown>';
  }
}

//////////////////////////////////////////////////////////////////////////////
