<?php

class app_admin extends App
{
	public  static function getName()
	{
		return 'Thread Admin Tools';
	}

	public  static function getSplash()
	{
		ob_start();

		?>
<x><?=ICO_COGS . ICO_THREAD . ICO_COGS?></x> Welcome to <?=CONPHID?> Thread Admin Tools <x><?=ICO_COGS . ICO_THREAD . ICO_COGS?></x>

Thread Admin Tools provides a series of commands for managing thread settings and moderation.
Use the <b>>commlist</b> command to see the available commands for thread administration.
		<?php

		return ob_get_clean();
	}

	public  static function getHelp()
	{
		return 'A suite of tools used for ' . CONPHID . ' admin tasks.';
	}
}

class comm_index extends Command
{
	static $level = 7;
	static $modComm = true;
	static $threadBased = true;
	
	public static function run($input)
	{
		global $thread;

		$thread->indexed = !$thread->indexed;

		if ($thread->indexed)
		{
			notifySuccess("Thread <w>{$thread->hash}</w> was successfully INDEXED and will now appear in search results.");
		}
		else
		{
			notifySuccess("Thread <w>{$thread->hash}</w> is NO LONGER INDEXED and will not appear in search results.");
		}
	}

	public  static function getHelp()
	{
		return 'Toggles indexing for the current thread. Only indexed threads can appear in search results.';
	}

	public  static function getExample()
	{
		return "<b>>index</b>\nThis will index the current thread allowing the thread's posts to appear in <b>>find</b> searches.";
	}
}

class comm_doc extends Command
{
	static $level = 7;
	static $modComm = true;
	static $threadBased = true;

	public  static function run($input)
	{
		global $thread;

		$thread->doc = !$thread->doc;

		if ($thread->doc)
		{
			notifySuccess("Thread <w>{$thread->hash}</w> is now a DOCUMENT.");
		}
		else
		{
			notifySuccess("Thread <w>{$thread->hash}</w> is now a DISCUSSION.");
		}
	}

	public  static function getHelp()
	{
		;
	}

	public  static function getExample()
	{
		;
	}
}

class comm_lock extends Command
{
	static $level		= 7;
	static $modComm		= true;

	public  static function run($input)
	{
		global $thread;

		$thread->locked = !$thread->locked;

		if ($thread->locked)
		{
			notifySuccess("Thread <w>{$thread->hash}</w> is now LOCKED.");
		}
		else
		{
			notifySuccess("Thread <w>{$thread->hash}</w> is now UNLOCKED.");
		}
	}

	public  static function getHelp()
	{
		return 'Locks the current thread preventing any further posts except from admins.';
	}

	public  static function getExample()
	{
		return "<b>>lock</b>\nThis will lock the current thread.";
	}
}

class comm_seal extends Command
{
	static $level		= 7;
	static $modComm		= true;
	static $threadBased	= true;

	public  static function run($input)
	{
		global $thread;

		$thread->sealed = !$thread->sealed;

		if ($thread->sealed)
		{
			notifySuccess("Thread <w>{$thread->hash}</w> is now SEALED.");
		}
		else
		{
			notifySuccess("Thread <w>{$thread->hash}</w> is now UNSEALED.");
		}
	}

	public  static function getHelp()
	{
		return 'Seals the current thread. Sealed threads can only be posted in by accounts who have posted in it before.';
	}

	public  static function getExample()
	{
		return "<b>>seal</b>\nThis will toggle sealed mode.";
	}
}

class comm_anon extends Command
{
	static $level		= 5;
	static $modComm		= true;
	static $threadBased	= true;

	public  static function run($input)
	{
		global $thread;

		$thread->anonymous = !$thread->anonymous;

		if ($thread->anonymous)
		{
			notifySuccess("Thread <w>{$thread->hash}</w> is now ANONYMOUS.");
		}
		else
		{
			notifySuccess("Thread <w>{$thread->hash}</w> is NO LONGER ANONYMOUS.");
		}
	}

	public  static function getHelp()
	{
		return 'Makes the thread into an anonymous thread. All username cards will contain "???" while the thread is anonymous.';
	}

