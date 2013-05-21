<?php
    //error_reporting(E_ALL & ~E_NOTICE);

    $host = array("Host: bj.ganji.com");
    $data = 'user=xxx&qq=xxx&id=xxx&post=xxx';
    $url = 'http://10.3.255.201/jobfairs/jobfairs_im_port.php?action=getIms';
    $res = curl_post($host, $data, $url);
   
    $arr = json_decode($res, true);
    $status = $arr["status"];

    if ($status != 1) {
        echo "Request Failed!";
        exit;
    }
    
    //获取返回的企业信息
    $ret = $arr["ret"];
    foreach ($ret as $key => $value) {
        $name = $value["name"][0];
        //将IM的Id和企业id进行hash映射
        foreach ($value["im_account"] as $v) {
            $userId[$v] = $key;
            $compName[$v] = $name;
        }
    }
    
    $logfile = "/data3/im-log/nginx.im.imp.current/nginx.im.imp.current_current";
    
    //tail -n1000获取最后1000行记录，并保存到$log变量中
    $shell = "tail -n 1000 $logfile | grep sendMsgOk | grep 'spamReasons=\[\]' | ";
    $shell .= "awk -F'\t' '{print $1,$3,$4,$11;}'";
    exec($shell, $log); //将执行的shell结果保存到数组中
   
    //处理每一行记录
    foreach ($log as $line) {
        //通过正则表达式匹配出所需要的字段
        $flag = preg_match("/([0-9]+:[0-9]+:[0-9]+).*fromUserId=([0-9]+).*toUserId=([0-9]+).*msgContent=(.*)/", $line, $matches);
        if( $flag == 0 ){//匹配失败
            continue;
        }
        
        //echo $line . "\n";
        $time = $matches[1];
        $fui  = $matches[2];
        $tui = $matches[3];
        $msgContent = $matches[4];
        
        //查看fromUserId和toUserId有没有对应的公司
        $output = $time . "\t";
        //通过hash判断IM的id是否属于某个企业
        if(isset($userId[$fui])){
            //echo $line . "\n";
            $output .= "companyId=$userId[$fui]\t";
            $output .= "companyName=$compName[$fui]\t";
            $output .= "companyDingdongId=$fui\t";
            $output .= "personalDingdongId=$tui\t";
            $output .= "whoSend=0\t";
            $output .= $msgContent;
            echo $output . "\n";
        }else if(isset($userId[$tui])){
            //echo $line . "\n";
            $output .= "companyId=$userId[$tui]\t";
            $output .= "companyName=$compName[$tui]\t";
            $output .= "companyDingdongId=$tui\t";
            $output .= "personalDingdongId=$fui\t";
            $output .= "whoSend=1\t";
            $output .= $msgContent;
            echo $output . "\n";
        }
    }

    /*
    * 提交请求
    * @param $host array 需要配置的域名 array("Host: bj.ganji.com");
    * @param $data string 需要提交的数据 'user=xxx&qq=xxx&id=xxx&post=xxx'....
    * @param $url string 要提交的url 'http://192.168.1.12/xxx/xxx/api/';
    */
    function curl_post($host,$data,$url){
        $ch = curl_init();
        $res= curl_setopt($ch, CURLOPT_URL,$url);
        //var_dump($res);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt ($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_HTTPHEADER,$host);
        $result = curl_exec($ch);
        curl_close($ch);
        if ($result == NULL) {
            return 0;
        }
        return $result;
    }
?>
