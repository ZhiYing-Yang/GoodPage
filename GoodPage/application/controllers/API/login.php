<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 *客户端登陆接口控制器
 */
class Login extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model('API_model');

	}

	/*
		*登录接口 post
	*/
	public function index() {
		$this->load->helper('cookie');
		$json = $this->input->raw_input_stream;
		$data = json_decode($json, true);
		//微信登陆
		//openid是否存在
		if (isset($data['openid']) && !empty($data['openid'])) {

			//判断数据库中有没有该用户以前的登陆信息
			$get_info = 'user_id, head_portrait, name, sex';
			$status = $this->db->select($get_info)->get_where('users', array('username' => $data['openid']))->result_array();
			if (!empty($status)) {
				$cookie = $data['openid'] . mt_rand(0, 9999);
				$this->db->update('users', array('last_login_time' => date('Y-m-d H:i:s', time()), 'cookie' => md5($cookie)), array('user_id' => $status[0]['user_id']));
				set_cookie('token', $cookie, 7 * 24 * 60 * 60);
				$datas = $status[0];

				//$datas['head_portrait'] = urlencode($datas['head_portrait']);
				$result = array(
					'code' => 200,
					'message' => '登录成功',
					'data' => $datas,
				);
				//var_dump(json_encode($result, JSON_UNESCAPED_UNICODE));die;
				get_type($result, 1);
			}

			//若没有 则将其信息注册保存到数据库
			//检查昵称是否重复
			$name = isset($data['name']) ? $data['name'] : mb_substr($data['openid'], 10, 15);
			$status = $this->db->select('user_id')->get_where('users', array('name' => $data['name']))->result_array();
			if (!empty($status)) {
				$name = $data['name'] . mt_rand(0, 999999999);
			}
			$datas['username'] = $data['openid'];
			$datas['password'] = md5($data['openid']);
			$datas['head_portrait'] = $data['iconurl']; //头像 url地址
			$datas['sex'] = $data['gender']; //性别
			$datas['name'] = $name;
			$datas['create_time'] = date('Y-m-d H:i:s', time());
			$datas['last_login_time'] = date('Y-m-d H:i:s', time());
			$cookie = $datas['username'] . mt_rand(0, 9999);
			$datas['cookie'] = md5($cookie);
			$status = $this->API_model->register($datas);
			if ($status) {
				set_cookie('token', $cookie, 7 * 24 * 60 * 60);
				//第一次微信登陆返回的信息
				$back = array(
					'user_id' => $status,
					'head_portrait' => $data['iconurl'],
					'name' => $name,
					'sex' => $data['gender'],
				);
				$result = array(
					'code' => 200,
					'message' => '登录成功',
					'data' => $back,
				);
				get_type($result);
			}
			$result = array(
				'code' => 400,
				'message' => '登陆失败',
			);
			get_type($result);
		}

		//账号密码登陆
		$username = $data['username'];
		$password = $data['password'];

		//用户名和密码不能为空
		if (empty($username) || empty($password)) {
			$result = array(
				'code' => 400,
				'message' => '用户名密码不正确',
			);
			get_type($result);
		}

		//校验登录信息
		$data = array('username' => $username, 'password' => md5($password));
		$user_info = $this->API_model->get_user_info($data);
		if (empty($user_info)) {
			$result = array(
				'code' => 400,
				'message' => '用户名密码不正确',
			);
			get_type($result);
		} else {

			$time = date('Y-m-d H:i:s', time());
			$cookie = $username . mt_rand(0, 9999);
			$status = $this->db->update('users', array('last_login_time' => $time, 'cookie' => md5($cookie)), array('user_id' => $user_info[0]['user_id']));
			if (!$status) {
				$result = array(
					'code' => 400,
					'message' => '异常错误，请联系网络管理员',
				);
				get_type($result);
			};
			set_cookie('token', $cookie, 7 * 24 * 60 * 60);
			$array = array(
				'user_id' => $user_info[0]['user_id'],
				'username' => $user_info[0]['username'],
			);
			$this->session->set_userdata($array);
			$result = array(
				'code' => 200,
				'message' => '登录成功',
				'data' => $user_info[0],
			);

			//登录成功 返回数据
			get_type($result);
		}

	}

	/*
		注册接口 post
	*/

	//短信验证 接tel_number
	public function authcode() {
		$json = file_get_contents('php://input');
		$data = json_decode($json, true);
		$tel_number = $data['tel_number'];

		$this->check_tel($tel_number);

		//如果是注册操作 判断该手机号是否被注册
		if (!empty($data['action']) && $data['action'] == 'register') {
			$isset = $this->db->select('user_id')->get_where('users', array('tel_number' => $tel_number))->result_array();
			if (!empty($isset)) {
				$result = array(
					'code' => 400,
					'message' => '该手机号已被注册',
				);
				get_type($result);
			}
		}
		if (isset($_SESSION['telphone']) && $_SESSION['telphone'] == $tel_number) {
			$result = array(
				'code' => 400,
				'message' => '请求过于频繁',
			);
			get_type($result);
		}
		$this->session->set_tempdata('telphone', $tel_number, 60);

		$code = mt_rand(100000, 999999);
		$to = $tel_number;
		$data = array("$code");
		$templateId = '193998';
		$result = send_note($to, $templateId, $data);

		//验证码发送成功
		if ($result['code'] == 200) {
			$this->session->set_tempdata('code', $code, 300);
			$this->session->set_tempdata('this_tel_number', $tel_number, 300);
			get_type($result);
		}

		//验证码发送失败 返回错误信息
		$result = array(
			'code' => '400',
			'message' => '验证码发送失败',
		);
		get_type($result);
	}

	//验证注册
	public function register() {
		$json = file_get_contents('php://input');
		$data = json_decode($json, true);
		$tel_number = $data['tel_number'];
		$password = $data['password'];
		$authcode = $data['authcode'];
		//检查是否为获取验证码的手机号和验证码是否正确
		$this->second_check_tel($tel_number, $authcode);

		$isset = $this->db->select('user_id')->get_where('users', array('tel_number' => $tel_number))->result_array();
		if (!empty($isset)) {
			$result = array(
				'code' => 400,
				'message' => '该手机号已被注册',
			);
			get_type($result);
		}

		if (strlen($password) < 6) {
			$result = array(
				'code' => 400,
				'message' => '密码不能少于6位',
			);
			get_type($result);
		}

		$data['password'] = md5($password);
		$data['name'] = $tel_number;
		$data['username'] = $tel_number;
		$data['tel_number'] = $tel_number;
		$data['head_portrait'] = 'user_icon.png';
		$data['create_time'] = date('Y-m-d H:i:s', time());
		$data['last_login_time'] = date('Y-m-d H:i:s', time());
		unset($data['authcode']);
		$status = $this->API_model->register($data);
		if ($status) {
			$result = array(
				'code' => 200,
				'message' => '注册成功',
			);
			if (isset($_SESSION)) {
				unset($_SESSION);
				session_destroy();
			}
			get_type($result);
		} else {
			$result = array(
				'code' => 403,
				'message' => '注册失败，请稍后再试',
			);
			get_type($result);
		}
	}

	//修改密码

	public function edituser() {
		$json = file_get_contents('php://input');
		$data = json_decode($json, true);
		$tel_number = $data['tel_number'];
		$authcode = $data['authcode'];

		$status = $this->db->select('user_id')->get_where('users', array('tel_number' => $tel_number))->result_array();
		if (empty($status)) {
			$result = array(
				'code' => 400,
				'message' => '请输入正确的手机号码',
			);
			get_type($result);
		}

		//检查是否为获取验证码的手机号并检查验证码是否输入正确
		$this->second_check_tel($tel_number, $authcode);

		//密码不能少于6位
		$password = $data['password'];
		if (strlen($password) < 6) {
			$result = array(
				'code' => 400,
				'message' => '密码不能少于6位',
			);
			get_type($result);
		}

		$password = md5($password);
		$cookie = $tel_number . mt_rand(0, 9999);
		$status = $this->db->update('users', array('password' => $password, 'cookie' => md5($cookie)), array('tel_number' => $tel_number));
		if ($status) {
			$result = array(
				'code' => 200,
				'message' => '密码修改成功',
			);

			//删除session和cookie
			if (isset($_SESSION)) {
				unset($_SESSION);
				session_destroy();
			}

			if (isset($_COOKIE['token'])) {
				$this->load->helper('cookie');

				delete_cookie('token');
			}

			//重新返回cookie
			set_cookie('token', $cookie, 7 * 24 * 60 * 60);
			get_type($result);
		} else {
			$result = array(
				'code' => 400,
				'message' => '密码修改失败，请检查操作是否合法',
			);
			get_type($result);
		}
	}

	//注销登陆
	public function logout() {
		$this->load->helper('cookie');
		$user_id = $this->session->userdata('user_id');
		delete_cookie('token');
		unset($_COOKIE['token']);
		unset($_SESSION);
		session_destroy();
		$status = $this->db->update('users', array('cookie' => ''), array('user_id' => $user_id));
		if (!isset($_COOKIE['token'])) {
			$result = array(
				'code' => 200,
				'message' => '注销成功',
			);
		} else {
			$result = array(
				'code' => 400,
				'message' => '注销失败，请稍后重试',
			);
		}
		get_type($result);
	}
	//检测是否为手机号
	public function check_tel($tel_number) {
		if (!preg_match('/^1[3,5,7,8]\d{9}$/', $tel_number)) {
			$result = array(
				'code' => 400,
				'message' => '请输入正确的手机号码',
			);
			get_type($result);
		}
	}

	//二次检测用户输入手机号
	public function second_check_tel($tel_number, $authcode) {

		if (!isset($_SESSION['this_tel_number']) || empty($_SESSION['this_tel_number'])) {
			$result = array(
				'code' => 400,
				'message' => '验证码已过期，请重新获取',
			);
			get_type($result);
		}
		//检查是否为真正的手机号并检查是否被注册
		if ($tel_number != $_SESSION['this_tel_number']) {
			$result = array(
				'code' => 400,
				'message' => '请输入正确的手机号码',
			);
			get_type($result);
		}

		if ($authcode != $_SESSION['code']) {
			$result = array(
				'code' => 400,
				'message' => '验证码输入错误',
			);
			get_type($result);
		}
	}

}

?>