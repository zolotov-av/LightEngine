<?php

/****************************************************************************

  bbcode.php

  Двухпроходный обработчик bb-кодов

  (cc-by) Zolotov Alex, 2007 - 2008, 2010
          zolotov-alex@shamangrad.net
          http://shamangrad.net/

 ***************************************************************************/

define('BBCODE_TEXT', 1);
define('BBCODE_MODE_CLASSIC', 'classic');
define('BBCODE_MODE_BBML', 'bbml');

/** вспомогательный класс компилятора bb-кодов */
/* private */ class bbcode_tag
{
	var $name;
	var $arg;
	var $text;
	var $value;
	var $items;
	
	function bbcode_tag($name, $arg, $text)
	{
		$this->name = $name;
		$this->arg = $arg;
		$this->text = $text;
		$this->items = array ();
	}
}

/**
* Класс обработчика bb-кодов
*
* Это вспомогательный класс для модуля mod_bbcode
* делающий за него всю черную работу.
*
* Писал очень давно поэтому оставляю практически без именений
*/
class bbcode
{
	/** стек атрибутов */
	var $stack;
	
	/** признак открытого абзаца */
	var $open;
	
	/** теги закрывающие абзац */
	var $close;
	
	/** конструктор */
	function bbcode()
	{
		$this->stack = array(array (
		'mode' => BBCODE_MODE_CLASSIC,	// режим bb-кодов
		'align' => 'justify',	// выравнивание текста
		'indent' => true,	// отступ певрой строки
		'bold' => false,	// полужирный
		'italic' => false,	// курсивный
		'style_open' => '',	// открывающие теги стиля
		'style_close' => '',	// закрывающие теги стиля
		'par_tag' => 'p',	// тег абзаца
		'par_open' => '<p>',	// открывающие теги абзаца
		'par_close' => '</p>',	// закрывающие теги абзаца
		'par_split' => '</p><p>'	// теги разделяющие абзацы
		));
		$this->open = false;	// признак открытого абзаца
		$this->close = '';	// теги закрывающие абзац
	}
	
	function get_attr($name)
	{
		return $this->stack[0][$name];
	}
	
	function set_attr($name, $value)
	{
		$this->stack[0][$name] = $value;
	}
	
	function open_attr()
	{
		$attr = $this->stack[0];
		array_unshift($this->stack, $this->stack[0]);
	}
	
	function close_attr()
	{
		array_shift($this->stack);
	}
	
	function update_attr()
	{
		$tmp = $this->stack[0];
		$tmp['style_open'] = ($tmp['bold'] ? '<b>' : '') . ($tmp['italic'] ? '<i>' : '');
		$tmp['style_close'] = ($tmp['italic'] ? '</i>' : '') . ($tmp['bold'] ? '</b>' : '');
		if ( ($tmp['par_tag'] === 'p') || ($tmp['par_tag'] === 'div') )
		{
			$tmp['par_open'] = "<$tmp[par_tag] class=\"$tmp[align]\">";
		}
		else
		{
			$tmp['par_open'] = "<$tmp[par_tag]>";
		}
		$tmp['par_close'] = "</$tmp[par_tag]>";
		$tmp['par_split'] = "$tmp[style_close]$tmp[par_close]\n\n$tmp[par_open]$tmp[style_open]";
		$this->stack[0] = $tmp;
	}
	
	function get_mode()
	{
		return $this->stack[0]['mode'];
	}
	
	function set_mode($mode)
	{
		$this->stack[0]['mode'] = $mode;
	}
	
	function get_par()
	{
		return $this->stack[0]['par_tag'];
	}
	
	function set_par($tag)
	{
		$this->stack[0]['par_tag'] = $tag;
	}
	
	function set_bold($bold)
	{
		$this->stack[0]['bold'] = $bold;
	}
	
	function set_italic($italic)
	{
		$this->stack[0]['italic'] = $italic;
	}
	
	function get_align()
	{
		return $this->stack[0]['align'];
	}
	
