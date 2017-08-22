<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once 'API/upload/autoload.php';
//需要登录才能访问的接口
// 引入鉴权类
use Qiniu\Auth;

// 引入上传类

class Authorised extends MY_Controller {
	public function __construct() {
		parent::__construct();
		$this->load->model('API_model');
	}
	/*
		我的好页页市展示接口
	*/
	public function index() {
		$user_id = $this->session->userdata('user_id');
		$create_time = $this->uri->segment(5);
		$la = $this->uri->segment(4);
		$per_page = 10;
		$status = $this->API_model->get_mypage_info($user_id, $per_page, $create_time, $la);
		if (empty($status)) {
			$result = array(
				'code' => 400,
				'message' => '没有了',
			);
			get_type($result);
		}

		$status = $this->API_model->format_data($status);

		$result = array(
			'code' => 200,
			'message' => '刷新成功',
			'data' => $status,
		);
		get_type($result, 1);
	}

	/*
		我的好友
	*/
	public function myfriend() {
		$json = $this->input->raw_input_stream;
		$data = json_decode($json, true);
		if (empty($data)) {
			$result = array(
				'code' => 400,
				'message' => '未找到相应结果',
			);
			get_type($result);
		}
		$str = '';
		foreach ($data['data'] as $feature_code) {
			$str .= ' feature_code= "' . $feature_code . '" OR';
		}
		$str = substr($str, 0, -2);

		$status = $this->API_model->get_myfriend_info($str);

		$result = array(
			'code' => 200,
			'message' => '匹配成功',
			'data' => $status,
		);
		get_type($result);
	}

	/*
		1.发布文章 接口
	*/
	public function create_article() {
		$json = $this->input->raw_input_stream;
		$data = json_decode($json, true);

		//如果存在article_id 且不为空 则为修改文章
		if (isset($data['article_id']) && is_numeric($data['article_id'])) {
			//只有文章 相册 问答类型的可以修改
			$this->edit_article($data);
		}
		$datas = array(
			//客户端传来数据
			'title' => preg_replace(array('/</', '/>/'), array('&lt;', '&gt;'), $data['title']),
			'type' => $data['type'],
			'content' => $data['content'],
			'user_id' => $this->session->userdata('user_id'),

			//服务端生成数据
			'create_time' => date('Y-m-d H:i:s', time()),
		);

		//插入数据库
		$status = $this->db->insert('article', $datas);
		if ($status) {
			$article_id = $this->db->insert_id();
			$is_private = $this->db->select('is_private')->get_where('article', array('article_id' => $article_id))->result_array();
			$picurl = '';
			if (preg_match("/<img .*?src=[\'\"]([^<>]*?)[\'\"]/", $datas['content'], $matchs)) {
				$picurl = $matchs[1];
			}
			$content = mb_substr($datas['content'], 0, 60);
			$content = preg_replace('/(<(img|IMG)\s+(.*)[^>])$/', '', $content);
			$content = preg_replace('/<\/?\s*\w+.*?>/', '', $content);
			$result = array(
				'code' => 200,
				'message' => '发布成功',
				'data' => array(
					'article_id' => $article_id,
					'url' => urlencode($_SERVER['see_article_url'] . $article_id . '/none'),
					'title' => $datas['title'],
					'content' => $content,
					'picurl' => urlencode($picurl),
					'is_private' => $is_private[0]['is_private'],
				),
			);
		} else {
			$result = array(
				'code' => 400,
				'message' => '发布失败！网路错误，请稍后再试',
			);
		}
		get_type($result, 1);
	}

