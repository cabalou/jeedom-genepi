#!/usr/bin/env node
'use strict';

const leave = require('leave');
const WebSocket = require('./recon-ws.js');

//////////////////////////////  Parsing arguments  //////////////////////////////
var ArgumentParser = require('argparse').ArgumentParser;
var parser = new ArgumentParser({
  version: '0.0.1',
  addHelp:true,
  description: 'Jeedom RFpi daemon'
});
parser.addArgument(
  [ '-p', '--port' ],
  {
    help: 'daemon listening port. default 8081',
    defaultValue: '8081'
  }
);
parser.addArgument(
  [ '-k', '--key' ],
  {
    help: 'Jeedom API key',
    defaultValue: 'gPUrMYlgGtZE7EZIAXJ74Lp0JI75IcSP7Txd52vJ5mlf3b6h',
//TODO
//    required: true
  }
);
parser.addArgument(
  '-f',
  {
    help: 'baz bar'
  }
);
var args = parser.parseArgs();
console.dir(args);


//////////////////////////////  Jeedom functions  //////////////////////////////

// GenePi daemons websocket list
var genepiList = {};

// send jsonRPC request to jeedom
async function jeedomAPI(method, params={}) {
  try {
    //adding apikey
    params.apikey=args.key;

    // jeedom jsonRPC API
    var options = {
      host: '127.0.0.1',
      path: '/core/api/jeeApi.php',
      port: '80',
      method: 'POST',
      headers: {'content-type': 'application/json'}
    };

    // HTTP request handler
    var req = http.request(options, function(response) {
      var str = ''
      response.on('data', function (chunk) {
        str += chunk;
      });

      response.on('end', function () {
        req.handleMessage(str);
      });
    });
    require('./jsonrpc.js')(req, req.end);

    // sending RPC request
    return await req.call(method, params);
  } catch (error) {
    throw 'jeedom jsonRPC API not working';
  }
}


// connect to GenePi daemons
async function connectToGenePi() {
  try {
    let name = await jeedomAPI('config::byKey', {"key":"name","plugin":"genepi"});
    let url  = await jeedomAPI('config::byKey', {"key":"ip","plugin":"genepi"});

    console.log('name %s - url %s', name, url);


//TODO check
    if (genepiList[name] === url) {
      // socket exists
    } else if (typeof genepiList[name] === 'string') {
      // socket name exists but url change
    }

    var ws = new WebSocket(url, 1000);
    require('./jsonrpc.js')(ws, ws.send);
    ws.on('message', ws.handleMessage);

    ws.on('open', function open() {
      // capabilities
      ws.call('capabilities').then((result) => {
//TODO save capa
console.log('capabilities response: %s', result);
      });
    });

  } catch (error) {
    throw 'connectToGenePi ERROR';
  }
}


//////////////////////////////  Configuring RPC methods  //////////////////////////////
const rpcMethod = {
  // capabilities
  'check': (params) => 'OK',

  'capabilities': async (params) => {
    try {
      let result = await ws.call('capabilities', params);
      console.log('capabilities response: %s', result);
      return result;
    } catch (error) {
      throw '';
    }
  },

  // subscribe
  'subscribe': (params) => params
}

//////////////////////////////  Starting HTTP server  //////////////////////////////
const http = require('http');
const url = require('url');
const textBody = require('body');

const server = http.createServer(function(req, res) {
  var page = url.parse(req.url).pathname;
  console.log(page);

//TODO ajout du APIkey
  if (page == '/') {

    textBody(req, res, function (err, body) {
      // err probably means invalid HTTP protocol or some shiz. 
      if (err) {
        res.statusCode = 500;
        return res.end('Server error');
      }

      // attach RPC requests handler
      require('./jsonrpc.js')(res, res.end, rpcMethod);

      // handle request
      res.writeHead(200, {"Content-Type": "application/json"});
      res.handleMessage(body);
    });

  } else {
    //TODO bad APIkey
    res.statusCode = 401;
    return res.end('Unauthorized');
  }

})
.listen(args.port);


//////////////////////////////  Connecting to GenePi daemons  //////////////////////////////

connectToGenePi();


/*
var config = {};
jsonfile.readFile('config.json', 'utf8', function (err, conf) {
  if (typeof conf == 'undefined') {
    console.log(err);
    process.exit(1);
  } else {
    config = conf;
  }
});
//curl -d '{"jsonrpc":"2.0","id":1,"method":"config::byKey","params":{"apikey":"gPUrMYlgGtZE7EZIAXJ74Lp0JI75IcSP7Txd52vJ5mlf3b6h","key":"ip","plugin":"genepi"}}' -H 'content-type:application/json;' http://127.0.0.1/core/api/jeeApi.php ; echo ; echo

*/
 
