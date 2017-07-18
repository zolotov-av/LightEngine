<?php

/**
* Модуль компилятора шаблонов
*
* (c) Золотов Алексей <zolotov-alex@shamangrad.net>, 2007-2009
*
* Любителям регулярок и конечных автоматов посвящается :)
*/
class mod_tplc extends LightModule
{
	/**
	* Текущий компилируемый шаблон
	*/
	private $name = '';
	
	/**
	* Стек открытых скобок (стек булевых значений)
	*
	* Различается два вида открытых скобок: скобка подвыражения
	* и скобка вызова функций. Элемент стека равен true если
	* открыта скобка вызова функций, если false, то скобка подвыражения
	*/
	private $expr_stack;
	
	/**
	* Признак вызова функции
	*
	* Если true, то текущая лексема находиться непосредственно внутри
	* скобок вызова функции.
	*/
	private $expr_in_call;
	
	/**
	* Признак ожидания значения
	*
	* Значение может быть идентификатором, вызовом функции или
	* подвыражением заключенным в скобки.
	*/
	private $expr_expect_value;
	
	/**
	* Список сохраняемых тегов
	*/
	private $save_tags;
	
	/**
	* Промежуточные аргументы
	*/
	private $args;
	
	/**
	* Список определений допустимых функций
	*/
	public $functions = array (
		'count' => 'count',
		'not' => '!',
		'include' => '$tpl->process',
		'defined' => 'isset',
		'lang' => '$tpl->lang->format',
		'sprintf' => 'sprintf',
		'encode' => 'UrlEncode',
		'decode' => 'UrlDecode',
		//'escape' => '$tpl->html->escape',
		'escape' => 'htmlspecialchars',
		'bbcode' => '$tpl->bbcode->pass2',
		'jstring' => '$tpl->html->jstring',
		'config' => '$tpl->config->read',
		'url' => 'makeurl',
		'date' => 'date',
		'time' => 'time',
		'rmonth' => '$tpl->time->rmonth',
		'lower' => 'strtolower',
		'upper' => 'strtoupper',
		'nl2br' => 'nl2br'
	);
	
	/**
	* Конструктор модуля
	* @param LightEngine менеджер модулей
	* @retval LightModule модуль
	*/
	public static function create(LightEngine $engine)
	{
		return new mod_tplc($engine);
	}
	
	/**
	* Генерировать ошибку комплияции
	* @param string сообщение об ошибке
	*/
	private function compile_error($message)
	{
		throw new tpl_exception($this->name, $this->line, $message);
	}
	
	/**
	* Компиляция тега
	* @param string тег
	* @return string скомпилированное PHP-выражение
	*/
	private function compile_tag($tag)
	{
		return '$tags["' . preg_replace('/\\.|\\//', '"]["', $tag) . '"]';
	}
	
	/**
	* Компиляция строковой константы
	* @param string значение константы
	* @return string скомпилированное PHP-выражение
	*/
	private function compile_string($text)
	{
		return "'" . strtr($text, array (
			"'" => "\\'",
			"\\" => "\\\\"
			)) . "'";
	}
	
	/**
	* Компиляция лексемы значения
	* @param array лексема
	* @return string скомпилированное PHP-выражение
	*/
	private function compile_value($lex)
	{
		if ( isset($lex[2]) && $lex[2] !== '' )
		{ // компиляция числовой константы
			$this->expr_expect_value = false;
			return $lex[2];
		}
		
		if ( isset($lex[6]) && $lex[5] !== '' )
		{ // компиляция строковой константы в двойных кавычках
			$this->expr_expect_value = false;
			return $this->compile_string(stripslashes($lex[6]));
		}
		
		if ( isset($lex[12]) && $lex[11] !== '' )
		{ // компиляция строковой константы в одинарных кавычках
			$this->expr_expect_value = false;
			return $this->compile_string(stripslashes($lex[12]));
		}
		
		if ( isset($lex[10]) && $lex[10] !== '' )
		{ // компиляция подстановки тега
			$this->expr_expect_value = false;
			return $this->compile_tag($lex[10]);
		}
		
		if ( isset($lex[9]) && $lex[9] !== '' )
		{ // компиляция вызова функции
			$func = strtolower($lex[9]);
			
			// вызов функции является значением
			if ( ! $this->expr_expect_value )
			{
				$this->compile_error("unexpected function call $func()");
			}
			
			if ( ! isset($this->functions[$func]) )
			{
				$this->compile_error("undefined function $func()");
			}
			
			array_push($this->expr_stack, $this->expr_in_call);
			$this->expr_in_call = true;
			$this->expr_expect_value = true;
			return $this->functions[$func] . "(";
		}
		
		$this->compile_error("unexpected $lex[0]");
	}
	
