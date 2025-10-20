<?php

/**
 * 
 * This is prepared to examin all the activities associates with a particular iLM.
 * It is invoked by Moodle through the Administrative interface to edit iLM (./admin/search.php),
 * chosing the "see activities" options.
 * 
 * The association {iassign_statement} with {files} must be:
 * - {files}.itemid              = {iassign_statement}.id  (despite the Moodle usual association {files}.itemid = {iassign}.id
 * - {iassign_statement}.filesid = {files}.id              (reduntant option to easy verification)
 * Remember: {course_modules}.instance = {iassign}.id and {course_modules}.id = {context}.instanceid
 * 
 * settings_activities.php
 * 
 * Release Notes:
 * - v 0.1.0 2022/02/22
 *   + First version
 * 
 * @author Leo^nidas de Oliveira Branda~o
 * @version v 0.1.0 2022/02/22
 * @package mod_iassign_settings
 * @since 2022/02/22
 * @copyright iMatica (<a href="http://www.matematica.br">iMath</a>) - Computer Science Dep. of IME-USP (Brazil)
 *
 * @calledby ./mod/iassign/settings.php: when choosed option "see all activities" in iAssign setting to an specific iLM (ilm_id)
 *
 * <b>License</b> 
 *  - http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// See: locallib.php ! static function update_iassign_statement_files (...): 2289/9174

// Moodle core defines constant MOODLE_INTERNAL which shall be used to make sure that the script is included and not called directly.
// Must be directly called... remove: defined('MOODLE_INTERNAL') || die();

global $OUTPUT, $CFG, $DB; //, $PAGE;

require_once('../../config.php'); //NAO

require_once($CFG->dirroot . '/mod/iassign/lib.php');
require_once($CFG->dirroot . '/mod/iassign/locallib.php');

// require login as Admin
if (!is_siteadmin()) { // ./lib/accesslib.php
  // require_login(0, true);
  print "Access denied!<br/>";
  exit();
  }

//$PAGE->set_context(context_system::instance());
//$PAGE->set_url('/admin/settings.php', array('section' => $section));
//$PAGE->set_pagetype('admin-setting-' . $section);
//$PAGE->set_pagelayout('admin');
//$PAGE->navigation->clear_cache();
//navigation_node::require_admin_tree();

print $OUTPUT->header();
print $OUTPUT->box(get_string('see_ilm_activities', 'iassign'));
// print $PAGE;

//D echo "<br/><br/><br/>2<br/>";
//D echo "action=" . $action . ", ilm_id=" . $ilm_id . ", ilm_param_id=" . $ilm_param_id . ", status=" . $status . "<br/>\n";
//D echo "CFG->dirroot=" . $CFG->dirroot . "<br/>\n";

// Used to recover only {iassign_statement} with {iassign_statement}.iassignid == {modules}.id == $ilm_id, such that {modules}.name=='iassign'
// that also means {iassign}.id == $ilm_id
$ilm_id = optional_param('ilm_id', 0, PARAM_INT);

$action = optional_param('action', 'view', PARAM_TEXT);
$ilm_param_id = optional_param('ilm_param_id', 0, PARAM_INT);
$ilm_id_parent = optional_param('ilm_id_parent', 0, PARAM_INT);
$status = optional_param('status', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$contextid = optional_param('contextid', 0, PARAM_INT);

//D echo "action=" . $action . ", ilm_id=" . $ilm_id . ", ilm_param_id=" . $ilm_param_id . ", status=" . $status . "<br/>\n";
//D echo "courseid=" . $courseid . ", contextid=" . $contextid . "<br/>\n";
//D if (!$courseid) $courseid = 484; //DEBUG: se nao definido, usar como teste o curso MAC118 2021

if (!$action && !$ilm_id) {
  return; // nothing to be done (it is the Admin entering at the initial administrative section)
  }

// Get the iLM
$list_all_iassign_ilm = $DB->get_records('iassign_ilm', array('id' => $ilm_id));
//$list_all_iassign_ilm = $DB->get_records('iassign_ilm', array('parent' => 0, 'enable' => 1));
//$msgD .= " -- #list_all_iassign_ilm = " . count($list_all_iassign_ilm) . "\n"; //D

//D echo "iLM.id=" . $ilm_id . ": total = " . sizeof($list_all_iassign_ilm) . "<br/>\n";
//D foreach ($list_all_iassign_ilm as $ilm) {
//D   // iLM.id=7 => iVProg 1
//D   // iLM.id=7 => 1
//D   echo " - iLM: id=" . $ilm->id . "; name=" . $ilm->name . "; extension=" . $ilm->extension . ", version=" . $ilm->version . "<br/>\n";
//D //$list_all_iassign_ilm->name . ", " . $list_all_iassign_ilm->extension . "<br/>\n";
//D //print_r($list_all_iassign_ilm);
//D   }

//D echo "--------------------<br/><br/>\n";

$module_iassign = $DB->get_record('modules', array('name' => 'iassign')); // get 'modules.id' of iAssign
if (!$module_iassign) exit(1);
// echo "module_iassign: "; print_r($module_iassign); echo "<br/>";

// Get all course/context with iAssign
//D $str_query = "SELECT {course}.id, {context}.id AS contextid, {course}.shortname FROM {course}, {context}, {course}_modules WHERE " .
//D             " {course}.id={course}_modules.course AND {course}_modules.id={context}.instanceid AND {course}_modules.module=" . $module_iassign->id . " ORDER BY {course}.id, {context}.id";
//DD echo $str_query;
//D $context_course = $DB->get_records_sql($str_query);
//D $countC = 0; foreach ($context_course as $item) { $countC++; echo " - " . $item->id . ", " . $item->contextid . ", " . $item->shortname . "<br/>"; } echo "Total = " . $countC . "<br/>";
//DD 173 cursos/contextos
//DD  - 484, 54184, 2021_mac118_ivprog

//  = $DB->set_field('course_modules', 'instance', $newitemid, array('id' => $this->task->get_moduleid()));
//echo "this->get_instance()->name = " . $this->get_instance()->name . "<br>";

//D $context = context_module::instance($USER->cm); //2022/02: get 'context.id' = 'files.contextid' ($USER->cm is the 'course_modules.id')
//D echo "course.id=" . $USER->cm->course . ", module=" . $USER->cm->module . "<br/>";
//D print_r($USER->cm); echo "<br/>";

//2022/02 update_iassign_statement_files(...)
//iassign::test_iassign_statement_files(); //DEBUG: test association 'iassign_statement' with 'files'
require('files_functions.php'); // load 'function see_files_context($contextid, $DB)'

// see_all_files_context($contextid, $DB); // inside 'files_functions.php'
// see_all_files_context(484, $DB); // inside 'files_functions.php'

// From 'settings.php' courseid came here undefined
// The $ilm_id has the iLM selected: get only {iassign_statement}.iassignid == {modules}.id == $ilm_id, such that {modules}.name=='iassign'
[$list_all_instances_of_iassign, $all_context, $list_of_ia_files, $list_of_contextid_by_courses, $list_of_courses] = get_files($courseid, $ilm_id, $DB); // inside 'files_functions.php'
//D echo "#list_all_instances_of_iassign=" . sizeof($list_all_instances_of_iassign) . ", #all_context=" . sizeof($all_context) . ", #list_of_ia_files=" . sizeof($list_of_ia_files) . "<br/>";

/*
$ilm = $DB->get_record('iassign_ilm', array('id' => $ilm_id));
$upgrade_file = $ilm->url . 'ilm-upgrade_' . strtolower($ilm->name) . '.xml';
$upgrade_xml = @simplexml_load_file($upgrade_file, null, LIBXML_NOCDATA);
$lang = current_language();
if (array_key_exists($lang, $upgrade_xml->description))  $description = $upgrade_xml->description->$lang;
else  $description = $upgrade_xml->description->en;
*/

