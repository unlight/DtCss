<?php

/*
    DtExpression - the DtTvB's Expression Evaluator
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

/* The code is undocumented.
   Have fun! */

define ('DTEXPR_MATCH', '~
		("(?:\\\\.|[^"])*"
			|	\'(?:\\\\.|[^\'])*\')                 # 1 string
	|	(\\-?(?:[0-9]+)(?:\\.(?:[0-9]+))?
			|	(?:0x[0-9a-fA-F]+))                   # 2 number
	|	(\\w+)                                        # 3 words
	|	([\\-' . preg_quote('()+*/%>,<?:', '~') . ']
			|>>|<<|>=|<=|==|!=|&&|\\|\\||!|\\.)                 # 4 operators
	~sxi');

define ('DTEXPR_UNKNOWN',  0);
define ('DTEXPR_STRING',   1);
define ('DTEXPR_NUMBER',   2);
define ('DTEXPR_WORD',     3);
define ('DTEXPR_OPERATOR', 4);
define ('DTEXPR_STRUCT',   5);

$DtExpr__FunctionMap = array(
	'abs' => 'abs',
	'acos' => 'acos',
	'acosh' => 'acosh',
	'asin' => 'asin',
	'asinh' => 'asinh',
	'atan2' => 'atan2',
	'atan' => 'atan',
	'atanh' => 'atanh',
	'base_convert' => 'base_convert',
	'bindec' => 'bindec',
	'ceil' => 'ceil',
	'cos' => 'cos',
	'cosh' => 'cosh',
	'decbin' => 'decbin',
	'dechex' => 'dechex',
	'decoct' => 'decoct',
	'deg2rad' => 'deg2rad',
	'exp' => 'exp',
	'expm1' => 'expm1',
	'floor' => 'floor',
	'fmod' => 'fmod',
	'getrandmax' => 'getrandmax',
	'hexdec' => 'hexdec',
	'hypot' => 'hypot',
	'is_finite' => 'is_finite',
	'is_infinite' => 'is_infinite',
	'is_nan' => 'is_nan',
	'lcg_value' => 'lcg_value',
	'log10' => 'log10',
	'log1p' => 'log1p',
	'log' => 'log',
	'max' => 'max',
	'min' => 'min',
	'mt_getrandmax' => 'mt_getrandmax',
	'mt_rand' => 'mt_rand',
	'mt_srand' => 'mt_srand',
	'octdec' => 'octdec',
	'pi' => 'pi',
	'pow' => 'pow',
	'rad2deg' => 'rad2deg',
	'rand' => 'rand',
	'round' => 'round',
	'sin' => 'sin',
	'sinh' => 'sinh',
	'sqrt' => 'sqrt',
	'srand' => 'srand',
	'tan' => 'tan',
	'tanh' => 'tanh',
	'crc32' => 'crc32',
	'chr' => 'chr',
	'levenshtein' => 'levenshtein',
	'ltrim' => 'ltrim',
	'md5' => 'md5',
	'metaphone' => 'metaphone',
	'money_format' => 'money_format',
	'nl2br' => 'nl2br',
	'number_format' => 'number_format',
	'ord' => 'ord',
	'printf' => 'sprintf',
	'sprintf' => 'sprintf',
	'rtrim' => 'rtrim',
	'sha1' => 'sha1',
	'similar_text' => 'similar_text',
	'soundex' => 'soundex',
	'str_pad' => 'str_pad',
	'pad' => 'str_pad',
	'str_repeat' => 'str_repeat',
	'repeat' => 'str_repeat',
	'str_replace' => 'str_replace',
	'replace' => 'str_replace',
	'str_rot13' => 'str_rot13',
	'rot13' => 'str_rot13',
	'str_shuffle' => 'str_shuffle',
	'shuffle' => 'str_shuffle',
	'strcasecmp' => 'strcasecmp',
	'casecmp' => 'strcasecmp',
	'strchr' => 'strchr',
	'strcmp' => 'strcmp',
	'cmp' => 'strcmp',
	'strip_tags' => 'strip_tags',
	'stristr' => 'stristr',
	'strnatcasecmp' => 'strnatcasecmp',
	'natcasecmp' => 'strnatcasecmp',
	'strnatcmp' => 'strnatcmp',
	'natcmp' => 'strnatcmp',
	'strrev' => 'strrev',
	'rev' => 'strrev',
	'reverse' => 'strrev',
	'strrchr' => 'strrchr',
	'strstr' => 'strstr',
	'substr_replace' => 'substr_replace',
	'substr' => 'substr',
	'mid' => 'substr',
	'trim' => 'trim',
	'ucfirst' => 'ucfirst',
	'ucwords' => 'ucwords',
	'date' => 'date',
	'gmdate' => 'gmdate',
	'mktime' => 'mktime',
	'gmmktime' => 'gmmktime',
	'strftime' => 'strftime',
	'gmstrftime' => 'gmstrftime',
	'time' => 'time',
	'strtotime' => 'strtotime',
	'strtolower' => 'strtolower',
	'tolower' => 'strtolower',
	'lower' => 'strtolower',
	'strtoupper' => 'strtoupper',
	'toupper' => 'strtoupper',
	'upper' => 'strtoupper',
	'str' => 'strval',
	'strval' => 'strval',
	'int' => 'intval',
	'intval' => 'intval',
	'float' => 'floatval',
	'floatval' => 'floatval',
	'escape' => 'DtExpression__quotestring',
);

