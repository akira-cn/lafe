<?php

require_once dirname(__FILE__)."/Smarty.class.php";

class SmartyLayout extends Smarty{
	/*
		默认的layout的路径，为$view的目录下的tpl文件
		这个下面的文件通常内容为
		<%extends $layout.file%>

		<%block moduleA%>
		 ...
		<%/block%>
		 ...
	*/
	protected $_layout; //存放当前layout
	protected $_la_path;   //存放layout到module的路径，每次添加模块用
	protected $_page_struct = array();
	protected $_data;
	protected $_open_setter = true;

	function __construct(){
		parent::__construct();
	}

	protected function _render($data=NULL){
		if(isSet($this->_layout)){
			$this->{"layout_".$this->_layout}($data);

			$this->_page_struct = array_pop($this->_page_struct);
			
			if($this->_page_struct){
				$this->_data = $this->_page_struct['layout'] + array('file' => $this->_page_struct['url']);
				$this->assignGlobal('layout', $this->_data);
			}
		}
	}
	
	public function fetch($template, $cache_id = null, $compile_id = null, $parent = null, $display = false){
		if(!$display)
			$this->_render();
		return parent::fetch($template, $cache_id, $compile_id, $parent, $display);
	}

	public function display($template, $cache_id = null, $compile_id = null, $parent = null){
		$this->_render();
		parent::display($template, $cache_id, $compile_id, $parent);
	}

	protected function _find($file, $type, $ext='tpl'){
		$file_name = explode('/', $file);
		$file_name = $file_name[count($file_name) - 1];
		$dir_name = dirname($file);

		$app_file = $this->template_dir."{$dir_name}/{$type}/{$file_name}".".{$ext}"; 
		$sys_file = $this->template_dir."{$type}/{$file_name}".".{$ext}"; 

		//echo file_exists($sys_file); exit();

		if(file_exists($app_file)) return $app_file;
		else if(file_exists($sys_file)) return $sys_file;
		else{ 
			throw new Exception("can't find layout files. {$sys_file} | {$app_file}");
		}
	}
	//查找对应的模板
	protected function find_layout($name){
		return $this->_find($name, 'layout');
	}
	
	//查找对应的模板
	protected function find_module($name){
		return $this->_find($name, 'module');
	}
	
	//外链的交给Layout文件去处理
	protected function find_css($name){
		return $this->_find($name, 'module/css', 'css');
	}

	//外链的交给Layout文件去处理
	protected function find_js($name){
		return $this->_find($name, 'module/js', 'js');
	}

	/**
	 	add一个module或者一个Layout 
		一个module是局部最小单位，不能再嵌套任何东西
		一个layout可以嵌套layout或module
		@param $part : 模块加载到layout的哪个部分
		@param $name : 加载的模块名字，根据名字和对应规则去查找模块

		Layout add 对象的时候有个$name的优先级查找规则
		首先查找当前layout目录下指定的layout或者module
		接着查找app的views目录下指定的layout或者module
		
		$this->Main->add("test", array(...));
		访问 $this->{'Main test'} = array(...);

		layoutName#id.PartName:class (TODO: 支持伪类： class = asyn | hover | ect..)
	 */
	protected function add($name, $value, $type='module'){
		
		if(!isSet($this->_la_path)){
			throw new Exception('you must spicified a part of layout!');
		}
		
		$module = $this->_add($name, $value, $type); //要add的模块
		
		//print_r(array($this->_la_path, $name, $value));
		//layout可能级联，所以_la_path要explode
		/*
			_la_path的结构为 LayoutPart(.layout_name::LayoutPart)*
		 */
		$parts = explode(' ',$this->_la_path); //模块前面的部分
		$count = count($parts);

		$page_struct = &$this->_page_struct;

		//寻路
		for($i = 0; $i < $count; $i++){
			
			$part = $parts[$i]; //part也是唯一标识
			$layout_part = explode('.', $part);
			$layout_name = $layout_part[0];		//Layout的名字 = $layout_file#id
			$layout_body = $layout_part[1];		//Layout当前容器的名字
			$layout_file = explode('#', $layout_name);
			$layout_id = $layout_file[1];
			$layout_file = $layout_file[0];

			$found = null;
			foreach($page_struct as &$part_struct){
				$_id = $part_struct['_id'];

				if(!strcmp($layout_name, $_id)){ //找到相同的name， 准备往里面插入数据
					$found = &$part_struct;
					unset($part_struct);
					break;
				}
			}
			
			if(!$found){ // 如果没找到layout建立一个
				$layout_info = array(
					'_id' => $layout_name,
					'url' => $this->find_layout($layout_file),
					'id' => $layout_id,
					'layout' => array(),
				);
				array_push($page_struct, &$layout_info);
				$found = &$layout_info;

				unset($layout_info);
			}

			$_body = $found['layout'][$layout_body];

			if(!isSet($_body)) //如果没有设置过这个body
			{
				$found['layout'][$layout_body] = array(); //设置这个body，以供插入数据
			}

			$page_struct = &$found['layout'][$layout_body];
			
			unset($found);	
			
			$path .= ' ';
		}

		array_push($page_struct, $module);
		
		unset($page_struct);

		return $this;	
	}

	protected function _add($name, $value, $type){
		$file = $this->_find($name, $type);
		$module = array('url'=>$file,'data'=>$value);
		return $module;
	}
	
	/**
	 * 支持一种简单的写法
	 * $this->{'PartA layout_b.PartB module'} = array(...);
	 */
	public function __set($key, $value){
		$key = preg_replace('/^\./','',$key); //可以以::开头，表示省略当前layout名
		$tokens = explode(' ',$key);
		if(count($tokens) < 2){
			return;
		}else if(count($tokens) >= 2){
			$name = array_pop($tokens);
			$tokens = preg_replace('/^\./','',$tokens);
			$this->_la_path = $this->_layout.'.'.strtolower(join(' ', $tokens)); //补全前面的layout名

			$this->add($name, $value);
		}	
	}

	/**
		magic方法
		$this->a->render();  //按名为a的layout渲染
		$this->Layout->add();
		$this->{Part1 layout_2.Part2}->add();
		...
	 */
	public function  & __get($key){
		$key = preg_replace('/^\./','',$key); //可以以.开头，表示省略当前layout名
		if(preg_match('/^[A-Z]/',$key)){ //大写字母开头，默认为layout的部分，在模板中的变量为 $layout.layout_$key小写
			$this->_la_path = $this->_layout.'.'.strtolower($key); //补全前面的layout名
		}
		else{ //是layout处理器的名字
			$this->_layout = $key;
		}
		
		$this->_la_path = strtolower($key);

		return $this;
	}

	/**
		给layout添加css样式
		css样式统一给layout管理
		如果是modules的css也是一样会给最外层的layout管理
		将css交给 $this->Css管理
	 */
	protected function css($name){
		$file = $this->find_css($name);
		$this->{"Css {$name}"} = array('src' => $file);
	}

	/**
		给layout统一添加js
		会在当前流的最后添加js
		所以一般要选择 $this->head->add， $this->foot->add
	 */
	protected function js($name, $header = TRUE){
		$file = $this->find_js($name);
		if($header) $this->{"Js_header {$name}"} = array('src' => $file);
		else $this->{"Js_footer {$name}"} = array('src' => $file);
	}
}