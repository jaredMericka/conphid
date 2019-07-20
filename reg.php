<?php

$rootPath = '';

require "{$rootPath}system/constants.php";

session_start();

$user = $_SESSION[SESS_USER];
//$thread = $_SESSION[SESS_THREAD];

$user_hash	= isset($_GET['u']) ? $_GET['u'] : null;
$email		= isset($_GET['e']) ? urldecode($_GET['e']) : null;
$conf		= isset($_GET['c']) ? $_GET['c'] : null;

$message	= '';
$redirect	= false;

if (isset($user_hash, $email, $conf))
{
	$trueConf = getEmailConfirmationCode($user_hash, $email);

	switch($conf)
	{
		case 'nomail':

			$mailURL = CONPHID_URL . "reg.php?u={$user_hash}&e=" . urlencode($email) . '&c=mail';

			$message = 'We apologise for this. ' . CONPHID . ' will not email you again without your explicit consent.'
				. '<br><br>'
				. "If this was not your intention, please click <a href=\"{$mailURL}\">HERE</a>.";
			query('INSERT INTO nomail (address) VALUES (:address)', [':address' => $email]);
			break;

		case 'mail':
			$message = 'You email (' . urldecode($email) . ') can now be used for ' . CONPHID . ' registration.';
			query('DELETE FROM nomail WHERE address = :address', [':address' => $email]);
			break;

		default:
			if ($trueConf === $conf)
			{
				$user = new User($user_hash);
				$thread = new Thread(DEFAULT_THREAD);

				if ($user->level > 0)
				{
					query('UPDATE users SET email = :email WHERE hash = :user', [':email' => $email, ':user' => $user->hash]);
					$user->promote(2);
					$user->save();
				}

				$_SESSION[SESS_USER] = $user;
//				$_SESSION[SESS_THREAD] = $thread;

				$redirect = true;
			}
			else
			{
				$message = 'Confirmation code incorrect. Please try again.';
			}
			break;
	}
}
else
{
	$redirect = true;
}


if ($redirect)
{
	$_SESSION[SESS_PENDING_RESPONSE] = $response;
	header('location:' . CONPHID_URL);
}
else
{
?>
<html>
	<head>
		<link rel="icon" type="image/png" href="favicon.png"/>
		<meta charset="UTF-8"/>
		<title>Con&phiv;d</title>
		<style>

			@font-face { font-family:SourceCodePro;	src:url('style/SourceCodePro.otf');	}
			@font-face { font-family:FontAwesome;	src:url('style/FontAwesome.otf');		}

			html, body, textArea, button
			{
				margin:0px;
				padding:0px;
				border:none;
				font-family:SourceCodePro;
				font-size:12px;
				color:#fff;
				text-shadow:0px 0px 10px #aaa;
			}

			ico
			{
				font-family:FontAwesome;
				display:inline-block;
				height:<?= CHAR_HEIGHT ?>px;
				width:<?= CHAR_WIDTH * 2 ?>px;
				text-align:center;
			}

			b {font-weight:normal;}
			a {color:inherit;text-decoration:inherit;}

			body
			{
				background-color:#000;
				box-shadow:
					0px 0px 80px #222 inset,
					0px 0px 1px #fff inset;
				text-align:center;
			}

			#body
			{
				text-align:left;
				display:inline-block;
				width:80%;
				margin-top:10%;
			}

			#title
			{
				font-size:40px;
			}

			#message
			{
				margin-top:<?= CHAR_HEIGHT * 3; ?>px;
				text-align:center;
			}

			r,.r,.R	{ color:#f88; background:none; text-shadow:0px 0px 10px #a00; }
			g,.g,.G	{ color:#8f8; background:none; text-shadow:0px 0px 10px #0a0; }
			b,.b,.B	{ color:#88f; background:none; text-shadow:0px 0px 10px #00a; }
			m,.m,.M	{ color:#f8f; background:none; text-shadow:0px 0px 10px #a0a; }
			c,.c,.C	{ color:#8ff; background:none; text-shadow:0px 0px 10px #0aa; }
			y,.y,.Y	{ color:#ff8; background:none; text-shadow:0px 0px 10px #aa0; }

			x,.x	{ color:#555; background:none; text-shadow:0px 0px 10px #333; }
			w,.w	{ color:#fff; background:none; text-shadow:0px 0px 10px #aaa; }

			r_,.r_	{ color:#000; background-color:#f88; box-shadow:0px 0px 10px #a00; text-shadow:none; }
			g_,.g_	{ color:#000; background-color:#8f8; box-shadow:0px 0px 10px #0a0; text-shadow:none; }
			b_,.b_	{ color:#000; background-color:#88f; box-shadow:0px 0px 10px #00a; text-shadow:none; }
			m_,.m_	{ color:#000; background-color:#f8f; box-shadow:0px 0px 10px #a0a; text-shadow:none; }
			c_,.c_	{ color:#000; background-color:#8ff; box-shadow:0px 0px 10px #0aa; text-shadow:none; }
			y_,.y_	{ color:#000; background-color:#ff8; box-shadow:0px 0px 10px #aa0; text-shadow:none; }

			x_,.x_	{ color:#000; background-color:#555; box-shadow:0px 0px 10px #333; text-shadow:none; }
			w_,.w_	{ color:#000; background-color:#fff; box-shadow:0px 0px 10px #aaa; text-shadow:none; }

			h,.h	{ color:#000; background-color:#000; box-shadow:0px 0px 05px #555; text-shadow:none; }

			h:hover,.h:hover
			{ color:inherit; background-color:inherit; box-shadow:0px 0px 10px #aaa; text-shadow:inherit; }

		</style>
	</head>
	<body>
		<div id="body">
			<span id="title"><?= CONPHID ?></span>
			<div id="message">
				<?= $message ?>
			</div>
		</div>
	</body>
</html>
<?php }