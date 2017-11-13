This file contains instructions for updating your Open Data-based Drupal site.

Open Data has a two-pronged update process. Out of the box, it provides a great
deal of default configuration for your site, but once it's installed, all that
configuration is "owned" by your site and Open Data cannot safely modify it
without potentially changing your site's behavior or, in a worst-case scenario,
causing data loss.

As it evolves, Open Data's default configuration may change. In certain limited
cases, Open Data will attempt to safely update configuration that it depends on
(which will usually be locked anyway to prevent you from modifying it).
Otherwise, Open Data will leave your configuration alone, respecting the fact
that your site owns it. So, to bring your site fully up-to-date with the latest
default configuration, you must follow the appropriate set(s) of instructions in
the "Manual update steps" section of this file.

## Updating Open Data

### Composer
If you've installed Open Data using our
[Composer-based project template][wxt-project], all you need to do is:

* ```cd /path/to/YOUR_PROJECT```
* ```composer update```
* Run ```drush updatedb``` or visit ```update.php``` to perform db updates.
* Perform any necessary manual updates (see below).

### Tarball
**Do not use ```drush pm-update``` or ```drush up``` to update Open Data!**
Open Data includes specific, vetted, pre-tested versions of modules, and
occasionally patches for those modules (and Drupal core). Drush's updater
totally disregards all of that and may therefore break your site.

To update Open Data safely:

1. Download the latest version of Open Data from
   https://github.com/open-data/od and extract it.
2. Replace your ```profiles/od``` directory with the one included in the
   fresh copy of Open Data.
3. Replace your ```core``` directory with the one included in the fresh copy
   Open Data.
4. Visit ```update.php``` or run ```drush updatedb``` to perform any necessary
   database updates.
5. Perform any necessary manual updates (see below).

## Manual update steps

These instructions describe how to update your site's configuration to bring
it in line with a newer version of Open Data. These changes are never made
automatically by Open Data because they have the potential to change the way
your site works.


<!-- Links Referenced -->

[wxt-project]:                https://github.com/drupalwxt/wxt-project
