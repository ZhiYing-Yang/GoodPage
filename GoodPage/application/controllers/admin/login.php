<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 *
 */
class Login extends CI_Controller {

	/*
		登录页面
	*/
	public function index() {
		$this->load->view('admin/login.html');
	}

	/*
		登陆验证页
	*/
	public function login_in() {
		$code = $this->input->post('authcode');
		if (!isset($_SESSION)) {
			session_start();
		}
		//echo $code;
		if (strtolower($code) != $_SESSION['authcode']) {error('验证码错误');return;};
		//echo $code;die;
	}

	/*
		退出
	*/
	public function login_out() {
		$this->session->sess_destroy();
		succeed('admin/login', '成功退出');
	}

	/*
		验证码
	*/
	public function authcode() {
		/*if (!isset($_SESSION)) {
			session_start();
		}*/
		$img = imagecreatetruecolor(100, 40);
		$bgcolor = imagecolorallocate($img, rand(200, 255), rand(200, 255), rand(200, 255));
		imagefill($img, 0, 0, $bgcolor);
		$captch_code = "";
		$fontfile = './Soopafresh.ttf';
		for ($i = 0; $i < 4; $i++) {
			$fontsize = 20;
			$fontcolor = imagecolorallocate($img, rand(0, 100), rand(0, 100), rand(0, 100));
			$date = "abcdefghjkmnpqrstuvwxyz23456789";
			$fontcontent = substr($date, rand(0, strlen($date)), 1);
			$captch_code .= $fontcontent;

			$x = ($i * 100 / 4) + rand(5, 10);
			$y = rand(25, 30);

			imagettftext($img, $fontsize, 0, $x, $y, $fontcolor, $fontfile, $fontcontent);
		}
		$this->session->set_userdata(array('authcode' => $captch_code));
		//点干扰
		for ($i = 0; $i < 200; $i++) {
			$pointcolor = imagecolorallocate($img, rand(50, 200), rand(50, 200), rand(50, 200));
			imagesetpixel($img, rand(1, 99), rand(1, 29), $pointcolor);

		}

		//线干扰
		for ($i = 0; $i < 3; $i++) {
			$linecolor = imagecolorallocate($img, rand(80, 220), rand(80, 220), rand(80, 220));
			imageline($img, rand(1, 99), rand(1, 29), rand(1, 99), rand(1, 29), $linecolor);
		}

		header('content-type:image/png');
		imagepng($img);
	}
}
?>