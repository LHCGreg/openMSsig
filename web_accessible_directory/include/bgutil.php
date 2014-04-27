<?php

/* This code is under a creative commons attribution license.
 * You can do whatever you want with it as long as the "Originally written by..." line remains intact
 *
 *
 * Originally written by Sp4z of sleepywood.net forums
 *
 *
 * There is no warranty on this code, yada yada yada
 *
 *
 * Although not required, if you use this code it would make me happy and motivate me to continue working on this if
 * you publicly credit me and send me a message letting me know you use it. :) I seem to only hear from people when
 * things go wrong...
 */

function drawCenteredString($img, $charData, &$drawInfo2, $i)
{
	/* drawInfo2 function hook
	 *
	 * Draws a string centered in a given area either horizontally, vertically, or neither (not centered)
	 *
	 * The string info is given in $drawInfo2[$i]['stringInfo']. 
	 * 
	 * Example: 
	 
	   $drawInfo2[1]['type'] = D2_FUNC;
	   $drawInfo2[1]['function'] = 'drawCenteredString';

	   $drawInfo2[1]['stringInfo']['type'] = D2_STRING;
	   $drawInfo2[1]['stringInfo']['object'] = 'name';
	   $drawInfo2[1]['stringInfo']['X'] = 133;
	   $drawInfo2[1]['stringInfo']['Y'] = 32;
	   $drawInfo2[1]['stringInfo']['Font'] = 'pala.ttf';
	   $drawInfo2[1]['stringInfo']['ColorRed'] = 28;
	   $drawInfo2[1]['stringInfo']['ColorGreen'] = 60;
	   $drawInfo2[1]['stringInfo']['ColorBlue'] = 81;
	   $drawInfo2[1]['stringInfo']['ColorAlpha'] = 0;
	   $drawInfo2[1]['stringInfo']['Size'] = 22;
	   $drawInfo2[1]['stringInfo']['Angle'] = 0;

	   $drawInfo2[1]['horizontalCenter'] = 200;
	   
	 *
	 * The horizontalCenter and verticalCenter keys are the points on which to center the string.
	 * If not specified, no centering is done for that alignment (horizontal or vertical).
	 */
	
	
	$horizontalCenter = array_key_exists('horizontalCenter', $drawInfo2[$i]) ? $drawInfo2[$i]['horizontalCenter'] : null;
	$verticalCenter = array_key_exists('verticalCenter', $drawInfo2[$i]) ? $drawInfo2[$i]['verticalCenter'] : null;
	
	/* Set string defaults */
	
	global $stringProperties, $conf_defaultDrawInfo;
	
	foreach($stringProperties as $stringProperty)
	{
		if(!isset($drawInfo2[$i]['stringInfo'][$stringProperty]))
		{
			$drawInfo2[$i]['stringInfo'][$stringProperty] = $conf_defaultDrawInfo[$drawInfo2[$i]['stringInfo']['object'].$stringProperty];
		}
	}
	if(!isset($drawInfo2[$i]['stringInfo']['str']) && !isset($drawInfo2[$i]['stringInfo']['getStr']))
	{
		$drawInfo2[$i]['stringInfo']['getStr'] = $conf_defaultDrawInfo[$drawInfo2[$i]['stringInfo']['object'].'Func'];
	}
	
	
	if(isset($drawInfo2[$i]['stringInfo']['str']))
	{
		$str = $drawInfo2[$i]['stringInfo']['str'];
	}
	else if(isset($drawInfo2[$i]['stringInfo']['getStr']))
	{
		$str = $drawInfo2[$i]['stringInfo']['getStr']($charData);
	}
	else
	{
		return false;
	}
	
	return do_DrawCenteredString($img, $str, $drawInfo2[$i]['stringInfo'], $horizontalCenter, $verticalCenter);
}

function do_DrawCenteredString($img, $str, &$stringInfo, $horizontalCenter, $verticalCenter)
{
	global $conf_fontDir;
	
	if($horizontalCenter === null && $verticalCenter === null)
	{
		$success = imagettftext($img, $stringInfo['Size'], $stringInfo['Angle'], $stringInfo['X'], $stringInfo['Y'], imagecolorallocatealpha($img, $stringInfo['ColorRed'], $stringInfo['ColorGreen'], $stringInfo['ColorBlue'], $stringInfo['ColorAlpha']), $conf_fontDir.'/'.$stringInfo['Font'], $str);
		$stringInfo['boundbox'] = $success;
		return $success;
	}
		
	$boundingBox = imagettfbbox($stringInfo['Size'], $stringInfo['Angle'], $conf_fontDir.'/'.$stringInfo['Font'], $str);
	
	/* Need to do this to find the leftmost, rightmost, topmost, bottommost because drawing text at an angle makes
	 * the "top left" relative to the text and not the background
	 */
	 
	$farLeft = $boundingBox[0];
	$farRight = $boundingBox[0];
	
	for($i = 2; $i < 8; $i += 2)
	{
		if($boundingBox[$i] < $farLeft)
		{
			$farLeft = $boundingBox[$i];
		}
		if($boundingBox[$i] > $farRight)
		{
			$farRight = $boundingBox[$i];
		}
	}
	
	$farTop = $boundingBox[1];
	$farBottom = $boundingBox[1];
	
	for($i = 3; $i < 8; $i += 2)
	{
		if($boundingBox[$i] < $farTop)
		{
			$farTop = $boundingBox[$i];
		}
		if($boundingBox[$i] > $farBottom)
		{
			$farBottom = $boundingBox[$i];
		}
	}
	
	$width = $farRight - $farLeft;
	$height = $farBottom - $farTop;
	
	$X = $stringInfo['X'];
	$Y = $stringInfo['Y'];
	if($horizontalCenter !== null)
	{
		$X = $horizontalCenter - floor($width / 2);
		// the text does not generally start with left and top at 0, depending on font and size it is
		// usually some negative number, so compensate for that
		$X -= $farLeft;
		
	}
	
	if($verticalCenter !== null)
	{
		$Y = $verticalCenter - floor($height / 2);
		$Y -= $farTop;
	}
	
	$success = imagettftext($img, $stringInfo['Size'], $stringInfo['Angle'], $X, $Y, imagecolorallocatealpha($img, $stringInfo['ColorRed'], $stringInfo['ColorGreen'], $stringInfo['ColorBlue'], $stringInfo['ColorAlpha']), $conf_fontDir.'/'.$stringInfo['Font'], $str);

	$stringInfo['boundbox'] = $success;
		
	return $success;
}


?>