	/*
		修改文章
	*/
	public function edit_article($data) {
		$datas = array(
			'title' => $data['title'],
			'content' => $data['content'],
			'create_time' => date('Y-m-d H:i:s', time()),
		);
		$status = $this->db->update('article', $datas, array('article_id' => $data['article_id']));
		if ($status) {
			$picurl = '';
			if (preg_match("/<img .*?src=[\'\"]([^<>]*?)[\'\"]/", $datas['content'], $matchs)) {
				$picurl = $matchs[1];
			}
			//文章是否私有
			$is_private = $this->db->select('is_private')->get_where('article', array('article_id' => $data['article_id']))->result_array();

			$content = mb_substr($datas['content'], 0, 60);
			$content = preg_replace('/(<(img|IMG)\s+(.*)[^>])$/', '', $content);
			$content = preg_replace('/<\/?\s*\w+.*?>/', '', $content);
			$result = array(
				'code' => 200,
				'message' => '修改成功',
				'data' => array(
					'article_id' => $data['article_id'],
					'url' => urlencode($_SERVER['see_article_url'] . $data['article_id'] . '/none'),
					'title' => $datas['title'],
					'content' => $content,
					'picurl' => $picurl,
					'is_private' => $is_private[0]['is_private'],
				),
			);
		} else {
			$result = array(
				'code' => 400,
				'message' => '修改失败,请稍后重试',
			);
		}
		get_type($result, 1);
	}

	/*
		获取要修改的文章接口
	*/
	public function the_article() {
		$article_id = $this->uri->segment(4);
		if (!is_numeric($article_id)) {
			$result = array(
				'code' => 400,
				'message' => '请输入正确的文章编号',
			);
			get_type($result);
		}

		$get_info = 'title, type, content';
		$status = $this->db->select($get_info)->get_where('article', array('article_id' => $article_id))->result_array();
		if (empty($status)) {
			$result = array(
				'code' => 400,
				'message' => '未找到相关信息，请检查文章编号是否正确',
			);
			get_type($result);
		}
		$article = $status[0];
		$type = $status[0]['type'];
		//如果是修改投票信息 查投票表
		//p($type);die;
		if ($type == '投票') {
			$status = $this->db->select('option')->get_where('vote', array('article_id' => $article_id))->result_array();
			$status = array_column($status, 'option');
			$article_field = array('data' => $status);
		} else {
			$get_info = 'article_field_id, field1, field2, field3, is_sign_up, is_name, is_tel_number, extra1, extra2';
			$status = $this->db->select($get_info)->get_where('article_field', array('article_id' => $article_id))->result_array();
			if (!empty($status)) {
				$article_field = $status[0];
			} else {
				$article_field = '';
			}
		}
		$result = array(
			'code' => 200,
			'message' => '文章信息获取成功',
			'data' => array(
				'article' => $article,
				'extra_info' => $article_field,
			),
		);
		get_type($result);
	}

	/*
		删除文章 连带评论 完善信息一起删除 未完待续
	*/
	public function delete_article() {
		$article_id = $this->uri->segment(4);

		if (!is_numeric($article_id)) {
			$result = array(
				'code' => 400,
				'message' => '文章编号不正确',
			);
			get_type($result);
		}
		$user_id = $this->db->select('user_id')->get_where('article', array('article_id' => $article_id))->result_array();
		//p($user_id);die;
		if (empty($user_id) || $user_id[0]['user_id'] != $this->session->userdata('user_id')) {
			$result = array(
				'code' => 400,
				'message' => '操作非法',
			);
			get_type($result);
		}
		$status1 = $this->db->delete('article', array('article_id' => $article_id, 'user_id' => $this->session->userdata('user_id')));

		if ($status1) {
			$status2 = $this->db->delete('article_field', array('article_id' => $article_id));
			$status3 = $this->db->delete('vote', array('article_id' => $article_id));
			$status4 = $this->db->delete('comment', array('article_id' => $article_id));

			//
			//删除报名信息 未完待续
			//

			$result = array(
				'code' => 200,
				'message' => '删除成功',
			);
		} else {
			$result = array(
				'code' => 400,
				'message' => '删除失败',
			);
		}
		get_type($result);
	}

