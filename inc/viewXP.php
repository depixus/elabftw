<?php
/********************************************************************************
*                                                                               *
*   Copyright 2012 Nicolas CARPi (nicolas.carpi@gmail.com)                      *
*   http://www.elabftw.net/                                                     *
*                                                                               *
********************************************************************************/

/********************************************************************************
*  This file is part of eLabFTW.                                                *
*                                                                               *
*    eLabFTW is free software: you can redistribute it and/or modify            *
*    it under the terms of the GNU Affero General Public License as             *
*    published by the Free Software Foundation, either version 3 of             *
*    the License, or (at your option) any later version.                        *
*                                                                               *
*    eLabFTW is distributed in the hope that it will be useful,                 *
*    but WITHOUT ANY WARRANTY; without even the implied                         *
*    warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR                    *
*    PURPOSE.  See the GNU Affero General Public License for more details.      *
*                                                                               *
*    You should have received a copy of the GNU Affero General Public           *
*    License along with eLabFTW.  If not, see <http://www.gnu.org/licenses/>.   *
*                                                                               *
********************************************************************************/
// inc/viewXP.php
// read only ?
$ro = false;
// ID
if (isset($_GET['id']) && !empty($_GET['id']) && is_pos_int($_GET['id'])) {
    $id = $_GET['id'];
} else {
    $message = "The id parameter in the URL isn't a valid experiment ID.";
    display_message('error', $message);
    require_once 'inc/footer.php';
    die();
}

// SQL for viewXP
$sql = "SELECT experiments.id AS expid,
    experiments.*,
    status.color,
    status.name
    FROM experiments
    LEFT JOIN status ON (experiments.status = status.id)
    WHERE experiments.id = :id";
$req = $pdo->prepare($sql);
$req->bindParam(':id', $id, PDO::PARAM_INT);
$req->execute();
// got results ?
$row_count = $req->rowCount();
if ($row_count === 0) {
    $message = 'Nothing to show with this ID.';
    display_message('error', $message);
    require_once 'inc/footer.php';
    die();
}

$data = $req->fetch();

// Check id is owned by connected user to present comment div if not
if ($data['userid'] != $_SESSION['userid']) {
    // Can the user see this experiment which is not his ?
    if ($data['visibility'] == 'user') {
        $message = "<strong>Access forbidden:</strong> the visibility setting of this experiment is set to 'owner only'.";
        display_message('error', $message);
        require_once 'inc/footer.php';
        exit();
    } else {
        // get who owns the experiment
        $sql = 'SELECT firstname, lastname FROM users WHERE userid = :userid';
        $get_owner = $pdo->prepare($sql);
        $get_owner->bindParam(':userid', $data['userid']);
        $get_owner->execute();
        $owner = $get_owner->fetch();
        $message = "<strong>Read-only mode:</strong> this experiment is owned by ".$owner['firstname']." ".$owner['lastname'].".";
        display_message('info', $message);
        // we set this variable for later, to check if we are in read only mode
        $ro = true;
    }
}



// Display experiment
?>
    <section class="item" style='padding:15px;border-left: 6px solid #<?php echo $data['color'];?>'>
    <span class='align_right' id='status'>(<?php echo $data['name'];?>)</span>
<?php
echo "<img src='img/calendar.png' title='date' alt='Date :' /><span class='date'> ".$data['date']."</span><br />
    <a href='experiments.php?mode=edit&id=".$data['expid']."'><img src='img/edit.png' title='edit' alt='edit' /></a> 
<a href='duplicate_item.php?id=".$data['expid']."&type=exp'><img src='img/duplicate.png' title='duplicate experiment' alt='duplicate' /></a> 
<a href='make_pdf.php?id=".$data['expid']."&type=experiments'><img src='img/pdf.png' title='make a pdf' alt='pdf' /></a> 
<a href='javascript:window.print()'><img src='img/print.png' title='Print this page' alt='Print' /></a> 
<a href='make_zip.php?id=".$data['expid']."&type=experiments'><img src='img/zip.png' title='make a zip archive' alt='zip' /></a> ";
// lock
if ($data['locked'] == 0) {
    echo "<a href='lock.php?id=".$data['expid']."&action=lock&type=experiments'><img src='img/unlock.png' title='lock experiment' alt='lock' /></a>";
} else { // experiment is locked
    echo "<a href='lock.php?id=".$data['expid']."&action=unlock&type=experiments'><img src='img/lock.png' title='unlock experiment' alt='unlock' /></a>";
    // show timestamp button if it's not timestamped already
    if ($data['timestamped'] == 0) {
        echo "<a onClick=\"return confirmStamp()\" href='timestamp.php?id=".$data['expid']."'><img src='img/stamp.png' title='timestamp experiment' alt='timestamp' /></a>";
    }
}

// TAGS
show_tags($id, 'experiments_tags');
// TITLE : click on it to go to edit mode only if we are not in read only mode
echo "<div ";
if ($ro === false) {
    echo "OnClick=\"document.location='experiments.php?mode=edit&id=".$data['expid']."'\"";
}
echo " class='title'>";
echo stripslashes($data['title'])."</div>";
// BODY (show only if not empty, click on it to edit
if ($data['body'] != '') {
    echo "<div ";
    // make the body clickable only if we are not in read only
    if ($ro === false) {
        echo "OnClick=\"document.location='experiments.php?mode=edit&id=".$data['expid']."'\"";
    }
    echo "class='txt'>".stripslashes($data['body'])."</div>";
    echo "<br>";
}

// DISPLAY FILES
require_once 'inc/display_file.php';

