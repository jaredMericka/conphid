<?php

//sleep(mt_rand(1,4));
//sleep(6);

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
////////////////////////////////////////////////////////////////////////////////
//
//	LOAD SESSION OBJECTS
//
////////////////////////////////////////////////////////////////////////////////

session_start();

checkToken();


$user = $_SESSION[SESS_USER];
//$thread = $_SESSION[SESS_THREAD];
$thread = new Thread($threadHash, $stateHash);

////////////////////////////////////////////////////////////////////////////////
//
//	LOAD COMMANDS AND APP
//
////////////////////////////////////////////////////////////////////////////////

require "{$rootPath}system/globalCommands.php";

if (isset($user->app))
{
    if (is_file("{$rootPath}apps/{$user->app}.php"))
    {
        require "{$rootPath}apps/{$user->app}.php";
    }
}

////////////////////////////////////////////////////////////////////////////////
//
//	PROCESS STRING
//
////////////////////////////////////////////////////////////////////////////////

$input = cleanInput($_POST['sub']);
//$input = strip_tags($_POST['sub']);
//$input = $_POST['sub'];

////////////////////////////////////////////////////////////////////////////////
//
//	CHECK FOR COMMAND
//
////////////////////////////////////////////////////////////////////////////////



if ($input[0] === '>')
{
    $parts = explode(' ', $input, 2);

    $switches = strtolower(trim($parts[0], '>'));
    $params = isset($parts[1]) ? $parts[1] : '';

	$switches = explode('-', $switches);

	$commandName = array_shift($switches);

    $help = false;
    if (strpos($commandName, '?') !== false)
    {
		$help = true;
		$commandName = trim($commandName, '?');
    }

    $command = "comm_{$commandName}";

    if (class_exists($command))
    {
		$canExecute = true;

		// Checks that happen inside this IF should be checks that are over-ridden by a thread moderator.
		if (!($command::$modComm && $user->hash === $thread->mod_hash))
		{
			////////////////////////////////////////////////////////////////////
			//
			// CHECK THAT LEVEL IS HIGH ENOUGH TO RUN COMMAND
			//
			////////////////////////////////////////////////////////////////////
			if ($command::$level > $user->level)
			{
				notifyError("Command \"<b>>{$commandName}</b>\" requires that the executing account be of level <w>{$command::$level}</w> or higher.");
				$canExecute = false;
			}

			////////////////////////////////////////////////////////////////////
			//
			// CHECK THAT LEVEL IS HIGH ENOUGH TO RUN COMMAND IN THIS PARTICULAR THREAD
			//
			////////////////////////////////////////////////////////////////////
			if ($command::$threadBased && $thread->level > $user->level)
			{
				notifyError("Command \"<b>>{$commandName}</b>\" is restricted by the thread level (<w>{$thread->level}</w>).");
				$canExecute = false;
			}
		}

		////////////////////////////////////////////////////////////////////////
		//
		// CHECK COOLDOWN STUFF
		//
		////////////////////////////////////////////////////////////////////////
		if (isset($command::$cd_runs, $command::$cd_period))
		{
			notifyDebug('Checking cooldown');

			if (is_array($command::$cd_runs))
			{
				if (isset($command::$cd_runs[$user->level]))
				{
					notifyDebug('Runs set by level');
					$runs = $command::$cd_runs[$user->level];
				}
			}
			else
			{
				notifyDebug('Runs set globally');
				$runs = $command::$cd_runs;
			}

			if (is_array($command::$cd_period))
			{
				if (isset($command::$cd_period[$user->level]))
				{
					notifyDebug('Period set by level');
					$period = $command::$cd_period[$user->level];
				}
			}
			else
			{
				notifyDebug('Period set globally');
				$period = $command::$cd_period;
			}

			$cd_key = $user->app . $command;

			if (isset($runs, $period, $user->cooldowns[$cd_key]))
			{
				notifyDebug('Commencing filter');


				$currentCooldowns = [];

				// Filter out the cooldowns that have expired
				foreach ($user->cooldowns[$cd_key] as $timestamp)
				{
					if ($timestamp > $_SERVER['REQUEST_TIME'] - $period)
					{
						$currentCooldowns[] = $timestamp;
					}
				}

				$user->cooldowns[$cd_key] = $currentCooldowns;

				if (count($currentCooldowns) >= $runs)
				{
					$canExecute = false;
					$rtime = getReadableTime($period);
					$goodIn = getReadableTime(min($currentCooldowns) + $period - $_SERVER['REQUEST_TIME']);
					notifyError("Command <w>{$command}</w> can only be used <w>{$runs}</w> time(s) every <w>{$rtime}</w>. It can be run again in <w>{$goodIn}</w>.");
				}
			}
		}

		////////////////////////////////////////////////////////////////////////
		//
		// CHECK SWITCHES
		//
		////////////////////////////////////////////////////////////////////////
		$invalidSwitches = [];

		if ($switches)
		{
			foreach ($switches as $switch)
			{
				$switch = strtolower($switch);

				if (!isset($command::$switches[$switch]))
				{
					$invalidSwitches[] = $switch;
				}
			}
		}

		if ($invalidSwitches)
		{
			$canExecute = false;

			$s = (count($invalidSwitches) > 1) ? 'es' : '';

			$invalidSwitches = implode('</w>, <w>', $invalidSwitches);

			notifyError("Invliad switch{$s}: <w>{$invalidSwitches}</w>.");
		}

		// If we can still do it after the checks, go ahead.
		if ($canExecute)
		{
			$appName = $user->app;

			if (!$command::$hidden)
			{
				$qm = $help ? '?' : '';
				$response->append(PANE_SYSTEM, '<b>>' . cleanString($commandName . $qm) . '</b> <c>' . cleanString($params) . '</c>');
			}

			if ($appName) $appName::beforeComm($input);

			if ($help)
			{
				$response->set(PANE_SYSTEM, "<y>Help:</y>\n<g>{$commandName}</g> - " . $command::getHelp() . "\n\n<y>Example:</y>\n" . $command::getExample());
			}
			else
			{

				foreach ($command::$switches as $switch)
				{
					if (in_array($switch, $switches))
					{
						$command::$switch($params);
					}
				}

				$command::run($params);
			}

			if ($appName) $appName::afterComm($input);

//			$response->clear('input');
		}
		// There should be no need to output anything at this point since the error checking loops should dispense their own errors if they fail.
    }
    else
    {
		$response->append(PANE_SYSTEM, '<r>>' . cleanString($commandName) . '</r> <y>' . cleanString($params) . '</y>');
		notifyError("Unrecognised command <y>\"{$commandName}\"</y>.</r>");
    }
}
else
{
	$thread->submitPost($input);
}

$response->clear('input');

$thread->getPosts($lastPost);

////////////////////////////////////////////////////////////////////////////////
//
//	RETURN THE RESPONSE
//
////////////////////////////////////////////////////////////////////////////////


if ($thread->saveMe)	$thread->save();
if ($user->saveMe)		$user->save();

echo $response;

$_SESSION[SESS_USER] = $user;
//$_SESSION[SESS_THREAD] = $thread;