	/**
	* Начать обработку выражения
	* @param string финализатор выражения (имя метода)
	* @param array лексема
	* @return string скомпилированное PHP-выражение
	*/
	private function start_expression($finalizer, $lex)
	{
		$this->expression = $finalizer;
		$this->expr_expect_value = true;
		return $this->compile_expression($lex);
	}

	/**
	* Начать обработку выражения со следующей лексемы
	* @param string финализатор выражения (имя метода)
	* @param array лексема
	* @return string скомпилированное PHP-выражение
	*/
	private function expect_expression($finalizer)
	{
		$this->expr_expect_value = true;
		$this->expression = $finalizer;
	}
	
	/**
	* Компиляция конца выражения
	* @param array лексема на которой обнаружен конец выражения
	* @return string скомпилированное PHP-выражение
	*/
	private function end_expression($lex)
	{
		// вызывать соответствующий обработчик конца выражения
		$callback = array(& $this, $this->expression);
		$this->expression = false;
		return call_user_func($callback, $lex);
	}
	
	/**
	* Компиляция лексемы выражения
	* @param array лексема
	* @return string скомпилированное PHP-выражение
	*/
	private function compile_expression($lex)
	{
		if ( $lex[0] === ',' )
		{ // разделитель аргументов функции или конец выражения
			if ( $this->expr_expect_value )
			{
				$this->compile_error("unexpected operator ,");
			}
			
			if ( count($this->expr_stack) == 0 )
			{ // конец выражения
				return $this->end_expression($lex);
			}
			
			// разделитель может быть только внутри скобок вызова функции
			if ( ! $this->expr_in_call )
			{
				$this->compile_error("unexpected operator ,");
			}
			
			$this->expr_expect_value = true;
			return ", ";
		}
		
		if ( isset($lex[7]) && $lex[7] !== '' )
		{ // оператор
			if ( $this->expr_expect_value )
			{
				$this->compile_error("unexpected operator $lex[7]");
			}
			$this->expr_expect_value = true;
			switch ( $lex[7] )
			{ // замена операторов отличающихся от php
			case '=': return '==';
			case '<>': return '!=';
			}
			return $lex[7];
		}
		
		if ( $lex[0] === '(' )
		{ // подвыражение в скобках
			array_push($this->expr_stack, $this->expr_in_call);
			$this->expr_in_call = false;
			$this->expr_expect_value = true;
			return '(';
		}
		
		if ( $lex[0] === ')' )
		{ // закрывается подвыражение или вызов функции
			if ( count($this->expr_stack) == 0 || $this->expr_expect_value )
			{ // нет открытых скобок или ожидается значение
				$this->compile_error("unexpected )");
			}
			$this->expr_in_call = array_pop($this->expr_stack);
			$this->expr_expect_value = false;
			return ")";
		}
		
		if ( $this->expr_expect_value )
		{ // обработка значения
			return $this->compile_value($lex);
		}
		
		if ( count($this->expr_stack) == 0 )
		{
			return $this->end_expression($lex);
		}
		else
		{
			$this->compile_error("unexpected $lex[0]");
		}
	}
	
	/**
	* Завершение компиляции выражения подстановки
	* @param array лексема
	* @return string скомпилированный PHP-код
	*/
	private function end_subst_expr($lex)
	{
		return ";\n" . $this->compile_instruction($lex);
	}
	
	/**
	* Сохранить значение в стеке тегов
	* @param mixed сохраняемое значение
	*/
	private function push_tag($value)
	{
		$this->tag_stack[] = $value;
	}
	
