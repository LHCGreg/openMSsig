<?php

/* openMSsig 2.3.0 */

/* 2.2.1sp2 --> 2.3.0: Added bgutil.php, added text-centering function, changed sample background to center the character name
   2.3.0sp1 --> 2.3.0sp2: Using function to get exp required, as it can vary between versions, using "exp curve" for GMS
   2.3.0sp2 --> 2.3.0sp3: Updated EMS to deal with EMS site changes. Updated GMS exp required function to return exp required as an integer, not a float
*/

/* This code is under a creative commons attribution license.
   You can do whatever you want with it as long as the "Originally written by..." line remains intact
 *
 *
 * Originally written by Sp4z of sleepywood.net forums
 *
 *
 * There is no warranty on this code, yada yada yada */
 
/* PHP 5.0 or higher is REQUIRED */

/* The PHP config option allow_url_fopen MUST be set */

/* PHP extensions needed:
 * GD Image extension that supports GIF, whatever format(s) your backgrounds are in, and whatever format(s) you output as, with
 *    the FreeType library for drawing strings
 * PCRE (perl-style regular expressions) [enabled by default, you probably don't have to worry about this] */

/* Parameters:
 * $_GET['char']	REQUIRED. The character name to do the sig for. Case-insensitive; the name displayed on the sig
 *                      will use the case used in-game. 
 * $_GET['bg']		OPTIONAL. The name given to a background defined in MSsigConfig.php. Case-insenstive. If not given,
                        $conf_defaultBG is used.
 * $_GET['out']         OPTIONAL. Image format to output as. Permitted values are jpg, jpeg, gif, and png
                        if not specified or an invalid value is given, the background's default output format is used.
                        If the background does not specify a default output format, $conf_defaultOutFormat is used.
                        This parameter only has any effect if $conf_allowVariableOutFormat is set to true.
   $_GET['ms']          OPTIONAl. Version of maplestory to get the character for. case-insensitive. Defaults to GMS.
                        Permitted values are GMS, MSEA, JMS, KMS, CMS, EMS, HKMS, ThMS, and TwMS. Currently supported
                        versions are GMS, MSEA, and EMS
 */

/* Later versions?:	Server caching option, for both images and character data
 *                      Adding character attributes from all the different kinds of character search (eg fame search).
 *                         - openMSsig will figure out how to get all attributes a layout needs in the least amount of searches
 *                      Removing backwards compatibility for the original drawInfo format
 *                      Using exceptions instead of return codes to signal errors
 *                      rankDelta key for charInfo
 *                      Factoring out things that depend on the rankings site into their own file, making for easier updating
 *                      More power for layout authors to skip certain steps or replace with their own function
 *                         - for example, custom authentication methods
 *                      Custom error functions, allowing for example a "sorry, there was an error" image to be displayed
 *                      GUI app, allowing people to design layouts without needing PHP and a web server to test and without having to manually write the layout file
 *			Support for other versions of MS?
 */
 
/* Note to possible future maintainers:
 * Here is a checklist of things you must do to add to support for a version of MS:
 *
 * 1. Get the world images, save them with the proper name ([version]_[world].gif), and make their backgrounds transparent if they are not already
 * 2. Set $rankings_url[newversion] in MSsigConfig.php
 * 3. Add a hardcoded viewstate in getViewstate()
 * 4. Add code in createPostContext()
 * 5. Add a regex in parseCharSearch()
 * 6. Add a regex handler in parseCharSearch()
 * 7. Set the appropriate abbreviation to true in MSsigConfig.php
 * 8. Test
 */
error_reporting(E_ALL | E_STRICT);
$conf_includeDir = './include'; // change this to the directory where expTable.php and MSsigConfig.php are

require($conf_includeDir . '/expTable.php');
require($conf_includeDir . '/MSsigConfig.php');
@include($conf_includeDir . '/blackwhitelist.php');
@include($conf_includeDir . '/bgutil.php');

if(!isset($_GET['char']) || $_GET['char'] > 20)
{
	die('char not set or too long');
}

if(!isset($_GET['ms']))
{
	$version = $conf_defaultVersion;
}
else
{
	$version = strtolower($_GET['ms']);
}
if(!isset($versionAbbreviations[$version]))
{
	die('unrecognized MS version');
}
else if(!$versionAbbreviations[$version])
{
	die('unsupported MS version');
}

if(isset($_GET['bg']))
{
	require($conf_BGInfoDir.'/bg_'.strtolower($_GET['bg']).'.php');
}
else
{
	require($conf_BGInfoDir.'/bg_'.strtolower($conf_defaultBG).'.php');
}

$charname = $_GET['char'];

$searchStatus = getCharData($charname, $version, $charData); // Stores the character data in the $charData associative array (see getCharData documentation)

if(!$searchStatus)
{
	if($searchStatus === false) // internal error
	{
		die('error in getting character data');
	}
	else if($searchStatus === 0) // character not found
	{
		die('character not found');
	}
}
else
{
	$sendSuccess = makeAndSendImage($charData);
	if(!$sendSuccess)
	{
		die('error in making and sending image');
	}
}


function getCharData($char, $version, &$charData)
{
	/* Gets character information for character $char and puts it in $charData, an associative array.
	   No validating of $char is done in this function.
	   $charData will have the following keys:
	   
	   string charname              The name of the character with correct capitalization
	   int level                    The level of the character
	   int rank                     The character's overall ranking
	   int experience               The number of experience points towards the next level the character has
	   float expPercent             The % towards the next level
	   string job                   The character's job (class)
	   string world                 The server the character is in
	   string characterImageURL     The URL for the character's image
	   string petImageURL           The URL for the character's pet's image
	   string jobImageURL           The URL of the character's job icon
	   string worldImageURL         The URL of the character's server icon
	   bool immigrant               True if the character is an immigrant
	   string version               The version of the character in lowercase. See top of this script for possible values.

	   
	   Returns TRUE on success, 0 if the character was not found or access denied, and FALSE on an internal error
	   
	   $version is assumed to be a supported version */
	
	/* First check the character against whitelists and blacklists if necessary */
	
	global $conf_useWhitelist, $conf_useBlacklist, $allow, $deny;
	
	if($conf_useWhitelist && (!isset($allow[$version][strtolower($char)]) || !$allow[$version][strtolower($char)]))
	{
		echo "Access denied; not on whitelist<br>\n";
		return 0;
	}
	if($conf_useBlacklist && (isset($deny[$version][strtolower($char)]) && $deny[$version][strtolower($char)]))
	{
		echo "Access denied; on blacklist<br>\n";
		return 0;
	}
	if(isset($GLOBALS['bg_allow']) && (!isset($GLOBALS['bg_allow'][$version][strtolower($char)]) || !$GLOBALS['bg_allow'][$version][strtolower($char)]))
	{
		echo "Access denied; not on background whitelist<br>\n";
		return 0;
	}
	if(isset($GLOBALS['bg_deny']) && (isset($GLOBALS['bg_deny'][$version][strtolower($char)]) && $GLOBALS['bg_deny'][$version][strtolower($char)]))
	{
		echo "Access denied; on background blacklist<br>\n";
		return 0;
	}
	
	$html = charSearch($char, $version);
	if($html === false) // character search failed
	{
		echo "Character search failed<br>\n";
		return false;
	}
	
	$parseSuccess = parseCharSearch($html, $char, $version, $charData);
	if(!$parseSuccess)
	{
		if($parseSuccess === false)
		{
			echo "error while parsing html<br>\n";
		}
		else if($parseSuccess === 0)
		{
			echo "character not found<br>\n";
		}
	}
	return $parseSuccess;
}


function charSearch($char, $version)
{
	/* Returns the html of doing a character search on $char, or FALSE on failure 
	 * No validating of $char is done in this function */
	
	global $rankings_url;
	$context = createPostContext($char, $version);
	
	$html = file_get_contents($rankings_url[$version], false, $context);
	if(!$html)
	{
		echo "error while getting rankings html<br>\n";
	}
	return $html;
}

