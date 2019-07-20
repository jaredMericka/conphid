<?php if (false) { ?><style><?php }

header('Content-type: text/css');
header('Cache-Control: must-revalidate');
$offset = 72000 ;
$ExpStr = "Expires: " . gmdate('D, d M Y H:i:s', time() + $offset) . ' GMT';
header($ExpStr);

const CHAR_WIDTH	= 7.2;
const CHAR_HEIGHT	= 15;

?>

@font-face { font-family:SourceCodePro;	src:url('SourceCodePro-Regular.otf'); }
@font-face { font-family:SourceCodePro;	src:url('SourceCodePro-Semibold.otf'); font-weight:bold; }
@font-face { font-family:FontAwesome;	src:url('FontAwesome.otf'); }

/*
{
	transition:
		color 0.2s,
		background-color 0.2s,
		text-shadow 0.2s,
		box-shadow 0.2s
		;
}

/**/

*[onclick]
{
	cursor:pointer;
}

html, body, textArea, button
{
	margin:0px;
	padding:0px;
	border:none;
	font-family:SourceCodePro, monospace;
	font-size:12px;
}

ico
{
	font-weight:normal;
	font-family:FontAwesome;
	display:inline-block;
	height:<?= CHAR_HEIGHT ?>px;
	width:<?= CHAR_WIDTH * 2 ?>px;
	text-align:center;
	line-height:<?= CHAR_HEIGHT ?>px;
	font-size:14px;
	vertical-align:-1px;
}

b {font-weight:normal;}
a {color:inherit;text-decoration:inherit;}

hr
{
	width:40%;
	height:3px;
	border-radius:3px;
	border:none;
	margin:<?=CHAR_HEIGHT *2?>px auto;
}

hr.doc
{
	width:80%;
}

span[onclick]
{
	cursor:pointer;
}

#input,
#header,
#main,
#system,
#notifications,
#bookmarks,
#links,
#feed,
#threadName,
#app
{
	padding:0px <?= CHAR_WIDTH; ?>px;
}

#main,
#system,
#bookmarks,
#links,
#feed,
#notifications
{
	white-space:pre-wrap;
	overflow-y:auto;
	overflow-x:hidden;
}

#header
{
	position:fixed;
	top:0px;
	left:0px;
	right:0px;
	height:<?= 2*CHAR_HEIGHT; ?>px;
}

#header_lhs,
#header_rhs
{
	display:inline-block;
	position:absolute;
}

#header_lhs
{

}

#header_rhs
{
	right:0px;
}

#header_rhs>div
{
	display:inline-block;
	top:0px;
	vertical-align:top;
}

#header_rhs .x_
{
	border-radius:0px;
	height:<?=2*CHAR_HEIGHT?>px;
}

#settings
{
	position:absolute;
	left:<?=CHAR_WIDTH * 20?>px;
}

#settings span:hover
{
	color:#fff;
}

#headerTop,
#headerBottom
{
	height:<?= CHAR_HEIGHT; ?>px;
	width:100%;
}

#body
{
	position:fixed;
	top:<?= 2*CHAR_HEIGHT; ?>px;
	left:0px;
	right:0px;
	bottom:0px;
}

#mainContainer
{
	position:absolute;
	top:0px;
	bottom:0px;
	left:0px;
	width:61.8%;
}

#main_outerDock
{
	position:absolute;
	top:0px;
	bottom:<?= CHAR_HEIGHT * 3 ?>px;
	left:0px;
	right:0px;
}

#main
{
	position:absolute;
	top:<?= CHAR_HEIGHT ?>px;
	bottom:0px;
	left:0px;
	right:0px;
}

#threadName
{
	position:absolute;
	top:0px;
	height:<?= CHAR_HEIGHT ?>px;
	left:0px;
	right:0px;
	text-align:center;
}

#threadSub
{
	float:right;
}

#inputContainer
{
	position:absolute;
	bottom:0px;
	left:0px;
	right:<?= (12 * CHAR_WIDTH) + (2 * CHAR_WIDTH); //Button width + margins ?>px;
	height:<?= CHAR_HEIGHT * 3; ?>px;
}

#input
{
	position:absolute;
	bottom:0px;
	left:0px;
	right:0px;
	height:100%;
	width:100%;
	resize:none;
	background-color:rgba(0,0,0,0);
}

#tips
{
	display:inline-block;
	margin-left:<?= CHAR_WIDTH ?>px;
}

#submit
{
	position:absolute;
	bottom:0px;
	right:0px;
	width:<?= 12 * CHAR_WIDTH; ?>px;
	height:<?= CHAR_HEIGHT * 3; ?>px;
}

#charCount
{
	position:absolute;
	bottom:0px;
	width:100%;
}

#auxContainer
{
	position:absolute;
	top:0px;
	bottom:0px;
	right:0px;
	width:38.2%;
}

#systemContainer
{
	position:absolute;
	height:61.8%;
	bottom:0px;
	left:0px;
	right:0px;
}

#system_outerDock
{
	position:absolute;
	top:0px;
	bottom:0px;
	left:0px;
	right:0px;
}

#system
{
	position:absolute;
	top:<?= CHAR_HEIGHT ?>px;
	bottom:0px;
	left:0px;
	right:0px;

}

#userContainer
{
	position:absolute;
	top:0px;
	right:0px;
	left:0px;
	height:38.2%;
}

#listsContainer
{
	position:absolute;
	top:0px;
	bottom:0px;
	left:0px;
	width:38.2%;
}

#notificationsContainer
{
	position:absolute;
	top:0px;
	bottom:0px;
	right:0px;
	width:61.8%;
}

#bookmarks,
#links,
#feed
{
	position:absolute;
	top:0px;
	left:0px;
	right:0px;
	bottom:<?=CHAR_HEIGHT?>px;
	white-space:nowrap;
}

#bookmarks, #links { display:none; }

#listTabs
{
	position:absolute;
	bottom:0px;
	left:0px;
	right:0px;
	height:<?=CHAR_HEIGHT?>px;
}

#linksTab,
#bookmarksTab,
#feedTab
{
	display:inline-block;
	height:100%;
	width:33%;
	text-align:center;
	vertical-align:top;
	cursor:pointer;
	border-radius:0px;
}

#linksTab { width:34%; }

#notifications
{
	position:absolute;
	top:0px;
	left:0px;
	right:0px;
	bottom:0px;
}

#expand
{
	position:absolute;
	display:inline-block;
	top:0px;
	right:-<?=2*CHAR_WIDTH?>px;
	cursor:pointer;
}

/****************************************************

	OTHER STUFF

****************************************************/

span.ra
{
	position:absolute;
	right:<?= CHAR_WIDTH; ?>px;
}

div.ra
{
	text-align:right;
}

#app
{
	position:absolute;
	width:100%;
	text-align:center;
	left:0px;
	right:0px;
}

.meta
{
	text-align:right;
}

#main_outerDock,
#inputContainer,
#submit
{

	transition:bottom 0.2s, height 0.2s;
}

/*#main.resized*/
#main_outerDock.resized
{
	bottom:<?=20*CHAR_HEIGHT?>px;
}

#inputContainer.resized,
#submit.resized
{
	height:<?=20*CHAR_HEIGHT?>px;
}

*::-webkit-scrollbar
{
	width:4px;
}

*::-webkit-scrollbar-button
{
	height:0px;
}
