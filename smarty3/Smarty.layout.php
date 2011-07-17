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
	protected $_la_layout; //存放当前layout
	protected $_la_path;   //存放layout到module的层级，每次添加模块用

	protected $_la_page_struct = array(); //存放render之后的结构化数据，包含了模板构建所需要的全部信息和模块用到的数据
	protected $_la_layout_xmap = array();

	protected $_la_data; //传递给layout_func用于渲染处理的变量
	
	//Smarty渲染用的配置
	protected $_la_template;		//存放初始化的模板

	protected $_la_space;		//用来存放layout路径的前缀

	/*
		存放查找模板文件的dir路径
		如果在系统默认的两个路径上找不到模板文件
		系统会在这个数组所存路径的{$type}下依次查找
		直到查找到文件所在位置为止
		注意，设置的文件路径优先级低于应用模板当前路径，高于系统路径
	*/
	protected $_la_find_dirs = array();

	function __construct($data=NULL){
		$this->_la_data = $data;	//渲染前传递给layout_func的数据
		parent::__construct();
	}
	
	/**
		为tpl增加一个查找目录，当在当前应用的模板文件目录下查找不到文件的时候
		会去添加的目录中依次查找
		eg: $this->add_dir(APPPATH."views/myapp/");
	 */
	public function add_dir($path){
		array_push($this->_la_find_dirs, $path);
	}

	/**
	    “渲染”当前的模板
		实际上所做的事情是将数据组织好准备传给拼合好的模块
	 */
	protected function _render(){
		if(isSet($this->_la_layout)){ //如果有layout，渲染layout
			$this->_la_space = $this->_la_layout;
			$this->{"layout_".$this->_la_layout}($this->_la_data);

			$this->_la_page_struct = array_pop($this->_la_page_struct);
			
			if($this->_la_page_struct){
				$_data = $this->_la_page_struct['layout'] + array('file' => $this->_la_page_struct['url']);
				$this->assignGlobal('layout', $_data);
			}
		} //如果没有，什么也不做，直接渲染模板
	}
	
	/**
		重载Samrty的fetch，render完再fetch
	 */
	public function fetch($template, $cache_id = null, $compile_id = null, $parent = null, $display = false){
		$this->_la_template = $template;
		if(!$display)
			$this->_render();
		return parent::fetch($template, $cache_id, $compile_id, $parent, $display);
	}

	/**
		重载Smarty的display, render完再display
	 */
	public function display($template, $cache_id = null, $compile_id = null, $parent = null){
		$this->_la_template = $template;
		$this->_render();
		parent::display($template, $cache_id, $compile_id, $parent);
	}
	
	/**
		查找指定的文件
		根据文件名、类型、扩展名进行查找
		会先查找当前应用的模板下面的对应类型的路径
		找不到会查找用户定义的路径下面对应类型的路径
		再找不到会从模板根目录进行查找
	 */
	protected function _find($file, $type, $ext='tpl'){
		/*
			eg: 
				template_dir : views/
				_la_template : sample/test.tpl 
				_find : a/b module

				=> views/sample/module/a/b.tpl
				=> views/module/a/b.tpl
		*/
		$tplpath = dirname($this->_la_template);
		$subpath = "{$type}/{$file}".".{$ext}"; 

		//先在应用的当前路径下找
		$fullpath = $this->template_dir."{$tplpath}/".$subpath;  
		
		if(!file_exists($fullpath)){
			foreach($this->_find_dirs as $dir){
				$fullpath = $dir.$subpath;
				if(file_exists($fullpath)){
					break;
				}
			}
		}

		//找不到去系统路径下找
		if(!file_exists($fullpath))
			$fullpath = $this->template_dir.$subpath; 
		if(!file_exists($fullpath)){ 
			throw new Exception("can't find layout files. {$sys_file} | {$app_file}");
		}
		return $fullpath;
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
		
		//layout可能级联，所以_la_path要explode
		/*
			_la_path的结构为 LayoutPart(.layout_name::LayoutPart)*
		 */
		$parts = explode(' ',$this->_la_path); //模块前面的部分
		$count = count($parts);

		$page_struct = &$this->_la_page_struct;
		
		$_layout_xpath = array();

		//寻路
		for($i = 0; $i < $count; $i++){
			
			$part = $parts[$i]; //part也是唯一标识
			$layout_part = explode('.', $part);
			$layout_name = $layout_part[0];		//Layout的名字 = $layout_file#id
			$layout_body = $layout_part[1];		//Layout当前容器的名字
			$layout_file = explode('#', $layout_name);
			$layout_id = $layout_file[1];
			$layout_file = $layout_file[0];
			
			array_push($_layout_xpath, $layout_name);

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
				
				$this->_la_layout_xmap[join(' ',$_layout_xpath)] = &$layout_info['layout'];

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
		}

		array_push($page_struct, $module);
		
		unset($page_struct);

		return $this;	
	}
	
	/**
		根据name、value生成对应的module数据结构
	 */
	protected function _add($name, $value, $type){
		$file = $this->_find($name, $type);
		$module = array('url'=>$file,'data'=>$value);
		return $module;
	}
	
	/**
		magic方法
		支持一种简单的写法
		$this->{'PartA layout_b.PartB module'} = array(...);
	 */
	public function __set($key, $value){
		$tokens = explode(' ',$key);
		if(count($tokens) < 2){
			return;
		}else if(count($tokens) >= 2){
			$name = array_pop($tokens);
			$tokens = preg_replace('/^\./','',$tokens);
			$this->_la_path = strtolower($this->_la_space.'.'.join(' ', $tokens)); //补全前面的layout名

			$this->add($name, $value);
		}	
	}

	/**
		magic方法
		$this->a->display();  //按名为a的layout渲染（自动调用 $layout->layout_a();
		$this->Layout->add();
		$this->{Part1 layout_2.Part2}->add();
		...
	 */
	public function  & __get($key){
		if(preg_match('/^[A-Z]/',$key)){ //大写字母开头，默认为layout的部分，在模板中的变量为 $layout.layout_$key小写
			$this->_la_path = strtolower($this->_la_space.'.'.$key); //补全前面的layout名
		}
		else{ //是layout处理器的名字
			$this->_la_layout = $key;
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

	public function with($space){
		$this->_la_space = $space;
	}

	public function endwith(){
		$this->_la_space = $this->_la_layout;
	}
}