	/**
	* Восстановить значение из стека тегов
	* @return mixed извлеченное значение
	*/
	private function pop_tag()
	{
		return count($this->tag_stack) > 0 ? array_pop($this->tag_stack) : false;
	}
	
	/**
	* Сохранить теги в стеке
	*/
	private function save_tags()
	{
		$this->push_tag($this->tags);
	}
	
	/**
	* Вернуть PHP-код восстановления тегов
	* @return string скомпилированный PHP-код
	*/
	private function restore_tags()
	{
		$code = "";
		$tags = array_reverse($this->pop_tag());
		foreach ($tags as $tag)
		{
			$code .= "$tag = array_pop(\$stack);\n";
		}
		return $code;
	}
	
	/**
	* Компиляция окончания if-выражения
	* @param array лексема
	* @return string скомпилированный PHP-код
	*/
	private function end_if_expr($lex)
	{
		return " ) {\n" . $this->compile_instruction($lex);
	}
	
	/**
	* Компиляция оператора if
	* @return string скомпилированный PHP-код
	*/
	private function compile_if()
	{
		$this->push_tag('if');
		$this->expect_expression('end_if_expr');
		return "if ( ";
	}
	
	/**
	* Компиляция окончания выражения оператора if
	* @param array лексема
	* @return string скомпилированный PHP-код
	*/
	private function end_ifdef_expr($lex)
	{
		return ") ) {\n" . $this->compile_instruction($lex);
	}
	
	/**
	* Компиляция ifdef-оператора
	* @return string скомпилированный PHP-код
	*/
	private function compile_ifdef()
	{
		$this->push_tag('if');
		$this->expect_expression('end_ifdef_expr');
		return "if ( isset(";
	}
	
	/**
	* Компиляция ifndef-оператора
	* @return string скомпилированный PHP-код
	*/
	private function compile_ifndef()
	{
		$this->push_tag('if');
		$this->expect_expression('end_ifdef_expr');
		return "if ( ! isset(";
	}
	
	/**
	* Компиляция elseif-блока оператора if
	* @return string скомпилированный PHP-код
	*/
	private function compile_elseif()
	{
		if ( ! ($tag = $this->pop_tag()) || $tag !== 'if' )
		{
			$this->compile_error("unexpected elseif");
		}
		$this->push_tag('if');
		$this->expect_expression('end_if_expr');
		return "} elseif ( ";
	}
	
	/**
	* Компиляция else-блока оператора if
	* @return string скомпилированный PHP-код
	*/
	private function compile_else()
	{
		if ( ! ($tag = $this->pop_tag()) || $tag !== 'if' )
		{
			$this->compile_error("unexpected elseif");
		}
		$this->push_tag('else');
		return "} else {\n";
	}
	
	/**
	* Компиляция окончания оператора if
	* @return string скомпилированный PHP-код
	*/
	private function compile_endif()
	{
		if ( ! ($tag = $this->pop_tag()) || $tag !== 'if' && $tag !== 'else' )
		{
			$this->compile_error("unexpected endif");
		}
		return "}\n";
	}
	
	/**
	* Компиляция третьего параметра цикла foreach (имя значения)
	* @param array лексема
	* @return string скомпилированный PHP-код
	*/
	private function expect_for_name2($lex)
	{
		if ( ! isset($lex[10]) || ! preg_match('/^[A-Za-z_][A-Za-z_0-9]*$/', $lex[10]) )
		{
			$this->compile_error("expected key or value name");
		}
		$this->expect = false;
		$tag = $this->compile_tag($this->args['tag']);
		$key = $this->compile_tag($this->args['value']);
		$value = $this->compile_tag($lex[10]);
		$this->push_tag(array($key, $value));
		$this->push_tag('foreach');
		return "\$stack[] = $key;\n\$stack[] = $value;\nforeach ($tag as $key => $value) {\n";
	}
	
