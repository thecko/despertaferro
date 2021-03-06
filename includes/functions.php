<?php
/**
 * @brief Creates an "unique" seed based on the current time
 * @returns and integer seed
 */
function make_seed()
{
  list($usec, $sec) = explode(' ', microtime());
  return (int)$sec;
}

/**
 * @brief Shuffle an assoc array "rows"
 * @param $array The array to be shuffled
 * @returns The shuffled array
 */
function rec_assoc_shuffle(&$array) {
	$keys = array_keys($array);

	shuffle($keys);

	foreach($keys as $key) {
		$new[$key] = $array[$key];
	}

	$array = $new;

	return true;
}
	
/**
 * @brief Returns the value of a $nombre var in the GET and POST
 * @param $nombre The variable name
 * @param $valorDefecto The value if not found
 * @returns The variable value or the $valorDefecto's value
 */
function getVar($nombre,$valorDefecto)
{
	// Sobresuponemos mal
	$res=$valorDefecto;
	
	// Miramos si no la han pasado por GET
	if(isset($_GET[$nombre]))
	{			
		if($_GET[$nombre]!="")
		{
			$res=$_GET[$nombre];
		}			
	}
	elseif(isset($_POST[$nombre])) // SIno, puede estar por post
	{			
		if($_POST[$nombre]!="")
		{
			$res=$_POST[$nombre];
		}
	}

	return $res;
}

/**
 * @brief Converts a data text file into an assoc array
 * @param $path The path to the file
 * @returns An numeric-assoc array with the pair key-values set
 * The data is stored in text files, First line as data headers and 
 * every line it's a row, and columns tab separated. 
 * This function will convert the file into a numeric array with every 
 * line (row in the dataset) of assoc arrays with the key-value
 * corresponding to the column header - column row value.
 */
function parseFile($path){
	$res = false;
	
	if ( file_exists($path) ){		
		$fil = fopen($path,"r");
		$content = fread($fil,filesize($path));
		
		if ( $content != "" ){
			$regs = explode("\n",$content);
			$i = 0;
			foreach ( $regs as $line ){
				$line = explode("\t", $line);
				// La primera línea contiene el nombre de los campos
				if ($i == 0) {
					$fields = $line;
				}
				else{
					$j = 0;
					foreach ( $line as $fieldValue){
						$newLine[trim($fields[$j])] = trim($fieldValue);
						$j++;
					}
					$res[$newLine["id"]] = $newLine;
				}
				$i++;
			}
		}
	}
	
	return $res;
}

/**
 * @brief Strips the last $str's char. If set, if it's the $thatChar char
 * @param $str The String to be stripped
 * @param $thatChar If set, will only strip the last char if its this char
 * @returns The stripped String
 */
function stripLastChar($str,$thatChar=""){
	if (strlen($str) > 0)
	{
		$lastChar = substr($str, -1);
		if ( ($lastChar == $thatChar) || ($thatChar=="") )
		{
			$str = substr($str, 0,strlen($str) - 1);
		}
	}
	
	return $str;
}

/*
Script Name: Full Operating system language detection
Author: Harald Hope, Website: http://techpatterns.com/
Script Source URI: http://techpatterns.com/downloads/php_language_detection.php
Version 0.3.6
Copyright (C) 8 December 2008

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

Get the full text of the GPL here: http://www.gnu.org/licenses/gpl.txt

Coding conventions:
http://cvs.sourceforge.net/viewcvs.py/phpbb/phpBB2/docs/codingstandards.htm?rev=1.3
*/

/*
Changes:
0.3.6 - added possible $feature values to comment header section
*/

/******************************************
Script is currently set to accept 2 parameters, triggered by $feature value.
for example, get_languages( 'data' ):
1. 'header' - sets header values, for redirects etc. No data is returned
2. 'data' - for language data handling, ie for stats, etc.
	Returns an array of the following 4 item array for each language the os supports:
	1. full language abbreviation, like en-ca
	2. primary language, like en
	3. full language string, like English (Canada)
	4. primary language string, like English
*******************************************/

