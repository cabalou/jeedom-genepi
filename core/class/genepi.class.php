<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';


class genepiConfig {

    private $configTree;

    private static function getPath() {
        return realpath(dirname(__FILE__) . "/../../daemon/capa");
    }

    // Constructor - get config file and build config tree
    function __construct() {
        $this->configTree = array();

        //get Nodes
//TODO: getConfig plutot que ls
        $nodes = array_map(function ($val) { return substr($val, 0, -5); }, preg_grep("/\.json$/", scandir(self::getPath() ) ));

        // parse config file
        foreach ($nodes as $node) {
            $this->configTree[$node] = json_decode(file_get_contents(self::getPath() . "/$node.json"), true);
        }
    }

    public function getNodes() {
        return array_keys($this->configTree);
    }

    public function getProto($node) {
        return array_keys($this->configTree[$node]);
    }

    public function getType($node, $proto) {
        return array_keys($this->configTree[$node][$proto]);
    }

    public function getParam($node, $proto, $type) {
        return $this->configTree[$node][$proto][$type]['param'];
    }

    public function getRoll($node, $proto, $type) {
        if (array_key_exists('rolling', $this->configTree[$node][$proto][$type])) {
            return $this->configTree[$node][$proto][$type]['rolling'];
        } else {
            return array();
        }
    }

    public function getCmd($node, $proto, $type) {
        return $this->configTree[$node][$proto][$type]['cmd'];
    }
}


class genepi extends eqLogic {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */

    // send a jsonRPC request to Daemon
    public static function sendToDaemon($method, $param = null) {

        $daemonURL = "http://127.0.0.1:8081/";

        log::add('genepi','info','RPC call - methode : ' . $method . ' - param : ' . json_encode($param));

        $json = json_encode([
            "jsonrpc" => "2.0",
            "id" => 1,
            "method" => $method,
            "params" => $param,
        ]);

        $curl = curl_init($daemonURL);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',      
        ));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $curl_response = curl_exec($curl);
        curl_close($curl);

//        if (curl_errno($curl)){
        if ($curl_response) {
            log::add('genepi','debug','Daemon response: ' . $curl_response);
            $result = json_decode($curl_response, true);
            if (array_key_exists('error', $result)) {
                // erreur RPC call
                log::add('genepi','error','Daemon RPC error: ' . $result['error']['message'] . ' - ' . json_encode($result['error']['data']) );
                return null;

            } else {
                // RPC call valide
                log::add('genepi','info','RPC call result: ' . json_encode($result['result']));
                return $result['result'];
            }
        } else {
            // erreur sur la requete curl
            log::add('genepi','error','Daemon curl error: ' . curl_error($curl));
            log::add('genepi','error','Daemon curl error: ' . curl_strerror(curl_errno($curl)));
            return null;
        }
    }


    // check if genepii daemon responds
    public static function check() {
//TODO: fonction check
        $gateway = config::byKey('ip','genepi');
        log::add('genepi','debug','Check genepi GW ' . $gateway);
        $pouet = genepi::sendToDaemon('check', $gateway);

        return "YEP YEP YEP";
    }


    public static function deamon_path() {
        return realpath(dirname(__FILE__) . '/../../daemon/client.js');
    }

    public static function deamon_pid() {
        return trim( shell_exec ("ps aux | grep '" . self::deamon_path() . "' | grep -v grep | awk '{print $2}'") );
    }


    public static function deamon_info() {
        $return = array(
            'log'        => 'genepi_daemon',
            'launchable' => 'ok',
            'state'      => 'nok'
        );

//        $pid = trim( shell_exec ("ps aux | grep 'daemon/client.js' | grep -v grep | awk '{print $2}'") );
        $pid = self::deamon_pid();
        if ($pid != '' && $pid != '0') {
            $return['state'] = 'ok';
        }
/*
        if (config::byKey('nodeGateway', 'rflink') == 'none' || config::byKey('nodeGateway','rflink') == '') {
            $return['launchable'] = 'nok';
            $return['launchable_message'] = __('Le port n\'est pas configuré', __FILE__);
        }
*/
        return $return;
    }

    public static function deamon_start() {
//        self::deamon_stop();

        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration : port du démon', __FILE__));
        }

        log::add('genepi', 'info', 'Lancement du démon genepi');

        $url = network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp');
        $apikey = jeedom::getApiKey();
//        $loglevel = log::convertLogLevel(log::getLogLevel('genepi'));
        $loglevel = log::getLogLevel('genepi');

