<?php
/**
 * A save was refused because a teammate had saved a newer state: objectid = {iassign_statement}.id;
 * other['otherauthorid'] = who saved the newer state.
 * See collab_event_base for the common 'other' data (iassign_statementid, groupsize).
 *
 * @package   mod_iassign
 * @copyright iMath (http://www.matematica.br) and LInE (http://line.ime.usp.br) - Computer Science Dep. of IME-USP (Brazil)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_iassign\event;

defined('MOODLE_INTERNAL') || die();

class save_conflict extends collab_event_base {

  protected function init () {
    $this->data['crud'] = 'r';
    $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    $this->data['objecttable'] = 'iassign_statement';
    }

  protected static function name_string () {
    return 'event_save_conflict';
    }

  public static function get_objectid_mapping () {
    return array('db' => 'iassign_statement', 'restore' => 'iassign_statement');
    }

  }