function createPostContext($char, $version)
{
	if($version == 'gms')
	{
		$charfield = '/Controls/Rank/TotRank$RANK$tbCharacterName'; // Name of the character form field on Nexon's site
		$searchButtonName = '/Controls/Rank/TotRank$RANK$imgbtnSearch'; // Will not work without giving some coordinates of where the search button was pressed
	}
	else if($version == 'msea')
	{
		$charfield = 'CharacterSearch';
		$searchButtonName = 'CharacterSearchAction';
	}
	else if($version == 'ems')
	{
		$charfield = '_uc_GameRank$tbTopCharacterName';
		$searchButtonName = '_uc_GameRank$imgTopBtnSearch';
	}
	
	$viewstate;
	$eventValidation;
	$ok = getViewStateAndEventValidation($version, $viewstate, $eventValidation);
	//if(!$viewstate)
	if(!$ok)
	{
		echo "error while getting viewstate<br>\n";
		return false;
	}
	
	$postdata[$charfield] = $char;
	$postdata['__VIEWSTATE'] = $viewstate;
	$postdata['__EVENTTARGET'] = '';
	$postdata['__EVENTARGUMENT'] = '';
	if($version == 'ems')
	{
		$postdata['__EVENTVALIDATION'] = $eventValidation;
	}
	$postdata[$searchButtonName.'.x'] = '17';
	$postdata[$searchButtonName.'.y'] = '13';
	
	$encodedData = http_build_query($postdata);
	
	$opts['http']['method'] = 'POST';
	$opts['http']['header'] = 'Content-type: application/x-www-form-urlencoded';
	$opts['http']['content'] = $encodedData;
	
	/*if($version == 'ems')
	{
		$sessionID = getEMSsessionID();
		
		// ems requires a valid sessionID cookie plus another cookie to tell it not to display the stupid map page instead of what we really want
		$opts['http']['header'] .= "\r\nCookie: EMSMapGuide=MapGuidePageSkip=TRUE";
		$opts['http']['header'] .= "; ASP.NET_SessionId=".$sessionID;
	}*/
	
	return stream_context_create($opts);
}

function parseCharSearch($html, $char, $version, &$charData)
{
	/* Parses $html, which is assumed to be the html of a character search, for the data relating to character $char
	   and stores it in $charData, an associative array. $charData will have the following keys:
	   
	   string charname              The name of the character with correct capitalization
	   int level                    The level of the character
	   int rank                     The character's overall ranking
	   int experience               The number of experience points towards the next level the character has
	   float expPercent             The % towards the next level
	   string job                   The character's job (class)
	   string world                 The server the character is in
	   string characterImageURL     The URL for the character's image
	   string petImageURL           The URL for the character's pet's image (Might not be set if the version's rankings does not give it)
	   string jobImageURL           The URL of the character's job icon (Might not be set if the version's rankings does not use it)
	   string worldImageURL         The URL of the character's server icon
	   bool immigrant               True if the character is an immigrant
	   string version               The version of the character in lowercase. See top of this script for possible values.
	   
	   Returns TRUE on success, 0 if the character was not found, and FALSE on an internal error
	   
	   Note that the parsing of the html is very sensitive to changes. If Nexon changes anything about the rankings page, there
	   is a good chance that it will break the regular expression. */
	
	// Everybody stand back! I know regular expressions!

	if($version == 'gms')
	{
		$pattern = '@<TD WIDTH=62 ALIGN=CENTER CLASS="menu"><B>([^<]*)</B></TD>\s*<TD WIDTH=9></TD>\s*<TD WIDTH=102 ALIGN=CENTER BACKGROUND="([^"]*)" STYLE="background-repeat:no-repeat;"><IMG SRC="([^"]*)" BORDER="0"></TD>\s*<TD WIDTH=9></TD>\s*<TD ALIGN=CENTER><SPAN CLASS="CTcharacter_name"><B><script>IsImmiGrant\(\'(.)\'\);</script><br></br>(' . preg_quote($char, '@') . ')</B>.*<IMG SRC=(.*) .*<IMG\s*SRC=(.*) .*<B>(.*)</B>.*\((.*)\)@Usi';
		
		/*
		Hokay, so here's the explanation of the regular expression.
		The U option is used to make the *'s ungreedy so they match as little as possible.
		Combined with the ".*"s, it will keep going until it reaches the character after the .*, and try to match
		the pattern after that point. For the fields before the character name, it is necessary to specify the ending character
		with a negative character class so that it does not match the first few fields of the first character on the page
		and then match the rest of the fields for the actual character. For the same reason, it's necessary to give the html
		for the fields before the character name; it can't just be a .* because then it would incorrectly match those fields
		of the first character.

		The s option is used so that newlines are matched with the .*'s
		The i option is used for case-insensitivity, so the user of this script does not have to enter the correct case

		*/
	}
	else if($version == 'msea')
	{
		$pattern = '@<font color="#333333">([^<]*)</font></strong></div></td>\s*<td width="26%" bgcolor="#FFFFFF"><div align="center"><img src="([^"]*)" /></div></td>\s*<td width="23%" bgcolor="#FFFFFF"><div align="center" id="(' . preg_quote($char, '@') . ')".*<img src="(.*)".*alt="(.*)".*<img src="(.*)".*alt="(.*)".*<font color="#000000">(.*)</font>.*<font color="#999999">\((.*)\)@Usi';
	}
	else if($version == 'ems')
	{
		$pattern = '@<td class="ranking_list_bg02 cen" width="81">([^<]*)</td>\s*<td class="ranking_list_bg02 cen">&nbsp;</td>\s*<td  width="110" class="ranking_list_bg02 cen" ALIGN=CENTER  BACKGROUND="([^"]*)" STYLE="background-repeat:no-repeat;"><IMG SRC="([^"]*)" BORDER="0"></td>\s*<td class="ranking_list_bg02 cen" width="2">&nbsp;</td>\s*<td class="ranking_list_bg02 cen" width="128">(' . preg_quote($char, '@'). ')</td>.*width="99">(.*)</td>.*<img src="(.*)".*alt="(.*)".*<B>(.*)</B><br><SPAN CLASS=rank_info01>\((.*)\)@Usi';
	}

	$matches = array();

	$regexSuccess = preg_match($pattern, $html, $matches);
	
	if(!$regexSuccess)
	{
		if($regexSuccess === false)
		{
			echo "error in character regex<br>\n";
		}
		else if($regexSuccess === 0)
		{
			echo "No matches with character regex<br>\n";
		}
		return $regexSuccess;
	}
	
	if($version == 'gms')
	{
		$charData['rank'] = $matches[1];
		$charData['petImageURL'] = $matches[2];
		$charData['characterImageURL'] = $matches[3];
		if($matches[4] == '1')
		{
			$charData['immigrant'] = true;
		}
		else if($matches[4] == '0')
		{
			$charData['immigrant'] = false;
		}
		else
		{
			echo "Unknown match for immigrant<br>\n";
			return false; // Something went wrong
		}
		$charData['charname'] = $matches[5];
		$charData['worldImageURL'] = $matches[6];

		if(preg_match('#world_(.*?).gif#', $charData['worldImageURL'], $worldMatches) != 1)
		{
			echo "error matching world<br>\n";
			return false;
		}
		$charData['world'] = ucfirst($worldMatches[1]);

		$charData['jobImageURL'] = $matches[7];

		if(preg_match('#job_(.*?).gif#', $charData['jobImageURL'], $jobMatches) != 1)
		{
			echo "error matching job<br>\n";
			return false;
		}
		$charData['job'] = ucfirst($jobMatches[1]);

		$charData['level'] = $matches[8];
		$charData['experience'] = str_replace(',', '', $matches[9]); // get rid of the commas (43,575,183 --> 43575183)
	}
	else if($version == 'msea')
	{
		global $rankings_url;
		
		$charData['rank'] = $matches[1];
		$charData['characterImageURL'] = $matches[2];
		$charData['charname'] = $matches[3];
		$charData['worldImageURL'] = dirname($rankings_url[$version]).'/'.$matches[4];
		$charData['world'] = $matches[5];
		$charData['jobImageURL'] = dirname($rankings_url[$version]).'/'.$matches[6];
		$charData['job'] = $matches[7];
		$charData['level'] = $matches[8];
		$charData['experience'] = str_replace(',', '', $matches[9]); // get rid of the commas (43,575,183 --> 43575183)
		$charData['immigrant'] = false;
	}
	else if($version == 'ems')
	{
		$charData['rank'] = $matches[1];
		$charData['petImageURL'] = $matches[2];
		$charData['characterImageURL'] = $matches[3];
		$charData['charname'] = $matches[4];
		$charData['job'] = $matches[5];
		$charData['worldImageURL'] = $matches[6];
		$charData['world'] = $matches[7];
		$charData['level'] = $matches[8];
		$charData['experience'] = str_replace(',', '', $matches[9]);
		$charData['immigrant'] = false;
	}
	
	if($charData['level'] < 200)
	{
		$charData['expPercent'] = ($charData['experience'] / getExpRequired($charData['level'], $version)) * 100;
	}
	else
	{
		$charData['expPercent'] = 0.00;
	}
	$charData['version'] = $version;
	
	return true;
}

function makeAndSendImage($charData)
{
	/* $charData is an associative array with keys as set by getCharData */
	
	global $conf_defaultBG, $conf_BGInfoDir;
	
	$img = getBGImg();
	if(!$img)
	{
		echo "error in getting BG image<br>\n";
		return false;
	}

	$success = imagealphablending($img, true);
	if(!$success)
	{
		echo "error setting alpha blending<br>\n";
		return $success;
	}
	$drawInfo2 = getDrawInfo2();
	$drawSuccess = draw($img, $charData, $drawInfo2);
	if(!$drawSuccess)
	{
		echo "error in drawing<br>\n";
		return false;
	}
	
	$sendSuccess = sendImage($img);
	if(!$sendSuccess)
	{
		echo "error sending image<br>\n";
		return false;
	}
	return true;
}

