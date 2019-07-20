<?php

/*
class comm_help extends Command
{
	static function run($input)
	{
		global $response;
		global $user;

		$app = $user->app;

		ob_start();
			?>
<y>General <?= CONPHID; ?> help</y>:
<g>&bull;</g> Type and press <w_> Enter </w_> to make a submission. Pressing <w_> Shift </w_> + <w_> Enter </w_> will yeild a new line.
<g>&bull;</g> To execue a command, begin your submission with a "<b>></b>". The word following the command chevron should be the name of the command (e.g., "<b>>help</b>" to execute the "help" command).
<g>&bull;</g> Press <w_> Ctrl </w_> + <w_> &#x2191; </w_> or <w_> Ctrl </w_> + <w_> &#x2193; </w_> to access previous input.
<g>&bull;</g> To see a list of all available commands, input "<b>>commlist</b>".
<g>&bull;</g> For more information on a specific command, input the command with an attached questionmark (e.g., "<b>>commlist?</b>" for help on the commlist command).
<g>&bull;</g> Apps allow the user to change the context of the <?= CONPHID ?> console offering different commands and functionality.
<g>&bull;</g> To see a list of all available apps, input "<b>>applist</b>".
<?php
			$help = ob_get_clean();

		if ($app)
		{
			$appName = $app::getName();
			$help .= "\n<y>$appName help</y>:\n";

			$help .= $app::getHelp();
		}

		$response->append(PANE_SYSTEM, $help);
	}

	static function getHelp()
	{
		return 'Displays the introductory help text.';
	}

	static function getExample()
	{
		return "<b>>help</b>\nThis will display the help text.";
	}
}

*/

class comm_tips extends Command
{
	public  static function run ($input)
	{
		global $response;

		$tips = getTip(true);

		$output = '';

		foreach ($tips as $tip)
		{
			$output .= "<r>&bull;</r> {$tip}\n";
		}

		$response->append(PANE_SYSTEM, $output);
	}

	public  static function getHelp ()
	{
		return 'Displays all level-relevant tips for the current account (all the tips that might appear in the input box).';
	}

	public  static function getExample ()
	{
		return "<b>>tips</b>\nThis will display a list of level-relevant tips in the app pane.";
	}
}

class comm_commlist extends Command
{
	static function run($input)
	{
		global $response;
		global $user;

		$classes = get_declared_classes();

		$responseText = "<y_> Global commands: </y_>\n";

		$globalComms	= [];
		$appComms		= [];

		$listingApps = false;

		foreach ($classes as $className)
		{
			if (substr($className, 0, 5) === 'comm_')
			{
				$commandName = str_replace('comm_', '>', $className);

				if ($className::$hidden) continue;

				$allowed = ($className::$level <= $user->level);
//				if ($className::$hidden
//					|| $className::$level > $user->level
//					)
//					continue;

				$help = $allowed ? $className::getHelp() : '<x>Account level requirements not met.</x>';
				$props = [];

				if ($className::$level > 0)		$props[] = '<y title="Acount level of ' . $className::$level . ' or higher required">' . ICO_STAR . $className::$level . '</y>';
				if ($className::$threadBased)	$props[] = '<w title="Thread access required">' . ICO_LOCK_OPEN . '</w>';
				if ($className::$modComm)		$props[] = '<w title="Moderator command">' . ICO_MOD . '</w>';

				if ($allowed && $className::$cd_period && $className::$cd_period)
				{
					if (is_array($className::$cd_runs))
					{
						if (isset($className::$cd_runs[$user->level])) $runs = $className::$cd_runs[$user->level];
					}
					else $runs = $className::$cd_runs;

					if (is_array($className::$cd_period))
					{
						if (isset($className::$cd_period[$user->level])) $period = $className::$cd_period[$user->level];
					}
					else $period = $className::$cd_period;

					if (isset($runs, $period))
					{
						$period = getReadableTime($period);
						$s = $runs > 1 ? 's' : '';
						$props[] = "<c title=\"Allowed to be run {$runs} time{$s} every {$period}\">" . ICO_COOLDOWN . " {$runs}/{$period}</c>";
					}
				}

//				$props = implode("\t", $props);
				$props = implode(' <x>|</x> ', $props);

				if ($props) $props = "{$props}\n";

				if ($listingApps)
				{
//					$appComms[$commandName] = $help;
					$appComms[] = "<b>{$commandName}</b>\n{$props}{$help}";
				}
				else
				{
//					$globalComms[$commandName] = $help;
					$globalComms[] = "<b>{$commandName}</b>\n{$props}{$help}";
				}
			}
//			elseif ($user->app && $className === $user->app)
			elseif (substr($className, 0, 4) === 'app_')
			{
				$listingApps = true;
			}
		}

		foreach ($globalComms as $comm)
		{
			$responseText .= "{$comm}\n\n";
		}

		if ($appComms)
		{
			$responseText .= "<y_> App commands: </y_>\n";

			foreach ($appComms as $comm)
			{
				$responseText .= "{$comm}\n\n";
			}
		}

		$responseText .= "\nFor more information on any command input the command with a questionmark appended (e.g., \"<b>>commlist?</b>\").";

		$response->append(PANE_SYSTEM, $responseText);
	}

