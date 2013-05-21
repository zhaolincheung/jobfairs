<?php
    $ret = $_SERVER["argv"][1];
    $arr = json_decode($ret, true);//将json字符串解码成数组
    foreach ($arr as $key => $value) {
        $name = $value["name"][0];//企业名称
        foreach ($value["im_account"] as $v) { //企业对应的叮咚id
            $userId[$v] = $key;
            $compName[$v] = $name;
            //echo $key ."\t" . $v ."\t" . $name ."\n";
        }
    }
  
    $line = $_SERVER["argv"][2];//获取日志的一条记录
    $logArr = explode("\t", $line);
    //echo $line . "\n";

    //获取各个字段
    $time = $logArr[0];
    $fromUserId = $logArr[1];
    $toUserId = $logArr[2];
    $msgContent = $logArr[3];
    
    $fuiArr = explode('=', $fromUserId);
    $tuiArr = explode('=', $toUserId);
    $fui = $fuiArr[1];
    $tui = $tuiArr[1];

    $output = $time . "\t";
    if(isset($userId[$fui])) { //fromUserId是某个企业的叮咚id
        //echo $line . "\n";
        $output .= "companyId=$userId[$fui]\t";
        $output .= "companyName=$compName[$fui]\t";
        $output .= "companyDingdongId=$fui\t";
        $output .= "personalDingdongId=$tui\t";
        $output .= "whoSend=0\t";
        $output .= $msgContent;
        echo $output . "\n";
    } else if(isset($userId[$tui])) { //toUserId是某个企业的叮咚id
        //echo $line . "\n";
        $output .= "companyId=$userId[$tui]\t";
        $output .= "companyName=$compName[$tui]\t";
        $output .= "companyDingdongId=$tui\t";
        $output .= "personalDingdongId=$fui\t";
        $output .= "whoSend=1\t";
        $output .= $msgContent;
        echo $output . "\n";
    }
?>