	/*
		2.完善字段接口
	*/
	public function article_field() {
		$json = $this->input->raw_input_stream;
		//echo $json;
		$data = json_decode($json, true);
		//p($data);die;
		if (!isset($data['article_id']) || !is_numeric($data['article_id'])) {
			$result = array(
				'code' => 200,
				'message' => '请输入文章ID',
			);
			get_type($result);
		}

		//投票
		if (isset($data['type']) && $data['type'] == '投票') {
			$this->vote($data);
		}
		//p($data);die;
		//接收的数组重组 防止原接收数组中出现数据库中没有字段
		if (isset($data['is_sign_up']) && $data['is_sign_up'] == 1) {
			$is_sign_up = 1;
			$is_name = 1;
			$is_tel_number = 1;
			$extra1 = isset($data['extra1']) ? $data['extra1'] : 0;
			$extra2 = isset($data['extra2']) ? $data['extra2'] : 0;

		} else {
			$is_sign_up = 0;
			$is_name = 0;
			$is_tel_number = 0;
			$extra1 = 0;
			$extra2 = 0;
		}
		$datas = array(
			'article_id' => $data['article_id'],
			'type' => $data['type'],
			'field1' => isset($data['field1']) ? $data['field1'] : '',
			'field2' => isset($data['field2']) ? $data['field2'] : '',
			'field3' => isset($data['field3']) ? $data['field3'] : '',
			'is_sign_up' => $is_sign_up,
			'is_name' => $is_name,
			'is_tel_number' => $is_tel_number,
			'extra1' => $extra1,
			'extra2' => $extra2,
		);
		//p($datas);

		//如果是修改 $data['action']=edit
		if (isset($data['action']) && $data['action'] == 'edit') {
			unset($datas['type'], $datas['article_id']);
			$status = $this->db->update('article_field', $datas, array('article_id' => $data['article_id']));
			if ($status) {
				$result = array(
					'code' => 200,
					'message' => '完善字段修改成功',
				);
			} else {
				$result = array(
					'code' => 400,
					'message' => '完善字段修改失败',
				);
			}
			get_type($result);
		}

		//没有action字段则为添加
		$isset = $this->db->select('article_field_id')->get_where('article_field', array('article_id' => $data['article_id']))->result_array();
		if (!empty($isset)) {
			$result = array(
				'code' => 400,
				'message' => '该文章的完善信息已添加，请勿重复添加',
			);
			get_type($result);
		}
		$status = $this->db->insert('article_field', $datas);
		if ($status) {
			$result = array(
				'code' => 200,
				'message' => '信息完善成功',
			);
		} else {
			$result = array(
				'code' => 400,
				'message' => '信息完善失败！请稍后再试。',
			);
		}
		get_type($result);
	}

	/*
		分享接口 是否设为隐私
	*/
	public function is_private() {
		$json = $this->input->raw_input_stream;
		$data = json_decode($json, true);
		//是否设为隐私
		$datas['is_private'] = isset($data['is_private']) ? $data['is_private'] : 0;
		$datas['article_id'] = $data['article_id'];
		$status = $this->db->update('article', array('is_private' => $datas['is_private']), array('article_id' => $datas['article_id']));
		if ($status) {
			$result = array(
				'code' => 200,
				'message' => '设置成功',
			);
		} else {
			$result = array(
				'code' => 400,
				'message' => '设置失败，系统将默认为公开，如需更改，请重试',
			);
		}
		get_type($result);
	}

