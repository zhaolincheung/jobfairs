#!/bin/sh

#执行前清除所有该进程
pids=`ps aux | grep jobfairs | grep -v "grep" | awk '{print $2}'`      
if [ "$pids" != "" ];then 
    echo $pids
    kill -9 $pids
fi
sh jobfairs.sh >> /home/ganji/log/jobfairs.log
