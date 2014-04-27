<?php

/* This layout made by regelus, centering added by Spaz */

/* openMSsig 2.3.0 or higher required */

$bgimage = 'boat.jpg';
$outFormat = 'png';

$drawInfo['outFormat'] = 'png';

$drawInfo2[0]['type'] = D2_IMAGE;
$drawInfo2[0]['object'] = 'char';
$drawInfo2[0]['X'] = 0;
$drawInfo2[0]['Y'] = 0;

$drawInfo2[1]['type'] = D2_IMAGE;
$drawInfo2[1]['object'] = 'pet';
$drawInfo2[1]['X'] = 72;
$drawInfo2[1]['Y'] = 12;

$drawInfo2[2]['type'] = D2_FUNCTION;
$drawInfo2[2]['function'] = 'drawCenteredString';

$drawInfo2[2]['stringInfo']['type'] = D2_STRING;
$drawInfo2[2]['stringInfo']['object'] = 'name';
$drawInfo2[2]['stringInfo']['X'] = 138;
$drawInfo2[2]['stringInfo']['Y'] = 53;
$drawInfo2[2]['stringInfo']['Font'] = 'fajardo.ttf';
$drawInfo2[2]['stringInfo']['Size'] = 42;
$drawInfo2[2]['stringInfo']['ColorRed'] = 28;
$drawInfo2[2]['stringInfo']['ColorGreen'] = 60;
$drawInfo2[2]['stringInfo']['ColorBlue'] = 81;

$drawInfo2[2]['horizontalCenter'] = 210;

function boat2_getLevel($charData)
{
	return 'lvl. ' . $charData['level'];
}

$drawInfo2[3]['type'] = D2_STRING;
$drawInfo2[3]['object'] = 'level';
$drawInfo2[3]['X'] = 182;
$drawInfo2[3]['Y'] = 70;
$drawInfo2[3]['Font'] = 'verdana.ttf';
$drawInfo2[3]['Size'] = 15;
$drawInfo2[3]['ColorRed'] = 28;
$drawInfo2[3]['ColorGreen'] = 60;
$drawInfo2[3]['ColorBlue'] = 81;
$drawInfo2[3]['getStr'] = 'boat2_getLevel';

$drawInfo2[4]['type'] = D2_STRING;
$drawInfo2[4]['object'] = 'expPer';
$drawInfo2[4]['X'] = 277;
$drawInfo2[4]['Y'] = 70;
$drawInfo2[4]['Font'] = 'verdana.ttf';
$drawInfo2[4]['Size'] = 15;
$drawInfo2[4]['ColorRed'] = 28;
$drawInfo2[4]['ColorGreen'] = 60;
$drawInfo2[4]['ColorBlue'] = 81;

$drawInfo2[5]['type'] = D2_IMAGE;
$drawInfo2[5]['object'] = 'jobImage';
$drawInfo2[5]['X'] = 140;
$drawInfo2[5]['Y'] = 49;

$drawInfo2[6]['type'] = D2_IMAGE;
$drawInfo2[6]['object'] = 'worldImage';
$drawInfo2[6]['X'] = 162;
$drawInfo2[6]['Y'] = 70;

$drawInfo2[7]['type'] = D2_IMAGE;
$drawInfo2[7]['object'] = 'immigrantImage';
$drawInfo2[7]['X'] = 186;
$drawInfo2[7]['Y'] = 74;

?>