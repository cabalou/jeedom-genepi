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
        return realpath(dirname(__FILE__) . "/../../daemon/config");
    }

    // Constructor - get config file and build config tree
    function __construct() {
        $this->configTree = array();

        //get Nodes
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
}


class genepi extends eqLogic {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */

    // send a jsonRPC request to Daemon
    public static function sendToDaemon($method, $param = null) {

        $daemonURL = "http://127.0.0.1:8081/";

        log::add('genepi','debug','RPC call with method: ' . $method);

        $json = json_encode([
            "jsonrpc" => "2.0",
            "id" => 1,
            "method" => $method,
            "param" => $param,
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

        if(curl_errno($curl)){
            log::add('genepi','error','Daemon error: ' . $curl_error($curl));
            return null;
        } else {
            log::add('genepi','debug','Daemon response: ' . $curl_response);
            return $curl_response;
        }
    }


    // check if genepii daemon responds
    public static function check() {
        $gateway = config::byKey('ip','genepi');
        log::add('genepi','debug','Check genepi GW ' . $gateway);
        $pouet = genepi::sendToDaemon('check', $gateway);

        return "YEP YEP YEP";
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



    /*     * *********************Méthodes d'instance************************* */

    public function preInsert() {
        
    }

    public function postInsert() {
        
    }

    public function preSave() {
        
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
        
    }

    /*     * **********************Getteur Setteur*************************** */
}

?>
