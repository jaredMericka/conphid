<?php

class app_mod extends App
{
	public  static function getName() { return 'Moderator Tools'; }

	public  static function getSplash()
	{
		return 'Moderator tools.';
	}
	public  static function getHelp()
	{
		return '';
	}
}

class comm_dm extends Command
{
	static $level = 9;

	public  static function run($input)
	{
		$devMode = (isset($_SESSION[SESS_DEV]) && $_SESSION[SESS_DEV] === true);

		$_SESSION[SESS_DEV] = !$devMode;

		notifySuccess('Debug mode is ' . ($_SESSION[SESS_DEV] ? '<w>ON</w>' : '<r>OFF</r>'));
	}

	public  static function getHelp()
	{
		return "Debug mode.";
	}

	public  static function getExample()
	{
		;
	}
}

class comm_rp extends Command
{
	static $level = 9;

	public  static function run($input)
	{
		global $response;

		if (!is_numeric($input))
		{
			$limit = 50;
		}
		else
		{
			$limit = min([intval($input), 500]);
		}

		$results = query(
			"SELECT p.id, p.body, p.username, p.nametag, t.hash, t.name
			FROM posts as p
			LEFT JOIN threads as t
			ON t.hash = p.thread_hash
			ORDER BY p.id DESC
			LIMIT {$limit}
			",
			[
//				':limit' => $limit
			]
		);

		$results = array_reverse($results);

		$string = '';
		foreach ($results as $result)
		{
			if (strlen($result['body']) > 50) $result['body'] = substr($result['body'], 0, 47) . '...';

			$string .= "<div title='{$result['name']}'><a href='?t={$result['hash']}'><{$result['nametag']}> {$result['username']} </{$result['nametag']}>{$result['body']}</div>";
		}

		$response->append(PANE_SYSTEM, $string);
	}

	public  static function getHelp()
	{
		return "Recent posts.";
	}

	public  static function getExample()
	{
		;
	}
}

class comm_rt extends Command
{
	static $level = 9;

	public  static function run($input)
	{
		global $response;

		if (!is_numeric($input))
		{
			$limit = 50;
		}
		else
		{
			$limit = min([intval($input), 500]);
		}

		$results = query(
			"SELECT
				t.hash,
				t.name
			FROM threads as t
			LEFT JOIN posts as p
			ON p.thread_hash = t.hash
			GROUP BY p.thread_hash
			Order by p.id DESC
			",
			[
//				':limit' => $limit
			]
		);

		$results = array_reverse($results);

		$string = '';
		foreach ($results as $result)
		{
			$name = $result['name'] ? $result['name'] : $result['hash'];
			$string .= "<div class='link'><a href='?t={$result['hash']}'>{$name}</div>";
		}

		$response->append(PANE_SYSTEM, $string);
	}

	public  static function getHelp()
	{
		return "Recent threads.";
	}

	public  static function getExample()
	{
		;
	}
}

class comm_el extends Command
{
	static $level = 9;

	public  static function run($input)
	{
		comm_go::run(md5(ERROR_THREAD));
	}

	public  static function getHelp()
	{
		return "Error logs.";
	}

	public  static function getExample()
	{
		;
	}
}

class comm_cel extends Command
{
	static $level = 9;

	public  static function run($input)
	{
		global $thread;
		global $response;

		query('DELETE FROM posts WHERE thread_hash = :elt', [':elt' => md5(ERROR_THREAD)]);
		notifySuccess('Error logs cleared.');

		if ($thread->hash === md5(ERROR_THREAD)) $response->clear(PANE_MAIN);
	}

	public  static function getHelp()
	{
		return "Clear error logs.";
	}

	public  static function getExample()
	{
		;
	}
}

class comm_te extends Command
{
	static $level = 9;

	public  static function run($input)
	{
		self::function_1();
	}

	private static function function_1 () { self::function_2();	}
	private static function function_2 () { self::function_3();	}
	private static function function_3 () { self::function_4();	}
	private static function function_4 () { self::function_5();	}
	private static function function_5 () { $var = 1 / 0;		}

	public  static function getHelp()
	{
		return "Throw error.";
	}

	public  static function getExample()
	{
		;
	}
}

class comm_pd extends Command
{
	static $level = 9;

	public  static function run($input)
	{
		global $response;
		global $user;

		$response->append(PANE_SYSTEM, $user->getPostDays());
	}

	public  static function getHelp()
	{
		return "Post days.";
	}

	public  static function getExample()
	{
		;
	}
}


//class comm_rp extends Command
//{
//	static $level = 9;
//
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