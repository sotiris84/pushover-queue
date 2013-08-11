#!/bin/bash
COUNTER=0
while [ $COUNTER -lt 5 ]; do
        curl -o /dev/null -u username:password http://example.com/notify-me.php?action=send
        sleep 10;
        let COUNTER=COUNTER+1
done