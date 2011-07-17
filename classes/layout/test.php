<?php

class TestLayout extends SmartyLayout{
	function __construct(){
		parent::__construct();

		$this->template_dir = APPPATH."views/";
		
		$this->left_delimiter = '<%'; 
		$this->right_delimiter = '%>';
	}

	function layout_a(){
		$this->{"Header test"}=array('test'=>1);
		$this->{"Body test"}=array('test'=>2);
		$this->{"Body test"}=array('test'=>3);
		$this->{"Footer test"}=array('test'=>4);

		$this->{"Body b.Left test"}=array('test'=>5);
		$this->{"Body b.Right test"}=array('test'=>6);
		$this->{"Body b.Right test"}=array('test'=>7);

		$this->{"Body b#2.Left test"}=array('test'=>8);  //another b.Left，不和上面那个b.Left合在一起，所以加一个id
	}
}