<?php

$rootPath = '../';

////////////////////////////////////////////////////////////////////////////////
//
//	SETUP
//
////////////////////////////////////////////////////////////////////////////////

require "{$rootPath}system/constants.php";

$threadHash	= isset($_POST['th']) ? $_POST['th'] : null;
$stateHash	= isset($_POST['sh']) ? $_POST['sh'] : null;
$lastPost	= isset($_POST['lp']) ? $_POST['lp'] : null;

session_start();

checkToken();

$user = $_SESSION[SESS_USER];
$thread = new Thread($threadHash, $stateHash);
//$thread = $_SESSION[SESS_THREAD];
//$thread->latestFetchedPost = intval($_POST['lp']);

//notifyDebug($thread->latestFetchedPost);

if (!isset($_SESSION[SESS_FETCH_N])) $_SESSION[SESS_FETCH_N] = 0;

$nextFetch = 1;

if		($_SESSION[SESS_FETCH_N] < 10)	{ $nextFetch = 1; }
elseif	($_SESSION[SESS_FETCH_N] < 30)	{ $nextFetch = 3; }
elseif	($_SESSION[SESS_FETCH_N] < 80)	{ $nextFetch = 5; }
elseif	($_SESSION[SESS_FETCH_N] < 100)	{ $nextFetch = 10; }
else									{ $nextFetch = 20; }

$response->vars('nf', $nextFetch);

if ($thread->getPosts($lastPost) || $user->getNotifications())
{
	$_SESSION[SESS_FETCH_N] = 0;
}

echo $response;

$_SESSION[SESS_FETCH_N] ++;