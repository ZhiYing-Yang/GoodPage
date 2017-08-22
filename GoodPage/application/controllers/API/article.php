<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
文章接口控制器
 */
class Article extends MY_Controller {
	public function ceshi() {
		$result = array(
			'code' => 200,
			'message' => '自动登录成功',
		);
		get_type($result);
	}
}

?>