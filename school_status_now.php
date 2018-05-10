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

//定義儲存各校資料初始值
$sch_data[] = null;
$sch_count = 0;

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
                $div_class="up";
                $d_count_up = $d_count_up + 1;
            }

            //總和為 100 是待確認
            if ($row['sum_loss'] == 100) {
               $school_status="待確認";
                $div_class="check";
                $d_count_check = $d_count_check + 1;
            }

            //總和 200 代表連續兩次 ping 失敗
            if ($row['sum_loss'] >= 200) {
                $school_status="異常";
                $div_class="down";
                $d_count_down = $d_count_down + 1;
            }


        $sch_data[$sch_count]['name'] = $datainfo['sysName'];
        $sch_data[$sch_count]['status'] = $school_status;
        $sch_data[$sch_count]['div_class'] = $div_class;
        $sch_data[$sch_count]['timestamp'] = $datainfo2['timestamp'];
        $sch_data[$sch_count]['ip'] = $datainfo2['hostname'];
        $sch_data[$sch_count]['features'] = $datainfo2['features'];
            $sch_count++;
            //echo "<div class=".  $div_class  .">";
            //echo  $datainfo['sysName'].$school_status;
            //echo "<br>檢測時間：".$datainfo2['timestamp'];
            //echo "<br>檢測IP：".$datainfo2['hostname'];
            //echo "<br>電路編號：".$datainfo2['features'];
            //echo "</div>";
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
//echo "<div class=device_count>檢測總數：" . $d_count_total . "　正常：" . $d_count_up . "　待確認：" . $d_count_check . "　異常：" . $d_count_down . "</div>"; 

// 變數說明
//$sch_data[$i]['name'] = 行政區-學校名稱
//$sch_data[$i]['status'] = 狀態[正常|異常|待確認]
//$sch_data[$i]['div_class'] = div用的class [up|down|check]
//$sch_data[$i]['timestamp'] = 時間戳記[yyyy-mm-dd hh-mm-ss]
//$sch_data[$i]['ip'] = 檢測IP
//$sch_data[$i]['features'] = 電路編號
//$run_time = 執行時間
//$d_count_total = 檢測總數
//$d_count_up = 正常總數
//$d_count_check = 待確認總數
//$d_count_down = 異常總數
?>
<!DOCTYPE html>
<html lang="zh-Hant-TW">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>北市各校路由設備狀態</title>
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" />
    <style type="text/css">
    /*定義說明文字*/
    .readme {
        border-width:1px;
        margin:0px auto;
        border-style:solid;
        background-color: #99FFFF;
        width: 500px;
        vertical-align: left;
        text-align: left;
        font-family:Microsoft JhengHei;
        font-size:small;
        float:;
    }
    
    /* 各校狀態表採 CSS Grid */
    .device_table {
      display: grid;
      justify-items: start;
      grid-template-columns: repeat(auto-fill, minmax(225px, 1fr));
    }
    
    .device {
        border-color:#888888;
        border-width:1px; 
        border-style:solid;
        margin-left:2px;
        margin-right:2px;
        margin-top:2px;
        margin-bottom:2px;
        border-radius: 3%; 
        -webkit-border-radius: 3%; 
        -moz-border-radius: 3%;
        width: 220px;
        height: 46px;
        vertical-align: middle;
        text-align: center;
        font: italic Helvetica, Arial, "文泉驛正黑", "WenQuanYi Zen Hei", "儷黑 Pro", "LiHei Pro", "黑體-繁", "Heiti TC", "微軟正黑體", "Microsoft JhengHei", sans-serif;
        font-size:small;
    }
    
    .up { 
        background-color: #00FF00;
    }

    .check {
        background-color: #FFFF77;
    }

    
    .down {
        background-color: #FF0000;
        color: #FFFFFF;
        font-weight:bold; 
    }
       
    /* 選單列採用 flexbox */
    .fbox {
        display:flex;
        flex-wrap: wrap;
    }
       
    .push {
        align-self: center;
        margin-left: auto;
        margin-right: 10px;
    }

    /* 寬度小於 740px 換行後右推改左推 */
    @media screen and (max-width: 740px) { .push { margin-left: 10px; } }
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
    <div class="fbox">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link active" href="#" onclick="showAll()" id="showAll">全部</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showDown()" id="showDown">異常</a>
            </li>
       </ul>
        <input class="form-inline" placeholder=" 快速搜尋 " aria-label="快速搜尋" id="search" onkeyup="keyFilter()" type="text">
        <div class="push">檢測總數：<?php echo $d_count_total;?>　正常：<?php echo $d_count_up;?>　待確認：<?php echo $d_count_check;?>　異常：<?php echo $d_count_down;?></div>
    </div>
    <br>
    <div class="device_table" id="device_table">
        <?php foreach($sch_data as $i => $value){ ?>
        <div class="device <?php echo $value['div_class']; ?>"><?php printf("%s%s<br>檢測時間：%s\n",$value['name'], $value['status'], $value['timestamp']); ?></div>
        <?php } ?>
    </div>
    <script>
    function keyFilter() {
        var input, filter, container, div, i;
        input = document.getElementById("search");
        filter = input.value.toUpperCase();
        container = document.getElementById("device_table");
        div = container.querySelectorAll(".up, .down");
    
        for (i = 0; i < div.length; i++) {
            if (div[i].innerHTML.toUpperCase().indexOf(filter) > -1) {
                div[i].style.display = "";
            } else {
                div[i].style.display = "none";
            }
        }
    }
 
    function showDown() {
        var lnk_all, lnk_down, div, i;
        lnk_all = document.getElementById("showAll");
        lnk_down = document.getElementById("showDown");
        div = document.getElementsByClassName("up");
        lnk_all.className = "nav-link";
        lnk_down.className = "nav-link active";
        for (i = 0; i < div.length; i++) {
            div[i].style.display = "none";
        }
    }
    
    function showAll() {
        var lnk_all, lnk_down, div, i;
        lnk_all = document.getElementById("showAll");
        lnk_down = document.getElementById("showDown");
        div = document.getElementsByClassName("up");
        lnk_all.className = "nav-link active";
        lnk_down.className = "nav-link";
        for (i = 0; i < div.length; i++) {
            div[i].style.display = "";
        }
    }
    </script>
</body>
</html>
