<?
if(empty($_POST['importance']) || empty($_POST['artists']) || empty($_POST['groupid']) || !is_number($_POST['importance']) || !is_number($_POST['groupid'])) {
	error(0);
}
if(!check_perms('torrents_edit')) {
	error(403);
}
authorize();

$GroupID = $_POST['groupid'];
$Artists = explode(',',$_POST['artists']);
$CleanArtists = array();
$ArtistIDs = array();
$ArtistsString = '0';

foreach($Artists as $i => $Artist) {
	list($Importance, $ArtistID) = explode(';',$Artist);
	if(is_number($ArtistID) && is_number($Importance)) {
		$Cache->delete_value('artist_'.$ArtistID);
		$CleanArtists[] = array($Importance, $ArtistID);
		$ArtistsString .= ",$ArtistID";
		$ArtistIDs[] = $ArtistID;
	}
}

if(count($CleanArtists) > 0) {
	if($_POST['manager_action'] == 'delete') {
		$DB->query("SELECT Name FROM torrents_group WHERE ID = '".$_POST['groupid']."'");
		list($GroupName) = $DB->next_record();
		$DB->query("SELECT ArtistID, Name FROM artists_group WHERE ArtistID IN (".$ArtistsString.")");
		$ArtistNames = $DB->to_array('ArtistID');
		print_r($ArtistNames);
		foreach ($CleanArtists AS $Artist) {
			list($Importance,$ArtistID) = $Artist;
			write_log("Artist (".$ArtistTypes[$Importance].") ".$ArtistID." (".$ArtistNames[$ArtistID]['Name'].") was removed from the group ".$_POST['groupid']." (".$GroupName.") by user ".$LoggedUser['ID']." (".$LoggedUser['Username'].")");
			write_group_log($GroupID, 0, $LoggedUser['ID'], "Removed artist ".$ArtistNames[$ArtistID]['Name']." (".$ArtistTypes[$Importance].")", 0);
			$DB->query("DELETE FROM torrents_artists WHERE GroupID = '$GroupID' AND ArtistID = '$ArtistID' AND Importance = '$Importance'");
		}
		$DB->query("SELECT ArtistID
			FROM requests_artists
			WHERE ArtistID IN (".$ArtistsString.")
		UNION SELECT ArtistID
			FROM torrents_artists
			WHERE ArtistID IN (".$ArtistsString.")");
		$Items = $DB->collect('ArtistID');
		$EmptyArtists = array_diff($ArtistIDs, $Items);
		foreach($EmptyArtists as $ArtistID) {
			delete_artist($ArtistID);
		}
	} else {
		$DB->query("UPDATE IGNORE torrents_artists SET Importance = '".$_POST['importance']."' WHERE GroupID = '$GroupID' AND ArtistID IN (".$ArtistsString.")");
	}
	$Cache->delete_value('groups_artists_'.$GroupID);
	header("Location: torrents.php?id=".$GroupID);
}
?>
