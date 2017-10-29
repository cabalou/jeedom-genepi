#!/bin/bash

curl -d '{"jsonrpc":"2.0","id":1,"method":"'$1'","params":'$2'}' -H 'content-type:application/json;' http://127.0.0.1:8081/ ; echo ; echo