	function set_align($align)
	{
		$this->stack[0]['align'] = $align;
	}
	
	function get_indent()
	{
		return $this->stack[0]['indent'];
	}
	
	function set_indent($indent)
	{
		$this->stack[0]['indent'] = $indent;
	}
	
	/** лексический анализ */
	function tokenize($text)
	{
		$r = preg_match_all('/\[|\]/', $text, $matches, PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER);
		if ( $r === false ) return array ();
		$tokens = array ();
		$i = $p = 0;
		$ch = '[]';
		foreach ($matches[0] as $match)
		{
			if ( $match[0] != $ch[$i] )
			{
				continue;
			}
			$k = $match[1];
			if ( $i == 0 )
			{ // text block
				$txt = substr($text, $p, $k-$p);
				if ( $txt ) $tokens[] = new bbcode_tag(BBCODE_TEXT, '', $txt);
			}
			else
			{ // tag
				$txt = substr($text, $p, $k-$p);
				if ( preg_match('/^(\/?[A-Za-z_][A-Za-z_0-9]*)(\s*=\s*("?)(.*?)\3)?\s*$/s', $txt, $tag) )
				{
					$tokens[] = new bbcode_tag($tag[1], isset($tag[4]) ? $tag[4] : '', "[$txt]");
				}
				else
				{
					$tokens[] = new bbcode_tag(BBCODE_TEXT, '', "[$txt]");
				}
			}
			$p = $k + 1;
			$i = ($i + 1) % 2;
		}
		$txt = substr($text, $p);
		if ( trim($txt) ) $tokens[] = new bbcode_tag(BBCODE_TEXT, '', $txt);
		return $tokens;
	}
	
	function _join($items)
	{
		$text = "";
		foreach($items as $item)
		{
			$text .= $item->text;
		}
		return $text;
	}
	
	/** синтаксический анализ */
	function compile($text)
	{
		$plain = false;
		$expect = '';
		$tokens = $this->tokenize($text);
		$stack = array ( new bbcode_tag(false, false, false) );
		foreach ($tokens as $token)
		{
			if ( $plain )
			{
				if ( $token->name === $expect )
				{
					$plain = false;
					$tmp = array_shift($stack);
					$tmp->value = $this->_join($tmp->items);
					$stack[0]->items[] = $tmp;
				}
				else
				{
					$stack[0]->items[] = new bbcode_tag(BBCODE_TEXT, '', $token->text);
				}
				continue;
			}
			if ( $token->name === BBCODE_TEXT )
			{
				$stack[0]->items[] = $token;
				continue;
			}
			if ( $token->name[0] == '/' )
			{ // закрывающий тег
				$name = substr($token->name, 1);
				if ( strtolower($name) !== 'mode' && ! method_exists($this, "plain_$name") && ! method_exists($this, "bb_$name") && ! method_exists($this, "bbml_$name") )
				{
					$stack[0]->items[] = new bbcode_tag(BBCODE_TEXT, '', $token->text);
					continue;
				}
				if ( count($stack) > 1 && $stack[0]->name !== $name )
				{
					continue;
				}
				$tmp = array_shift($stack);
				$stack[0]->items[] = $tmp;
				continue;
			}
			if ( method_exists($this, "plain_{$token->name}") )
			{
				$plain = true;
				$expect = '/' . $token->name;
				array_unshift($stack, $token);
			}
			elseif ( strtolower($token->name) === 'mode' || method_exists($this, "bb_{$token->name}") || method_exists($this, "bbml_{$token->name}") || method_exists($this, "plain_{$token->name}") )
			{
				array_unshift($stack, $token);
			}
			else
			{
				$stack[0]->items[] = new bbcode_tag(BBCODE_TEXT, '', $token->text);
			}
		}
		
		while ( count($stack) > 1 )
		{
			$tmp = array_shift($stack);
			$stack[0]->items[] = $tmp;
		}
		
		return $stack[0];
	}
	
