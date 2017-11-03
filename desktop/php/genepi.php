<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('genepi');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());


// Recuperation des noeuds genepi et de leur capa
$genepiConfig = new genepiConfig();

?>
       <div class="form-group debuggen">
       </div>


<div class="row row-overflow">
  <div class="col-lg-2">
    <div class="bs-sidebar">
      <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
        <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un dispositif}}</a>
        <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
        <?php
foreach ($eqLogics as $eqLogic) {
	echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
}
?>
     </ul>
   </div>
 </div>
 <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
   <legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
   <div class="eqLogicThumbnailContainer">
    <div class="cursor eqLogicAction" data-action="add" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
      <i class="fa fa-plus-circle" style="font-size : 5em;color:#94ca02;"></i>
      <br>
      <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;;color:#94ca02">{{Ajouter}}</span>
    </div>
    <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="text-align: center; background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
      <i class="fa fa-wrench" style="font-size : 5em;color:#767676;"></i>
      <br>
      <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676">{{Configuration}}</span>
    </div>
  </div>
  <legend><i class="icon meteo-soleil"></i>  {{Mes GenePi}}
  </legend>
  <div class="eqLogicThumbnailContainer">
    <?php
foreach ($eqLogics as $eqLogic) {
	$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
	echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="text-align: center; background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
	echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="95" />';
	echo "<br>";
	echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;">' . $eqLogic->getHumanName(true, true) . '</span>';
	echo '</div>';
}
?>
 </div>
</div>

<div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
 <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
 <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
 <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a>
 <ul class="nav nav-tabs" role="tablist">
   <li role="presentation"><a class="eqLogicAction cursor" aria-controls="home" role="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
   <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fa fa-tachometer"></i> {{Equipement}}</a></li>
   <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
 </ul>
 <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
  <div role="tabpanel" class="tab-pane active" id="eqlogictab">
    <br/>
    <form class="form-horizontal">
      <fieldset>
        <legend><i class="fa fa-wrench"></i>  {{Configuration}}</legend>
        <div class="form-group">
          <label class="col-sm-3 control-label">{{Nom de l'équipement GenePi}}</label>
          <div class="col-sm-3">
            <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
            <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement GenePi}}"/>
          </div>
        </div>
        <div class="form-group">
          <label class="col-sm-3 control-label" >{{Objet parent}}</label>
          <div class="col-sm-3">
            <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
              <option value="">{{Aucun}}</option>
              <?php
foreach (object::all() as $object) {
	echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
}
?>
           </select>
         </div>
       </div>
       <div class="form-group">
        <label class="col-sm-3 control-label">{{Catégorie}}</label>
        <div class="col-sm-6">
          <?php
foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
          echo '<label class="checkbox-inline">';
          echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
          echo '</label>';
}
          ?>
        </div>
       </div>
       <div class="form-group">
        <label class="col-sm-3 control-label"></label>
        <div class="col-sm-9">
          <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
          <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
        </div>
       </div>


       <legend><i class="fa fa-wrench"></i>  {{Equipement}}</legend>

       <div class="form-group">
        <label class="col-sm-3 control-label" >{{Noeud GenePi}}</label>
        <div class="col-sm-3">
         <select id="sel_node" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="node">
           <?php
             foreach ($genepiConfig->getNodes() as $node) {
               echo '<option value="' . "$node" . '">' . "$node" . '</option>';
             }
           ?>
         </select>
        </div>
       </div>

       <div class="form-group">
        <label class="col-sm-3 control-label" >{{Protocole}}</label>
        <div class="col-sm-3">
         <select id="sel_proto" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="proto">
           <?php
             foreach ($genepiConfig->getNodes() as $node) {
               foreach ($genepiConfig->getProto($node) as $proto) {
                 echo '<option class="genepi-proto" data-node="' . $node . '" value="' . $proto . '">' . "$proto" . '</option>';
               }
             }
           ?>
         </select>
        </div>
       </div>

       <div class="form-group">
        <label class="col-sm-3 control-label" >{{Type d'équipement}}</label>
        <div class="col-sm-3">
         <select id="sel_type" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="type">
           <?php
             foreach ($genepiConfig->getNodes() as $node) {
               foreach ($genepiConfig->getProto($node) as $proto) {
                 foreach ($genepiConfig->getType($node, $proto) as $type) {
                   echo '<option class="genepi-type" data-proto="' . "$node.$proto" . '" value="' . $type . '">' . "$type" . '</option>';
                 }
               }
             }
           ?>
         </select>
        </div>
       </div>


       <legend><i class="fa fa-wrench"></i>  {{Paramètres de l'équipement}}</legend>
       <div class="form-group">
        <?php
         foreach ($genepiConfig->getNodes() as $node) {
           foreach ($genepiConfig->getProto($node) as $proto) {
             foreach ($genepiConfig->getType($node, $proto) as $type) {
               foreach (array_merge($genepiConfig->getParam($node, $proto, $type), $genepiConfig->getRoll($node, $proto, $type)) as $paramName => $paramType) {

                 echo '<div class="form-group genepi-param" data-type="' . "$node.$proto.$type" . '">';
                 echo ' <label class="col-sm-3 control-label">{{' . $paramName . '}}</label>';
                 echo ' <div class="col-sm-3">';

                 switch ($paramType) {
                   case ('string') :
                     echo '  <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="param.' . $paramName . '"/>';
                     break;
                   case ('number') :
                     echo '  <input type="number" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="param.' . $paramName . '"/>';
                     break;
                   case (preg_match('/^\[(\d+)\-(\d+)\]$/', $paramType, $match) ? true : false) :
                     echo '  <input type="number" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="param.' . $paramName . '" placeholder="Valeur entre ' . $match[1] . ' et ' . $match[2] . '"/>';
                     break;
                   default:
                     echo ' <label class="col-sm-3 control-label">{{' . $paramType . ' inconnu}}</label>';
                     break;
                 }

                 echo ' </div>';
                 echo '</div>';
               }
             }
           }
         }
        ?>
       </div>

      </fieldset>
    </form>
   </div>

<div role="tabpanel" class="tab-pane" id="commandtab">
  <br/>
  <form class="form-horizontal">
    <fieldset>
       <legend><i class="fa fa-wrench"></i>  {{Choix des commandes}}</legend>
       <div class="form-group">
        <?php
         foreach ($genepiConfig->getNodes() as $node) {
           foreach ($genepiConfig->getProto($node) as $proto) {
             foreach ($genepiConfig->getType($node, $proto) as $type) {
               foreach ($genepiConfig->getCmd($node, $proto, $type) as $cmdName => $cmdObj) {

                 echo '<div class="form-group genepi-cmd" data-type="' . "$node.$proto.$type" . '">';
                 echo ' <label class="genepi-cmd-name col-sm-3 control-label">{{' . $cmdName . '}}</label>';
                 echo ' <div class="col-sm-3">';

                 foreach ($cmdObj as $cmdParam => $cmdType) {
                   if (($cmdParam === 'action') || ($cmdParam === 'state')) {
                     echo '<input class="genepi-cmd-' . $cmdParam . '" data-cmd-param-type="' . $cmdType . '" style="display : none;">';
                   } else {
                     switch ($cmdType) {
                       case ('string') :
                         echo '  <input type="text" class="genepi-cmd-attr col-sm3 form-control" data-cmd-param-name="' . $cmdParam . '"/>';
                         break;
                       case ('number') :
                         echo '  <input type="number" class="genepi-cmd-attr col-sm3 form-control" data-cmd-param-name="' . $cmdParam . '"/>';
                         break;
                       case (preg_match('/^\[(\d+)\-(\d+)\]$/', $cmdType, $match) ? true : false) :
                         echo '  <input type="number" class="genepi-cmd-attr col-sm3 form-control" data-cmd-param-name="' . $cmdParam . '" placeholder="Valeur entre ' . $match[1] . ' et ' . $match[2] . '"/>';
                         break;
                       default:
                         echo ' <label class="col-sm-3 control-label">{{' . $cmdType . ' inconnu}}</label>';
                         break;
                     }
                   }
                 }
                 echo ' </div>';
                 echo ' <a class="genepi-cmd-add btn btn-success col-sm-3">Ajouter</a>';
                 echo '</div>';
               }
             }
           }
         }
       ?>



    </fieldset>
  </form>
  <legend><i class="fa fa-wrench"></i>  {{Commandes}}</legend>
  <table id="table_cmd" class="table table-bordered table-condensed">
    <thead>
      <tr>
        <th>{{ID}}</th>
        <th>{{LogicalID}}</th>
        <th>{{Nom}}</th>
        <th>{{Type}}</th>
        <th>{{SubType}}</th>
        <th>{{Value}}</th>
        <th>{{Configuration}}</th>
        <th>{{Options}}</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    </tbody>
  </table>

</div>
</div>

</div>
</div>

<?php include_file('desktop', 'genepi', 'js', 'genepi');?>
<?php include_file('core', 'plugin.template', 'js');?>
