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
            combatLogger::instance()->newCombat();
            combatLogger::instance()->newTurn();

            // Before the combats starts, do a battlecry
            combatLogger::instance()->newAction();
            $this->pj1->battleCry();
            combatLogger::instance()->newAction();
            $this->pj2->battleCry();

            // Initiative calculation for this combat
            combatLogger::instance()->newAction();
            $this->ini1 = $this->pj1->initiative();
            combatLogger::instance()->newAction();
            $this->ini2 = $this->pj2->initiative();

            // Number of actions
            combatLogger::instance()->newAction();
            $this->pj1->calNumActions();
            combatLogger::instance()->newAction();
            $this->pj2->calNumActions();

            // Cambiamos el orden si fuera necesario		
            if( $this->ini2 > $this->ini1 ){

                combatLogger::instance()->newAction();
                combatLogger::instance()->logAction( "Intercanviem possicions per que" . $this->pj2->name . " es més ràpid que " . $this->pj1->name . "." );

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
                combatLogger::instance()->setBleeding( 1 , $this->pj1->wounds[ WOUND_BLEED ] );

                // Does pj1 dies?
                if( !$this->isAlive( $this->pj1 , $this->pj2 ) ){
                    continue;
                }

                // Apply bleeding effect if present
                $this->pj2->hp -= $this->pj2->wounds[ WOUND_BLEED ];
                combatLogger::instance()->setBleeding( 2 , $this->pj2->wounds[ WOUND_BLEED ] );

                // Does pj2 dies?
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
                    combatLogger::instance()->newAction();
                    combatLogger::instance()->logAction( "No hi ha acció degut a conmoció" );
                }
                // Can't attack due to initiative blunt
                elseif( $this->pj1->iIniBonus == THROW_BLUNT && $this->turno==1){
                    combatLogger::instance()->newAction();
                    combatLogger::instance()->logAction( "No hi ha acció degut a pifia a iniciativa" );
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
                            combatLogger::instance()->logAction( $this->pj1->name . " no pot atacar " );
                        }
                    } // for every action
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

                        combatLogger::instance()->newAction();
                        combatLogger::instance()->logAction( "No hi ha acció degut a conmoció" );
                    }
                    // Can't attack due to initiative blunt
                    elseif( $this->pj2->iIniBonus == THROW_BLUNT && $this->turno==1){
                        combatLogger::instance()->newAction();
                        combatLogger::instance()->logAction( "No hi ha acció degut a pifia a iniciativa" );
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
                                combatLogger::instance()->logAction( $this->pj2->name . " no pot atacar" );
                            }
                        } // For every action
                    }
                }
                $this->turno++ ;
            }
            $this->combatLog["ganador"] = $this->pjVivo->name . " (" . $this->pjVivo->hp . "pv restants)";
            $this->combatLog["perdedor"] = $this->pjMuerto->name;

            return true;
	} // Do combat
	
	function isAlive( &$defender , &$attacker ){
            $alive = true;
            if ( $defender->hp <= 0 ){	
                $defender->status = STATUS_DEAD;
                $this->pjMuerto = $defender;
                $this->pjVivo = $attacker;

                combatLogger::instance()->setWinner($this->pjVivo);
                combatLogger::instance()->setLoser($this->pjMuerto);

                $alive = false;
            }

            return $alive;
	}
	
	function attack(&$attacker, &$defender){
            $damage = 0;
		
            combatLogger::instance()->logAction( $attackLog = "Tirada d'atac de " . $attacker->name . ". " );
            $throw = throw1o3dN( $attacker->getObjDice() , BASE_DICE , $attacker->atkBonus );	
            
            if( $throw != THROW_FAIL && $throw != THROW_BLUNT ){
		
                // Apply the pain wound if present
                $throw -= (WOUND_PAIN_MODIFIER * $attacker->wounds[ WOUND_PAIN ] );

                combatLogger::instance()->logAction( ". Restem " . (WOUND_PAIN_MODIFIER * $attacker->wounds[ WOUND_PAIN ] ) . " degut a ferides de dolor." );

                combatLogger::instance()->logAction( " La tirada d'atac es de " . ($throw + $attacker->attack) . " contra una defensa de " . $defender->def . "." );

                // Hit
                $weaponDamage = 0;
                if ( ($throw + $attacker->attack) >= $defender->def){
                    $extraDices =  (int)(($throw + $attacker->attack - $defender->def) / 5);

                    combatLogger::instance()->logAction( " Donem, superem per " . ($throw + $attacker->attack - $defender->def) . " la defensa de l'enemic. Llancem " . ($attacker->weaponNumDices + $extraDices) . " daus de mal. " );

                    // Damage
                    $weaponDamage = throwNdD($attacker->weaponNumDices + $extraDices,$attacker->weaponBaseDice)  + $attacker->weaponModificator;

                    if($attacker->weaponModificator!=0)
                    combatLogger::instance()->logAction("+" .$attacker->weaponModificator);

                    combatLogger::instance()->logAction( " total de " . $weaponDamage );

                    $damage =  $weaponDamage - $defender->absortion;
                    if( $defender->absortion > 0 ){
                        combatLogger::instance()->logAction( " que menys l'absorció de " . $defender->absortion . " ens deixa un total de mal de " . $damage );
                    }

                    // Hemos hecho daño?
                    if ( $damage > 0 ){
                        // miramos si cambiamos el dado objetivo por estar malherido
                        /*
                            if ( $defender->hp <= $defender->fis ){
                                $defender->objDice = DICE_LOW;
                            }
                        */
                        // Wounded?
                        if( $damage >= ($defender->fis * CRITICAL_DAMAGE) ){
                            $iNumWounds = floor( $damage / ($defender->fis * CRITICAL_DAMAGE) );
                            combatLogger::instance()->logAction( " que a més a més causa " . $iNumWounds . " ferides (" );
                            for( $iWound = 0 ; $iWound < $iNumWounds ; $iWound++ ){
                                $wound = $defender->wound( WOUND_RANDOM );
                            }
                            combatLogger::instance()->logAction( " )" );
                        }

                        // Restamos vida
                        (int)$defender->hp -= (int)$damage;

                        combatLogger::instance()->logAction( ". A " . $defender->name . " li queden ". $defender->hp . "pv" );
                    }
                    else{
                        combatLogger::instance()->logAction( $attacker->name . " falla" );
                    }
                }
            } // Fail or blunt ?
            else{
                combatLogger::instance()->logAction( $attacker->name . " falla" );
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
                combatLogger::instance()->logAction( "No hi ha que calcular distancies per que ambdós estan conmocionats" );
            }

            // Only fight for distance if weapons are different
            if( $pj1->weaponType == $pj2->weaponType ){
                $haveToCalculate = false;
                combatLogger::instance()->logAction( "No hi ha que calcular distancies per que ambdós usen armes de la mateixa distancia" );			
            }

            if( $haveToCalculate ) {
                combatLogger::instance()->logAction( "Competició de maniobres - " );
                // Throw while one of them win
                do{
                    combatLogger::instance()->logAction( ". " . $pj1->name . " " );
                    
                    // IF in shock, the throw will be 0
                    if( $pj1->wounds[ WOUND_SHOCK ] ){
                        $pj1Throw = 0;
                    } else {
                        $pj1Throw = throw1o3dN( $pj1->objDice , BASE_DICE , $pj1->atkBonus );
                        if( $pj1Throw == THROW_BLUNT ){
                        } else {
                            // Add the tactics bonus
                            $pj1Throw += + $pj1->tac;
                        
                            // Apply the pain wound if present
                            $pj1Throw -= (WOUND_PAIN_MODIFIER * $pj1->wounds[ WOUND_PAIN ] );
                        
                            combatLogger::instance()->logAction( " Malus de dolor de " . $pj1->name . " de " . (WOUND_PAIN_MODIFIER * $pj1->wounds[ WOUND_PAIN ] ) .", pel que la tirada final es de " . $pj1Throw . "." );
                        }
                    }
                    
                    combatLogger::instance()->logAction( ". " . $pj2->name . " " );
                    // IF in shock, the throw will be 0
                    if( $pj2->wounds[ WOUND_SHOCK ] ){
                        $pj1Throw = 0;
                    } else {                        
                        $pj2Throw = throw1o3dN( $pj2->objDice , BASE_DICE , $pj2->atkBonus );
                        
                        if( $pj2Throw == THROW_BLUNT ) {
                        }
                        else {
                            // Add the tactics bonus
                            $pj2Throw += + $pj2->tac;
                        
                            // Apply the pain wound if present
                            $pj2Throw -= (WOUND_PAIN_MODIFIER * $pj2->wounds[ WOUND_PAIN ] );
                            combatLogger::instance()->logAction( " Malus de dolor de " . $pj2->name . " de " . (WOUND_PAIN_MODIFIER * $pj2->wounds[ WOUND_PAIN ] ) .", pel que la tirada final es de " . $pj2Throw . "." );
                        }
                    }
                    
                } while( $pj1Throw == $pj2Throw );

                combatLogger::instance()->logAction( ". La tirada de tàctica de " . $pj1->name . " es de " . $pj1Throw . "." );
                combatLogger::instance()->logAction( ". La tirada de tàctica de " . $pj2->name . " es de " . $pj2Throw . "." );			

                if( $pj1Throw > $pj2Throw ){
                    combatLogger::instance()->logAction( $pj1->name . " te la distancia " );
                    $this->setDistance( $pj1 , $pj2 );
                    combatLogger::instance()->logAction( "i escull " . $langCom["distancia_" . $pj1->distance]["texto"] );
                }
                else{
                    combatLogger::instance()->logAction( $pj2->name . " te la distancia " );
                    $this->setDistance( $pj2 , $pj1 );
                    combatLogger::instance()->logAction( "i escull " . $langCom["distancia_" . $pj2->distance]["texto"] );
                }
            }
            else{
                $pj1->distance = false;
                $pj2->distance = false;
            }
	} // End Calculate distance
	
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
        var $iIniBonus;
	
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
	
	function calNumActions(){
            // Minimum of 1 turn
            $iNumActions = 1;
            if( $this->iIniBonus > 0 ){
		$iNumActions = max(1,intval($this->iIniBonus/10));
            }
            $this->actions = $iNumActions;
            combatLogger::instance()->logAction( $this->name . " tendrá " . $this->actions . " accion(es)");
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
        
        function initiative(){
            $iIni = THROW_FAIL;
            
            combatLogger::instance()->logAction( "Iniciativa de " . $this->name . ". " );
            
            $iIniThrow = throw1o3d10( $this->objDice , BASE_DICE , $this->atkBonus );
            if( $iIniThrow > 0 ){
                $iIni = $this->ini + $iIniThrow ;
            }
            else{
                $iIni = $iIniThrow;
            }
            combatLogger::instance()->logAction( " + " . $this->ini . " = " .  $iIni );
            
            $this->iIniBonus = $iIni;
            
            return $iIni;
        }
}

