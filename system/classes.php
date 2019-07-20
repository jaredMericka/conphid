<?php

class Response
{
	public $clear	= [];
	public $append	= [];
	public $prepend	= [];
	public $set		= [];
	public $vars	= [];
	public $scroll	= [];

	public function clear ($id)
	{
		$this->clear[] = $id;
	}

	public function append ($id, $body, $timestamp = null, $postId = null)
	{
		if (in_array($id, [PANE_SYSTEM]))
		{
			$body .= $this->getMeta($timestamp);
		}

		$details = ['id' => $id, 'body' => $body];

		if ($postId) $details['postId'] = $postId;

		$this->append[] = $details;
	}

	public function prepend ($id, $body, $timestamp = null, $postId = null)
	{
		if (in_array($id, [PANE_SYSTEM]))
		{
			$body .= $this->getMeta($timestamp, $postId);
		}

		$details = ['id' => $id, 'body' => $body];

		if ($postId) $details['postId'] = $postId;

		$this->prepend[] = $details;
	}

	public function set ($id, $body)
	{
		$this->set[] = ['id' => $id, 'body' => $body];
	}

	public function vars ($name, $val)
	{
		$this->vars[] = ['name' => $name, 'val' => $val];
	}

	public function scroll ($name, $SCROLL)
	{
		$this->scroll[] = ['id' => $name, 'scroll' => $SCROLL];
	}

	public function __toString ()
	{
		return json_encode($this, JSON_FORCE_OBJECT);
	}

	private function getMeta ($timestamp = null)
	{
		$timestamp = getTimestamp($timestamp);

		return "<div class=\"ra\"><x>{$timestamp}<x/></div>";
	}
}

$response = new Response();

abstract class Command
{
	static $level		= 0;
	static $threadBased = false;
	static $modComm		= false;
	static $hidden		= false;

	static $switches		= [];

	// Cooldown vars
	static $cd_runs;
	static $cd_period;

	abstract static function run ($input);
	abstract static function getHelp ();
	abstract static function getExample ();

	static function cooldown ()
	{
		global $user;

		$cd_key = $user->app . get_called_class();

		if (!isset($user->cooldowns[$cd_key])) $user->cooldowns[$cd_key] = [];

		$user->cooldowns[$cd_key][] = $_SERVER['REQUEST_TIME'];
		$user->saveMe = true;

		// Reporting time

		$command = get_called_class();

		if (is_array($command::$cd_runs))
		{
			if (isset($command::$cd_runs[$user->level])) $runs = $command::$cd_runs[$user->level];
		}
		else $runs = $command::$cd_runs;

		if (is_array($command::$cd_period))
		{
			if (isset($command::$cd_period[$user->level])) $period = $command::$cd_period[$user->level];
		}
		else $period = $command::$cd_period;

		if (isset($runs, $period))
		{
			$period = getReadableTime($period);

			$commName = str_replace('comm_', '', $command);

			$doneRuns = count($user->cooldowns[$cd_key]);
			notifyCooldown("<w>{$doneRuns}</w>/<w>{$runs}</w> uses of <W>>{$commName}</w> per <w>{$period}</w> have been used.");
		}
	}
}

abstract class ClientCommand extends Command
{

}

abstract class App
{
	static $level = 0;

	abstract static function getName ();
	abstract static function getSplash ();
	abstract static function getHelp ();

	static function onLoad ()		{ }
	static function onUnload ()		{ }

	static function beforePost	($input) { }
	static function afterPost	($input) { }
	static function beforeComm	($input) { }
	static function afterComm	($input) { }
}

class User
{
	public	$hash;
	private	$name;

	private	$level = 0;

	private	$app;

	public	$appCache = [];

	public	$settings	= [];
	public	$properties	= [];
	public	$cooldowns	= [];

	public	$notifications = [];

	static	$defaultSettings = [
		SETT_TIMESTAMP_FORMAT => 'r',
		SETT_HOTKEYS => [],
	];

	static	$defaultProperties = [
//		PROP_NEXT_NAME_CHANGE => 0,
//		PROP_PALETTE => [],
		PROP_NAMETAG => 'x_',
		PROP_NEXT_EMAIL_REGO => 0,
	];

	public $saveMe = false;