	/** первый проход обработчика bb-кодов */
	function process($text, $mode)
	{
		$code = $this->compile($text);
		$result = $this->open_section('p');
		$this->set_attr('mode', $mode === BBCODE_MODE_BBML ? BBCODE_MODE_BBML : BBCODE_MODE_CLASSIC);
		$this->update_attr();
		$result .= $mode === BBCODE_MODE_BBML ? $this->process_bbml($code) : $this->process_classic($code);
		$result .= $this->close_section();
		return $result;
	}
	
	/** обработка потомков элемента в режиме classic */
	function process_classic($tree)
	{
		$text = "";
		foreach ($tree->items as $item)
		{
			if ( $item->name === false )
			{
				$text .= $this->process_classic($item);
			}
			elseif ( $item->name === BBCODE_TEXT )
			{
				$text .= $this->text_classic($item->text);
			}
			elseif ( strtolower($item->name) === 'mode' )
			{
				if ( strtolower($item->arg) === 'bbml' )
				{
					$text .= $this->open_section('p');
					$this->set_attr('mode', BBCODE_MODE_BBML);
					$this->update_attr();
					$text .= $item->text . $this->process_bbml($item) . "[/{$item->name}]";
					$text .= $this->close_section('p');
				}
				else
				{
					$text .= $item->text . $this->process_classic($item) . "[/{$item->name}]";
				}
			}
			elseif ( method_exists($this, "plain_{$item->name}") )
			{
				$text .= $item->text . call_user_func(array(& $this, "plain_{$item->name}"), $item->arg, $item->value) . "[/{$item->name}]";
			}
			elseif ( method_exists($this, "bb_{$item->name}") )
			{
				$text .= $item->text . call_user_func(array(& $this, "bb_{$item->name}"), $item->arg, $this->process_classic($item)) . "[/{$item->name}]";
			}
			else
			{
				$text .= bbcode_escape($item->text) . $this->process_classic($item) . bbcode_escape("[/{$item->name}]");
			}
		}
		return $text;
	}
	
	/** обработка потомков элемента в режиме BBML */
	function process_bbml($tree)
	{
		$text = "";
		foreach ($tree->items as $item)
		{
			if ( $item->name === false )
			{
				$text .= $this->process_bbml($item);
			}
			elseif ( $item->name === BBCODE_TEXT )
			{
				$text .= $this->text_bbml($item->text);
			}
			elseif ( strtolower($item->name) === 'mode' )
			{
				if ( strtolower($item->arg) === 'classic' )
				{
					$text .= $this->open_section('p');
					$this->set_attr('mode', BBCODE_MODE_BBML);
					$this->update_attr();
					$text .= $item->text . $this->process_classic($item) . "[/{$item->name}]";
					$text .= $this->close_section();
				}
				else
				{
					$text .= $item->text . $this->process_bbml($item) . "[/{$item->name}]";
				}
			}
			elseif ( method_exists($this, "plain_{$item->name}") )
			{
				$text .= $item->text . call_user_func(array(& $this, "plain_{$item->name}"), $item->arg, $item->value) . "[/{$item->name}]";
			}
			elseif ( method_exists($this, "bbml_{$item->name}") )
			{
				$text .= $item->text . call_user_func(array(& $this, "bbml_{$item->name}"), $item) . "[/{$item->name}]";
			}
			else
			{
				$text .= bbcode_escape($item->text) . $this->process_bbml($item) . bbcode_escape("[/{$item->name}]");
			}
		}
		return $text;
	}
	
	/** экранирование всех спецсимволов HTML и bb-кодов */
	function plainescape($text)
	{
		return strtr($text, array (
		'<' => '&lt;',
		'>' => '&gt;',
		'&' => '&amp;',
		'"' => '&quot;',
		'[' => '&#'.ord('[').';',
		']' => '&#'.ord(']').';'
		));
	}
	
	/** обработка автозамен и подсветка ссылок */
	function escape($text)
	{
		return mod_bbcode::makelinks(mod_bbcode::escape($text));
	}
	
