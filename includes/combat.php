<?php
class Combat{
	var $pj1;
	var $pj2;
	var $ini1;
	var $ini2;
	var $pjVivo;
	var $pjMuerto;
	var $combatLog;
	var $turno;
	var $activeTurnLog;
	
	function Combat($pj1, $pj2){
		$this->pj1 = $pj1;
		$this->pj2 = $pj2;
		combatLogger::instance()->pj1 = $pj1;
		combatLogger::instance()->pj2 = $pj2;
	}
	
	function doCombat(){
		$this->pjMuerto = null;
		$this->pjVivo = null;
		combatLogger::instance()->newTurn();
		
		// Before the combats starts, do a battlecry
		combatLogger::instance()->newAction();
		$this->pj1->battleCry();
		combatLogger::instance()->newAction();
		$this->pj2->battleCry();
		
		
		// Calculamos la iniciativa de este combate
		combatLogger::instance()->newAction();
		combatLogger::instance()->logAction( "Iniciativa de " . $this->pj1->name . ". " );
		$iPj1IniThrow = throw1o3d10( $this->pj1->objDice , BASE_DICE , $this->pj1->atkBonus );
		// TODO: Add the Blund check
		$this->ini1 = $this->pj1->ini + $iPj1IniThrow ;
		combatLogger::instance()->logAction( " + " . $this->pj1->ini . " = " .  $this->ini1);
		
		combatLogger::instance()->newAction();		
		combatLogger::instance()->logAction( "Iniciativa de " . $this->pj2->name . ". " );
		$iPj2IniThrow = throw1o3d10( $this->pj2->objDice  , BASE_DICE , $this->pj2->atkBonus);
		$this->ini2 = $this->pj2->ini + $iPj2IniThrow;
		combatLogger::instance()->logAction( " + " . $this->pj2->ini . " = " .  $this->ini2);
		
		combatLogger::instance()->newAction();
		$this->pj1->calNumActions($this->ini1);
		combatLogger::instance()->logAction( $this->pj1->name . " tendrá " . $this->pj1->actions . " accion(es)");
		
		combatLogger::instance()->newAction();
		$this->pj2->calNumActions($this->ini2);
		combatLogger::instance()->logAction( $this->pj2->name . " tendrá " . $this->pj2->actions . " accion(es)");
		
		// Cambiamos el orden si fuera necesario		
		if( $this->ini2 > $this->ini1 ){
			
			combatLogger::instance()->newAction();
			combatLogger::instance()->logAction( "Intercambiamos posiciones porque " . $this->pj2->name . " es más rápido que " . $this->pj1->name . "." );
			
			$tmpPj = $this->pj1;
			$this->pj1 = $this->pj2;
			$this->pj2 = $tmpPj;
			
			$tmpIni = $this->ini1;
			$this->ini1 = $this->ini2;
			$this->ini2 = $tmpIni;
		}
		
		
		$this->turno = 1;		
		while ( $this->pjMuerto == null ){			
			combatLogger::instance()->newTurn();
			
			// Apply bleeding effect if present
			$this->pj1->hp -= $this->pj1->wounds[ WOUND_BLEED ];
			combatLogger::instance()->currentTurn->bleeding1 = $this->pj1->wounds[ WOUND_BLEED ];
			
			// Does pj1 dies?
			if( !$this->isAlive( $this->pj1 , $this->pj2 ) ){
				continue;
			}
			
			// Apply bleeding effect if present
			$this->pj2->hp -= $this->pj2->wounds[ WOUND_BLEED ];				
			combatLogger::instance()->currentTurn->bleeding2 = $this->pj2->wounds[ WOUND_BLEED ];
			
			// Does pj1 dies?
			if( !$this->isAlive( $this->pj2 , $this->pj1 ) ){
				continue;
			}
			
			// Distance calculation
			$this->calculateDistance($this->pj1,$this->pj2);
			
			/**************
			 *  Pj1
			 **************/
			$text = "";
			
			// If in shock, will not be any action
			if( $this->pj1->wounds[ WOUND_SHOCK ] ){
				// Remove the shock status
				$this->pj1->wounds[ WOUND_SHOCK ] = false;
				$text .= "No hay acción debido a Shock";
				
				combatLogger::instance()->newAction();
				combatLogger::instance()->logAction( "No hay acción debido a Shock" );
			}
			else{				
				for ( $i = 0; $i < $this->pj1->actions ; $i++){
					combatLogger::instance()->newAction();
					
					if( $this->pj1->canAttack() ){
						$atk = $this->attack($this->pj1,$this->pj2);
						
						// Does pj2 dies?
						if( !$this->isAlive( $this->pj2 , $this->pj1 ) ){
							continue;
						}
					}
					else{
						combatLogger::instance()->logAction( $this->pj1->name . " no puede atacar " );
					}
				}				
			}
			$aTurno["accion1"] = $text;
			
			if ( $this->pjMuerto == "" ){
			
				/**************
				*  Pj2
				**************/
				
				$text = "";			
				// If in shock, will not be any action
				if( $this->pj2->wounds[ WOUND_SHOCK ] ){
					// Remove the shock status
					$this->pj2->wounds[ WOUND_SHOCK ] = false;
					$text .= "No hay acción debido a Shock";
					
					combatLogger::instance()->newAction();
					combatLogger::instance()->logAction( "No hay acción debido a Shock" );
				}
				else{
					for ( $i = 0; $i < $this->pj2->actions ; $i++){
						combatLogger::instance()->newAction();
						
						if( $this->pj2->canAttack() ){
							$atk = $this->attack($this->pj2,$this->pj1);
							
							// Does pj1 dies?
							if( !$this->isAlive( $this->pj1 , $this->pj2 ) ){
								continue;
							}
						}
						else{
							combatLogger::instance()->logAction( $this->pj2->name . " no puede atacar" );
						}
					}
				}
			}
			
			//array_push( $this->combatLog["turnos"] , $aTurno );
			
			$this->turno++ ;
		}
		$this->combatLog["ganador"] = $this->pjVivo->name . " (" . $this->pjVivo->hp . "pv restantes)";
		$this->combatLog["perdedor"] = $this->pjMuerto->name;
		
		return true;
	}
	
