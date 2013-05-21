#!/bin/sh

logfile="/data3/im-log/nginx.im.imp.current/nginx.im.imp.current_current"

hours=`date +%H`
start_time=`date +%s`

#17点后停止运行程序
while [ $hours -lt 17 ]
do
    res=`curl -s -H "Host: bj.ganji.com" http://10.3.255.201/jobfairs/jobfairs_im_port.php?action=getIms`
    #echo $res
    
    len=${#res}
    if [ $len = 0 ]; then
        echo "Failed! Request error!"
        exit
    fi

    status=`echo $res | sed -e 's/.*status"://' -e 's/,.*//'`
    if [ $status != 1 ]; then 
        echo "Failed! Request stauts:"$status
        exit
    fi

    ret=`echo $res | sed -e 's/.*ret"://' -e 's/,"msg.*//'`
    #ret='{"2028097":{"im_account":["2875001357","197823104","3032631861","197305863"],"name":["8\u811a\u732b\u521b\u65b0\u79d1\u6280\u6709\u9650\u516c\u53f8\uff08\u4e60\u5927\u7237\u6dae\u8089\u5bf9\u976210000\u7c73\u7684\u79d1\u6280\u516c\u53f8\uff09"]},"2028098":{"im_account":["3658247660","192683241","197488883","108963206","197305001"],"name":["9\u811a\u732b\u521b\u65b0\u79d1\u6280\u6709\u9650\u516c\u53f8"]}}';

    tail -f $logfile | grep sendMsgOk | grep "spamReasons=\[\]" | awk -F"\t" '{
        printf("%s\t%s\t%s\t%s\n",$1,$3,$4,$11); 
    }' | while read line
    do
        /usr/local/webserver/php/bin/php jobfairs.php $ret "$line"
        
        #120s后停止生成日志，重新执行http请求去获取公司相关信息
        end_time=`date +%s`
        if [ $(expr $end_time - $start_time) -ge 120 ]; then
            #echo `date +%T`" "`date +%D`
            #echo "120s is done!"
            break
        fi
    done
    
    start_time=`date +%s`
    hours=`date +%H`
done
