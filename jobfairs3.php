<?php
    //error_reporting(E_ALL & ~E_NOTICE);

    //php通过curl获取http相应的内容
    $host = array("Host: bj.ganji.com");
    $data = 'user=xxx&qq=xxx&id=xxx&post=xxx';
    $url = 'http://10.3.255.201/jobfairs/jobfairs_im_port.php?action=getIms';
    $res = curl_post($host, $data, $url);
    //将json字符串解码成数组 
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
    tail_f($logfile, $userId);


    //通过php实现shell的tail -f命令
    function tail_f($logfile, $userId) {
        $size = filesize($logfile);
        $ch = fopen($logfile, 'r');
        $i = 0;
        while (1) {
            clearstatcache();
            $tmp_size = filesize($logfile);
            if (0 < ($len = $tmp_size - $size)) {
                $i = 0;
                fseek($ch, -($len -1), SEEK_END);
                $content = fread($ch, $len);
                $lineArr = explode("\n", $content);
                foreach ($lineArr as $line) {
                    //echo $line . "\n";
                    if (preg_match("/sendMsgOk.*spamReasons=\[\]/", $line)) {
                        matchCompany($line, $userId);
                    }
                }
            } else {
                $i++;
                if ($i > 60) {
                    echo PHP_EOL . 'The file in 60s without change,So exit!';
                    break;
                }
                sleep(1);
                continue;
            }
            $size = $tmp_size;
        }
        fclose($ch);
    }

    //对一行记录判断是否在企业信息中，如果在，则输出组合后的记录
    function matchCompany($line, $userId) {
        $flag = preg_match("/([0-9]+:[0-9]+:[0-9]+).*fromUserId=([0-9]+).*toUserId=([0-9]+).*msgContent=(.*)\tchannel=.*/", $line, $matches);
        if( $flag == 0 ){
            return;
        }

        //echo $matches[0] ."\t" .$matches[1] . "\t" . $matches[2] . "\t" . $matches[3] . "\t" . $matches[4] . "\n";
        $time = $matches[1];
        $fromUserId  = $matches[2];
        $toUserId = $matches[3];
        $msgContent = $matches[4];

        //查看fromUserId和toUserId有没有对应的公司
        $output = $time . "\t";
        //如果IM的id属于某个企业
        if(isset($userId[$fromUserId])){
            //echo $line . "\n";
            $output .= "companyId=$userId[$fui]\t";
            $output .= "companyName=$compName[$fui]\t";
            $output .= "companyDingdongId=$fui\t";
            $output .= "personalDingdongId=$tui\t";
            $output .= "whoSend=0\t";
            $output .= $msgContent;
            echo $output . "\n";
        }else if(isset($userId[$toUserId])){
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