	function isAlive( &$defender , &$attacker ){
		$alive = true;
		if ( $defender->hp <= 0 ){	
			$defender->status = STATUS_DEAD;
			$this->pjMuerto = $defender;
			$this->pjVivo = $attacker;
			
			combatLogger::instance()->winner = $this->pjVivo;
			combatLogger::instance()->loser = $this->pjMuerto;
			
			$alive = false;
		}
		
		return $alive;
	}
	
	function attack(&$attacker, &$defender){
		$damage = 0;
		
		combatLogger::instance()->logAction( $attackLog = "Tirada de ataque de " . $attacker->name . ". " );
		$throw = throw1o3dN( $attacker->getObjDice() , BASE_DICE , $attacker->atkBonus );	
		
		// Apply the pain wound if present
		$throw -= (WOUND_PAIN_MODIFIER * $attacker->wounds[ WOUND_PAIN ] );
		
		combatLogger::instance()->logAction( ". Restamos " . (WOUND_PAIN_MODIFIER * $attacker->wounds[ WOUND_PAIN ] ) . " debido a heridas de dolor." );
		
		combatLogger::instance()->logAction( " La tirada de ataque es de " . ($throw + $attacker->attack) . " contra una defensa de " . $defender->def . "." );
		
		// Damos
		$weaponDamage = 0;
		if ( ($throw + $attacker->attack) >= $defender->def){
			$extraDices =  (int)(($throw + $attacker->attack - $defender->def) / 5);
			
			combatLogger::instance()->logAction( " Damos, superamos por " . ($throw + $attacker->attack - $defender->def) . " la defensa del enemigo. Lanzamos " . ($attacker->weaponNumDices + $extraDices) . " dados de daño. " );
			
			// Daño
			$weaponDamage = throwNdD($attacker->weaponNumDices + $extraDices,$attacker->weaponBaseDice)  + $attacker->weaponModificator;
			
			if($attacker->weaponModificator!=0)
				combatLogger::instance()->logAction("+" .$attacker->weaponModificator);
			
			combatLogger::instance()->logAction( " total de " . $weaponDamage );
			
			$damage =  $weaponDamage - $defender->absortion;
			
			if( $defender->absortion > 0 )
				combatLogger::instance()->logAction( " que menos la absorción de " . $defender->absortion . " nos deja un total de daño de " . $damage );
			
			// Hemos hecho daño?
			if ( $damage > 0 ){
				// miramos si cambiamos el dado objetivo por estar malherido
				/*
				if ( $defender->hp <= $defender->fis ){
					$defender->objDice = DICE_LOW;
				}
				*/
				// Wounded?
				if( $damage > ($defender->fis * CRITICAL_DAMAGE) ){
					$iNumWounds = ceil( $damage / ($defender->fis * CRITICAL_DAMAGE) );
					combatLogger::instance()->logAction( " que además causa " . $iNumWounds . " heridas (" );
					for( $iWound = 0 ; $iWound < $iNumWounds ; $iWound++ ){
						$wound = $defender->wound( WOUND_RANDOM );
					}
					combatLogger::instance()->logAction( " )" );
				}
				
				// Restamos vida
				(int)$defender->hp -= (int)$damage;
			
				combatLogger::instance()->logAction( ". A " . $defender->name . " le quedan ". $defender->hp . "pv" );
			}
			else{
				combatLogger::instance()->logAction( $attacker->name . " falla" );
			}
			
		}
		
		$res["throw"] = $throw;
		$res["damage"] = $damage;
		
		return $res;
	}
	
