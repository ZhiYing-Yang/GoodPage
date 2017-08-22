$(function(){
	/* 公共jq开始 */
		/* 公共常量	*/
	var base_url='http://ascexz.320.io/GoodPage/API/';//API目录
    var web_http='http://ascexz.320.io/GoodPage/';
    var app_memo='memo://ascexz.320.io/GoodPage/';
	var article_id=$('#article_id').attr('value');	//文章id
	var login_user_id=$('#user_id').attr('value');
	var article_user_id=$('#article_user_id').attr('value');
    var client_type=$('#client_type').attr('value');	
    //评论区
    
        //特效部分
    $('.comment_button').on('click', function(){
        //$('.hui_bg').fadeIn(200);
        $('#comment').slideDown(200, function() {
             $('.blank').on('click', function(){
                 $('#data_form').slideUp(400);
                 $('#comment').slideUp(400);
            });
        });
    });
        //请求部分
    $('#comment_submit').click(function(){
        if($('#comment_content').val()==''){
            $('#alert_info').html('评论内容不能为空').fadeIn(400).fadeOut(400);
            return;
        }
        var token=document.cookie;
        document.cookie = token+';path=/';
        $.ajax({
            type:'POST',
            url:base_url+'authorised/create_comment',
            async:true,
            contentType:'aplication/json;charset=utf-8',
            dataType:'json',
            data:JSON.stringify({
                'article_id':article_id,
                'content':$('#comment_content').val(),
            }),
            success:function(data){
                //若没有登陆调用微信登录
                $('#comment').slideUp(400);
                $('.hui_bg').fadeOut(200);
                if(data.code==401){
                    alert('请先登录');
                }
                if(data.code==200){
                    var user_id=login_user_id;
                    var html="<li class='media'><div class='media-left'><a href='"+user_id+"'><img class='media-object' src='"+data.data.head_portrait+"' alt='头像'></a></div><div class='media-body'><h5 class='media-heading'>"+data.data.name+"<a comment_id='"+data.data.comment_id+"' class='ups praise_comment1'>0</a></h5><p class='time'>"+data.data.create_time+"</p><p class='info'>"+data.data.content+"</p></div><hr/></li>";
                    $('#comment_content').val('');
                    $(html).hide().insertBefore($('.users .media').first()).slideDown();
                    if(parseInt($('.comment_total').html())>=99){
                        $('.comment_total').html('99+');
                    }
                    else{
                        $('.comment_total').html(parseInt($('.comment_total').html())+1);
                    }

                    //重新注册对评论的点赞 评论后的内容自己可以立马点赞
				    $('.praise_comment1').click(function(){
				        //前台判断是否点赞过
				        if($(this).attr('praised')=='praised'){
				            $('#alert_info').html('您已赞过').fadeIn(700).fadeOut(700);
				            return;
				        }
				        //调用对评论的点赞接口

				        $.ajax({
				            type:'GET',
				            url:base_url+'index/praise_comment/'+$(this).attr('comment_id'),
				            success:function(){

				                $('#alert_info').html('点赞成功').fadeIn(700).fadeOut(700);

				            },
				        });
				        //alert($(this).html());
				        var now_praise=parseInt($(this).html())+1;
				        $(this).html(now_praise);
				        $(this).css('color', '#3595FF');
				        $(this).attr('praised', 'praised');
				    });

                }
            }
        });
    });

    //获取更多评论
    $('#see_all_comment').click(function(){
        if($('.users .media').length < 10 || $('#see_all_comment').attr('disabled')=='disabled'){
            $('#alert_info').html('没有更多了').fadeIn(700).fadeOut(700);
            return;
        }
        $.ajax({
            url: base_url+'authorised/all_comment/'+article_id,
            type: 'GET',
            dataType: 'json',
            success:function(data){
                if(data.code==401){
                    //需要登陆才能访问的接口 以后调用微信登陆
                    alert('请先登录');
                }
                else if(data.code==200){
                    for(var i=0; i<data.all; i++){
                        var html="<li class='media'><div class='media-left'><a href='"+data.data[i].user_id+"'><img class='media-object' src='"+data.data[i].head_portrait+"' alt='头像'></a></div><div class='media-body'><h5 class='media-heading'>"+data.data[i].name+"<a comment_id='"+data.data[i].comment_id+"' class='ups praise_comment2'>"+data.data[i].praise+"</a></h5><p class='time'>"+data.data[i].create_time+"</p><p class='info'>"+data.data[i].content+"</p></div><hr/></li>";
                        $(html).hide().insertAfter($('.users .media').last()).slideDown();
                    }
                    $('#see_all_comment').attr('disabled', true);
                     //重新注册对评论的点赞
				    $('.praise_comment2').click(function(){
				        //前台判断是否点赞过
				        if($(this).attr('praised')=='praised'){
				            $('#alert_info').html('您已赞过').fadeIn(700).fadeOut(700);
				            return;
				        }
				        //调用对评论的点赞接口

				        $.ajax({
				            type:'GET',
				            url:base_url+'index/praise_comment/'+$(this).attr('comment_id'),
				            success:function(){

				                $('#alert_info').html('点赞成功').fadeIn(700).fadeOut(700);

				            },
				        });
				        //alert($(this).html());
				        var now_praise=parseInt($(this).html())+1;
				        $(this).html(now_praise);
				        $(this).css('color', '#3595FF');
				        $(this).attr('praised', 'praised');
				    });
                }
                else{
                    $('#alert_info').html('没有更多了').fadeIn(700).fadeOut(700);
                }
            }
        });

    });


    //分享到QQ好友
    document.getElementById('qcShareQQDiv').onclick = function(e){
    var  p = {
            url: base_url+'index/see_article/'+article_id,/*获取URL，可加上来自分享到QQ标识，方便统计*/
            desc: 'QQ分享', /*分享理由(风格应模拟用户对话),支持多分享语随机展现（使用|分隔）*/
            title : $('#article_title').html(),/*分享标题(可选)*/
            summary : 'QQ分享',/*分享描述(可选)*/
            pics : $('.look-content').children('img').eq(0).attr('src'),/*分享图片(可选)*/
            flash : '', /*视频地址(可选)*/
            //commonClient : true, /*客户端嵌入标志*/
            site: 'QQ分享'/*分享来源 (可选) ，如：QQ分享*/
        };

        var s = [];
        for (var i in p) {
            s.push(i + '=' + encodeURIComponent(p[i] || ''));
        }
        //使用http://connect.qq.com/widget/shareqq/iframe_index.html链接，iframe_index.html是弹出层效果，index.html是新打开页面效果
        var _src = "http://connect.qq.com/widget/shareqq/index.html?" + s.join('&') ;
        window.open(_src);
    };
    //打开App
    // $('.open_app').on('touchend click', function(){
    //     location.href='memo://www.orangecpp.com:80/mypath?key=mykey';
    // });
    //淡入淡出提示框居中
    var sp_center=($(window).width()-$('#alert_info').innerWidth())/2+30;
    $('#alert_info').css('left', sp_center);
    $('.look-content').children('img').addClass('img-responsive');
    $('#do').on('touchend click', function(){
       // $('body').css('background', 'rgba(0,0,0,.5)');
        $('#data_form').slideDown(200, function(){
            $('.blank').on('touchend click', function(){
                 $('#data_form').slideUp(400);
                 $('#comment').slideUp(400);
                 //$('body').css('background', '');
            });
        });
    });

    //对文章的点赞
    $('.praise_article').click(function(){
        if($('.praise_article').length==0){
            $('#alert_info').html('您已赞过').fadeIn(400).fadeOut(400);
            return;
        }
        var old_praise_article=parseInt($('.praise_article_total').html());
        $.ajax({
            type:'GET',
            url:base_url+'index/praise_article/'+article_id,
            success:function(){
                $('#alert_info').html('点赞成功').fadeIn(700).fadeOut(700);
                var praise_article_total=old_praise_article+1;
                //alert( praise_article_total);
                if(praise_article_total>99){
                    praise_article_total='99+';
                }
                $('.praise_article_total').html( praise_article_total );
                //alert( praise_article_total);
                $('.praise_article').eq(0).css('background', 'gray').html('已赞('+ praise_article_total+')');
                $('.praise_article').eq(1).css('color', '#3595FF');
                $('.praise_article').removeClass('praise_article');
            },
        });
    });

    //对评论的点赞
    $('.praise_comment').click(function(){
        //前台判断是否点赞过
        if($(this).attr('praised')=='praised'){
            $('#alert_info').html('您已赞过').fadeIn(700).fadeOut(700);
            return;
        }
        //调用对评论的点赞接口

        $.ajax({
            type:'GET',
            url:base_url+'index/praise_comment/'+$(this).attr('comment_id'),
            success:function(){

                $('#alert_info').html('点赞成功').fadeIn(700).fadeOut(700);

            },
        });
        //alert($(this).html());
        var now_praise=parseInt($(this).html())+1;
        $(this).html(now_praise);
        $(this).css('color', '#3595FF');
        $(this).attr('praised', 'praised');
    });

    //中间框框点击跳转个人主页
    $('#go_myindex').on('click', function(e){
        if($(e.target).closest('.guanzhu').length!=0){
            return;
        }
        if(client_type=='none'){
            location.href=app_memo+'author/'+article_user_id;
        }else{
    	   location.href=web_http+'author/'+article_user_id;
        }
    });

    /*关注*/
    $('.guanzhu_button').on('click', function(){
        if($('.guanzhu_button').html()=='已关注'){
            return;
        }
        //var token=document.cookie;
        //document.cookie = token+';path=/';
        $.ajax({
            url: base_url+'user/follow/'+article_user_id+'/1',
            type: 'GET',
            dataType:'json',
            success:function(data){
                if(data.code==200){
                    $('.guanzhu_button').html('已关注');
                    $('.guanzhu_button').css({
                        'background':'#C1C1C1',
                        'border':'1px solid #C1C1C1',
                    });
                }
                if(data.code==401){
                    alert('请先登录');
                }
            },
        });

    });
/*  公共jq结束  */
});