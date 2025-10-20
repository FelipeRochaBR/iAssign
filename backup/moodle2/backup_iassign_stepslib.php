<?php

/**
 * Define all the backup steps that will be used by the "backup_iassign_activity_task.class.php"
 * to make a copy of all iAssign activities (mod/iassign).
 * 
 * After 2021 we stated to use the file associated to each activity with: {iassign_statement}.filesid={files}.id and {iassign_statement}.id={files}.itemid
 * 
 * It builds the 'iassign.xml' with XML code to register block activities {iassign} and {iassign_statement} activities.
 * 
 * From 2.0 version (2023/04/25) we use the trick to (artificially) register on {iassign_statement}
 * tags (<statement...>...</statement>) additional fields from {iassign_ilm}, with the name of
 * the used iLM (fields: name, version, type, extension, url). See 'define_structure()' with
 * these artificial field: 'ilm_name', 'version', 'type', 'extension', 'url'.
 * 
 * These additional fields will be used by 'restore_iassign_stepslib.php' to search on the target
 * Moodle the better corresponding iLM.
 *
 * @author Patricia Alves Rodrigues
 * @author Leo^nidas de Oliveira Branda~o
 * @version v 2.0 2023 - new fields; comments on new association {iassign_statement}/{files} ; trick to recover iLM on target
 * @version v 1.0 2012
 * @package mod_iassign_backup
 * @since 2012
 * @cite iMath (http://www.matematica.br) - LInE (www.usp.br/line) - Computer Science Dep. of IME-USP (Brazil)
 * 
 * <b>License</b> 
 *  - http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *  
 * @see backup_activity_structure_step
 */

//DEBUG Initial
//DEBUG To test 'backup_iassign_stepslib.php' alone through URL http://localhost/...(moodle).../mod/iassign/backup/moodle2/backup_iassign_stepslib.php //DDD
//DEBUG Comment the line bellow and uncomment the next ones until //DEBUG final

defined('MOODLE_INTERNAL') || die();
//DDD define('SAW', 'dev_saw'); //DDD para Paprika3
//DDD // 
define('SAW', 'saw'); //DDD para Paprika4
/// Moodle core defines constant MOODLE_INTERNAL which shall be used to make sure that the script is included and not called directly.
//DDD echo "backup_iassign_stepslib.php: inicio<br/>\n";
//DDD require_once "/var/www/html/" . SAW . "/config.php"; //DDD
//DDD require_once "/var/www/html/" . SAW . "/lib/filestorage/file_progress.php"; //DDD interface file_progress : no ./backup/util/plan/backup_structure_step.class.php
//DDD require_once "/var/www/html/" . SAW . "/backup/util/interfaces/executable.class.php"; //DDD
//DDD require_once "/var/www/html/" . SAW . "/backup/util/interfaces/loggable.class.php"; //DDD
//DDD require_once "/var/www/html/" . SAW . "/backup/util/plan/base_step.class.php"; //DDD abstract class base_step implements executable, loggable 
//DDD require_once "/var/www/html/" . SAW . "/backup/util/plan/backup_step.class.php"; //DDD abstract class backup_step extends base_step
//DDD require_once "/var/www/html/" . SAW . "/backup/util/plan/backup_execution_step.class.php"; //DDD abstract class backup_execution_step extends backup_step
//DDD require_once "/var/www/html/" . SAW . "/backup/util/plan/backup_structure_step.class.php"; //DDD abstract class backup_structure_step extends backup_step
//DDD require_once "/var/www/html/" . SAW . "/backup/moodle2/backup_stepslib.php"; //DDD // backup_activity_structure_step extends backup_execution_step
//DDD require_once "/var/www/html/" . SAW . "/backup/util/structure/backup_nested_element.class.php"; //DDD
//DDD require_once "/var/www/html/" . SAW . "/backup/moodle2/backup_stepslib.php"; //DDD
//DEBUG final

