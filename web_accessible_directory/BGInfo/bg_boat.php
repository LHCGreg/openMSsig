<?php

/* You *MUST* set the 'object' property of images and strings if you want default values to be used!!!!!!!!
 * If you DON'T set the 'object' property, you *MUST* specify all required properties.
 *
 * Image objects:
 * 'char'
 * 'pet'
 * 'jobImage'
 * 'worldImage'
 * 'immigrantImage'
 * 'rankDeltaImage'
 *
 * Image properties:
 * 'type' - REQUIRED. Must be set to D2_IMAGE
 * 'object' - Set to 'char', or 'pet', etc. If you do not set this, no defaults will be used, so you must specify
 *            *ALL* other required properties. Do not set this if you are drawing a non-standard image. If you are
 *            drawing a non-standard image, you must specify all required properties because there will not be defaults for it
 * 'img' - For advanced users only - if you set this to an image resource, the drawing procedure will use that
 *                                   image resource instead of getting a new one with getImg. The img resource will not
 *                                   be destroyed after drawing it
 * 'getImg' - Required if not using 'img' and 'object' is not set. Function taking one argument, the
 *            $charData associative array, and returing an image resource or false on failure. The resource returned will be
 *            destroyed after it is used
 * 'X' - Required if 'object' is not set. X coordinate of the location to draw the left of the image
 * 'Y' - Required if 'object' is not set. Y coordinate of the location to draw the top of the image
 * 'Pct' - Required if 'object' is not set. Merging %. 0 means nothing is drawn, 100 means the image is completly copied.
 *         100 is the normal value. Values > 0 and < 100 make the image have a faded, ghostly appearance. Could be useful
 *         for Halloween-themed backgrounds...
 *
 * String objects:
 * 'name'
 * 'level'
 * 'exp'
 * 'expPer'
 * 'jobString'
 * 'Rank'
 * 'worldString'
 * 'immigrantString'
 * 'rankDeltaString'
 *
 * String properties:
 * 'type' - REQUIRED. Must be set to D2_STRING
 * 'object' - Set to 'name' or 'rank', etc. If you do not set this, no defaults will be used, so you must specify
 *            *ALL* other required properties. Do not set this if you are drawing a non-standard string. If you are
 *            drawing a non-standard string, you must specify all required properties because there will not be defaults for it
 * 'str' - For advanced users only - if you set this to a string, the drawing procedure will use that string instead
 *                                   of getting a new one getStr
 * 'getStr' - Required if not using 'str' and 'object' is not set. Function taking one argument, the
              $charData associative array, and returning a string or false on failure
 * 'X' - Required if 'object' is not set. X coordinate of the location to start drawing the string
 * 'Y' - Required if 'object' is not set. Y coordinate of the location to start drawing the string
 * 'Font' - Required if 'object' is not set. File name of the font stored in the font directory to use, eg pala.ttf.
 *          Must be a truetype or truetype compatible (such as opentype) font, generally ending in .ttf or .TTF
 * 'Size' - Required if 'object' is not set. Font size
 * 'Angle' - Required if 'object' is not set. Angle to draw the string at. 0 is normal, 180 is upside-down and backwards,
 *           -90 is vertical, etc
 * 'ColorRed' - Required if 'object' is not set. Red portion of the color to draw the string as, ranging from 0-255. If you
 *              use hex, prefix the 2 hex digits with 0x, eg 0xFF
 * 'ColorGreen' - Required if 'object' is not set. Green portion of the color to draw the string as.
 * 'ColorBlue' - Required if 'object' is not set. Blue portion of the color to draw the string as
 * 'ColorAlpha' - Required if 'object' is not set. Alpha of the color to draw the string as. 0 is completely opaque, 127 is
 *                completely transparent.
 *
 * Everything here is case-sensitive!!!
 *
 *
 * Really advanced users can set a $drawInfo2 element to a function by setting 'type' to D2_FUNCTION and 'function' to a
 * function name. The function used must take 4 parameters: &$img, &$charData, &$drawInfo2, and $currentIndex.
 * Such functions can call functions defined in MSsig.php such as drawImage and getCharData.
 * This greatly increases the power and flexibility of bg configs. You can do things like display more than 1
 * character on one sig or create a shadow effect function by drawing the same thing multiple times, fading it more each time.
 * As far as background layouts go, the sky's the limit if you know PHP!
 *
 *
 * After drawing a string, the script will set the 'boundbox' property of it to an array of 4 points that are the text's
 * bounding box; see the documentation for imagettftext() on php.net
 *
 *
 * This is also a good time to mention that since bg configs are php, sig hosters should inspect a bg config before using it
 * to make sure it doesn't do anything nasty like deleting your files.
 *
 *
 * $drawInfo2 items are drawn in order, starting from 0, not from 1!
 *
 *
 *
 * To use a background-specific whitelist or blacklist, set $bg_auth = 'auth_bgbwlist', $bg_allow[VERSION][CHARACTER] = true; 
 * where VERSION is the character's version abbreviation in all lowercase: gms, msea, jms, kms, cms, ems, hkms, thms, or twms.
 * CHARACTER is the character name in all lowercase. Example: $bg_allow['gms']['spaz'] = true;
 * Any characters that are not specified will not be allowed to use the background.
 * This could be used to provide a special background only for members of a certain guild.
 *
 * Blacklist is similar, but uses $bg_deny[VERSION][CHARACTER]. Any character specified on the blacklist will not be allowed to use
 * the background.
 *
 *
 *
 * The old $drawInfo config format is NO LONGER SUPPORTED AND HAS BEEN REMOVED
 *
 *
 *
 * Look for more backgrounds at http://www.cs.stevens.edu/~gnajda/openMSsig/backgrounds and layouts to go with those backgrounds at
 * http://www.cs.stevens.edu/~gnajda/openMSsig/layouts
 *
 * (those sites may change, https://sourceforge.net/projects/openmssig  is a more permanent site
 *
 */

