//提示信息
function toast(msg, timer) {
	var timer = timer || 2;
	layer.open({content:msg, time:timer, skin:'msg'});
}
//表单ajax提交
$('.submit-ajax').submit(function(){
    var url = $(this).attr('action');
    var data = $(this).serialize();
	$.post(url,data,function(res){					
		if (res.status === 1) {
			toast(res.msg);
            setTimeout(function(){
                window.location.href = res.url;
            },2000);			
		} else {
			toast(res.msg);
		}
	},'json');
    return false;
}); 
//ajax链接 refresh:1跳转url2页面重新加载3只提示信息
function ajaxGet(url, refresh) {
	var refresh = refresh || 1;
	$.get(url,function(res){					
		if (res.status === 1) {
			toast(res.msg);
			if (refresh === 1) {
	            setTimeout(function(){
	                window.location.href = res.url;
	            },2000);					
			} else if (refresh === 2) {
	            setTimeout(function(){
	            	location.reload();
	            },2000);					
			}		
		} else {
			toast(res.msg);
		}
	},'json');		
}
//ajax操作确认
function ajaxConfirm(url, action, refresh) {
	var action = action || '删除';
	var refresh = refresh || 1;
	layer.open({content:'确认要'+action+'吗？', btn:['确认','取消'], yes:function(index) {
			ajaxGet(url, refresh);
			layer.close(index);
		}		
	});
}
//全选
function selectAll(selectStatus) {
	if (selectStatus) {
		$("input[name='ids']").each(function(i,n){
			n.checked = true;
		});
	} else {
		$("input[name='ids']").each(function(i,n){
			n.checked = false;
		});		
	}
}
//多选ajax操作确认
function actionConfirm(action, url) {
	var ids = [];
	$('tbody input').each(function(index, el) {
		if($(this).prop('checked')){
			ids.push($(this).val());
		}
	});
	if (ids.length === 0) {
		toast('请选择ID');
		return;
	}
	var msg = '确认要'+action+'吗？'+ids.toString();
	layer.open({content:msg, btn:['确认','取消'], yes:function(index) {
			$.post(url,{ids:ids.toString()},function(res){
				if (res.status === 1) {
					toast(res.msg);				
		            setTimeout(function(){
		            	location.reload();
		            },2000);				
				} else {
					toast(res.msg);
				}
			},'json'); 			
			layer.close(index);
		}		
	});	
}