	public  static function getExample()
	{
		return "<b>>anon</b>\nThis will toggle anonymous mode.";
	}
}

class comm_burn extends Command
{
//	static $level		= 1;
//	static $modComm		= true;
//	static $threadBased	= true;

	public  static function run($input)
	{
		global $thread;

		if (
			query('SELECT * FROM threads WHERE hash = :hash', [':hash' => $thread->hash], true) ||
			query('SELECT * FROM posts WHERE thread_hash = :hash && hidden = 0 && deleted = 0', [':hash' => $thread->hash], true)
			)
		{
			notifyError('Can only set a burn period on an empty, unconfigured thread.');
			return;
		}

		$input = strtolower($input);

		$pieces = explode(' ', $input);

		$total = 0;
		$unrecognised = [];


		foreach ($pieces as $piece)
		{
			$unit = substr($piece, -1);
			$amount = trim($piece, $unit);

			if (!is_numeric($amount))
			{
				$unrecognised[] = $piece;
				continue;
			}

			switch ($unit)
			{
				case 's':
//					$amount *= TIME_SECOND;
					break;

				case 'm':
					$amount *= TIME_MINUTE;
					break;

				case 'h':
					$amount *= TIME_HOUR;
					break;

				case 'd':
					$amount *= TIME_DAY;
					break;

				case 'w':
					$amount *= TIME_WEEK;
					break;

				default:
					$unrecognised[] = $piece;
					continue;
			}

			$total += $amount;
		}

		if ($unrecognised)
		{
			$s = count($unrecognised) > 1 ? 's' : '';
			$unrecognised = implode(' ', $unrecognised);

			notifyError("Unrecognised time unit{$s}: <W>{$unrecognised}</w>.");

			return;
		}

		$thread->burn = $_SERVER['REQUEST_TIME'] + $total;
		$readableTime = getReadableTime($total);

		notifySuccess("Thread <w>{$thread->hash}</w> will burn in <w>{$readableTime}</w>.");

		notifyDebug($thread->burn);
	}

	public  static function getHelp()
	{
		return 'Adds a burn period to an empty, unconfigured thread. After the burn period expires, all posts and thread configuration will be erased. Use command help to see accepted burn period syntax. Since the burn period counts as thread configuration, the burn period cannot be altered once it has been set.';
	}

	public  static function getExample()
	{
		return "<b>>burn</b> <c>3d 5h</c>\nThis set the current thread to burn in three days and five hours. Accepted units are:\n\t<g>&bull;</g> \"w\" for weeks\n\t<g>&bull;</g> \"d\" for days\n\t<g>&bull;</g> \"h\" for hours\n\t<g>&bull;</g> \"m\" for minutes\n\t<g>&bull;</g> \"s\" for seconds";
	}
}

class comm_level extends Command
{
	static $level		= 7;
	static $modComm		= true;
	static $threadBased = true;

	public  static function run($input)
	{
		global $thread;
		global $user;

		if (!is_numeric($input))
		{
			notifyError('Invalid thread restriction level.');
			return;
		}

		$level = intval($input);

		if ($level > $user->level)
		{
			notifyWarning("Unable to restrict a thread to a level above that of the current account (<w>{$level}</w> > <w>{$user->level}</w>).");
			return;
		}

		if ($level === $thread->level)
		{
			notifyWarning("Thread <w>{$thread->hash}</w> is already restricted to account levels <w>{$thread->level}</w> and above.");
			return;
		}

		$thread->level = $level;

		if ($thread->level > 0)
		{
			notifySuccess("Thread <w>{$thread->hash}</w> is now restricted to account levels <w>{$thread->level}</w> and above.");
		}
		else
		{
			notifySuccess("Thread <w>{$thread->hash}</w> is now unrestricted.");
		}
	}

	public  static function getHelp()
	{
		return 'Sets the thread level. Users cannot post in threads with levels higher than their account levels. Thread level also restricts the useage of certain commands.';
	}