//DEBUG @see './mod/iassign/db/upgrade.php'
//DEBUG @param: $filetype1 to special indentification (e.g. "student", "log", "sendemail")
//DEBUG @return 1 in case of success; -1 in case do not overwrite
function writeContentBIS ($filetype1, $pathbase, $outputFile, $msgToRegister) {
  global $CFG, $GROUP, $WRITEMSG, $OVERWRITE;
  $pathbase = $CFG->dirroot . "/mod/iassign/ilm_debug/"; // "/moodle/mod/iassign/ilm_debug/", $CFG->dirroot defined in "./lib/setup.php"
  $outputFile = $pathbase . $outputFile;
  if (!is_writable($pathbase)) { // TRUE se arquivo existe e pode ser escrito
    // Just ignore... You must change the write permission to 'www-data' on './mod/iassign/ilm_debug/'
    // print "Erro! Problema de acesso ao servidor! Por favor, avise ao administrador (<tt>$pathbase</tt> nao acessivel para escrita).<br/>"; //  . $file_debug . "
    // exit(0);
    }
   // To write: verify if the file does not exists or have permission to overwrite
   if (is_file($outputFile)) { // already exist this file
     $fpointer = fopen($outputFile, "a"); // write - if executed, it clear the previou content at this file
     } // if (is_file($outputFile))
   else
     $fpointer = fopen($outputFile, "w"); // write - if executed, it clear the previou content at this file
   if (!$fpointer) {
     // $file_debug .= "Erro: nao foi possivel abrir o arquivo (" . $outputFile . ")!<br/>\n";
     // it was not possible to open the file '$completfilepath" . $file_name . "'!<br/>\n";
     // print "<br/>" . $file_debug . "<br/>\n";
     return 0;
     }
   else {
     fwrite($fpointer, $msgToRegister . "\n");
     fclose($fpointer);
     } // error
  return 1;
  } // function writeContentBIS($filetype1, $pathbase, $outputFile, $msgToRegister)
  
  

// Define the complete choice structure for backup, with file and id annotations
// @see backup_activity_structure_step
class backup_iassign_activity_structure_step extends backup_activity_structure_step {


  // Considering MBZ backup file, this script will build each file ""iassign_x/iassign.xml" with the iAssign block, where "x" is the ={iassign}.id
  // The "iassign_x/iassign.xml" file will have several information under the tag "activity", e.g.
  // '<activity id="1301" moduleid="17037" modulename="iassign" contextid="42017">'
  // - id="1301"           : is the {iassign}.id
  // - moduleid="17037"    : is {course_modules}.id, which has the property {course_modules}.instance == {iassign}.id
  // - modulename="iassign": is {modules}.name == "iassign", that is {modules} with iAssign definition
  // - contextid="42017"   : is {context}.id, which has the property {context}.id == {course_modules}.id
  // Inside the tag "iassign | statements | statement | iassign_submissions | iassign_submission" we have each student submission
  // with its contents under "answer" tag.
  // To create each item, it is necessary a "new backup_nested_element('name' [, array])"
  // For instance, to get {context}.id (AS contextid), {course_modules}.id, {course_modules}.instance, {context}.instanceid of one
  // particular iAssign block {iassign}.id such that {iassign}.id == {course_modules}.instance you must use (e.g. 17037):
  //  SELECT {context}.id AS contextid, {course_modules}.id, {course_modules}.instance, {context}.instanceid FROM
  //  {course_modules, {context WHERE {course_modules}.id = {context}.instanceid AND {course_modules}.course = 2 AND {course_modules}.id = 17037 