$str = "";
$str .= $OUTPUT->box_start();
// $str .= '<center>' . $OUTPUT->error_text(get_string('error_check_iassign_filter', 'iassign')) . '</center>';
// $str .= "<center>"; // . $OUTPUT->heading(get_string('settings_see_activities', 'iassign'), 3, 'helptitle', 'uniqueid');

print "<style>
 .error { background-color: #f4d6d2; color: #ca3120; }
 .course { background-color: #a1d2f3; }
</style>\n";

// All course with activities of this iLM ($ilm_id):
$size_contexts = sizeof($all_context);
print "Size of contexts: " . $size_contexts . "<br/>\n";

//for ($ii=0; $ii<$size_contexts; $ii++)
//  if ($all_context[$ii]->contextid) // if not empty
//    print "Course: <tt title='" . $all_context[$ii]->contextid . "'>id=" . $all_context[$ii]->id . "</tt>; <strong title='fullname'>" . $all_context[$ii]->fullname . "</strong><br/>\n";
$size_of_courses = sizeof($list_of_courses); // $list_of_courses[i] = array({course}.id, {course}.shortname, {course}.fullname)
print "Size of courses: " . $size_of_courses . "<br/>\n";
for ($ii=0; $ii<$size_of_courses; $ii++) {
  $course = $list_of_courses[$ii]; // the $ii-th course: {course}.id, {course}.shortname, {course}.fullname
  if (isset($course)) {
    $courseid = $course[0]; // {course}.id;
    $list_of_context = $list_of_contextid_by_courses[$courseid]; // $list_of_contextid_by_courses[] is indexed by {course}.id
    $total_of_context = sizeof($list_of_context);
    print "Course.<tt title='" . $course[2] . "'>id=" . $courseid . "</tt>: <strong title='shortname'>" . $course[1] . "</strong>: #context=" . $total_of_context . " : " . $course[2] . "<br/> &nbsp; \n";
    for ($jj=0; $jj<$total_of_context; $jj++)
      if (isset($list_of_context[$jj]))
        print $list_of_context[$jj] . ", "; // if not empty
    print "<br/>\n";
    }
  } // for $list_of_contextid_by_courses, $list_of_courses
  
print "<br/>";

$courseid0 = -1; // last {course}.id
$str .= "<table id='outlinetable' class='generaltable boxaligncenter' width='100%'>\n";
$str .= "<tr class='course'><td class='course' title='course.id'>c.id</td><td class='course' colspan='4' title='course.fullname'>{course}.fullname</td></tr>\n";
$str .= "<tr><td title='context.id'>cxtid</td><td title='iAssign statement id'>{ia}.id</td><td title='ID:={course_modules}.id to access mod/iassign/view.php?id=ID'>{course_modules}.id</td><td># files</td><td>fullname</td></tr>\n";
$sizeList = sizeof($list_all_instances_of_iassign);
for ($ii=0; $ii<=$sizeList; $ii++) {
  $context_statement = $all_context[$ii]; //REVER!!!!
  $list_of_iassign = $list_all_instances_of_iassign[$ii]; //  title='iassign_statement.id'
  $list_of_files = $list_of_ia_files[$ii]; // { ia_id, ia_file, ia_filesid, filesid=files.id, filename=files.filename, itemid=files.itemid, contextid=files.contextid }

  if (!$list_of_iassign || !$list_of_iassign->courseid) continue; // jump this item, it is empty!

  $courseid = $list_of_iassign->courseid; // last {course}.id
  if ($courseid!=$courseid0) { // new course
    $str .= "<tr class='course'><td class='course' title='course.id'>" . $courseid . "</td><td class='course' colspan='4' title='course.fullname'>" . $list_of_iassign->fullname . "</td></tr>\n";
    $courseid0 = $courseid; // update last {course}.id
    }

  $sizeofFiles = sizeof($list_of_files);
  if ($sizeofFiles==0) continue;
  $str .= "<tr><td title='context.id'>" . $context_statement->contextid . "</td><td title='iassign.id=course_modules.instance'>" . $list_of_iassign->id. "</td>";
  $str .= "<td title='ID:={course_modules}.id to access mod/iassign/view.php?id=ID'>" . $context_statement->course_module_id . "</td>\n";
  $str .= "<td title='Number of activities in this block'>" . $sizeofFiles . "</td><td>" . $list_of_iassign->name . "</td></tr>\n";
  $str .= "<tr><td></td><td colspan='4'><table>\n";
  // Line table : {iassign_statement}.id ; {iassign_statement}.name ; {iassign_statement}.file ; {iassign_statement}.filesid ; {files}.id ; {files}.itemid ; {files}.filename
  $str .= "<tr><td title='{iassign_statement}.id'>ia_id</td><td title='{iassign_statement}.name'>ia_name</td><td title='{iassign_statement}.file'>ia_file</td>";
  $str .= "<td title='{iassign_statement}.filesid'>ia_filesid</td>\n";
  $str .= " <td title='{files}.id'>filesid</td><td title='{files}.itemid'>filesitemid</td><td title='{files}.filename'>filename</td></tr>\n";
  for ($jj=0; $jj<$sizeofFiles; $jj++) {
    $item = $list_of_files[$jj];
    if ($item->ia_id) {
      $msgUpdateItem = ""; $msgUpdateIA = ""; $msgFilesNotFound = "";
      if ($item->itemid!=$item->ia_id)
        $msgUpdateItem = " title='must update {files}.itemid <- {iassign_statement}.id=" . $item->ia_id . "' class='error'"; // td {files}.itemid              <- {iassign_statement}.id
      if ($item->filename!="." && $item->ia_filesid!=$item->filesid) // item->ia_filesid={iassign_statement}.filesid != item->filesid={files}.id
        $msgUpdateIA = " title='must update {iassign_statement}.filesid <- {files}.id=" . $item->id . "' class='error'";     // td {iassign_statement}.filesid <- {files}.id
      if ($item->filesid == NULL)
        $msgFilesNotFound = " title='lost file!' class='error'";
      $str .= "<tr><td title='{iassign_statement}.id - item " . $jj . "'>" . $item->ia_id . "</td>\n";  // {iassign_statement}.id
      $str .= " <td" . $msgFilesNotFound . ">" . $item->ia_name . "</td>\n";                            // {iassign_statement}.name
      $str .= " <td" . $msgFilesNotFound . ">" . $item->ia_file . "</td>\n";                            // {iassign_statement}.file
      $str .= " <td" . $msgUpdateIA . ">" . $item->ia_filesid . "</td>\n";                              // {iassign_statement}.filesid
      if ($item->filesid != NULL) $str .= " <td title='files.id'>" . $item->filesid . "</td>\n";        // {files}.id
      else $str .= " <td title='files.id' class='error'>-</td>\n";                                      // idem
      $str .= " <td" . $msgUpdateItem . ">" . $item->itemid . "</td>\n";                                // {files}.itemid
      if ($item->filesid != NULL) $str .= " <td>" . $item->filename . "</td></tr>\n";                   // {files}.filename
      else $str .= " <td class='error'>(emprty!)</td></tr>\n";                                          // idem
      }
    else echo "Erro: jj=" . $jj . " vazio {" . $item->ia_id . "," . $item->itemid . "}<br>";
    } // for ($jj=0; $jj<$sizeofFiles; $jj++)
  $str .= "</table></td></tr>\n";
  //D print_r($context_statement); echo "<br/>";
  }
$str .= "</table>\n";

//echo $str;

// $optionsno = new moodle_url('/admin/settings.php', array('section' => 'modsettingiassign', 'action' => 'view'));
// $optionsyes = new moodle_url('/mod/iassign/settings_ilm.php', array('action' => 'upgrade', 'ilm_id' => $ilm_id));

//$str .= "<center>" . $OUTPUT->heading(get_string('confirm_upgrade_ilm', 'iassign'), 3, 'helptitle', 'uniqueid');

//$url_yes = new moodle_url('/mod/iassign/settings_ilm.php', array('action' => 'upgrade', 'ilm_id' => $ilm_id));
//$link_yes = $OUTPUT->action_link($url_yes, "<font color='green'><b>" . get_string('yes', 'iassign') . "</b></font>");

//$url_no = new moodle_url('/admin/settings.php', array('section' => 'modsettingiassign', 'action' => 'view'));
//$link_no = $OUTPUT->action_link($url_no, "<b>" . get_string('no', 'iassign') . "</b>");

//$str .= $link_no . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $link_yes . "</center>";

//$str .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . "</center>\n";

$str .= $OUTPUT->box_end();

print $str;

print $OUTPUT->footer();

//$settings_activities->add(new admin_setting_heading('iassign', get_string('upgrade_ilm_title', 'iassign'), $str));
