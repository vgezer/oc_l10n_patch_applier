<?php
/**
 * ownCloud
 *
 * @author Volkan Gezer
 * @copyright 2014-2015 Volkan Gezer volkangezer@gmail.com
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
$total_files = 0;
$failed_files = 0;
$total_skipped = 0;
if(PHP_SAPI !== 'cli') {
  echo("<html><title>ownCloud Language Patch Creator/Applier</title><body>");
}
if((isset($argv[1]) && $argv[1] === "start") || isset($_GET['start']))
{
  $strings_tmp = array();
  $stripped = array(array());
  echo_nl2br("<green>Starting Actual Write</close>\n");
  $lines = @file("copy_strings");
  if(!$lines) {
    echo_nl2br("<red>copy_strings not found! Have you created the patch?</close>\n");
    die;
  }
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
  if(empty($strings)) {
    echo_nl2br("<red>Nothing to do. There are no strings to look for in copy_strings!</close>");
    die;
  }
  foreach($stripped as $index => $param)
  {
    $lines = array();
    $file = $param[0];
    $string = $param[1];
    $found_str = false;
    $whole_language_file = @file($file);
    // Search all found language files for new strings
    if(!$whole_language_file) {
      echo_nl2br("\n<red>Language \"$file\" is new or cannot be found, skipping to the next language...</close>\n");
      $failed_files++;
      continue;
    }
    $only_key = explode("\" => ", $string);
    echo_nl2br("\nLooking for \"".mb_substr($only_key[0], 0, 50)."[...]\" in $file\n");
    foreach($whole_language_file as $line)
    {
	    if(preg_match("/\b(".$only_key[0].")\b/", $line) > 0)
	    {
		// Hmm, if we are here, this means that the patch is already applied. Skip.
		echo_nl2br("=> Skipping... \"".mb_substr($string, 0, 50)."[...]\" is already in file \"$file\". The translation is: $line\n");
		$total_skipped++;
		$found_str = true;
		break;
	    }
    }
    if(!$found_str)
    {
	// If it is not found, do the magic and create a fresh language file including the new string
	echo_nl2br("<green>Could not found \"".mb_substr($string, 0, 50)."[...]\" in file \"$file\". Copying full translation: \"$string\"</close>\n");
	$total_files++;
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
  echo_nl2br("\n<green>Patching is done! $total_files string(s) have been patched. $total_skipped string(s) have the patch already.</close> <red>$failed_files string(s) have failed!</close>\n");
  if(PHP_SAPI !== 'cli') {
    echo_nl2br("<b>Now change permissions of all files inside l10n dirs to 775. Run: find . -maxdepth 4 -type d -name \"l10n\" -exec sh -c 'cd  \"{}\"/../ && pwd && chmod -R 775 *' \;</b>\n");
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
if(!$lines) {
  echo_nl2br("<red>find_strings not found! You need to add \"file\",\"string\" pairs to this file to create patch!</close>\n");
  die;
}

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
    // Only search for js and json files
    $display = Array ( 'js', 'json' );
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
		    echo_nl2br("<green>=> Found \"".mb_substr($string, 0, 50)."[...]\" in file \"$file\". Queuing for copying. The full line is: $rec</close>");
		    $total_files++;
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
    echo_nl2br("<red>ERROR: Could not add the file: $e</close>\n");
  }

}
echo_nl2br("\n<green>Everything is done! $total_files string(s) will searched to apply the patch. Start patching by using start argument.</close>\n");
if(PHP_SAPI !== 'cli') {
  echo_nl2br("<b>Now change permissions of all files inside l10n dirs to 777. Run: find . -maxdepth 4 -type d -name \"l10n\" -exec sh -c 'cd  \"{}\"/../ && pwd && chmod -R 777 *' \;</b>\n");
  echo("</body></html>");
}
fclose($cpy_strings);

// Convert newlines into <br>'s if browser is used
function echo_nl2br($text) {
    if(PHP_SAPI !== 'cli') {
      $text = str_replace("<green>", "<font style='color: green'>", $text);
      $text = str_replace("<red>", "<font style='color: red'>", $text);
      $text = str_replace("</close>", "</font>", $text);
      echo nl2br($text);
    }
    else {
      $text = str_replace("<green>", "\033[32m", $text);
      $text = str_replace("<red>", "\033[31m", $text);
      $text = str_replace("</close>", "\033[0m", $text);
      echo($text);
    }
}

?>
