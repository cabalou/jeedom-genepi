#!/bin/bash

# unknown
curl -d '{"jsonrpc":"2.0","id":1,"method":"unknown_met","params":[1, 2]}' -H 'content-type:application/json;' http://127.0.0.1:8081/ ; echo ; echo

# capabilities
curl -d '{"jsonrpc":"2.0","id":1,"method":"capabilities","params":[1, 2]}' -H 'content-type:application/json;' http://127.0.0.1:8081/ ; echo ; echo

# subscribe
curl -d '{"jsonrpc":"2.0","id":1,"method":"subscribe","params":[1, 2]}' -H 'content-type:application/json;' http://127.0.0.1:8081/ ; echo ; echo

