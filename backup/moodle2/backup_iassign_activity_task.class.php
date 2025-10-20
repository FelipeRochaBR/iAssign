<?php

/**
 * This file contains the backup task for the iAssign module
 * 
 * Release Notes:
 * - v 1.1 2014/01/06
 *   + Fix bug in activity name, remove tag filter (backup_iassign_activity_task::define_my_settings).
 *
 * @author Patricia Alves Rodrigues
 * @author Leo^nidas de Oliveira Branda~o
 * @version v 1.1 2014/01/06
 * @package mod_iassign_backup
 * @since 2012
 * @cite iMath (http://www.matematica.br) - LInE (www.usp.br/line) - Computer Science Dep. of IME-USP (Brazil)
 * 
 * <b>License</b> 
 *  - http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *  
 * @see backup_activity_task
 */

defined('MOODLE_INTERNAL') || die(); // Moodle core defines constant MOODLE_INTERNAL which shall be used to make sure that the script is included and not called directly.

require_once($CFG->dirroot . '/mod/iassign/backup/moodle2/backup_iassign_stepslib.php');


// Provides the steps to perform one complete backup of the iAssign instance
// @see backup_activity_task
class backup_iassign_activity_task extends backup_activity_task {


  // No specific settings for this activity
  protected function define_my_settings () {
    //TODO Retirar quando atualizar todo os iAssign que estao com a tag &lt;ia_uc&gt;
    $temp = explode("&lt;ia_uc&gt;", $this->name);
    $this->name = $temp[0];
    }


  // Defines a backup step to store the instance data in the 'iassign.xml' file (and 'iassign_ilm.xml')
  protected function define_my_steps () {
    $this->add_step(new backup_iassign_activity_structure_step('iassign_structure', 'iassign.xml'));
    // $this->add_step(new backup_iassign_activity_structure_step('iassign_ilm_structure', 'iassign_ilm.xml')); //2023 To register all iLM
    }


  // Transform encoded links (URL) of iAssign, from this Moodle to generic 
  // mark ("@IASSIGNINDEX" or "@IASSIGNVIEWBYID") to be decoded by restoring process.
  // Change anything like "http://...backupmoodle/mod/iassign/view.php?id=X" (or "iassign/index.php"), to the
  // "http://...restoremoodle/mod/iassign/view.php?id=X".
  // It is used in 'restore_iassign_activity_task.class.php!define_decode_rules()' to restore with correct ID
  // @param string $content some HTML text that eventually contains URL to the activity instance scripts
  // @return string The content with the URL encoded
  static public function encode_content_links ($content) {
    global $CFG;

    $base = preg_quote($CFG->wwwroot, "/");
    // $base = preg_quote($CFG->wwwroot.'/mod/iassign','#');
    // Link to the list of choices
    $search = "/(" . $base . "\/mod\/iassign\/index.php\?id\=)([0-9]+)/";
    $content = preg_replace($search, '$@IASSIGNINDEX*$2@$', $content);

    // Link to choice view by moduleid
    $search = "/(" . $base . "\/mod\/iassign\/view.php\?id\=)([0-9]+)/";
    $content = preg_replace($search, '$@IASSIGNVIEWBYID*$2@$', $content);

    return $content;
    }

  } // class backup_iassign_activity_task extends backup_activity_task
