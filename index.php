<?php

$rootPath = '';

require "{$rootPath}system/constants.php";


ini_set('session.gc_maxlifetime', TIME_DAY * 2);
session_set_cookie_params(TIME_DAY * 2);

session_start();

if (!isset($_SESSION[SESS_TOKEN])) $_SESSION[SESS_TOKEN] = getRandomHash();

// Check for reset
if (isset($_GET['r'])) session_destroy();

// Check for pending response
if (isset($_SESSION[SESS_PENDING_RESPONSE]))
{
	$response = $_SESSION[SESS_PENDING_RESPONSE];
	unset($_SESSION[SESS_PENDING_RESPONSE]);
}

////////////////////////////////////////////////////////////////////////////////
//
//	GET USER
//
////////////////////////////////////////////////////////////////////////////////


if (isset($_GET['u']))
{
	$user = new User(getHash($_GET['u']));
	$_SESSION[SESS_USER] = $user;
	unset($_COOKIE[COOK_USER]);
}
elseif (isset($_SESSION[SESS_USER]) && !isset($_GET['r']))
{
	$user = $_SESSION[SESS_USER];

	if (isset($user->app))
	{
		require "{$rootPath}apps/{$user->app}.php";
	}
}
elseif (isset($_COOKIE[COOK_USER]))
{

	$user = new User($_COOKIE[COOK_USER]);
	$_SESSION[SESS_USER] = $user;
}
else
{
	$user = new User();
	$_SESSION[SESS_USER] = $user;
}

$user->notifications = [];

////////////////////////////////////////////////////////////////////////////////
//
//	GET POST ID
//
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['p']))
{
	$result = query('SELECT thread_hash FROM posts WHERE id = :id', [':id' => $_GET['p']]);
	if ($result)
	{
		$thread = new Thread($result[0]['thread_hash']);
	}
}

////////////////////////////////////////////////////////////////////////////////
//
//	GET THREAD HASH
//
////////////////////////////////////////////////////////////////////////////////

if (!isset($thread))
{
	if (isset($_GET['t']))
	{
		$thread = new Thread($_GET['t']);
		$_SESSION[SESS_THREAD] = $_GET['t'];
	}
	elseif (isset($_SESSION[SESS_THREAD]))
	{
		$thread = new Thread($_SESSION[SESS_THREAD]);
	}
	else
	{
		$thread = new Thread(DEFAULT_THREAD);
	}
}

//$_SESSION[SESS_THREAD] = $thread;

$thread->latestFetchedPost = 0;

if (isset($_GET['u']))
{
	$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$url = strtok($url, '?');

	$gets = '';

	foreach ($_GET as $key => $val)
	{
		// Scrub the u GET parameter out of the address bar
		// so that the hash is only visible for a split second
		// when navigating to a login link.
		if ($key === 'u') continue;

		$gets .= $gets ? '&' : '?';

		$gets .= "{$key}={$val}";
	}

	$url .= $gets;

	header("location:{$url}");
}


$appName = $user->app;

$thread->getPosts();	// Oosenupt - keep an eye on this.

$user->getNotifications();
$user->getBookmarks();


$response->set(UI_USER_NAME, $user->name);
$response->set(UI_THREAD_NAME, $thread->name);

getFeed();

notifyDebug('Debug reporting ON.');
notifyDebug($thread->latestFetchedPost);

ob_start();

