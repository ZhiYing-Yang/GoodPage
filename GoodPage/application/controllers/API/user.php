<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
用户个人操作
 */
class User extends MY_controller {
	/*
		修改签名 昵称 头像
	*/
	public function edit_userinfo() {
		$user_id = $this->session->userdata('user_id');
		$json = $this->input->raw_input_stream;
		$data = json_decode($json, true);

		//修改签名
		if (!empty($data['self_introduction'])) {
			$status = $this->db->update('users', array('self_introduction' => $data['self_introduction']), array('user_id' => $user_id));
			$msg = '个性签名';
			$this->isno($status, $msg);
		}

		//修改昵称
		if (!empty($data['name'])) {
			$isset = $this->db->select('user_id')->get_where('users', array('name' => $data['name']))->result_array();
			if (!empty($isset)) {
				$result = array(
					'code' => 400,
					'message' => '改用户名已存在',
				);
				get_type($result);
			}
			$status = $this->db->update('users', array('name' => $data['name']), array('user_id' => $user_id));
			$msg = '昵称';
			$this->isno($status, $msg);
		}

		//修改头像
		if (!empty($data['head_portrait'])) {
			$status = $this->db->update('users', array('head_portrait' => $data['head_portrait']), array('user_id' => $user_id));
			$msg = '头像';
			$this->isno($status, $msg);

		}

		//修改性别
		if (!empty($data['sex'])) {
			if ($data['sex'] == '男' || $data['sex'] == '女') {
				$status = $this->db->update('users', array('sex' => $data['sex']), array('user_id' => $user_id));
				$msg = '性别';
				$this->isno($status, $msg);
			} else {
				$result = array(
					'code' => 400,
					'message' => '请输入正确的性别',
				);
				get_type($result);
			}

		}
	}
	//判断成功与否
	public function isno($status, $msg) {
		if ($status) {
			$result = array(
				'code' => 200,
				'message' => $msg . '修改成功',
			);
		} else {
			$result = array(
				'code' => 400,
				'message' => $msg . '修改失败，请稍后重试',
			);
		}
		get_type($result);
	}

	/*
		添加关注
	*/
	public function follow() {
		$user_id = $this->session->userdata('user_id');
		$follow_user_id = $this->uri->segment(4);
		$cancel = $this->uri->segment(5);

		//如果get过来的id不为数字
		if (!is_numeric($follow_user_id)) {
			$result = array(
				'code' => 400,
				'message' => '该用户不存在',
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