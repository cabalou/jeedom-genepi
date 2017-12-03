#!/usr/bin/env node
'use strict';

const leave = require('leave');
const WebSocket = require('./recon-ws.js');
const fs = require('fs');


//////////////////////////////  Checking capa dir  //////////////////////////////
const capaPath = __dirname + '/capa/';
if (!fs.existsSync(capaPath)) {
  fs.mkdirSync(capaPath, 0o777);
}

//////////////////////////////  Parsing arguments  //////////////////////////////
const ArgumentParser = require('argparse').ArgumentParser;
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



//////////////////////////////  GenePi daemons handling  //////////////////////////////
// connect to GenePi daemons
async function connectToGenePi() {
  try {
    let name = await jeedomAPI('config::byKey', {"key":"name","plugin":"genepi"});
    let url  = await jeedomAPI('config::byKey', {"key":"ip","plugin":"genepi"});
//TODO: tester name/url

    console.log('name %s - url %s', name, url);


//TODO check
    if (genepiList[name] === url) {
      // socket exists
    } else if (typeof genepiList[name] === 'string') {
      // socket name exists but url change
    }

    genepiList[name] = new WebSocket(url, 1000);
    require('./jsonrpc.js')(genepiList[name], genepiList[name].send);
    genepiList[name].on('message', genepiList[name].handleMessage);

    genepiList[name].on('open', function open() {
      // capabilities
//TODO: result = await
//TODO: call timeout si genepi pas joignable
      genepiList[name].call('capabilities').then((result) => {
        // save capa to file
        fs.writeFile(capaPath + name + '.json', JSON.stringify(result, true, 4), function(err) {
          if(err) {
console.log(err);
          }
        }); 
      });
    });

    // handle genepi notif
    genepiList[name].addMethod('message', (param) => {
      console.info('Got notification from genepi: %s - %j', name, param);
      param.plugin = 'genepi';
      jeedomAPI('notif', param);
    });

  } catch (error) {
    throw 'connectToGenePi ERROR';
  }
}


//////////////////////////////  Configuring RPC methods  //////////////////////////////
const rpcMethod = {
  // TODO : param = URL
  'check': (params) => 'OK',

  // TODO : a supprimer ?
  'send': async (params) => {
    try {
console.log('send request with params : %s', params);

      if (typeof(params.node) !== 'undefined') {
        //getting genepi node
        let node = params.node;
        delete(params.node);

        if (typeof(genepiList[node]) !== 'undefined') {
          let result = await genepiList[node].call('send', params);
          console.log('capabilities response: %s', result);
          return result;

        } else {
          throw 'RPC method send: node ' + node + ' inconnu';
        }

      } else {
        throw 'RPC method send: pas de param node';
      }

    } catch (error) {
      throw error;
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
 