function getBGImg()
{
	/* Returns a background image according to the background configuration file or FALSE on failure.
	 * If not specified there, use the old method of using the background name to look for an image */
	global $conf_BGDir, $conf_defaultBG;
	if(!isset($GLOBALS['bgimage'])) // from require()'ing the background config earlier
	{
		// backwards compatibility
		if(isset($_GET['bg']))
		{
			$glob = glob($conf_BGDir.'/'.preg_quote(strtolower($_GET['bg'])).'.*'); // preg_quote to escape certain characters
		}
		else
		{
			$glob = glob($conf_BGDir.'/'.preg_quote(strtolower($conf_defaultBG)).'.*'); // preg_quote to escape certain characters
		}
		if(!$glob)
		{
			return false;
		}
		$bgfile = $glob[0];
	}
	else
	{
		$bgfile = $conf_BGDir.'/'.$GLOBALS['bgimage']; // background image specified by background config
	}
	
	$bgImg = imageCreateFromAny($bgfile);
	if(!$bgImg)
	{
		return $bgImg;
	}
	$success = imagesavealpha($bgImg, true);
	if(!$success)
	{
		return $success;
	}
	return $bgImg;
}

function getDrawInfo2()
{
	/* Returns the $drawInfo associative array given in the configuration file for the background selected.
	 * The file must be in $conf_BGInfoDir as set in MSsigConfig.php and named bg_backgroundname.php where
	 * backgroundname is the name of the background */
	 
	/* Returns a $drawInfo2 numeric array given in the configuration file. For backwards compatibility, if it does
	 * not exist, convert the $drawInfo associative array given to $drawInfo2 format */
	
	if(!isset($GLOBALS['drawInfo2']))
	{
		convertD1ToD2();
	}
	else
	{
		setD2DefaultsAsNeeded();
	}
	
	return $GLOBALS['drawInfo2'];
}

function convertD1ToD2()
{
	/* Converts the global $drawInfo to a global $drawInfo2 */
	global $drawInfo;
	global $drawInfo2;
	
	global $imageBaseKeys, $imageProperties, $conf_defaultGetImg;
	foreach($imageBaseKeys as $imageBaseKey)
	{
		setImageDefaultsIfNeeded($drawInfo, $imageBaseKey);
		if(isset($drawInfo[$imageBaseKey]) && $drawInfo[$imageBaseKey])
		{
			$index = count($drawInfo2); // index to put this element in
			$drawInfo2[$index]['object'] = $imageBaseKey;
			$drawInfo2[$index]['type'] = D2_IMAGE;
			foreach($imageProperties as $imageProperty)
			{
				$drawInfo2[$index][$imageProperty] = $drawInfo[$imageBaseKey.$imageProperty];
			}
			$drawInfo2[$index]['getImg'] = $conf_defaultGetImg[$imageBaseKey];
		}
	}
	
	setFunctionDefaultsIfNeeded($drawInfo);
	
	global $stringBaseKeys, $stringProperties;
	foreach($stringBaseKeys as $stringBaseKey)
	{
		setStringDefaultsIfNeeded($drawInfo, $stringBaseKey);
		if(isset($drawInfo[$stringBaseKey]) && $drawInfo[$stringBaseKey])
		{
			$index = count($drawInfo2); // index to put this element in
			$drawInfo2[$index]['object'] = $stringBaseKey;
			$drawInfo2[$index]['type'] = D2_STRING;
			foreach($stringProperties as $stringProperty)
			{
				$drawInfo2[$index][$stringProperty] = $drawInfo[$stringBaseKey.$stringProperty];
			}
			$drawInfo2[$index]['getStr'] = $drawInfo2[$index]['Func'];
		}
	}
	
	if(isset($drawInfo['outFormat']))
	{
		$GLOBALS['outFormat'] = $drawInfo['outFormat'];
	}
}

function setD2DefaultsAsNeeded()
{
	global $drawInfo2;
	global $conf_defaultDrawInfo, $conf_defaultGetImg;
	global $imageBaseKeys, $imageProperties, $stringBaseKeys, $stringProperties;
	foreach($drawInfo2 as &$object)
	{
		if(isset($object['object']) && $object['type'] == D2_IMAGE && isset($conf_defaultDrawInfo[$object['object'].'X']))
		{
			foreach($imageProperties as $imageProperty)
			{
				if(!isset($object[$imageProperty]))
				{
					$object[$imageProperty] = $conf_defaultDrawInfo[$object['object'].$imageProperty];
				}
			}
			if(!isset($object['img']) && !isset($object['getImg']))
			{
				$object['getImg'] = $conf_defaultGetImg[$object['object']];
			}
		}
		else if(isset($object['object']) && $object['type'] == D2_STRING && isset($conf_defaultDrawInfo[$object['object'].'X']))
		{
			foreach($stringProperties as $stringProperty)
			{
				if(!isset($object[$stringProperty]))
				{
					$object[$stringProperty] = $conf_defaultDrawInfo[$object['object'].$stringProperty];
				}
			}
			if(!isset($object['str']) && !isset($object['getStr']))
			{
				$object['getStr'] = $conf_defaultDrawInfo[$object['object'].'Func'];
			}
		}
	}
}

function draw($img, &$charData, &$drawInfo2)
{
	/* Does the drawing of images and strings onto the image resource given by $img, using the character data and drawing info
	 * returns TRUE on success or FALSE on failure */
	
	for($i = 0; $i < count($drawInfo2); $i++)
	{
		if($drawInfo2[$i]['type'] == D2_IMAGE)
		{
			if(!drawImage($img, $charData, $drawInfo2[$i]))
			{
				echo 'error drawing image; index ' . $i . "<br>\n";
				return false;
			}
		}
		else if($drawInfo2[$i]['type'] == D2_STRING)
		{
			if(!drawString($img, $charData, $drawInfo2[$i]))
			{
				echo 'error drawing string; index ' . $i . "<br>\n";
				return false;
			}
		}
		else if($drawInfo2[$i]['type'] == D2_FUNCTION)
		{
			$drawInfo2[$i]['function']($img, $charData, $drawInfo2, $i);
		}
	}
	return true;
}

function getCharImg($charData)
{
	/* Using character data $charData, return an image resource to the character image or a 1x1 transparent image on failure */
	$charImg = @imageCreateFromAny($charData['characterImageURL']); // sometimes glitchy sites mess up the avatar; display the rest of the sig if that happens
	if(!$charImg)
	{
		return getNullImage();
	}
	else
	{
		return $charImg;
	}
}

function getPetImg($charData)
{
	/* Using character data $charData, return an image resource to the pet image or a 1x1 transparent image on failure */
	if(isset($charData['petImageURL']))
	{
		$petImg = @imageCreateFromAny($charData['petImageURL']);
		if(!$petImg)
		{
			return getNullImage();
		}
		else
		{
			return $petImg;
		}
	}
	else // Not all versions' ranking sites have pet images
	{
		return getNullImage();
	}
}

function getJobImg($charData)
{
	/* Using character data $charData, return an image resource to the character's job image or FALSE on failure */
	global $conf_imageDir;
	//$imagePath = $conf_imageDir.'/'.basename($charData['jobImageURL']);
	$imagePath = $conf_imageDir.'/job_'.strtolower($charData['job']).'.gif';
	if(file_exists($imagePath))
	{
		return imageCreateFromAny($imagePath);
		/* Use a stored image instead of getting it from Nexon's site for speed */
	}
	else
	{
		return getNullImage();
	}
}

function getWorldImg($charData)
{
	/* Using character data $charData, return an image resource to the character's world image or FALSE on failure */
	global $conf_imageDir;
	$imagePath = $conf_imageDir.'/'.$charData['version'].'_'.strtolower($charData['world']).'.gif';
	if(file_exists($imagePath))
	{
		return imageCreateFromAny($imagePath);
		/* Use a stored image instead of getting it from Nexon's site for speed */
	}
	else
	{
		return getNullImage(); // Don't stop sig generation if the world images are not up-to-date
	}
}

function getImmigrantImg($charData)
{
	/* Using character data $charData, return an image resource to the immigrant image if the character is an immigrant,
	 * a 1x1 transparent image if the character is not, or FALSE on failure */
	
	global $conf_imageDir;
	if($charData['immigrant'])
	{
		return imageCreateFromAny($conf_imageDir.'/ranking_immigrant02.gif');
	}
	else
	{
		return getNullImage();
	}
}

