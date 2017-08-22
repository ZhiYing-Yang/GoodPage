<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once 'API/xinge-api-php/src/XingeApp.php';
class API_model extends CI_Model {

/*
一、
 */
	/*
		登录检测
	*/
	public function get_user_info($data) {
		$get_info = 'user_id, username, email, tel_number, follow, feature_code, name, sex, head_portrait, self_introduction, create_time, is_autonym';
		$status = $this->db->select($get_info)->get_where('users', $data)->result_array();
		unset($status[0]['password'], $status[0]['last_login_time']);
		return $status;
	}

	/*
		注册
	*/
	public function register($data) {
		$status = $this->db->insert('users', $data);
		$user_id = $this->db->insert_id();
		return $user_id;
	}

	/*
		二、文章
	*/
	/*
		推荐 最新文章列表
	*/
	public function get_article_info($per_page, $create_time, $la, $where) {

		$limit = 'LIMIT ' . $per_page;
		$get_info = 'article_id, title, article.create_time, content, type, article.user_id, name, head_portrait, read_total, praise';
		//下拉刷新
		if ($create_time == 0) {
			$sql = "SELECT $get_info FROM article, users WHERE $where AND article.user_id=users.user_id ORDER BY is_top DESC, create_time DESC $limit";
			$status = $this->db->query($sql)->result_array();
			return $status;
		}
		//格式化时间
		$create_time = date('Y-m-d H:i:s', $create_time);
		if ($la == 'down') {
			$sql = "SELECT $get_info FROM article, users WHERE article.create_time>'{$create_time}' AND $where AND article.user_id=users.user_id ORDER BY is_top DESC, create_time DESC $limit";
		} else {
			$sql = "SELECT $get_info FROM article, users WHERE article.create_time<'{$create_time}' AND $where AND article.user_id=users.user_id ORDER BY is_top DESC, create_time DESC $limit";
		}

		$status = $this->db->query($sql)->result_array();
		return $status;
	}

	//关注
	public function get_myfollow_info($per_page, $create_time, $la, $where) {

		$limit = 'LIMIT ' . $per_page;
		$get_info = 'article_id, title, article.create_time, content, type, article.user_id, name, head_portrait, read_total, praise';
		//下拉刷新
		if ($create_time == 0) {
			$sql = "SELECT $get_info FROM article, users WHERE ($where) AND article.user_id=users.user_id ORDER BY is_top DESC, create_time DESC $limit";
			$status = $this->db->query($sql)->result_array();
			return $status;
		}
		//格式化时间
		$create_time = date('Y-m-d H:i:s', $create_time);
		if ($la == 'down') {
			$sql = "SELECT $get_info FROM article, users WHERE article.user_id=users.user_id AND article.create_time>'{$create_time}' AND is_private=0 AND ($where) ORDER BY is_top DESC, create_time DESC $limit";
		} else {
			$sql = "SELECT $get_info FROM article, users WHERE article.user_id=users.user_id AND article.create_time<'{$create_time}' AND is_private=0 AND ($where) ORDER BY is_top DESC, create_time DESC $limit";
		}
		$status = $this->db->query($sql)->result_array();
		return $status;
	}

	/*
		关键字搜索
	*/
	public function get_search($keywords) {
		$get_info = 'article_id, article.user_id, title, content, users.name, article.create_time, type, read_total,  praise';
		$sql = "SELECT $get_info FROM article,users WHERE (title LIKE '%" . $keywords . "%' ESCAPE '!'  OR content LIKE '%" . $keywords . "%' ESCAPE '!')  AND article.user_id=users.user_id AND is_private=0";
		$status = $this->db->query($sql)->result_array();
		return $status;
	}