	static function getHelp()
	{
		return 'Provides a list of common commands with their blurbs.';
	}

	static function getExample()
	{
		return "<b>>commlist</b>\nThis will display a list of currently available commands and their blurb.";
	}
}

class comm_iam extends Command
{
	static function run ($input)
	{
		global $user;
		global $thread;
		global $response;

		if (!$input)
		{
			$signInLink = CONPHID_URL . "?u={$user->hash}";
			$response->append(PANE_SYSTEM, "Your hash is <h>{$user->hash}</h>.<br>To sign in automatically, drag the following link into your browser's favourites bar or menu: <a class=\"link\" name=\"Conphid\" href=\"{$signInLink}\">Con&phi;d</a>");
			return;
		}

		$hash = getHash($input);

		if ($user->hash === $hash)
		{
			notifyWarning("Already logged in as <w>{$hash}</w>.");
			return; // You're already logged in as the person you're trying to log in as.
		}

		$user	= new User($hash);
		$thread	= new Thread($thread->hash);

		$response->set(UI_USER_NAME, $user->name);
		$response->set(UI_USER_LEVEL, $user->level);
		$response->set(UI_APP, NO_APP);

		$response->clear(PANE_SYSTEM);

		sleep(3);
	}

	static function getHelp ()
	{
		return 'Logs in as the provided hash. If a valid hash is not provided, the input provided will be hashed via md5 and the resulting hash will be used to log in.';
	}

	static function getExample ()
	{
		return "<b>>iam</b> <c>user</c>\n<b>>iam</b> <c>ee11cbb19052e40b07aac0ca060c23ee</c>\nThese two commands will do the exact same thing (since \"ee11cbb19052e40b07aac0ca060c23ee\" is the mdh5 hash of \"user\"). Note: <b>>iam</b> does NOT log in using the username and if your username's hash is your login hash, you should change it immediately.";
	}
}

class comm_applist extends Command
{
	static function run($input)
	{
		global $user;
		global $response;
		global $rootPath;
		global $appRegistry;

		$files = scandir("{$rootPath}apps/");

		$responseText = "<y>Available apps:</y>\n";

//		foreach ($files as $file)
//		{
//			if ($file[0] !== 'a') continue;
//			$handle = str_replace(['app_', '.php'], '', $file);
//			$responseText .= "<g>{$handle}</g>\n";
//		}

		foreach ($appRegistry as $app)
		{
			if (!in_array("app_{$app->file}.php", $files))
			{
				notifyDebug("Missing app \"<w>{$app->file}</w>\".");
				continue;
			}

			if ($app->level > $user->level)
			{
				$responseText .= "<x_> {$app->name} </x_>\t<y>" . ICO_LOCK . ' ' . ICO_STAR . $app->level . "</y>\n";
				continue;
			}

			$name = "<g_> {$app->name} </g_>";

			if ($app->level)
			{
				$name .= "\t<y>" . ICO_STAR . $app->level . '</y>';
			}

			$responseText .= "<div onclick=\"setInput('>load {$app->file}');\">{$name}\n\tAccess with: <b>>load {$app->file}</b>\n\t{$app->description}</div>\n";
		}

		$responseText .= 'To run an app, input the <b>>load</b> command followed by the app handle (as listed above).';

		$response->append(PANE_SYSTEM, $responseText);
	}

