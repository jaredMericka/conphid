<?php

class app_settings extends App
{
	static $level = 2;

	static function getName()
	{
		return 'User Settings Manager';
	}

	static function getHelp()
	{
		return 'User Settings Manager provides a series of commands which allow you to view and change your personal settings.';
	}

	static function getSplash()
	{
		global $user;

		ob_start();

		?>
<x><?=ICO_COGS . ICO_USER . ICO_COGS?></x> Welcome to <?=CONPHID?> User Settings Manager <x><?=ICO_COGS . ICO_USER . ICO_COGS?></x>

Here you can modify the values in your settings array.
Use the <b>>commlist</b> command to see the available commands for settings customisation.

<?php if ($user->level < 2) { ?>
<c> Account registration </c>
If you wish to register an account, the following steps will guide you through the process.

<?php if ($user->level < 1) { ?>
<m>&#x2756;</m> Account level <y><ico>&#xf005;</ico>0</y> to <y><ico>&#xf005;</ico>1</y>: Use the command <b>>name</b> to name your account.
(e.g., "<b>>name John Citizen</b>").
This will elevate your account level to level <y><ico>&#xf005;</ico>1</y>.
<?php } ?>

<m>&#x2756;</m> Account level <y><ico>&#xf005;</ico>1</y> to <y><ico>&#xf005;</ico>2</y>: Use the command <b>>register</b> to associate an email address with your account.
(e.g., "<b>>register johncitizen@domain.com</b>").
This will elevate your account level to level <y><ico>&#xf005;</ico>2</y>.
<?php } ?>
		<?php
		return ob_get_clean() . "\n\n" . self::getSettingsTable();
	}

	static function onLoad()
	{
		self::displaySettings();
	}

	static function getSettingsTable ()
	{
		global $user;

		return "<w_> User settings for {$user->name}: </w_>\n\n" . assocArrayToTable($user->settings, 'g', null, '<x>=</x> ');
	}

	static function displaySettings($set = false)
	{
		global $user;
		global $response;

		$text = "<b>User settings for <c>{$user->name}</c>:</b>\n\n" . assocArrayToTable($user->settings, 'g', null, '<x>=</x> ');

		$response->set(PANE_SYSTEM, $text);
	}

	static function afterComm($input)
	{
		global $user;
		global $response;


		$comm = explode(' ', $input)[0];

		if (in_array($comm, [
			'>name',
			'>timestamp',
			'>hotkey'
		]))
		{
			$user->saveMe = true;
			$response->set(PANE_SYSTEM, self::getSplash());
		}
	}
}

class comm_name extends Command
{
	static $cd_period	= TIME_WEEK;
	static $cd_runs		= 1;

	static function run ($input)
	{
		global $user;

		$input = cleanString($input);

		$length = strlen($input);

		if ($length > 20)
		{
			notifyError("Name must be 20 characters or less. \"<w>{$input}</w>\" is too long.");
			return;
		}
		elseif ($length < 2)
		{
			notifyError("Name must be 2 characters or more. \"<w>{$input}</w>\" is too short.");
			return;
		}

		$results = query('SELECT name FROM users WHERE name = :name', [':name' => $input]);

		if (count($results) > 0)
		{
			notifyError("Name \"<w>{$input}</w>\" is in use.");
			return;
		}

		$nameBefore = $user->name;

		$user->name = $input;
		$user->promote(1);

		notifySuccess("Name changed from \"<w>{$nameBefore}</w>\" to \"<w>{$user->name}</w>\".");

		self::cooldown();
	}

	static function getHelp ()
	{
		return 'Changes the name associated with your hash. You may only change your name once a week.';
	}

	static function getExample()
	{
		return "<b>>name</b> <c>Fred</c>\nThis will change your username to \"Fred\" (if that name is available and you haven't changed your name is the last week).";
	}
}

class comm_register extends Command
{
	static $level = 1;

	public  static function run($input)
	{
		$input = trim($input);

		if (preg_match('/^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/', $input))
		{
			$inUse = query('SELECT email FROM users WHERE email = :email', [':email' => $input]);

			if ($inUse)
			{
				notifyError("Email address <w>{$input}</w> is already in use.");
				return;
			}

			self::sendConfirmation($input);
		}
		else
		{
			notifyError('Please enter a valid email address.');
			return;
		}
	}

	public  static function getHelp()
	{
		return 'Registers an email address to this account. No unsolicited mail will be sent to the nominated email address and the address will not be forwarded to any third parties. No other users will be able to see your email address. Registering an email address will result in an account promotion to level 2.';
	}

	public  static function getExample()
	{
		return "<b>>register</b> <c>person@domain.com</c>\nThis will send an email to the nominated address to instructions on how to proceed with the email registration.";
	}

