<?php
    if(C('LAYOUT_ON')) {
        echo '{__NOLAYOUT__}';
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
<title>系统发生错误</title>
<style type="text/css">
</style>
</head>
<body style="padding: 0; margin: 0;background: #fff; font-family: '微软雅黑'; color: #333; font-size: 16px;">
<div class="error">
<p class="face" style="font-size: 100px; font-weight: normal; line-height: 120px; margin-bottom: 12px;">:(</p>
<h1 style="font-size: 32px; line-height: 48px;"><?php echo strip_tags($e['message']);?></h1>
<div class="content" style="padding-top: 10px">
<?php if(isset($e['file'])) {?>
	<div class="info" style='margin-bottom: 12px;'>
		<div class="title" style='margin-bottom: 3px;'>
			<h3 style='color: #000; font-weight: 700; font-size: 16px;'>错误位置</h3>
		</div>
		<div class="text" style=' line-height: 24px;'>
			<p>FILE: <?php echo $e['file'] ;?> &#12288;LINE: <?php echo $e['line'];?></p>
		</div>
	</div>
<?php }?>
<?php if(isset($e['trace'])) {?>
	<div class="info" style='margin-bottom: 12px;'>
		<div class="title" style='margin-bottom: 3px;'>
			<h3 style='color: #000; font-weight: 700; font-size: 16px;'>TRACE</h3>
		</div>
		<div class="text" style=' line-height: 24px;'>
			<p><?php echo nl2br($e['trace']);?></p>
		</div>
	</div>
<?php }?>
</div>
</div>
<div class="copyright" style="padding: 12px 0px; color: #999;">
<p><a style="color: #000; text-decoration: none;" title="官方网站" href="http://www.thinkphp.cn">ThinkPHP</a><sup><?php echo THINK_VERSION ?></sup> { Fast & Simple OOP PHP Framework } -- [ WE CAN DO IT JUST THINK ]</p>
</div>
</body>
</html>
