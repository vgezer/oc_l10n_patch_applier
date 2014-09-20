ownCloud Language Patch Applier
===============================

A script to create &amp; apply language patches for ownCloud


Usage
------

 * Create a file named `find_strings`
 * In each line, write a filename of the string that needs to be patched inside double quotes and the original English string itself.

   e.g.

   ~~~
   "settings","Dummy string"
   "apps/files_external","Here is the second string"
   ~~~

 * Run script.

   * To use as console script, run `php5 find.php` in the root directory of ownCloud.
   * Otherwise, call the script using http://localhost/find.php

      This will search the strings written in `find_strings` file in specified app's `l10n` directory
      and copy the available translations into `copy_strings` file.

 * Check for errors and the contents of `copy_strings` file.
 * Switch to another branch which the patch should be applied to.
 * Call the script again using "start" argument.

   * For console: php5 find.php start
   * For browser, first, you need to change the permissions of all `l10n` files inside ownCloud dir. For this, run:

   ~~~
   find . -maxdepth 4 -type d -name "l10n" -exec sh -c 'cd "{}"/../ && pwd && chmod -R 777 *' \;
   ~~~

   inside terminal. Then change the permissions back by invoking this command:

   ~~~
   find . -maxdepth 4 -type d -name "l10n" -exec sh -c 'cd "{}"/../ && pwd && chmod -R 775 *' \;
   ~~~

 * Commit & push