class combatLogger {
    var $currentCombat;
    var $winner;
    var $loser;
    var $pj1;
    var $pj2;
    var $combats;
    
    private function __construct(){
        $this->combats = array();
    }
        
    public static function Instance()
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new combatLogger();
        }
        return $inst;
    }
    
    public function newCombat(){
        $combat = new combatLog( $this->pj1 , $this->pj2 );
        $this->currentCombat = &$combat;

        array_push( $this->combats , $this->currentCombat );
    }
    
    public function newTurn(){
        $this->currentCombat->newTurn();
    }
    
    public function newAction(){
        $this->currentCombat->newAction();
    }
    
    public function logAction( $sText ){
        $this->currentCombat->logAction( $sText );
    }
    
    public function setBleeding( $iNumPj , $iBleeding ){
        $sProperty = "bleeding$iNumPj";
        $this->currentCombat->currentTurn->$sProperty = $iBleeding;
    }
    
    public function setWinner( $oPj ){
        $this->currentCombat->winner = $oPj;
    }
    
    public function setLoser( $oPj ){
        $this->currentCombat->loser = $oPj;
    }
}

class combatLog {
	
    var $currentTurn;
    var $winner;
    var $loser;
    var $pj1;
    var $pj2;
    
    function __construct( $pj1 , $pj2)
    {
        $this->pj1      = $pj1;
        $this->pj2      = $pj2;
        $this->winner   = null;
        $this->loser    = null;
        $this->turns    = array();
    }
	    
    public function logAction( $sText ){
        $this->currentTurn->logAction( $sText );
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
