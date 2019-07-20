<?php

////////////////////////////////////////////////////////////////////////////////
//
//	VARIABLES
//
////////////////////////////////////////////////////////////////////////////////

// Settings
define('LOCAL_MODE', in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) ? true : false);

error_reporting(0);

// Session slots
const SESS_USER					= 'u';
const SESS_THREAD				= 't';
const SESS_PENDING_RESPONSE		= 'r';
const SESS_FETCH_TIME			= 'ft';
const SESS_FETCH_N				= 'fn';
const SESS_FLOOD				= 'f';
const SESS_DEV					= 'd';
const SESS_TOKEN				= 'tk';
const SESS_BURN					= 'b';

// Cookie slots
const COOK_USER		= 'u';

// Panes
const PANE_MAIN				= 'main';
const PANE_SYSTEM			= 'system';
const PANE_NOTIFICATIONS	= 'notifications';
const PANE_BOOKMARKS		= 'bookmarks';

const PANE_FEED				= 'feed';
const PANE_LINKS			= 'links';

// UI elements
const UI_USER_NAME			= 'username';
const UI_USER_LEVEL			= 'userLevel';

const UI_APP				= 'app';

const UI_THREAD_HASH		= 'threadHash';
const UI_THREAD_NAME		= 'threadName';
const UI_THREAD_LOCK		= 'threadLock';
const UI_THREAD_SUB			= 'threadSub';

const UI_INPUT				= 'input';
const UI_TIPS				= 'tips';

// Scroll directions
const SCROLL_TOP	= 1;
const SCROLL_BOTTOM	= 2;

// Misc
const CONPHID_URL		= 'http://www.conphid.com/';
const SYSTEM_EMAIL		= 'admin@conphid.com';
const NAME_SYSTEM		= 'System';
const NO_APP			= '<x>No app currently running</x>';
const CONPHID			= '<g>Con&phi;<g_>d</g_></g>';
const VERSION			= '0.6.1&beta;';
//const LOCKOUT_MESSAGE	= '<r>The contents of this thread are only visible to those who can post inside it.</r>';
const LOCKOUT_MESSAGE	= "<r_> <ico>&#xf06e;</ico> You do not have permission to view this thread </r_>\n<r>This thread has restricted access and is only viewable by users who are allowed to make posts.</r>";
const DELETED_POST		= '<r title="Post deleted"><ico>&#xf00d;</ico></r>';
const ANONYMOUS_NAME	= '???';
const BURN_NOTICE		= '<r><ico>&#xf1e2;</ico> This thread has been burned and no longer exists.</r>';


const DEFAULT_THREAD	= 'conphid';
const ERROR_THREAD		= 'to error town.';

const POSTS_FETCHED		= 100;
const SCROLL_FETCHED	= 20;

const CHAR_WIDTH	= 7.2;
const CHAR_HEIGHT	= 15;

// Maximums
$maxSubmissionSize = [
	0 => 2000,
	1 => 3000,
	2 => 5000,
	3 => 6000,
	4 => 7000,
	5 => 8000,
	6 => 9000,
	7 => 10000,
	8 => 20000,
	9 => 30000,
];
const MAX_SUBMISSION	= 5000;
const MAX_BOOKMARK_NAME	= 100;
const MAX_THREAD_NAME	= 100;
const MAX_USER_NAME		= 20;

// Settings
//const SETT_POSTS_FETCHED	= 'POSTS_FETCHED';
const SETT_TIMESTAMP_FORMAT	= 'TIMESTAMP_FORMAT';
//const SETT_TIMEZONE			= 'TIMEZONE';
const SETT_HOTKEYS			= 'HOTKEYS';
const SETT_DEFAULT_APP		= 'DEFAULT_APP';

// Properties
//const PROP_NEXT_NAME_CHANGE		= 1;
//const PROP_PALETTE				= 2;
const PROP_NAMETAG				= 3;
const PROP_NEXT_EMAIL_REGO		= 4;
const PROP_NEXT_PROMOTION_CHECK	= 5;

// Database
define('DB_NAME',		LOCAL_MODE ? 'conphid'	: 'jarebnsk_conphid');
define('DB_USER',		LOCAL_MODE ? 'root'		: 'jarebnsk_root');
define('DB_PASSWORD',	LOCAL_MODE ? ''			: 'suff!cent1yL0ngPa$$w0rd');

