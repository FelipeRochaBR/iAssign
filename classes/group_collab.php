<?php
/**
 * Group collaboration support for iAssign activities: group members, presence, saved states ("Save current state"),
 * conflict detection, version history and restore.
 *
 * Used by:
 *  - locallib.php (class iassign): save_state(), group submission sync, the group panel printed above the iLM
 *  - group_ajax.php: lightweight AJAX endpoint polled by the group panel (status, history, version, restore)
 *
 * Saved states live in {iassign_submission_state}, an append-only log: in a group activity every save inserts one
 * identical row per group member ('userid'), with 'authorid' = who actually clicked "Save". Restoring an old version
 * never deletes anything: it appends a new state with the old content.
 *
 * @package   mod_iassign
 * @copyright iMath (http://www.matematica.br) and LInE (http://line.ime.usp.br) - Computer Science Dep. of IME-USP (Brazil)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_iassign;

defined('MOODLE_INTERNAL') || die();

class group_collab {

  /** Polling interval of the group panel while some teammate has the activity open (ms). */
  const POLL_FAST_MS = 3000;
  /** Polling interval of the group panel while the student is alone in the activity (ms). */
  const POLL_SLOW_MS = 10000;
  /** A member is "online" while their last heartbeat is at most this old (> 2 slow polls). */
  const ONLINE_SECONDS = 25;
  /** How many versions the history lists. */
  const HISTORY_LIMIT = 50;

  /** @var \stdClass {iassign} record */
  protected $iassign;
  /** @var \stdClass|\cm_info course module */
  protected $cm;
  /** @var int course id */
  protected $courseid;

  public function __construct ($iassign, $cm, $courseid) {
    $this->iassign = $iassign;
    $this->cm = $cm;
    $this->courseid = $courseid;
    }

  // ---------------------------------------------------------------------------------------------------------------
  // Group membership

  /// Ids of the groups of $userid that count for this iAssign instance
  //  (empty when group submission is disabled or the student has no group).
  public function user_groupids ($userid) {
    if (empty($this->iassign->group_submission))
      return array();

    $groupingid = (isset($this->cm->groupingid) && $this->cm->groupingid) ? $this->cm->groupingid : 0;
    $usergroups = groups_get_user_groups($this->courseid, $userid);
    if ($groupingid) // activity restricted to a grouping: only groups of that grouping count
      return !empty($usergroups[$groupingid]) ? $usergroups[$groupingid] : array();
    return isset($usergroups[0]) ? $usergroups[0] : array();
    }

  /// Userids that must share the same submission as $userid (array($userid) alone when not a group activity).
  public function member_ids ($userid) {
    $groupids = $this->user_groupids($userid);
    if (empty($groupids))
      return array($userid);

    $members = array();
    foreach ($groupids as $groupid)
      foreach (groups_get_members($groupid, 'u.id') as $member)
        $members[$member->id] = (int) $member->id;

    if (empty($members))
      return array($userid);
    return array_values($members);
    }

  /// Names of the groups of $userid that count for this iAssign instance.
  public function group_names ($userid) {
    $names = array();
    foreach ($this->user_groupids($userid) as $groupid)
      $names[] = format_string(groups_get_group_name($groupid));
    return $names;
    }

  // ---------------------------------------------------------------------------------------------------------------
  // Saved states

  /// Most recent saved state of $userid in the activity (without the content), or false.
  public function latest_state ($statementid, $userid) {
    global $DB;
    $rows = $DB->get_records('iassign_submission_state',
      array('iassign_statementid' => $statementid, 'userid' => $userid),
      'timecreated DESC, id DESC', 'id, timecreated, authorid', 0, 1);
    return $rows ? reset($rows) : false;
    }

  /// Public info (who/when) of a saved state - never the content.
  public function state_info ($state) {
    global $USER;
    $authorid = (int) $state->authorid;
    $authorname = '';
    if ($authorid) {
      $users = self::users_by_id(array($authorid));
      if ($users)
        $authorname = fullname(reset($users));
      }
    return array(
      'id'         => isset($state->id) ? (int) $state->id : 0,
      'time'       => (int) $state->timecreated,
      'timestr'    => self::time_str($state->timecreated),
      'authorid'   => $authorid,
      'authorname' => $authorname,
      'mine'       => ($authorid == $USER->id)
      );
    }

  /// Stores a new state ("Save current state") for $userid and, in a group activity, for every teammate.
  //  @param int $base_time timecreated of the state the student's page is working over (-1: do not check)
  //  @param bool $force true: save even if a teammate saved a newer state after $base_time
  //  @return array reply for the AJAX caller: status 'OK' (with 'time', 'latest'), 'CONFLICT' (with 'latest') or 'ERROR'
  public function save_state ($statementid, $userid, $content, $base_time = -1, $force = false) {
    global $DB, $USER;

    $members = $this->member_ids($userid);

    // Group activity: refuse to silently overwrite a state saved by a teammate after the student's page was loaded
    if (count($members) > 1 && $base_time >= 0 && !$force) {
      $latest = $this->latest_state($statementid, $userid);
      if ($latest && $latest->timecreated > $base_time && $latest->authorid != $USER->id)
        return array('status' => 'CONFLICT', 'latest' => $this->state_info($latest));
      }

    $timenow = time();
    $myid = 0;
    foreach ($members as $memberid) {
      $newentry = new \stdClass();
      $newentry->iassign_statementid = $statementid;
      $newentry->userid = $memberid;
      $newentry->timecreated = $timenow;
      $newentry->answer = $content;
      $newentry->authorid = $USER->id; // who actually saved (the row is replicated to every member)
      $id = $DB->insert_record('iassign_submission_state', $newentry);
      if (!$id)
        return array('status' => 'ERROR', 'error' => 'insert');
      if ($memberid == $userid)
        $myid = $id;
      }

    $saved = (object) array('id' => $myid, 'timecreated' => $timenow, 'authorid' => $USER->id);
    return array('status' => 'OK', 'time' => $timenow, 'latest' => $this->state_info($saved));
    }

  /// Version history of $userid in the activity (newest first, without contents).
  public function history ($statementid, $userid) {
    global $DB;
    $rows = $DB->get_records('iassign_submission_state',
      array('iassign_statementid' => $statementid, 'userid' => $userid),
      'timecreated DESC, id DESC', 'id, timecreated, authorid', 0, self::HISTORY_LIMIT);

    // Resolve all author names at once
    $authorids = array();
    foreach ($rows as $row)
      if ($row->authorid)
        $authorids[$row->authorid] = $row->authorid;
    $authors = self::users_by_id(array_values($authorids));

    global $USER;
    $versions = array();
    foreach ($rows as $row) {
      $versions[] = array(
        'id'         => (int) $row->id,
        'time'       => (int) $row->timecreated,
        'timestr'    => self::time_str($row->timecreated),
        'authorname' => isset($authors[$row->authorid]) ? fullname($authors[$row->authorid]) : '',
        'mine'       => ($row->authorid == $USER->id)
        );
      }
    return $versions;
    }

  /// One saved state of $userid (with content), or false when it does not exist / is not theirs.
  public function get_version ($statementid, $userid, $stateid) {
    global $DB;
    return $DB->get_record('iassign_submission_state',
      array('id' => $stateid, 'iassign_statementid' => $statementid, 'userid' => $userid));
    }

  /// Restores an old version: appends it as a new state (for the whole group), so nothing is lost and it can be undone.
  public function restore ($statementid, $userid, $stateid) {
    $version = $this->get_version($statementid, $userid, $stateid);
    if (!$version)
      return array('status' => 'ERROR', 'error' => 'version');
    return $this->save_state($statementid, $userid, $version->answer, -1, true);
    }

  // ---------------------------------------------------------------------------------------------------------------
  // Presence / panel data

  /// Registers that $userid has the activity open right now.
  public function heartbeat ($statementid, $userid) {
    global $DB;
    $timenow = time();
    $presence = $DB->get_record('iassign_group_presence', array('iassign_statementid' => $statementid, 'userid' => $userid));
    if ($presence) {
      $presence->timemodified = $timenow;
      $DB->update_record('iassign_group_presence', $presence);
      }
    else {
      try {
        $DB->insert_record('iassign_group_presence',
          (object) array('iassign_statementid' => $statementid, 'userid' => $userid, 'timemodified' => $timenow));
        }
      catch (\dml_exception $e) { } // concurrent first heartbeat (unique index): the other request already registered it
      }
    }

  /// Data shown by the group panel: members (with "online"), groups, the latest saved state and the last submission.
  public function status_data ($statementid, $userid) {
    global $DB, $USER;

    $timenow = time();
    $memberids = $this->member_ids($userid);

    $presence = array();
    if ($memberids) {
      list($insql, $params) = $DB->get_in_or_equal($memberids, SQL_PARAMS_NAMED);
      $params['statementid'] = $statementid;
      $presence = $DB->get_records_select_menu('iassign_group_presence',
        "iassign_statementid = :statementid AND userid $insql", $params, '', 'userid, timemodified');
      }

    $members = array();
    foreach (self::users_by_id($memberids) as $user) {
      $online = ($user->id == $USER->id) ||
        (isset($presence[$user->id]) && $presence[$user->id] >= $timenow - self::ONLINE_SECONDS);
      $members[] = array(
        'id'       => (int) $user->id,
        'name'     => fullname($user),
        'initials' => \core_text::strtoupper(\core_text::substr($user->firstname, 0, 1) . \core_text::substr($user->lastname, 0, 1)),
        'me'       => ($user->id == $USER->id),
        'online'   => $online
        );
      }

    $latest = $this->latest_state($statementid, $userid);

    $submission = $DB->get_record('iassign_submission',
      array('iassign_statementid' => $statementid, 'userid' => $userid), 'id, timemodified, status');
    $submission_info = null;
    if ($submission && $submission->status > 0)
      $submission_info = array('time' => (int) $submission->timemodified, 'timestr' => self::time_str($submission->timemodified));

    return array(
      'members'    => $members,
      'groups'     => $this->group_names($userid),
      'latest'     => $latest ? $this->state_info($latest) : null,
      'submission' => $submission_info
      );
    }

  // ---------------------------------------------------------------------------------------------------------------
  // Helpers

  /// {user} records (with the fields fullname() needs) for the given ids, indexed by id, ordered by name.
  public static function users_by_id ($userids) {
    global $DB;
    if (empty($userids))
      return array();
    $fields = 'id, ' . implode(', ', \core_user\fields::get_name_fields());
    return $DB->get_records_list('user', 'id', $userids, 'firstname, lastname', $fields);
    }

  public static function time_str ($time) {
    return userdate($time, get_string('strftimedatetimeshort', 'langconfig'));
    }

  } // class group_collab