	public static function sendConfirmation ($address)
	{
		global $user;

		if ($user->properties[PROP_NEXT_EMAIL_REGO] > $_SERVER['REQUEST_TIME'])
		{
			$canDo = getReadableTime($user->properties[PROP_NEXT_EMAIL_REGO] - $_SERVER['REQUEST_TIME']);

			notifyWarning("Previous registration attempt too recent. You may not send another registration meail for <w>{$canDo}</w>.");
			return;
		}

		$confirmationCode = getEmailConfirmationCode($user->hash, $address);
		$getAddress = urlencode($address);

		$confirmationURL	= CONPHID_URL . "reg.php?u={$user->hash}&e={$getAddress}&c={$confirmationCode}";
		$quickLoginURL		= CONPHID_URL . "?u={$user->hash}";
		$nomailURL			= CONPHID_URL . "reg.php?u={$user->hash}&e={$getAddress}&c=nomail";

		$subject = "Conphid account confirmation";

		$lc = '#048';

		ob_start();
?><html>
	<body style="font-family:monospace; font-size:11pt;">
		<h1 style="font-weight:normal;">Con&phi;<span style="background-color:#000; color:#fff;">d</span></h1>
		<p>
			Thankyou for registering with Con&phi;<span style="background-color:#000; color:#fff;">d</span>.
		</p>
		<p>
			Registering a valid email address to your account will raise your account level by one point, granting you access to new features and privileges.
			Con&phi;<span style="background-color:#000; color:#fff;">d</span> will not send any unsolicited mail or forward your email address to any third parties for any reason. Your email address will not be visible to any other users.
		</p>
		<p>
			Your account name and hash are: <b><?= $user->name . ' (' . $user->hash . ')'; ?></b>
			<br>To register this email address to your account, <a href="<?= $confirmationURL; ?>" style="color:<?=$lc?>">CLICK HERE</a>.
		</p>
		<p>
			We recommend you keep this email in case you misplace your hash.
			<br>Use the following link to access Con&phi;<span style="background-color:#000; color:#fff;">d</span> and log straight into your account:
			<br><a href="<?= $quickLoginURL; ?>" style="color:<?=$lc?>"><?= $quickLoginURL ?></a>
			<br>It is recommended that you save that URL to a bookmark only if you are using a private computer.
			<br>If you did not request this email and wish not to be emailed by Con&phi;<span style="background-color:#000; color:#fff;">d</span>, please click <a href="<?= $nomailURL ?>" style="color:<?=$lc?>">HERE</a>.
		</p>
	</body>
</html><?php

		$message = ob_get_clean();

		notifyDebug($confirmationURL);

		if (sendMail($address, $subject, $message))
		{
			$user->properties[PROP_NEXT_EMAIL_REGO] = $_SERVER['REQUEST_TIME'] + TIME_DAY;
			$user->saveMe = true;
			notifySuccess("Confirmation email sent to \"<w>{$address}</w>\".");
		}
		else
		{
			notifyError("Failed to send confirmation email to \"<w>{$address}</w>\".");
		}
	}
}

class comm_timestamp extends Command
{
	static $level = 1;

	static function run ($input)
	{
		global $user;
		global $response;

		$length = strlen($input);

		if ($length > 20)
		{
			notifyError("Date format string is too long (<w>{$length}</w> characters; must be 20 or lower).");
		}
		elseif ($input === '')
		{
			unset($user->settings[SETT_TIMESTAMP_FORMAT]);
			notifyWarning("Date format string missing; default format will be used.");
		}
		else
		{
			if ($input === '?')
			{
				$response->append(PANE_SYSTEM, self::getKey());
			}
			else
			{
				$user->settings[SETT_TIMESTAMP_FORMAT] = $input;
				notifySuccess("Timestamp format set to \"<w>{$input}</w>\" (<w>" . getTimestamp() . "</w>).");
			}
		}
	}

	static function getHelp ()
	{
		return 'Sets the timestamp format. Format string may not excede 20 characters. An empty parameter will use the default format. For formatting help, input "<b>?</b>" as the parameter.';
	}

