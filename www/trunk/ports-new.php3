<?
$Debug=1;
require( "./_private/commonlogin.php3");
require( "./_private/getvalues.php3");
require( "./_private/freshports.php3");
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2//EN">
<html>

<head>
<meta name="description" content="freshports - new ports, applications">
<meta name="keywords" content="FreeBSD, index, applications, ports">  
<!--// DVL Software is a New Zealand company specializing in database applications. //-->
<title>freshports - recently added ports</title>
</head>

<body bgcolor="#ffffff" link="#0000cc">
 <? include("./_private/header.inc") ?>
<table width="100%" border="0">
<tr><td colspan="2">
This page shows the ports which have been recently added to the ports tree.  As such, some information
may be missing (such as description, maintainer, etc.). This information will be obtained during the
next database update.
</td></tr>
<tr><td valign="top" width="100%">
<table width="100%" border="0">
<tr>
    <td bgcolor="#AD0040" height="30"><font color="#FFFFFF" size="+1">freshports - recently added ports</font></td>
  </tr>
<tr><td>
<?

$DESC_URL = "ftp://ftp.freebsd.org/pub/FreeBSD/branches/-current/ports";

// make sure the value for $sort is valid

switch ($sort) {
/* sorting by port is disabled. Doesn't make sense to do this
   case "port":
      $sort = "port, updated desc";
      $cache_file .= ".port";
      break;
*/
//   case "updated":
//      $sort = "updated desc, port";
//      break;

   default:
      $sort ="date_created desc, port";
      $cache_file .= ".updated";
}

srand((double)microtime()*1000000);
$cache_time_rnd =       300 - rand(0, 600);


$UpdateCache = 0;
if (!file_exists($cache_file)) {
//   echo 'cache does not exist<br>';
   // cache does not exist, we create it
   $UpdateCache = 1;
} else {
//   echo 'cache exists<br>';
   if (!file_exists($LastUpdateFile)) {
      // no updates, so cache is fine.
//      echo 'but no update file<br>';
   } else {
//      echo 'cache file was ';
      // is the cache older than the db?
      if ((filectime($cache_file) + $cache_time_rnd) < filectime($LastUpdateFile)) {
//         echo 'created before the last database update<br>';
         $UpdateCache = 1;
      } else {
//         echo 'crated after the last database update<br>';
      }
   }
}

$UpdateCache = 1;

if ($UpdateCache == 1) {
//   echo 'time to update the cache';

$sql = "select ports.id, ports.name as port, " .
       "categories.name as category, categories.id as category_id, ports.version as version, ".
       "ports.committer, ports.last_update_description as update_description, " .
       "ports.maintainer, ports.short_description, UNIX_TIMESTAMP(ports.date_created) as date_created, ".
       "date_format(date_created, '$FormatDate $FormatTime') as date_created_formatted, ".
       "ports.package_exists, ports.extract_suffix, ports.needs_refresh, ports.homepage, ports.status, " .
       "date_format(change_log.commit_date, '$FormatDate $FormatTime') as updated, change_log.committer, change_log.update_description, " . 
       "change_log_details.change_type, ports.last_change_log_detail_id " .
       "from ports, categories, change_log, change_log_details  ".
       "WHERE ports.system = 'FreeBSD' ".
       "  and ports.primary_category_id       = categories.id " .
       "  and ports.last_change_log_detail_id = change_log_details.id " .
       "  and change_log.id                   = change_log_details.change_log_id ";       "from ports, categories, newports ".
       "  and ports.status                    = 'A' ";

$sql .= "order by $sort limit $MaxNumberOfPorts";

if ($Debug) {
   echo $sql;
}

$result = mysql_query($sql, $db);

// get the list of topics, which we need to modify the order
$NumTopics=0;
while ($myrow = mysql_fetch_array($result)) {
   include("./_private/port-basics.inc");
}

//$HTML .= '</tr>';

//$HTML .= "</table>\n";

mysql_free_result($result);


echo $HTML;

   $fpwrite = fopen($cache_file, 'w');
   if(!$fpwrite) {
      echo 'error on open<br>';
      echo "$errstr ($errno)<br>\n";
      exit;
   } else {
//      echo 'written<br>';
      fputs($fpwrite, $HTML);
      fclose($fpwrite);
   }
} else {
//   echo 'looks like I\'ll read from cache this time';
   if (file_exists($cache_file)) {
      include($cache_file);
   }
}

</script>
</table>
</td>
  <td valign="top" width="*">
   <? include("./_private/side-bars.php3") ?>
 </td>
</tr>
</table>
<? include("./_private/footer.inc") ?>
</body>
</html>
