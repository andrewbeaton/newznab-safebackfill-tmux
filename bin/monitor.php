<?php

$config = parse_ini_file("../config/newznab-safebackfill-tmux.conf");
$newzpath = $config['NEWZNAB_DIR'];
  
require_once("$newzpath/www/config.php");
require_once("$newzpath/../../www/newznab/www/lib/postprocess.php");

$sleeptime = 60;

$db = new DB();

// Initial queries
// Books to process
$book_query = "SELECT COUNT(*) AS cnt from releases where bookinfoID IS NULL and categoryID = 7020;";
// Books in db
$book_query2 = "SELECT COUNT(*) AS cnt from releases where categoryID = 7020;";
// Console to process
$console_query = "SELECT COUNT(*) AS cnt from releases where consoleinfoID IS NULL and categoryID in ( select ID from category where parentID = 1000 );";
// Console in db
$console_query2 = "SELECT COUNT(*) AS cnt from releases where categoryID in ( select ID from category where parentID = 1000 );";
// Movie to process
$movie_query = "SELECT COUNT(*) AS cnt from releases where imdbID IS NULL and categoryID in ( select ID from category where parentID = 2000 );";
// Movie in db
$movie_query2 = "SELECT COUNT(*) AS cnt from releases where categoryID in ( select ID from category where parentID = 2000 );";
// Music to process
$music_query = "SELECT COUNT(*) AS cnt from releases where musicinfoID IS NULL and categoryID in ( select ID from category where parentID = 3000 );";
// Music in db
$music_query2 = "SELECT COUNT(*) AS cnt from releases where categoryID in ( select ID from category where parentID = 3000 );";
// PC to process
$pc_query = "SELECT COUNT(*) AS cnt from releases r left join category c on c.ID = r.categoryID where (categoryID in ( select ID from category where parentID = 4000)) and ((r.passwordstatus between -6 and -1) or (r.haspreview = -1 and c.disablepreview = 0));";
// PC in db
$pc_query2 = "SELECT COUNT(*) AS cnt from releases where categoryID in ( select ID from category where parentID = 4000 );";
// TV to process
$tvrage_query = "SELECT COUNT(*) AS cnt, ID from releases where rageID = -1 and categoryID in ( select ID from category where parentID = 5000 );";
// TV in db
$tvrage_query2 = "SELECT COUNT(*) AS cnt, ID from releases where categoryID in ( select ID from category where parentID = 5000 );";
// Total releases in db
$releases_query = "SELECT COUNT(*) AS cnt from releases;";
// Realeases to postprocess
$work_remaining_query = "SELECT COUNT(*) AS cnt from releases r left join category c on c.ID = r.categoryID where (r.passwordstatus between -6 and -1) or (r.haspreview = -1 and c.disablepreview = 0);";
// Nfos to process
$nfo_remaining_query = "SELECT COUNT(*) AS cnt FROM releases r WHERE r.releasenfoID = 0;";
// Nfos in db
$nfo_query = "SELECT count(*) AS cnt FROM releases WHERE releasenfoID not in (0, -1);";

// Parts row count
$parts_query = "SELECT table_rows AS cnt FROM information_schema.TABLES where table_name = 'parts';";
// Parts table size
$parts_size = "SELECT concat(round((data_length+index_length)/(1024*1024*1024),2),'GB') AS cnt FROM information_schema.tables where table_name = 'parts';";
 
// Initial counts
$releases_start = $db->query($releases_query);
$releases_start = $releases_start[0]['cnt'];

$time = TIME();
$time2 = TIME();
$time3 = TIME();
$time4 = TIME();

