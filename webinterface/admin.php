﻿<?PHP
session_start();

require_once('../other/config.php');

function getclientip() {
	if (!empty($_SERVER['HTTP_CLIENT_IP']))
		return $_SERVER['HTTP_CLIENT_IP'];
	elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
	elseif(!empty($_SERVER['HTTP_X_FORWARDED']))
		return $_SERVER['HTTP_X_FORWARDED'];
	elseif(!empty($_SERVER['HTTP_FORWARDED_FOR']))
		return $_SERVER['HTTP_FORWARDED_FOR'];
	elseif(!empty($_SERVER['HTTP_FORWARDED']))
		return $_SERVER['HTTP_FORWARDED'];
	elseif(!empty($_SERVER['REMOTE_ADDR']))
		return $_SERVER['REMOTE_ADDR'];
	else
		return false;
}

if (isset($_POST['logout'])) {
	echo "logout";
    $_SESSION = array();
    session_destroy();
	if($_SERVER['HTTPS'] == "on") {
		header("Location: https://".$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\'));
	} else {
		header("Location: http://".$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\'));
	}
	exit;
}

if (!isset($_SESSION['username']) || $_SESSION['username'] != $webuser || $_SESSION['password'] != $webpass || $_SESSION['clientip'] != getclientip()) {
	if($_SERVER['HTTPS'] == "on") {
		header("Location: https://".$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\'));
	} else {
		header("Location: http://".$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['PHP_SELF']), '/\\'));
	}
	exit;
}

require_once('nav.php');

if(!isset($_POST['number']) || $_POST['number'] == "yes") {
	$_SESSION['showexcepted'] = "yes";
	$filter = " AND except='0'";
} else {
	$_SESSION['showexcepted'] = "no";
	$filter = "";
}


if(($dbuserdata = $mysqlcon->query("SELECT uuid,cldbid,name FROM $dbname.user WHERE 1=1$filter ORDER BY name ASC")) === false) {
	$err_msg = "DB Error: ".print_r($mysqlcon->errorInfo(), true); $err_lvl = 3;
}
$user_arr = $dbuserdata->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['update']) && $_SESSION['username'] == $webuser && $_SESSION['password'] == $webpass && $_SESSION['clientip'] == getclientip()) {
	$setontime = 0;
	if($_POST['setontime_day']) { $setontime = $setontime + $_POST['setontime_day'] * 86400; }
	if($_POST['setontime_hour']) { $setontime = $setontime + $_POST['setontime_hour'] * 3600; }
	if($_POST['setontime_min']) { $setontime = $setontime + $_POST['setontime_min'] * 60; }
	if($_POST['setontime_sec']) { $setontime = $setontime + $_POST['setontime_sec']; }
	if($setontime == 0) {
		$err_msg = $lang['errseltime']; $err_lvl = 3;
	} elseif($_POST['user'] == NULL) {
		$err_msg = $lang['errselusr']; $err_lvl = 3;
	} else {
		$allupdateuuid = '';
		foreach($_POST['user'] as $user) {
			$allupdateuuid .= "'".$user."',";
		}
		$allupdateuuid = substr($allupdateuuid, 0, -1);
		if($mysqlcon->exec("UPDATE $dbname.user set count = count + $setontime WHERE uuid IN ($allupdateuuid)") === false) {
			$err_msg = $lang['isntwidbmsg'].print_r($mysqlcon->errorInfo(), true); $err_lvl = 3;
		} else {
			if($mysqlcon->exec("UPDATE $dbname.user_snapshot set count = count + $setontime WHERE uuid IN ($allupdateuuid)") === false) { }
			$err_msg = sprintf($lang['sccupcount'],$setontime,$allupdateuuid); $err_lvl = NULL;
		}
	}
}
?>
		<div id="page-wrapper">
<?PHP if(isset($err_msg)) error_handling($err_msg, $err_lvl); ?>
			<div class="container-fluid">
				<div class="row">
					<div class="col-lg-12">
						<h1 class="page-header">
							<?php echo $lang['wihladm1']; ?>
						</h1>
					</div>
				</div>
				<!-- <form id="update" method="POST"></form> -->
				<form name="post" method="POST">
				<div class="form-horizontal">
					<div class="row">
						<div class="col-md-3">
						</div>
						<div class="col-md-6">
							<div class="panel panel-default">
								<div class="panel-body">
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#wiadmhidedesc"><?php echo $lang['wiadmhide']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8 pull-right">
											<select class="selectpicker show-tick form-control" id="number" name="number" onchange="this.form.submit();">
											<?PHP
											echo '<option value="yes"'; if(!isset($_SESSION['showexcepted']) || $_SESSION['showexcepted'] == "yes") echo " selected=selected"; echo '>hide</option>';
											echo '<option value="no"'; if(isset($_SESSION['showexcepted']) && $_SESSION['showexcepted'] == "no") echo " selected=selected"; echo '>show</option>';
											?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#wiselclddesc"><?php echo $lang['wiselcld']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8">
											<select class="selectpicker show-tick form-control" data-live-search="true" multiple name="user[]">
											<?PHP
											foreach ($user_arr as $user) {
												echo '<option value="',$user['uuid'],'" data-subtext="UUID: ',$user['uuid'],'; DBID: ',$user['cldbid'],'">',$user['name'],'</option>';
											}
											?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#setontimedesc"><?php echo $lang['setontime']; ?><i class="help-hover glyphicon glyphicon-question-sign"></i></label>
										<div class="col-sm-8">
											<input type="text" class="form-control" name="setontime_day">
											<script>
											$("input[name='setontime_day']").TouchSpin({
												min: 0,
												max: 106751991167299,
												verticalbuttons: true,
												prefix: 'Day(s):'
											});
											</script>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#setontimedesc"></label>
										<div class="col-sm-8">
											<input type="text" class="form-control" name="setontime_hour">
											<script>
											$("input[name='setontime_hour']").TouchSpin({
												min: 0,
												max: 23,
												verticalbuttons: true,
												prefix: 'Hour(s):'
											});
											</script>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#setontimedesc"></label>
										<div class="col-sm-8">
											<input type="text" class="form-control" name="setontime_min">
											<script>
											$("input[name='setontime_min']").TouchSpin({
												min: 0,
												max: 59,
												verticalbuttons: true,
												prefix: 'Min.:'
											});
											</script>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-4 control-label" data-toggle="modal" data-target="#setontimedesc"></label>
										<div class="col-sm-8">
											<input type="text" class="form-control" name="setontime_sec">
											<script>
											$("input[name='setontime_sec']").TouchSpin({
												min: 0,
												max: 59,
												verticalbuttons: true,
												prefix: 'Sec:'
											});
											</script>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">&nbsp;</div>
					<div class="row">
						<div class="text-center">
							<button type="submit" class="btn btn-primary" name="update"><?php echo $lang['wisvconf']; ?></button>
						</div>
					</div>
					<div class="row">&nbsp;</div>
				</div>
				</form>
			</div>
		</div>
	</div>
	
<div class="modal fade" id="wiselclddesc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $lang['wiselcld']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['wiselclddesc']; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="setontimedesc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $lang['setontime']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['setontimedesc']; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="wiadmhidedesc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?php echo $lang['wiadmhide']; ?></h4>
      </div>
      <div class="modal-body">
        <?php echo $lang['wiadmhidedesc']; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
      </div>
    </div>
  </div>
</div>
</body>
</html>