	/**
	* Компиляция разделителя => цикла foreach
	* @param array лексема
	* @return string скомпилированный PHP-код
	*/
	private function expect_for_key($lex)
	{
		if ( $lex[0] !== '=>' )
		{
			$this->expect = false;
			$tag = $this->compile_tag($this->args['tag']);
			$value = $this->compile_tag($this->args['value']);
			$this->push_tag(array($value));
			$this->push_tag('foreach');
			return "\$stack[] = $value;\nforeach ($tag as $value) {\n" . $this->compile_instruction($lex);
		}
		else
		{
			$this->expect = 'expect_for_name2';
			return '';
		}
	}
	
	/**
	* Компиляция второго параметра цикла foreach (имя ключ или значения)
	* @param array лексема
	* @return string скомпилированный PHP-код
	*/
	private function expect_for_name1($lex)
	{
		if ( ! isset($lex[10]) || ! preg_match('/^[A-Za-z_][A-Za-z_0-9]*$/', $lex[10]) )
		{
			$this->compile_error("expected key or value name");
		}
		$this->args['value'] = $lex[0];
		$this->expect = 'expect_for_key';
		return '';
	}
	
	/**
	* Компиляция разделителя as цикла foreach
	* @param array лексема
	* @return string скомпилированный PHP-код
	*/
	private function expect_for_as($lex)
	{
		if ( strtolower($lex[0]) !== 'as' )
		{
			$this->compile_error("expected 'as'");
		}
		$this->expect = 'expect_for_name1';
		return '';
	}
	
	/**
	* Компиляция первого аргумента цикла foreach
	* @param array лексема
	* @return string скомпилированный PHP-код
	*/
	private function expect_foreach_arg($lex)
	{
		if ( ! isset($lex[10]) || $lex[10] === '' )
		{
			$this->compile_error("expected tag");
		}
		$this->args['tag'] = $lex[10];
		$this->expect = 'expect_for_as';
		return '';
	}
	
	/**
	* Компиляция foreach оператора
	* @return string скомпилированный PHP-код
	*/
	private function compile_foreach()
	{
		$this->expect = 'expect_foreach_arg';
		return '';
	}
	
	/**
	* Компиляция окончания foreach оператора
	* @return string скомпилированный PHP-код
	*/
	private function compile_endeach()
	{
		if ( ! ($tag = $this->pop_tag()) || $tag !== 'foreach' )
		{
			$this->compile_error("unexpected endeach");
		}
		return "}\n" . $this->restore_tags();
	}
	
	/**
	* Компиляция окончания include-выражения
	* @param array лексема
	* @return string скомпилированный PHP-код
	*/
	private function end_include_expr($lex)
	{
		return ", \$tags );\n" . $this->compile_instruction($lex);
	}
	
	/**
	* Компиляция include-оператора
	* @return string скомпилированный PHP-код
	*/
	private function compile_include()
	{
		$this->expect_expression('end_include_expr');
		return "\$result .= \$tpl->process( ";
	}
	
	/**
	* Компиляция окончания let-выражения
	* @param array лексема
	* @return string скомпилированный PHP-код
	*/
	private function end_let_expr($lex)
	{
		if ( $lex[0] === ',' )
		{
			$this->expect = "expect_let_name";
			return ";\n";
		}
		else
		{
			$this->save_tags();
			$this->push_tag('let');
			$this->expect = false;
			return ";\n" . $this->compile_instruction($lex);
		}
	}
	
	/**
	* Компиляция открывающейся квадратной скобки в let-выражении
	* @param array лексема
	* @return string скомпилированный PHP-код
	*/
	private function expect_let_brace($lex)
	{
		$this->expect = false;
		$tag = $this->args['tag'];
		if ( $lex[0] === '[' )
		{ // компиляция оператора []
			$this->push_tag($tag);
			$this->push_tag('[');
			return "\$stack[] = $tag;\n\$stack[] = \$result;\n\$result = '';\n";
		}
		else
		{
			return "\$stack[] = $tag;\n$tag = " . $this->start_expression('end_let_expr', $lex);
		}
	}
	
	/**
	* Компиляция знака равенства в let-выражении
	* @param array лексема
	* @return string скомпилированный PHP-код
	*/
	private function expect_let_sign($lex)
	{
		if ( $lex[0] !== '=' )
		{
			$this->compile_error('expected operator =');
		}
		$this->expect = 'expect_let_brace';
		return '';
	}
	
