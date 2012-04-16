<?php
define('FORUM_ROOT', '../../');
require FORUM_ROOT . 'include/common.php';

$isajax = (isset($_GET['ajax'])) ? 1 : 0;

function repu_message($msg)
{
    global $isajax;
    if ($isajax) {
        die($msg);
    } else {
        message($msg);
    }
}

function repu_get_post_details($pid)
{
    global $forum_db;
    $result = $forum_db->query("SELECT * FROM `{$forum_db->prefix}posts` WHERE `id`='" . $forum_db->escape($pid) . "'");
    if ($forum_db->num_rows($result)) {
        return $forum_db->fetch_assoc($result);
    } else {
        return false;
    }
}

if ($forum_user['is_guest'])
    message($lang_common['No permission']);


$cur_path = dirname(__FILE__);
$cur_url = $base_url . '/' . basename(dirname(dirname(__FILE__))) . '/' . basename(dirname(__FILE__));

//load language file
if (file_exists($cur_path . '/lang/' . $forum_user['language'] . '.php'))
    require $cur_path . '/lang/' . $forum_user['language'] . '.php';
else
    require $cur_path . '/lang/English.php';

$action = isset($_GET['action']) ? $_GET['action'] : null;


//trash repu
if ($action == "trash") {

    //check values
    $rid = isset($_GET['rid']) ? intval($_GET['rid']) : 0;

    if ($rid < 1) message($lang_common['Bad request']);

    //check permission whether user can delete repu or not
    if (!$forum_user['is_admmod']) message($lang_common['Bad request']);

    //get user_id;
    $query = $forum_db->query("SELECT user_id FROM {$forum_db->prefix}reputation WHERE id='{$forum_db->escape($rid)}' LIMIT 1") or error($forum_db->error(), __FILE__, __LINE__);
    if ($forum_db->num_rows($query) != 1) message($lang_common['Bad request']);
    $user = $forum_db->fetch_assoc($query);
    $uid = $user['user_id'];

    $reason = urldecode($_GET['reason']);
    //now trash the reputation from reputation table;
    $forum_db->query("UPDATE  {$forum_db->prefix}reputation SET `trashed`='1', `trashed_reason`='" . $forum_db->escape($reason) . "', `mod_name`='" . $forum_db->escape($forum_user['username']) . "' WHERE id='{$forum_db->escape($rid)}'") or error($forum_db->error(), __FILE__, __LINE__);

    //adjust the total reputation
    $forum_db->query("UPDATE {$forum_db->prefix}users as u SET u.reputation=(select sum(`action`) FROM {$forum_db->prefix}reputation as r WHERE r.user_id = $uid AND r.trashed='0') where u.id=$uid") or error($forum_db->error(), __FILE__, __LINE__);

    redirect($forum_user['prev_url'], sprintf($lang_reputation['Modified reputation'], forum_htmlencode($forum_config['o_reputation_word'])));

}

//untrash repu
if ($action == "untrash") {

    //check values
    $rid = isset($_GET['rid']) ? intval($_GET['rid']) : 0;

    if ($rid < 1) message($lang_common['Bad request']);

    //check permission whether user can delete repu or not
    if (!$forum_user['is_admmod']) message($lang_common['Bad request']);

    //get user_id;
    $query = $forum_db->query("SELECT user_id FROM {$forum_db->prefix}reputation WHERE id='{$forum_db->escape($rid)}' LIMIT 1") or error($forum_db->error(), __FILE__, __LINE__);
    if ($forum_db->num_rows($query) != 1) message($lang_common['Bad request']);
    $user = $forum_db->fetch_assoc($query);
    $uid = $user['user_id'];


    //now undelete the reputation from reputation table; undelete means just update the deleted field to 0
    $forum_db->query("UPDATE  {$forum_db->prefix}reputation SET `trashed`='0', `trashed_reason`='', `mod_name`='" . $forum_db->escape($forum_user['username']) . "' WHERE id='{$forum_db->escape($rid)}'") or error($forum_db->error(), __FILE__, __LINE__);

    //adjust the total reputation
    $forum_db->query("UPDATE {$forum_db->prefix}users as u SET u.reputation=(select sum(`action`) FROM {$forum_db->prefix}reputation as r WHERE r.user_id = $uid AND r.trashed='0') where u.id=$uid") or error($forum_db->error(), __FILE__, __LINE__);

    redirect($forum_user['prev_url'], sprintf($lang_reputation['Modified reputation'], forum_htmlencode($forum_config['o_reputation_word'])));

}

