<?php
include_once('lib/rankings/ranklib.php');
$ranking = $ranklib->forums_ranking_most_read_topics($module_rows);
$smarty->assign('modForumsMostReadTopics',$ranking["data"]);
?>