	/**
	* Компиляция имени let-параметра
	* @param array лексема
	* @return string скомпилированный PHP-код
	*/
	private function expect_let_name($lex)
	{
		if ( ! isset($lex[10]) || ! preg_match('/^[A-Za-z_][A-Za-z_0-9]*$/', $lex[10]) )
		{
			$this->compile_error("expected tag name");
		}
		$this->tags[] = $this->args['tag'] = $this->compile_tag($lex[10]);
		$this->expect = 'expect_let_sign';
		return '';
	}
	
	/**
	* Компиляция оператора let
	* @return string скомпилированный PHP-код
	*/
	private function compile_let()
	{
		// список названий определяемых тегов
		$this->tags = array ();
		$this->expect = 'expect_let_name';
		return '';
	}
	
	/**
	* Компиляция конца выражения
	* @return string скомпилированный PHP-код
	*/
	private function compile_endlet()
	{
		if ( ! ($tag = $this->pop_tag()) || $tag !== 'let' )
		{
			$this->compile_error("unexpected endlet");
		}
		return $this->restore_tags();
	}
	
	/**
	* Компиляция инструкции
	* @param array лексема
	* @return string скомпилированный PHP-код
	*/
	private function compile_instruction($lex)
	{
		if ( isset($lex[4]) && $lex[3] !== '' )
		{ // компиляция тектового блока
			if ( $lex[4] === '' ) return '';
			return "\$result .= " . $this->compile_string($lex[4]) . ";\n";
		}
		
		if ( isset($lex[8]) && $lex[8] !== '' )
		{ // компиляция ключевого слова
			return call_user_func(array(&$this, "compile_$lex[8]"));
		}
		
		if ( $lex[0] === ']' )
		{ // окончание оператора []
			if ( ! ($tag = $this->pop_tag()) || $tag !== '[' )
			{
				$this->compile_error("unexpected ]");
			}
			$tag = $this->pop_tag();
			$this->expect = 'end_let_expr';
			return "$tag = \$result;\n\$result = array_pop(\$stack);\n";
		}
		
		// компиляция подстановки
		return "\$result .= " . $this->start_expression('end_subst_expr', $lex);
	}
	
	/**
	* Компиляция лексемы
	* @param array лексема
	* @return string скомпилированный PHP-код
	*/
	private function compile_token($lex)
	{
		if ( trim($lex[0]) === '' || isset($lex[1]) && $lex[1] !== '' )
		{ // игнорируем пробелы и комментарии
			return '';
		}
		
		if ( isset($lex[13]) && $lex[13] !== '' )
		{ // неожиданный символ
			$this->compile_error("unexpected char \"$lex[13]\"");
		}
		
		//print_r($lex);
		
		if ( $this->expect )
		{ // компиляция ожидаемых лексем
			return call_user_func(array(& $this, $this->expect), $lex);
		}
		
		if ( $this->expression )
		{ // компиляция выражения
			return $this->compile_expression($lex);
		}
		else
		{ // компиляция инструкции
			return $this->compile_instruction($lex);
		}
	}
	
	/**
	* Обработка лексемы
	*
	* Данная функция ведет отсчет номеров строк,
	* а реальная обработка лексем производиться в функции
	* compile_token()
	*
	* @param array лексема
	* @return string скомпилированный PHP-код
	*/
	private function process_token($lex)
	{
		$code = $this->compile_token($lex);
		$this->line += substr_count($lex[0], "\n");
		return $code;
	}
	