//TODO port + URL dans demon

        $cmd = "node " . self::deamon_path() . " --jeedom-url $url --loglevel $loglevel --port 8081";

        log::add('genepi', 'debug', 'Lancement démon genepi : ' . $cmd);

        putenv("JEEDOM_APIKEY=$apikey");
        $result = exec('nohup ' . $cmd . ' >> ' . log::getPathToLog('genepi_daemon') . ' 2>&1 &');
        if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
            log::add('genepi', 'error', $result);
            return false;
        }

        $i = 0;
        while ($i < 30) {
            $deamon_info = self::deamon_info();
            if ($deamon_info['state'] == 'ok') {
                break;
            }
            sleep(1);
            $i++;
        }
        if ($i >= 30) {
            log::add('genepi', 'error', 'Impossible de lancer le démon genepi', 'unableStartDeamon');
            return false;
        }

        message::removeAll('genepi', 'unableStartDeamon');
        log::add('genepi', 'info', 'Démon genepi lancé');
        return true;
    }


    public static function deamon_stop() {
        $pid = self::deamon_pid();
        log::add('genepi', 'info', 'Arrêt du démon genepi. PID : ' . $pid);

        if (!$pid) { return;}

        exec("kill $pid");
        sleep(1);

        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] == 'ok') {
            exec("kill -9 $pid");
        }
        sleep(1);

        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] == 'ok') {
            exec("sudo kill -9 $pid");
        }
    }
/*

    public static function dependancy_info() {
        $return = array();
        $return['log'] = 'rflink_dep';
        $serialport = realpath(dirname(__FILE__) . '/../../node/node_modules/serialport');
        $request = realpath(dirname(__FILE__) . '/../../node/node_modules/request');
        $return['progress_file'] = '/tmp/rflink_dep';
        if (is_dir($serialport) && is_dir($request)) {
            $return['state'] = 'ok';
        } else {
            $return['state'] = 'nok';
        }
        return $return;
    }

    public static function dependancy_install() {
        log::add('rflink','info','Installation des dépéndances nodejs');
        $resource_path = realpath(dirname(__FILE__) . '/../../resources');
        passthru('/bin/bash ' . $resource_path . '/nodejs.sh ' . $resource_path . ' > ' . log::getPathToLog('rflink_dep') . ' 2>&1 &');
    }
*/

    // reception de donnees
    public static function receiveData($recData) {
        log::add('genepi','info','receiveData : ' . json_encode($recData));

//TODO:
        $dataSet = [ $recData ];

        foreach ($dataSet as $data) {

            // equipements du meme protocole
            foreach (eqLogic::byTypeAndSearhConfiguration('genepi', $data['protocol'])  as $equip) {
                if ($equip->getConfiguration('proto') != $data['protocol']) { continue; }
//                log::add('genepi','debug','Checking param for equip - ID: ' . $equip->getId() . ' name: ' . $equip->getName() . ' param: ' . json_encode($equip->getConfiguration()));

                // check des param
                $match = true;
                foreach ($data['param'] as $paramName => $paramValue) {
                    log::add('genepi','debug',' Checking param ' . $paramName . ':' . $paramValue . ' - config: ' . $equip->getConfiguration("param.$paramName"));
                    if ($equip->getConfiguration("param.$paramName") != $paramValue) {
                        $match = false;
                        break;
                    }
                }

                if ($match) {
                    // equipement trouve
                    log::add('genepi','debug','MAJ des infos pour equipement - ID: ' . $equip->getId() . ' name: ' . $equip->getName() . ' param: ' . json_encode($equip->getConfiguration()));

                    if ($data['rolling']) {
                        // MAJ rolling
                        log::add('genepi','info','MAJ des infos de rolling pour equipement - ID: ' . $equip->getId() . ' name: ' . $equip->getName() . ' param: ' . json_encode($equip->getConfiguration()));
                        foreach ($data['rolling'] as $paramName => $paramValue) {
//                            log::add('genepi','debug',' MAJ rolling ' . $paramName . ':' . $paramValue . ' - config: ' . $equip->getConfiguration("param.$paramName"));
                            $equip->setConfiguration("param.$paramName", $paramValue);
                        }
                        $equip->save();
                    }

                    // pour chaque cmd a MAJ
                    foreach (array_keys($data['cmd']) as $cmdName) {
//                        log::add('genepi','debug',' Checking received cmd ' . $cmdName);

                        foreach ($equip->getCmd() as $cmd) {
                            if (($cmd->getType() !== 'info') || ($cmd->getConfiguration('param.cmd') !== $cmdName)) { continue; }
//                            log::add('genepi','debug','  Checking param for cmd ' . $cmd->getId() . ' name: ' . $cmd->getName() . ' param: ' . json_encode($cmd->getConfiguration()));

                            // check des cmd
                            $value = '';
                            $match = true;
                            foreach ($data['cmd'][$cmdName] as $paramName => $paramValue) {
                                if ($paramName === 'state') {
                                    $value = $paramValue;
                                    continue;
                                }
//                                log::add('genepi','debug','   Checking param ' . $paramName . ':' . $paramValue . ' - config: ' . $cmd->getConfiguration("param.$paramName"));
                                if ($cmd->getConfiguration("param.$paramName") != $paramValue) {
                                    $match = false;
                                    break;
                                }
                            }

                            if ( $match && ($value !== '')) {
                                // cmd trouve
                                log::add('genepi','info','MAJ des infos pour cmd - ID: ' . $cmd->getId() . ' name: ' . $cmd->getName() . ' param: ' . json_encode($cmd->getConfiguration()) . ' value: ' . $value);
                                $cmd->event($value);
                            }
                        }
                    }
                }
            }
        }
    }

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
      public static function cron() {

      }
     */


    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {

      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDayly() {

      }
     */

/*
curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
sudo apt-get install -y nodejs
*/

    /*     * *********************Méthodes d'instance************************* */

    public function preSave() {
//TODO: delete all param

        // suppression des params vides
        foreach ($this->getConfiguration() as $paramName => $paramValue) {
            if (preg_match("/^param\./", $paramName) and ($paramValue === '')) {
                $this->setConfiguration($paramName, null);
                log::add('genepi','debug','preSave: deleting empty config: ' . $paramName);
            }
        }
    }

    public function postAjax() {
        $cmdList = $this->getCmd();

        // mapping des value des cmd action
        foreach ($cmdList as $cmd) {
            log::add('genepi','debug','postAjax: cmd: ' . $cmd->getId() . ' - ' . $cmd->getName() . ' - ' . $cmd->getLogicalId() . ' - ' . $cmd->getType() . ' - ' . $cmd->getValue());

            if ( ($cmd->getType() === 'action') && ($cmd->getValue() == null) && (preg_match('/(.*)\.(btn|on|off|slider|color)$/', $cmd->getLogicalId(), $match)) ) {
                $infoCmd = cmd::byEqLogicIdAndLogicalId($cmd->getEqLogic_id(), $match[1]);
                if (is_object($infoCmd)) {
                    log::add('genepi','debug','postAjax: utilisation de ' . $infoCmd->getId() . ' comme valeur pour la commande ' . $cmd->getID());
                    $cmd->setValue($infoCmd->getId());
                    $cmd->save();
                    $infoCmd->setIsVisible(false);
                    $infoCmd->save();
                }
            }
        }
    }
/*
    public function preInsert() {
        
    }

    public function postInsert() {
        
    }

    public function postSave() {

    }

    public function preUpdate() {
        
    }

    public function postUpdate() {
        
    }

    public function preRemove() {
        
    }

    public function postRemove() {
        
    }
*/
    /*
     * Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {

      }
     */

    /*     * **********************Getteur Setteur*************************** */
}

class genepiCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array()) {

        if ($this->getType() == 'info') {
            return $this->getConfiguration('value');
        }

        $equip = eqLogic::byId($this->getEqLogic_id());
        $equipConfig = $equip->getConfiguration();

        $sendParam = array(
            "node"     => $equipConfig['node'],
            "protocol" => $equipConfig['proto'],
            "type"     => $equipConfig['type']
        );

        // ajout de la cmd et des param de l'equipement
        foreach ($equipConfig as $param => $value) {
            if (preg_match('/^param\.(.+)$/', $param, $match)) {
                $sendParam[$match[1]] = $value;
            }
        }

        // ajout des param de la cmd
        foreach ($this->getConfiguration() as $param => $value) {
            if (preg_match('/^param\.(.+)$/', $param, $match)) {
                $sendParam[$match[1]] = $value;
            }
            //$sendParam[$param] = $value;
        }

        // analyse logicalId
        if (!preg_match('/(.*)\.(btn|on|off|slider|color)$/', $this->getLogicalId(), $match)) { throw new Exception(__('LogicalId non reconnu : ' . $this->getLogicalId() . 'pour la commande ' . $this->getId() )); }

        // calcul de la valeur
        switch ($this->getSubType()) {
            case 'other':
                switch ($match[2]) {
                    case 'on':
                        $sendParam['value'] = 1;
                        break;
                    case 'off':
                        $sendParam['value'] = 0;
                        break;
                }
                break;
            case 'color':
                $sendParam['value'] = $_options['color'];
                break;
            case 'slider':
                $sendParam['value'] = $_options['slider'];
                break;
            default:
                throw new Exception(__('Pas de subType configure pour la commande ' . $this->getId() ));
                break;
        }

        // ancienne valeur
        $infoCmd = cmd::byId($this->getValue());
        if ( is_object($infoCmd) and ($infoCmd->getCache('value') !== '') ) {
            $sendParam['oldValue'] = $infoCmd->getCache('value');
        }


        $result = genepi::sendToDaemon('send', $sendParam);
        log::add('genepi','debug','execute response : ' . json_encode($result));

        genepi::receiveData($result);
        return true;
    }

    /*     * **********************Getteur Setteur*************************** */
}

?>