  // Define the structure for the iassign activity
  // @return void Return the root element (choice), wrapped into standard activity structure
  protected function define_structure () { //DDD function define_structure ()
    // To know if we are including userinfo
    $userinfo = $this->get_setting_value('userinfo'); // ./backup/util/plan/base_step.class.php : $this->task->get_setting_value($name);

    // Each iAssign block of activities {iassign} : {course_modules}.instance={iassign}.id AND {course_modules}.course={iassign}.course
    // ./backup/util/structure/backup_nested_element.class.php : class backup_nested_element extends base_nested_element implements processable
    // Restored by "restore_iassign_stepslib.php!process_iassign($data)"
    $iassign = new backup_nested_element('iassign', array('id'), array('name',
      'course', 'intro', 'introformat', 'activity_group', 'grade',
      'timeavailable', 'timedue', 'preventlate', 'test', 'max_experiment'));

    // It creates the tag "<statements>...</statements>" to each {iassign} element
    $statements = new backup_nested_element('statements');

    // It creates the tag '<statement id="X">... </statement>' to each {iassign_statement} : {iassign_statement}.iassignid={iassign}.id
    // Restored by "restore_iassign_stepslib.php!process_iassign_statement($data)"
    // ATTENTION, we need additional fields from {iassign_ilm} 'ilm_name', 'version', 'type', 'extension' to restore each
    // {iassign_statement} with a correct field 'iassign_ilmid' (that depends on the iLM installed in the iAssign/Moodle destiny)
    $statement = new backup_nested_element('statement', array('id'), array('name',
      'iassignid', 'type_iassign', 'proposition', 'author_name', 'author_modified_name',
      'iassign_ilmid', 'file', 'grade', 'timecreated', 'timemodified', 'timeavailable',
      'timedue', 'preventlate', 'test', 'special_param1', 'position', 'visible',
      'max_experiment', 'dependency', 'automatic_evaluate', 'show_answer', 'store_all_submissions', 'filesid',
      'ilm_name', 'version', 'type', 'extension', 'url'));
    // fields from {iassign_statement}
    $sql_fields_iassign_statement =
      " ias.id, ias.name, ias.iassignid, ias.type_iassign, ias.proposition, ias.author_name, ias.author_modified_name, " .
      "ias.iassign_ilmid, ias.file, ias.grade, ias.timecreated, ias.timemodified, ias.timeavailable, " .
      "ias.timedue, ias.preventlate, ias.test, ias.special_param1, ias.position, ias.visible, " .
      "ias.max_experiment, ias.dependency, ias.automatic_evaluate, ias.show_answer, ias.store_all_submissions, ias.filesid, ";
    // additional fields from {iassign_ilm}
    $sql_additional_fields_ilm = " ilm.name as ilm_name, ilm.version, ilm.type, ilm.extension, ilm.url ";

    // ./backup/util/plan/base_step.class.php : 102 : "return $this->task->get_setting_value($name);" / $this->task=./backup/util/plan/base_task.class.php:
    // ./backup/util/plan/base_task.class.php : 106 : public function get_setting_value($name): return $this->get_setting($name)->get_value(); / ./backup/moodle2/backup_activity_task.class.php!get_setting($name): return parent::get_setting($name)
    // ./backup/moodle2/backup_activity_task.class.php : 250 : public function get_setting($name): return parent::get_setting($name); / ./backup/util/plan/base_task.class.php
    // ./backup/util/plan/base_task.class.php :  97 : public function get_setting($name): return $this->plan->get_setting($name);
    // ./backup/util/plan/base_plan.class.php : 124 : public function get_setting($name): if (isset($this->settings[$name]))

    $iassign_submissions = new backup_nested_element('iassign_submissions');

    // Each student submission (to each {iassign_statement}) : {iassign_submission}.iassign_statementid = {iassign_statement}.id
    $iassign_submission = new backup_nested_element('iassign_submission', array('id'), array('iassign_statementid',
      'userid', 'timecreated', 'timemodified', 'grade', 'teacher', 'answer', 'experiment', 'status', 'previous_grade'));
    //DEBUG foreach ($iassign_submission as $one_statement) $msg_ias .= " - " . $one_statement->id . "," . $one_statement->iassign_statementid .
    //DEBUG   "," . $one_statement->userid . "," . $one_statement->timecreated . "\n";

    $iassign_submission_comments = new backup_nested_element('iassign_submission_comments');

    // Each comment associated with one {iassign_statement} of one student : {iassign_submission_comment}.iassign_submissionid = {iassign_submission}.id
    $iassign_submission_comment = new backup_nested_element('iassign_submission_comment', array('id'), array('iassign_submissionid',
      'comment_authorid', 'timecreated', 'comment', 'return_status', 'receiver'));

    // Build the tree
    $iassign->add_child($statements);
    $statements->add_child($statement);

    $statement->add_child($iassign_submissions);
    $iassign_submissions->add_child($iassign_submission);

    $iassign_submission->add_child($iassign_submission_comments);
    $iassign_submission_comments->add_child($iassign_submission_comment);

    // ./backup/util/structure/backup_nested_element.class.php: Only elements having final elements can set source
    //   Define sources: associate to iAssign main table {iassign} and use as foreign key the {course_modules}.instance
    // ./backup/backup.class.php: VAR_ACTIVITYID = -21 => $this->find_first_parent_by_name('id');
    //   The "backup::VAR_ACTIVITYID" says to use the 'id' from {iassign} table as 'id' being backup
    $iassign->set_source_table('iassign', array('id' => backup::VAR_ACTIVITYID));

    // Select all {iassign_statement} activities with iassignid == {iassign}.id: all {iassign_statement}.* fields
    // Includes the additional {iassign_ilm} field in order to restore with the correct iLM in Moodle destination:
    // ilm_name, version, type, extension, url
    // @see ./backup/util/structure/backup_nested_element.class.php : public function set_source_sql($sql, $params)
    // $statement->set_source_sql('SELECT * FROM {iassign_statement} WHERE iassignid = ?', array(backup::VAR_PARENTID)); // The value of the first parent id found in the structure
    $sql_query = 'SELECT ' . $sql_fields_iassign_statement . $sql_additional_fields_ilm . 
      ' FROM {iassign_statement} AS ias, {iassign_ilm} AS ilm' .
      ' WHERE ilm.id = ias.iassign_ilmid AND ias.iassignid = ?';
    $statement->set_source_sql($sql_query, array(backup::VAR_PARENTID)); // The value of the first parent id found in the structure

    if ($userinfo) {
      $iassign_submission->set_source_table('iassign_submission', array('iassign_statementid' => backup::VAR_PARENTID));
      $iassign_submission_comment->set_source_table('iassign_submission_comment', array('iassign_submissionid' => backup::VAR_PARENTID));
      }

    // Define id annotations: during processing, store them in the table {backup_ids_temp} (if existing...)
    $iassign_submission->annotate_ids('user', 'userid'); // {iassign_submission}.userid = {user}.id
    $iassign_submission->annotate_ids('user', 'teacher'); // {iassign_submission}.teacher = {user}.id
    $iassign_submission_comment->annotate_ids('user', 'comment_authorid'); // {iassign_submission_comment}.comment_authorid = {user}.id

    //DEBUG: See '/var/www/html/" . SAW . "/backup/util/structure/backup_nested_element.class.php ! public get_source_sql()"
    /*/D*/ $sql_query = $statement->get_source_sql(); //DDD $testa = new backup_iassign_activity_structure_step("",""); //DDD
    /*/D*/ $timeWrite = date('Y_m_d_H_i_s'); // Year_Month_Day_Hour_Minutes_Seconds
    /*/D*/ echo "backup_iassign_stepslib.php: writeContentBIS 'backup_1_" . $timeWrite . ".txt'<br/>\n";
    /*/D*/ $msgDstatement = "";
    /*/D*/ foreach ($statement as $itemS) { $msgDstatement .= "[";
    /*/D*/   foreach ($itemS as $item) $msgDstatement .= (string)$item . "; ";
    /*/D*/   $msgDstatement .= "]"; }
    /*/D*/ $msg = "backup_iassign_stepslib.php : backup::VAR_ACTIVITYID=" . backup::VAR_ACTIVITYID . ", set_source_sql(iassign_statement}\n" . $sql_query . "\n";
    /*/D*/ writeContentBIS("", "", "backup_1_" . $timeWrite . ".txt", $msg . $msgDstatement); //DDD

    // Define file annotations: to table {files} (associated with {iassign_statement})
    $iassign->annotate_files('mod_iassign', 'exercise', null); // This file area hasn't itemid

    return $this->prepare_activity_structure($iassign);
    } // protected function define_structure()

  } // class backup_iassign_activity_structure_step extends backup_activity_structure_step

/* //DEBUG
 $testa = new backup_iassign_activity_structure_step("",""); //DDD
 $timeWrite = date('Y_m_d_H_i_s'); // Year_Month_Day_Hour_Minutes_Seconds
 $sql_query = "SELECT * FROM {iassign} WHERE id = 2";
 $iassign = $DB->get_records_sql($sql_query);
 $testa->define_structure(); // $iassign);
 $msg = "Teste";
 writeContentBIS("", "", "backup_" . $timeWrite . ".txt", $msg); //DDD
*/
