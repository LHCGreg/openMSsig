<?php

/* This file should be kept out of your document root or kept from being web-accessible some other way like .htaccess */

$conf_fontDir = './fonts'; // Directory where the fonts are stored; should not be web-accessible unless you have a reason
$conf_imageDir = './images'; // Directory where the world and job images are stored; should not be web-accessible unless you have a reason
$conf_BGInfoDir = './BGInfo'; // Directory where the background information files are stored; should not be web-accessible
$conf_BGDir = './backgrounds'; // Directory where the background image files are stored; can be web-accessible

define('SECOND', 1);
define('MINUTE', 60 * SECOND);
define('HOUR', 60 * MINUTE);
define('DAY', 24 * HOUR);

$conf_expires = 3 * HOUR;
/* Amount of time viewer's browser is allowed to cache the result image. Set to 0 if you want viewers to always get 
 * a fresh image; however it is recommended that you set this to at least 1 minute. Browsers are not obligated to
 * use the cached image; they can force a refresh. However, this will probably require action on their part, so
 * setting a reasonable value for this can reduce server load */
 
//$conf_forceOutFormat = 'jpg';
/* Set $conf_forceOutFormat to 'jpg', 'jpeg', 'gif' or 'png' if you want ALL images sent to have that format */

$conf_defaultOutFormat = 'png';
/* Output format to use if the background configuration does not specify a format and if the user did not specify one with
 * the 'out' GET parameter (applicable only if $conf_allowVariableOutFormat is true) */
 
$conf_allowVariableOutFormat = false; // Allow user to specify output image format with $_GET['out']? If so, the user's preference takes priority over the format specified by the background info.
$conf_allowedOutFormats['jpg'] = true; // If user is allowed to specify output format, allow jpg?
$conf_allowedOutFormats['jpeg'] = true; // If user is allowed to specify output format, allow jpeg?
$conf_allowedOutFormats['png'] = true; // If user is allowed to specify output format, allow png?
$conf_allowedOutFormats['gif'] = true; // If user is allowed to specify output format, allow gif?

$conf_getFreshViewstate = false;
/* I'm not sure if viewstates can expire. I haven't had any trouble so far, but turn this on if it suddenly stops working.
 * The cost of turning this on is an extra trip to the rankings site; and those are the bottleneck in speed. */

$conf_defaultBG = 'boat'; // name of the default background to use

$conf_useWhitelist = false; // If true, if a character is not on the whitelist, access will be denied
$conf_useBlacklist = false; // if true, if a character is on the blacklist, access will be denied

//$allow['gms']['chartoallow'] = true; // You MUST put character names here in all lower-case
//$allow['msea']['anotherchartoallow'] = true; // The first bracketed string is the character's version; see below for version abbreviations

//$deny['gms']['charnottoallow'] = true;
//$deny['gms']['nosigforyou'] = true;


$rankings_url['gms'] = 'http://maplestory.nexon.net/Modules/Rank/Ranking.aspx?PART=/Controls/Rank/TotRank&ranktype=TotRank';
$rankings_url['msea'] = 'http://www.maplesea.com/ranking/ranking_overall.aspx';
$rankings_url['ems'] = 'http://en.maplestory.nexoneu.com/modules/Rank.aspx?bGr=TotRank&mode=list';


// To disallow a version from using the sig, set its value to false. Do not set a version to true unless MSsig.php supports it
$versionAbbreviations = array('gms' => true, 'msea' => true, 'jms' => false, 'kms' => false, 'cms' => false, 'ems' => true,
                              'hkms' => false, 'thms' => false, 'twms' => false);
                              
$conf_defaultVersion = 'gms'; // If no version is specified, use this MS version

$imageBaseKeys = array('pet', 'char', 'jobImage', 'worldImage', 'immigrantImage'); // don't touch this
$imageProperties = array('X', 'Y', 'Pct'); // $drawInfo v1 properties only; don't touch this

$stringBaseKeys = array('name', 'level', 'exp', 'expPer', 'jobString', 'rank', 'worldString', 'immigrantString', 'versionString'); // don't touch this
$stringProperties = array('Func', 'X', 'Y', 'Font', 'Size', 'Angle', 'ColorRed', 'ColorGreen', 'ColorBlue', 'ColorAlpha'); // $drawInfo v1 properties only; don't touch this