/* BG config files should specify the background image file name and the output format as below */

/* This layout made by Spaz */

/* openMSsig 2.3.0 or higher required */

$bgimage = 'boat.jpg';
$outFormat = 'png'; // Can be jpg, jpeg, png, or gif

//$bg_auth = 'auth_bgbwlist';
//$bg_allow['gms']['spaz'] = true;
//$bg_deny['gms']['spaz'] = true;

$drawInfo2[0]['type'] = D2_IMAGE;
$drawInfo2[0]['object'] = 'char';
$drawInfo2[0]['X'] = 0;
$drawInfo2[0]['Y'] = 0;
$drawInfo2[0]['Pct'] = 100;

/*$drawInfo2[1]['type'] = D2_STRING;
$drawInfo2[1]['object'] = 'name';
$drawInfo2[1]['X'] = 133;
$drawInfo2[1]['Y'] = 32;
$drawInfo2[1]['Font'] = 'pala.ttf';
$drawInfo2[1]['ColorRed'] = 28;
$drawInfo2[1]['ColorGreen'] = 60;
$drawInfo2[1]['ColorBlue'] = 81;
$drawInfo2[1]['ColorAlpha'] = 0;
$drawInfo2[1]['Size'] = 22;
$drawInfo2[1]['Angle'] = 0;*/

$drawInfo2[1]['type'] = D2_FUNCTION;
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
//$drawInfo2[1]['stringInfo']['Angle'] = 0;

$drawInfo2[1]['horizontalCenter'] = 207;


$drawInfo2[2]['type'] = D2_STRING;
$drawInfo2[2]['object'] = 'level';
$drawInfo2[2]['X'] = 135;
$drawInfo2[2]['Y'] = 54;
$drawInfo2[2]['Font'] = 'pala.ttf';
$drawInfo2[2]['ColorRed'] = 28;
$drawInfo2[2]['ColorGreen'] = 60;
$drawInfo2[2]['ColorBlue'] = 81;
$drawInfo2[2]['ColorAlpha'] = 0;
$drawInfo2[2]['Size'] = 13;
$drawInfo2[2]['Angle'] = 0;

$drawInfo2[3]['type'] = D2_STRING;
$drawInfo2[3]['object'] = 'expPer';
$drawInfo2[3]['X'] = 220;
$drawInfo2[3]['Y'] = 54;
$drawInfo2[3]['Font'] = 'pala.ttf';
$drawInfo2[3]['ColorRed'] = 28;
$drawInfo2[3]['ColorGreen'] = 60;
$drawInfo2[3]['ColorBlue'] = 81;
$drawInfo2[3]['ColorAlpha'] = 0;
$drawInfo2[3]['Size'] = 13;
$drawInfo2[3]['Angle'] = 0;

$drawInfo2[4]['type'] = D2_IMAGE;
$drawInfo2[4]['object'] = 'worldImage';
$drawInfo2[4]['X'] = 346;
$drawInfo2[4]['Y'] = 45;
$drawInfo2[4]['Pct'] = 100;

$drawInfo2[5]['type'] = D2_IMAGE;
$drawInfo2[5]['object'] = 'jobImage';
$drawInfo2[5]['X'] = 340;
$drawInfo2[5]['Y'] = 10;
$drawInfo2[5]['Pct'] = 100;


?>