// choice of redirection header or just getting language data
// to call this you only need to use the $feature parameter
function getLanguages($feature, $spare='' )
{
	// get the languages
	$a_languages = browserLanguages();
	$index = '';
	$complete = '';
	$found = false;// set to default value
	//prepare user language array
	$user_languages = array();

	//check to see if language is set
	if ( isset( $_SERVER["HTTP_ACCEPT_LANGUAGE"] ) )
	{
		$languages = strtolower( $_SERVER["HTTP_ACCEPT_LANGUAGE"] );
		// $languages = ' fr-ch;q=0.3, da, en-us;q=0.8, en;q=0.5, fr;q=0.3';
		// need to remove spaces from strings to avoid error
		$languages = str_replace( ' ', '', $languages );
		$languages = explode( ",", $languages );
		//$languages = explode( ",", $test);// this is for testing purposes only

		foreach ( $languages as $language_list )
		{
			// pull out the language, place languages into array of full and primary
			// string structure:
			$temp_array = array();
			// slice out the part before ; on first step, the part before - on second, place into array
			$temp_array[0] = substr( $language_list, 0, strcspn( $language_list, ';' ) );//full language
			$temp_array[1] = substr( $language_list, 0, 2 );// cut out primary language
			//place this array into main $user_languages language array
			$user_languages[] = $temp_array;
		}

		//start going through each one
		for ( $i = 0; $i < count( $user_languages ); $i++ )
		{
			foreach ( $a_languages as $index => $complete )
			{
				if ( $index == $user_languages[$i][0] )
				{
					// complete language, like english (canada)
					$user_languages[$i][2] = $complete;
					// extract working language, like english
					$user_languages[$i][3] = substr( $complete, 0, strcspn( $complete, ' (' ) );
					// extract ID LANGUAGE FROM DATABASE
					$user_languages[$i][3] = substr( $complete, 0, strcspn( $complete, ' (' ) );
				}
			}
		}
	}else// if no languages found
	{
		$user_languages[0] = array( '','','','' ); //return blank array.
	}
	
	// return parameters
	if ( $feature == 'data' )
	{
		return $user_languages;
	}elseif ( $feature == 'header' ) // this is just a sample, replace target language and file names with your own.
	{
		switch ( $user_languages[0][1] )// get default primary language, the first one in array that is
		{
			case 'en':
				$location = 'english.php';
				$found = true;
				break;
			case 'sp':
				$location = 'spanish.php';
				$found = true;
				break;
			default:
				break;
		}
		if ( $found )
		{
			header("Location: $location");
		}
		else// make sure you have a default page to send them to
		{
			header("Location: default.php");
		}
	}
}

