<?php
	#
	# $Id: date.php,v 1.3 2006-12-30 21:16:12 dan Exp $
	#
	# Copyright (c) 1998-2006 DVL Software Limited
	#

	require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/common.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/freshports.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/databaselogin.php');

	require_once($_SERVER['DOCUMENT_ROOT'] . '/../include/getvalues.php');
	require_once($_SERVER['DOCUMENT_ROOT'] . '/../classes/commits.php');

	# NOTE: All dates must be of the form: YYYY/MM/DD
	# this format can be achieved using the date('Y/m/d') function.

	#
	# Get the date we are going to work with.
	#
	if (IsSet($_GET['date'])) {
		$Date = pg_escape_string($_GET['date']);
	} else {
		$Date = '';
	}

	$DateMessage = '';

	if ($Date == '' || strtotime($Date) == -1) {
		$DateMessage = 'date assumed';
		$Date = date('Y/m/d');
	}
	list($year, $month, $day) = explode('/', $Date);
	if (!CheckDate($month, $day, $year)) {
		$DateMessage = 'date adjusted to something realistic';
		$Date = date('Y/m/d');
	} else {
		$Date = date('Y/m/d', strtotime($Date));
	}

	if (IsSet($_REQUEST['branch'])) {
		$BranchName = htmlspecialchars($_REQUEST['branch']);
	} else {
		$BranchName = BRANCH_HEAD;
	}

	$commits = new Commits($db, $BranchName);
	$last_modified = $commits->LastModified($Date);
	$NumCommits    = $commits->Count($Date);

	freshports_ConditionalGet($last_modified);

	freshports_Start($FreshPortsSlogan,
					$FreshPortsName . ' - new ports, applications',
					'FreeBSD, index, applications, ports');
	$Debug = 0;

	function ArchiveFileName($Date) {
		$File = ARCHIVE_DIRECTORY . '/' . $Date . '.daily';
		
		return $File;
	}

	function ArchiveDirectoryCreate($Date) {
		$SubDir      = date('Y/m', strtotime($Date));
		$DirToCreate = ARCHIVE_DIRECTORY . '/' . $SubDir;
		if (!file_exists($DirToCreate)) {
			$old = umask(0);
			mkdir($DirToCreate, 0770, true);
			umask($old);
		}

		return $DirToCreate;
	}

	function ArchiveExists($Date) {
		# returns file name for archive if it exists
		# empty string otherwise

		$File = ArchiveFileName($Date);
		if (!file_exists($File)) {
			$File = '';
		}

		return $File;
	}

	function ArchiveSave($Date, $HTML) {
		# saves the archive away...
		
		ArchiveDirectoryCreate($Date);
		$File = ArchiveFileName($Date);

		$myfile = fopen($File, 'w');
		fwrite($myfile, $HTML);
		fclose($myfile);
		$old = umask(0);
		chmod($File, 0664);
		umask($old);
		
	}

	function ArchiveGet($Date) {
		# saves the archive away...
		
		$File = ArchiveFileName($Date);
		
		$myfile = fopen($File, 'r');
		$HTML = fread($myfile, filesize($File));
		fclose($myfile);

		return $HTML;
	}

	function ArchiveCreate($Date, $DateMessage, $db, $Use, $BranchName) {
		GLOBAL $freshports_CommitMsgMaxNumOfLinesToShow;

		$commits = new Commits($db);
		$commits->SetBranch($BranchName);
		$NumRows = $commits->Fetch($Date, isset($User) ? $User->id : null);
	
		#echo '<br>NumRows = ' . $NumRows;

		$HTML = '';

		if ($NumRows == 0) {
			$HTML .= '<TR><TD COLSPAN="3" BGCOLOR="' . BACKGROUND_COLOUR . '" HEIGHT="0">' . "\n";
			$HTML .= '   <FONT COLOR="#FFFFFF"><BIG>' . FormatTime($Date, 0, "D, j M Y") . '</BIG></FONT>' . "\n";
			$HTML .= '</TD></TR>' . "\n\n";
			$HTML .= '<TR><TD>No commits found for that date</TD></TR>';
		}
		
		unset($ThisCommitLogID);

		require_once($_SERVER['DOCUMENT_ROOT'] . '/../classes/display_commit.php');

		$DisplayCommit = new DisplayCommit($db, $commits->LocalResult);
		$DisplayCommit->SanityTestFailure = true;
		$RetVal = $DisplayCommit->CreateHTML();

		$HTML = $DisplayCommit->HTML;

		return $HTML;
	}

?>

<?php

define('RELATIVE_DATE_24HOURS', 24 * 60 * 60);	# seconds in a day

$Today = '<a href="/commits.php">Latest commits</a>';

# use DateTime because it gets the math correct, even with daylight savings changes
# see https://github.com/FreshPorts/freshports/issues/18
# this will be yesterday
$dateBefore = new DateTime($Date);
$dateBefore->add(new DateInterval('P1D'));

# this will be tomorrow
$dateAfter = new DateTime($Date);
$dateAfter->sub(new DateInterval('P1D'));

$Yesterday = freshports_LinkToDate(strtotime($dateBefore->format('Y-m-d')));
$Tomorrow  = freshports_LinkToDate(strtotime($dateAfter->format('Y-m-d')));

# This *seems* to cater for looking at yesterday's commits.
# I think maybe we should not try to be so clever.
if (strtotime($Date) + RELATIVE_DATE_24HOURS == strtotime(date('Y/m/d'))) {
	$DateLinks = '&lt; ' . $Today . ' | ' . $Yesterday . ' &gt;';
} else {
	$DateLinks = '&lt; ' . $Today . ' | ' . $Tomorrow . ' | ' . $Yesterday . ' &gt;';
}
echo $DateLinks;
if ($NumCommits > 0) {
  echo " | Number of commits: " . $NumCommits;
}

?>

<?php echo freshports_MainTable(); ?>

<TR><TD VALIGN="top" WIDTH="100%">
<?php

echo freshports_MainContentTable();

if (ArchiveExists($Date)) {
  $HTML = ArchiveGet($Date);
} else {
  $HTML = ArchiveCreate($Date, $DateMessage, $db, $User, $BranchName);
  ArchiveSave($Date, $HTML);
}

echo $HTML;

echo '</table>';

echo $DateLinks;
if ($NumCommits > 0) {
  echo " | Number of commits: " . $NumCommits;
}

?>

</td>

  <TD VALIGN="top" WIDTH="*" ALIGN="center">

	<?
	echo freshports_SideBar();
	?>

  </td>	
</tr>
</table>

<?
echo freshports_ShowFooter();
?>

</body>
</html>