// DISPLAY LINKED ITEMS
$sql = "SELECT items.id AS itemid,
    experiments_links.id AS linkid,
    experiments_links.*,
    items.*,
    items_types.*
    FROM experiments_links
    LEFT JOIN items ON (experiments_links.link_id = items.id)
    LEFT JOIN items_types ON (items.type = items_types.id)
    WHERE experiments_links.item_id = :id";
$req = $pdo->prepare($sql);
$req->execute(array(
    'id' => $id
));
// Check there is at least one link to display
if ($req->rowcount() > 0) {
    echo "<hr class='flourishes'>";
    echo "<img src='img/link.png'> <h4 style='display:inline'>Linked items</h4>
<div id='links_div'><ul>";
    while ($link = $req->fetch()) {
        // SQL to get title
        echo "<li>[".$link['name']."] - <a href='database.php?mode=view&id=".$link['itemid']."'>".
            stripslashes($link['title'])."</a></li>";
    } // end while
    echo "</ul>";
} else { // end if link exist
    echo "<br />";
}

// SHOW INFO ON TIMESTAMP
if ($data['timestamped'] == 1) {
    // who what when ?
    $sql = 'SELECT firstname, lastname FROM users WHERE userid = :userid';
    $req_stamper = $pdo->prepare($sql);
    $req_stamper->bindParam(':userid', $data['timestampedby']);
    $req_stamper->execute();
    $timestamper = $req_stamper->fetch();

    // display timestamped pdf download link
    $sql = "SELECT * FROM uploads WHERE type = 'exp-pdf-timestamp' AND item_id = :item_id LIMIT 1";
    $req_stamper = $pdo->prepare($sql);
    $req_stamper->bindParam(':item_id', $id);
    $req_stamper->execute();
    $uploads = $req_stamper->fetch();

    display_message('info', "Experiment was timestamped by ".$timestamper['firstname']." ".$timestamper['lastname']." on ".$data['timestampedwhen']."
        <a href='uploads/".$uploads['long_name']."'><img src='img/pdf.png' title='Download timestamped pdf' alt='pdf' /></a>");
    unset($timestamper);
    unset($uploads);

}

// DISPLAY eLabID
echo "<p class='elabid'>Unique eLabID : ".$data['elabid'];
// DISPLAY visibility
echo "<br />Visibility : ".$data['visibility']."</p>";
echo "</section>";

// COMMENT BOX
?>
<!-- we need to add a container here so the reload function in the callback of .editable() doesn't mess things up -->
<section id='expcomment_container'>
<div id='expcomment' class='item'>
    <h3>Comments</h3>
    <p class='editable newexpcomment' id='newexpcomment_<?php echo $id;?>'>Click to add a comment.</p>
<?php

// check if there is something to display first
// get all comments, and infos on the commenter associated with this experiment
$sql = "SELECT * FROM experiments_comments LEFT JOIN users ON (experiments_comments.userid = users.userid) WHERE exp_id = :id ORDER BY experiments_comments.datetime DESC";
$req = $pdo->prepare($sql);
$req->execute(array(
    'id' => $id
));
if ($req->rowCount() > 0) {
    // there is comments to display
    while ($comments = $req->fetch()) {
        if (empty($comments['firstname'])) {
            $comments['firstname'] = '[deleted]';
        }
    echo "<div class='expcomment_box'>
    <img class='align_right' src='img/trash.png' title='delete' alt='delete' onClick=\"deleteThisAndReload(".$comments['id'].",'expcomment')\" />";
     echo "<span class='smallgray'>On ".$comments['datetime']." ".$comments['firstname']." ".$comments['lastname']." wrote :</span><br />";
        echo "<p class='editable' id='expcomment_".$comments['id']."'>".$comments['comment']."</p></div>";
    }
}
?>
</div>
</section>

<script>
// DELETE EXP COMMENT
function deleteThisAndReload(id, type) {
    var you_sure = confirm('Delete this ?');
    if (you_sure == true) {
        $.post('delete.php', {
            id:id,
            type:type
        })
        // on success we reload the block
        .success(function() {
             $('#expcomment_container').load("experiments.php?mode=view&id=<?php echo $id;?> #expcomment");
        });
    } else {
        return false;
    }
}

function makeEditable() {
    // Experiment comment is editable
    $('div#expcomment').on("mouseover", ".editable", function(){
        $('div#expcomment p.editable').editable('editinplace.php', {
            tooltip : 'Click to edit',
            indicator : 'Saving...',
            id   : 'id',
            name : 'expcomment',
            submit : 'Save',
            cancel : 'Cancel',
            style : 'display:inline',
            callback : function() {
                // now we reload the comments part to show the comment we just submitted
                $('#expcomment_container').load("experiments.php?mode=view&id=<?php echo $id;?> #expcomment");
                // we reload the function so editable zones are editable again
                makeEditable();
            }
        })
    });
}


// READY ? GO !!

function confirmStamp() {
    var you_sure = confirm('Once timestamped, an experiment cannot be edited anymore ! Are you sure you want to do this ?');
    if (you_sure === true) {
        return true;
    } else {

        return false;
    }
}
$(document).ready(function() {
    // change title
    // fix for the ' and "
    title = "<?php echo $data['title']; ?>".replace(/\&#39;/g, "'").replace(/\&#34;/g, "\"");
    document.title = title;
    // Keyboard shortcuts
    key('<?php echo $_SESSION['prefs']['shortcuts']['create'];?>', function(){location.href = 'create_item.php?type=exp'});
    key('<?php echo $_SESSION['prefs']['shortcuts']['edit'];?>', function(){location.href = 'experiments.php?mode=edit&id=<?php echo $id;?>'});
    // make editable
    setInterval(makeEditable, 50);
});
</script>

