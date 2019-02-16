#!/usr/bin/env node
'use strict';

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
  description: 'Jeedom GenePi daemon'
});
parser.addArgument(
  [ '-p', '--port' ],
  {
    help: 'daemon listening port. default 8081',
    defaultValue: '8081'
//    required: true
  }
);
parser.addArgument(
  [ '-k', '--apikey' ],
  {
    help: 'Jeedom API key',
    defaultValue: process.env.JEEDOM_APIKEY,
  }
);
parser.addArgument(
  [ '-l', '--loglevel' ],
  {
    help: 'Daemon loglevel. Default: 3 (LOG)',
    defaultValue: 3
  }
);
parser.addArgument(
  [ '-j', '--jeedom-url' ],
  {
    help: 'Jeedom API URL',
    defaultValue: 'http://127.0.0.1:80/'
  }
);
var args = parser.parseArgs();


// Converting loglevel
if (isNaN(args.loglevel) && typeof( require('console-ten').LEVELS[args.loglevel.toUpperCase()] ) !== 'undefined' ) {
  args.loglevel = require('console-ten').LEVELS[args.loglevel.toUpperCase()];
}


// Init logging
require('console-ten').init(console, args.loglevel, (level) => "[" + (new Date().toISOString().substr(5, 18).replace('T', ' ')) + "] [" + level + "]\t");


console.debug('Arguments: %j', args);


//////////////////////////////  Jeedom functions  //////////////////////////////

// GenePi daemons websocket list
var genepiList = {};

// send jsonRPC request to jeedom
async function jeedomAPI(method, params={}) {
  try {
    console.info('jeedonAPI - Method: %s - params: %j', method, params);
    //adding apikey
    params.apikey=args.apikey;

    // jeedom jsonRPC API
    var options = {
      host: '127.0.0.1',
//TODO : utiliser args.jeedom-url
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
    throw 'Jeedom jsonRPC API not working: ' + error;
  }
}



//////////////////////////////  GenePi daemons handling  //////////////////////////////
// connect to GenePi daemons
async function connectToGenePi() {
  try {
    let name = await jeedomAPI('config::byKey', {"key":"name","plugin":"genepi"});
    let url  = await jeedomAPI('config::byKey', {"key":"ip","plugin":"genepi"});
//TODO: tester name/url - utiliser une api dediee

    console.info('Connecting Genepi name %s - url %s', name, url);


//TODO check
    if (genepiList[name] === url) {
      // socket exists
    } else if (typeof genepiList[name] === 'string') {
      // socket name exists but url change
    }

    // bind websocket and add RPC handler
    genepiList[name] = new WebSocket(url, 1000);
    require('./jsonrpc.js')(genepiList[name], genepiList[name].send);
    genepiList[name].on('message', genepiList[name].handleMessage);

    genepiList[name].on('open', async () => {
      try {
        console.log('Connexion au Genepi %s - OK', name);

        // get capabilities
        let result = await genepiList[name].call('capabilities');

        // save capa to file
        fs.writeFile(capaPath + name + '.json', JSON.stringify(result, true, 4), function(err) {
          if(err) {
            throw 'Error writing genepi ' + name + ' capabilities: ' + err;
          }
          console.log('Capabilities pour GenePi %s - OK', name);
        });

      } catch (err) {
        console.error('Impossible de recuperer les capabilities du genepi %s: %s', name, err);
//TODO: retry ou kill de la conn
      }
    });

    // handle genepi notif
    genepiList[name].addMethod('message', async (param) => {
      console.info('Got notification from genepi: %s - %j', name, param);
      param.plugin = 'genepi';
      try {
        await jeedomAPI('notif', param);
      } catch (error) {
        console.error('Message non reconnu depuis GenePi %s: %s', name, error);
      }
    });

    genepiList[name].on('error', function (err) {
      console.error('GenePi %s socket error: %s', name, err);
    });

    genepiList[name].on('close', function () {
      console.warn('GenePi %s connexion terminee', name);
    });


  } catch (error) {
    console.error('Echec de la connection au GenePi: %s', error);
//    throw 'Connection to GenePi failed: ' + error;
// appeler la fct avec await si besoin de throw
  }
}


//////////////////////////////  Configuring RPC methods  //////////////////////////////
const rpcMethod = {
  // TODO: update connect / return state. tester apikey du genepi
  'update_nodes': (params) => 'OK',

  // TODO : param = URL ? A suppr ?
  'check': (params) => 'OK',

  // send request to genepi node
  'send': async (params) => {
    console.info('Send request with params : %j', params);

    if (typeof(params.node) !== 'undefined') {
      // getting genepi node
      let node = params.node;
      delete(params.node);

      if (typeof(genepiList[node]) !== 'undefined') {
        try {
          let result = await genepiList[node].call('send', params);

          console.info('Send response: %j', result);
          return result;

        } catch (error) {
          throw 'RPC method send: '+ error;
        }

      } else {
        throw 'RPC method send: node ' + node + ' inconnu';
      }

    } else {
      throw 'RPC method send: pas de param node';
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
  console.info('Request for URL: %s', page);

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

});

server.on('error', (err) => {
  if (err.code === 'EADDRINUSE') {
    console.error('Impossible de lancer le daemon: port deja utilise');
    process.exit(1);
  }

  console.error('Erreur sur le serveur: %s', err);  
});

server.listen(args.port, 'localhost', function (err) {
  if (err) {
    console.error('Impossible de lancer le daemon: %s', err);
    process.exit(1);
  }
  console.log('Daemon en ecoute sur le port port %d', server.address().port);
});

//////////////////////////////  Connecting to GenePi daemons  //////////////////////////////

console.log('Initialisation des connexions au GenePi...');
connectToGenePi();
//TODO: retry ?