	/**
	* Компиляция текста шаблона в PHP-код
	*
	* PHP-код на вход принимает две переменные:
	*  $tpl - объект компилятора
	*  $tags - теги
	* и на выходе дает переменную $result
	*
	* @param string текст шаблона
	* @param string имя шаблона
	* @return string PHP-код
	*/
	public function compile($text, $name)
	{
		$this->line = 1;
		$this->name = $name;
		$this->tag_stack = array ();
		$this->expr_stack = array ();
		$this->expr_in_call = false;
		$this->expr_expect_value = false;
		$this->expression = false;
		$this->expect = false;
		$code = preg_replace_callback("/
			(\\s+|\\/\\*.*?\\*\\/) | # пробелы и комментарии игнорируются [1]
			(\\d+(?:\\.\\d+)?) | # числовая константа [2]
			(}(?:[\\ \\\t\\\r]*\\\n\\\r?)?([^{]*?)(?:(?<=\\\n)[\\ \\\t\\\r]*)?{) | # текстовый блок [3, 4]
			(\"((?:[^\"\\\\]|\\\\ \"|\\\\ \\\\)*)\") | # строка [5, 6]
			=> | , | \\b as \\b | # разные разделители
			(!?=|>=?|<(?:=|>)?|\\+|\\*|\\-|\\/|%|\band\b|\bor\b) | # операторы [7]
			\\#?\\b(if|ifn?def|else|elseif|endif|foreach|endeach|include|let|endlet)\\b | # ключевые слова [8]
			\\b(?:([A-Za-z_][A-Za-z_0-9]*)\\s*\\() | # вызов функции [9]
			\\b([A-Za-z_][A-Za-z_0-9]*(?:(?:\\.|\\/)[A-Za-z_][A-Za-z_0-9]*)*) | # тег [10]
			\\[ | \\] | \\( | \\) | # операторные скобки
			('((?:[^'\\\\]|\\\\ '|\\\\ \\\\)*)') | # строка [11, 12]
			(.) # неожиданный символ [13]
			/xsi", array (& $this, 'process_token'), "}$text{");
		
		if ( $this->expr_expect_value )
		{
			$this->compile_error('expected value but EOF found');
		}
		
		if ( count($this->expr_stack) > 0 )
		{
			$this->compile_error('expected ) but EOF found');
		}
		
		if ( $this->expect )
		{
			$this->compile_error($this->expect);
		}
		
		if ( count($this->tag_stack) > 0 )
		{
			$op = $this->pop_tag();
			$this->compile_error("unterminated $op-block");
		}
		
		return "\$result = '';\n\$stack = array ();\n" . $code . "return \$result;\n";
	}
	
	/**
	* Компиляция шаблона
	* @param string реальный путь к файлу шаблона
	* @param string виртуальный путь к файлу шаблона
	* @retval string PHP-код (функции)
	*/
	protected function compile_template($path, $vpath)
	{
		$text = @ file_get_contents($path);
		if ( $text === false ) throw new Exception("read template fault: $path");
		$body = $this->compile($text, $vpath);
		$hash = md5($vpath);
		return "function tpl_$hash(\$tpl, \$tags){\n$body}\n";
	}
	
	/**
	* Компиляция каталога шаблонов
	* @param string реальный путь к каталогу с шаблонами
	* @param string виртуальный путь к каталогу с шаблонами
	* @retval string PHP-код
	*/
	protected function compile_dir($path, $vpath)
	{
		$files = dir_get_contents($path);
		if ( $files === false ) throw new Exception("open directory fault: $path");
		$result = array ();
		foreach($files as $file)
		{
			$filePath = makepath($path, $file);
			$fileVPath = "$vpath/$file";
			if ( is_dir($filePath) )
			{
				$result[] = $this->compile_dir($filePath, $fileVPath);
			}
			elseif ( substr($file, -4, 4) === '.tpl' )
			{
				$result[] = $this->compile_template($filePath, $fileVPath);
			}
		}
		return implode('', $result);
	}
	
	/**
	* Компиляция одиночного шаблона
	* @param string реальный путь к шаблону
	* @param string виртуальный путь к шаблону
	* @retval string текст PHP-сценария
	*/
	public function compile_file($path, $vpath)
	{
		return "<?php\n" . $this->compile_template($path, $vpath) . "?>";
	}
	
	/**
	* Компиляция всех шаблонов всех тем
	* @param string путь к каталогу с темами
	* @retval string текст PHP-сценария
	*/
	public function compile_all($path)
	{
		return "<?php\n" . $this->compile_dir($path, '') . "?>";
	}
}

?>