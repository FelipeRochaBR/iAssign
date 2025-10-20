<?php

/**
 * This class provides all the functionality for an ia (interactive activities).
 *
 * Release Notes:
 * - v 4.9.9 2023/04/20
 *   + Fix in ias:={iassign_statement}/{files}:=f association. Methods in 'files_functions.php' to try associations: f.itemid=ias.id or f.itemid=ias.file (old)
 *     Mainly in 'add_edit_iassign()'
 * 
 * - v 4.9.8 2022/09/15
 *   + Fix iLM edition (now is possible to update fields 'reevaluate', 'editingbehavior' and 'evaluate' of table 'iassign_ilm')
 * 
 * - v 4.9.7 2022/02/11
 *   + Try to fix teacher edit of activity changing 'statement.id' to 'statement.file' in 'add_edit_iassign()';
 *   + Changed fix to activities order in 'show_iassign()', from "student view" to "teacher view"
 *
 * - v 4.9.6 2022/01/21
 *   + Try to fix view activity not sent through report ("view_iassign_current()": else if (!$student_answer) $loadTeacherActivity=true;)
 *
 * - v 4.9.5 2021/12/28
 *   + Detail to fix order o activities inside a block ('iassign_statement.position' - old instalation could generate order error)
 *
 * - v 4.9.4 2020/05/28
 *   + Detail fixed in old line 6789/7240, avoid error with file without extension ($filetype = $tmp[1];)
 *
 * - v 4.9.3 2020/01/16
 *   + Several fixes to allow to move activities in a block and improvements in repository and to appear comment submission button
 *
 * - v 4.9.2 2019/03/13
 *   + Several fixes to allow iLM under HTML stack
 *
 * - v 4.9.1 2017/11/02
 *   + Fixed a bug with iGeom script - the view was not loading the CEO with activity (resulting in an error "Erro ao gravar na base de dados")
 *   + function view_iassign_current(): it were inserted 2 'if(substr($ilm_name,0,5)=="igeom") if($iassign_statement_activity_item->special_param1==1) $lTA=true;' to call '$ilm->view_iLM(...lTA)'
 *
 * - v 4.9 2017/03/13
 *   + Great number of changes to allow the use o iVProgH5 (e.g. function changed: from 'applet_ilm' to 'build_ilm_tags')
 *
 * - v 4.8 2016/05/12
 *   + Function add_to_log() is deprecated it was then  rewritten  to the new events API
 *   + Resolved: the field "description" table "iassign_ilm" was being filled with the codes in HTML when importing magnet package and
 *     due to this the language field was not being displayed because in place of the quotes was recorded in the & quot field.
 *     ("$description_str = htmlentities(str_replace(array('<description>','</description>'), array('',''), $application_xml->description->asXML()));"
 *     replaced by
 *     "description_str = str_replace(array('<description>','</description>'), array('',''), $application_xml->description->asXML());")
 * - v 4.7 2016/02/17
 *   + Moodle 3.X: now iAssign is working fine under version 3.X (iassign/version.php: '$module->' changed to '$plugin->'; iassign/locallib.php: 'format_text(...)' replaced 'filter_text(...)')
 *   + Improved: now is possible to see the iGeom menus in preview (from iAssign Repository) - it depends on the version 1.3 of iAssign filter!
 *   + Improved: new names for 'form.input.MA_POST_Archive' and 'form.input.MA_POST_Value', now: 'iLM_PARAM_ArchiveContent' and 'iLM_PARAM_ActivityEvaluation'
 *   + BUG fixed: now it is fine the "online" edition of activities (in iAssign Repository) - inserted 'iLM_PARAM_Authoring' (iLM 2) and 'MA_POST_ArchiveTeacher' (iLM 1)
 *   + BUG fixed: it is possible to change the name of any file in iAssign Repository - problems was in 'optional_param(...)', 'PARAM_TEXT' replaced 'PARAM_ALPHANUMEXT'
 *   + BUG fixed: it is possible to duplicate any file in iAssign Repository - problems also in 'optional_param(...)', 'PARAM_TEXT' replaced 'PARAM_ALPHANUMEXT'
 *   + BUG fixed: now is possible to edit an iAssign activity with no new object been created (in iGeom: turn an example in exercise)
 *
 * --------------- (code bellow was used at our MOOC 2014)
 * - v 4.6 2014/02/25
 *   + Fix bugs in filter function for open applets.
 * - v 4.5 2014/02/24
 *   + Fix bugs in params.
 *   + Insert new param type.
 * - v 4.4 2014/01/24
 *   + Allow select type of params.
 *   + Insert the use of applet params specific for activities.
 * - v 4.3 2014/01/23
 *   + Insert function for move activities for other iLM (ilm_settings::confirm_move_iassign, ilm_settings::move_iassign).
 * - v 4.2 2016/02/13
 *   + Fixed API usage to work fine under Moodle 3.X: ilm_editor_new()
 * --------------- (above code used at our MOOC 2014)
 *
 * - v 4.1 2013/12/13
 *   + Insert log in iAssign actions.
 *   + Allow use the language in iLM description (ilm_settings::new_file_ilm, ilm_settings::new_ilm, ilm_settings::edit_ilm, ilm_settings::copy_new_version_ilm, ilm_settings::add_edit_copy_ilm, iassign_language::get_description_lang, iassign_language::get_all_lang).
 *   + Insert class for Log actions in system.
 * - v 4.0 2013/10/31
 *   + Insert support of export iLM in zip packages (ilm_settings::export_ilm).
 *   + Insert support of import iLM from zip packages (ilm_settings::import_ilm).
 *   + Fix bugs in message alert in iassign title and remove message alert of the description by cache error.
 * - v 3.9 2013/10/25
 *   + Insert support of upgrade iLM.
 *   + Insert support for more than one extension in iLM.
 *   + Fix bugs in verion control.
 * - v 3.8 2013/09/19
 *   + Get data of general fields in iassign statement table (iassign::add_edit_iassign).
 * - v 3.7 2013/09/12
 *   + Change tag APPLET in all functions of module (ilm::view_iLM, ilm_manager::ilm_editor_new, ilm_manager::ilm_editor_update).
 *   + Insert tool for manage aditional params for iLM (ilm_settings::add_edit_copy_param, ilm_settings::visible_param, ilm_settings::add_param, ilm_settings::edit_param, ilm_settings::copy_param, ilm_settings::delete_param).
 * - v 3.6 2013/09/05
 *   + Insert function ilm_settings::applet_ilm for create APPLET html tag.
 *   + Insert function ilm_settings::applet_filetime for get modified date of iLM file.
 *   + Change tag APPLET in function ilm_settings::view_ilm.
 * - v 3.5 2013/08/26
 *   + Fix bug in download package iassign without answers (iassign::report).
 * - v 3.4 2013/08/23
 *   + Fix bug in export package iassign.
 * - v 3.3 2013/08/22
 *   + Insert functions for export users answer in iassign (iassign::export_file_answer, iassign::export_package_answer, iassign::view_iassign_current, iassign::report).
 *   + Insert function for rename iassign file (ilm_manager::rename_file_ilm, ilm_manager::view_files_ilm).
 * - v 3.2 2013/08/21
 *   + Change title link with message for get file for donwload file (ilm_manager::view_files_ilm).
 *   + Change functions for import files for ilm_manager.php.
 *   + Create static utils class for functions system utils (iassign_utils::format_filename, iassign_utils::version_filename).
 * - v 3.1 2013/08/15
 *   + Change return file selected (ilm_manager::add_ilm).
 *   + Insert functions for import files, export files and remove selected files (ilm_manager::view_files_ilm, ilm_manager::import_files_ilm, ilm_manager::export_files_ilm, ilm_manager::delete_selected_ilm).
 * - v 3.0 2013/08/02
 *   + Insert link for view informations of iLMs in teacher view, same screen of admin view but wiht some features hide (ilm_settings::list_ilm, ilm_settings::view_ilm, iassign::view_iassigns).
 * - v 2.9 2013/08/01
 *   + Fix bugs in functions ilm_settings::new_file_ilm, ilm_settings::copy_new_version_ilm, ilm_settings::add_edit_copy_ilm.
 * - v 2.8 2013/07/25
 *   + Insert the activity name in header of view (activity::view_dates).
 *   + Set function default iLM in view iLMs versions (ilm_settings::default_ilm and ilm_settings::confirm_default_ilm).
 * - v 2.7 2013/07/24
 *   + Create link previous and next for student view in one activity (activity::view_dates).
 *   + Fix bugs for view error in iLM not on DB in function iassign::view_iassign_current.
 * - v 2.6 2013/07/23
 *   + Fix bugs for view files in function ilm_manager::view_files_ilm.
 *   + Fix bugs for comment on teacher view in function iassign::view_iassign_current.
 * - v 2.5 2013/07/12
 *   + Change iLM settings for accept versions (ilm_settings::new_file_ilm, ilm_settings::new_ilm, ilm_settings::edit_ilm, ilm_settings::copy_new_version_ilm).
 *   + Insert new informations in iLMs table: created date, modified date, author, version, modified date of JAR (ilm_settings::view_ilm).
 *   + Added support for PHP 7.0 Constructors and fallback for previous Moodle Versions (<3.1) (@contributor Ma'rcio Passos)
 *
 * @author Patricia Alves Rodrigues
 * @author Leo^nidas de Oliveira Branda~o
 * @contributor Igor Moreira Fe'lix (2018-)
 * @contributor Ma'rcio de Lima Passos (2014)
 * @version v 4.9.9 2023/04/20
 * @version v 4.9.2 2019/03/13
 * @version v 4.8 2016/05/12
 * @package mod_iassign_lib
 * @since 2010/09/27
 * @copyright iMath (http://www.matematica.br) and LInE (http://line.ime.usp.br) - Computer Science Dep. of IME-USP (Brazil)
 *
 * <b>License</b>
 *  - http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//TODO Review: eliminate iLM JAR under MoodleData? It is necessary to HTML5 packages (like iVProgH5), anyway
//TODO Whenever under HTTPS, verify if MoodleData is working, if it is not, please use iLM JAR under WWW setting $CONF_WWW = 1
//TODO (under HTTPS could fail 'pluginfile', like 'https://saw.atp.usp.br/pluginfile.php/1/mod_iassign/ilm/182563135/iassign/ilm/igeom/5920/iGeom.jar"'
$CONF_WWW = 1; //TODO get iLM (JAR) from WWW, avoiding MoodleData


/// Standard base class for all iAssign
//  Basic class to iAssign block of activities
class iassign {

  var $cm;
  var $course;
  var $iassign;
  var $striassign;
  var $striassigns;
  var $context;
  var $activity;
  var $iassign_up;
  var $iassign_down;
  var $action;
  var $iassign_submission_current;
  var $userid_iassign;
  var $allowPostButton;
  var $write_solution;
  var $view_iassign;
  var $coursecontext, $return; // if missing, PHP8 launch warning bellow
  // Attention with PHP8: Deprecated: Creation of dynamic property iassign::X is deprecated in /var/www/html/saw/mod/iassign/locallib.php on line Y

  // 3.1 update PHP 7.0 compatibility for all moodle versions
  //D public function iassign($iassign, $cm, $course) { self::__construct($iassign, $cm, $course); }

  /// Constructor for the base iassign class
  //  @calledby ./mod/iassign/view.php : $iassigninstance = new iassign($iassign, $cm, $course)
  //  @calledby ./mod/iassign/grade.php : $iassigninstance = new iassign($iassign, $cm, $course);
  //  @calledby ./mod/iassign/renderer.php : return $this->render(new iassign_files($context, $itemid, $filearea));
  function __construct ($iassign, $cm, $course) {
    global $COURSE, $CFG, $USER, $DB;

    $botton = optional_param('botton', NULL, PARAM_TEXT);
    $this->userid_iassign = optional_param('userid_iassign', 0, PARAM_INT);

    if (!is_null($botton))
      $USER->iassignEdit = $botton;

    // The Moodle function 'optional_param(...)' allow to filter GET parameters over a click that launches, e.g., './mod/iassign/view.php&userid_iassign=6'
    // If "$var1 = optional_param('userid_iassign', 'NOT', PARAM_TEXT);", $var1 will be set to 6 (if 'userid_iassign' is not present, $var1 will be set to 'NOT'
    $this->iassign_up = optional_param('iassign_up', 0, PARAM_INT); // if parameter 'iassign_up' does not exists or it is not integer => use 0 as "default"
    $this->iassign_down = optional_param('iassign_down', 0, PARAM_INT);
    $this->iassign_submission_current = optional_param('iassign_submission_current', 0, PARAM_INT);
    $this->write_solution = optional_param('write_solution', 0, PARAM_INT);
    $this->action = optional_param('action', NULL, PARAM_TEXT);
    $this->cm = $cm;
    $this->context = context_module::instance($this->cm->id);

    if (!has_capability('mod/iassign:evaluateiassign', $this->context, $USER->id))
      $this->userid_iassign = $USER->id;

    if ($course) {
      $this->course = $course;
      }
    else if ($this->cm->course == $COURSE->id) {
      $this->course = $COURSE;
      }
    else if (!$this->course = $DB->get_record('course', array('id' => $this->cm->course))) {
      print_error('invalidid', 'iassign');
      }
    $this->coursecontext = context_course::instance($this->course->id);

    if ($iassign) {
      $this->iassign = $iassign;
      }
    else if (!$this->iassign = $DB->get_record('iassign', array('id' => $this->cm->instance))) {
      print_error('invalidid', 'iassign');
      }

    $USER->context = context_module::instance($this->cm->id);
    $USER->cm = $this->cm->id;

    $this->iassign->cmidnumber = $this->cm->idnumber; // compatibility with modedit ia obj
    $this->iassign->courseid = $this->course->id; // compatibility with modedit ia obj
    $this->context = context_module::instance($this->cm->id);
    $this->striassign = get_string('modulename', 'iassign');
    $this->striassigns = get_string('modulenameplural', 'iassign');
    $this->return = $CFG->wwwroot . "/mod/iassign/view.php?id=" . $this->cm->id;
    $this->allowPostButton = 0; // hide submit button
    $this->view_iassign = optional_param('action', false, PARAM_BOOL);
    $this->activity = new activity(optional_param('iassign_current', NULL, PARAM_TEXT)); // this is the activity inside iAssign block (content file)

    $this->view();
    } // function __construct($iassign, $cm, $course)


  /// Show iAssign by using the security filter (temporary data on '*_iassign_security')
  //  This method provides the page to view each iLM with any interactive activity
  //  @calledby view.php : $iassigninstance->view($id); that instantiate 'iassign' (constructor above)
  function view () { // This is an standard function for each iAssign instance (to not use parameters, it is ignored)
    global $USER, $DB, $OUTPUT;

    // $iassign_statementid = $this->iassign->iassign_statementid;
    if ($this->activity!=null && $this->activity->get_activity()!=null && isset($this->activity->get_activity()->id))
      $iassign_statementid = $this->activity->get_activity()->id;
    else
      $iassign_statementid = "";

    // '$this' has : iassign Object = cm ; course ; iassign ; striassign ; striassigns ; context ; activity ; iassign_up ; iassign_down ; action ; ... ; coursecontext ; return
    // If this user has no capability to View 'iassign': stop here
    require_capability('mod/iassign:view', $this->context);

    // Trigger module view event - in lib/classes/event/base.php!create(array $data = null)
    $event = \mod_iassign\event\course_module_viewed::create(array(
      'objectid' => $this->iassign->id,
      'context' => $this->context
      ));

    $event->add_record_snapshot('course', $this->course);
    $event->trigger();

    if ($this->action) { // when student do/redo activity or teacher see student solution
      $this->action(); // calls '$this->view_iLM();'
      }
    else {
      print $OUTPUT->header();
      $this->view_iassigns(); // show the iLM with the content
      print $OUTPUT->footer();
      }

    // Security: delete all records with an error loading iLM - 'iassign_security : id iassign_statementid userid file timecreated view'
    //D $DB->delete_records("iassign_security", array("userid" => $USER->id, "view" => 1));
    //D $iassign_iLM_security = $DB->get_record("iassign_security", array("iassign_statementid" => $iassign_statementid));
    //D foreach ($iassign_iLM_security as $item) { echo $iassign_iLM_security->id . " ; " . $iassign_iLM_security->iassign_statementid . " ; " . $iassign_iLM_security->userid . " ; "  . $iassign_iLM_security->timecreated . " ; " .  $iassign_iLM_security->view . " ; " . $iassign_iLM_security->file . "<br/>\n"   }
    $DB->delete_records("iassign_security", array("userid" => $USER->id, "iassign_statementid" => $iassign_statementid));
    //D $iassign_iLM_security = $DB->get_record("iassign_security", array("iassign_statementid" => $iassign_statementid));
    //D if ($iassign_iLM_security) foreach ($iassign_iLM_security as $item) { echo $iassign_iLM_security->id . " ; " . $iassign_iLM_security->iassign_statementid . " ; " . $iassign_iLM_security->userid . " ; "  . $iassign_iLM_security->timecreated . " ; " .  $iassign_iLM_security->view . " ; " . $iassign_iLM_security->file . "<br/>\n"   }
    //D else echo "Apagou!<br/>";

    die();
    } // function view()


  /// Execute the action from Moodle (move, make visible, register the exercise (teacher) or its answer (learner)...)
  function action () {
    global $USER;
    // action:
    // up - move up activity (mover atividade para cima)
    // down - move down activity (mover atividade para baixo)
    // visible - view/hide activity (exibir/ocultar atividade)
    // delete - delete activity (excluir atividade)
    // deleteyes - confirms exclusion of activity (confirm the activity exclusion)
    // deleteno - does not erase activity (do not erase activity)
    // add - add activity (adicionar atividade)
    // edit - edit activity (modificar atividade)

    $action_iassign = array(
      'newcomment' => '$this->add_comment();',
      'view' => '$this->view_iassign_current();',
      'get_answer' => '$this->get_answer();',
      'repeat' => '$this->view_iassign_current();',
      'overwrite' => '$this->get_answer();',
      'stats_student' => '$this->stats_students();',
      'download_answer' => '$this->export_file_answer();',
      'download_all_answer' => '$this->export_package_answer();',
      'notifications' => '$this->view_notifications();');

    $action_iassign_limit = array(
      'view' => '$this->view_iassign_current();',
      //'newcomment' => '$this->get_answer();',
      'viewsubmission' => '$this->view_iassign_current();',
      'edit_status' => '$this->edit_status();',
      'edit_grade' => '$this->edit_grade();',
      'export_csv' => '$this->export_csv();',
      'report' => '$this->report();',
      'print' => '$this->report();',
      'stats' => '$this->stats();',
      'printstats' => '$this->stats();');

    $restricted = array('firstdown' => '$this->activity->move_iassign("firstdown", $this->iassign_down, $this->return);',
      'up' => '$this->activity->move_iassign("up", $this->iassign_up, $this->return);',
      'down' => '$this->activity->move_iassign("down", $this->iassign_down, $this->return);',
      'visible' => '$this->activity->visible_iassign($this->return);',
      'delete' => '$this->activity->delete($this->return);',
      'deleteno' => '$this->return_home_course("confirm_not_delete_iassign");',
      'deleteyes' => '$this->activity->deleteyes($this->return, $this);',
      'add' => '$this->add_edit_iassign();',
      'edit' => '$this->add_edit_iassign();',
      'get_answer' => '$this->get_answer();',
      'duplicate_activity' => '$this->duplicate_activity();',
      'move_activity' => '$this->move_activity();',
      'auto_evaluate' => '$this->auto_evaluate();',
      'all_submissions' => '$this->all_submissions();',
      'delete_submissions' => '$this->delete_submissions();',
      'download_submissions' => '$this->download_submissions();',
      'open_individual_submission' => '$this->open_individual_submission();',
      'get_individual_submission' => '$this->get_individual_submission();',
      'get_student_submission' => '$this->get_student_submission();',
      'get_teacher_exercise' => '$this->get_teacher_exercise();',
      'post_auto_eval_result' => '$this->post_auto_eval_result();',
      'import_activity' => '$this->import_activity();',
      'import_activity_confirm' => '$this->import_activity_confirm();'
    );

    $action_iassign_restricted = array_merge($restricted, $action_iassign_limit, $action_iassign);

    $action = $this->action;
    $array_teacher = array("edit", "delete", "visible", "firstdown", "up", "down", "duplicate_activity");
    if (in_array($action, $array_teacher) && !has_capability('mod/iassign:editiassign', $this->context, $USER->id)) { //TODO block when editing (probably block must be in "add_edit_iassign()"
      // Invalid access!!! Perhaps the message 'invalidsection'='Invalid section.' is more explainable?
      // Capabilities 'mod/iassign:editiassign' and 'mod/iassign:editiassign' are similar, the first one is more permissive
      // allowing 'teacher' (the second starts with 'editingteacher').
      // redirect(new moodle_url('/admin/index.php'));
      print_error('accessdenied', 'admin', $CFG->wwwroot . "/" . $CFG->admin); // ./lang/en/admin.php: "Access denied"
      exit; // additional security...
      // $this->userid_iassign = $USER->id;
      }

    // On depends the user's capability: precedence to edit power ('editiassign'); after to analyse ('evaluateiassign'); otherwise only view (any other).
    if (has_capability('mod/iassign:editiassign', $this->context, $USER->id)) {
      // When teacher is seeing student solution. By 'viewsubmission' goes to the function 'view_iassign_current()'
      eval($action_iassign_restricted[$this->action]); // Now load 'view_iassign_current()' with 'viewsubmission' or 'new_iassign($param)' by '$this->add_edit_iassign()'
      }
    elseif (has_capability('mod/iassign:evaluateiassign', $this->context, $USER->id)) {
      eval($action_iassign_limit[$this->action]);
      }
    else { // Student reaches this point
      // When student do/redo activity: do => action = "view"; redo => action = "repeat"
      // Arrive here with: "get_answer"; "view"
      if (isset($action_iassign[$this->action])) // avoid error if non capable user try direct acess
        eval($action_iassign[$this->action]); // Now load 'view_iassign_current()' with 'view'
      }

    } // function action()


//2022: ---------------------------------------------------------------------------------------------------------------------------------
//2022: Recuperacao {files} {iassign_statement}
//2022/02 see_all_files_context($context, $DB)

  /// Temporary: fix 'iassign_statement.filesid' and 'files.itemid' (iassign_statement.filesid <- files.id; files.itemid <- iassign_statement.id)
  //  First version of iAssign used a random value to associate 'files.itemid' with 'iassign_statement.file'
  //  @calledby locallib.php ! get_update_files($context_id, $component, $filearea, $iassign_statement)
  static function update_iassign_statement_files ($iassign_statement_id, $context_id, $component, $filearea, $files_itemid) {
    global $DB; //2022: echo "update_iassign_statement_files:<br/>";//D2022
    // $files = $DB->get_records("files", array("component" => "mod_iassign", "filearea" => "exercise", "contextid" => $context_id, "itemid" => $files_itemid)); // contextid=1390 ; itemid=400 ;
    // $sql_query = "SELECT * FROM {files} WHERE component='mod_iassign' AND filearea='exercise' AND contextid=" . $context_id . " AND filename<>'.' AND itemid=" . $files_itemid;
    $sql_query = "SELECT * FROM {files} WHERE component='mod_iassign' AND filearea='exercise' AND contextid=" . $context_id . " AND itemid=" . $files_itemid;
    $elem_in_files = $DB->get_records_sql($sql_query);
    if (!$elem_in_files) {
      print "Vazio! <br/>"; //TODO: se mantiver, internacionalizar!
      return NULL;
      }
    // if (count($elem_in_files)>1) { echo "Problema! Mais de um arquivo!<br/>"; return NULL; }
    //2022: echo "#files=" . count($elem_in_files) . ": "; // print_r($elem_in_files); echo "<br/>";//D2022
    foreach ($elem_in_files as $element_file) { // since ID is greater than 0, only "$file1 = $elem_in_files[0]" will not work!
      if ($element_file->filename != '.') { // If not '.' is the actual file
        $file1 = $element_file; // this '$file1' has the 'hashcontent' of the real file content, then it will be used to update 'iassign_statement.filesid' <- 'files.id'
        }
      // Update 'files.itemid' with 'iassign_statement.id'
      $newentry = new stdClass();
      $newentry->id = $element_file->id; $newentry->itemid = $iassign_statement_id;
      //2022: if (!$DB->update_record('files', $newentry)) print_error('error_update', 'iassign');
      }
    // Update 'iassign_statement.filesid' with 'files.id'
    $newentry = new stdClass();
    $newentry->id = $iassign_statement_id; $newentry->filesid = $file1->id;

    //2022: if (!$DB->update_record('iassign_statement', $newentry)) print_error('error_update', 'iassign');
    } // static function update_iassign_statement_files($iassign_statement_id, $context_id, $component, $filearea, $files_itemid)


//TODO
//TODO Remover funcao 'get_update_files(.)' em favor do 'files_functions.php!update_iassignstatement_files(.)' ???????????
//TODO
  /// Get file from table 'files', if 'files.itemid != iassign_statement.id' then update 'files.itemid' with 'iassign_statement.id'
  //  Usually 'context.instanceid' is 'course_modules.id' and 'course_modules.instance = iassign.id'
  //  However since iAssign is a "block of actual activities" from "iassign_statement", we store in 'files.itemid' the 'iassign_statement.id'
  //  @calledby function add_edit_iassign(): if ($this->action == 'edit'): to edit iAssign activity with "$files_list = iassign::get_update_files(...)"  [2721/9504]
  static function get_update_files ($context_id, $component, $filearea, $iassign_statement, $fs) {
    // $fs is the reference to all files in Moodle data
    echo "<br/><br/><br/><br/><br/>get_update_files: iassign_statement: " . $context_id . ", " . $component . ", " . $filearea . ", " . 
      "id=" . $iassign_statement->id . ", file=" . $iassign_statement->file . ", filesid=" . $iassign_statement->filesid .
      ", iassign_ilmid =" . $iassign_statement->iassign_ilmid . ",name=" . $iassign_statement->name . "<br/>\n";//D2022
    $msgAux = "";
    $must_update_files_itemid = 0; // if changed to 1 => must update {files}.itemid; depois muda para $iassign_statement->filesid
    // To consider old versions with improper association 'files.itemid': try 'iassign_statement.id' (correct one), 'iassign_statement.file' and 'iassign_statement.filesid'
    // Correct association: {files}->itemid = $iassign_statement->id and {files}->id=$iassign_statement->fileid;
    $files = $fs->get_area_files($context_id, $component, $filearea, $iassign_statement->id); // try: 'iassign_statement.id'
    if (!$files) { // Could not find with 'iassign_statement.id'
      $msgAux .= "- Nao com 'iassign_statement.id'<br/>\n";
      $must_update_files_itemid = 1; // {files}.itemid must receive {iassign_statement}.id
      $files = $fs->get_area_files($context_id, $component, $filearea, $iassign_statement->file); // try with 'iassign_statement.file'
      if (!$files) { // Could not find with 'iassign_statement.file'
        $msgAux .= "- Nao com 'iassign_statement.file'<br/>\n";
        $files = $fs->get_area_files($context_id, $component, $filearea, $iassign_statement->filesid); // try with 'iassign_statement.filesid'
        if (!$files) { // Could not find with 'iassign_statement.file'
          print "ERROR {files} not found: " . $msgAux; //D2022
          return NULL;
          }
        else { $must_update_files_itemid = $iassign_statement->filesid; echo "OK 3, nao com 'id', nem com 'file', mas pegou com 'iassign_statement.filesid'=" . $iassign_statement->filesid . "<br/>\n"; }
        }
      else { $must_update_files_itemid = $iassign_statement->file; echo "OK 2, nao com 'id', pegou com 'iassign_statement.file'=" . $iassign_statement->file . "<br/>\n"; }
      }
    else echo "OK 1, pegou com 'iassign_statement.id'=" . $iassign_statement->id . "<br/>\n";//D2022
    if ($must_update_files_itemid!=0) {
      $res_update = iassign::update_iassign_statement_files($iassign_statement->id, $context_id, $component, $filearea, $must_update_files_itemid);
      }
    foreach ($files as $file) { if ($file->get_filename() != '.') break; }
    //$files_itemid = $file->get_itemid(); // /var/www/html/saw2021_1/lib/filestorage/stored_file.php: devolve 'files.itemid'
    //$files_id = $file->get_id(); // file_record
    echo "files[.]: id=" . $files_id . ", itemid=" . $files_itemid . "<br/>\n"; //D2022
    return $file;
    } // function get_update_files($context_id, $component, $filearea, $iassign_statement)
//2022: Recuperacao {files} {iassign_statement}
//2022: ---------------------------------------------------------------------------------------------------------------------------------


  /// This method prints the student submission to a specific exercise
  function get_student_submission () {
    global $DB;
    $student = optional_param('iassign_student', NULL, PARAM_TEXT);
    $exercise = optional_param('iassign_exercise', NULL, PARAM_TEXT);
    if (!$student || !$exercise) exit;

    $iassign_submission = $DB->get_record("iassign_submission", array("iassign_statementid" => $exercise, "userid" => $student));

    if ($iassign_submission)
      print($iassign_submission->answer);

    die();
    }


  /// This method prints the teacher exercise
  //  @calledby AJAX trhough "function action()" that calls "function auto_evaluate()"
  function get_teacher_exercise () {
    global $DB, $COURSE;

    $ilmid = optional_param('iassign_exercise', NULL, PARAM_INT);

    $iassign_statement = $DB->get_record("iassign_statement", array("id" => $ilmid));
    $fs = get_file_storage(); // Get reference to all files in Moodle data

    //2021/12: $files = $fs->get_area_files(context_module::instance($this->cm->id)->id, 'mod_iassign', 'exercise', $iassign_statement->filesid);
    //TODO 2022: trocar "$iassign_statement->id" por "$iassign_statement->file"???
    // ./lib/filestorage/file_storage.php: public function get_area_files($contextid, $component, $filearea, $itemid = false,...)
    $files = $fs->get_area_files(context_module::instance($this->cm->id)->id, 'mod_iassign', 'exercise', $iassign_statement->id); // use itemid=$iassign_statement->id
    if (!$files) { // try with 'iassign_statement->filesid'
      $files = $fs->get_area_files(context_module::instance($this->cm->id)->id, 'mod_iassign', 'exercise', $iassign_statement->filesid);
      }

    foreach ($files as $file) {
      if ($file->get_filename() != ".") {
        print($file->get_content());
        }
      }

    die();
    }

  /// This method receives the data from auto_eval event
  /// @see auto_evaluate()
  function post_auto_eval_result () {
    global $COURSE, $CFG, $USER, $DB;

    if (!has_capability('mod/iassign:runautoevaluate',  $USER->context, $USER->id))
      exit;

    //$exercise="+exercises[actual_exercise]+"&student="+students[actual_student]+"&grade="+(grade * actual_value),
    $exercise = optional_param('iassign_exercise', -1, PARAM_INT);
    $student  = optional_param('student', -1, PARAM_INT);
    $grade  = optional_param('grade', 0, PARAM_FLOAT);

    // 1: Recuperar a nota atual:
    $actual = $DB->get_record('iassign_submission', array('iassign_statementid' => $exercise, 'userid' => $student));

    // 2: Atualizar a tabela:
    $update_entry = new stdClass();
    $update_entry->id = $actual->id;
    $update_entry->previous_grade = $actual->grade;
    $update_entry->grade = $grade;

    // Update the iassign_submission
    $DB->update_record("iassign_submission", $update_entry);
    }


  /// Function to help download all_submissions files
  function download_submissions_help ($list) {
    global $DB, $CFG, $OUTPUT;

    // Get list of iLMs
    $ilm_list = $DB->get_records("iassign_ilm");

    // Get list of enrolled students:
    $params_temp = array('shortname' => 'student');
    $role = $DB->get_record_sql("SELECT s.id, s.shortname FROM {role} s WHERE s.shortname = :shortname", $params_temp);
    $context = context_course::instance($this->course->id);
    $params_temp = array('contextid' => $context->id, 'roleid' => $role->id);
    $students_list = $DB->get_records_sql(
      "SELECT s.userid, a.firstname, a.lastname FROM {role_assignments} s, {user} a WHERE s.contextid = :contextid AND s.userid = a.id AND s.roleid = :roleid ORDER BY a.firstname ASC,a.lastname ASC", $params_temp);

    $json_obj = new stdclass();
    $json_obj->submissions = [];

    $csvfields = [];
    $csvfields[] = 'submissionid';
    $csvfields[] = 'file';
    $csvfields[] = 'filesize';
    $csvfields[] = 'timestamp';
    $csvfields[] = 'humandate';
    $csvfields[] = 'grade';
    $csvfields[] = 'userid';
    $csvfields[] = 'exerciseid';
    $json_obj->submissions[] = $csvfields;

    $exercise_dir = [];
    $exercises_list = []; 
    foreach ($list as $item) {

      if (!in_array($item->iassign_statementid, $exercise_dir)) {
        $temp = $DB->get_record("iassign_statement", array("id" => $item->iassign_statementid));
        $exercises_list[] = $temp;
        $exercise_dir[] = $temp->id;
        }
      }

    $student_dir = [];
    $students_list = [];
    foreach ($list as $item) {

      if (!in_array($item->userid, $student_dir)) {
        $temp = $DB->get_record("user", array("id" => $item->userid));
        $students_list[] = $temp;
        $student_dir[] = $temp->id;
        }
      }

    //2021 $directory = $CFG->dataroot . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR;
    //TODO: tem que criar moodledata/temp/iassign_files
    // Create $CFG->dataroot/temp/iassign_files

    $tempfilespath = $CFG->dataroot . DIRECTORY_SEPARATOR . 'temp';
    if (!file_exists($tempfilespath)) {
      mkdir($tempfilespath, 0777, true);
      }
    $iassignfilespath = $tempfilespath . DIRECTORY_SEPARATOR . 'iassign_files';
    if (!file_exists($iassignfilespath)) {
      mkdir($iassignfilespath, 0777, true);
      }

    $directory = $CFG->dataroot . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'iassign_files' . DIRECTORY_SEPARATOR;
    
    if (!file_exists($directory)) {
      // print 'Não foi possível criar o diretório para preparar o arquivo com as submissões. Possivelmente o caminho está bloqueado para escrita.';
      print 'It was not possible to create the directory to receive the students submission files. Possibly the path has no write permission.';
      exit;
      }

    $zip_filename = $directory . 'package_iassign_' . time() . '.zip';
    $zip = new zip_archive(); // create ZIP
    if (!$zip->open($zip_filename)) {
      // print 'Não foi possível criar o arquivo zip com as submissões dos alunos, pois o diretório no servidor está bloqueado para escrita: ' . $directory;
      print 'It was not possible to create the ZIP file with the students submission files. Possibly the server directory has no write permission: ' . $directory;
      exit;
      }

    foreach ($exercises_list as $exercise) {
      $dir_ex = iassign_utils::format_filename($exercise->name);

      $zip->add_directory($dir_ex);

      $extension_ilm = '';
      foreach ($ilm_list as $ilm) {
        if ($exercise->iassign_ilmid == $ilm->id) {
          $extension_ilm = $ilm->extension;
          break;
          }
        }

      foreach ($students_list as $student) {
        $individual_dir = 
          $dir_ex . DIRECTORY_SEPARATOR .
          iassign_utils::format_filename($student->firstname . ' ' . $student->lastname);

        $zip->add_directory($individual_dir);

        $i = 1;
        foreach ($list as $item) {
          if ($item->userid == $student->id  && $item->iassign_statementid == $exercise->id) {
            $date = new DateTime();
            $date->setTimestamp($item->timecreated);

            $new_file = $individual_dir . DIRECTORY_SEPARATOR . iassign_utils::format_filename('submission_' . ($i++) . '_' . date_format($date, 'Y-m-d_H-i-s')) . '.' . $extension_ilm;

            $zip->add_file_from_string($new_file, $item->answer);

            $temp_json = [];
            $temp_json[] = intval($item->id);
            $temp_json[] = $new_file;
            $temp_json[] = strlen($new_file);
            $temp_json[] = intval($item->timecreated);
            $temp_json[] = date_format($date, 'Y-m-d H-i-s');
            $temp_json[] = doubleval($item->grade);
            $temp_json[] = intval($item->userid);
            $temp_json[] = intval($exercise->id);

            $json_obj->submissions[] = $temp_json;
            }
          }
        }
      }
    $csv_str = "";
    foreach ($json_obj->submissions as $sub) {
      $csv_str .= $this->csvstr($sub) . "\n";
      }
    $zip->add_file_from_string('index.csv', $csv_str);
    $zip->close();
    iassign_utils::download_file($zip_filename);
    exit;
    }


  function csvstr (array $fields) : string {
    $f = fopen('php://memory', 'r+');
    if (fputcsv($f, $fields) === false) {
      return false;
      }
    rewind($f);
    $csv_line = stream_get_contents($f);
    return rtrim($csv_line);
    }


  /// Download submissions from table iassign_allsubmissions
  function download_submissions () {
    global $COURSE, $CFG, $USER, $DB, $OUTPUT, $PAGE;

    if (!has_capability('mod/iassign:runautoevaluate', $this->context, $USER->id))
      exit;

    $type = optional_param('type', NULL, PARAM_TEXT);
    $item = optional_param('item', NULL, PARAM_TEXT);
    $exercise = optional_param('exercise', NULL, PARAM_TEXT);
    $iassign_id = optional_param('iassign', NULL, PARAM_TEXT);

    $sub_list = [];

    if ($type == 'individual') {
      $sub_list[] = $DB->get_record(
        'iassign_allsubmissions', array('id' => $item));
    } elseif ($type == 'student') {
      $sub_list = $DB->get_records(
          'iassign_allsubmissions', array('userid' => $item, 'iassign_statementid' => $exercise));
    } elseif ($type == 'exercise') {
      $sub_list = $DB->get_records(
        'iassign_allsubmissions', array('iassign_statementid' => $item));
    } elseif ($type == 'block') {
      $exercises_in_block = $DB->get_records(
        'iassign_statement', array('iassignid' => $iassign_id));
      foreach ($exercises_in_block as $exercise) {
        $temp = $DB->get_records(
          'iassign_allsubmissions', array('iassign_statementid' => $exercise->id));
        $sub_list = array_merge($sub_list, $temp);
        }
      }
    $this->download_submissions_help($sub_list);
    }

  /// Delete submissions in table iassign_allsubmissions
  function delete_submissions () {
    global $COURSE, $CFG, $USER, $DB, $OUTPUT, $PAGE;

    if (!has_capability('mod/iassign:runautoevaluate', $this->context, $USER->id))
      exit;

    $type = optional_param('type', NULL, PARAM_TEXT);
    $item = optional_param('item', NULL, PARAM_TEXT);
    $exercise = optional_param('exercise', NULL, PARAM_TEXT);

    if ($type == 'individual') {
      $DB->delete_records('iassign_allsubmissions', array('id' => $item));
    } elseif ($type == 'student') {
      $DB->delete_records('iassign_allsubmissions', array('userid' => $item, 'iassign_statementid' => $exercise));
    } elseif ($type == 'exercise') {
      $DB->delete_records('iassign_allsubmissions', array('iassign_statementid' => $item));
      }
    }


  function get_individual_submission () {
    global $DB, $USER;

    if (!has_capability('mod/iassign:runautoevaluate', $this->context, $USER->id))
      exit;

    $submissionid = optional_param('submissionid', NULL, PARAM_TEXT);
    $submission = $DB->get_record("iassign_allsubmissions", array("id" => $submissionid));
    print $submission->answer;
    }


  /// Open individual submission
  function open_individual_submission () {
    global $DB, $USER, $CFG;

    if (!has_capability('mod/iassign:runautoevaluate', $this->context, $USER->id))
      exit;

    $submissionid = optional_param('item', NULL, PARAM_TEXT);
    $id = $this->cm->id;
    $param_aux = "id=" . $id . "&userid_iassign=" . $USER->id;
    $url = "" . $CFG->wwwroot . "/mod/iassign/view.php?action=get_individual_submission&" . $param_aux . "&submissionid=" . $submissionid;

    $atual_submission = $DB->get_record('iassign_allsubmissions', array('id' => $submissionid));
    $atual_statement = $DB->get_record("iassign_statement", array("id" => $atual_submission->iassign_statementid));
    $all_submissions = $DB->get_records('iassign_allsubmissions', array('iassign_statementid' => $atual_submission->iassign_statementid, 'userid' => $atual_submission->userid));

    $user = $DB->get_record('user', array('id' => $atual_submission->userid));

    $previous = null;
    $next = null;
    $i = 0;
    $found = false;
    foreach($all_submissions as $individual) {
      if (!$found && $individual->id != $submissionid) {
        $previous = $individual;
        }
      if ($found) {
        $next = $individual;
        break;
        }
      if ($individual->id === $submissionid) {
        $found = true;
        }
      $i++;
      }
    print '<center>' . "\n";
    print '<table cellpading=5 cellspacing=5 style="padding-bottom: 2rem;font-size: 1.1em;"><tr><td>' . get_string('exercise', 'iassign') . ':</td><td>' . $atual_statement->name . '</td></tr>' . "\n";
    print '<tr><td>' . get_string('student', 'iassign') . '</td><td>' . $user->firstname . ' ' . $user->lastname . '</td></tr>' . "\n";
    print '<tr><td>' . get_string('grade_student', 'iassign') . ':</td><td>' . $atual_submission->grade . '</td></tr>' . "\n";
    print '<tr><td>' . get_string('all_submissions_manager_date', 'iassign') . ':</td><td>' . userdate($atual_submission->timecreated) . '</td></tr>' . "\n";

    print '</table>' . "\n";

    if ($previous) {
      $url_prev = "" . $CFG->wwwroot . "/mod/iassign/view.php?action=open_individual_submission&" . $param_aux . "&item=" . $previous->id;
      print '<a href="' . $url_prev . '" style="color: blue; text-decoration: none;margin: 1rem;"><span>&#9664;</span> ' . get_string('all_submissions_manager_previous', 'iassign') . '</a> ';
    } else {
      print '<a href="#" style="color: gray; text-decoration: none;margin: 1rem;"><span>&#9664;</span> ' . get_string('all_submissions_manager_previous', 'iassign') . '</a> ';
      }
    print ' (' . $i . '/' . count($all_submissions) . ')';
    if ($next) {
      $url_next = "" . $CFG->wwwroot . "/mod/iassign/view.php?action=open_individual_submission&" . $param_aux . "&item=" . $next->id;
      print '<a href="' . $url_next . '" style="color: blue; text-decoration: none;margin: 1rem;">' . get_string('all_submissions_manager_next', 'iassign') . ' <span>&#9654;</span></a>' . "\n";
    } else {
      print '<a href="#" style="color: gray; text-decoration: none;margin: 1rem;">' . get_string('all_submissions_manager_next', 'iassign') . ' <span>&#9654;</span></a>' . "\n";
      }
    $statement = $DB->get_record('iassign_statement', array('id' => $atual_submission->iassign_statementid));

    //array("type" => "editor_new", "notSEND" => "true")
    print ilm_settings::build_ilm_tags($statement->iassign_ilmid, ['type' => "activity", 'Proposition' => $url, "notSEND" => "false"]);
    print '</center>' . "\n";
    } // function open_individual_submission()


  //2021/12 ---------------------------------------------- start 1 (to import activities)
  // To import activities
  function import_activity_confirm () {
    global $USER, $DB;

    if (!has_capability('mod/iassign:runautoevaluate', $this->context, $USER->id)) // security
      exit;
    if (!isset($_REQUEST['statements'])) // security
      exit;

    $iassign_destiny = $this->iassign->id;
    $activities_list = $_REQUEST['statements'];

    foreach ($activities_list as $activity) {
      $activity_source = $DB->get_record('iassign_statement', array('id' => $activity));

      $activity_source->id = 0;

      $iassign_id_from = $activity_source->iassignid;
      $filesid_from = $activity_source->filesid;

      $activity_source->iassignid = $iassign_destiny;
      $author = $USER; //x $DB->get_record("user", array("id" => $USER->id)); --- useless since we already have {user} info.!!!
      $activity_source->author_modified_name = $author->firstname . ' ' . $author->lastname . ' (' . $author->id . ')';
      $activity_source->author_modified = $activity_source->author_modified_name;
      
      if ($id_ = $DB->insert_record("iassign_statement", $activity_source)) {
        $fs = get_file_storage();
        // Duplicate activity file
        $files_ids = $DB->get_records_sql('SELECT id FROM {files} WHERE component="mod_iassign" AND itemid=' . $filesid_from);

        print("Files to be copied (ID): "); //TODO: universalizar 'Arquivos a serem copiados: '
        print_r($files_ids); //
        
        foreach ($files_ids as $fileid) {
          $source_file = $fs->get_file_by_id($fileid->id);

          // ./lib/filestorage/file_storage.php : "public function create_file_from_storedfile($filerecord, $fileorid)" 1051/2412 on Moodle 2018120302.08
          $newfile = $fs->create_file_from_storedfile(array('contextid' =>  $this->context->id, 'component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $id_), $source_file);

          $updateentry = new stdClass();
          $updateentry->id = $id_;
          $updateentry->filesid = $id_;

          // Update the duplicated iLM iAssign with new file id
          $DB->update_record("iassign_statement", $updateentry);
          }
        }
      } // foreach ($activities_list as $activity)

    $destiny_url = new moodle_url('/mod/iassign/view.php', array('id' => $this->context->id));

    $str_index = count($activities_list) > 1 ? 'import_activities_success' : 'import_activity_success';

    redirect($this->return, get_string($str_index, 'iassign'), null, \core\output\notification::NOTIFY_SUCCESS);
    exit;
    } // function import_activity_confirm()


  function import_activity () {
    global $COURSE, $CFG, $USER, $DB, $OUTPUT, $PAGE;

    if (!has_capability('mod/iassign:runautoevaluate', $this->context, $USER->id))
      exit;

    print '<script>
    function verify_selection () {
      document.getElementById("msg-error").classList.add("d-none");

      var checkboxes = document.getElementsByName("statements[]");
      for (var i = 0; i < checkboxes.length; i++)
        if (checkboxes[i].checked) return true;
        
      document.getElementById("msg-error").classList.remove("d-none");
      return false;
      }
    </script>'; // "

    $destiny_url = new moodle_url('/mod/iassign/view.php', array('id' => $this->cm->id, 'action' => 'import_activity_confirm'));

    print $OUTPUT->header();
    $this->print_main_menu();

    print '<h2 class="m-b-2 mt-4">' . get_string('import_activity', 'iassign') . '</h2>' . "\n";
    print '<form action="' . $destiny_url . '" method="post" onsubmit="return verify_selection()">' . "\n";

    $courses_as_teacher = $this->get_courses_enroled_as_teacher($USER->id);

    foreach ($courses_as_teacher as $course) {
      print '<a href="#item-' . $course->id . '" class="list-group-item list-group-item-action h5" style="margin-top: 1rem;" data-toggle="collapse" onclick=\'$(".fa-caret-right, .fa-caret-down", this).toggleClass("fa-caret-right").toggleClass("fa-caret-down");\' > <i class="fa fa-caret-right"></i> ' . $course->fullname . ' </a>' . "\n";
      print '<div class="collapse" style="padding: 0 1.5rem;" id="item-' . $course->id . '">' . "\n";

      $iassigns = $this->get_iassign_items_by_course($course->id);

      if ($iassigns) {
        foreach ($iassigns as $iassign) {
          print '<a href="#item-' . $course->id . '-' . $iassign->id . '" class="list-group-item list-group-item-action" data-toggle="collapse" onclick=\'$(".fa-caret-right, .fa-caret-down", this).toggleClass("fa-caret-right").toggleClass("fa-caret-down");\'><i class="fa fa-caret-right"></i> ' . $iassign->name . '</a>' . "\n";
          print '<div class="collapse" style="padding: 0 1.5rem;" id="item-' . $course->id . '-' . $iassign->id . '">' . "\n";

          $statements = $this->get_iassign_statement_by_iassign($iassign->id);

          print '<table class="table table-hover">' . "\n";

          if ($statements) {
            foreach ($statements as $statement) {
              print '<tr><td> <div class="form-check"> <input class="form-check-input" name="statements[]" type="checkbox" value="' . $statement->id . '" id="statement-' . $statement->id . '">' . "\n";
              print '<label class="form-check-label w-100" for="statement-' . $statement->id . '">' . $statement->name;
              print '</label></div></td></tr>' . "\n";
              }
            }
          else {
            print '<tr><td>' . get_string('no_activity', 'iassign') . '</td></tr>' . "\n";
            }
          print '</table></div>' . "\n";
          }
        }
      else {
        print '<div class="p-2">' . get_string('import_statement_empty', 'iassign') . '</div>' . "\n";
        }
      print '</div>' . "\n";
      }
    print '<div class="text-center"><button type="submit" class="btn btn-primary mt-2">Confirmar</button></div>' . "\n";
    print '<div class="text-center text-danger d-none" id="msg-error">' . get_string('import_statement_no_exercise', 'iassign') . '</div>' . "\n";

    print "</form>\n";

    print $OUTPUT->footer();
    } // function post_auto_eval_result()

  function get_iassign_items_by_course ($courseid) {
    global $DB;
    return $DB->get_records("iassign", array("course" => $courseid));
    }

  function get_iassign_statement_by_iassign ($iassignid) {
    global $DB;
    return $DB->get_records("iassign_statement", array("iassignid" => $iassignid));
    }

  function get_courses_enroled_as_teacher ($userid) {
    $all_enroled = enrol_get_users_courses($userid);
    $return = array();
    foreach ($all_enroled as $enroled) {
      $context_course = context_course::instance($enroled->id);
      if (has_capability('mod/iassign:runautoevaluate', $context_course, $userid, false) ) {
        array_push($return, $enroled);
        }
      }
    return $return;
    }
  //2021/12 ---------------------------------------------- end 1 (to import activities)


  /// Manage all submissions in exercises to table iassign_allsubmissions
  function all_submissions () {
    global $COURSE, $CFG, $USER, $DB, $OUTPUT, $PAGE;

    if (!has_capability('mod/iassign:runautoevaluate', $this->context, $USER->id))
      exit;

    print $OUTPUT->header();

    $this->print_main_menu();

    $id = $this->cm->id;
    $param_aux = "id=" . $id . "&userid_iassign=" . $USER->id;
    $enderecoPOST = "" . $CFG->wwwroot . "/mod/iassign/view.php?" . $param_aux;

    print '
    <div class="modal" id="modalConfirm" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLongTitle">' . get_string('delete', 'iassign') . '</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">' . get_string('all_submissions_manager_confirm', 'iassign') . '</div>
          <div class="modal-footer">
            <button type="button" onclick="confirm_delete()" class="btn btn-primary">'.get_string('confirm', 'iassign').'</button>
            <button type="button" class="btn btn-secondary" data-dismiss="modal">'.get_string('cancel', 'iassign').'</button>
          </div>
        </div>
      </div>
    </div>'; // "

    print '
        <script>
          var sub_type;
          var sub_item;
          var sub_el;
          var sub_exercise;

          function delete_submission (type, item, el, exercise = 0) {
            sub_type = type;
            sub_item = item;
            sub_exercise = exercise;
            sub_el = el.closest("tr");
            if (!sub_el) sub_el = el.closest("a");
            $("#modalConfirm").modal();
            }
          function confirm_delete () {
            var url = "'.$enderecoPOST.'&action=delete_submissions";
            var xhttp = new XMLHttpRequest();
            xhttp.open("GET", url + "&type=" + sub_type + "&item=" + sub_item + "&exercise=" + sub_exercise, true);
            xhttp.send();

            $("#modalConfirm").modal("hide");
            if ($(sub_el).attr("href"))
              $($(sub_el).attr("href")).remove();
            if (sub_el) sub_el.remove();
            }
          function download_submission(type, item, exercise = 0, iassign = 0) {
            var url = "'.$enderecoPOST.'&action=download_submissions";
            url += "&type=" + type + "&item=" + item + "&exercise=" + exercise + "&iassign=" + iassign;
            document.location = url;
            }
          function open_submission(item) {
            var url = "'.$enderecoPOST.'&action=open_individual_submission&item=" + item;
            var poupup = window.open(url, "popup", "width=900,height=900");
            }
        </script>' . chr(13); // '

    // Get list of exercises in the instance:
    $iassignid = optional_param('iassignid', NULL, PARAM_TEXT);
    $exercises_list = $DB->get_records("iassign_statement", array("iassignid" => $iassignid));

    print '<h2 class="m-b-2 mt-5">' . get_string('all_submissions_manager', 'iassign') . '';
    print '<i onclick="download_submission(\'block\', 0, 0, ' . $iassignid . ')" class="fa fa-download" style="float: right; color: #1fa67a; font-size: 1.5rem; cursor: pointer; margin-left: 1rem; margin-top: .2rem;" aria-hidden="true"></i> </h2>' . "\n";

    // Get list of enrolled students:
    $params_temp = array('shortname' => 'student');
    $role = $DB->get_record_sql("SELECT s.id, s.shortname FROM {role} s WHERE s.shortname = :shortname", $params_temp);
    $context = context_course::instance($this->course->id);
    $params_temp = array('contextid' => $context->id, 'roleid' => $role->id);
    $students_list = $DB->get_records_sql(
      "SELECT s.userid, a.firstname, a.lastname FROM {role_assignments} s, {user} a " .  " WHERE s.contextid = :contextid AND s.userid = a.id AND s.roleid = :roleid " . " ORDER BY a.firstname ASC,a.lastname ASC", $params_temp);

    print '<div>';
    foreach ($exercises_list as $exercise) {
      $submissions_list = $DB->get_records("iassign_allsubmissions", array("iassign_statementid" => $exercise->id));
      $iassign_ilm = $DB->get_record("iassign_ilm", array("id" => $exercise->iassign_ilmid));

      print "<a href='#item-" . $exercise->id . "' class='list-group-item list-group-item-action h4' style='margin-top: 1rem;' data-toggle='collapse' onclick=\'$('.fa-caret-right, .fa-caret-down', this).toggleClass('fa-caret-right').toggleClass('fa-caret-down');\' >\n" 
        . " <i class='fa fa-caret-right'></i>\n" . $exercise->name . ' - ' . $iassign_ilm->name
        . "<i class='fa fa-times' onclick='event.stopPropagation(); delete_submission(\'exercise\', " . $exercise->id . ", this)' "
        . " style='color:#C62828; cursor: pointer; font-size: 1.5rem; float: right; margin-left: .5rem;' aria-hidden='true'></i>\n"
        . "<i onclick='event.stopPropagation(); download_submission(\'exercise\', " . $exercise->id . ")' class='fa fa-download' style='float: right; color: #1fa67a; font-size: 1.2rem; cursor: pointer; margin-left: 1rem; margin-top: .2rem;' aria-hidden='true'></i> \n"
        . "<span class='badge badge-primary badge-pill' style='float: right;'>" . count($submissions_list) . "</span></a>\n";
      print "<div class='collapse' style='padding: 0 1.5rem;' id='item-" . $exercise->id . "'>\n";

      foreach ($students_list as $student) { // total of student submission to this block of activities
        $total = 0;
        $student_submissions = [];
        foreach ($submissions_list as $submission) {
          if ($submission->userid == $student->userid) {
            $student_submissions[] = $submission;
            $total++;
            }
          }

        print "<a href='#item-" . $exercise->id . "-" . $student->userid . "' class='list-group-item list-group-item-action' data-toggle='collapse' onclick=\'$('.fa-caret-right, .fa-caret-down', this).toggleClass('fa-caret-right').toggleClass('fa-caret-down');\'>\n"
          . " <i class='fa fa-caret-right'></i> " . $student->firstname . " " . $student->lastname
          . "<i class='fa fa-times' onclick='event.stopPropagation(); delete_submission(\'student\', " . $student->userid . ", this, " . $exercise->id . ")' "
          . " style='color:#C62828; cursor: pointer; font-size: 1.5rem; float: right; margin-left: .5rem;' aria-hidden='true'></i>\n"
          . "<i onclick='event.stopPropagation(); download_submission(\'student\', " . $student->userid . ", " . $exercise->id . ")' class='fa fa-download' " 
          . " style='float: right; color: #1fa67a; font-size: 1.3rem; cursor: pointer; margin-left: 1rem; margin-top: .1rem;' aria-hidden='true'></i> "
          . "<span class='badge badge-primary badge-pill' style='float: right;'>" . $total . "</span></a>\n";
        print "<div class='collapse' style='padding-left: 5rem;padding-right: 5rem;' id='item-" . $exercise->id . "-" . $student->userid . "'>\n";
        print "<table class='table table-striped table-hover'><thead>
  <tr>
    <th scope='col'>#</th>
    <th scope='col'>" . get_string('all_submissions_manager_date', 'iassign') . "</th>
    <th scope='col'>" . get_string('grade_student', 'iassign') . "</th>
    <th scope='col'>" . get_string('config_param_actions', 'iassign') . "</th>
  </tr>
 </thead><tbody>\n";

        $i = 1;
        foreach ($student_submissions as $individual) {
          $date = new DateTime();
          $date->setTimestamp($individual->timecreated);
          print '<tr>';
          print '<td>' . ($i++) . '</td>';
          print '<td>' . date_format($date, 'd/m/Y H:i:s') . '</td>';
          print '<td>' . $individual->grade . '</td>';
          print "<td>
 <i onclick='open_submission(" . $individual->id . ")' class='fa fa-external-link' style='color:#1177d1; cursor: pointer; font-size: 1.2rem; margin-right: .5rem;' aria-hidden='true'></i>
            <i onclick='download_submission(\'individual\', " . $individual->id . ")' class='fa fa-download' style='color: #1fa67a; font-size: 1.2rem; cursor: pointer; margin-right: .5rem;' aria-hidden='true'></i> 
            <i class='fa fa-times' onclick='delete_submission(\'individual\', " . $individual->id . ", this)' style='color:#C62828; cursor: pointer; font-size: 1.5rem;' aria-hidden='true'></i></td>\n";
          print " </tr>\n";
          }
        print "</tbody></table>\n";
        //print '<a href="#" class="list-group-item list-group-item-action">Item 1.1.1</a>';
        print "</div>\n";
        }
      print "</div>\n";
      }
    print "</div>\n";

    print $OUTPUT->footer();
    } // function all_submissions()


  /// This method runs activities auto evaluation in teacher area
  function auto_evaluate () {
    global $COURSE, $CFG, $USER, $DB, $OUTPUT, $PAGE;

    if (!has_capability('mod/iassign:runautoevaluate',  $USER->context, $USER->id))
      exit;

    $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $PAGE->navbar->add(get_string('auto_evaluate_title', 'iassign'), $actual_link);

    // Get list of exercises in the instance:
    $iassignid = optional_param('iassignid', NULL, PARAM_TEXT);
    $exercises_list = $DB->get_records("iassign_statement", array("iassignid" => $iassignid));

    // Get list of enrolled students:
    $params_temp = array('shortname' => 'student');
    $role = $DB->get_record_sql("SELECT s.id, s.shortname FROM {role} s WHERE s.shortname = :shortname", $params_temp);
    $context = context_course::instance($this->course->id);
    $params_temp = array('contextid' => $context->id, 'roleid' => $role->id);
    $students_list = $DB->get_records_sql(
        "SELECT s.userid, a.firstname, a.lastname FROM {role_assignments} s, {user} a " .
        " WHERE s.contextid = :contextid AND s.userid = a.id AND s.roleid = :roleid " .
        " ORDER BY a.firstname ASC,a.lastname ASC", $params_temp);

    print $OUTPUT->header();

    $this->print_main_menu();

    print '<div class="mt-5 mb-4"><div>' . get_string('auto_evaluate_select', 'iassign') . '</div>';
    print '<ul style="list-style-type: none; margin: 1em;">';
    foreach ($exercises_list as $exercise) {
      $matches = [];
      preg_match('/[0-9]*\.?[0-9]*/', $exercise->name, $matches, PREG_OFFSET_CAPTURE);

      $iassign_ilm = $DB->get_record("iassign_ilm", array("id" => $exercise->iassign_ilmid));

      if ($iassign_ilm && $iassign_ilm->reevaluate == 1) {
        if ($matches[0][0]) {
          print '<li id="li-item-'.$exercise->id.'"><input data-iditem="'.$matches[0][0].'" data-exercise="'.$exercise->id.'" class="form-check-input" type="checkbox" id="ex_'.$exercise->id.'"><label id="label_ex_'.$exercise->id.'" for="ex_'.$exercise->id.'">' . $exercise->name . '</label></li>';
        } else {
          print '<li id="li-item-'.$exercise->id.'"><input data-iditem="('.$exercise->id.')" data-exercise="'.$exercise->id.'" class="form-check-input" type="checkbox" id="ex_'.$exercise->id.'"><label id="label_ex_'.$exercise->id.'" for="ex_'.$exercise->id.'">(' . $exercise->id . ') - ' . $exercise->name . '</label></li>';
          }
      } else {
        if ($matches[0][0]) {
          print '<li id="li-item-'.$exercise->id.'"> <i onmouseenter="$(\'#li-item-'.$exercise->id.'\').find(\'i\').tooltip(\'show\');" class="fa fa-times without-reevaluator" style="font-size: 1.1em;color: red;margin-bottom: .7em;margin-left: -1.3em;margin-right: .58em;" data-toggle="tooltip" data-original-title="'.get_string('auto_evaluate_missing', 'iassign').'"></i>' . $exercise->name . '</li>';
        } else {
          print '<li id="li-item-'.$exercise->id.'">  <i onmouseenter="$(\'#li-item-'.$exercise->id.'\').find(\'i\').tooltip(\'show\');" class="fa fa-times without-reevaluator" style="font-size: 1.1em;color: red;margin-bottom: .7em;margin-left: -1.3em;margin-right: .58em;" data-toggle="tooltip" data-original-title="'.get_string('auto_evaluate_missing', 'iassign').'"></i>(' . $exercise->id . ') - ' . $exercise->name . '</li>';
          }
        }
      }

    print '</ul>
      <center>
        <button class="btn btn-primary" onclick="start_evaluation()" id="button_start">'.get_string('auto_evaluate_start', 'iassign').'</button>
        <button class="btn btn-danger" disabled="disabled" onclick="stop_evaluation()" id="button_stop">'.get_string('auto_evaluate_stop', 'iassign').'</button>
        <div id="evaluate_message" style="color: red"></div>
        <div id="evaluate_alerts"></div>
        <div>
        <div id="progress_bar" class="progress" style="width: 40em; height: 30px; margin-top: 1em; display: none; text-align: left;">
          <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 1%; height: 100%;" aria-valuenow="0" aria-valuemin="1" aria-valuemax="100">1%</div>
        </div>
      </center>'; // close "

    print '   </div>' . "\n";
    print ' <script>' . "\n";
    print '// #submissions = ' . count($exercises_list) . "\n"; //Debug
    print '   var submissions = [';

    // Get list of student id have submit for each exercise:
    foreach ($exercises_list as $exercise) {
      $iassign_ilm = $DB->get_record("iassign_ilm", array("id" => $exercise->iassign_ilmid));

      print '
        {
          "exerciseid": "' . $exercise->id . '",
          "grade": ' . $exercise->grade . ',
          "ilm": "' . $CFG->wwwroot . '/mod/iassign/' . $iassign_ilm->file_jar . $iassign_ilm->file_class . '",
          "submited": ['; // "

      $params_temp = array('iassign_statementid' => $exercise->id);
      $submited = $DB->get_records_sql( // Get userid and his grade
        "SELECT s.userid, s.grade FROM {iassign_submission} s " .
        " WHERE s.iassign_statementid = :iassign_statementid", $params_temp);

      foreach ($submited as $ss) {
        print($ss->userid . ',');
        }
      print '],';
      print '"allgrades": [';
      foreach ($submited as $ss) {
        print( $ss->grade . ',');
        }
      print ']
        },'; // close "
      } // foreach ($exercises_list as $exercise)

    // Start of JavaScript code to auto-evaluation
    print "        ];\n";
    print '
    var total_submissions = 0;
    var selected_exercises;
    var count_steps;
    var value_by_step;
    var stoped = false;
    var time_interval = 5000;
    var var_interval;

    function add_hover_th (id_th, id_li) { //AJAX
      $("#" + id_th).hover(function() {
        $("#" + id_li).css("background-color", "#d9d8d8");
        $(this).css("background-color", "#d9d8d8");
      }, function() {
        $("#" + id_li).css("background-color", "");
        $(this).css("background-color", "");
        });
      }

    function start_evaluation () {
      stoped = false;
      //AJAX
      $("#progress_bar").css("display", "none");
      $("#evaluate_message").text("");
      $("#evaluate_alerts").text("");
      $(".added-col").remove();
      $(".progress-bar").addClass("progress-bar-animated progress-bar-striped").text("0%").css("width", "1%");

      selected_exercises = [];

      var c = 0;
      for (var i = 0; i < exercises.length; i++) {
        if ($("#ex_"+exercises[i]).is(":checked") == true) {
          selected_exercises[c++] = exercises[i];
          }
        }

      if (selected_exercises.length == 0) {
        $("#evaluate_message").text("'.get_string('auto_evaluate_select_msg', 'iassign').'");
        return;
      } else {
        $("input:checkbox").attr("disabled", true); //AJAX

        for (var i = 0; i < selected_exercises.length; i++) {
          var desc = $("#ex_" + selected_exercises[i]).data("iditem");
          //AJAX
          $("#cols-exercises").append( $("<th />", { class : "added-col text-center", text : desc, id : "th-item-"+selected_exercises[i] }));
          $("#table-results tbody tr").each(function(e) {
            var id_t = "std_" + $(this).data("student") + "_ex_" + selected_exercises[i];
            $(this).append( $("<td />", {class : "added-col text-center", id : id_t, text : "", data : {"student": $(this).data("student"), "exercise": selected_exercises[i]} }));
            });
          add_hover_th("th-item-"+selected_exercises[i], "li-item-"+selected_exercises[i]);
          }
        //AJAX
        $("#button_start").prop("disabled", true);
        $("#button_stop").prop("disabled", false);
        $("#progress_bar").css("display", "block");
        }

      total_submissions = selected_exercises.length * students.length;

      count_steps = 0;
      value_by_step = 100/total_submissions;

      actual_exercise = 0;
      actual_student = 0;
      actual_submission = 0;

      get_original_propositions();

      var_interval = setInterval(verifyBreak, time_interval);
      } // function start_evaluation()

    var actual_exercise;
    var actual_student;
    var actual_submission;
    var actual_answer_url; // to provide the current student answer (to "iassing_integration_functios.js" of iLM

    function run_individual_evaluation () {

      if (stoped) return;

      // Verify if student really sent his answer
      var found = false;
      for (var j = 0; j < submissions.length; j++) {
        if (submissions[j].exerciseid == selected_exercises[actual_exercise]) {
          for (var k = 0; k < submissions[j].submited.length; k++) {
            if (students[actual_student] == submissions[j].submited[k]) {
              found = true;
              break;
              }
            }
          break;
          }
        }

      if (found == false) {
        postResultAutoEval();
      } else {
        // If it was sent, search it...
        var final_url = "";
        var student_answer = "' . $CFG->wwwroot . "/mod/iassign/view.php?action=get_student_submission&id=" . $this->cm->id . '" + "&iassign_student=" + students[actual_student] + "&iassign_exercise=" + selected_exercises[actual_exercise];
        for (var j = 0; j < submissions.length; j++) {
          if (submissions[j].exerciseid == selected_exercises[actual_exercise]) {
            final_url = submissions[j].ilm;
            break;
            }
          }
        final_url += "?iLM_PARAM_AssignmentURL=true&iLM_PARAM_Assignment=" + encodeURIComponent(student_answer) + "&iLM_PARAM_TeacherAutoEval=true";
        actual_answer_url = student_answer; // to provide the current student answer (to "iassing_integration_functios.js" of iLM)

        window.frames["ifrm"].location=final_url;
        }
      } // function run_individual_evaluation()

    var break_student = -1;
    var break_exercise = -1;

    function verifyBreak () {
      if (actual_student == break_student && actual_exercise == break_exercise) {
        postResultAutoEval(-6);
        break_student = -1;
        break_exercise = -1;
        return;
      } else {
        break_student = actual_student;
        break_exercise = actual_exercise;
        }
      }

    // This function is called by the iLM to get the student s answer (to compare with re-evaluation)
    function getStudentAnswerURL () {
      return actual_answer_url; // to provide the current student answer (to "iassing_integration_functios.js" of iLM)
      }

    // This function is called by the iLM!!!
    function getAutoEvalOriginalData () {
      return original_propositions[actual_exercise];
      }

    function updateProgressBar () {
      if (stoped) return;

      var temp = (count_steps * value_by_step)|0;
      count_steps++;
      temp = temp > 100 ? 100 : temp;
      $(".progress-bar").css("width", temp + "%").text(temp + "%");
      if (temp >= 100) {
        $(".progress-bar").removeClass("progress-bar-animated progress-bar-striped");
        $("#button_start").prop("disabled", false);
        $("#button_stop").prop("disabled", true);
        $("input:checkbox").attr("disabled", false);

        clearInterval(var_interval);
        }
      }

    // Process the re-evaluated "grade" - DEVELOPER: here, do NOT use apostrophe!
    // The iLM method "getiLMContent()" must, after re-evaluate getting "new_grade", call this with "postResultAutoEval(new_grade)"
    function postResultAutoEval (grade = null) {

      var id_t = "std_" + students[actual_student] + "_ex_" + selected_exercises[actual_exercise];

      if (grade == null) {
        $("#"+id_t).html(\'<i class="fa fa-circle-o text-muted" aria-hidden="true" style="font-size: 2em;" data-toggle="tooltip" data-original-title="'.get_string('not_post', 'iassign').'"></i>\');
        $("#"+id_t).find(\'[data-toggle="tooltip"]\').tooltip();
      } else {
        // Verify if the re-evaluation grade is the of that one registered:
        var actual_value = 0;
        var actual_student_grade = 0;
        for (var k = 0; k < submissions.length; k++) {
          if (submissions[k].exerciseid == selected_exercises[actual_exercise]) {
            actual_value = submissions[k].grade;
            for (var h = 0; h < submissions[k].submited.length; h++) {
              if (submissions[k].submited[h] == students[actual_student]) {
                actual_student_grade = submissions[k].allgrades[h];
                break;
                }
              }
            break;
            }
          }

        // @FeedbackConvention
        if (grade < 0) {
          switch (grade) {
            case -1:
              // Test-cases are empty:
              $("#"+id_t).html(\'<i data-toggle="modal" class="details-button" data-target="#exampleModalCenter"><i class="fa fa-times-circle-o fa-6" aria-hidden="true" style="color: #ef5151; font-size: 2em; cursor: pointer;" data-toggle="tooltip" data-original-title="'.get_string('auto_evaluate_msg_t_empty_cases', 'iassign').'"></i></i>\');
              var mm = actual_exercise;
              $("#"+id_t).find(".details-button").click(function(e) {
                $(".modal-body").html(\'<table class="table"><tr><td style="width: 30%;">'.get_string('exercise', 'iassign').':</td><td>\'+$("#label_ex_"+selected_exercises[mm]).text()+\'</td></tr><tr><td style="width: 30%;">'.get_string('student', 'iassign').'</td><td>\'+$(this).closest("tr").find(".student-name").text()+\'</td></tr><tr><td style="width: 30%;" colspan="2"><b>'.get_string('auto_result', 'iassign').'</b><br>'.get_string('auto_evaluate_msg_empty_cases', 'iassign').'</td></tr></table>\');
              });
              break;
            case -2:
              // Test-cases were manually altered by the student (fraud!):
              $("#"+id_t).html(\'<i data-toggle="modal" class="details-button" data-target="#exampleModalCenter"><i class="fa fa-times-circle-o fa-6" aria-hidden="true" style="color: #ef5151; font-size: 2em; cursor: pointer;" data-toggle="tooltip" data-original-title="'.get_string('auto_evaluate_msg_t_diff_cases', 'iassign').'"></i></i>\');
              var mm = actual_exercise;
              $("#"+id_t).find(".details-button").click(function(e) {
                $(".modal-body").html(\'<table class="table"><tr><td style="width: 30%;">'.get_string('exercise', 'iassign').':</td><td>\'+$("#label_ex_"+selected_exercises[mm]).text()+\'</td></tr><tr><td style="width: 30%;">'.get_string('student', 'iassign').'</td><td>\'+$(this).closest("tr").find(".student-name").text()+"</td></tr><tr><td style=\'width: 30%;\' colspan=\'2\'><b>'.get_string('auto_result', 'iassign').'</b><br>'.get_string('auto_evaluate_msg_diff_cases', 'iassign').'</td></tr></table>");
              });
              //AJAX
              $.ajax({
                dataType: "html",
                url: "'.$CFG->wwwroot.'/mod/iassign/view.php?action=post_auto_eval_result&id='.$this->cm->id.'&iassign_exercise="+selected_exercises[actual_exercise]+"&student="+students[actual_student]+"&grade=0",
                success: function(d) {} });
              break;
            case -5:
              // Execution failed:
              $("#"+id_t).html(\'<i data-toggle="modal" class="details-button" data-target="#exampleModalCenter"><i class="fa fa-times-circle-o fa-6" aria-hidden="true" style="color: #ef5151; font-size: 2em; cursor: pointer;" data-toggle="tooltip" data-original-title="'.get_string('auto_evaluate_msg_t_fail', 'iassign').'"></i></i>\');
              var mm = actual_exercise;
              //AJAX
              $("#"+id_t).find(".details-button").click(function(e) {
                $(".modal-body").html(\'<table class="table"><tr><td style="width: 30%;">'.get_string('exercise', 'iassign').':</td><td>\'+$("#label_ex_"+selected_exercises[mm]).text()+\'</td></tr><tr><td style="width: 30%;">'.get_string('student', 'iassign').'</td><td>\'+$(this).closest("tr").find(".student-name").text()+\'</td></tr><tr><td style="width: 30%;" colspan="2"><b>'.get_string('auto_result', 'iassign').'</b><br>'.get_string('auto_evaluate_msg_fail', 'iassign').'</td></tr></table>\');
              });
              break;
            case -6: // iLM not answering was detected in "verifyBreak()"
              // The iLM are not answering:
              //AJAX
              $("#"+id_t).html(\'<i data-toggle="modal" class="details-button" data-target="#exampleModalCenter"><i class="fa fa-clock-o fa-6" aria-hidden="true" style="color: orange; font-size: 2em; cursor: pointer;" data-toggle="tooltip" data-original-title="'.get_string('auto_evaluate_title_time', 'iassign').'"></i></i>\');
              $("#"+id_t).find(".details-button").click(function(e) {
                $(".modal-body").html(\'<table class="table"><tr><td style="width: 30%;">' . get_string('auto_evaluate_msg_time', 'iassign') . '</td></tr></table>\');
              });
              break;
            }
          //AJAX
          $("#"+id_t).find(\'[data-toggle="tooltip"]\').tooltip();

        } else {
          console.log((grade), (actual_student_grade));
          if ((grade) == (actual_student_grade)) {
            $("#"+id_t).html(\'<i class="fa fa-check-circle-o text-success" aria-hidden="true" style="font-size: 2em;" data-toggle="tooltip" data-original-title="'.get_string('auto_evaluate_ok', 'iassign').'"></i>\');
          } else { // Notas diferem:
            $("#"+id_t).html(\'<i data-toggle="modal" class="details-button" data-target="#exampleModalCenter"><i class="fa fa-exclamation-circle text-warning" aria-hidden="true" style="font-size: 2em; cursor: pointer;" data-toggle="tooltip" data-original-title="'.get_string('auto_evaluate_different_grades', 'iassign').'"></i></i>\');
            var mm = actual_exercise;
            //AJAX
            $("#"+id_t).find(".details-button").click(function(e) {
              $(".modal-body").html(\'<table class="table"><tr><td style="width: 30%;">'.get_string('exercise', 'iassign').':</td><td>\'+$("#label_ex_"+selected_exercises[mm]).text()+\'</td></tr><tr><td style="width: 30%;">'.get_string('student', 'iassign').'</td><td>\'+$(this).closest("tr").find(".student-name").text()+\'</td></tr><tr><td style="width: 30%;">'.get_string('auto_evaluate_grade_registered', 'iassign').'</td><td>\'+(actual_student_grade*grade)+\'</td></tr><tr><td style="width: 30%;"><b>'.get_string('auto_evaluate_grade_new', 'iassign').'</b></td><td>\'+(grade * actual_value)+\'</td></tr><tr><td colspan="2">'.get_string('auto_evaluate_msg_grade_update', 'iassign').'</tr></table>\');
            });

            //AJAX
            // Send to the endpoint the post_auto_eval_result to verify:
            $.ajax({
              dataType: "html",
              url: "' . $CFG->wwwroot . '/mod/iassign/view.php?action=post_auto_eval_result&id=' . $this->cm->id . '&iassign_exercise=" + selected_exercises[actual_exercise] + "&student=" + students[actual_student] + "&grade=" + (grade),
              success: function(d) {}
              });

            }
          //AJAX
          $("#"+id_t).find(\'[data-toggle="tooltip"]\').tooltip();
          }
        }

      updateProgressBar();

      // Does it must continue in the same exercise or stop?
      if (actual_student < students.length) {
        actual_student++;
        run_individual_evaluation();
      } else {
        actual_student = 0;
        // Does it go to next exercise or is it finished?
        if (actual_exercise < selected_exercises.length) {
          actual_exercise++;
          run_individual_evaluation();
        } else {
          //AJAX
          $("#evaluate_alerts").text("' . get_string('auto_evaluate_finished', 'iassign') . '");
          }
        }
      }

    var original_propositions;
    function get_original_propositions () {
      original_propositions = [];
      var count = 0;
      for (var i = 0; i < exercises.length; i++) {
        if ($("#ex_"+exercises[i]).is(":checked") == true) {
          //AJAX
          $.ajax({
            dataType: "html",
            url: "' . $CFG->wwwroot . '/mod/iassign/view.php?action=get_teacher_exercise&id=' . $this->cm->id . '&iassign_exercise="+exercises[i],
            success: function(d) { // variable "d" has the iLM content
              original_propositions[count] = d; // exercise original content
              count++;

              if (count == selected_exercises.length) {
                run_individual_evaluation();
                }
              }
            });
          }
        }
      }

    function stop_evaluation () {
      stoped = true;
      //AJAX
      $("#button_start").prop("disabled", false);
      $("#button_stop").prop("disabled", true);
      $(".progress-bar").removeClass("progress-bar-animated progress-bar-striped");
      $("input:checkbox").attr("disabled", false);
      $("#evaluate_alerts").text("' . get_string('auto_evaluate_stopped', 'iassign') . '");
     clearInterval(var_interval);
     }
    var exercises = [ '; // '

    foreach ($exercises_list as $exercise) {
      print $exercise->id . ',';
      }
    print ' ];';
    print ' var students = [ ';
    foreach ($students_list as $student) {
      print $student->userid . ',';
      }
    print '];';
    print ' var ilms = [';
    foreach ($exercises_list as $exercise) {
      $iassign_ilm = $DB->get_record("iassign_ilm", array("id" => $exercise->iassign_ilmid));
      print "'" . $CFG->wwwroot . "/mod/iassign/" . $iassign_ilm->file_jar . "/" . $iassign_ilm->file_class . "',";
      }
    print ']';
    print '</script>';
    print '
    <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLongTitle">' . get_string('auto_evaluate_details', 'iassign') . '</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            ...
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">' . get_string('close', 'iassign') . '</button>
          </div>
        </div>
      </div>
    </div>'; //

    print '<iframe name="ifrm" style="position: absolute; top: -1000px;"></iframe>';

    print '<table id="table-results" class="table table-hover m-t-1">
            <thead>
              <tr id="cols-exercises">
                <th scope="col" style="width: 30rem;">'.get_string('students', 'iassign').'</th>
              </tr>
            </thead>
            <tbody>'; // "
    // End of JavaScript code to auto-evaluation

    foreach ($students_list as $student) {
      $user = $DB->get_record('user', array('id' => $student->userid));
      $avatar = new user_picture($user);
      $avatar->courseid = $COURSE->id;
      $avatar->link = true;

      print '  <tr data-student="' . $student->userid . '">
    <td>' . $OUTPUT->render($avatar) . ' <span class="student-name">' . $user->firstname . ' ' . $user->lastname . '</span></td>
    </tr>' . "\n"; // "
      }

    print " </tbody>
 </table>\n";

    print $OUTPUT->footer();
    } // function auto_evaluate()


  /// This method moves an iAssign activity
  function move_activity () {
    global $USER, $CFG, $DB, $COURSE;

    $iassign_statement_id = optional_param('iassign_current', NULL, PARAM_TEXT);
    $iassignid_destiny = optional_param('iassign_destiny', NULL, PARAM_TEXT);

    $update_entry_new_iassign = new stdClass();
    $update_entry_new_iassign->id = $iassign_statement_id;
    $update_entry_new_iassign->iassignid = $iassignid_destiny;

    // Update the iassign_statement
    $DB->update_record("iassign_statement", $update_entry_new_iassign);

    // Update file context
    $c_i = context_module::instance($this->cm->id);
    $ret = get_coursemodule_from_id("iassign", $c_i->instanceid);

    $course_module_info = $DB->get_record("course_modules", array("course" => $COURSE->id, "module" => $ret->module, "instance" => $iassignid_destiny));

    $path_module = context_course::instance($COURSE->id);

    $context_file = $DB->get_record_sql('SELECT * FROM {context} WHERE instanceid="' . $course_module_info->id . '" AND path like "'.$path_module->path.'%"');

    $course_module = get_coursemodule_from_instance('iassign', $iassignid_destiny); //2021/12

    if ($context_file) {
      $id_context = $context_file->id;

      $iassign_statement = $DB->get_record("iassign_statement", array("id" => $iassign_statement_id));
      $fs = get_file_storage(); // Get reference to all files in Moodle data

      $context = context_module::instance($USER->cm); //2021/12

      //2021/12 $files = $fs->get_area_files(context_module::instance($this->cm->id)->id, 'mod_iassign', 'exercise', $iassign_statement->filesid);
      //TODO 2022: trocar "$iassign_statement->id" por "$iassign_statement->file"???
      $files = $fs->get_area_files($context->id, 'mod_iassign', 'exercise', $iassign_statement->id);

      foreach ($files as $value) {
        if ($value->get_filename() != ".") {
          //2021/12 $last_id = $DB->get_record_sql('SELECT itemid FROM {files} WHERE component="mod_iassign" ORDER BY itemid DESC LIMIT 1')->itemid;
          //2021/12 $newfile = $fs->create_file_from_storedfile(array('contextid' => $id_context, 'component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $iassign_statement_id), $value);
          $fs->delete_area_files(context_module::instance($this->cm->id)->id, 'mod_iassign', 'exercise', $value->get_itemid());

          $destiny_context = context_module::instance($course_module->id);
          // ./lib/filestorage/file_storage.php : "public function create_file_from_string($filerecord, $content)" 1347/2412 on Moodle 2018120302.08
          $newfile = $fs->create_file_from_string(array('contextid' => $destiny_context->id, 'component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $iassign_statement_id, 'filepath' => '/', 'filename' => $value->get_filename()), $value->get_content());

          $updateentry = new stdClass();
          $updateentry->id = $iassign_statement_id;
          // Correct association: {files}.itemid = {iassign_statement}.id and {iassign_statement}.filesid={files}.id
          $updateentry->filesid = $newfile->get_id(); // 2022/10/10: was "$updateentry->filesid = $iassign_statement_id;"
          $updateentry->file = $updateentry->filesid; // keep the old field

          $DB->update_record("iassign_statement", $updateentry);
          //2021/12 $fs->delete_area_files(context_module::instance($this->cm->id)->id, 'mod_iassign', 'exercise', $value->get_itemid());
          break;
          }
        }
      }

    //2021/12 $this->return_home_course('moved_activity');
    $destiny_url = new moodle_url('/mod/iassign/view.php', array('id' => $course_module->id));
    redirect($this->return, get_string('moved_activity', 'iassign').' <a href="'.$destiny_url.'">' . get_string('move_activity_access', 'iassign') . '\'' . $course_module->name . '\'</a>', null, \core\output\notification::NOTIFY_SUCCESS);
    exit;
    } // function move_activity()


  /// This method duplicates an iAssign activity
  //  To duplicate one {iassign_statement}: "click" over the activity "duplicate" button
  //  with 'http...mod/iassign/view.php?action=duplicate_activity&id=ID1&iassign_current=ID2&iassign_up=IDu&iassign_down=IDd'
  //  @param GET ID1 = {course_modules}.id
  //  @param GET ID2 = {iassign_statement}.id
  //  @param GET IDu = {iassign_statement}.id of the activity above of ID2
  //  @param GET IDd = {iassign_statement}.id of the activity bellow of ID2
  //  @calledby locallib.php : function action(): "$restricted = array(... 'duplicate_activity' => '$this->duplicate_activity();'," ...
  //  @calledby locallib.php : function action(): 'import_activity_confirm' => '$this->import_activity_confirm();'
  //  @calledby locallib.php : function import_activity_confirm(): ''
  function duplicate_activity () {
    global $USER, $CFG, $COURSE, $DB, $OUTPUT;

    // Not used: $id = $this->cm->id; // Get {iassign}.id from {course_module}: {iassign_statement}.iassignid = {course_module}.id = $id
    $iassignid = optional_param('iassign_current', NULL, PARAM_TEXT);
    $iassign_up = optional_param('iassign_up', NULL, PARAM_TEXT);
    $iassign_down = optional_param('iassign_down', NULL, PARAM_TEXT);

    $context = context_module::instance($this->cm->id);

    // Not used: $contextuser = context_user::instance($USER->id);

    // Get the the iAssign acitivity to be duplicated
    $original_iassign_statement = $DB->get_record("iassign_statement", array("id" => $iassignid));

    // Make a copy of $original_iassign_statement (after we will change fields 'id, name, author_modified, author_modified_name, timemodified, file, filesid')
    $original_iassign_statement_id = $original_iassign_statement->id;
    $new_iassign_statement = new stdClass(); // build record to the new {iassign_statement}
    foreach ($original_iassign_statement as $field_name => $value) {
      // id name iassignid type_iassign proposition author_name author_modified_name iassign_ilmid file grade timemodified timecreated timeavailable timedue preventlate test special_param1 position visible max_experiment dependency automatic_evaluate show_answer filesid store_all_submissions
      $new_iassign_statement->$field_name = $value; // copy the original value
      }// $new_iassign_statement->id = 0; // will receive the new {iassign_statement}.id - below "$updateentry->id = $id"

    // Get the information about current author, and add this information in author_modified field
    $author = $USER; //x $DB->get_record("user", array("id" => $USER->id)); --- useless since we already have {user}=id; auth; ... username; password; idnumber; firstname; lastname; email; ... description ...

    // Change the field 'name' using the prefix "Copy of ", 'author_modified_name' and 'author_modified' to the current $USER
    $new_iassign_statement->name = get_string('duplicate_iassign_prefix', 'iassign') . ' ' . $original_iassign_statement->name; // new name to {iassign_statement}.name
    $new_iassign_statement->author_modified_name = $author->firstname . ' ' . $author->lastname . ' (' . $author->id . ')'; // use the current user as author
    $new_iassign_statement->author_modified = $new_iassign_statement->author_modified_name ; // original_iassign_statement->author_modified_name;
    $new_iassign_statement->timemodified = time(); // current time

    // Store the activity in the table
    if ($new_iassign_statement_id = $DB->insert_record("iassign_statement", $new_iassign_statement)) {
      $new_iassign_statement->id = $new_iassign_statement_id; // IMPORTANT: update the {iassign_statement}.id to the correct new value!

      // ./lib/filestorage/file_storage.php: public function get_area_files($contextid, $component, $filearea, $itemid = false,...)
      $fs = get_file_storage(); // Get reference to all files in Moodle data (with entry in {files})
      
      // Try to {files} through get_from_files($ia_id, $ia_file, $ia_filesid, $fs, $contextid, $component, $filearea).
      // Let A={files}.itemid and B={iassign_statement}, they try in this order: A=B.id ; A=B.file ; A=B.filesid
      require_once('files_functions.php'); // Get auxiliary functions to manage {iassign_statement}/{files}
      $answer_array = get_from_files($original_iassign_statement_id, $original_iassign_statement->file, $original_iassign_statement->filesid, $fs, $context->id, 'mod_iassign', 'exercise');
      if ($answer_array==NULL) {
        //x print_error('invalidid', 'iassign'); // $string['invalidid'] = 'Invalid ID';
        // print($OUTPUT->notification(get_string('error_view_ilm', 'iassign'), 'notifyproblem')); --- other option, to present msg a frame (on the same page)
        print_error('error_view_ilm', 'iassign'); // Error: exercise file not found. Inform the teacher of the course.  
        exit;
        }
      $f1_obj = $answer_array[0]; // regular 'filename'
      $f2_obj = $answer_array[1]; // directory (filename == '.') - do not used it
      if ($f1_obj!=null) { // duplicate this entry from {files} creating a new file in Moodle Data
        // @seeurl https://docs.moodle.org/dev/File_API : difference between $fs->create_file_from_string($fileinfo, 'hello world');
        // ./lib/filestorage/file_storage.php : "public function create_file_from_string($filerecord, $content)" 1347/2412 on Moodle 2018120302.08
        // ./lib/filestorage/file_storage.php : "public function create_file_from_storedfile($filerecord, $fileorid)" 1051/2412 on Moodle 2018120302.08

        // Version 1: use one preexisting file - this version is better since we are duplicating a previous files
        // ./lib/filestorage/file_storage.php : "public function create_file_from_storedfile($filerecord, $fileorid)" 1051/2412 on Moodle 2018120302.08
        // ./lib/filestorage/file_storage.php: '$new_files' is protected
        $array_config = array('contextid' => $context->id, 'component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $new_iassign_statement_id); // Version 1
        $new_files = $fs->create_file_from_storedfile($array_config, $f1_obj); // create entry in {files} and a new file in Moodle Data // Version 1
        //2 Version 2: create NEW file by contents, add the prefix 'new_' followed to the original name
        //2 $array_param = array('contextid' => $context->id, 'component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $new_iassign_statement_id,
        //2    'userid' => $USER->id, 'author' => $new_iassign_statement->author_modified_name, 'license' => 'allrightsreserved',
        //2    'filepath' => '/', 'filename' => 'new_' . $f1_obj->get_filename());
        //2 // The './lib/file_storage.php!get_pathname_hash(.)' computed path name with: sha1("/".$contextid."/".$component."/".$filearea."/".$itemid.$filepath.$filename);
        //2 $new_files = $fs->create_file_from_string($array_param, $f1_obj->get_content());
        //D echo " * ./lib/file_storage.php!get_pathname_hash(.) = " . file_storage::get_pathname_hash($context->id,'mod_iassign','exercise',$new_iassign_statement_id,'/','new_' . $f1_obj->get_filename()) . "<br/>";
        //$f1_obj->author = $USER->id; $f1_obj->license = 'allrightsreserved';

        // Now update {iassign_statement}.filesid with '$new_files->id': {iassign_statement}.filesid = {files}.id
        //2021/12 $updateentry->filesid = $new_files->get_itemid();
        $new_iassign_statement->filesid = $new_files->get_id(); // points {iassign_statement}.filesid to the new {files}: {iassign_statement}.filesid = {files}.id
        $new_iassign_statement->file = $new_iassign_statement->filesid; // old field

        // Update the duplicated {iassign_statement} with references to the new {files} (and its associated file in Moodle Data)
        if (!$DB->update_record("iassign_statement", $new_iassign_statement)) {
          // print_error('error_update_move_iassign', 'iassign');
          // print($OUTPUT->notification(get_string('error_add_ilm_zip', 'iassign'), 'notifyproblem'));
          print_error('error_file_dupl_1', 'iassign'); // Error: it was not possible to duplicate this file, perhaps it already exists!
          }
        } // if ($f1_obj!=null) - f1_obj in {files}
      else { //DEBUG 
        print_error('error_file_not_exists', 'iassign'); // '<font color="red">Error: file not existing!!</font>'
        }

      } //  if ($new_iassign_statement_id = $DB->insert_record("iassign_statement", $new_iassign_statement))

    // log event --------------------------------------------------------------------------------------
    iassign_log::add_log('duplicate_iassign_exercise', 'name: ' . $author->firstname, $new_iassign_statement_id, $this->cm->id);

    $this->return_home_course('duplicated_activity');
    exit;
    } // function duplicate_activity()


  // / This method gets the content from comment and register it
  function add_comment () {
    global $USER, $DB;
    $submission_comment = optional_param('submission_comment', NULL, PARAM_TEXT);
    $comment = false;
    if ($submission_comment)
      $comment = $this->write_comment_submission();
    $id_submission = optional_param('iassign_submission_current', NULL, PARAM_TEXT);
    if (!$id_submission) {
      $iassign_submission = $DB->get_record("iassign_submission", array("iassign_statementid" => $this->activity->get_activity()->id, "userid" => $USER->id));
      if ($iassign_submission) {
        $id_submission = $iassign_submission->id;
        }
      }

    $content = $this->search_comment_submission($id_submission);
    ob_end_clean();
    header("Connection: close\r\n");
    header("Content-Encoding: none\r\n");
    header('Content-Type: text/html');
    ob_start();
    print $this->search_comment_submission($id_submission);

    $size = ob_get_length();
    header("Content-Length: $size");
    ob_end_flush();
    flush();
    ob_end_clean();

    // Send email to users:
    $iassign_submission = $DB->get_record("iassign_submission", array("id" => $id_submission));

    $this->send_alert_new_comment($submission_comment, $iassign_submission->userid);
    }


  /// This method alerts envolved to new comment sent
  function send_alert_new_comment ($comment, $student_id = -1) {
    global $USER, $DB, $COURSE, $SITE;

    $subject = $COURSE->shortname . ": " . get_string('new_comment_mail_subject', 'iassign') . " ";
    $iassign_ilm = $DB->get_record("iassign_ilm", array("id" => $this->activity->get_activity()->iassign_ilmid)); //leo 2021/12
    $subject .= $iassign_ilm->name . ' ' . get_string('new_comment_mail_at', 'iassign') . ' ' . get_string("iassign", "iassign") . '/Moodle'; //leo 2021/12

    // $url_curso; $url_submissao;

    $text = "<p style='margin-top: 1rem;'>\n";
    $text .= "<a href=\"" . new moodle_url('/course/view.php', array('id' => $COURSE->id)) . "\">" . $COURSE->shortname . "</a> " . " &#8811; \n";

    $modinfo = get_fast_modinfo($COURSE);

    for ($i = 0; $i < count($modinfo->sections); $i++) {
      if ($modinfo->get_section_info($i) && $modinfo->get_section_info($i)->id == $this->cm->section) {
        $url_curso = new moodle_url('/course/view.php', array('id' => $COURSE->id));
        $text .= "<a href=\"" . $url_curso . "#section-" . $modinfo->get_section_info($i)->section . "\">" . get_section_name($COURSE, $modinfo->get_section_info($i)->section) . "</a> &#8811; \n";
        break;
        }
      }

    $iassign = $DB->get_record('iassign', array('id' => $this->activity->get_activity()->iassignid));
    $text .= "<a href=\"" . new moodle_url('/mod/iassign/view.php', array('id' => $this->cm->id)) . "\">" . $iassign->name . "</a> &#8811; \n";

    $url_submissao = new moodle_url('/mod/iassign/view.php', 
      array(
        'id' => $this->cm->id, 
        'action' => 'viewsubmission', 
        'iassign_current' => $this->activity->get_activity()->id,
        'userid_iassign' => $USER->id));

    $text .= "<a href=\"" . $url_submissao . "\">" . $this->activity->get_activity()->name . "</a></p>\n";
    $text .= "<div style='margin-left: .5rem;'>\n";
    $text .= get_string('new_comment_mail_course', 'iassign') . ": <a href=\"" . $url_curso . "\">" . $COURSE->fullname . "</a>\n";
    $text .= "</div><div style='margin-top: .3rem; margin-left: .5rem;'>\n";
    $text .= get_string('exercise', 'iassign') . ": <a href=\"" . $url_submissao . "\">" . $this->activity->get_activity()->name . "</a>\n";
    $text .= "</div><div style='margin-top: .3rem; margin-left: .5rem;'>\n";
    $text .= get_string('new_comment_mail_sent_by', 'iassign') . ': ' . "<a href=\"" .
               new moodle_url('/user/view.php', array('id' => $USER->id, 'course' => $COURSE->id)) . "\">" . $USER->firstname . " " . $USER->lastname . "</a>\n";
    $text .= " - " . userdate(time());
    $text .= "</div><div style='margin: 2rem;'>\n";
    $text .= $comment;
    $text .= "</div><div>\n";
    $text .= "<a href=\"" . $url_submissao . "#comments\">" . get_string('new_comment_mail_open_exercise', 'iassign') .  "</a> | \n";
    $text .= "<a href=\"" . $url_submissao . "#comments\">" . get_string('new_comment_mail_answer', 'iassign') . "</a>\n";
    $text .= "</div>\n";

    // Who has to receive?
    $teachers = get_users_by_capability($this->context, 'mod/iassign:evaluateiassign');
    $userfrom = \core_user::get_noreply_user();

    if (has_capability('mod/iassign:evaluateiassign', $this->context, $USER->id)) { // a teacher has sent the comment:
      // other teachers receive also: (excepts the sender)
      foreach ($teachers as $teacher) {
        if ($teacher->id != $USER->id) {
          email_to_user(
            $teacher, 
            $USER->firstname . " " . $USER->lastname . " (via " . $SITE->shortname . ")",
            $subject, 
            "", 
            $text, 
            '', 
            '', 
            false,
            $userfrom->email,
            get_string('comment_not_answer_this', 'iassign') // 'Do not answer this message'
            );
          $message = new \core\message\message();
          $message->component = 'mod_iassign';
          $message->name = 'message';
          $message->userfrom = core_user::get_noreply_user();
          $message->userto = $teacher;
          $message->subject = $subject;
          $message->fullmessage = $text;
          $message->fullmessageformat = FORMAT_MARKDOWN;
          $message->fullmessagehtml = $text;
          $message->smallmessage = $subject;
          $message->notification = 1;
          $message->contexturl = $url_submissao . '#comments';
          $message->contexturlname = $this->activity->get_activity()->name;
          $messageid = message_send($message);
          }
        } // foreach ($teachers as $teacher)
      // and student:
      $student = $DB->get_record('user', array('id' => $student_id));
      $formated_text = str_replace("viewsubmission", "view", $text);
      email_to_user(
        $student,
        $USER->firstname . " " . $USER->lastname . " (via " . $SITE->shortname . ")",
        $subject,
        "",
        $formated_text,
        '',
        '',
        false,
        $userfrom->email,
        get_string('comment_not_answer_this', 'iassign') // 'Do not answer this message'
        );
      $url_student = new moodle_url('/mod/iassign/view.php',
        array(
          'id' => $this->cm->id,
          'action' => 'view',
          'iassign_current' => $this->activity->get_activity()->id));
      $message = new \core\message\message();
      $message->component = 'mod_iassign';
      $message->name = 'message';
      $message->userfrom = core_user::get_noreply_user();
      $message->userto = $student;
      $message->subject = $subject;
      $message->fullmessage = $formated_text;
      $message->fullmessageformat = FORMAT_MARKDOWN;
      $message->fullmessagehtml = $formated_text;
      $message->smallmessage = $subject;
      $message->notification = 1;
      $message->contexturl = $url_student . '#comments';
      $message->contexturlname = $this->activity->get_activity()->name;
      $messageid = message_send($message);
      } // if (has_capability('mod/iassign:evaluateiassign', $this->context, $USER->id))
    else { // a student has sent the comment:
      foreach ($teachers as $teacher) {
        email_to_user(
          $teacher,
          $USER->firstname . " " . $USER->lastname . " (via " . $SITE->shortname . ")",
          $subject,
          "",
          $text,
          '',
          '',
          false,
          $userfrom->email,
          get_string('comment_not_answer_this', 'iassign') // 'Do not answer this message'
          );
        $message = new \core\message\message();
        $message->component = 'mod_iassign';
        $message->name = 'message';
        $message->userfrom = core_user::get_noreply_user();
        $message->userto = $teacher;
        $message->subject = $subject;
        $message->fullmessage = $text;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = $text;
        $message->smallmessage = $subject;
        $message->notification = 1;
        $message->contexturl = $url_submissao . '#comments';
        $message->contexturlname = $this->activity->get_activity()->name;
        $messageid = message_send($message);
        } // foreach ($teachers as $teacher)
      }
    } // function send_alert_new_comment($comment, $student_id = -1)


  /// This method gets the content from the iLM and register it
  //  It could be the "exercise template" (teacher) or an answer (student)
  //  If student answer: the answer must be registered in table {iassign_submission} (perhaps also in {submission_comment}, {iassign_allsubmissions})
  //  In 2022/10/05 was fixed to allow the registration of new solutions send by the student (even when the grade is reduced)
  function get_answer () {

    global $USER, $CFG, $DB, $OUTPUT;
    $id = $this->cm->id;

    $submission_comment = optional_param('submission_comment', NULL, PARAM_TEXT);
    $submission_ajax    = optional_param('iLM_ajax', NULL, PARAM_TEXT);

    if ($submission_ajax == 1) ob_start(); // submission through JavaScript => do not present echoes

    $comment = false;
    if ($submission_comment)
      $comment = $this->write_comment_submission();

    // receives data of iLM using the current activity
    $iassign_ilm = $DB->get_record("iassign_ilm", array("id" => $this->activity->get_activity()->iassign_ilmid)); // has automatic evaluation?
    $iassign = $DB->get_record("iassign", array("id" => $this->activity->get_activity()->iassignid)); // activity
    // Receives data of submission of current activity: the field 'activity' of class 'activity' has all fields of {iassign_statement}
    $iassign_submission = $DB->get_record("iassign_submission", array("iassign_statementid" => $this->activity->get_activity()->id, "userid" => $this->userid_iassign)); // data about student solution

    // receives post and get
    $iLM_PARAM_ActivityEvaluation = optional_param('iLM_PARAM_ActivityEvaluation', 0, PARAM_INT); // 1 - activity evaluated as correct ; 0 - activity evaluated as incorrect
    $iLM_PARAM_RealGrade = optional_param('iLM_PARAM_RealGrade', 0, PARAM_FLOAT); // the grade send by the iLM automatic evaluator (if it has one): a value between 0 and 1

    //2016/02/16: IMPORTANTE trocar formatador para "nao formatado", pois esta destruindo o conteudo do arquivo
    $iLM_PARAM_ArchiveContent = optional_param('iLM_PARAM_ArchiveContent', NULL, PARAM_RAW); // answer file (ATTENTION: do not change format, use RAW in order to ensure the correct content)
    $MA_POST_Info = optional_param('MA_POST_Info', NULL, PARAM_FORMAT);
    $MA_POST_SystemData = optional_param('MA_POST_SystemData', NULL, PARAM_FORMAT);

    //TODO: With $return_get_answer == 1 implies that the new submission will over-write the old one, even with a smaller grade
    //TODO: To be implemented: we can use JS functions 'overwrite()' and 'nooverwrite()' (bellow) to the student to choose over-write or not
    // $return_get_answer = optional_param('return_get_answer', 0, PARAM_INT);
    $return_get_answer = optional_param('return_get_answer', 1, PARAM_INT); // if 1 then register new submission even if the grade is reduced from 1.0 to any lower value
    $msg = '';

    // If the activity is defined to store all submissions
    if ($this->activity->get_activity()->store_all_submissions == 1) {
      $activity_scale = $this->activity->get_activity()->grade; // get {iassign_statement}.grade (to be used to weighted the student grade from [0,1])
      $newentry = new stdClass();
      $newentry->iassign_statementid = $this->activity->get_activity()->id;
      $newentry->userid = $this->userid_iassign;
      $newentry->timecreated = time();
      //2023/05/25 $newentry->grade = round($iLM_PARAM_RealGrade, 2);
      $newentry->grade = round($iLM_PARAM_RealGrade * $activity_scale, 2); // iLM must return through 'getEvaluation()' a value between 0 and 1
      $newentry->answer = $iLM_PARAM_ArchiveContent;

      $DB->insert_record("iassign_allsubmissions", $newentry);
      }

    // Feedback
    // Activity status: 0 => not post 1; => post; 2 => evaluated as incorrect; 3 => evaluated as correct
    $str_action = "view"; // repeat
    if (strtolower($iassign_ilm->name) == "igeom") {
      }

    $title = get_string('evaluate_iassign', 'iassign');
    print $OUTPUT->header();
    print $OUTPUT->box_start();

    // Action = { view ; repeat ; viewsubmission }
    // * 'view' => it is impossible to re-send answer (this is correct with iGeom because its model to ensure security - do not allow the learner's access to the "answer model")
    // * 'repeat' => student explicitly requested to redo the activiy or enter in the last submission under iLM other then iGeom
    // * 'viewsubmission' => teacher/non editing teacher seeing the learner's activity

    $return = $CFG->wwwroot . "/mod/iassign/view.php?action=view&id=" . $id . "&iassign_submission_current=" . $this->iassign_submission_current . "&userid_iassign=" . $this->userid_iassign . "&iassign_current=" . $this->activity->get_activity()->id;

    $return_last = " <a href='" . $return . "'>" . iassign_icons::insert('return_home') . ' ' . get_string('return_iassign', 'iassign') . "</a>\n";

    $link_return = " <a href='" . $this->return . "'>" . iassign_icons::insert('home') . ' ' . get_string('activities_page', 'iassign') . "</a>\n";
    print "<table  width=100% >\n";

    if ($iLM_PARAM_ArchiveContent == - 1 || empty($iLM_PARAM_ArchiveContent)) { // if ($iLM_PARAM_ActivityEvaluation == -1)
      //TODO Does it need review?
      $this->write_solution = 0; // necessary in order to take note in Moodle 'grade' system
      // empty_answer_post = No solution was posted.
      if ($comment)
        print '<tr><td colspan=2><br>' . get_string('empty_answer_post', 'iassign') . '</br>' . get_string('confirm_add_comment', 'iassign') . '</td>' . "\n";
      else
        print '<tr><td colspan=2><br>' . get_string('empty_answer_post', 'iassign') . '</td>' . "\n";
      print '<tr><td width=40% align=right>' . $return_last . ' ' . $link_return . '</td></tr>' . "\n";
      }
    else { // if ($iLM_PARAM_ArchiveContent == - 1 || empty($iLM_PARAM_ArchiveContent))

      $this->write_solution = 1; // indicate that this answer must overwrite the old one

      if ($iassign_ilm->evaluate == 1 && $this->activity->get_activity()->automatic_evaluate == 1) { // iLM with automatic evaluator
        //TODO Do compare with previous soluction to prevent update to one answer with a smaller grade?
        // The student grade must be a value between 0 and 1: 0 => complete wrong ; 1 => 100% correct!
        if (intval($iLM_PARAM_ActivityEvaluation) == 1) {
        // if (true) { // after 2022/10/04: accept any new submission (overwrite, even when the grade is reduced!)
          // intval($iLM_PARAM_ActivityEvaluation) == 1 => correct answer!!
          //TODO Perhaps, must we use 'write_solution=0'?

          // $this->write_solution = 1; // indicate that this answer must overwrite the old one

          $status = 3;

          $grade_student = $iLM_PARAM_RealGrade; // evaluated as correct solution submitted is assigned the note pattern of activity

          $msg = '<tr><td colspan=2>' . iassign_icons::insert('feedback_correct') . '<br>' . get_string('get_answer_correct', 'iassign') . '</td>';

          // log record
          $info = $iassign->name . "&nbsp;-&nbsp;" . $this->activity->get_activity()->name . "&nbsp;-&nbsp;" . get_string('feedback_correct', 'iassign') . "&nbsp;-&nbsp;" . get_string('grade_iassign', 'iassign') . ":" . $grade_student;
          // Trigger module viewed event.
          $event = \mod_iassign\event\submission_created::create(array(
            'objectid' => $this->iassign->id,
            'context' => $this->context,
            'other' => $info
            ));
          $event->add_record_snapshot('course', $this->course);
          $event->trigger();
          }
        else { // else if (intval($iLM_PARAM_ActivityEvaluation) == 1)
          // Wrong answer...: get_answer_incorrect
          $status = 2;
          $grade_student = $iLM_PARAM_RealGrade; // evaluated as incorrect solution
          $msg = '<tr><td colspan=2>' . iassign_icons::insert('feedback_incorrect') . '<br>' . get_string('get_answer_incorrect', 'iassign') . '</td>' . "\n";

          // log record
          $info = $iassign->name . " - " . $this->activity->get_activity()->name . " - " . get_string('feedback_incorrect', 'iassign') .
            " - " . get_string('grade_iassign', 'iassign') . $grade_student;
          // Trigger module viewed event.
          $event = \mod_iassign\event\submission_created::create(array(
            'objectid' => $this->iassign->id,
            'context' => $this->context,
            'other' => $info
            ));
          $event->add_record_snapshot('course', $this->course);
          $event->trigger();
          } // else if (intval($iLM_PARAM_ActivityEvaluation) == 1)
        // Presents to the learner the result of the automatic evaluate?
        if ($this->activity->get_activity()->show_answer == 0) { // no...
          print '<tr><td width=60% ><strong>' . iassign_icons::insert('post') . get_string('get_answer', 'iassign') . '</strong></td>';
          print '<tr><td width=40% align=right>' . $return_last . '&nbsp;' . $link_return . '</td></tr>';
          print '<tr>';
          // log record
          $info = $iassign->name . "&nbsp;-&nbsp;" . $this->activity->get_activity()->name . "&nbsp;-&nbsp;" . get_string('get_answer', 'iassign');
          // Trigger module viewed event.
          $event = \mod_iassign\event\submission_created::create(array(
            'objectid' => $this->iassign->id,
            'context' => $this->context,
            'other' => $info
            ));
          $event->add_record_snapshot('course', $this->course);
          $event->trigger();
        } else { // yes!!!
          print '<tr><td width=60% ><strong>' . get_string('auto_result', 'iassign') . '</strong></td>';
          print '<td width=40% align=right>' . $return_last . '&nbsp;' . $link_return . '</td></tr>';
          print '<tr>';
          print $msg;
          }
        }
      else { // if ($iassign_ilm->evaluate == 1 && $this->activity->get_activity()->automatic_evaluate == 1)
        $status = 1;
        $grade_student = $iLM_PARAM_RealGrade; // iLM not have automatic evaluator

        print '<tr><td colspan=2>' . iassign_icons::insert('post') . get_string('get_answer_post', 'iassign') . '</td>';
        print '<tr><td width=40% align=right>' . $return_last . '&nbsp;' . $link_return . '</td></tr>';
        print '<tr>';

        // log record
        $info = $iassign->name . "&nbsp;-&nbsp;" . $this->activity->get_activity()->name . "&nbsp;-&nbsp;" . get_string('get_answer_post', 'iassign');
        $event = \mod_iassign\event\submission_created::create(array(
          'objectid' => $this->iassign->id,
          'context' => $this->context,
          'other' => $info
          ));
        $event->add_record_snapshot('course', $this->course);
        $event->trigger();
        } // if ($iassign_ilm->evaluate == 1)
      } // else of if ($iLM_PARAM_ArchiveContent == - 1 || empty($iLM_PARAM_ArchiveContent))
    print "</tr></table>\n";
    print $OUTPUT->box_end();

    // Insert new entry to {iassign_statement} or update it
    if ($this->write_solution == 1) {
      $timenow = time();

      // New record to table {iassign_submission}
      if (!$iassign_submission) {
        $activity_scale = $this->activity->get_activity()->grade; // get {iassign_statement}.grade (to be used to weighted the student grade from [0,1])
        $newentry = new stdClass();
        $newentry->userid = $this->userid_iassign;
        // $newentry->userid = $USER->id;
        $newentry->iassign_statementid = $this->activity->get_activity()->id;
        $newentry->timecreated = $timenow;
        $newentry->timemodified = $timenow;
        $newentry->answer = $iLM_PARAM_ArchiveContent;
        //2023/05/25 $newentry->grade = round($grade_student, 2);
        $newentry->grade = round($iLM_PARAM_RealGrade * $activity_scale, 2); // iLM must return through 'getEvaluation()' a value between 0 and 1
        $newentry->status = $status;
        $newentry->experiment = 1;

        if (!$newentry->id = $DB->insert_record("iassign_submission", $newentry)) {
          print_error('error_insert', 'iassign');
        } else {
          // Trigger module viewed event.
          $event = \mod_iassign\event\submission_created::create(array(
            'objectid' => $this->iassign->id,
            'context' => $this->context
            ));
          $event->add_record_snapshot('course', $this->course);
          $event->trigger();
          $this->update_grade_student($newentry->userid, $newentry->iassign_statementid, $this->iassign->id);
          }
      } elseif ($iassign_submission->status != 3) { // last submission is not correct
        $activity_scale = $this->activity->get_activity()->grade; // get {iassign_statement}.grade (to be used to weighted the student grade from [0,1])
        $newentry = new stdClass();
        $newentry->id = $iassign_submission->id;
        $newentry->iassign_statementid = $iassign_submission->iassign_statementid;
        $newentry->userid = $iassign_submission->userid;
        $newentry->timecreated = $iassign_submission->timecreated;
        $newentry->timemodified = $timenow;
        $newentry->answer = $iLM_PARAM_ArchiveContent;
        //2023/05/25 $newentry->grade = round($grade_student, 2);
        $newentry->grade = round($iLM_PARAM_RealGrade * $activity_scale, 2); // iLM must return through 'getEvaluation()' a value between 0 and 1
        $newentry->status = $status;
        $newentry->experiment = $iassign_submission->experiment + 1;
        if (!$DB->update_record("iassign_submission", $newentry)) {
          print_error('error_update', 'iassign');
          //D depurar...
          //D $stringAux = "ia.class.php: ".$iLM_PARAM_ArchiveContent."<br/> ".utf8_encode($iLM_PARAM_ArchiveContent)."<br/>".utf8_encode(utf8_encode($iLM_PARAM_ArchiveContent))."<br/>";
          //D $fp = fopen("teste1.txt","w");
          //D fwrite($fp,$stringAux);
        } else {
          // Trigger module viewed event.
          $event = \mod_iassign\event\submission_updated::create(array(
            'objectid' => $this->iassign->id,
            'context' => $this->context
            ));
          $event->add_record_snapshot('course', $this->course);
          $this->update_grade_student($newentry->userid, $newentry->iassign_statementid, $this->iassign->id);
          }
      } else { // elseif ($iassign_submission->status != 3)
        // last submission is correct
        if ($return_get_answer == 1) { // register the new solution, even if it is wrong!
          $activity_scale = $this->activity->get_activity()->grade; // get {iassign_statement}.grade (to be used to weighted the student grade from [0,1])
          $newentry = new stdClass();
          $newentry->id = $iassign_submission->id;
          $newentry->iassign_statementid = $iassign_submission->iassign_statementid;
          $newentry->userid = $iassign_submission->userid;
          $newentry->timecreated = $iassign_submission->timecreated;
          $newentry->timemodified = $timenow;
          $newentry->answer = $iLM_PARAM_ArchiveContent;
          //2023/05/25 $newentry->grade = round($grade_student, 2);
          $newentry->grade = round($iLM_PARAM_RealGrade * $activity_scale, 2); // iLM must return through 'getEvaluation()' a value between 0 and 1
          $newentry->status = $status;
          $newentry->experiment = $iassign_submission->experiment + 1;
          if (!$DB->update_record("iassign_submission", $newentry))
            print_error('error_update', 'iassign');
          else {
            $event = \mod_iassign\event\submission_updated::create(array(
              'objectid' => $this->iassign->id,
              'context' => $this->context
              ));
            $event->add_record_snapshot('course', $this->course);
            $event->trigger();
            $this->update_grade_student($newentry->userid, $newentry->iassign_statementid, $this->iassign->id);
            print $OUTPUT->box_start();
            print "<p>" . get_string('iassign_update', 'iassign') . "</p>";
            print $OUTPUT->box_end();
            }
        } elseif ($return_get_answer == 2) {
          print $OUTPUT->box_start();
          print "<p>" . get_string('iassign_cancel', 'iassign') . "</p>";
          print $OUTPUT->box_end();
        } else {
          print $OUTPUT->box_start();
          print "
     <script type='text/javascript'>
       //<![CDATA[
       function overwrite () {
         document.formEnvio.return_get_answer.value = 1;
         document.formEnvio.submit();
         }

       function nooverwrite () {
         document.formEnvio.return_get_answer.value = 2;
         document.formEnvio.submit();
         }
      //]]>
     </script>";
          $param_aux = "action=overwrite&iassign_submission_current=" . $iassign_submission->id . "&id=" . $id . "&iassign_current=" . $this->activity->get_activity()->id . "&write_solution=" . $this->write_solution . "&userid_iassign=" . $USER->id;
          $get_answer_overwrite = $CFG->wwwroot . "/mod/iassign/view.php?" . $param_aux;
          print "<form name='formEnvio' method='post' action='" . $get_answer_overwrite . "' enctype='multipart/form-data'>";
          print "<p>" . get_string('last_iassign_correct', 'iassign') . "</p>";
          print "<p>" . get_string('update_iassign', 'iassign') . "</p>";
          print "<input type='hidden' name='iLM_PARAM_ArchiveContent' value='" . $iLM_PARAM_ArchiveContent . "'/>
       <input type='hidden' name='iLM_PARAM_ActivityEvaluation' value='$iLM_PARAM_ActivityEvaluation'/>
       <input type='hidden' name='MA_POST_Info' value='" . $MA_POST_Info . "'/>
       <input type='hidden' name='MA_POST_SystemData' value='" . $MA_POST_SystemData . "'/>
       <input type='hidden' name='return_get_answer'/> ";
          print "<input type=button value='" . get_string('yes', 'iassign') . "' onClick = 'overwrite()'
      title='" . get_string('message_update_iassign', 'iassign') . "'/>\n";
          print "<input type=button value='" . get_string('no', 'iassign') . "' onClick = 'nooverwrite()'
      title='" . get_string('message_no_update_iassign', 'iassign') . "'/>\n";
          print " </form>\n";
          print $OUTPUT->box_end();
          }
        }
      } // if ($this->write_solution == 1)

    print $OUTPUT->footer();

    if ($submission_ajax == 1) {
      ob_end_clean();
      print 'OK';
      }

    die();
    } // function get_answer()


  /// Export in file the answer of student.
  function export_file_answer () {
    global $DB;

    $iassign_submission_id = optional_param('iassign_submission_id', NULL, PARAM_INT);

    $iassign_submission = $DB->get_record("iassign_submission", array("id" => $iassign_submission_id));
    $iassign_statement = $DB->get_record("iassign_statement", array("id" => $iassign_submission->iassign_statementid));
    $name = iassign_utils::format_filename(strip_tags($iassign_statement->name));

    $iassign_ilm = $DB->get_record("iassign_ilm", array("id" => $iassign_statement->iassign_ilmid));
    $extensions = explode(",", $iassign_ilm->extension);

    $iassign_user = $DB->get_record("user", array("id" => $iassign_submission->userid));
    $username = iassign_utils::format_filename($iassign_user->firstname . ' ' . $iassign_user->lastname);

    $name_answer = $username . '-' . $name . '-' . userdate($iassign_submission->timemodified, '%Y%m%d-%H%M') . '.' . $extensions[0];

    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private", false);
    header("Content-Type: document/unknown");
    header("Content-Disposition: attachment; filename=\"" . $name_answer . "\";");
    set_time_limit(0);
    print($iassign_submission->answer);
    exit;
    } // function export_file_answer()


  /// Prepare data to static exportation (with course name, iAsssing block name, and exercises names
  //  @calledby $this->export_package_answer()
  //TODO Precisa terminar! //TODO_HTML
  function htmlcode_2_export ($course_fullname, $course_id, $iassign_block_id, $iassign_block_name, $array_iassign_name, $array_iassign_id, $userid, $username) {
    $str_html  = "<html  dir='ltr' lang='pt-br' xml:lang='pt-br'>
 <head>
  <title>" . get_string('pluginname', 'iassign') . "</title>\n"; // 'iAssign: interactive Learning Activities'
    $str_html .= " </head>
 <body>\n";
    $tam = sizeof($array_iassign_name);
    for ($ii=0; $ii<$tam; $ii++) {
      //TODO_HTML completar construir HTML com titulo do exercicio e talvez legar com HTML para abri-lo com o iMA
      }
    $str_html .= "
 </body>\n</html>";
    return $str_html;
    }


  /// Export an package (zip) with all answer of students
  //  @calledby $this->action()
  function export_package_answer () {
    global $DB, $CFG, $OUTPUT;

    $iassign_id = optional_param('iassign_id', NULL, PARAM_INT); // ID of iAssign block of activities
    $iassign = $DB->get_record("iassign", array("id" => $iassign_id)); // all data from this block of iAssign activities (table '*_iassign')
    $iassign_block_name = iassign_utils::format_filename($iassign->name);

    $userid = optional_param('userid', NULL, PARAM_INT);
    $iassign_user = $DB->get_record("user", array("id" => $userid));
    $username = iassign_utils::format_filename($iassign_user->firstname . '_' . $iassign_user->lastname); // user information

    $current_date = date('Y_m_d_H');

    $directory = $CFG->dataroot . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR; // /var/<moodledata>/temp/files/
    // If temporary directory under Moodle data does not exists => create it
    if (!is_dir($directory)) {
      mkdir($directory, 0755, true); // permissions: drwxr-xr-x
      // created directory with permissions to be seen by everyone
      }

    $zip_filename = $directory . 'package_iassign_' . $username . '-' . $iassign_block_name . '_' . $current_date . '.zip';
    if (!is_writable($directory)) { // send a highlighted message!
      print_error('error_answer_export_dir', 'iassign'); // Error: the target directory has no permission! Please, send this message to the Moodle admin!
      exit; // just in case
      }
    //MOOC 2014: $zip_filename = $CFG->dataroot . '/temp/ilm-' . iassign_utils::format_pathname($iassign_ilm->name . '-v' . $iassign_ilm->version) . '.ipz';

    $zip = new zip_archive(); // create ZIP
    $zip->open($zip_filename); // open ZIP using the temporary file/directory

    $iassign_statements = $DB->get_records("iassign_statement", array("iassignid" => $iassign_id));
    //TODO $vet_exerc_title = array(); //TODO to be used with 'htmlcode_2_export(...)' - missing: build the HTML and directories linking files
    $vet_student_filename = array();
    $vet_student_content = array();
    foreach ($iassign_statements as $iassign_statement) {
      $activity_name = iassign_utils::format_filename(strip_tags($iassign_statement->name));
      $iassign_ilm = $DB->get_record("iassign_ilm", array("id" => $iassign_statement->iassign_ilmid));
      //TODO_HTML $vet_exerc_title[] = $iassign_statement->name;
      $extensions = explode(",", $iassign_ilm->extension);
      $iassign_submission = $DB->get_record("iassign_submission", array("iassign_statementid" => $iassign_statement->id, "userid" => $userid));
      if ($iassign_submission) {
        $timemodified = '-' . userdate($iassign_submission->timemodified, '%Y_%m_%d_%H_%M');
        $student_content = $iassign_submission->answer;
        $student_filename = iassign_utils::format_filename($activity_name) . $timemodified . '.' . $extensions[0];

        $resp = $zip->add_file_from_string($student_filename, $student_content); // add file to ZIP
        //$resp = $zip->add_file_from_string($path_dir . $one_file, $destination . $ds . $one_file); //2 Esta versao funciona!

        if ($resp) {
          $vet_student_filename[] = $student_filename;
          $vet_student_content[] = $student_content;
          }
        else { // send a highlighted message!
          print_error('error_answer_export_file', 'iassign'); // Error: fail to insert content to the file
          exit; // just in case
          }
        }
      } // foreach ($iassign_statements as $iassign_statement)

    $zip->close(); // close the ZIP file

    if (count($vet_student_filename)>0) {
      $vet_result = iassign_utils::register_temporary_file($zip_filename, $vet_student_filename, $vet_student_content);
      $result_tf = $vet_result[0];
      $filenameZip = $vet_result[1]->filename;
      }

    iassign_utils::download_file($zip_filename); // download the ZIP file
    exit;
    } // function export_package_answer()


  /// Editing status of interactive activities
  function edit_status () {
    global $USER, $DB, $OUTPUT;
    $newentry = new stdClass();
    $newentry->id = $this->iassign_submission_current;
    $newentry->status = optional_param('return_status', 0, PARAM_INT);

    $iassign_submission = $DB->get_record('iassign_submission', array('id' => $this->iassign_submission_current));
    if ($iassign_submission->status != 0 && $newentry->status == 0)
      $newentry->status = $iassign_submission->status;

    $newentry->teacher = $USER->id;
    if (!$DB->update_record('iassign_submission', $newentry))
      print_error('error_update', 'iassign');
    else {
      // Trigger module viewed event.
      $event = \mod_iassign\event\submission_updated::create(array(
        'objectid' => $this->iassign->id,
        'context' => $this->context
        ));
      $event->add_record_snapshot('course', $this->course);
      $event->trigger();
      $this->action = 'viewsubmission';
      $this->view_iassign_current();
      } // if (!$DB->update_record('iassign_submission', $newentry))
    } // function edit_status()


  /// Editing grade of interactive activities
  function edit_grade () {
    global $USER, $DB, $OUTPUT;

    $newgrade = optional_param('return_grade', 0, PARAM_INT);
    if ($newgrade && $newgrade >= 0) {
      $newentry = new stdClass();
      $newentry->id = $this->iassign_submission_current;
      $newentry->grade = optional_param('return_grade', 0, PARAM_INT);
      $newentry->teacher = $USER->id;
      if (!$DB->update_record('iassign_submission', $newentry))
        print_error('error_update', 'iassign');
      else {
        // Trigger module viewed event.
        $event = \mod_iassign\event\submission_updated::create(array(
          'objectid' => $this->iassign->id,
          'context' => $this->context
          ));
        $event->add_record_snapshot('course', $this->course);
        $event->trigger();
        }
      } // if ($newgrade >= 0)

    $this->action = 'viewsubmission';
    $this->view_iassign_current();
    } // function edit_grade()


  /// Add or Edit interactive activities
  //  Get here when editing form and and submitted form data
  //  To each activity, we have an associated file in Moodle Data (registered in {files}) with: {files}.itemid := {iassign_statement}.id and {iassign_statement}.filesid := {files}.id
  //  Given a {iassign_statement}.id it is possible to recover its associated {files} using this (e.g. {iassign_statement}.id=10):
  //  SELECT cm.course, cm.id, cm.instance, c.instanceid, c.id AS contextid, f.id AS files_id, f.itemid, f.author, f.timecreated, f.filename,
  //   ia.id AS iaid, ias.file, ia.name AS ianame, ias.file AS iasfile, ias.id AS iasid, ias.name AS iasname, ias.timecreated
  //  FROM {modules} AS m, {course_modules} AS cm, {context} AS c, {files} AS f, {iassign} AS ia, {iassign_statement} AS ias
  //  WHERE m.name='iassign' AND m.id=cm.module AND cm.id = c.instanceid AND cm.instance = ia.id AND c.id=f.contextid AND f.component='mod_iassign'
  //   AND ia.id=ias.iassignid AND (f.itemid=ias.file OR f.itemid=ias.id) AND ias.id=10
  //  @calledby $this->action(): with "'add' => '$this->add_edit_iassign();'," or "'edit' => '$this->add_edit_iassign();',"
  function add_edit_iassign () {
    global $USER, $CFG, $COURSE, $DB, $OUTPUT;
    require_once('iassign_form.php');

    $id = $this->cm->id; // All iAssign statement => iassign_statement.iassignid
    $iassignid = $this->iassign->id;

    $param = new stdClass(); // parameters to build data to the form - see bellow "$mform = new mod_iassign_form(...); $mform->set_data($param);

    $param->action = $this->action; // hidden
    $param->id = $id; // hidden

    $param->special_param1 = 0; // 'special_param1 == 1' => script of iGeom

    // Trick to pass value to the Moodle form that is loaded bellow (with "$mform = new mod_iassign_form(null,...)")
    // the last 2 are used in "iassign_form.php!definition()"
    //x $COURSE->cm = $id; // "$id = $COURSE->cm;" but not used...
    $COURSE->iassignid = $iassignid; // "$iassigns = $DB->get_records('iassign_statement', array('iassignid' => $COURSE->iassignid));"
    $COURSE->iassign_file_id = NULL; // "if (!is_null($COURSE->iassign_file_id))"

    // Associated {files} are registered with contextid from {context}.contextlevel = CONTEXT_MODULE
    // ./lib/accesslib.php : CONTEXT_MODULE = 70 (CONTEXT_COURSE = 50)
    $context = context_module::instance($this->cm->id); // use context from this course

    $component = 'mod_iassign'; // to {files}
    $filearea = 'exercise';

    //x TODO remove this after a while (2023/04/19) - here $this->iassign_current is empty and $COURSE->iassign_id is never used!
    if (!empty($this->iassign_current)) $COURSE->iassign_id = $this->iassign_current;
    else $COURSE->iassign_id = 0;

    $total_of_activities = 0; // get the total number of activities in this iAssign block

    if ($this->action == 'add') {
      $iassign_data = $DB->get_record("iassign", array('id' => $iassignid));

      $params_temp = array('iassignid' => $iassignid);

      $query_str = "SELECT s.id, s.name, s.dependency FROM {iassign_statement} s WHERE s.iassignid = :iassignid ORDER BY s.position ASC";
      $iassign_statement = $DB->get_records_sql($query_str, $params_temp); //
      $total_of_activities = count($iassign_statement);

      $param->iassignid = $iassignid;
      $param->name = "";
      $param->oldname = "";
      $param->type_iassign = 3;
      $param->proposition = "";
      $author = $USER; //x $DB->get_record("user", array("id" => $USER->id)); --- useless since we already have {user} info.!!!
      $param->author_name = $author->firstname . ' ' . $author->lastname . ' (' . $author->id . ')';
      $param->author_modified_name = $author->firstname . ' ' . $author->lastname . ' (' . $author->id . ')';
      $param->author = $param->author_name;
      $param->author_modified = $param->author_modified_name;
      $COURSE->iassign_list = array();
      $param->iassign_list = array();
      if ($iassign_statement) {
        foreach ($iassign_statement as $iassign) {
          $iassignid = $iassign->id;
          $param->iassign_list[$iassignid] = 0;
          $COURSE->iassign_list[$iassignid] = new stdClass();
          $COURSE->iassign_list[$iassignid]->id = $iassignid;
          $COURSE->iassign_list[$iassignid]->name = $iassign->name;
          $COURSE->iassign_list[$iassignid]->enable = 1;
          } // foreach ($iassign_statement as $iassign)
        }
      $param->iassign_ilmid = 0;
      $param->file = 0;
      $param->fileid = 0; // to receive {files}.id
      $param->fileold = 0;
      $param->filename = "";
      $param->grade = $iassign_data->grade;
      $param->timemodified = time();
      $param->timecreated = time();
      $param->timeavailable = $iassign_data->timeavailable;
      $param->timedue = $iassign_data->timedue;
      $param->preventlate = $iassign_data->preventlate;
      $param->test = $iassign_data->test;
      $param->special_param1 = 0;
      $param->visible = 1;
      $param->max_experiment = $iassign_data->max_experiment;
      $param->dependency = 0;
      $param->automatic_evaluate = 1;
      $param->show_answer = 1;
      } // if ($this->action == 'add')
    elseif ($this->action == 'edit') {
      // First form load
      // The update is effectively registered in {iassign_statement} and {files} bellow,
      // in the second "elseif ($this->action == 'edit')": $iassignid = $this->activity->update_iassign($result);
      $COURSE->iassign_list = array();
      $this_activity = $this->activity->get_activity();

      if ($this_activity != null) { // first enter here: fill form data
        $iassign_statement_current = $this_activity; // $DB->get_record("iassign_statement", array("id" => $this_activity->id));
        $param->iassign_id = $iassign_statement_current->id; // oculto
        $param->iassignid = $iassign_statement_current->iassignid; // oculto
        $param->name = $iassign_statement_current->name;
        $param->oldname = $iassign_statement_current->name;
        $param->type_iassign = $iassign_statement_current->type_iassign;
        $param->store_all_submissions = $iassign_statement_current->store_all_submissions;
        $param->proposition = array('text' => $iassign_statement_current->proposition, ''); //'format' => $instance->introformat
        $param->author_name = $iassign_statement_current->author_name; // hidden field
        $param->author = $iassign_statement_current->author_name;
        $author = $USER; //x $DB->get_record("user", array('id' => $USER->id)); --- useless since we already have {user} info.!!!
        $param->author_modified_name = $author->firstname . ' ' . $author->lastname . ' (' . $author->id . ')';
        $param->author_modified = $param->author_modified_name;
        $dependency = explode(';', $iassign_statement_current->dependency);
        $param->iassign_list = array();

        // Select all other iAssign activities inside this block with dependency
        $str_query = "SELECT * FROM {iassign_statement} WHERE iassignid = '" . $iassignid . "' AND id!='" . $iassign_statement_current->id . "' AND dependency!=0";
        $iassign_statement_dependency = $DB->get_records_sql($str_query);

        $array_dependency = array();
        $subdependency = "";
        $sub_subdependency = "";
        // Dependents on this exercise
        if ($iassign_statement_dependency) {
          $subdependency .= $this->search_dependency($iassign_statement_current->id, $iassign_statement_dependency);

          // to whom this exercise depends
          foreach ($subdependency as $tmp)
            $sub_subdependency .= $this->search_sub_dependency($tmp);

          $list_dependency = $subdependency . $sub_subdependency;
          $array_dependency = explode(";", $list_dependency);
          }

        // Get all other activities in the same iAssigment block:
        $str_query = "SELECT id, name FROM {iassign_statement} WHERE iassignid = '" . $iassignid . "' AND id!='" . $iassign_statement_current->id . "' ORDER BY position ASC";
        $all_others_iassign_statement = $DB->get_records_sql($str_query);

        $total_of_activities = count($all_others_iassign_statement) + 1;

        $inter = array();
        if ($all_others_iassign_statement) {
          foreach ($all_others_iassign_statement as $iassign)
            if (in_array($iassign->id, $dependency))
              $inter[] = $iassign->id;
          }

        if ($all_others_iassign_statement) {
          foreach ($all_others_iassign_statement as $iassign) {
            $iassignid = $iassign->id;
            $COURSE->iassign_list[$iassignid] = new stdClass();
            $COURSE->iassign_list[$iassignid]->name = $iassign->name;
            $COURSE->iassign_list[$iassignid]->id = $iassignid;

            if (in_array($iassignid, $dependency))
              $param->iassign_list[$iassignid] = 1;
            else
              $param->iassign_list[$iassignid] = 0;

            if (in_array($iassignid, $array_dependency))
              $COURSE->iassign_list[$iassignid]->enable = 0;
            else
              $COURSE->iassign_list[$iassignid]->enable = 1;
            } // foreach ($all_others_iassign_statement as $iassign)
          } // if ($all_others_iassign_statement)

        $param->iassign_ilmid = $iassign_statement_current->iassign_ilmid; // define iLM ID to be used to define 'select' on 'iassign_form.php'
        $param->fileold = 0;
        $param->filesid = 0;
        $param->file = $iassign_statement_current->file;    // old version of {iassign_statement}/{files} association
        $param->filesid = 0; // new version of {iassign_statement}/{files} association: {iassign_statement}.filesid := {files}.id and {files}.itemid := {iassign_statement}.id
        $param->filename = '';
        $param->grade = $iassign_statement_current->grade;
        $param->timecreated = $iassign_statement_current->timecreated; // hidden
        $param->timeavailable = $iassign_statement_current->timeavailable;
        $param->timedue = $iassign_statement_current->timedue;
        $param->preventlate = $iassign_statement_current->preventlate;
        $param->test = $iassign_statement_current->test;
        $param->special_param1 = $iassign_statement_current->special_param1;  // 'special_param1 == 1' => script of iGeom
        $param->position = $iassign_statement_current->position; // hidden
        $param->visible = $iassign_statement_current->visible;
        $param->max_experiment = $iassign_statement_current->max_experiment;
        $param->automatic_evaluate = $iassign_statement_current->automatic_evaluate;
        $param->show_answer = $iassign_statement_current->show_answer;

        // Association {iassign_statement} with {files}
        // Attention to the {files}.contextid and {files}.itemid used: "{files}.itemid" must be "{iassign_statement}.id" (and {iassign_statement}.filesid={files}.id)
        // Old version used: {iassign_statement}.files = {files}.itemid with a randam value...
        // ./lib/filestorage/file_storage.php: public function get_area_files($contextid, $component, $filearea, $itemid = false,...)
        $fs = get_file_storage(); // Get reference to all files in Moodle data

        //2021/12 $files_list = $fs->get_area_files($context->id, $component, $filearea, $iassign_statement_current->filesid);
        //2022/02 $files_list = $fs->get_area_files($context->id, $component, $filearea, $iassign_statement_current->id); // em alguns "_files.id" tem 'file' NAO 'id'
        $iassign_statement_id = $iassign_statement_current->id;
        $iassign_statement_file = $iassign_statement_current->file;
        $iassign_statement_filesid = $iassign_statement_current->filesid;
        //D if ($USER->id == 3) // Here: function add_edit_iassign() : elseif ($this->action == 'edit') : if ($this_activity != null) 
        //D echo "<br/><br/><br/><br/>locallib.php: add_edit_iassign(): user.id=3: {ia}.id=" . $iassign_statement_id . ", contextid=" . $context->id . "<br/>\n"; //leo

        // Get the element associated to {iassign_statement} in {files} using 'files_functions.php!get_from_files($ia_id, $ia_file, $ia_filesid, $fs, $contextid, $component, $filearea)'
        require_once('files_functions.php'); // Get auxiliary functions to manage {iassign_statement}/{files}
        $answer_array = get_from_files($iassign_statement_id, $iassign_statement_file, $iassign_statement_filesid, $fs, $context->id, $component, $filearea);
        // if $f2_obj!=NULL but $f2_obj==NULL, then probably $f1_obj was recovered by 'files_functions.php!get_from_filesAll(.)' in the previous edition

        if ($answer_array==NULL) {
          if ($USER->id == 3) { //D Debug study - only {user}.id==3 sees it
            echo " x locallib.php!add_edit_iassign(.): ERRO! {iassign_statement}.id=" . $iassign_statement_id . ", contextid=" . $context->id . " NAO esta' com {files} associado!<br/>\n"; //D
            echo "Error: answer_array==NULL<br/>"; //D
            echo "invalidid = " . get_string('invalidid', 'iassign') . "<br/>\n"; //D
            // {files} : id contenthash pathnamehash contextid component filearea itemid filepath filename userid filesize mimetype status source author license timecreated
            }

          // Try to recover file in the same context with the smallest Levenstein distance from the activity name:
          // levenshtein($str1,$str2) is the minimal number of characters you have to replace, insert or delete to transform $str1 into $str2
          $ia_name = $iassign_statement_current->name;
          $mindist_levenshtein = strlen($ia_name);
          $mindist_component = '';
          $answer_array = get_from_filesAll($fs, $context->id, 'exercise', $filearea); // $component='exercise'
          if ($USER->id == 3) { //D Debug study - only {user}.id==3 sees it
            if (is_null($answer_array) || !is_array($answer_array)) $count_arr = 0; //D
            else $count_arr = count($answer_array); //D
            }
          foreach ($answer_array as $one_files)
            if ($one_files->filename!='.') {
              $dist_levenshtein = levenshtein($one_files->filename,$ia_name);
              if ($dist_levenshtein <= $mindist_levenshtein) { // smaller distance (get always the last one - more recent one)
                $mindist_levenshtein = $dist_levenshtein;
                $mindist_component = 'exercise';
                $f1_obj = $one_files;
                }
              } // if ($one_files->filename!='.')
          $answer_array = get_from_filesAll($fs, $context->id, 'activity', $filearea); // $component='activity'
          foreach ($answer_array as $one_files)
            if ($one_files->filename!='.') {
              $dist_levenshtein = levenshtein($one_files->filename,$ia_name);
              if ($dist_levenshtein <= $mindist_levenshtein) { // smaller distance (get always the last one - more recent one)
                $mindist_levenshtein = $dist_levenshtein;
                $mindist_component = 'activity';
                $f1_obj = $one_files;
                }
              } // if ($one_files->filename!='.')
          // Now get as a 'file_storage' object (not a generic one returned fron $DB->get_records('files',...)
          //./lib/filestorage/file_storage.php: public function get_file_instance(stdClass $filerecord)
          if ($f1_obj != NULL) {
            $f1_obj = $fs->get_file_instance($f1_obj);
            }
          $f2_obj = NULL;
          //D print_error('invalidid', 'iassign'); // $string['invalidid'] = 'Invalid ID';
          //D exit; // do not need 'print_error(.)' will stop execution
          }
        else { //DEBUG if ($answer_array==NULL)
          //DEBUG evitar mensagens de depuracao no caso de erro de 'files_functions.php!get_from_files(.)' devolver vazio
          $f1_obj = $answer_array[0]; // regular 'filename'
          $f2_obj = $answer_array[1]; // directory (filename == '.')
          } //DEBUG REMOVER if ($answer_array==NULL)

        $ia_obj = $iassign_statement_current;

        // 2022/10/07 Keep this until next iAssign version update
        // ATTENTION: Some iAssign activity could have only one entry in {files} => came from duplication (using the same Moodle Data file)
        //            TODO: every duplication of activity must generate a new copy of actual file in Moodle Data
        //TOD: Quem nao esta' duplicando {files}: "duplicar" ou "backup"?
        // Ver: /home/leo/projetos/iMA/lms/itarefa/novo/files_iassign_statement/sobre_recuperacao_ia_files.txt

        // It must have: {files}->id=$iassign_statement_current->file
        if ($f1_obj != NULL) {
          // ---------------------------------------------------------------------------------------------------------------------------------
          // Try to recover association {iassign_statement}/{files}
          // It uses "files_functions.php ! update_iassignstatement_files(...)" to (eventually) reconstruct relation
          $result = update_iassignstatement_files($DB, $ia_obj->id, $f1_obj->get_id(), $ia_obj, $f1_obj, $f2_obj, $context); // ./files_functions.php
          if (!$result) print "Could not change {iassign_statement}.filesid=" . $ia_obj->id . " or {files}.itemid=" . $f1_obj->itemid . "<br/>";
          // $files_list = iassign::get_update_files($context->id, $component, $filearea, $iassign_statement_current, $fs); //2022: tentativa de recupearar arquivos se perdido

          if ($f1_obj->get_filename() != '.') { // additional security: ensure the first {files} has real "filename"
            $param->filename = $f1_obj->get_filename();
            $param->file = $f1_obj->get_id();
            $param->fileold = $f1_obj->get_id();
            $COURSE->iassign_file_id = $f1_obj->get_id(); // necessary to "iassign_form.php" (loaded bellow with "$mform = new mod_iassign_form(...)")
            }
          } // if ($f1_obj != NULL)

        //TODO //MOOC2014 -- get special parameters?
        //D  $iassign_ilm_configs = $DB->get_records('iassign_statement_config', array('iassign_statementid' => $iassign_statement_current->id));
        //D  if ($iassign_ilm_configs) {
        //D  foreach ($iassign_ilm_configs as $iassign_ilm_config)
        //D  $param->{'param_'.$iassign_ilm_config->iassign_ilm_configid} = $iassign_ilm_config->param_value;
        //D  } //MOOC2014 --
        } // if ($this_activity != null)

      } // elseif ($this->action == 'edit')

    //D Get all iActivity in the iAssign block with id = $this->iassign->id ($this->iassign has data from the form)
    //D It could be used to define dependency of the current activity (considering those list $iassign_list)
    //D $iassign_list = $DB->get_records_list('iassign_statement', 'iassignid', array($this->iassign->id), 'position ASC'); //2022: not used any more
    //D echo "locallib.php: add_edit_iassign(): this->iassign->id=" . $this->iassign->id . "<br/>";
    //D foreach ($iassign_list as $item) echo " - id=" . $item->id . ",  name=" . $item->name . ", iassignid=" . $item->iassignid . ",  type_iassign=" . $item->type_iassign . "<br/>";
    //D if ($iassign_list) { $end_list = array_pop($iassign_list); $param->position = $end_list->position + 1; } else $param->position = 1;

    $param->position = $total_of_activities; // $total_of_activities = count($all_others_iassign_statement) + 1;

    // Create form and associate its data that will be finally processed by 'locallib.php!update_iassign($param)'
    // If {iassign_ilm}.enable == 0, then iLM remains undefined (does not appear in iLM selection)
    // 'mform1' is used in: ./mod/iassign/iassign_form.php, ./mod/iassign/settings_form.php
    // new mod_iassign_form(...) in ./mod/iassign/iassign_form.php extends /lib/formslib.php:
    // ./lib/formslib.php: __construct($action=null, $customdata=null, $method='post', $target='', $attributes=null, $editable=true, $ajaxformdata=null)
    if (isset($param->iassign_ilmid)) {
      // array is additional atributes to 'form'
      // 'special_param1 == 1' => script of iGeom : <select class="custom-select" name="special_param1" id="id_special_param1"> "Assignment with script(algorithm)? (Applicable only to iGeom iLM)"
      $mform = new mod_iassign_form(null, array('special_param1'=>$param->special_param1),
        'post', null, array('id'=>'mform1', 'iassign_ilmid'=>$param->iassign_ilmid, 'author'=>$param->author, 'author_name'=>$param->author), true, null);
      }
    else {
      $mform = new mod_iassign_form(null, null, null, null, array('id'=>'mform1')); // ./mod/iassign/iassign_form.php
      }
    // After "submit" button is pushed '$mform->get_data()' is sent to 'function update_iassign($param)'

    $mform->set_data($param); // define data in the form

    if ($mform->is_cancelled()) {
      $this->return_home_course('iassign_cancel');
      exit;
      }
    else { // else if ($mform->is_cancelled())

      $result = $mform->get_data();

      if ($result) { // from: $result = $mform->get_data();
        $result->context = $context;

        if ($result->type_iassign == 1 || $result->type_iassign == 2)
          $result->grade = 0;
        if ($result->type_iassign == 1) {
          $result->automatic_evaluate = 0;
          $result->show_answer = 0;
          }
        elseif ($result->automatic_evaluate == 0)
          $result->show_answer = 0;

        // $_POST['iassign_list']
        $result->iassign_list = optional_param_array('iassign_list', array(), PARAM_RAW);
        if ($result->iassign_list) {
          foreach ($result->iassign_list as $key => $value)
            $result->dependency .= $key . ';';
          }
        else
          $result->dependency = 0;

        //TODO I used in 'iassign_form.php' '<select name='iassign_ilmid'...>' with 'optgroup' by hand! But MoodleForm clear/do not register the 'iassign_ilmid'
        //TODO Then get it directly from the form data $_POST!
        //TODO See: 'iassign_form.php' field 'iassign_ilmid' and here 'function new_iassign($param)'
        if (!isset($result->iassign_ilmid) && isset($_POST['iassign_ilmid']))
          $result->iassign_ilmid = $_POST['iassign_ilmid'];

        // $iassign_ilm = $DB->get_record("iassign_ilm", array("id" => $result->iassign_ilmid)); //2022: not used any more
        if ($this->action == 'add') {
          $iassign_statement_name = $DB->get_records('iassign_statement', array('iassignid' => $result->iassignid, 'name' => $result->name));
          if ($iassign_statement_name) { // if this name already exist (in the same iAssign block) launch message error!
            $this->return_home_course('error_iassign_name');
            die();
            }

          // Really insert the new iActivity in table {iassign_statement}
          // The form of 'ilm_manager.php' provides the {files} using its id (in GET variavel 'fileid') - copy it in {iassign_security}
          // $file = $mform->save_stored_file(...)
          $iassignid = $this->activity->new_iassign($result); // class activity : function new_iassign($param)

          $this->activity->add_calendar($iassignid); // here calls static function add_calendar($iassignid): calendar_event::create($event); [5885/9304]

          // Trigger module viewed event.
          $event = \mod_iassign\event\iassign_created::create(array(
            'objectid' => $iassignid,
            'context' => $context
            ));

          $event->add_record_snapshot('course', $this->course);
          $event->trigger();

          $this->return_home_course('iassign_add');
           } // if ($this->action == 'add')
        elseif ($this->action == 'edit') {
          // Here is the real registration in {iassign_statement} and {files}
          // Above: $result = $mform->get_data();
          $iassignid = $this->activity->update_iassign($result); // important: $result->file has the {files}.id selected

          $this->activity->update_calendar($iassignid, $result->oldname);
          // Trigger module viewed event.
          $event = \mod_iassign\event\iassign_updated::create(array(
            'objectid' => $iassignid,
            'context' => $context
            ));
          $event->add_record_snapshot('course', $this->course);
          $event->trigger();
          $this->return_home_course('iassign_update');
          } // elseif ($this->action == 'edit')

        die();
        } // if ($result): from $result = $mform->get_data();
      } // else if ($mform->is_cancelled())

    print $OUTPUT->header();
    $mform->display();
    print $OUTPUT->footer();

    die();
    } // function add_edit_iassign()


  /// Search for dependencies
  function search_dependency ($search_iassing_id, $iassign_statement) {
    global $DB, $OUTPUT;
    $dependency = "";
    if ($iassign_statement)
      foreach ($iassign_statement as $iassign) {
        $inter_dependency = explode(';', $iassign->dependency);
        if (in_array($search_iassing_id, $inter_dependency)) {
          $dependency .= $iassign->id . ";";
          $dependency .= $this->search_dependency($iassign->id, $iassign_statement);
          } // if (in_array($search_iassing_id, $inter_dependency))
        } // foreach ($iassign_statement as $iassign)
    return $dependency;
    }


  /// Search for "sub"dependency
  function search_sub_dependency ($search_iassing_id) {
    global $DB, $OUTPUT;

    $iassign_statement = $DB->get_record("iassign_statement", array("id" => $search_iassing_id));

    $dependency = "";
    if ($iassign_statement) {
      $inter_dependency = explode(';', $iassign_statement->dependency);

      foreach ($inter_dependency as $tmp) {
        if ($tmp != 0)
          $dependency .= $tmp . ";";
        $dependency .= $this->search_sub_dependency($tmp);
        } // foreach ($inter_dependency as $tmp)
      } // if ($iassign_statement)
    return $dependency;
    } // function search_sub_dependency($search_iassing_id)


  // Warning message
  static function warning_message_iassign ($strcode) {
    return "<div class='warning' style='display:inline; font-weight: bold; color:#a00'>" . get_string($strcode, 'iassign') . "</div>\n";
    }


  /// Update grade of iAssign
  //  Called always any iAssign activity is created
  //  @see /mod/iassign/view.php: call to iassign->iassign(): starting point
  static function update_grade_iassign ($iassignid) {
    global $USER, $CFG, $COURSE, $DB, $OUTPUT;
    require_once($CFG->libdir . '/gradelib.php');
    //D $sum_grade = $DB->get_records_sql ( "SELECT SUM(grade) as total
    //D FROM {$CFG->prefix}iassign_statement s WHERE s.iassignid = '$iassignid' AND s.type_iassign=3" );
    //TODO: REVIEW: wich one is more efficienty, '$DB->get_records' geting objects or '$DB->get_records' with 'foreach'?
    // Each iAssign item is associated with one item on the "gradebook"
    // Sum all '*_iassign_statement' associated with one item in '*_grade_items': iassignid AND type_iassign=3
    //$sum_grade = 0;
    //$grade = $DB->get_records('iassign_statement', array('iassignid' => $iassignid, 'type_iassign' => 3));
    //foreach($grade as $tmp) {
    //    $sum_grade += $tmp->grade;
    // }
    //1 Solution 1
    //1 $grade = $DB->get_records('iassign_statement', array('iassignid' => $iassignid, 'type_iassign' => 3)); //1
    //1 $sum_grade = 0; //1
    //1 foreach ($grade as $tmp) { $sum_grade += $tmp->grade; } //1
    //2 Solution 2
    $array_sum_grade = $DB->get_records_sql("SELECT SUM(grade) as total FROM {iassign_statement} s WHERE s.iassignid = '$iassignid' AND s.type_iassign=3"); //2
    //2 foreach ($array_sum_grade as $array_item) { $sum_grade = $array_item->total; break; } // nao necessario, basta 'key(...)' abaixo
    if (key($array_sum_grade))
      $sum_grade = key($array_sum_grade); //2
    else
      $sum_grade = 0; //2

    $grade_iassign = $DB->get_record("iassign", array("id" => $iassignid));
    $grades = NULL;
    $params_temp = array('itemname' => $grade_iassign->name);
    $params_temp['iteminstance'] = $iassignid;
    $params_temp['gradetype'] = GRADE_TYPE_VALUE;
    //2016 if ($sum_grade != 0) {
    $params_temp['grademax'] = $sum_grade;
    $params_temp['rawgrademax'] = $sum_grade;
    //2016 } else { $params_temp['grademax'] = 0; $params_temp['rawgrademax'] = 0; }
    $params_temp['grademin'] = 0;
    // @calls /lib/gradelib.php: function grade_update($source, $courseid, $itemtype, $itemmodule, $iteminstance, $itemnumber, $grades=null, $itemdetails=null, $isbulkupdate=false)
    //                           It uses the table {grade_items}
    //TODO: is there any error here in Moodle version 3.0?
    //TODO: Incorrect property 'grademax' found when inserting grade object
    //TODO: line 899 of /mod/iassign/locallib.php: call to grade_update()
    grade_update('mod/iassign', $grade_iassign->course, 'mod', 'iassign', $iassignid, 0, $grades, $params_temp);
    }


  /// Update grade of student
  //  {iassign_statement}.grade  = maximum grade to this activity
  //  {iassign_submission}.grade = full grade in this submission (considering [0,1]*{iassign_statement}.grade)
  function update_grade_student ($userid, $iassign_statementid, $iassignid) {
    global $CFG, $DB, $OUTPUT;
    require_once($CFG->libdir . '/gradelib.php');
    $grade_iassign = $DB->get_record('iassign', array('id' => $iassignid));

    // Review all the student submission for this iAssign activity
    $grade_iassign_statements = $DB->get_records('iassign_statement', array('iassignid' => $iassignid));
    $total_grade = 0;
    foreach ($grade_iassign_statements as $grade_iassign_statement) {
      $iassign_submission = $DB->get_record('iassign_submission', array('iassign_statementid' => $grade_iassign_statement->id, 'userid' => $userid));
      if ($iassign_submission) // sum all {iassign_submission}.grade
        $total_grade += $iassign_submission->grade;
      } // foreach ($grade_iassign_statements as $grade_iassign_statement)
    //D $sum_grade = $DB->get_records_sql("SELECT SUM(grade) as total
    //D  FROM {$CFG->prefix}iassign_statement s WHERE s.iassignid = '$iassignid' AND s.type_iassign=3" );
    //TODO: REVIEW: wich one is more efficienty, '$DB->get_records' geting objects or '$DB->get_records' with 'foreach'?
    //1 $sum_grade = 0; $grade = $DB->get_records('iassign_statement', array('iassignid' => $iassignid, 'type_iassign' => 3));
    //1 foreach ($grade as $tmp) { $sum_grade += $tmp->grade; }
    // For each {iassign_statement} in the block {iassign}, sum their max grade (field {grade})
    $array_sum_grade = $DB->get_records_sql("SELECT SUM(grade) as total FROM {iassign_statement} s WHERE s.iassignid = '$iassignid' AND s.type_iassign=3"); //2
    if (key($array_sum_grade))
      $sum_grade = key($array_sum_grade); //2
    else
      $sum_grade = 0; //2

    $grades['userid'] = $userid;
    $grades['rawgrade'] = $total_grade; // sum of all submissions for this iAssign activity
    $params_temp = array('itemname' => $grade_iassign->name);
    $params_temp['iteminstance'] = $iassignid;
    $params_temp['gradetype'] = GRADE_TYPE_VALUE;

    //2016 if ($sum_grade != 0) { // depois eliminar comentario
    $params_temp['grademax'] = $sum_grade;
    $params_temp['rawgrademax'] = $sum_grade;
    //2016 } else { $params_temp['grademax'] = 0; $params_temp['rawgrademax'] = 0; }
    
    // Now write the grade of iAssign activity (sum of all of its {iassign_statement}.grade) to the Moodle grade book
    // @calls /lib/gradelib.php: function grade_update($source, $courseid, $itemtype, $itemmodule, $iteminstance, $itemnumber, $grades=null, $itemdetails=null, $isbulkupdate=false)
    //                           It uses the table {grade_items}
    grade_update('mod/iassign', $grade_iassign->course, 'mod', 'iassign', $iassignid, 0, $grades, $params_temp);
    } // function update_grade_student($userid, $iassign_statementid, $iassignid)


  /// Display caption of icons - to the bottom page of teacher's report
  function view_legend_icons () {
    global $USER, $CFG, $DB, $OUTPUT;
    $id = $this->cm->id;

    if ($this->action == 'print')
      print '<table border=1 width=100%><tr>';
    else
      print '<table width=100%><tr>';
    print '<td >';

    if (has_capability('mod/iassign:viewreport', $this->context, $USER->id) && $this->action == 'report') {
      if ($this->action != 'print') {
        $link_print = "<a href='" . $CFG->wwwroot . "/mod/iassign/view.php?id=" . $id . "&action=print&iassignid=" . $this->iassign->id . "'>" . iassign_icons::insert('print') . '&nbsp;' . get_string('print', 'iassign') . "</a>\n";
        $link_export = "<a href='" . $CFG->wwwroot . "/mod/iassign/view.php?id=" . $id . "&action=export_csv&iassignid=" . $this->iassign->id . "'>" . iassign_icons::insert('export_ilm') . '&nbsp;' . get_string('export_csv', 'iassign') . "</a>\n";
        print '<td width=10% align="right">' . $link_export . '</td>' . "\n";
        print '<td width=15% align="right">' . $link_print . '</td>' . "\n";
        } // if ($this->action != 'print')
      print '</tr></table>' . "\n";
      } // if (has_capability('mod/iassign:viewreport', $this->context, $USER->id) && $this->action == 'report')
      print '</td></tr></table>' . "\n";
    } // function view_legend_icons()


  /// Display activity current
  //  @calledby view() -> action() : when student do/redo activity and teacher see student answer
  function view_iassign_current () {
    global $USER, $CFG, $COURSE, $DB, $OUTPUT, $PAGE;
    $id = $this->cm->id;
    $iassignid = $this->iassign->id;

    $only_one_send_button = 0; // to avoid to put 2 copies of comments (area to send and see comments between teacher and student)

    $iassign = $DB->get_record("iassign", array("id" => $iassignid)); // from table '*_iassign': id course name intro introformat activity_group grade timeavailable timedue preventlate test max_experiment

    // Get data of current activity : table '*_iassign_statement' = id name iassignid type_iassign proposition author_name author_modified_name iassign_ilmid file grade timemodified timecreated ...
    $iassign_statement_activity_item = $this->activity->get_activity(); // get all field to the current activity: the teacher activity model in {iassign_statement}

    if (!$iassign_statement_activity_item) {
      print $OUTPUT->header();
      \core\notification::error(get_string('activity_not_found', 'iassign'));
      print $OUTPUT->footer();
      exit;
      }

    $ilm = new ilm($iassign_statement_activity_item->iassign_ilmid);

    $ilm_name = '';
    if ($ilm->ilm)
      $ilm_name = strtolower($ilm->ilm->name); // class ilm has a unique property ('ilm'), get the iLM name
    // Do not allow the learner resent his solution only is iGeom iLM ("igeom") - why? iGeom acitivity "model answer" is not sent with the learner solution
    $allow_resubmission = (substr($ilm_name, 0, 5) != "igeom" ? 1 : 0);

    // log record
    $info = $iassign->name . ":" . $iassign_statement_activity_item->name;
    //Trigger module viewed event.
    $event = \mod_iassign\event\submission_viewed::create(array(
      'objectid' => $iassign->id,
      'context' => $this->context,
      'other' => $info
      ));
    $event->add_record_snapshot('course', $this->course);
    $event->trigger();

    // Search of iLM data used in the current activity
    $iassign_ilm = $DB->get_record("iassign_ilm", array("id" => $iassign_statement_activity_item->iassign_ilmid));

    // Add actual activity to navbar:
    $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $PAGE->navbar->add($iassign_statement_activity_item->name, $actual_link);

    print $OUTPUT->header();

    if ($this->action == 'viewsubmission') {
      if (!empty($this->iassign_submission_current) || $this->iassign_submission_current != 0)
        $iassign_submission = $DB->get_record("iassign_submission", array("id" => $this->iassign_submission_current)); // data about activity current
      else
        $iassign_submission = $DB->get_record("iassign_submission", array("iassign_statementid" => $this->activity->get_activity()->id, "userid" => $this->userid_iassign)); // data about student solution
      }
    else {
        $iassign_submission = $DB->get_record("iassign_submission", array("iassign_statementid" => $this->activity->get_activity()->id, "userid" => $this->userid_iassign)); // data about student solution
        }

    if ($iassign_submission)
      $this->update_comment($iassign_submission->id);

    //2021/12 $file = $iassign_statement_activity_item->filesid;
    // $file = $iassign_statement_activity_item->id; - nao usado aqui!

    // 1 when open previous file; 2 when the activity is redone!; 3 when the teacher enter in the activity
    // 1 => locallib.php: view_iassign_current(): action=view will set write_solution=0!!!!!
    // 2 => locallib.php: view_iassign_current(): action=repeat will set write_solution=0!!!!!
    // 3 => locallib.php: view_iassign_current(): action=viewsubmission will set write_solution=0!!!!!

    $this->allowPostButton = 0; // hide submit button
    //xxx $this->write_solution = 0; // disable recording solution (however, iVProg allow the learner to edit previou solution)
    $this->view_iassign = false; // disable visualization of activity
    $repeat = "";
    $last_iassign = "";
    $student_answer = "";
    $comment = "";

    // *** Teacher access (view learner's submission) or view the exercise
    if (($this->action != 'viewsubmission') && has_capability('mod/iassign:evaluateiassign', $USER->context, $USER->id)) {
      //TODO leo Verificar se o correto eh '$this->context' ou '$USER->context' como deixei

      // It is not 'viewsubmission' and it is teacher or 'non editing teacher'?
      // ---> access teacher for test

      if ($iassign_statement_activity_item->type_iassign != 1) // type_iassign=1 => activity of type "example" - usually does not allow submit button
        $this->allowPostButton = 1;

      print $OUTPUT->box('<p><strong>' . get_string('area_specific_teacher', 'iassign') . '</strong></p>');

      $this->activity->view_dates();
      $USER->iassignEdit = $this->allowPostButton; // To be used by "./mod/iassign/ilm_handers/*.php"
      $this->activity->show_info_iassign();

      // Prepare variable "$enderecoPOST" that is used in the JS code in /mod/iassign/ilm_handlers/html5.php
      if ($iassign_submission) {
        //xxx Removed tag "write_solution": . "&write_solution=" . $this->write_solution .
        $param_aux = "action=get_answer&iassign_submission_current=" . $iassign_submission->id . "&id=" . $id . "&iassign_current=" .
          $this->activity->get_activity()->id . "&userid_iassign=" . $USER->id;
      } else {
        //xxx Removed tag "write_solution": . "&write_solution=" . $this->write_solution .
        $param_aux = "action=get_answer&id=" . $id . "&iassign_current=" . $this->activity->get_activity()->id . "&userid_iassign=" . $USER->id;
        }
      $enderecoPOST = "" . $CFG->wwwroot . "/mod/iassign/view.php?" . $param_aux;

      // Prepare tags to present the iLM
      print $OUTPUT->box($ilm->view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, true));

      } // if (($this->action != 'viewsubmission') && has_capability('mod/iassign:evaluateiassign', $USER->context, $USER->id))
    // *** (end) Teacher access (view the activity)

    // *** Teacher access (view learner's submission to the activity)
    elseif (($this->action == 'viewsubmission') && has_capability('mod/iassign:evaluateiassign', $USER->context, $USER->id)) {

      // It is teacher or 'nonediting teacher' that can evaluate
      // ----> area teacher evaluate

      $row = optional_param('row', 0, PARAM_INT);
      $column = optional_param('column', 0, PARAM_INT);

      $link_next = iassign_icons::insert('right_disable');
      $link_previous = iassign_icons::insert('left_disable');
      $link_up = iassign_icons::insert('up_disable');
      $link_down = iassign_icons::insert('down_disable');

      if (isset($USER->matrix_iassign[$row][$column])) {
        $has_row_column = True;
        $element_matrix_iassign = $USER->matrix_iassign[$row][$column];
        }
      else {
        $has_row_column = False;
        $element_matrix_iassign = $USER->matrix_iassign[$row][$column];
        }

      $msgViewAction = "view.php?action=viewsubmission&id=$id&iassign_submission_current=";
      $msgUserId_current = "&userid_iassign=" . $this->userid_iassign . "&iassign_current=";
      // next_activity
      if ($has_row_column && $element_matrix_iassign->iassign_next != - 1) {
        $url_next = $msgViewAction . $USER->matrix_iassign[$row][$column + 1]->iassign_submission_current . $msgUserId_current . $element_matrix_iassign->iassign_next . "&view_iassign=report&row=" . ($row) . "&column=" . ($column + 1);
        $link_next = "<a href='" . $url_next . "'>" . (iassign_icons::insert('next_activity')) . "</a>";
        }
      // previous_activity
      if ($has_row_column && $element_matrix_iassign->iassign_previous != - 1) {
        $url_previous = $msgViewAction . $USER->matrix_iassign[$row][$column - 1]->iassign_submission_current . $msgUserId_current . $element_matrix_iassign->iassign_previous . "&view_iassign=report&row=" . ($row) . "&column=" . ($column - 1);
        $link_previous = "<a href='" . $url_previous . "'>" . (iassign_icons::insert('previous_activity')) . "</a>";
        }
      // previous_student
      if ($has_row_column && $element_matrix_iassign->user_next != - 1) {
        $url_down = $msgViewAction . $USER->matrix_iassign[$row + 1][$column]->iassign_submission_current . "&userid_iassign=" . $element_matrix_iassign->user_next . "&iassign_current=" . $this->activity->get_activity()->id . "&view_iassign=report&row=" . ($row + 1) . "&column=" . ($column);
        $link_down = "<a href='" . $url_down . "'>" . (iassign_icons::insert('previous_student')) . "</a>";
        }
      // next_student
      if ($has_row_column && $element_matrix_iassign->user_previous != - 1) {
        $url_up = $msgViewAction . $USER->matrix_iassign[$row - 1][$column]->iassign_submission_current . "&userid_iassign=" . $element_matrix_iassign->user_previous . "&iassign_current=" . $this->activity->get_activity()->id . "&view_iassign=report&row=" . ($row - 1) . "&column=" . ($column);
        $link_up = "<a href='" . $url_up . "'>" . (iassign_icons::insert('next_student')) . "</a>";
        }

      if ($iassign_submission) {
        $student_answer = $iassign_submission->answer;
        }

      $last_iassign = get_string('last_iassign', 'iassign');

      $user_data = $DB->get_record("user", array('id' => $this->userid_iassign));

      // Messages related to due date (and user role)
      $this->activity->view_dates();
      print $OUTPUT->box_start();
      print '<table width=100% border=0 valign="top"><tr>' . "\n";
      print '<td width=80%><font color="blue"><strong>' . get_string('area_available', 'iassign') . '</strong></font><br>' . "\n";
      if ($user_data) // additional security (if occurs programming errors in $url_next, $url_previous...)
        print $OUTPUT->user_picture($user_data);
      print ' ' . $user_data->firstname . ' ' . $user_data->lastname;
      print '</td>' . "\n";
      print '<td width=20% align=right>' . "\n";
      print '<table width=50 cellpadding="0">';
      print '<tr><td colspan=2 align=center>' . $link_up . '</td></tr>' . "\n";
      print '<tr><td align=center>' . $link_previous . '</td>' . "\n";
      print '<td align=center>' . $link_next . '</td></tr>' . "\n";
      print '<td colspan=2 align=center>' . $link_down . '</td></tr>' . "\n";
      print '</table>' . "\n";
      print '</td></tr></table>' . "\n";
      print $OUTPUT->box_end();
      print $OUTPUT->box_start();

      print '<table width=100% border=0 valign="top"><tr>' . "\n";
      print '<td width=60% valign="top">' . "\n";
      print '<p><strong>' . get_string('proposition', 'iassign') . ':</strong></p>' . "\n";
      print '<div class="proposition">' . $iassign_statement_activity_item->proposition . '</div>' . "\n";

      if ($iassign_statement_activity_item->automatic_evaluate == 1)
        $resp = get_string('yes');
      else
        $resp = get_string('no');
      print '<p>' . get_string('automatic_evaluate', 'iassign') . '&nbsp;' . $resp . '</p>' . "\n";
      if ($iassign_statement_activity_item->show_answer == 1)
        $resp = get_string('yes');
      else
        $resp = get_string('no');
      print '<p>' . get_string('show_answer', 'iassign') . '&nbsp;' . $resp . '</p>' . "\n";
      print '</td>';

      if ($iassign_statement_activity_item->type_iassign == 3) { // type_iassign=3 => activity of type "exercise" - submit button and automatic evaluation
        print '<td width=40% valign="top" align="left">';
        print '<strong>' . get_string('status', 'iassign') . '</strong>' . "\n";

        // check status of solution sent by the student
        if ($iassign_submission) {
          switch ($iassign_submission->status) {
            case 3 :
              print iassign_icons::insert('correct') . '&nbsp;' . get_string('correct', 'iassign') . '&nbsp;' . $comment;
              break;
            case 2 :
              print iassign_icons::insert('incorrect') . '&nbsp;' . get_string('incorrect', 'iassign') . '&nbsp;' . $comment;
              break;
            case 1 :
              print iassign_icons::insert('post') . '&nbsp;' . get_string('post', 'iassign') . '&nbsp;' . $comment;
              break;
            default :
              print iassign_icons::insert('not_post') . '&nbsp;' . get_string('not_post', 'iassign') . '&nbsp;' . $comment;
              $last_iassign = get_string('no_iLM_PARAM_ArchiveContent', 'iassign');
            } // switch ($iassign_submission->status)
          }
        else { // if ($iassign_submission)
          print iassign_icons::insert('not_post') . '&nbsp;' . get_string('not_post', 'iassign') . '&nbsp;' . $comment;
          $last_iassign = get_string('no_iLM_PARAM_ArchiveContent', 'iassign');
          }

        // update_status
        if ($iassign_submission && $iassign_submission->experiment > 0) {
          $edit_status = $CFG->wwwroot . "/mod/iassign/view.php?action=edit_status&id=" . $id . "&userid_iassign=" . $this->userid_iassign . "&iassign_current=" . $this->activity->get_activity()->id . "&iassign_submission_current=" . $this->iassign_submission_current . "&row=" . ($row) . "&column=" . ($column);

          print " <script type='text/javascript'>
  //<![CDATA[
  function overwriteStatus (newstatus) {
    if (confirm('" . get_string('confirm_change_situation', 'iassign') . "')) {
      document.formEditStatus.return_status.value=newstatus;
      document.formEditStatus.submit();
      }
    else
      document.formEditStatus.return_status.value=-1;
    }
  //]]>
  </script>";

          print "<form name='formEditStatus' method='post' action='$edit_status' enctype='multipart/form-data'>\n";
          print ' <font color="blue"><strong>' . get_string('changeto', 'iassign') . "</strong></font>\n";
          print " <select name='status' onchange= 'overwriteStatus(this.value)'>\n" . " <option value=\"3\">" . get_string('correct', 'iassign') . "</option>\n" . " <option value=\"2\">" . get_string('incorrect', 'iassign') . "</option>\n" . " <option value=\"1\">" . get_string('post', 'iassign') . "</option>\n" . " <option value=\"0\">" . get_string('not_post', 'iassign') . "</option>\n" . " <option value=\"-1\" selected>" . get_string('newsituation', 'iassign') . "</option>\n" . " </select>\n";
          print " <input type='hidden' name='return_status'>\n";
          print "</form>\n";

          print '<p><strong>' . get_string('grade_student', 'iassign') . '</strong> ' . $iassign_submission->grade . "</p>\n";
          print '<p><strong>' . get_string('grade_iassign', 'iassign') . '</strong> ' . $iassign_statement_activity_item->grade . "</p>\n";
          $edit_grade = $CFG->wwwroot . "/mod/iassign/view.php?action=edit_grade&id=" . $id . "&userid_iassign=" . $this->userid_iassign . "&iassign_current=" . $this->activity->get_activity()->id . "&iassign_submission_current=" . $this->iassign_submission_current . "&row=" . ($row) . "&column=" . ($column);
          print "
  <script type='text/javascript'>
  //<![CDATA[
  function overwriteGrade (newgrade,maxgrade) {
   if (newgrade<0 || newgrade>maxgrade) {
     alert('" . get_string('erro_grade', 'iassign') . " '+maxgrade)
     document.formEditGrade.return_grade.value=-1;
     document.formEditGrade.submit();
     }
   else {
     document.formEditGrade.return_grade.value=newgrade;
     document.formEditGrade.submit();
     }
    }
  //]]>
  </script>";
          print "<form name='formEditGrade' method='post' action='$edit_grade' enctype='multipart/form-data'>\n";
          print ' <font color="blue"><strong>' . get_string('changeto', 'iassign') . "</strong></font>" . "\n";
          print " <input type='text' name='grade' size='6'>";
          print " <input type='hidden' name='return_grade'> ";
          print " <input type=button value='" . get_string('confirm', 'iassign') . "' onClick = 'overwriteGrade(grade.value," . $iassign_statement_activity_item->grade . ")' " . "  title='" . get_string('confirm_new_grade', 'iassign') . "'>\n";
          print "</form>";

          $url_answer = "" . $CFG->wwwroot . "/mod/iassign/view.php?" . "action=download_answer&iassign_submission_id=" . $iassign_submission->id . "&id=" . $id;
          print '<p><strong>' . get_string('experiment', 'iassign') . '</strong> ' . $iassign_submission->experiment . ' <a href="' . $url_answer . '">' . iassign_icons::insert('download_assign') . '</a></p>';

          print '<p><strong>' . get_string('timemodified', 'iassign') . '</strong> ' . userdate($iassign_submission->timemodified) . '</p>';
          $teacher = $DB->get_record("user", array('id' => $iassign_submission->teacher));
          if ($teacher)
            print '<p><strong>' . get_string('last_modification', 'iassign') . '</strong> ' . $teacher->firstname . '</p>' . "\n";
          } // if ($iassign_submission->experiment > 0)
        print '</td>';
        } // if ($iassign_statement_activity_item->type_iassign == 3)

      print '</tr></table>';
      print $OUTPUT->box_end();

      //TODO It can be removed, since now we have the field {iassign_statement}.editingbehavior?
      $USER->iassignEdit = $this->allowPostButton; // To be used by "./mod/iassign/ilm_handers/*.php"

      print activity::toggle_columns_script();

      if ($iassign_submission && $allow_resubmission) {
        // Put the iLM to
        print $OUTPUT->box_start();
        print '<p><strong>' . $last_iassign . '</strong></p>';
        $enderecoPOST = "";
        // Prepare tags to present the iLM
        print $ilm->view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, false);

        print $OUTPUT->box_end();
        } // if ($iassign_submission && $allow_resubmission)
      else { // if ($iassign_submission && $allow_resubmission) - techer view student answer
        // If '$student_answer' is empty, then the student does not submit any solution to the exercise
        // ATTENTION: exception used by iGeom (exercise with "script")
        $loadTeacherActivity = false; // use 'true' whenever 'special_param1 == 1'
        if (substr($ilm_name, 0, 5)=="igeom") {
          if ($iassign_statement_activity_item->special_param1 == 1) // if 1 => use the teacher activity with some complement from the student (in iGeom = GEO + SCR)
            $loadTeacherActivity = true;
          }
        else
        if (!$student_answer) { //2022/01 this student does not submit any soluction => load the teacher content
          $loadTeacherActivity = true;
          }

        print $OUTPUT->box('<p><strong>' . $last_iassign . '</strong></p>' . "\n");

        if (!isset($enderecoPOST)) $enderecoPOST = "";
        print activity::toggle_columns_script();
        // Prepare tags to present the iLM
        print $OUTPUT->box($ilm->view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, $loadTeacherActivity));
        }

      //2020 Eliminei daqui o codigo para colocar o quadro com area para enviar e ler comentarios
      //2020 Estava subordinado ao: elseif (($this->action == 'viewsubmission') && has_capability('mod/iassign:evaluateiassign', $USER->context, $USER->id))
      //2020 e com isso NAO permitia aluno ter o quadro para envio!
      //2020 Foi para final dessa funcao 'view_iassign_current()'
    } // elseif (($this->action == 'viewsubmission') && has_capability('mod/iassign:evaluateiassign', $USER->context, $USER->id))
    // *** (end) Teacher access (view learner's submission to the activity)

    // *** Student access (view the activity)
    elseif (has_capability('mod/iassign:submitiassign', $USER->context, $USER->id)) {

      // It could be the learner (he could send or resend the activity)
      // ---> access student

      $time_now = time();
      if ($iassign_statement_activity_item->type_iassign == 1) { // type_iassign=1 => activity of type "exemple" - no submit button
        // activity of type example - not submit button for submission
        $this->view_iassign = true;
        //TODO rever esta condicao para iMA que nao fazem autoavaliacao
        } 
      elseif ($iassign_statement_activity_item->type_iassign == 2 && $iassign_ilm->evaluate == 1) {
        // activity of type test - iLM automatic evaluator - submit button for submission
        if ($iassign_statement_activity_item->timeavailable < $time_now && $iassign_statement_activity_item->timedue > $time_now) { // activity within of deadline
          $this->allowPostButton = 1;
          $this->view_iassign = true;
          }
        else {
          $this->allowPostButton = 0;
          $this->view_iassign = false;
          }
        }
      elseif ($iassign_statement_activity_item->type_iassign == 3) { // type_iassign=3 => activity of type "exercise" - submit button and automatic evaluation
        // Activity of type exercise (learner can send his answer, if yet open...)
        // $iassign_statement_activity_item has {iassign_statement} = id name iassignid type_iassign ... timedue preventlate test
        $this->view_iassign = true;
        if ($iassign_statement_activity_item->timeavailable > $time_now) { // activity not yet available
          $this->view_iassign = false;
          }
        elseif ($iassign_statement_activity_item->timedue > $time_now || $iassign_statement_activity_item->preventlate == 1) {
          // Activity open: due date not expired
          $this->allowPostButton = 1; // allow the submit button
          // Look at table 'iassign_submission' ('iassign_submission.experiment' is the number of submissions)

          // action=repeat will set write_solution=0 => the iLM allow to edit the "old" solution
          $repeat_title = ' title="' . get_string('repeat_alt', 'iassign') . '" '; // Use this button to 'redo the activity'
          if (!$iassign_submission || $this->action == 'repeat' || ($iassign_submission && $allow_resubmission)) {
            // No submission OR action repeat (from scratch) OR (has submission and is allowed to edit it)
            if (!$iassign_submission) {
              $str_iassign_submission_id = '';
              $str_iassign_submission_answer = '';
              }
            else {
              $str_iassign_submission_id = $iassign_submission->id;
              $str_iassign_submission_answer = $iassign_submission->answer;
              }
            if ($this->action != 'repeat') {
              $repeat = "<a href='view.php?action=repeat&id=" . $id . "&userid_iassign=" . $USER->id . "&iassign_current="
                 . $this->activity->get_activity()->id . "&iassign_submission_current=" . $str_iassign_submission_id
                 . "'" . $repeat_title . ">" . iassign_icons::insert('repeat') . ' ' . get_string('repeat', 'iassign') . "</a>\n";
              }
            $this->allowPostButton = 1;
            $this->write_solution = 1; // can register his submission
            $student_answer = $str_iassign_submission_answer;
            }
          else { // if (!$iassign_submission || $this->action == 'repeat' || ($iassign_submission && $allow_resubmission))
            // In 'class ilm : view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, $view)'
            $last_iassign = get_string('last_iassign', 'iassign');
            if ($iassign_submission) {
              $repeat = "<a href='view.php?action=repeat&id=" . $id . "&userid_iassign=$USER->id&iassign_current=" . $this->activity->get_activity()->id .
                "&iassign_submission_current=" . $iassign_submission->id . "'" . $repeat_title . ">" . iassign_icons::insert('repeat') . ' ' . get_string('repeat', 'iassign') . "</a>\n";
              $student_answer = $iassign_submission->answer;
            } else {
              $repeat = "<a href='view.php?action=repeat&id=" . $id . "&userid_iassign=$USER->id&iassign_current=" . $this->activity->get_activity()->id .
                "'" . $repeat_title . ">" . iassign_icons::insert('repeat') . ' ' . get_string('repeat', 'iassign') . "</a>\n";
              }
            }
          } //  elseif ($iassign_statement_activity_item->timedue > $time_now || $iassign_statement_activity_item->preventlate == 1)

        elseif ($iassign_statement_activity_item->test == 1) { // allowed to test after expired due date
          if ($this->action == 'repeat' || ($iassign_submission && $iassign_submission->experiment < 1)) {
            $this->allowPostButton = 1;
            $this->write_solution = 0; // if iVProg it is valid to the learner to edit previous solution
          } else {
            $last_iassign = get_string('last_iassign', 'iassign');
            if ($iassign_submission) {
              $repeat = "<a href='view.php?action=repeat&id=" . $id . "&userid_iassign=$USER->id&iassign_current=" . $this->activity->get_activity()->id .
                "&iassign_submission_current=" . $iassign_submission->id . "'" . $repeat_title . ">" . iassign_icons::insert('repeat') . ' ' . get_string('repeat', 'iassign') . "</a>\n";
              $student_answer = $iassign_submission->answer;
            } else {
              // Symbol of "redo activity"
              $repeat = "<a href='view.php?action=repeat&id=" . $id . "&userid_iassign=$USER->id&iassign_current=" . $this->activity->get_activity()->id .
                "'" . $repeat_title . ">" . iassign_icons::insert('repeat') . ' ' . get_string('repeat', 'iassign') . "</a>\n";
              }
            }
          } // elseif ($iassign_statement_activity_item->test == 1)

        elseif ($iassign_statement_activity_item->test == 0) {
          $this->view_iassign = false;
          }

        } // elseif ($iassign_statement_activity_item->type_iassign == 3)

      if ($iassign_statement_activity_item->timeavailable <= $time_now &&
          ($iassign_statement_activity_item->timedue > $time_now || $iassign_statement_activity_item->preventlate==1)
         ) { // 2023/05/30
        // Enter if, and only if, exercise is AVAILABLE and ( ON TIME OR allow submission after due date )
        // $iassign_statement_activity_item->preventlate == 0
        // {iassign_statement} = id name iassignid type_iassign ... timedue preventlate test: preventlate==0 <=> do NOT allow submission after due date
        // Prepare variable "$enderecoPOST" that is used in the JS code in /mod/iassign/ilm_handlers/html5.php
        if ($iassign_submission)
          $param_aux = "action=get_answer&iassign_submission_current=" . $iassign_submission->id . "&id=" . $id . "&iassign_current=" . $this->activity->get_activity()->id .
            "&write_solution=" . $this->write_solution . "&userid_iassign=" . $USER->id;
        else
          $param_aux = "action=get_answer&id=" . $id . "&iassign_current=" . $this->activity->get_activity()->id . "&write_solution=" . $this->write_solution . "&userid_iassign=" . $USER->id;
        // iLM: to be used to the iLM to where it must send the student answer
        $enderecoPOST = "" . $CFG->wwwroot . "/mod/iassign/view.php?" . $param_aux;
        }
      else { // Otherwise, set address to process answer as empty
        // ilm_handlers/html5.php!show_activity_in_ilm(...  $enderecoPOST ...): will check this, if empty do NOT present "submission button" nor allow sending
        $enderecoPOST = ""; // do NOT allow send if after due date
        }

      $this->activity->view_dates(); // prints "Type of activity:", "Available from:"

      if ($this->view_iassign) { // Box 5 to 'proposition' - open
        print $OUTPUT->box_start();

        print '<table width=100% border=0 valign="top">' . "\n";
        print '<tr><td width=60% valign="top">' . "\n";
       
        $flag_dependency = true;

        if ($iassign_statement_activity_item->type_iassign == 3) {
          if ($iassign_statement_activity_item->dependency == 0) {
            print '<strong>' . get_string('independent_activity', 'iassign') . '</strong>' . "\n";
          } else {
            $dependencys = explode(';', $iassign_statement_activity_item->dependency);
            print '<p><strong>' . get_string('dependency', 'iassign') . '</strong></p>' . "\n";
            foreach ($dependencys as $dependency) {
              if ($dependency) {
                $dependencyiassign = $DB->get_record("iassign_statement", array("id" => $dependency));
                $dependencysubmissions = $DB->get_record("iassign_submission", array("iassign_statementid" => $dependencyiassign->id, 'userid' => $USER->id));
                if ($dependencysubmissions) {
                  if ($dependencysubmissions->status == 3)
                    $icon = iassign_icons::insert('correct');
                  elseif ($dependencysubmissions->status == 2) {
                    $icon = iassign_icons::insert('incorrect');
                    $flag_dependency = false;
                  } elseif ($dependencysubmissions->status == 1) {
                    $icon = iassign_icons::insert('post');
                    $flag_dependency = false;
                  } elseif ($dependencysubmissions->status == 0) {
                    $icon = iassign_icons::insert('not_post');
                    $flag_dependency = false;
                    }
                } else {
                  $icon = iassign_icons::insert('not_post');
                  $flag_dependency = false;
                  } // if ($dependencysubmissions)

                print '<p> ' . $icon . $dependencyiassign->name . '</p>' . "\n";
                } // if ($dependency)
              } // foreach ($dependencys as $dependency)
            } // if ($iassign_statement_activity_item->dependency == 0)
          } // if ($iassign_statement_activity_item->type_iassign == 3)

        if ($flag_dependency == false) {
          print '<strong>' . get_string('message_dependency', 'iassign') . '</strong>.' . "\n";
          $this->view_iassign = false;
          print '</tr></table>' . "\n";
          }
        else {
          $this->view_iassign = true;
          print '</td>' . "\n";
          } // if ($flag_dependency == false)

        if ($this->view_iassign) { // it is already inside 'if ($this->view_iassign)' but the dependency check could had change its value (above)
          if ($iassign_statement_activity_item->type_iassign == 3) { // activity is present only if exercise
            // receiver=1 - message to teacher
            // receiver=2 - message to student
            if ($iassign_submission) {
              $verify_message = $DB->get_record('iassign_submission_comment', array('iassign_submissionid' => $iassign_submission->id, 'return_status' => 0, 'receiver' => 2));
              if ($verify_message)
                $comment = iassign_icons::insert('comment_unread');
              }
            print '<td width=40% valign="top" align="left">';
            print '<strong>' . get_string('status', 'iassign') . '</strong>' . "\n";

            if ($iassign_statement_activity_item->show_answer == 1) {
              // Check status of solution sent by the student:
              if ($iassign_submission) {
                switch ($iassign_submission->status) { // {iassign_submission}.status = 3 => 'correct'; 2 => 'incorrect'; 1 => 'post'; 0 => 'not_post'
                  case 3 :
                    print iassign_icons::insert('correct') . ' ' . get_string('correct', 'iassign') . ' ' . $comment;
                    break;
                  case 2 :
                    print iassign_icons::insert('incorrect') . ' ' . get_string('incorrect', 'iassign') . ' ' . $comment;
                    break;
                  case 1 :
                    print iassign_icons::insert('post') . ' ' . get_string('post', 'iassign') . ' ' . $comment;
                    break;
                  default :
                    print iassign_icons::insert('not_post') . ' ' . get_string('not_post', 'iassign') . ' ' . $comment;
                    $repeat = "";
                    $last_iassign = "";
                  } // switch ($iassign_submission->status)
              } else {
                  print iassign_icons::insert('not_post') . ' ' . get_string('not_post', 'iassign') . ' ' . $comment;
                  $repeat = "";
                  $last_iassign = "";
                }

              if ($iassign_submission && $iassign_submission->experiment > 0) {
                // {iassign_statement}.grade  = float representing the maximum score in the activity
                // {iassign_submission}.grade = float representing the student real grade (must be <= {iassign_statement}.grade)
                // {iassign_submission}.grade = grade_from_iLM*{iassign_statement}.grade (grade_from_iLM must be a float value in [0,1])
                // Messages: grade_max='Maximum score' ; grade='Grade activity'
                if ($iassign_submission->grade)
                  $student_grade = $iassign_submission->grade;
                else
                  $student_grade = 0; // security
                if ($iassign_statement_activity_item->grade) // prevent max grade indefined => zero
                  $ia_max_grade = $iassign_statement_activity_item->grade; // get max grade in {iassign_statement}
                else // If 'else' is executed, the {iassign_statement}.grade must be fixed by the teacher!
                  $ia_max_grade = 10; // security: if {iassign_statement}.grade empty, set the default 10...

                //D echo "locallib.php!view_iassign_current(): ia_max_grade=" . $ia_max_grade . ", student_grade=" . $student_grade . "<br/>";
                // To prevent changes provoking errors: probably iLM send wrong value (by evaluation button?)...
                if ($student_grade > $ia_max_grade) $msg_grade = $ia_max_grade;
                else $msg_grade = $student_grade;

                print '<p><strong>' . get_string('grade', 'iassign') . '</strong> ' . $msg_grade; // "Grade activity:"
                print ' (' . get_string('grade_max', 'iassign') . ': ' . $iassign_statement_activity_item->grade . ')</p>' . "\n";

                print '<p><strong>' . get_string('experiment_student', 'iassign') . '</strong> ' . $iassign_submission->experiment;

                if ($iassign_statement_activity_item->max_experiment == 0)
                  print ' (' . get_string('experiment_iassign', 'iassign') . ' ' . get_string('ilimit', 'iassign') . ')</p>' . "\n";
                else {
                  print ' (' . get_string('experiment_iassign', 'iassign') . ' ' . $iassign_statement_activity_item->max_experiment . ')</p>' . "\n";
                  if ($iassign_submission->experiment >= $iassign_statement_activity_item->max_experiment) {
                    $repeat = "";
                    $last_iassign .= " <font color=red>" . get_string('attempts_exhausted', 'iassign') . '</font>' . "\n";
                    $this->allowPostButton = 0;
                    $this->write_solution = 0;
                    }
                  } // else if ($iassign_statement_activity_item->max_experiment == 0)

                print '<p><strong>' . get_string('timemodified', 'iassign') . '</strong> ' . userdate($iassign_submission->timemodified) . '</p>' . "\n";
                $teacher = $DB->get_record("user", array('id' => $iassign_submission->teacher));
                if ($teacher)
                  print '<p><strong>' . get_string('last_modification', 'iassign') . '</strong> ' . $teacher->firstname . '</p>' . "\n";
                } // if ($iassign_submission && $iassign_submission->experiment > 0)

              } // if ($iassign_statement_activity_item->show_answer==1)
            else {
              if (!isset($iassign_submission) || $iassign_submission->status == 0) {
                print iassign_icons::insert('not_post') . ' ' . get_string('not_post', 'iassign') . ' ' . $comment;
                $repeat = "";
                $last_iassign = "";
              } elseif ($iassign_submission->status == 1) {
                print iassign_icons::insert('post') . ' ' . get_string('post', 'iassign') . ' ' . $comment;
                }
              }

            print '</td>';
            } // if ($iassign_statement_activity_item->type_iassign == 3)

          print '</tr></table>' . "\n";

          // Presents the iLM
          print '<table width=100% border=0 valign="top">' . "\n";
          print '<td width=80% align="left">';
          print '<strong>' . $last_iassign . ' ' . get_string('repeat_msg', 'iassign') . '</strong></td>' . "\n"; // If you want to do this activity from the beginning again, use the \"Redo button\".
          print '<td width=20% align="rigth">';
          //D $ilm_name = strtolower($this->ilm->name); //if (substr($ilm_name, 0, 5) == "igeom") ; // is iGeom exercise
          print $repeat; // symbol of "redo activity"
          print '</td></tr></table>' . "\n";
          //2019 print $OUTPUT->box_end(); // closing Box 5 but sometimes it does reach this point - see it at the end of 'if ($this->view_iassign)'

          print activity::toggle_columns_script();

          print '<p class="mt-5"><strong>' . get_string('proposition', 'iassign') . '</strong></p>' . "\n";
          print '<div class="proposition">' . $iassign_statement_activity_item->proposition . '</div>' . "\n";

          $output = $OUTPUT->box_start();
          $USER->iassignEdit = $this->allowPostButton; // To be used by "./mod/iassign/ilm_handers/*.php"

          //leo Re-submission
          //D echo " * locallib.php: view_iassign_current(): !iassign_submission : action=" . $this->action . ", answer=" . $iassign_submission->answer . "<br/>\n"; //leo
          // view_iLM: $retorno = $typec::show_activity_in_ilm($iassign_statement_activity_item, $student_answer, $enderecoPOST, $view_teacherfileversion);

          // ---
          // Presents the iLM
          // Prepare tags to present the iLM: it use 'ilm_handlers/<{iassign_statement}.type>.php' typec::show_activity_in_ilm(.)
          if (!$iassign_submission || $this->action == 'repeat') { // or $iassign_submission->answer==0
            $student_answer = ""; // when student never answer this exercise reaches this point

            $output .= $ilm->view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, true); // presents iLM and true => see the teacher file

          } elseif ($iassign_submission && $iassign_submission->answer == '0') {
            $student_answer = "";

            $output .= $ilm->view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, true); // presents iLM

          } else {
            // When student is redoing his activity

            // ATTENTION: exception used by iGeom (exercise with "script")
            $loadTeacherActivity = false; // trocar para 'true' se 'special_param1 == 1'
            if (substr($ilm_name, 0, 5)=="igeom") {
              if ($iassign_statement_activity_item->special_param1 == 1) // if 1 => use the teacher activity with some complement from the student (in iGeom = GEO + SCR)
                $loadTeacherActivity = true;
              }

            // Will calls "./ilm_handlers/html5.php!show_activity_in_ilm(.)"
            $output .= $ilm->view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, $loadTeacherActivity); // presents iLM

            }

          //1 Desse modo entre area para comentario, mas nao seu botao separado - deixar para o final com botao!
          //1 if ($iassign_statement_activity_item->type_iassign == 3) {
          //1   $history_comment = '';
          //1   if ($iassign_submission) { $history_comment = $this->search_comment_submission($iassign_submission->id); }
          //1   if (!empty($history_comment))
          //1     $output .= "\n  <table id='outlinetable' class='generaltable boxaligncenter' cellpadding='5' width='100%'>\n" .
          //1       "   <tr><th>" . get_string('history_comments', 'iassign') . "</th></tr>\n" . $history_comment . "</table>\n";
          //1   $output .= "</form></center>\n";
          //1   $only_one_send_button = 1; // avoid put comment frame again (bellow)
          //1   }

          $output .= $OUTPUT->box_end();
          print $output;
          } // if ($this->view_iassign)

        print $OUTPUT->box_end(); // Box 5 to 'proposition' - close
        } // if ($this->view_iassign)

      } // elseif (has_capability('mod/iassign:submitiassign', $USER->context, $USER->id))
    else
    if (isguestuser()) { // else of elseif (has_capability('mod/iassign:submitiassign', $USER->context, $USER->id))
      print($OUTPUT->notification(get_string('no_permission_iassign', 'iassign'), 'notifyproblem'));
      print '<table width=100% border=0 valign="top">' . "\n";
      print '<tr><td width=60% valign="top">' . "\n";
      print '<strong>' . get_string('proposition', 'iassign') . '</strong>' . "\n";
      print '<p>' . $iassign_statement_activity_item->proposition . '</p>' . "\n";
      print '</tr></table>' . "\n";
      $student_answer = "";
      $enderecoPOST = "";

      // Prepare tags to present the iLM
      $output = $ilm->view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, true);

      print $output;
      }

    //2020 Frame with area to send and read comments (in this point to allow everyone read)
    // $only_one_send_button is to avoid to put 2 copies of comments (area to send and see comments between teacher and student)
    if ($only_one_send_button==0 && $iassign_statement_activity_item->type_iassign == 3) { // type_iassign=3 => activity of type "exercise" - submit button and automatic evaluation
      // Put a block with area to sent/answer comments
      $output = '';
      $history_comment = '';
      $row = optional_param('row', 0, PARAM_INT);
      $column = optional_param('column', 0, PARAM_INT);
      if ($iassign_submission) {
        $enderecoPOSTcomment = "" . $CFG->wwwroot . "/mod/iassign/view.php?id=" . $id . "&action=newcomment&iassign_current=" . $this->activity->get_activity()->id .
          "&iassign_submission_current=" . $iassign_submission->id . "&userid_iassign=" . $this->userid_iassign . "&row=" . ($row) . "&column=" . ($column);
        $history_comment = $this->search_comment_submission($iassign_submission->id);
        }
      else {
        $enderecoPOSTcomment = "" . $CFG->wwwroot . "/mod/iassign/view.php?id=" . $id . "&action=newcomment&iassign_current=" . $this->activity->get_activity()->id .
          "&userid_iassign=" . $this->userid_iassign . "&row=" . ($row) . "&column=" . ($column);
        }

      $output .= $OUTPUT->box_start();

      //if (!empty($history_comment)) {
        $output .= "<a id='comments'></a>";
        $output .= "  <table id='outlinetable' class='generaltable boxaligncenter' cellpadding='5' width='100%'> \n";
        $output .= "     <tr><th><i style='font-size: 20px' class='icon fa fa-comments-o'></i>" . get_string('history_comments', 'iassign') . "</th></tr>";
        $output .= "</table>";

        print " <script type='text/javascript'>
        //<![CDATA[
        function submit_comment(url) {
          if (document.formEnvioComment.submission_comment.value.length < 1) return;

          var formData = new FormData();
          formData.append('submission_comment', document.formEnvioComment.submission_comment.value);
          var request = new XMLHttpRequest();
          request.open('POST', url);
          document.getElementById('comments_history').innerHTML = '';
          request.onload = function (e) {
            if (request.readyState === 4) {
              if (request.status === 200) {
                document.getElementById('comments_history').innerHTML = request.responseText;
                document.getElementById('check-message-success').style.visibility = 'visible';
                setTimeout(function(){
                  document.getElementById('check-message-success').style.visibility = 'hidden';
              }, 3000);
              }
            }
          };
          document.formEnvioComment.submission_comment.value = '';
          request.send(formData);
        }

        function event_text_area (event) {
          if (event.ctrlKey && event.keyCode == 13)
            submit_comment('" . $enderecoPOSTcomment . "');
          }
        //]]>
        </script>";

        $output .= "<center><form name='formEnvioComment' id='formEnvioComment1' enctype='multipart/form-data'>\n";
        $output .= "<br><p><textarea class='form-control w-50' rows='2' cols='60' name='submission_comment' onkeypress='event_text_area(event)'></textarea></p>\n";
        $output .= "<p><button class='btn btn-primary' onclick=\"submit_comment('" . $enderecoPOSTcomment . "')\" type=button value='" . get_string('submit_comment', 'iassign') . "'>".get_string('submit_comment', 'iassign')."</button> <i id='check-message-success' class='fa fa-check' aria-hidden='true' style='position: absolute; color: green; font-size: 2.0rem; margin-left: 1rem; visibility: hidden;'></i> </p> \n";

        $output .= "</form> </center>\n";

        $output .= "<table id='comments_history' class='generaltable boxaligncenter' cellpadding='5' width='100%'>".$history_comment."</table>";

      //  }
      $output .= $OUTPUT->box_end();
      print $output;
      } // if ($iassign_statement_activity_item->type_iassign == 3)


    // final block 'studant'
    print $OUTPUT->footer();
    die();
    } // function view_iassign_current()


  /// Export a survey of all the student submissions in the current course into CSV file format
  //  Each student will have one line with:
  //   student_id, student_name, [followed by 7 columns to each student submissions]
  //  The 7 columns are: activity_id_i, activity_name_i, total_submissions_activity_i, grade_activity_i, status_activity_i, ilm_id_activity_i, ilm_name_activity_i
  function export_csv () {
    global $USER, $CFG, $DB, $OUTPUT;
    $SEP = ';'; // CSV separator

    $str_csv_content = // string to be used with CSV text
      $SEP . "Attention field 'answer' is encoded with PHP function 'base64_encode(.)'.\n" .
      $SEP . "To process it by Python, you can use package 'base64' and function 'base64.b64decode(your_string)'.\n";

    // Finding all {iassign_statement} for the current iAssign block {iassign}:
    $fields = "student_id" . $SEP . "student_name";
    $id = $this->cm->id;
    $iassign_list = $DB->get_records_list('iassign_statement', 'iassignid', array('iassignid' => $this->iassign->id), "position ASC");
    $c = 1;
    foreach ($iassign_list as $iassign) {
      $fields .= $SEP . " activity_id_" . $c . $SEP . "activity_name_" . $c . $SEP . "total_submissions_activity_" . $c .
        $SEP . "grade_activity_" . $c . "" . $SEP . "status_activity_" . $c . $SEP . "ilm_id_activity_" . $c .
        $SEP . "ilm_name_activity_" . $c . $SEP . "timecreated_" . $c . $SEP . "timemodified_" . $c . $SEP . "answer_" . $c;
      $c++;
      }
    $str_csv_content .= $fields . "\n";

    // Find all enrolled students:
    $params_temp = array('shortname' => 'student');
    $role = $DB->get_record_sql("SELECT s.id, s.shortname FROM {role} s WHERE s.shortname = :shortname", $params_temp);
    $context = context_course::instance($this->course->id);
    $params_temp = array('contextid' => $context->id, 'roleid' => $role->id);
    $sql_query = "SELECT s.userid, a.firstname, a.lastname FROM {role_assignments} s, {user} a " .
      " WHERE s.contextid = :contextid AND s.userid = a.id AND s.roleid = :roleid " .
      " ORDER BY a.firstname ASC,a.lastname ASC";
    $students = $DB->get_records_sql($sql_query, $params_temp);
      
    // Now is exported each answer submited by each student
    foreach ($students as $student) { // Consider each student $student in the course
      $str_csv_content .= $student->userid . $SEP;
      $str_csv_content .= '"' . $student->firstname . ' ' . $student->lastname . '"' . $SEP;

      foreach ($iassign_list as $iassign) { // Consider each submission to this student $student->userid
        $str_csv_content .= $iassign->id . $SEP;
        $str_csv_content .= '"' . $iassign->name . '"' . $SEP;

        $student_submissions = $DB->get_record("iassign_submission", array('iassign_statementid' => $iassign->id, 'userid' => $student->userid));
        //D if ($student->userid==52) { echo "Leonardo: "; print_r($student_submissions); echo "<br/>"; }
        if ($student_submissions) {
          $str_csv_content .= $student_submissions->experiment . $SEP;
          $str_csv_content .= $student_submissions->grade . $SEP;
          switch ($student_submissions->status) { // {iassign_submission}.status = 3 => 'correct'; 2 => 'incorrect'; 1 => 'post'; 0 => 'not_post'
            case 3:
              $str_csv_content .= '"correct"' . $SEP;
              break;
            case 2:
              $str_csv_content .= '"incorrect"' . $SEP;
              break;
            case 1:
              $str_csv_content .= '"post"' . $SEP;
              break;
            case 0:
              $str_csv_content .= '"not_post"' . $SEP;
              break;
            }
          }
        else {
          $str_csv_content .= $SEP . $SEP . '"not_post"' . $SEP;
          }

        $str_csv_content .= $iassign->iassign_ilmid . $SEP;

        $ilm_activity = $DB->get_record("iassign_ilm", array('id' => $iassign->iassign_ilmid));
        if (is_null($ilm_activity) || !isset($ilm_activity->name)) ; //leo $str_csv_content .= '{iassign_ilm}.id=' . $iassign->iassign_ilmid . ' empty!';
        else $str_csv_content .= '"' . $ilm_activity->name . '"';

        if ($student_submissions) { // Also register fields: timecreated, timemodified, answer
          $str_csv_content .=  $SEP . $student_submissions->timecreated . $SEP . $student_submissions->timemodified . $SEP .
            base64_encode($student_submissions->answer) . $SEP;
	  }
	else $str_csv_content .= $SEP . $SEP . $SEP . $SEP;
        } // foreach ($iassign_list as $iassign)
      $str_csv_content .= "\n";
      }

    header("Content-type: text/csv");
    header("Content-disposition: attachment; filename=report.csv");
    // header("Content-type: text"); //D export as text
    // header("Content-disposition: attachment; filename=report.txt"); //D export as text
    header("Pragma: no-cache");
    header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
    header('Content-Length: ' . strlen($str_csv_content));
    header('Connection: close');
    print $str_csv_content;
    flush();
    } // function export_csv()


  /// Print the teachers' report (of one iAssign block {iassign}), when selected: report='Reports' | home='Home'
  //  Page with all activities {iassign_statement} in iAssign block {iassign}: {iassign_submission} to each student
  function report () {
    global $USER, $CFG, $DB, $OUTPUT;
    $id = $this->cm->id;

    // Get all activities in this block
    $iassign_list = $DB->get_records_list('iassign_statement', 'iassignid', array('iassignid' => $this->iassign->id), "position ASC");

    if ($this->action != 'print') {
      $title = get_string('report', 'iassign');
      print $OUTPUT->header();
      } // if ($this->action != 'print')

    $this->print_main_menu(); // line with other options: activities_page ; help_ilm  ; report  ; results ; auto_evaluate_name_config ; notifications ; add_iassign

    print $OUTPUT->box_start();
    $this->view_legend_icons();
    print $OUTPUT->box_end();

    if ($this->action == 'print')
      print '<table border=1 width="100%">' . "\n";
    else
      print '<table id="outlinetable" class="generaltable boxaligncenter table table-hover table-striped mt-5"  width="100%">' . "\n";
    print '<tr><th colspan=3 class="header c1">' . iassign_utils::remove_code_message($this->iassign->name) . '</th></tr>' . "\n";

    // report ; all_submissions

    // Prepare the list of all {iassign_statement} in this iAssign block {iassign}: number; due date; exercise statement ({iassign_statement}.name)
    $i = 1;
    $students_submissions = array(); // array indexed by student index with all submissions (to all activities)
    $stylebutton = "background: none; -webkit-box-shadow: 2px 2px 5px 0px rgba(0,0,0,0.75);-moz-box-shadow: 2px 2px 5px 0px rgba(0,0,0,0.75);box-shadow: 2px 2px 5px 0px rgba(0,0,0,0.75); min-width: 4.5rem;";
    foreach ($iassign_list as $one_iassign_statement) { // for all exercise in this block, print its information: number; due date; exercise statement
      $test_exercise = "";

      // Get all submissions to this activity (all students)
      $iassign_submission = $DB->get_records("iassign_submission", array("iassign_statementid" => $one_iassign_statement->id));

      if (($iassign_submission) && $one_iassign_statement->type_iassign < 3) {
        $test_exercise = " <b>(" . get_string('iassign_exercise', 'iassign') . ")</b>";
        }
      if ($one_iassign_statement->type_iassign == 3) { // 2023/09/03 removed (not used!): || ($iassign_submission)
        $students_submissions[$i] = new stdClass();
        $students_submissions[$i]->name = $one_iassign_statement->name;
        $students_submissions[$i]->id = $i;
        $students_submissions[$i]->iassignid = $one_iassign_statement->id;
        $students_submissions[$i]->timedue = $one_iassign_statement->timedue;
        $students_submissions[$i]->grade = $one_iassign_statement->grade;
        $students_submissions[$i]->total_answers = 0; // initialize the total of answers to this activity
        //D echo " - " . $students_submissions[$i]->id . " ; " . date("d/m/Y H:i",$students_submissions[$i]->timedue) . " ; " . $students_submissions[$i]->grade . " ; " . $students_submissions[$i]->name . "<br/>\n";

        print ' <tr >' . "\n";
        print "  <td class='cell c1 numviews' width='10px' align='center'><strong>" . $students_submissions[$i]->id . "</strong></td>\n";
        print "  <td class='cell c1' width='180px' title='" . get_string('duedate', 'iassign') . "'> " . date("d/m/Y H:i",$students_submissions[$i]->timedue) . " </td>\n";
        print "  <td class='cell c0 actvity' title='max grade=" . $one_iassign_statement->grade . "'> " . $students_submissions[$i]->name . ' ' . $test_exercise . " </td>\n";
        print ' </tr>' . "\n";
        $i++;
        } // if ($one_iassign_statement->type_iassign == 3)
      } // foreach ($iassign_list as $one_iassign_statement)

    $num_exerc = $i; // total of exercises + 1

    print "</table>\n";
    print "<p></p>\n";
    if ($this->action == 'print')
      print '<table border=1 width="100%">' . "\n";
    else // ./theme/boost/style/moodle.css: .generaltable
      print '<table id="outlinetable" class="generaltable boxaligncenter table table-hover table-striped table-bordered" cellpadding="5" width="100%">' . "\n";
    $context = context_course::instance($this->course->id);
    if ($num_exerc > 1) {
      // $role = $DB->get_record_sql("SELECT s.id, s.shortname FROM {$CFG->prefix}role s WHERE s.shortname = 'student'");
      $params_temp = array('shortname' => 'student');
      $role = $DB->get_record_sql("SELECT s.id, s.shortname FROM {role} s WHERE s.shortname = :shortname", $params_temp);

      // $students = $DB->get_records_sql("SELECT s.userid, a.firstname, a.lastname FROM {$CFG->prefix}role_assignments s, {$CFG->prefix}user a WHERE s.contextid = '$context->id' AND s.userid = a.id AND s.roleid = '$role->id' ORDER BY a.firstname ASC,a.lastname ASC");
      $params_temp = array('contextid' => $context->id, 'roleid' => $role->id);
      $students = $DB->get_records_sql(
        "SELECT s.userid, a.firstname, a.lastname FROM {role_assignments} s, {user} a " .
        " WHERE s.contextid = :contextid AND s.userid = a.id AND s.roleid = :roleid " .
        " ORDER BY a.firstname ASC,a.lastname ASC", $params_temp);

      print '<tr><th class="header c1">' . get_string('students', 'iassign') . '</th>' . "\n";

      $width = 90/$num_exerc;
     
      for ($j=1; $j<$num_exerc; $j++) { // all exercises on this block: initialize the counter of correct answers
        $sum_iassign_correct[$j] = 0;
        print '<th class="header c1" scope="col" style="width:' . $width . '%; text-align: center;">' . $students_submissions[$j]->id . '</th>' . "\n";
        // <th class="header c1" scope="col">
        }

      print '<th class="header c1" width=5%> ' . get_string('functions', 'iassign') . '</th>';
      $sum_iassign = $j - 1;
      print '</tr>' . "\n";
      $total = 0;
      $sum_student = 0;
      $comment = iassign_icons::insert('comment_read');
      $sum_comment = 0;
      $sum_correct_iassign = array(); //NOT used
      $sum_correct_student = array(); //NOT used

      $USER->matrix_iassign = array();
      if ($students) {
        $total_students = 0;
        foreach ($students as $tmp) { // count students
          $users_array[$total_students] = $tmp;
          $total_students++;
          }

        print "<strong>" . get_string('report_total_students', 'iassign') . "</strong>: " . $total_students . "<br/>"; // 'Total of students in class'

        for ($student_index=0; $student_index<$total_students; $student_index++) { // for all students
          print '<tr>' . "\n";
          $sum_student++;
          $name = $users_array[$student_index]->firstname . ' ' . $users_array[$student_index]->lastname;
          print '  <td style="vertical-align: middle;">' . $name . '</td>' . "\n";
          $total_in_activity = 0;
          $tentativas = 0;

          for ($j=1; $j<$num_exerc; $j++) { // for all exercise
            $sum_comment = $sum_verify_message = 0;

            // Now the last submission to this activity of this student
            $student_submissions = $DB->get_record("iassign_submission", array('iassign_statementid' => $students_submissions[$j]->iassignid, 'userid' => $users_array[$student_index]->userid)); // data about student solution
            // echo " - user.id=" . $users_array[$student_index]->userid . ", {ia}.id=" . $students_submissions[$j]->iassignid . " ; grade=" . $student_submissions->grade . "<br/>";

            if ($student_submissions) {

              $students_submissions[$j]->total_answers += 1; // one more submission to this activity

              $msg_grade = $student_submissions->grade . " &Element;  [0,1]"; // &Element; = bellong symbol
              if ($students_submissions[$j]->grade) // activity has grade associated
                $msg_grade .= " => " . $students_submissions[$j]->grade * $student_submissions->grade;

              $last_solution_submission = " title='" . userdate($student_submissions->timemodified) . ", " . $msg_grade . "' "; // timemodified: time of the last student solution
              $tentativas = $student_submissions->experiment;

              // Get student comments
              // $student_submissions_comment = $DB->get_record_sql("SELECT COUNT(iassign_submissionid) FROM {$CFG->prefix}ia_assign_submissions_comment WHERE iassign_submissionid = '$student_submissions->id'");
              $params_temp = array('iassign_submissionid' => $student_submissions->id);
              $student_submissions_comment = $DB->get_record_sql(
                "SELECT COUNT(iassign_submissionid) FROM {iassign_submission_comment} " .
                "WHERE iassign_submissionid = :iassign_submissionid", $params_temp);
              if ($student_submissions_comment) // student has comment
                foreach ($student_submissions_comment as $tmp)
                  $sum_comment = $tmp;

              // informations to previous activities
              if ($j - 1 < 1 || $j == $num_exerc) $iassign_previous = "-1";
              else $iassign_previous = $students_submissions[$j - 1]->iassignid;

              if ($student_index - 1 < 0 || $student_index == $total_students) $user_previous = "-1";
              else $user_previous = $users_array[$student_index - 1]->userid;

              // next
              if ($i - 1 > $j) $iassign_next = $students_submissions[$j + 1]->iassignid;
              else $iassign_next = "-1";

              if ($total_students - 1 > $student_index) $user_next = $users_array[$student_index + 1]->userid;
              else $user_next = "-1";

              $url = "" . $CFG->wwwroot . "/mod/iassign/view.php?action=viewsubmission&id=" . $id . "&iassign_submission_current=" . $student_submissions->id . "&userid_iassign=" .
                $users_array[$student_index]->userid . "&iassign_current=" . $students_submissions[$j]->iassignid . "&view_iassign=" . $this->view_iassign . "&row=" . $student_index . "&column=" . $j;

              // receiver=1 - message to teacher
              // receiver=2 - message to student
              // $verify_message = $DB->get_record_sql("SELECT COUNT(iassign_submissionid) FROM {$CFG->prefix}ia_assign_submissions_comment " .
              // "WHERE iassign_submissionid = '$student_submissions->id' AND return_status='0' AND receiver='1'");

              $params_temp = array('iassign_submissionid' => $student_submissions->id, 'return_status' => '0', 'receiver' => '1');
              $verify_message = $DB->get_record_sql(
                "SELECT COUNT(iassign_submissionid) FROM {iassign_submission_comment} " .
                "WHERE iassign_submissionid = :iassign_submissionid " .
                "  AND return_status= :return_status " .
                "  AND receiver= :receiver", $params_temp);

              if ($verify_message)
                foreach ($verify_message as $tmp)
                  $sum_verify_message = $tmp;

              if ($sum_verify_message > 0)
                $comment = '<i class="fa fa-comments" style="color: #658af7; font-size: 1.4rem;" aria-hidden="true"></i>';
              else
                $comment = '<i class="fa fa-comments" style="color: gray; font-size: 1.3rem;" aria-hidden="true"></i>';

              if ($student_submissions->status == 3) { // {iassign_submission}.status = 3 => 'correct'; 2 => 'incorrect'; 1 => 'post'; 0 => 'not_post'
                $sum_iassign_correct[$j]++;
                $total_in_activity++;
                $feedback = '<i class="fa fa-check" style="color: green; font-size: 1.2rem;" aria-hidden="true""></i>';
                }
              elseif ($student_submissions->status == 2) {
                $feedback = '<i class="fa fa-times" style="color: red; font-size: 1.2rem;" aria-hidden="true"></i>';
                }
              elseif ($student_submissions->status == 1) {
                $feedback = '<i class="fa fa-question" style="color: #77229c; font-size: 1.2rem;" aria-hidden="true"></i>';
                }
              elseif ($student_submissions->status == 0) {
                $feedback = '<i class="fa fa-question" style="color: #77229c; font-size: 1.2rem;" aria-hidden="true"></i>';
                }

              if ($this->action != 'print') {
                if ($tentativas > 0)
                  print '<td style="text-align: center;"> <a href="' . $url . '" ' . $last_solution_submission . '>'
                  . '<button type="button" class="btn btn-secondary" style="' . $stylebutton . '">'
                  . $feedback
                  . '<span class="badge badge-light" style="font-size: 1rem; margin-left: .5rem;"> '
                  . $tentativas 
                  . '</span> </button>'
                  . '</a>' . "\n";
                else
                  print '<td style="text-align: center;"> <a href="' . $url . '" ' . $last_solution_submission . '>'
                  . '<button type="button" class="btn btn-secondary" style="'.$stylebutton.'">'
                  . $feedback
                  .'</button>'
                  . '</a>' . "\n";

                if ($sum_comment > 0 && $sum_verify_message > 0)
                  print '<a href="' . $url . '"> '
                  . '<button type="button" class="btn btn-secondary" style="'.$stylebutton.'">'
                  . $comment
                  .'<span class="badge badge-light" style="font-size: 1rem; margin-left: .5rem;">'
                  . $sum_verify_message . '/' . $sum_comment
                  . '</span> </button>'
                  . '</a>';
                else if ($sum_comment > 0)
                  print '<a href="' . $url . '"> '
                  . '<button type="button" class="btn btn-secondary" style="'.$stylebutton.'"> '
                  . $comment
                  .'<span class="badge badge-light" style="font-size: 1rem; margin-left: .5rem;">'
                  . $sum_comment
                  . '</span> </button>'
                  . '</a>';
                print '</td>' . "\n";
                }

              if ($this->action == 'print')
                print $feedback . ' (' . $tentativas . ')<br>' . $comment . ' (' . $sum_comment . ') ' . "\n";

              } // if ($student_submissions)
            else { // if ($student_submissions) 
              // Student does not have any submission
 
              // informations to browse previous activities
              if ($j - 1 < 1 || $j == $i) $iassign_previous = "-1";
              else $iassign_previous = $students_submissions[$j - 1]->iassignid;

              if ($student_index - 1 < 0 || $student_index == $total_students) $user_previous = "-1";
              else $user_previous = $users_array[$student_index - 1]->userid;

              // next
              if ($i - 1 > $j) $iassign_next = $students_submissions[$j + 1]->iassignid;
              else $iassign_next = "-1";

              if ($total_students - 1 > $student_index) $user_next = $users_array[$student_index + 1]->userid;
              else $user_next = "-1";

              $url = $CFG->wwwroot . "/mod/iassign/view.php?action=viewsubmission&id=" . $id . "&userid_iassign=" . $users_array[$student_index]->userid .
                "&iassign_current=" . $students_submissions[$j]->iassignid . "&view_iassign=" . $this->view_iassign . "&row=" . $student_index . "&column=" . $j;

              $feedback = '<i class="fa fa-question" style="color: #77229c; font-size: 1.2rem;" aria-hidden="true"></i>';
              if ($this->action == 'print')
                print $feedback . ' (0)<br>' . $comment . ' (' . $sum_comment . ') ' . "\n";
              else {
                print '<td  style="text-align: center;"> <a href="' . $url . '">'
                . '<button type="button" class="btn btn-secondary" style="'.$stylebutton.'">'
                  . $feedback
                  .'</button>'
                . '</a>' . "\n";
                // print '<td>  </td>';
                if ($sum_comment > 0)
                  print '<a href="' . $url . '">' . $comment . '</a>  (' . $sum_comment . ') ' . "\n";
                // else print '<td>  </td>';
                print '</td>';
                }
              } // else if ($student_submissions)
            $USER->matrix_iassign[$student_index][$j] = new stdClass();
            $USER->matrix_iassign[$student_index][$j]->iassign_previous = $iassign_previous;
            $USER->matrix_iassign[$student_index][$j]->user_previous = $user_previous;
            $USER->matrix_iassign[$student_index][$j]->iassign_next = $iassign_next;
            $USER->matrix_iassign[$student_index][$j]->user_next = $user_next;

            if ($student_submissions)
              $USER->matrix_iassign[$student_index][$j]->iassign_submission_current = $student_submissions->id;
            else
              $USER->matrix_iassign[$student_index][$j]->iassign_submission_current = 0;
            //print '</td>' . "\n";
            } // for ($j=1; $j<$students_submissions_exerc; $j++)

          $total = $total + $total_in_activity;

          $porcentagem = ($total_in_activity / ($j - 1)) * 100; //Attention: final grade

          if ($tentativas != 0 && $tentativas != null) {
            $url_answer = "" . $CFG->wwwroot . "/mod/iassign/view.php?" . "action=download_all_answer&iassign_id=" . $this->iassign->id . "&userid=" . $users_array[$student_index]->userid . "&id=" . $id;
            print '  <td  align="center"><a href="' . $url_answer . '">' . iassign_icons::insert('download_all_assign') . '</a></td>' . "\n";
            }
          else {
            print '  <td  align="center">' . iassign_icons::insert('download_all_assign_disabled') . '</td>' . "\n";
            }

          print '</tr>' . "\n";
          //NOTused $sum_correct_student[$sum_student] = new stdClass();
          //NOTused $sum_correct_student[$sum_student]->name = $name;
          //NOTused $sum_correct_student[$sum_student]->sum = $total_in_activity;
          } // for ($student_index = 0; $student_index < $total_students; $student_index++)

        //NOTused for ($ii = 1; $ii < $num_exerc; $ii++) {
        //NOTused   if (is_null($sum_iassign_correct[$ii]))
        //NOTused     $sum_iassign_correct[$ii] = 0;
        //NOTused   $sum_correct_iassign[$ii] = new stdClass();
        //NOTused   $sum_correct_iassign[$ii]->sum = $sum_iassign_correct[$i];
        //NOTused   $sum_correct_iassign[$ii]->name = $students_submissions[$i]->name;
        //NOTused   }

        // Print total of submissions per activity: last line of table
        print " <tr><td><strong>" . get_string('report_total_submissions', 'iassign') . "</strong></td>\n"; // 'Total of submissions'
        for ($ii = 1; $ii < $num_exerc; $ii++) {
          print " <td  style='text-align: center;'>" . $students_submissions[$ii]->total_answers . "</td> ";
          }
        print " </tr>\n";

        } // if ($students)
      } // if ($num_exerc>1)
    else {
      print_string('no_activity', 'iassign');
      }

    print "</table>\n";

    print '<p class="mt-5">' . get_string('ps_experiment', 'iassign') . "</p>\n"; // the number of atemps is...
    print '<p>' . get_string('ps_comment', 'iassign') . "</p>\n"; // the total number of messages is...

    if ($this->action != 'print')
      print $OUTPUT->footer();
    die();
    } // function report()


  /// Display graphics of performance
  //  Used by teacher's interface: Reports | Graphics
  function stats () {
    global $USER, $CFG, $DB, $OUTPUT;
    $id = $this->cm->id;
    $iassign_statement_list = $DB->get_records_list('iassign_statement', 'iassignid', array('iassignid' => $this->iassign->id), "position ASC");

    if ($this->action != 'printstats')
      $title = get_string('graphic', 'iassign');

    $num = array();

    $ii = 1;
    $total_iassign_statement = 0; // total of exercises in this iAssign block
    foreach ($iassign_statement_list as $one_iassign_statement) {
      //REMOVE: useless here: $iassign_submission = $DB->get_records("iassign_submission", array("iassign_statementid" => $one_iassign_statement->id));
      if ($one_iassign_statement->type_iassign == 3) { // {iassign_statement}.type_iassign == 3 <=> is exercise
        $total_iassign_statement++; // one more exercise

        $sum_iassign_correct[$ii] = 0;

        $num[$ii] = new stdClass();
        $num[$ii]->name = $one_iassign_statement->name;
        $num[$ii]->id = $ii; // register index by {iassign_statement}.id

        $num[$ii]->iassignid = $one_iassign_statement->id; // {iassign_submission}.id xxxxxxxxxxxxx remover!!!
        $num[$ii]->iassign_statementid = $one_iassign_statement->id; // {iassign_submission}.iassign_statementid = {iassign_statement}.id

        $ii++;
        } // if ($one_iassign_statement->type_iassign == 3)
      } // foreach ($iassign_statement_list as $one_iassign_statement)

    $context = context_course::instance($this->course->id);

    if ($total_iassign_statement > 0) { // it has any exercise?
      // $role = $DB->get_record_sql("SELECT s.id, s.shortname FROM {$CFG->prefix}role s WHERE s.shortname = 'student'");
      $params_temp = array('shortname' => 'student');
      $role = $DB->get_record_sql(
        "SELECT s.id, s.shortname FROM {role} s " .
        " WHERE s.shortname = :shortname", $params_temp);

      // $students = $DB->get_records_sql("SELECT s.userid, a.firstname, a.lastname FROM {$CFG->prefix}role_assignments s, {$CFG->prefix}user a WHERE s.contextid = '$context->id' AND s.userid = a.id AND s.roleid = '$role->id' ORDER BY a.firstname ASC,a.lastname ASC");

      $params_temp = array('contextid' => $context->id, 'roleid' => $role->id);
      $students = $DB->get_records_sql(
        "SELECT s.userid, a.firstname, a.lastname FROM {role_assignments} s, {user} a " .
        " WHERE s.contextid = :contextid AND s.userid = a.id AND s.roleid = :roleid " .
        " ORDER BY a.firstname ASC,a.lastname ASC", $params_temp);

      $j = 0;
      $total = 0;
      $total_students_class = 0; // will receive the total of students in class
      $aux_total_students_class = 0; // will count students in class, to define '$total_students_class'
      $sum_correct_iassign = array();
      $sum_correct_student = array();
      $sum_experiment = array();
      $total_at_least_one_submission = array(); // array to register the total number of exercises with at least one submission (per exercicse)

      for ($jj = 1; $jj <= $total_iassign_statement; $jj++) {
         $sum_experiment[$jj] = 0;
         $total_at_least_one_submission[$jj] = 0;
         }

      if ($students) { // $students has {role_assignments}.userid, {user}.firstname, {user}.lastname
        foreach ($students as $one_user) { // For each student
          $aux_total_students_class++;
          $name = $one_user->firstname . ' ' . $one_user->lastname;
          // rows
          $count_student_correct_answer = 0;

          for ($jj = 1; $jj <= $total_iassign_statement; $jj++) { // {iassign_submission}.iassign_statementid = {iassign_statement}.id
            $student_submissions = $DB->get_record("iassign_submission", array('iassign_statementid' => $num[$jj]->iassignid, 'userid' => $one_user->userid)); // data about student solution
            if ($student_submissions) {
              if ($student_submissions->status == 3) {
                $sum_iassign_correct[$jj]++;
                $count_student_correct_answer++;
                } // if ($student_submissions->status == 3)

              // Sum the total number of "experiment" (if set, all of them is registerede in {iassign_allsubmissions})
              // {iassign_submission}.experiment is the number of time student "click" in the evaluation button of the iLM
              $total_experiment = $student_submissions->experiment;
              $sum_experiment[$jj] += $student_submissions->experiment; // to be used in 'Total Attempts' and 'Average attempts'
              $total_at_least_one_submission[$jj]++; // one more student with submission to this exercise - used in 'Average attempts'
              }
            } // for ($jj=1; $jj<$total_iassign_statement; $jj++)

          $total = $total + $count_student_correct_answer;
          $sum_correct_student[$aux_total_students_class] = new stdClass();
          $sum_correct_student[$aux_total_students_class]->name = $name;
          $sum_correct_student[$aux_total_students_class]->sum = $count_student_correct_answer; // total of correct exercises of this student
          } // foreach ($students as $one_user)
        $total_students_class = $aux_total_students_class;
        }

      for ($ii = 1; $ii < $total_iassign_statement; $ii++) {
        if (is_null($sum_iassign_correct[$ii])) {
          $sum_iassign_correct[$ii] = 0;
          }
        $sum_correct_iassign[$ii] = new stdClass();
        $sum_correct_iassign[$ii]->sum = $sum_iassign_correct[$ii];
        $sum_correct_iassign[$ii]->name = $num[$ii]->name;
        $sum_correct_iassign[$ii]->experiment = $sum_experiment[$ii]; // {iassign_submission}.experiment : total
        } // for ($ii = 1; $ii < $total_iassign_statement; $ii++)
      } // if ($total_iassign_statement > 1)

    print $OUTPUT->header();
    $this->print_main_menu();

    if ($this->action != 'printstats') {
      $title = get_string('graphic', 'iassign');
      $link_report = "<a href='" . $CFG->wwwroot . "/mod/iassign/view.php?id=" . $id . "&action=report&iassignid=" . $this->iassign->id . "'>" . iassign_icons::insert('view_report') . ' ' . get_string('report', 'iassign') . "</a>";
      $link_print_stats = "<a href='" . $CFG->wwwroot . "/mod/iassign/view.php?id=" . $id . "&action=printstats&&iassignid=" . $this->iassign->id . "'>" . iassign_icons::insert('print') . ' ' . get_string('print', 'iassign') . "</a>";
      print '<table width=100%><tr>';
      print '<td align="right">' . $link_print_stats . '</td>' . "\n";
      print '<td width=15% align="right">' . $link_report . '</td>';
      print '</td></tr></table>' . "\n";

      print "<br><br>";
      print '<table id="outlinetable" class="generaltable boxaligncenter" cellpadding="5" width="100%">' . "\n";
      print '<tr><th colspan=5 class="header c1">' . get_string('distribution_activity', 'iassign') . '</th></tr>' . "\n"; // 'Distribution of hits per activity'
      print '<tr><td class=\'cell c0 actvity\' width=35%><strong>' . iassign_utils::remove_code_message($this->iassign->name) . '</strong></td>' . "\n";
      print '<td class=\'cell c0 actvity\' width=35%><strong>' . get_string('percentage_correct', 'iassign') . '</strong></td>' . "\n";           // 'Percentage of correct'
      print '<td class=\'cell c0 actvity\' width=10% align="right"><strong>' . get_string('proportion_correct', 'iassign') . '</strong>' . "\n";  // 'Percentage of correct'
      print '<td class=\'cell c0 actvity\' width=10% align="right"><strong>' . get_string('sum_experiment', 'iassign') . '</strong></td>' . "\n"; // 'Total Attempts'
      print '<td class=\'cell c0 actvity\' width=10% align="right"><strong>' . get_string('avg_experiment', 'iassign') . '</strong></td>' . "\n"; // 'Average attempts'
      print '</tr>' . "\n";
      $sum_correct = 0;

      //D echo "total_students_class=" . $total_students_class . "<br/>\n"; //D 2023/09/02

      if ($sum_correct_iassign) { // is any correct answer in the array?
        $ii = 1; // exercise number
        foreach ($sum_correct_iassign as $each_correct_answer) { // element of array with: sum, name, experiment
          if (is_null($each_correct_answer->experiment))
            $each_correct_answer->experiment = 0;
          $bar = "";
          $sum = $each_correct_answer->sum;

          $percent = ($sum / $total_students_class) * 100; //Attention: final grade

          $text = number_format($percent, 1) . '%';
          $sum_correct += $sum;
          if ($sum > 0) {
            for ($i = 1; $i < $percent * 2; $i++)
              $bar .= iassign_icons::insert('hbar_blue');
            $bar .= iassign_icons::insert('hbar_blue_r');
            } // if ($sum > 0)

          print '<tr ><td class=\'cell c0 actvity\'width=35%>' . $each_correct_answer->name . '</td>' . "\n";
          print ' <td class=\'cell c0 actvity\' width=35%>' . $bar . ' ' . $text . '</td>' . "\n";                                // 'Percentage of correct' answers
          print ' <td class=\'cell c0 actvity\' width=10% align="right">' . $sum . '/' . $total_students_class . '</td>' . "\n";  // 'Percentage of correct'
          print ' <td class=\'cell c0 actvity\' width=10% align="right">' . $each_correct_answer->experiment . '</td>' . "\n";    // 'Total Attempts'

	  //Attention: final grade
          print ' <td class=\'cell c0 actvity\' width=10% align="right">' . number_format($each_correct_answer->experiment / $total_at_least_one_submission[$ii], 1) . '</td>' . "\n"; // 'Average attempts'

          print '</tr>' . "\n";
          $ii++;
          } // foreach ($sum_correct_iassign as $each_correct_answer)
        }
      print "</table>";
      print "<br><br>";
      print '<table id="outlinetable" class="generaltable boxaligncenter" cellpadding="5" width="100%">' . "\n";
      print '<tr><th colspan=3 class="header c1">' . get_string('distribution_student', 'iassign') . '</th></tr>' . "\n";         // 'Distribution of hits per studant'
      print '<tr><td class=\'cell c0 actvity\' width=50%><strong>' . iassign_utils::remove_code_message($this->iassign->name) . '</strong></td>' . "\n";
      print ' <td class=\'cell c0 actvity\' width=40%><strong>' . get_string('percentage_correct', 'iassign') . '</strong></td>'; // 'Percentage of correct'
      print ' <td class=\'cell c0 actvity\' width=10% align="right"><strong>' . get_string('sum_correct', 'iassign') . '</strong></td>';
      print '</tr>' . "\n";
      $sum_correct = 0;
      foreach ($sum_correct_student as $student) {
        $bar = "";
        $sum = $student->sum;
        $percent = ($sum / $total_iassign_statement) * 100; //Attention: final grade
        $text = number_format($percent, 1) . '%';
        $sum_correct += $sum;
        if ($sum > 0) {
          for ($i = 1; $i < $percent * 2; $i++)
            $bar .= iassign_icons::insert('hbar_blue');
          $bar .= iassign_icons::insert('hbar_blue_r');
          } // if ($sum > 0)
        print '<tr ><td class=\'cell c0 actvity\'width=50%>' . $student->name . '</td>' . "\n";
        print '<td class=\'cell c0 actvity\' width=40%>' . $bar . ' ' . $text . '</td>' . "\n";
        print '<td class=\'cell c0 actvity\' width=10% align="right">' . $sum . '/' . $total_iassign_statement . '</td>' . "\n";
        print '</tr>' . "\n";
        } // foreach ($sum_correct_student as $student)
      print "</table>\n";
      print "<br><br>\n";

      $var1 = 0;
      $cv = 0;
      $dv = 0;
      $avg = 0;
      if ($sum_correct_student) {
        $avg = $sum_correct / $total_students_class; //Attention: final grade
        if ($avg > 0) {
          foreach ($sum_correct_student as $student) {
            $aux = $student->sum - $avg;
            $var1 += $aux * $aux; // pow($student->sum - $avg, 2);
            }
          $var1 = $var1 / $total_students_class; //Attention: final grade
          $dv = sqrt($var1); // population variation
          $cv = ($dv / $avg) * 100; // population standart deviation //Attention: final grade
          }
        }

      $msg_sum_activity = ' title="' . get_string('sum_activity_title', 'iassign') . '"'; // 'The number of exercises in this iAssign block of activities (NE)'
      $msg_sum_student = ' title="' . get_string('sum_student_title', 'iassign') . '"'; // 'The number of students in this course/class (NS)'
      $msg_mean_score = ' title="' . get_string('mean_score_title', 'iassign') . '"'; // 'The ration total number of students with correct answers divided by the number of students (M)'
      $msg_standard_deviation = ' title="' . get_string('standard_deviation_title', 'iassign') . '"'; // 'Population variation (PV) - over NS'
      $msg_coefficient_variation = ' title="' . get_string('coefficient_variation_title', 'iassign') . '"'; // 'Population standard deviation: square root of PV'
      print '<table id="outlinetable" class="generaltable boxaligncenter" cellpadding="5" width="100%">' . "\n";
      print '<tr><th colspan=5 class="header c1">' . get_string('statistics', 'iassign') . '</th></tr>' . "\n";
      print '<tr><td class=\'cell c0 actvity\' width=20% align="center"' . $msg_sum_activity . '><strong>' . get_string('sum_activity', 'iassign') . '</strong></td>' . "\n"; // 'Total activity'
      print ' <td class=\'cell c0 actvity\' width=20% align="center"' . $msg_sum_student . '><strong>' . get_string('sum_student', 'iassign') . '</strong></td>' . "\n";  // 'Total students'
      print ' <td class=\'cell c0 actvity\' width=20% align="center"' . $msg_mean_score . '><strong>' . get_string('mean_score', 'iassign') . '</strong></td>' . "\n"; // 'Mean scores'
      print ' <td class=\'cell c0 actvity\' width=20% align="center"' . $msg_standard_deviation . '><strong>' . get_string('standard_deviation', 'iassign') . '</strong></td>' . "\n"; // 'Standard deviation'
      print ' <td class=\'cell c0 actvity\' width=20% align="center"' . $msg_coefficient_variation . '><strong>' . get_string('coefficient_variation', 'iassign') . '</strong></td></tr>' . "\n"; // 'Coefficient of variation'
      print '<tr><td class=\'cell c0 actvity\' width=20% align="center">' . $total_iassign_statement . '</td>' . "\n";
      print ' <td class=\'cell c0 actvity\' width=20% align="center">' . $total_students_class . '</td>' . "\n";
      print ' <td class=\'cell c0 actvity\' width=20% align="center">' . number_format($avg, 1) . '</td>' . "\n";  // 'Mean scores'
      print ' <td class=\'cell c0 actvity\' width=20% align="center">' . number_format($dv, 1) . '</td>' . "\n";
      print ' <td class=\'cell c0 actvity\' width=20% align="center">' . number_format($cv, 1) . '%</td></tr>' . "\n";
      print "</table>\n";

      print $OUTPUT->footer();
    } else {
      print "<STYLE TYPE='text/css'>
  <!--
  .boldtable {
    font-family:sans-serif;
    font-size:10pt;
    }
  -->
</STYLE>\n";

      print '<table border=1 width=100%>' . "\n";
      print '<tr><td colspan=3 align="center"><strong>' . get_string('distribution_activity', 'iassign') . '</strong></td></tr>' . "\n"; // 'Distribution of hits per activity'
      print '<tr><td width=50%><strong>' . iassign_utils::remove_code_message($this->iassign->name) . '</strong></td>' . "\n";
      print '<td width=40%><strong>' . get_string('percentage_correct', 'iassign') . '</strong></td>';// 'Percentage of correct'
      print '<td width=10% align="right"><strong>' . get_string('sum_correct', 'iassign') . '</strong></td>';
      print '</tr>' . "\n";
      $sum_correct = 0;
      foreach ($sum_correct_iassign as $each_correct_answer) {
        $bar = "";
        $sum = $each_correct_answer->sum;
        $percent = ($sum / $total_students_class) * 100; //Attention: final grade
        $text = number_format($percent, 1) . '%';
        $sum_correct += $sum;
        if ($sum > 0) {
            for ($i = 1; $i < $percent * 2; $i++)
                $bar .= iassign_icons::insert('hbar_blue');
            $bar .= iassign_icons::insert('hbar_blue_r');
          } // if ($sum > 0)
        print '<tr><td width=50%>' . $each_correct_answer->name . '</td>' . "\n";
        print '<td width=40%>' . $bar . ' ' . $text . '</td>';
        print '<td width=10% align="right">' . $sum . '/' . $total_students_class . '</td>';
        print '</tr>' . "\n";
        } // foreach ($sum_correct_iassign as $each_correct_answer)
      print "</table>";
      print "<br><br>";
      print '<table border=1 class="boldtable" width=100%>' . "\n";
      print '<tr><td colspan=3 align="center" ><strong>' . get_string('distribution_student', 'iassign') . '</strong></td></tr>' . "\n"; // 'Distribution of hits per studant'
      print '<tr><td width=50%><strong>' . iassign_utils::remove_code_message($this->iassign->name) . '</strong></td>' . "\n";
      print '<td  width=40%><strong>' . get_string('percentage_correct', 'iassign') . '</strong></td>'; // 'Percentage of correct'
      print '<td  width=10% align="right"><strong>' . get_string('sum_correct', 'iassign') . '</strong></td>';
      print '</tr>' . "\n";
      $sum_correct = 0;
      foreach ($sum_correct_student as $student) {
        $bar = "";
        $sum = $student->sum;
        $percent = ($sum / $total_iassign_statement) * 100; //Attention: final grade
        $text = number_format($percent, 1) . '%';
        $sum_correct += $sum;
        if ($sum > 0) {
          for ($i = 1; $i < $percent * 2; $i++)
            $bar .= iassign_icons::insert('hbar_blue');
          $bar .= iassign_icons::insert('hbar_blue_r');
          } // if ($sum > 0)
        print "<tr><td width=50%>" . $student->name . "</td>\n";
        print ' <td width=40%>' . $bar . ' ' . $text . '</td>' . "\n";
        print ' <td width=10% align="right">' . $sum . '/' . $total_iassign_statement . '</td>' . "\n";
        print '</tr>' . "\n";
        } // foreach ($sum_correct_student as $student)
      print "</table>\n";
      print "<br/><br/>\n";

      $var1 = 0;
      $cv = 0;
      $dv = 0;
      $avg = 0;
      if ($sum_correct_student) {
        $avg = $sum_correct / $total_students_class; //Attention: final grade
        if ($avg > 0) {
          foreach ($sum_correct_student as $student)
            $var1 += pow($student->sum - $avg, 2);
          $var1 = $var1 / $total_students_class;
          $dv = sqrt($var1);
          $cv = ($dv / $avg) * 100;
          }
        }

      print '<table border=1 class="boldtable" width=100%>' . "\n";
      print '<tr><td colspan=5 align="center"><strong>' . get_string('statistics', 'iassign') . '</strong></th></tr>' . "\n";
      print '<tr><td width=20% align="center"><strong>' . get_string('sum_activity', 'iassign') . '</strong></td>' . "\n";
      print '<td  width=20% align="center"><strong>' . get_string('sum_student', 'iassign') . '</strong></td>' . "\n";
      print '<td  width=20% align="center"><strong>' . get_string('mean_score', 'iassign') . '</strong></td>' . "\n";
      print '<td  width=20% align="center"><strong>' . get_string('standard_deviation', 'iassign') . '</strong></td>' . "\n";
      print '<td  width=20% align="center"><strong>' . get_string('coefficient_variation', 'iassign') . '</strong></td></tr>' . "\n";
      print '<tr><td  width=20% align="center">' . $total_iassign_statement . '</td>' . "\n";
      print '<td  width=20% align="center">' . $total_students_class . '</td>' . "\n";
      print '<td  width=20% align="center">' . number_format($avg, 1) . '</td>' . "\n";
      print '<td  width=20% align="center">' . number_format($dv, 1) . '</td>' . "\n";
      print '<td  width=20% align="center">' . number_format($cv, 1) . '%</td></tr>' . "\n";
      print "</table>\n";
      } // if ($this->action != 'printstats')
    die();
    } // function stats()


  /// Display graphics of performance for students
  function stats_students () {
    global $USER, $CFG, $DB, $OUTPUT;
    $id = $this->cm->id;
    $iassign_statement_list = $DB->get_records_sql("SELECT * FROM {iassign_statement} s " .
      " WHERE s.iassignid = '{$this->iassign->id}' AND s.type_iassign=3 ORDER BY s.position");

    $title = get_string('results', 'iassign');

    $sum_correct = 0;
    $sum_incorrect = 0;
    $sum_post = 0;
    $sum_nopost = 0;
    $sum_iassign_st = count($iassign_statement_list);
    $bar_nopost = "";
    $bar_correct = "";
    $bar_incorrect = "";
    $bar_post = "";
    $text_nopost = "";
    $text_correct = "";
    $text_incorrect = "";

    foreach ($iassign_statement_list as $iassign_statement_activity_item) {
      $iassign_submission = $DB->get_record("iassign_submission", array('iassign_statementid' => $iassign_statement_activity_item->id, 'userid' => $USER->id)); // data about student solution
      if ($iassign_submission) {
        if ($iassign_submission->status == 3)
          $sum_correct++;
        elseif ($iassign_submission->status == 2)
          $sum_incorrect++;
        elseif ($iassign_submission->status == 1)
          $sum_post++;
        elseif ($iassign_submission->status == 0 || !$iassign_submission)
          $sum_nopost++;
        } // if ($iassign_submission)
      } // foreach ($iassign_statement_list as $iassign_statement_activity_item)

    if ($sum_iassign_st > 0) {
      $percent_correct = ($sum_correct / $sum_iassign_st) * 100; //Attention: final grade
      $text_correct = number_format($percent_correct, 1) . '%';
      }

    if ($sum_correct > 0) {
      for ($i = 1; $i < $percent_correct * 2; $i++)
        $bar_correct .= iassign_icons::insert('hbar_green');
      $bar_correct .= iassign_icons::insert('hbar_green_r');
      } // if ($sum_correct > 0)

    if ($sum_iassign_st > 0) {
      $percent_incorrect = ($sum_incorrect / $sum_iassign_st) * 100;
      $text_incorrect = number_format($percent_incorrect, 1) . '%';
      }

    if ($sum_incorrect > 0) {
      for ($i = 1; $i < $percent_incorrect * 2; $i++)
        $bar_incorrect .= iassign_icons::insert('hbar_red');
      $bar_incorrect .= iassign_icons::insert('hbar_red_r');
      } // if ($sum_incorrect > 0)

    if ($sum_iassign_st > 0) {
      $percent_post = ($sum_post / $sum_iassign_st) * 100;
      $text_post = number_format($percent_post, 1) . '%';
      }
    if ($sum_post > 0) {
      for ($i = 1; $i < $percent_post * 2; $i++)
        $bar_post .= iassign_icons::insert('hbar_blue');
      $bar_post .= iassign_icons::insert('hbar_blue_r');
      } // if ($sum_post > 0)

    if ($sum_iassign_st > 0) {
      $percent_nopost = ($sum_nopost / $sum_iassign_st) * 100;
      $text_nopost = number_format($percent_nopost, 1) . '%';
      }
    if ($sum_nopost > 0) {
      for ($i = 1; $i < $percent_nopost * 2; $i++)
        $bar_nopost .= iassign_icons::insert('hbar_orange');
      $bar_nopost .= iassign_icons::insert('hbar_orange_r');
      } // if ($sum_nopost > 0)

    print $OUTPUT->header();

    //2021/12 $link_return = " <a href='" . $this->return . "'>" . iassign_icons::insert('home') . get_string('activities_page', 'iassign') . "</a>";
    //2021/12 print '<table width=100%><tr>';
    //2021/12 print '<td align="right">' . $link_return . '</td>' . "\n";
    //2021/12 print '</td></tr></table>' . "\n";

    print "<br/><br/>\n";
    print '<table id="outlinetable" class="generaltable box aligncenter mt-5" cellpadding="5" width="100%">' . "\n";

    print '<tr><th colspan=3 class="header c1">' . "\n";
    // helpbutton('legend', get_string('legend', 'iassign'), 'iassign', $image = true, $linktext = false, $text = '', $return = false,
    // $imagetext = '');
    print iassign_utils::remove_code_message($this->iassign->name) . '</th></tr>' . "\n";

    print '<tr ><td class=\'cell c0 actvity\'width=50%>' . get_string('correct', 'iassign') . '</td>' . "\n";
    print '<td class=\'cell c0 actvity\' width=40%>' . $bar_correct . ' ' . $text_correct . '</td>';
    print '<td class=\'cell c0 actvity\' width=10% align="right">' . $sum_correct . '/' . $sum_iassign_st . '</td>';
    print '</tr>' . "\n";
    print '<tr ><td class=\'cell c0 actvity\'width=50%>' . get_string('incorrect', 'iassign') . '</td>' . "\n";
    print '<td class=\'cell c0 actvity\' width=40%>' . $bar_incorrect . ' ' . $text_incorrect . '</td>';
    print '<td class=\'cell c0 actvity\' width=10% align="right">' . $sum_incorrect . '/' . $sum_iassign_st . '</td>';
    print '</tr>' . "\n";
    if ($sum_post) {
      print '<tr ><td class=\'cell c0 actvity\'width=50%>' . get_string('post', 'iassign') . '</td>' . "\n";
      print '<td class=\'cell c0 actvity\' width=40%>' . $bar_post . ' ' . $text_post . '</td>';
      print '<td class=\'cell c0 actvity\' width=10% align="right">' . $sum_post . '/' . $sum_iassign_st . '</td>';
      print '</tr>' . "\n";
      } // if ($sum_post)
    print '<tr ><td class=\'cell c0 actvity\'width=50%>' . get_string('not_post', 'iassign') . '</td>' . "\n";
    print '<td class=\'cell c0 actvity\' width=40%>' . $bar_nopost . ' ' . $text_nopost . '</td>';
    print '<td class=\'cell c0 actvity\' width=10% align="right">' . $sum_nopost . '/' . $sum_iassign_st . '</td>';
    print '</tr>' . "\n";
    print "</table>";
    print "<br><br>";
    print '<table id="outlinetable" class="generaltable boxaligncenter" cellpadding="5" width="100%">' . "\n";
    print '<tr><th colspan=3 class="header c1">' . get_string('grades', 'iassign') . '</th></tr>' . "\n";
    print '<tr><td class=\'cell c0 actvity\' width=50%><strong>' . iassign_utils::remove_code_message($this->iassign->name) . '</strong></td>' . "\n";
    print '<td class=\'cell c0 actvity\' width=25% align=right><strong>' . get_string('grade_student', 'iassign') . '</strong></td>' . "\n";
    print '<td class=\'cell c0 actvity\' width=25% align=right><strong>' . get_string('grade_iassign', 'iassign') . '</strong></tr>' . "\n";

    $sum_grade = 0;
    $sum_grade_student = 0;
    $avg = 0;
    foreach ($iassign_statement_list as $iassign_statement_activity_item) {
      $iassign_submission = $DB->get_record("iassign_submission", array('iassign_statementid' => $iassign_statement_activity_item->id, 'userid' => $USER->id));
      if (!$iassign_submission) {
        $iassign_submission = new stdClass();
        $iassign_submission->grade = 0;
        }
      print '<tr ><td class=\'cell c0 actvity\'width=50%>' . $iassign_statement_activity_item->name . '</td>' . "\n";
      print '<td class=\'cell c0 actvity\' width=25% align=right>' . $iassign_submission->grade . '</td>';
      print '<td class=\'cell c0 actvity\' width=25% align=right>' . $iassign_statement_activity_item->grade . '</td>';
      print '</tr>' . "\n";

      $sum_grade += $iassign_statement_activity_item->grade;
      $sum_grade_student += $iassign_submission->grade;
      } // foreach ($iassign_statement_list as $iassign_statement_activity_item)
    if ($sum_grade > 0)
      $avg = $sum_grade_student / $sum_grade * 100;

    print '<tr><td class=\'cell c0 actvity\' width=50%><strong>' . get_string('total', 'iassign') . '</strong></td>' . "\n";
    print '<td class=\'cell c0 actvity\' width=25% align=right><strong>' . $sum_grade_student . '</strong></td>' . "\n";
    print '<td class=\'cell c0 actvity\' width=25% align=right><strong>' . $sum_grade . '</strong></tr>' . "\n";
    print '<tr><td class=\'cell c0 actvity\' width=25% align=left><strong>' . get_string('percentage_correct', 'iassign') . '</strong></td>' . "\n"; // 'Percentage of correct'
    print '<td colspan=2 class=\'cell c0 actvity\' align=right><strong>' . number_format($avg, 1) . '%</strong></tr>' . "\n";
    print "</table>";

    print $OUTPUT->footer();
    die();
    } // function stats_students()


  function get_total_notifications () {
    global $DB, $USER, $COURSE;
    $total_messages = 0;
    if (has_capability('mod/iassign:editiassign', $this->context, $USER->id)) {
      $params = array('userid' => $USER->id, 'course' => $COURSE->id, 'receiver' => '1');
      $total_messages = $DB->get_record_sql("SELECT COUNT(*) AS total FROM {iassign_submission_comment} AS c, {iassign_submission} AS s, {iassign_statement} AS st, {iassign} AS ias "
        . " WHERE c.return_status=0 AND c.receiver=:receiver AND c.iassign_submissionid=s.id AND s.iassign_statementid=st.id AND st.iassignid=ias.id AND ias.course=:course", $params);
      }
    else {
      $params = array('userid' => $USER->id, 'course' => $COURSE->id, 'receiver' => '2');

      $total_messages = $DB->get_record_sql("SELECT COUNT(*) AS total FROM {iassign_submission_comment} AS c, {iassign_submission} AS s, {iassign_statement} AS st, {iassign} AS ias "
        . " WHERE c.return_status=0 AND c.receiver=:receiver AND c.iassign_submissionid=s.id AND s.userid=:userid AND s.iassign_statementid=st.id AND st.iassignid=ias.id AND ias.course=:course", $params);

      }
    return $total_messages->total;
    }


  function get_iassign_statement_messages () {
    global $DB, $USER, $COURSE;
    if (has_capability('mod/iassign:editiassign', $this->context, $USER->id)) {
      $params = array('userid' => $USER->id, 'course' => $COURSE->id, 'receiver' => '1');
      $totais = array();
      $messages = $DB->get_records_sql("SELECT c.id AS commentid, st.id AS stid, s.userid AS user, ias.id AS iasid, s.id AS subid, st.name AS stname, ias.name AS iasname FROM {iassign_submission_comment} AS c, {iassign_submission} AS s, {iassign_statement} AS st, {iassign} AS ias "
        . " WHERE c.return_status=0 AND c.receiver=:receiver AND c.iassign_submissionid=s.id AND s.iassign_statementid=st.id AND st.iassignid=ias.id AND ias.course=:course", $params);
      foreach($messages as $item) {
        if (isset($totais[$item->subid])) {
          $totais[$item->subid]->total++;
          }
        else {
          $totais[$item->subid] = new stdclass();
          $item->total = 1;
          $totais[$item->subid] = $item;
          }
        }
      return $totais;
      }
    else {
      $params = array('userid' => $USER->id, 'course' => $COURSE->id, 'receiver' => '2');
      $totais = array();
      $messages = $DB->get_records_sql("SELECT c.id as commentid, s.id AS subid, st.id AS stid, ias.id AS iasid, s.userid AS userid, st.name AS stname, ias.name AS iasname FROM {iassign_submission_comment} AS c, {iassign_submission} AS s, {iassign_statement} AS st, {iassign} AS ias "
        . " WHERE c.return_status=0 AND c.receiver=:receiver AND c.iassign_submissionid=s.id AND s.userid=:userid AND s.iassign_statementid=st.id AND st.iassignid=ias.id AND ias.course=:course", $params);
      foreach ($messages as $item) {
        if (isset($totais[$item->subid])) {
          $totais[$item->subid]->total++;
          }
        else {
          $totais[$item->subid] = new stdclass();
          $item->total = 1;
          $totais[$item->subid] = $item;
          }
        }
      return $totais;
      }
    }


  // It shows to the teacher all iAssign notifications (all new students' comments)
  function view_notifications_teachers () {
    global $OUTPUT, $USER, $DB;

    $messages = $this->get_iassign_statement_messages();
    print '<table class="table table-hover ml-4 mb-4 w-75 mt-3" style="border-bottom: 1px solid #dee2e6;">';

    // 1. list all messages received in the current block:
    $actual_block = $this->iassign->id;
    $id = $this->cm->id;
    $total_include = 0;
    $activities_actual_block = array();
    if ($messages) {
      foreach ($messages as $item)
        if ($item->iasid == $actual_block)
          if (!in_array($item->stid, $activities_actual_block))
            array_push($activities_actual_block, $item->stid);
      }
    if ($activities_actual_block) {
      foreach ($activities_actual_block as $activity_id) {
        $printed = false;
        foreach ($messages as $item) {
          if ($item->stid == $activity_id) {
            if (!$printed) {
              $student_url = "view.php?id=$id&userid_iassign=$USER->id&action=view&iassign_current=$item->stid";
              $link_student = "<a href='$student_url'>" . $item->stname . "</a>";
              print '<tr><td style="white-space: nowrap; width: 1%; padding-right: 2rem;" class="align-middle">' . $link_student . '';
              print '<table class="table table-hover ml-4 mb-4 w-75 mt-3" style="border-bottom: 1px solid #dee2e6;">';
              $printed = true;
            }
            $submission_url = "view.php?id=$id&userid_iassign=$item->user&action=viewsubmission&iassign_current=$item->stid";

            $user = $DB->get_record('user', array('id' => $item->user));

            $label = $item->total > 1 ? get_string('comment_unread', 'iassign') : get_string('comment_unread_one', 'iassign');
            print '<tr><td style="white-space: nowrap; width: 1%; padding-right: 2rem;" class="align-middle"><i class="fa fa-user" aria-hidden="true"></i> ' . $user->firstname . ' '
              . $user->lastname . ' </td> <td class="align-middle"> <a href="' . $submission_url . '#comments"><span class="badge bg-danger" style="color:white; font-size: .9em;">'
              . '<i class="fa fa-envelope" aria-hidden="true"></i> ' . $label . ' <span class="badge rounded-pill bg-light text-dark" style="font-size: .8em;">'.$item->total.'</span></span></a></td>'
              . "</tr>\n";
            $total_include++;
            }
          }
        print "</td></tr></table>\n";
        }
      }
    print "</table>\n";
    if (count($activities_actual_block) == 0) {
      print '<h6 class="ml-5">' . get_string('empty_new_messages_block', 'iassign') . "</h6>\n";
      print '<hr class="my-4">' . "\n";
      }
    if ($messages && $total_include == count($messages)) {
      print $OUTPUT->footer();
      return;
      }
    print '<h4 class="mt-4 mb-3 ml-2"><i class="fa fa-comments-o" aria-hidden="true"></i> ' . get_string('new_messages_other_blocks', 'iassign') . "</h4>\n";
    $iassign_ids = array();
    if ($messages) {
      foreach ($messages as $item)
        if (!in_array($item->iasid, $iassign_ids) && $item->iasid != $actual_block)
          array_push($iassign_ids, $item->iasid);
      }
    foreach ($iassign_ids as $id_iassign) {
      $printed = false;
      $activities_in_block = array();
      foreach ($messages as $subitem) {
        if ($subitem->iasid == $id_iassign)
          if (!in_array($item->stid, $activities_in_block))
            array_push($activities_in_block, $item->stid);
        }
      foreach ($messages as $item) {
        if ($item->iasid == $id_iassign) {
          if (!$printed) {
            $printed = true;
            print '<i class="fa fa-th-large ml-4 mb-3 mt-2" aria-hidden="true"></i> ' . $item->iasname . "<br>\n";
            print '<table class="table table-hover ml-5 w-75" style="border-bottom: 1px solid #dee2e6;">' . "\n";
            }
          foreach ($activities_in_block as $id_activity) {
            $print = false;
            foreach ($messages as $subitem) {
              if ($subitem->stid == $id_activity) {
                if (!$print) {
                  $student_url = "view.php?id=$id&userid_iassign=$USER->id&action=view&iassign_current=$subitem->stid";
                  $link_student = "<a href='$student_url'>" . $subitem->stname . "</a>";
                  print '<tr><td style="white-space: nowrap; width: 1%; padding-right: 2rem;" class="align-middle">' . $link_student . '';
                  print '<table class="table table-hover ml-4 mb-4 w-75 mt-3" style="border-bottom: 1px solid #dee2e6;">';
                  $print = true;
                  }
                foreach ($messages as $comment_student) {
                  if ($comment_student->stid == $id_activity) {
                    $submission_url = "view.php?id=$id&userid_iassign=$comment_student->user&action=viewsubmission&iassign_current=$comment_student->stid";

                    $user = $DB->get_record('user', array('id' => $comment_student->user));

                    $label = $comment_student->total > 1 ? get_string('comment_unread', 'iassign') : get_string('comment_unread_one', 'iassign');
                    print '<tr><td style="white-space: nowrap; width: 1%; padding-right: 2rem;" class="align-middle"><i class="fa fa-user" aria-hidden="true"></i> ' . $user->firstname . ' ' . $user->lastname . ' </td>'
                      . '<td class="align-middle"> <a href="' . $submission_url . '#comments"><span class="badge bg-danger" style="color:white; font-size: .9em;"><i class="fa fa-envelope" aria-hidden="true"></i> '
                      . $label . ' <span class="badge rounded-pill bg-light text-dark" style="font-size: .8em;">'.$comment_student->total.'</span> </span></a></td>' . "</tr>\n";
                    }
                  }
                break;
                }
              }
            } // foreach ($activities_in_block as $id_activity)
          print '</table></td></tr></table>';
          break;
          } // if ($item->iasid == $id_iassign)
        } // foreach ($messages as $item)
      } // foreach ($iassign_ids as $id_iassign)

    print $OUTPUT->footer();
    } // function view_notifications_teachers()


  // Teachers' page with notifications (student messagens associated to each {iassign_statement})
  function view_notifications () {
    global $OUTPUT, $USER;

    print $OUTPUT->header();
    $this->print_main_menu();
    print '<h4 class="mt-4 ml-2"><i class="fa fa-comments-o" aria-hidden="true"></i> ' . get_string('new_messages', 'iassign') . "</h4>\n";
    if ($this->get_total_notifications() == 0) {
      print '<h6 class="mt-3 ml-5">' . get_string('empty_new_messages', 'iassign') . "</h6>\n";
      print '<hr class="my-4" />';
      print $OUTPUT->footer();
      return;
      }
    if (has_capability('mod/iassign:editiassign', $this->context, $USER->id)) {
      $this->view_notifications_teachers();
      return;
      }
    $messages = $this->get_iassign_statement_messages();
    print '<table class="table table-hover ml-4 mb-4 w-75 mt-3" style="border-bottom: 1px solid #dee2e6;">' . "\n";

    // 1. list all messages received in the current block:
    $actual_block = $this->iassign->id;
    $id = $this->cm->id;
    $total_include = 0;
    if ($messages)
      foreach ($messages as $item) {
        if ($item->iasid == $actual_block) {
          $student_url = "view.php?id=" . $id . "&userid_iassign=" . $USER->id . "&action=view&iassign_current=" . $item->stid . "#comments";
          $link_student = "<a href='$student_url'>" . $item->stname . "</a>";
          $label = $item->total > 1 ? get_string('comment_unread', 'iassign') : get_string('comment_unread_one', 'iassign');
          print '<tr><td style="white-space: nowrap; width: 1%; padding-right: 2rem;" class="align-middle">' . $link_student . '</td><td class="align-middle"> <a href="'.$student_url.'"><span class="badge bg-danger" style="color:white; font-size: .9em;"><i class="fa fa-envelope" aria-hidden="true"></i> ' . $label . ' <span class="badge rounded-pill bg-light text-dark" style="font-size: .8em;">'.$item->total.'</span> </span></a></td></tr>';
          $total_include++;
          }
        }
    print "</table>\n";
    if ($total_include == 0) {
      print '<h6 class="ml-5">'.get_string('empty_new_messages_block', 'iassign') . "</h6>\n";
      print '<hr class="my-4">';
      }
    if ($messages && $total_include == count($messages)) {
      print $OUTPUT->footer();
      return;
      }
    print '<h4 class="mt-4 mb-3 ml-2"><i class="fa fa-comments-o" aria-hidden="true"></i> '.get_string('new_messages_other_blocks', 'iassign').'</h4>';

    // 2. List all the messages received from another blocks:
    $iassign_ids = array();
    if ($messages) {
      foreach ($messages as $item)
        if (!in_array($item->iasid, $iassign_ids) && $item->iasid != $actual_block)
          array_push($iassign_ids, $item->iasid);
      }
    foreach ($iassign_ids as $id_iassign) {
      $printed = false;
      foreach ($messages as $item) {
        if ($item->iasid == $id_iassign) {
          if (!$printed) {
            $printed = true;
            print '<i class="fa fa-th-large ml-4 mb-3 mt-2" aria-hidden="true"></i> ' . $item->iasname . "<br>\n";
            print '<table class="table table-hover ml-5 w-75" style="border-bottom: 1px solid #dee2e6;">';
            }
          $student_url = "view.php?id=" . $id . "&userid_iassign=" . $USER->id . "&action=view&iassign_current=" . $item->stid . "#comments";
          $link_student = "<a href='$student_url'>" . $item->stname . "</a>";
          $label = $item->total > 1 ? get_string('comment_unread', 'iassign') : get_string('comment_unread_one', 'iassign');
          print '<tr><td style="white-space: nowrap; width: 1%; padding-right: 2rem;" class="align-middle">' . $link_student . '</td><td class="align-middle">' .
            '<a href="' . $student_url . '"><span class="badge bg-danger" style="color:white; font-size: .9em;"><i class="fa fa-envelope" aria-hidden="true"></i> ' . $label .
            ' <span class="badge rounded-pill bg-light text-dark" style="font-size: .8em;">' . $item->total . "</span></span></a> </td></tr>\n";
          }
        }
      print "</table>\n";
      }
    print $OUTPUT->footer();
    } // function view_notifications()


  // Print the first line with options to teachers' report (of one iAssign block {iassign}): report='Reports' | home='Home'
  // Options: activities_page ; help_ilm  ; report  ; results ; auto_evaluate_name_config ; notifications ; add_iassign
  //          Activities Page | About iLM | Reports | Results | Auto reevaluation         | Notifications | Add activity
  function print_main_menu () {
    global $USER;

    $id = $this->cm->id;
    $actual_action = optional_param('action', '', PARAM_TEXT);
   
    $url_help = new moodle_url('/mod/iassign/settings_ilm.php', array('action' => 'list', 'ilm_id' => 1));
    $url_report = new moodle_url('/mod/iassign/view.php', array('action' => 'report', 'id' => $id, 'iassignid' => $this->iassign->id));
    $url_add = new moodle_url('/mod/iassign/view.php', array('id' => $id, 'action' => 'add', 'iassignid' => $this->iassign->id));
    $url_stats = new moodle_url('/mod/iassign/view.php', array('id' => $id, 'action' => 'stats_student', 'iassignid' => $this->iassign->id));
    $url_home = new moodle_url('/mod/iassign/view.php', array('id' => $id));
    $url_notifications = new moodle_url('/mod/iassign/view.php', array('id' => $id, 'action' => 'notifications', 'iassignid' => $this->iassign->id));
    $url_auto_evaluate = new moodle_url('/mod/iassign/view.php', array('id' => $id, 'action' => 'auto_evaluate', 'iassignid' => $this->iassign->id));
    $url_sub_stats = new moodle_url('/mod/iassign/view.php', array('id' => $id, 'action' => 'stats', 'iassignid' => $this->iassign->id));
    $url_all_submissions = new moodle_url('/mod/iassign/view.php', array('id' => $id, 'action' => 'all_submissions', 'iassignid' => $this->iassign->id));
    $url_import_activity = new moodle_url('/mod/iassign/view.php', array('id' => $id, 'action' => 'import_activity', 'iassignid' => $this->iassign->id));

    print '<div class="btn-group" role="group" aria-label="">';

    print '<a href="' . $url_home . '" class="btn btn-outline-primary ' . ($actual_action == '' ? 'active' : '') . '">' . "\n";
    print '   <i class="fa fa-home" aria-hidden="true"></i> ' . get_string('activities_page', 'iassign') . "</a>\n";

    if (has_capability('mod/iassign:viewiassignall', $this->context, $USER->id)) {
      // could be "has_capability('mod/iassign:viewiassignall', $this->context, $USER->id)"
      // Has capability to see "report": teacher or up
      print '<a href="' . $url_help . '" class="btn btn-outline-primary"><i class="fa fa-question-circle" aria-hidden="true"></i> ' . get_string('help_ilm', 'iassign') . "</a>\n";
      print '<div class="btn-group"><div class="btn-group">'. "\n" .
        ' <a href="' . $url_report . '" class="btn btn-outline-primary '. ($actual_action == 'report' || $actual_action == 'stats' || $actual_action == 'all_submissions' ? 'active' : '') . ' dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' . "\n" .
        ' <i class="fa fa-bar-chart" aria-hidden="true"></i> ' . get_string('report', 'iassign') . "</a>\n" .
        ' <div class="dropdown-menu"> <a href="' . $url_report . '" class=" '. ($actual_action == 'report' ? 'active' : '') . ' dropdown-item"><i class="fa fa-bar-chart" aria-hidden="true"></i> ' .
        get_string('home', 'iassign') . '</a>' . "\n" .
        '  <div class="dropdown-divider"></div>' . "\n" .
        '    <a href="' . $url_sub_stats . '" class="dropdown-item ' . ($actual_action == 'stats' ? 'active' : '') . '"><i class="fa fa-pie-chart" aria-hidden="true"></i> ' . get_string('graphic', 'iassign') . "</a>\n" .
        '    <div class="dropdown-divider"></div>' . "\n" .
        '    <a href="' . $url_all_submissions . '" class="dropdown-item ' . ($actual_action == 'all_submissions' ? 'active' : '') . '"><i class="fa fa-files-o" aria-hidden="true"></i> ' . get_string('all_submissions_manager', 'iassign') .
        "    </a></div></div>\n";
      print '<a href="' . $url_stats . '" class="btn btn-outline-primary ' . ($actual_action == 'stats_student' ? 'active' : '') . '"><i class="fa fa-list" aria-hidden="true"></i> ' . get_string('results', 'iassign') . "</a>\n";
      print '<a href="' . $url_auto_evaluate . '" class="btn btn-outline-primary ' . ($actual_action == 'auto_evaluate' ? 'active' : '') . '"><i class="fa fa-check-square-o" aria-hidden="true"></i> ' . get_string('auto_evaluate_name_config', 'iassign') . "</a>\n";     
      }
   
    $notifications = $this->get_total_notifications();
    print '<a href="' . $url_notifications . '" class="btn btn-outline-primary ' . ($actual_action == 'notifications' ? 'active' : '') . '">' . "\n";
    print '   <i class="fa fa-bell" aria-hidden="true"></i> ' . get_string('notifications', 'iassign');
    if ($notifications > 0)
      print ' <span class="badge bg-danger rounded-pill" style="color: white; font-size: .9rem;">' . $notifications . "</span>\n";
    print '</a></div><div class="btn-group">';

    if (has_capability('mod/iassign:editiassign', $this->context, $USER->id)) {
      print '<a href="' . $url_add . '" class="btn btn-success"><i class="fa fa-plus" aria-hidden="true"></i> ' . get_string('add_iassign', 'iassign') . "</a>\n";
      print ' <button type="button" class="btn btn-success dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> '.
        ' </button> <div class="dropdown-menu"> <a class="dropdown-item" href="' . $url_import_activity . '">' . get_string('import_activity', 'iassign') .
        " </a></div>\n";
      } // if (has_capability('mod/iassign:editiassign', $this->context, $USER->id))

    print '</div></div>' . "\n";
    } // function print_main_menu()


  /// Display page of iAssign's block of activities
  function view_iassigns () {
    global $USER, $CFG, $COURSE, $DB, $OUTPUT;
    $id = $this->cm->id;

    $iassign_statement_list = $DB->get_records_list('iassign_statement', 'iassignid', array('iassignid' => $this->iassign->id), 'position ASC');

    $notice = optional_param('notice', '', PARAM_TEXT);
    if (strpos($notice, 'error'))
      print($OUTPUT->notification(get_string($notice, 'iassign'), 'notifyproblem'));
    else if ($notice != '')
      print($OUTPUT->notification(get_string($notice, 'iassign'), 'notifysuccess'));

    $this->print_main_menu(); // teachers' menu: Page of activities; About iLM; Report; Results; Reevaluation; Notifications

    print $OUTPUT->box_start();

    // Put the "Enable editing" or "Disable editing" button - if teacher on
    if (has_capability('mod/iassign:editiassign', $this->context, $USER->id)) {
      print '<div class="border float-right">' . "\n";
      if ($iassign_statement_list) {
        // $USER->iassignEdit == 0 view 'Turn editing off'
        // $USER->iassignEdit == 1 view 'Turn editing on'
        if (!isset($USER->iassignEdit)) // To be used by "./mod/iassign/ilm_handers/*.php"
          $USER->iassignEdit = 0;

        if ($USER->iassignEdit == 0) {
          $bottonEdit_message = get_string('turneditingon', 'iassign');
          $botton = 1;
          }
        elseif ($USER->iassignEdit == 1) {
          $bottonEdit_message = get_string('turneditingoff', 'iassign');
          $botton = 0;
          }
        $editPost = "" . $CFG->wwwroot . "/mod/iassign/view.php?id=" . $id . "&botton=" . $botton;
        print "\n<form name='formEditPost' id='formEditPost' method='post' action='" . $editPost . "' enctype='multipart/form-data'>\n";
        print " <input class='btn btn-primary' type=submit value='" . $bottonEdit_message . "'/>\n";
        print "</form>\n";
        } // if ($iassign_statement_list)
      print "</div>\n";
      }

    // if (has_capability('mod/iassign:submitiassign', $this->context, $USER->id))
    //   $this->view_legend_icons();

    print $OUTPUT->box_end();

    $iassign_array_exercise = array();
    $i_exercise = 0; // count total number of activities of type 'exercise' inside the block
    $iassign_array_test = array();
    $i_test = 0; // count total number of activities of type 'test' inside the block
    $iassign_array_example = array();
    $i_example = 0; // count total number of activities of type 'example' inside the block
    $iassign_array_general = array();
    $i_general = 0;

    if ($iassign_statement_list) { // This iAssign block has activities

      if ($this->iassign->activity_group == 0) {
        foreach ($iassign_statement_list as $one_iassign) {
          $iassign_array_general[$i_general] = $one_iassign;
          $i_general++;
          }
        } // if ($this->iassign->activity_group == 0)
      else {
        foreach ($iassign_statement_list as $one_iassign) {
          if ($one_iassign->type_iassign == 3) {
            $iassign_array_exercise[$i_exercise] = $one_iassign;
            $i_exercise++;
            } // if ($one_iassign->type_iassign == 3)
          if ($one_iassign->type_iassign == 2) {
            $iassign_array_test[$i_test] = $one_iassign;
            $i_test++;
            } // if ($one_iassign->type_iassign == 2)
          if ($one_iassign->type_iassign == 1) {
            $iassign_array_example[$i_example] = $one_iassign;
            $i_example++;
            } // if ($one_iassign->type_iassign == 1)
          }
        }

      // Now insert HTML with iAssign statements and their URL
      if ($iassign_array_exercise) {
        $title = get_string('exercise', 'iassign').(count($iassign_array_exercise)>1?'s':'');
        $this->show_iassign($title, $iassign_array_exercise, $i_exercise);
        }
      if ($iassign_array_test) {
        $title = get_string('test', 'iassign').(count($iassign_array_test)>1?'s':'');
        $this->show_iassign($title, $iassign_array_test, $i_test);
        }
      if ($iassign_array_example) {
        $title = get_string('example', 'iassign').(count($iassign_array_example)>1?'s':'');
        $this->show_iassign($title, $iassign_array_example, $i_example);
        }
      if ($iassign_array_general) {
        $title = "";
        $this->show_iassign($title, $iassign_array_general, $i_general);
        }
      } else { // if ($iassign_statement_list)
        print $OUTPUT->notification(get_string('no_activity', 'iassign'), 'notifysuccess');
        }

    if (count($iassign_statement_list) > 5 && !(has_capability('mod/iassign:submitiassign', $this->context, $USER->id))) {
      if (has_capability('mod/iassign:viewiassignall', $this->context, $USER->id)) {
        print $OUTPUT->box_start();
        print '<table width=100% border=0><tr>' . "\n";
        print '<td width=10% align="left">' . "\n";
        print $link_report;
        print '</td>' . "\n";
        print '</tr></table>' . "\n";
        print $OUTPUT->box_end();
        } // if (has_capability('mod/iassign:viewiassignall', $this->context, $USER->id))
      if (has_capability('mod/iassign:editiassign', $this->context, $USER->id)) {
        print $OUTPUT->box_start();
        print '<table width=100% border=0><tr>' . "\n";
        print '<td align="left">' . "\n";
        print $link_add;
        print '</td>' . "\n";
        print '</tr></table>' . "\n";
        print $OUTPUT->box_end();
        } // if (has_capability('mod/iassign:editiassign', $this->context, $USER->id))
      } // if (count($iassign_statement_list) > 5 && !(has_capability('mod/iassign:submitiassign', $this->context, $USER->id)))

      // if (has_capability('mod/iassign:submitiassign', $this->context, $USER->id))
      //   $this->view_legend_icons();
    } // function view_iassigns()


  /// Display all iAssigns: the list of activities inside the iAssign block
  //  @calledby this->view_iassigns(): $this->show_iassign($title, $iassign_array_exercise, $i_exercise);
  //  HTTP: ./mod/iassign/view.php?id= <$id>
  function show_iassign ($title, $iassign_array, $total_of_activities) {
    global $USER, $CFG, $DB, $OUTPUT, $PAGE;

    $id = $this->cm->id; // ID of iAssign block (accessed throgh table "couse_module")
    print $OUTPUT->box_start();

    print "    <script type='text/javascript'>
      //<![CDATA[
      function validate_move () {
      var radios = document.getElementsByName('iassign_destiny');
      var formValid = false;
      //alert('aqui validate_move()');
      var i = 0;
      while (!formValid && i < radios.length) {
        if (radios[i].checked) formValid = true;
          i++;
          }

        if (!formValid) {
          document.getElementById('move_dest').classList.add('alert-danger');
          }
        return formValid;
        }

      var modal;
      function load_move(id_ias) {
        document.form_move_activity.iassign_current.value = id_ias;
        modal.style.display = 'block';

        var radios = document.getElementsByName('iassign_destiny');

        var i = 0;
        while (i < radios.length) {
          radios[i].checked = false;
          i++;
          }

        document.getElementById('move_dest').classList.remove('alert-danger');
        }

      function closeModal () {
        modal.style.display = 'none';
        }

      function load_modal_elements () {
        modal = document.getElementById('myModal');
        var span = document.getElementById('close_modal');
        span.onclick = function() {
          modal.style.display = 'none';
          }
        window.onclick = function(event) {
          if (event.target == modal) {
            modal.style.display = 'none';
            }
          }
        }
      window.onload = load_modal_elements;
      //]]>
    </script>\n";
    print "    <div id='myModal' class='modal'>\n      <div class='modal-content'>\n        <span id='close_modal'>&times;</span>\n" .
          "        <div id='modal_title' class='moodle-dialogue-hd yui3-widget-hd'>" . get_string('move_activity', 'iassign') . "</div>\n" .
          "        <div style='padding: 10px; padding-left: 20px;' id='move_dest'>" . get_string('move_destination', 'iassign') . ":\n              <div style='margin: 5px;'>";

    $ccm = get_coursemodule_from_id('iassign', optional_param('id', 0, PARAM_INT));

    $radio_buttons = "  <form name='form_move_activity'><input type='hidden' name='action' value='move_activity' />
      <input type='hidden' name='id' value='" . optional_param('id', NULL, PARAM_TEXT) . "' />
      <input type='hidden' name='iassign_current' value='' />";

    $course_sections = get_fast_modinfo($PAGE->course->id)->get_section_info_all();

    $section = $course_sections[0];
    $ids_sections = array_keys($section->__get('modinfo')->get_sections());
    for ($k = 0; $k < count($ids_sections); $k++) {
      $radio_buttons .= get_section_name($PAGE->course->id, $ids_sections[$k]). "<ul style='list-style-type: none'>";
      for ($j = 0; $j < count($section->__get('modinfo')->get_sections()[$ids_sections[$k]]); $j++) {
        $c_cm = get_coursemodule_from_id('iassign', $section->__get('modinfo')->get_sections()[$ids_sections[$k]][$j]);
        if ($c_cm) {
          if ($ccm->instance == $c_cm->instance) {
            $radio_buttons .= "    <li><input type='radio' disabled='disabled' id='radio_" . $c_cm->instance . "' name='iassign_destiny' value='" . $c_cm->instance . "'>
      <label for='radio_" . $c_cm->instance . "'>" . $c_cm->name . "</label></li>\n";
            }
          else {
            $radio_buttons .= "    <li><input type='radio' id='radio_" . $c_cm->instance . "' name='iassign_destiny' value='" . $c_cm->instance . "'>
      <label class='input_move' for='radio_" . $c_cm->instance . "'>" . $c_cm->name . "</label></li>\n";
            }
          }
        }
      $radio_buttons .= "</ul>";
      }

    print $radio_buttons . "    <center><input type='submit' value='OK' onClick='return validate_move();' />\n  
      <input type='button' value='" . get_string('cancel', 'iassign') . "' onClick='closeModal()'  data-bs-dismiss='modal' /></center>
    </form>
    </div></div> </div></div>\n";
    print "    <style>.modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: scroll;
        background-color: rgb(0,0,0);
        background-color: rgba(0,0,0,0.4);
        }
      .modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 10px 10px;
        border: 1px solid #888;
        width: 50%;
        }
      #close_modal {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        margin-top: -15px;
        margin-right: -3px;
        }
      #close_modal:hover,
      #close_modal:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
        }
      #modal_title {
        min-height: 3rem;
        color: initial;
        background: initial;
        font-size: 1.5rem;
        line-height: 1.5;
        padding: 15px;
        border-bottom: 1px solid #e5e5e5;
        }
      .input_move {
        width: 90%;
        padding: 2px;
        }
      .input_move:hover {
        background: #eff0ef;
        }
      </style>";

    $action_links = "";

    if (has_capability('mod/iassign:viewiassignall', $this->context, $USER->id)) {
      print '<h5 class="fw-bold" style="font-weight: 400; margin: -1rem 0 1.5rem 0;">' . $title . "</h5>\n";
      print '<table class="table table-hover" style="border-bottom: 1px solid #dee2e6;">' . "\n";

      for ($j=0; $j<$total_of_activities; $j++) {
        $iassign_current = $iassign_array[$j]->id;

        // receiver=1 - message to teacher
        // receiver=2 - message to student
        $sum_comment = 0;
        $iassign_submissions = $DB->get_records('iassign_submission', array('iassign_statementid' => $iassign_current));
        foreach ($iassign_submissions as $iassign_submission) {
          $params_temp = array('iassign_submissionid' => $iassign_submission->id, 'return_status' => '0', 'receiver' => '1');
          $verify_message = $DB->get_record_sql("SELECT COUNT(iassign_submissionid) FROM {iassign_submission_comment} " .
            " WHERE iassign_submissionid = :iassign_submissionid AND return_status= :return_status AND receiver= :receiver", $params_temp);
          if ($verify_message)
            foreach ($verify_message as $tmp)
              $sum_comment += $tmp;
          } // foreach ($iassign_submissions as $iassign_submission)

        if ($sum_comment == 0)
          $comment_unread = "";
        else {
          $comment_unread_message = get_string('comment_unread', 'iassign');
          if ($sum_comment == 1)
            $comment_unread_message = get_string('comment_unread_one', 'iassign');
          $comment_unread = ' <a href="' . $CFG->wwwroot . '/mod/iassign/view.php?id=' . $id . '&action=report&iassignid=' . $this->iassign->id . '">' .
            ' <span class="badge bg-danger ml-2" style="color:white; font-size: .9em;"><i class="fa fa-envelope" aria-hidden="true"></i> ' . $comment_unread_message .
            ' <span class="badge rounded-pill bg-light text-dark" style="font-size: .8em;">' . $sum_comment . '</span> </span></a>' . "\n";
          }

        if ($j == $total_of_activities - 1)
          $iassign_down = $iassign_array[$j]->id;
        else
          $iassign_down = $iassign_array[$j + 1]->id;
        if ($j > 0)
          $iassign_up = $iassign_array[$j - 1]->id;
        else
          $iassign_up = $iassign_array[$j]->id;
        if ($iassign_array[$j]->visible == 0)
          $links = " <a href='view.php?id=" . $id . "&userid_iassign=" . $USER->id . "&action=view&iassign_current=" . $iassign_current . "'><font color='#bbbbbb'>" . $iassign_array[$j]->name . "</font></a>\n";
        else
          $links = " <a href='view.php?id=" . $id . "&userid_iassign=" . $USER->id . "&action=view&iassign_current=" . $iassign_current . "'>" . $iassign_array[$j]->name . "</a>\n";

        $links .= $comment_unread;
        if (has_capability('mod/iassign:editiassign', $this->context, $USER->id) && $USER->iassignEdit == 1) {
          $action_links = "<nav aria-label='Action links'><ul class='pagination pagination-sm mb-0 my-1 text-success'>\n";

          $str_aux = "&id=" . $id . "&iassign_current=" . $iassign_current . "&iassign_up=" . $iassign_up . "&iassign_down=" . $iassign_down;
          $link_up = "<li class='page-item'><a href='view.php?action=up" . $str_aux . "' class='page-link' title='" . get_string('move_up_iassign', 'iassign') . "'><i class='fa fa-arrow-up' aria-hidden='true'></i></a></li>\n";
          if ($j==0) // first activity
            $link_down = "<li class='page-item'><a href='view.php?action=firstdown" . $str_aux . "' class='page-link' title='" . get_string('move_down_iassign', 'iassign') . "'><i class='fa fa-arrow-down' aria-hidden='true'></i></a></li>\n";
          else
            $link_down = "<li class='page-item'><a href='view.php?action=down" . $str_aux . "' class='page-link' title='" . get_string('move_up_iassign', 'iassign') . "'><i class='fa fa-arrow-down' aria-hidden='true'></i></a></li>\n";
          $link_delete = "<li class='page-item'><a href='view.php?action=delete" . $str_aux . "' class='page-link' title='" . get_string('delete_iassign', 'iassign') . "'><i class='fa fa-trash-o' aria-hidden='true'></i></a></li>\n";
          $link_visible_hide = "<li class='page-item'><a href='view.php?action=visible" . $str_aux . "' class='page-link' title='" . get_string('hide_iassign', 'iassign') . "'><i class='fa fa-eye' aria-hidden='true'></i></a></li>\n";
          $link_visible_show = "<li class='page-item'><a href='view.php?action=visible" . $str_aux . "' class='page-link' title='" . get_string('show_iassign', 'iassign') . "'><i class='fa fa-eye-slash' aria-hidden='true'></i></a></li>\n";
          $link_edit = "<li class='page-item'><a href='view.php?action=edit" . $str_aux . "' class='page-link' title='" . get_string('edit_iassign', 'iassign') . "'><i class='fa fa-pencil-square-o' aria-hidden='true'></i></a></li>\n";
          $link_duplicate_activity = "<li class='page-item'><a href='view.php?action=duplicate_activity" . $str_aux . "' class='page-link' title='" . get_string('duplicate_iassign', 'iassign') . "'><i class='fa fa-clone' aria-hidden='true'></i></a></li>\n";
          $link_move_activity = "<li class='page-item'><a href='#' onclick='load_move(" . $iassign_current . "); return false;' class='page-link' title='" . get_string('move_activity', 'iassign') . "'><i class='fa fa-sign-out' aria-hidden='true'></i></a></li>\n";

          if (count($iassign_array) > 1) {
            if ($j == 0)
              $action_links .= $link_down;
            elseif ($j == $total_of_activities - 1)
              $action_links .= $link_up;
            else
              $action_links .= $link_up . $link_down;
            } // if (count($iassign_array) > 1)
          $action_links .= $link_edit . $link_delete;

          if ($iassign_array[$j]->visible == 0)
            $action_links .= $link_visible_show;
          else
            $action_links .= $link_visible_hide;

          $action_links .= $link_duplicate_activity . $link_move_activity;
          } // if ($USER->iassignEdit == 1 && has_capability('mod/iassign:editiassign', $this->context, $USER->id))

        $action_links .= "</ul></nav>\n";
        print '<tr><td style="white-space: nowrap; width: 1%; padding-right: 2rem;" class="align-middle">' . $links . '</td><td class="align-middle">' . $action_links . "</td></tr>\n";
        } // for ($j=0; $j<$total_of_activities; $j++)

      //D echo "locallib.php: show_iassign():<br/>";
      //EXTRA: This for is to correct error in ordem inside the block, to detect (and fix it) 'iassign_statement.position' errors
      $vector_final_order = array(); // get the final order to be used to correct existent one
      for ($j=0; $j<$total_of_activities; $j++) { // List all iAssign activity inside this block
        //D echo $j . ": id=" . $iassign_array[$j]->id . ", position=" . $iassign_array[$j]->position . "<br/>";
        if ($iassign_array[$j]->position!=($j+1)) {
          activity::move_change_activity_position($DB, $iassign_array[$j]->id, $iassign_array[$j]->id, $j+1, $j+1);
          }
        }

      print "</tbody></table>\n";
      } // if (has_capability('mod/iassign:viewiassignall', $this->context, $USER->id))
    elseif (has_capability('mod/iassign:submitiassign', $this->context, $USER->id)) { // student
      print '<h5 class="fw-bold" style="font-weight: 400; margin: -1rem 0 1.5rem 0;">' . $title . "</h5>\n";
      print '<table class="table table-hover" style="border-bottom: 1px solid #dee2e6;">' . "\n";

      for ($j=0; $j<$total_of_activities; $j++) {
        $icon_status = "";
        $icon_comment = "";
        if ($iassign_array[$j]->visible == 1) {
          $iassign_current = $iassign_array[$j]->id;
          $iassign_submission = $DB->get_record('iassign_submission', array('iassign_statementid' => $iassign_current, 'userid' => $USER->id));
          $links = " <a href='view.php?id=" . $id . "&userid_iassign=" . $USER->id . "&action=view&iassign_current=" . $iassign_current . "'>" . $iassign_array[$j]->name . "</a>\n";
          $icon_status = "";
          $icon_comment = "";
          if ($iassign_submission) {
            // receiver=1 - message to teacher
            // receiver=2 - message to student
            // $verify_message = $DB->get_record_sql("SELECT COUNT(iassign_submissionid) FROM {$CFG->prefix}ia_assign_submissions_comment WHERE iassign_submissionid = '$iassign_submission->id' and return_status= 0 and receiver=2");

            $params_temp = array('iassign_submissionid' => $iassign_submission->id, 'return_status' => '0', 'receiver' => '2');
            $verify_message = $DB->get_record_sql(
              "SELECT COUNT(iassign_submissionid) FROM {iassign_submission_comment} " .
              " WHERE iassign_submissionid = :iassign_submissionid AND return_status= :return_status AND receiver= :receiver", $params_temp);

            if ($verify_message)
              foreach ($verify_message as $tmp)
                $sum_comment = $tmp;

            if ($sum_comment > 0) {
              $comment_unread_message = get_string('comment_unread', 'iassign');
              if ($sum_comment == 1)
                $comment_unread_message = get_string('comment_unread_one', 'iassign');
              $icon_comment = "<a href='view.php?id=" . $id . "&userid_iassign=" . $USER->id . "&action=view&iassign_current=" . $iassign_current . "#comments'>\n" .
                ' <span class="badge bg-danger" style="color:white; font-size: .9em;"><i class="fa fa-envelope" aria-hidden="true"></i> ' . $comment_unread_message . "\n" .
                ' <span class="badge rounded-pill bg-light text-dark" style="font-size: .8em;">' . $sum_comment . "</span> </span></a>\n";
              }
            // $icon_comment = iassign_icons::insert('comment_unread');

            if ($iassign_array[$j]->type_iassign == 3) {
              if ($iassign_array[$j]->show_answer == 1) {
                if ($iassign_submission->status == 3)
                  $icon_status = '<i class="fa fa-check" style="color: green; font-size: 1.2rem;" aria-hidden="true" title="' . get_string('correct', 'iassign') . '"></i>' . "\n";
                elseif ($iassign_submission->status == 2)
                  $icon_status = '<i class="fa fa-times" style="color: red; font-size: 1.2rem;" aria-hidden="true" title="' . get_string('incorrect', 'iassign') . '"></i>' . "\n";
                elseif ($iassign_submission->status == 1)
                  $icon_status = '<i class="fa fa-question" style="color: #77229; font-size: 1.2rem;" aria-hidden="true" title="' . get_string('post', 'iassign') . '"></i>' . "\n";
                  //2021/12 $icon_status = iassign_icons::insert('post');
                elseif ($iassign_submission->status == 0)
                  $icon_status = '<i class="fa fa-question" style="color: red; font-size: 1.2rem;" aria-hidden="true" title="' . get_string('not_post', 'iassign') . '"></i>' . "\n";
                } // if ($iassign_array[$j]->show_answer==1)
              else {
                if ($iassign_submission->status == 0) {
                  //TODO: $icon_status = '<i class="fa fa-question" style="color: #77229c; font-size: 1.2rem;" aria-hidden="true" title="' . get_string('not_post', 'iassign') . '"></i>' . "\n";
                  $icon_status = iassign_icons::insert('not_post');
                  }
                else {
                  //TODO: $icon_status = '<i class="fa fa-question" style="color: #77229c; font-size: 1.2rem;" aria-hidden="true" title="' . get_string('post', 'iassign') . '"></i>' . "\n";
                  $icon_status = iassign_icons::insert('post');
                  }
                }
              } // if ($iassign_array[$j]->type_iassign == 3)
            } // if ($iassign_submission)
          elseif ($iassign_array[$j]->type_iassign == 3) {
            $icon_status = '<i class="fa fa-question" style="color: #77229c; font-size: 1.2rem;" aria-hidden="true" title="' . get_string('not_post', 'iassign') . '"></i>' . "\n";
            //2021/12 $icon_status = iassign_icons::insert('not_post');
            } // if ($iassign_array[$j]->type_iassign == 3)

          print '<tr><td style="white-space: nowrap; width: 1%; padding-right: 0; text-align: center;">' . $icon_status . '</td><td style="white-space: nowrap; width: 1%; padding-right: 2rem; padding-left: 0.2rem;">'
            . $links . '</td><td>' . $icon_comment . '</td></tr>' . "\n";
          //print '<p>' . $icon_status . ' ' . $links . ' ' . $icon_comment . '</p>' . "\n";
          } // if ($iassign_array[$j]->visible == 1)

        } // for ($j=0; $j<$total_of_activities; $j++)

      print "</table>\n";

      } // elseif (has_capability('mod/iassign:submitiassign', $this->context, $USER->id))
    else if (isguestuser()) {
      print($OUTPUT->notification(get_string('no_permission_iassign', 'iassign'), 'notifyproblem'));
      print '<table width=100% ><tr>' . "\n";
      print "<td width=70% align='left'><font color='#0000aa'><strong>" . $title . "</strong></font></td>" . "\n";
      print '</tr></table>' . "\n";

      for ($j=0; $j<$total_of_activities; $j++) {
        $icon_status = "";
        $icon_comment = "";
        if ($iassign_array[$j]->visible == 1) {
          $iassign_current = $iassign_array[$j]->id;
          $links = " <a href='view.php?id=" . $id . "&userid_iassign=" . $USER->id . "&action=view&iassign_current=" . $iassign_current . "'>" . $iassign_array[$j]->name . "</a>\n";
          print '<p>' . $links . '</p>' . "\n";
          } // if ($iassign_array[$j]->visible == 1)
        }
      }

    print $OUTPUT->box_end();
    } // function show_iassign($title, $iassign_array, $total_of_activities)


  /// Show message of return
  function return_home_course ($message) {
    //D global $DB, $OUTPUT;
    //D $link_return = " <a href='" . $this->return . "'>" . iassign_icons::insert('home') . get_string('activities_page', 'iassign') . "</a>";
    //D echo $OUTPUT->box_start();
    //D echo '<table width=100% border=0 valign="top">' . "\n";
    //D echo '<tr><td align="left"><strong>' . "\n";
    //D print_string($message, 'iassign');
    //D echo '</strong></td>' . "\n";
    //D echo '<td width=20% align="right">' . "\n";
    //D echo $link_return;
    //D echo '</td></tr></table>' . "\n";
    //D echo $OUTPUT->box_end();
    //D // echo $OUTPUT->footer();
    redirect(new moodle_url($this->return . '&notice=' . $message));
    exit;
    }


  /// Search comment of activity
  function search_comment_submission ($iassign_submissionid) {
    global $USER, $DB, $OUTPUT, $COURSE;
    // $context = context_course::instance($COURSE->id);
    $colorEdit1 = "#b7ceee"; // "#dce7ec"
    $colorEdit2 = "#d2e5fc";
    $has_capability = 0;

    $comments = $DB->get_records_list('iassign_submission_comment', 'iassign_submissionid', array('iassign_submissionid' => $iassign_submissionid), 'timecreated DESC'); // 'ORDER BY "timecreated" ASC'
    $text = "";
    if ($comments) {

      $even = 1;
      foreach ($comments as $tmp) {
        $user_data = $DB->get_record("user", array('id' => $tmp->comment_authorid));
        // if (has_capability('mod/iassign:editiassign', $context, $tmp->comment_authorid)) {
        //  $text .= "<tr><td bgcolor='#fee7ae'><b> $user_data->firstname</b> (" . userdate($tmp->timecreated) . ")</br>\n";
        //  $text .= $tmp->comment . "</td></tr>";
        //} else {
        //  $text .= "<tr><td bgcolor='#dce7ec'>&raquo;<b>" . $user_data->firstname . "</b> (" . userdate($tmp->timecreated) . "</br>\n";
        $avatar = new user_picture($user_data);
        $avatar->courseid = $COURSE->id;
        $avatar->link = true;
        $user_picture = $OUTPUT->render($avatar);
        $user_picture = str_replace('"35"', '"45"', $user_picture);

        if ($even) {
          $text .= "<tr><td style='vertical-align: middle; width: 55px !important;' bgcolor='" . $colorEdit1 . "'>$user_picture</td><td bgcolor='" . $colorEdit1 . "' style='padding-left: 0px !important;'><b style='font-size: 1.1rem;'>" . $user_data->firstname . "</b> <span style='color: #686969; font-size: 90%'><i class='fa fa-clock-o' aria-hidden='true' style='margin-left: 2rem; margin-right: .1rem;'></i> " . userdate($tmp->timecreated) . "</span></br>\n";
          $even = 0;
        } else {
          $text .= "<tr><td style='vertical-align: middle; width: 55px !important;' bgcolor='" . $colorEdit2 . "'>$user_picture</td><td bgcolor='" . $colorEdit2 . "' style='padding-left: 0px !important;'><b style='font-size: 1.1rem;'>" . $user_data->firstname . "</b> <span style='color: #686969; font-size: 90%'><i class='fa fa-clock-o' aria-hidden='true' style='margin-left: 2rem; margin-right: .1rem;'></i> " . userdate($tmp->timecreated) . "</span></br>\n";
          $even = 1;
          }
        $text .= "<div style='padding-left: 0rem; padding-top: .2rem;'>" . $tmp->comment . "</div></td></tr>\n";

        } // foreach ($comments as $tmp)
      }
    return $text;
    }


  /// Update comment of activity
  function update_comment ($iassign_submissionid) {
    global $USER, $DB, $OUTPUT;
    if (!has_capability('mod/iassign:submitiassign', $this->context, $USER->id) || is_siteadmin())
      $receiver = 1; // student message to teacher
    else
      $receiver = 2; // teacher message to student

    $verify_message = $DB->get_records('iassign_submission_comment', array('iassign_submissionid' => $iassign_submissionid)); //

    if ($verify_message) {
      foreach ($verify_message as $message) {
        if ($message->receiver == $receiver) {
            $newentry = new stdClass();
            $newentry->id = $message->id;
            $newentry->return_status = 1;
            if (!$DB->update_record('iassign_submission_comment', $newentry)) {
                print_error('error_update', 'iassign');
              } // if (!$DB->update_record('iassign_submission_comment', $newentry))
          } // if ($message->receiver == $receiver)
        // Trigger module viewed event.
        $event = \mod_iassign\event\submission_comment_updated::create(array(
          'objectid' => $this->iassign->id,
          'context' => $this->context
          ));
        $event->add_record_snapshot('course', $this->course);
        $event->trigger();
        } // foreach ($verify_message as $message)
      }
    }


  /// Record comment of activity
  function write_comment_submission () {
    global $USER, $CFG, $DB;
    $id = $this->cm->id;
    $submission_comment = optional_param('submission_comment', NULL, PARAM_TEXT);
    $row = optional_param('row', 0, PARAM_INT);
    $column = optional_param('column', 0, PARAM_INT);

    $sum_comment = 0;

    $return = "" . $CFG->wwwroot . "/mod/iassign/view.php?action=viewsubmission&id=" . $id . "&iassign_submission_current=" . $this->iassign_submission_current . "&userid_iassign=" . $this->userid_iassign . "&iassign_current=" . $this->activity->get_activity()->id . "&row=" . ($row) . "&column=" . ($column);

    $link_return = " <a href='" . $return . "'>" . iassign_icons::insert('return_home') . get_string('return', 'iassign') . "</a>";

    $str1 = trim($submission_comment);
    $str2 = trim(get_string('box_comment_message', 'iassign'));

    if (!empty($submission_comment) && (strcmp($str1, $str2) != 0)) { // there is comment and it is different from "previous"
      if (has_capability('mod/iassign:submitiassign', $this->context, $USER->id) && !is_siteadmin()) { //MOOC '&& !is_siteadmin()'
        $receiver = 1; // student message to teacher
        $this->action = 'view';
        }
      else {
        $receiver = 2; // teacher message to student
        $this->action = 'viewsubmission';
        }

      //MOOC 2016: foi p/ 15 linhas acima:
      $iassign_submission = $DB->get_record("iassign_submission", array("id" => $this->iassign_submission_current));

      // Look if the student has previou submission and inform him
      if (!$iassign_submission) {
        $iassign_submission = $DB->get_record("iassign_submission", array("iassign_statementid" => $this->activity->get_activity()->id, "userid" => $USER->id));
        }

      if (!$iassign_submission) { // the student has none submission to this activity, creates an empty one
        $iassign_statement_activity_item = $DB->get_record("iassign_statement", array("id" => $this->activity->get_activity()->id));
        $id_submission = $this->empty_submission($iassign_statement_activity_item->id, $this->userid_iassign, $receiver);
        $this->iassign_submission_current = $id_submission;
        }
      else {
        $id_submission = $iassign_submission->id;
        } // if (!$iassign_submission)
      // $comments = $DB->get_record_sql("SELECT COUNT(iassign_submissionid) FROM {$CFG->prefix}ia_assign_submissions_comment
      // WHERE iassign_submissionid = '$id_submission' and comment='$submission_comment' and comment_authorid='$USER->id'"); //
      // Attention: this Moodle function 'get_record_sql' makes a replace in ':comment'
      $params_temp = array("iassign_submissionid" => $id_submission, "comment" => $submission_comment, "comment_authorid" => $USER->id);
      $comments = $DB->get_record_sql(
        "SELECT COUNT(iassign_submissionid) FROM {iassign_submission_comment} " .
        " WHERE iassign_submissionid = :iassign_submissionid AND comment= :comment AND comment_authorid= :comment_authorid", $params_temp);

      if ($comments)
        foreach ($comments as $tmp)
          $sum_comment = $tmp;

      if ($sum_comment == 0) {
        $newentry = new stdClass();
        $newentry->iassign_submissionid = $id_submission;
        $newentry->comment_authorid = $USER->id;
        $newentry->timecreated = time();
        $newentry->comment = $submission_comment;
        $newentry->receiver = $receiver;
        $ia_assign_submissions_comment_id = $DB->insert_record('iassign_submission_comment', $newentry);

        // Trigger module viewed event
        $event = \mod_iassign\event\submission_comment_created::create(array(
          'objectid' => $this->iassign->id,
          'context' => $this->context
          ));
        $event->add_record_snapshot('course', $this->course);
        $event->trigger();
        }
      } // if (!empty($submission_comment) && (strcmp($str1, $str2) != 0))
    // if ($this->action=='viewsubmission') {
    // echo $OUTPUT->header();
    // $this->return_last('confirm_add_comment', $link_return);
    // die;
    // }

    return true;
    } // function write_comment_submission()


  /// Writes one artificial submission, to be associated with {iassign_submission_comment}
  //  since {iassign_submission_comment}.iassign_submissionid = {iassign_submission}.id
  //  A real register to {iassign_submission} is performed by 'this->get_answer()'
  //  @calledby class iassign : function write_comment_submission(): $id_submission = $this->empty_submission($iassign_statement_activity_item->id, $this->userid_iassign, $receiver);
  function empty_submission ($iassignid, $id_user, $receiver) {
    global $USER, $DB, $OUTPUT;
    $newentry = new stdClass();
    $newentry->iassign_statementid = $iassignid;
    $newentry->userid = $id_user;
    $newentry->timecreated = time();
    $newentry->timemodified = time();
    $newentry->answer = 0; // student only submited a message
    if ($receiver == 2) // teacher message to student (write id teacher)
      $newentry->teacher = $USER->id;

    if (!$newentry->id = $DB->insert_record("iassign_submission", $newentry))
      return_home_course('error_insert_submissions');
    else {
      // Trigger module view event
      $event = \mod_iassign\event\submission_created::create(array(
        'objectid' => $this->iassign->id,
        'context' => $this->context
        ));
      $event->add_record_snapshot('course', $this->course);
      $event->trigger();
      }
    return $newentry->id;
    }


  /// Return to a specific address of page
  function return_last ($message, $link_return) {
    global $DB, $OUTPUT;
    print $OUTPUT->box_start();
    print '<table width=100% border=0 valign="top">' . "\n";
    print '<tr><td align="left"><strong>' . "\n";
    print_string($message, 'iassign');
    print '</strong></td>' . "\n";
    print '<td width=20% align="right">' . "\n";
    print $link_return;
    print '</td></tr></table>' . "\n";
    print $OUTPUT->box_end();
    print $OUTPUT->footer();
    die();
    } // function return_last($message, $link_return)

  } // class iassign


/// Class for manage activities
class activity {

  var $activity;

  /// Constructor of class.
  //  @param int $id Id of activity
  //  3.1 update PHP 7.0 compatibility for all moodle versions
  //  public function activity($id) { self::__construct($id); }

  function __construct ($id) {
    global $DB;
    $this->activity = $DB->get_record("iassign_statement", array("id" => $id));
    if (empty($this->activity))
      $this->activity = null;
    }

  /// Get an activity
  //  @return NULL
  function get_activity () {
    if ($this->activity != null)
      return $this->activity;
    else
      return null;
    }


  /// Delete interactive activities
  function delete ($return) {
    global $USER, $CFG, $DB, $OUTPUT;

    $iassign_submission_currents = $DB->get_records("iassign_submission", array("iassign_statementid" => $this->activity->id));

    print $OUTPUT->header();

    $output .= $OUTPUT->box_start();
    $output .= "<p>" . get_string('delete_activity', 'iassign') . " <strong>" . $this->activity->name . "</strong></p>";
    if ($iassign_submission_currents) {
      $output .= "<p>" . get_string('number_submissions', 'iassign') . " <strong>" . count($iassign_submission_currents) . "</strong></p>";
      if (!has_capability('mod/iassign:deleteiassignnotnull', $USER->context, $USER->id)) {
        $output .= $OUTPUT->heading(get_string('delete_activity_permission_adm', 'iassign'));
        $output .= $OUTPUT->single_button($return, get_string('return', 'iassign'), 'get');
        $output .= $OUTPUT->box_end();
        $output .= $OUTPUT->footer();
        print $output;
        die();
        } // if (!has_capability('mod/iassign:deleteiassignnotnull', $this->context, $USER->id))
      }   // if ($iassign_submission_currents)
    else
      $output .= "<p>" . get_string('not_submissions_activity', 'iassign') . "</p>\n";
    $output .= '<table width=50% border=0>';
    $output .= '<tr valign="top"><td>';
    $output .= "<p>" . get_string('what_do', 'iassign') . "</p>\n";
    $output .= '</td><td>';

    $bottonDelete_yes = get_string('delete_iassign', 'iassign');
    $deleteiassignyes = $CFG->wwwroot . "/mod/iassign/view.php?id=" . $USER->cm . "&action=deleteyes&iassign_current=" . $this->activity->id;
    $output .= "<form name='formDelete' id='formDelete' method='post' action='" . $deleteiassignyes . "' enctype='multipart/form-data'>\n";
    $output .= " <input type=submit value='" . $bottonDelete_yes . "'/>\n";
    $output .= "</form>\n";
    $output .= '</td><td>';
    $bottonDelete_no = get_string('delete_cancel', 'iassign');
    $deleteiassignno = $CFG->wwwroot . "/mod/iassign/view.php?id=" . $USER->cm . "&action=deleteno&iassign_current=" . $this->activity->id;
    $output .= "<form name='formDelete' id='formDelete' method='post' action='" . $deleteiassignno . "' enctype='multipart/form-data'>\n";
    $output .= " <p><input type=submit value='" . $bottonDelete_no . "'/></p>\n";
    $output .= "</form>\n";
    $output .= '</td></tr></table>' . "\n";
    $output .= $OUTPUT->box_end();
    $output .= $OUTPUT->footer();
    print $output;
    } //  function delete($return)


  /// Function for confirm the delete of activity
  //  @param String $return Url of return
  //  @param Object $iassign Object content an activity
  function deleteyes ($return, $iassign) {
    global $USER, $CFG, $DB, $OUTPUT, $COURSE;
    $msg = '';

    if (!empty($this->activity->id)) {
      $iassign_submission_currents = $DB->get_records("iassign_submission", array("iassign_statementid" => $this->activity->id));
      if ($iassign_submission_currents) {
        if (has_capability('mod/iassign:deleteassignnull', $USER->context, $USER->id)) {
          foreach ($iassign_submission_currents as $iassign_submission)
            $DB->delete_records('iassign_submission_comment', array('iassign_submissionid' => $iassign_submission->id));
          $delete_iassign_submission_currents = $DB->delete_records("iassign_submission ", array("iassign_statementid" => $this->activity->id));
          } // if ($iassign_submission_currents)
        }

      //$delete_iassign_statement_config = $DB->delete_records('iassign_statement_config', array('iassign_statementid' => $this->activity->id)); //MOOC 2016

      // Remove all files associated to this activity:
      $fs = get_file_storage();
      //TODO 2022: nao deveria ser "$this->activity->file" no lugar de "$this->activity->id"?
      $fs->delete_area_files($iassign->context->id, 'mod_iassign', 'exercise', $this->activity->id);

      $this->delete_calendar($this->activity->id);
      $delete_iassign_current = $DB->delete_records('iassign_statement', array('id' => $this->activity->id));
      iassign::update_grade_iassign($this->activity->iassignid);

      if ($delete_iassign_current) {
        //x $iassign->return_home_course('confirm_del_iassign');
        $iassign->return_home_course(get_string('confirm_del_iassign', 'iassign')); // Activity successfully removed.
        //$msg = get_string('confirm_del_iassign', 'iassign');
        }
      else {
        //x $iassign->return_home_course('error_confirm_del_iassign');
        $iassign->return_home_course(get_string('error_confirm_del_iassign', 'iassign'));
        //$msg = get_string('error_confirm_del_iassign', 'iassign');
        }
      // if (($this->action == 'deleteyes') && (has_capability('mod/iassign:deleteassignnull', $this->context, $USER->id)))
      }
    } // function deleteyes($return, $iassign)


  /// Change fields 'iassign_statement.position' between 2 activities
  //  @calledby : class this->move_iassign($move_action, $target, $return)
  //  @calledby : class iassign show_iassign($title, $iassign_array, $total_of_activities) - this is used only to fix 'position' erros (e.g. {1,2,2,3} to {1,2,3,4})
  static function move_change_activity_position ($DB, $idFrom, $idTo, $posFrom, $posTo) { //1
    $newentry = new stdClass(); // define a new object (activity) to receive only 2 fields: 'id' and 'position'
    $newentry->id = $idFrom; // ID of the "source activity" //1 $this->activity->id -> $idFrom
    $newentry->position = $posFrom; // final position to source //1 $final_position_source -> $posFrom
    if (!$DB->update_record('iassign_statement', $newentry)) { // modify 'position' of the activity with 'id'=$target
      print_error('error_update_move_iassign', 'iassign');
      }
    // If $idFrom == $idTo then came here from 'this->show_iassign(.)' above, to correct order!
    if ($idFrom != $idTo) { // if $idFrom != $idTo then must update also the targe position
      $newentry->id = $idTo; //1 $iassign_target->id -> $idTo
      $newentry->position = $posTo; //1 $final_position_target -> $posTo
      if (!$DB->update_record('iassign_statement', $newentry))
        print_error('error_update_move_iassign', 'iassign');
      }
    }


  /// Changes position of activities (between those inside a group of interactive activities)
  //  @param $move_action = must be "up" or "down"
  //  @param $target = if 'down' => is 'iassign_statement.id' of the activity bellow the current one; if 'up' => is activity above the current one
  function move_iassign ($move_action, $target, $return) {
    global $DB, $OUTPUT;
    $initial_position_source = $this->activity->position; // the position of the "source activity" (that one to be moved)
    $iassign_target = $DB->get_record("iassign_statement", array("id" => $target)); // "target activity" = the activity in the position the receive "source activity"
    if ($move_action=="firstdown") {
      $final_position_source = 2;
      $final_position_target = 1;
      }
    else
    if ($move_action=="down") {
      $final_position_target = $this->activity->position;
      $final_position_source = $final_position_target + 1;
      }
    else
    if ($move_action=="up") {
      $final_position_target = $this->activity->position;
      $final_position_source = $final_position_target - 1;
      }
    else { // error:
      print_error('error_update_move_iassign', 'iassign');
      exit;
      }

    activity::move_change_activity_position($DB, $this->activity->id, $iassign_target->id, $final_position_source, $final_position_target); // efectively exchange 'position'

    redirect($return);
    } // function move_iassign($move_action, $target, $return)


  /// Enable or disable the display of interactive activities
  function visible_iassign ($return) {
    global $DB;
    $newentry = new stdClass();
    $newentry->id = $this->activity->id;
    $newentry->visible = $this->activity->visible == 0 ? 1 : 0;
    if (!$DB->update_record('iassign_statement', $newentry))
      print_error('error_update_visible_iassign', 'iassign');
    redirect($return);
    }


  /// Add new interactive activities (register in table 'iassign_statement')
  //  After 'add_edit_iassign()' built form (using 'iassign_form.php'), 'new_iassign(.)'
  //  is used to process new activity (register im {iassign_statement} and in {files}.
  //  The file content arrives here through form, temporaly stored in MoodleData "filedir"
  //  @calledby this->add_edit_iassign() : $iassignid = $this->activity->new_iassign($result);
  //  @see      ilm_manager.php: $ilm_manager_instance->ilm_editor_new();
  function new_iassign ($param) {
    global $DB;
    //D echo "locallib.php:new_iassign(): param->file=" . $param->file . "<br/>";

    $newentry = new stdClass();
    $newentry->iassignid = $param->iassignid;
    $newentry->name = $param->name;
    $newentry->type_iassign = $param->type_iassign;
    $newentry->store_all_submissions = $param->store_all_submissions;

    // $newentry->proposition = $param->proposition; // 2019/02/13 - With the use of 'editor' in 'mform', field 'proposition' is Array ( [text] => <DESCRIPTION> [format] => 1 )
    $proposition = $param->proposition;
    if (is_array($proposition)) $proposition_text = $proposition['text'];
    else $proposition_text = $proposition;
    $newentry->proposition = $proposition_text; // Notice: Trying to get property of non-object

    //TODO I used in 'iassign_form.php' '<select name='iassign_ilmid'...>' with 'optgroup' by hand! But MoodleForm clear/do not register the 'iassign_ilmid'
    //TODO Then get it directly from the form data $_POST!
    //TODO See: 'iassign_form.php' field 'iassign_ilmid' and here (above) 'function add_edit_iassign()'
    if (!isset($param->iassign_ilmid) && isset($_POST['iassign_ilmid']))
      $param->iassign_ilmid = $_POST['iassign_ilmid'];

    $newentry->author_name = $param->author_name;
    $newentry->author_modified_name = $param->author_modified_name;
    $newentry->iassign_ilmid = $param->iassign_ilmid;

    // $param->file is the {files}.id: it came from the attached file (not from {iassign_statement}.file)
    $newentry->file = $param->file;       // came from a file from 'get_file_storage()' (it is its ID)
    $newentry->filesid = $param->file;    // came from a file from 'get_file_storage()' (it is its ID)

    //TODO criar o campo 'iassign_statement.filesid' e usar: $newentry->filesid = $param->file; // register 'files.id' here in 'iassign_statement.filesid'

    $time_now = time();
    $newentry->grade = $param->grade;
    $newentry->timemodified = $time_now;
    $newentry->timecreated = $time_now;
    if ($param->type_iassign == 1) {
      $newentry->timedue = 0;
      $newentry->timeavailable = 0;
      }
    else {
      $newentry->timedue = $param->timedue;
      $newentry->timeavailable = $param->timeavailable;
      }
    $newentry->preventlate = $param->preventlate;
    $newentry->test = $param->test;
    $newentry->special_param1 = $param->special_param1;
    $newentry->visible = $param->visible;
    $newentry->position = $param->position;
    $newentry->max_experiment = $param->max_experiment;
    $newentry->dependency = $param->dependency;
    $newentry->automatic_evaluate = $param->automatic_evaluate;
    $newentry->show_answer = $param->show_answer;

    // ./lib/dml/moodle_database.php: public abstract function insert_record($table, $dataobject, $returnid=true, $bulk=false)
    // ./lib/dml/mysqli_native_moodle_database.php: public function insert_record($table, $dataobject, $returnid=true, $bulk=false): return $this->insert_record_raw($table, $cleaned, $returnid, $bulk);
    $new_iassign_statement_id = $DB->insert_record("iassign_statement", $newentry); // get the new {iassign_statement}.id

    if ($new_iassign_statement_id) { // now, let's create the reference in {files} to the file in MoodleData
      $component = 'mod_iassign';
      $filearea = 'exercise'; // the associated {files} has field 'filearea="activity"'
      //HISTORY: old version of iAssing (at least since 2012), here was used {files}.filearea = 'activity'

      // Remember: $param->file is the {files}.id (of a temporary file in MoodleData) selected to this activity
      $fs = get_file_storage(); // Get reference to all files in Moodle data
      $new_file_fs = $fs->get_file_by_id($param->file); // remember: $param->file is the {files}.id selected to this activity
      // Attention: $fs->get_file_by_id(.) return is different of $fs->get_area_files(.) that return "./lib/filestorage/file_storage.php" object

      // ATTENTION: {files}.itemid must be equal to {iassign_statement}.id and {iassign_statement}.filesid = {files}.id
      $itemid = $new_iassign_statement_id; // remember: {files}.itemid = {iassign_statement}.id

      //D //TODO Verificar: melhor deixar "$itemid = $new_file_fs->get_itemid()" pois assim 'files.itemid' guarda 'iassign_statement.id'???
      //D echo "new_iassign_statement_id=" . $new_iassign_statement_id . "<br/>\n";
      //D //echo "new_file_fs: id=" . $new_file_fs->get_id() . ", filename=" . $new_file_fs->get_filename() . ", contextid=" . $new_file_fs->get_contextid() .
      //D //", filearea=" . $new_file_fs->get_filearea() . ", itemid=" . $new_file_fs->get_itemid() . ", pathnamehash=" . $new_file_fs->get_pathnamehash() . "<br/>";
      //D echo "new_file_fs: "; print_r($new_file_fs); echo "<br/>";
      //D echo "contextid=" . $param->context->id . ", component=" . $component . ", filearea=" . $filearea . ", itemid=" . $itemid . "<br/>";
      //D //exit;

      // Actual file already inserted in other context (and with filearea='activity') with the iAssign file selector!
      // Make a copy of 'new_file_fs' now with {iassign_statement} context_id and with filearea='exercise'
      // ./lib/filestorage/file_storage.php: class file_storage: create_file_from_storedfile(.): always create 2 entries in {files} (one to directory with filename=".")
      //TODO Verificar se apenas esta' inserindo nova referencia em 'files' (NAO deve duplicar no Moodle Data)
      $newfile_fs = $fs->create_file_from_storedfile(array('contextid' => $param->context->id, 'component' => $component, 'filearea' => $filearea, 'itemid' => $itemid), $new_file_fs);
      //D echo "newfile_fs: id=" . $newfile_fs->get_id() . ", filearea=" . $newfile_fs->get_filearea() . ", itemid=" . $newfile_fs->get_itemid() . "<br/>\n";
      //D //exit;

      $updateentry = new stdClass();
      $updateentry->id = $new_iassign_statement_id;
      $updateentry->filesid = $newfile_fs->get_id(); // {iassign_statement}.filesid must have {files}.id
      if (!$DB->update_record("iassign_statement", $updateentry))
        print_error('error_add', 'iassign');

      if ($param->type_iassign == 3)
        iassign::update_grade_iassign($param->iassignid);

      //TODO iLM_HTML5 :: //MOOC2014
      //D  $iassign_ilm_configs = $DB->get_records('iassign_ilm_config', array('iassign_ilmid' => $param->iassign_ilmid, 'visible' => '1'));
      //D  if ($iassign_ilm_configs) {
      //D  foreach ($iassign_ilm_configs as $iassign_ilm_config) {
      //D  if ($iassign_ilm_config->param_type != 'static') {
      //D  $newentry = new stdClass();
      //D  $newentry->iassign_statementid = $new_iassign_statement_id;
      //D  $newentry->iassign_ilm_configid = $iassign_ilm_config->id;
      //D  $newentry->param_name = $iassign_ilm_config->param_name;
      //D  $newentry->param_value =(is_array($param->{'param_'.$iassign_ilm_config->id}) ? implode(",", $param->{'param_'.$iassign_ilm_config->id}) : $param->{'param_'.$iassign_ilm_config->id});
      //D  if (!$DB->insert_record("iassign_statement_config", $newentry))
      //D  print_error('error_add_param', 'iassign');
      //D  }
      //D  }
      //D  }
      // log event --------------------------------------------------------------------------------------
      iassign_log::add_log('add_iassign_exercise', 'name: ' . $param->name, $new_iassign_statement_id, $param->iassign_ilmid);
      // log event --------------------------------------------------------------------------------------

      return $new_iassign_statement_id;
      }
    else
      print_error('error_add', 'iassign');
    } // function new_iassign($param)


  /// Add the calendar entries for this iassign
  //  @param int $coursemoduleid - Required to pass this in because it might not exist in the database yet
  //  @return bool
  static function add_calendar ($iassignid) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/calendar/lib.php');

    $iassign_statement_activity_item = $DB->get_record("iassign_statement", array("id" => $iassignid));
    $iassign = $DB->get_record("iassign", array("id" => $iassign_statement_activity_item->iassignid));

    $event = new stdClass();
    $event->name = $iassign->name . ' - ' . $iassign_statement_activity_item->name;
    $event->description = $iassign_statement_activity_item->name;
    $event->courseid = $iassign->course;
    $event->groupid = 0;
    $event->userid = 0;
    $event->modulename = 'iassign';
    $event->instance = $iassign->id;
    $event->eventtype = 'due';
    $event->timestart = $iassign_statement_activity_item->timeavailable;
    $event->timeduration = ($iassign_statement_activity_item->timedue - $iassign_statement_activity_item->timeavailable);
    calendar_event::create($event);
    } // static function add_calendar($iassignid)


  /// Update the calendar entries for this iassign
  //  @param int $coursemoduleid - Required to pass this in because it might not exist in the database yet
  //  @return bool
  function update_calendar ($iassignid, $olddescription) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/calendar/lib.php');

    $iassign_statement_activity_item = $DB->get_record("iassign_statement", array("id" => $iassignid));
    $iassign = $DB->get_record("iassign", array("id" => $iassign_statement_activity_item->iassignid));

    $event = new stdClass();
    $event->id = 0;
    $events = $DB->get_records('event', array('modulename' => 'iassign', 'instance' => $iassign->id));
    if ($events) {
      foreach ($events as $value) {
        if ($value->description == $olddescription) {
          $event->id = $value->id;
          }
        }
      }
    if ($event->id != 0) {
      $event->name = $iassign->name . ' - ' . $iassign_statement_activity_item->name;
      $event->description = $iassign_statement_activity_item->name;
      $event->timestart = $iassign_statement_activity_item->timeavailable;
      $event->timeduration = ($iassign_statement_activity_item->timedue - $iassign_statement_activity_item->timeavailable);

      $calendarevent = calendar_event::load($event->id);
      $calendarevent->update($event);
    } else
      $this->add_calendar($iassignid);
    }


  /// Update the calendar entries for this iassign
  //  @param int $coursemoduleid - Required to pass this in because it might not exist in the database yet
  //  @return bool
  function delete_calendar ($iassignid) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/calendar/lib.php');

    $iassign_statement_activity_item = $DB->get_record("iassign_statement", array("id" => $iassignid));
    $iassign = $DB->get_record("iassign", array("id" => $iassign_statement_activity_item->iassignid));
    $events = $DB->get_records('event', array('modulename' => 'iassign', 'instance' => $iassign->id));
    if ($events) {
      foreach ($events as $value) {
        if ($value->description == $iassign_statement_activity_item->name) {
          $DB->delete_records('event', array('id' => $value->id));
          }
        }
      }
    }


  /// Update interactive activities
  //  Reaches this point after teacher "clicks" the "update button" (sending all of his fields update)
  //  Here is registered update in {iassign_statement} and {files}
  //  @calledby this->activity->add_edit_iassign(): $iassignid = $this->activity->update_iassign($result);
  function update_iassign ($param) {
    global $DB, $USER;
    $component = 'mod_iassign';
    $filearea = 'exercise';
    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $one_file_fs = $fs->get_file_by_id($param->file); // important: $param->file has the {files}.id selected
    $one_file_fsold = $fs->get_file_by_id($param->fileold);

    //DEBUG //REMOVER
if (!isset($param->fileid)) $param->fileid = NULL;
$aux_fs_id = "[erro]";   $aux_fs_itemid = "[erro]"; // function update_iassign($param)
$aux_fs_filename = "[erro]";
$aux_fs_old_id = "[erro]"; 
$aux_fs_old_itemid = "[erro]";
$aux_fs_old_Filename = "[erro]";
try {
  if ($one_file_fs) {
    if (!empty($one_file_fs))                    $aux_fs_id = $one_file_fs->get_id();
    if (!empty($one_file_fs))                    $aux_fs_itemid = $one_file_fs->get_itemid();
    if (!empty($one_file_fs))                    $aux_fs_filename = $one_file_fs->get_filename();
    if (!empty($one_file_fsold))                 $aux_fs_old_id = $one_file_fsold->get_id();
    if (!empty($one_file_fsold))                 $aux_fs_old_itemid = $one_file_fsold->get_itemid();
    if (!empty($one_file_fsold))                 $aux_fs_old_Filename = $one_file_fsold->get_filename();
    }
} catch (Exception $e) {
  echo 'Caught exception: ',  $e->getMessage(), "\n";
  }
if ($USER->id==3) { //D REMOVER
echo "x locallib.php:update_iassign(.): param->id=" . $param->id, ", param->file=" . $param->file . ", param->filesid=" . $param->fileid . ", param->fileold=" . $param->fileold . "<br/>";
echo "x one_file_fs : id=" . $aux_fs_id . ", itemid=" . $aux_fs_itemid . ", filename=" . $aux_fs_filename . "<br/>";
echo "x one_file_fsold : id=" . $aux_fs_old_id . ", itemid=" . $aux_fs_old_itemid . ", filename=" . $aux_fs_old_Filename . "<br/>";
} //D REMOVER
    //D echo "-------------------------------------------------------------------------------<br/>"; //D //exit;

    if ($param->file != $param->fileold) { // changed the content file: $param->file is the new {files}.id and $param->fileold is the old {files}.id
      if ($one_file_fsold) {
        // Remove old file content in {files}: remove its 2 references in {files} (beside the {files}.id, the other associated with {files}.filename='.'
        $fileoldarea = $fs->get_area_files($one_file_fsold->get_contextid(), $one_file_fsold->get_component(), $one_file_fsold->get_filearea(), $one_file_fsold->get_itemid());
        foreach ($fileoldarea as $one_file_from_fs) {
if ($USER->id==3) echo " (a) delete(): one_file_from_fs : id=" . $one_file_from_fs->get_id() . ", itemid=" . $one_file_from_fs->get_itemid() . ", filename=" . $one_file_from_fs->get_filename() . "<br/>";
          $one_file_from_fs->delete(); // remove old file from {files} (but keep real content in MoodleData)
          }
        }

      // To get the file to be associated with {iassign_statement}.id = $param->iassign_id
      // $newfile_onefile = NULL; 
      $newfile_onefile_final = NULL; // to get {files} with actual file content (not the directory {files}.filename='.')

      $itemid = $param->iassign_id; // {files}.itemid = {iassign_statement}.id

      // If the file was send with $one_file_fs->get_itemid()==0, better use '$param->id'
      // ./lib/filestorage/stored_file.php: public function file_exists($contextid, $component, $filearea, $itemid, $filepath, $filename)
      // if (!$fs->file_exists($param->context->id, $component, $filearea, $one_file_fs->get_itemid(), $one_file_fs->get_filepath(), $one_file_fs->get_filename()))
      // if (!$fs->file_exists($param->context->id, $component, $filearea, $param->id, $one_file_fs->get_filepath(), $one_file_fs->get_filename()))
      if (!$fs->file_exists($param->context->id, $component, $filearea, $itemid, $one_file_fs->get_filepath(), $one_file_fs->get_filename())) {
        // The file is new in {files} (perhaps it is not new in Moodle Data)
        if ($USER->id==3) { //D REMOVER
echo "x USER==3: entrou em !fs->file_exists: filearea=" . $filearea . ", get_itemid=" . $aux_fs_itemid . ", get_filepath=" . $one_file_fs->get_filepath() . "<br/>";
echo "x  * Acabou de remover 2 referencias velhas para {files}, agora deve criar 2 novas<br/>";
echo "x one_file_fs->get_id()=" . $aux_fs_id . ", one_file_fs->get_itemid()=" . $aux_fs_itemid . ", one_file_fs->get_filename()=" . $aux_fs_filename . "<br/>";
echo "x param->file=" . $param->file . ", param->fileold=" . $param->fileold . "<br/>";
echo "x itemid=" . $itemid . " <- param->iassign_id=" . $param->iassign_id . ", param->id=" . $param->id . "<br/>";
echo "x new_iassign_statement_id = " . $new_iassign_statement_id . "<br/>";
//exit;
} //D
//exit; //REMOVER exit

        //D  $itemid = $one_file_fs->get_itemid() + $param->iassign_id; //REVER!!!! $param->iassign_id tem o valor do {iassign_statement}.id
        //2022/09/27 $itemid = $param->id; // {files}.itemid = {iassign_statement}.id
        //D moved up: $itemid = $param->iassign_id; // {files}.itemid = {iassign_statement}.id

        // ./lib/filestorage/file_storage.php : "public function create_file_from_storedfile($filerecord, $fileorid)" 1051/2412 on Moodle 2018120302.08
        $newfile_fs = $fs->create_file_from_storedfile(array('contextid' => $param->context->id, 'component' => $component, 'filearea' => $filearea, 'itemid' => $itemid), $one_file_fs);

// Exception - sizeof(): Argument #1 ($value) must be of type Countable|array, stored_file given
// Exception - sizeof(): Argument #1 ($value) must be of type Countable|array, stored_file given
if ($USER->id==3)
  echo "x USER.id=3: Criado novo arquivo? com contextid=".$param->context->id.", itemid=".$itemid." => #newfile_fs=" .
       ((is_null($newfile_fs) || !is_array($newfile_fs)) ? 0 : sizeof($newfile_fs)) . "<br/>";

        if (is_array($newfile_fs)) {
          // Aditional security: "./lib/filestorage/stored_file.php : create_file_from_storedfile(.)" return only one file
          foreach ($newfile_fs as $newfile_onefile) { // trocar as referencias {files}.itemid por {iassign_statement}.id
            //if ($newfile_onefile->get_filename()!=".") { break; } }
            if ($newfile_onefile->get_itemid()!= $param->iassign_id) {
if ($USER->id==3) { echo "x Entrou na troca de temp_file.itemid=" . $newfile_onefile->get_itemid() . " <- " . $param->iassign_id . "<br/>"; } //D
echo "x Cria FS em foreach: temp_file.itemid=" . $newfile_onefile->get_itemid() . ", get_filepath=" . $newfile_onefile->get_filepath() . "<br/>";
echo "x temp_file->get_id()=" . $newfile_onefile->get_id() . ", temp_file->get_itemid()=" . $newfile_onefile->get_itemid() . ", temp_file->get_filename()=" . $newfile_onefile->get_filename() . "<br/>";
              // Change the {files}.itemid to the current {iassign_statement}.id
              require_once 'files_functions.php';
              $error = update_files($DB, $newfile_onefile->get_id(), $param->iassign_id);
              }
            if ($newfile_onefile->get_filename()!='.')
              $newfile_onefile_final = $newfile_onefile; // file with the content
            } // foreach
          } // if (is_array($newfile_fs)) 
        else {
          // Since "./lib/filestorage/stored_file.php : create_file_from_storedfile(.)" return only one file use this "else"
if ($USER->id==3) echo "x USER.id=3: Cria FS fora de laco! newfile_onefile_final: id=". $newfile_fs->get_id() . ", filename=" . $newfile_fs->get_filename() . "<br/>";
          $newfile_onefile_final = $newfile_fs;
          }

if ($USER->id==3) { //D REMOVER!
 if ($newfile_onefile_final==NULL) {
  echo "x  xxxx Erro: newfile_onefile_final == NULL<br/>Remove {files}: "; //D
  if (is_array($newfile_fs)) { //D
    foreach ($newfile_fs as $newfile_onefile) { echo "x id=" . $newfile_onefile->get_id() . ", itemid=" . $newfile_onefile->get_itemid() . ", "; $newfile_onefile->delete(); }
    } //D
  else { echo "x Remove apenas 'newfile_fs': id=" . $newfile_fs->get_id() . "<br/>"; $newfile_fs->delete(); } //D
  } //D
 }

        //D $param->file = $newfile_fs->get_itemid();
        $param->file = $newfile_onefile_final->get_id(); // prepare to update {iassign_statement}.filesid = {files}.id
        $param->filesid = $param->file;
if ($USER->id==3) { //D REMOVER!
 echo "x  * atualiza 1 {iassign_statement}.file <- " . $param->file . "<br/>\n";
 echo "x  * atualiza 1 {iassign_statement}.filesid <- " . $param->filesid . "<br/>\n";
 }
        }
      else { // if (!$fs->file_exists($param->context->id, $component, $filearea, $param->id, $one_file_fs->get_filepath(), $one_file_fs->get_filename()))
        // There exist one {files} in this context, with this name, is there an ERROR?
        // Try to recover with this previous {files}
if ($USER->id==3) { //D
 echo "x locallib.php:update_iassign(.): *** USER==3: entrou em fs->file_exists: provavel ERRO de colisao de arquivos!<br/>";
 echo "x filearea=" . $filearea . ", param->iassign_id=" . $param->iassign_id . "";
 }
        $newfile_fs = $fs->get_area_files($param->context->id, $component, $filearea, $param->id);
        foreach ($newfile_fs as $temp_file) { // trocar as referencias {files}.itemid por {iassign_statement}.id
          //if ($temp_file->get_filename()!=".") { break; } }
if ($USER->id==3) { //REMOVER
 echo "x get_itemid=" . $aux_fs_itemid . ", get_filepath=" . $one_file_fs->get_filepath() . "<br/>";
 echo "x one_file_fs->get_id()=" . $one_file_fs->get_id() . ", one_file_fs->get_itemid()=" . $aux_fs_itemid . ", one_file_fs->get_filename()=" . $aux_fs_filename . "<br/>";
 // echo "x param="; print_r($param); echo "x <br/>param->name=" . $param->name . "<br/>";
 echo "x param->id=" . $param->id . ", param->name=" . $param->name . "<br/>";
 echo "x param->file=" . $param->file . ", param->fileold=" . $param->fileold . "<br/>";
 echo "x itemid=" . $param->id . "<br/>";
 echo "x newfile_fs->get_id=" . $temp_file->get_id() . "<br/>";
 echo "x newfile_fs->get_itemid=" . $temp_file->get_itemid() . "<br/>";
 echo "x newfile_fs->get_filename=" . $temp_file->get_filename() . "<br/>";
 } //REMOVER
          if ($temp_file->get_itemid() != $param->iassign_id) {
if ($USER->id==3) { echo "x Entrou na troca de temp_file.itemid=" . $temp_file->get_itemid() . " <- " . $param->iassign_id . "<br/>"; } //D
            // Change the {files}.itemid to the current {iassign_statement}.id
            require_once 'files_functions.php';
            $error = update_files($DB, $temp_file->get_id(), $param->iassign_id);
            }
          if ($temp_file->get_filename()!='.')
            $newfile_onefile_finel = $temp_file; // file with the content
          }
        } // else if (!$fs->file_exists($param->context->id, $component, $filearea, $param->id, $one_file_fs->get_filepath(), $one_file_fs->get_filename()))
      //exit; //REMOVER exit

      if ($newfile_onefile_final) { // additional security...
        // $param->file = $newfile_fs->get_id();
        $param->file = $newfile_onefile_final->get_id(); // prepare to update {iassign_statement}.filesid = {files}.id
        $param->filesid = $param->file;
if ($USER->id==3) {
 echo "x  * atualiza 2 {iassign_statement}.file <- " . $param->file . "<br/>\n";
 echo "x  * atualiza 2 {iassign_statement}.filesid <- " . $param->filesid . "<br/>\n";
 }
        }
      else {
        print("update_iassign: error, 'newfile_onefile_final' empty!<br/>"); //TODO Remove this, use 'thrown'
        // print_error('error_security', 'iassign'); // ./lib/setuplib.php: moodle_exception thrown
        }

      }
    else {
      // one_file_fs=stored_file Object ( [fs:stored_file:private] => file_storage Object ( [tempdir:file_storage:private] => /var/data/moodle_saw_404p/temp/filestorage [filesystem:file_storage:private] => file_system_filedir Object ( [filedir:protected] => /var/data/moodle_saw_404p/filedir [trashdir:protected] => /var/data/moodle_saw_404p/trashdir [dirpermissions:protected] => 511 [filepermissions:protected] => 438 ) ) [file_record:stored_file:private] => stdClass Object ( [id] => 8 [contenthash] => 50d23783ca40251d070872e90b7e2674beb5e231 [pathnamehash] => 535b60f04cef0dda8bb157e7a0ada4955de40500 [contextid] => 22 [component] => mod_iassign [filearea] => exercise [itemid] => 1 [filepath] => / [filename] => ex1_1_ler_int_imprimir.ivph [userid] => 3 [filesize] => 1686 [mimetype] => text/plain [status] => 0 [source] => [author] => Leo leo [license] => allrightsreserved [timecreated] => 1663646432 [timemodified] => 1663646432 [sortorder] => 0 [referencefileid] => [repositoryid] => [reference] => [referencelastsync] => ) [repository:stored_file:private] => [filesystem:stored_file:private] => file_system_filedir Object ( [filedir:protected] => /var/data/moodle_saw_404p/filedir [trashdir:protected] => /var/data/moodle_saw_404p/trashdir [dirpermissions:protected] => 511 [filepermissions:protected] => 438 ) )
      $param->file = $one_file_fs->get_id();
      $param->filesid = $param->file;
if ($USER->id==3) { // - only {user}.id==3 sees it
 echo "x  * atualiza 3 {iassign_statement}.file <- " . $param->file . "<br/>\n";
 echo "x  * atualiza 3 {iassign_statement}.filesid <- " . $param->filesid . "<br/>\n";
 }
      }

    $newentry = new stdClass();
    $newentry->id = $param->iassign_id;
    $newentry->name = $param->name;
    $newentry->type_iassign = $param->type_iassign;
    $newentry->store_all_submissions = $param->store_all_submissions;

    //2019 $newentry->proposition = $param->proposition;
    $proposition = $param->proposition;
    if (is_array($proposition))
      $proposition_text = $proposition['text'];
    else
      $proposition_text = $proposition;
    $newentry->proposition = $proposition_text;
    $newentry->iassign_ilmid = $param->iassign_ilmid;
    $newentry->file = $param->file; // old field of {iassign_statement} to stablish relation with {files}.itemid (now {iassign_statement}.filesid={files}.id
    if (!$param->filesid) // additional security: $param->filesid is missing, but it must have the {files}.id
      $newentry->filesid = $param->file;    
    else
      $newentry->filesid = $param->filesid; // already defined
    $newentry->grade = $param->grade;
    if (isset($param->author_name)) // 2023: every time edit activity change old author "firstname&nbsp;lastname" to "firstname lastname ({user}.id)"
      $newentry->author_name = $param->author_name;
    $newentry->author_modified_name = $param->author_modified_name;
    $newentry->timemodified = time();
    if ($param->type_iassign == 1) {
      $newentry->timedue = 0;
      $newentry->timeavailable = 0;
      } // if ($param->type_iassign == 1)
    else {
      $newentry->timedue = $param->timedue;
      $newentry->timeavailable = $param->timeavailable;
      }
    $newentry->preventlate = $param->preventlate;
    $newentry->test = $param->test;
    if (isset($param->special_param1))
      $newentry->special_param1 = $param->special_param1;
    else
      $newentry->special_param1 = NULL;
    $newentry->visible = $param->visible;
    $newentry->max_experiment = $param->max_experiment;
    $newentry->dependency = $param->dependency;
    $newentry->automatic_evaluate = $param->automatic_evaluate;
    $newentry->show_answer = $param->show_answer;

    if (!$DB->update_record("iassign_statement", $newentry))
      print_error('error_update', 'iassign');

    if ($param->type_iassign == 3) {
      iassign::update_grade_iassign($param->iassignid);
      }

    //$id = $newentry->id; // MOOC 2016 --- inicio
    //$iassign_activity_item_configs = $DB->get_records('iassign_statement_config', array('iassign_statementid' => $newentry->id));
    //if ($iassign_activity_item_configs) {
    //foreach ($iassign_activity_item_configs as $iassign_activity_item_config) {
    //  $newentry = new stdClass();
    //   $newentry->id = $iassign_activity_item_config->id;
    //   $newentry->param_value =(is_array($param->{'param_'.$iassign_activity_item_config->iassign_ilm_configid}) ? implode(",",
    //   $newentry->param_value =(is_array($param->{'param_' . $iassign_activity_item_config->iassign_ilm_configid}) ? implode(",",
    //             $param->{'param_' . $iassign_activity_item_config->iassign_ilm_configid}) : $param->{'param_' . $iassign_activity_item_config->iassign_ilm_configid});
    //   if (!$DB->update_record("iassign_statement_config", $newentry))
    //     print_error('error_edit_param', 'iassign');
    //  }
    // } // MOOC 2016 --- final
    // log event --------------------------------------------------------------------------------------
    iassign_log::add_log('update_iassign_exercise', 'name: ' . $param->name, $param->iassign_id, $param->iassign_ilmid);
    // log event --------------------------------------------------------------------------------------
    // remove old file from {files} (and from MoodleData?)
    // remove old file from {files} (but keep real content in MoodleData)

//D REMOVER
// - only {user}.id==3 sees it
if ($USER->id==3) echo "x  * Acabou de atualizar {iassign_statement}.id=" . $newentry->id . "=" . $param->iassign_id . " com {ia}.filesid=" . $newentry->filesid . " e {files}.itemid=" . $newentry->id . " (espero)<br/>";
// xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

    return $newentry->id; // to be used by this->add_edit_iassign() to update calendar
    // if ($param->type_iassign==3)
    // $this->update_grade_iassign($param->iassignid);
    } // function update_iassign($param)


  /// Show information of activity
  function show_info_iassign () {
    global $DB, $OUTPUT;
    $output = '';

    if ($this->activity->type_iassign == 3) {
      if ($this->activity->dependency == 0) {
        $output .= '<p style="margin-top: -1rem;"><strong>' . get_string('independent_activity', 'iassign') . '</strong>' . "\n";
        }
      else {
        $dependencys = explode(';', $this->activity->dependency);
        $output .= '<p style="margin-top: -1rem;"><strong>' . get_string('dependency', 'iassign') . '</strong>';
        foreach ($dependencys as $dependency) {
            $dependencyiassign = $DB->get_record("iassign_statement", array("id" => $dependency));
            if ($dependencyiassign)
                $output .= '<p>' . $dependencyiassign->name . '</p>' . "\n";
          } // foreach ($dependencys as $dependency)
        } // if ($iassign_statement_activity_item->dependency == 0)
      if ($this->activity->max_experiment == 0)
        $output .= '<strong style="margin-left: 3rem;">' . get_string('experiment', 'iassign') . '</strong> ' . get_string('ilimit', 'iassign');
      else
        $output .= '<strong style="margin-left: 3rem;">' . get_string('experiment_iassign', 'iassign') . '</strong> ' . $this->activity->max_experiment . "\n";
      $output .= '<strong style="margin-left: 3rem;">' . get_string('grade_iassign', 'iassign') . ':</strong> ' . $this->activity->grade . '</p>' . "\n";
      } // if ($iassign_statement_activity_item->type_iassign == 3)

    $output .= $this->toggle_columns_script();

    $output .= '<p><strong>' . get_string('proposition', 'iassign') . ':</strong></p> <div class="proposition">' . $this->activity->proposition . '</div>' . "\n";
   
    print $OUTPUT->box($output);
    } // function show_info_iassign()

  /// Provide script to toggle columns
  static function toggle_columns_script () {
    $output = "";
    $output .= '<script>
    function toLeft () {
      document.getElementsByClassName("proposition")[0].style.cssFloat = "left";
      document.getElementsByClassName("proposition")[0].style.width = "40%";
      document.getElementById("fullscreen").style.marginTop = "-3rem";
      document.getElementById("columnsbutton").style.color = "#1da075";
      }
    function toDefault () {
      document.getElementsByClassName("proposition")[0].style.cssFloat = "none";
      document.getElementsByClassName("proposition")[0].style.width = "100%";
      document.getElementById("fullscreen").style.marginTop = "0";
      document.getElementById("columnsbutton").style.color = "#343a40";
      }
    var activate = false;
    function toggleColumns () {
      if (activate) {
        activate = false;
        toDefault();
      } else {
        activate = true;
        toLeft();
        }
      }
    </script>'; // '

    $output .= '<span tabindex="0" data-toggle="tooltip" title="' . get_string('enable_disable_columns', 'iassign')
      . '" style="float: right"> <i id="columnsbutton" onclick="toggleColumns()" class="fa fa-columns" style="font-size: 1.5rem;" aria-hidden="true" role="button"></i></span>' . "\n";

    return $output;
    } // function write_comment_submission()


  static function get_latest_array_item ($DB, $iassignid, $position) { //TODO: may be search for the greatest grade?
    $iassign_statement = $DB->get_records('iassign_statement', array('iassignid' => $iassignid, 'position' => $position));
    $item = NULL;
    foreach ($iassign_statement as $item) ; // get the last item!
    if ($item) return $item; // return the last one
    return NULL;
    }


  /// Shows date of opening and closing activities
  function view_dates () {
    global $USER, $CFG, $DB, $OUTPUT;

    $return = $CFG->wwwroot . "/mod/iassign/view.php?id=" . $USER->cm;
    $link_return = " <a href='" . $return . "'>" . iassign_icons::insert('home') . get_string('activities_page', 'iassign') . "</a>";
    $status_iassign = "";
    $status_iassign1 = "";
    $status_iassign2 = "";
    if ($this->activity->type_iassign == 1) // activity of type example
      $type_iassign = get_string('example_iassign', 'iassign');
    elseif ($this->activity->type_iassign == 2) { // activity of type test
      $type_iassign = get_string('test_iassign', 'iassign');
      if ($this->activity->timeavailable > time()) {
        $status_iassign = get_string('previous_timeavailable', 'iassign');
        } elseif ($this->activity->timedue < time()) {
        $status_iassign = get_string('last_timedue', 'iassign');
        }
    } elseif ($this->activity->type_iassign == 3) { // activity of type exercise
      $type_iassign = get_string('exercise_iassign', 'iassign');
      if ($this->activity->timeavailable > time()) {
        $status_iassign = get_string('previous_timeavailable', 'iassign'); // before of deadline
      } elseif ($this->activity->timedue < time()) { // after delivery
        $status_iassign = get_string('last_timedue', 'iassign');
        if ($this->activity->preventlate == 1) // permitted to submit after the deadline
          $status_iassign1 = get_string('duedate_preventlate_enable', 'iassign');
        elseif ($this->activity->preventlate == 0) { // not permitted to submit after the deadline
          $status_iassign1 = get_string('duedate_preventlate_desable', 'iassign');
          if ($this->activity->test == 1) // allowed to test after of deadline
            $status_iassign2 = get_string('test_preventlate', 'iassign');
          elseif ($this->activity->test == 0) { // not allowed to test after of deadline
            $status_iassign2 = get_string('test_preventlate_no', 'iassign');
            } // elseif ($iassign_statement_activity_item->test == 0)
          } // elseif ($iassign_statement_activity_item->preventlate == 0)
        } // elseif ($iassign_statement_activity_item->timedue < time())
      } // elseif ($iassign_statement_activity_item->type_iassign == 3)

    $output = '<table  width=100% >' . "\n";
    $output .= '<tr><td colspan=2><h4>' . $this->activity->name . '</h4></td></tr>' . "\n";
    $output .= '<tr>' . "\n";

    //leo testes para passar por cima com 'has_capability('mod/iassign:...', $this->context, $USER->id)
    $output .= '<td width=60%>' . $type_iassign . '</td>' . "\n";
    //leo $output .= '<td width=80%>' . $type_iassign;
    // $output .= $auxStr . " - status_assign=$status_iassign - this->activity->type_iassign=" . $this->activity->type_iassign. "<br/>"; // Period ended.
    // $output .= '</td>' . "\n";

    if (has_capability('mod/iassign:viewiassignall', $USER->context, $USER->id) && ($this->activity->type_iassign == 3)) {
      // Link (with icon) to report survey (to present all interactivy exercises inside this block)
      $link_report = "<a href='" . $CFG->wwwroot . "/mod/iassign/view.php?id=" . $USER->cm . "&action=report&iassignid=" . $this->activity->iassign_ilmid . "'>" . iassign_icons::insert('view_report') . ' ' . get_string('report', 'iassign') . "</a>";
      $output .= '<td width=40% align="right">' . ' ' . $link_report . '</td>' . "\n";
      }
    else {
      // Get all previous/next solution sent by this student (present link to his last answer)

      // $iassign_previous = $DB->get_record('iassign_statement', array('iassignid' => $this->activity->iassignid, 'position' => $this->activity->position - 1));
      $iassign_previous = activity::get_latest_array_item($DB, $this->activity->iassignid, $this->activity->position-1); // previous activity of this student inside this block

      // $iassign_next = $DB->get_record('iassign_statement', array('iassignid' => $this->activity->iassignid, 'position' => $this->activity->position + 1));
      $iassign_next = activity::get_latest_array_item($DB, $this->activity->iassignid, $this->activity->position+1); // next activity of this student inside this block

      $link_next = "";
      $link_previous = "";
      if ($iassign_previous) { // previous_activity
        $url_previous = "view.php?id=" . $USER->cm . "&userid_iassign=" . $USER->id . "&action=view&iassign_current=" . $iassign_previous->id;
        $link_previous = "<a href='" . $url_previous . "'>" . (iassign_icons::insert('previous_student_activity')) . "</a>\n";
        }
      if ($iassign_next) { // next_activity
        $url_next = "view.php?id=" . $USER->cm . "&userid_iassign=" . $USER->id . "&action=view&iassign_current=" . $iassign_next->id;
        $link_next = "<a href='" . $url_next . "'>" . (iassign_icons::insert('next_student_activity')) . "</a>\n";
        }

      $output .= '<td width=40% align="right">' . $link_previous . ' &nbsp; ' . $link_return . ' &nbsp; ' . $link_next . '</td>' . "\n";
      } // if (has_capability('mod/iassign:viewiassignall', $this->context, $USER->id) && ($iassign_statement_activity_item->type_iassign == 3))
    $output .= '</tr></table>' . "\n";
    $output .= '<table  width=100% >' . "\n";
    if ($this->activity->type_iassign > 1) {
      if ($this->activity->timeavailable)
        $output .= '<tr><td width=50% align="left"> <strong>' . get_string('availabledate', 'iassign') . ':</strong> ' . userdate($this->activity->timeavailable) . '</td>' . "\n";
      if ($this->activity->timedue)
        $output .= '<td width=50% align="left"><strong>' . get_string('duedate', 'iassign') . ':</strong> ' . userdate($this->activity->timedue) . '</td>' . "\n";
      } // if ($iassign_statement_activity_item->type_iassign > 1)
    if ($status_iassign != "" && $status_iassign1 != "" && $status_iassign2 != "")
      $output .= '<tr><td><font color="red">' . $status_iassign . ' ' . $status_iassign1 . ' ' . $status_iassign2 . '</font></td></tr>' . "\n";

    $output .= '</table>' . "\n";

    print $OUTPUT->box($output);
    } // function view_dates()

  } // class activity


/// Class to manage Interactive Learning Module (iLM)
class ilm {

  var $ilm;

  /// Constructor of class
  //  @param int $id Id of iLM
  //D  3.1 update PHP 7.0 compatibility for all moodle versions
  //D public function ilm($id) { self::__construct($iassign, $cm, $course); }
  function __construct ($id) {
    global $DB;
    $this->ilm = $DB->get_record("iassign_ilm", array("id" => $id));
    if (empty($this->ilm))
      $this->ilm = null;
    }


  /// Shows activity in iLM ($iassign_statement_activity_item{author_name, iassign_ilmid, file, ...})
  //  @calledby this->view_iassign_current() from 7 points, all calling ./ilm_handlers/html5.php:show_activity_in_ilm(.)
  //  @calledby view_iassign_current() : print $OUTPUT->box($ilm->view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, true));
  //  @calledby view_iassign_current() : print $ilm->view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, false);
  //  @calledby view_iassign_current() : print $OUTPUT->box($ilm->view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, $loadTeacherActivity));
  //  @calledby view_iassign_current() : $output .= $ilm->view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, true);
  //  @calledby view_iassign_current() : $output .= $ilm->view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, true); 
  //  @calledby view_iassign_current() : $output .= $ilm->view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, $loadTeacherActivity);
  //  @calledby view_iassign_current() : $output = $ilm->view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, true);
  function view_iLM ($iassign_statement_activity_item, $student_answer, $enderecoPOST, $view_teacherfileversion) {
    global $USER, $CFG, $DB;

    $iassign_ilm = $DB->get_record('iassign_ilm', array('id' => $iassign_statement_activity_item->iassign_ilmid));
    // $iassign_ilm = $DB->get_records_sql("SELECT * FROM {iassign_ilm} WHERE id=" . $iassign_statement_activity_item->iassign_ilmid);

    if (!$iassign_ilm) {
      // Currently, the backup store the original iLM in table {iassign_ilm} what could result error in restored Moodle.
      // In backup/restore, if the iLM does not exist in the restored Moodle, it is impossible to present the activity.
      // The teacher could to associate the activity to another compatible iLM (other version).
      print "The iLM (with id indicated) was not found! (" . $iassign_statement_activity_item->iassign_ilmid . ")<br/>\n";
      print "Perhaps you could edit this activity associating it to another compatible iLM<br/>\n";
      return "";
      }

    // Get the iLM type (Java or HTML) to provide a correct caller
    $typec = strtolower($iassign_ilm->type);
    require_once 'ilm_handlers/' . $typec . '.php';

    // ilm_handlers/html5.php (or ilm_handlers/java.php)
    $retorno = $typec::show_activity_in_ilm($iassign_statement_activity_item, $student_answer, $enderecoPOST, $view_teacherfileversion);

    return $retorno;
    } // function view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, $view_teacherfileversion)


  /// Function to give a single access to an iLM content avoi (after used, 'view()', after 'view_iLM(...)', will erase the entry)
  //  @calledby view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, $view_teacherfileversion) : $id_iLM_security=$this->write_iLM_security($iassign_statement_activity_item->id,$content_or_id_from_ilm_security);
  //  @param int $iassign_statement_activity_itemid Id of iassign statement
  //  @param Object $file File in use in activity
  //  @return int Return the id of log
  function write_iLM_security ($iassign_activity_itemid, $content_or_id_from_ilm_security) {
    global $CFG, $USER, $COURSE, $DB, $OUTPUT;
    $newentry = new stdClass();
    $newentry->iassign_statementid = $iassign_activity_itemid;
    $newentry->userid = $USER->id;
    $newentry->filesid = $content_or_id_from_ilm_security;
    $newentry->timecreated = time();
    $newentry->view = 1;
    $id_iLM_security = $DB->insert_record("iassign_security", $newentry);
    if (!$id_iLM_security) {
      print_error('error_security', 'iassign'); // ./lib/setuplib.php: moodle_exception thrown
      } // from (!$DB->insert_record("iassign_security", $newentry))

    return $id_iLM_security;
    }

  /// Function to avoid that erros in remotion of entries in table 'iassign_security' allow future access to this contents
  //  @calledby view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, $view_teacherfileversion) : $this->remove_old_iLM_security_entries($USER->id, $iassign_statement_activity_item->id);
  //  @param int $userid
  //  @param int $iassign_activity_itemid Id of iassign statement
  function remove_old_iLM_security_entries ($userid, $iassign_activity_itemid) {
    global $DB;
    // This is an additional security: erase eventually old entries in 'iassign_security' table (do not remove '$iassign_activity_itemid' since it is going to be used "now")
    $result = $DB->delete_records_select("iassign_security", "userid=" . $userid . " AND iassign_statementid<>" . $iassign_activity_itemid, null);
    }

  } // class ilm


/// Class to manage settings of iLM.
class ilm_settings {

  /// Function to prepare tag to load iLM (that is stored in Moodle file system - usually /var/moodledata/filedir/).
  //  In case of JAR it will prepare the tag "applet". In case of HTML5 will prepare an "iframe".
  //  @param int $ilm_id Id of iLM
  //  @param array $options An array with options for create dynamic tag html APPLET
  //  @return string Return with a tag html to JavaScript (or Applet)
  static function build_ilm_tags ($ilm_id, $options = array()) {
    global $DB;

    if (!isset($ilm_id) || $ilm_id<0) // security to avoid error: Exception - Attempt to assign property "type" on bool
      return "";

    $iassign_ilm = $DB->get_record('iassign_ilm', array('id' => $ilm_id));

    // Read the iLM type: 'html5' or 'java'
    if (!isset($iassign_ilm->type)) // avoid error "Deprecated: strtolower(): Passing null to parameter #1"
      $iassign_ilm->type = "html5"; // default is HTML/JavaScript
    $typec = strtolower($iassign_ilm->type);
    require_once 'ilm_handlers/' . $typec . '.php';
    $retorno = $typec::build_ilm_tags($ilm_id, $options); // In 'ilm_handlers/html5.php' or 'ilm_handlers/java.php'
    return $retorno;
    } // static function build_ilm_tags($ilm_id, $options = array())


  /// Function for get modified date of iLM file
  //  @param string $file_jar String with Ids of iLM files
  //  @return string Return with the filenames and modified date
  static function applet_filetime ($file_jar) {
    $filetime = "";
    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $files_jar = explode(",", $file_jar);
    foreach ($files_jar as $one_file) {
      $file = $fs->get_file_by_id($one_file);
      if ($file)
        $filetime .= "\n" . $file->get_filename() . ' (' . userdate($file->get_timemodified()) . ')' . '</br>';
      }
    return $filetime;
    }


  /// Function for verify an default applet
  //  @param String $file_jar String containing an list de ids of applet files
  //  @return boolean Return true or fale if applet is default
  static function applet_default ($file_jar) {
    $is_default = true;
    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $files_jar = explode(",", $file_jar);
    foreach ($files_jar as $one_file) {
      $file = $fs->get_file_by_id($one_file);
      if ($file)
        $is_default &= ($file->get_itemid() == 0);
      }
    return $is_default;
    }


  /// Function for get form variables for add, edit, or copy iLM
  //  This function is called to fill in the iLM data to the form
  //  The function 'edit_ilm(.)' bellow is responsible to process edited data
  //  @calledby settings_ilm.php: On the first call $ilm_id is defined: it is used to fill in with parent iLM data {iassign_ilm}
  //  @calledby settings_ilm.php: On the second call $ilm_id empty (however must be used)
  //  @see      settings_form.php
  //  @see      locallib.php : static function copy_new_version_ilm($param) : perform the data register (files and database)
  //  @see      locallib.php : static function edit_ilm($param,$itemid): perform the data register (files and database) in edition process
  //  @param int $ilm_id Id of iLM
  //  @param string $action String with the action
  //  @return object Return an object with forms variables
  static function add_edit_copy_ilm ($ilm_id, $action) {
    global $USER, $DB, $CFG;

    require_once('settings_form.php'); // put the form

    $iassign_ilm = $DB->get_record('iassign_ilm', array('id' => $ilm_id));

    $param = new stdClass();

    $param->action = $action;
    $param->ilm_id = $ilm_id;
    $CFG->action_ilm = $action;
    $CFG->ilm_id = $ilm_id;

    if ($action == 'add') {
      $param->title = get_string('add_ilm', 'iassign');
      $param->name = "";
      $param->version = "";
      $param->url = "";
      $param->description = "";
      $param->extension = "";
      $param->author = $USER->id;
      $param->file_jar = "";
      $param->file_jar_static = "";
      $param->file_class = "";
      $param->width = 800;
      $param->height = 600;
      $param->enable = 0; // 0 - hide / 1 - show
      $param->timecreated = time();
      $param->timemodified = time();
      $param->evaluate = 0; // evaluate = 1 => iLM has auto-evaluation method
      $param->reevaluate = 0; // reevaluate = 1 => allows to the teacher calls method to re-evaluate (does it in batch, to all students)
      $param->editingbehavior = 0; // editingbehavior = 1 => iLM auto-evaluation remains working over a solution sent by the student (iGeom does not work, iVProg does)
      $param->submissionbehavior = 0; // 0 => 'After submission, this iLM remains on the same page'; 1 => 'After submission, this iLM changes the current page'
      $param->parent = 0;
      }
    elseif ($action == 'edit') { // static function add_edit_copy_ilm($ilm_id, $action)
      if ($iassign_ilm) {
        $description = json_decode($iassign_ilm->description);

        $param->title = get_string('edit_ilm', 'iassign');
        $param->id = $iassign_ilm->id;
        $param->name_ilm = $iassign_ilm->name;
        $param->name = $iassign_ilm->name;
        $param->version = $iassign_ilm->version;
        $param->ilm_type = $iassign_ilm->type;
        $param->type = $iassign_ilm->type; // using as 'type' in ilm_handlers/html5.php
        $param->url = $iassign_ilm->url; // iLM official URL

        // 'iassign_ilm.submissionbehavior'=1 => this iLM does not have submission button
        $param->submissionbehavior = $iassign_ilm->submissionbehavior;
        $param->editingbehavior = $iassign_ilm->editingbehavior;

        // If the iLM does not present curret language => Notice: Undefined property: stdClass::$en_us in...
        //D try {
        if (!isset($description->{current_language()})) {
          if (isset($description->{"en_us"})) $param->description = $description->{"en_us"};
          else
          if (isset($description->{"pt_br"})) $param->description = $description->{"pt_br"};
          else $param->description = ""; //TODO Providenciar uma mensagem!? Pegar alguma lingua existente?
          }
        else
          $param->description = $description->{current_language()};
        //D } catch (Exception $e) { $param->description = ""; } // avoid error in /lib/external/classes/external_api.php: invalid_parameter_exception thrown ?

        $param->description_lang = $iassign_ilm->description;
        $param->extension = $iassign_ilm->extension;
        $param->author = $iassign_ilm->author;
        $param->file_jar = $iassign_ilm->file_jar;
        $param->file_jar_static = ilm_settings::applet_filetime($iassign_ilm->file_jar);

        $param->file_class = $iassign_ilm->file_class;

        $param->width = $iassign_ilm->width;
        $param->height = $iassign_ilm->height;
        $param->enable = $iassign_ilm->enable;
        $param->timecreated = $iassign_ilm->timecreated;
        $param->timemodified = time();
        $param->evaluate = $iassign_ilm->evaluate; // evaluate = 1 => iLM has auto-evaluation method
        $param->reevaluate = $iassign_ilm->reevaluate; // reevaluate = 1 => allows to the teacher calls method to re-evaluate (does it in batch, to all students)
        $param->editingbehavior = $iassign_ilm->editingbehavior; // editingbehavior = 1 => iLM auto-evaluation remains working over a solution sent by the student (iGeom does not work, iVProg does)
        $param->submissionbehavior = $iassign_ilm->submissionbehavior; // 0 => 'After submission, this iLM remains on the same page'; 1 => 'After submission, this iLM changes the current page'
        $param->parent = $iassign_ilm->parent;
        } // if ($iassign_ilm)
      } // elseif ($action == 'edit')
    elseif ($action == 'new_version') { // Reaches this point from the form to add new iLM
      // @see ./settings_ilm.php: $param = ilm_settings::add_edit_copy_ilm($ilm_id, $action);
      // $iassign_ilm = $DB->get_record('iassign_ilm', array('id' => $ilm_id));

      if ($ilm_id==0 && !isset($mform)) { // The second turn (form sended) reaches this point - Just in case (if the admin use back and forward in the browser)
        // Not necessary message: when data is sent, reaches this point with 0 and $mform null..., just return and let 'ilm_handlers/html5.php' process
        // // if ($iassign_ilm)
        // print get_string('empty_file', 'iassign') . "?<br/>\n"; //TODO warning not to use back?
        return;
        }
      $description = json_decode($iassign_ilm->description);
      if ($iassign_ilm) {
        if ($iassign_ilm->parent == 0)
          $iassign_ilm->parent = $ilm_id;
        $param->ilm_id = $ilm_id; //2019 after 'settings_ilm.php' it reaches this point again, better to define 'ilm_id'
        $param->title = get_string('new_version_ilm', 'iassign');
        $param->name_ilm = $iassign_ilm->name;
        $param->name = $iassign_ilm->name;
        $param->version = ""; // erase field 'version' - in order to present this field empty on the form (in 'settings_form.php')
        $param->version_last = $iassign_ilm->version; //TODO not yet used!
        $param->version = $iassign_ilm->version; // do not erase field 'version'
        $param->ilm_type = $iassign_ilm->type;
        $param->url = $iassign_ilm->url;
        $param->description = $description->{current_language()};
        $param->description_lang = $iassign_ilm->description; // JSON format: {"en":"Visual Interactive Programming on the Internet HTML5","pt_br":"Programac,a~o visual interativa na Internet"}
        $param->extension = $iassign_ilm->extension;
        $param->author = $USER->id;
        //D $param->file_jar = ''; // do not erase field 'file_jar'!
        $param->file_jar = $iassign_ilm->file_jar; // do not erase field 'file_jar'!
        $param->file_jar_static = '';
        $param->file_class = $iassign_ilm->file_class;
        $param->width = $iassign_ilm->width;
        $param->height = $iassign_ilm->height;
        $param->enable = 0;
        $param->timecreated = time();
        $param->timemodified = time();
        $param->evaluate = $iassign_ilm->evaluate;
        $param->reevaluate = $iassign_ilm->reevaluate; // reevaluate = 1 => allows to the teacher calls method to re-evaluate (does it in batch, to all students)
        $param->editingbehavior = $iassign_ilm->editingbehavior; // editingbehavior = 1 => iLM auto-evaluation remains working over a solution sent by the student (iGeom does not work, iVProg does)
        $param->submissionbehavior = $iassign_ilm->submissionbehavior; // 0 => 'After submission, this iLM remains on the same page'; 1 => 'After submission, this iLM changes the current page'
        $param->parent = $iassign_ilm->parent;
        } // if ($iassign_ilm)
      } // elseif ($action == 'new_version')
    elseif ($action == 'copy') {
      $description = json_decode($iassign_ilm->description);
      if ($iassign_ilm) {
        if ($iassign_ilm->parent == 0)
          $iassign_ilm->parent = $ilm_id;
        $param->title = get_string('copy_ilm', 'iassign');
        $param->id = $iassign_ilm->id;
        $param->name_ilm = $iassign_ilm->name;
        $param->name = $iassign_ilm->name;
        $param->version = "";
        $param->ilm_type = $iassign_ilm->type;
        $param->type = $iassign_ilm->type; // using as 'type' in ilm_handlers/html5.php
        $param->url = $iassign_ilm->url;
        $param->description = $description->{current_language()};
        $param->description_lang = $iassign_ilm->description;
        $param->extension = $iassign_ilm->extension;
        $param->author = $USER->id;
        $param->file_jar = '';
        $param->file_jar_static = '';
        $param->file_class = $iassign_ilm->file_class;
        $param->width = $iassign_ilm->width;
        $param->height = $iassign_ilm->height;
        $param->enable = 0;
        $param->timecreated = time();
        $param->timemodified = time();
        $param->evaluate = $iassign_ilm->evaluate;
        $param->reevaluate = $iassign_ilm->reevaluate; // reevaluate = 1 => allows to the teacher calls method to re-evaluate (does it in batch, to all students)
        $param->editingbehavior = $iassign_ilm->editingbehavior; // editingbehavior = 1 => iLM auto-evaluation remains working over a solution sent by the student (iGeom does not work, iVProg does)
        $param->submissionbehavior = $iassign_ilm->submissionbehavior; // 0 => 'After submission, this iLM remains on the same page'; 1 => 'After submission, this iLM changes the current page'
        $param->parent = $iassign_ilm->parent;
        } // if ($iassign_ilm)
      } // elseif ($action == 'copy'

   //D debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
   //D echo "Fim: action=" . $param->action . "<br/>---------------------------------<br/>";
   //D var_dump(debug_backtrace());
   //D print_r(debug_backtrace()); echo "param="; print_r($param); echo "<br/>\n";
   //D echo "<br/>---------------------------------<br/>"; exit;

    return $param;
    } // static function add_edit_copy_ilm($ilm_id, $action)


//2023/05/30 
// Removi funcao NAO mais usada: static function new_file_ilm ($itemid, $iassign_ilm)
// Tambem removi homonima de './iassign/ilm_handlers/html5.php': static function new_file_ilm ($itemid, $fs, $contextuser, $contextsystem, $files_ilm) : 831/1172
// Para : /home/leo/projetos/iMA/lms/itarefa/novo/removidos/locallib_html_2023_05_30.php


  /// Function insert new iLM: it creates directory "mod/iassign/ilm/<iLM name>/<iLM version>" and register in {iassign_ilm}
  //  Admin user in iLM manager interface (iassign/settings_ilm.php) clicks on the button "Add iLM"
  //  fill the form (from 'settings_ilm.php') and send a ZIP file with the complete HTML5 package
  //  @calledby ./iassign/settings_ilm.php: if ($action == 'add'): $retorno = ilm_settings::new_ilm($formdata->file);
  //  @param object $param An object with iLM params
  static function new_ilm ($formdata) {
    global $CFG, $USER, $OUTPUT;

    //D Check if there is any previous ZIP file in Moodle data "temp" (if positive, remove it): MoodleData/temp
    //D if (file_exists($CFG->dataroot . '/temp/' . 'ilm-application.xml')) {      unlink($CFG->dataroot . '/temp/' . 'ilm-application.xml');      }
    $file_draft = $formdata->file;

    $pathtemp = $CFG->dataroot . '/temp/';
    $contextuser = context_user::instance($USER->id);

    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $zip = new zip_packer();
    $files = $fs->get_directory_files($contextuser->id, 'user', 'draft', $file_draft, '/');
    foreach ($files as $file) {
      if (!$file->is_directory())
        $files_extract = $zip->extract_to_pathname($file, $pathtemp);
      }

     // From './iassign/settings_ilm.php': $formdata = $mform->get_submitted_data();
     $iassign_ilm_instance = $formdata;
     // {iassign_ilm}.typ: $formdata->type : 0 = package JavaScript; 1 = package Java (JAR)
     if ($formdata->type==1) $formdata->type = "Java";
     else $formdata->type = "HTML5"; // default is "HTML5"

    // Check if form sent all the necessary data to insert into table {iassign_ilm}
    if (!$iassign_ilm_instance) {
      // print($OUTPUT->notification(get_string('error_xml_ilm', 'iassign'), 'notifyproblem'));
      print($OUTPUT->notification(get_string('error_add_ilm_zip', 'iassign'), 'notifyproblem')); // Error while extracting the content from iLM package file
      return;
      }
    else {
      $missing = "";
      if (!isset($iassign_ilm_instance->name)) { $missing .= "name"; }
      if (!isset($iassign_ilm_instance->version)) { $missing .= ", version"; }
      if (!isset($iassign_ilm_instance->type)) { $missing .= ", type"; }
      if (!isset($iassign_ilm_instance->extension)) { $missing .= ", extension"; }
      if (!isset($iassign_ilm_instance->file_jar)) { $missing .= ", file_jar"; }
      if (!isset($iassign_ilm_instance->file_class)) { $missing .= ", file_class"; }
      if (!isset($iassign_ilm_instance->width)) { $missing .= ", width"; }
      if (!isset($iassign_ilm_instance->height)) { $missing .= ", height"; }
      if (!isset($iassign_ilm_instance->evaluate)) { $missing .= ", evaluate"; }
      if (strlen($missing) > 2) {
        // print($OUTPUT->notification(get_string('error_xml_missing', 'iassign') . $missing . ".", 'notifyproblem'));
        // ERROR: in selecting compacted file (zip) to be imported!
        print($OUTPUT->notification(get_string('er_file_not_selected_to_import', 'iassign') . $missing . ".", 'notifyproblem'));
        return;
        }
      }

    // Process form data using ./ilm_handlers/html5.php (or ./ilm_handlers/java.php):
    $typec = strtolower($iassign_ilm_instance->type);
    require_once 'ilm_handlers/' . $typec . '.php';
    $retorno = $typec::new_ilm($file_draft, $files_extract, $iassign_ilm_instance, $contextuser, $fs); // ./mod/iassign/ilm_handlers/html5.php
    return $retorno;
    } // static function new_ilm($file_draft, $formdata)


  /// Function for save in database an iLM edit
  //  The above function 'add_edit_copy_ilm(.)' is responsible to provide the iLM data in this form
  //  The data stored through 'ilm_handlers/html5.php' (or 'java.php')
  //  @param object $param An object with iLM params
  //  @calledby settings_ilm.php: ilm_settings::edit_ilm($formdata, $formdata->file);
  static function edit_ilm ($param, $itemid) {
    // Descobrir o tipo de iLM:
    global $DB, $USER, $CFG;

    //D debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS); //exit;

    $iassign_t = $DB->get_record('iassign_ilm', array('id' => $param->id));

    $pathtemp = $CFG->dataroot . '/temp/';

    $contextuser = context_user::instance($USER->id);

    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $zip = new zip_packer();
    $files = $fs->get_directory_files($contextuser->id, 'user', 'draft', $itemid, '/');
    $files_extract = null;
    foreach ($files as $file) {
      if (!$file->is_directory())
        $files_extract = $zip->extract_to_pathname($file, $pathtemp);
      }

    // Get the iLM type (HTML5 or Java) to call the correspondent updater
    $typec = strtolower($iassign_t->type);

    require_once 'ilm_handlers/' . $typec . '.php';
    $typec::edit_ilm($param, $itemid, $files_extract, $contextuser); // ilm_handlers/html5.php or ilm_handlers/java.php
    } // static function edit_ilm($param, $itemid)


  /// Function for register the new iLM (in database and in the directory)
  //  Came from 'settings_ilm.php' with $action=='new_version' or $action == 'copy'
  //  Interface: Administration > plugins > iAssign : after select the iLM and the option 'Add new iLM version'
  //  @see      settings_form.php is extended by 'settings_ilm.php'
  //  @calledby settings_ilm.php!ilm_settings::copy_new_version_ilm($formdata)
  //  @param object $param An object with iLM params
  static function copy_new_version_ilm ($param) {
    global $DB, $CFG, $USER;
    $itemid = $param->file;

    $pathtemp = $CFG->dataroot . '/temp/'; // in Moodle Data
    //D echo "locallib.php: copy_new_version_ilm(...): param->description=" . $param->description . ", param->description_lang=". $param->description_lang . "<br/>";
    //D echo "pathtemp=$pathtemp<br/>";

    $contextuser = context_user::instance($USER->id);

    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $zip = new zip_packer();
    $files = $fs->get_directory_files($contextuser->id, 'user', 'draft', $itemid, '/');
    $files_extract = null;
    foreach ($files as $one_file) {
      if (!$one_file->is_directory())
        $files_extract = $zip->extract_to_pathname($one_file, $pathtemp);
      }

    //D echo "locallib.php!copy_new_version_ilm(.): itemid=" . $itemid . ", ilm_id=" . $param->ilm_id . ", parent=" . $param->parent . "<br/>"; // . $type_ipz_zip . "<br/>";

    // Get information of iLM parent
    // $param->parent must be set!
    $iassign_t = $DB->get_record('iassign_ilm', array('id' => $param->parent));
    $param->ilm_id = $param->parent; // original {iassign_ilm}.id

    $typec = strtolower($iassign_t->type);
    require_once 'ilm_handlers/' . $typec . '.php';
    $typec::copy_new_version_ilm($param, $files_extract); // ./mod/iassign/ilm_handlers/html5.php: in fact, here the new iLM is inserted

    } // static function copy_new_version_ilm($param)


  //VER::
  //Notice: Undefined variable: output_ilm in ./mod/iassign/locallib.php on line 5054
  //Notice: Trying to get property of non-object in ./mod/iassign/locallib.php on line 192
  //Notice: Trying to get property of non-object in ./mod/iassign/locallib.php on line 194
  /// Function for change visibility of iLM
  //  @param int $ilm_id Id of iLM
  //  @param int $status Indicator of change vibility (0 = hide, 1 = show)
  static function visible_ilm ($ilm_id, $status) {
    global $DB;
    if ($status == 0)
      $visible = 1;
    else
      $visible = 0;
    $newentry = new stdClass();
    $newentry->id = $ilm_id;
    $newentry->enable = $visible;

    if (!$DB->update_record("iassign_ilm", $newentry))
      error(get_string('error_edit_ilm', 'iassign'));
    }


  /// Function for confirm change default iLM
  //  @param int $ilm_id Id of iLM
  //  @param int $ilm_parent Id of parent iLM
  //  @return string Return with an string for create default page confirmation
  static function confirm_default_ilm ($ilm_id, $ilm_parent) {
    global $OUTPUT, $DB;

    $iassign_ilm = $DB->get_record('iassign_ilm', array('id' => $ilm_id));

    $optionsno = new moodle_url('/admin/settings.php', array('section' => 'modsettingiassign', 'action' => 'config', 'ilm_id' => $ilm_parent));
    $optionsyes = new moodle_url('/mod/iassign/settings_ilm.php', array('action' => 'default', 'ilm_id' => $ilm_id, 'ilm_parent' => $ilm_parent));

    $return = $OUTPUT->heading(get_string('confirm_default', 'iassign') . ': ' . $iassign_ilm->name);
    $return .= $OUTPUT->confirm(get_string('confirm_default_ilm', 'iassign') . $OUTPUT->help_icon('confirm_default_ilm', 'iassign'), $optionsyes, $optionsno);
    return $return;
    }


  /// Function for change the default iLM
  //  @see   settings_ilm.php
  //  @param int $ilm_id Id of iLM
  //  @return int Return Id of default iLM
  static function default_ilm ($ilm_id) {
    global $DB;

    $iassign_ilm_default = $DB->get_record("iassign_ilm", array('id' => $ilm_id));

    $iassign_ilm = $DB->get_record("iassign_ilm", array('id' => $iassign_ilm_default->parent));

    $DB->delete_records("iassign_ilm", array('id' => $iassign_ilm_default->id));

    $iassign_ilm_default->id = $iassign_ilm->id;
    $iassign_ilm_default->parent = 0;
    $iassign_ilm->parent = $iassign_ilm_default->id;
    $iassign_ilm->id = 0;
    $iassign_ilm_default->enable = 1;

    if (!$DB->update_record("iassign_ilm", $iassign_ilm_default)) {
      print_error('error_edit_ilm', 'iassign');
      }

    $inserted = $DB->insert_record("iassign_ilm", $iassign_ilm);
    if (!$inserted) {
      $msg_error = get_string('error_add_ilm', 'iassign') . "<br/>In default_ilm(" . $ilm_id . ")<br/>\n";
      print_error($msg_error);
      //xx print_error('error_add_ilm', 'iassign');
      }

    return $iassign_ilm_default->id;
    } // static function default_ilm ($ilm_id)


  /// Function to present to the user (tipically the Moodle Admin) the frame to confirm the intention of iLM remotion
  //  @see      settings_ilm.php!$action=='delete' : after the Admin confirmation, 'settings_ilm.php' will be called again with 'delete' option (really erase iLM)
  //  @see      locallib.php!delete_ilm($ilm_id) : called by "settings_ilm.php!$action=='delete'" that calls "ilm_handlers/html5.php!delete_ilm($ilm_id)"
  //  @see      ilm_handlers/html5.php!delete_ilm($ilm_id) : called by "settings_ilm.php!$action=='delete'" to really erase the iLM
  //  @calledby settings_ilm.php!$action=='confirm_delete_ilm' : $delete_ilm = ilm_settings::confirm_delete_ilm($ilm_id, $ilm_parent);
  //  @param int $ilm_id Id of iLM
  //  @param int $ilm_parent Id of parent iLM
  //  @return string Return with an string to create delete page confirmation
  static function confirm_delete_ilm ($ilm_id, $ilm_parent) {
    global $OUTPUT, $DB, $USER;

//D echo "locallib.php!confirm_delete_ilm(.): (1) ilm_id=" . $ilm_id . ", ilm_parent="; print_r($ilm_parent); echo "<br/>\n"; //exit; //2025/04/11 leo
    $iassign_ilm = $DB->get_record('iassign_ilm', array('id' => $ilm_id));
    $msg = "";
    // Verify if there are {iassign_statement} or {iassign_submission} associated to this iLM
    // in this case, launch a message to inform Admin of this problem
    if ($iassign_ilm) { // there is this iLM with ID=$ilm_id
//D echo "locallib.php!confirm_delete_ilm(.): (2) ilm_id=" . $ilm_id . ", existe este iLM<br/>\n"; // exit; //2025/04/11 leo
      // Attention, "./lib/dml/moodle_database.php ! public abstract function get_records_sql($sql, array $params=null, $limitfrom=0, $limitnum=0)"
      // return a single element considering the first field, so we need to use {iassign_submission}.id (not {iassign_statement}.id))
      $str_query = "SELECT iasubm.id AS iasub_id, iastat.id FROM {iassign_statement} AS iastat, {iassign_submission} AS iasubm" .
        " WHERE iastat.iassign_ilmid=" . $ilm_id . " AND iastat.id=iasubm.iassign_statementid"; //
      $tmp_list_all_iassign_statement_subm = $DB->get_records_sql($str_query); // get array of stdClass (must be only one element)
      $num_is = count($tmp_list_all_iassign_statement_subm);
      if ($num_is>0) {
        $param1 = new stdClass(); $param1->num = $num_is; $param1->id = $ilm_id;
        // If the Admin remove this iLM its activities will became orphans!
        $msg .= get_string('confirm_del_ilm_sure1', 'iassign', $param1); // This iLM has {$num_is} submissions to its activities! See its ID and number of submissions bellow:
        $last_iassign_id = -1; // to find different {iassign}
        $count_ia = 0;
        $count_subm = 0;
        foreach ($tmp_list_all_iassign_statement_subm as $item) {
          $count_subm++; // new submission to the last {iassign}.id
          $iassign_id = $item->id; // {iassign}.id
          if ($iassign_id != $last_iassign_id) { // new {iassign}.id, close the last one
            if ($last_iassign_id!=-1) { // it is not the first
              $url = "view.php?userid_iassign=" . $USER->id . "&action=view&iassign_current=" . $iassign_id;
              // Click to check this activity (id,#submissions) - you will need to change its iLM
              $msg .= "<a href='" . $url . "' title='" . get_string('confirm_del_ilm_sure2', 'iassign') . "' target='_blank'>" .
                $iassign_id . " (" . $count_subm . ")</a> ";
              }
            $count_ia++;
            if ($count_ia>20) {
              break; // present at most 20
              }
            $last_iassign_id = $iassign_id; // the new identified activity
            $count_subm = 1; // first submission to the new identified activity
            }
          }
        // the last {iassign}.id was not registered
        $url = "view.php?userid_iassign=" . $USER->id . "&action=view&iassign_current=" . $iassign_id;
        $msg .= "<a href='" . $url . "' title='" . get_string('confirm_del_ilm_sure2', 'iassign') . "' target='_blank'>" .
          $iassign_id . " (" . $count_subm . ")</a> ";
        if ($count_ia>20) $msg .= "...";
        $msg .= "<br/>\n";
        } // if ($num_is>0)
      else { // Despite the existence of activities with this iLM they haven't any associated submission!
//D echo "locallib.php!confirm_delete_ilm(.): (3) ilm_id=" . $ilm_id . ", OK, NAO existe submissoes com este iLM<br/>ilm_parent=" . $ilm_parent . "<br/>\n"; //exit; //2025/04/11 leo
        $str_query = "SELECT id,name FROM {iassign_statement} WHERE iassign_ilmid=" . $ilm_id . ""; //
        $tmp_list_all_iassign_statement = $DB->get_records_sql($str_query); // get array of stdClass (must be only one element)
        $num_is = count($tmp_list_all_iassign_statement);
        $param1 = new stdClass(); $param1->num = $num_is; $param1->id = $ilm_id;
        // 'confirm_del_ilm_sure3' = 'This iLM has {$a} activities! Before decide to remove it, register their ID:'
        $msg .= get_string('confirm_del_ilm_sure3', 'iassign', $param1);
        foreach ($tmp_list_all_iassign_statement as $item) {
          $url = "view.php?userid_iassign=" . $USER->id . "&action=view&iassign_current=" . $item->id;
          $msg .= "<a href='" . $url . "' title='Click to check this activity (id) - you will need to change its iLM' target='_blank'>" . $item->id . " </a> ";
          }
        }
      } // if ($iassign_ilm) // there is this iLM with ID=$ilm_id
    else { // security
      // $string['error_confirms_ilm']             = 'Error: iLM not registered in the database. Inform the system administrator.';
      print($OUTPUT->notification(get_string('error_remove_ilm_inexist', 'iassign'), 'notifyproblem')); // Error: the given iLM (ID) does not exist
      return;
      }

    $optionsno = new moodle_url('/admin/settings.php', array('section' => 'modsettingiassign', 'action' => 'config', 'ilm_id' => $ilm_parent));
    // $optionsyes = new moodle_url('/mod/iassign/settings_ilm.php', array('action' => 'delete', 'ilm_id' => $ilm_id, 'ilm_parent' => $ilm_parent));
    $optionsyes = new moodle_url('/mod/iassign/settings_ilm.php', array('action' => 'delete', 'ilm_id' => $ilm_id, 'ilm_parent' => $ilm_parent));

    $msg .= "<br/>" . get_string('confirm_del_ilm', 'iassign', $iassign_ilm->name . ' ' . $iassign_ilm->version);

    $answer = $OUTPUT->confirm($msg, $optionsyes, $optionsno); // return to "settings_ilm.php!$action=='confirm_delete_ilm'" message
//D echo "locallib.php!confirm_delete_ilm(.): ilm_id=" . $ilm_id . ", answer="; print_r($answer); echo "<br/>\n"; //exit; //2025/04/11 leo
    return $answer;
    } // static function confirm_delete_ilm($ilm_id, $ilm_parent)


  /// Function for delete directory where the iLM is allocated.
  //  @param string $dirPath
  //  @throws InvalidArgumentException
  public static function delete_dir ($dirPath) {
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
      $dirPath .= '/';
      }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $one_file) {
      if (is_dir($one_file)) {
        ilm_settings::delete_dir($one_file);
        } else {
        unlink($one_file);
        }
      }
    rmdir($dirPath);
    }


  /// Function to provide the iLM removal
  /// After Admin confirm his intention to remove the iLM, reaches this function
  //  @see       ilm_handlers/html5.php!delete_ilm($ilm_id) : to really erase the iLM
  //  @calledy   settings_ilm.php!$action=='delete' : $parent = ilm_settings::delete_ilm($ilm_id);
  //  @param int $ilm_id is the iLM ID     ({iassign_ilm}.id)
  //  @return int Return ID of parent iLM  ({iassign_ilm}.id)
  static function delete_ilm ($ilm_id) {
    global $DB;
    //D echo "locallib.php!delete_ilm(.): ilm_id=" . $ilm_id . "<br/>\n"; //exit; //2025/04/11 leo
    $iassign_ilm = $DB->get_record('iassign_ilm', array('id' => $ilm_id));
    //D echo "locallib.php!delete_ilm(.): ilm_id=" . $ilm_id . ", type=" . $iassign_ilm->type . "<br/>\n"; //exit; //2025/04/11 leo

    // Get the iLM type ({iassign_ilm}.type) to call the correct function that really erase the iLM (from DataBase and from ./mod/iassin/ilm/ directory)
    $typec = strtolower($iassign_ilm->type);
    require_once 'ilm_handlers/' . $typec . '.php';
    $return_ilm_parent_id = $typec::delete_ilm($ilm_id); // $typec=="html5" => ilm_handlers/html5.php
    return $return_ilm_parent_id;
    }


  /// Function to export iLM package (ZIP file), eventually to install in other iAssign/Moodle
  //  @calledby settings.php : if ($action == 'export') ilm_settings::export_ilm($ilm_id);
  //  @param int $ilm_id Id of iLM
  static function export_ilm ($ilm_id) {
    global $DB;
    $iassign_ilm = $DB->get_record('iassign_ilm', array('id' => $ilm_id));
    if (!$iassign_ilm) return; // security debug...

    // Get the iLM type ("HTML5" or "Java") and call the correspondent function in 'ilm_handlers'
    $typec = strtolower($iassign_ilm->type);
    if (!$typec) $typec = "html5"; // security debug...
    require_once 'ilm_handlers/' . $typec . '.php'; // prepare correct handler (now Java or HTML5)
    $typec::export_ilm($ilm_id);
    } // static function export_ilm($ilm_id)


  // REMOVER NAO e' chamada! ilm_settings::export_update_ilm(.)
  //TODO iLM_HTML5 :: //MOOC 2016
  //  Function to export iLM package descriptor (it allows online update)
  //  @param int $ilm_id Id of iLM
  static function export_update_ilm ($ilm_id) {
    global $DB, $CFG;

    $iassign_ilm = $DB->get_record('iassign_ilm', array('id' => $ilm_id));

    $xml_filename = $CFG->dataroot . '/temp/ilm-upgrade_' . iassign_utils::format_pathname($iassign_ilm->name) . '.xml';
    $zip_filename = 'ilm-' . iassign_utils::format_pathname($iassign_ilm->name . '-v' . $iassign_ilm->version) . '.ipz';

    $upgrade_descriptor = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    $upgrade_descriptor .= '<upgrade xmlns="http://line.ime.usp.br/application/1.5">' . "\n";
    $upgrade_descriptor .= '   <version>' . $iassign_ilm->version . '</version>' . "\n";
    $upgrade_descriptor .= '   <file>' . $zip_filename . '</file>' . "\n";
    $upgrade_descriptor .= '   <description>' . iassign_language::json_to_xml($iassign_ilm->description) . "\n  " . '</description>' . "\n";
    $upgrade_descriptor .= '</upgrade>' . "\n";

    file_put_contents($xml_filename, $upgrade_descriptor);

    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private", false);
    header('Content-Type: application/xml; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"" . basename($xml_filename) . "\";");
    header("Content-Length: " . @filesize($xml_filename));
    set_time_limit(0);
    @readfile("$xml_filename") || die("File not found.");
    unlink($xml_filename);
    exit;
    } // static function export_update_ilm($ilm_id) //MOOC 2016


  // REMOVER? 
  //   ilm_settings::save_ilm_by_xml(.)
  //   Codigo equivalente em: ./mod/iassign/ilm_handlers/html5.php!save_ilm_by_xml($str_from, $application_xml, $files_extract)
  //
  //TODO Perhaps add param. "$ilm_id" to {files}.itemid <- $ilm_id ???
  /// Function for save iLM from XML descriptor
  //  Inside: class ilm_settings
  //  @calledby static function upgrade_ilm($ilm_id) : $file_jar = self::save_ilm_by_xml($ilm_id, $application_xml, $files_extract);
  //  @param array $application_xml Data of XML descriptor
  //  @param array $files_extract Filenames of extract files
  //  @return array Return an array content id of JAR files
  static function save_ilm_by_xml ($parent_ilm_id, $application_xml, $files_extract) {
    global $CFG, $USER;

    // Tratamento diferenciado se for do tipo HTML5:
    $source = "";
    $directory = "";
    if (strtolower($application_xml->type) == 'html5') {
      $i = 0;
      foreach ($files_extract as $key => $value) {
        $file = $CFG->dataroot . '/temp/' . $key;
        // Check the previous existence of the directory (to iLM):
        if ($i == 0) {
          $source = $file;
          if (file_exists("ilm/" . basename($file))) {
            $j = 1;
            while (file_exists('ilm/' . basename($file) . "_" . $j)) {
              $j++;
              }
            $directory = 'ilm/' . basename($file) . "_" . $j;
            // mkdir($directory, 0777, true);
            mkdir($directory, 0755, true); // permissions: drwxr-xr-x
            }
          else {
            $directory = 'ilm/' . basename($file);
            // mkdir($directory, 0777, true);
            mkdir($directory, 0755, true);
            }
          break;
          }
        $i++;
        }

      foreach ($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item) {
        if ($item->isDir()) {
          mkdir($directory . DIRECTORY_SEPARATOR . $iterator->getSubPathName(), 0755, true);
          }
        else {
          copy($item, $directory . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
          }
        }

      ilm_settings::delete_dir($source);
      return "./" . $directory;
      }

    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $file_jar = array();
    $files_ilm = explode(",", $application_xml->file_jar);
    $contextsystem = context_system::instance();

    foreach ($files_ilm as $value) {
      //TODO Sobre {files}.itemid: NAO existe no {iassign_ilm} campo para guardar {files}.itemid, entao usar 'itemid = {iassign_ilm}.id'
      $file_ilm = array(
        'userid' => $USER->id,
        'contextid' => $contextsystem->id,
        'component' => 'mod_iassign',
        'filearea' => 'ilm',
        'itemid' => $parent_ilm_id, //2023 rand(1, 999999999), //2023 added param. "$ilm_id" to {files}.itemid <- $ilm_id
        'filepath' => '/iassign/ilm/' . iassign_utils::format_pathname($application_xml->name) . '/' . iassign_utils::format_pathname($application_xml->version) . '/',
        'filename' => $value);

      $file_ilm = $fs->create_file_from_pathname($file_ilm, $CFG->dataroot . '/temp/' . $value);

      array_push($file_jar, $file_ilm->get_id()); //TODO: must be reviewed! {iassign_ilm}.file_jar as array() of int???
      }

    foreach ($files_extract as $key => $value) {
      $file = $CFG->dataroot . '/temp/' . $key; // in Moodle Data
      if (file_exists($file))
        unlink($file);
      }
    return $file_jar; // return array() with {files}.id of each created file in Moodle Data
    } // static function save_ilm_by_xml($parent_ilm_id, $application_xml, $files_extract)


  /// Function to import the iLM from a package IPZ
  //  @see      https://www.matematica.br/ia/about_ilm-application.html
  //  @calledby iassign/settings_ilm.php: if ($action == 'import'): $retorno = ilm_settings::import_ilm($formdata->file);
  //  @param int $itemid Itemid of zip file
  static function import_ilm ($itemid) {
    global $CFG, $USER, $OUTPUT;

    // Verify if is remaining an old 'ilm-application.xml' in temp directory, in this case remove it
    if (file_exists($CFG->dataroot . '/temp/' . 'ilm-application.xml')) {
      unlink($CFG->dataroot . '/temp/' . 'ilm-application.xml');
      }
    $pathtemp = $CFG->dataroot . '/temp/';
    $contextuser = context_user::instance($USER->id);

    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $zip = new zip_packer();
    $files = $fs->get_directory_files($contextuser->id, 'user', 'draft', $itemid, '/');
    foreach ($files as $one_file) {
      if (!$one_file->is_directory()) {
        $files_extract = $zip->extract_to_pathname($one_file, $pathtemp);
        break;
        }
      }

    //D $end_arquivo = $CFG->dataroot . '/temp/' . 'ilm-application.xml';
    //D echo "locallib.php!import_ilm(.): itemid=" . $itemid . "<br/>end_arquivo=" . $end_arquivo . "<br/>\n";
    //D $myfile = fopen($end_arquivo, "r") or die("Unable to open file!");
    //D echo "<pre>" . fread($myfile,filesize($end_arquivo)) . "</pre><br\>\n";
    //D fclose($myfile);

    // /var/data/saw_moodle-data/temp/...
    $application_xml = @simplexml_load_file($CFG->dataroot . '/temp/' . 'ilm-application.xml', null, LIBXML_NOCDATA); // PHP 'simplexml_load_file(.)' interprets an XML file into an object 
    //D echo "locallib.php!import_ilm(itemid): itemid=" . $itemid . "<br/>\napplication_xml="; print_r($application_xml); echo "<br/>-----<br/>\n"; //2023/05/08
    // $application_xml->description->asXML()

    // Verifica se o pacote possui o XML
    if (!$application_xml) {
      //D- echo "locallib.php!import_ilm(itemid): erro !application_xml<br/>\n"; //D-
      print($OUTPUT->notification(get_string('error_xml_ilm', 'iassign'), 'notifyproblem'));
      return;
    } else { // if (!$application_xml)
      //D- echo "locallib.php!import_ilm(itemid): NAO entrou no !application_xml<br/>\n"; //D-
      $missing = "";
      if (!isset($application_xml->name)) {
        $missing .= "name"; // {iassign_ilm}.name has at most 20 characters
        }
      if (!isset($application_xml->version)) {
        $missing .= ", version";
        }
      if (!isset($application_xml->type)) {
        $missing .= ", type"; // {iassign_ilm}.type must be "Java" or "HTML5"
        }
      if (!isset($application_xml->extension)) {
        $missing .= ", extension";
        }
      if (!isset($application_xml->file_jar)) {
        $missing .= ", file_jar"; // {iassign_ilm}.file_jar the base name directory under ./mod/iassign/ilm/<iLM name>/<iLM version>/<base name dir>
        }
      if (!isset($application_xml->file_class)) {
        $missing .= ", file_class"; // {iassign_ilm}.file_class is the iLM starting point (to JS could be "index.html", to JAR like "IGeomApplet.class")
        }
      if (!isset($application_xml->width)) {
        $missing .= ", width";
        }
      if (!isset($application_xml->height)) {
        $missing .= ", height";
        }
      if (!isset($application_xml->evaluate)) {
        $missing .= ", evaluate";
        }
      if (strlen($missing) > 2) {
        print($OUTPUT->notification(get_string('error_xml_missing', 'iassign') . $missing . ".", 'notifyproblem'));
        return;
        }
      } // else if (!$application_xml)

    // Get the iLM type and calls the correct handler in ./mod/iassign/ilm_handlers/
    // From the IPZ package, 'import_ilm(.)' will create the directory ./mod/iassign/ilm/<iLM name>/<iLM version>/<base_name>
    // and using files and meta-data in 'ilm-application.xml' will insert iLM data into {iassign_ilm} table
    $typec = strtolower($application_xml->type);
    //D- echo "locallib.php!import_ilm(itemid): typec=" . $typec . "<br/>\napplication_xml=|"; print_r($application_xml); echo "|<br/>\n"; exit;

    require_once 'ilm_handlers/' . $typec . '.php';

    $typec::import_ilm($itemid, $files_extract, $application_xml, $contextuser, $fs); // ./mod/iassign/ilm_handlers/html5.php: insert in table {iassign_ilm}
    } // static function import_ilm($itemid)


  /// Function for list iLM defaults
  //  @return string Return an string with a table of iLM
  static function list_ilm () {
    global $DB, $OUTPUT;

    $iassign_ilm = $DB->get_records('iassign_ilm', array("enable" => 1));

    $str = "\n";
    $str .= '<table id="outlinetable" cellpadding="5" width="100%" >' . "\n";
    $str .= '<tr><td align=right><input type=button value="' . get_string('close', 'iassign') . '"  onclick="javascript:window.close ();"></td></tr>' . "\n";

    if ($iassign_ilm) {
      foreach ($iassign_ilm as $ilm) {
        $url_view = new moodle_url('/mod/iassign/settings_ilm.php', array('action' => 'view', 'ilm_id' => $ilm->id));
        $link_view = $OUTPUT->action_link($url_view, iassign_icons::insert('view_ilm') . ' ' . get_string('read_more', 'iassign'));

        $str .= '<tr><td>';
        $str .= '<table class="generaltable boxaligncenter" width="100%">';
        $str .= '<tr>';
        $str .= '<td class=\'cell c0 actvity\' width=40%><strong>' . get_string('name_ilm', 'iassign') . ':</strong> ' . $ilm->name . '</td>' . "\n";
        $str .= '<td><strong>' . get_string('version_ilm', 'iassign') . ':</strong> ' . $ilm->version . '</td>' . "\n";
        $str .= '<td align=right>' . $link_view . '</td>' . "\n";
        $str .= '</tr>';
        $str .= '<tr><td colspan=3>' . iassign_language::get_description_lang(current_language(), $ilm->description) . '</td></tr>';
        $str .= '<tr><td colspan=3><a href="' . $ilm->url . '">' . $ilm->url . '</a></td></tr>';
        $str .= "</table>\n";
        $str .= "</td></tr>\n";
        }
      }
    $str .= "</table>\n";
    return $str;
    }


  /// Function to download and install an upgrade of some iLM
  //  @calledby settings_ilm.php: $ilm = ilm_settings::upgrade_ilm($ilm_id);
  //  @param int $ilm_id Id of iLM
  static function upgrade_ilm ($ilm_id) {
    global $DB, $CFG, $USER;

    $iassign_ilm = $DB->get_record('iassign_ilm', array('id' => $ilm_id));

    $upgrade_file = $iassign_ilm->url . 'ilm-upgrade_' . strtolower($iassign_ilm->name) . '.xml';

    $update_xml = @simplexml_load_file($upgrade_file, null, LIBXML_NOCDATA);

    $result = file_put_contents($CFG->dataroot . '/temp/' . $update_xml->file, fopen($iassign_ilm->url . $update_xml->file, 'r'));
    //D echo "locallib.php!upgrade_ilm(" . $ilm_id . "): aqui!"; // exit; //2023
    // class ilm_settings : function build_ilm_tags ($ilm_id, $options=array()): 6545/9615 ; $typec::build_ilm_tags(...)

    if (!$result)
      print_error('error_upgrade_ilm', 'iassign');
    else {
      $zip_filename = $CFG->dataroot . '/temp/' . $update_xml->file;
      $extension = explode(".", $zip_filename);
      if ($extension[count($extension) - 1] != 'ipz') {
        print($OUTPUT->notification(get_string('error_upload_ilm', 'iassign'), 'notifyproblem'));
        die;
        }
      $zip = new zip_packer();
      $fs = get_file_storage(); // Get reference to all files in Moodle data
      $contextuser = context_user::instance($USER->id);
      $files_extract = $zip->extract_to_pathname($zip_filename, $CFG->dataroot . '/temp/');

      $application_xml = @simplexml_load_file($CFG->dataroot . '/temp/' . 'ilm-application.xml', null, LIBXML_NOCDATA);
      $description_str = htmlentities(str_replace(array('<description>', '</description>'), array('', ''), $application_xml->description->asXML()));

      // Attention: we have another function with the same name in "ilm_handers/", there
      // to create directory "./mod/iassign/ilm/<iLM name>/<iLM version>/<base_name>"
      // The return is array() with {files}.id of each created file
      $file_jar = self::save_ilm_by_xml($ilm_id, $application_xml, $files_extract); //2023 added param. "$ilm_id" to {files}.itemid <- $ilm_id

      if (file_exists($zip_filename)) // unzip the IPZ package
        unlink($zip_filename);

      if (empty($file_jar)) {
        $msg_error = get_string('error_add_ilm', 'iassign') . "<br/>In upgrade_ilm(" . $ilm_id . ")<br/>\n";
        print_error($msg_error);
        //xx print_error('error_add_ilm', 'iassign');
        }
      else {
        //TODO: where is the update('iassign_ilm', $newentry)???
        $time_now = time();
        $newentry = new stdClass();
        $newentry->name = (String) $application_xml->name;
        $newentry->version = (String) $application_xml->version;
        $newentry->url = (String) $application_xml->url;
        $newentry->description = $description_str;
        $newentry->extension = strtolower((String) $application_xml->extension);
        $newentry->file_jar = implode(",", $file_jar); //TODO Must be reviewed, {iassign_ilm}.file_jar must be directory name
        $newentry->file_class = (String) $application_xml->file_class;
        $newentry->width = (String) $application_xml->width;
        $newentry->height = (String) $application_xml->height;
        $newentry->enable = 0;
        $newentry->timemodified = $time_now;
        $newentry->author = $USER->id;
        $newentry->timecreated = $time_now;
        $newentry->evaluate = (String) $application_xml->evaluate;
        $newentry->parent = $ilm_id;
        }
      }

    return $iassign_ilm->id;
    } // static function upgrade_ilm($ilm_id)


  //MOOC2014 -- inicio
  //TODO: REVIEW
  // static function confirm_move_iassign($ilmid, $ilm_parent)
  // static function move_iassign($ilm_id)
  //MOOC2014 -- final


  /// Function for list iLM versions with all informations, under "admin" plugin view: .../mod/iassign/settings_ilm.php?action=view&ilm_id=".$ilmid."&from=admin
  //  @return string Return an string with a table of iLM
  static function view_ilm ($ilmid, $from) {
    global $DB;
    $iassign_ilm = $DB->get_record('iassign_ilm', array('id' => $ilmid));

    // Get the iLM type and calls its correspondent handler ('Java' or 'HTML5')
    $typec = strtolower($iassign_ilm->type);
    require_once 'ilm_handlers/' . $typec . '.php';
    $return_html_str = $typec::view_ilm($ilmid, $from); // get the string with HTML code to present the iLM
    return $return_html_str;
    }


  /// Function for get form variables for add, edit, or copy iLM params
  //  @param int $ilm_param_id Id of iLM param
  //  @param string $action String with the action
  //  @return object Return an object with forms variables
  static function add_edit_copy_param ($ilm_param_id, $action) {
    global $DB;

    require_once('params_form.php');
    $iassign_ilm_config = $DB->get_record('iassign_ilm_config', array('id' => $ilm_param_id));
    $param = new stdClass();
    $param->action = $action;
    $param->ilm_param_id = $ilm_param_id;

    $type = optional_param('type', NULL, PARAM_TEXT); //MOOC2014
    if ($type == NULL && $iassign_ilm_config) //MOOC2014
      $type = $iassign_ilm_config->param_type;

    if ($action == 'add') {
      $param->title = get_string('add_ilm', 'iassign');
      $param->iassign_ilmid = $ilm_param_id;
      $param->param_name = "";
      $param->param_value = "";
      $param->description = "";
      $param->visible = 1;
      }
    elseif ($action == 'edit') {
      if ($iassign_ilm_config) {
        $param->title = get_string('edit_ilm', 'iassign');
        $param->id = $iassign_ilm_config->id;
        $param->iassign_ilmid = $iassign_ilm_config->iassign_ilmid;

        $param->param_type = $type; //MOOC2014
        $param->param_name = $iassign_ilm_config->param_name;
        if ($type != 'choice' && $type != 'multiple') //MOOC2014
          $param->param_value = $iassign_ilm_config->param_value;
        else //MOOC2014
          $param->param_value = str_replace(", ", "\n", $iassign_ilm_config->param_value); //MOOC2014

        $param->param_value = $iassign_ilm_config->param_value;
        $param->description = $iassign_ilm_config->description;
        $param->visible = $iassign_ilm_config->visible;
        }
      }
    elseif ($action == 'copy') {
      if ($iassign_ilm_config) {
        $param->title = get_string('copy_ilm', 'iassign');
        $param->iassign_ilmid = $iassign_ilm_config->iassign_ilmid;
        $param->param_type = $type; //MOOC2014
        $param->param_name = $iassign_ilm_config->param_name;

        if ($type != 'choice' && $type != 'multiple') //MOOC2014
          $param->param_value = $iassign_ilm_config->param_value;
        else //MOOC2014
          $param->param_value = str_replace(", ", "\n", $iassign_ilm_config->param_value); //MOOC2014

        $param->description = $iassign_ilm_config->description;
        $param->visible = $iassign_ilm_config->visible;
        }
      }
    return $param;
    } // static function add_edit_copy_param($ilm_param_id, $action)


  /// Function for change visibility of iLM param
  //  @param int $ilm_param_id Id of iLM param
  //  @param int $status Indicator of change vibility (0 = hide, 1 = show)
  static function visible_param ($ilm_param_id, $status) {
    global $DB, $CFG;
    if ($status == 0)
      $visible = 1;
    else
      $visible = 0;
    $newentry = new stdClass();
    $newentry->id = $ilm_param_id;
    $newentry->visible = $visible;

    if (!$DB->update_record("iassign_ilm_config", $newentry))
      error(get_string('error_edit_param', 'iassign'));
    }


  /// Function for save in database an new iLM param
  //  @param object $param An object with iLM params
  static function add_param ($param) {
    global $DB;

    $newentry = new stdClass();
    $newentry->iassign_ilmid = $param->iassign_ilmid;
    //MOOC2014 $newentry->param_name = $param->param_name;
    $newentry->param_type = $param->param_type; //MOOC2014
    $newentry->param_name = iassign_utils::format_filename($param->param_name); //MOOC2014
    if ($newentry->param_type != 'choice' && $newentry->param_type != 'multiple') //MOOC2014
      $newentry->param_value = $param->param_value;
    else //MOOC2014
      $newentry->param_value = str_replace("\r\n", ", ", $param->param_value); //MOOC2014

    $newentry->description = $param->description;
    $newentry->visible = $param->visible;

    $newentry->id = $DB->insert_record("iassign_ilm_config", $newentry);
    if (!$newentry->id) {
      print_error('error_add_param', 'iassign');
      }
    }


  /// Function for save in database a iLM param edit
  //  @param object $param An object with iLM params
  static function edit_param ($param) {

    global $DB;

    $updentry = new stdClass();
    $updentry->id = $param->id;
    $updentry->iassign_ilmid = $param->iassign_ilmid;
    $updentry->param_type = $param->param_type; //MOOC2014
    // $updentry->param_name = $param->param_name;
    $newentry->param_name = iassign_utils::format_filename($param->param_name); //MOOC2014

    if ($updentry->param_type != 'choice' && $updentry->param_type != 'multiple') //MOOC2014
      $updentry->param_value = $param->param_value;
    else //MOOC2014
      $updentry->param_value = str_replace("\r\n", ", ", $param->param_value); //MOOC2014

    $updentry->description = $param->description;
    $updentry->visible = $param->visible;

    if (!$DB->update_record("iassign_ilm_config", $updentry)) {
      error(get_string('error_edit_param', 'iassign'));
      }
    }


  /// Function for save in database a iLM param copy
  //  @param object $param An object with iLM params
  static function copy_param ($param) {
    global $DB;

    $newentry = new stdClass();
    $newentry->iassign_ilmid = $param->iassign_ilmid;
    //$newentry->param_name = $param->param_name;
    $newentry->param_name = iassign_utils::format_filename($param->param_name); //MOOC2014

    if ($newentry->param_type != 'choice' && $newentry->param_type != 'multiple') //MOOC2014
      $newentry->param_value = $param->param_value;
    else //MOOC2014
      $newentry->param_value = str_replace("\r\n", ", ", $param->param_value); //MOOC2014

    $newentry->description = $param->description;
    $newentry->visible = $param->visible;

    $newentry->id = $DB->insert_record("iassign_ilm_config", $newentry);
    if (!$newentry->id) {
      print_error('error_add_param', 'iassign');
      }
    }


  /// Function for delete iLM param of database
  //  @param int $param_id Id of iLM param
  static function delete_param ($param_id) {
    global $DB;

    if (!$DB->delete_records("iassign_ilm_config", array('id' => $param_id))) {
      print_error('error_delete_param', 'iassign');
      }
    }

  } // class ilm_settings


/// Class for manage iLM files (editor).
class ilm_manager {

  var $id; // course id
  var $url;
  var $from;

  /// Constructor for the base ilm_manager class
  //  3.1 update PHP 7.0 compatibility for all moodle versions
  //D public function ilm_manager($id, $url, $from) { self::__construct($iassign, $cm, $course); }
  function __construct ($id, $url, $from) {
    $this->id = $id; // course id
    $this->url = $url;
    $this->from = $from;
    }

  /// Function to get iAssign content file in Moodle data (exercise)
  //  @calledby x function preview_ilm($iassign_ilm) :
  //  @calledby ilm_manager.php : with 'action=get' in '$ilm_manager_instance->get_file_ilm($ilmid, $fileid)'
  function get_file_ilm () {
    $ilmid = optional_param('ilmid', NULL, PARAM_INT);
    $fileid = optional_param('fileid', NULL, PARAM_INT);
    //$filename = optional_param('filename', NULL, PARAM_TEXT);
    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $md_file = $fs->get_file_by_id($fileid);
    $ilm_content_file = $md_file->get_content();
    return $ilm_content_file;
    }


  /// Function for creating a new file from the iLM online editor
  //  @calledby ilm_manager.php: case 'new': $ilm_manager_instance->ilm_editor_new();
  function ilm_editor_new () {
    global $CFG, $DB, $OUTPUT, $PAGE;

    $ilmid = optional_param('ilmid', NULL, PARAM_INT); // iAssign ID
    $dirid = optional_param('dirid', NULL, PARAM_INT);
    $iassign = $DB->get_record("iassign_ilm", array("id" => $ilmid));
    $context = context_course::instance($this->id);
    $returnurl = $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=$this->id&dirid=$dirid&ilmid=$ilmid";

    // verify if the JAR file $PAGE->set_course($course); is registered in DB (table '*_iassign_ilm')
    if (!$iassign) {
      print $OUTPUT->notification(get_string('error_confirms_ilm', 'iassign'), 'notifysuccess');
      die();
      }

    $temp = explode(",", $iassign->extension);
    $extension = $temp[0]; // default extension for this iLM
    //2016/02/16: IMPORTANTE trocar formatador para "nao formatado", pois esta destruindo o nome do arquivo (e.g. limpa brancos)
    //2016/02/16: $stringArchiveContent = optional_param('iLM_PARAM_ArchiveContent', NULL, PARAM_ALPHANUMEXT);
    $stringArchiveContent = optional_param('iLM_PARAM_ArchiveContent', NULL, PARAM_RAW); // iLM 2
    // $stringArchiveContent = optional_param('iLM_PARAM_ArchiveContent', NULL, PARAM_RAW); // iLM 1

    if ($stringArchiveContent != NULL) {
      // $stringArchiveContent = $_POST['iLM_PARAM_ArchiveContent'];
      //2016/02/16: IMPORTANTE trocar formatador para "nao formatado", pois esta destruindo o conteudo do arquivo
      //2016/02/16: $filename = optional_param('filename', NULL, PARAM_ALPHANUMEXT);
      $filename = optional_param('filename', NULL, PARAM_RAW);

      //Leo $filename = iassign_utils::format_filename($filename); //
      $filename = iassign_utils::filter_filename_extension($filename, $extension); // here: class iassign_utils

      //2019/02 It is not necessary, since 'iassign_utils::filter_filename_extension(...)' does all the work!
      //2019/02 $arrayfilename = explode(".", $filename);
      //2019/02 // if (count($arrayfilename) == 1) $filename = $arrayfilename[0] . '.' . $extension;
      //2019/02 $count_dots = count($arrayfilename);
      //2019/02 if ($count_dots > 0) { // at least one dot mark
      //2019/02   $last_name = $arrayfilename[$count_dots - 1];
      //2019/02   if ($last_name != $extension)
      //2019/02     $filename = $filename . '.' . $extension;
      //2019/02   }
      //2019/02 else { // no extension...
      //2019/02   $filename = $filename . '.' . $extension;
      //2019/02   }

      // Store the name of recent created file
      $_SESSION['file_name'] = $filename;

      //$this->write_file_iassign($string, $filename);

      // Register and (by JS.alet(.)) inform the user about the result (and the final name of the file)
      $this->write_file_iassign($stringArchiveContent, $filename); // class ilm_manager - inform the user about the file insertion result

      die();

      }
    else { // if ($stringArchiveContent != NULL)
      // iLM On-line editor
      if ($extension == "html" || $extension == "ivph" || strtolower($iassign->type) == 'html5') { // if iLM is HTML5 is inside a frame
        $str_get_iLM = "window.frames.iLM";
        $str_submitbutton_name = "javascript:window.submit_iLM_Answer()";
        }
      else { // otherwise it is JAR named 'iLM'
        $str_get_iLM = "document.iLM";
        $str_submitbutton_name = "submit_iLM_Answer()"; // to call 'submit_iLM_Answer()'
        }

      $fs = get_file_storage(); // Get reference to all files in Moodle data
      $files = $fs->get_area_files($context->id, 'mod_iassign', 'activity');
      $files_array = '';
      foreach ($files as $value) {
        if ($value->get_filename() != ".")
            $files_array .= "'" . $value->get_filename() . "',";
        }
      $files_array .= "''";
      $file = null;
      $ia_content = "";
      $filename = "";
      $error_files_exists = get_string('error_file_exists', 'iassign');

      $output = "<script type='text/javascript'>
   //<![CDATA[
   //D alert('locallib.php: ilm_editor_new');

   function submit_iLM_Answer () {
     var docFormOnLineEditor = document.formEnvio;
     var resposta_exerc = new Array(3);
     var valor_resposta = new Array(3);
     var sessao = new Array(3);
     var doc_iLM = " . $str_get_iLM . "; // 'window.frames.iLM' or 'document.iLM'
     resposta_exerc[0] = doc_iLM.getAnswer();
     valor_resposta[0] = doc_iLM.getEvaluation();
     docFormOnLineEditor.iLM_PARAM_ActivityEvaluation.value = valor_resposta[0];
     docFormOnLineEditor.iLM_PARAM_ArchiveContent.value = resposta_exerc[0];
     var files = new Array(" . $files_array . ");
     var filename = docFormOnLineEditor.filename.value+'.'+'" . $extension . "';
     if (docFormOnLineEditor.filename.value=='') {
      // ERROR: the file name is empty
      alert('" . get_string('error_file_null_iassign', 'iassign') . "');
      return false;
      }
    // }

    for (i=0; i<files.length; i++) {
      if (files[i]==docFormOnLineEditor.filename.value || files[i]==filename) {
        alert('" . $error_files_exists . "');
        return false;
        }
      }
    docFormOnLineEditor.submit();
    return true;
    }
   //]]>
</script>\n";

      $output .= "
   <form name='formEnvio' id='formEnvio' method='post' enctype='multipart/form-data'>\n";
      $output .= $OUTPUT->box_start();

      // Put text "File name" and the corresponding "input" to enter the file nama: "File name: [   ]"
      $output .= "
   <table width='100%' cellpadding='20'>
   <tr><td>" . get_string('label_file_iassign', 'iassign') . " <input type='text' name='filename' size=50/>

     <input type=button value='" . get_string('label_write_iassign', 'iassign') . "' title='' onclick='" . $str_submitbutton_name . ";'/></td>

   </tr>
   </table>\n";

      $output .= $OUTPUT->box_end();
      $output .= "<center>\n";

      // Prepare tag to load the iLM. In case of JAR it will prepare the tag "applet". In case of HTML5 will prepare an "iframe".
      // Since it is the activity file, it is not necessary to use 'ilm_security'
      $output_ilm = ilm_settings::build_ilm_tags($ilmid, array("type" => "editor_new", "notSEND" => "true"));
      $output .= $output_ilm;

      $output .= "   <input type='hidden' name='iLM_PARAM_ArchiveContent' value='" . $ia_content . "'>
  <input type='hidden' name='iLM_PARAM_ActivityEvaluation'>
  </center>
  </form>\n";

      $title = get_string('title_editor_iassign', 'iassign') . " - " . $iassign->name . " " . $iassign->version; //MOOC2014
      $PAGE->navbar->add($title);
      $PAGE->set_title($title);
      $PAGE->set_heading($title); // insert title above the navigation bar
      print $OUTPUT->header();
      //print $OUTPUT->heading("  " . $title); // insert title below the navigation bar

      print $output;

      print $OUTPUT->footer();
      }
    die();
    } // function ilm_editor_new()


  /// Function for editing an file in online editor
  //  @calledby ilm_manager.php: case 'update': $ilm_manager_instance->ilm_editor_update($security['content'], $security['token'], $security['secure_id']);
  function ilm_editor_update ($filename, $content_file, $token, $secure_id) {
    global $CFG, $DB, $OUTPUT, $PAGE;

    $ilmid = optional_param('ilmid', NULL, PARAM_INT); // iAssign ID
    $dirid = optional_param('dirid', NULL, PARAM_INT);
    $fileid = optional_param('fileid', NULL, PARAM_TEXT);
    $iassign = $DB->get_record("iassign_ilm", array("id" => $ilmid));
    $returnurl = $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=$this->id&dirid=$dirid&ilmid=$ilmid";

    // If iLM is not registered, error!
    if (!$iassign) {
      print $OUTPUT->notification(get_string('error_confirms_ilm', 'iassign'), 'notifyproblem');
      die;
      }

    //2017/03/12 //QUARANTINE agora vindo de 'ilm_manager.php' via os 3 parametros
    //2017/03/12 $fs = get_file_storage(); // Get reference to all files in Moodle data // from Moodle data
    //2017/03/12 $filename = ''; $end_file = ''; $file = $fs->get_file_by_id($fileid); if ($file) $filename = iassign_utils::format_filename($file->get_filename());
    //D echo "locallib.php: ilm_editor_update(): filename=$filename<br/>\n";
    //2016/02/16: IMPORTANTE trocar formatador para "nao formatado", pois esta destruindo o conteudo do arquivo
    //2016/02/16: $stringArchiveContent = optional_param('iLM_PARAM_ArchiveContent', NULL, PARAM_ALPHANUMEXT);
    $stringArchiveContent = optional_param('iLM_PARAM_ArchiveContent', NULL, PARAM_RAW);

    //D echo "stringArchiveContent:$stringArchiveContent<br/>\n";

    if ($stringArchiveContent != NULL) {
      $this->update_file_iassign($stringArchiveContent, $filename, $fileid);
      die();
      } else { // if ($stringArchiveContent != NULL)
      $end_file = '';
      if ($content_file) {
        // 2017/03/12 $token=''; $view=-1; $end_file = $CFG->wwwroot . '/mod/iassign/ilm_security.php?id=' . $fileid . '&token=' . $token . '&view=' . $view; // need full path...
        $end_file = $CFG->wwwroot . '/mod/iassign/ilm_security.php?id=' . $secure_id . '&action=update&token=' . $token . '&view=0'; // need full path...
        }

      $temp = explode(",", $iassign->extension);
      $extension = $temp[0]; // default extension for this iLM

      if ($extension == "html" || $extension == "ivph" || strtolower($iassign->type) == 'html5') { // if iLM is HTML5 is inside a frame
        $str_get_iLM = "window.frames.iLM";
        $str_submitbutton_name = "javascript:window.submit_iLM_Answer()";
        } else { // otherwise it is JAR named 'iLM'
        $str_get_iLM = "document.iLM";
        $str_submitbutton_name = "submit_iLM_Answer()"; // to call 'submit_iLM_Answer()'
        }

      $output = "<script type='text/javascript'>
   //<![CDATA[
   function submit_iLM_Answer () {
     var docFormEditor = document.formEnvio;
     var activityAnswer; // to get activity answer (text)
     var activityValue; // to get activity answer value (float)
     var doc_iLM = " . $str_get_iLM . "; // 'window.frames.iLM' or 'document.iLM'

     //activityAnswer = doc_iLM.getAnswer();
     //activityValue = doc_iLM.getEvaluation();
     activityAnswer = " . $str_get_iLM . ".getAnswer();
     activityValue = " . $str_get_iLM . ".getEvaluation();

     docFormEditor.iLM_PARAM_ActivityEvaluation.value = activityValue;
     docFormEditor.iLM_PARAM_ArchiveContent.value = activityAnswer;

     docFormEditor.submit();
     return true;
     }
  //]]>
</script>\n";
      // 2016/02/16: NOT necessary, since it is teacher editing (perhaps he only make an example as exercise)
      // if (activityAnswer == -1) { // E.g. in iGeom it is possible to turn an example in exercise
      //   alert('" . get_string('error_null_iassign', 'iassign') . "'); // ERRO: O exercício esta vazio ou não foi alterado
      //   return true;
      //   }

      $output .= "
 <form name='formEnvio' id='formEnvio' method='post' enctype='multipart/form-data'>\n";
      $output .= $OUTPUT->box_start();
      $output .= "
  <table width='100%' cellpadding='20'>
  <tr><td width='75%'>" . get_string('label_file_iassign', 'iassign') . "<b>$filename</b></td>
  <td align='right' width='25%'><input type=button value='" . get_string('label_write_iassign', 'iassign') . "' title='' onclick='submit_iLM_Answer();'/>
  <input type='hidden' name='filename' value='$filename'/></td>
  <td>
    <input type=button value='" . get_string('close', 'iassign') . "' title='' onclick='javascript:window.location = \"$returnurl\";'/></td>
  </tr>
  </table>\n";

      $output .= $OUTPUT->box_end();
      $output .= " <center>\n";

      // Since it is the activity file, it is not necessary to use 'ilm_security' (id_iLM_security)
      $output .= ilm_settings::build_ilm_tags($ilmid, array("type" => "editor_update", "notSEND" => "false", "Proposition" => $end_file));

      $output .= " <input type='hidden' name='iLM_PARAM_ArchiveContent' value=''>
 <input type='hidden' name='iLM_PARAM_ActivityEvaluation' value=''>
 </center>
 </form>\n";

      $title = get_string('title_editor_iassign', 'iassign') . " - " . $iassign->name . " " . $iassign->version; //MOOC2014
      $PAGE->navbar->add($title);
      $PAGE->set_title($title);
      $PAGE->set_heading($title); // insert title above the navigation bar
      print $OUTPUT->header();
      //print $OUTPUT->heading("  " . $title); // insert title below the navigation bar
      print $output;
      print $OUTPUT->footer();
      } // else if ($stringArchiveContent != NULL)
    die();
    } // function ilm_editor_update()


  /// Function for write iAssign file in Moodle data (exercise)
  //  @calledby ilm_editor_new(): $this->write_file_iassign($stringArchiveContent, $filename);
  //  @param string $stringArchiveContent Content of iassign file
  //  @param string $filename Filename of iassign file
  function write_file_iassign ($stringArchiveContent, $filename) {
    global $USER;

    $ilmid = optional_param('ilmid', NULL, PARAM_INT);
    $context = context_course::instance($this->id);
    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $dirid = $this->get_dir_ilm('dirid');
    $dir = $fs->get_file_by_id($dirid);

    $fileinfo = array('contextid' => $context->id, // ID of course
      'component' => 'mod_iassign', // usually = table name
      'filearea' => 'activity', // usually = table name
      'itemid' => 0, // usually = ID of row in table
      'filepath' => '/', // any path beginning and ending with '/'
      'userid' => $USER->id,
      'author' => $USER->firstname . ' ' . $USER->lastname, 'license' => 'allrightsreserved', // allrightsreserved
      'filename' => $filename); // any filename

    // Create file containing text. '$stringArchiveContent'
    //TODO Verificar se apenas esta' inserindo nova referencia em 'files' (NAO deve duplicar no Moodle Data)
    $file_course = $fs->create_file_from_string($fileinfo, $stringArchiveContent);

    // sucess_write -> 'Interactive assignment successfully registered under the name: '
    // 'Tarea interactiva registrada exitosamente bajo el nombre:'
    // 'Activité interactive enregistrée avec succès sous le nom:';
    // 'Atividade interativa gravada com sucesso sob o nome:'
    $output = "
    <script type='text/javascript'>
     //<![CDATA[
     alert('" . get_string('sucess_write', 'iassign') . " " . $filename . "');
     window.location='" . $this->url . "&dirid=$dirid&ilmid=$ilmid';
     //]]>
     </script>";
    print $output;
    die();
    }


  /// Function for write iAssign file in Moodle data (exercise)
  //  @param string $stringArchiveContent Content of iassign file
  //  @param string $filename Filename of iassign file
  //  @param int $itemid Itemid of iassign file
  function update_file_iassign ($stringArchiveContent, $filename, $fileid) {
    global $OUTPUT, $USER;

    $ilmid = optional_param('ilmid', NULL, PARAM_INT);
    $context = context_course::instance($this->id);
    $dirid = $this->get_dir_ilm('dirid');

    if ($stringArchiveContent != (-1)) {
      $fs = get_file_storage(); // Get reference to all files in Moodle data
      $file = $fs->get_file_by_id($fileid);
      if (!$file) {
        print $OUTPUT->notification(get_string('error_view_ilm', 'iassign'), 'notifyproblem');
        die;
        }

      $fileinfo = array('contextid' => $context->id, // ID of context
        'component' => 'mod_iassign', // usually = table name
        'filearea' => 'activity', // usually = table name
        'itemid' => 0, // usually = ID of row in table
        'filepath' => $file->get_filepath(), // any path beginning and ending with '/'
        'userid' => $USER->id,
        'author' => $USER->firstname . ' ' . $USER->lastname,
        'license' => 'allrightsreserved', // allrightsreserved
        'timecreated' => $file->get_timecreated(),
        'filename' => $file->get_filename()); // any filename
      $file->delete();

      //TODO Verificar se apenas esta' inserindo nova referencia em 'files' (NAO deve duplicar no Moodle Data)
      $file_course = $fs->create_file_from_string($fileinfo, $stringArchiveContent); //$string

      }
    $output = "<script type='text/javascript'>
     //<![CDATA[
     alert('" . get_string('sucess_update', 'iassign') . "');\n
     window.location='" . $this->url . "&dirid=$dirid&ilmid=$ilmid';
     //]]>
     </script>";
    print $output;
    die();
    }

  /// Function for create an tag for iAssign filter
  //  @calledby function preview_ilm($iassign_ilm) : $tag_filter = $this->tag_ilm($fileid);
  //  @calledby function tinymce_ilm($fileid) : $tag_filter = $this->tag_ilm($fileid);
  //  @calledby function editor_ilm($fileid, $editor) : $tag_filter = $this->tag_ilm($fileid);
  //  @calledby function atto_ilm ($fileid) : $tag_filter = $this->tag_ilm($fileid);
  //  @param int $fileid Id of file
  //  @return string Return an string with an tag of iassign filter
  function tag_ilm ($fileid) {
    global $DB;

    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $width = '600';
    $height = '400';
    $file = $fs->get_file_by_id($fileid);
    $filetype = explode(".", $file->get_filename());
    $iassign_ilm = $DB->get_records('iassign_ilm', array("enable" => 1, "parent" => 0));
    foreach ($iassign_ilm as $value) {
      $extensions = explode(",", $value->extension);
      if (in_array($filetype[1], $extensions)) {
        $width = $value->width;
        $height = $value->height;
        }
      }
    return("<p>&lt;ia toolbar=disable width=$width height=$height &gt;$fileid&lt;/ia&gt;</p>");
    }

  /// Function for delete iAssign file in Moodle data (exercise)
  function delete_file_ilm () {
    $ilmid = optional_param('ilmid', NULL, PARAM_INT);

    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $fileid = optional_param('fileid', NULL, PARAM_RAW);
    $file = $fs->get_file_by_id($fileid);
    if ($file)
      $file->delete();
    redirect(new moodle_url($this->url . '&dirid=' . $this->get_dir_ilm('dirid') . '&ilmid=' . $ilmid));
    die();
    }

  /// Function for delete selected iAssign file in Moodle data (exercise)
  function delete_selected_ilm () {
    $ilmid = optional_param('ilmid', NULL, PARAM_INT);
    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $context = context_course::instance($this->id);
    $files_id = explode(",", optional_param('files_id', '', PARAM_TEXT));
    $dirid = $this->get_dir_ilm('dirid');
    foreach ($files_id as $file_id) {
      $file = $fs->get_file_by_id($file_id);
      if ($file) {
        if (!$file->is_directory())
            $file->delete();
        else {
            $files_delete = $fs->get_directory_files($context->id, 'mod_iassign', 'activity', 0, $file->get_filepath(), true, true);
            foreach ($files_delete as $value)
                $value->delete();
            $file->delete();
          }
        }
      }
    redirect(new moodle_url($this->url . '&dirid=' . $dirid . '&ilmid=' . $ilmid));
    die();
    }


  /// Function for duplicate iAssign file from "online" edition
  //  @callby JavaScript function 'duplicate_ilm(ilmid, filename, fileid)' bellow
  function duplicate_file_ilm () {
    global $USER, $COURSE;

    $ilmid = optional_param('ilmid', NULL, PARAM_INT);
    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $fileid = optional_param('fileid', NULL, PARAM_INT);
    $filename = optional_param('filename', NULL, PARAM_RAW);

    $file = $fs->get_file_by_id($fileid);
    $context = context_course::instance($this->id);

    $fileinfo = array(
      'contextid' => $context->id, // ID of context
      'component' => 'mod_iassign', // usually = table name
      'filearea' => 'activity', // usually = table name
      'itemid' => 0, // usually = ID of row in table
      'filepath' => $this->get_dir_ilm('dir_base'), // any path beginning and ending in /
      'userid' => $USER->id,
      'author' => $USER->firstname . ' ' . $USER->lastname, 'license' => 'allrightsreserved', // allrightsreserved
      'timecreated' => $file->get_timecreated(), 'filename' => $filename); // any filename

    $newfile_fs = $fs->create_file_from_string($fileinfo, $file->get_content());

    redirect(new moodle_url($this->url . "&dirid=" . $this->get_dir_ilm('dirid') . "&ilmid=" . $ilmid));
    die();
    }


  /// Function for rename iAssign file
  function rename_file_ilm () {
    $ilmid = optional_param('ilmid', NULL, PARAM_INT);
    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $fileid = optional_param('fileid', NULL, PARAM_INT);
    $filename = optional_param('filename', NULL, PARAM_TEXT);

    $file = $fs->get_file_by_id($fileid);

    $file->rename($this->get_dir_ilm('dir_base'), $filename);

    //MOOC2014 redirect(new moodle_url($this->url . "&dirid=" . $dir_parent . "&ilmid=$ilmid"));
    redirect(new moodle_url($this->url . '&dirid=' . $this->get_dir_ilm('dirid') . '&ilmid=' . $ilmid));
    die();
    }


  /// Function for get iassign file for iassign form
  function add_ilm () {
    $fileid = optional_param('fileid', NULL, PARAM_INT);
    $filename = optional_param('filename', NULL, PARAM_TEXT);
    $output = "
  <script type='text/javascript'>
    //<![CDATA[
    var iassign_file_link = window.opener.document.getElementById('iassign_file_link');
    iassign_file_link.innerHTML = '$filename';
    window.opener.document.forms['mform1'].file.value='$fileid';
    window.opener.document.forms['mform1'].filename.value='$filename';
    window.close();
    //]]>
  </script>";
    print $output;
    die();
    }


  /// Function for preview iAssign file from iAssign Repository (it uses iAssign filter)
  //  @see /iassign_filter/filter.php : function 'filter($text, array $options = array())' and exit (do not continue bellow)
  //  @see ilm_manager.php : $ilm_manager_instance->preview_ilm();
  function preview_ilm ($courseid, $iassign_ilm) {
    global $OUTPUT, $CFG, $USER;

    $fileid = optional_param('fileid', NULL, PARAM_TEXT);
    $title = get_string('modulename', 'iassign'); // iAssign
    print $OUTPUT->header();
    print $OUTPUT->box_start();

    $javascript = "
   <!-- iAssign preview iLM content / LInE - http://line.ime.usp.br -->
   <script type='text/javascript'>
    //<![CDATA[
    function submit_close () {
     window.opener.location.reload();
    window.close();
    }
    //]]>
   </script>\n";

    // Use iAssin filter to change "&lt;ia toolbar=disable width=800 height=600 &gt;45&lt;/ia&gt;"
    // to the complete "applet" tag
    // Version previous to: 2016/02/16: do not present menus' options - similar to the filter
    //TODO: E' melhor apresentar o iMA completo, sem usar o filtro com opcao "<param name='SOH_ADD' value='ADD'>"
    //TODO: Talvez seja melhor colocar opcao no filtro para evitar opcao 'ADD'!
    //TODO: Here reaches '/iassign_filter/filter.php : function 'filter($text, array $options = array())' with parameter 'originalformat=0' (in glossary is 1)
    //TODO: then, I changed '/iassign_filter/filter.php' (functions 'filter(...) to avoid 'SOH_ADD' if 'originalformat=0'
    $ilm_name = substr(strtolower($iassign_ilm->name), 0, 6);

    require_once($CFG->dirroot . '/mod/iassign/ilm_security.php');
    $content_or_id_from_ilm_security = $this->get_file_ilm();
    $timecreated = time();
    $token = md5($timecreated);
    $id_iLM_security = ilm_security::write_iLM_security($USER->id, $timecreated, -1, $content_or_id_from_ilm_security); // ./mod/iassign/ilm_security.php: insert in 'iassign_security'

    if (!isset($iassign_ilm->id))
      $iassign_ilm->id = -1; // avoid warning "Warning: Undefined property: stdClass::$id"
    $param = 'id=' . $id_iLM_security . '&ilmid=' . $iassign_ilm->id . '&token=' . $token . '&view=0';
    $url_file = $CFG->wwwroot . '/mod/iassign/ilm_security.php?action=preview&' . $param;
    $tag_filter_filtered = ilm_settings::build_ilm_tags($iassign_ilm->id, array("type" => "view", "notSEND" => "true", "Proposition" => $url_file)); // buil iLM tag (JAR or HTML5)

    $html = "
  <form name='formEnvio' id='formEnvio' method='post' enctype='multipart/form-data'>
  <table border='1'>
   <tr><td>
     <!-- iLM calls : begin -->\n" . $tag_filter_filtered . "\n
     <!-- iLM calls : end -->
     </td></tr>
  </table>
  <table>
   <tr>
     <td align='center'>
       <input type=button value='" . get_string('close', 'iassign') . "' title='' onclick='submit_close();' />
     </td></tr>
  </table>
  </form>\n";

    print $javascript . $html;
    print $OUTPUT->box_end();

    //NAO echo format_string($html); // Moodle 3.X
    die();
    } // function preview_ilm($courseid, $iassign_ilm)


  //r_ function preview_ilm ($iassign_ilm)
  //r_ /// Function for preview iassign file from iassign filter.
  //r_ function preview_ilm() {
  //r_   $fileid = optional_param('fileid', NULL, PARAM_TEXT);
  //r_   $tag_filter = $this->tag_ilm($fileid);
  //r_   $javascript = "<script type='text/javascript'>
  //r_   //<![CDATA[
  //r_   function submit_close() { window.opener.location.reload(); window.close(); }
  //r_   //]]>
  //r_   </script>";
  //r_   $html = "<html><head></head><body><form name='formEnvio' id='formEnvio' method='post' enctype='multipart/form-data'>
  //r_   <table border='1'><tr><td>$tag_filter</td></tr></table><table><tr><td align='center'><input type=button value='" . get_string('close', 'iassign') . "' title='' onclick='submit_close();'/></td></tr>
  //r_   </table></form></body></html>";
  //r_   echo $javascript . format_text($html);
  //r_   die;
  //r_   }


  /// Function for export an package (zip) of iassign files
  function export_files_ilm () {
    global $CFG;
    $context = context_course::instance($this->id);

    $files_id = explode(",", optional_param('files_id', '', PARAM_TEXT));

    $zip_filename = $CFG->dataroot . '/temp/backup-iassign-files-' . date("Ymd-Hi") . '.zip';
    $zip = new zip_archive();
    $zip->open($zip_filename);
    $fs = get_file_storage(); // Get reference to all files in Moodle data
    foreach ($files_id as $file_id) {
      $file = $fs->get_file_by_id($file_id);
      if (!$file->is_directory())
        $zip->add_file_from_string($file->get_filename(), $file->get_content());
      else {
        $zip->add_directory($file->get_filepath());
        $files_zip = $fs->get_directory_files($context->id, 'mod_iassign', 'activity', 0, $file->get_filepath(), true, true);
        foreach ($files_zip as $value) {
          if (!$value->is_directory())
            $zip->add_file_from_string($value->get_filepath() . $value->get_filename(), $value->get_content());
          else
            $zip->add_directory($value->get_filepath());
          }
        }
      }
    $zip->close();

    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private", false);
    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename=\"" . basename($zip_filename) . "\";");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . @filesize($zip_filename));
    set_time_limit(0);
    @readfile("$zip_filename") || die("File not found.");
    unlink($zip_filename);
    exit;
    }


  /// Function of execute a command in button editor tinymce
  //  @param int $fileid Id of file
  function tinymce_ilm ($fileid) {

    $tag_filter = $this->tag_ilm($fileid);

    $output = "<script type='text/javascript'>
  //<![CDATA[
     var tag_filter = '$tag_filter';
     if (window.opener.tinyMCE.execCommand('mceiAssignReturn', tag_filter)) {
       // all right, insert it
     } else {
       alert('Error trying to insert iLM content (tinymce_ilm " + $fileid + ")');
       }
     // window.close();
  //]]>
</script>\n";
    print $output;
    die();
    }


  //TODO sugestao para uso de apenas uma funcao para chamar os editores, cortando 17 linhas de codigo
  /// Function to add content to the Editor Window
  //  Updated: Marcio Passos - marciopassosbel[at]gmail[dot]com :: 07 / Jul / 2016
  //  @param int $fileid Id of file
  function editor_ilm ($fileid, $editor) {
    $tag_filter = $this->tag_ilm($fileid); // Prepare tag like: <ia toolbar=disable width=800 height=600>ID</ia>
    $output1 = "<script type='text/javascript'>
  //<![CDATA[
  var tag_filter = '$tag_filter';\n";

    if ($editor == 'atto') {
      $output2 = "  if (window.opener.document.execCommand('insertHTML', false, tag_filter)) { } // all right, insert it";
      } elseif ($editor == 'tinyMCE') {
      $output2 = "  if (window.opener.tinyMCE.execCommand('mceiAssignReturn', tag_filter)) { } // all right, insert it";
      } else
      $output2 = "";

    $output3 = "  else { console.log('Error trying to insert iLM content (atto_ilm " . $fileid . ")'); }
  window.close();
  //]]>
</script>\n";

    //D var_dump($output);
    print $output1 . $output2 . $output3;

    die();
    }


  /// Function to add content to the Atto Editor Window
  //  Updated: Marcio Passos - marciopassosbel[at]gmail[dot]com :: 07 / Jul / 2016
  //  @param int $fileid Id of file
  function atto_ilm ($fileid) {
    global $CFG, $DB;

    $tag_filter = $this->tag_ilm($fileid);

    $output = "<script type='text/javascript'>
     //<![CDATA[
     var tag_filter = '$tag_filter';
     if (window.opener.document.execCommand('insertHTML', false, tag_filter)) {
      // all right, insert iLM tag to this editor
     } else {
     // error trying to insert
     alert('Error trying to insert iLM content (atto_ilm " + $fileid + ")');
       }
     //window.close();
     //]]>
</script>\n";
    print $output;
    die();
    }


  /// Function for get path and info of directories: dirid,  dir_base, dir_parent, dir_home
  //  @param string $key Key for return information
  //  @return Ambigous <unknown, number, string, NULL> Return an information requested
  function get_dir_ilm ($key) {
    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $context = context_course::instance($this->id);
    $dirid = optional_param('dirid', 0, PARAM_INT);
    $dir_home = $fs->get_file($context->id, 'mod_iassign', 'activity', 0, $dir_base = '/', '.');
    if ($dirid == 0) {
      $dir = ($dir_home = $fs->create_directory($context->id, 'mod_iassign', 'activity', 0, $dir_base));
      $dirid = $dir->get_id();
      }
    else {
      $dir = $fs->get_file_by_id($dirid);
      $dir_base = $dir->get_filepath();
      }
    $dir_parent = $dir->get_parent_directory();
    $data = array('dirid' => $dirid, 'dir_base' => $dir_base, 'dir_parent' => ($dir_parent == NULL ? 0 : $dir_parent->get_id()), 'dir_home' => $dir_home->get_id());
    return $data[$key];
    }


  /// Function for create an new dir
  function new_dir_ilm () {
    global $USER;

    $ilmid = optional_param('ilmid', NULL, PARAM_INT);

    $dirname = optional_param('dirname', NULL, PARAM_TEXT);
    $dir_base = $this->get_dir_ilm('dir_base');

    $context = context_course::instance($this->id);
    $fs = get_file_storage(); // Get reference to all files in Moodle data

    $fs->create_directory($context->id, 'mod_iassign', 'activity', 0, $dir_base . $dirname . "/", $USER->id);
    $dir_base = $fs->get_file($context->id, 'mod_iassign', 'activity', 0, $dir_base . $dirname . "/", '.');
    $dir_base->set_author($USER->firstname . ' ' . $USER->lastname);

    redirect(new moodle_url($this->url . '&dirid=' . $this->get_dir_ilm('dirid') . '&ilmid=' . $ilmid));
    }


  /// Function for delete an dir
  function delete_dir_ilm () { //
    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $ilmid = optional_param('ilmid', NULL, PARAM_INT);
    $context = context_course::instance($this->id);
    $dir = $fs->get_file_by_id($this->get_dir_ilm('dirid'));
    $dir_parent = $this->get_dir_ilm('dir_parent');
    if ($dir) {
      if ($dir->is_directory()) {
        $files_delete = $fs->get_directory_files($context->id, 'mod_iassign', 'activity', 0, $dir->get_filepath(), true, true);
        foreach ($files_delete as $value)
            $value->delete();
        $dir->delete();
        }
      }
    redirect(new moodle_url($this->url . '&dirid=' . $dir_parent . '&ilmid=' . $ilmid));
    die();
    }


  /// Function for rename an dir
  function rename_dir_ilm () {
    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $ilmid = optional_param('ilmid', NULL, PARAM_INT);
    $context = context_course::instance($this->id);
    $dir = $fs->get_file_by_id($this->get_dir_ilm('dirid'));
    $dir_parent = $this->get_dir_ilm('dir_parent');
    $dirname = optional_param('dirname', NULL, PARAM_TEXT);

    $pathname = explode("/", substr($dir->get_filepath(), 0, strlen($dir->get_filepath()) - 1));
    if ($dir->is_directory()) {
      $files_rename_path = $fs->get_directory_files($context->id, 'mod_iassign', 'activity', 0, $dir->get_filepath(), true, true);
      foreach ($files_rename_path as $value)
        $value->rename(str_replace($pathname[count($pathname) - 1], $dirname, $value->get_filepath()), $value->get_filename());
      $dir->rename(str_replace($pathname[count($pathname) - 1], $dirname, $dir->get_filepath()), $dir->get_filename());
      }

    redirect(new moodle_url($this->url . '&dirid=' . $dir_parent . '&ilmid=' . $ilmid));
    die();
    }


  /// Function for move an dir and your content for other dir
  function selected_move_ilm () {
    global $PAGE, $OUTPUT, $CFG;
    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $context = context_course::instance($this->id);

    $ilmid = optional_param('ilmid', NULL, PARAM_INT);
    $dirid = $this->get_dir_ilm('dirid');
    $dir_base = $this->get_dir_ilm('dir_base');
    $files_id = explode(",", optional_param('files_id', '', PARAM_TEXT));

    $code_javascript_ilm = "
<script type='text/javascript'>
 //<![CDATA[
  function getRadiobutton () {
  var radioButtons = document.getElementsByTagName('input');
  var param = '';
  for (var counter=0; counter < radioButtons.length; counter++) {
    if (radioButtons[counter].type.toUpperCase()=='RADIO' && radioButtons[counter].checked == true && radioButtons[counter].name == 'selected_dir')
    param = radioButtons[counter].value;
    }
  return param;
    }

  function move_selected_ilm () {
  var msgAnswer;
  if (getRadiobutton() != '') {
    msgAnswer = confirm('" . get_string('question_move_dir', 'iassign') . "');
    if (msgAnswer)
      window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=move&ilmid=" . $ilmid . "&dirid=" . $dirid . "&files_id=" .
        optional_param('files_id', '', PARAM_TEXT) . "&dir_move='+getRadiobutton();
    }
  else
    alert('" . get_string('error_dir_not_selected_to_move', 'iassign') . "');
    }

  function cancel_selected_ilm () {
    window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&ilmid=" . $ilmid . "&dirid=" . $dirid . "';
    }
   //]]>
</script>\n";

    $title = get_string('move_files', 'iassign');
    $PAGE->set_title($title);
    $PAGE->set_pagelayout('base');
    print $OUTPUT->header();
    print $OUTPUT->heading($title);
    $dir_paths = array();
    print $OUTPUT->box_start();
    print "<center>";
    foreach ($files_id as $file_id) {
      $file = $fs->get_file_by_id($file_id);
      if ($file) {
        if (!$file->is_directory())
          print "<p>" . iassign_icons::insert('file') . " " . $file->get_filepath() . $file->get_filename() . "</p>";
        else {
          print "<p>" . iassign_icons::insert('dir') . " " . $file->get_filepath() . "</p>";
          array_push($dir_paths, $file->get_filepath());
          }
        }
      }
    print $OUTPUT->heading(get_string('select_move_ilm', 'iassign'), 3, 'move', 'move_files');
    if ($dir_base != '/') {
      $check_select = "<input name='selected_dir' type='radio' value='" . $this->get_dir_ilm('dir_home') . "'/>";
      print $check_select . " " . iassign_icons::insert('dir') . " /<br>";
      }
    $files_tree = $fs->get_directory_files($context->id, 'mod_iassign', 'activity', 0, '/', true, true, 'filepath');
    foreach ($files_tree as $file) {
      if ($file->is_directory() && $file->get_filepath() != $dir_base) {
        $is_parent = false;
        foreach ($dir_paths as $dir) {
          $path = explode("/", $dir);
          array_pop($path);
          $path[count($path) - 1] = '';
          $path = implode("/", $path);
          $is_parent |= (strpos($file->get_filepath(), $dir) === false ? false : true);
          $is_parent |= ($file->get_filepath() != $path ? false : true);
          }
        if ($is_parent == false) {
          $check_select = "<input name='selected_dir' type='radio' value='" . $file->get_id() . "'/>";
          print "<p>" . $check_select . " " . iassign_icons::insert('dir') . " " . $file->get_filepath() . "</p>";
          }
        }
      }
    print "<p><input type='button' value='" . get_string('ok') . "' onclick='move_selected_ilm();'/> &nbsp; &nbsp;";
    print "<input type='button' value='" . get_string('cancel') . "' onclick='cancel_selected_ilm();'/></p>";
    print "</center>";
    print $OUTPUT->box_end();
    print $OUTPUT->footer();
    print $code_javascript_ilm;
    die;
    }


  /// Function for move files for an dir
  function move_files_ilm () {
    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $context = context_course::instance($this->id);

    $ilmid = optional_param('ilmid', NULL, PARAM_INT);
    $dirid = $this->get_dir_ilm('dirid');
    $dir_move = $fs->get_file_by_id(optional_param('dir_move', 0, PARAM_INT));
    $files_id = explode(",", optional_param('files_id', '', PARAM_TEXT));

    foreach ($files_id as $file_id) {
      $file = $fs->get_file_by_id($file_id);
      if ($file) {
        if ($file->is_directory()) {
          $pathname = explode("/", $file->get_filepath());
          $files_move_path = $fs->get_directory_files($context->id, 'mod_iassign', 'activity', 0, $file->get_filepath(), true, true);
          foreach ($files_move_path as $value) {
            $path_move = $dir_move->get_filepath() . $pathname[count($pathname) - 2] . '/' . str_replace($file->get_filepath(), '', $value->get_filepath());
            $value->rename($path_move, $value->get_filename());
            //echo($value->get_filepath().$value->get_filename()." - $path_move".$value->get_filename()."<br>");
            }
          $path_move = $dir_move->get_filepath() . $pathname[count($pathname) - 2] . '/';
          //echo($file->get_filepath().$file->get_filename()." - $path_move".$file->get_filename()."<br>");
          $file->rename($path_move, $file->get_filename());
        } else {
          //echo($file->get_filepath().$file->get_filename()." -> ".$dir_move->get_filepath().$file->get_filename()."<br>");
          $file->rename($dir_move->get_filepath(), $file->get_filename());
          }
        }
      }
    //die;

    redirect(new moodle_url($this->url . '&ilmid=' . $ilmid . '&dirid=' . $dirid));
    die();
    } // function move_files_ilm()


  /// Function for recover files in use on all activities of a course
  function recover_files_ilm () {
    global $DB, $USER;

    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $courseid = optional_param('id', NULL, PARAM_INT);
    $dirid = $this->get_dir_ilm('dirid');
    $ilmid = optional_param('ilmid', NULL, PARAM_INT);
    $contextfile = context_course::instance($this->id);

    $iassigns = $DB->get_records("iassign", array("course" => $courseid));
    foreach ($iassigns as $iassign) {
      $iassign_statement_activity_list = $DB->get_records("iassign_statement", array("iassignid" => $iassign->id));
      foreach ($iassign_statement_activity_list as $iassign_statement_activity_item) {
        $cm = get_coursemodule_from_instance("iassign", $iassign->id, $courseid);
        $context = context_module::instance($cm->id);

        // Before associtaion 'files.id' with 'iassign_statement.iassign_ilmid': $files_fs = $fs->get_area_files($context->id, 'mod_iassign', 'exercise', $iassign_statement_activity_item->filesid);
        $files_fs = $fs->get_area_files($context->id, 'mod_iassign', 'exercise', $iassign_statement_activity_item->id);

        if ($files_fs) {
          foreach ($files_fs as $value) {
            $extension = explode(".", $value->get_filename());
            if (!$value->is_directory()) {
              $fileinfo = array('contextid' => $contextfile->id,
                 'component' => 'mod_iassign',
                 'filearea' => 'activity',
                 'itemid' => 0,
                 'filepath' => $this->get_dir_ilm('dir_base'),
                 'userid' => $USER->id,
                 'author' => $USER->firstname . ' ' . $USER->lastname,
                 'license' => 'allrightsreserved',
                 'timecreated' => time(),
                 'filename' => $iassign_statement_activity_item->name . "." . $extension[1]); // any filename
              $newfile_fs = $fs->create_file_from_string($fileinfo, $value->get_content());
              }
            }
          } // if ($files_fs)
        } // foreach ($iassign_statement_activity_list as $iassign_statement_activity_item)
      } // foreach ($iassigns as $iassign)

    redirect(new moodle_url($this->url . '&dirid=' . $dirid . '&ilmid=' . $ilmid));
    die();
    } // function recover_files_ilm()


  /// Auxiliary function the get all files in a given context
  //  @calledby view_files_ilm($iassign_ilm_class, $extension, $start = 0)
  //  @return [array_contextid_files_thisauthor[], array_contextid_files_otherauthors[], array_contextid_files_otherauthors_userid[]]
  //TODO Falta separar os arquivos listados, apresentar primeiro aqueles do autor e depois os demais.
  //TODO Nao devemos listar aqueles que estao sem permissao (license == 'allrightsreserved')
  function get_files_in_context (&$filesfrommine, &$filesfromothers, $contextid, $extension, $userid) {
    // s_iassign_statement: id name iassignid type_iassign proposition author_name author_modified_name iassign_ilmid
    // (NAO s_iassign_statement.file = s_files.id)
    $fs = get_file_storage(); // Get reference to all files in Moodle data
    $dir_base = $this->get_dir_ilm('dir_base');
    $files_course = $fs->get_directory_files($contextid, 'mod_iassign', 'activity', 0, $dir_base, false, true, 'filename');
    $countf = 0;
    $array_files_id = array(); // all files from this context
    foreach ($files_course as $item_fc) { // license = not "allrightsreserved" AND is not from this author => present it
      //$filename = $item_fc->get_filename();
      //$filepath = $item_fc->get_filepath();
      //$fileid = $item_fc->get_id(); // get file ID (in *_iassign_statement table)
      $fileuserid = $item_fc->get_userid(); // get user.id of the author
      // $array_files_aux[] = $item_fc;
      $itemid = $item_fc->get_id();
      if (!in_array($itemid, $array_files_id)) {
        //TODO Avoid to insert twice - necessary since it has been inserting 2 copies in iassign_statement
        if ($fileuserid == $userid)
          $filesfrommine[] = $item_fc;
        else
          $filesfromothers[] = $item_fc;
        $array_files_id[] = $itemid;
        $countf++;
        }
      }

    return $countf;
    } // function get_files_in_context(&$filesfrommine, &$filesfromothers, $contextid, $extension, $userid)


  // Function to get all iLM files from the same "file type": (usually) files under the same directory
  // @calledby view_files_ilm($iassign_ilm_class, $extension, $start = 0)
  function get_all_ilm_files_by_fileid ($fileid) {
    global $DB, $USER;
    // $iassign_statement_activity_list = $DB->get_records("iassign_statement", array("file" => $fileid));
    //TODO Criar coluna 'authorid'
    $str_query = "SELECT * FROM {iassign_statement} WHERE file = " . $fileid . " ORDER BY author_name, timecreated DESC";
    $array_iassign_ilm = $DB->get_records_sql($str_query);
    return $array_iassign_ilm;
    }


  //NN
//NN
  // Build string with JavaScript code with function to edit/remove/double iLM files
  // @calledby view_files_ilm($iassign_ilm_class, $extension, $start = 0)
  function get_string_JavaScript_functions ($dirid, $ilmid, $files_array, $dirs_array) {
    global $CFG;
    $error_files_exists = get_string('error_file_exists', 'iassign');
    $error_dir_exists = get_string('error_dir_exists', 'iassign');
    $code_javascript_ilm = "
<script type='text/javascript'>
 //<![CDATA[
   function preview_ilm (fileid, ilmid) { //JavaScript function
   var param = '" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=preview&fileid='+fileid+'&ilmid='+ilmid;
   var preview_ilm=window.open(param,'','menubar=0,location=0,scrollbars,status,resizable,width=900 height=700');
     }

   function update_ilm (ilmid, fileid) { //JavaScript function
     window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=update&ilmid='+ilmid+'&dirid=" . $dirid . "&fileid='+fileid;
     }

   function delete_ilm (ilmid, fileid) { //JavaScript function
     var msgAnswer;
     msgAnswer = confirm('" . get_string('delete_file', 'iassign') . "');
     if (msgAnswer) {
       window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=delete&ilmid='+ilmid+'&dirid=" . $dirid . "&fileid='+fileid;
       }
     }

   function delete_selected_ilm () { //JavaScript function
     var msgAnswer;
     var param = getCheckbox();
     if (param.join() != '') {
       msgAnswer = confirm('" . get_string('delete_files', 'iassign') . "');
       if (msgAnswer)
         window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=selected_delete&dirid=" . $dirid . "&files_id='+param.join();
       }
     else
       alert('" . get_string('er_file_not_selected_to_delete', 'iassign') . "');
     }

   function add_ilm_iassign (ilmid, filename, fileid) { //JavaScript function
     window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=addilm&ilmid='+ilmid+'&fileid='+fileid+'&filename='+filename;
     }

   function duplicate_ilm (ilmid, filename, fileid) { //JavaScript function
     var filenamecopy;
     var i;
     var files = new Array(" . $files_array . ");
     do {
      filenamecopy = prompt ('" . get_string('duplicate_file', 'iassign') . "',filename);
       } while (filenamecopy == '');
     if (filenamecopy == null)
       return false;\n
     else {
       for (i=0;i<files.length;i++) {
         if (files[i]==filenamecopy) {
           alert('" . $error_files_exists . "');
           return false;\n
           }
         }
       // @see: PHP 'function duplicate_file_ilm()' above (line 8083/9608)
       window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?" .
         "from=" . $this->from . "&id=" . $this->id . "&action=duplicate&ilmid=' + ilmid + '&dirid=" . $dirid . "&fileid=' + fileid + '&filename=' + filenamecopy;
       } // else
     }

   function rename_ilm (ilmid, filename, fileid) { //JavaScript function
     var filenamecopy;
     var i;
     var files = new Array($files_array);
     do {
      filenamecopy = prompt('" . get_string('rename_file', 'iassign') . "',filename);
     } while (filenamecopy == '');
     if (filenamecopy == null)
       return false;
     else {
       for (i=0;i<files.length;i++) {
         if (files[i]==filenamecopy) {
           alert('" . $error_files_exists . "');
           return false;
           }
         }
       window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=rename&ilmid='+ilmid+'&dirid=" . $dirid . "&fileid='+fileid+'&filename='+filenamecopy;
       }
     }

   function export_files_ilm () { //JavaScript function
     var param = getCheckbox();
     if (param.join() != '')
       window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=export&dirid=" . $dirid . "&files_id='+param.join();
     else
       alert('" . get_string('er_file_not_selected_to_export', 'iassign') . "');
     }

  function select_all_ilm () { //JavaScript function
    var checkBoxes = document.getElementsByTagName('input');
    var selectAll = document.getElementById('select_all');
    for (var counter=0; counter < checkBoxes.length; counter++) {
      if (checkBoxes[counter].type.toUpperCase()=='CHECKBOX' && checkBoxes[counter].name == 'selected_file')
        checkBoxes[counter].checked = selectAll.checked;
      }
    }

  function getCheckbox () { //JavaScript function
    var checkBoxes = document.getElementsByTagName('input');
    var param = new Array();
    for (var counter=0; counter < checkBoxes.length; counter++) {
    if (checkBoxes[counter].type.toUpperCase()=='CHECKBOX' && checkBoxes[counter].checked == true && checkBoxes[counter].name == 'selected_file')
      param.push(checkBoxes[counter].value);
      }
    return param;
    }

  function new_dir_ilm () { //JavaScript function
    var dirname = '';
    var i;
    var dirs = new Array(" . $dirs_array . ");
    do {
     var dirname = prompt ('" . get_string('question_new_dir', 'iassign') . "', '');
    }  while (dirname == '');
    if (dirname == null)
      return false;\n
    else {
      for (i=0;i<dirs.length;i++) {
        if (dirs[i]==dirname) {
          alert('" . $error_dir_exists . "');
          return false;\n
          }
        }
      window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=new_dir&ilmid=" . $ilmid . "&dirid=" . $dirid . "&dirname='+dirname;
      }
    }

   function delete_dir_ilm (ilmid, dirid) { //JavaScript function
     var msgAnswer;
     msgAnswer = confirm('" . get_string('question_delete_dir', 'iassign') . "');
     if (msgAnswer) {
       window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=delete_dir&ilmid='+ilmid+'&dirid='+dirid;
       }
     }

   function rename_dir_ilm (ilmid, dirname, dirid) { //JavaScript function
     var dirnamecopy;
     var i;
     var dirs = new Array($dirs_array);
     do {
       dirnamecopy = prompt ('" . get_string('question_rename_dir', 'iassign') . "',dirname);
       } while (dirnamecopy == '');
     if (dirnamecopy == null)
       return false;\n
     else {
       for (i=0;i<dirs.length;i++) {
         if (dirs[i]==dirnamecopy) {
           alert('" . $error_dir_exists . "');
           return false;\n
           }
         }
       window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=rename_dir&ilmid='+ilmid+'&dirid='+dirid+'&dirname='+dirnamecopy;
       }
     }

   function move_selected_ilm (ilmid) { //JavaScript function
   var param = getCheckbox();
   if (param.join() != '')
     window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=selected_move&ilmid='+ilmid+'&dirid=" . $dirid . "&files_id='+param.join();
   else
     alert('" . get_string('er_file_not_selected_to_move', 'iassign') . "');
     }

   function recover_files_ilm () { //JavaScript function
     var msgAnswer;
     msgAnswer = confirm('" . get_string('question_recover_files', 'iassign') . "');
     if (msgAnswer) {
       window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=recover&ilmid=" . $ilmid . "&dirid=" . $dirid . "';
       }
    }

    window.onload = function() { //JavaScript function
      var xPosition = 0;
      var yPosition = 0;
      element = document.getElementById('new_file');
      if (element && element!=null) {
        while (element) {
          xPosition += (element.offsetLeft - element.scrollLeft + element.clientLeft);
          yPosition += (element.offsetTop - element.scrollTop + element.clientTop);
          element = element.offsetParent;
          }
        document.getElementById('new_file').style.top = '' + (yPosition - 400) + 'px';
        document.getElementById('new_file').style.right = '' + 0 + 'px';
        }
      location.hash = '#new_file';
      };

   //]]>
</script>\n"; // end of $code_javascript_ilm
     return $code_javascript_ilm;
     } // function get_string_JavaScript_functions($dirid, $ilmid, $files_array, $dirs_array)


  /// List iassign files from course directory
  //  User (teacher or above it) can access all the iLM files under the current course
  //  @calledby ilm_manager.php : $ilm_manager_instance->view_files_ilm($iassign_ilm->extension);
  function view_files_ilm ($iassign_ilm_class, $extension) {
    global $CFG, $DB, $USER, $OUTPUT; // class iassign : var $cm / class ilm_manager
    $fs = get_file_storage();
    $context = context_course::instance($this->id);
    //D echo "<br/><br/><br/><br/><br/>locallib.php!view_files_ilm(.): id=" . $this->id . "<br/>";

    $ilmid = optional_param('ilmid', NULL, PARAM_INT);
    $dirid = $this->get_dir_ilm('dirid');
    $dir_base = $this->get_dir_ilm('dir_base');
    // $context->id must be the same as context_module::instance($USER->cm)->id

    // {files}.filearea='activity' => it is the file ('exercise'=>the correspondent entry associated to {iassign_statement}
    $files_all = $fs->get_directory_files($context->id, 'mod_iassign', 'activity', 0, $dir_base, false, true, 'filename');
    $files_filename = array(); // auxiliary to avoid duplicit ({files}.filearea = 'activity' and the same in {files}.filearea = 'exercise')
    $files_contenthash = array(); // auxiliary to avoid duplicit ({files}.filearea = 'activity' and the same in {files}.filearea = 'exercise')
    $is_activity = array(); // $is_activity => use {files}.filearea = 'activity', otherwise {files}.filearea = 'exercise'
    foreach ($files_all as $one_files) {
      $is_activity[] = true; 
      $files_filename[] = $one_files->get_filename();
      $files_contenthash[] = $one_files->get_contenthash();
      //D if ($one_files->get_filename()=="exd--desafio.ivph") echo " * exd--desafio.ivph : index=" . (count($files_filename)-1) . ": |" . $one_files->get_contenthash() . "|<br/>";
      }
    // https://moodledev.io/docs/4.5/apis/subsystems/form/usage/files

    // Get {files} from another contexts in this course
    require_once 'files_functions.php';
    $files_exercise = get_all_files_course($DB, $this->id, $fs); // $this->id = {course}.id
    // $files_all = array_merge($files_exercise, $files_all); // append new elements
    if (count($files_exercise)>0) {
      foreach ($files_exercise as $item_files_exercise) {
        $not_existent = true;
        $index = array_search($item_files_exercise->get_filename(), $files_filename);
        if ($index!="") { // not empyt means existent
          if ($files_contenthash[$index]==$item_files_exercise->get_contenthash())
            $not_existent = false; // the file is already existent in {files}.filearea='activity'
          //D else { echo " - nao : sem index : " . $item_files_exercise->get_filename() . " - |" . $item_files_exercise->get_contenthash() . "|<br/>"; }
          }
        //D else { echo " - nao : index=" . $index . " : " . $item_files_exercise->get_filename() . " - |" . $item_files_exercise->get_contenthash() . "|<br/>"; }
        if ($not_existent) { // empyt means inexistent
          $files_all[] = $item_files_exercise; // append the {files}.filearea='exercise' to $files_all[]
          $is_activity[] = false; // register it has {files}.filearea= 'exercise'
          $files_filename[] = $item_files_exercise->get_filename();
          $files_contenthash[] = $item_files_exercise->get_contenthash();
          }
        //D else {
        //D  echo " - SIM : index=" . $index . " : " . $item_files_exercise->get_filename() . " - |" . $item_files_exercise->get_contenthash() . "|=|" . $files_contenthash[$index] . "|=|" . $item_files_exercise->get_contenthash() . "|<br/>";
        //D }
        }
      } // if (count($files_exercise)>0)

    $files_array = '';
    foreach ($files_all as $value) {
      if (!$value->is_directory())
        $files_array .= "'" . $value->get_filename() . "',";
      }
    $files_array .= "''";
    $error_files_exists = get_string('error_file_exists', 'iassign');

    $dirs_array = '';
    foreach ($files_all as $value) {
      if ($value->is_directory()) {
        $pathname = explode("/", substr($value->get_filepath(), 0, strlen($value->get_filepath()) - 1));
        $dirs_array .= "'" . $pathname[count($pathname) - 1] . "',";
        }
      }
    $dirs_array .= "''";
    $error_dir_exists = get_string('error_dir_exists', 'iassign');

    //TODO: Rever o 'preview', pois so' apresenta uma vez!
    $code_javascript_ilm = "
<script type='text/javascript'>
//<![CDATA[
 function preview_ilm (fileid, ilmid) {
   var param = '" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=preview&fileid='+fileid+'&ilmid='+ilmid;
   var preview_ilm=window.open(param,'','menubar=0,location=0,scrollbars,status,resizable,width=900 height=700');
   }

 function update_ilm (ilmid, fileid) {
   window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=update&ilmid='+ilmid+'&dirid=" . $dirid . "&fileid='+fileid;
   }

 function delete_ilm (ilmid, fileid) {
   var msgAnswer;
   msgAnswer = confirm('" . get_string('delete_file', 'iassign') . "');
   if (msgAnswer) {
     window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=delete&ilmid='+ilmid+'&dirid=" . $dirid . "&fileid='+fileid;
     }
   }

 function delete_selected_ilm () {
   var msgAnswer;
   var param = getCheckbox();
   if (param.join() != '') {
     msgAnswer = confirm('" . get_string('delete_files', 'iassign') . "');
     if (msgAnswer)
       window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=selected_delete&dirid=" . $dirid . "&files_id='+param.join();
     }
   else
     alert('" . get_string('er_file_not_selected_to_delete', 'iassign') . "');
   }

 function add_ilm_iassign (ilmid, filename, fileid) {
   window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=addilm&ilmid='+ilmid+'&fileid='+fileid+'&filename='+filename;
   }

 function duplicate_ilm (ilmid, filename, fileid) {
   var filenamecopy;
   var i;
   var files = new Array(" . $files_array . ");
   do {
     filenamecopy = prompt('" . get_string('duplicate_file', 'iassign') . "',filename);
   } while (filenamecopy == '');
   if (filenamecopy == null)
     return false;
   else {
     for (i=0;i<files.length;i++) {
       if (files[i]==filenamecopy) {
         alert('" . $error_files_exists . "');
         return false;
         }
       }
     // @see: PHP 'function duplicate_file_ilm()' above (line 8083/9608)
     window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?" .
       "from=" . $this->from . "&id=" . $this->id . "&action=duplicate&ilmid=' + ilmid + '&dirid=" . $dirid . "&fileid=' + fileid + '&filename=' + filenamecopy;
     } // else
   }

 function rename_ilm (ilmid, filename, fileid) {
   var filenamecopy;
   var i;
   var files = new Array($files_array);
   do {
     filenamecopy = prompt('" . get_string('rename_file', 'iassign') . "',filename);
   } while (filenamecopy == '');
   if (filenamecopy == null)
     return false;
   else {
     for (i=0;i<files.length;i++) {
       if (files[i]==filenamecopy) {
         alert('" . $error_files_exists . "');
         return false;
         }
       }
     window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=rename&ilmid='+ilmid+'&dirid=" . $dirid . "&fileid='+fileid+'&filename='+filenamecopy;
     }
   }

 function export_files_ilm () {
   var param = getCheckbox();
   if (param.join() != '')
     window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=export&dirid=" . $dirid . "&files_id='+param.join();
   else
     alert('" . get_string('er_file_not_selected_to_export', 'iassign') . "');
   }

 function select_all_ilm () {
   var checkBoxes = document.getElementsByTagName('input');
   var selectAll = document.getElementById('select_all');
   for (var counter=0; counter < checkBoxes.length; counter++) {
     if (checkBoxes[counter].type.toUpperCase()=='CHECKBOX' && checkBoxes[counter].name == 'selected_file')
       checkBoxes[counter].checked = selectAll.checked;
     }
   }

 function getCheckbox () {
   var checkBoxes = document.getElementsByTagName('input');
   var param = new Array();
   for (var counter=0; counter < checkBoxes.length; counter++) {
     if (checkBoxes[counter].type.toUpperCase()=='CHECKBOX' && checkBoxes[counter].checked == true && checkBoxes[counter].name == 'selected_file')
       param.push(checkBoxes[counter].value);
     }
   return param;
   }

 function new_dir_ilm () {
   var dirname = '';
   var i;
   var dirs = new Array(" . $dirs_array . ");
   do {
     var dirname = prompt('" . get_string('question_new_dir', 'iassign') . "', '');
   }  while (dirname == '');
   if (dirname == null)
     return false;
   else {
     for (i=0;i<dirs.length;i++) {
       if (dirs[i]==dirname) {
         alert('" . $error_dir_exists . "');
         return false;
         }
       }
     window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=new_dir&ilmid=" . $ilmid . "&dirid=" . $dirid . "&dirname='+dirname;
     }
   }

 function delete_dir_ilm (ilmid, dirid) {
   var msgAnswer;
   msgAnswer = confirm('" . get_string('question_delete_dir', 'iassign') . "');
   if (msgAnswer) {
     window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=delete_dir&ilmid='+ilmid+'&dirid='+dirid;
     }
   }

 function rename_dir_ilm (ilmid, dirname, dirid) {
   var dirnamecopy;
   var i;
   var dirs = new Array(" . $dirs_array . ");
   do {
     dirnamecopy = prompt('" . get_string('question_rename_dir', 'iassign') . "',dirname);
   } while (dirnamecopy == '');
   if (dirnamecopy == null)
     return false;
   else {
     for (i=0;i<dirs.length;i++) {
       if (dirs[i]==dirnamecopy) {
         alert('" . $error_dir_exists . "');
         return false;
         }
       }
     window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=rename_dir&ilmid='+ilmid+'&dirid='+dirid+'&dirname='+dirnamecopy;
     }
   }

 function move_selected_ilm (ilmid) {
   var param = getCheckbox();
   if (param.join() != '')
     window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=selected_move&ilmid='+ilmid+'&dirid=" . $dirid . "&files_id='+param.join();
   else
     alert('" . get_string('er_file_not_selected_to_move', 'iassign') . "');
   }

 function recover_files_ilm () {
   var msgAnswer;
   msgAnswer = confirm('" . get_string('question_recover_files', 'iassign') . "');
   if (msgAnswer) {
     window.location='" . $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&action=recover&ilmid=" . $ilmid . "&dirid=" . $dirid . "';
     }
   }

 window.onload = function () {
   var xPosition = 0;
   var yPosition = 0;
   element = document.getElementById('new_file');
   if (element && element!=null) {
     while (element) {
       xPosition += (element.offsetLeft - element.scrollLeft + element.clientLeft);
       yPosition += (element.offsetTop - element.scrollTop + element.clientTop);
       element = element.offsetParent;
       }
     document.getElementById('new_file').style.top = '' + (yPosition - 400) + 'px';
     document.getElementById('new_file').style.right = '' + 0 + 'px';
     }
   location.hash = '#new_file';
   };

//]]>
</script>\n";

    $output = "";
    $select_all = "";
    $count_files = 0;

    $extensions_allow = array();
    $iassign_ilm = $DB->get_records('iassign_ilm', array("enable" => 1)); // extensions for all iLM...
    foreach ($iassign_ilm as $item_iassign_ilm)
      $extensions_allow = array_merge($extensions_allow, explode(",", $item_iassign_ilm->extension));

    $ii = 0; // get $is_activity[$ii] associated to $one_files
    foreach ($files_all as $one_files) {
      $filename = $one_files->get_filename();
      $filepath = $one_files->get_filepath();
      $pathname = explode("/", substr($filepath, 0, strlen($filepath) - 1));
      $pathname = $pathname[count($pathname) - 1];
      $fileid = $one_files->get_id();
      $tmp = explode(".", $filename);
      if (count($tmp)>1) // avoid error if the file has no extension
        $filetype = $tmp[1];
      else // probably error...
        $filetype = '';
      $author = $one_files->get_author();
      $timemodified = date("d/m/Y H:i:s", $one_files->get_timemodified());
      $timecreated = date("d/m/Y H:i:s", $one_files->get_timecreated());
      $extensions = explode(",", $extension);

      if (in_array(strtolower($filetype), $extensions) || $one_files->is_directory() || $this->from == 'block' || $this->from == 'tinymce' || $this->from == 'atto') {
        $count_files++;

        // buscar fileid nas tabelas do iassign
        $list_filein_use = "";
        $iassign_statement_activity_list = $DB->get_records("iassign_statement", array("file" => $fileid));
        if ($iassign_statement_activity_list) {
          foreach ($iassign_statement_activity_list as $iassign_statement_activity_item) {
            $list_filein_use .= $iassign_statement_activity_item->name . "</br>\n";
            }
          }

        $iassign_ilm = $DB->get_record("iassign_ilm", array('extension' => $filetype, 'id' => $ilmid, 'enable' => '1'));
        if (!$iassign_ilm) {
          $iassign_ilm = new stdClass();
          $iassign_ilm->id = $ilmid;
          }

        // Do not use $var1 inside "..." - problem with some "unusual" operational system
        if ($is_activity[$ii]) // use {files}.filearea='activity'
          $url = $CFG->wwwroot . "/pluginfile.php/" . $one_files->get_contextid() . "/mod_iassign/activity";
        else // use {files}.filearea='exercise'
          $url = $CFG->wwwroot . "/pluginfile.php/" . $one_files->get_contextid() . "/mod_iassign/exercise";
        $ii++; // get the next element of $is_activity[]/$files_all[]

        $fileurl = $url . '/' . $one_files->get_itemid() . $filepath . $filename;
        $dirurl = new moodle_url($this->url) . '&ilmid=' . $iassign_ilm->id . '&dirid=' . $fileid;

        $straux = $CFG->wwwroot . "/mod/iassign/ilm_manager.php?from=" . $this->from . "&id=" . $this->id . "&fileid=" . $fileid . "&";
        $link_add_ilm_iassign = "  <a href='" . $straux . "action=addilm&filename=" . $filename . "'>" . iassign_icons::insert('add_ilm_iassign') . "</a>\n";
        $link_add_ilm_tinymce = "  <a href='" . $straux . "action=tinymceilm'>" . iassign_icons::insert('add_ilm_iassign') . "</a>\n";
        $link_add_ilm_atto = "  <a href='" . $straux . "action=attoilm'>" . iassign_icons::insert('add_ilm_iassign') . "</a>\n";

        $check_select = "";
        $link_rename = "";
        $link_delete = "";
        $link_duplicate = "  <a href='#' onclick=\"duplicate_ilm('" . $iassign_ilm->id . "', '". $filename . "'," . $fileid . ");\">" . iassign_icons::insert('duplicate_iassign') . "</a>\n";
        $link_edit = "  " . iassign_icons::insert('no_edit_iassign');
        $link_filter = "  <a href='#' onclick=\"preview_ilm(" . $fileid . "," . $ilmid . ");\">" . iassign_icons::insert('preview_iassign') . "</a>\n";

        $link_duplicate = "  <a href='#' onclick=\"duplicate_ilm('" . $iassign_ilm->id . "', '" . $filename . "'," . $fileid . ");\">" .
              iassign_icons::insert('duplicate_iassign') . "</a>\n";

        $link_edit = "  " . iassign_icons::insert('no_edit_iassign');
        $link_filter = "  <a href='#' onclick=\"preview_ilm(" . $fileid . "," . $ilmid . ");\">" . iassign_icons::insert('preview_iassign') . "</a>\n";

        if ($one_files->get_userid() == $USER->id) {
          if ($iassign_statement_activity_list) {
            $check_select = "";
            $link_edit = iassign_icons::insert('edit_iassign_disable');
            $link_delete = "  " . iassign_icons::insert('delete_iassign_disable');
            $link_rename = "";
          } else {
            $check_select = "<input name='selected_file' type='checkbox' value='" . $fileid . "'/>\n";
            $link_edit = "  <a href='#' onclick=\"update_ilm('" . $iassign_ilm->id . "', " . $fileid . ")\">" . iassign_icons::insert('edit_iassign') . "</a>\n";
            $link_delete = "  <a href='#' onclick=\"delete_ilm('" . $iassign_ilm->id . "', " . $fileid . ");\">" . iassign_icons::insert('delete_iassign') . "</a>\n";
            $link_rename = "  <a href='#' onclick=\"rename_ilm('" . $iassign_ilm->id . "', '" . $filename . "'," . $fileid . ");\">" . iassign_icons::insert('rename_iassign') . "</a>\n";
            }
          }
        if (!in_array($filetype, $extensions_allow)) {
          $link_edit = "";
          $link_add_ilm_iassign = "";
          $link_add_ilm_tinymce = "";
          $link_add_ilm_atto = "";
          $link_filter = "";
          }

        if ($one_files->is_directory()) {
          $link_delete = "  <a href='#' onclick=\"delete_dir_ilm('" . $iassign_ilm->id . "', " . $fileid . ");\">" . iassign_icons::insert('delete_dir') . "</a>\n";
          $link_rename = "  <a href='#' onclick=\"rename_dir_ilm('" . $iassign_ilm->id . "', '" . $pathname . "'," . $fileid . ");\">" . iassign_icons::insert('rename_dir') . "</a>\n";
          $output .= "<tr><td>" . $check_select . $link_rename . $link_delete . "</td>
   <td><a href='" . $dirurl . "' title='" . get_string('dir', 'iassign') . $pathname . "'>" . iassign_icons::insert('dir') . " " . $pathname . "</a></td>
   <td><center>" . $author . "</center></td>
   <td><center>" . $timecreated . "</center></td>
   <td><center>" . $timemodified . "</center></td></tr>\n";
          }
        else if ($this->from == 'iassign') {
          $new_id = "";
          $new_class = "";
          if (isset($_SESSION['file_name']) && $filename == $_SESSION['file_name']) {
            $new_class = "<div id='new_file' style='position: absolute;'></div>"; unset($_SESSION['file_name']);
            $new_id = "id='id_new_blink' style='background-color: hsl(244,61%,90%);'";
            }

          $output .= "<tr $new_id><td>" . $new_class . " " . $check_select . $link_rename . $link_delete . $link_duplicate . $link_edit . $link_filter . $link_add_ilm_iassign . "</td>
   <td><a href='" . $fileurl . "' title='" . get_string('download_file', 'iassign') . " " . $filename . "'>" . $filename . "</a></td>
   <td><center>" . $author . "</center></td>
   <td><center>" . $timecreated . "</center></td>
   <td><center>" . $timemodified . "</center></td></tr>\n";
          }
        else if ($this->from == 'block') {
          $output .= "<tr><td>" . $check_select . $link_rename . $link_delete . $link_duplicate . $link_edit . $link_filter . "</td>
   <td><a href='" . $fileurl . "' title='" . get_string('download_file', 'iassign') . " " . $filename . "'>" . $filename . "</a></td>
   <td><center>" . $author . "</center></td>
   <td><center>" . $timecreated . "</center></td>
   <td><center>" . $timemodified . "</center></td></tr>\n";
          }
        else if ($this->from == 'tinymce') {
          $output .= "<tr><td>" . $check_select . $link_rename . $link_delete . $link_duplicate . $link_edit . $link_filter . $link_add_ilm_tinymce . "</td>
   <td><a href='" . $fileurl . "' title='" . get_string('download_file', 'iassign') . " " . $filename . "'>" . $filename . "</a></td>
   <td><center>" . $author . "</center></td>
   <td><center>" . $timecreated . "</center></td>
   <td><center>" . $timemodified . "</center></td></tr>\n";
          }
        else if ($this->from == 'atto') {
          $output .= "<tr><td>" . $check_select . $link_rename . $link_delete . $link_duplicate . $link_edit . $link_filter . $link_add_ilm_atto . "</td>
   <td><a href='" . $fileurl . "' title='" . get_string('download_file', 'iassign') . " " . $filename . "'>" . $filename . "</a></td>
   <td><center>" . $author . "</center></td>
   <td><center>" . $timecreated . "</center></td>
   <td><center>" . $timemodified . "</center></td></tr>\n";
          }
        }
      }

    $basename = explode("/", substr($dir_base, 0, strlen($dir_base) - 1));
    $dir_base = "";
    $header = "";

    // Get all files in the course context with the associated iLM extension (e.g., iLM=iVProgH => extension "*ivph")
    foreach ($basename as $value) {
      $dir_base .= $value . "/";
      $dir_id = $fs->get_file($context->id, 'mod_iassign', 'activity', 0, $dir_base, '.');
      if ($dir_id) {
        if ($value == "") {
          $fileurl = new moodle_url($this->url) . '&dirid=' . $dir_id->get_id() . '&ilmid=' . $ilmid;
          $header .= " <a href='$fileurl' title='" . get_string('dir', 'iassign') . "Home'>Home</a>\n";
        } else {
          $fileurl = new moodle_url($this->url) . '&dirid=' . $dir_id->get_id() . '&ilmid=' . $ilmid;
          $header .= " " . $OUTPUT->rarrow() . " <a href='$fileurl' title='" . get_string('dir', 'iassign') . "$dir_base'>$value</a>\n";
          }
        }
      } // foreach ($basename as $value)

    $html = $OUTPUT->heading(iassign_icons::insert('open_dir') . $header, 2, 'dirtitle', 'iassign');
    $select_all = "<input id='select_all' type='checkbox' onclick='select_all_ilm();'/>\n";

    $html .= "
  <table id='outlinetable' class='generaltable boxaligncenter' cellpadding='5' width='100%'>
  <tr><th class='header c1' width='20%'>$select_all " . get_string('functions', 'iassign') . "</th>
    <th class='header c1' width='*'>" . get_string('file', 'iassign') . "</th>
    <th class='header c1' width='10%'>" . get_string('author', 'iassign') . "</th>
    <th class='header c1' width='10%'>" . get_string('file_created', 'iassign') . "</th>
    <th class='header c1' width='10%'>" . get_string('file_modified', 'iassign') . "</th>\n " . $output . "
  </table>\n";
    $html .= "<form id='form_buttons' method='post' enctype='multipart/form-data'>\n";
    $html .= "<table width='100%'><tr>\n";
    $html .= "<td width='80%'><input type='button' value='" . get_string('new_dir', 'iassign') . "' onClick='new_dir_ilm();'>\n";
    if ($count_files != 0) {
      $html .= " &nbsp; | &nbsp; <input type='button' value='" . get_string('file_ilm_move', 'iassign') . "' onClick=\"move_selected_ilm('" . $iassign_ilm->id . "');\"/> &nbsp; | &nbsp; \n";
      $html .= "<input type='button' value='" . get_string('file_ilm_delete', 'iassign') . "' onClick=\"delete_selected_ilm('" . $iassign_ilm->id . "');\"> &nbsp; | &nbsp; \n";
      $html .= "<input type='button' value='" . get_string('file_ilm_export', 'iassign') . "' onClick='export_files_ilm();'>\n";
      }
    $html .= "</td><td  width='100%' align='right'><input type='button' value='" . get_string('file_ilm_recover', 'iassign') . "' onClick='recover_files_ilm();'>\n";
    $html .= $OUTPUT->help_icon('file_ilm_recover', 'iassign') . "</td></tr></table>\n";
    $html .= "</form>\n";

    print $code_javascript_ilm;

    print $html;
    } // function view_files_ilm($iassign_ilm_class, $extension)

  } // class ilm_manager


/// Class to insert of icons
class iassign_icons {

  static function insert ($icon) {
    global $CFG;
    $string = '<img src="' . $CFG->wwwroot . '/mod/iassign/icon/' . $icon . '.gif" title="' . get_string($icon, 'iassign') . '" alt="' . get_string($icon, 'iassign') . '"/>'; // "\n"
    return $string;
    }

  static function insertIT ($icon, $title, $text_alt) {
    global $CFG;
    $string = '<img src="' . $CFG->wwwroot . '/mod/iassign/icon/' . $icon . '.gif" title="' . get_string($title, 'iassign') . '" alt="' . get_string($text_alt, 'iassign') . '"/>'; // "\n"
    return $string;
    }

  }


/// Class with util functions for plugin manage
class iassign_utils {

  /// Function to return the filename extension
  //  @param  string The $filename as an string
  //  @return string Return an string (last group after '.')
  static function filename_extension ($filename) {
    if ($filename == null || $filename == '') {
      return null;
      }
    $itens = explode('.', $filename);
    $num = count($itens);
    if ($num < 2) {
      return null;
      }
    return $itens[$num - 1];
    }


  /// Use the PHP class/package ZipArchive to create a Zip file
  //  The version 1 with ZipArchive (with '$zip->addFile(...)') is NOT working:
  //      Warning: ZipArchive::close(): Failure to create temporary file: No such file or directory in /var/www/html/.../lib/filestorage/zip_archive.php on line 224
  //  @param $full_path_zip_filename, $vet_student_filename, $vet_student_content
  static function register_temporary_file ($full_path_zip_filename, $vet_student_filename, $vet_student_content) {
    //1 $zip = new ZipArchive(); //TODO this version uses '$zip->addFile(...)' and it is NOT working!
    $zip = new zip_archive(); //2
    $res = $zip->open($full_path_zip_filename);
    $total_vet = count($vet_student_filename);
    for ($ii=0; $ii<$total_vet; $ii++) {
      // $zip->addFile($vet_student_filename[$ii], $vet_student_content[$ii]); //1 to ZipArchive
      $zip->add_file_from_string($vet_student_filename[$ii], $vet_student_content[$ii]); //2 to zip_archive
      }

    $ret = $zip->close();
    iassign_utils::download_file($full_path_zip_filename);
    $filenameZip = $zip->filename;

    return array($ret, $zip);
    }


  static function download_file ($zip_filename) {
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private", false);
    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename=\"" . basename($zip_filename) . "\";");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . @filesize($zip_filename));
    set_time_limit(0);
    @readfile("$zip_filename") || die("File not found.");
    unlink($zip_filename);
    }


  /// Function for format filename remove special caracters
  //  @param string $text String for clean
  //  @param boolean $is_lowercase Boolean for apply lowercase in filename
  //  @return string Return an string in clean format
  static function format_filename ($text) {
    if ($text != '.') {
      //D $text = htmlspecialchars(urldecode($text));
      //D echo "locallib.php: format_filename: $text<br/>";
      if (is_object($text)) // with iAssign did not filtered text from JSON format
        $text = json_decode($text)->{current_language()};
      // $text = htmlspecialchars($text); // not good ideia, it results strange at 'iassign_ilm' field
      $array1 = array("(", ")", ",", "/", "\\", "!", "@", "#", "$", "&", "*", "+", "!", "?", ".", " ", ":",
                "á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "ẽ", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü",
                "ç", "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç");
      $array2 = array("", "", "_", "_", "_", "", "", "", "", "", "", "", "", "", "_", "_", "-",
                "a", "a", "a", "a", "a", "e", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u",
                "c", "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C");

      // E.g. text = "exerc_midpoint.course.geo"
      //              0123456789012345678901234
      $index_lastposition = strrpos($text, "."); // Find the position of the last occurrence of a substring in a string (21)
      $text2 = substr($text, 0, $index_lastposition); // Erase the final point and its extension ("exerc_midpoint.course")
      $ext = substr($text, $index_lastposition); // Point with extention (".geo")
      $text = str_replace(".", "", $text2) . $ext; // Clear any other point and redefine the name ("exerc_midpoint.course.geo")
      $text = str_replace($array1, $array2, $text); // Replace any letter with accent mark under the UTF8 format
      //$ext = strrpos($text, ".");
      //$text = str_replace(".", "", substr($text, 0, $ext)).substr($text, $ext);
      //$text = str_replace($array1, $array2, $text);
      }
    return $text;
    }


  /// Function to filter characters to the file name to iLM content
  //  @param string $text String for clean
  //  @param string $ext  String ot the extension of the iLM
  //  @return string Return an string in clean format
  static function filter_filename_extension ($filename, $ext) {
    if ($filename != '.') {
      $arrFrom = array("=","~","`","!","@","$","%","^","&","*","(",")");
      $arrTo   = array("_","","","","","","","","","","","","");
      $filename = str_replace($arrFrom, $arrTo, $filename);

      $filenameF = preg_replace('/[^\x20-\x7E]/','', $filename); // only ASCII - https://stackoverflow.com/questions/8781911/remove-non-ascii-characters-from-string

      $index_lastposition = strrpos($filenameF, "."); // find the position of the last occurrence of a substring in a string (21)
      if (!isset($index_lastposition) || $index_lastposition=="" || $index_lastposition<0) // Just in case (logic of function 'strrpos(...)' change, ensure to preserve name without '.'
        $index_lastposition = strlen($filenameF);
      $text = substr($filenameF, 0, $index_lastposition); // erase the final point and its extension ("exerc_midpoint.course")

      $text = str_replace(".", "_", $text); // erase all other "."
      $existext = substr($filenameF, $index_lastposition); // get the possibly extension

      $filenameF = $text . "." . $ext; // no matter, use the prefiz with oficial extension

      }
    else
      $filenameF = 'file_without_name.' . $ext; // Just in case... if the use do not enter a valid name, only entered '.'!

    return $filenameF;
    }


  /// Function for format pathname remove special caracters
  //  @param string $text String for clean
  //  @param boolean $is_lowercase Boolean for apply lowercase in pathname
  //  @return string Return an string in clean format
  static function format_pathname ($text, $is_lowercase = true) {
    $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç", "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç", "@", " ", "!", "?", ".");
    $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c", "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C", "-", "_", "", "", "");
    $text = str_replace($array1, $array2, $text);
    if ($is_lowercase)
      $text = strtolower($text);
    return $text;
    }


  /// Function to standardize file name from iLM original name (<iLM name>) and "base_name" ({iassign_ilm}.file_jar)
  /// Will be created the directory: ./mod/iassign/ilm/<iLM name>/<iLM version>/<base_name>
  //  @calledby ./mod/iassign/ilm_handlers/html5.php!save_ilm_by_xml($application_xml,$files_extract): twice times, first with 'name', second with 'file_jar'
  //             $application_file_name = iassign_utils::filename_from_iLM_name($application_xml->name,false);
  //             $file_jar = iassign_utils::filename_from_iLM_name($application_xml->file_jar, false); // in "locallib.php"
  //  @param string $text String to build the file neam
  //  @param boolean $is_lowercase Boolean for apply lowercase in pathname
  //  @return string Return an string in clean format
  static function filename_from_iLM_name ($ilm_name, $is_lowercase = true) {
    // Change special UTF-8 characters to the corresponding ASCII
    $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", 
      "ú", "ù", "û", "ü", "ç", "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï",
      "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç", "@", " ", "!", "?"); // in 2025/04 removed last item, dot: , "."
    $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o",
      "u", "u", "u", "u", "c", "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I",
      "O", "O", "O", "O", "O", "U", "U", "U", "U", "C", "-", "_", "", ""); // in 2025/04 removed last item, dot: , ""
    $index_firstposition_blank = strpos($ilm_name, " ");
    if ($index_firstposition_blank) { // truncate the name!
      $ilm_name = substr($ilm_name, 0, $index_firstposition_blank);
      }
    $index_firstposition_dots  = strpos($ilm_name, ":");
    if ($index_firstposition_dots) { // truncate the name!
      $ilm_name = substr($ilm_name, 0, $index_firstposition_dots);
      }
    $namefiltered = str_replace($array1, $array2, $ilm_name); // Erase UTF-8 specials to the simple ASCII
    if ($is_lowercase)
      $namefiltered = strtolower($namefiltered);
    return $namefiltered;
    }


  /// Function for insert version in the filename
  //  @param string $filename Name of file
  //  @return string Return the filename with version
  static function version_filename ($filename) {
    $array_filename = explode('.', $filename);
    if (count($array_filename) > 1)
      $filename = $array_filename[0] . '-' . date("Ymd-His") . '.' . $array_filename[1];
    else
      $filename = $array_filename[0] . '-' . date("Ymd-His");
    return $filename;
    }


  //TODO Retirar quando atualizar todo os iassign que estão com a tag &lt;ia_uc&gt;
  static function remove_code_message ($string) {
    $array = explode("&lt;ia_uc&gt;", $string);
    return $array[0];
    }

  }

// class iassign_utils
/// Class with language functions for plugin manage

class iassign_language {

  /// Function for return text in language or get default language (en)
  //  @param string $lang Code of language
  //  @param string $description JSON text content all languages
  //  @return string Return an string in the language selected
  static function get_description_lang ($lang, $descriptions) {
    $description_lang = "";
    $description = json_decode($descriptions);
    if ($description == null) {
      $description_lang = $descriptions;
      }
    else {
      if (isset($description->{$lang}))
        $description_lang = $description->{$lang};
      else
        if (isset($description->en)) //MOOC2014
          $description_lang = $description->en;
        else //MOOC2014
          $description_lang = "en"; //MOOC2014
      }
    return $description_lang;
    }


  /// Function for return all language supported by iLM
  //  @param string $descriptions JSON text content all languages
  //  @return string Return as string with all languages
  static function get_all_lang ($descriptions) {
    $langs = "";
    $description = json_decode($descriptions);
    if ($description) {
      foreach ($description as $key => $value) {
        $langs .= $key . " ; ";
        }
      $langs = substr($langs, 0, strlen($langs) - 3);
      }
    return $langs;
    }


  /// Function for convert json in xml //MOOC2014
  //  @param string $json JSON text
  //  @return string Return as string with xml tags
  static function json_to_xml ($json) {
    $xml = "";
    $json = json_decode($json);
    foreach ($json as $key => $value) {
      $xml .= "\n    <" . $key . ">" . $value . "</" . $key . ">";
      }
    return $xml;
    }

  }


// class iassign_language
/// Class with log functions for plugin manage.

class iassign_log {

  /// Function for insert log event
  //  @param string $action Code action of event
  //  @param string $information Text for describe action of event
  //  @param int $cmid Id of context module
  //  @param int $ilmid Id of iLM
  static function add_log ($action, $information = "", $cmid = 0, $ilmid = 0) {
    global $COURSE, $CFG, $USER, $DB;

    $newentry = new stdClass();
    $newentry->time = time();
    $newentry->userid = $USER->id;
    $newentry->ip = $_SERVER['REMOTE_ADDR'];
    $newentry->course = $COURSE->id;
    $newentry->cmid = $cmid;
    $newentry->ilmid = $ilmid;
    $newentry->action = $action;
    $newentry->info = $information;
    $newentry->language = current_language();
    $newentry->user_agent = $_SERVER['HTTP_USER_AGENT'];
    if (ini_get("browscap") && function_exists('get_browse')) {
      $browser = get_browse(null, true);
      $newentry->javascript = $browser['javascript'];
      $newentry->java = $browser['javaapplets'];
      }
    if (!$newentry->id = $DB->insert_record("iassign_log", $newentry))
      print_error('error_add_log', 'iassign');
    } // static function add_log($action, $information = "", $cmid = 0, $ilmid = 0)

  } // class iassign_log