	static function getHelp()
	{
		return 'Provides a list of available ' . CONPHID . ' app handles.';
	}

	static function getExample()
	{
		return "<b>>applist</b>\nThis will display a list of the currently available apps.";
	}
}

class comm_find extends Command
{
	static $limit = 200;

	public  static function run($input)
	{
		global $response;

		$sql =
		'SELECT p.username,
			p.nametag,
			p.body,
			p.timestamp,
			p.id,
			t.name,
			t.hash
		FROM posts p
		LEFT JOIN threads t
		ON p.thread_hash = t.hash
		WHERE t.indexed = 1
		AND t.hidden = 0
		AND p.body LIKE concat("%",:term,"%")
		LIMIT :limit';


		$db = getDB();

		$stmt = $db->prepare($sql);

		$stmt->bindValue(':term', $input, PDO::PARAM_STR);
		$stmt->bindValue(':limit', (int) self::$limit, PDO::PARAM_INT);

		$stmt->execute();

		$results = $stmt->fetchAll();

		$count = count($results);

		$resultString = "<b_> Search for \"{$input}\": </b_>\n\n";

		foreach ($results as $r)
		{
			$body = str_replace("\n", '', $r['body']);
			$body = cleanReply($body);
			$body = str_replace($input, "<g_>{$input}</g_>", $body);


			$result = "<a href=\"?p={$r['id']}\" title=\"{$r['name']}\">";
			$result .= "<{$r['nametag']}> {$r['username']} </{$r['nametag']}> ";
			$result .= "{$body}</a>\n\n";

			$resultString .= $result;
		}

		$resultString .= "<g_> END OF RESULTS. {$count} results found";
		if ($count === self::$limit) $resultString .= ' (result limit reached)';
		$resultString .= '. </g_>';

		$response->append(PANE_SYSTEM, $resultString);
	}

	public  static function getHelp()
	{
		return 'Searches indexed threads for the keyword or phrase entered.';
	}

	public  static function getExample()
	{
		return "<b>>search</b> <c>Conphid</c>\nThis will search all indexed threads for posts or thread titles containing \"Conphid\". Search is not case sensitive.";
	}
}

class comm_load extends Command
{
	static function run($input)
	{
		global $response;
		global $user;
		global $rootPath;
		global $appRegistry;

		$appName = "app_{$input}";

		if (isset($user->app))
		{
			if ($user->app === $appName)
			{
				notifyWarning("App \"<w>{$input}</w>\" is already loaded.");

			}
			else
			{
				$currentApp = $user->app;
				$currentApp = $currentApp::getName();

				notifyError("Please unload \"<w>{$currentApp}</w>\" with command \"<b>>unload</b>\" before loading another app.");
			}
			return;
		}

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
			require "{$rootPath}apps/{$appName}.php";

			if (class_exists($appName))
			{

				$user->app = $appName;
				$appName::onLoad();
				$response->set(UI_APP, $appName::getName());
				$response->set(PANE_SYSTEM, $appName::getSplash() . "\n\n");

//				$response->clear(PANE_SYSTEM);

				notifySuccess("App \"<w>{$input}</w>\" loaded!");
			}
			else
			{
				notifyWarning("App \"<w>{$input}</w>\" found but is invalid.");
			}
		}
		else
		{
			notifyError("App \"<w>{$input}</w>\" not found!");
		}
	}

	static function getHelp()
	{
		return 'Used to load apps. For a list of available apps, use "<b>>applist</b>".';
	}

	static function getExample()
	{
		return "<b>>load <c>settings</c></b>\nThis will load the \"settings\" app.";
	}
}