function getNullImage()
{
	/* Return a 1x1 transparent image */
	$nullImg = imagecreatetruecolor(1, 1); // Create a 1z1 image
	imagecolortransparent($nullImg, imagecolorat($nullImg, 0, 0)); // Make the pixel transparent
	return $nullImg;
}

function getNameString($charData)
{
	/* A premade function for returning a string representation of the character's name */
	return $charData['charname'];
}

function getLevelString($charData)
{
	/* A premade function for returning a string representation of the character's level */
	return 'Level '.$charData['level'];
}

function getExpString($charData)
{
	/* A premade function for returning a string representation of the character's experience */
	return 'Experience: '.$charData['experience'];
}

function getExpPerString($charData)
{
	/* A premade function for returning a string representation of the character's expereince % */
	return sprintf('%.2F', $charData['expPercent']).'%'; // returns xx.xx%
}

function getRankString($charData)
{
	/* A premade function for returning a string representation of the character's rank */
	return 'Rank: '.$charData['rank'];
}

function getJobString($charData)
{
	/* A premade function for returning a string representation of the character's job */
	return $charData['job'];
}

function getWorldString($charData)
{
	/* A premade function for returning a string representation of the character's world */
	return $charData['world'];
}

function getVersionString($charData)
{
	/* A premade function for returning a string representation of the character's version */
	return strtoupper($charData['version']);
}

function getImmigrantString($charData)
{
	/* A premade function for returning a string representation of whether or not the character is an immigrant */
	if($charData['immigrant'])
	{
		return 'immigrant';
	}
	else
	{
		return '';
	}
}

function setImageDefaultsIfNeeded(&$drawInfo, $baseKey)
{
	/* Give the drawing info for a certain image object defaults if needed */
	global $conf_defaultDrawInfo;
	if(!isset($drawInfo[$baseKey.'X']) || !isset($drawInfo[$baseKey.'Y']))
	{
		$drawInfo[$baseKey.'X'] = $conf_defaultDrawInfo[$baseKey.'X'];
		$drawInfo[$baseKey.'Y'] = $conf_defaultDrawInfo[$baseKey.'Y'];
	}
	if(!isset($drawInfo[$baseKey.'Pct']))
	{
		$drawInfo[$baseKey.'Pct'] = $conf_defaultDrawInfo[$baseKey.'Pct'];
	}
}

function drawImage($img, $charData, $imageInfo)
{
	/* Draw the image using the resource specified by $imageInfo['img'] onto $img; if that does not exist, call 
	 * $imageInfo['getImg']($charData) to get an image resource. Use $imageInfo for drawing parameters */
	
	if(isset($imageInfo['img']))
	{
		$newImg = $imageInfo['img'];
	}
	else if(isset($imageInfo['getImg']))
	{
		$newImg = $imageInfo['getImg']($charData);
	}
	else
	{
		echo "no image or way of getting image specified<br>\n";
		return false;
	}
	
	if(!$newImg)
	{
		echo "error getting non-background image<br>\n";
		return false;
	}
	
	$success = imagecopymerge($img, $newImg, $imageInfo['X'], $imageInfo['Y'], 0, 0, imagesx($newImg), imagesy($newImg), $imageInfo['Pct']);
	if(!isset($imageInfo['img'])) // We can only destroy the image if we got it from a function. If the layout set an image, it might still want it
	{
		imagedestroy($newImg); // Save some memory during script execution since we don't need that image anymore
	}
	return $success;
}

function setFunctionDefaultsIfNeeded(&$drawInfo)
{
	/* If any of the string creation functions are not set, set them to the default */
	
	global $conf_defaultDrawInfo;
	if(!isset($drawInfo['nameFunc']))
	{
		$drawInfo['nameFunc'] = $conf_defaultDrawInfo['nameFunc'];
	}
	if(!isset($drawInfo['levelFunc']))
	{
		$drawInfo['levelFunc'] = $conf_defaultDrawInfo['levelFunc'];
	}
	if(!isset($drawInfo['expFunc']))
	{
		$drawInfo['expFunc'] = $conf_defaultDrawInfo['expFunc'];
	}
	if(!isset($drawInfo['expPerFunc']))
	{
		$drawInfo['expPerFunc'] = $conf_defaultDrawInfo['expPerFunc'];
	}
	if(!isset($drawInfo['rankFunc']))
	{
		$drawInfo['rankFunc'] = $conf_defaultDrawInfo['rankFunc'];
	}
	if(!isset($drawInfo['jobStringFunc']))
	{
		$drawInfo['jobStringFunc'] = $conf_defaultDrawInfo['jobStringFunc'];
	}
	if(!isset($drawInfo['worldStringFunc']))
	{
		$drawInfo['worldStringFunc'] = $conf_defaultDrawInfo['worldStringFunc'];
	}
	if(!isset($drawInfo['immigrantStringFunc']))
	{
		$drawInfo['immigrantStringFunc'] = $conf_defaultDrawInfo['immigrantStringFunc'];
	}
}

function setStringDefaultsIfNeeded(&$drawInfo, $baseKey)
{
	/* Set default attributes for the given string basekey if needed. basekey would be something like 'name' or 'rank' */
	
	global $conf_defaultDrawInfo;
	if(!isset($drawInfo[$baseKey.'X']) || !isset($drawInfo[$baseKey.'Y']))
	{
		$drawInfo[$baseKey.'X'] = $conf_defaultDrawInfo[$baseKey.'X'];
		$drawInfo[$baseKey.'Y'] = $conf_defaultDrawInfo[$baseKey.'Y'];
	}
	if(!isset($drawInfo[$baseKey.'Font']))
	{
		$drawInfo[$baseKey.'Font'] = $conf_defaultDrawInfo[$baseKey.'Font'];
	}
	if(!isset($drawInfo[$baseKey.'ColorRed']) || !isset($drawInfo[$baseKey.'ColorGreen']) || !isset($drawInfo[$baseKey.'ColorBlue']))
	{
		$drawInfo[$baseKey.'ColorRed'] = $conf_defaultDrawInfo[$baseKey.'ColorRed'];
		$drawInfo[$baseKey.'ColorGreen'] = $conf_defaultDrawInfo[$baseKey.'ColorGreen'];
		$drawInfo[$baseKey.'ColorBlue'] = $conf_defaultDrawInfo[$baseKey.'ColorBlue'];
	}
	if(!isset($drawInfo[$baseKey.'ColorAlpha']))
	{
		$drawInfo[$baseKey.'ColorAlpha'] = $conf_defaultDrawInfo[$baseKey.'ColorAlpha'];
	}
	if(!isset($drawInfo[$baseKey.'Size']))
	{
		$drawInfo[$baseKey.'Size'] = $conf_defaultDrawInfo[$baseKey.'Size'];
	}
	if(!isset($drawInfo[$baseKey.'Angle']))
	{
		$drawInfo[$baseKey.'Angle'] = $conf_defaultDrawInfo[$baseKey.'Angle'];
	}
}

function drawString($img, $charData, &$stringInfo)
{
	/* Draw the string using the string specified by $stringInfo['str'] onto $img; if that does not exist, call 
	 * $stringInfo['getStr']($charData) to get a string. Use $stringInfo for drawing parameters */
	
	if(isset($stringInfo['str']))
	{
		$str = $stringInfo['str'];
	}
	else if(isset($stringInfo['getStr']))
	{
		$str = $stringInfo['getStr']($charData);
	}
	else
	{
		echo "no string or way of getting string specified<br>\n";
		return false;
	}
	
	global $conf_fontDir;
	$success = imagettftext($img, $stringInfo['Size'], $stringInfo['Angle'], $stringInfo['X'], $stringInfo['Y'], imagecolorallocatealpha($img, $stringInfo['ColorRed'], $stringInfo['ColorGreen'], $stringInfo['ColorBlue'], $stringInfo['ColorAlpha']), $conf_fontDir.'/'.$stringInfo['Font'], $str);
	
	$stringInfo['boundbox'] = $success;
	
	return $success;
}

function imageCreateFromAny($location)
{
	/* Create an image from any filename, guessing which image function to use based on the extension.
	 * Returns an image resource of the image file or FALSE on failure
	 * WARNING: I may have left out some image types or valid extensions */
	$extension = strtolower(pathinfo($location, PATHINFO_EXTENSION));
	if($extension == "jpg" || $extension == "jpeg")
	{
		return imagecreatefromjpeg($location);
	}
	else if($extension == "gif")
	{
		return imagecreatefromgif($location);
	}
	else if($extension == "png")
	{
		return imagecreatefrompng($location);
	}
	else
	{
		return false;
	}
}