?>
<!DOCTYPE html>
<html>
    <head>
		<link rel="icon" type="image/png" href="favicon.png"/>
        <meta charset="UTF-8"/>
        <title id="title">Con&phiv;d</title>
		<link rel="stylesheet" type="text/css" href="style/css.php"/>
		<link id="style" rel="stylesheet" type="text/css" href="style/style_default.css"/>
    </head>
    <body style="background-color:#000;">
		<div id="header">
			<!--<div id="header_lhs">-->
				<g><?= CONPHID; ?> <x>v<?= VERSION; ?></x></g>
				<span id="working"></span>
				<span id="settings" class="x">
					<span title="Zoom in" onclick="zoom(0.1)"><?=ICO_ZOOM_IN?></span>
					<span title="Zoom out" onclick="zoom(-0.1)"><?=ICO_ZOOM_OUT?></span>
					<span title="Switch theme" onclick="changeStyle()"><?=ICO_THEME?></span>
					<span title="Switch thread and system panes" id="switch" onclick="switchPanes()"><?=ICO_SWITCH?></span>
					<span title="Mute" id="soundIcon" onclick="toggleSound()"><?=ICO_SOUND?></span>
				</span>
			<!--</div>-->
			<div id="header_rhs"><div class="x_">&nbsp;<?=ICO_THREAD?>Thread: <br>&nbsp;</div>
				<div>
					<div>
						<span id="<?= UI_THREAD_HASH ?>"><?= $thread->hash; ?></span>
					</div>
					<div>
						<span id="<?= UI_THREAD_LOCK ?>"><?= $thread->getLockIcon(); ?></span>
						<span id="<?= UI_THREAD_SUB ?>"><?= $thread->getSubIcon(); ?></span>
					</div>
				</div>

				<div class="x_">&nbsp;<?=ICO_USER?>User: <br>&nbsp;</div>
				<div style="padding-right:<?=CHAR_WIDTH?>px">
					<div>
						<span id="<?= UI_USER_NAME ?>"></span>
						<x> @ <?= $_SERVER['REMOTE_ADDR']; ?></x>
					</div>
					<div>
						<y><?= ICO_STAR ?><span id="<?= UI_USER_LEVEL ?>"><?= $user->level; ?></span></y>
					</div>
				</div>
			</div>
		</div>
		<div id="body" onload="onLoad()">
			<div id="mainContainer">
				<div id="main_outerDock">
					<div id="main_innerDock">
						<div id="<?= UI_THREAD_NAME ?>"></div>
						<div id="<?= PANE_MAIN ?>"></div>
					</div>
				</div>
				<div id="inputContainer">
					<span class="x" id="<?= UI_TIPS ?>">Input posts or commands here.<br>TIP: <?= getTip(); ?></span>
					<textarea id="<?= UI_INPUT ?>"></textarea>
					<div onclick="resizeInput()" id='expand'><ico>&#xf102;</ico></div>
				</div>
				<button id='submit'>
					<span style="font-weight:bold;">Submit</span>
					<div title="Character limit" id="charCount"><?=$maxSubmissionSize[$user->level];?></div>
				</button>
			</div>
			<div id="auxContainer">
				<div id="userContainer">
					<div id="listsContainer">
						<div id="<?= PANE_BOOKMARKS ?>"></div>
						<div id="<?= PANE_LINKS ?>"></div>
						<div id="<?= PANE_FEED ?>"></div>
						<div id="listTabs"><!--
							--><div id="feedTab" onclick="showList('<?=PANE_FEED?>')" class="b"><?=ICO_FEED?></div><!--
							--><div id="linksTab" onclick="showList('<?=PANE_LINKS?>')" class="x_"><?=ICO_LINKS?></div><!--
							--><div id="bookmarksTab" onclick="showList('<?=PANE_BOOKMARKS?>')" class="x_"><?=ICO_BOOKMARK?></div><!--
						--></div>
					</div>
					<div id="notificationsContainer">
					<div id="<?= PANE_NOTIFICATIONS ?>"></div>
					</div>
				</div>
				<div id="systemContainer">
					<div id="system_outerDock">
						<div id="system_innerDock">
							<div id="app"><?= $appName ? $appName::getName() : NO_APP; ?></div>
							<div id="<?= PANE_SYSTEM ?>"><?php

								if (isset($user->app))
								{
									$app = $user->app;
									echo $app::getSplash() . "\n\n";
								}

							?></div>
						</div>
					</div>
				</div>
			</div>
		</div>
    </body>
	<script>
		var main			= document.getElementById('main');
		var system			= document.getElementById('system');

		var input			= document.getElementById('input');
		var submitButton	= document.getElementById('submit');

		var nf = 1;	<?php // Next fetch		?>
		var ep;		<?php // Earliest post	?>
		var lp;		<?php // Latest post	?>
		var sh;		<?php // State hash		?>
		var th = '<?=$thread->hash?>';		<?php // Thread hash	?>
		var k = '<?= $_SESSION[SESS_TOKEN] ?>';					<?php // Token	?>
		var doc = <?= $thread->doc ? 'true' : 'false'; ?>;
		var docDiv;
		var cl = <?= $maxSubmissionSize[$user->level]; ?>;

		submitButton.onclick = function ()
		{
			inputHistory.unshift(input.value);
			inputHistoryPos = 0;
			if (inputHistory.length > 100) inputHistory.pop();

			submit(input.value);
		};

		input.onkeyup = checkInput;

		var inputHistory = [];
		var inputHistoryPos = 0;

		var hotkeys = {
			<?php
			if ($user->settings[SETT_HOTKEYS])
			{
				$hks = [];
				foreach ($user->settings[SETT_HOTKEYS] as $key => $value)
				{
					$hks[] = "'{$key}':'{$value}'";
				}
				echo implode(',', $hks);
			}
			?>
		};

		var firstPost = <?= $thread->firstPost ? $thread->firstPost : 'null' ?>;

		function submit(submission, onComplete)
		{
			if (submission)
			{
				var request = new XMLHttpRequest();

				request.open("POST","requests/submit.php","true");
				request.setRequestHeader("Content-type","application/x-www-form-urlencoded");
				request.send('k='+k+'&th='+th+'&lp='+lp+'&sh='+sh+'&sub='+encodeURIComponent(submission));
//				request.send('th='+th+'&lp='+lp+'&sh='+sh+'&sub='+encodeURIComponent(submission));

				request.onComplete = onComplete;

				request.onreadystatechange = function ()
				{
					if (this.readyState === 4 && this.status === 200 && this.responseText !== '')
					{
						showResult(this.responseText);
						if (this.onComplete) eval(this.onComplete);
					}
				};

				startWaiting();
			}

			input.focus();
		}

		function startWaiting ()
		{
			input.disabled = true;
			startSpinner();

		}

		function stopWaiting ()
		{
			input.disabled = false;
			stopSpinner();
		}

		function showResult(response)
		{
			if (response === '') return;

			try
			{
				response = JSON.parse(response);
			}
			catch (e)
			{
				var appendDiv = document.createElement('div');

				appendDiv.innerHTML = "<r_>RESPONSE ERROR:</r_> <r>" + response + "</r>";
				document.getElementById("<?= PANE_SYSTEM ?>").appendChild(appendDiv);
			}

			if (!response) return;

			for (var index in response.clear)
			{
				var element = document.getElementById(response.clear[index]);

				if (element)
				{
					if (element.innerHTML !== undefined) element.innerHTML = '';
					if (element.value !== undefined) element.value = '';

					if (element === input) checkInput();
				}
			}

			for (var index in response.set)
			{
				var element = document.getElementById(response.set[index].id);

				if (element)
				{
					if (element.innerHTML !== undefined) element.innerHTML = response.set[index].body;
					if (element.value !== undefined) element.value = response.set[index].body;

					if (element === input) checkInput();
				}
			}

			for (var index in response.append)
			{
				var id = response.append[index].id;
				var element = document.getElementById(id);

				if (element)
				{
					var appendDiv = document.createElement('div');

					if (response.append[index].postId)
					{
						var pid = parseInt(response.append[index].postId);

						if (document.getElementById('p_' + pid) !== null) continue;

						appendDiv.id = 'p_' + pid;
						appendDiv.name = 'p_' + pid;

						if (!ep || pid < ep) ep = pid;
						if (!lp || pid > lp) lp = pid;

						if (isBlurred)
						{
							blurPostCount++;
							playSound('newPost');
						}
					}

					appendDiv.innerHTML = response.append[index].body;

					if (doc && element.id === '<?=PANE_MAIN?>')
					{
						if (!docDiv)
						{
							docDiv = appendDiv;

							ep = null;

							var hr  = document.createElement('hr');
							hr.className = 'doc';
							docDiv.appendChild(hr);
						}

						if (docDiv.nextSibling)
						{
							element.insertBefore(appendDiv, docDiv.nextSibling);
						}
						else
						{
							element.appendChild(appendDiv);
						}
					}
					else
					{
						element.appendChild(appendDiv);
					}

					switch (id)
					{
						case '<?=PANE_MAIN?>':
							if (doc) break;
						case '<?=PANE_SYSTEM?>':
							if (isInitial)
							{
								var elm = document.getElementById(id);
								elm.scrollTop = elm.scrollHeight + 500;
							}
							else autoScrollById(id);
							break;
						case '<?=PANE_NOTIFICATIONS?>':
							if (!isInitial) playSound('notif');
							break;
					}
				}
			}

			for (var index in response.prepend)
			{
				var element = document.getElementById(response.prepend[index].id);

				var beforeScrollHeight = element.scrollHeight;

				if (element)
				{
					var prependDiv = document.createElement('div');

					if (response.prepend[index].postId)
					{
						var pid = parseInt(response.prepend[index].postId);

						if (document.getElementById('p_' + pid) !== null) continue;

						prependDiv.id = 'p_' + response.prepend[index].postId;
						prependDiv.name = 'p_' + response.prepend[index].postId;

						if (!ep || pid < ep) ep = pid;
						if (!lp || pid > lp) lp = pid;
					}

					prependDiv.innerHTML = response.prepend[index].body;

					if (doc && element.id === '<?=PANE_MAIN?>')
					{
						element.appendChild(prependDiv);
					}
					else
					{
						if (element.childNodes.length > 0)
						{
							element.insertBefore(prependDiv, element.childNodes[0]);
						}
						else
						{
							element.prependChild(prependDiv);
						}

						element.scrollTop += element.scrollHeight - beforeScrollHeight;
					}
				}
			}

			for (var index in response.vars)
			{
				var name	= response.vars[index].name;
				var val		= response.vars[index].val;

				setValue(name, val);

				switch (name)
				{
					case 'th':
						// If you're here in the future confused by why this isn't
						// working, this is, for some reason, dependant on the order
						// in which val vars are set on the server. This seems to always
						// capure the last one (like it references the variable with some
						// kind of lag after it has already been realloved).
						// Fixing this by changing the order of operations felt flimsy
						// and wrong but so be it.

						window.history.pushState('', '', '?t=' + val);
						break;
					case 'doc':
						if (!val)
						{
							docDiv = undefined;
						}
						break;
				}
			}

			for (var index in response.vars)
			{
				var name	= response.vars[index].name;
				var scroll	= response.vars[index].scroll;

				var elm = document.getElementById(name);

				if (elm)
				{
					switch (scroll)
					{
						case <?=SCROLL_TOP?>:
								elm.scrollTop = 0;
							break;
						case <?=SCROLL_BOTTOM?>:
								elm.scrollTop = elm.scrollHeight;
							break;
					}
				}

				switch (name)
				{
					case 'th':
						window.history.pushState('', '', '?t=' + val);
						break;
					case 'cl':
						checkInput();
						break;
				}
			}

			titleNotifications();
			stopWaiting();
		}

		function titleNotifications ()
		{
			var nots = document.getElementById('<?=PANE_NOTIFICATIONS?>').children.length;
			var title = document.getElementById('title');

			var titleText = 'Con&phiv;d';

			if (blurPostCount) titleText = '[' + blurPostCount + '] ' + titleText;

			title.innerHTML = titleText;
		}

		function fetch()
		{
			var submission = new XMLHttpRequest();

			submission.open("POST","requests/fetch.php","true");
			submission.setRequestHeader("Content-type","application/x-www-form-urlencoded");
			submission.send('k='+k+'&th='+th+'&lp='+lp+'&sh='+sh);
//			submission.send('th='+th+'&lp='+lp+'&sh='+sh);

//				submission.onreadystatechange = showResult;
			submission.onreadystatechange = function ()
			{
				if (this.readyState === 4 && this.status === 200)
				{
					switch (this.responseText)
					{
						case 'WAIT':
							return;
							break;
						default:
							showResult(this.responseText);
							break;
					}

					setTimeout(fetch, parseInt(nf * 1000));
				}
			};
		}

		function checkInput()
		{
			if (input.value.charAt(0) === '>')
			{
				input.className = 'comm';
			}
			else
			{
				input.className = '';
			}

			document.getElementById("<?= UI_TIPS ?>").style.display = (input.value.length > 0 ? 'none' : 'inline-block');

			var charsLeft = cl - input.value.length;

			document.getElementById('charCount').innerHTML = charsLeft;

			if (charsLeft < 0)
			{
				input.className = 'r';
			}
		}

		window.onkeydown = function (e)
		{
			switch (e.keyCode)
			{
				case 16:
				case 17:
					return;

				case 13: // ENTER
					if (!e.shiftKey)
					{
						submitButton.onclick();
						return false;
					}
					break;
				case 38: // UP
					if (e.ctrlKey)
					{
						if (inputHistory[inputHistoryPos] !== undefined)
						{
							input.value = inputHistory[inputHistoryPos];
							inputHistoryPos++;
						}
					}
					break;
				case 40: // DOWN
					if (e.ctrlKey)
					{
						if (inputHistory[inputHistoryPos - 2] !== undefined)
						{
							inputHistoryPos--;
							input.value = inputHistory[inputHistoryPos - 1];
						}
					}
					break;

				case 48: if(e.ctrlKey) runHotkey('0'); break;
				case 49: if(e.ctrlKey) runHotkey('1'); break;
				case 50: if(e.ctrlKey) runHotkey('2'); break;
				case 51: if(e.ctrlKey) runHotkey('3'); break;
				case 52: if(e.ctrlKey) runHotkey('4'); break;
				case 53: if(e.ctrlKey) runHotkey('5'); break;
				case 54: if(e.ctrlKey) runHotkey('6'); break;
				case 55: if(e.ctrlKey) runHotkey('7'); break;
				case 56: if(e.ctrlKey) runHotkey('8'); break;
				case 57: if(e.ctrlKey) runHotkey('9'); break;

				default: if (e.ctrlKey) return;
			}

			input.focus();
		};

		function runHotkey (key)
		{
			if (hotkeys[key] !== undefined)
			{
				input.value = hotkeys[key];
			}
		}

		function setValue (varName, value)
		{
			var variable = window;

			varName = varName.split('.');
			var last = varName.pop();

			for (var pos in varName)
			{
				if (variable[varName[pos]] !== undefined)
				{
					variable = variable[varName[pos]];
				}
				else
				{
					var varName = varName.join('.');

					var appendDiv = document.createElement('div');
					appendDiv.innerHTML = '<r>Attempting to set variable "<w>' + varName + '"</w>.</r>';
					aux.appendChild(appendDiv);

					aux.scrollTop = aux.scrollHeight;
					return;
				}
			}

			variable[last] = value;
		}

		var spinnerTimer;

		function startSpinner ()
		{
			var spinner = document.getElementById('working');

			spinner.frames = [
				' ',
				'&#x2591;',
				'&#x2592;',
				'&#x2593;',
				'&#x2588;',
				'&#x2587;',
				'&#x2586;',
				'&#x2585;',
				'&#x2584;',
				'&#x2583;',
				'&#x2582;',
				'&#x2581;',
				'_'
			];

			spinner.frame = 1;
			spinner.innerHTML = spinner.frames[0];

			spinnerTimer = setInterval(spinSpinner, 100);

			spinner.spinner = spinner;
		}

		function spinSpinner ()
		{
			var spinner = document.getElementById('working');

			if (spinner.frames[spinner.frame] === undefined) spinner.frame = 0;

			spinner.innerHTML = spinner.frames[spinner.frame];
			spinner.frame++;
		}

		function stopSpinner()
		{

			clearInterval(spinnerTimer);

			var spinner = document.getElementById('working');
			spinner.innerHTML = '';

		}

		function addRe(pid)
		{
			var prefix = '>re ' + pid + ' ';

			if (input.value.charAt(0) !== '>')
			{
				input.value = prefix + input.value;
				input.onkeyup();
				input.focus();
			}
			else
			{
				input.value = input.value.replace(/^>re (\d)+ /g, prefix);
			}
		}

		function highlight (id, tag)
		{
			tag = tag.toUpperCase();

			var elms = document.getElementsByClassName(tag);

			if (elms.length > 0)
			{
				for (var i = elms.length; i > 0; i--)
				{
					elms[i-1].className = elms[i-1].className.replace(tag, '').trim();
				}
			}

			var target = document.getElementById(id);

			if (target) target.className = target.className + ' ' + tag;
		}

		function fetchIfMissing (id)
		{
			var postDiv = document.getElementById('p_' + id);

			if (!postDiv)
			{
				submit('>fetcholdposts ' + ep + ' ' + id, 'jumpTo("p_' + id + '")');
			}
		}

		main.onscroll = function (event)
		{
			var fetch = false;

			if (doc)
			{
				if (this.scrollTop + this.offsetHeight === this.scrollHeight) fetch = true;
			}
			else
			{
				if (this.scrollTop === 0) fetch = true;
			}

			if (fetch && (ep > parseInt(firstPost)))
			{
				submit('>fetcholdposts ' + ep);
			}
		};

		function jumpTo (id)
		{
			var element = document.getElementById(id);

			if (element)
			{
				element.parentNode.scrollTop = element.offsetTop;
			}
		}

		function resizeInput()
		{
			var inputContainer = document.getElementById('inputContainer');
			var submit = document.getElementById('submit');
//			var main = document.getElementById('<?=PANE_MAIN?>');
			var main = document.getElementById('main_outerDock');
			var expand = document.getElementById('expand');

			if (main.className === 'resized')
			{
				main.className				= '';
				submit.className			= '';
				inputContainer.className	= '';

				expand.innerHTML = '<ico>&#xf102;</ico>';
			}
			else
			{
				main.className				= 'resized';
				submit.className			= 'resized';
				inputContainer.className	= 'resized';

				expand.innerHTML = '<ico>&#xf103;</ico>';
			}
		}

		function changeStyle ()
		{
			var style = document.getElementById('style');

			if (style.href.indexOf('default.css') !== -1)
			{
				style.href = style.href.replace('default.css', 'reading.css');
				document.cookie = 'style=read';
			}
			else
			{
				style.href = style.href.replace('reading.css', 'default.css');
				document.cookie = 'style=def';
			}
		}

		if (getCookieValue('style') === 'read')
		{
			changeStyle();
		}

		function showList(id)
		{
			if (document.getElementById(id) === undefined) return;

			document.getElementById('<?=PANE_FEED?>').style.display = 'none';
			document.getElementById('<?=PANE_LINKS?>').style.display = 'none';
			document.getElementById('<?=PANE_BOOKMARKS?>').style.display = 'none';

			document.getElementById('feedTab').className = 'x_';
			document.getElementById('linksTab').className = 'x_';
			document.getElementById('bookmarksTab').className = 'x_';


			document.getElementById(id).style.display = 'block';

			switch(id)
			{
				case '<?=PANE_FEED?>':
					document.getElementById('feedTab').className = 'b';
					document.cookie = 'tab=<?=PANE_FEED?>';
					break;

				case '<?=PANE_LINKS?>':
					document.getElementById('linksTab').className = 'w';
					document.cookie = 'tab=<?=PANE_LINKS?>';
					break;

				case '<?=PANE_BOOKMARKS?>':
					document.getElementById('bookmarksTab').className = 'r';
					document.cookie = 'tab=<?=PANE_BOOKMARKS?>';
					break;
			}
		}

		if (document.cookie)
		{
			var tab = getCookieValue('tab') || '<?=PANE_FEED?>';
			showList(tab);
		}

		input.onkeydown = function(e)
		{
			if (e.keyCode === 9)
			{
				var val = this.value,
					start = this.selectionStart,
					end = this.selectionEnd;

				this.value = val.substring(0, start) + '\t' + val.substring(end);
				this.selectionStart = this.selectionEnd = start + 1;
				return false;
			}
		};

		function getCookieValue(name)
		{
			var name = name + '=';
			var ca = document.cookie.split(';');
			for(var i=0; i<ca.length; i++) {
				var c = ca[i];
				while (c.charAt(0)===' ') c = c.substring(1);
				if (c.indexOf(name)=== 0) return c.substring(name.length,c.length);
			}
			return null;
		}

		function setInput(string)
		{
			input.value = string;
			checkInput();
		}

		var isBlurred = false;
		var blurPostCount = 0;

		window.onblur = function ()
		{
			isBlurred = true;
		};

		window.onfocus = function ()
		{
			isBlurred = false;
			blurPostCount = 0;
			titleNotifications();
		};

		var zoomLevel = 1;

		function zoom (amount)
		{
			zoomLevel += amount;
			if (zoomLevel > 1.5) zoomLevel = 1.5;
			if (zoomLevel < 1) zoomLevel = 1;

			document.body.style.zoom = zoomLevel;
		}

		////////////////////////////////////////////////////////////////////////
		//
		//	AUTO SCROLL STUFF
		//
		////////////////////////////////////////////////////////////////////////

		var as_register = {};
		var as_timer = null;

		function autoScrollById (id)
		{
			if (as_register[id] !== undefined) return;

			var elm = document.getElementById(id);
			if (!elm) return;

//			if (elm.scrollTop < elm.scrollHeight - elm.offsetHeight - 10) return;

			elm.onscroll = autoScroller__onscroll;
			as_register[id] = 1;

			if (as_timer === null)
			{
				as_timer = setTimeout(autoScroll, 20);
			}
		}

		function autoScroll ()
		{
			for (var id in as_register)
			{
				var elm = document.getElementById(id);
				elm.isAuto = true;
				elm.scrollTop = elm.scrollTop + as_register[id];


//				elm.onscroll = function () { if (as_register[id] !== undefined) delete as_register[id]; };

				if (elm.scrollHeight <= elm.scrollTop + elm.offsetHeight)
				{
					delete as_register[id];
				}
				else
				{
					as_register[id] += 1 + (as_register[id] * 0.1);
//					as_register[id] += <?=CHAR_HEIGHT?>;
				}
			}

			if (Object.keys(as_register).length > 0)
			{
				as_timer = setTimeout(autoScroll, 20);
			}
			else
			{
				as_timer = null;
			}
		}

		function autoScroller__onscroll ()
		{
			if (this.isAuto)
			{
				this.isAuto = false;
				return;
			}

			delete as_register[this.id];
		}

		////////////////////////////////////////////////////////////////////////
		//
		//	SOUND
		//
		////////////////////////////////////////////////////////////////////////

		var mute = false;

		function toggleSound ()
		{
			mute = !mute;
			document.cookie = 'mute=' + (mute ? 'true' : 'false');

			var icon = document.getElementById('soundIcon');

			icon.innerHTML = mute ? '<?=ICO_MUTE?>' : '<?=ICO_SOUND?>';
		}

		if (getCookieValue('mute') === 'true')
		{
			toggleSound();
		}

		function playSound (sound)
		{
			if (mute) return;

			var path = 'sounds/' + sound + '.wav';

			var sound = new Audio();
			sound.src = path;
			sound.play();
		}

		function switchPanes ()
		{
			var main_innerDock = document.getElementById('main_innerDock');
			var system_innerDock = document.getElementById('system_innerDock');

			var main_outerDock = document.getElementById('main_outerDock');
			var system_outerDock = document.getElementById('system_outerDock');

			var main = document.getElementById('<?=PANE_MAIN?>');
			var system = document.getElementById('<?=PANE_SYSTEM?>');

			if (main_innerDock.parentNode.id === 'main_outerDock')
			{
				system_outerDock.appendChild(main_innerDock);
				main_outerDock.appendChild(system_innerDock);
			}
			else
			{
				main_outerDock.appendChild(main_innerDock);
				system_outerDock.appendChild(system_innerDock);
			}

			main.scrollTop = main.scrollHeight;
			system.scrollTop = system.scrollHeight;
		}

	</script>

	<script>
		isInitial = true;
		showResult('<?= addslashes("{$response}"); ?>');
		<?php if (isset($_GET['p'])) { ?>
		linkedId = 'p_<?=$_GET['p'];?>';
		jumpTo(linkedId);
		linkedDiv = document.getElementById(linkedId);
		if (linkedDiv)
		{
			linkedDiv.className = linkedDiv.className + ' linkedPost';
		}
		<?php } ?>
		isInitial = false;
		fetch();
	</script>
</html>