class comm_unload extends Command
{
	static function run($input)
	{
		global $response;
		global $user;

		if (!isset($user->app))
		{
			notifyWarning('No app currently loaded.');
			return;
		}

		$appBefore = $user->app;

		$appBefore::onUnload();

		$user->app = null;

		$appBefore = substr($appBefore, 4);

		notifySuccess("App \"<w>{$appBefore}</w>\" unloaded.");

		$response->set(UI_APP, NO_APP);
		$response->clear(PANE_SYSTEM);
	}

	static function getHelp()
	{
		return 'Used to unload the currently loaded app.';
	}

	static function getExample()
	{
		return "<b>>unload</b>\nThis will unload the currently loaded app.";
	}
}

class comm_go extends Command
{
	static function run ($input)
	{
		global $thread;

		$hash = $input === '' ? getRandomHash() : getHash($input);

		$thread = new Thread($hash);
//		$thread->getPosts();
	}

	static function getHelp ()
	{
		return 'Go to a thread. Character strings are hashed and hashes are used without modification.';
	}

	static function getExample()
	{
		return "<b>>go</b> <c>conphid</c>\nThis will navigate to the main thread (i.e., thread bf001056fdb6d253036b96f2f33f8f96).\n<b>>go</b> <c>bf001056fdb6d253036b96f2f33f8f96</c>\nThis will achieve the same result.";
	}
}

class comm_autolink extends Command
{
	public  static function run($input)
	{
		global $response;
		global $thread;
		global $user;

		$message = "The following link will bring you to this thread signed in as {$user->name}.\n";
		$url = CONPHID_URL . "?t={$thread->hash}&u={$user->hash}";
		$warning = "\n<r>Do not use this link to refer anyone else to this thread.</r>";

		$response->append(PANE_SYSTEM, $message . "<a class=\"link\" href=\"{$url}\">{$url}</a>{$warning}");
	}

	public  static function getHelp()
	{
		return 'Provides an "auto link"; a link that will take you to the current thread and sign you in as the current user.';
	}

	public  static function getExample()
	{
		return "<b>>autolink</b>\nThis will provide an auto link.";
	}
}

class comm_re extends Command
{
	static function run($input)
	{
		global $thread;

		$parts = explode(' ', $input, 2);

		if (isset($parts[0]) && is_numeric($parts[0]))
		{
			$postId = intval($parts[0]);
		}
		else
		{
			notifyError('Invalid post ID.');
			return;
		}

		if (isset($parts[1]))
		{
			$body = $parts[1];
		}
		else
		{
			notifyError('No body text included.');
			return;
		}

		$rePostId = query(
			'SELECT id FROM posts WHERE id = :id AND thread_hash = :hash AND deleted = 0 AND hidden = 0',
			[':id' => $postId, ':hash' => $thread->hash]);

		if (!$rePostId)
		{
			notifyError('Replies must be made to posts in the same thread. Deleted posts cannot be replied to.');
			return;
		}


		$thread->submitPost($body, $postId);
	}

	static function getHelp()
	{
		return 'Replies to another post using its ID as a handle. The ID of a post can be found before its timestamp and should be the first parameter. The rest of the command should consist of the intended reply. Replies will link to the post the yare replying to and replies can only be made to posts in the same thread.';
	}

	static function getExample()
	{
		return "<b>>re</b> <c>283 I agree</c>\nThis will reply to the post with the ID of \"238\" with the response \"I agree\".";
	}
}

class comm_palette extends Command
{

	public  static function run($input)
	{
		global $response;
		global $user;

		$tags	= getAvailableTags();
		$emoji	= getAvailableEmoji();

		$output = "<c>Available colours:</c>\n";

		$sameLine = true;
		foreach ($tags as $tag)
		{
			$output .= "[{$tag}]<{$tag}>Text</{$tag}>[/{$tag}]" . ($sameLine ? "\t\t" : "\n");
			$sameLine = !$sameLine;
		}

		$output .= "\n<c>Available emoji:</c>\n";

		$sameLine = true;
		foreach ($emoji as $key => $emoji)
		{
			$output .= "{$key}\t{$emoji}" . ($sameLine ? "\t\t" : "\n");
			$sameLine = !$sameLine;
		}

		if ($user->level >= HR_LEVEL)
		{
			$output .= "\n" . '<div style="text-align:center;">(hr)</div><hr style="margin:auto"/>';
		}

		$response->append(PANE_SYSTEM, $output);
	}