	public function __construct ($hash = null)
	{
		global $response;
		global $maxSubmissionSize;

		$this->hash = $hash ? $hash : getRandomHash();

		$results = query('SELECT * FROM users WHERE hash = :hash', [':hash' => $this->hash]);

		if (isset($results[0]))
		{
			$info = $results[0];

			$this->name = $info['name'];

			$this->settings = json_decode($info['settings'], JSON_OBJECT_AS_ARRAY);
			$this->settings = $this->settings + self::$defaultSettings;

			$this->properties = json_decode($info['properties'], JSON_OBJECT_AS_ARRAY);
			$this->properties = $this->properties + self::$defaultProperties;

			$this->cooldowns = json_decode($info['cooldowns'], JSON_OBJECT_AS_ARRAY);

			$this->level = $info['level'];
		}
		else
		{
			$this->settings		= self::$defaultSettings;
			$this->properties	= self::$defaultProperties;
			$this->name = getRandomName();
		}

		$this->getBookmarks();
		$this->getNotifications();

		$response->clear(PANE_NOTIFICATIONS);
		$response->vars('cl', $maxSubmissionSize[$this->level]);
	}

	public function __get ($name) { return $this->$name; }
	public function __isset ($name) { return isset($this->$name); }

	public function __set ($name, $value)
	{
		global $response;
		global $maxSubmissionSize;

		if ($name === 'app')
		{
			if ($this->app === $value) return;
			$this->app = $value;
			$this->appCache = [];
			return;
		}

		$this->$name = $value;

		switch ($name)
		{
			case 'level':
				$response->set(UI_USER_LEVEL, $this->level);
				$response->vars('cl', $maxSubmissionSize[$this->level]);
				$this->saveMe = true;
				break;
			case 'name':
				$response->set(UI_USER_NAME, $this->name);
				$this->saveMe = true;
				break;
		}
	}

	public function save ()
	{
		$this->saveMe = false;

		$db = getDB();

		$stmt = $db->prepare(
		'INSERT INTO users
			(
				hash,
				name,
				level,
				settings,
				properties,
				cooldowns,
				ip,
				created
			)
			VALUES
			(
				:hash,
				:name,
				:level,
				:settings,
				:properties,
				:cooldowns,
				:ip,
				:created
			)
			ON DUPLICATE KEY UPDATE
				name = :name,
				level = :level,
				settings = :settings,
				properties = :properties,
				cooldowns = :cooldowns'
		);

		$stmt->execute([
			':hash' => $this->hash,
			':name' => $this->name,
			':level' => $this->level,
			':settings' => json_encode($this->settings),
			':properties' => json_encode($this->properties),
			':cooldowns' => json_encode($this->cooldowns),
			':ip' => $_SERVER['REMOTE_ADDR'],
			':created' => $_SERVER['REQUEST_TIME'],
		]);
	}

	public function promote ($level)
	{
		if ($this->level < $level)
		{
			$this->__set('level', $level);
			$this->saveMe = true;
			notifyPromotion("Congratulations! Your account is now level \"<w>{$this->level}</w>\"!");
		}
	}


	public function getNotifications ()
	{
		global $response;
		global $thread;

		$sql = 'SELECT s.thread_hash,
				p.id,
				p.timestamp,
				s.repliesOnly,
				null as sender
			FROM subscriptions AS s
			LEFT JOIN posts AS p ON (s.thread_hash = p.thread_hash)
			LEFT JOIN posts AS p2 ON (p2.id = p.re)
			WHERE p.id = (SELECT MAX(id) FROM posts AS p2 WHERE p2.thread_hash = p.thread_hash)
			AND s.user_hash = :u
			AND (
				s.repliesOnly = 0
				OR (
					p.id > s.lastSeenPostId
					AND p2.user_hash = :u
				)
			)
			AND lastSeenPostId < (
				SELECT id FROM posts
				WHERE thread_hash = s.thread_hash
				ORDER BY id DESC LIMIT 1
			)
		UNION
			SELECT i.thread_hash,
				null as id,
				i.timestamp,
				null as repliesOnly,
				(SELECT name FROM users as u WHERE u.hash = i.sender_hash) AS sender
			FROM invitations AS i WHERE recipient_hash = :u
			ORDER BY timestamp DESC
			LIMIT 10';

		$results = query($sql,
		[
			':u' => $this->hash
		]);

		$new = false;

		foreach ($results as $result)
		{
			$notification = '';

			if ($result['sender'] && !in_array(md5($result['thread_hash'] . $result['sender']), $this->notifications))
			{
				$this->notifications[] = md5($result['thread_hash'] . $result['sender']);
				$new = true;

				if ($result['thread_hash'] === $thread->hash)
				{
					query('DELETE FROM invitations WHERE recipient_hash = :user AND thread_hash = :thread', [':user' => $this->hash, ':thread' => $thread->hash]);
					notifyDebug('Removing invitations for this thread.');
					continue;
				}
				else
				{
					$notification .= ICO_INVITE . " <c>User <w>{$result['sender']}</w> has invited you to thread <w>{$result['thread_hash']}</w></c>";
				}
			}
			elseif (!in_array($result['thread_hash'], $this->notifications))
			{
				$this->notifications[] = $result['thread_hash'];

				$new = true;

				if ($result['repliesOnly'])
				{
					$notification .= ICO_REPLIES . " <y>New replies to your post <w>{$result['id']}</w> in <w>{$result['thread_hash']}</w></y>";
				}
				else
				{
					$notification .= ICO_SUB . " <g>New posts in subscribed thread <w>{$result['thread_hash']}</w></g>";
				}

			}

			if ($notification)
			{
				$notification = "<a href=\"?t={$result['thread_hash']}\">{$notification}</a>";
				$response->append(PANE_NOTIFICATIONS, $notification);

			}
		}

//		if ($new) $response->clear(PANE_NOTIFICATIONS);

		return $new;
	}

