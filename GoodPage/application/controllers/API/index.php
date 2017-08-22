<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/*
不登录状态下进入的 推荐和搜索页面
 */
class Index extends CI_Controller {
	/*
		构造函数 自动载入API_model
	*/
	public function __construct() {
		parent::__construct();

		$this->load->model('API_model');
	}
	/*
		推荐页面
			每次显示10条数据 下拉加载更多
	*/
	public function index() {
		$create_time = $this->uri->segment(5);
		$la = $this->uri->segment(4);
		$per_page = 10;
		$where = 'is_recommend=1 AND is_private=0';
		$data = $this->API_model->get_article_info($per_page, $create_time, $la, $where);
		if (empty($data)) {
			$result = array(
				'code' => 400,
				'message' => '没有了',
			);
			get_type($result);
		}
		//对查询结果处理 清楚html标签和截content
		$status = $this->API_model->format_data($data);
		$result = array(
			'code' => 200,
			'message' => '刷新成功',
			'data' => $status,
		);
		get_type($result, 1);
	}
	/*
		个人主页
	*/
	//app端个人主页
	public function app_myindex() {
		$get_user_id = $this->uri->segment(4);
		$this->get_myindex('app', $get_user_id);
	}
	//网页端
	public function web_myindex() {
		$get_user_id = $this->uri->segment(2);
		if (!is_numeric($get_user_id)) {
			$get_user_id = $this->uri->segment(4);
		}
		//echo $get_user_id;die;
		$this->get_myindex('web', $get_user_id);
	}
	public function get_myindex($duan = 'app', $get_user_id) {
		$my_user_id = $this->session->userdata('user_id');
		//获得用户信息
		$get_info = 'user_id, name, sex, head_portrait, self_introduction, is_autonym, follow';
		$status = $this->db->select($get_info)->get_where('users', array('user_id' => $get_user_id))->result_array();
		$user_info = $status[0];
		//p($user_info);
		//获得文章列表
		$get_info = 'article_id, title, create_time, content, type, read_total, praise';
		//$this->db->where('user_id', $user_id);
		if ($get_user_id == $my_user_id) {
			$status = $this->db->select($get_info)->order_by('create_time DESC')->get_where('article', array('user_id' => $get_user_id))->result_array();
		} else {
			$status = $this->db->select($get_info)->order_by('create_time DESC')->get_where('article', array('user_id' => $get_user_id, 'is_private' => 0))->result_array();
		}
		foreach ($status as $v) {
			$v['name'] = $user_info['name'];
			$v['head_portrait'] = urlencode($user_info['head_portrait']);
			$datas[] = $v;
		}
		$article = $this->API_model->format_data($datas);
		//p($article);
		//app端

		if ($duan == 'app') {
			$result = array(
				'code' => 200,
				'message' => '信息获取成功',
				'data' => array(
					'user_info' => $user_info,
					'article' => $article,
				),
			);
			get_type($result, 1);
		}
		//网页端
		else {
			$data['user_info'] = $user_info;
			$data['article'] = $article;
			//登陆用户关注的人
			$login_follow = '空';
			$login_user_id = $this->session->userdata('user_id');
			if (!empty($login_user_id)) {
				$login_follow = $this->db->select('follow')->get_where('users', array('user_id' => $login_user_id))->result_array();
				$login_follow = $login_follow[0]['follow'];

			}
			$data['user_info']['login_follow'] = $login_follow;
			$this->load->view('index/myindex.html', $data);
		}
	}

	/*
		最新页  展示所有发布的信息
	*/
	public function latest() {
		$create_time = $this->uri->segment(5);
		$la = $this->uri->segment(4);
		$per_page = 10;
		$where = 'is_private=0';
		$data = $this->API_model->get_article_info($per_page, $create_time, $la, $where);
		if (empty($data)) {
			$result = array(
				'code' => 400,
				'message' => '没有了',
			);
			get_type($result);
		}
		//对查询结果处理 清楚html标签和截content
		$status = $this->API_model->format_data($data);
		$result = array(
			'code' => 200,
			'message' => '刷新成功',
			'data' => $status,
		);
		get_type($result, 1);
	}