function sendImage($img)
{
	/* Send the image identified by the image resource $img
	 * return TRUE on success, FALSE on failure */

	/* Send an Expires header to tell the browser how long it's allowed to cache the image */
	global $conf_expires; 
	header('Expires: ' . gmstrftime('%a, %d %b %Y %H:%M:%S GMT', time() + $conf_expires), true); // time must be given in RFC 1123 date format (Thu, 01 Dec 1994 16:00:00 GMT)

	 
	global $conf_allowVariableOutFormat;
	global $conf_defaultOutFormat;
	
	$outFormat;
	
	global $conf_forceOutFormat;
	
	/* Output format priority goes: Forced format if set in config, user preference (if allowed), background preference, default in configuration, hardcoded default */
	if(isset($conf_forceOutFormat))
	{
		$outFormat = $conf_forceOutFormat;
	}
	else if($conf_allowVariableOutFormat && isset($_GET['out']) && isset($conf_allowedOutFormats[strtolower($_GET['out'])]) && $conf_allowedOutFormats[strtolower($_GET['out'])])
	{
		$outFormat = strtolower($_GET['out']);
	}
	else if(isset($GLOBALS['outFormat']))
	{
		$outFormat = $GLOBALS['outFormat'];
	}
	else if(isset($conf_defaultOutFormat))
	{
		$outFormat = $conf_defaultOutFormat;
	}
	else
	{
		$outFormat = 'png';
	}
	
	if($outFormat == 'jpg')
	{
		$outFormat = 'jpeg'; // Content-type is image/jpeg, image function is imagejpeg, so convert jpg to jpeg
	}
	$contentType = 'image/'.$outFormat;
	$sendFunction = 'image'.$outFormat;
	header('Content-type: '.$contentType, true); // Tell the browser what type to expect
	return $sendFunction($img);
}

