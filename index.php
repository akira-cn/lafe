<?php 

//echo "It works";

define(APPPATH, dirname(__FILE__).'/');

require_once dirname(__FILE__).'/smarty3/Smarty.layout.php';

require_once dirname(__FILE__).'/classes/layout/test.php';

$layout = new TestLayout();
//see layouts/test.php - function layout_a
$layout->a->display('index.tpl');