/* I strongly recommend that you do not change the defaultGetImg functions */

$conf_defaultGetImg['char'] = 'getCharImg';
$conf_defaultGetImg['pet'] = 'getPetImg';
$conf_defaultGetImg['jobImage'] = 'getJobImg';
$conf_defaultGetImg['worldImage'] = 'getWorldImg';
$conf_defaultGetImg['immigrantImage'] = 'getImmigrantImg';

// define the types of $drawInfo2 objects
define('D2_FUNCTION', 0);
define('D2_STRING', 1);
define('D2_IMAGE', 2);



/* I strongly recommend that you do not change the the default drawInfo. Some bg configurations may depend on them */

$conf_defaultDrawInfo['charX'] = 0;
$conf_defaultDrawInfo['charY'] = 0;
$conf_defaultDrawInfo['charPct'] = 100;

$conf_defaultDrawInfo['petX'] = 83;
$conf_defaultDrawInfo['petY'] = 20;
$conf_defaultDrawInfo['petPct'] = 100;

$conf_defaultDrawInfo['jobImageX'] = 320;
$conf_defaultDrawInfo['jobImageY'] = 40;
$conf_defaultDrawInfo['jobImagePct'] = 100;

$conf_defaultDrawInfo['worldImageX'] = 287;
$conf_defaultDrawInfo['worldImageY'] = 40;
$conf_defaultDrawInfo['worldImagePct'] = 100;

$conf_defaultDrawInfo['immigrantImageX'] = 300;
$conf_defaultDrawInfo['immigrantImageY'] = 90;
$conf_defaultDrawInfo['immigrantImagePct'] = 100;

$conf_defaultDrawInfo['nameFunc'] = 'getNameString';
$conf_defaultDrawInfo['levelFunc'] = 'getLevelString';
$conf_defaultDrawInfo['expFunc'] = 'getExpString';
$conf_defaultDrawInfo['expPerFunc'] = 'getExpPerString';
$conf_defaultDrawInfo['rankFunc'] = 'getRankString';
$conf_defaultDrawInfo['jobStringFunc'] = 'getJobString';
$conf_defaultDrawInfo['worldStringFunc'] = 'getWorldString';
$conf_defaultDrawInfo['immigrantStringFunc'] = 'getImmigrantString';
$conf_defaultDrawInfo['versionStringFunc'] = 'getVersionString';

$conf_defaultDrawInfo['nameX'] = 130;
$conf_defaultDrawInfo['nameY'] = 32;
$conf_defaultDrawInfo['nameFont'] = 'pala.ttf';
$conf_defaultDrawInfo['nameColorRed'] = 28;
$conf_defaultDrawInfo['nameColorGreen'] = 60;
$conf_defaultDrawInfo['nameColorBlue'] = 81;
$conf_defaultDrawInfo['nameColorAlpha'] = 0;
$conf_defaultDrawInfo['nameSize'] = 22;
$conf_defaultDrawInfo['nameAngle'] = 0;

$conf_defaultDrawInfo['levelX'] = 135;
$conf_defaultDrawInfo['levelY'] = 54;
$conf_defaultDrawInfo['levelFont'] = 'pala.ttf';
$conf_defaultDrawInfo['levelColorRed'] = 28;
$conf_defaultDrawInfo['levelColorGreen'] = 60;
$conf_defaultDrawInfo['levelColorBlue'] = 81;
$conf_defaultDrawInfo['levelColorAlpha'] = 0;
$conf_defaultDrawInfo['levelSize'] = 13;
$conf_defaultDrawInfo['levelAngle'] = 0;

$conf_defaultDrawInfo['expX'] = 220;
$conf_defaultDrawInfo['expY'] = 54;
$conf_defaultDrawInfo['expFont'] = 'pala.ttf';
$conf_defaultDrawInfo['expColorRed'] = 28;
$conf_defaultDrawInfo['expColorGreen'] = 60;
$conf_defaultDrawInfo['expColorBlue'] = 81;
$conf_defaultDrawInfo['expColorAlpha'] = 0;
$conf_defaultDrawInfo['expSize'] = 13;
$conf_defaultDrawInfo['expAngle'] = 0;