//Delete repu
if ($action == "delete") {

    //check values
    $rid = isset($_GET['rid']) ? intval($_GET['rid']) : 0;

    if ($rid < 1) message($lang_common['Bad request']);

    //check permission whether user can delete repu or not
    if (!$forum_user['is_admmod']) message($lang_common['Bad request']);

    //get user_id;
    $query = $forum_db->query("SELECT user_id FROM {$forum_db->prefix}reputation WHERE id='{$forum_db->escape($rid)}' LIMIT 1") or error($forum_db->error(), __FILE__, __LINE__);
    if ($forum_db->num_rows($query) != 1) message($lang_common['Bad request']);
    $user = $forum_db->fetch_assoc($query);
    $uid = $user['user_id'];


    //now delete the reputation from reputation table; undelete means just update the deleted field to 0
    $forum_db->query("DELETE FROM  `{$forum_db->prefix}reputation` WHERE id='{$forum_db->escape($rid)}'") or error($forum_db->error(), __FILE__, __LINE__);

    //adjust the total reputation
    $forum_db->query("UPDATE {$forum_db->prefix}users as u SET u.reputation=(select sum(`action`) FROM {$forum_db->prefix}reputation as r WHERE r.user_id = $uid AND r.trashed='0') where u.id=$uid") or error($forum_db->error(), __FILE__, __LINE__);

    redirect($forum_user['prev_url'], sprintf($lang_reputation['Modified reputation'], forum_htmlencode($forum_config['o_reputation_word'])));

}