$i = 1;
while ($i > 0)
{
	$secs = TIME() - $time;
	$mins = floor($secs / 60);
	$hrs = floor($mins / 60);
	$days = floor($hrs / 24);
	$sec = floor($secs % 60);
	$min = ($mins % 60);
	$day = ($days % 24);
	$hr = ($hrs % 24);
  
    // Loop counts
    $releases_loop = $db->query($releases_query);
    $releases_loop = $releases_loop[0]['cnt'];

    if ($i!=1) {
        sleep($sleeptime);
    }
  
    $short_sleep = $sleeptime;

	// Get totals inside loop
	$nfo_remaining_now = $db->query($nfo_remaining_query);
	$nfo_remaining_now = $nfo_remaining_now[0]['cnt'];
	$nfo_now = $db->query($nfo_query);
	$nfo_now = $nfo_now[0]['cnt'];
	$book_releases_proc = $db->query($book_query);
	$book_releases_proc = $book_releases_proc[0]['cnt'];
	$book_releases_now = $db->query($book_query2);
	$book_releases_now = $book_releases_now[0]['cnt'];
	$console_releases_proc = $db->query($console_query);
	$console_releases_proc = $console_releases_proc[0]['cnt'];
	$console_releases_now = $db->query($console_query2);
	$console_releases_now = $console_releases_now[0]['cnt'];
	$movie_releases_proc = $db->query($movie_query);
	$movie_releases_proc = $movie_releases_proc[0]['cnt'];
	$movie_releases_now = $db->query($movie_query2);
	$movie_releases_now = $movie_releases_now[0]['cnt'];
	$music_releases_proc = $db->query($music_query);
	$music_releases_proc = $music_releases_proc[0]['cnt'];
	$music_releases_now = $db->query($music_query2);
	$music_releases_now = $music_releases_now[0]['cnt'];
	$pc_releases_proc = $db->query($pc_query);
	$pc_releases_proc = $pc_releases_proc[0]['cnt'];
	$pc_releases_now = $db->query($pc_query2);
	$pc_releases_now = $pc_releases_now[0]['cnt'];
	$tvrage_releases_proc = $db->query($tvrage_query);
	$tvrage_releases_proc = $tvrage_releases_proc[0]['cnt'];
	$tvrage_releases_now = $db->query($tvrage_query2);
	$tvrage_releases_now = $tvrage_releases_now[0]['cnt'];
	$releases_now = $db->query($releases_query);
	$releases_now = $releases_now[0]['cnt'];
	$work_remaining_now = $db->query($work_remaining_query);
	$work_remaining_now = $work_remaining_now[0]['cnt'];
	$releases_since_start = $releases_now - $releases_start;
	$releases_since_loop = $releases_now - $releases_loop;
	$additional_releases_now = $releases_now - $book_releases_now - $console_releases_now - $movie_releases_now - $music_releases_now - $pc_releases_now - $tvrage_releases_now;
	$total_work_now = $work_remaining_now + $tvrage_releases_proc + $music_releases_proc + $movie_releases_proc + $console_releases_proc + $book_releases_proc;

	$parts_rows = $db->query($parts_query);
	$parts_rows = $parts_rows[0]['cnt'];
	$parts_size_gb = $db->query($parts_size);
	$parts_size_gb = $parts_size_gb[0]['cnt'];
   
	if ( $releases_since_start > 0 ) { $signed = "+"; }
	else { $signed = ""; }

	if ( $min != 1 ) { $string_min = "mins"; }
	else { $string_min = "min"; }

	if ( $hr != 1 ) { $string_hr = "hrs"; }
	else { $string_hr = "hr"; }

	if ( $day != 1 ) { $string_day = "days"; }
	else { $string_day = "day"; }

	if ( $day > 0 ) { $time_string = "\033[38;5;160m$day\033[0m $string_day, \033[38;5;208m$hr\033[0m $string_hr, \033[1;31m$min\033[0m $string_min."; }
	elseif ( $hr > 0 ) { $time_string = "\033[38;5;208m$hr\033[0m $string_hr, \033[1;31m$min\033[0m $string_min."; }
	else { $time_string = "\033[1;31m$min\033[0m $string_min."; }  
   
	passthru('clear');
	printf("\033[1;31mMonitor\033[0m has been running for: $time_string\n");
	printf("\033[1;31m$releases_since_loop\033[0m releases added in the previous \033[1;31m$sleeptime\033[0m secs.\n");
	printf("\033[1;31m$releases_now($signed$releases_since_start)\033[0m releases in your database.\n");
	printf("\033[1;31m$total_work_now\033[0m releases left to postprocess."); 
	printf("\n\n\033[1;33m");  

	$mask = "%16s %10s %10s \n";
	printf($mask, "Category", "In Process", "In Database");
	printf($mask, "===============", "==========", "==========\033[0m"); 
	printf($mask, "NFO's","$nfo_remaining_now","$nfo_now");
	printf($mask, "Books(7020)","$book_releases_proc","$book_releases_now");
	printf($mask, "Console(1000)","$console_releases_proc","$console_releases_now");
	printf($mask, "Movie(2000)","$movie_releases_proc","$movie_releases_now");
	printf($mask, "Audio(3000)","$music_releases_proc","$music_releases_now");
	printf($mask, "PC(4000)","$pc_releases_proc","$pc_releases_now");
	printf($mask, "TVShows(5000)","$tvrage_releases_proc","$tvrage_releases_now");
	printf($mask, "Additional Proc","$work_remaining_now","$additional_releases_now");
	$parts_rows = number_format("$parts_rows");
	printf("\n \033[0mThe parts table has \033[1;31m$parts_rows\033[0m rows and is \033[1;31m$parts_size_gb\n");

    $i++;
}

?>
