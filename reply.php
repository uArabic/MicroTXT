<?php
/*
MicroTXT - A tiny PHP Textboard Software
Copyright (c) 2016 Kevin Froman (https://ChaosWebs.net/)

MIT License
*/
include('php/settings.php');
include('php/csrf.php');

function tripcode($tripcode)
{
	if ($tripcode == '')
	{
		return '';
	}
	else
	{
		return hash('sha256', $tripcode . $salt);
	}
}


// Redirect if some BS is going on
function redirectError()
{
	setcookie('microtxterror', 'true', time()+3600);
	header('location: index.php');
	die(0);
}

if (! isset($_POST['text']) || ! isset($_POST['CSRF']) || ! isset($_POST['name']) || ! isset($_POST['threadID']) || ! isset($_POST['replyTo']) || ! isset($_POST['tripcode']))
{
	redirectError();
}

if ($_POST['CSRF'] != $_SESSION['CSRF'])
{
	redirectError();
}


if ($captcha)
{
	if (! isset($_SESSION['currentPosts']))
	{
		$_SESSION['currentPosts'] = $postsBeforeCaptcha;
	}
	if ($_SESSION['currentPosts'] >= $postsBeforeCaptcha)
	{
		if (! isset($_POST['captcha']))
		{
			redirectError();
		}
		if ($_POST['captcha'] != $_SESSION['captchaVal'])
		{
			redirectError();
		}
		else
		{
			$_SESSION['currentPosts'] = 0;
		}
	}
}

$replyTo = $_POST['replyTo'];

if ($replyTo == '')
{
	redirectError();
}

$threadID = $_POST['threadID'];

$threadFile = 'posts/' . $threadID . '.html';

if (! file_exists($threadFile))
{
	die('The thread you are replying in does not exist');
}


// Get user data

$text = $_POST['text'];
$name = $_POST['name'];
$tripcode = $_POST['tripcode'];

// html encode user data to prevent xss
$text = htmlentities($text);
$name = htmlentities($name);
$tripcode = htmlentities($tripcode);
if (strlen($_POST['text']) > 100000 || strlen($_POST['name'] > 20) || strlen($_POST['tripcode']) > 100)
{
	redirectError();
}


// Generate Post ID
$postID = time();

$doc = new DOMDocument;
$doc->loadHtmlFile($threadFile);
$parent = $doc->getElementById($replyTo);

if ($parent == null)
{
	die('Post id not found');
}

$child = $doc->createElement('div', $name);
$child->setAttribute( 'class', 'name');

$parent->appendChild( $child);

$tripcode = tripcode($tripcode);

if ($tripcode != '')
{
	$child = $doc->createElement('input');
	$child->setAttribute( 'class', 'tripcode');
	$child->setAttribute( 'type', 'text');
	$child->setAttribute( 'value', $tripcode);

	$parent->appendChild($child);
}

$parent->appendChild( $child);

$child = $doc->createElement('div', $postID);
$child->setAttribute( 'class', 'postID');
$child->setAttribute( 'onClick', 'javascript: clickItem(\'' . $postID . '\');');

$parent->appendChild( $child);

$child = $doc->createElement('div', '>> ' . $replyTo);
$child->setAttribute( 'class', 'replyTo');
$child->setAttribute( 'onClick', 'javascript: clickItem(\'' . $replyTo . '\');');

$parent->appendChild( $child);

$child = $doc->createElement('div', $text);
$child->setAttribute( 'class', 'post');

$parent->appendChild( $child);

$child = $doc->createElement('div');
$child->setAttribute( 'id', $postID);
$child->setAttribute( 'class', 'hiddenPostID');

$parent->appendChild( $child);

// Write html to thread file

file_put_contents($threadFile, nl2br($doc->saveHTML(), $false));

// If captcha is to be used, increment the user session post count
if ($captcha)
{
	$_SESSION['currentPosts'] = $_SESSION['currentPosts'] + 1;
}


header('location: view.php?post=' . $threadID);

?>
