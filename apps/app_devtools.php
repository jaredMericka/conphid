<?php

class app_devtools extends App
{
	static function getName()
	{
		return 'Development Tools';
	}

	static function getSplash()
	{
		return 'Welcome to Development Tools for ' . CONPHID;
	}

	static function getHelp()
	{
		return 'A bunch of dev tools to make the code go mental.';
	}
}

class comm_posts extends Command
{
	static $level = 9;

	public  static function run($input)
	{
		global $thread;

		if (!is_numeric($input))
		{
			notifyError('How many?');
			return;
		}

		$reps = intval($input);

		$reps = max(0, min($reps, 100));

		set_time_limit(5 * $reps);


		for ($i = 0; $i < $reps; $i++)
		{
			$words = mt_rand(3, 100);
			$post = '';

			sleep(mt_rand(0,5));

			for ($j = 0; $j < $words; $j++)
			{
				$post .= strtolower(getRandomName()) . ' ';
			}

			$thread->submitPost($post);
		}

	}

	public  static function getHelp()
	{
		return 'Generate bogus posts. Posts will be submitted at a rate of 1 every 0 - 5 seconds. The gap is to allow conversation to be simulated with multiple accounts if open in different tabs and running the command at the same time.';
	}

	public  static function getExample()
	{
		return "If you don't know how this works it means you didn't write the code and if you didn't write the code you shouldn't be using it. <y>>:(</y>";
	}
}

class comm_test extends Command
{
	static $level = 9;

	static $cd_runs = 5;
	static $cd_period = TIME_MINUTE;

	public  static function run($input)
	{
		global $response;

//		$response->append(PANE_SYSTEM, 'I don\'t do anything.');

//		$response->append(PANE_SYSTEM, getStringDiff(
//			'a',
//			'abjjjjjjjjjjcdefg'));

		self::cooldown();
	}

	public  static function getHelp()
	{
	}

	public  static function getExample()
	{

	}
}

class comm_dump extends Command
{
	static $level = 9;

	public  static function run($input)
	{
		global $response;
		global $user;

		ob_start();

		var_dump($user);

		$response->append(PANE_SYSTEM, ob_get_clean());
	}

	public  static function getHelp()
	{
	}

	public  static function getExample()
	{

	}
}