function browserLanguages()
{
	// pack abbreviation/language array
	// important note: you must have the default language as the last item in each major language, after all the
	// en-ca type entries, so en would be last in that case
	$a_languages = array(
	'af' => 'Afrikaans',
	'sq' => 'Albanian',
	'ar-dz' => 'Arabic (Algeria)',
	'ar-bh' => 'Arabic (Bahrain)',
	'ar-eg' => 'Arabic (Egypt)',
	'ar-iq' => 'Arabic (Iraq)',
	'ar-jo' => 'Arabic (Jordan)',
	'ar-kw' => 'Arabic (Kuwait)',
	'ar-lb' => 'Arabic (Lebanon)',
	'ar-ly' => 'Arabic (libya)',
	'ar-ma' => 'Arabic (Morocco)',
	'ar-om' => 'Arabic (Oman)',
	'ar-qa' => 'Arabic (Qatar)',
	'ar-sa' => 'Arabic (Saudi Arabia)',
	'ar-sy' => 'Arabic (Syria)',
	'ar-tn' => 'Arabic (Tunisia)',
	'ar-ae' => 'Arabic (U.A.E.)',
	'ar-ye' => 'Arabic (Yemen)',
	'ar' => 'Arabic',
	'hy' => 'Armenian',
	'as' => 'Assamese',
	'az' => 'Azeri',
	'eu' => 'Basque',
	'be' => 'Belarusian',
	'bn' => 'Bengali',
	'bg' => 'Bulgarian',
	'ca' => 'Catalan',
	'zh-cn' => 'Chinese (China)',
	'zh-hk' => 'Chinese (Hong Kong SAR)',
	'zh-mo' => 'Chinese (Macau SAR)',
	'zh-sg' => 'Chinese (Singapore)',
	'zh-tw' => 'Chinese (Taiwan)',
	'zh' => 'Chinese',
	'hr' => 'Croatian',
	'cs' => 'Czech',
	'da' => 'Danish',
	'div' => 'Divehi',
	'nl-be' => 'Dutch (Belgium)',
	'nl' => 'Dutch (Netherlands)',
	'en-au' => 'English (Australia)',
	'en-bz' => 'English (Belize)',
	'en-ca' => 'English (Canada)',
	'en-ie' => 'English (Ireland)',
	'en-jm' => 'English (Jamaica)',
	'en-nz' => 'English (New Zealand)',
	'en-ph' => 'English (Philippines)',
	'en-za' => 'English (South Africa)',
	'en-tt' => 'English (Trinidad)',
	'en-gb' => 'English (United Kingdom)',
	'en-us' => 'English (United States)',
	'en-zw' => 'English (Zimbabwe)',
	'en' => 'English',
	'us' => 'English (United States)',
	'et' => 'Estonian',
	'fo' => 'Faeroese',
	'fa' => 'Farsi',
	'fi' => 'Finnish',
	'fr-be' => 'French (Belgium)',
	'fr-ca' => 'French (Canada)',
	'fr-lu' => 'French (Luxembourg)',
	'fr-mc' => 'French (Monaco)',
	'fr-ch' => 'French (Switzerland)',
	'fr' => 'French (France)',
	'mk' => 'FYRO Macedonian',
	'gd' => 'Gaelic',
	'ka' => 'Georgian',
	'de-at' => 'German (Austria)',
	'de-li' => 'German (Liechtenstein)',
	'de-lu' => 'German (Luxembourg)',
	'de-ch' => 'German (Switzerland)',
	'de' => 'German (Germany)',
	'el' => 'Greek',
	'gu' => 'Gujarati',
	'he' => 'Hebrew',
	'hi' => 'Hindi',
	'hu' => 'Hungarian',
	'is' => 'Icelandic',
	'id' => 'Indonesian',
	'it-ch' => 'Italian (Switzerland)',
	'it' => 'Italian (Italy)',
	'ja' => 'Japanese',
	'kn' => 'Kannada',
	'kk' => 'Kazakh',
	'kok' => 'Konkani',
	'ko' => 'Korean',
	'kz' => 'Kyrgyz',
	'lv' => 'Latvian',
	'lt' => 'Lithuanian',
	'ms' => 'Malay',
	'ml' => 'Malayalam',
	'mt' => 'Maltese',
	'mr' => 'Marathi',
	'mn' => 'Mongolian (Cyrillic)',
	'ne' => 'Nepali (India)',
	'nb-no' => 'Norwegian (Bokmal)',
	'nn-no' => 'Norwegian (Nynorsk)',
	'no' => 'Norwegian (Bokmal)',
	'or' => 'Oriya',
	'pl' => 'Polish',
	'pt-br' => 'Portuguese (Brazil)',
	'pt' => 'Portuguese (Portugal)',
	'pa' => 'Punjabi',
	'rm' => 'Rhaeto-Romanic',
	'ro-md' => 'Romanian (Moldova)',
	'ro' => 'Romanian',
	'ru-md' => 'Russian (Moldova)',
	'ru' => 'Russian',
	'sa' => 'Sanskrit',
	'sr' => 'Serbian',
	'sk' => 'Slovak',
	'ls' => 'Slovenian',
	'sb' => 'Sorbian',
	'es-ar' => 'Spanish (Argentina)',
	'es-bo' => 'Spanish (Bolivia)',
	'es-cl' => 'Spanish (Chile)',
	'es-co' => 'Spanish (Colombia)',
	'es-cr' => 'Spanish (Costa Rica)',
	'es-do' => 'Spanish (Dominican Republic)',
	'es-ec' => 'Spanish (Ecuador)',
	'es-sv' => 'Spanish (El Salvador)',
	'es-gt' => 'Spanish (Guatemala)',
	'es-hn' => 'Spanish (Honduras)',
	'es-mx' => 'Spanish (Mexico)',
	'es-ni' => 'Spanish (Nicaragua)',
	'es-pa' => 'Spanish (Panama)',
	'es-py' => 'Spanish (Paraguay)',
	'es-pe' => 'Spanish (Peru)',
	'es-pr' => 'Spanish (Puerto Rico)',
	'es-us' => 'Spanish (United States)',
	'es-uy' => 'Spanish (Uruguay)',
	'es-ve' => 'Spanish (Venezuela)',
	'es' => 'Spanish (Traditional Sort)',
	'sx' => 'Sutu',
	'sw' => 'Swahili',
	'sv-fi' => 'Swedish (Finland)',
	'sv' => 'Swedish',
	'syr' => 'Syriac',
	'ta' => 'Tamil',
	'tt' => 'Tatar',
	'te' => 'Telugu',
	'th' => 'Thai',
	'ts' => 'Tsonga',
	'tn' => 'Tswana',
	'tr' => 'Turkish',
	'uk' => 'Ukrainian',
	'ur' => 'Urdu',
	'uz' => 'Uzbek',
	'vi' => 'Vietnamese',
	'xh' => 'Xhosa',
	'yi' => 'Yiddish',
	'zu' => 'Zulu' );

	return $a_languages;
}