function getViewStateAndEventValidation($version, &$viewState, &$eventValidation)
{
	/* Returns a valid viewstate (and for EMS, an event validation) or FALSE on failure. I *think* that the hardcoded viewstate will always be good, but if not,
	 * $conf_getFreshViewstate can be set to true and it can get a guaranteed valid viewstate, at a significant time cost */
	 
	global $conf_getFreshViewstate;
	if($conf_getFreshViewstate)
	{
		global $rankings_url;
		//if($version == 'ems')
		//{
			//$opts['http']['method'] = 'GET';
			//$sessionID = getEMSsessionID();

			// ems requires a valid sessionID cookie plus another cookie to tell it not to display the stupid map page instead of what we really want
			//$opts['http']['header'] = "Cookie: EMSMapGuide=MapGuidePageSkip=TRUE";
			//$opts['http']['header'] .= "; ASP.NET_SessionId=".$sessionID;
			
			//$context = stream_context_create($opts);
			//$html = file_get_contents($rankings_url[$version], false, $context);
		//}
		//else
		//{
			$html = file_get_contents($rankings_url[$version]);
		//}
		if(!$html)
		{
			return false;
		}
		$pattern = '#__VIEWSTATE" value="(.*?)"#';
		$matches = array();
		$regexSuccess = preg_match($pattern, $html, $matches);
		if(!$regexSuccess)
		{
			return false;
		}
		$viewState = $matches[1];
		
		if($version == 'ems')
		{
			$pattern = '#__EVENTVALIDATION" value="(.*?)"#';
			$matches = array();
			$regexSuccess = preg_match($pattern, $html, $matches);
			if(!$regexSuccess)
			{
				return false;
			}
			$eventValidation = $matches[1];
		}
	}
	else
	{
		if($version == 'gms')
		{
			$viewState = '/wEPDwULLTEzMjIwNDg5ODcPZBYCAgEPZBYCAgMPZBYCZg9kFgICAQ8PFgQeDmludFByZXZSYW5rSURYBQI0Mx4OaW50TmV4dFJhbmtJRFgFAjUzZBYIAgMPD2QWAh4HT25DbGljawUkcmV0dXJuIGZybVNlYXJjaENoYXJhY3RlcigndG90cmFuaycpZAIFDw9kFgQeB09uS2V5dXAFDW51bV9jaGsodGhpcykeCk9uS2V5cHJlc3MFaGlmIChldmVudC5rZXlDb2RlID09IDEzKSB7X19kb1Bvc3RCYWNrKCcvQ29udHJvbHMvUmFuay9Ub3RSYW5rJFJBTkskaW1nVG9wYnRuSnVtcFRvJywnJyk7IHJldHVybiBmYWxzZTt9ZAIPDxYCHgtfIUl0ZW1Db3VudAIFFgpmD2QWAmYPFQwHI0ZGRkZGRgI0OGNodHRwOi8vbXNhdmF0YXIxLm5leG9uLm5ldC9QZXQvSEhKTEhERkZFRkRHRUxMTkZPUE9LSkZQTEpJSklPRkpHS0JGSUVOQU9MRkxDQUNNR1BFREpMT0tIQkxDR01CTi5naWagATxJTUcgU1JDPSJodHRwOi8vbXNhdmF0YXIxLm5leG9uLm5ldC9DaGFyYWN0ZXIvTlBNQkVOSUNNSUVHQ0FCQ0hNRU1JRkVET0dISEJCS05KSklCQ0pJRU5ESUhJTkZLS0NCSk1NQ0dGS0ZQS1BCSU1NRktLTENNSkREREhFRk9ESkFLRkFKSE9LQ09NTkNLLmdpZiIgQk9SREVSPSIwIj4BMApOb3ZhenN0b3J5GS9SYW5raW5nL3dvcmxkX2toYWluaS5naWYGS2hhaW5pFVJhbmtpbmcvam9iX3RoaWVmLmdpZgV0aGllZkI8Qj4xODE8L0I+PGJyPjxTUEFOIENMQVNTPW54X3RhYmxlX3RleHQwND4oNDY1LDEyMiwzOTApPC9TUEFOPjxicj4HJm5ic3A7LWQCAQ9kFgJmDxUMByNGRkZGRkYCNDljaHR0cDovL21zYXZhdGFyMS5uZXhvbi5uZXQvUGV0L1BBS01IRlBGSExLSk9PUE9JSUVJSkxCRkVMRUxIS0tGSEhJTE9NT0lGTE5CS0xQSUZFQUFKRktMTlBNTklIREEuZ2lmoAE8SU1HIFNSQz0iaHR0cDovL21zYXZhdGFyMS5uZXhvbi5uZXQvQ2hhcmFjdGVyL05HUEJBREFHSkFFS0xCQ0lIT0tOUEdCQkVPQ0dQTkRER0ZEQ0dPSUZQQkxCQ0VBTUlCQUlMT0lKSVBCUEJBTkhMTUJHTkpKT0pGSERQQUlDTEVLT1BQR0dMTERPR0ZCQS5naWYiIEJPUkRFUj0iMCI+ATAHQWxleGluYRcvUmFua2luZy93b3JsZF9iZXJhLmdpZgRCZXJhGFJhbmtpbmcvam9iX21hZ2ljaWFuLmdpZghtYWdpY2lhbkI8Qj4xODE8L0I+PGJyPjxTUEFOIENMQVNTPW54X3RhYmxlX3RleHQwND4oMjIwLDMwMSwyNTcpPC9TUEFOPjxicj4HJm5ic3A7LWQCAg9kFgJmDxUMByNCNzAwMDACNTBjaHR0cDovL21zYXZhdGFyMS5uZXhvbi5uZXQvUGV0L0hISkxIREZGRUZER0VMTE5GT1BPS0pGUExKSUpJT0ZKR0tCRklFTkFPTEZMQ0FDTUdQRURKTE9LSEJMQ0dNQk4uZ2lmoAE8SU1HIFNSQz0iaHR0cDovL21zYXZhdGFyMS5uZXhvbi5uZXQvQ2hhcmFjdGVyL0hDQUFLQkRCR09JS05LRVBETkdCSEFCSE9GTUlOSU5PRkdLTEdJREpMR0ZIQUpQSUJFTkxPRkxNS0lLQkdLSUlDRUVGSEpQS0ZCTUVNQ01NTENBR0xLQUNGTkdGQU5PSC5naWYiIEJPUkRFUj0iMCI+ATAFVGlnZXIZL1Jhbmtpbmcvd29ybGRfc2NhbmlhLmdpZgZTY2FuaWEXUmFua2luZy9qb2Jfd2Fycmlvci5naWYHd2FycmlvckE8Qj4xODE8L0I+PGJyPjxTUEFOIENMQVNTPW54X3RhYmxlX3RleHQwND4oNjMsMDc4LDEzNyk8L1NQQU4+PGJyPgcmbmJzcDstZAIDD2QWAmYPFQwHI0ZGRkZGRgI1MWNodHRwOi8vbXNhdmF0YXIxLm5leG9uLm5ldC9QZXQvQkNKSkdEQkhDTk5FSENKSkhLTENPTUNPTUFJSEhQTUJPQ05KQUZLREdFQkxOR0hGTE9QSU9DRUVPTVBLTkhMUC5naWagATxJTUcgU1JDPSJodHRwOi8vbXNhdmF0YXIxLm5leG9uLm5ldC9DaGFyYWN0ZXIvT0pCS09FSU1FSU1LTEVBSU5NS0xJTE9QREpFSU9FR0ZGSk5DUEdFUEZLRExFTEhCT0xJUEpHTUNITkFFSERMQ0tITUlPR0NBRkdKQUhLRUdDR0RFQktJUEhOUFBLREJPLmdpZiIgQk9SREVSPSIwIj4BMAZpQmFyYW0ZL1Jhbmtpbmcvd29ybGRfa2hhaW5pLmdpZgZLaGFpbmkVUmFua2luZy9qb2JfdGhpZWYuZ2lmBXRoaWVmQTxCPjE4MTwvQj48YnI+PFNQQU4gQ0xBU1M9bnhfdGFibGVfdGV4dDA0PigyNiw4MjEsNzA4KTwvU1BBTj48YnI+ByZuYnNwOy1kAgQPZBYCZg8VDAcjRkZGRkZGAjUyY2h0dHA6Ly9tc2F2YXRhcjEubmV4b24ubmV0L1BldC9ISEpMSERGRkVGREdFTExORk9QT0tKRlBMSklKSU9GSkdLQkZJRU5BT0xGTENBQ01HUEVESkxPS0hCTENHTUJOLmdpZqABPElNRyBTUkM9Imh0dHA6Ly9tc2F2YXRhcjEubmV4b24ubmV0L0NoYXJhY3Rlci9HSE1PQ0dQSE1FRU5FTkFITExETkdCTk5HQUFDREhQREpOQU9PSUhCTE1LR0VESlBJT0dQSkJQRUVFRFBMR01GRkdIS0JKTEVCTklMT0xFSk1LRktISk1PRkxKQkJGTEkuZ2lmIiBCT1JERVI9IjAiPgEwBGNzY1gXL1Jhbmtpbmcvd29ybGRfYmVyYS5naWYEQmVyYRdSYW5raW5nL2pvYl93YXJyaW9yLmdpZgd3YXJyaW9yQjxCPjE4MDwvQj48YnI+PFNQQU4gQ0xBU1M9bnhfdGFibGVfdGV4dDA0Pig3MTYsOTQ1LDgxMSk8L1NQQU4+PGJyPgcmbmJzcDstZAIRDw9kFgQfAwUNbnVtX2Noayh0aGlzKR8EBWhpZiAoZXZlbnQua2V5Q29kZSA9PSAxMykge19fZG9Qb3N0QmFjaygnL0NvbnRyb2xzL1JhbmsvVG90UmFuayRSQU5LJGltZ0JvdGJ0bkp1bXBUbycsJycpOyByZXR1cm4gZmFsc2U7fWQYAQUeX19Db250cm9sc1JlcXVpcmVQb3N0QmFja0tleV9fFgcFKC9Db250cm9scy9SYW5rL1RvdFJhbmskUkFOSyRpbWdidG5TZWFyY2gFKy9Db250cm9scy9SYW5rL1RvdFJhbmskUkFOSyRpbWdUb3BidG5KdW1wVG8FKS9Db250cm9scy9SYW5rL1RvdFJhbmskUkFOSyRpbWdUb3BCdG5QcmV2BSkvQ29udHJvbHMvUmFuay9Ub3RSYW5rJFJBTkskaW1nVG9wQnRuTmV4dAUrL0NvbnRyb2xzL1JhbmsvVG90UmFuayRSQU5LJGltZ0JvdGJ0bkp1bXBUbwUpL0NvbnRyb2xzL1JhbmsvVG90UmFuayRSQU5LJGltZ0JvdEJ0blByZXYFKS9Db250cm9scy9SYW5rL1RvdFJhbmskUkFOSyRpbWdCb3RCdG5OZXh0';
		}
		else if($version == 'msea')
		{
			$viewState = 'dDwxNDE5MDEyMTMwO3Q8cDxsPGN1cnJlbnRSYW5rOz47bDxpPDE+Oz4+O2w8aTwxPjs+O2w8dDw7bDxpPDE5Pjs+O2w8dDxwPGw8XyFJdGVtQ291bnQ7PjtsPGk8NT47Pj47bDxpPDA+O2k8MT47aTwyPjtpPDM+O2k8ND47PjtsPHQ8O2w8aTwwPjs+O2w8dDxAPDE7XDxpbWcgc3JjPSJodHRwOi8vYXZhdGFyLm1hcGxlc2VhLmNvbS9DaGFyYWN0ZXIvQ0xLUFBLRFBCTFBPQ0NEQ1BIQURLSEhGREZBQ01DUEFKQ0tDSkZOQ0xDQ0lDUElGSEpLR0pHSU5KTkNDSUVLQkxDSEpITkJETkdKUE5QREVBS0hBQUFBTkFQRk9DSkZMLmdpZiIgL1w+O01yWWFOZEFvO01yWWFOZEFvOzA7QXF1aWxhO1RoaWVmO1RoaWVmOzIwMDsyLDEyMSwyNzYsMzIzOz47Oz47Pj47dDw7bDxpPDA+Oz47bDx0PEA8MjtcPGltZyBzcmM9Imh0dHA6Ly9hdmF0YXIubWFwbGVzZWEuY29tL0NoYXJhY3Rlci9NSUVFTUdKSUVGR0NQSUJCRU5NT0RMTENFT0tBSEFKT05PREtMSURPRExBSUFIS0pETkFGTU9CSkpOR0hER0ZHRERBQU5BQktOT0xDTEFIUE9ET05LRFBKREhBR0NNSUYuZ2lmIiAvXD47T3NzYXJpcztPc3NhcmlzOzA7QXF1aWxhO1dhcnJpb3I7V2FycmlvcjsyMDA7MiwxMjEsMjc2LDMyMzs+Ozs+Oz4+O3Q8O2w8aTwwPjs+O2w8dDxAPDM7XDxpbWcgc3JjPSJodHRwOi8vYXZhdGFyLm1hcGxlc2VhLmNvbS9DaGFyYWN0ZXIvSkVFSERITkpEQktLR0RDR0ZFS0hGTkhNR0RLT09OS0NGRE9NUE9MQUtJSkZPT09BREpIQUZEQkxQTE5FR0hCQ0tGTEpFQ01PSk5GTk5NQ0VBS09KRkRMQUhBT0JBQUhMLmdpZiIgL1w+O2plc3NidW5ueTtqZXNzYnVubnk7MTtCb290ZXM7TWFnaWNpYW47TWFnaWNpYW47MjAwOzIsMTIxLDI3NiwzMjM7Pjs7Pjs+Pjt0PDtsPGk8MD47PjtsPHQ8QDw0O1w8aW1nIHNyYz0iaHR0cDovL2F2YXRhci5tYXBsZXNlYS5jb20vQ2hhcmFjdGVyL0hEQ0RKR0FHQkJOR0dKR0lNTE9LTERPQUpLQkxOSUJJSUVPRUpCTU5IQk9IUE9BQUFOSlBCQ0hQQU5OS0xOT05GTUdPTkxNTkhCRUVER0dJRUlBSUlCUElESU5OR09ERS5naWYiIC9cPjtCZWxJZTtCZWxJZTswO0FxdWlsYTtXYXJyaW9yO1dhcnJpb3I7MjAwOzIsMTIxLDI3NiwzMjM7Pjs7Pjs+Pjt0PDtsPGk8MD47PjtsPHQ8QDw1O1w8aW1nIHNyYz0iaHR0cDovL2F2YXRhci5tYXBsZXNlYS5jb20vQ2hhcmFjdGVyL0tIR05ESkpHS01QTk1QUFBLS0xHQ0VJSUhBTU1GUEpDREhCSU1DREVNSElJRk1GQkhDR0hJRkJKUENFS0hPTklPUEZKSUFEQ05KRUdJSklLR0dPT0dDTExPTkFQSkhBTC5naWYiIC9cPjtwdXJwbGVwb3A7cHVycGxlcG9wOzE7Qm9vdGVzO01hZ2ljaWFuO01hZ2ljaWFuOzIwMDsyLDEyMSwyNzYsMzIzOz47Oz47Pj47Pj47Pj47Pj47bDxDaGFyYWN0ZXJTZWFyY2hBY3Rpb247U2VhcmNoVG9wQWN0aW9uO1ByZXZpb3VzVG9wQWN0aW9uO05leHRUb3BBY3Rpb247U2VhcmNoQm90dG9tQWN0aW9uO1ByZXZpb3VzQm90dG9tQWN0aW9uO05leHRCb3R0b21BY3Rpb247Pj6lXV1tKbD/Hr5ljwfVFxsTQuUvJA=='; 
		}
		else if($version == 'ems')
		{
			$viewState = '/wEPDwUKMjA5ODc2MjgxOQ9kFgJmDw8WBB4OaW50UHJldlJhbmtJRFgFAi00Hg5pbnROZXh0UmFua0lEWAUBNmQWAmYPZBYiZg9kFgQCAQ8PFgIeB1Zpc2libGVoZGQCAw9kFgICAQ8PFgIeBFRleHQFI1BsZWFzZSBsb2cgaW4gdG8gY2hlY2sgeW91ciBzdGF0dXMuZGQCAQ9kFgJmDxYCHgtfIUl0ZW1Db3VudAIDFgZmD2QWDGYPFQKtATxJTUcgU1JDPSJodHRwOi8vYXZhdGFyMS5tYXBsZWV1cm9wZS5jb20vQ2hhcmFjdGVyL1BLTU1ISERHQ0tNQk9IRUpOQUZBQkVFSkVDS0dLRE1CRkVHTUNKRUJFSExFSkRDRkhGSExFSFBNTk9JT0VGT09NREtFT0JJSk1GSExGUEZBS0ZER0ZJTkhJT0VFQk9FSi5naWYiICBhbGlnbj0iYWJzTWlkZGxlIiA+B1N3aW5GbHVkAgEPFQEGa3JhZGlhZAICDxUBDE5pZ2h0IFdhbGtlcmQCAw8VARFMZXZlbCA6IDxiPjMyPC9iPmQCBA8VAQcxMDMsMDc2ZAIFDxUBejxJTUcgU1JDPSJodHRwOi8vbXNpbWFnZS5uZXhvbmV1LmNvbS9hdmF0YXIvbG9naW5fbW92ZV91cC5naWYiICBhbGlnbj0iYWJzbWlkZGxlIj48c3BhbiBjbGFzcz0icmFua19pbmZvMDIiPjU3OCwwNjM8L3NwYW4+ZAICD2QWDGYPFQKtATxJTUcgU1JDPSJodHRwOi8vYXZhdGFyMS5tYXBsZWV1cm9wZS5jb20vQ2hhcmFjdGVyL0hIR0ZHUEtKREdKQ0NJS0lBRE5FQUdBS0JEUEZBTkJGS0lQUEdDS0VMQURCRE9JRUdJT0RGTkdLTUNQSUpDUElOQk1LTEdPQk9QSUpOREtJS0lMSkRKSkdHUE1MQ0RFQi5naWYiICBhbGlnbj0iYWJzTWlkZGxlIiA+BldiQ29sZWQCAQ8VAQZrcmFkaWFkAgIPFQEMV2luZCBCcmVha2VyZAIDDxUBEUxldmVsIDogPGI+MzA8L2I+ZAIEDxUBBzExNSwxMjhkAgUPFQF6PElNRyBTUkM9Imh0dHA6Ly9tc2ltYWdlLm5leG9uZXUuY29tL2F2YXRhci9sb2dpbl9tb3ZlX3VwLmdpZiIgIGFsaWduPSJhYnNtaWRkbGUiPjxzcGFuIGNsYXNzPSJyYW5rX2luZm8wMiI+NTc3LDk5Mjwvc3Bhbj5kAgQPZBYMZg8VAq0BPElNRyBTUkM9Imh0dHA6Ly9hdmF0YXIxLm1hcGxlZXVyb3BlLmNvbS9DaGFyYWN0ZXIvRElBQkNETE5HSE5PTkdLS0lMSUVMSE5CS1BGQUxQT0pPQUJOS0lPR01LS09MRktKSE5LREZNUEVJR0xIREtFTEZLQUJFR0RKSUtCRkJJR0dMRUZESkhPTEFIT0dHSUVBLmdpZiIgIGFsaWduPSJhYnNNaWRkbGUiID4KQW5hRGV4TGVzc2QCAQ8VAQZrcmFkaWFkAgIPFQEMTmlnaHQgV2Fsa2VyZAIDDxUBEUxldmVsIDogPGI+MzE8L2I+ZAIEDxUBBzEwMyw1NjFkAgUPFQF6PElNRyBTUkM9Imh0dHA6Ly9tc2ltYWdlLm5leG9uZXUuY29tL2F2YXRhci9sb2dpbl9tb3ZlX3VwLmdpZiIgIGFsaWduPSJhYnNtaWRkbGUiPjxzcGFuIGNsYXNzPSJyYW5rX2luZm8wMiI+NTcxLDgzMTwvc3Bhbj5kAgIPZBYCZg8PFgIfAmhkZAIDDw9kFgIeCm9ua2V5cHJlc3MFWWlmIChldmVudC5rZXlDb2RlID09IDEzKSB7X19kb1Bvc3RCYWNrKCdfdWNfR2FtZVJhbmskaW1nVG9wQnRuSnVtcFRvJywnJyk7IHJldHVybiBmYWxzZTt9ZAIEDw8WAh4ISW1hZ2VVcmwFKmh0dHA6Ly9tc2ltYWdlLm5leG9uZXUuY29tL2VuL2ZvcnVtL2dvLmdpZhYCHgV0aXRsZQUEUmFua2QCBQ8PFgQfBgUyaHR0cDovL21zaW1hZ2UubmV4b25ldS5jb20vZW4vcmFua2luZy9iYnNfcHJldi5naWYeDUFsdGVybmF0ZVRleHQFBVByZXY1ZGQCBg8PFgQfBgUyaHR0cDovL21zaW1hZ2UubmV4b25ldS5jb20vZW4vcmFua2luZy9iYnNfbmV4dC5naWYfCAUFTmV4dDVkZAIHDw9kFgIfBQVZaWYgKGV2ZW50LmtleUNvZGUgPT0gMTMpIHtfX2RvUG9zdEJhY2soJ191Y19HYW1lUmFuayRpbWdUb3BCdG5TZWFyY2gnLCcnKTsgcmV0dXJuIGZhbHNlO31kAggPDxYEHwYFLmh0dHA6Ly9tc2ltYWdlLm5leG9uZXUuY29tL2VuL2ZvcnVtL3NlYXJjaC5naWYfCAUJQ2hhcmFjdGVyFgIfBwUJQ2hhcmFjdGVyZAIJDw8WAh8DBQpMZXZlbC9Nb3ZlZGQCCg8WAh8EAgUWCmYPZBYGZg8VBhFyYW5raW5nX2xpc3RfYmcwMgExZ2h0dHA6Ly9hdmF0YXIxLm1hcGxlZXVyb3BlLmNvbS9QZXQvQUNDSE9FSk1IS0VQQk1HQVBJTUZMUE1BR05GT0hIRkxITk1QTEVER0lQR05BSkRKRVBJSk9LREJGTE5ER0NHQi5naWakATxJTUcgU1JDPSJodHRwOi8vYXZhdGFyMS5tYXBsZWV1cm9wZS5jb20vQ2hhcmFjdGVyL0dBTUtDQ0xCR0tDR0tBSVBER09ISU1IR01MRU1QT0ZNR0FQREdBRUxIR0NCRUxMS0JLQUhCTkpISEJMQkdLRkhPQ0NCUE5OUEZCR0xDSUlIS0xKRUVBSENIR0FKQkZQRC5naWYiIEJPUkRFUj0iMCI+CEl0em1qYXVaBVRoaWVmZAIBDxUEEi9BdmF0YXIva3JhZGlhLmdpZgZrcmFkaWEGa3JhZGlhNDxCPjIwMDwvQj48YnI+PFNQQU4gQ0xBU1M9cmFua19pbmZvMDE+KDApPC9TUEFOPjxicj5kAgIPFQEIJm5ic3A7LSBkAgEPZBYGZg8VBhFyYW5raW5nX2xpc3RfYmcwMgEyZ2h0dHA6Ly9hdmF0YXIxLm1hcGxlZXVyb3BlLmNvbS9QZXQvSEhKTEhERkZFRkRHRUxMTkZPUE9LSkZQTEpJSklPRkpHS0JGSUVOQU9MRkxDQUNNR1BFREpMT0tIQkxDR01CTi5naWakATxJTUcgU1JDPSJodHRwOi8vYXZhdGFyMS5tYXBsZWV1cm9wZS5jb20vQ2hhcmFjdGVyL0hGRkRET0hHRkZCQkdDS0FMUEdPR0xBTUpNS0VBREJJTUZMSERDSEJKSUVOR0JQSU5FSUVKSExNSExIQ09ERUdGRkFFRkpGR0RCQUhQT0FFS0dHSUZORk9OREtPRURLSy5naWYiIEJPUkRFUj0iMCI+CFJ5dUg0ZG91BVRoaWVmZAIBDxUEEi9BdmF0YXIva3JhZGlhLmdpZgZrcmFkaWEGa3JhZGlhPjxCPjE4NDwvQj48YnI+PFNQQU4gQ0xBU1M9cmFua19pbmZvMDE+KDQ2NCw1ODYsODUwKTwvU1BBTj48YnI+ZAICDxUBCCZuYnNwOy0gZAICD2QWBmYPFQYRcmFua2luZ19saXN0X2JnMDIBM2dodHRwOi8vYXZhdGFyMS5tYXBsZWV1cm9wZS5jb20vUGV0L0hISkxIREZGRUZER0VMTE5GT1BPS0pGUExKSUpJT0ZKR0tCRklFTkFPTEZMQ0FDTUdQRURKTE9LSEJMQ0dNQk4uZ2lmpAE8SU1HIFNSQz0iaHR0cDovL2F2YXRhcjEubWFwbGVldXJvcGUuY29tL0NoYXJhY3Rlci9HTERPRFBJTkFDUERKSktOTk1BS09ESk1LUEdKSk1QTUVMSlBMR0lHQ0dLRk5OSEZMRUNGQ09CQ01OSEtESU1MRExBUENFS0hQQUVIQUJNRERGQUxCQkhJTkFCTUVGQUkuZ2lmIiBCT1JERVI9IjAiPgZPYmplY3QITWFnaWNpYW5kAgEPFQQSL0F2YXRhci9rcmFkaWEuZ2lmBmtyYWRpYQZrcmFkaWE+PEI+MTgzPC9CPjxicj48U1BBTiBDTEFTUz1yYW5rX2luZm8wMT4oMzUyLDI4NCwwNjEpPC9TUEFOPjxicj5kAgIPFQEIJm5ic3A7LSBkAgMPZBYGZg8VBhFyYW5raW5nX2xpc3RfYmcwMgE0Z2h0dHA6Ly9hdmF0YXIxLm1hcGxlZXVyb3BlLmNvbS9QZXQvSEJERU5KREZFUEJDSkNKQkxMQUtESklGSUlBR0lQTUdOTU5JS0NGS0VDSUVERU5QQkpITU1PQ1BIRk1PQ0lGSC5naWakATxJTUcgU1JDPSJodHRwOi8vYXZhdGFyMS5tYXBsZWV1cm9wZS5jb20vQ2hhcmFjdGVyL0NJR0lEREdKSENORU9GRUdNT0lDQk9MTERBRERBTE1BSkFCSE9CS0tMTEZPSlBGT0NQQlBBQ09EREFCTUpJQUJCTkZERkdMQUlHTVBJR0xFTkxERk5FR0JKR1BBQlBISC5naWYiIEJPUkRFUj0iMCI+BFBld2UHV2FycmlvcmQCAQ8VBBIvQXZhdGFyL2tyYWRpYS5naWYGa3JhZGlhBmtyYWRpYT48Qj4xODI8L0I+PGJyPjxTUEFOIENMQVNTPXJhbmtfaW5mbzAxPig2MjAsNTI3LDIyMyk8L1NQQU4+PGJyPmQCAg8VAQgmbmJzcDstIGQCBA9kFgZmDxUGEXJhbmtpbmdfbGlzdF9iZzAyATVnaHR0cDovL2F2YXRhcjEubWFwbGVldXJvcGUuY29tL1BldC9ISEpMSERGRkVGREdFTExORk9QT0tKRlBMSklKSU9GSkdLQkZJRU5BT0xGTENBQ01HUEVESkxPS0hCTENHTUJOLmdpZqQBPElNRyBTUkM9Imh0dHA6Ly9hdmF0YXIxLm1hcGxlZXVyb3BlLmNvbS9DaGFyYWN0ZXIvTEFIREpMTkFLQUdQTE9LSUZOSEVCSUxITkhQUE5KQ0RLRUpCUFBJTUJQRUNOS0xPSENISkdNRUpCTUZQRExOTU5ITUdNQUJPTUxDUEhMR09BRE9KTUxITk9HS0JMSUNELmdpZiIgQk9SREVSPSIwIj4JbFR6RGFuaWVsBVRoaWVmZAIBDxUEEi9BdmF0YXIva3JhZGlhLmdpZgZrcmFkaWEGa3JhZGlhPjxCPjE4MDwvQj48YnI+PFNQQU4gQ0xBU1M9cmFua19pbmZvMDE+KDYxOSw1NTgsMTk4KTwvU1BBTj48YnI+ZAICDxUBCCZuYnNwOy0gZAILDw9kFgIfBQVZaWYgKGV2ZW50LmtleUNvZGUgPT0gMTMpIHtfX2RvUG9zdEJhY2soJ191Y19HYW1lUmFuayRpbWdCb3RCdG5KdW1wVG8nLCcnKTsgcmV0dXJuIGZhbHNlO31kAgwPDxYCHwYFKmh0dHA6Ly9tc2ltYWdlLm5leG9uZXUuY29tL2VuL2ZvcnVtL2dvLmdpZhYCHwcFBFJhbmtkAg0PDxYEHwYFMmh0dHA6Ly9tc2ltYWdlLm5leG9uZXUuY29tL2VuL3JhbmtpbmcvYmJzX3ByZXYuZ2lmHwgFBVByZXY1ZGQCDg8PFgQfBgUyaHR0cDovL21zaW1hZ2UubmV4b25ldS5jb20vZW4vcmFua2luZy9iYnNfbmV4dC5naWYfCAUFTmV4dDVkZAIPDw9kFgIfBQVZaWYgKGV2ZW50LmtleUNvZGUgPT0gMTMpIHtfX2RvUG9zdEJhY2soJ191Y19HYW1lUmFuayRpbWdCb3RCdG5TZWFyY2gnLCcnKTsgcmV0dXJuIGZhbHNlO31kAhAPDxYEHwYFLmh0dHA6Ly9tc2ltYWdlLm5leG9uZXUuY29tL2VuL2ZvcnVtL3NlYXJjaC5naWYfCAUJQ2hhcmFjdGVyFgIfBwUJQ2hhcmFjdGVyZBgBBR5fX0NvbnRyb2xzUmVxdWlyZVBvc3RCYWNrS2V5X18WCAUcX3VjX0dhbWVSYW5rJGltZ1RvcEJ0bkp1bXBUbwUaX3VjX0dhbWVSYW5rJGltZ1RvcEJ0blByZXYFGl91Y19HYW1lUmFuayRpbWdUb3BCdG5OZXh0BRxfdWNfR2FtZVJhbmskaW1nVG9wQnRuU2VhcmNoBRxfdWNfR2FtZVJhbmskaW1nQm90QnRuSnVtcFRvBRpfdWNfR2FtZVJhbmskaW1nQm90QnRuUHJldgUaX3VjX0dhbWVSYW5rJGltZ0JvdEJ0bk5leHQFHF91Y19HYW1lUmFuayRpbWdCb3RCdG5TZWFyY2hh5tOH0JP/g1fKlg/x1mrlXSHN2g==';
			$eventValidation = '/wEWDQLS963tBgKbiuqsCgLA06gUAuzRxo8HAsuSrOkNAqWzjcEJAsa7n5MLApuKosAIAp7Vj8MGAsK/5JwJArHKuKABAv2olLAFAuT7oZcLNQbWUE8Y2MmQdhCzY7LBr6HAAgQ=';
		}
	}
	
	return true;
}

