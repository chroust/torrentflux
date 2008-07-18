<?php
function parse_css($file){
	$tplfile = ENGINE_ROOT.'template/css/'.$file.'.css';

	if(!$fp = fopen($tplfile, 'r')) {
		return '<!-- css template '.$tplfile.' not found or have no access!-->';
	}
	$template = fread($fp, filesize($tplfile));
	fclose($fp);
	$template = str_replace("{LF}", "<?=\"\\n\"?>", $template);
	return '<style type="text/css">'.str_replace('; ',';',str_replace(' }','}',str_replace('{ ','{',str_replace(array("\r\n","\r","\n","\t",'  ','    ','    '),"",preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!','',$template))))).'</style>';
}

function parse_js($fileArray){
	require_once ENGINE_ROOT . 'include/JavaScriptPacker.class.php';
		if(!is_array($fileArray)){
			return;
		}
	$jscontent='';
	//load all js
	foreach($fileArray as $file){
		$tplfile = ENGINE_ROOT.'js/'.$file.'.js';
		$objfile = ENGINE_ROOT . 'cache/js/js_packed' .'.js';
			if(!@$fp = fopen($tplfile, 'r')) {
				exit("$file js template file , not found or have no access!");
			}
		$jscontent .= fread($fp, filesize($tplfile));
		fclose($fp);
	}
	//$packer = new JavaScriptPacker($jscontent, '62', '1', '');
	//$template = $packer->pack();
	//unset($packer);
	$template=$jscontent;
	//write a new js file
	$objfile_dir=dirname($objfile).DIRECTORY_SEPARATOR;
	if(!file_exists($objfile_dir)){
			if(!@mkdir($objfile_dir)) {
				exit("Directory './cache/js/' not found or have no access!");
			}
	}
	if(!@$fp = fopen($objfile, 'w')) {
		exit("Directory './cache/js/' not found or have no access!");
	}
	flock($fp, 2);
	fwrite($fp, $template);
	fclose($fp);
}


function get_Template_HTML($file){
	global $lang;
	$objfile = ENGINE_ROOT.'cache/templates/'.$lang.'_'.$file.'.tpl.php';
		if(!$fp = @fopen($objfile, 'r')) {
				if(parse_template($file,1)=='exit')	return '<!--{cannot find template '.$file.'}-->';
				$fp = @fopen($objfile, 'r');
		}
	$template = str_replace('<? if(!defined(\'IN_ENGINE\')) exit(\'Access Denied\'); ?>','',fread($fp, filesize($objfile)));
	fclose($fp);
	return $template;
}

function parse_template($file,$InTemplate=0) {
	global $lang;
	$nest = 5;
	$tplfile = ENGINE_ROOT.'template/'.$file.'.htm';
	$objfile = ENGINE_ROOT.'cache/templates/'.$lang.'_'.$file.'.tpl.php';

	if(!@$fp = fopen($tplfile, 'r')) {
			if($InTemplate){
				return 'exit';
			}else{
				exit("Current template file , not found or have no access!");
			}
	}

	$template = fread($fp, filesize($tplfile));
	fclose($fp);
	
	$var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
	$const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";

	$template = preg_replace("/([\n\r]+)\t+/s", "\\1", $template);

	$template = preg_replace("/\{lang\s+(.+?)\}/ies", "languagevar('$1')", $template);

	$template = str_replace("{LF}", "<?=\"\\n\"?>", $template);

	$template = preg_replace("/\{(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+)\}/s", "<?=\\1?>", $template);

	$template = preg_replace("/[\n\r\t]*\{js\s+([a-z0-9_]+)\}[\n\r\t]*/is", " <script type=\"text/javascript\" src=\"cache/js/\\1_packed.js\"></script> ", $template);




	$template = preg_replace("/[\n\r\t]*\{css\s+([a-z0-9_]+)\}[\n\r\t]*/e", "parse_css('$1')", $template);

	$template = preg_replace("/[\n\r\t]*\{template\s+([a-z0-9_]+)\}[\n\r\t]*/e", "get_Template_HTML('$1')", $template);
	$template = preg_replace("/[\n\r\t]*\{template\s+(.+?)\}[\n\r\t]*/e", "get_Template_HTML('$1')", $template);
	$template = preg_replace("/[\n\r\t]*\{eval\s+(.+?)\}[\n\r\t]*/ies", "stripvtags('<? \\1 ?>\n','')", $template);
	$template = preg_replace("/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/ies", "stripvtags('<? echo \\1; ?>\n','')", $template);
	$template = preg_replace("/[\n\r\t]*\{elseif\s+(.+?)\}[\n\r\t]*/ies", "stripvtags('<? } elseif(\\1) { ?>\n','')", $template);
	$template = preg_replace("/[\n\r\t]*\{else\}[\n\r\t]*/is", "\n<? } else { ?>\n", $template);

	for($i = 0; $i < $nest; $i++) {
		$template = preg_replace("/[\n\r\t]*\{foreach\s+(\S+)\s+(\S+)\}[\n\r]*(.+?)[\n\r]*\{\/foreach\}[\n\r\t]*/ies", "stripvtags('\n<? if(isset(\\1) && is_array(\\1)) { foreach(\\1 as \\2) { ?>','\n\\3\n<? } } ?>\n')", $template);
		$template = preg_replace("/[\n\r\t]*\{foreach\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*(.+?)[\n\r\t]*\{\/foreach\}[\n\r\t]*/ies", "stripvtags('\n<? if(isset(\\1) && is_array(\\1)) { foreach(\\1 as \\2 => \\3) { ?>','\n\\4\n<? } } ?>\n')", $template);
		$template = preg_replace("/[\n\r\t]*\{if\s+(.+?)\}[\n\r]*(.+?)[\n\r]*\{\/if\}[\n\r\t]*/ies", "stripvtags('\n<? if(\\1) { ?>','\n\\2\n<? } ?>\n')", $template);
		$template = preg_replace("/[\n\r\t]*\{for\s+(\S+)+\s+(\S+)+\s+(\S+)\}[\n\r]*(.+?)[\n\r]*\{\/for\}[\n\r\t]*/ies", "stripvtags('\n<? for(\$\\3=\\1; \$\\3 <= \\2; \$\\3++) { ?>','\n\\4\n<? } ?>\n')", $template);
	}
	$template = preg_replace("/\{$const_regexp\}/s", "<?=\\1?>", $template);
	$template = preg_replace("/ \?\>[\n\r]*\<\? /s", " ", $template);
	$template = str_replace('<?=$clear?>','$clear',$template);
	$template = str_replace('<?=$defined?>','$defined',$template);
	$template = str_replace('<?=$E?>','$E',$template);
	$template = str_replace('<?=$time?>','$time',$template);


	$compression=array("\n","\r","\t");
	//$template = str_replace($compression,'',$template);
			$template = preg_replace("/[\n\r\t]*\{block\s+([a-zA-Z0-9_]+)\}(.+?)\{\/block\}/ies", "stripblock('\\1', '\\2')", $template);
	$objfile_dir=dirname($objfile).DIRECTORY_SEPARATOR;
	
	if(!file_exists($objfile_dir)){
			if(!@mkdir($objfile_dir)) {exit($objfile_dir);
				exit($$objfile."1Directory './cache/templates/' not found or have no access!");
			}
	}
	if(!@$fp = fopen($objfile, 'w')) {
		exit($objfile."1Directory './cache/templates/' not found or have no access!");
	}

	flock($fp, 2);
	fwrite($fp, $template);
	fclose($fp);
}

function addquote($var) {
	return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var));
}

function languagevar($var) {
	if(array_key_exists($var,$GLOBALS['language'])) {
		return $GLOBALS['language'][$var];
	} else {
		return "!$var!";
	}
}
function stripblock($var, $s) {
	$s = str_replace('\\"', '"', $s);
	$s = preg_replace("/<\?=\\\$(.+?)\?>/", "{\$\\1}", $s);
	preg_match_all("/<\?=(.+?)\?>/e", $s, $constary);
	$constadd = '';
	$constary[1] = array_unique($constary[1]);
	foreach($constary[1] as $const) {
		$constadd .= '$__'.$const.' = '.$const.';';
	}
	$s = preg_replace("/<\?=(.+?)\?>/", "{\$__\\1}", $s);
	$s = str_replace('?>', "\n\$$var .= <<<EOF\n", $s);
	$s = str_replace('<?', "\nEOF;\n", $s);
	return "<?\n$constadd\$$var = <<<EOF\n".$s."\nEOF;\n?>";
}
function stripvtags($expr, $statement) {
	$expr = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
	$statement = str_replace("\\\"", "\"", $statement);
	return $expr.$statement;
}

?>
