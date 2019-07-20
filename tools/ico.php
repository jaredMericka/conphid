<html>
	<head>
		<title>FontAwesome Viewer</title>
		<style>
			@font-face { font-family:FontAwesome; src:url('../style/FontAwesome.otf'); }

			html
			{
				font-family:FontAwesome;
			}

			div
			{
				float:left;
				position:relative;
				border: 2px solid grey;
				border-radius: 10px;
				margin:5px;
				width:80px;
				height:90px;
			}

			div span.glyph
			{
				position:absolute;
				top:10px;
				width:100%;
				text-align:center;

				font-size:40px;
			}

			div span.code
			{
				position:absolute;
				bottom:10px;
				width:100%;
				display:block;
				font-family:monospace;
				font-size:14px;
				padding-top:20px;
				text-align:center;
			}

			div:hover
			{
				background-color:black;
				color:white;
			}

			div:hover span.glyph
			{
				top:20px;
				font-size:12px;
			}
		</style>
	</head>
	<body>
		<?php

		$chars = str_split('0123456789abcdef');

		$limit = 700;
		$count = 0;

		foreach ($chars as $a)
		{
			foreach ($chars as $b)
			{
				foreach ($chars as $c)
				{
					$count++;
					$code = "f{$a}{$b}{$c}";

					?>
						<div>
							<span class="glyph">&#x<?= $code ?></span>
							<span class="code">&amp;#x<?= $code ?>;</span>
						</div>
					<?php

					if ($count > $limit) exit();

				}
			}
		}

		?>
	</body>
</html>