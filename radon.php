<?php
 use Radius;

register_menu("Radius Online Users", true, "radius_users", 'AFTER_SETTINGS', 'ion ion-wifi');

function radius_users()
{
    global $ui;
    _admin();
    $ui->assign('_title', 'Radius Online Users');
    $ui->assign('_system_menu', 'Radius Online Users');
    $admin = Admin::_info();
    $ui->assign('_admin', $admin);
    
	$search = _post('search');
	if ($search != '') {
		$paginator = Paginator::build(ORM::for_table('radacct'));
		$useron = ORM::for_table('radacct')
			->where_raw("acctstoptime IS NULL")
			->where_like('username', '%' . $search . '%')
			->offset($paginator['startpoint'])
			->limit($paginator['limit'])
			->order_by_desc('username')
			->find_many();
	} else {
		$paginator = Paginator::build(ORM::for_table('radacct'));
		$useron = ORM::for_table('radacct')
			->where_raw("acctstoptime IS NULL")
			->offset($paginator['startpoint'])
			->limit($paginator['limit'])
			->order_by_desc('acctsessiontime')
			->find_many();
	}
	
	$totalCount = ORM::for_table('radacct')
		->where_raw("acctstoptime IS NULL")
		->count();	
	$paginator['total_count'] = $totalCount;
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kill']) && $_POST['kill'] === 'true') {
    $output = array();
    $retcode = 0;
	$coaport = 3799;
	$d = _post('d');
	$dd = _post('dd');
	$ddd = ORM::for_table('nas')->where_like('nasname', '%' . $dd . '%')->find_one();
	$sharedsecret = $ddd['secret'];

    $os = strtoupper(PHP_OS);

    if (strpos($os, 'WIN') === 0) {
        // Windows OS
        exec("echo 'User-Name=$d'|radclient $dd:$coaport disconnect '$sharedsecret'", $output, $retcode);
    } else {
        // Linux OS
        exec("echo 'User-Name=$d'|radclient $dd:$coaport disconnect '$sharedsecret'", $output, $retcode);
    }
    $ui->assign('output', $output);
    $ui->assign('returnCode', $retcode);
	$ui->assign('d', $d);
	$ui->assign('dd', $dd);

	}	
	$ui->assign('paginator', $paginator);
	$ui->assign('useron', $useron);
	$ui->assign('totalCount', $totalCount);
	$ui->assign('search', $search);
    $ui->display('radon.tpl');
	
}


// Function to format bytes into KB, MB, GB or TB
function mikrotik_formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Convert seconds into months, days, hours, minutes, and seconds.
function secondsToTimeFull($ss)
{
    $s = $ss%60;
    $m = floor(($ss%3600)/60);
    $h = floor(($ss%86400)/3600);
    $d = floor(($ss%2592000)/86400);
    $M = floor($ss/2592000);

    return "$M months, $d days, $h hours, $m minutes, $s seconds";
}

function secondsToTime($inputSeconds)
{
    $secondsInAMinute = 60;
    $secondsInAnHour = 60 * $secondsInAMinute;
    $secondsInADay = 24 * $secondsInAnHour;

    // Extract days
    $days = floor($inputSeconds / $secondsInADay);

    // Extract hours
    $hourSeconds = $inputSeconds % $secondsInADay;
    $hours = floor($hourSeconds / $secondsInAnHour);

    // Extract minutes
    $minuteSeconds = $hourSeconds % $secondsInAnHour;
    $minutes = floor($minuteSeconds / $secondsInAMinute);

    // Extract the remaining seconds
    $remainingSeconds = $minuteSeconds % $secondsInAMinute;
    $seconds = ceil($remainingSeconds);

    // Format and return
    $timeParts = [];
    $sections = [
        'day' => (int)$days,
        'hour' => (int)$hours,
        'minute' => (int)$minutes,
        'second' => (int)$seconds,
    ];

    foreach ($sections as $name => $value){
        if ($value > 0){
            $timeParts[] = $value. ' '.$name.($value == 1 ? '' : 's');
        }
    }

    return implode(', ', $timeParts);
}