<style>
#reputation_box{
text-align:center;
color:#FFFFFF;
position:absolute;
border:2px dotted green;
width:600px;
display:none;
background-color:#1f537b;
-moz-border-radius:10px;
}

.reputation{
cursor:pointer;
}

</style>
<div id="reputation_box" style="">
<div id="type"></div>
<p  onclick="$('#reputation_box').hide('slow')" style="float:right; cursor:pointer">X</p>
<center>

<form id="frmrepu" action="<?php echo $ext_info['url'].'/reputation.php';?>" method="get" style="padding:20px;" >
<label style="color:white;display:block">কারণ: <br />
<input type="text" name="reason" id="reason" size="50" maxlength="255" /></label><br />
<input type="hidden" name="action" id="action" value="" />
<input type="hidden" name="uid" id="uid" value="" />
<input type="hidden" name="pid" id="pid" value="" />
<br /><input type="submit" id="repusubmit" name="submit" value="<?php echo $lang_common['Submit']?>" />
</form>
</center>
</div>
