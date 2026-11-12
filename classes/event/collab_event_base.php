<?php
/**
 * Base of the research-instrumentation events of iAssign (saved states, versions, conflicts, opened activities...).
 *
 * All of them are triggered in the context of the iAssign course module and carry in 'other':
 *  - iassign_statementid: the activity (exercise) inside the iAssign instance
 *  - groupsize: members sharing the submission at that moment (1 = individual condition, >1 = group/collaborative condition)
 * so the standard Moodle log (Reports > Logs, logstore_standard_log) is enough to compute engagement indicators.
 *
 * @package   mod_iassign
 * @copyright iMath (http://www.matematica.br) and LInE (http://line.ime.usp.br) - Computer Science Dep. of IME-USP (Brazil)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_iassign\event;

defined('MOODLE_INTERNAL') || die();

abstract class collab_event_base extends \core\event\base {

  /** Language string (mod_iassign) with the event name. */
  protected static function name_string () {
    return '';
    }

  public static function get_name () {
    return get_string(static::name_string(), 'mod_iassign');
    }

  public function get_description () {
    $statementid = isset($this->other['iassign_statementid']) ? $this->other['iassign_statementid'] : 0;
    $groupsize = isset($this->other['groupsize']) ? $this->other['groupsize'] : 1;
    return "The user with id '$this->userid' - " . static::class . " - in the activity with id '$statementid' " .
      "(group size $groupsize) of the iAssign with course module id '$this->contextinstanceid'.";
    }

  public function get_url () {
    $params = array('id' => $this->contextinstanceid);
    if (!empty($this->other['iassign_statementid'])) {
      $params['action'] = 'view';
      $params['iassign_current'] = $this->other['iassign_statementid'];
      }
    return new \moodle_url('/mod/iassign/view.php', $params);
    }

  protected function validate_data () {
    parent::validate_data();
    if ($this->contextlevel != CONTEXT_MODULE)
      throw new \coding_exception('Context level must be CONTEXT_MODULE.');
    if (!isset($this->other['iassign_statementid']))
      throw new \coding_exception('The \'iassign_statementid\' value must be set in other.');
    }

  public static function get_other_mapping () {
    return array('iassign_statementid' => array('db' => 'iassign_statement', 'restore' => 'iassign_statement'));
    }

  } // abstract class collab_event_base