// plus/minus reputation
if ($action == 'plus' OR $action == 'minus') {

    $uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;


    if ($uid < 2)
        repu_message($lang_common['Bad request']);

    //check if this user can use reputation feature
    $repu_banned_user = @$forum_config['o_reputation_exclude_user'];
    if (!empty($repu_banned_user)) $repu_banned_user = explode(',', $repu_banned_user);
    if (count($repu_banned_user) > 0) {
        if (in_array($forum_user['id'], @$repu_banned_user)) {
            repu_message($lang_common['Bad request']);
        }
    }


    //csrf_confirm_form();
    $reputation = ($action == 'minus') ? -1 : 1;

    if (file_exists($cur_path . '/lang/' . $forum_user['language'] . '.php'))
        require $cur_path . '/lang/' . $forum_user['language'] . '.php';
    else
        require $cur_path . '/lang/English.php';


    if ($forum_config['o_reputation_enable'] == '0' || $forum_user['num_posts'] < 50 || ($forum_config['o_reputation_admods_only'] == '1' && ($forum_user['g_moderator'] != '1' && $forum_user['g_id'] != FORUM_ADMIN)))
        repu_message($lang_common['Bad request']);

    if ($forum_config['o_reputation_guests'] == '0' && $forum_user['is_guest'])
        repu_message($lang_common['Bad request']);

    $ext_reputation_reason = isset($_GET['reason']) ? $_GET['reason'] : '';

    $ext_reputation_reason = urldecode($ext_reputation_reason);


    if (empty($ext_reputation_reason) or strlen($ext_reputation_reason) < 10)
        repu_message($lang_reputation['Reason too short']);

    $ext_reputation_pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;

    if (empty($ext_reputation_pid))
        repu_message($lang_common['Bad request']);

    $ext_reputation_uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;

    if ($ext_reputation_uid < 2)
        repu_message($lang_common['Bad request']);

    if ($ext_reputation_uid == intval($forum_user['id']))
        repu_message($lang_common['Bad request']);

    //get post details
    $post_detail = repu_get_post_details($ext_reputation_pid);

    //check if the topic is open or not and if it is enabled to give repu on closed topic
    $repu_on_closed_topic = @$forum_config['o_reputation_on_closed_topic'];
    if (!$repu_on_closed_topic) {
        //check if the topic is closed or not. if closed, disallow reputation
        $query_result = $forum_db->query("SELECT `id` FROM `{$forum_db->prefix}topics` WHERE `id`='" . $forum_db->escape($post_detail['topic_id']) . "' AND `closed`='1'");
        if ($forum_db->num_rows($query_result)) {
            //topic is closed, so show that we can't allow reputation here
            repu_message($lang_reputation['Closed topic']);
        }
    }

    //check if already given reputation in this post
    $query_result = $forum_db->query("SELECT id FROM `{$forum_db->prefix}reputation` WHERE post_id='" . $forum_db->escape($ext_reputation_pid) . "' AND `changer_id`='{$forum_user['id']}'") or error(__FILE__, __LINE__);
    if ($forum_db->num_rows($query_result))
        repu_message($lang_reputation['Reputation already given']);

    //check if reputation is given to this same user recently
    $hourlimit = $forum_config['o_reputation_time_limit'];
    $hourlimit_time = strtotime("-{$hourlimit}hours");

    $query_result = $forum_db->query("SELECT * FROM `{$forum_db->prefix}reputation` WHERE `user_id`='{$ext_reputation_uid}' AND `changer_id`='{$forum_user['id']}' AND `time` > '$hourlimit_time'") or error(__FILE__, __LINE__);
    if ($forum_db->num_rows($query_result))
        repu_message($lang_reputation['Time limit info']);


    $ext_reputation_query = array(
        'INSERT' => 'reason, post_id, user_id, changer_id, time, action',
        'INTO' => 'reputation',
        'VALUES' => '"' . $forum_db->escape($ext_reputation_reason) . '", "' . $ext_reputation_pid . '", "' . $ext_reputation_uid . '", "' . $forum_user['id'] . '", "' . time() . '", "' . $reputation . '"'
    );


    ($hook = get_hook('mi_ext_ek_qr_user_add_reputation')) ? eval($hook) : null;
    $forum_db->query_build($ext_reputation_query) or error(__FILE__, __LINE__);

    $ext_reputation_query = array(
        'UPDATE' => 'users',
        'SET' => 'reputation = reputation + ' . $reputation,
        'WHERE' => 'id=' . $ext_reputation_uid
    );

    ($hook = get_hook('mi_ext_ek_qr_user_add_reputation_count')) ? eval($hook) : null;
    $forum_db->query_build($ext_reputation_query) or error(__FILE__, __LINE__);

    if ($isajax) {
        echo $lang_reputation['Modified reputation'];
        exit;
    } else {
        redirect($forum_user['prev_url'], sprintf($lang_reputation['Modified reputation'], forum_htmlencode($forum_config['o_reputation_word'])));
    }
}


if ($action == 'show') {


    if (!defined('FORUM_PAGE'))
        @define('FORUM_PAGE', 'reputation');

    $uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;


    if ($uid < 2)
        message($lang_common['Bad request']);

    //get username of the uid
    $query = $forum_db->query_build(array(
                                         'SELECT' => 'username',
                                         'FROM' => 'users',
                                         'WHERE' => "id='" . $forum_db->escape($uid) . "'"
                                    ));
    $result = $forum_db->fetch_assoc($query);


    // Setup breadcrumbs

    $forum_page['crumbs'] = array(
        array($forum_config['o_board_title'], forum_link($forum_url['index'])),
        array(sprintf($lang_reputation['Breadcrumb'], forum_htmlencode($forum_config['o_reputation_word']), forum_htmlencode($result['username'])), forum_link($forum_url['profile_about'], $uid)),
    );

    ob_start();
    require FORUM_ROOT . 'header.php';


    if (!defined('FORUM_PARSER_LOADED'))
        require FORUM_ROOT . 'include/parser.php';

    //prepare the summary.
    $query = $forum_db->query("SELECT count(action) as positive, (SELECT count(id)
					 FROM `{$forum_db->prefix}reputation`
					 WHERE user_id = '" . $forum_db->escape($uid) . "'
					 AND action = -1 AND `trashed`='0') AS negative,
					 (SELECT count(`id`) FROM  `{$forum_db->prefix}reputation`
					 WHERE user_id = '" . $forum_db->escape($uid) . "'
					 AND `trashed` = '1') AS trashed
FROM `{$forum_db->prefix}reputation`
WHERE `action`='1' AND `trashed`='0' AND user_id = '" . $forum_db->escape($uid) . "'");
    $summary = $forum_db->fetch_assoc($query);
    ?>

<div class="main-subhead">
    <h2 class="hn"><span><?php echo forum_htmlencode($forum_config['o_reputation_word']) . ' [+' . forum_number_format($summary['positive']) . ' / -' . forum_number_format($summary['negative']) . ']';
        if ($forum_user['is_admmod']) {
            echo ' Reputation Trashed: ' . forum_number_format($summary['trashed']);
        }
        ?></span></h2>
</div>
<style>
    #reputation td {
        overflow: hidden;

    }

    .reason, .subject {
        width: 30%;
    }

    .deleted {
        background-color: grey;
    }