	public function getBookmarks ()
	{
		global $response;

		$results = query('SELECT thread_hash, name FROM bookmarks WHERE user_hash = :user ORDER BY timestamp DESC', [':user' => $this->hash]);

		$response->clear(PANE_BOOKMARKS);

		$ico = '<r>' . ICO_BOOKMARK . '</r> ';

		foreach ($results as $result)
		{
			$tag = "<a href=\"?t={$result['thread_hash']}\" title=\"{$result['name']}\">{$ico}{$result['name']}</a>";

			$response->append(PANE_BOOKMARKS, $tag);
		}

		if (!$results)
		{
			$response->append(PANE_BOOKMARKS, '<x>' . ICO_BOOKMARK . ' No bookmarks</x>');
		}
	}

	public function checkPostPromotion ()
	{
		$debugString = 'Checking Post Promotion';
		global $level_promotions;

		if (!isset($level_promotions[$this->level]))
		{
			$debugString .= "\nNo available promo.";
			notifyDebug($debugString);
			return;
		}
		if ($this->properties[PROP_NEXT_PROMOTION_CHECK] > $_SERVER['REQUEST_TIME'])
		{
			$time = getReadableTime($this->properties[PROP_NEXT_PROMOTION_CHECK] - $_SERVER['REQUEST_TIME']);
			$debugString .= "\nToo soon to check. Check back in {$time}.";
			notifyDebug($debugString);
			return;
		}

		$promo = $level_promotions[$this->level];
		$postDays = $this->getPostDays();

		$debugString .= "\nDays required: {$promo}\nDays accumulated: {$postDays}";

		if ($postDays >= $promo)
		{
			$debugString .= "\nPROMOTION!";
			$this->promote($this->level + 1);
		}
		else
		{
			$days = $_SERVER['REQUEST_TIME'] + ($promo - $postDays);
			$debugString .= "\nWe'll check back in {$days} days";
			$this->properties[PROP_NEXT_PROMOTION_CHECK] = $_SERVER['REQUEST_TIME'] + (($promo - $postDays) * TIME_DAY);
		}

		notifyDebug($debugString);
	}

	public function getPostDays ()
	{

		$sql =
			'SELECT timestamp FROM
			(
				SELECT
					timestamp,
					user_hash,
						CASE
							WHEN timestamp - @i >= ' . TIME_DAY . '
							AND user_hash = :user
						THEN @i := timestamp
						ELSE @i
						END
					AS correctTimeStamp
				FROM posts, (SELECT @i := 0) r
				ORDER BY timestamp
			) a
			WHERE timestamp = correctTimeStamp
			AND user_hash = :user
			ORDER BY timestamp';

		return query($sql, [':user' => $this->hash], true);
	}
}

class Thread
{
	// Thread stuff
	public	$hash;
	private	$name;

	public	$stateHash;

	private $mod_hash;
	private $mod_name;

	private	$level = 0;
	private	$locked = false;
	private $sealed = false;
	private $hidden = false;

