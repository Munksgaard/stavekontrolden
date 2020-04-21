<?php
$relative="../";
include_once($relative."/settings/conf.php");
include_once($backenddir."checklogin.php");
if(!$_SESSION["user_id"]) exit;
$res=array();
if($_POST["limit"]>0) $_SESSION["limit"]=$_POST["limit"];
$show=($_SESSION["limit"]?$_SESSION["limit"]:25);

$cols='f.`id`, f.`fugeelement`, fa.`affixclassid`, a.`description`';
$wheres=array();
$f=$_POST["filtersetting"];
$_SESSION["filtersetting"]=$f;
parse_str($_POST["where"], $wheres);
foreach($wheres as $k=>$w) {
	if($w!="")
		$where.=' AND `'.$k.'` LIKE "'.(in_array($f,array("inword","endword"))?"%":"").''.$w.''.(in_array($f,array("inword","beginword"))?"%":"").'"';
}
$where=" WHERE f.lang='".$_SESSION["lang"]."' ".$where;
$order=" ORDER BY  `fugeelement`, a.`description`"; #" ORDER BY ".($_POST["order"]?$_POST["order"]: );

$baseq=" from fugeelement f left join fugeelement_to_affixclass fa on f.`id`=fa.`fugeelementid` left join affixclass a on fa.`affixclassid`=a.`id` ";

$orderdesc=" ORDER BY  `fugeelement`";
if($_POST["next"]<0) {
// // 	$last1="SELECT * FROM (";
// //     $last2=") sub ";
    $orderdesc.=" DESC";
    $_POST["next"]+=1;
}
elseif(!$_POST["next"]){
	$q="SELECT count(*) as numrows ".$baseq.$where;
	$result=$mysqli->query($q);
	if(!$result) $res["log"].=mysqlerror($q); 
	else $res["numrows"]=$result->fetch_assoc()["numrows"];	
}
$res["numshow"]=abs($_POST["next"])*$show;
$limit=" LIMIT ".abs($_POST["next"])*$show.",".$show;

$q=$last1."select distinct(f.id)".$baseq.$where.$orderdesc.$limit.$last2;
#$res["log"]=$q;
$result=$mysqli->query($q);
if(!$result) $res["log"].=mysqlerror($q); 
else $rall=$result->fetch_all();
if(!empty($rall)) {
	$ids=call_user_func_array('array_merge',$rall);

	$q='select '.$cols.$baseq.' where f.id in ('.implode(",",$ids).') '.$order;

	$result=$mysqli->query($q);
	if(!$result) $res["log"].=mysqlerror($q); 
	else {
		$res["rows"]=array();
		$prev_wc=0;
		$i=-1;
		while($wc=$result->fetch_assoc()) {
			if($wc["id"]!=$prev_wc) { 
				$prev_wc=$wc["id"];
				$i++; 
				$res["rows"][$i]=array(0=>$wc["id"],1=>$wc["fugeelement"],2=>"");
			}
			if($wc["description"])
				$res["rows"][$i][2].='<button class="editaffixclass btn btn-sm btn-light" data-affixclassid="'.$wc["affixclassid"].'">'.$wc["description"].'</button>&nbsp;';
		}
	}
}
else $res["rows"]=array(1=>array(_("No more word classes")));
$res["nextsingle"]=$_POST["nextsingle"];
if($_POST["nextsingle"])
	$res["single"]=true;
#print_r($res["rows"],true).
#file_put_contents("/home/stavekontrolden.dk/www/stavekontrolden/rows.txt",print_r($res,true));
echo json_encode($res);