</style>
	<div id="reputation" class="main-content main-frm">
        <script type="text/javascript">
            function trashreason(rep_id) {
                var baseurl = '<?php echo $cur_url;?>/reputation.php?action=trash&rid=';
                var reason = prompt('Enter the reason of trashing this reputation');
                if (!reason) {
                    return false;
                }

                window.location = baseurl + rep_id + '&reason=' + encodeURIComponent(reason);

            }
            function delrepu(rep_id) {
                var baseurl = '<?php echo $cur_url;?>/reputation.php?action=delete&rid=';
                if (confirm('Are you sure you want to delete this reputation? Please note: this can\'t be undone!')) {
                    window.location = baseurl + rep_id;
                }
            }


        </script>
		<table>
			<thead>
            <tr>
                <td><b><?php echo $lang_reputation['From user']; ?></b></td>
                <td class="subject"><b><?php echo $lang_reputation['Topic']; ?></b></td>
                <td class="reason"><b><?php echo $lang_reputation['Reason']; ?></b></td>
                <td><b><?php echo $lang_reputation['Type']; ?></b></td>
                <td><b><?php echo $lang_reputation['Date']; ?></b></td>
                <?php if ($forum_user['is_admmod']) echo '<td>Action</td>'; ?>
            </tr>
            </thead>
    <?php

    if ($forum_user['id']==$uid  OR $forum_user['is_admmod']){
        $query = "SELECT k.id, k.time, k.reason, k.post_id, k.changer_id, k.action, k.user_id, k.`trashed`, k.`trashed_reason`,k.`mod_name`, t.subject, u.username FROM {$forum_db->prefix}reputation AS k LEFT JOIN {$forum_db->prefix}users AS u ON u.id=k.changer_id left join {$forum_db->prefix}posts as p on p.id=k.post_id LEFT JOIN {$forum_db->prefix}topics AS t ON t.id=p.topic_id WHERE k.user_id='{$forum_db->escape($uid)}' ORDER BY k.id DESC limit 5000";
    } else {
        $query = "SELECT k.id, k.time, k.reason, k.post_id, k.changer_id, k.action, k.user_id, k.`trashed`, k.`trashed_reason`, t.subject, u.username FROM {$forum_db->prefix}reputation AS k LEFT JOIN {$forum_db->prefix}users AS u ON u.id=k.changer_id left join {$forum_db->prefix}posts as p on p.id=k.post_id LEFT JOIN {$forum_db->prefix}topics AS t ON t.id=p.topic_id WHERE k.user_id='{$forum_db->escape($uid)}' AND k.trashed != '1' ORDER BY k.id DESC limit 5000";
    }

    $result = $forum_db->query($query) or error($forum_db->error(), __FILE__, __LINE__);
    while (false != ($row = $forum_db->fetch_assoc($result)))
    {
        ?>
            <tbody><tr <?php if ($row['trashed']) echo 'class="deleted"';?>>
				<?php
                echo '<td><a href="' . $cur_url . '/reputation.php?action=show&uid=' . $row['changer_id'] . '">' . $row['username'] . '</a> ';
        if ($forum_user['is_admmod']) {
            echo '<a href="' . $cur_url . '/reputation.php?action=log&uid=' . $row['changer_id'] . '"><img src="' . $cur_url . '/log.gif" alt="Log" title="Reputation Log" /></a>';
        }
        echo '</td>';
        if (is_null($row['subject'])) {
            echo '<td>Action</td>';
        } else {
            echo '<td class="subject"><a href="' . $base_url . '/viewtopic.php?pid=' . $row['post_id'] . '#p' . $row['post_id'] . '">' . $row['subject'] . '</a></td>';
        }
        echo '<td>' . parse_message($row['reason'], 0) . '</td>';
        $type = ($row['action'] == '1') ? $cur_url . '/plus.gif' : $cur_url . '/minus.gif';
        echo '<td><img src="' . $type . '" /></td>';
        echo '<td>' . forum_number_format(date('d-m-Y h:i A', $row['time'])) . '</td>';


        if ($forum_user['is_admmod']) {
            echo '<td>';
            if ($row['trashed']) {
                //echo '<td title="Trash Reason: '.@$row['delete_reason'].' by '.@$row['mod_name'].'">Deleted<br/>';
                echo '<a  href="' . $cur_url . '/reputation.php?action=untrash&rid=' . $row['id'] . '" alt="Undelete" title="Untrash this reputation"><img src="untrash.png" /></a>';
            } else {
                echo '<a onclick="trashreason(\'' . $row['id'] . '\');" title="Trash"><img alt="Trash" src="trash.png" width="24" height="24" /></a>';
            }
            echo '<br/> <a onclick="delrepu(\'' . $row['id'] . '\');" title="Completely Delete (not undoable)"  alt="Delete"><img src="del.gif" /></a>';
            echo '</td>';
        }


        echo '</tr>';
        //show the reputation trashed reason to member
        if (!$forum_user['is_guest'] AND ($forum_user['id'] == $uid OR $forum_user['is_admmod']) AND $row['trashed'] == 1) {
            echo '<tr><td colspan="5">সম্মাননা বাতিলের কারণ: ' . $row['trashed_reason'];
            if($forum_user['is_admmod']){
                echo ' <strong>by: '. $row['mod_name'].'</strong>';
            }
            echo '</td></tr>';
        }
    }
    echo '</table></div>';
}


