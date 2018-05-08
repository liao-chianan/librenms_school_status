<!DOCTYPE html>
<html>
<head>
<style>


/*定義說明文字*/
.readme {
    border-width:1px;
    margin:0px auto;
    border-style:solid;
    background-color: #99FFFF;
    width: 500px;
    height: 105px;
    vertical-align: left;
    text-align: left;
    font-family:Microsoft JhengHei;
    font-size:small;
    float:;
}

/* 定義斷線統計*/
.device_count { 
    border-width:0px; 		
    background-color: #99FFFF; 
    border-radius: 3px; 
    -webkit-border-radius: 3px; 
    -moz-border-radius: 3px;
    width: 400px;
    height: 22px;
	vertical-align: middle;
	text-align: center;
	font-family:Microsoft JhengHei;
	font-size:small;
	float:left;
	position:absolute;
	top:180px;
	right:77px;
	
}


/* 定義設備正常*/
.device_up { 
    border-width:1px; 
	border-style:solid;
	margin-left:2px;
	margin-right:2px;
	margin-top:2px;
	margin-bottom:2px;
    background-color: #66FF66; 
    border-radius: 7px; 
    -webkit-border-radius: 7px; 
    -moz-border-radius: 7px;
    width: 200px;
    height: 36px;
	vertical-align: middle;
	text-align: center;
	font-family:Microsoft JhengHei;
	font-size:small;
	float:left;
}

/* 定義設備待確認*/
.device_check { 
    border-width:1px; 
	border-style:solid;
	margin-left:2px;
	margin-right:2px;
	margin-top:2px;
	margin-bottom:2px;
    background-color: #DDFF77; 
    border-radius: 7px; 
    -webkit-border-radius: 7px; 
    -moz-border-radius: 7px;
    width: 200px;
    height: 36px;
	vertical-align: middle;
	text-align: center;
	font-family:Microsoft JhengHei;
	font-size:small;
	float:left;
}


/* 定義設備故障*/
.device_down {
    border-width:1px; 
	border-style:solid;
	margin-left:2px;
	margin-right:2px;
	margin-top:2px;
	margin-bottom:2px;
    background-color: #FF0000; 
    border-color: #8C0044;
    border-radius: 7px; 
    -webkit-border-radius: 7px; 
    -moz-border-radius: 7px;
    width: 200px;
    height: 36px;
	vertical-align: middle;
	text-align: center;
	font-family:Microsoft JhengHei;
	font-size:small;
    color: #FFFFFF;
	float:left;
   }

</style>
</head>
<body>
<br><br>
<div class="readme">
說明：本頁面建置於市網中心，檢測各校與市網介接之 L3 路由設備
<br>檢測方式：每隔 60 秒 ping 1 次，ping 回應時間高於 100ms 即為 ping loss<br>
<br>正常：　最近連續兩次 ping 皆沒有loss 則為正常，顯示綠色
<br>待確認：最近連續兩次 ping 中有1次 loss 則為待確認，顯示淺黃色
<br>異常：　最近連續兩次 ping 皆 loss 則為異常，顯示紅色
</div>
<br><br>


<button type="button" id="toggle" onclick="changeMode()">顯示異常</button> 
<input type="text" id="search" onkeyup="keyFilter()" placeholder="快速搜尋">
<br>
<div class="container" id="container">

<?php
include("../config.php");
$DBNAME = $config['db_name'];
$DBUSER = $config['db_user'];
$DBPASS = $config['db_pass'];
$DBHOST = $config['db_host'];

//定義執行時間
$time_start = microtime(true);

//定義統計起始量
$d_count_up = 0;
$d_count_check = 0;
$d_count_down = 0;

try {
    //查詢 devices 資料表中所有設備的 id,名稱 
    $pdo = new PDO("mysql:host=".$DBHOST.";dbname=".$DBNAME, $DBUSER, $DBPASS);
    $pdo->query("set names utf8");
    $sql = "select device_id,sysName from devices";
    $query = $pdo->query($sql);

    //從 device_id 去比對 device_perf 中最後一筆 ping 的資料
    while ($datainfo = $query->fetch()) {
        $sql2 = "select devices.device_id,devices.features,devices.sysName,devices.hostname,device_perf.timestamp,device_perf.loss  from devices JOIN device_perf on devices.device_id=device_perf.device_id  where  device_perf.device_id =". $datainfo['device_id']. " order by device_perf.timestamp desc limit 1;";
        $query2 = $pdo->query($sql2);

        //計算每一部裝置最近兩筆的 ping 資料
        while ($datainfo2 = $query2->fetch()) {
            $query3 = $pdo->query("SELECT sum(loss) as sum_loss from(SELECT loss FROM device_perf  where device_id =".$datainfo['device_id'] ." order by device_perf.timestamp desc limit 2) as subquery");
            $row = $query3->fetch();
            $query3 = null;
            //echo $row['sum_loss'];

            //總和為 0 是正常
            if ($row['sum_loss'] == 0) {
                $school_status="正常";
                $div_class="device_up";
				$d_count_up = $d_count_up + 1;
            }

            //總和為 100 是待確認
            if ($row['sum_loss'] == 100) {
               $school_status="待確認";
                $div_class="device_check";
				$d_count_check = $d_count_check + 1;
            }

            //總和 200 代表連續兩次 ping 失敗
            if ($row['sum_loss'] >= 200) {
                $school_status="異常";
                $div_class="device_down";
				$d_count_down = $d_count_down + 1;
            }


            echo "<div class=".  $div_class  .">";
            echo  $datainfo['sysName'].$school_status;
            echo "<br>檢測時間：".$datainfo2['timestamp'];
            //echo "<br>檢測IP：".$datainfo2['hostname'];
            //echo "<br>電路編號：".$datainfo2['features'];
            echo "</div>";
        }
        $query2 = null;
    }
    $query = null;
    $pdo = null;
} catch(Exception $e) {
    // 錯誤顯示
    echo $e->getMessage();
}

//計算執行時間
$run_time = microtime(true) - $time_start;
//echo "<div style=position:absolute;top:100px;left:10px>執行時間：" . $run_time . "</div>"; 

//顯示統計
$d_count_total = $d_count_up + $d_count_down + $d_count_check;
echo "<div class=device_count>檢測總數：" . $d_count_total . "　正常：" . $d_count_up . "　待確認：" . $d_count_check . "　異常：" . $d_count_down . "</div>"; 
?>

</div>




<script>
    function keyFilter() {
      var input, filter, container, div, i;
      input = document.getElementById("search");
      filter = input.value.toUpperCase();
      container = document.getElementById("container");
      div = container.getElementsByTagName("div");
    
      for (i = 0; i < div.length; i++) {
        if (div[i].innerHTML.toUpperCase().indexOf(filter) > -1) {
            div[i].style.display = "";
          } else {
            div[i].style.display = "none";
          }
        }
    }

    function changeMode(){
        var btn, div, i;
        btn = document.getElementById("toggle");
        div = document.getElementsByClassName("device_up");
        if (btn.style.backgroundColor == ""){
            for (i = 0; i < div.length; i++){
                div[i].style.display = "none";
            } 
            btn.style.backgroundColor = "RED";
            btn.innerHTML = "顯示全部";
        } else {
            for (i = 0; i < div.length; i++){
                div[i].style.display = "";
            } 
            btn.style.backgroundColor = "";
            btn.innerHTML = "顯示異常";
        } 
    }


    </script>



</body>
</html>