	/** обработка текстового блока в режиме classic */
	function text_classic($text)
	{
		$text = preg_replace("/(\n\r|\r\n|\r|\n)/", "\n", $text);
		$text = preg_replace("/\n{3,}/", "\n\n", $text);
		return str_replace("\n", "<br />\n", $this->escape($text));
	}
	
	/** обработка текстового блока в режиме BBML */
	function text_bbml($text)
	{
		$attr = $this->stack[0];
		$par = preg_split("/(\\s*(\n\r|\r\n|\n|\r)){2,}/s", $this->escape($text));
		if ( count($par) == 1 && trim($par[0]) == "" )
		{
			return $par[0];
		}
		if ( trim($par[0]) == "" && $this->open )
		{
			$close_prefix = $this->close;
			$this->open = false;
		}
		else $close_prefix = "";
		$prefix = $close_prefix . (trim($par[0]) == "" ? "$par[0]\n\n" : "") . ($this->open ? $attr['style_open'] : "$attr[par_open]$attr[style_open]");
		$this->open = end($par) != "";
		$suffix = ($this->open ? $attr['style_close'] : "$attr[style_close]$attr[par_close]") . (trim(end($par)) == "" ? "\n\n" . end($par) : "");
		if ( $this->open )
		{
			$this->close = "</$attr[par_tag]>";
		}
		if ( trim(end($par)) == "" ) unset($par[count($par)-1]);
		if ( trim($par[0]) == "" ) unset($par[0]);
		if ( count($par) == 0 ) return "$close_prefix$text";
		return $prefix . implode($attr['par_split'], $par) . $suffix;
	}
	
	/** открыть абзац */
	function open_par()
	{
		$attr = $this->stack[0];
		$this->open = true;
		$this->close = "</$attr[par_tag]>";
		return $attr['par_open'];
	}
	
	/** закрыть абзац */
	function close_par()
	{
		if ( $this->open )
		{
			$this->open = false;
			return $this->close;
		}
		return '';
	}
	
	/** открыть секцию */
	function open_section($par)
	{
		$result = $this->close_par();
		$this->open_attr();
		$this->set_par($par);
		$this->update_attr();
		return $result;
	}
	
	/** закрыть секцию */
	function close_section()
	{
		$result = $this->close_par();
		$this->close_attr();
		return $result;
	}
	
	//////////////////////////////////////////////////////////////////////
	
	function bb_b($arg, $text)
	{
		return "<b>$text</b>";
	}
	
	function bbml_b($item)
	{
		$this->open_attr();
		$this->set_bold(strtolower($item->arg) !== 'off');
		$this->update_attr();
		$result = $this->process_bbml($item);
		$this->close_attr();
		return $result;
	}
	
	function bb_i($arg, $text)
	{
		return "<i>$text</i>";
	}
	
	function bbml_i($item)
	{
		$this->open_attr();
		$this->set_italic(strtolower($item->arg) !== 'off');
		$this->update_attr();
		$result = $this->process_bbml($item);
		$this->close_attr();
		return $result;
	}
	
	function bbml_p($item)
	{
		$result = $this->open_section($this->get_attr('indent') ? 'p' : 'div');
		if ( preg_match('/^left|right|center|justify$/', $item->arg) )
		{
			$this->set_align($item->arg);
			$this->update_attr();
		}
		return $result . $this->process_bbml($item) . $this->close_section();
	}
	
	function bb_ol($arg, $text)
	{
		return "<ol>$text</ol>";
	}
	
	function bbml_ol($item)
	{
		return $this->open_section('li') . '<ol>' . $this->process_bbml($item) . $this->close_section() . '</ol>';
	}
	
	function bb_ul($arg, $text)
	{
		return "<ul>$text</ul>";
	}
	
	function bbml_ul($item)
	{
		return $this->open_section('li') . '<ul>' . $this->process_bbml($item) . $this->close_section() . '</ul>';
	}
	
	function bb_li($arg, $text)
	{
		return "<li>$text</li>";
	}
	