	/*
		查看文章

		1.先检测文章是否存在
	*/
	public function verify_content() {
		$article_id = $this->uri->segment(4);
		$get_info = 'article.user_id, name, head_portrait, type, praise';
		$sql = 'SELECT ' . $get_info . ' FROM users, article WHERE article.user_id=users.user_id AND article_id=' . $article_id;
		$status = $this->db->query($sql)->result_array();
		if ($status) {
			$data = $status[0];
			$comment_total = $this->db->where(array('article_id' => $article_id))->count_all_results('comment');
			$data['comment_total'] = "$comment_total";
			//url按需替换
			$data['url'] = urlencode($_SERVER['see_article_url'] . $article_id . '/none');
			$data['head_portrait'] = urlencode($data['head_portrait']);
			$result = array(
				'code' => 200,
				'message' => '该文章存在',
				'data' => $data,
			);
		} else {
			$result = array(
				'code' => 404,
				'message' => '未找到相关信息',
			);
		}
		get_type($result, 1);
	}
	public function see_article() {
		error_reporting(0);
		//error_reporting(E_ALL^E_NOTICE);
		$article_id = $this->uri->segment(2);
		$hidden = $this->uri->segment(3);
		$json = $this->input->raw_input_stream;
		$data = json_decode($json, true);
		if (isset($data['article_id']) && !empty($data['article_id'])) {
			$article_id = $data['article_id'];
		}
		if (isset($_GET['article_id'])) {
			$article_id = $_GET['article_id'];
		}
		if (!is_numeric($article_id) || !isset($article_id)) {
			$result = array(
				'code' => 400,
				'message' => '请输入正确的文章编号',
			);
			get_type($result);
		}

		//阅读量+1
		$sql = 'UPDATE article SET read_total=read_total+1 WHERE article_id=' . $article_id;
		$this->db->query($sql);
		//echo $article_id;
		//获取文章
		$get_info = "article_id, title, article.create_time, content, type, article.user_id, name, head_portrait, read_total, praise, self_introduction";
		$sql = "SELECT $get_info FROM article, users WHERE article_id=$article_id AND article.user_id=users.user_id";
		$article = $this->db->query($sql)->result_array();
		//p($article);
		if (!empty($article)) {
			$article = $article[0];
			$article['hidden'] = $hidden;
			//$this->load->view();
		} else {
			$result = array(
				'code' => 400,
				'message' => '未找到相关信息',
			);
			get_type($result);
		}

		//获取完善信息
		$article_field = $this->db->get_where('article_field', array('article_id' => $article_id))->result_array();
		//echo $this->db->last_query();
		if (!empty($article_field)) {
			$data['article_field'] = $article_field[0];
		} else {
			$data['article_field'] = '';
		}
		//p($article_field);die;
		//获取评论
		$limit = 'LIMIT 10';
		$comment = $this->API_model->get_comment($article_id, $limit);
		$article['comment_total'] = $this->db->where(array('article_id' => $article_id))->count_all_results('comment');
		$data['article'] = $article;
		$data['comment'] = $comment;
		switch ($article['type']) {
		case '活动':
			$this->load->view('index/huodong.html', $data);
			break;
		default:
			$this->load->view('index/wenzhang.html', $data);
		}

		//get_type($result);
	}

	/*
		搜索接口	通过关键字搜索
	*/
	public function search() {
		$json = $this->input->raw_input_stream;
		$data = json_decode($json, true);
		if (empty($data['keywords'])) {
			if (!empty($this->uri->segment(4))) {
				$keywords = $this->uri->segment(4);
			} else if (isset($_GET['keywords'])) {
				$keywords = $_GET['keywords'];
			} else {
				$result = array(
					'code' => 400,
					'message' => '请输入关键字',
				);
				get_type($result);
			}
		} else {
			$keywords = $data['keywords'];
		}
		//echo $keywords;die;
		$keywords = $this->db->escape_like_str($keywords);

		$status = $this->API_model->get_search($keywords);
		//p($status);

		if (empty($status)) {
			$result = array(
				'code' => 400,
				'message' => '未找到相应结果',
			);
		} else {
			$status = $this->API_model->format_data($status);
			$result = array(
				'code' => 200,
				'data' => $status,
			);
		}
		get_type($result, 1);
	}

	/*
		对文章的点赞
	*/
	public function praise_article() {
		$article_id = $this->uri->segment(4);
		if (!is_numeric($article_id)) {
			exit;
		}
		$sql = 'UPDATE article SET praise=praise+1 WHERE article_id=' . $article_id;
		$this->db->query($sql);
		//echo $this->db->last_query();
	}

	/*
		对评论的点赞
	*/
	public function praise_comment() {
		$comment_id = $this->uri->segment(4);
		if (!is_numeric($comment_id)) {
			exit;
		}
		$sql = 'UPDATE comment SET praise=praise+1 WHERE comment_id=' . $comment_id;
		$this->db->query($sql);
	}

	/*
		举报反馈
	 */
	public function accuse() {
		$this->load->view('index/jubao.html');
	}
	/*
			好友圈

		public function friend_page(){
			$json=$this->input->raw_input_stream;
			$data=json_decode($json, true);
			if(empty($data)){
				$result=array(
					'code'=>400,
					'message'=>'未找到相应结果',
				);
				get_type($result);
			}
			//p($data);
			$str='';
			foreach($data['data'] as $tel){
				$str =$str.' tel_number='.$tel.' OR';
			}
			$str=substr($str, 0, -2);
			//echo $str;die;
			$sql='SELECT user_id FROM users WHERE'.$str;
			$status=$this->db->query($sql)->result_array();
			if(empty($status)){
				$result=array(
					'code'=>400,
					'message'=>'未找到相应结果',
				);
				get_type($result);
			}

			$str='';
			foreach($status as $v ){
				$str .=' article.user_id='.$v['user_id'].' OR';
			}
			$str=substr($str, 0, -2);
			//echo $str;die;

			//分页显示
			$all=$this->db->where( array('is_recommend'=>1) )->count_all_results('article');
			$per_page=20;	//每页显示多少条数据
			$page=$this->uri->segment(4); //从第几条开始显示0 10 20...
			if(empty($page) || !is_numeric($page) || $page<=0){
				$page=1;
			}
			$offset=$per_page*($page-1);
			//echo $offset;
			if($offset>$all){
				$result=array(
					'code'=>400,
					'message'=>'没有更多了',
				);
				get_type($result);
			}
			if($offset < 0 ){
				$offset=0;
				$result=array(
					'code'=>400,
					'message'=>'已经是第一页了',
				);
				get_type($result);
			}

			$status=$this->API_model->get_friendpage_info($str, $per_page, $offset);
			//p($status);

			$status=$this->API_model->format_data($status);
			$result=array(
				'code'=>200,
				'message'=>'刷新成功',
				'data'=>$status
			);
			get_type($result, 1);
	*/

	/*
		查数据库表
	*/
	public function select() {
		$table = $this->uri->segment(4);
		$status = $this->db->get($table)->result_array();
		p($status);
	}
} //class结束

?>