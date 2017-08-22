<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config = array(
	'article'=>array(
		array(
			'field'=>'title', // 表单name值
			'label'=>'标题',//错误提示语Ps:标题不能为空
			'rules'=>'required|min_length[5]' //规则 多个规则用|隔开
		)
	),
	'users'=>array(
		array(
			'field'   =>'username',
			'label'   =>'用户名',
			'rules'   => 'required|min_length[6]|alpha_numeric'
		),
		array(
			'field'   =>'password',
			'label'   =>'密码',
			'rules'   =>'required',
		)
	),
	'select'=>array(
		array(
			'field'=>'radio',
			'lable'=>'选择框',
			'rules'=>'required|less_than_equal_to[1]'
		),
		array(
			'field'=>'nav_value',
			'label'=>'栏目名',
			'rules'=>'required|alpha'
		)
	),
);
?>