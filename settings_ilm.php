<?php

/**
 * Settings iLM manager.
 * Interface:
 * 1. Reaches this code from administrative Moodle area
 *    'Site administration|Plugins|Activity modules|iAssign:Add iLM'; or
 *    'Site administration|Plugins|Activity modules|iAssign:Import iLM'; or
 *    'Site administration|Plugins|Activity modules|iAssign: "click" on the "gear" of any iLM then in "Add new iLM version" or "edit"
 * 
 * Release Notes:
 * - v 2.0.1 2022/09/15
 *   + Considering table 'iassign_ilm': messages to fields 'evaluate', 'reevaluate', 'editingbehavior'; fixes to allow the edition of 'reevaluate', 'editingbehavior'
 * - v 1.6.1 2017/12/02
 *   + Changed 'echo' to 'print'
 * - v 1.6 2013/10/31
 *   + Insert support of import iLM from zip packages.
 * - v 1.5 2013/10/24
 *   + Insert function for upgrade an iLM.
 * - v 1.4 2013/08/02
 *   + Insert list of iLMs informations for teacher view.
 * - v 1.3 2013/07/12
 *   + Insert actions: copy (new version from an iLM) and new version (empty new version).
 * 
 * @author Patricia Alves Rodrigues
 * @author Leo^nidas de Oliveira Branda~o
 * @version v 2.0.1 2022/09/15
 * @package mod_iassign_settings
 * @since 2013/01/29
 * @see   locallib.php : class ilm_settings
 * @see   settings_form.php
 * @copyleft iMatica (<a href="http://www.matematica.br">iMath</a>) - Computer Science Dep. of IME-USP (Brazil)
 * @copyleft LInE (<a href="http://www.usp.br/line">LInE</a>) - IME-USP (Brazil)
 * 
 * <b>License</b> 
 *  - http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG, $USER, $PAGE, $OUTPUT, $DB;

require_once("../../config.php");
require_once($CFG->dirroot . '/mod/iassign/locallib.php');
require_once($CFG->dirroot . '/mod/iassign/settings_form.php');

require_login();
if (isguestuser()) {
  die();
  }

// Parameters GET e POST
$ilm_id = optional_param('ilm_id', 0, PARAM_INT);
$ilmid = optional_param('ilmid', 0, PARAM_INT);
$ilm_parent = optional_param('ilm_parent', 0, PARAM_INT);
$status = optional_param('status', 0, PARAM_INT);
$action = optional_param('action', NULL, PARAM_TEXT);
$url = new moodle_url('/admin/settings.php', array('section' => 'modsettingiassign'));
$from = optional_param('from', NULL, PARAM_TEXT);

$contextuser = context_user::instance($USER->id);

$PAGE->set_url($url);
$PAGE->set_context($contextuser);
$PAGE->blocks->show_only_fake_blocks(); //
$PAGE->set_pagelayout('popup');

if ($action == 'edit') { // Edit data of an iLM => processed in 'settings_form.php'

  $title = get_string('edit_ilm', 'iassign') . $OUTPUT->help_icon('add_ilm_iassign', 'iassign');
  $PAGE->set_title($title);

  // Get all fields of this iLM: name, type, set_lang, description_lang, author, action, timecreated, timemodified, parent, ...
  // On the first time $ilm_id is well defined, but on the second (it is empty), however we need to call "add_edit_copy_ilm(.)"
  $param = ilm_settings::add_edit_copy_ilm($ilm_id, $action); // locallib.php: class ilm_settings: add_edit_copy_ilm($ilm_id, $action)

  // On the first call $ilm_id is defined: the calling is necessary to fill in data to the form
  // on the second call $ilm_id empty and could be avoided
  // without 'ilm_settings::add_edit_copy_ilm(.)' the process are not successfully finished

  $description = $param->description_lang; // used to present the iLM description in 'settings_form.php' - {"en":"...","pt":"..."}

  $mform = new mod_ilm_form(); // in 'settings_form.php': class mod_ilm_form
  //DEBUG: do NOT use "mod_ilm_form($param)" Warning: htmlspecialchars() expects parameter 1 to be string, object given in /var/www/html/saw/lib/pear/HTML/Common.php on line 177
  //DEBUg: since bellow fills form data
  $mform->set_data($param);

  if ($mform->is_cancelled()) {
    close_window();
    die;
    }
  else if ($formdata = $mform->get_submitted_data()) { // chegando aqui com $formdata->type vazio!
    // $formdata = { [name] [version] [type]vazio! [url] [lang] [description] [extension] [width] [height] [evaluate] [file] [id] [set_lang] [description_lang] [author] [action] [timecreated] [timemodified] [parent] [enable] [submitbutton]
    ilm_settings::edit_ilm($formdata, $formdata->file); // localib.php: class ilm_settings
    close_window(0, true);
    die;
    }

  print $OUTPUT->header();
  print $OUTPUT->heading($title); // put the header title
  $mform->display();
  print $OUTPUT->footer();

  die;
  } // if ($action == 'edit')
else

if ($action == 'new_version') { // Administration > plugins > iAssign : after select the iLM and the option 'Add new iLM version'

  $title = get_string('new_version_ilm', 'iassign') . $OUTPUT->help_icon('add_ilm_iassign', 'iassign');
  $PAGE->set_title($title);

  //echo "settings_ilm.php: form = "; print_r($form); echo "<br/>";

  if (!$mform) {
    // Get values from the parent iLM in {iassign_ilm}, put them in array $param
    $param = ilm_settings::add_edit_copy_ilm($ilm_id, $action); // locallib.php: class iassign
  
    // Field "description_lang" is used to present the iLM description in 'settings_form.php' - {"en":"...","pt":"..."}
    // {iassign_ilm}.description is JSON like: {"en":"interactive...", "pt_br":"interativo..."}
    // $lang = current_language(); $sizel = strlen($lang); if ($sizel>2) $lang = $lang[0] . $lang[1];
    // locallib.php : class iassign_language : static function get_description_lang($lang, $descriptions)
    $description = iassign_language::get_description_lang(current_language(), $param->description_lang);
    if (isset($description)) // necessary to present a simple text inside form "text" field
      $param->description = $description;

    //D echo "settings_ilm.php: " . $msg . ", description=" . $description . "<br/>lang=" . $lang . "<br/>";
    //D echo "param="; print_r($param); echo "<br/>"; // description_lang
    //D print_r($description); echo "<br/>";
  
    $mform = new mod_ilm_form(); // in 'settings_form.php': class mod_ilm_form
    $mform->set_data($param);
    }
  //D $msg1 .= "param->ilm_id=" . $param->ilm_id; echo "settings_ilm.php: msg1 = " . $msg1 . "<br/>";

  if ($mform->is_cancelled()) {
    close_window();
    die;
    }
  else { // Final form processing! Registered by 'locallib.php' with ilm_settings::copy_new_version_ilm($formdata)
    $formdata = $mform->get_data();
    if ($formdata) { // already exists the iLM
      ilm_settings::copy_new_version_ilm($formdata); // locallib.php: class ilm_settings
      close_window(0, true);
      die;
      }
    }
  
  print($OUTPUT->header());
  print($OUTPUT->heading($title));
  $mform->display();
  print($OUTPUT->footer());
  die;
  }
else

if ($action == 'copy') {
  $title = get_string('copy_ilm', 'iassign') . $OUTPUT->help_icon('add_ilm_iassign', 'iassign');
  $PAGE->set_title($title);
  $param = ilm_settings::add_edit_copy_ilm($ilm_id, $action);

  $mform = new mod_ilm_form(); // in 'settings_form.php': class mod_ilm_form
  $mform->set_data($param);
  if ($mform->is_cancelled()) {
    close_window();
    die;
    }
  else if ($formdata = $mform->get_data()) {
    ilm_settings::copy_new_version_ilm($formdata);
    close_window(0, true);
    die;
    }

  print($OUTPUT->header());
  print($OUTPUT->heading($title));
  $mform->display();
  print($OUTPUT->footer());
  die;
  }
else

if ($action == 'add') { // add new iLM using data from form and package from ZIP file
  $title = get_string('add_ilm_iassign', 'iassign') . $OUTPUT->help_icon('add_ilm_iassign', 'iassign'); // add_ilm_iassign='Add iLM'
  $PAGE->set_title($title);
  $param = ilm_settings::add_edit_copy_ilm($ilm_id, $action); // ./mod/iassign/locallib.php

  $mform = new mod_ilm_form(); // iLM form to fill data (name, extension,...)
  $mform->set_data($param);

  if ($mform->is_cancelled()) {
    close_window();
    die;
    }
  else if ($formdata = $mform->get_data()) {
    $filename = strtolower($mform->get_new_filename('file'));
    $extension = explode(".", $filename);
    if ($extension[count($extension) - 1] == 'zip') { // accept ZIP file
      // {iassign_ilm}.typ: $formdata->type : 0 = package JavaScript; 1 = package Java (JAR)
      $retorno = ilm_settings::new_ilm($formdata); // ./mod/iassign/locallib.php: unpack and process it (create directory...)
      if ($retorno == true) {
        close_window(0, true);
        die;
        }
      }
    else { // Open a frame (on the same page) with warnings
      // 'Error while extracting the content from iLM package file.'
      print($OUTPUT->notification(get_string('error_add_ilm_zip', 'iassign'), 'notifyproblem'));
      }
    }
  print($OUTPUT->header());
  print($OUTPUT->heading($title));
  $mform->display();
  print($OUTPUT->footer());
  die;
  } // if ($action == 'add')
else

if ($action == 'import') { // must be file with extension 'ipz'
echo "settings_ilm.php: 5 - action=" . $action . "<br/>"; //leo //DEBUG remover
  // About IPZ format see https://www.matematica.br/ia/about_ilm-application.html
  $title = get_string('import_ilm', 'iassign') . $OUTPUT->help_icon('import_ilm', 'iassign');
  $PAGE->set_title($title);
  $param = new stdClass();
  $param->action = $action;
  $CFG->action_ilm = $action;

echo "settings_ilm.php: 6 - action=" . $action . "<br/>"; //leo //DEBUG remover

  $mform = new mod_ilm_form(); // in 'settings_form.php': class mod_ilm_form
echo "settings_ilm.php: 7 - action=" . $action . "<br/>"; //leo //DEBUG remover
  $mform->set_data($param);
  if ($mform->is_cancelled()) { // enter here if user "clicks" at button "Cancel"
echo "settings_ilm.php: 8 - action=" . $action . "<br/>"; //leo //DEBUG remover
    close_window();
    die;
    }
  else if ($formdata = $mform->get_data()) { // enter here if user adds an IPZ file and "clicks" at button "Sava changes"
echo "settings_ilm.php: 9 - action=" . $action . "<br/>"; //leo //DEBUG remover
    $filename = strtolower($mform->get_new_filename('file'));      
    $extension = explode(".", $filename);
echo "settings_ilm.php: 10 - explode filename="; print_r($extension); echo "<br/>"; //leo //DEBUG remover
// settings_ilm.php: 10 - explode filename=Array ( [0] => ifractions_2025_04_07 [1] => ipz ) 
//if (is_array($extension)) $extension = $extension[sizeof($extension)-1];
//echo "settings_ilm.php: 11 - filename=" . $filename . ", extension=" . $extension . "<br/>"; //leo //DEBUG remover
    if ($extension[count($extension) - 1] == 'ipz') {
echo "settings_ilm.php: 12 - action=" . $action . "<br/>"; //leo //DEBUG remover
       $retorno = ilm_settings::import_ilm($formdata->file); // locallib.php: class ilm_settings
echo "settings_ilm.php: 13 - retorno=" . ($retorno?"true":"false") . "<br/>"; //leo //DEBUG remover
       if ($retorno == true) {
         close_window(5, true);
         die;
         }
      }
    else { // Open a frame (on the same page) with warnings
echo "settings_ilm.php: 10 - action=" . $action . "<br/>"; //leo //DEBUG remover
      print($OUTPUT->notification(get_string('error_upload_ilm', 'iassign'), 'notifyproblem'));
      }
    }
  print($OUTPUT->header());
  print($OUTPUT->heading($title));
  $mform->display();
  print($OUTPUT->footer());
  die;
  } // if ($action == 'import')
else

if ($action == 'confirm_delete_ilm') { // Step 1 to remove iLM: will provide information about activities with this iLM
echo "<br/><br/><br/><br/><br/>settings_ilm.php: (0) delete  action=" . $action . ", ilm_parent=" . $ilm_parent . "<br/>\n"; //2025/04/11 leo
  // iLM removal: step 1
  // After Admin "click" button "Delete iLM", it reaches this point (verify the presence of activities of this iLM and ask Admin to confirm his intention)
  // If Admin "click" "Continue" (to confirm remotion) it calls "settings_ilm.php!$action=='delete'"
  $title = get_string('delete_ilm', 'iassign');
  $PAGE->set_title($title);
  $PAGE->set_pagelayout('base');
  $delete_ilm = ilm_settings::confirm_delete_ilm($ilm_id, $ilm_parent); // ./locallib.php : class ilm_settings : static function confirm_delete_ilm($ilm_id, $ilm_parent)
  print($OUTPUT->header());
  print($OUTPUT->heading($title));
  print($delete_ilm);
  print($OUTPUT->footer());
  die;
  }
else

if ($action == 'delete') { // Step 2 to remove iLM:
echo "<br/><br/><br/><br/><br/>settings_ilm.php: delete action=" . $action . "<br/>\n"; //2025/04/11 leo
  // iLM removal: step 2
  // After Admin "click" button "Delete iLM" and confirm his intention to remove the iLM, reaches this point (really remove the iLM)
  // If Admin "click" "Continue" (to confirm remotion) it calls "settings_ilm.php!$action=='delete'"
  $title = get_string('delete_ilm', 'iassign');
  $PAGE->set_title($title);
  
  $PAGE->set_pagelayout('redirect');
  $parent = ilm_settings::delete_ilm($ilm_id); // ./locallib.php
echo "settings_ilm.php: (1) delete parent null, action=" . $action . "<br/>\n"; //2025/04/11 leo

  if ($parent == null) {
echo "settings_ilm.php: (2) delete parent null<br/>\n"; //2025/04/11 leo
    $title = get_string('delete_ilm', 'iassign');
    $PAGE->set_title($title);
    $PAGE->set_pagelayout('base');
    $delete_ilm = ilm_settings::confirm_delete_ilm($ilm_id, $ilm_parent); // ./locallib.php : class ilm_settings : static function confirm_delete_ilm($ilm_id, $ilm_parent)
    $msg_error = get_string('error_folder_permission_denied', 'iassign');
    if ($CFG->debugdisplay) // debug messages is turned on
      $msg_error .= "<br/>On settings_ilm.php: no iLM returned!<br/>";
    // Open a frame (on the same page) with warnings
    print($OUTPUT->header());
    print($OUTPUT->heading($title));
    print($OUTPUT->notification($msg_error, 'notifyproblem'));
    print($delete_ilm);
    print($OUTPUT->footer());
    die;
    }
  if ($parent == 0)
    redirect(new moodle_url('/admin/settings.php?', array('section' => 'modsettingiassign', 'action' => 'view')));
  else
    redirect(new moodle_url('/admin/settings.php?', array('section' => 'modsettingiassign', 'action' => 'config', 'ilm_id' => $ilm_parent)));
  } // if ($action == 'delete') : step 2
else

if ($action == 'confirm_default_ilm') {
  $title = get_string('confirm_default', 'iassign');
  $PAGE->set_title($title);
  $PAGE->set_pagelayout('base');
  $default_ilm = ilm_settings::confirm_default_ilm($ilm_id, $ilm_parent);

  print($OUTPUT->header());
  print($default_ilm);
  print($OUTPUT->footer());
  die;
  }
else

if ($action == 'default') {
  $title = get_string('default_ilm', 'iassign');
  $PAGE->set_title($title);
  $PAGE->set_pagelayout('redirect');
  ilm_settings::default_ilm($ilm_id);
  redirect(new moodle_url('/admin/settings.php?', array('section' => 'modsettingiassign', 'action' => 'config', 'ilm_id' => $ilm_parent)));
  }
else

if ($action == 'list') {
  $title = get_string('list_ilm', 'iassign');
  $PAGE->set_title($title);
  $list_ilm = ilm_settings::list_ilm();
  print($OUTPUT->header());
  print($OUTPUT->heading($title . $OUTPUT->help_icon('list_ilm', 'iassign')));
  print($list_ilm);
  print($OUTPUT->footer());
  die;
  }
else

if ($action == 'upgrade') {
  $title = get_string('upgrade_ilm_title', 'iassign');
  $PAGE->set_title($title);
  $PAGE->set_pagelayout('redirect');

  $ilm = ilm_settings::upgrade_ilm($ilm_id);
  if ($ilm == 0)
    redirect(new moodle_url('/admin/settings.php?', array('section' => 'modsettingiassign', 'action' => 'view')));
  else
    redirect(new moodle_url('/admin/settings.php?', array('section' => 'modsettingiassign', 'action' => 'config', 'ilm_id' => $ilm)));
  }
else

if ($action == 'view') {
  $iassign_ilm = $DB->get_record('iassign_ilm', array('id' => $ilm_id));
  $title = get_string('view_ilm', 'iassign') . $OUTPUT->help_icon('add_ilm_iassign', 'iassign'); // add_ilm_iassign='Add iLM'
  $PAGE->set_title($title . ': ' . $iassign_ilm->name . ' ' . $iassign_ilm->version);
  $view_ilm = ilm_settings::view_ilm($ilm_id, $from);
  print($OUTPUT->header());
  print($OUTPUT->heading($title . ': ' . $iassign_ilm->name . ' ' . $iassign_ilm->version));
  print($view_ilm);
  print($OUTPUT->footer());
  die;
  }