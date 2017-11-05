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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
//TODO: delete config file quand on suppr un noeud
?>
<form class="form-horizontal">
    <fieldset>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Nom du GenePi}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="name" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Adresse IP}}</label>
            <div class="col-lg-2">
                <input class="configKey form-control" data-l1key="ip" value="80" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Global param 2}}</label>
            <div class="col-lg-2">
                <select class="configKey form-control" data-l1key="param3">
                    <option value="value1">value1</option>
                    <option value="value2">value2</option>
                </select>
            </div>
            <div class="col-lg-3">
                <a class="btn btn-success bt_check"><i class="fa fa-check-circle"></i>Test</a>
            </div>
        </div>
  </fieldset>
</form>

<script>
$('.bt_check').on('click',function(){
  $.ajax({// fonction permettant de faire de l'ajax
  type: "POST", // méthode de transmission des données au fichier php
  url: "plugins/genepi/core/ajax/genepi.ajax.php", // url du fichier php
  data: {
    action: "check",
  },
  dataType: 'json',
  global: false,
  error: function (request, status, error) {
    handleAjaxError(request, status, error);
  },
  success: function (data) { // si l'appel a bien fonctionné
  if (data.state != 'ok') {
    $('#div_alert').showAlert({message: data.result, level: 'danger'});
    return;
  } else {
    $('.bt_check').text(data.result);
//    window.location.href = 'index.php?v=d&p=plugin&id=genepi';
  }
}
});
});

/*
$('.eqLogicAction[data-action=add]').on('click', function () {
    bootbox.prompt("{{Nom de l'équipement ?}}", function (result) {
        if (result !== null) {
*/
</script>

