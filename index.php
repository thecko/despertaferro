<?php

include("./includes/config.php");
include("./includes/functions.php");
include("./includes/objects.php");

session_start();

// Look for the language
if ( !isset($_SESSION["lang"]) ){
	$data = getLanguages("data");
	if ( $data ){
		$lang = $data[0][1];
	}
	else {
		$lang = "es";
	}
	
	$_SESSION["lang"] = $lang;
}
else {
	// Does the user want to change it?
	$lang = getVar("lang","es");
	$_SESSION["lang"] = $lang;
}

$langCom = parseFile("./data/" . $_SESSION["lang"] . "/common.php");
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="./css/jquery-ui-1.8.2.custom.css">
		<link rel="stylesheet" type="text/css" href="./css/style.css">
		<script src="./js/jquery-1.4.2.min.js" language="javascript"></script>
		<script src="./js/jquery-ui-1.8.2.custom.min.js" language="javascript"></script>
		<script src="./js/functions.js" language="javascript"></script>
		<script language="javascript">
		langCom = <?=generateLangJSON($langCom)?>;
		$(function() {
			$("input[type=button]").button();
		});
		
		function generateCombat(fId){
			formu = $('#'+fId);
			
			$('#test').val('func=doCombat&' + formu.serialize());
			
			$.ajax({
				url: './ajax.php',
				type: 'get',
				async: true,
				data: 'func=doCombat&' + formu.serialize(),
				dataType: 'json',
				success: function(data){					
					diag = $("<div />");
					diag.attr("id","combate");
					diag.attr("title", $('#name1').val() + " vs " + $('#name2').val());
					diag.html(generateCombatResults(data));
					diag.dialog({
						height: 465,
						width: 600						
					});
				},
				error: function(){
					alert(langCom.errData);
				}
			});
		}
		</script>
	</head>
	<body>
		<div id="site">
			<h1><?=$langCom["genAle"]["texto"]?></h1>
			<a href="index.php?lang=es"><img src="./images/flags/espana.gif" border="0"></a> | <a href="index.php?lang=en"><img src="./images/flags/inglaterra.gif" border="0"></a>
			
			<fieldset class="ui-widget ui-widget-content">
				<legend class="">
					<?=$langCom["generador_combates"]["texto"]?> (<?=$langCom["alpha"]["texto"]?>)
				</legend>
				<table>
				<tr>
					<td>
						<form id="frmCombates" name="frmCombates">
						<table border="0" cellspacing="1" class="tblFicha">
						<tr>
							<th>Atr</th>
							<th>Pj1</th>
							<th>Pj2</th>
						</tr>
						<tr>
							<td><?=$langCom["nombre"]["texto"]?></td>
							<td>
								<input type="text" name="name1" id="name1" value="PJ 1">
							</td>							
							<td>
								<input type="text" name="name2" id="name2" value="PJ 2">
							</td>
						</tr>
						<tr>
							<td><?=$langCom["fisico"]["texto"]?></td>
							<td>
								<input type="text" name="fis1" id="fis1" value="8">
							</td>							
							<td>
								<input type="text" name="fis2" id="fis2" value="8">
							</td>
						</tr>
						<tr>
							<td><?=$langCom["absorcion"]["texto"]?></td>
							<td>
								<input type="text" name="abs1" id="abs1" value="0">
							</td>							
							<td>
								<input type="text" name="abs2" id="abs2" value="0">
							</td>
						</tr>
						<tr>
							<td><?=$langCom["ataque"]["texto"]?></td>
							<td>
								<input type="text" name="att1" id="att1" value="12">
							</td>							
							<td>
								<input type="text" name="att2" id="att2" value="12">
							</td>
						</tr>
						<tr>
							<td><?=$langCom["defensa"]["texto"]?></td>
							<td>
								<input type="text" name="def1" id="def1" value="16">
							</td>							
							<td>
								<input type="text" name="def2" id="def2" value="16">
							</td>
						</tr>
						<tr>
							<td><?=$langCom["iniciativa"]["texto"]?></td>
							<td>
								<input type="text" name="ini1" id="ini1" value="8">
							</td>							
							<td>
								<input type="text" name="ini2" id="ini2" value="8">
							</td>
						</tr>
						<tr>
							<td><?=$langCom["tactica"]["texto"]?></td>
							<td>
								<input type="text" name="tac1" id="tac1" value="9">
							</td>							
							<td>
								<input type="text" name="tac2" id="tac2" value="9">
							</td>
						</tr>
						<tr>
							<td><?=$langCom["comandament"]["texto"]?></td>
							<td>
								<input type="text" name="com1" id="com1" value="8">
							</td>							
							<td>
								<input type="text" name="com2" id="com2" value="8">
							</td>
						</tr>
						<tr>
							<td><?=$langCom["arma"]["texto"]?></td>
							<td>
								<select name="wpn1" id="wpn1">
									<option value="<?=WPN_SHORT?>"><?=$langCom["arma_corta"]["texto"]?></option>
									<option value="<?=WPN_MED?>"><?=$langCom["arma_media"]["texto"]?></option>
									<option value="<?=WPN_LONG?>"><?=$langCom["arma_larga"]["texto"]?></option>
								</select>
							</td>							
							<td>
								<select name="wpn2" id="wpn2">
									<option value="<?=WPN_SHORT?>"><?=$langCom["arma_corta"]["texto"]?></option>
									<option value="<?=WPN_MED?>"><?=$langCom["arma_media"]["texto"]?></option>
									<option value="<?=WPN_LONG?>"><?=$langCom["arma_larga"]["texto"]?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td><?=$langCom["danyo"]["texto"]?></td>
							<td>
								<input type="text" name="wND1" id="wND1" size="2" value="2">D<input type="text" name="wBD1" id="wBD1" size="2" value="6">+<input type="text" name="wMD1" id="wMD1" size="2" value="0">
							</td>							
							<td>
								<input type="text" name="wND2" id="wND2" size="2" value="2">D<input type="text" name="wBD2" id="wBD2" size="2" value="6">+<input type="text" name="wMD2" id="wMD2" size="2" value="0">
							</td>
						</tr>						
						</table>
						<?=$langCom["num_combates"]["texto"]?> <input type="text" name="num" id="num" size="2" maxlength="3" value="1">
						</form>
					</td>
				</tr>
				<tr>
					<td>
						<input type="button" onclick="javascript: generateCombat('frmCombates')" value="<?=$langCom["genCombate"]["texto"]?>" /><br />
						<input type="hidden" name="test" id="test" />
					</td>
				</tr>
				</table>
			</fieldset>
		</div>
	</body>
</html>
