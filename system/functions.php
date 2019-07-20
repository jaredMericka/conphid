<?php

function devMode ()
{
	global $user;
	return isset($_SESSION[SESS_DEV]) && $_SESSION[SESS_DEV] === true && $user->level >= 9;
}

$dbConn = null;
//$dbCount = 0;
function getDB()
{
	global $dbConn;
//	global $dbCount;

//	$dbCount++;

//	if ($dbCount > 5) notifyDebug("Queries: {$dbCount}");

	if (!($dbConn instanceof PDO))
	{
		$dbConn = new PDO('mysql:dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
	}

	return $dbConn;
}

function query($query, $params, $countOnly = false)
{
	$db = getDB();

	$stmt = $db->prepare($query);

//	foreach ($params as $handle => $param)
//	{
//		if (is_int($param))
//		{
//			notifyDebug($handle . ' ' . $param);
//			$stmt->bindParam($handle, $param, PDO::PARAM_INT);
//		}
//		else
//		{
//			notifyDebug($handle . ' ' . $param);
//			$stmt->bindParam($handle, $param, PDO::PARAM_STR);
//		}
//	}
//	$stmt->execute();

	$stmt->execute($params);

	$errorInfo = $stmt->errorInfo();
	if ($errorInfo[0] !== '00000')
	{
		throw new Exception("SQL ERROR {$errorInfo[0]}: {$errorInfo[2]}", E_USER_NOTICE);
	}

	$results = $stmt->fetchAll();

//	notifyDebug($stmt->queryString);

	if ($countOnly)
	{
		return count($results);
	}
	else
	{
		return $results;
	}
}

function getHash($string)
{
	if (isHash($string))
	{
		return $string;
	}
	else
	{
		return md5($string);
	}
}

function isHash ($string)
{
	return preg_match('/^[a-f0-9]{32}$/', $string);
}

function getRandomHash()
{
	$chars = str_split('0123456789abcdef');

	$hash = '';

	for ($i = 0; $i < 32; $i ++)
	{
		$hash .= $chars[array_rand($chars)];
	}

	return $hash;
}

function getTimestamp ($time = null)
{
	global $user;

	if (!$time) $time = $_SERVER['REQUEST_TIME'];

	if (is_object($user->settings))
	{
		return json_encode($user);
	}

	if (isset($user->settings[SETT_TIMESTAMP_FORMAT]))
	{
		$format = $user->settings[SETT_TIMESTAMP_FORMAT];
	}
	else
	{
		$format = 'r';
	}

	return date($format, $time);
}

function assocArrayToTable ($array, $tag_keys = null, $tag_vals = null, $separator = null, $indent = null)
{
	$keyWidth = 0;

	$indent = str_pad('', $indent ? $indent : 0, ' ');

	foreach ($array as $key => $val)
	{
		$length = strlen($key) + 1;
		if ($length > $keyWidth) $keyWidth = $length;
	}

	$result = '';

	foreach ($array as $key => $val)
	{

		$key = str_pad($key, $keyWidth);
		if (is_array($val))
		{
//			$val = trim(assocArrayToTable($val, $tag_keys, $tag_vals, $separator, strlen(strip_tags("{$indent}{$key}{$separator}"))));
			$val = trim(assocArrayToTable($val, null, null, '<x>-</x> ', strlen(strip_tags("{$indent}{$key}{$separator}"))));
		}
		elseif (is_bool($val))
		{
			$val = $val ? 'true' : 'false';
		}
		if ($tag_keys) $key = "<{$tag_keys}>{$key}</{$tag_keys}>";
		if ($tag_vals) $val = "<{$tag_vals}>{$val}</{$tag_vals}>";
		$result .= "{$indent}{$key}{$separator}{$val}\n";
	}

	return $result;
}

function getRandomName ()
{
	$sylables = mt_rand(2, 4);

	$word = '';

	for ($s = 1; $s <= $sylables; $s++)
	{
		$word = appendSylable($word);
	}

	return ucwords($word);
}

function appendSylable (& $word)
{
	$consts_start	= str_split('bcdfghjklmnprstvwz');
	$consts	= str_split('bcdfghjklmnprstvwxyz');
	$vowels	= str_split('aeiou');

	$first = $word === '';

	// [Add this letter] => [if this letter is last]
	$nextLetterKey = [
		'r' => str_split('bcdfgkpt'),
		'h' => str_split('scg'),
		'l' => str_split('bcfgks'),
		'y' => str_split('eo')
	];

	$lastLetter = substr($word, -1, 1);

	if (mt_rand(0,3) || in_array($lastLetter, $vowels))	$word .= ar($first ? $consts_start : $consts);

	if (mt_rand(0,2))
	{
		$lastLetter = substr($word, -1, 1);
		$nextLetters = [];

		foreach ($nextLetterKey as $nextLetter => $lastLetters)
		{
			if (in_array($lastLetter, $lastLetters))
			{
				$nextLetters[] = $nextLetter;
			}
		}

		if ($nextLetters) $word .= ar($nextLetters);
	}

	$word .= ar($vowels);
//	if (mt_rand(0,1)) $sylable .= ar($consts);

	return $word;
}

function ar ($array) { return $array[array_rand($array)]; }


function errorHandler ($errno, $errstr, $errfile, $errline)
{
//	if (!devMode()) return;

//    global $response;
    global $user;

    $errfile = basename($errfile);

	$fluff = '(cog)(cgs)';

	$errmess = "[r_] {$fluff} SYSTEM ERROR {$errno} {$fluff} [/r_]\n";
    $errmess .= "[r]{$errfile}[/r] - [[r]{$errline}[/r]]:\n{$errstr}\n\n";

    foreach(debug_backtrace(null, 10) as $level => $trace)
    {
		if (!$level) continue;

		$function	= isset($trace['function'])	? $trace['function']			: '';
		$line		= isset($trace['line'])		? "[[w]{$trace['line']}[/w]]"	: '';
		$file		= '';
		$class		= isset($trace['class'])	? $trace['class']				: '';
		$object		= isset($trace['object'])	? get_class($trace['object'])	: '';
		$type		= isset($trace['type'])		? "[r]{$trace['type']}[/r]"	: '';
		$args		= '';

		if (isset($trace['args']))
		{
				foreach ($trace['args'] as $arg)
				{
						if (is_object($arg)) $args .= get_class($arg) . ', ';
						elseif (is_array($arg)) $args .= 'array, ';
						else $args .= $arg . ', ';
				}
				$args = "({$args})";
		}

		if (isset($trace['file']))
		{
//				$file = $trace['file'];
				$file = explode('conphid', $trace['file'])[1];
				$file = trim($file, '\\/');
				$file = "[r]{$file}[/r]";
		}

		$errmess .= "[{$level}]\t{$file}\t{$line}:\t{$object} {$class}{$type}{$function}{$args};\n";
    }

	$errmess = htmlspecialchars($errmess);

	$db = getDB();

	$sql = 'INSERT INTO posts (thread_hash, body, timestamp, username, ip, nametag, re, user_hash)';
	$sql .= ' VALUES (:hash, :body, :timestamp, :username, :ip, :nametag, :re, :uhash)';

	$stmt = $db->prepare($sql);

	$stmt->execute([
		':hash'			=> md5(ERROR_THREAD),
		':body'			=> $errmess,
		':timestamp'	=> $_SERVER['REQUEST_TIME'],
		':username'		=> $user->name,
		':ip'			=> $_SERVER['REMOTE_ADDR'],
		':nametag'		=> $user->properties[PROP_NAMETAG],
		':re'			=> null,
		':uhash'		=> $user->hash,
	]);

    return true;
}

set_error_handler('errorHandler', E_ALL);

function checkStatement (PDOStatement &$stmt)
{
	notifyDebug('Checking statement');
//	return;
	$errorInfo = $stmt->errorInfo();


	if ($errorInfo[0] !== '00000')
	{
		notifyDebug(json_encode($errorInfo));
		return;
		throw new Exception("SQL ERROR {$errorInfo[0]}: {$errorInfo[2]}", E_USER_NOTICE);
	}
}

function notifyError ($string)
{
	global $response;

	$response->append(PANE_SYSTEM, '<r_>' . ICO_ERROR . "ERROR:</r_> <r>{$string}</r>");
}

function notifyWarning ($string)
{
	global $response;

	$response->append(PANE_SYSTEM, '<y_>' . ICO_WARN . "WARNING:</y_> <y>{$string}</y>");
}

function notifySuccess ($string)
{
	global $response;

	$response->append(PANE_SYSTEM, '<g_>' . ICO_SUCCESS . "SUCCESS:</g_> <g>{$string}</g>");
}

function notifyPromotion ($string)
{
	global $response;

	$response->append(PANE_SYSTEM, '<y_>' . ICO_STAR . "PROMOTION:</y_> <y>{$string}</y>");
}

function notifyCooldown ($string)
{
	global $response;

	$response->append(PANE_SYSTEM, '<c_>' . ICO_COOLDOWN . "COOLDOWN:</c_> <c>{$string}</c>");
}

function notifyDebug ($string)
{
	if (!devMode()) return;

	global $response;

	$response->append(PANE_SYSTEM, '<m_>' . ICO_DEBUG . "DEBUG:</m_> <m>{$string}</m>");
}

function runCommand($commandName, $params)
{
	global $thread;
	global $user;
	global $response;

	$help = false;
    if (strpos($commandName, '?') !== false)
    {
		$help = true;
		$commandName = trim($commandName, '?');
    }

    $command = "comm_{$commandName}";

    if (class_exists($command))
    {
		$appName = $user->app;

		if ($command::$threadBased)
		{
			if ($thread->level > $user->level)
			{
				notifyError("Command \"<b>>{$commandName}</b>\" is restricted by the thread level (<w>{$thread->level}</w>).");
				return;
			}
		}

		if ($appName) $appName::beforeComm($params);

		if ($help)
		{
			$response->set(PANE_SYSTEM, "<y>Help:</y>\n<g>{$commandName}</g> - " . $command::getHelp() . "\n\n<y>Example:</y>\n" . $command::getExample());
		}
		else
		{
			$command::run($params);
		}

		$response->append(PANE_MAIN, "<b>>{$commandName}</b> <c>{$params}</c>");

		if ($appName) $appName::afterComm($params);

		$response->clear('input');
    }
    else
    {
		notifyError("Unrecognised command <y>\"{$commandName}\"</y>.</r>");
		$response->append(PANE_MAIN, "<r>>{$commandName}</r> <y>{$params}</y>");
    }
}

function cleanInput ($string)
{
	global $user;
	global $maxSubmissionSize;
	global $level_colours;
	global $level_emoji;

	$string = substr($string, 0, $maxSubmissionSize[$user->level]);

	$forbiddenColours = [];
	foreach ($level_colours as $level => $colours)
	{
		if ($user->level >= $level) continue;
		$forbiddenColours = array_merge($forbiddenColours, $colours);
	}


	foreach ($forbiddenColours as $tag)
	{
		$string = str_replace(["[{$tag}]", "[/{$tag}]"], '', $string);
	}

	$forbiddenEmoji = [];
	foreach ($level_emoji as $level => $emoji)
	{
		if ($user->level >= $level) continue;
		$forbiddenEmoji = array_merge($forbiddenEmoji, $emoji);
	}

	if ($user->level < HR_LEVEL) $forbiddenEmoji[HR_KEY] = HR_KEY;

	$string = str_replace(array_keys($forbiddenEmoji), '', $string);

	return $string;
}

//function cleanOutput ($string)
//{
//	global $level_colours;
//
//	$tags = [];
//	$rags = [];
//
//	foreach ($level_colours as $colours)
//	{
//		foreach ($colours as $colour)
//		{
//			$tags[] = "<{$colour}>";
//			$tags[] = "</{$colour}>";
//
//			$rags[] = "@#@#{$colour}@#@#";
//			$rags[] = "#@#@{$colour}#@#@";
//		}
//	}
//
//	$string = str_replace($tags, $rags, $string);
//	$string = htmlspecialchars($string);
//
//	$string = str_replace($rags, $tags, $string);
//
//	return $string;
//}

// This is done right before the reply is sent to the client
function cleanReply ($string)
{
	global $level_colours;
	global $level_emoji;

	$string = htmlspecialchars($string);

	$fauxTags = [];
	$realTags = [];

	$tags = [];
	foreach ($level_colours as $colours) $tags = array_merge($tags, $colours);

	foreach ($tags as $tag)
	{
		$fauxTags[] = "[{$tag}]";
		$fauxTags[] = "[/{$tag}]";

		$realTags[] = "<{$tag}>";
		$realTags[] = "</{$tag}>";
	}

	$string = str_replace($fauxTags, $realTags, $string);

	$emoji = [];
	foreach ($level_emoji as $lemoji) $emoji = array_merge($lemoji, $emoji);

	$emojiKeys = array_keys($emoji);

	$string = str_replace($emojiKeys, $emoji, $string);

	$string = preg_replace('/\s*\(hr\)\s*/', '<hr>', $string);

	$string = replaceLinks($string);

	return $string;
}

// This is run on data before it goes to the database
function cleanString ($string, $maxLength = null)
{
	if (isset($maxLength)) $string = substr($string, 0, $maxLength);
	$string = htmlspecialchars($string);
	$string = trim($string);

	return $string;
}

function getAvailableTags()
{
	global $user;
	global $level_colours;

	$tags = [];

	for ($lvl = 0; $lvl <= $user->level; $lvl ++)
	{
		if (isset($level_colours[$lvl]))
		{
			$tags = array_merge($tags, $level_colours[$lvl]);
		}
	}

	return $tags;
}

function getAvailableEmoji()
{
	global $user;
	global $level_emoji;

	$emoji = [];

	for ($lvl = 0; $lvl <= $user->level; $lvl ++)
	{
		if (isset($level_emoji[$lvl]))
		{
			$emoji = array_merge($emoji, $level_emoji[$lvl]);
		}
	}

	return $emoji;
}

function getEmailConfirmationCode ($user_hash, $email)
{
	return md5($user_hash . SALT_EMAIL . $email);
}

function getReadableTime ($seconds)
{
	$weeks		= 0;
	$days		= 0;
	$hours		= 0;
	$minutes	= 0;

	if ($seconds > TIME_WEEK)
	{
		$weeks = floor($seconds / TIME_WEEK);
		$seconds = $seconds % TIME_WEEK;
	}

	if ($seconds > TIME_DAY)
	{
		$days = floor($seconds / TIME_DAY);
		$seconds = $seconds % TIME_DAY;
	}

	if ($seconds > TIME_HOUR)
	{
		$hours = floor($seconds / TIME_HOUR);
		$seconds = $seconds % TIME_HOUR;
	}

	if ($seconds > TIME_MINUTE)
	{
		$minutes = floor($seconds / TIME_MINUTE);
		$seconds = $seconds % TIME_MINUTE;
	}

	$string = '';

	if ($weeks)		$string .= "{$weeks} weeks ";
	if ($days)		$string .= "{$days} days ";
	if ($hours)		$string .= "{$hours} hours ";
	if ($minutes)	$string .= "{$minutes} mins ";
	if ($seconds)	$string .= "{$seconds} secs ";

	return trim($string);
}

function floodControl()
{
	global $user;
	global $level_floodControl;

	if (isset($_SESSION[SESS_FLOOD]) && $_SESSION[SESS_FLOOD] > $_SERVER['REQUEST_TIME']) return $_SESSION[SESS_FLOOD] - $_SERVER['REQUEST_TIME'];

	$floodLevel = isset($level_floodControl[$user->level]) ? $level_floodControl[$user->level] : 1;

	$_SESSION[SESS_FLOOD] = $_SERVER['REQUEST_TIME'] + $floodLevel;

	return 0;
}

function sendMail($to, $subject, $message, $from = null)
{
	$from = $from ? $from : SYSTEM_EMAIL;

	if (query('SELECT * FROM nomail WHERE address = :to', [':to' => $to]))
	{
		notifyError("The owner of <w>{$to}</w> has expressed that they would not like to recieve email from " . CONPHID . '. If you are the owner of this email account and wish to have the account removed from the blacklist, contact a ' . CONPHID . ' administrator.');
		return false;
	}

	$headers =	"From: {$from}\r\n";
	$headers .=	"Reply-To: {$from}\r\n";
	$headers .=	"MIME-Version: 1.0\r\n";
	$headers .=	"Content-Type: text/html; charset=ISO-8859-1\r\n";

	return mail($to, $subject, $message, $headers);
}

function getFeed ()
{
	global $response;

	$sql =
		'SELECT
			f.thread_hash,
			f.pinned,
			t.name
		FROM feed f
		LEFT JOIN threads t ON t.hash = f.thread_hash
		ORDER BY f.pinned DESC, f.timestamp DESC
		LIMIT 100';

	$results = query($sql, []);

	$response->clear(PANE_FEED);

	$ico = '<b>' . ICO_FEED . '</b> ';

	foreach ($results as $result)
	{
		$ico = $result['pinned'] ? '<c>' . ICO_PINNED . '</c> ' : '<b>' . ICO_FEED . '</b> ';

		$name = $result['name'] ? $result['name'] : $result['thread_hash'];

		$tag = "<a href=\"?t={$result['thread_hash']}\" title=\"{$name}\">{$ico}{$name}</a>";

		$response->append(PANE_FEED, $tag);
	}

	if (!$results)
	{
		$response->append(PANE_FEED, '<x>' . ICO_FEED . ' No feed</x>');
	}
}

////////////////////////////////////////////////////////////////////////////////
//
//	LINK STUFF
//
////////////////////////////////////////////////////////////////////////////////

function _make_url_clickable_cb($matches) {
	$ret = '';
	$url = $matches[2];

	if ( empty($url) )
		return $matches[0];
	// removed trailing [.,;:] from URL
	if ( in_array(substr($url, -1), array('.', ',', ';', ':')) === true ) {
		$ret = substr($url, -1);
		$url = substr($url, 0, strlen($url)-1);
	}
	return $matches[1] . "<a class=\"link\" href=\"$url\" rel=\"nofollow\">$url</a>" . $ret;
}

function _make_web_ftp_clickable_cb($matches) {
	$ret = '';
	$dest = $matches[2];
	$dest = 'http://' . $dest;

	if ( empty($dest) )
		return $matches[0];
	// removed trailing [,;:] from URL
	if ( in_array(substr($dest, -1), array('.', ',', ';', ':')) === true ) {
		$ret = substr($dest, -1);
		$dest = substr($dest, 0, strlen($dest)-1);
	}
	return $matches[1] . "<a class=\"link\" href=\"$dest\" rel=\"nofollow\">$dest</a>" . $ret;
}

function _make_email_clickable_cb($matches) {
	$email = $matches[2] . '@' . $matches[3];
	return $matches[1] . "<a class=\"link\" href=\"mailto:$email\">$email</a>";
}

// This one's mine.
function _make_hash_clickable_cb($matches) {
	$hash = $matches[2];
	return $matches[1] . "<a class=\"link\" href=\"?t={$hash}\">{$hash}</a>";
}

function replaceLinks($ret)
{
	$ret = ' ' . $ret;
	$ret = preg_replace_callback('#([\s>])([\w]+?://[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', '_make_url_clickable_cb', $ret);
	$ret = preg_replace_callback('#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', '_make_web_ftp_clickable_cb', $ret);
	$ret = preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i', '_make_email_clickable_cb', $ret);
	$ret = preg_replace_callback('#([\s>])\#([a-f0-9]{32})#i', '_make_hash_clickable_cb', $ret);

	$ret = preg_replace("#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i", "$1$3</a>", $ret);
	$ret = trim($ret);
	return $ret;
}

function getTip($allOfThem = false)
{
	global $tips;
	global $level_tips;
	global $user;

	$level = isset($level_tips[$user->level]) ? $level_tips[$user->level] : [];

	$tipPool = $user->level > 0 ? array_merge($tips, $level) : $level;

	if ($allOfThem) return $tipPool;

	return $tipPool[array_rand($tipPool)];

}

function checkToken()
{
	if (!isset($_POST['k']) || $_POST['k'] !== $_SESSION[SESS_TOKEN])
	{
		DIE('Something has gone wrong. Refresh your browser to continue.');
	}

	notifyDebug("{$_POST['k']} vs {$_SESSION[SESS_TOKEN]}");
}

function getStringDiff ($string1, $string2)
{
	$string1_length = strlen($string1);
	$string2_length = strlen($string2);

	notifyDebug("s1:{$string1}\n{$string1_length}\n\ns2:{$string2}\n{$string2_length}");

	$chars1 = str_split(strtolower($string1));
	$chars2 = str_split(strtolower($string2));

	$diff1 = count(array_diff($chars1, $chars2));
	$diff2 = count(array_diff($chars2, $chars1));

	// This doesn't work. Figure it out if you can.

	return ($diff1 + $diff2) / ($string1_length + $string2_length);
}

//function refreshToken()
//{
//	global $response;
//
//	$_SESSION[SESS_TOKEN] = getRandomHash();
//
//	$response->vars('k', $_SESSION[SESS_TOKEN]);
//}


function canLoadApp ($input) // Input should be the name of the app sans "app_" prefix.
{
	global $appRegistry;
	global $user;
	global $rootPath;

	$appName = "app_{$input}";

	$canLoad = false;
	foreach ($appRegistry as $app)
	{
		if ($app->file === $input && $app->level <= $user->level)
		{
			$canLoad = true;
			break;
		}
	}

	if ($canLoad && is_file("{$rootPath}apps/{$appName}.php"))
	{
		return true;
	}

	return false;
}