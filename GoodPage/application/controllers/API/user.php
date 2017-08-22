<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
用户个人操作
 */
class User extends MY_controller {
	public function __construct() {
		parent::__construct();
		$this->load->model('API_model');
	}
	/*
		修改签名 昵称 头像
	*/
	public function edit_userinfo() {
		$user_id = $this->session->userdata('user_id');
		$json = $this->input->raw_input_stream;
		$data = json_decode($json, true);

		//修改签名
		if (isset($data['self_introduction']) && !empty($data['self_introduction'])) {
			$datas['self_introduction'] = $data['self_introduction'];
		}

		//修改昵称
		if (isset($data['name']) && !empty($data['name'])) {
			$isset = $this->db->select('user_id')->get_where('users', array('name' => $data['name']))->result_array();
			if (!empty($isset)) {
				$result = array(
					'code' => 400,
					'message' => '该昵称已存在',
				);
				get_type($result);
			}
			$datas['name'] = $data['name'];
		}

		//修改头像
		if (isset($data['head_portrait']) && !empty($data['head_portrait'])) {
			$datas['head_portrait'] = $data['head_portrait'];

		}

		//修改性别
		if (isset($data['sex']) && !empty($data['sex'])) {
			if ($data['sex'] == '男' || $data['sex'] == '女') {
				$datas['sex'] = $data['sex'];
			} else {
				$result = array(
					'code' => 400,
					'message' => '请输入正确的性别',
				);
				get_type($result);
			}
		}

		//执行修改操作
		if (!empty($datas)) {
			$status = $this->db->update('users', $datas, array('user_id' => $user_id));
			if ($status) {
				$result = array(
					'code' => 200,
					'message' => '修改成功',
				);
			} else {
				$result = array(
					'code' => 400,
					'message' => '修改失败，请稍后重试',
				);
			}
			get_type($result);
		} else {
			$result = array(
				'code' => 400,
				'message' => '请输入要修改的信息',
			);
			get_type($result);
		}
	}

	/*
		添加关注
	*/
	public function follow() {
		$user_id = $this->session->userdata('user_id');
		$follow_user_id = $this->uri->segment(4);
		$cancel = $this->uri->segment(5);
		$to_user_id = $follow_user_id;
		//如果get过来的id不为数字
		if (!is_numeric($follow_user_id)) {
			$result = array(
				'code' => 400,
				'message' => '该用户不存在',
			);
			get_type($result);
		}
		if ($user_id == $follow_user_id) {
			$result = array(
				'code' => 400,
				'message' => '不能关注自己',
			);
			get_type($result);
		}
		//如果cancel有值 且值为0  为取消关注
		if ($cancel == 0) {
			$status = $this->db->select('follow')->get_where('users', array('user_id' => $user_id))->result_array();
			if (!empty($status)) {
				$follow_str = $status[0]['follow'];
				$follow_str = str_replace($follow_user_id . '-', '', $follow_str);
				//echo $follow_str;die;
				$this->db->update('users', array('follow' => $follow_str), array('user_id' => $user_id));
			}
			//取消关注推送 type=>0
			$client = $this->input->get_request_header('requestType', TRUE);
			if (empty($client)) {
				$title = 'sync_info';
				$content_arr = array(
					'type' => 0,
					'user_id' => $user_id,
				);
				$content = json_encode($content_arr);
				$status = $this->API_model->to_Account($title, $content, $to_user_id);
				//var_dump($status);die;
			}
			$result = array(
				'code' => 200,
				'message' => '已取消关注',
			);
			get_type($result);
		}

		//判断是否之前已经添加过关注
		$status = $this->db->select('follow')->get_where('users', array('user_id' => $user_id))->result_array();
		$follow_str = $status[0]['follow'];
		if (!empty($follow_str)) {
			$follow_arr = explode('-', $follow_str, -1);
			if (in_array($follow_user_id, $follow_arr)) {
				$result = array(
					'code' => 200,
					'message' => '已关注',
				);
				get_type($result);
			}

		}

		//关注的用户id用-分隔开
		$follow_user_id .= '-';
		$sql = 'UPDATE users SET follow=CONCAT(follow,' . "'{$follow_user_id}'" . ') WHERE user_id=' . $user_id;
		$status = $this->db->query($sql);
		if ($status) {
			//关注推送 type=>1
			$client = $this->input->get_request_header('requestType', TRUE);
			if (empty($client)) {
				$title = 'sync_info';
				$content_arr = array(
					'type' => 1,
					'user_id' => $user_id,
				);
				$content = json_encode($content_arr);
				$status = $this->API_model->to_Account($title, $content, $to_user_id);
				//var_dump($status);die;
			}
			$result = array(
				'code' => 200,
				'message' => '已关注',
			);
		} else {
			$result = array(
				'code' => 400,
				'message' => '网络错误，请稍后重试',
			);
		}
		get_type($result);

	}

	/*
		通过cookie获取用户信息
	*/
	public function get_myinfo() {
		$cookie = md5($_COOKIE['token']);
		$get_info = 'user_id, username, email, tel_number, follow, feature_code, name, sex, head_portrait, self_introduction, create_time, is_autonym';
		$status = $this->db->select($get_info)->get_where('users', array('cookie' => $cookie))->result_array();
		if ($status) {
			$status[0]['head_portrait'] = urlencode($status[0]['head_portrait']);
			$result = array(
				'code' => 200,
				'message' => '用户信息获取成功',
				'data' => $status[0],
			);
		} else {
			$result = array(
				'code' => 400,
				'message' => '用户信息获取失败',
			);
		}
		get_type($result, 1);
	}

}
?>