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

/**
 *  DtCSS - the DtTvB's CSS Macro
 *  This is a library file.
 *  This file is need by the main file.
 *  That means, without me,
 *  the main file won't work.
 *  And the main file won't work without me.
 *  You know. SVN version.
 */

/* This is a comment. */
/* What are the comments doing here? */
/* Well... I need to document the code so that when I come back to read the code,
   I don't forget everything. */
/* I don't use classes so much. No classes are created in this file. */

/* This is a regular expression to tokenize CSS syntaxes. */
define ('DTCSS_MATCH', '~
		"(?:\\\\.|[^"])*"                   # double quotes
	|	\'(?:\\\\.|[^\'])*\'                # single quotes
	|   /\\*([\\s\\S]*?)\\*/                # comments
	|   <\\#(?:[\s\S]+?)\\#>
	|	(?:mix|shade|lighter|darker|variant)\\s*
			\\(
	|   \\([^\\)]*\\)                       # match parens
	|   //.*
	|   \\w+                                # words
	|	[^{},;:"\'\\[\\]\\s\\(\\)/]+        # string
	|   [\\s\\S]
		~x');

include dirname(__FILE__) . '/libdtexpression.php';

/* This global variable holds the list of available HTML colors */
$DtCSS__colors = array();

/* Call this function to use DtCSS
   Pass the following argument:
	 + file   -->  the file name of the css file you want to preprocess
	 |             if you don't want one just use the __FILE__ magic constant.
     + css    -->  the css source code you want to preprocess
	 + macros -->  an associative array of predefined macros
*/
function DtCSS($file, $css, $macros = array()) {
	
	/* First process the macros */
	$css = DtCSS__preprocess($file, $css, $macros);

	/* Tokenize the processed CSS */
	if (!preg_match_all(DTCSS_MATCH, $css, $tokens, PREG_PATTERN_ORDER)) {
		return '';
	}

	/* Tokens -> Structures */
	$next = DtCSS__firstpass($tokens[0]);

	/* Structures -> Rules */
	$list = array();
	DtCSS__secondpass ($next, '', $list, $macros);

	/* Ok now we have the rules.
	   Let's output it nicely. */
	$copies = array();
	$out = '';

	/* For each rules... Why should I type this? */
	foreach ($list as $v) {

		/* What is this? */
		if (empty($v)) continue;
		$out .= "\n";
		if (count($v) > 0) {

			/* Get the selector. See if it is a template. */
			$selector = trim(substr($v[0], 0, -1));
			$current = '';
			$is_copy = 0;
			$cname = '';

			/* Now we have the templates! */
			if (substr($selector, 0, 1) == '%') {
				$cname = trim(substr($selector, 1)); 
				$current = '/* % ' . $cname . ' */';
				$is_copy = 1;
			}

			/* Now go through each declarations. */
			foreach ($v as $w) {

				$c = trim($w);

				/* If it is not an opening or closing brace then indent it a bit. */
				if (substr($c, -1, 1) != '{' && $c != '}') {
					$current .= "\n";
					$current .= '    ';

				/* I don't want braces in templates. */
				} else if ($is_copy) {
					continue;

				/* But we are not in a template. */
				} else {
					$current .= "\n";
				}

				/* We are using a template. */
				if (preg_match('~^use\\s*:\\s*(.*);$~', trim($w), $m)) {
					$cpname = $m[1];
					if (isset($copies[$cpname])) {
						$current .= trim($copies[$cpname]);
					} else {
						$current .= '/* ' . $m[0] . ' -- not found */';
					}

				/* Wait, we are not. */
				} else {
				
					/* Let's see if we can make more rules out of it. */
					if (preg_match('~^([a-z0-9\\-\\s,]+):([\\s\\S]+;)~i', $c, $m)) {
						
						$properties = DtCSS__splitproperties($m[1]);
						$shall_pad = 0;
						foreach ($properties as $vv) {
							$current .= ($shall_pad ? "\n    " : '') . $vv . ':' . $m[2];
							$shall_pad = 1;
						}
						
					} else {
						$current .= $c;
					}
					
				}

			}

			/* Output them or add to template. */
			if ($is_copy) {
				$copies[$cname] = $current . "\n    /* $cname % */";
			} else {
				$out .= $current;
			}

		}
	}

	return $out;

}

/* Make writing properties easier */
function DtCSS__splitproperties($x) {

	/* Split CSS */
	
	/* First pass split with comma. */
	$o = array();
	$t = explode(',', $x);
	foreach ($t as $v) {
		
		/* top left right bottom can be grouped together. */
		$v = trim($v);
		if (preg_match('~(?:(?:(?:^|\\-)(?:left|top|right|bottom)){2,4})~i', $v, $m, PREG_OFFSET_CAPTURE)) {
			$string = $m[0][0];
			$offset = $m[0][1];
			if (substr($string, 0, 1) == '-') {
				$string = substr($string, 1);
				$offset += 1;
			}
			$splitter = explode('-', $string);
			foreach ($splitter as $w) {
				$o[] = substr($v, 0, $offset) . $w . substr($v, $offset + strlen($string));
			}
		} else {
			$o[] = $v;
		}
		
	}
	
	return $o;
	
}

/* This functions loads the color list from */
function DtCSS__loadcolors() {

	global $DtCSS__colors;
	$fp = fopen(dirname(__FILE__) . '/DtCSS__colorlist.txt', 'r');
	while (!feof($fp)) {
		$l = trim(fgets($fp));
		if (substr($l, 0, 1) == '#' || trim($l) == '') {
			continue;
		}
		$d = preg_split('~\\s+~', $l);
		$DtCSS__colors[strtolower($d[0])] = array($d[1] / 255, $d[2] / 255, $d[3] / 255);
	}

}

/* Ok. Let's do it now. I made this function because
   I don't want to screw any other global variables.. */
DtCSS__loadcolors ();

/* Some color related function.
   This function converts the color in RGB and returns the same color in HLS.
   This function was taken from GTK+'s gtkstyle.c
   Pass these:
	 - R [0.0 - 1.0]
	 - G [0.0 - 1.0]
	 - B [0.0 - 1.0]
   Outputs array:
	 - H [0.0 - 359.99999999999999999999999999999999999999999999999999999....]
	 - L [0.0 - 1.0]
	 - S [0.0 - 1.0]
   */
function DtCSS__rgb_to_hls($r, $g, $b) {

	$max = max($r, $g, $b);
	$min = min($r, $g, $b);

	$l = ($max + $min) / 2;
	$s = $h = 0;

	if ($max != $min) {
		if ($l < 0.5)
			$s = ($max - $min) / ($max + $min);
		else
			$s = ($max - $min) / (2 - $max - $min);
		$d = $max - $min;
		if ($r == $max)
			$h = ($g - $b) / $d;
		else if ($g == $max)
			$h = 2 + ($b - $r) / $d;
		else if ($b == $max)
			$h = 4 + ($r - $g) / $d;
		$h *= 60;
		if ($h < 0) $h += 360;
	}

	return array($h, $l, $s);

}

/* Some color related function.
   This function converts the color in RGB and returns the same color in HLS.
   This function was taken from GTK+'s gtkstyle.c
   Pass these:
	 - H [0.0 - 359.99999999999999999999999999999999999999999999999999999....]
	 - L [0.0 - 1.0]
	 - S [0.0 - 1.0]
   Outputs array:
	 - R [0.0 - 1.0]
	 - G [0.0 - 1.0]
	 - B [0.0 - 1.0]
   */
function DtCSS__hls_to_rgb($h, $l, $s) {
	if ($l < 0.5) 
		$n = $l * (1 + $s);
	else
		$n = $l + $s - $l * $s;
	$m = 2 * $l - $n;
	if ($s == 0) {
		return array($l, $l, $l);
	}
	$hue = $h + 120;
	while ($hue > 360) $hue -= 360;
	while ($hue < 0) $hue += 360;
	if ($hue < 60)
		$r = $m + ($n - $m) * $hue / 60;
	else if ($hue < 180)
		$r = $n;
	else if ($hue < 240)
		$r = $m + ($n - $m) * (240 - $hue) / 60;
	else
		$r = $m;
	$hue = $h;
	while ($hue > 360) $hue -= 360;
	while ($hue < 0) $hue += 360;
	if ($hue < 60)
		$g = $m + ($n - $m) * $hue / 60;
	else if ($hue < 180)
		$g = $n;
	else if ($hue < 240)
		$g = $m + ($n - $m) * (240 - $hue) / 60;
	else
		$g = $m;
	$hue = $h - 120;
	while ($hue > 360) $hue -= 360;
	while ($hue < 0) $hue += 360;
	if ($hue < 60)
		$b = $m + ($n - $m) * $hue / 60;
	else if ($hue < 180)
		$b = $n;
	else if ($hue < 240)
		$b = $m + ($n - $m) * (240 - $hue) / 60;
	else
		$b = $m;
	return array($r, $g, $b);
}

/* This function replaces the macro. Text in strings will not be processed. */
function DtCSS__replacemacros($v, &$macro) {
	if (preg_match_all(DTCSS_MATCH, $v, $tokens, PREG_PATTERN_ORDER)) {
		$n = '';
		foreach ($tokens[0] as $w) {
			if (isset($macro[$w])) {
				$n .= $macro[$w];
			} else {
				$n .= $w;
			}
		}
		$v = $n;
	}
	return $v;
}

/* The preprocess function.
   Takes the arguments like the same as DtCSS. */
function DtCSS__preprocess($file, $text, &$macro) {

	/* Split it into lines first. */
	$x = explode("\n", $text);
	$o = array();
	$level = 0;
	
	foreach ($x as $v) {

		/* ifdef, ifndef, and endif
		   supports nested */
		if (strtolower(trim($v)) == '#endif') {
			if ($level > 0) {
				$level --;
			}
			continue;
		} else if (strtolower(trim($v)) == '#else') {
			if ($level == 1) {
				$level --;
			} else if ($level <= 0) {
				$level ++;
			}
			continue;
		} else if (preg_match('~^[#]if(n?)expr\\s+(.*)$~is', trim($v), $m)) {
			if ($level > 0) {
				$level ++;
			} else {
				$isset = DtCSS__DtExpression($m[2], $macro);
				$negate = strtolower($m[1]) == 'n';
				if ($negate) $isset = !$isset;
				if (!$isset) {
					$level ++;
				}
			}
			continue;
		} else if (preg_match('~^[#]if(n?)def\\s+(\\w+)$~is', trim($v), $m)) {
			if ($level > 0) {
				$level ++;
			} else {
				$isset = isset($macro[$m[2]]);
				$negate = strtolower($m[1]) == 'n';
				if ($negate) $isset = !$isset;
				if (!$isset) {
					$level ++;
				}
			}
			continue;
		}
		if ($level > 0) continue;

		/* Define a macro. Allows an earlier defined macros to be used in the content.
		   The name won't be processed. */
		if (preg_match('~^[#]define\\s+(\\w+)\\s+(.*)$~is', ltrim($v), $m)) {
			$macro[$m[1]] = DtCSS__replacemacros($m[2], $macro);

		/* Include that file. But before that the file must be processed first. */
		} else if (preg_match('~^[#]include\\s*"([^"]+)"$~is', trim($v), $m)) {
			$fn = dirname($file) . '/' . $m[1];
			if (file_exists($fn)) {
				/* Fcking recursive call. */
				/* I think this can lead to endless loop. */
				$o[] = DtCSS__preprocess($fn, file_get_contents($fn), $macro);
			} else {
				$o[] = '/* Warning: #include "' . $m[1] . '" -- not found */';
			}

		/* Nothing. */
		} else {
			$o[] = DtCSS__replacemacros($v, $macro);
		}
	}
	return implode("\n", $o);
}

/* Tokenize and split the selector using ',' */
function DtCSS__splitselector($sel) {

	/* Ok? */
	if (!preg_match_all(DTCSS_MATCH, $sel, $tokens, PREG_PATTERN_ORDER)) {
		return array();
	}

	/* Split it!!!! */
	$o = array();
	$b = '';
	foreach ($tokens[0] as $v) {
		if ($v == ',') {
			$o[] = trim($b);
			$b = '';
		} else {
			$b .= $v;
		}
	}

	/* Any craps left? */
	if (trim($b) != '') 
		$o[] = trim($b);
	
	/* Work done. */
	return $o;

}

/* Combines a single selector "a" with "b" */
function DtCSS__combinesingle($a, $b) {

	// fixed by S
	if (strtolower(substr($b, 0, 4)) == 'self') return $a . substr($b, 4);
	elseif (strtolower(substr($b, 0, 8)) == '__self__') return $a . substr($b, 8);
	
	if (trim($a) == '') return $b;
	return $a . ' ' . $b;

}

/* Combines selectors in "a" with "b"
   Multiple selectors maybe. */
function DtCSS__combineselector($a, $b) {
	if (trim($a) == '') return DtCSS__combinesingle('', $b);
	$ta = DtCSS__splitselector($a);
	$tb = DtCSS__splitselector($b);
	$tc = array();
	$la = count($ta);
	$lb = count($tb);
	for ($i = 0; $i < $la; $i ++) {
		for ($j = 0; $j < $lb; $j ++) {
			$tc[] = DtCSS__combinesingle($ta[$i], $tb[$j]);
		}
	}
	return implode(', ', $tc);
}

/* Parse the color and output as an RGB array. */
function DtCSS__parsecolor($c) {
	$c = preg_replace('~\\s~', '', strtolower(trim($c)));
	if (preg_match('~^#([a-f0-9])([a-f0-9])([a-f0-9])$~', $c, $m)) {
		return array(
			(hexdec($m[1]) * 17) / 255,
			(hexdec($m[2]) * 17) / 255,
			(hexdec($m[3]) * 17) / 255
		);
	}
	if (preg_match('~^#([a-f0-9][a-f0-9])([a-f0-9][a-f0-9])([a-f0-9][a-f0-9])$~', $c, $m)) {
		return array(
			(hexdec($m[1])) / 255,
			(hexdec($m[2])) / 255,
			(hexdec($m[3])) / 255
		);
	}
	if (preg_match('~rgb\\((\\d+),(\\d+),(\\d+)\\)~', $c, $m)) {
		return array(
			($m[1] / 255),
			($m[2] / 255),
			($m[3] / 255)
		);
	}
	if (preg_match('~rgb\\((\\d+)[%],(\\d+)[%],(\\d+)[%]\\)~', $c, $m)) {
		return array(
			($m[1] / 100),
			($m[2] / 100),
			($m[3] / 100)
		);
	}
	global $DtCSS__colors;
	if (isset($DtCSS__colors[$c]))
		return $DtCSS__colors[$c];
	return array(0, 0, 0);
}

/* The shade function from GTK+ */
function DtCSS__shade($factor, $color) {
	$hls = DtCSS__rgb_to_hls($color[0], $color[1], $color[2]);
	$hls[1] = min(1, max(0, $hls[1] * $factor));
	$hls[2] = min(1, max(0, $hls[2] * $factor));
	return DtCSS__hls_to_rgb($hls[0], $hls[1], $hls[2]);
}

/* Into 0 or 1
   Factor = 0 returns old.
   Factor < 0 goes towards 0.
   Factor > 0 goes towards 1. */
function DtCSS__into($factor, $old) {
	if ($factor > 1)  $factor = 1;
	if ($factor < -1) $factor = -1;
	if ($factor > 0) {
		return $old + ((1 - $old) * $factor);
	}
	if ($factor < 0) {
		return $old + ((0 - $old) * abs($factor));
	}
	return $old;
}

/* Expression parsing. Returns color in RGB.
   Fun time. */
function DtCSS__expression($text) {

	/* This is an expression! Tokenize it. */
	if (preg_match('~^(mix|shade|lighter|darker|variant)\\(([\\s\\S]*)\\)$~', $text, $m)) {
		if (!preg_match_all(DTCSS_MATCH, $m[2], $tokens, PREG_PATTERN_ORDER)) {
			return array(0, 0, 0);
		}

		/* Find out the
			 (i)  Function name
			 (ii) List of arguments. Maybe nested. */
		$buffer = '';
		$list = array();
		$level = 0;
		$buf = '';
		foreach ($tokens[0] as $v) {
			if (preg_match('~^(lighter|darker|shade|mix|variant)\\s*\\($~s', $v)) {
				if ($level == 0) {
					$buf = $v;
				} else {
					$buf .= $v;
				}
				$level ++;
				continue;
			} else if ($v == ')') {
				$buf .= $v;
				$level --;
				if ($level == 0) {
					$buffer .= $buf;
					$buf = '';
				}
				continue;
			} else if ($level > 0) {
				$buf .= $v;
				continue;
			}
			if ($v == ',') {
				$list[] = $buffer;
				$buffer = '';
			} else {
				$buffer .= $v;
			}
		}
		if ($buffer != '') $list[] = $buffer;

		/* Now for each function... */
		if ($m[1] == 'mix') {
			$color1 = DtCSS__expression($list[1]);
			$color2 = DtCSS__expression($list[2]);
			return array(
				$color2[0] + (($color1[0] - $color2[0]) * $list[0]),
				$color2[1] + (($color1[1] - $color2[1]) * $list[0]),
				$color2[2] + (($color1[2] - $color2[2]) * $list[0])
			);
		}
		if ($m[1] == 'lighter') {
			return DtCSS__shade(1.3, DtCSS__expression($list[0]));
		}
		if ($m[1] == 'darker') {
			return DtCSS__shade(0.7, DtCSS__expression($list[0]));
		}
		if ($m[1] == 'shade') {
			return DtCSS__shade($list[0], DtCSS__expression($list[1]));
		}
		if ($m[1] == 'variant') {
			$color = DtCSS__expression($list[3]);
			$hls = DtCSS__rgb_to_hls($color[0], $color[1], $color[2]);
			$my_h = ($list[0]) - 0;
			$my_s = ($list[1]) / 100;
			$my_l = ($list[2]) / 100;
			$hls[0] += $my_h;
			while ($hls[0] > 360) $hls[0] -= 360;
			while ($hls[0] < 0)   $hls[0] += 360;
			$hls[1] = DtCSS__into($my_l, $hls[1]);
			$hls[2] = DtCSS__into($my_s, $hls[2]);
			return DtCSS__hls_to_rgb($hls[0], $hls[1], $hls[2]);
		}
	}
	return DtCSS__parsecolor($text);
}

/* Changes the value from 0.0 - 1.0 to 00 - ff */
function DtCSS__hexcolor($x) {
	$v = dechex(floor($x * 255));
	if (strlen($v) < 2) return '0' . $v;
	return $v;
}

/* Make HTML color code from array of 0.0 - 1.0. */
function DtCSS__makecolor($a) {
	return '#' . DtCSS__hexcolor($a[0]) . DtCSS__hexcolor($a[1]) . DtCSS__hexcolor($a[2]);
}

/* This thing evaluates an expression and returns the result.
   It also process macros. Really powerful!
   It uses libdtexpression.php that comes with this package. */
function DtCSS__DtExpression($expr, &$macros) {
	$d = '';
	$dtexsub = $expr;
	if (preg_match_all(DTEXPR_MATCH, $dtexsub, $dtextokens, PREG_SET_ORDER)) {
		$dtexcode = '';
		foreach ($dtextokens as $dtexv) {
			if (isset($macros[$dtexv[0]])) {
				$mct = $macros[$dtexv[0]];
				if (!is_numeric($mct)) {
					$mct = DtExpression__quotestring($mct);
				}
				$dtexcode .= $mct;
			} else {
				$dtexcode .= $dtexv[0];
			}
			$dtexcode .= ' ';
		}
		//$d = $dtexcode;
		$d = DtExpression($dtexcode);
	}
	return $d;

}

/* This function parses the nested CSS structures and explode it. */
function DtCSS__secondpass($tokens, $old_selector, &$output, &$macros) {
	$my_sel = array();
	$output[] = &$my_sel;
	$selector = '';
	$level = 0;
	$buffer = '';

	/* We are not in the top. We have a rule. Add it. */
	if ($old_selector != '')
		$my_sel[] = $old_selector . ' {';

	/* Go through the tokens */
	foreach ($tokens as $v) {
		if (is_array($v)) {

			/* If it's an array it means it's a structure.
			   Combine the selectors and put in the rules. */
			$sel = trim($selector);
			$sel = DtCSS__combineselector($old_selector, $sel);
			DtCSS__secondpass ($v, $sel, $output, $macros);
			$selector = '';

		} else if (substr($v, 0, 2) == '//') {

			/* Line comments. Change them to multiline comments. */
			$my_sel[] = '/* ' . trim(substr(($v), 2)) . ' */';

		} else if (substr($v, 0, 2) == '/*') {

			/* Comments */
			$my_sel[] = $v;

		} else if (substr($v, 0, 2) == '<#') {

			$selector .= DtCSS__DtExpression(substr(substr($v, 2), 0, -2), $macros);

		} else if ($v == ';') {

			/* We have it. */
			$my_sel[] = $selector . ';';
			$selector = '';

		} else if (preg_match('~^(lighter|darker|shade|mix|variant)\\s*\\($~s', $v)) {
			
			/* We found the function. Find its closing. */
			if ($level > 0) {
				$buffer .= $v;
				$level ++;
			} else {
				$buffer = $v;
				$level ++;
			}

		} else if ($level > 0 && $v == ')') {

			/* Almost */
			$level --;
			$buffer .= $v;
			if ($level == 0) {
				/* I found that stupid closing brace!
				   It's perfectly nested!
				   So now I can parse the expression!!! HAHA! */
				$selector .= DtCSS__makecolor(DtCSS__expression(preg_replace('~\\s~', '', $buffer)));
			}

		} else {

			/* Normal syntax */
			if ($level > 0) {
				$buffer .= $v;
			} else {
				$selector .= $v;
			}

		}
	}

	/* Need more? */
	if (trim($selector) != '') {
		$my_sel[] = trim($selector) . ';';
	}

	/* A root level does not need a closing brace. */
	if ($old_selector != '')
		$my_sel[] = '}';

}

/* Parses nested structures into ... 
   yeah
   something like nested array.
   
   Right it's nested array. */
function DtCSS__firstpass(&$tokens) {

	$cnt = count($tokens);
	$buffer = array();
	$level = 0;
	for ($i = 0; $i < $cnt; $i ++) {
		$c = $tokens[$i];
		if ($c == '{') {
			if ($level > 0) {
				$level ++;
				$add[] = $c;
			} else {
				$level ++;
				$add = array();
			}
		} else if ($c == '}' && $level > 0) {
			$level --;
			if ($level > 0) {
				$add[] = $c;
			} else {
				$buffer[] = DtCSS__firstpass($add);
			}
		} else {
			if ($level > 0) {
				$add[] = $c;
			} else {
				$buffer[] = $c;
			}
		}
	}

	return $buffer;

}

?>
