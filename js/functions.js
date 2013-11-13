function generateCombatResults(data){
	var res = "";
	var totalTurnos = 0;
	var rnd=Math.floor(Math.random()*1000)
	
	res += data.victorias1 + ' vs ' + data.victorias2;
	res += '<br />';
	
	res += '<a href="javascript:void($(\'#combates_' + rnd + '\').toggle())">Ver detalle de combates</a><div class="caja tabulacion oculto" id="combates_' + rnd + '">';
	
	for(i=0;i<data.combates.length;i++){
		combate = data.combates[i];
		res += "Ganador: " + combate.winner.name + "<br />";
		res += "Perdedor: " + combate.loser.name + "<br />";
		res += "Turnos: " + ((combate.turns.length)-1) + "<br />"; // Quitamos uno porque el 0 es el cálculo de iniciativa
		res += '<a href="javascript:void($(\'#combate_' + i + '_' + rnd+ '\').toggle())">Ver detalle del combate</a><div class="caja tabulacion oculto" id="combate_' + i + '_' + rnd + '">';
		for(j=0;j<combate.turns.length;j++){
			turno = combate.turns[j];
			res += 'Turno ' + j;
			res += '<ul>';
			for( cAcciones = 0 ; cAcciones < turno.actions.length ; cAcciones++ ){
				var accion = turno.actions[cAcciones];
				res += '<li>' + accion.text + '</li>';							
			}
			if( turno.bleeding1 ){
				res += '<li>Desangramiento ' + combate.pj1.name + ':' + turno.bleeding1 + 'pv</li>';
			}
			if( turno.bleeding2 ){
				res += '<li>Desangramiento ' + combate.pj2.name + ':' + turno.bleeding2 + 'pv</li>';
			}
			res += '</ul>';
		}
		res += '</div><br /><br />';
		totalTurnos += combate.turns.length-1;
	}	
	res += '</div><br /><br />';	
	res += 'Total de turnos: ' + totalTurnos + ' (promedio de ' + (totalTurnos/data.combates.length) + ' turno por combate)';	
	
	return res;
}
