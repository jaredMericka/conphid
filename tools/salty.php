<html>
	<head>
		<title>Salty</title>
		<style>
			html,
			body
			{
				padding:0px;
				margin:0px;
			}

			textArea
			{
				height:100%;
				width:100%;
				font-family:monospace;
				padding:200px 400px;
				background:black;
				color:white;
				word-break:break-all;
				word-wrap:break-word;
			}
		</style>
	</head>
	<body>
		<textarea><?php

		$chars = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890~`!@#$%^&*()_+-={}|[]\\:";<>?,./ ');

		for ($i = 0; $i < 200; $i++)
		{
			shuffle($chars);
			echo $chars[array_rand($chars)];
		}

		?></textarea>
	</body>
</html>