	function bbml_li($item)
	{
		return $this->open_section('li') . $this->process_bbml($item) . $this->close_section();
	}
	
	function bbml_align($item)
	{
		$result = $this->open_section($this->get_par());
		if ( preg_match('/^left|right|center|justify$/si', $item->arg) )
		{
			$this->set_align(strtolower($item->arg));
			$this->update_attr();
		}
		return $result . $this->process_bbml($item) . $this->close_section();
	}
	
	function bbml_indent($item)
	{
		$result = $this->open_section($this->get_par());
		if ( $item->arg === "" || preg_match('/^on|off$/si', $item->arg) )
		{
			$this->set_indent(strtolower($item->arg) === 'on' || $item->arg === '');
			$this->update_attr();
		}
		return $result . $this->process_bbml($item) . $this->close_section();
	}
	
	function bb_h1($arg, $text)
	{
		return "<h1>$text</h1>";
	}
	
	function bbml_h1($item)
	{
		return $this->open_section('h1') . $this->process_bbml($item) . $this->close_section();
	}
	
	function bb_h2($arg, $text)
	{
		return "<h2>$text</h2>";
	}
	
	function bbml_h2($item)
	{
		return $this->open_section('h2') . $this->process_bbml($item) . $this->close_section();
	}
	
	function bb_h3($arg, $text)
	{
		return "<h3>$text</h3>";
	}
	
	function bbml_h3($item)
	{
		return $this->open_section('h3') . $this->process_bbml($item) . $this->close_section();
	}
	
	function bbml_table($item)
	{
		$width = preg_match('/^\d+%$/', $item->arg) ? " width=\"{$item->arg}\"" : "";
		return $this->open_section('div') . "<table class=\"gc_table\"$width border=\"1\">" . $this->process_bbml($item) . $this->close_section() . "</table>";
	}
	
	function bbml_tr($item)
	{
		return $this->open_section('div') . "<tr>" . $this->process_bbml($item) . $this->close_section() . "</tr>";
	}
	
	function bbml_td($item)
	{
		$result = $this->open_section('div');
		$align = preg_match('/^left|right|center|justify|parent$/', $item->arg) ? $item->arg : 'left';
		if ( $align !== 'parent' )
		{
			$this->set_align($align);
			$this->update_attr();
		}
		return "<td>" . $this->process_bbml($item) . $this->close_section() . "</td>";
	}
	
	function bbml_th($item)
	{
		$result = $this->open_section('div');
		$align = preg_match('/^left|right|center|justify|parent$/', $item->arg) ? $item->arg : 'center';
		if ( $align !== 'parent' )
		{
			$this->set_align($align);
			$this->update_attr();
		}
		return "<th>" . $this->process_bbml($item) . $this->close_section() . "</th>";
	}
	
	function bb_prj($arg, $text)
	{
		$prj = UrlEncode($arg);
		return "<a href=\"project.php?act=view&prj=$prj\">$text</a>";
	}
	
	function bbml_prj($item)
	{
		return $this->bb_prj($item->arg, $this->process_bbml($item));
	}
	
	function bb_url($arg, $text)
	{
		if ( $ref = mod_bbcode::escapeURL($arg) )
		{
			return '<a href="' . mod_bbcode::escapePlain($ref) . '" target="_blank">' . $text . '</a>';
		}
		else
		{
			return $text;
		}
	}
	
	function bbml_url($item)
	{
		return $this->bb_url($item->arg, $this->process_bbml($item));
	}
	
	function bb_pic($arg, $text)
	{
		$ref = mod_bbcode::escapePlain( mod_bbcode::escapeURL($arg) );
		return "[+]<div class=\"bbimg\"><div class=\"image\"><img src=\"$ref\" alt=\"image\" /></div><div class=\"text\">[-]{$text}[+]</div></div>[-]";
	}
	
