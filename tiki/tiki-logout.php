<?php

// $Header: /cvsroot/tikiwiki/tiki/tiki-logout.php,v 1.27 2007-05-31 09:42:56 nyloth Exp $

// Copyright (c) 2002-2007, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// Initialization
$bypass_siteclose_check = 'y';
require_once ('tiki-setup.php');

// go offline in Live Support
if ($feature_live_support == 'y') {
	include_once ('lib/live_support/lslib.php');

	if ($lslib->get_operator_status($user) != 'offline') {
		$lslib->set_operator_status($user, 'offline');
	}
}

setcookie($user_cookie_site, '', -3600, $cookie_path, $cookie_domain);
$userlib->delete_user_cookie($user,'cookie','');
$userlib->user_logout($user);
$logslib->add_log('login','logged out');		
session_unregister ('user');
unset ($_SESSION[$user_cookie_site]);
session_destroy();

/* change group home page or desactivate if no page is set */
if ( ($groupHome = $userlib->get_group_home('Anonymous')) != '' ) $url = ( preg_match('/^(\/|https?:)/', $groupHome) ) ? $groupHome : 'tiki-index.php?page='.$groupHome;
else $url = $tikilib->get_preference('tikiIndex', 'tiki-index.php');

if ($phpcas_enabled == 'y' and $tikilib->get_preference('auth_method', 'tiki') == 'cas' and $user != 'admin') {
	require_once('phpcas/source/CAS/CAS.php');
	phpCAS::client($cas_version, "$cas_hostname", (int) $cas_port, "$cas_path");
	phpCAS::logout();
}

// RFC 2616 defines that the 'Location' HTTP headerconsists of an absolute URI
if ( ! eregi('/^https?\:/', $url) ) $url = ( ereg('^/', $url) ? $url_scheme.'://'.$url_host.(($url_port!='')?":$url_port":'') : $base_url ).$url;

if ( SID ) $url .= '?'.SID;
header('Location: '.$url);
exit;
?>
