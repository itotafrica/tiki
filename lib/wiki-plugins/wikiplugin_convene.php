<?php
// (c) Copyright 2002-2016 by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

function wikiplugin_convene_info()
{
	return [
		'name' => tra('Convene'),
		'documentation' => 'PluginConvene',
		'description' => tra('Agree a date from a list of alternatives'),
		'introduced' => 9,
		'prefs' => ['wikiplugin_convene','feature_calendar'],
		'body' => tra('Convene data generated from user input'),
		'iconname' => 'group',
		'filter' => 'rawhtml_unsafe',
		'tags' => [ 'basic' ],
		'params' => [
			'title' => [
				'required' => false,
				'name' => tra('Title'),
				'description' => tra('Title for the event'),
				'since' => '9.0',
				'default' => tra('Convene'),
			],
			'calendarid' => [
				'required' => false,
				'name' => tra('Calendar ID'),
				'description' => tra('ID number of the site calendar in which to store the date for the events with the most votes'),
				'since' => '9.0',
				'filter' => 'digits',
				'default' => '',
				'profile_reference' => 'calendar',
			],
			'minvotes' => [
				'required' => false,
				'name' => tra('Minimum Votes'),
				'description' => tra('Minimum number of votes needed to show Add-to-Calendar icon, so that new users do
					not see a potentially confusing icon before the convene has enough information on it'),
				'since' => '10.3',
				'filter' => 'digits',
				'default' => '3',
			],
			'dateformat' => [
				'required' => false,
				'name' => tra('Date-Time Format'),
				'description' => tra('Display date and time in short or long format, according to the site wide setting'),
				'since' => '9.0',
				'filter' => 'alpha',
				'default' => '',
				'options' => [
					['text' => '', 'value' => ''],
					['text' => tra('Short'), 'value' => 'short'],
					['text' => tra('Long'), 'value' => 'long']
				]
			],
		]
	];
}

function wikiplugin_convene($data, $params)
{
	global $page;
	$headerlib = TikiLib::lib('header');
	$tikilib = TikiLib::lib('tiki');
	$smarty = TikiLib::lib('smarty');
	$smarty->loadPlugin('smarty_function_icon');
	$perms = Perms::get();

	static $conveneI = 0;
	++$conveneI;
	$i = $conveneI;

	$params = array_merge(
		[
			"title" => "Convene",
			"calendarid" => "1",
			"minvotes" => "3",
			"dateformat" => "short"
		],
		$params
	);

	extract($params, EXTR_SKIP);

	$dataString = $data . '';
	$dataArray = [];

	//start flat static text to prepared array
	$lines = explode("\n", trim($data));
	sort($lines);
	foreach ($lines as $line) {
		$line = trim($line);

		if (! empty($line)) {
			$parts = explode(':', $line);
			$dataArray[trim($parts[0])] = trim($parts[1]);
		}
	}

	$data = TikiFilter_PrepareInput::delimiter('_')->prepare($dataArray);
	//end flat static text to prepared array

	//start get users from array
	$users = [];
	foreach (end($data['dates']) as $user => $vote) {
		$users[] = $user;
	}
	//end get users from array


	//start votes summed together
	$votes = [];
	foreach ($data['dates'] as $stamp => $date) {
		foreach ($date as $vote) {
			if (empty($votes[$stamp])) {
				$votes[$stamp] = 0;
			}
			$votes[$stamp] += (int)$vote;
		}
	}
	//end votes summed together


	//start find top vote stamp
	$topVoteStamp = 0;
	foreach ($votes as $stamp => $vote) {
		if (! isset($votes[$topVoteStamp]) || (
				isset($votes[$topVoteStamp]) &&
				$vote > $votes[$topVoteStamp]
			)
		) {
			$topVoteStamp = $stamp;
		}
	}
	//end find top vote stamp


	//start reverse array for easy listing as table
	$rows = [];
	foreach ($data['dates'] as $stamp => $date) {
		foreach ($date as $user => $vote) {
			if (isset($rows[$user][$stamp])) {
				$rows[$user][$stamp] = [];
			}

			$rows[$user][$stamp] = $vote;
		}
	}
	//end reverse array for easy listing as table

	$result = "";

	//start date header
	$dateHeader = "";
	$deleteicon = smarty_function_icon(['name' => 'delete', 'iclass' => 'tips', 'ititle' => ':' . tr('Delete Date')], $smarty);
	$tikiDate = new TikiDate();
	$gmformat = str_replace($tikiDate->search, $tikiDate->replace, $tikilib->get_short_datetime_format());
	foreach ($votes as $stamp => $totals) {
		$dateHeader .= '<td class="conveneHeader"><span class="tips" title="' . tr('UTC date time: %0', gmdate($gmformat, $stamp)) . '">';
		if (! empty($dateformat) && $dateformat == "long") {
			$dateHeader .= $tikilib->get_long_datetime($stamp);
		} else {
			$dateHeader .= $tikilib->get_short_datetime($stamp);
		}
		$dateHeader .= '</span>';
		$dateHeader .= ($perms->edit ? " <button class='conveneDeleteDate$i icon btn btn-default btn-xs' data-date='$stamp'>$deleteicon</button>" : "") . "</td>";
	}
	$result .= "<tr class='conveneHeaderRow'>";

	$result .= "<td style='vertical-align: middle'>" . (
		$perms->edit
			?
				"<input type='button' class='conveneAddDate$i btn btn-default btn-sm' value='" . tr('Add Date') . "'/>"
			: ""
	) . "</td>";

	$result .= "$dateHeader
		</tr>";
	//end date header


	//start user list and votes
	$userList = "";
	foreach ($rows as $user => $row) {
		$userList .= "<tr class='conveneVotes conveneUserVotes$i'>";
		$userList .= "<td style='white-space: nowrap'>" . ($perms->edit ? "<button class='conveneUpdateUser$i icon btn btn-default btn-sm'>"
				. smarty_function_icon(['name' => 'pencil', 'iclass' => 'tips', 'ititle' => ':'
					. tr("Edit User/Save changes")], $smarty)
				. "</button><button data-user='$user' title='" . tr("Delete User")
				. "' class='conveneDeleteUser$i icon btn btn-danger btn-sm'>"
				. smarty_function_icon(['name' => 'delete'], $smarty) . "</button> " : "") . TikiLib::lib('user')->clean_user($user) . "</td>";
		foreach ($row as $stamp => $vote) {
			if ($vote == 1) {
				$class = "convene-ok text-center label-success";
				$text = smarty_function_icon(['name' => 'ok', 'iclass' => 'tips', 'ititle' => ':' . tr('OK')], $smarty);
			} elseif ($vote == -1) {
				$class = "convene-no text-center label-danger";
				$text = smarty_function_icon(['name' => 'remove', 'iclass' => 'tips', 'ititle' => ':'
					. tr('Not OK')], $smarty);
			} else {
				$class = "convene-unconfirmed text-center label-default";
				$text = smarty_function_icon(['name' => 'help', 'iclass' => 'tips', 'ititle' => ':'
					. tr('Unconfirmed')], $smarty);
			}

			$userList .= "<td class='$class'>" . $text
				. "<input type='hidden' name='dates_" . $stamp . "_" . $user . "' value='$vote' class='conveneUserVote$i form-control' />"
				. "</td>";
		}
		$userList .= "</tr>";
	}
	$result .= $userList;
	//end user list and votes


	//start add new user and votes
	$result .= "<tr class='conveneFooterRow'>";


	if (! empty($data['dates'])) {	// need a date before adding users
		$result .= "<td>" . (
			$perms->edit
				?
				"<div class='btn-group'>
						<input class='conveneAddUser$i form-control' value='' placeholder='" . tr("Username...") . "' style='float:left;width:72%;border-bottom-right-radius:0;border-top-right-radius:0;'>
						<input type='button' value='+' title='" . tr('Add User') . "' class='conveneAddUserButton$i btn btn-default' />
					</div>"
				: ""
			) .
			"</td>";
	}
	//end add new user and votes


	//start last row with auto selected date(s)
	$lastRow = "";
	foreach ($votes as $stamp => $total) {
		$pic = "";
		if ($total == $votes[$topVoteStamp]) {
			$pic .= ($perms->edit ? smarty_function_icon(['name' => 'ok', 'iclass' => 'tips', 'ititle' => ':'
					. tr("Selected Date")], $smarty) : "");
			if ($perms->edit && $votes[$topVoteStamp] >= $minvotes) {
				$pic .= "<a class='btn btn-default btn-xs' href='tiki-calendar_edit_item.php?todate=$stamp&calendarId=$calendarid' title='"
					. tr("Add as Calendar Event") . "'>"
					. smarty_function_icon(['name' => 'calendar'], $smarty)
					. "</a>";
			}
		}

		$lastRow .= "<td class='conveneFooter'>" . $total . "&nbsp;$pic</td>";
	}
	$result .= $lastRow;

	$result .= "</tr>";
	//end last row with auto selected date(s)


	$result = <<<FORM
			<form id='pluginConvene$i'>
			    <div class="table-responsive">
    				<table class="table table-bordered">$result</table>
    		    </div>
			</form>
FORM;

	$conveneData = json_encode(
		[
			"dates" => $data['dates'],
			"users" => $users,
			"votes" => $votes,
			"topVote" => $votes[$topVoteStamp],
			"rows" => $rows,
			"data" => $dataString,
		]
	);

	$n = '\n';
	$regexN = '/[\r\n]+/g';

	$access = TikiLib::lib('access');
	$access->checkAuthenticity();
	$ticket = $access->getTicket();

	$headerlib->add_jq_onready(
		/** @lang JavaScript */
		<<<JQ

		var convene$i = $.extend({
			fromBlank: function(user, date) {
				if (!user || !date) return;
				this.data = "dates_" + Date.parseUnix(date) + "_" + user;
				this.save();
			},
			updateUsersVotes: function() {
				var data = [];
				$('.conveneUserVotes$i').each(function() {
					$('.conveneUserVote$i').each(function() {
						data.push($(this).attr('name') + ' : ' + $(this).val());
					});
				});

				this.data = data.join('$n');

				this.save();
			},
			addUser: function(user) {
				if (!user) return;

				var data = [];

				for(date in this.dates) {
					data.push("dates_" + date + "_" + user);
				}

				this.data += '$n' + data.join('$n');

				this.save();
			},
			deleteUser: function(user) {
				if (!user) return;
				var data = '';

				for(date in this.dates) {
					for(i in this.users) {
						if (this.users[i] != user) {
							data += 'dates_' + date + '_' + this.users[i] + ' : ' + this.dates[date][this.users[i]] + '$n';
						}
					}
				}

				this.data = data;

				this.save(true);
			},
			addDate: function(date) {
				if (!date) return;
				date = Date.parseUnix(date);
				var addedData = '';

				if (! this.users.length) {	// add current user if it's the first
					this.users.push(jqueryTiki.username);
				}
				for(var user in this.users) {
					if (this.users.hasOwnProperty(user)) {
						addedData += 'dates_' + date + '_' + this.users[user] + ' : 0$n';
					}
				}

				this.data = (this.data + '$n' + addedData).split($regexN).sort();

				//remove empty lines
				for(var line in this.data) {
					if (this.data.hasOwnProperty(line)) {
						if (!this.data[line]) this.data.splice(line, 1);
					}
				}

				this.data = this.data.join('$n');

				this.save();
			},
			deleteDate: function(date) {
				if (!date) return;
				date += '';
				var addedData = '';

				for(user in this.users) {
					addedData += 'dates_' + date + '_' + this.users[user] + ' : 0$n';
				}

				var lines = convene$i.data.split($regexN);
				var newData = [];
				for(var line in lines) {
					if (lines.hasOwnProperty(line)) {
						if (!(lines[line] + '').match(date)) {
							 newData.push(lines[line]);
						}
					}
				}

				this.data = newData.join('$n');
				this.save();
			},
			save: function(reload) {
				$("#page-data").tikiModal(tr("Loading..."));
				var content = $.trim(this.data);
				
				// do semaphore check and set
				$.getJSON($.service("semaphore", "is_set"), {
						object_type: jqueryTiki.current_object.type,
						object_id: jqueryTiki.current_object.object
					}, function (data) {
						if (data) {
							$("#tikifeedback").showError(tr("This page is being edited by another user. Please reload the page and try again later."));
							$("#page-data").tikiModal();
						} else {
							// no one else using it, so carry on...

							$.getJSON($.service("semaphore", "set"), {
								object_type: jqueryTiki.current_object.type,
								object_id: jqueryTiki.current_object.object
							});
				

							var needReload = reload != undefined;
							var params = {
								page: "$page",
								content: content,
								index: $i,
								type: "convene",
								ticket: "$ticket",
								daconfirm: "y",
								params: {
									title: "$title",
									calendarid: $calendarid,
									minvotes: $minvotes
								}
							};
							$.post($.service("plugin", "replace"), params, function() {
								$.get($.service("wiki", "get_page", {page: "$page"}), function (data) {
									// unset semaphore
									$.getJSON($.service("semaphore", "unset"), {
										object_type: jqueryTiki.current_object.type,
										object_id: jqueryTiki.current_object.object
									});
									
									if (needReload) {
										history.go(0);
									} else {
										if (data) {
											var newForm = $("#pluginConvene$i", data);
											$("#pluginConvene$i", "#page-data").replaceWith(newForm);
										}
										initConvene$i();
										$("#page-data").tikiModal();
									}
								});
			
							})
							.fail(function (jqXHR) {
								$("#tikifeedback").showError(jqXHR);
							})
							.always(function () {
								$.getJSON($.service("semaphore", "unset"), {
									object_type: jqueryTiki.current_object.type,
									object_id: jqueryTiki.current_object.object
								});
								$("#page-data").tikiModal();
							});
						}
					});
				}
		}, $conveneData);

		var initConvene$i = function () {
			$('.conveneAddDate$i').click(function() {
				var dialogOptions = {
					modal: true,
					title: tr("Add Date"),
					buttons: {}
				};

				dialogOptions.buttons[tr("Add")] = function() {
					convene$i.addDate(o.find('input:first').val());
					o.dialog('close');
				};

				var o = $('<div><input type="text" style="width: 100%;" /></div>')
					.dialog(dialogOptions);

				o.find('input:first')
					.datetimepicker()
					.focus();
				return false;
			});

			$('.conveneDeleteDate$i')
				.click(function() {
					convene$i.deleteDate($(this).data("date"));
					return false;
				});

			$('.conveneDeleteUser$i')
				.click(function() {
					if (confirm(tr("Are you sure you want to remove this user's votes?") + "\\n" +
							tr("There is no undo"))) {
						convene$i.deleteUser($(this).data("user"));
					}
					return false;
				});

			$('.conveneUpdateUser$i').click(function() {
				if ($('.conveneDeleteUser$i:visible').length) {
					$(this).find(".icon").popover("hide");
					$('.conveneUpdateUser$i').not(this).hide();
					$('.conveneDeleteUser$i').hide();
					$('.conveneDeleteDate$i').hide();
					$('.conveneMain$i').hide();
					$(this).parent().parent()
						.addClass('ui-state-highlight')
						.find('td').not(':first')
						.addClass('conveneTd$i')
						.removeClass('ui-state-default')
						.addClass('ui-state-highlight');
	
					$(this).find('.icon').setIcon("save");
					var parent = $(this).parent().parent();
					parent.find('.vote').hide();
					parent.find('input').each(function() {
						$('<select>' +
							'<option value="">' + tr('Unconfirmed') + '</option>' +
							'<option value="-1">' + tr('Not ok') + '</option>' +
							'<option value="1">' + tr('Ok') + '</option>' +
						'</select>')
							.css({
								position: "absolute",
								left: 0,
								bottom: 0
							})
							.val($(this).val())
							.insertAfter(this)
							.change(function() {
								var cl = '';
	
								switch($(this).val() * 1) {
									case 1:     cl = 'convene-ok';break;
									case -1:    cl = 'convene-no';break;
									default:    cl = 'convene-unconfirmed';
								}
	
								$(this)
									.parent()
									.removeClass('convene-no convene-ok convene-unconfirmed')
									.addClass(cl);
	
								convene$i.updateUsers = true;
							}).
							parent().css({position: "relative"});
					});
				} else {
					$('.conveneUpdateUser$i').show();
					$('.conveneDeleteUser$i').show();
					$('.conveneDeleteDate$i').show();
					$(this).parent().parent()
						.removeClass('ui-state-highlight')
						.find('.conveneTd$i')
						.removeClass('ui-state-highlight')
						.addClass('ui-state-default');
	
					$('.conveneMain$i').show();
					$(this).find('span.icon-pencil');
					parent = $(this).parent().parent();
					parent.find('select').each(function(i) {
						parent.find('input.conveneUserVote$i').eq(i).val( $(this).val() );
	
						$(this).remove();
					});
	
					if (convene$i.updateUsers) {
						convene$i.updateUsersVotes();
					}
				}
				return false;
			});

			var addUsers$i = $('.conveneAddUser$i')
				.click(function() {
					if (!$(this).data('clicked')) {
						$(this)
							.data('initval', $(this).val())
							.val('')
							.data('clicked', true);
					}
				})
				.blur(function() {
					if (!$(this).val()) {
						$(this)
							.val($(this).data('initval'))
							.data('clicked', '');

					}
				})
				.keydown(function(e) {
					var user = $(this).val();

					if (e.which == 13) {//enter
						convene$i.addUser(user);
						return false;
					}
				});

			//ensure autocomplete works, it may not be available in mobile mode
            if (addUsers$i.autocomplete) {
				addUsers$i.tiki("autocomplete", "username");
            }

            $('.conveneAddUserButton$i').click(function() {
            	if ($('.conveneAddUser$i').val()) {
	                convene$i.addUser($('.conveneAddUser$i').val());
				} else {
					$('.conveneAddUser$i').val(jqueryTiki.username).focus()
				}
				return false;
            });

			$('#pluginConvene$i .icon').css('cursor', 'pointer');
		};
		initConvene$i();
JQ
	);

	return
	<<<RETURN
~np~
	<div class="panel panel-default">
		<div class="panel-heading">
			<h3 class="panel-title">$title</h3>
		</div>
		<div class="panel-body">
		    $result
		</div>
	</div>
~/np~
RETURN;
}