	function bbml_pic($item)
	{
		$ref = mod_bbcode::escapePlain( mod_bbcode::escapeURL($item->arg) );
		$result = $this->open_section('p');
		$this->set_italic(false);
		$this->set_bold(false);
		$this->set_align('justify');
		$this->update_attr();
		$result .= "[+]<div class=\"bbimg\"><div class=\"image\"><img src=\"$ref\" alt=\"image\" /></div><div class=\"text\">[-]";
		$result .= $this->process_bbml($item);
		$result .= $this->close_par();
		$result .= "[+]</div></div>[-]";
		$result .= $this->close_section();
		return $result;
	}
	
	function bb_quote($arg, $text)
	{
		$author = $this->escape(trim($arg));
		$title = $author === '' ? 'цитата' : "$author писал:";
		return "[+]<div class=\"bbquote\"><div class=\"caption\">$title</div><div class=\"content\">[-]{$text}[+]</div></div>[-]";
	}
	
	function bbml_quote($item)
	{
		$author = $this->escape(trim($item->arg));
		$title = $author === '' ? 'цитата' : "$author писал:";
		$result = $this->open_section('p');
		$this->set_italic(false);
		$this->set_bold(false);
		$this->set_align('justify');
		$this->update_attr();
		$result .= "[+]<div class=\"bbquote\"><div class=\"caption\">$title</div><div class=\"content\">[-]";
		$result .= $this->process_bbml($item);
		$result .= $this->close_par();
		$result .= "[+]</div></div>[-]";
		$result .= $this->close_section();
		return $result;
	}
	
	function bb_div($arg, $text)
	{
		$caption = $this->escape($arg);
		$text = "<div class=\"" . htmlspecialchars($this->get_attr('align')) . "\">$text</div>";
		$result = "[+]<div class=\"bbdiv\"><div class=\"caption\">$caption</div><div class=\"content\">[-]{$text}[+]</div></div>[-]";
		return $result;
	}
	
	function bbml_div($item)
	{
		$caption = $this->escape($item->arg);
		$result = $this->open_section('p');
		$this->set_italic(false);
		$this->set_bold(false);
		$this->set_align('justify');
		$this->update_attr();
		$result .= "[+]<div class=\"bbdiv\"><div class=\"caption\">$caption</div><div class=\"content\">[-]";
		$result .= $this->process_bbml($item);
		$result .= "[+]</div></div>[-]";
		$result .= $this->close_section();
		return $result;
	}
	
	function bb_offtopic($arg, $text)
	{
		return "[+]<p><i>Здесь был offtopic &mdash; точно был&hellip; но он ушёл и больше не вернется.</i></p>[-]";
	}
	
	function bbml_offtopic($item)
	{
		return $this->open_section('p') . $this->bb_offtopic($item->arg, '') . $this->close_section();
	}
	
	function plain_img($arg, $text)
	{
		$ref = mod_bbcode::escapePlain( mod_bbcode::escapeURL($text) );
		$alt = mod_bbcode::escapePlain($arg);
		return $ref ? "[+]<img src=\"[-]{$ref}[+]\" alt=\"$alt\" />[-]" : $alt;
	}
	
	function plain_code($arg, $text)
	{
		if ( $arg == "" )
		{
			$code = "code";
			$text = $this->plainescape($text);
		}
		else
		{
			$text = bbcode_highlight($arg, $text);
			$code = $arg;
		}
		$prefix = ($this->get_mode() === BBCODE_MODE_BBML) ? $this->close_par() : "";
		return "{$prefix}[+]<div class=\"bbcode\"><div class=\"caption\">$code:</div>\n<pre class=\"$code\">[-]{$text}[+]</pre></div>[-]";
	}
	
	function plain_nobb($arg, $text)
	{
		if ( $this->get_mode() === BBCODE_MODE_BBML )
		{
			$attr = $this->stack[0];
			$result = "";
			if ( ! $this->open )
			{
				$result = $this->open_par();
			}
			return $result . $attr['style_open'] . $this->plainescape($text) . $attr['style_close'];
		}
		return $this->plainescape($text);
	}
}

?>