	function calculateDistance( $pj1 , $pj2 ){
		global $langCom;
		
		$haveToCalculate = true;
		
		combatLogger::instance()->newAction();
		
		// If both are in shock, no need to know who haves the distance, they'll skip turn
		if( $pj1->wounds[ WOUND_SHOCK ] && $pj2->wounds[ WOUND_SHOCK ] ){			
			$haveToCalculate = false;
			
			combatLogger::instance()->logAction( "No hay que calcular distancias porque ambos están en estado de shock" );			
		}
		
		// Only fight for distance if weapons are different
		if( $pj1->weaponType == $pj2->weaponType ){			
			$haveToCalculate = false;
			
			combatLogger::instance()->logAction( "No hay que calcular distancias porque ambos usan armas de la misma distancia" );			
		}
			
		if( $haveToCalculate ) {
			combatLogger::instance()->logAction( "Competición de táctica - " );
			// Throw while one of them win
			do{
				combatLogger::instance()->logAction( ". " . $pj1->name . " " );
				$pj1Throw = throw1o3dN( $pj1->objDice , BASE_DICE , $pj1->atkBonus );
				
				combatLogger::instance()->logAction( ". " . $pj2->name . " " );
				$pj2Throw = throw1o3dN( $pj2->objDice , BASE_DICE , $pj2->atkBonus );
				
				// Add the tactics bonus
				$pj1Throw += + $pj1->tac;
				$pj2Throw += + $pj2->tac;
				
				// Apply the pain wound if present
				$pj1Throw -= (WOUND_PAIN_MODIFIER * $pj1->wounds[ WOUND_PAIN ] );
				$pj2Throw -= (WOUND_PAIN_MODIFIER * $pj2->wounds[ WOUND_PAIN ] );
			
				combatLogger::instance()->logAction( " Malus de dolor de " . $pj1->name . " de " . (WOUND_PAIN_MODIFIER * $pj1->wounds[ WOUND_PAIN ] ) .", por lo que la tirada final es de " . $pj1Throw . "." );
				combatLogger::instance()->logAction( " Malus de dolor de " . $pj2->name . " de " . (WOUND_PAIN_MODIFIER * $pj2->wounds[ WOUND_PAIN ] ) .", por lo que la tirada final es de " . $pj2Throw . "." );
				
				// IF in shock, the throw will be 0
				$pj1Throw = $pj1->wounds[ WOUND_SHOCK ] ? 0 : $pj1Throw ;
				$pj2Throw = $pj2->wounds[ WOUND_SHOCK ] ? 0 : $pj2Throw ;				
			} while( $pj1Throw == $pj2Throw );
			
			combatLogger::instance()->logAction( ". La tirada de táctica de " . $pj1->name . " es de " . $pj1Throw . "." );
			combatLogger::instance()->logAction( " La tirada de táctica de " . $pj2->name . " es de " . $pj2Throw . "." );			
			
			if( $pj1Throw > $pj2Throw ){
				combatLogger::instance()->logAction( $pj1->name . " tiene la distancia " );
				$this->setDistance( $pj1 , $pj2 );
				combatLogger::instance()->logAction( "y escoje " . $langCom["distancia_" . $pj1->distance]["texto"] );
			}
			else{
				combatLogger::instance()->logAction( $pj2->name . " tiene la distancia " );
				$this->setDistance( $pj2 , $pj1 );
				combatLogger::instance()->logAction( "y escoje " . $langCom["distancia_" . $pj2->distance]["texto"] );
			}
			
		}
		else{
			$pj1->distance = false;
			$pj2->distance = false;
		}
	}
	
