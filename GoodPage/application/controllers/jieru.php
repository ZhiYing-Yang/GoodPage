<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Jieru extends CI_Controller{
	public function __construct(){
		parent::__construct();
		error_reporting(E_ALL^E_NOTICE);
	}
	public function index(){
		$signature=$this->input->get('signature');
		$nonce=$this->input->get('nonce');
		$token='weixin';
		$timestamp=$this->input->get('timestamp');
		$echostr=$this->input->get('echostr');
		
		$array = array($nonce, $timestamp, $token);
		sort($array);
		$str=sha1(implode($array));
		
		if($str==$signature && !empty($echostr) ){
			echo $echostr;
			exit;
		}
		else{
			$this->responseMsg();
		}
	}
	
	//消息回复方法
	public function responseMsg(){
		
		//1.获取微信推送过来的POST数据（xml格式）
		$postStr=file_get_contents('php://input');
		//2.处理消息类型，并设置回复类型和内容
		$postObj=simplexml_load_string($postStr);		
		//判断数据包是否为订阅事件
		if( strtolower($postObj->MsgType) == 'event' ){

			//用户参数
			$toUser = $postObj->FromUserName;
			$fromUser = $postObj->ToUserName;
			$time = time();
			//如果是关注时间 subscribe
			if(strtolower($postObj->Event) == 'subscribe' ){
				
				$msgType = 'text';
				$content ='欢迎关注小志的微信公众号';
				$template ="<xml>
							<ToUserName><![CDATA[$toUser]]></ToUserName>
							<FromUserName><![CDATA[$fromUser]]></FromUserName>
							<CreateTime>$time</CreateTime>
							<MsgType><![CDATA[$msgType]]></MsgType>
							<Content><![CDATA[$content]]></Content>
							</xml>				
							";
				echo $template;
			}

			//扫码事件推送
			if(strtolower($postObj->Event)=='scan'){
				$msgType = 'text';
				$content ='欢迎关注小志的微信公众号';
				$template ="<xml>
							<ToUserName><![CDATA[$toUser]]></ToUserName>
							<FromUserName><![CDATA[$fromUser]]></FromUserName>
							<CreateTime>$time</CreateTime>
							<MsgType><![CDATA[$msgType]]></MsgType>
							<Content><![CDATA[$content]]></Content>
							</xml>				
							";
				echo $template;
			}

			//点击事件推送
			if(strtolower($postObj->Event) == 'click'){
				if($postObj->EventKey== 'songs1'){
					$msgType='news';
					$arr = array(
						array(
							'title'=>'文章标题',
							'description'=>'踏破铁鞋无觅处,得来全不不费功夫',
							'picurl'=>'http://ascexz.320.io/cang.jpg',
							'url'=>'https://m.xhamster.com/tags/japanese-av'
						),
					);
					foreach($arr as $k=>$v){
						$content .="<item>
									<Title><![CDATA[".$v['title']."]]></Title> 
									<Description><![CDATA[".$v['description']."]]></Description>
									<PicUrl><![CDATA[".$v['picurl']."]]></PicUrl>
									<Url><![CDATA[".$v['url']."]]></Url>
									</item>
						";
					}
					//$content='00';
					$template="<xml>
								<ToUserName><![CDATA[$toUser]]></ToUserName>
								<FromUserName><![CDATA[$fromUser]]></FromUserName>
								<CreateTime>$time</CreateTime>
								<MsgType><![CDATA[$msgType]]></MsgType>
								<ArticleCount>1</ArticleCount>
								<Articles>
								$content
								</Articles>
								</xml>
					";
					echo $template;
					return;
					}
			}
		}
		
		//消息回复
		if(strtolower($postObj->MsgType == 'text' )){
			if($postObj->Content == 'hello' ){
				//回复初始消息
				$content="<a href='http://www.baidu.com'>你好啊</a>";
				$msgType='text';
			}
			else if(preg_match('/\x{9ec4}\x{7247}/u', $postObj->Content)){
				//回复图文消息	
				$fromUser=$postObj->ToUserName;
				$toUser=$postObj->FromUserName;
				$time=time();
				$msgType='news';
				$arr = array(
					array(
						'title'=>'你想看的',
						'description'=>'踏破铁鞋无觅处,Teacher苍在此等候',
						'picurl'=>'http://ascexz.320.io/cang.jpg',
						'url'=>'https://m.xhamster.com/tags/japanese-av'
					),
				);
				foreach($arr as $k=>$v){
					$content .="<item>
								<Title><![CDATA[".$v['title']."]]></Title> 
								<Description><![CDATA[".$v['description']."]]></Description>
								<PicUrl><![CDATA[".$v['picurl']."]]></PicUrl>
								<Url><![CDATA[".$v['url']."]]></Url>
								</item>
					";
				}
				//$content='00';
				$template="<xml>
							<ToUserName><![CDATA[$toUser]]></ToUserName>
							<FromUserName><![CDATA[$fromUser]]></FromUserName>
							<CreateTime>$time</CreateTime>
							<MsgType><![CDATA[$msgType]]></MsgType>
							<ArticleCount>1</ArticleCount>
							<Articles>
							$content
							</Articles>
							</xml>
				";
				echo $template;
				return;
			}
			else{
				//接入聊天机器人回复消息
				$url="http://www.tuling123.com/openapi/api?key=78ceb20dc9414a4aa9c785b78af69ef3";
				$url =$url.'&info='.$postObj->Content."&userid=1234";
				$content=file_get_contents($url);
				$content=json_decode($content)->text;
				$msgType='text';
			}
			$fromUser=$postObj->ToUserName;
			$toUser=$postObj->FromUserName;
			$time=time();
			
			$template ="<xml>
							<ToUserName><![CDATA[$toUser]]></ToUserName>
							<FromUserName><![CDATA[$fromUser]]></FromUserName>
							<CreateTime>$time</CreateTime>
							<MsgType><![CDATA[$msgType]]></MsgType>
							<Content><![CDATA[$content]]></Content>
						</xml>				
						";
			echo $template;
		}
	}

	//获取微信AccessToken
	public function getWxAccessToken(){

		if($_SESSION['access_token'] ){
			return $_SESSION['access_token'];
		}
		$appid="wx3c4680b883d17b89";
		$secret="b2991eb8561ec3df6995bb753ba84c11";
		$url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$secret";
		
		//创建一个新cURL资源
		$ch=curl_init();
		//设置url和相应的选项
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_HEADER, 0);
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//这个是主要参数
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//抓取url并把它传递给浏览器
		$res=curl_exec($ch);
		if(curl_errno($ch)){
			var_dump(curl_error($ch));
		}
		
		//关闭资源
		curl_close($ch);
		
		
		$data=json_decode($res, true);
		$this->session->set_tempdata('access_token', $data['access_token'], 7000);
		return $_SESSION['access_token'];
	}

	//获得微信ip
	public function getWxIp(){
		$accesstoken='_GE2Flk07Pvz8FG3UWHE1tkPjcxfvn3agsoBmKHnCXN-vFUNYmdelqO7kjUvTbDW87hdLYuG3uMsXV7t5BCjDotB6-dH8IVwNKg9aQzkVNtln64uV004SJqp4woS-V51NLKfAEAXVU';
		$url='https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token='.$accesstoken;
		$ch=curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$res=curl_exec($ch);
		
		
		
		if(curl_errno($ch)){
			var_dump(curl_error($ch));
		}
		curl_close($ch);
		$data=json_decode($res, true);
		var_dump($data);
	}

	//http curl接口请求
	public function  http_curl($url, $type='GET',$res='json', $json=''){
		//初始化
		$ch=curl_init();

		//设置选项，包括URl
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		//如果是post方式
		if( strtolower($type)== 'post' ){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		}

		$output=curl_exec($ch);
		if($res=='json'){
			if(curl_errno($ch)){
				return curl_error($ch);
			}
			else{
				return json_decode($output, true);
			}
		}
		
		curl_close($ch);
	}
	//测试账号自定义菜单
	public function definedItem(){
		//创建微信菜单
		//目前接口的调用方式都是通过curl post/get
		$access_token=$this->getWxAccessToken();
		$url="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;
		$postArr=array(
			
			'button'=>array(
				//第一个一级菜单
				array(
					'name'=>'菜单一',
					'sub_button'=>array(
						array(
							'type' =>'click',
							'name' =>'今日歌曲',
							'key' => 'songs1',
						),
						array(
							'type' =>'click',
							'name' =>'明日歌曲',
							'key' => 'songs2',
						),
					),
				 ),

				//第二个一级菜单
				array(
					'name'=>'菜单2',
					'sub_button'=>array(
						//第一个二级菜单
						array(
							'type' => 'view',
							'name' => '电影',
							'url' =>'http://www.iqiyi.com',
						),
						array(
							'type' => 'view',
							'name' => '搜索',
							'url' =>'https://www.baidu.com',
						),
						array(
							'type'=>'view',
							'name'=>'主页',
							'url'=>'http://ascexz.320.io/weiixn/jieru/getBaseInfo',
						),
					),
				),
				array(
					'name'=>'菜单3',
					'sub_button'=>array(
						//第一个二级菜单
						array(
							'type' => 'scancode_waitmsg',
							'name' => '扫码带提示',
							'key' =>'rselfmenu_0_0',
							'sub_button'=>[],
						),
						array(
							'type' => 'pic_photo_or_album',
							'name' => '拍照或者相册发图',
							'key' =>'rselfmenu_1_1',
							'sub_button'=>[],
						),
						array(
							'type'=>'location_select',
							'name'=>'发送位置',
							'key'=>'rselfmenu_2_0',
						),
						
					),
				),		
			),
		);
		$postJson =json_encode($postArr, JSON_UNESCAPED_UNICODE);
		var_dump($postJson);
		echo "<hr/>";
		$res = $this->http_curl($url, 'post', 'json', $postJson);
		var_dump($res);
	}

	//消息群发接口测试
	public function sendMsgAll(){
		$access_token=$this->getWxAccessToken();
		$url="https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token=".$access_token;
		$postArr=array(
			'touser'=>'oC4Nt04JlOZOK_9cVLWaM_2bn4cs',
			'text'=>array(
				'content'=>'这是一条群发消息',
			),
			'msgtype'=>'text'
		);
		$postJson= json_encode($postArr, JSON_UNESCAPED_UNICODE);

		$res=$this->http_curl($url, 'post', 'json', $postJson);
		var_dump($res);
	}

	//网页授权
		//（1）.
	public function getBaseInfo(){
		//1.用户同意授权，获取code
		$appid="wx3c4680b883d17b89";
		$redirect_uri=urlencode("http://ascexz.320.io/weixin/jieru/getUserOpenId");
		$url="https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$appid."&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_base&state=123#wechat_redirect";
		header('location:'.$url);
	}

		//（2）.
	public function getUserOpenId(){
		//2.获取网页授权的access_token
		$appid="wx3c4680b883d17b89";
		$appsecret="b2991eb8561ec3df6995bb753ba84c11";
		$code=$_GET['code'];
		$url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$appid."&secret=".$appsecret."&code=".$code."&grant_type=authorization_code";
		$res=$this->http_curl($url);
		var_dump($res);
	}

	//发送模板消息
	public function sendTemplateMsg(){
		$access_token=$this->getWxAccessToken();
		$template_id="_RUb7LW16C4mrGwGke-uuI9ZaqUKqJopLkxGx0kmNuY";
		$url="https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
		$array=array(
			'touser'=>'oC4Nt04JlOZOK_9cVLWaM_2bn4cs',
			'template_id'=>'_RUb7LW16C4mrGwGke-uuI9ZaqUKqJopLkxGx0kmNuY',
			'url'=> 'http://www.baidu.com',
			'topcolor'=>'#FF0000',
			'data'=>array(
				'name'=>array('value'=>'张三', 'color'=>'#173177'),
				'money'=>array('value'=>'￥100.00', 'color'=>'#173177'),
				'date'=>array('value'=>date('Y-m-d H:i:s'), 'color'=>'#173177'),
			),
		);
		
		$postJson=json_encode($array, JSON_UNESCAPED_UNICODE);
		//var_dump($postJson);
		//echo "<hr>";
		$res=$this->http_curl($url, 'post', 'json', $postJson);
		//var_dump($res);
	}

	//生成临时二维码
	public function getTmpQrcode(){
		$access_token=$this->getWxAccessToken();
		$url="https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=".$access_token;
		$postArr=array(
			'expire_seconds'=>604800,
			'action_name'=>'QR_SCENE',
			'action_info'=>array(
				'scene'=>array('scene_id'=>2000),
			),
		);

		$postJson= json_encode($postArr, JSON_UNESCAPED_UNICODE);
		$res=$this->http_curl($url, 'post', 'json', $postJson);

		//var_dump($res);
		//使用ticket获取二维码图片
		$ticket=urlencode($res['ticket']);
		$url="https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=".$ticket;

		//直接展示
		echo "<img src='".$url."'>";

	}


	public function dis(){
		unset($_SESSION['access_token']);
	}
}
?>
