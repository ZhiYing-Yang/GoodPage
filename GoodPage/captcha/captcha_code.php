<?php
//用session保存验证信息；

	$img=imagecreatetruecolor(100,40);
	$bgcolor = imagecolorallocate($img, rand(151,255), rand(151,255), rand(151,255));
	imagefill($img, 0, 0, $bgcolor);
	
	//随机数字验证码
	/*for($i=0 ; $i<4 ; $i++){
		$fontsize = 6;
		$fontcolor=imagecolorallocate($img, rand(0,120), rand(0,120), rand(0,120));//0-120深色区域；120-255为浅色区域；
		$fontcontent=rand(0 , 9);
		
		$x=($i*100/4)+rand(5,10);
		$y=rand(5,10);
		
		imagestring($img, $fontsize, $x, $y, $fontcontent, $fontcolor);
	}*/
	
	
	//随机字母加数字验证码
	$captch_code="";
	for( $i=0 ; $i<4 ; $i++){
		$fontsize=18;
		$fontcolor=imagecolorallocate($img, rand(0,100), rand(0,100), rand(0,100));
		$date="abcdefghjkmnpqrstuvwxyz23456789";
		$fontcontent=substr($date, rand(0,strlen($date)), 1);
		$captch_code .=$fontcontent;
		
		$x=($i*100/4)+rand(5,10);
		$y=rand(5,10);
		
		imagestring($img, $fontsize, $x, $y, $fontcontent, $fontcolor);
	}
	$_SESSION['authcode']=$captch_code;
	//点干扰
	for($i=0 ; $i<200 ; $i++){
		$pointcolor=imagecolorallocate($img, rand(50,200), rand(50,200), rand(50,200));
		imagesetpixel($img, rand(1,99), rand(1,29), $pointcolor);
		
		
	}
	
	
	//线干扰
	for($i=0 ; $i< 3 ; $i++){
		$linecolor=imagecolorallocate($img, rand(80,220), rand(80,220), rand(80,220));
		imageline($img, rand(1,99), rand(1,29), rand(1,99), rand(1,29), $linecolor);
	}
	header('content-type:image/png');
	imagepng($img);
?>

 