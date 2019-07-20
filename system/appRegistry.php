<?php

class AppEntry
{
	public $file;
	public $name;
	public $description;
	public $level;

	public function __construct($file, $name, $description, $level)
	{
		$this->file			= $file;
		$this->name			= $name;
		$this->description	= $description;
		$this->level		= $level;
	}
}

$appRegistry = [
	new AppEntry(
		'settings',
		'User Settings',
		'Account management app, facilitates account registration and modification of account settings.',
		0),

	new AppEntry(
		'admin',
		'Thread Admin Tools',
		'Thread management app, allows access restrictions to be placed on threads.',
		0),

	new AppEntry(
		'mod',
		'Moderator Tools',
		'Commands to aid with top level site adminstration.',
		9),

	new AppEntry(
		'devtools',
		'Development Tools',
		'Commands for testing various aspects of the site. Should not be used in production environments.',
		9),
];