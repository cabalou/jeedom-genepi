
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

//$('.debuggen').append($("<p></p>").text("Debug : " + $(this).val()) );


// Node selection
$('#sel_node').on('change', function(){
    $('.genepi-proto').hide();
    if ($(this).val()) {
        $('.genepi-proto[data-node="' + $(this).val() + '"]').show();
        $('#sel_proto').val(null);
        $('#sel_type').val(null);
    }
    $('#sel_proto').change();
});


//Proto selection
$('#sel_proto').on('change', function(){
    $('.genepi-type').hide();
    if ($(this).val()) {
        $('.genepi-type[data-proto="' + $('#sel_node').val() + '.' + $(this).val() + '"]').show();
        $('#sel_type').val(null);
    }
    $('#sel_type').change();
});


//Type selection
$('#sel_type').on('change', function(){
    //equip params
    $('.genepi-param').hide();
    $('.genepi-param').find('input').removeClass('eqLogicAttr').val(null);
    if ($(this).val()) {
        $('.genepi-param[data-type="' + $('#sel_node').val() + '.' + $('#sel_proto').val() + '.' + $(this).val() + '"]').show();
        $('.genepi-param[data-type="' + $('#sel_node').val() + '.' + $('#sel_proto').val() + '.' + $(this).val() + '"]').find('input').addClass('eqLogicAttr');
    }

    //choix cmd
    $('.genepi-cmd').hide();
    if ($(this).val()) {
        $('.genepi-cmd[data-type="' + $('#sel_node').val() + '.' + $('#sel_proto').val() + '.' + $(this).val() + '"]').show();
    }
});


//ajout des commandes
$('.genepi-cmd-add').on('click', function(){

$('.debuggen').empty();

    var param = {};

    $(this).closest('.genepi-cmd').find('.genepi-cmd-attr').each(function () {
        if ($(this).val() == '') {
            alert("Le champ ne peut être vide");
            throw 'Le champ ne peut être vide';
//TODO: faire mieux
        }

        param[$(this).attr('data-cmd-param-name')] = $(this).val();
    });


    var cmdName    = $(this).closest('.genepi-cmd').find('.genepi-cmd-name').text();
    var actionType = $(this).closest('.genepi-cmd').find('.genepi-cmd-action').attr('data-cmd-param-type');
    var stateType  = $(this).closest('.genepi-cmd').find('.genepi-cmd-state' ).attr('data-cmd-param-type');

    if (typeof(actionType) !== 'undefined') {
        switch (actionType) {
          case 'button':
//TODO name
            addCmdToTableDebug({name: cmdName, type: 'action', subType: 'other', configuration: param});
            break;
          case 'toggle':
            addCmdToTableDebug({name: cmdName, type: 'action', subType: 'other', configuration: param});
            addCmdToTableDebug({name: cmdName, type: 'action', subType: 'other', configuration: param});
            break;
          case 'slider':
            addCmdToTableDebug({name: cmdName, type: 'action', subType: 'slider', configuration: param});
            break;
          case 'color':
            addCmdToTableDebug({name: cmdName, type: 'action', subType: 'color', configuration: param});
            break;
          default:
            alert('Type d\'action non reconnu : ' + actionType);
            break;
        }
    }

    if (typeof(stateType) !== 'undefined') {
        switch (stateType) {
          case 'toggle':
            addCmdToTableDebug({name: cmdName, type: 'info', subType: 'binary', configuration: param});
            break;
          case 'slider':
            break;
          case 'color':
            break;
          default:
            alert('Type d\'info non reconnu : ' + stateType);
            break;
        }
    }


});

function addCmdToTableDebug(_cmd) {
$('.debuggen').append($("<p></p>").text("Addcmd: " + JSON.stringify(_cmd) ));
}

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="subType" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}"></td>';
    tr += '<td>';
    if(!isset(_cmd.type) || _cmd.type == 'info' ){
        tr += '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> ';
    }
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}