	private $burn		= false;
	private $anonymous	= false;
	private $singleton	= false;
	private $indexed	= false;
	private $doc		= false;

	// User stuff
	public	$latestFetchedPost = 0;
	private	$subscribed;
	private $bookmarked;

	// System stuff
	public	$firstPost;
	public	$saveMe = false;

	public function __construct ($hash, $stateHash = null)
	{
		global $user;
		global $response;
		global $thread;

		$hash = getHash($hash);

		$this->hash = $hash;

		$results = query(
			'SELECT
				t.name,
				t.mod_hash,
				t.level,
				t.locked,
				t.sealed,
				t.hidden,
				t.burn,
				t.anonymous,
				t.singleton,
				t.indexed,
				t.doc,
				u.name as mod_name,
				(SELECT id FROM posts WHERE thread_hash = :thread ORDER BY id ASC LIMIT 1) as fp,
				(SELECT COUNT(*) FROM subscriptions WHERE thread_hash = :thread AND user_hash = :user AND repliesOnly = 0) as sub,
				(SELECT COUNT(*) FROM bookmarks WHERE thread_hash = :thread AND user_hash = :user) as bm
			FROM threads t
			LEFT JOIN users u ON u.hash = t.mod_hash
			WHERE t.hash = :thread',
			[
				':thread' => $hash,
				':user' => $user->hash
			]
		);

		if ($results)
		{
			$threadDetails = $results[0];

			$this->name			= $threadDetails['name'];
			$this->level		= $threadDetails['level'];
			$this->locked		= $threadDetails['locked'];
			$this->sealed		= $threadDetails['sealed'];
			$this->hidden		= $threadDetails['hidden'];
			$this->burn			= $threadDetails['burn'] ? intval($threadDetails['burn']) : null;
			$this->anonymous	= $threadDetails['anonymous'];
			$this->singleton	= $threadDetails['singleton'];
			$this->indexed		= $threadDetails['indexed'];
			$this->doc			= $threadDetails['doc'];
			$this->mod_hash		= $threadDetails['mod_hash'];
			$this->mod_name		= $threadDetails['mod_name'];
			$this->firstPost	= $threadDetails['fp'];
			$this->subscribed	= $threadDetails['sub'];
			$this->bookmarked	= $threadDetails['bm'];

			if (isset($this->burn))
			{
				if (!isset($_SESSION[SESS_BURN])) $_SESSION[SESS_BURN] = [];

				$_SESSION[SESS_BURN][$this->hash] = $this->burn;
			}

			$this->stateHash = md5(implode('', $threadDetails));
		}
		else
		{
			if (
				isset($_SESSION[SESS_BURN])
				&& isset($_SESSION[SESS_BURN][$this->hash])
				&& $_SESSION[SESS_BURN][$this->hash] < $_SERVER['REQUEST_TIME']
			)
			{
				$response->set(PANE_MAIN, BURN_NOTICE);
				unset($_SESSION[SESS_BURN][$this->hash]);
			}

			$this->stateHash = md5('');
		}

		$response->vars('firstPost', $this->firstPost);

		if (isset($thread) && $thread->hash !== $hash) // This means we're in a new thread now.
		{
			$response->clear(PANE_MAIN);
			$this->getPosts();
			$_SESSION[SESS_THREAD] = $hash;
		}

		if ($stateHash !== $this->stateHash)
		{
			notifyDebug("Altered state-hash detected.");

			$this->getLinks();

			$response->set(UI_THREAD_HASH, $this->hash);
			$response->set(UI_THREAD_NAME, $this->name);
			$response->set(UI_THREAD_LOCK, $this->getLockIcon());
			$response->set(UI_THREAD_SUB, $this->getSubIcon());


			if ($this->doc)
			{
				$sql = 'SELECT a.id, a.username, a.nametag, a.re, a.body, a.timestamp, a.deleted,
					(SELECT COUNT(*) FROM posts WHERE a.id = re) as replies,
					(SELECT COUNT(*) FROM edits WHERE post_id = a.id) as edits
				FROM posts a
				LEFT JOIN posts b
				ON b.id = a.re
				WHERE a.thread_hash = :thread
				ORDER BY a.id
				ASC LIMIT 1';

				$docResult = query($sql, [':thread' => $this->hash]);

				if ($docResult)
				{
					$docResult = $docResult[0];

					$response->append(PANE_MAIN, $this->rowToPost($docResult), $docResult['timestamp'], $docResult['id']);
					notifyDebug('DocBody got');
				}

				$response->vars('doc', true);
			}
			else
			{
				$response->vars('doc', false);
			}

			$response->vars('sh', $this->stateHash);
			$response->vars('th', $this->hash);

			notifyDebug($this->hash);
		}
	}