/**
 * @brief Ajax landing function for combat generation
 */
function doCombat(){
	$num = getVar("num",1);
	
	// PJ1
	$name1 = getVar("name1","");
	$fis1 = getVar("fis1","");
	$abs1 = getVar("abs1","");
	$att1 = getVar("att1","");
	$def1 = getVar("def1","");
	$wND1 = getVar("wND1","");
	$wBD1 = getVar("wBD1","");
	$wMD1 = getVar("wMD1","");
	$ini1 = getVar("ini1","");
	$tac1 = getVar("tac1","");
	$com1 = getVar("com1","");
	$wpn1 = getVar("wpn1","");
	
	// PJ2
	$name2 = getVar("name2","");
	$fis2 = getVar("fis2","");
	$abs2 = getVar("abs2","");
	$att2 = getVar("att2","");
	$def2 = getVar("def2","");
	$wND2 = getVar("wND2","");
	$wBD2 = getVar("wBD2","");
	$wMD2 = getVar("wMD2","");
	$ini2 = getVar("ini2","");
	$tac2 = getVar("tac2","");
	$com2 = getVar("com2","");
	$wpn2 = getVar("wpn2","");
	
	$cont1 = 0;
	$cont2 = 0;
	
	$log = array();
	
	for($i=0;$i<$num;$i++){
		$pj1 = new CombatPj($name1, $fis1, $abs1, $att1, $def1, $wND1, $wBD1,$wMD1,$ini1,$tac1, $com1, $wpn1);
		$pj2 = new CombatPj($name2, $fis2, $abs2, $att2, $def2, $wND2, $wBD2,$wMD2,$ini2,$tac2, $com2, $wpn2);
		$combate = new Combat($pj1,$pj2);
		$resultado = $combate->doCombat();
				
		if ($combate->pjVivo->name == $name1 ){
			$cont1++;
		}
		else{
			$cont2++;
		}
	}
	
        $log["log"] = combatLogger::instance();
        $log["victorias1"] = $cont1;
	$log["victorias2"] = $cont2;
		
	/*
	echo "<pre>";
	print_r( $log );
	echo "</pre>";
	*/	
	
	echo json_encode($log);
	
	//http://localhost:200/despertaferro/ajax.php?func=doCombat&name1=a&name2=b&fis1=9&fis2=10&abs1=2&abs2=0&att1=12&att2=13&def1=17&def2=16&ini1=8&ini2=9&tac1=8&tac2=9&wpn1=1&wpn2=0&wND1=2&wBD1=6&wMD1=0&wND2=2&wBD2=6&wMD2=0&num=1
}


function generateLangJSON($data){
	$res = "{";
	foreach ( $data as $id=>$value ){
		$res .= "\"" . $id . "\":\"" . $value["texto"] . "\",";	
	}
	$res = substr($res,0,strlen($res)-1);	// Última coma
	$res .= "}";
	
	return $res;
}