	static function getKey ()
	{
		ob_start(); ?>
<g>Full Date/Time</g>
  <y>c</y> - ISO 8601 date (2004-02-12T15:19:21+00:00)
  <y>r</y> - RFC 2822 formatted date (Thu, 21 Dec 2000 16:01:07 +0200)
  <y>U</y> - Seconds since the Unix Epoch

<g>Days</g>
  <y>d</y> - Day of the month, 2 digits with leading zeros
  <y>D</y> - A textual representation of a day, three letters
  <y>j</y> - Day of the month without leading zeros
  <y>l</y> - A full textual representation of the day of the week
  <y>N</y> - Numeric representation of the day of the week
  <y>S</y> - English ordinal suffix for the day of the month, 2 characters (works with j)
  <y>w</y> - Numeric representation of the day of the week
  <y>z</y> - The day of the year (starting from 0)

<g>Weeks</g>
  <y>W</y> - Week number of year
  <y>F</y> - A full textual representation of a month, such as January or March
  <y>m</y> - Numeric representation of a month, with leading zeros

<g>Months</g>
  <y>M</y> - A short textual representation of a month, three letters
  <y>n</y> - Numeric representation of a month, without leading zeros
  <y>t</y> - Number of days in the given month

<g>Years</g>
  <y>L</y> - Whether it's a leap year
  <y>o</y> - Year number. This has the same value as Y, except that if the ISO week number (W) belongs to the previous or next year, that year is used instead
  <y>Y</y> - A full numeric representation of a year, 4 digits
  <y>y</y> - A two digit representation of a year

<g>Time</g>
  <y>a</y> - Lowercase Ante meridiem and Post meridiem
  <y>A</y> - Uppercase Ante meridiem and Post meridiem
  <y>B</y> - Swatch Internet time
  <y>g</y> - 12-hour format of an hour without leading zeros
  <y>G</y> - 24-hour format of an hour without leading zeros
  <y>h</y> - 12-hour format of an hour with leading zeros
  <y>H</y> - 24-hour format of an hour with leading zeros
  <y>i</y> - Minutes with leading zeros
  <y>s</y> - Seconds, with leading zeros

<g>Timezone</g>
  <y>e</y> - Timezone identifier
  <y>I</y> - Whether or not the date is in daylight saving time
  <y>O</y> - Difference to Greenwich time (GMT) in hours
  <y>P</y> - Difference to Greenwich time (GMT) with colon between hours and minutes
  <y>T</y> - Timezone abbreviation
  <y>Z</y> - Timezone offset in seconds. The offset for timezones west of UTC is always negative, and for those east of UTC is always positive.
<?php

		return ob_get_clean();
	}

	static function getExample()
	{
		return "<b>>timestamp</b> <c>?</c>\nThis will display the key for constructing timestamps.\n<b>>timestamp</b> <c>d/M/y G:i:s</c>\nThis will set time timestamp format to \"d/M/y G:i:s\" (which will render as \""
		. date('d/M/y G:i:s', $_SERVER['REQUEST_TIME_FLOAT'])
		. '").';
	}
}

class comm_hotkey extends Command
{
	static $level = 1;

	const CHAR_LIMIT = 200;

	public static function run($input)
	{
		global $user;
		global $response;

		$parts = explode(' ', $input, 2);

		if (!isset($parts[0], $parts[1]))
		{
			notifyError('Please ensure parameters consist of a number between 0 and 9 followed by the desired stored input (e.g., "<b>>set_hotkey 3 This will be allocated to three.</b>")');
			return;
		}

		$key = $parts[0];

		if (!is_numeric($key))
		{
			notifyError("Can only allocate to keys 0 - 9. \"<w>{$key}</w>\" does not meet this criteria.");
			return;
		}

		$key = (int)$key;

		if ($key > 9 || $key < 0)
		{
			notifyError("Can only allocate to keys 0 - 9. \"<w>{$key}</w>\" does not meet this criteria.");
			return;
		}

		$length = strlen($parts[1]);
		if ($length > self::CHAR_LIMIT)
		{
			notifyError('Can only store strings of <w>' . self::CHAR_LIMIT . "</w> characters or less. <w>{$length}</w> characters were submitted.");
			return;
		}

		$user->settings[SETT_HOTKEYS][$key] = $parts[1];
		ksort($user->settings[SETT_HOTKEYS]);

		notifySuccess("\"<w>{$parts[1]}</w>\" was successfully allocated to key \"<w>{$key}</w>\". To access, press <w_> Ctrl </w_> + <w_> {$key} </w_>.");

		$response->vars('hotkeys', $user->settings[SETT_HOTKEYS]);
	}

	public static function getHelp()
	{
		return 'Allocates an input to a number between 0 and 9. Pressing <w_> Ctrl </w_> + [the allocated number] will automatically input the stored text. This can be used to store commands or other messages. Stored inputs are limited to ' . self::CHAR_LIMIT . ' characters.';
	}

	static function getExample()
	{
		return "<b>>hotkey</b> <c>3 Common phrase</c>\nThis will allcate the string \"Common phrase\" to the <w_> 3 </w_> and pressing <w_> Ctrl </w_> + <w_> 3 </w_> will paste \"Common phrase\" into the input box.";
	}
}

class comm_hash extends Command
{
	public  static function run($input)
	{
		global $user;

		$newHash = getHash($input);

		if ($user->hash === $newHash)
		{
			notifyWarning('New user hash is the same as existing user hash.');
			return;
		}

		if (query('SELECT name FROM users WHERE hash = :hash', [':hash' => $newHash], true))
		{
			notifyError("Hash <w>{$newHash}</w> is reserved by the system and cannot be used.");
			return;
		}

		
	}

	public  static function getHelp()
	{
		return 'Current the current user\'s hash to the inputted hash or the hash of the input.';
	}

	public  static function getExample()
	{
		;
	}
}

//class comm_defapp extends Command
//{
//	public  static function run($input)
//	{
//		;
//	}
//
//	public  static function getHelp()
//	{
//		;
//	}
//
//	public  static function getExample()
//	{
//		;
//	}
//}