	public  static function getHelp()
	{
		return 'Shows a list of formatting options available for the current user. Additional formatting options become available with higher account levels.';
	}

	public  static function getExample()
	{
		return "<b>>palette</b>\nThis will show a list of available formatting options.";
	}
}

class comm_md5 extends Command
{
	public  static function run($input)
	{
		global $response;

		$response->append(PANE_SYSTEM, md5($input));
	}

	public  static function getHelp()
	{
		return 'Outputs the md5 hash of the inputted string.';
	}

	public  static function getExample()
	{
		return "<b>>md5</b> <c>conphid</c>\nThis will output \"bf001056fdb6d253036b96f2f33f8f96\".";
	}
}

class comm_bookmark extends Command
{
	static $level = 1;

	public  static function run($input)
	{
		global $user;
		global $thread;

		$exists = query('SELECT * FROM bookmarks WHERE user_hash = :user AND thread_hash = :thread', [':user' => $user->hash, ':thread' => $thread->hash], true);

		if ($exists)
		{
			query('DELETE FROM bookmarks WHERE user_hash = :user AND thread_hash = :thread', [':user' => $user->hash, ':thread' => $thread->hash]);
			notifySuccess("Thread \"<w>{$thread->hash}</w>\" has been removed from bookmarks.");
			$thread->bookmarked = false;
		}
		else
		{
			$input = trim(cleanString($input));

			if (strlen($input > MAX_BOOKMARK_NAME))
			{
				$input = substr($input, 0, MAX_BOOKMARK_NAME);
				notifyWarning("Bookmark name is too long. Name will be truncated down to <w>200</w> characters \"<w>{$input}</w>\"().");
			}

			$name = $thread->hash;
			if ($thread->name) $name = $thread->name;
			if ($input) $name = $input;

			query('INSERT INTO bookmarks (user_hash, thread_hash, name, timestamp) VALUES (:user, :thread, :name, :time)',
				[
					':user' => $user->hash,
					':thread' => $thread->hash,
					':name' => $name,
					':time' => $_SERVER['REQUEST_TIME'],
				]
			);

			notifySuccess("Thread \"<w>{$thread->hash}</w>\" has been added to bookmarks.");
			$thread->bookmarked = true;
		}

		$user->getBookmarks();
	}

	public  static function getHelp()
	{
		return 'Sets a bookmark link to the current thread (or removes the existing bookmark link if the current thread is already bookmarked). Bookmarks can be named and will be accessible from the bookmark pane.';
	}

	public  static function getExample()
	{
		return "<b>>bookmark</b>\nThis will bookmark the current thread. If the thread is named, the bookmark will use the thread's name. If not, the thread's hash will be used.\n<b>>bookmark</b> <c>My favourite thread</c>\nThis will bookmark the current thread under the name \"My favourite thread\".";
	}
}

class comm_subscribe extends Command
{
	static $level = 1;

	public  static function run($input)
	{
		global $thread;
//		global $response;

		if ($thread->subscribe())
		{
			notifySuccess("You are now subscribed to thread <w>{$thread->hash}</w>.");
		}
		else
		{
			notifySuccess("You are no longer subscribed to thread <w>{$thread->hash}</w>.");
		}
	}

	public  static function getHelp()
	{
		return 'Subscribes (or unsubscribes) the current account to the current thread. If subscribed to a thread, unread posts will result in a notification being issued.';
	}

	public  static function getExample()
	{
		return "<b>>subscribe</b>\nIf the current user is not subscribed to the current thread, this will subscribe them. If they are already subscribed, this will cancel the subscription.";
	}
}

class comm_invite extends Command
{
	static $level = 2;
	static $threadBased	= true;
	static $modComm		= true;

