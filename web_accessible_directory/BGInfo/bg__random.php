<?php

/* Picks a random "normal" layout to use (avoids special layouts such as itself) */

global $conf_BGInfoDir;

$layouts = glob($conf_BGInfoDir.'/bg_[!_]*.php', GLOB_NOSORT);
if($layouts === false)
{
	die('error in random glob');
}
if(sizeof($layouts) == 0)
{
	die('no layouts to pick');
}

$randomIndex = mt_rand(0, sizeof($layouts) - 1);
$_random_layout = $layouts[$randomIndex];

unset($layouts); unset($randomIndex); // make the global namespace as clean as possible
require($_random_layout);

?>