if ($action == 'log') {


    if (!defined('FORUM_PAGE'))
        @define('FORUM_PAGE', 'reputation');

    $uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;


    if (!$forum_user['is_admmod'])
        message($lang_common['Bad request']);

    //get username of the uid
    $query = $forum_db->query_build(array(
                                         'SELECT' => 'username',
                                         'FROM' => 'users',
                                         'WHERE' => "id='" . $forum_db->escape($uid) . "'"
                                    ));
    $result = $forum_db->fetch_assoc($query);


    // Setup breadcrumbs

    $forum_page['crumbs'] = array(
        array($forum_config['o_board_title'], forum_link($forum_url['index'])),
        array(sprintf($lang_reputation['Breadcrumb'], forum_htmlencode($lang_reputation['Reputation log']), forum_htmlencode($result['username'])), forum_link($forum_url['profile_about'], $uid)),
    );

    ob_start();
    require FORUM_ROOT . 'header.php';


    if (!defined('FORUM_PARSER_LOADED'))
        require FORUM_ROOT . 'include/parser.php';

    //prepare the summary.
    $query = $forum_db->query("SELECT count(action) as positive, (SELECT SUM(action)
					 FROM `{$forum_db->prefix}reputation`
					 WHERE changer_id = '" . $forum_db->escape($uid) . "'
					 AND action = -1) AS negative
FROM `{$forum_db->prefix}reputation`
WHERE `action`='1' AND changer_id = '" . $forum_db->escape($uid) . "'");
    $summary = $forum_db->fetch_assoc($query);
    ?>
    <script type="text/javascript">
        function trashreason(rep_id) {
            var baseurl = '<?php echo $cur_url;?>/reputation.php?action=trash&rid=';
            var reason = prompt('Enter the reason of trashing this reputation');
            if (!reason) {
                return false;
            }

            window.location = baseurl + rep_id + '&reason=' + encodeURIComponent(reason);

        }
        function delrepu(rep_id) {
            var baseurl = '<?php echo $cur_url;?>/reputation.php?action=delete&rid=';
            if (confirm('Are you sure you want to delete this reputation? Please note: this can\'t be undone!')) {
                window.location = baseurl + rep_id;
            }
        }

    </script>
<div class="main-subhead">
    <h2 class="hn">
        <span><?php echo forum_htmlencode($forum_config['o_reputation_word']) . ' [+' . forum_number_format($summary['positive']) . ' / ' . forum_number_format($summary['negative']) . ']';?></span>
    </h2>
</div>
<style>
    #reputation td {
        overflow: hidden;

    }

    .reason, .subject {
        width: 30%;
    }

    .deleted {
        background-color: grey;
    }