	public  static function run($input)
	{
		global $thread;
		global $user;

		$recipient = query('SELECT hash FROM users WHERE name = :name', [':name' => $input]);

		if (isset($recipient[0])) $recipient = $recipient[0]['hash'];
		else
		{
			notifyWarning("User <w>{$input}</w> not found. Invitation could not be sent.");
			return;
		}

		query(
			'INSERT INTO invitations
			(recipient_hash, sender_hash, thread_hash, timestamp)
			VALUES
			(:recipient, :sender, :thread, :time)',
			[
				':recipient'	=> $recipient,
				':sender'		=> $user->hash,
				':thread'		=> $thread->hash,
				':time'			=> $_SERVER['REQUEST_TIME'],
			]);

		notifySuccess("User <w>{$input}</w> has been invited to join this thread.");
	}

	public  static function getHelp()
	{
		return 'Allows another user to be invited to the current thread.';
	}

	public  static function getExample()
	{
		return "<b>>invite</b> <c>Jared</c>\nThis will send an invitation notification to the user with the username \"Jared\" containing a link to the current thread.";
	}
}

class comm_edit extends Command
{
	static $level = 2;
	static $threadBased = true;
	static $modComm		= true;

	static $cd_period	= TIME_DAY;
	static $cd_runs		= [
		0 => 1,
		1 => 1,
		2 => 3,
		3 => 5,
		4 => 8,
		5 => 15,
	];

	public  static function run($input)
	{
		global $user;
		global $thread;
		global $response;

		$parts = explode(' ', $input, 2);

		if (isset($parts[0]) && is_numeric($parts[0]))
		{
			$post_id = intval($parts[0]);
		}
		else
		{
			notifyError('Invalid post id.');
			return;
		}

		$sql = 'SELECT * FROM posts WHERE id = :id AND user_hash = :user AND thread_hash = :thread AND deleted = 0';

		$db = getDB();

		$stmt = $db->prepare($sql);

		$stmt->bindValue(':id', $post_id, PDO::PARAM_INT);
		$stmt->bindValue(':user', $user->hash, PDO::PARAM_STR);
		$stmt->bindValue(':thread', $thread->hash, PDO::PARAM_STR);

		$stmt->execute();

		$isAllowed = $stmt->fetchAll();

		if (!$isAllowed)
		{
			notifyError('You can only edit posts which you aurthored in the current thread.');
			return;
		}

		////////////////////////////////////////////////////////////////////////
		//
		//	WE'RE ALLOWED TO DO THIS
		//
		////////////////////////////////////////////////////////////////////////

//		$body = isset($parts[1]) ? cleanString($parts[1]) : null; // NOPE! The string has already been cleaned at this point.
		$body = isset($parts[1]) ? $parts[1] : null;

		$result = query('SELECT body FROM posts WHERE id = :id', [':id' => $post_id]);
		$existingBody = $result[0]['body'];

		if ($body)
		{
			////////////////////////////////////////////////////////////////////////
			//
			//	MOVE EXISTING POST TO EDIT LOG
			//
			////////////////////////////////////////////////////////////////////////

			$sql =
			'INSERT INTO edits (post_id, body, timestamp, ip)
			SELECT id , body, timestamp, ip
			FROM posts
			WHERE id = :pid';

			query($sql, [':pid' => $post_id]);

			////////////////////////////////////////////////////////////////////////
			//
			//	UPDATE POST
			//
			////////////////////////////////////////////////////////////////////////

			$sql =
			'UPDATE posts
			SET body = :body, timestamp = :timestamp, ip = :ip
			WHERE id = :id';

			query($sql,
			[
				':body' => $body,
				':timestamp' => $_SERVER['REQUEST_TIME'],
				':ip' => $_SERVER['REMOTE_ADDR'],
				':id' => $post_id,
			]);

			notifySuccess("Post <w>{$post_id}</w> successfully updated.");
			self::cooldown();
			$response->set("pb_{$post_id}", cleanReply($body));
		}
		else
		{
			$response->set(UI_INPUT, ">edit {$post_id} {$existingBody}");
		}
	}

