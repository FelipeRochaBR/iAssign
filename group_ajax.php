<?php
/**
 * Lightweight AJAX endpoint of the group collaboration panel (see locallib.php!iassign::iassign_group_panel()).
 *
 * Unlike view.php it does not render the activity, trigger "viewed" events or update completion, and it releases the
 * session lock right away, so the panel can poll it every few seconds.
 *
 * Parameters: cmid, statementid, sesskey, action =
 *   status  - heartbeat (presence) + group status (members/online, latest saved state, last submission)
 *   history - list of saved versions (newest first, without contents)
 *   version - content of one saved version ('stateid'), used by the panel to check it can be reopened before restoring
 *   restore - appends version 'stateid' as the newest state of the whole group
 * Replies JSON: {status: 'OK'|'CONFLICT'|'ERROR', ...}
 *
 * @package   mod_iassign
 * @copyright iMath (http://www.matematica.br) and LInE (http://line.ime.usp.br) - Computer Science Dep. of IME-USP (Brazil)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

$cmid        = required_param('cmid', PARAM_INT);
$statementid = required_param('statementid', PARAM_INT);
$action      = required_param('action', PARAM_ALPHA);

$cm = get_coursemodule_from_id('iassign', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
require_login($course, false, $cm, false, true);
require_sesskey();

$context = context_module::instance($cm->id);
require_capability('mod/iassign:submitiassign', $context);

// Nothing below writes to the session: release its lock so the frequent polling never blocks the student's other requests
\core\session\manager::write_close();

$iassign = $DB->get_record('iassign', array('id' => $cm->instance), '*', MUST_EXIST);
$statement = $DB->get_record('iassign_statement', array('id' => $statementid, 'iassignid' => $iassign->id), 'id, iassignid', MUST_EXIST);

$collab = new \mod_iassign\group_collab($iassign, $cm, $course->id);
$userid = $USER->id; // always the logged student: never someone else's data

switch ($action) {
  case 'status':
    $collab->heartbeat($statement->id, $userid);
    $reply = $collab->status_data($statement->id, $userid);
    $reply['status'] = 'OK';
    break;

  case 'history':
    $reply = array('status' => 'OK', 'versions' => $collab->history($statement->id, $userid));
    break;

  case 'version':
    $version = $collab->get_version($statement->id, $userid, required_param('stateid', PARAM_INT));
    $reply = $version ? array('status' => 'OK', 'content' => $version->answer) : array('status' => 'ERROR', 'error' => 'version');
    break;

  case 'restore':
    $reply = $collab->restore($statement->id, $userid, required_param('stateid', PARAM_INT));
    break;

  default:
    $reply = array('status' => 'ERROR', 'error' => 'action');
  }

header('Content-Type: application/json; charset=utf-8');
echo json_encode($reply);
