<?php

if (strpos($_SERVER["SCRIPT_NAME"], basename(__FILE__)) !== false) {
	header("location: index.php");
	exit;
}

function smarty_modifier_in_group($group) {
	global $user;
	return TikiLib::lib('user')->user_is_in_group($user, $group);
}
