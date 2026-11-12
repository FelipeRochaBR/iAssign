<?php
/**
 * Lightweight AJAX endpoint of the group collaboration panel (see locallib.php!iassign::iassign_group_panel()).
 *
 * Unlike view.php it does not render the activity, trigger "viewed" events or update completion, and it releases the
 * session lock right away, so the panel can poll it every few seconds.
 *
 * Parameters: cmid, statementid, sesskey, action =
 *   status  - heartbeat (presence) + group status (members/online, latest saved state, last submission)
 *   history - one page of saved versions (newest first, without contents): 'offset', 'limit' (default 5); replies 'more'
 *   version - content of one saved version ('stateid'), used by the panel to check it can be reopened before restoring
 *   versionraw - content of one saved version as plain text, loaded by the iLM in the history preview ("eye" icon)
 *   restore - appends version 'stateid' as the newest state of the whole group ('direction' = back/forward, for the log)
 *   heartbeat - research: extends/starts the student's work session ('active' = 1 if keyboard/mouse was used)
 *   autosync  - research: logs the student's "Sync automatically" choice ('enabled', 'initial')
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

  case 'history': // one page of the version history ('offset', 'limit'); 'more' = there are older versions after this page
    $offset = max(0, optional_param('offset', 0, PARAM_INT));
    $limit = min(\mod_iassign\group_collab::HISTORY_LIMIT, max(1, optional_param('limit', 5, PARAM_INT)));
    $versions = $collab->history($statement->id, $userid, $offset, $limit + 1); // one extra just to know if there is more
    $more = count($versions) > $limit;
    $reply = array('status' => 'OK', 'versions' => array_slice($versions, 0, $limit), 'more' => $more);
    break;

  case 'version':
    $version = $collab->get_version($statement->id, $userid, required_param('stateid', PARAM_INT));
    $reply = $version ? array('status' => 'OK', 'content' => $version->answer) : array('status' => 'ERROR', 'error' => 'version');
    break;

  case 'versionraw': // the iLM loads this URL as its content (iLM_PARAM_Assignment) to preview a version: plain text, as ilm_security.php
    $version = $collab->get_version($statement->id, $userid, required_param('stateid', PARAM_INT));
    header('Content-Type: text/plain; charset=utf-8'); // not JSON: iVProg's $.get would hand an object instead of the text
    echo $version ? $version->answer : '';
    exit;

  case 'restore': // 'direction': 'back' (older than the version the student had = restore) or 'forward' (newer = update)
    $direction = optional_param('direction', 'back', PARAM_ALPHA) === 'forward' ? 'forward' : 'back';
    $reply = $collab->restore($statement->id, $userid, required_param('stateid', PARAM_INT), $direction);
    break;

  case 'heartbeat': // research instrumentation: work sessions, same in both conditions
    $collab->heartbeat_session($statement->id, $userid, optional_param('active', 0, PARAM_INT) ? 1 : 0);
    $reply = array('status' => 'OK');
    break;

  case 'autosync': // research instrumentation: "Sync automatically" choice of the student (changes the treatment)
    $collab->trigger_event('autosync_changed', $statement->id, $statement->id, array(
      'enabled' => optional_param('enabled', 0, PARAM_INT) ? 1 : 0,
      'initial' => optional_param('initial', 0, PARAM_INT) ? 1 : 0));
    $reply = array('status' => 'OK');
    break;

  default:
    $reply = array('status' => 'ERROR', 'error' => 'action');
  }

header('Content-Type: application/json; charset=utf-8');
echo json_encode($reply);