</style>
	<div id="reputation" class="main-content main-frm">
		<table>
			<thead>
            <tr>
                <td><b><?php echo $lang_reputation['To user']; ?></b></td>
                <td class="subject"><b><?php echo $lang_reputation['Topic']; ?></b></td>
                <td class="reason"><b><?php echo $lang_reputation['Reason']; ?></b></td>
                <td width="40px"><b><?php echo $lang_reputation['Type']; ?></b></td>
                <td><b><?php echo $lang_reputation['Date']; ?></b></td>
                <?php if ($forum_user['is_admmod']) echo '<td width="50px">Action</td>'; ?>
            </tr>
            </thead>
    <?php
                $query = "SELECT k.id, k.time, k.reason, k.post_id, k.changer_id, k.action, k.user_id, k.`trashed`, k.`trashed_reason`, k.`mod_name`, t.subject, u.username FROM {$forum_db->prefix}reputation AS k LEFT JOIN {$forum_db->prefix}users AS u ON u.id=k.user_id left join {$forum_db->prefix}posts as p on p.id=k.post_id LEFT JOIN {$forum_db->prefix}topics AS t ON t.id=p.topic_id WHERE k.changer_id='{$forum_db->escape($uid)}' ORDER BY k.id DESC limit 5000";


    $result = $forum_db->query($query) or error($forum_db->error(), __FILE__, __LINE__);
    while (false != ($row = $forum_db->fetch_assoc($result)))
    {
        ?>
        <tbody>
        <tr <?php if ($row['trashed']) echo 'class="deleted"';?>>
    <?php
                    echo '<td><a href="' . $cur_url . '/reputation.php?action=show&uid=' . $row['user_id'] . '">' . $row['username'] . '</a>';
        if ($forum_user['is_admmod']) {
            echo '<a href="' . $cur_url . '/reputation.php?action=log&uid=' . $row['user_id'] . '"><img src="' . $cur_url . '/log.gif" alt="Log" title="Reputation Log" /></a>';
        }

        echo '</td>';

        if (is_null($row['subject'])) {
            echo '<td>Deleted</td>';
        } else {
            echo '<td class="subject"><a href="' . $base_url . '/viewtopic.php?pid=' . $row['post_id'] . '#p' . $row['post_id'] . '">' . $row['subject'] . '</a></td>';
        }
        echo '<td>' . parse_message($row['reason'], 0) . '</td>';
        $type = ($row['action'] == '1') ? $cur_url . '/plus.gif' : $cur_url . '/minus.gif';
        echo '<td><img src="' . $type . '" /></td>';
        echo '<td>' . forum_number_format(date('d-m-Y h:i A', $row['time'])) . '</td>';
        if ($forum_user['is_admmod']) {
            echo '<td>';
            if ($row['trashed']) {
                //echo '<td title="Trash Reason: '.@$row['delete_reason'].' by '.@$row['mod_name'].'">Deleted<br/>';
                echo '<a  href="' . $cur_url . '/reputation.php?action=untrash&rid=' . $row['id'] . '" alt="Undelete" title="Untrash this reputation"><img src="untrash.png" /></a>';
            } else {
                echo '<a onclick="trashreason(\'' . $row['id'] . '\');" title="Trash"><img alt="Trash" src="trash.png" width="24" height="24" /></a>';

            }
            echo '<br/> <a onclick="delrepu(\'' . $row['id'] . '\');" title="Completely Delete (not undoable)"  alt="Delete"><img src="del.gif" /></a>';
            echo '</td>';
        }


        echo '</tr>';
        //show the reputation trashed reason to member
        if ($forum_user['is_admmod'] AND $row['trashed'] == 1) {
            echo '<tr><td colspan="5">সম্মাননা বাতিলের কারণ: ' . $row['trashed_reason'];
             if($forum_user['is_admmod']){
                echo ' <strong>by: '. $row['mod_name'].'</strong>';
            }
            echo '</td></tr>';
        }
    }
    echo '</table></div>';
}


$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <!-- forum_qpost -->


require FORUM_ROOT . 'footer.php';
