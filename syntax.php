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
class syntax_plugin_dtableremote extends DokuWiki_Syntax_Plugin {

    function getPType(){
       return 'block';
    }

    function getType() { return 'substition'; }
    function getSort() { return 32; }


    function connectTo($mode) {
	$this->Lexer->addSpecialPattern('\[dtable.*?\]',$mode,'plugin_dtableremote');
    }

    function handle($match, $state, $pos, &$handler) {
	//remove [dtable
	$match = substr($match, 7);
	$match = substr($match,0, -1);
	$match = trim($match);

	$dtableremote =& plugin_load('helper', 'dtableremote');
	return $dtableremote->syntax_parse($match);

    }

    function render($mode, &$renderer, $data) {
	global $ID;
	if($mode == 'xhtml')
	{
	    if(!plugin_isdisabled('dtable'))
	    {
		$dtable  =& plugin_load('helper', 'dtable');

		$MAX_TABLE_WIDTH = $this->getConf('max_table_width');

		$NAZWA_BAZY = $data['file'];
		$NAGLOWKI = $data['fileds']['all'];
		$KOLUMNY_Z_DATAMI = $data['fileds']['date'];
		$SUBMIT_WIDTH = 60;
		$INPUT_WIDTH = floor(($MAX_TABLE_WIDTH-$SUBMIT_WIDTH)/count($NAGLOWKI))-5;//border około 5px;

		$dtableremote =& plugin_load('helper', 'dtableremote');


		$baza = $dtableremote->db_path($NAZWA_BAZY);
		$baza_meta = $dtableremote->db_meta_path($NAZWA_BAZY);

		if(!is_dir($dtableremote->file_path()))
		{
		    mkdir($dtableremote->file_path(), 0755, true);
		}

		//creata base
		if(!file_exists($baza)) {
		    $handle = fopen($baza, 'w+');
		    fclose($handle);
		} 

		//this data should be cached
		$handle = fopen($baza_meta, 'w');
		$naglowki_md5 = $dtableremote->md5_array($NAGLOWKI);
		$data_md5 = $dtableremote->md5_array($KOLUMNY_Z_DATAMI);
		fwrite($handle, json_encode(array($naglowki_md5, $data_md5)));
		fclose($handle);

		if (auth_quickaclcheck($ID) >= AUTH_EDIT) 
		{

		    //$renderer->doc .= '<form class="dtable_form" id="dtable_form_'.$NAZWA_BAZY.rand(1,1000000).'" action="'.$DOKU_BASE.'lib/exe/ajax.php" method="post">';
		    $renderer->doc .= '<form class="dtable" id="dtable_'.$NAZWA_BAZY.'" action="'.$DOKU_BASE.'lib/exe/ajax.php" method="post">';

		    $renderer->doc .= '<input type="hidden" name="table" value="'.$NAZWA_BAZY.'" >';
		    $renderer->doc .= '<input type="hidden" name="call" value="dtableremote" >';
		    $renderer->doc .= '<input type="hidden" class="dtable_action" name="add" value="-1" >';

		    $renderer->doc .= '<input type="hidden" name="id" value="'.$ID.'">';

		}
		    $renderer->doc .= '<table><tr>';
		    foreach($NAGLOWKI as $v)
		    {
		      $renderer->doc .= "<th>$v</th>";
		    }
		    $renderer->doc .= '</tr>';

		    $renderer->doc .= '<tr class="form_row" style="';
		    if(count(file($baza)) != 0)
			$renderer->doc .='display:none;';
		    $renderer->doc .= '">';
			foreach($NAGLOWKI as $v)
			{
			  if(is_array($KOLUMNY_Z_DATAMI) && in_array($v, $KOLUMNY_Z_DATAMI))
			    $renderer->doc .= '<td><input type="date" name="'.md5($v).'" style="width: '.$INPUT_WIDTH.'px" /></td>';
			  else
			    $renderer->doc .= '<td><textarea name="'.md5($v).'" style="width: '.$INPUT_WIDTH.'px; height: 100px;"></textarea></td>';
			}

			$renderer->doc .= '</tr>';
			$CON_TO_PRA = '<html>';//content to dokuwkiki parser
			$handle = fopen($baza, 'r');
		      
		    if (!$handle) 
		      exit($this->getLang('db_error'));

			while (($bufor = fgets($handle)) !== false) {
			    $dane = explode($dtableremote->separator(), $bufor);
				$CON_TO_PRA .= '<tr></html>';
				for($i=1;$i<sizeof($dane);$i++)
				{
				    $CON_TO_PRA .= '<html><td></html>'.$dane[$i].'<html></td></html>';
				}
				$CON_TO_PRA .= '<html></tr>';
			}
			if (!feof($handle)) {
			   $CON_TO_PRA .= $this->getLang('db_error');
			}
			fclose($handle);

		    $CON_TO_PRA .= '</table></html>';

		    $renderer->doc .= $dtableremote->parse(str_replace('<br>', "\n", str_replace($dtableremote->separator_en(), $dtableremote->separator(), $CON_TO_PRA)));
		if (auth_quickaclcheck($ID) >= AUTH_EDIT) 
		    $renderer->doc .= '</form>';


		} else
		{
		    $renderer->doc .= 'To use this plugin you have to install dtable first visit <a href="http://www.dokuwiki.org/plugins:dtable">www.dokuwiki.org/plugins:dtable</a> for more information';
		}
		return true;
	    }
        return false;
    }
}