$conf_defaultDrawInfo['expPerX'] = 220;
$conf_defaultDrawInfo['expPerY'] = 54;
$conf_defaultDrawInfo['expPerFont'] = 'pala.ttf';
$conf_defaultDrawInfo['expPerColorRed'] = 28;
$conf_defaultDrawInfo['expPerColorGreen'] = 60;
$conf_defaultDrawInfo['expPerColorBlue'] = 81;
$conf_defaultDrawInfo['expPerColorAlpha'] = 0;
$conf_defaultDrawInfo['expPerSize'] = 13;
$conf_defaultDrawInfo['expPerAngle'] = 0;

$conf_defaultDrawInfo['rankX'] = 260;
$conf_defaultDrawInfo['rankY'] = 32;
$conf_defaultDrawInfo['rankFont'] = 'pala.ttf';
$conf_defaultDrawInfo['rankColorRed'] = 28;
$conf_defaultDrawInfo['rankColorGreen'] = 60;
$conf_defaultDrawInfo['rankColorBlue'] = 81;
$conf_defaultDrawInfo['rankColorAlpha'] = 0;
$conf_defaultDrawInfo['rankSize'] = 13;
$conf_defaultDrawInfo['rankAngle'] = 0;

$conf_defaultDrawInfo['jobStringX'] = 320;
$conf_defaultDrawInfo['jobStringY'] = 40;
$conf_defaultDrawInfo['jobStringFont'] = 'pala.ttf';
$conf_defaultDrawInfo['jobStringColorRed'] = 28;
$conf_defaultDrawInfo['jobStringColorGreen'] = 60;
$conf_defaultDrawInfo['jobStringColorBlue'] = 81;
$conf_defaultDrawInfo['jobStringColorAlpha'] = 0;
$conf_defaultDrawInfo['jobStringSize'] = 13;
$conf_defaultDrawInfo['jobStringAngle'] = 0;

$conf_defaultDrawInfo['worldStringX'] = 287;
$conf_defaultDrawInfo['worldStringY'] = 40;
$conf_defaultDrawInfo['worldStringFont'] = 'pala.ttf';
$conf_defaultDrawInfo['worldStringColorRed'] = 28;
$conf_defaultDrawInfo['worldStringColorGreen'] = 60;
$conf_defaultDrawInfo['worldStringColorBlue'] = 81;
$conf_defaultDrawInfo['worldStringColorAlpha'] = 0;
$conf_defaultDrawInfo['worldStringSize'] = 13;
$conf_defaultDrawInfo['worldStringAngle'] = 0;

$conf_defaultDrawInfo['immigrantStringX'] = 300;
$conf_defaultDrawInfo['immigrantStringY'] = 90;
$conf_defaultDrawInfo['immigrantStringFont'] = 'pala.ttf';
$conf_defaultDrawInfo['immigrantStringColorRed'] = 28;
$conf_defaultDrawInfo['immigrantStringColorGreen'] = 60;
$conf_defaultDrawInfo['immigrantStringColorBlue'] = 81;
$conf_defaultDrawInfo['immigrantStringColorAlpha'] = 0;
$conf_defaultDrawInfo['immigrantStringSize'] = 13;
$conf_defaultDrawInfo['immigrantStringAngle'] = 0;

$conf_defaultDrawInfo['versionStringX'] = 400;
$conf_defaultDrawInfo['versionStringY'] = 90;
$conf_defaultDrawInfo['versionStringFont'] = 'pala.ttf';
$conf_defaultDrawInfo['versionStringColorRed'] = 28;
$conf_defaultDrawInfo['versionStringColorGreen'] = 60;
$conf_defaultDrawInfo['versionStringColorBlue'] = 81;
$conf_defaultDrawInfo['versionStringColorAlpha'] = 0;
$conf_defaultDrawInfo['versionStringSize'] = 13;
$conf_defaultDrawInfo['versionStringAngle'] = 0;

?>