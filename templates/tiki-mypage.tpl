{literal}
<script type="text/javascript" src="lib/mootools/mootools.js"></script>
<script type="text/javascript" src="lib/mootools/extensions/windoo/windoo.js"></script>
{/literal}

<div style='border: solid 3px blue; width: {$mypage_width}; height: {$mypage_height};'>
<div id='mypage' style='position: absolute; width: {$mypage_width}; height: {$mypage_height};'>
 <div id='mypage_tools'>
  <a href='#'>New IFrame</a>
 </div>

 <div>
  <p>IFrame:</p>
  Title: <input id='mypage_newiframe_title' type='text' value='' /><br />
  URL:   <input id='mypage_newiframe_url' type='text' value='' /><br />
  <input id='mypage_newiframe_submit' type='button' value='Create'>
 </div>

 <div>
  <p>wiki:</p>
  Title: <input id='mypage_newwiki_pagename' type='text' value='' /><br />
  <input id='mypage_newwiki_submit' type='button' value='Create'>
 </div>
</div>
</div>

{literal}
<script type="text/javascript">

// open saved windows
tikimypagewin=[];
{/literal}{$mypagejswindows}{literal}


$('mypage_newiframe_submit').addEvent('click', function() {
	var title=$('mypage_newiframe_title').value;
	var url=$('mypage_newiframe_url').value;

	xajax_mypage_win_create({/literal}{$id_mypage}{literal}, 'iframe', title, url);
});

$('mypage_newwiki_submit').addEvent('click', function() {
	var pagename=$('mypage_newwiki_pagename').value;

	xajax_mypage_win_create({/literal}{$id_mypage}{literal}, 'wiki', pagename, pagename);
});


</script>
{/literal}
