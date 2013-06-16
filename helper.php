<?php
/**
 * Plugin Now: Inserts a timestamp.
 * 
 * @license    GPL 3 (http://www.gnu.org/licenses/gpl.html)
 * @author     Szymon Olewniczak <szymon.olewniczak@rid.pl>
 */

// must be run within DokuWiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class helper_plugin_dtableremote extends dokuwiki_plugin
{
    function getMethods(){
      $result[] = array(
	'name'   => 'md5_array',
	'desc'   => 'returns array with md5 of each value',
	'params' => array('array' => 'array'),
	'return' => array('md5_array' => 'array'),
      );
      $result[] = array(
	'name'   => 'file_path',
	'desc'   => 'returns db path',
	'params' => array('name' => 'string'),
	'return' => array('path' => 'string'),
      );
      $result[] = array(
	'name'   => 'db_path',
	'desc'   => 'returns full db path',
	'params' => array('name' => 'string'),
      );
      $result[] = array(
	'name'   => 'db_meta_path',
	'desc'   => 'returns full db path',
	'params' => array('name' => 'string'),
	'return' => array('path' => 'string'),
      );
      $result[] = array(
	'name'   => 'separator',
	'desc'   => 'csv separator',
	'params' => array(),
	'return' => array('separator' => 'string'),
      );
      $result[] = array(
	'name'   => 'separator_en',
	'desc'   => 'csv separator - utf code',
	'params' => array(),
	'return' => array('separator_en' => 'string'),
      );
      $result[] = array(
	'name'   => 'parse',
	'desc'   => 'change dokuwiki syntax to html',
	'params' => array('string' => 'string'),
	'return' => array('content' => 'string'),
      );
      $result[] = array(
	'name'   => 'syntax_parse',
	'desc'   => 'save [dtable ] syntax to array. match -> without [dtable ]',
	'params' => array('match' => 'string'),
	'return' => array('parsed' => 'array'),
      );
      return $result;
    }
    function md5_array($array)
    {
	if(count($array) == 0)
	    return $array;

	$md5_array = array();
	foreach($array as $k => $v)
	{
	    $md5_array[$k] = md5($v);
	}
	return $md5_array;	
    }
    function file_path($name='')
    {
	$base_dir = $this->getConf('bases_dir'); 
	return ($base_dir[0] != '/' ? DOKU_INC : '').$base_dir.'/'.$name;
    }
    
    function db_path($name)
    {
	return $this->file_path($name.'.txt');
    }
    function db_meta_path($name)
    {
	return $this->file_path('meta.'.$name.'.txt');
    }
    function separator()
    {
	return '\\';
    }
    function separator_en()
    {
	return '&#92;';
    }
    function parse($string)
    {
	$info = array();
	$r_str = str_replace('<br>', "\n", str_replace($this->separator_en(), $this->separator(), $string));
	return p_render('xhtml',p_get_instructions($r_str),$info);
    }
    function syntax_parse($match)
    {
	$special_cols = array('date');

	$exploded = explode(' ', $match);
	$file = $exploded[0];
	preg_match('/"(.*?)"/', $match, $res);
	$fileds = array();
	preg_match_all('/[[:alnum:]]*\(.*?\)/', $res[1], $fileds_raw);
	foreach($fileds_raw[0] as $filed)
	{
	    preg_match('/(.*?)\((.*?)\)/', $filed, $res2);
	    if(in_array($res2[1], $special_cols))
	    {
		$fileds[$res2[1]][] = $res2[2];
	    }
	    $fileds['all'][] = $res2[2];
	}
	return array('file' => $file, 'fileds' => $fileds);
    }
}