	public  static function getExample()
	{
		return "<b>>restrict</b> <c>2</c>\nThis will set the current thread to level 2.";
	}
}

class comm_hide extends Command
{
	static $level		= 7;
	static $modComm		= true;
	static $threadBased = true;

	public  static function run($input)
	{
		global $thread;

		$thread->hidden = !$thread->hidden;

		if ($thread->hidden)
		{
			notifySuccess("Thread <w>{$thread->hash}</w> is now HIDDEN.");
		}
		else
		{
			notifySuccess("Thread <w>{$thread->hash}</w> is now VISIBLE.");
		}
	}

	public  static function getHelp()
	{
		return 'Hides the current thread. Posts in hidden threads will only be visible to users who have permission to post in the thread. To be used in conjuction with <b>>lock</b>, <b>>level</b> and <b>>seal</b>.';
	}

	public  static function getExample()
	{
		return "<b>>hide</b>\nThis will hide the current thread from anyone who is unable to post in it.";
	}
}

class comm_name extends Command
{
	static $level		= 2;
	static $modComm		= true;
	static $threadBased = true;

	static $cd_period = TIME_DAY;
	static $cd_runs = [
		0 => 0,
		1 => 0,
		2 => 3,
		4 => 5,
		5 => 8,
		6 => 10,
		7 => 10,
		8 => 10
	];

	public  static function run($input)
	{
		global $user;
		global $thread;

		if ($user->level < 4 && isset($thread->name))
		{
			notifyWarning('Replacing an existing thread name requires account level <y>'.ICO_STAR.'4</y> or higher.');
			return;
		}

		$input = cleanString($input);

		$length = strlen($input);
		if ($length > MAX_THREAD_NAME)
		{
			notifyError('Thread name is too long. <w>' . MAX_THREAD_NAME . "</w> characters are permitted; <w>{$length}</w> characters used.");
			return;
		}

		$thread->name = $input;

		notifySuccess("Thread name changed to <w>{$input}</w>");
		self::cooldown();
	}

	public  static function getHelp()
	{
		return 'Sets the name of the current thread.';
	}

	public  static function getExample()
	{
		return "<b>>name</b> <c>Serious business</c>\nThis will set the name of the current thread to \"Serious business\". The name will appear at the top of the thread for all users and become the default bookmark title for bookmakrs made of this thread while it has this name.";
	}
}

class comm_link extends Command
{
	static $level = 2;
	static $modComm		= true;
	static $threadBased = true;

	static $cd_period	= TIME_DAY;
	static $cd_runs		= [
		0	=> 1,
		1	=> 1,
		2	=> 10,
		3	=> 20,
	];

	public  static function run($input)
	{
		global $thread;
		global $user;

		$threadName = $thread->name ? $thread->name : $thread->hash;

		if (!isHash($input))
		{
			notifyError("Invalid thread hash <w>$input</w>. Link could not be created.");
			return;
		}

		if ($input === $thread->hash)
		{
			notifyWarning("You cannot link a thread to itself.");
			return;
		}

		if (query('SELECT thread_hash FROM links WHERE thread_hash = :thread AND link_hash = :link', [':thread' => $thread->hash, ':link' => $input], true))
		{
			query('DELETE FROM links WHERE thread_hash = :thread AND link_hash = :link', [':thread' => $thread->hash, ':link' => $input]);
			notifySuccess("Thread <w>{$threadName}</w> has now been unlinked from thread <w>{$input}</w>. The link back will persist until removed.");

			$thread->getLinks();
			return;
		}

		query(
			'INSERT INTO links (
			thread_hash,
			link_hash,
			timestamp,
			user_hash
			)
			VALUES
			(:thread, :link, :time, :user)
			ON DUPLICATE KEY UPDATE
			timestamp = :time,
			user_hash = :user',
			[
				':thread' => $thread->hash,
				':link' => $input,
				':time' => $_SERVER['REQUEST_TIME'],
				':user' => $user->hash
			]
		);

		$results = query('SELECT name FROM threads WHERE hash = :link', [':link' => $input]);

		$linkName = isset($results[0]) ? $results[0]['name'] : $input;