	function setDistance( $winner , $loser ){
		// With a short or long weapon, we'll move to our comfortable distance
		if( $winner->weaponType != WPN_MED ){
			if( $winner->weaponType == WPN_SHORT ){
				$iDistance = DISTANCE_SHORT;
			}
			else{
				$iDistance = DISTANCE_LONG;
			}
		}
		// With a medium weapon we'll go to the worst distance for the enemy
		else{
			if( $loser->weaponType == WPN_SHORT ){
				$iDistance = DISTANCE_LONG;
			}
			else{
				$iDistance = DISTANCE_SHORT;
			}
		}
		
		$winner->distance	= $iDistance;
		$loser->distance	= $iDistance;
	}
}

class CombatPj{
	var $name;
	var $objDice;
	var $hp;
	var $fis;
	var $absortion;
	var $attack;
	var $def;
	var $ini;
	var $tac;
	var $com;
	var $weaponNumDices;
	var $weaponBaseDice;
	var $weaponModificator;
	var $weaponType;
	var $status;
	var $actions;
	var $distance;
	var $wounds;
	var $atkBonus;
	
	function CombatPJ($name, $fis, $abs, $att, $def, $wND, $wBD,$wMD,$ini,$tac, $com , $wpn){
		$this->name = $name;
		$this->objDice = DICE_MED;
		$this->hp = $fis * HP_MOD;
		$this->fis = $fis;
		$this->absortion = $abs;
		$this->attack = $att;
		$this->def = $def;
		$this->ini = $ini;
		$this->tac = $tac;
		$this->com = $com;
		$this->weaponNumDices = $wND;
		$this->weaponBaseDice = $wBD;
		$this->weaponModificator = $wMD;
		$this->weaponType = $wpn;
		$this->status = STATUS_ALIVE;
		$this->actions = 1;
		$this->atkBonus = 0;
		// TODO Mejorar esta mierda
		$this->wounds = array(
			1 => 0,
			2 => 0,
			3 => 0,
		);
	}
	
	function getObjDice(){
		// By default, it'll be the med dice
		$this->objDice = DICE_MED;
		
		if( $this->weaponType == WPN_SHORT ){
			if( $this->distance === DISTANCE_SHORT ){
				$this->objDice = DICE_HIG;
			}
			elseif( $this->distance === DISTANCE_MED ){
				$this->objDice = DICE_LOW;
			}
		}
		elseif( $this->weaponType == WPN_LONG ){
			if( $this->distance === DISTANCE_LONG ){
				$this->objDice = DICE_HIG;
			}
			elseif( $this->distance === DISTANCE_MED ){
				$this->objDice = DICE_LOW;
			}
		}
		
		return $this->objDice;
	}
	
	function calNumActions($iniThrow){
		// Minimum of 1 turn
		$this->actions = max(1,intval($iniThrow/10));
	}
	