	public function __get ($name) { return $this->$name; }
	public function __isset ($name) { return isset($this->$name); }

	public function __set ($name, $value)
	{
		global $response;

		$this->$name = $value;

		switch ($name)
		{
			case 'sealed':
			case 'level':
			case 'locked':
			case 'hidden':
			case 'burn':
			case 'anonymous':
			case 'singleton':
			case 'indexed':
			case 'doc':
				$response->set(UI_THREAD_LOCK, $this->getLockIcon());
				$this->saveMe = true;
				break;

			case 'mod_name':
			case 'bookmarked':
			case 'subscribed';
				$response->set(UI_THREAD_SUB, $this->getSubIcon());
				break;

			case 'name':
				$response->set(UI_THREAD_NAME, $this->name);
				$this->saveMe = true;
				break;

			case 'mod_hash':
			case 'settings':
			case 'properties':
				$this->saveMe = true;
				break;
		}
	}

	public function save ()
	{
		$this->saveMe = false;

		$db = getDB();

		$sql =
		'INSERT INTO threads (
			hash,
			name,
			level,
			locked,
			sealed,
			hidden,
			burn,
			anonymous,
			singleton,
			indexed,
			doc,
			mod_hash
		)
		VALUES (
			:hash,
			:name,
			:level,
			:locked,
			:sealed,
			:hidden,
			:burn,
			:anon,
			:single,
			:indexed,
			:doc,
			:mod
		)
		ON DUPLICATE KEY UPDATE
		hash = :hash,
		name = :name,
		level = :level,
		locked = :locked,
		sealed = :sealed,
		hidden = :hidden,
		burn = :burn,
		anonymous = :anon,
		singleton = :single,
		indexed = :indexed,
		doc = :doc,
		mod_hash = :mod';

		$stmt = $db->prepare($sql);

		$stmt->bindParam(':hash',	$this->hash);
		$stmt->bindParam(':name',	$this->name);
		$stmt->bindParam(':level',	$this->level,		PDO::PARAM_INT);
		$stmt->bindParam(':locked',	$this->locked,		PDO::PARAM_INT);
		$stmt->bindParam(':sealed',	$this->sealed,		PDO::PARAM_INT);
		$stmt->bindParam(':hidden',	$this->hidden,		PDO::PARAM_INT);
		$stmt->bindParam(':burn',	$this->burn,		PDO::PARAM_INT);
		$stmt->bindParam(':anon',	$this->anonymous,	PDO::PARAM_INT);
		$stmt->bindParam(':single',	$this->singleton,	PDO::PARAM_INT);
		$stmt->bindParam(':indexed',$this->indexed,		PDO::PARAM_INT);
		$stmt->bindParam(':doc',	$this->doc,			PDO::PARAM_INT);
		$stmt->bindParam(':mod',	$this->mod_hash);

		$stmt->execute();

