<?
/**
 * scaffolder-layout
 *
 * @author mnt
 * @package kata_view
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Scaffold</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="pragma" content="no-cache" />
<meta http-equiv="expires" content="0" />
<style type="text/css">
body, * {
    font-size:10pt;
    font-family:
    font-family: Arial, Verdana, Sans-serif;
    color:black;
    background-color:white;
}

table {
    border-collapse: collapse;
    margin:0;
}

table tr td {
    border: 1px solid black;
    background-color: #F0F0F0;
    padding: 2px;
    vertical-align:middle;
}

input, textarea {
    border:1px solid black;
    color:black;
    background-color:#aaaaaa;
}
</style>
<script type="text/javascript">
function numericOnly(inpEl, e)
{
	var key;
	var keychar;

	if (window.event) {
	   key = window.event.keyCode;
	} else if (e) {
	   key = e.which;
	} else {
	   return true;
	}
	keychar = String.fromCharCode(key);

	if ((key==null) || (key==0) || (key==8) || (key==9) || (key==13) || (key==27)) {
	   return true;
	}
	if (("0123456789").indexOf(keychar) > -1) {
	   return true;
	}
	return false;
}
function editDate() {
    alert('todo');
}
</script>
</head>

<body>
<div id="scafcal" style="display:hidden;position:absolute;top:0px;left:0px;">
<form action="#" name="scafcalform" onsubmit="return false;">

</form>
</div>


<?php echo $content_for_layout; ?>

</body>
</html>
