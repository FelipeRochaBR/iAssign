<?php
/**
 * A student opened an activity (exercise) of iAssign: objectid = {iassign_statement}.id.
 * See collab_event_base for the common 'other' data (iassign_statementid, groupsize).
 *
 * @package   mod_iassign
 * @copyright iMath (http://www.matematica.br) and LInE (http://line.ime.usp.br) - Computer Science Dep. of IME-USP (Brazil)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_iassign\event;

defined('MOODLE_INTERNAL') || die();

class activity_opened extends collab_event_base {

  protected function init () {
    $this->data['crud'] = 'r';
    $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    $this->data['objecttable'] = 'iassign_statement';
    }

  protected static function name_string () {
    return 'event_activity_opened';
    }

  public static function get_objectid_mapping () {
    return array('db' => 'iassign_statement', 'restore' => 'iassign_statement');
    }

  }
