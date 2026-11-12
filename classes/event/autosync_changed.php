<?php
/**
 * Group panel "Sync automatically" option of a student: objectid = {iassign_statement}.id; other['enabled'] = 0/1;
 * other['initial'] = 1 when it only reports the value in use when the activity was opened.
 * See collab_event_base for the common 'other' data (iassign_statementid, groupsize).
 *
 * @package   mod_iassign
 * @copyright iMath (http://www.matematica.br) and LInE (http://line.ime.usp.br) - Computer Science Dep. of IME-USP (Brazil)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_iassign\event;

defined('MOODLE_INTERNAL') || die();

class autosync_changed extends collab_event_base {

  protected function init () {
    $this->data['crud'] = 'u';
    $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    $this->data['objecttable'] = 'iassign_statement';
    }

  protected static function name_string () {
    return 'event_autosync_changed';
    }

  public static function get_objectid_mapping () {
    return array('db' => 'iassign_statement', 'restore' => 'iassign_statement');
    }

  }
