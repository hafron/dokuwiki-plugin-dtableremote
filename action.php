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
class action_plugin_dtableremote extends DokuWiki_Action_Plugin {

    function register(&$controller) {
	    $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE',  $this, 'handle_ajax');
	    $controller->register_hook('IO_WIKIPAGE_WRITE', 'BEFORE',  $this, 'export_dtable');
    }
    function export_dtable(&$event, $parm)
    {
	$lines = explode("\n", $event->data[0][1]);
	$new_lines = array();
	foreach($lines as $line)
	{
	    if(preg_match('/^\[export[\s]+dtable/', $line))  
	    {

		//remove [export 
		$line = substr($line, 7);
		$line = trim($line);

		$dtable_str = $line;

		$dtable_str = substr($dtable_str, strpos($dtable_str, 'dtable')+7);
		$dtable_str = substr($dtable_str, 0, -1);//remove ]

		//leave [dtable as code
		$new_lines[] = '  [dtable '.$dtable_str.']';

		$h_dtable =& plugin_load('helper', 'dtableremote');
		$data = $h_dtable->syntax_parse($dtable_str);

		$new_lines[] = '';
		$header = '';
		foreach($data['fileds']['all'] as $head)
		{
		    $header .= '^'.$head;
		}	
		$header .= '^';
		$new_lines[] = $header;

		$baza = $h_dtable->db_path($data['file']);
		$rows = file($baza);
		$new_row = '';
		foreach($rows as $row)
		{
		    $new_row = '';
		    //remove last \n
		    $row = substr($row, 0, -1);
		    $dane = explode($h_dtable->separator(), $row);
		    for($i=1;$i<sizeof($dane);$i++)
		    {
			$dane[$i] = trim(str_replace('<br>', '\\\\ ', $dane[$i]));
			if(strlen($dane[$i]) <= 0)
			    $new_row .= '| ';
			else 
			    $new_row .= '|'.$dane[$i];
		    }

		$new_lines[] = $new_row.'|';
		}

	    } else
	    {
		$new_lines[] = $line;
	    }
	}
	$event->data[0][1] = implode("\n", $new_lines);
    }
    
    function handle_ajax(&$event, $param)
    {
	if($event->data != 'dtableremote') return;
	$event->preventDefault();
	$event->stopPropagation();

	$dtable =& plugin_load('helper', 'dtable');
	$dtableremote =& plugin_load('helper', 'dtableremote');


	    $baza = $dtableremote->db_path($_POST['table']);
	    $baza_meta = $dtableremote->db_meta_path($_POST['table']);

	    if(isset($_POST['add']))
	    {
		$after_line = $_POST['add'];

		$lines = file($baza);

		$line .= '-'.$dtableremote->separator();
		$handle = fopen($baza, 'w+');
		if (!$handle) 
		    exit($dtable->error('db_error', true));

		    $conf_file = file($baza_meta);

		    if (!$conf_file) 
			exit($dtable->error('db_error', true));

		    $conf = json_decode($conf_file[0]);
		    $heads = $conf[0];

		    $in_fileds = array();

		    foreach($heads as $v)
		    {  
			$value = str_replace($dtableremote->separator(), 
					     $dtableremote->separator_en(),
					     str_replace("\n", '<br>', $_POST[$v])
					    );
			$in_fileds[] = $dtableremote->parse($value);

		       $line .= $value.$dtableremote->separator();
		    }
		    $line = substr($line, 0, -1);
		    $line .= "\n";
		    if($after_line == -1)
			array_unshift($lines, $line);

			foreach ($lines as $k => $file_line) { 
			    fwrite( $handle, "$file_line"); 
			    if($k == $after_line)
				fwrite( $handle, "$line"); 
			}

		    fclose($handle);

		    echo json_encode(array('type' => 'success', 'fileds' => $in_fileds));

	    } elseif(isset($_POST['get']))
	    {
		$id = (int)$_POST['get'];
		$lines = file($baza);

		if(!$lines) 
		    exit($dtable->error('db_error', true));

	        foreach ($lines as $key => $file_line) { 
		    if($key == $id)
		    {
			$dane = explode($dtableremote->separator(), $file_line);
			array_shift($dane);
			foreach($dane as $k => $d)
			{
			    $dane[$k] = str_replace($dtableremote->separator_en(), 
					     $dtableremote->separator(),
					     str_replace('<br>', "\n", $d)
					    );

			}
			echo json_encode($dane);
			break;
		    }
		}
	    } elseif(isset($_POST['edit']))
	    {
	    $id = (int)$_POST['edit'];
	    $lines = file($baza);

	    if(!$lines) 
		exit($dtable->error('db_error', true));

	    $line .= $id.$dtableremote->separator();
	    
	    $conf_file = file($baza_meta);

	    if (!$conf_file) 
		exit($dtable->error('db_error', true));

	    $conf = json_decode($conf_file[0]);
	    $heads = $conf[0];

	    $in_fileds = array();
	    foreach($heads as $v)
	    {  
		$value = str_replace($dtableremote->separator(), $dtableremote->separator_en(), 
					 str_replace("\n", '<br>', $_POST[$v]));
	        $line .= $value.$dtableremote->separator();

		$in_fileds[] = $dtableremote->parse($value);
	    }
	    $line = substr($line, 0, -1);
	    $line .= "\n";

	    $handle = fopen($baza, 'w+');
	    if (!$handle) 
		exit($dtable->error('db_error', true));

	      foreach ($lines as $k => $file_line) { 
		if($k != $id)
		{
		  fwrite( $handle, "$file_line");
		} else
		{
		  fwrite($handle, "$line");
		}
	      }
	      fclose($handle);
	      echo json_encode(array('type' => 'success', 'id' => $id, 'fileds' => $in_fileds));

	    } elseif(isset($_POST['remove']))
	    {
		$id = $_POST['remove'];
		$lines = file($baza);
		
		if(!$lines) 
		  exit($dtable->error('db_error', true));

		$handle = fopen($baza, 'w+');
		if (!$handle) 
		    exit($dtable->error('db_error', true));

		  foreach ($lines as $k => $file_line) { 
		    if($k != $id)
		    {
			fwrite( $handle, "$file_line");
		    }
		  }
		  fclose($handle);
		  echo json_encode(array('type' => 'success'));

	    }
    }
}
