<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller{
	
	public function __construct(){
		parent::__construct();
		$user_id=$this->session->userdata('user_id');
		$username=$this->session->userdata('username');
		if( empty($user_id) || empty($username) ){
			if(!isset($_COOKIE['token'])){
				$result=array(
					'code'=>401,
					'message'=>'',
				);
				get_type($result);
			}
			else{
				$cookie=md5($_COOKIE['token']);
				$status=$this->db->select('user_id, username')->get_where('users', array('cookie'=>"$cookie"))->result_array();
				if(empty($status)){
					$result=array(
						'code'=>401,
						'message'=>'非法操作，请先登录',
					);
					get_type($result);
				}
				else{
					// unset($status[0]['password'], $status[0]['cookie']);
					// $result=array(
					// 	'code'=>200,
					// 	'message'=>'登陆成功',
					// 	'data'=>$status[0],
					// );
					$array=array(
						'user_id'=>$status[0]['user_id'],
						'username'=>$status[0]['username'],
					);
					$this->session->set_userdata($array);
				}
			}
		}
	}
}
?>