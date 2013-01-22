# newznab-safebackfill-tmux

* This uses the backfill script from Thracky's 'safe' backfill script and the monitor php script from Johnyboy's Newznab-tmux script and combines them both using tmux giving you a nice overview of what's happening whist the backfill is running.

* The script can be safely run via cron to ensure it is always up. The main newznab-safebackfill-tmux.sh script will only create a new instance of tmux if one is not already running.

* For more information on the functionality, please take a look at the GitHub pages in the Credits section.

# Usage

* newznab-safebackfill-tmux can be installed in any location but <newznab location>/misc/update_scripts/nix_scripts/ is recommended.

* Once the files are in place, edit the config in **config/newznab-safebackfill-tmux.conf**

* Here you can set the number of days to backfill and the path to your newznab install. Database settings come from the main newznab config.

* Edit **bin/safebackfill.sh** and choose to run the standard or threaded versions of **backfill.php** and **update_binaries.php** as per the comments.

* Once configured, simply run **./newznab-safebackfill-tmux.sh** and **tmux att** to attach to the session when needed.

# Credits

Credit goes to Thracky (https://github.com/Thracky/nnstuff) and Johnnyboy (https://github.com/jonnyboy/newznab-tmux/) for their initial hard work.