function getEMSsessionID()
{
	// Get a valid EMS sessionID by going to the site and reading the cookie it wants to set
	global $rankings_url;
	$emsHost = parse_url($rankings_url['ems'], PHP_URL_HOST);
	$emsSocket = fsockopen($emsHost, 80);
	if(!$emsSocket)
	{
		die('EMS socket opening failed while getting session ID');
	}
	
	$request = "GET / HTTP/1.1\r\n";
	$request .= "Host: ".$emsHost."\r\n";
	$request .= "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.14) Gecko/20080404 Firefox/2.0.0.14\r\n";
	$request .= "Connection: Close\r\n\r\n";
	fwrite($emsSocket, $request);
	
	$contents = '';
	while(!feof($emsSocket))
	{
		$contents .= fread($emsSocket, 8192);
	}
	fclose($emsSocket);

	$matches = array();
	$pattern = '@ASP.NET_SessionId=(.*?);@';
	$regexSuccess = preg_match($pattern, $contents, $matches);
	if(!$regexSuccess)
	{
		if($regexSuccess === false)
		{
			die('error in EMS sessionID regex');
		}
		else if($regexSuccess === 0)
		{
			die('no matches in EMS sessionID regex');
		}
	}
	
	$sessionID = $matches[1];
	return $sessionID;
}


function getExpRequired($currentLevel, $version)
{
	global $expForNext;
	if($version == "gms") // gms has reduced the exp requirements to level
	{
		if($currentLevel >= 30)
		{
			return (int) ($expForNext[$currentLevel] * 0.8); // 80% normal exp required for >= level 30
		}
		else if($currentLevel >= 10)
		{
			return (int) ($expForNext[$currentLevel] * 0.6667); // .6667 appears to be the actual multiplier, not the exact value of 2/3
		}
		else
		{
			return $expForNext[$currentLevel];
		}
	}
	else
	{
		return $expForNext[$currentLevel];
	}
}

?>