	/*
		评论接口
	*/
	//(1).创建评论
	public function create_comment() {
		$json = $this->input->raw_input_stream;
		$data = json_decode($json, true);
		$patterns = array('/</', '/>/');
		$replacements = array('&lt;', '&gt;');

		$content = preg_replace($patterns, $replacements, $data['content']);
		$user_id = $this->session->userdata('user_id');
		//重组数组
		$create_time = date('Y-m-d H:i:s', time());
		$datas = array(
			'article_id' => $data['article_id'],
			'user_id' => $user_id,
			'content' => $content,
			'create_time' => $create_time,
		);

		$status = $this->db->insert('comment', $datas);
		$comment_id = $this->db->insert_id();
		if ($status) {
			$get_info = 'name, head_portrait';
			$status = $this->db->select($get_info)->get_where('users', array('user_id' => $user_id))->result_array();
			$user = $status[0];
			$result = array(
				'code' => 200,
				'message' => '评论成功',
				'data' => array(
					'comment_id' => $comment_id,
					'head_portrait' => $user['head_portrait'],
					'name' => $user['name'],
					'create_time' => formatTime($create_time),
					'content' => $content,
				),
			);
		} else {
			$result = array(
				'code' => 400,
				'message' => '评论失败，请重试',
			);
		}
		get_type($result);
	}
	//(2)回复评论
	public function reply_comment() {
		/*

			待开发 待开发  待开发

		*/
	}
	//（3）删除评论
	public function delete_comment() {
		$json = $this->input->raw_input_stream;
		$data = json_decode($json, true);

		$status = $this->db->delete('comment', array('comment_id' => $data['comment_id']));
		if ($status) {
			$result = array(
				'code' => 200,
				'message' => '删除成功',
			);
		} else {
			$result = array(
				'code' => 400,
				'message' => '删除失败',
			);
		}
		get_type($result);
	}
	/*
		查看文章里的 查看所有评论 登陆才能看
	 */
	public function all_comment() {
		$article_id = $this->uri->segment(4);
		$limit = 'LIMIT 9,10';
		$comment = $this->API_model->get_comment($article_id, $limit);
		if ($comment) {
			$result = array(
				'code' => 200,
				'message' => '评论获取成功',
				'all' => count($comment),
				'data' => $comment,
			);
		} else {
			$result = array(
				'code' => 400,
				'message' => '没有更多了',
			);
		}
		get_type($result);
	}

	/*
		投票接口
	*/
	public function vote($data) {

		if (!is_array($data['data'])) {
			$result = array(
				'code' => 400,
				'message' => '请输入正确的投票项格式',
			);
			get_type($result);
		}

		$article_id = $data['article_id'];
		foreach ($data['data'] as $v) {
			$status = $this->db->insert('vote', array('article_id' => $article_id, 'option' => $v));
			//echo $v.'  ';
		}
		//die;
		if ($status) {
			$result = array(
				'code' => 200,
				'message' => '投票项设置成功',
			);
		} else {
			$result = array(
				'code' => 400,
				'message' => '投票项设置失败，请重试',
			);
		}
		get_type($result);
	}

	//关注的人
	public function my_follow() {
		$user_id = $this->session->userdata('user_id');
		$status = $this->db->select('follow')->get_where('users', array('user_id' => $user_id))->result_array();
		$follow_str = $status[0]['follow'];
		if (empty($follow_str)) {
			$result = array(
				'code' => 400,
				'message' => '您还没有关注任何人，请先选择关注',
			);
			get_type($result);
		}
		$follow_arr = explode('-', $follow_str, -1);
		$where = '';

		foreach ($follow_arr as $v) {
			$where .= ' article.user_id=' . $v . ' OR';
		}
		$where = substr($where, 0, -2);

		//查询条件

		//echo $where;die;
		//按要求获取信息

		$create_time = $this->uri->segment(5);
		$la = $this->uri->segment(4);
		$per_page = 10;
		$data = $this->API_model->get_myfollow_info($per_page, $create_time, $la, $where);
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
		上传凭证获取
	*/
	public function upload() {
		// 需要填写你的 Access Key 和 Secret Key
		$accessKey = '4AXvKBpu_OIE9RE_18fQnzY8ux-CA9rEnL8HaQ79';
		$secretKey = 'C-C7dOAwpr1bGkxO6RaoORPnoCYlh8Sk1MgkDkjP';
		// 构建鉴权对象
		$auth = new Auth($accessKey, $secretKey);
		// 要上传的空间
		$bucket = 'renrenpage';
		// 生成上传 Token
		$token = $auth->uploadToken($bucket);
		$result = array(
			'code' => 200,
			'message' => $token,
		);
		echo json_encode($result, JSON_UNESCAPED_UNICODE);
		exit;
		//p($result);
		//get_type($token);
		//echo '{"code":"200","token":"'.$token.'"}';
	}

}

?>