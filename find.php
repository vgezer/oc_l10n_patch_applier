<?php
/**
 * ownCloud
 *
 * @author Volkan Gezer
 * @copyright 2014 Volkan Gezer volkangezer@gmail.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
if(PHP_SAPI !== 'cli') {
  echo("<html><title>ownCloud Language Patch Creator/Applier</title><body>");
}
if((isset($argv[1]) && $argv[1] === "start") || isset($_GET['start']))
{
  $strings_tmp = array();
  $stripped = array(array());
  echo_nl2br("Starting Actual Write\n");
  $lines = @file("copy_strings");
  if(!$lines) die("copy_strings not found!\n");
  foreach ($lines as $line_num => $line) {
    // Comma should only split into two as we might have commas inside string
    $strings_tmp[]=explode(",", $line, 2);
  }
  for($i = 0; $i < count($strings_tmp); $i++)
  {

    for($j = 0; $j < 2; $j++)
    {
      // Like comma, only remove the first and last quote since we might have quotes inside string
      $stripped[$i][$j] = preg_replace('/(^[\"\']|[\"\']$)/', '', trim($strings_tmp[$i][$j]));
    }
    $strings[$stripped[$i][0]] = $stripped[$i][1];

  }
  if(empty($strings)) die("Nothing to do. There are no strings to look for in copy_strings!");
  foreach($stripped as $index => $param)
  {
    $lines = array();
    $file = $param[0];
    $string = $param[1];
    $found_str = false;
    $whole_language_file = file($file);
    echo_nl2br("\nLooking for \"".mb_substr($string, 0, 50)."[...]\" in $file\n");
    $only_key = explode("\" => \"", $string);
    // Search all found language files for new strings
    foreach($whole_language_file as $line)
    {
	    if(preg_match("/\b".$only_key[0]."\b/", $line) > 0)
	    {
		// Hmm, if we are here, this means that the patch is already applied. Skip.
		echo_nl2br("=> Skipping... \"".mb_substr($string, 0, 50)."[...]\" is already in file \"$file\". The translation is: $line\n");
		$found_str = true;
		break;
	    }
    }
    if(!$found_str)
    {
	// If it is not found, do the magic and create a fresh language file including the new string
	echo_nl2br("Could not found \"".mb_substr($string, 0, 50)."[...]\" in file \"$file\". Copying full translation: \"$string\"\n");
	$the_prev_line = array();
	// If the string is the last string in language file, it will have no comma. So add it...
	if(substr($whole_language_file[count($whole_language_file)-3],-2, 1) == ",") {
	  $the_prev_line = array(count($whole_language_file)-3 => $whole_language_file[count($whole_language_file)-3]);
	}
	else {
	  $the_prev_line = array(count($whole_language_file)-3 => str_replace("\n", ",\n", $whole_language_file[count($whole_language_file)-3]));
	}
	$new_lng_file = array_slice($whole_language_file, 0, count($whole_language_file)-3, true) + $the_prev_line + array(count($whole_language_file)-2 => "\"".$string."\n");
	// Finish the file
	$file_end = array_slice($whole_language_file, -2, 2, false);

	$new_lng_file = array_merge($new_lng_file, $file_end);
	// And write it
	file_put_contents($file, $new_lng_file);
    }
  }
  echo_nl2br("\nPatching is done!\n");
  if(PHP_SAPI !== 'cli') {
    echo_nl2br(" <b>Now 'chmod 775 *' all files inside l10n dirs. Run: find . -maxdepth 4 -type d -name \"l10n\" -exec sh -c 'cd  \"{}\"/../ && pwd && chmod -R 775 *' \;</b>\n");
  }
  else {
  // Run shell commands to automate
//     shell_exec("git checkout -b stable_l10n_backport");
//     shell_exec("git commit -a -m \"translation backport\"");
//     shell_exec("git push origin stable_l10n_backport");
  }
  die;
}

$lines = @file("find_strings");
if(!$lines) die("find_strings not found!\n");

foreach ($lines as $line_num => $line) {
  // Comma should only split into two as we might have commas inside string
  $strings_tmp[]=explode(",", $line, 2);
}

for($i = 0; $i < count($strings_tmp); $i++)
{
  for($j = 0; $j < 2; $j++)
  {
    // Like comma, only remove the first and last quote since we might have quotes inside string
    $stripped[$i][$j] = preg_replace('/(^[\"\']|[\"\']$)/', '', trim($strings_tmp[$i][$j]));
  }
}
$cpy_strings = fopen("copy_strings","w+");
foreach($stripped as $index => $param)
{
  $dir = $param[0]; // keeps the directory
  $string = $param[1]; // keeps the original English string
  try {
    $it = new RecursiveDirectoryIterator($dir."/l10n/");
    // Only search for php files
    $display = Array ( 'php' );
    foreach(new RecursiveIteratorIterator($it) as $file)
    {
	// Search for language files inside l10n dirs
	if (in_array(strtolower(array_pop(explode('.', $file))), $display))
	{
	    $fp = fopen($file,"r");
	    echo_nl2br("\nSearch for: \"".mb_substr($string, 0, 50)."[...]\" in \"$dir\", file \"$file\"\n");
	    while($rec = fgets($fp)){
		// check to see if the line contains the string
		if (preg_match("/\b$string\b/", $rec) > 0){
		    // if so copy the whole line into copy_strings file
		    echo_nl2br("=> Found \"".mb_substr($string, 0, 50)."[...]\" in file \"$file\". Queuing for copying. The full line is: $rec");
		    // If this is the last string inside language file, add comma as well
		    if(strrpos($rec, ",") === false) {
			$rec = substr($rec, 0, strlen($rec)-1);
			$rec.=",\n";
		    }
		    fputs($cpy_strings, "\"$file\", $rec");
		    break;
		}
	    }
	    fclose($fp);
	}
    }
  }
  catch (Exception $e) {
    echo_nl2br("ERROR: Could not add the file: $e\n");
  }

}
echo_nl2br("\nEverything is done! Start patching by using start argument\n");
if(PHP_SAPI !== 'cli') {
  echo_nl2br(" <b>Now 'chmod 777 *' all files inside l10n dirs. Run: find . -maxdepth 4 -type d -name \"l10n\" -exec sh -c 'cd  \"{}\"/../ && pwd && chmod -R 777 *' \;</b>\n");
  echo("</body></html>");
}
fclose($cpy_strings);

// Convert newlines into <br>'s if browser is used
function echo_nl2br($text) {
    if(PHP_SAPI !== 'cli') echo nl2br($text);
    else echo($text);
}

?>
