<?php
/**
 * A student saved the current state ("Save current state"): objectid = the new {iassign_submission_state}.id of the student;
 * other['forced'] = 1 when it overwrote a newer state of a teammate after a conflict warning.
 * See collab_event_base for the common 'other' data (iassign_statementid, groupsize).
 *
 * @package   mod_iassign
 * @copyright iMath (http://www.matematica.br) and LInE (http://line.ime.usp.br) - Computer Science Dep. of IME-USP (Brazil)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_iassign\event;

defined('MOODLE_INTERNAL') || die();

class state_saved extends collab_event_base {

  protected function init () {
    $this->data['crud'] = 'c';
    $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    $this->data['objecttable'] = 'iassign_submission_state';
    }

  protected static function name_string () {
    return 'event_state_saved';
    }

  public static function get_objectid_mapping () {
    return self::NOT_MAPPED;
    }

  }