/**\deprecated use throw1o3dN instead
 * @brief 
 * @param $obj 
 * @param $base 
 * @param $iBonus 
 * @returns 
 * 
 * 
 */
function throw1o3d10($obj, $base=10 , $iBonus=0){	
    return throw1o3dN( $obj , $base , $iBonus );
}

/**
 * @brief Simulates a 3 $base dice throw returning the $obj dice.
 * @param $obj It can be the Low, mid or high dice. Use DICE_LOW, DICE_MED, DICE_HIG constants
 * @param $base The base dice, 10 by default as RyF's standart
 * @param $iBonus A bonus to the Throw, only applied if it's not a blunt.
 * @returns The total throw result or false if blunt
 * It'll get the objective dice and check if there's a blunt or critic 
 * (with the base number in the objective dice result, 
 * it'll throw and sum again).
 */
 // TODO Add the blunt system. Returning false if 1 in the objetive dice and <= 5 in the next dice 
function throw1o3dN($obj, $base=10 , $iBonus=0 ){
    $bFirstThrow = true; // Blunts apply only in the first Throw
    $logText = "Tirada 1o3d".$base . ":";
    $total = 0;
    do { 
        $dado[0] = rand(1,$base);
        $dado[1] = rand(1,$base);
        $dado[2] = rand(1,$base);
        sort($dado);

        $logText .= " (" . 
            ($obj==DICE_LOW?"[":"") . $dado[DICE_LOW] . ($obj==DICE_LOW?"]":"") . 
            "-" . 
            ($obj==DICE_MED?"[":"") . $dado[DICE_MED] . ($obj==DICE_MED?"]":"")  . 
            "-" . 
            ($obj==DICE_HIG?"[":"") . $dado[DICE_HIG] . ($obj==DICE_HIG?"]":"")  . 
            ") > ";
        
        // 1 in the obj. dice it's a direct fail, and maybe, a blunt
        if( $bFirstThrow && $dado[$obj]==1 ){
            $total = THROW_FAIL;
            // With High objective dice cannot be blunt
            if( $obj != DICE_HIG ){
                $iBluntDice = $obj+1;
                // With 1-5 result in the next dice, it's a blunt
                if( $dado[$iBluntDice] <= ($base/2) ){
                    $total = THROW_BLUNT;
                }
            }
            break; // Exit while, no more throws
        }

        $total = $total + $dado[$obj];
        $bFirstThrow = false;
    } while ($dado[$obj] == $base);

    if( $total > 0 ){
        $logText .= $total;
        
        // If bonus, apply it
        $total += $iBonus;
        $logText .= ($iBonus!=0 ? " + " . $iBonus : '' );
    }
    else{
        // Log the corresponding failiure
        if( $total == THROW_FAIL ){
            $logText .= " Errada ";
        }
        else{
            $logText .= " Pifia ";
        }
    }

    combatLogger::instance()->logAction( $logText );

    return $total;
}

/**
 * @brief Simulates a throw with explosion of NdD
 * @param $num The number of dices to throw
 * @param $base The base dicem d6, d10, etc.
 * @param $iBonus A bonus to the final result
 * @returns The total amount of the dice throw
 * If any dice result it's the base number, it'll throw it again and sum
 * to the final result.
 */
function throwNdD($num, $base , $iBonus=0){
	$logText = "Tirada " . $num . "d".$base . ":";
	$total = 0;
	
	for ($dice=0;$dice<$num;$dice++){
		$logText .= " [ ";
		do { 
			$dado = rand(1,$base);
			$logText .= $dado;
			
			if( $dado == $base ){
				$logText .= "+" ;
			}
			
			
			$total = $total + $dado;
		} while ($dado == $base);
		$logText .= " ]";
	}
	
	// If bonus, apply it	
	$total += $iBonus;
	$logText .= ($iBonus!=0 ? " + " . $iBonus : '' );
	
	combatLogger::instance()->logAction( $logText );
	
	return $total;
}

/**
 * @brief Converts a wound Id to his localized name
 * @param $idx The Wound Id
 * @returns The Wound name
 * This function will check the lang array to extract the "herida_<ID>"
 * text.
 */
function woundsName( $idx ){
	global $langCom;
	return $langCom["herida_".$idx]["texto"];
}
?>