	function wound( $woundType ){
		// If random, pick one of the three types
		if( $woundType == WOUND_RANDOM ){
			// TODO: Mejorar esto, cómo haya más heridas la liamos
			$woundType = rand(1,3);
		}
		
		combatLogger::instance()->logAction( " " . woundsName($woundType) );
		
		switch( $woundType ){
			case WOUND_BLEED:
				combatLogger::instance()->logAction( " que causará una pérdida de " );
				$bloodLost = throw1o3dN( DICE_MED , WOUND_BLOOD_LOST_DICE );				
				$this->wounds[ WOUND_BLEED ] += $bloodLost;
				combatLogger::instance()->logAction( " puntos de vida más por asalto. El personje pierde ahora mismo " .$this->wounds[ WOUND_BLEED ] . "pv/as" );
				break;
			case WOUND_SHOCK:
				$this->wounds[ WOUND_SHOCK ] = true;
				break;
			case WOUND_PAIN:		
				$this->wounds[ WOUND_PAIN ]++;
				break;
		}
		
		return $woundType;
		
	}
	
	function canAttack(){
		$bCanAttack = false;
		
		switch( $this->weaponType ){
			case WPN_SHORT:
				$bCanAttack = $this->distance == DISTANCE_SHORT;
			break;
			case WPN_MED:
				$bCanAttack = true;
			break;
			case WPN_LONG:
				$bCanAttack = $this->distance == DISTANCE_LONG;
			break;
		}
		
		return $bCanAttack;
	}
	
	function battleCry(){
		combatLogger::instance()->logAction( "Crit de guerra de " . $this->name . " " );
		$iBattleCryThrow = throw1o3d10( $this->objDice );
		$iBattleCryThrow += $this->com;
		combatLogger::instance()->logAction( " + " . $this->com . " = " . $iBattleCryThrow . ".");
		// We'll have a bonus level for every 5 points up from 10. Ex. 15 = 1 level
		$iBonusLevels = floor( ($iBattleCryThrow-10) / 5);
		$oBonusD6Element = new D6ScaleElement( $iBonusLevels );
		combatLogger::instance()->logAction( " resultant en una bonificació de ");		
		$iBonus = $oBonusD6Element->throwDices();
		combatLogger::instance()->logAction( " = " . $iBonus . ".");
		
		$this->atkBonus = $iBonus;
	}
}

class combatLogger {
	
	var $currentTurn;
	var $winner;
	var $loser;
	var $pj1;
	var $pj2;
	
	public static function Instance()
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new combatLogger();
        }
        return $inst;
    }
    
    public function __destruct(){
		static $inst = null;
	}
    
    /**
     * Private ctor so nobody else can instance it
     *
     */
    private function __construct()
    {
		$this->turns = array();
    }
	    
    public function logAction( $msg ){
		$this->currentTurn->logAction( $msg );
	}
	
	public function newTurn(){
		$turn = new combatTurn();
		$this->currentTurn = &$turn;
		
		array_push( $this->turns , $this->currentTurn );
	}
	
	public function newAction(){
		$this->currentTurn->newAction();
	}
}

class combatTurn{
	var $actions;
	var $currentAction;
	var $bleeding1;
	var $bleeding2;
	
	public function __construct()
    {
		$this->actions = array();
    }
	
	public function logAction( $msg ){
		$this->currentAction->logAction( $msg );
	}
	
	public function newAction(){
		$action = new combatAction();
		$this->currentAction = &$action;
		array_push( $this->actions , $this->currentAction );
	}
}

class combatAction{
	var $text;
	
	public function __construct()
    {
		$text = "";
    }
	
	public function logAction( $msg ){
		$this->text .= $msg;
	}
}

class D6ScaleElement {
	var $iNumDices;
	var $iBonus;
	
	public function __construct( $iNumLevels ){
		$this->iNumDices = floor( $iNumLevels/4 );
		$this->iBonus = $iNumLevels%4;
	}
	
	public function throwDices(){
		return throwNdD( $this->iNumDices , 6 , $this->iBonus );
	}
}
?>