// Time
const TIME_SECOND	= 1;
const TIME_MINUTE	= 60;
const TIME_HOUR		= 3600;
const TIME_DAY		= 86400;
const TIME_WEEK		= 604800;

// Icons
const ICO_STAR		= '<ico>&#xf005;</ico>';
const ICO_USER		= '<ico>&#xf007;</ico>';
//const ICO_THREAD	= '<ico>&#xf15c;</ico>';
const ICO_THREAD	= '<ico>&#xf075;</ico>';

const ICO_DOC		= '<ico>&#xf15c;</ico>';

const ICO_LOCK		= '<ico>&#xf023;</ico>';
const ICO_LOCK_OPEN	= '<ico>&#xf09c;</ico>';
const ICO_SEALED	= '<ico>&#xf084;</ico>';
const ICO_HIDDEN	= '<ico>&#xf06e;</ico>';
const ICO_COOLDOWN	= '<ico>&#xf017;</ico>';
const ICO_BURNER	= '<ico>&#xf1e2;</ico>';
const ICO_ANON		= '<ico>&#xf21b;</ico>';
const ICO_SINGLETON	= '<ico>&#xf21d;</ico>';
const ICO_SEARCH	= '<ico>&#xf002;</ico>';

const ICO_MOD		= '<ico>&#xf0ad;</ico>';
const ICO_SUB		= '<ico>&#xf0f3;</ico>';
const ICO_INVITE	= '<ico>&#xf0e0;</ico>';

const ICO_BOOKMARK	= '<ico>&#xf02e;</ico>';
const ICO_FEED		= '<ico>&#xf1ea;</ico>';
const ICO_PINNED	= '<ico>&#xf08d;</ico>';
const ICO_LINKS		= '<ico>&#xf0c1;</ico>';

const ICO_REPLIES	= '<ico>&#xf112;</ico>';
const ICO_EDITED	= '<ico>&#xf040;</ico>';

const ICO_COG		= '<ico>&#xf013;</ico>';
const ICO_COGS		= '<ico>&#xf085;</ico>';
const ICO_SETTINGS	= '<ico>&#xf1de;</ico>';

//const ICO_ZOOM_IN	= '<ico>&#xf00e;</ico>';
const ICO_ZOOM_IN	= '<ico>&#xf055;</ico>';
//const ICO_ZOOM_OUT	= '<ico>&#xf010;</ico>';
const ICO_ZOOM_OUT	= '<ico>&#xf056;</ico>';
const ICO_THEME		= '<ico>&#xf042;</ico>';
const ICO_MUTE		= '<ico>&#xf026;&nbsp;&nbsp;</ico>'; // The spaces push the icon backwards so that the speaker part lines up with the speaker in the non-mute version.
const ICO_SOUND		= '<ico>&#xf028;</ico>';
const ICO_SWITCH	= '<ico>&#xf0ec;</ico>';

const ICO_SUCCESS	= '<ico>&#xf058;</ico>';
const ICO_WARN		= '<ico>&#xf071;</ico>';
const ICO_ERROR		= '<ico>&#xf057;</ico>';
const ICO_INFO		= '<ico>&#xf05a;</ico>';
const ICO_DEBUG		= '<ico>&#xf188;</ico>';

// Salts
const SALT_EMAIL	= ']-oK?cf%ABb0CJ\N>$WL/)7uk`8Qk!5qq~/zd`}Ka tGb0-CN8XVR\/r-#n1I]"bxX[MB5 -*P)D;eQ<^p433a2rX{uC`SZy$dnX.019Fweyb?99La]^r8 23#+ci/,Q-e\^Sh\a~]ljegn7`;7L.Jp#GAjvKq6\rikD_$mhFa:kw}C|cY_{L?}|/SgGDr9fL>d;I2lU';

// Promotion post days
$level_promotions = [
	2 => 50,
	3 => 200,
	4 => 500
];

// Flood control
$level_floodControl = [
	0 => 10,
	1 => 5,
	2 => 2,
	3 => 1,
	9 => 0,
];

// Level palettes
$level_colours =
[
	0 =>[
		'w',
		'g',
		'x',
		'h',
	],
	1 =>[
		'y',
		'b',
	],
	2 =>[
		'c',
		'm',
		'g_'
	],
	3 => [
		'w_',
		'x_',
	],
	4 => [
		'b_',
		'y_',
	],
	5 => [
		'c_',
		'm_',
	],
	9 => [
		'r',
		'r_',
	]
];