	public  static function getHelp()
	{
		return 'Edits an existing post in the current thread made by the current user. Inputting the ID only will load the input with the existing post for minor edits. Edited posts show a pencil in their metadata.';
	}

	public  static function getExample()
	{
		return "<b>>edit</b> <c>24 New text for post</c>\nThis will change the body of post 24 to \"New text for post\".\n<b>>edit</b> <c>24</c>\nThis will load the input box with the text boy of post 24 to fascilitate minor edits.";
	}
}

class comm_delete extends Command
{
	static $level = 3;
	static $threadBased = true;
	static $modComm		= true;

	static $cd_period	= TIME_DAY;
	static $cd_runs		= [
		0 => 1,
		1 => 1,
		2 => 1,
		3 => 2,
		4 => 2,
		5 => 3,
	];

	public  static function run($input)
	{
		global $user;
		global $thread;
		global $response;

		if (is_numeric($input))
		{
			$post_id = intval($input);
		}
		else
		{
			notifyError('Invalid post id.');
			return;
		}

		$sql = 'SELECT * FROM posts WHERE id = :id AND user_hash = :user AND thread_hash = :thread';

		$db = getDB();

		$stmt = $db->prepare($sql);

		$stmt->bindValue(':id', $post_id, PDO::PARAM_INT);
		$stmt->bindValue(':user', $user->hash, PDO::PARAM_STR);
		$stmt->bindValue(':thread', $thread->hash, PDO::PARAM_STR);

		$stmt->execute();

		$results = $stmt->fetchAll();

		if (!$results)
		{
			notifyError('You can only delete posts which you aurthored in the current thread.');
			return;
		}

		////////////////////////////////////////////////////////////////////////
		//
		//	WE'RE ALLOWED TO DO THIS
		//
		////////////////////////////////////////////////////////////////////////

		$deleted = intval($results[0]['deleted']) === 0 ? 1 : 0;

		notifyDebug($results[0]['deleted']);

		query('UPDATE posts SET deleted = :del WHERE id = :postId',
			[
				':del' => $deleted,
				':postId' => $post_id
			]
		);

		if ($deleted)
		{
			notifySuccess("Post <w>{$post_id}</w> deleted.");
			self::cooldown();
			$response->set("pb_{$post_id}", DELETED_POST);
		}
		else
		{
			notifySuccess("Post <w>{$post_id}</w> restored.");
			$response->set("pb_{$post_id}", cleanReply($results[0]['body']));
		}
	}

	public  static function getHelp()
	{
		return 'Deltes an existing post. If used on a deleted post, the post will be restored. Restoring a post does not activate command cooldown.';
	}

	public  static function getExample()
	{
		return "<b>>delete</b> <c>24</c>\nThis will delte the post with the ID of 24. If 24 was deleted, the post will instead be restored. Users can still see the metadata associated with the post but the post body will be removed.";
	}
}

class comm_reclaim extends Command
{
	static $level = 1;
	static $threadBased = true;
	static $modComm		= true;

	public  static function run($input)
	{
		global $user;
		global $thread;
		global $response;

		if ($thread->anonymous)
		{
			notifyWarning('You cannot reclaim posts while a thread is anonymous.');
			return;
		}

		$parts = explode(' ', $input, 2);

		if (isset($parts[0]) && is_numeric($parts[0]))
		{
			$post_id = intval($parts[0]);
		}
		else
		{
			notifyError('Invalid post id.');
			return;
		}

//		$isAllowed = query(,
//		[
//			':id' => $post_id,
//			':user' => $user->hash,
//			':thread' => $thread->hash,
//		], true);

		$sql = 'SELECT * FROM posts WHERE id = :id AND user_hash = :user AND thread_hash = :thread';

		$db = getDB();

		$stmt = $db->prepare($sql);

		$stmt->bindValue(':id', $post_id, PDO::PARAM_INT);
		$stmt->bindValue(':user', $user->hash, PDO::PARAM_STR);
		$stmt->bindValue(':thread', $thread->hash, PDO::PARAM_STR);

		$stmt->execute();

		$isAllowed = $stmt->fetchAll();

		if (!$isAllowed)
		{
			notifyError('You can only reclaim posts which you aurthored in the current thread.');
			return;
		}

		////////////////////////////////////////////////////////////////////////
		//
		//	WE'RE ALLOWED TO DO THIS
		//
		////////////////////////////////////////////////////////////////////////
		notifyDebug('pre prepare');

		$stmt = null;
		$stmt = $db->prepare(
			'UPDATE posts
			SET username = :name, nametag = :tag
			WHERE id = :id'
		);

		notifyDebug('pre binding');

		$stmt->bindValue(':id', $post_id, PDO::PARAM_INT);
		$stmt->bindValue(':name', $user->name, PDO::PARAM_STR);
		$stmt->bindValue(':tag', $user->properties[PROP_NAMETAG], PDO::PARAM_STR);

		notifyDebug('pre execution');
//		return;

		$stmt->execute();

		notifyDebug('post execution');
		checkStatement($stmt);

		notifySuccess("Post <w>{$post_id}</w> successfully reclaimed.");

		$response->set("pn_{$post_id}", "<{$user->properties[PROP_NAMETAG]}> {$user->name} </{$user->properties[PROP_NAMETAG]}>");
	}