		$thread->getLinks();

		notifySuccess("Thread \"<w>{$threadName}</w>\" is now linked to thread \"<w>{$linkName}</w>\"");
		self::cooldown();
	}

	public  static function getHelp()
	{
		return 'Links two threads together via the links list. If the link is removed in one thread, the link back will persist.';
	}

	public  static function getExample()
	{
		return "<b>>link <c>bf001056fdb6d253036b96f2f33f8f96</c>\nThis will place links between the current thread and thread \"bf001056fdb6d253036b96f2f33f8f96\" in thier respective link lists.";
	}
}

class comm_pin extends Command
{
	static $level = 7;

	public  static function run($input)
	{
		global $thread;
		global $user;

		$results = query('SELECT timestamp FROM feed WHERE thread_hash = :thread AND pinned = 1', [':thread' => $thread->hash]);

		if ($results)
		{
			query('UPDATE feed SET
				pinned = 0,
				timestamp = :time
				WHERE thread_hash = :thread',
				[
					':thread' => $thread->hash,
					':time' => $_SERVER['REQUEST_TIME']
				]
			);

			notifySuccess("Thread <w>{$thread->hash}</w> has been successfully UNPINNED.");
			getFeed();
			return;
		}

		query('INSERT INTO feed
			(thread_hash, user_hash, timestamp, pinned)
			VALUES
			(:thread, :user, :time, 1)
			ON DUPLICATE KEY UPDATE
			user_hash = :user,
			timestamp = :time,
			pinned = 1',
			[
				':thread' => $thread->hash,
				':user' => $user->hash,
				':time' => $_SERVER['REQUEST_TIME']
			]
		);

		getFeed();

		notifySuccess("Thread <w>{$thread->hash}</w> has been successfully PINNED.");
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

class comm_mod extends Command
{
	static $level		= 7;
	static $modComm		= true;

	public  static function run($input)
	{
		global $thread;

		$ls = '<y>' . ICO_STAR . '1</y>';

		if (isHash($input))
		{
			$results = query('SELECT name FROM users WHERE hash = :hash', [':hash' => $input]);

			if (isset($results[0]))
			{
				$mod_name = $results[0]['name'];
				$mod_hash = $input;
			}
			else
			{
				notifyError("User hash <w>{$input}</w> does not belong to an eligible account. Only users with an account level of {$ls} or higher can be appointed mods.");
			}
		}
		else
		{
			$results = query('SELECT hash FROM users WHERE name = :name', [':name' => $input]);

			if (isset($results[0]))
			{
				$mod_name = $input;
				$mod_hash = $results[0]['hash'];
			}
			else
			{
				notifyError("User <w>{$input}</w> is either anonymous or non-existant. Only users with an account level of {$ls} or higher can be appointed mods.");
				return;
			}
		}

		$thread->mod_name = $mod_name;
		$thread->mod_hash = $mod_hash;
	}

	public  static function getHelp()
	{
		return 'Appoints a chosen user to be the current thread\'s moderator. A thread can only have one moderator and being a moderator allows access to otherwise restricted commands but only in threads of which they are appointed moderator.';
	}

	public  static function getExample()
	{
		return "<b>>mod<b> <c>Casey</c>\nThis would appoint the user with the username \"Casey\" to be the moderator of the current thread.";
	}
}

class comm_move extends Command
{
	static $level = 9;

	public  static function run($input)
	{
		global $thread;

		$newHash = $input ? getHash($input) : getRandomHash();

		query('UPDATE posts SET thread_hash = :newHash WHERE thread_hash = :oldHash',
			[
				':newHash' => $newHash,
				':oldHash' => $thread->hash,
			]
		);

		notifySuccess("All posts from thread <w>{$thread->hash}</w> have been moved to thread <w>{$newHash}</w>.");
	}

	public  static function getHelp()
	{
		return 'Moves the current thread to a new hash.';
	}

	public  static function getExample()
	{
		return "<b>>move</b> <c>bnce</c>\nThis will move the current thread to the md5 hash of \"bnce\".";
	}
}