<?php
// Initialization
require_once('tiki-setup.php');

if($feature_html_pages != 'y') {
  $smarty->assign('msg',tra("This feature is disabled"));
  $smarty->display('error.tpl');
  die;  
}


if($tiki_p_edit_html_pages != 'y') {
    $smarty->assign('msg',tra("You dont have permission to use this feature"));
    $smarty->display('error.tpl');
    die;
}

if(!isset($_REQUEST["pageName"])) {
  $_REQUEST["pageName"] = '';
}
$smarty->assign('pageName',$_REQUEST["pageName"]);

if($_REQUEST["pageName"]) {
  $info = $tikilib->get_html_page($_REQUEST["pageName"]);
} else {
  $info = Array();
  $info["pageName"]='';
  $info["content"]='';
  $info["refresh"]=0;
  $info["type"]='s';
}
$smarty->assign('info',$info);


if(isset($_REQUEST["remove"])) {
  $tikilib->remove_html_page($_REQUEST["remove"]);
}

if(isset($_REQUEST["templateId"])&&$_REQUEST["templateId"]>0) {
  $template_data = $tikilib->get_template($_REQUEST["templateId"]);
  $_REQUEST["content"]=$template_data["content"];
  $_REQUEST["preview"]=1;
}

$smarty->assign('preview','n');
if(isset($_REQUEST["preview"])) {
  $smarty->assign('preview','y');
  //$parsed = $tikilib->parse_data($_REQUEST["content"]);
  $parsed = $tikilib->parse_html_page($_REQUEST["pageName"],$_REQUEST["content"]);
  $smarty->assign('parsed',$parsed);
  $info["content"]=$_REQUEST["content"];
  $info["refresh"]=$_REQUEST["refresh"];
  $info["pageName"]=$_REQUEST["pageName"];
  $info["type"]=$_REQUEST["type"];
  $smarty->assign('info',$info);
}

if(isset($_REQUEST["save"]) && !empty($_REQUEST["pageName"])) {
  $tid = $tikilib->replace_html_page($_REQUEST["pageName"], $_REQUEST["type"],$_REQUEST["content"],$_REQUEST["refresh"]);
  $smarty->assign("pageName",'');
  $info["pageName"]='';
  $info["content"]='';
  $info["regresh"]=0;
  $info["type"]='s';
  $smarty->assign('info',$info);
}

if(!isset($_REQUEST["sort_mode"])) {
  $sort_mode = 'created_desc'; 
} else {
  $sort_mode = $_REQUEST["sort_mode"];
} 

if(!isset($_REQUEST["offset"])) {
  $offset = 0;
} else {
  $offset = $_REQUEST["offset"]; 
}
$smarty->assign_by_ref('offset',$offset);

if(isset($_REQUEST["find"])) {
  $find = $_REQUEST["find"];  
} else {
  $find = ''; 
}
$smarty->assign('find',$find);

$smarty->assign_by_ref('sort_mode',$sort_mode);
$channels = $tikilib->list_html_pages($offset,$maxRecords,$sort_mode,$find);

$cant_pages = ceil($channels["cant"] / $maxRecords);
$smarty->assign_by_ref('cant_pages',$cant_pages);
$smarty->assign('actual_page',1+($offset/$maxRecords));
if($channels["cant"] > ($offset+$maxRecords)) {
  $smarty->assign('next_offset',$offset + $maxRecords);
} else {
  $smarty->assign('next_offset',-1); 
}
// If offset is > 0 then prev_offset
if($offset>0) {
  $smarty->assign('prev_offset',$offset - $maxRecords);  
} else {
  $smarty->assign('prev_offset',-1); 
}

$smarty->assign_by_ref('channels',$channels["data"]);

if($tiki_p_use_content_templates == 'y') {
  $templates = $tikilib->list_templates('html',0,-1,'name_asc','');
}
$smarty->assign_by_ref('templates',$templates["data"]);


// Display the template
$smarty->assign('mid','tiki-admin_html_pages.tpl');
$smarty->display('tiki.tpl');
?>