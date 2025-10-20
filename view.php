<?php

/**
 * This php script contains all the stuff to display iAssign.
 * 
 * @author Patricia Alves Rodrigues
 * @author Leo^nidas O. Branda~o
 * @version v 1.0 2012/10/16
 * @package mod_iassign
 * @since 2010/09/27
 * @copyright iMatica (<a href="http://www.matematica.br">iMath</a>) - Computer Science Dep. of IME-USP (Brazil)
 * 
 * <b>License</b> 
 *  - http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("lib.php");
require_once($CFG->libdir . '/completionlib.php'); // $completion = new completion_info($course); $completion->set_module_viewed($cm);
require_once($CFG->libdir . '/plagiarismlib.php');

//REMOVER
//DEBUG 2020/08/31
//D require_once("ilm_debug/escreva.php"); //leo REMOVER! xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
//D $resp = writeContent("", "", "todos_files_iassign.txt", "Teste"); // ($filetype1, $pathbase, $outputFile, $msgToRegister) //leo REMOVER!
function get_all_iassign_files () {
  // Get all iAssign files on table 'files':
  // files = id; contenthash; pathnamehash; contextid; component; filearea; itemid; filepath; filename; userid; filesize; mimetype; status; source; author;
  //         license; timecreated; timemodified; sortorder; referencefileid
  global $DB;
  // $all_files = $DB->get_records('files', array('component' => 'mod_iassign')); // pegar os do iAssign
  $indices = "id=257279 OR id=257791 OR id=258303 OR id=258559 OR id=127743 OR id=64767 OR id=65023 OR id= 65535 OR id= 87394 OR " .
             "id=138498 OR id=138506 OR id=138514 OR id=138522 OR id=50463 OR id=50464 OR id= 50465 OR id=138530 OR id= 50466 OR " .
             "id= 50467 OR id= 50468 OR id= 50469 OR id=50470 OR id=50471 OR id=104744 OR id=50472";
  $str_query = "SELECT id,contextid,filearea,itemid,filepath,filename,userid,author,timecreated FROM {files}\n" .
    "WHERE " . $indices . " ORDER BY timecreated DESC";
  $all_files = $DB->get_records_sql($str_query);

  $total = count($all_files);
  $msg = $str_query . "\n#linhas = " . $total . "\n";  
  foreach ($all_files as $linha) {
    foreach ($linha as $key => $value) {
      if ($key == "timecreated") {
        $time1 = date('Y-m-d H:i:s', $value - date('Z')); // 12:50:29
        $msg .= $time1 . ";" . $value . ";";
        }
      else
      if ($key == "source") {
        // $item = str_replace(";", "\;", $value);
        $item = addslashes($value);
        $msg .= "'" . addslashes($value) . "';";
        }
      else $msg .= $value . ";";
      }
    $msg .= "\n";
    }
  require_once("ilm_debug/escreva.php"); //leo REMOVER! xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
  $resp = writeContent("", "", "todos_files_iassign.csv", $msg); // ($filetype1, $pathbase, $outputFile, $msgToRegister) //leo REMOVER!
  }

//D get_all_iassign_files(); //REMOVER

//D get_all_iassign_files(); //leo REMOVER! xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
//D Remover todos itens de 'files' tal que: filesize = 0

// Parameters GET and POST
$id = optional_param('id', 0, PARAM_INT); // Course Module ID
$a = optional_param('a', 0, PARAM_INT); //  iAssign instance id (from table 'iassign')

$mood_url = new moodle_url('/mod/iassign/view.php');

if ($id) {
  // ./lib/datalib.php : function get_coursemodule_from_id($modulename, $cmid, $courseid=0, $sectionnum=false, $strictness=IGNORE_MISSING): returns 'course_modules.*' and 'modules.name'
  $cm = get_coursemodule_from_id('iassign', $id);

  if (!$cm) { // Moodle function 'get_coursemodule_from_id(...)' returns the object from table '*_iassign_statement'
    print_error('invalidcoursemodule');
    }

  $iassign = $DB->get_record("iassign", array("id" => $cm->instance));
  if (!$iassign) { // 'course_modules.instance = iassign.id'
    print_error('invalidid', 'iassign');
    }

  $course = $DB->get_record("course", array("id" => $iassign->course));
  if (!$course) {
    print_error('coursemisconf', 'iassign');
    }
  $mood_url->param('id', $id);
  }
else {
  if (!$a) { // try with 'iassign_current'
    $iassign_current = optional_param('iassign_current', 0, PARAM_INT);
    if ($iassign_current) { // use {iassign_statement}.id to get {iassign}.id
      $iassign_statement = $DB->get_record("iassign_statement", array("id" => $iassign_current));
      $a = $iassign_statement->iassignid;
      }
    }
  $iassign = $DB->get_record("iassign", array("id" => $a));
  if (!$iassign) {
    print_error('invalidid', 'iassign');
    }
  $course = $DB->get_record("course", array("id" => $iassign->course));
  if (!$course) {
    print_error('coursemisconf', 'iassign');
    }
  $cm = get_coursemodule_from_instance("iassign", $iassign->id, $course->id);
  if (!$cm) {
    print_error('invalidcoursemodule');
    }
  $mood_url->param('a', $a);
  }

$PAGE->set_url($mood_url);

require_login($course, true, $cm);

$PAGE->set_title(format_string($iassign->name));
$PAGE->set_heading($course->fullname);

// About each object/table
// - $iassing :: object from table '*_iassign_statement'
// - $cm      :: object from table '*_course_modules'
// - $course  :: object from table '*_course_modules_completion'

// Marks a module as viewed, i.e., register in {course_modules_completion} the current {course_modules}.id and {user}.id
// It is necessary: "$CFG->enablecompletion = 1" in "./config.php"
// With "$CFG->enablecompletion = 1" in "config.php" it is possible to set item "Completion tracking" in ./course/edit.php?id=X
// {course} must have 'enablecompletion = 1'
$completion = new completion_info($course);
$completion->set_module_viewed($cm, $USER->id); // ./lib/completionlib.php : set_module_viewed($cm, $userid=0)

// ./lib/completionlib.php : see this
// - public function set_module_viewed($cm, $userid=0): 715/1372 - need {course_modules}.completionview == 1
//   cm->completionview=1, COMPLETION_VIEW_NOT_REQUIRED=0, this->is_enabled(cm)=0
//   if ($cm->completionview == COMPLETION_VIEW_NOT_REQUIRED || !$this->is_enabled($cm)) return;
//   $this->update_state($cm, COMPLETION_COMPLETE, $userid); 
// - public function update_state($cm, $possibleresult=COMPLETION_UNKNOWN, $userid=0, $override = false) : 565/1374
//   $this->internal_set_data($cm, $current);
// - public function internal_set_data($cm, $data) : 1039/1372
//   $event->add_record_snapshot('course_modules_completion', $data);
// - public function is_enabled($cm = null): 279/1374 -- Checks whether completion is enabled in a particular course and possibly activity
//   $CFG->enablecompletion, $CFG->enablecompletion != COMPLETION_DISABLED
//   $this->course->enablecompletion != COMPLETION_DISABLED == 0 ; COMPLETION_ENABLED == 1

$write_solution = 1;

// ./mod/iassign/locallib.php : class iassign : function __construct ($iassign, $cm, $course)
//$iassigninstance = new iassign($iassign, $cm, $course, array('write_solution' => 1));
$iassigninstance = new iassign($iassign, $cm, $course); // this will provide read iLM content

// ./mod/iassign/locallib.php : in class iassign, actually who display the iAssign whose id is '$id'! (this function ignores parameters)
$iassigninstance->view(); //    will call $this->action(), that calls view_iassign_current()
