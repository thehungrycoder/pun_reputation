function reputation(obj,user,act,uid,pid){
	var pos = $(obj).offset();
	$('#reputation_box').css("position","fixed");
	$('#reputation_box').css("position","fixed");
	$('#reputation_box').css("top",200+'px');
	$('#reputation_box').css("left",pos.left+'px');
	$('#action').attr("value",act);
	$('#uid').attr("value",uid);
	$('#pid').attr("value",pid);
	$('#reputation_box').show('slow');
	var img = '<img src="'+$(obj).attr('src')+'" />';
	$('#type').html('&nbsp;'+img+'&nbsp;'+user);
	return false;
}

function confirmrepudel(){
	conf = confirm('Are you sure to delete this reputation?');
	if(!conf){
		return false;
	}
	return true;
}

$(document).ready(function(){
	$('#repusubmit').click(function(){
		var params= $('#frmrepu').serialize();
		params = params + '&ajax=true';
		$.ajax({
			type: 'GET',
			url: $('#frmrepu').attr('action'),
			data: params,
			success: function(result){
				alert(result);
				$('#reputation_box').hide('slow');
				return false;
			},
		});
		return false;
	});
});