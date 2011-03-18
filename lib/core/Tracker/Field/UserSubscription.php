<?php

/**
 * Handler class for User Subscription
 * 
 * Letter key: ~U~
 *
 */
class Tracker_Field_UserSubscription extends Tracker_Field_Abstract
{
	function getFieldData(array $requestData = array())
	{
		global $user;
		$userlib = TikiLib::lib('user');
		$smarty = TikiLib::lib('smarty');

		$ins_id = $this->getInsertId();

		if (isset($requestData[$this->getInsertId()])) {
			$value = $requestData[$this->getInsertId()];
		} else {
			$value = $this->getValue();
		}
		$current_field_ins = $this->parseUsers($value);
		if (isset($requestData['user_subscribe'])) { // TODO: do only one time
			$found = false;
			$nb =  min($current_field_ins['maxsubscriptions'], intval($requestData['user_friends']));
			foreach ($current_field_ins['users_array'] as $i=>$U) {
				if ($U['login'] == $user) {
					$current_field_ins['users_array'][$i]['friends'] = $nb;
					$found = true;
					break;					
				}
			}
			if (!$found) {
				$userlib = TikiLib::lib('user');
				$temp = $userlib->get_user_info($user);
				$current_field_ins['users_array'][] = array('id' => $temp['userId'], 'login' => $user, 'friends' => $nb);
			}
			$value = $this->encodeUsers($current_field_ins);
			$this->save($value);
			$current_field_ins = $this->parseUsers($value);
		}
		if (isset($requestData['user_unsubscribe'])) { // TODO: do only one time
			foreach ($current_field_ins['users_array'] as $i=>$U) {
				if ($U['login'] == $user) {
					unset($current_field_ins['users_array'][$i]);
					$value = $this->encodeUsers($current_field_ins);
					$this->save($value);
					$current_field_ins = $this->parseUsers($value);
					break;
				}
			}
		}
		$U_liste = $this->parseShortcut($current_field_ins);
		$smarty->assign('U_liste', $U_liste);
		return $current_field_ins;
	}
	
	function renderInput($context = array())
	{
		return $this->renderTemplate('trackerinput/usersubscription.tpl', $context);
	}
	function renderOutput($context = array())
	{
		return $this->renderTemplate('trackeroutput/usersubscription.tpl', $context);
	}
	private function encodeUsers($current_field_ins)
	{
		$value =  $current_field_ins['maxsubscriptions'].'#';
		foreach ($current_field_ins['users_array'] as $i=>$U) {
			if (!empty($i))
				$value .= ',';
			$value .= $U['id'].'['.$U['friends'].']';
		}
		return $value;
	}
	private function parseUsers($value)
	{
		global $user;
		$userlib = TikiLib::lib('user');
		$current_field_ins['value'] = $value; // encoded value
		$temp = $userlib->get_user_info($user);
		$id_user = $temp['userId'];
		$pattern = "/(\d+)\[(\d+)\]/";
		preg_match_all($pattern, $value, $match);
		$users_array = array();
		$current_field_ins['user_subscription'] = false; // user is subscribed with firnd or not
		$current_field_ins['user_nb_users'] = 0; // total number of user attending
		$current_field_ins['user_nb_friends'] = 0; // total of friends for this user
		foreach($match[1] as $j => $id_user) {
			$temp = $userlib->get_userId_info($id_user);
			array_push($users_array, array(
					'id' => $id_user,
					'login' => $temp['login'],
					'friends' => $match[2][$j]
			));
			$current_field_ins['user_nb_users'] += $match[2][$j] + 1;
			if ($id_user == $id_tiki_user) {
				$current_field_ins['user_subscription'] = true;
				$current_field_ins['user_nb_friends'] = $match[2][$j];
			}
		}
		$current_field_ins['users_array'] = $users_array; // list user and subscriptions
		$current_field_ins['maxsubscriptions'] = substr($value, 0, strpos($value, '#'));
		return $current_field_ins;
	}
	private function parseShortcut($current_field_ins)
	{
		$U_liste = NULL;
		$U_othersubscriptions = $current_field_ins['user_nb_friends'];
		if (!$current_field_ins['user_subscription']) {
			$U_othersubscriptions--;
		}
		if ($current_field_ins['maxsubscriptions']) {
			for ($j = 0; $j <= $current_field_ins['maxsubscriptions'] - $current_field_ins['user_nb_users'] + $U_othersubscriptions; $j++) {
				$U_liste[$j] = $j;
			}
		}
		return $U_liste;
	}
	private function save($value)
	{
		$query = 'update `tiki_tracker_item_fields` set `value`=? where `itemId`=? and `fieldId`=?';
		TikiLib::lib('trk')->query($query, array($value, $this->getItemId(), $this->getConfiguration('fieldId')));
	}
}