function DtExpression($data) {
	$tokens = DtExpression__Tokenize($data);
	$struct = DtExpression__MakeStruct($tokens);
	$code   = DtExpression__GenerateCode($struct);
	return @eval('return ' . $code . ';');
}

function DtExpression__quotestring($text) {
	return '\'' . strtr($text, array(
		'\\' => '\\\\',
		'\'' => '\\\''
	)) . '\'';
}

function DtExpression__GenerateCode($struct) {
	$final     = array();
	$last      = false;
	$inlist    = false;
	$afterword = false;
	foreach ($struct as $v) {
		$current = $v;
		if ($inlist == true) {
			if ($current[0] == DTEXPR_OPERATOR && $current[1] == ',') {
				$final[] = $current;
				$last    = $v;
				continue;
			} else if ($last[0] == DTEXPR_OPERATOR && $last[1] == ',') {
				$final[] = $current;
				$last    = $v;
				continue;
			} else {
				$final[]   = ')';
				$afterword = false;
				$inlist    = false;
			}
		}
		if ($v[0] == DTEXPR_STRUCT) {
			if ($last[0] == DTEXPR_WORD) {
				array_pop ($v[1]);
				array_shift ($v[1]);
			}
			$current[1] = DtExpression__GenerateCode($v[1]);
		}
		if ($v[0] == DTEXPR_WORD) {
			if (isset($GLOBALS['DtExpr__FunctionMap'][$current[1]])) {
				$current[1] = $GLOBALS['DtExpr__FunctionMap'][$current[1]];
				$afterword = true;
			} else {
				continue;
			}
		}
		if ($last !== false && $current[0] != DTEXPR_OPERATOR && $last[0] != DTEXPR_OPERATOR) {
			if ($last[0] == DTEXPR_WORD) {
				$final[] = '(';
				$final[] = $current;
				$inlist  = true;
			} else if ($current[0] == DTEXPR_STRING || $last[0] == DTEXPR_STRING) {
				$final[] .= '.';
				$final[] = $current;
			} else {
				$final[] .= '*';
				$final[] = $current;
			}
		} else {
			$final[] = $current;
		}
		$last    = $v;
	}
	if ($inlist) {
		$final[]   = ')';
		$afterword = false;
	}
	if ($afterword) {
		$final[]   = '()';
	}
	$out = array();
	foreach ($final as $v) {
		if (is_array($v)) {
			$out[] = $v[1];
		} else {
			$out[] = $v;
		}
	}
	return implode(' ', $out);
}

function DtExpression__MakeStruct($tokens) {
	$final  = array();
	$buffer = array();
	$level  = 0;
	foreach ($tokens as $k => $v) {
		if ($v[0] == DTEXPR_OPERATOR && $v[1] == '(' && $k != 0) {
			if ($level == 0) {
				$buffer = array(DTEXPR_STRUCT, array());
			}
			$level ++;
		}
		if ($level > 0) {
			$buffer[1][] = $v;
		} else {
			$final[] = $v;
		}
		if ($v[0] == DTEXPR_OPERATOR && $v[1] == ')' && $level > 0) {
			$level --;
			if ($level == 0) {
				$buffer[1] = DtExpression__MakeStruct($buffer[1]);
				$final[] = $buffer;
			}
		}
	}
	return $final;
}

function DtExpression__Tokenize($data) {
	preg_match_all (DTEXPR_MATCH, $data, $tokens, PREG_SET_ORDER);
	$final = array();
	foreach ($tokens as $match) {
		$raw  = $match[0];
		$type = DTEXPR_UNKNOWN;
		if (isset($match[1]) && $match[1] !== '') $type = DTEXPR_STRING;
		if (isset($match[2]) && $match[2] !== '') $type = DTEXPR_NUMBER;
		if (isset($match[3]) && $match[3] !== '') $type = DTEXPR_WORD;
		if (isset($match[4]) && $match[4] !== '') $type = DTEXPR_OPERATOR;
		if ($type == DTEXPR_STRING && $raw[0] == '"') {
			$new = '';
			$len = strlen($raw);
			for ($i = 0; $i <= $len; $i ++) {
				if ($raw[$i] == '\\') {
					$new .= $raw[$i] . $raw[$i + 1];
					$i ++;
				} else {
					if ($raw[$i] == '$') {
						$new .= '\\$';
					} else {
						$new .= $raw[$i];
					}
				}
			}
			$raw = $new;
		}
		$final[] = array($type, $raw);
	}
	return $final;
}

?>