		$errorInfo = $stmt->errorInfo();
		if ($errorInfo[0] !== '00000')
		{
			global $response;

			$response->append(PANE_SYSTEM, json_encode($errorInfo));
//			throw new Exception("SQL ERROR {$errorInfo[0]}: {$errorInfo[2]}", E_USER_NOTICE);
		}
	}

	public function submitPost ($post, $re = null)
	{
		global $user;

		if (!$this->canPost(true)) return;

		$appName = $user->app;

		if ($appName) $appName::beforePost($post);

		if ($fc = floodControl())
		{
			$fc = getReadableTime($fc);
			notifyWarning("Flood control activated. You must wait <w>{$fc}</w> before you can post again.");
			return;
		}

		$username = $this->anonymous ? ANONYMOUS_NAME : $user->name;
		$nameTag = $this->anonymous ? 'x_' : $user->properties[PROP_NAMETAG];

		$db = getDB();

		$sql = 'INSERT INTO posts (thread_hash, body, timestamp, username, ip, nametag, re, user_hash)';
		$sql .= ' VALUES (:hash, :body, :timestamp, :username, :ip, :nametag, :re, :uhash)';

		$stmt = $db->prepare($sql);

		$stmt->execute([
			':hash'			=> $this->hash,
			':body'			=> $post,
			':timestamp'	=> $_SERVER['REQUEST_TIME'],
			':username'		=> $username,
			':ip'			=> $_SERVER['REMOTE_ADDR'],
			':nametag'		=> $nameTag,
			':re'			=> $re,
			':uhash'		=> $user->hash,
		]);

		$this->subscribe(true);

		if ($appName) $appName::afterPost($post);

		$user->checkPostPromotion();
	}

	public function canPost ($outputErrors = false)
	{
		global $user;

		if ($this->mod_hash === $user->hash) return true; // A mod can always post in their own thread.

		if ($this->locked)
		{
			if ($outputErrors) notifyError('Unable to post in this thread; this thread is locked.');
			return false;
		}

		if ($this->level > $user->level)
		{
			if ($outputErrors) notifyError("Unable to post in this thread; this thread is restricted to account levels <w>{$this->level}</w> and above.");
			return false;
		}

		if ($this->sealed)
		{
			$canPost = query('SELECT * FROM posts WHERE thread_hash = :thread AND user_hash = :user', [':thread' => $this->hash, ':user' => $user->hash], true);

			if (!$canPost)
			{
				if ($outputErrors) notifyError("Unable to post in this thread; this thread is sealed.");
				return false;
			}
		}

		return true;
	}

	public function getPosts ($lp = 0)
	{
		global $user;
		global $response;

		////////////////////////////////////////////////////////////////////////
		//
		//	GET POSTS
		//
		////////////////////////////////////////////////////////////////////////

		if (!$this->checkTimeCriticalConditions()) return;

		$limit = POSTS_FETCHED;

		$sql =
		'SELECT a.id, a.username, a.nametag, a.re, a.body, a.timestamp, a.deleted,
			(SELECT COUNT(*) FROM posts WHERE a.id = re) as replies,
			(SELECT COUNT(*) FROM edits WHERE post_id = a.id) as edits
		FROM posts a
		LEFT JOIN posts b
		ON b.id = a.re
		WHERE a.thread_hash = :thread
		AND (a.hidden IS NULL OR a.hidden = 0)
		AND a.id > :lastId
		ORDER BY a.id desc
		LIMIT :limit';

		$db = getDB();

		$stmt = $db->prepare($sql);

		$stmt->bindValue(':thread', $this->hash, PDO::PARAM_STR);
		$stmt->bindValue(':lastId', $lp, PDO::PARAM_STR);
		$stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);

		$stmt->execute();

		$results = $stmt->fetchAll();

		$results = array_reverse($results);

		$lastFetched = 0;
		foreach ($results as $post)
		{
			$body = $this->rowToPost($post);

			$response->append('main', $body, $post['timestamp'], $post['id']);
			$lastFetched = (int) $post['id'];
		}

		////////////////////////////////////////////////////////////////////////
		//
		//	UPDATE SUBSCRIPTION (IF EXISTS)
		//
		////////////////////////////////////////////////////////////////////////

		if ($lastFetched)
		{
			$sql = 'UPDATE subscriptions SET lastSeenPostId = :post_id WHERE user_hash = :u AND thread_hash = :t';
			query ($sql,
				[
	//				':post_id' => $this->latestFetchedPost,
	//				':post_id' => $lp,
					':post_id' => $lastFetched,
					':u' => $user->hash,
					':t' => $this->hash
				]
			);
		}

		return count($results) > 0;
	}

	function getOldPosts ($firstFetchedPost, $fetchFrom = null)
	{
		global $response;

		if (!$this->checkTimeCriticalConditions()) return;

		$firstFetchedPost = intval($firstFetchedPost);
		if ($fetchFrom) $fetchFrom = intval($fetchFrom);

		$db = getDB();

		$sql =
		'SELECT a.id, a.username, a.nametag, a.re, a.body, a.timestamp, a.deleted,
			(SELECT COUNT(*) FROM posts WHERE a.id = re) as replies,
			(SELECT COUNT(*) FROM edits WHERE post_id = a.id) as edits
		FROM posts a
		LEFT JOIN posts b
		ON b.id = a.re
		WHERE a.thread_hash = :thread ';
		if ($fetchFrom) $sql .= 'AND a.id >= :from ';
		$sql .= 'AND a.id < :to
		ORDER BY a.id DESC ';
		if (!$fetchFrom) $sql .= 'LIMIT :limit';



		$stmt = $db->prepare($sql);

		$stmt->bindValue(':thread', $this->hash, PDO::PARAM_STR);
		$stmt->bindValue(':to', $firstFetchedPost, PDO::PARAM_INT);

		if ($fetchFrom)		$stmt->bindValue(':from', $fetchFrom, PDO::PARAM_INT);
		if (!$fetchFrom)	$stmt->bindValue(':limit', SCROLL_FETCHED, PDO::PARAM_INT);

		$stmt->execute();

		$results = $stmt->fetchAll();

		$latestFetchedPost = false;

		foreach ($results as $post)
		{
			$body = $this->rowToPost($post);
			$response->prepend(PANE_MAIN, $body, $post['timestamp'], $post['id']);
			$latestFetchedPost = (int) $post['id'];
		}

		notifyDebug($sql);

		notifyDebug(":to = {$firstFetchedPost}");
		notifyDebug(":from = {$fetchFrom}");
		notifyDebug(':limit = ' . SCROLL_FETCHED);

		notifyDebug('Result count = ' . count($results));

		return $latestFetchedPost;
	}

	function checkTimeCriticalConditions ()
	{
		global $user;
		global $response;

		if ($this->hidden && !$this->canPost())
		{
			$response->set(PANE_MAIN, LOCKOUT_MESSAGE);
			return false;
		}

		if (isset($this->burn) && is_int($this->burn) && $this->burn < $_SERVER['REQUEST_TIME'])
		{

			query('UPDATE posts SET hidden = 1, deleted = 1 WHERE thread_hash = :thread', [':thread' => $this->hash]);
			query('DELETE FROM threads WHERE hash = :thread', [':thread' => $this->hash]);

			$response->set(PANE_MAIN, BURN_NOTICE);
			unset($_SESSION[SESS_BURN][$this->hash]);
			return false;
		}


		return true;
	}

	function rowToPost ($row)
	{
		$re = $row['re'] ? " <a onclick=\"fetchIfMissing({$row['re']})\" href=\"#p_{$row['re']}\">re:{$row['re']}</a>" : '';
		$body = $row['deleted'] ? DELETED_POST : cleanReply($row['body']);

		$html = "<span id=\"pn_{$row['id']}\"><{$row['nametag']}> {$row['username']}{$re} </{$row['nametag']}></span> <span id=\"pb_{$row['id']}\">{$body}</span>";

		$res = $row['replies'] ? " <span title=\"{$row['replies']} " . ($row['replies'] > 1 ? 'replies' : 'reply') ."\">" . ICO_REPLIES . " {$row['replies']}</span>" : '';
		$ed = $row['edits'] ? '<span title="Edited">' . ICO_EDITED . '</span> ' : '';
		$id = "<span onclick=\"addRe({$row['id']})\">{$row['id']}</span>{$res} @ ";
		$ts = getTimestamp($row['timestamp']);

		$html .= "<div class=\"ra\"><x>{$ed}{$id}{$ts}<x/></div>";

		return $html;
	}

	public function getLockIcon ()
	{
		global $user;

		$ico = '';

		$lock = $this->canPost() ? ICO_LOCK_OPEN : ICO_LOCK;

		if ($this->hidden)
		{
			$ico .= '<r title="Hidden">' . ICO_HIDDEN . '</r> ';
		}
		else if ($this->indexed)
		{
			$ico .= '<w title="Indexed">' . ICO_SEARCH . '</w> ';
		}

		if ($this->doc)
		{
			$ico .= '<w title="Document">' . ICO_DOC . '</w> ';
		}

		if ($this->mod_hash === $user->hash)
		{
			$lock = "<w>{$lock}</w>";
		}

		if ($this->locked)
		{
			$ico .= "<r title=\"Locked\">{$lock}</r> ";
		}
		elseif ($this->level)
		{
			$ico .= "<y title=\"Restricted\">{$lock}</y> ";
		}
		elseif ($this->sealed)
		{
			$ico .= "<b title=\"Sealed\">{$lock}</b> ";
		}

		if ($this->sealed)
		{
			$ico .= '<b title="Sealed">' . ICO_SEALED . '</b> ';
		}

		if ($this->level)
		{
			$ico .= '<y title="Restricted">' . ICO_STAR . $this->level . '</y> ';
		}

		if ($this->anonymous)
		{
			$ico .= '<w title="Anonymous">' . ICO_ANON . '</w> ';
		}

		if ($this->burn && $this->burn > $_SERVER['REQUEST_TIME_FLOAT'])
		{
			$timeLeft = getReadableTime($this->burn - $_SERVER['REQUEST_TIME']);
			$ico .= "<r title=\"{$timeLeft} (from time of thread load)\">" . ICO_BURNER . '</r> ';
		}

		if ($this->singleton)
		{
			$ico .= '<m title="Singleton">' . ICO_SINGLETON . '</m> ';
		}

		return $ico;
	}

	public function getSubIcon ()
	{
		$ico = '';


		if ($this->subscribed)
		{
			$ico .= ' <y title="Subscribed">' . ICO_SUB . '</y>';
		}

		if ($this->bookmarked)
		{
			$ico .= ' <r title="Bookmarked">' . ICO_BOOKMARK . '</r>';
		}

		if ($this->mod_name)
		{
			$ico .= " <w title=\"Moderator: {$this->mod_name}\">" . ICO_MOD . '</w>';
		}

		return $ico;
	}

	public function subscribe ($repliesOnly = false)
	{
		global $user;
		global $response;

		$repliesOnly = $repliesOnly ? 1 : 0;

		$sql =
		"SELECT repliesOnly,
			(SELECT COUNT(*)
			FROM posts
			WHERE user_hash = :user
			AND thread_hash = :thread) AS posts
		FROM subscriptions
		WHERE user_hash = :user
		AND thread_hash = :thread";

		$results = query($sql,[
			':user' => $user->hash,
			':thread' => $this->hash
		]);

		$subInfo = isset($results[0]) ? $results[0] : false;

		if (!$subInfo)
		{
			// No subscription exists.
			query(
			'INSERT INTO subscriptions (
				user_hash,
				thread_hash,
				repliesOnly
			)
			VALUES (
				:user,
				:thread,
				:ro
			)',
			[
				':user' => $user->hash,
				':thread' => $this->hash,
				':ro' => $repliesOnly
			]);

			$this->subscribed = $repliesOnly ? false : true;
		}
		else
		{
			$isRepliesOnly = intval($subInfo['repliesOnly']);
			$postsInThread = intval($subInfo['posts']);

			if ($repliesOnly)
			{
				// User either already has this subscription
				// or a heavier one so just leave.
				$this->subscribed = false;
			}
			else
			{

				if ($isRepliesOnly)
				{
					// Existing subscription is to replies only but we want the whole thing
					query(
					'UPDATE subscriptions
					SET repliesOnly = 0
					WHERE user_hash = :user
					AND thread_hash = :thread',
					[
						':user' => $user->hash,
						':thread' => $this->hash
					]);
					$this->subscribed = true;
				}
				else
				{
					if ($postsInThread)
					{
						// Existing subscription is to replies only but we want the whole thing
						query(
						'UPDATE subscriptions
						SET repliesOnly = 1
						WHERE user_hash = :user
						AND thread_hash = :thread',
						[
							':user' => $user->hash,
							':thread' => $this->hash
						]);
						$this->subscribed = false;
					}
					else
					{
						// This is where we delete the subscription.
						query('DELETE FROM subscriptions WHERE user_hash = :u AND thread_hash = :t', [':u' => $user->hash, ':t' => $this->hash]);
						$this->subscribed = false;
					}
				}
			}
		}

		$response->set(UI_THREAD_SUB, $this->getSubIcon());

		return $this->subscribed;
	}

	public function getLinks ()
	{
		global $response;

		$sql =
		'SELECT
			l.link_hash,
			t.name
		FROM links l
		LEFT JOIN threads t ON t.hash = l.link_hash
		WHERE l.thread_hash = :thread
		ORDER BY l.timestamp ASC';

		$results = query($sql, [':thread' => $this->hash]);

		$response->clear(PANE_LINKS);

		$ico = '<w>' . ICO_LINKS . '</w> ';

		foreach ($results as $result)
		{
			$name = $result['name'] ? $result['name'] : $result['link_hash'];

			$tag = "<a href=\"?t={$result['link_hash']}\"  title=\"{$name}\">{$ico}{$name}</a>";

			$response->append(PANE_LINKS, $tag);
		}

		if (!$results)
		{
			$response->append(PANE_LINKS, '<x>' . ICO_LINKS . ' No links</x>');
		}
	}
}