	public  static function getHelp()
	{
		return 'Allows a user who has changed their name to apply their new name to an old post that they have made. The timestamp is not changed.';
	}

	public  static function getExample()
	{
		return "<b>>reclaim</b> <c>24</c>\nThis will change the name associated with the post to their current name as if the post had just been made. The timestamp will be unaffected.";
	}
}

class comm_bump extends Command
{
	static $threadBased = true;
	static $cd_period	= TIME_HOUR;
	static $cd_runs		= [
		0 => 3,
		1 => 5,
		2 => 8,
		3 => 15,
	];

	public static function run($input)
	{
		global $thread;
		global $user;

		$time = $_SERVER['REQUEST_TIME'] - TIME_DAY;

		$results = query('SELECT timestamp FROM feed WHERE thread_hash = :thread AND timestamp > :time', [':thread' => $thread->hash, ':time' => $time]);

		if ($results)
		{
			$time = intval($results[0]['timestamp']) + TIME_DAY - $_SERVER['REQUEST_TIME'];

			notifyWarning('This thread has been bumped too recently. It cannot be bumped again for <w>' . getReadableTime($time) . '</w>.');
			return;
		}

		query('INSERT INTO feed
			(thread_hash, user_hash, timestamp)
			VALUES
			(:thread, :user, :time)
			ON DUPLICATE KEY UPDATE
			user_hash = :user,
			timestamp = :time',
			[
				':thread' => $thread->hash,
				':user' => $user->hash,
				':time' => $_SERVER['REQUEST_TIME']
			]
		);

		getFeed();

		notifySuccess("Thread <w>{$thread->hash}</w> has been successfully bumped. It cannot be bumped again for 24 hours.");

		self::cooldown();
	}

	public  static function getHelp()
	{
		return 'Adds the current thread to the top of the feeds panel. A thread can only be bumped onces in any 24 hour period. Links to threads in the feeds panel are visible to all users.';
	}

	public  static function getExample()
	{
		return "<b>>bump</b>\nThis will bump the thread to the top of the feeds panel.";
	}
}














////////////////////////////////////////////////////////////////////////////////
//
//	HIDDEN
//
////////////////////////////////////////////////////////////////////////////////

class comm_fetcholdposts extends Command
{
	static $hidden = true;

	public  static function run($input)
	{
		global $thread;

		$vars = explode(' ', $input, 2);

		if (!isset($vars[0])) return;

		$firstFetchedPost = intval($vars[0]);
		$fetchFrom = isset($vars[1]) ? intval($vars[1]) : null;


		$thread->getOldPosts($firstFetchedPost, $fetchFrom);
	}

	public  static function getHelp()
	{
		// Format:
		// >comm_fetcholdposts $from $to

		return 'Very sneaky! I\'m glad you\'re so curious about the inner workings of Con&phi;d.';
	}

	public  static function getExample()
	{
		return 'Want to make a Con&phi;d app? Get in touch with admin.';
	}
}