	/*
		获取我的好页 文章列表
	*/
	public function get_mypage_info($user_id, $per_page, $create_time, $la) {

		$get_info = 'article_id, title, create_time, content, type, read_total, praise, is_private';
		$this->db->where('user_id', $user_id);
		if ($create_time == 0) {
			$this->db->limit($per_page);
			$status = $this->db->select($get_info)->order_by('create_time DESC')->get('article')->result_array();
			return $status;
		}

		//格式化时间
		$create_time = date('Y-m-d H:i:s', $create_time);
		if ($la == 'down') {
			$this->db->where('create_time >', $create_time);
		} else {
			$this->db->where('create_time <', $create_time);
			$this->db->limit($per_page);
		}

		$status = $this->db->select($get_info)->order_by('create_time DESC')->get('article')->result_array();
		return $status;
	}

	/*
		获取评论
	 */
	public function get_comment($article_id, $limit) {
		$get_info = "comment_id, comment.user_id, comment.create_time, praise, content, name, head_portrait";
		$sql = "SELECT $get_info FROM comment, users WHERE comment.user_id=users.user_id AND article_id=$article_id ORDER BY comment.praise DESC, create_time DESC $limit";
		$status = $this->db->query($sql)->result_array();
		return $status;
	}

	/*
		好友圈文章列表展示

		public function get_friendpage_info($str, $per_page, $offset){
			$limit = "LIMIT ".$offset.", $per_page";
			$get_info='article_id, title, article.create_time, content, type, article.user_id, name, head_portrait, read_total, praise';
			$sql="SELECT $get_info FROM article, users WHERE ($str) AND is_private=0 AND article.user_id=users.user_id ORDER BY create_time DESC $limit";
			$status=$this->db->query($sql)->result_array();
			return $status;

	*/

	/*
		获取我的通讯录好友信息
	*/
	public function get_myfriend_info($str) {
		$get_info = 'user_id, username, name, head_portrait, self_introduction';
		$sql = 'SELECT ' . $get_info . ' FROM users WHERE' . $str;
		$status = $this->db->query($sql)->result_array();

		return $status;
	}

	/*

	 */
	//处理文章列表数据
	public function format_data($data) {
		$pattern_img = "/<(img|IMG)(.*?)(\/>|><\/img>|>)/";
		$pattern_src = '/(src|SRC)=(\'|\")(.*?)(\'|\")/';
		$status = array();
		foreach ($data as $datas) {
			$datas['url1'] = '';
			$datas['url2'] = '';
			if (preg_match_all($pattern_img, $datas['content'], $matchs)) {
				if (preg_match_all($pattern_src, $matchs[0][0], $src)) {

					$datas['url1'] = urlencode($src[3][0]);
				}
				if (isset($matchs[0][1]) && !empty($matchs[0][1]) && preg_match_all($pattern_src, $matchs[0][1], $src)) {
					$datas['url2'] = urlencode($src[3][0]);
				}
			}
			$comment_total = $this->db->where(array('article_id' => $datas['article_id']))->count_all_results('comment');
			$datas['comment_total'] = "$comment_total";
			$datas['content'] = mb_substr($datas['content'], 0, 60);
			$pattern = '/(<(img|IMG)\s+(.*)[^>])$/';
			$datas['content'] = preg_replace($pattern, '', $datas['content']);
			$datas['content'] = preg_replace('/<\/?\s*\w+.*?>/', '', $datas['content']);
			$datas['url'] = urlencode($_SERVER['see_article_url'] . $datas['article_id'] . '/none');
			$status[] = $datas;
		}
		return $status;
	}

	//单个账户发送消息	默认为消息透传
	public function to_Account($title, $content, $user_id, $type = 2) {
		//echo $user_id;die;
		$push = new XingeApp(2100265112, 'f897345cd8825bed2b62f8cd8e905f5f');
		$mess = new Message();
		$mess->setExpireTime(86400);
		$mess->setTitle($title);
		$mess->setContent($content);
		$mess->setType(Message::TYPE_MESSAGE);
		$ret = $push->PushSingleAccount($type, $user_id, $mess);
		return ($ret);
	}
}
?>