const HR_LEVEL	= 2;
const HR_KEY	= '(hr)';

$level_emoji =
[
	0 => [
		'(con)'	=> '<g>Con&phi;</g><g_>d</g_>',
		'(b1)'	=> '&bull;',
		'(phi)'	=> '&phi;',
		'(dg)'	=> '&dagger;',
		'(ddg)'	=> '&Dagger;',
		'(*)'	=> '<ico>&#xf005;</ico>',
		'(y)'	=> '<ico>&#xf164;</ico>',
		'(n)'	=> '<ico>&#xf165;</ico>',
		'(l)'	=> '<ico>&#xf004;</ico>',
	],
	1 => [
		'(b2)'	=> '&#x2756;',
		'(lt)'	=> '<ico>&#xf0eb;</ico>',
		'(um)'	=> '<ico>&#xf0e9;</ico>',
		'(cf)'	=> '<ico>&#xf0f4;</ico>',
		'(mn)'	=> '<ico>&#xf001;</ico>',
		'(mt)'	=> '<ico>&#xf000;</ico>'
	],
	2 => [
		'(b3)'	=> '&#x261b;',
	],
	3 => [

	],
	4 => [

	],
	5 => [

	],
	6 => [

	],
	7 => [

	],
	8 => [

	],
	9 => [
		'(lvl)' => ICO_STAR		,
		'(usr)' => ICO_USER		,
		'(thd)' => ICO_THREAD	,

		'(mod)' => ICO_MOD		,
		'(lck)' => ICO_LOCK		,
		'(ulk)' => ICO_LOCK_OPEN,
		'(sub)' => ICO_SUB		,
		'(inv)' => ICO_INVITE	,
		'(hid)' => ICO_HIDDEN	,
		'(doc)' => ICO_DOC		,
		'(non)' => ICO_ANON		,
		'(brn)' => ICO_BURNER	,
		'(idx)' => ICO_SEARCH	,

		'(bkm)' => ICO_BOOKMARK	,
		'(fed)' => ICO_FEED		,
		'(pin)' => ICO_PINNED	,
		'(lnk)' => ICO_LINKS	,

		'(rep)' => ICO_REPLIES	,
		'(edt)' => ICO_EDITED	,
		'(key)' => ICO_SEALED	,

		'(cog)' => ICO_COG		,
		'(cgs)' => ICO_COGS		,

		'(scs)' => ICO_SUCCESS	,
		'(wrn)' => ICO_WARN		,
		'(err)' => ICO_ERROR	,
		'(inf)' => ICO_INFO		,
		'(dbg)' => ICO_DEBUG	,

		'(cld)' => ICO_COOLDOWN	,

		'(zin)' => ICO_ZOOM_IN	,
		'(zot)' => ICO_ZOOM_OUT	,
		'(thm)' => ICO_THEME	,
		'(snd)' => ICO_SOUND	,
		'(mut)' => ICO_MUTE		,
		'(swc)' => ICO_SWITCH	,
	],
];

$tips = [
	'Run the ">palette" command to see available text colours and emoji.',
	'All commands have help text that is accessed by appending a questionmark to the command name (e.g., ">commlist?").',
	'Use the ">go" command to enter an empty thread and then bump it to the top of the feed with the ">bump" command.',
	'Access recent submissions by pressing Ctrl + Up and Ctrl + Down.',
	'Pressing Enter will submit the input but Shift + Enter allows you to add a new line.',
	'The Tab key will instert an indentation. Useful for neat formatting of lists or paragraphs.',
];

$level_tips = [
	0 => [
		'The input box is used both for making posts and running commands. To see a list of commands, submit ">commlist".',
		'Registration is optional. You are already logged into an anonymous, temporary account.',
		'To increase your account level, run the Settings app with ">load settings" and follow the instructions in the app pane.'
	],
	1 => [
		'Don\'t forget to save your user hash so you can log into this account again. Run ">iam" to see your hash.',
		'To increae your account level to level 2, load the settings app and register an email address with ">register".'
	],
	2 => [
	],
];

// Other files
require "{$rootPath}system/classes.php";
require "{$rootPath}system/functions.php";
require "{$rootPath}system/appRegistry.php";

