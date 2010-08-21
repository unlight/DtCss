<?php

/*
    DtCSS - the DtTvB's CSS Macro
    Copyright (C) 2008  Thai Pangsakulyanont

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

header ('Content-Type: text/css');
include 'libdtcss.php';

if (!isset($_GET['f'])) {
	die ('/* No ! */');
}

$filename = $_SERVER['DOCUMENT_ROOT'] . '/' . $_GET['f'];

if (file_exists($filename) && is_file($filename)
	&& strpos('/' . $filename . '/', '/../') === false) {
	$j = array();
	foreach ($_GET as $k => $v) {
		if ($k == 'PHPSESSID') continue;
		if ($k == 'f') continue;
		$k = trim(preg_replace('~[^A-Z0-9_]~', '_', strtoupper($k)), '_');
		if ($k == '') continue;
		$j[$k] = $v;
	}
	$fnhash = md5($filename) . md5(serialize($j));
	$hash = $fnhash . '-' . filemtime($filename);
	$cachebase = './cache/';
	$cachefile = $cachebase . $hash . '.cssc';
	$filedata = file_get_contents($filename);
	if (strpos($filedata, '<#') === false && strpos($filedata, '#ifexpr') === false && file_exists($cachefile)) {
		echo '/* cached */

';
		readfile ($cachefile);
	} else {
		$data = DtCSS($filename, $filedata, $j);
		$dir = opendir($cachebase);
		while ($entry = readdir($dir)) {
			if (substr($entry, 0, strlen($fnhash)) == $fnhash)  {
				unlink ($cachebase . $entry);
			}
		}
		$fp = fopen($cachefile, 'w');
		fwrite ($fp, $data); fclose ($fp);
		echo $data;
	}
} else {
	echo '/* File not found */';
}


?>
