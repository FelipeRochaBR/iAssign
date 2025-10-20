<?php

/**
 * Define all the restore steps that will be used by the restore_iassign_activity_task
 * 
 * It uses the 'iassign.xml' with XML code to rebuild the block activities {iassign} and {iassign_statement} activities.
 * 
 * After 2021 we stated to use the file associated to each activity with: {iassign_statement}.filesid={files}.id and {iassign_statement}.id={files}.itemid
 * 
 * From 2.0 version (2023/04/25) we use the trick to (artificially) register on {iassign_statement}
 * tags (<statement...>...</statement>) additional fields from {iassign_ilm}, with the name of
 * the used iLM (fields: name, version, type, extension, url).
 * These additional fields was artificially introduced by 'backup_iassign_stepslib.php' to the bellow function
 * "process_iassign_statement($data)" recover the better iLM to be associated with each activity
 * (it uses auxiliary function "get_destination_ilmid($data)").
 * 
 * @author Patricia Alves Rodrigues
 * @author Leo^nidas de Oliveira Branda~o
 * @version v 2.0 2023
 * @version v 1.0 2012
 * @package mod_iassign_backup
 * @since 2012
 * @cite iMath (http://www.matematica.br) - LInE (www.usp.br/line) - Computer Science Dep. of IME-USP (Brazil)
 * 
 * <b>License</b> 
 *  - http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *  
 * @see      restore_activity_structure_step
 * @calledby /backup/util/plan/restore_structure_step.class.php: restore_iassign_activity_structure_step->process_iassign_statement()
 * 
 */

// Moodle core defines constant MOODLE_INTERNAL which shall be used to make sure that the script is included and not called directly.
defined('MOODLE_INTERNAL') || die();

/// Define the complete assignment structure for restore, with file and id annotations.
//  @see restore_activity_structure_step
class restore_iassign_activity_structure_step extends restore_activity_structure_step {

  // /var/www/html/saw_limpo/backup/moodle2/restore_plugin.class.php
  // + abstract class restore_plugin :
  //   * protected function add_related_files($component, $filearea, $mappingitemname, $filesctxid = null, $olditemid = null)

  // /var/www/html/saw_limpo/backup/moodle2/restore_subplugin.class.php <- 'add_related_files(.)' alterei
  //   * public function add_related_files($component, $filearea, $mappingitemname, $filesctxid = null, $olditemid = null)

  // /var/www/html/saw_limpo/backup/util/plan/restore_structure_step.class.php <- 'add_related_files(.)' alterei!
  // + abstract class restore_structure_step extends restore_step
  //   * public function add_related_files($component, $filearea, $mappingitemname, $filesctxid = null, $olditemid = null) : is no called with mappingitemname="files"...
  // /var/www/html/saw_limpo/backup/moodle2/restore_stepslib.php
  // + abstract class restore_activity_structure_step extends restore_structure_step

  var $source_course_id = null; // to register the {course}.id from the original course
  var $old_id = null; // to register the old {iassign_statement}.id

  // Define the structure of the restore workflow
  // @return void Adds support for the 'exercise' path that is common to all the activities.
  protected function define_structure () {
    $paths = array();
    // To know if we are including userinfo
    $userinfo = $this->get_setting_value('userinfo');

    // Define each element separated
    $paths[] = new restore_path_element('iassign', '/activity/iassign');
    $paths[] = new restore_path_element('iassign_statement', '/activity/iassign/statements/statement');
    //x $paths[] = new restore_path_element('iassign', '/exercise/iassign'); //2025
    //x $paths[] = new restore_path_element('iassign_statement', '/exercise/iassign/statements/statement'); //2025
      
    if ($userinfo) {
      $iassign_submissions = new restore_path_element('iassign_submission', '/activity/iassign/statements/statement/iassign_submissions/iassign_submission');
      $paths[] = $iassign_submissions;
      $iassign_submission_comments = new restore_path_element('iassign_submission_comment', '/activity/iassign/statements/statement/iassign_submissions/iassign_submission/iassign_submission_comments/iassign_submission_comment');
      $paths[] = $iassign_submission_comments;
      }
    return $this->prepare_activity_structure($paths);
    }

  // Process an iAssign activity (restore it)
  // @param object $data The data in object form
  protected function process_iassign ($data) {
    global $DB, $CFG;
    $data = (object) $data;
    $oldid = $data->id; // source {iassign}.id
    $this->old_id = $oldid; // to register the old {iassign_statement}.id, to be used by 'after_execute()' bellow //xxxxxxxxxxxxxxx

    // In the first time reaching this point (first iAssign activity processed), register the source {course}.id
    // {iassign}.course = {course}.id - {iassign_statement}.iassignid = {iassign}.id
    if ($this->source_course_id==null)
      $this->source_course_id = $data->course; // source {course}.id
    $data->course = $this->get_courseid(); // target {course}.id: new {iassign} uses the new {course}.id

    if ($CFG->debugdisplay) { // debug messages is turned on
      print "restore_iassign_stepslib.php!process_iassign(.): this->source_course_id=" . $this->source_course_id . " is the {course}.id of source course! destination course.id=" . $data->course . "<br/>"; //DEBUG
      }

    // Create a copy 
    $newitemid = $DB->insert_record('iassign', $data); // insert in 'iassign'
    $this->apply_activity_instance($newitemid);
    }

  // Considering the destination Moodle, try to find the nearest iLM to a given {iassign_statement} activity
  // Given artificial {iassign_statement} fields provided by 'backup_iassign_stepslib.php!define_structure()' (ilm_name,version,type,extension)
  // find the destination Moodle the closest iLM (considering those matching lower(ilm_name) and lower(extension)).
  // @param $data is from table {iassign_statement}
  protected function get_destination_ilmid ($data) {
    global $DB, $CFG;
    // Since iAssign X the backup of each {iassign_statement} receive the additional fields: "ilm_name", "version", "type", "extension"
    if (!isset($data->ilm_name) || !isset($data->version) || !isset($data->type) || !isset($data->extension)) {
      if ($CFG->debugdisplay) {
        print "Error in mod/iassign/backup/moodle2/restore_iassign_stepslib.php!get_destination_ilmid(data): ilm_name, version, type or extension empty<br/>\n";
        print " - ilm_name=" . (!isset($data->ilm_name)?'null':$data->ilm_name) . ", version=" . (!isset($data->version)?'null':$data->version) . ", type=" .
          (!isset($data->type)?'null':$data->type) . ", extension=" . (!isset($data->extension)?'null':$data->extension) . "<br/>\ndata=";
        var_dump($data); // ja imprime
        }
      return NULL; // launch warning the Admin that iAssign is missing some iLM...
      }
    // Select 1 from table_name will return false if the table does not exist.
    $sql_query = "SELECT 1 FROM {iassign} LIMIT 1";
    $iassign = $DB->get_records_sql($sql_query);
    if ($iassign == FALSE) { // iAssign is not installed!
      if ($CFG->debugdisplay) {
        print "Error in mod/iassign/backup/moodle2/restore_iassign_stepslib.php!get_destination_ilmid(data): iAssign is NOT installed yet! Ask admin to install it<br/>\n";
        }
      return NULL; // launch warning the Admin that iAssign is missing some iLM...
      }

    // Artificials field inserted into the {iassign_statement} entry:
    $origin_ilm_name = strtolower($data->ilm_name);   // field {iassign_ilm}.name
    $origin_version = strtolower($data->version);     // field {iassign_ilm}.version
    $origin_type = strtolower($data->type);           // field {iassign_ilm}.type
    $origin_extension = strtolower($data->extension); // field {iassign_ilm}.extension
    // $iassign_ilm = $DB->get_records('iassign_ilm', array('name' => $origin_ilm_name)); // search for all with this 'name'
    // Search in the current Moodle (target) a iLM similar to the origin Moodle
    $sql_query = "SELECT id, LOWER(name) AS ilm_name, LOWER(version) AS version, LOWER(type) AS type, LOWER(extension) AS extension " .
      " FROM {iassign_ilm} WHERE name='" . $origin_ilm_name . "' AND LOWER(extension)='" . $origin_extension . "' ORDER BY id DESC";
    $iassign_ilm = $DB->get_records_sql($sql_query);
    foreach ($iassign_ilm as $one_iassign_ilm) { // preference to the same 'type' and 'version'
      if ($one_iassign_ilm->type == $origin_type && $one_iassign_ilm->version == $origin_version)
        return $one_iassign_ilm->id; // return the {iassign_ilm} with bigger ID
      }
    foreach ($iassign_ilm as $one_iassign_ilm) { // accept only the same 'type'
      if ($one_iassign_ilm->type == $origin_type)
        return $one_iassign_ilm->id; // return the {iassign_ilm} with bigger ID
      }
    foreach ($iassign_ilm as $one_iassign_ilm) { // accept any iLM...
      return $one_iassign_ilm->id; // return the {iassign_ilm} with bigger ID
      }
    return NULL; // launch warning the Admin that iAssign is missing some iLM...
    }


  // Process a {iassign_statement} restoration
  // Table {iassign_statement} : id, name, iassignid, type_iassign, proposition, author_name, author_modified_name, iassign_ilmid, file ..., fileid
  // Attention, in order to perform the correct association {iassign_ilm}.id from origin to destination
  // the 'backup_iassign_stepslib.php' used all {iassign_statement} field besides some from {iassign_ilm}
  // on each '<statement id="X">... </statement>' in 'iassign.xml'.
  // From the original Moodle was used {iassign_statement}.* and from {iassign_ilm}: name as ilm_name, version, type, extension
  // @param object $data The data in object form
  // @calledby /backup/util/plan/restore_structure_step.class.php : final public function process($data) : $rdata=$object->$method($data['tags']);
  // @calledby /backup/util/plan/restore_structure_step.class.php : line 418, call to restore_iassign_activity_structure_step->after_execute()
  protected function process_iassign_statement ($data) {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/mod/iassign/locallib.php');
    $data = (object) $data;

    // Since iAssign X the backup of each {iassign_statement} receive the additional fields: "ilm_name", "version", "type", "extension"
    if (isset($data->ilm_name)) $ilm_name = $data->ilm_name;
    else $ilm_name = "empty";

    // If we find the iLM on destination, change the {iassign_statement}.iassign_ilmid = {iassign_ilm}.id
    $destination_ilmid = $this->get_destination_ilmid($data); //TODO: give an error message in case of iLM not found NULL
    if ($destination_ilmid != NULL)
      $data->iassign_ilmid = $destination_ilmid; // fix {iassign_statement}.iassign_ilmid from origin to destination
    else { // launch warning the Admin that iAssign is missing some iLM...
      if ($CFG->debugdisplay)
        print "process_iassign_statement(data): returned empty from get_destination_ilmid(.)<br/>\n";
      $data->iassign_ilmid = 0; // point to the first one...
      $destination_ilmid = 0; // point to the first one...
      $error_msg  = "Potential problem: the backup file has one iAssign activity to which the iLM " . $ilm_name . " was not found.<br/>\n";
      $error_msg .= "Please, try to install " . $ilm_name . " from the URL " . $data->url;
      // With any of the bellow warning, the restoring process is interrupted!
      // throw new restore_step_exception($error_msg);
      // debugging('Error in restore_iassign_stepslib.php!process_iassign_statement(.): the iLM ' . $ilm_name . ' is missing!', DEBUG_DEVELOPER);
      }
    $oldid = $data->id; // from source {course}.id: old {iassign_statement}.id

    $msgD = "";
    $msgD1 = "restore_iassign_stepslib.php!process_iassign_statement(.): <br/>" .
             " - original {iassign_statement}.id=" . $oldid . "; this->source_course_id=" . $this->source_course_id . "<br/>\n" . //DEBUG
             " - original {iassign_ilm}.name=" . $ilm_name . " (nome usado para buscar iMA semelhante) destination_ilmid=" . $destination_ilmid . "<br/>\n"; //DEBUG
    if ($CFG->debugdisplay) { // debug messages is turned on
      print $msgD1; //DEBUG
      }

    $msgD .= $msgD1; // append

    // In /var/www/html/saw_limpo/backup/moodle2/restore_plugin.class.php
    // + abstract class restore_plugin: protected 'apply_date_offset(.)' is a Moodle function to ajust source date to current date

    // In /var/www/html/saw_limpo/backup/moodle2/restore_stepslib.php
    // + abstract class restore_activity_structure_step extends restore_structure_step : 4873/6612

    // In /var/www/html/saw_limpo/backup/util/plan/restore_structure_step.class.php : 30/549
    // + abstract class restore_structure_step extends restore_step: 
    //   public function get_new_parentid($itemname): 199/549 - Returns the latest (parent) old id mapped by one pathelement
    //   public function get_mappingid($itemname, $oldid, $ifnotfound = false): 210/549
    //     // Return the new id of a mapping for the given itemname - itemname is the Moodle table name as {user}, {files}
    //     // @param string $itemname the type of item - "the type of item you wish to backup - for example, 'activities and resources'" - "type of item" e' o tipo do item  'activities and resources' - como "iassign", "files", "assignment"...???
    //     // @param int $oldid the item ID from the backup	
    // + 

    // /var/www/html/saw_limpo/backup/moodle2/restore_plugin.class.php
    //  * To send ids pairs to backup_ids_table and to store them into paths
    //  * This method will send the given itemname and old/new ids to the backup_ids_temp table, and, at the same time, will save the new id
    //  * into the corresponding restore_path_element for easier access by children. Also will inject the known old context id for the task
    //  * in case it's going to be used for restoring files later
    //  + protected function set_mapping($itemname, $oldid, $newid, $restorefiles = false, $filesctxid = null, $parentid = null)

    $str_aux = "";
    //$aux_file = $this->get_mappingid('files', $data->file); // get the new ID corresponding to the old {files}.id=$data->file
    //$files_id = $this->get_mappingid('files', $data->filesid); //TODO: depois precisa processar {files}.itemid para este {iassign_statement}.id!
    //if (empty($aux_file) || !$aux_file) { $str_aux.="(1)"; $aux_file = $this->get_mappingid('iassign_statement', $data->file); } // get the new ID corresponding to the old {files}.id=$data->file
    //if (empty($files_id) || !$files_id) { $str_aux.="(2)"; $files_id = $this->get_mappingid('iassign_statement', $data->file); } // get the new ID corresponding to the old {files}.id=$data->file
    $aux_file = $data->file; // get the new ID corresponding to the old {files}.id=$data->file
    $files_id = $data->filesid; //TODO: depois precisa processar {files}.itemid para este {iassign_statement}.id!

    $msgD1 = " - original {iassign_statement}.id=" . $data->id . " (old id) :: " . //DEBUG
             " - get_new_parentid('files')=" . $this->get_new_parentid('files') . "'<br/>\n" . //DEBUG
             " - original {iassign_statement}.file=" . $data->file . " -> " . $data->id . " :: str_aux=" . $str_aux . ", after get_mappingid to 'file' id='" . $aux_file . "'<br/>\n" . //DEBUG
             " - original {iassign_statement}.filesid=" . $data->filesid . " :: str_aux=" . $str_aux . ", after get_mappingid to 'filesid' id='" . $files_id . "'<br/>\n"; //DEBUG
    $msgD1 .= " * timeavailable=" . $data->timeavailable . " -> apply_date_offset(timeavailable)=" . $this->apply_date_offset($data->timeavailable) . "<br/>\n";
    $msgD1 .= " * timedue=" . $data->timedue . " -> apply_date_offset(timedue)=" . $this->apply_date_offset($data->timedue) . "<br/>\n";
    // Open discussion if is allowed to roll date. See capability "moodle/restore:rolldates"
    // https://tracker.moodle.org/browse/MDL-78420
    if ($CFG->debugdisplay) { // debug messages is turned on
      print $msgD1; //DEBUG
      }
    $msgD .= $msgD1; // append

    // All field to table {iassign_statement}.
    // The {iassign_statement}.id will be generated by "$DB->insert_record('iassign_statement', $new_iassign_statement)"
    $new_iassign_statement = new stdClass();
    $new_iassign_statement->name                  = $data->name                  ;
    $new_iassign_statement->iassignid             = $this->get_new_parentid('iassign'); // replace {iassign_statement}.iassignid from the origin to the destination
    $new_iassign_statement->type_iassign          = $data->type_iassign          ;
    $new_iassign_statement->proposition           = $data->proposition           ;
    $new_iassign_statement->author_name           = $data->author_name           ;
    $new_iassign_statement->author_modified_name  = $data->author_modified_name  ;
    $new_iassign_statement->iassign_ilmid         = $destination_ilmid; // $this->get_mappingid('iassign_ilm', $data->iassign_ilmid); // ideally it was changed above (with iLM on destination)
    $new_iassign_statement->file                  = $data->id; // old {iassign_statement}.id eventually will be used by 'after_execute()' to recover {files} associated to this {iassign_statement} //2025 $files_id; // $this->get_mappingid('files', $data->file);
    $new_iassign_statement->grade                 = $data->grade                 ;
    $new_iassign_statement->timecreated           = $data->timecreated           ;
    $new_iassign_statement->timemodified          = $data->timemodified          ;
    $new_iassign_statement->timeavailable         = $this->apply_date_offset($data->timeavailable);
    $new_iassign_statement->timedue               = $this->apply_date_offset($data->timedue);
    $new_iassign_statement->preventlate           = $data->preventlate           ;
    $new_iassign_statement->test                  = $data->test                  ;
    $new_iassign_statement->special_param1        = $data->special_param1        ;
    $new_iassign_statement->position              = $data->position              ;
    $new_iassign_statement->visible               = $data->visible               ;
    $new_iassign_statement->max_experiment        = $data->max_experiment        ;
    $new_iassign_statement->dependency            = $data->dependency            ;
    $new_iassign_statement->automatic_evaluate    = $data->automatic_evaluate    ;
    $new_iassign_statement->show_answer           = $data->show_answer           ;
    $new_iassign_statement->store_all_submissions = $data->store_all_submissions ;
    $new_iassign_statement->filesid               = $files_id;

    $timeWrite = date('Y_m_d_H_i_s'); // Year_Month_Day_Hour_Minutes_Seconds
    $filenamewrite = "output_restore_iassign_stepslib_" . $timeWrite . ".txt";
    // ./mod/iassign/ilm_debug/escreva.php: writeContent($filetype1, $pathbase, $outputFile, $msgToRegister)
    require_once($CFG->dirroot . "/mod/iassign/ilm_debug/escreva.php"); //leo REMOVER! xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx$
    $resp = writeContent("", "", $filenamewrite, $msgD); // ($filetype1, $pathbase, $outputFile, $msgToRegister) //leo REMOVER!
    //$ss=0; for ($i=0; $i<10000; $i++) $ss = $ss+$i;
    //TODO: em instalacoes distintas o 'iassign_ilmid' precisa ser buscado para encontrar o {iassign_ilm} adequado (se existir)!
    try {

      // Insert in table  {iassign_statement} and get its ID {iassign_statement}.id
      $newitemid = $DB->insert_record('iassign_statement', $new_iassign_statement);

      // /backup/moodle2/restore_subplugin.class.php!set_mapping($itemname, $oldid, $newid, $restorefiles = false, $filesctxid = null, $parentid = null)
      // * To send ids pairs to backup_ids_table and to store them into paths to table {files}
      //2025 $this->set_mapping('iassign_statement', $oldid, $newitemid, true); // Has related fileareas
      $this->set_mapping('iassign_statement', $oldid, $newitemid, false, null, $this->task->get_old_contextid());
      // Note - the old contextid is required in order to be able to restore files stored in sub plugin file areas attached to the iassign_statementid.

    } catch (Exception $e) {
      $error_msg  = "Error on restore_iassign_stepslib.php!process_iassign_statement(.): " . $e->getMessage() . "<br/>\n";	    
      // With any of the bellow warning, the restoring process is interrupted!
      // throw new restore_step_exception($error_msg);
      debugging('Error in restore_iassign_stepslib.php!process_iassign_statement(.): the iLM ' . $ilm_name . '!', DEBUG_DEVELOPER);
      }

    activity::add_calendar($newitemid);
    } // protected function process_iassign_statement($data)


  // Process a iassign_submission restore.
  // @param object $data The data in object form
  protected function process_iassign_submission ($data) {
    global $DB, $CFG;
    $data = (object) $data;
    $oldid = $data->id; // from source {course}.id: old {iassign_statement}.id

    if ($CFG->debugdisplay) { // debug messages is turned on
      if (isset($this->source_course_id)) //DEBUG
        print "restore_iassign_stepslib.php!process_iassign_submission(.): this->source_course_id=" . $this->source_course_id . "; old {issign}.id=" . $oldid . "<br/>"; //exit;
      }

    $data->iassign_statementid = $this->get_new_parentid('iassign_statement');
    $data->userid = $this->get_mappingid('user', $data->userid);
    $data->teacher = $this->get_mappingid('user', $data->teacher);
    $newitemid = $DB->insert_record('iassign_submission', $data); // insert in 'iassign_submission'
    $this->set_mapping('iassign_submission', $oldid, $newitemid, true); // Has related fileareas
    }

  // Process a iassign_submission_comment restore.
  // @param object $data The data in object form
  protected function process_iassign_submission_comment ($data) {
    global $DB;
    $data = (object) $data;
    $oldid = $data->id; // from source {course}.id: old {iassign_statement}.id

    if ($CFG->debugdisplay) { // debug messages is turned on
      if (isset($this->source_course_id)) //DEBUG
        print "restore_iassign_stepslib.php!process_iassign_submission_comment(.): this->source_course_id=" . $this->source_course_id . "; old {issign}.id=" . $oldid . "<br/>"; //exit;
      }

    $data->iassign_submissionid = $this->get_new_parentid('iassign_submission');
    $data->comment_authorid = $this->get_mappingid('user', $data->comment_authorid);
    $newitemid = $DB->insert_record('iassign_submission_comment', $data); // insert in 'iassign_submission_comment'
    $this->set_mapping('iassign_submission_comment', $oldid, $newitemid, true); // Has related fileareas
    }

  // Try current and old association {files} and {iassign_statemnt}
  // Initially try to recover with {files}.itemid = {iassign_statement}.id, after with {iassign_statement}.file and then with {iassign_statement}.filesid
  protected function get_all_files_ia ($DB, $ias_id, $ias_file, $ias_filesid, $ias_name) {
    // Try to get all associated {files} using: {files}.itemid = {iassign_statement}.id
    $files = $DB->get_records('files', array('component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $ias_id));
    if ($files) 
      return $files;
    // Now try with: {files}.itemid = {iassign_statement}.file
    $files = $DB->get_records('files', array('component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $ias_file));
    if ($files) 
      return $files;
    // Now try with: {files}.itemid = {iassign_statement}.file
    $files = $DB->get_records('files', array('component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $ias_filesid));
      return $files;
    }

  // An extension of Levenshtein to avoid wrong association, as $name="6.3 Laco simples: Dado N, determinar a soma dos N primeiros pares" 
  // with $filename1="aula5-circuncentro_EF_web.geo", instead of $filename2="ex6_3_n_prim_pares.ivph",
  // since Levenshtein($name,$filename1)=57 and Levenshtein($name,$filename2)=59
  static function extend_levenshtein_distance ($name, $filename) { // Levenshtein distance
    $itens = explode(" ", $name);
    $dist = 0;
    foreach ($itens as $item) // use all word in {iassign_statement}.name
      $dist += Levenshtein($item, $filename);
    return $dist;     
    }

  // Once the database tables have been fully restored, restore the files.
  // The current code is only necessary to eventually change {files}.itemid and {iassign_statement}.filesid to
  // the current standard: {files}.itemid = {iassign_statement}.id AND {iassign_statement}.filesid={files}.id
  // @calledby ./backup/util/plan/restore_structure_step.class.php: restore_iassign_activity_structure_step->after_execute()
  // @calls    ./backup/moodle2/restore_subplugin.class.php: protected function add_related_files($component, $filearea, $mappingitemname, $filesctxid = null, $olditemid = null)
  protected function after_execute () {
    global $CFG, $DB;


    //RR $oldcontextid = $this->task->get_old_contextid(); // $data->oldcontextid;  // The old context ID (from the backup)
    //xxxxxxxxxxxxxxx adicionei: , 'files', $this->old_id
    //RR $this->add_related_files('mod_iassign', 'exercise', 'files', $oldcontextid, $this->old_id); // $this->add_related_files('mod_iassign', 'exercise', null);
    //RR $this->add_related_files('mod_iassign', 'activity', 'files', $oldcontextid, $this->old_id); // $this->add_related_files('mod_iassign', 'activity', null); //2025
    $this->add_related_files('mod_iassign', 'exercise', null);
    $this->add_related_files('mod_iassign', 'activity', null); //2025/04
    $this->add_related_files('mod_iassign', 'intro', null);    //2025/04

    // $fs = get_file_storage();

    $msgD = "\n----------------------\nrestore_iassign_stepslib.php!after_execute():\n"; //Dxxx
    $countF = 0; // count the total of iAssign activities file  //Dxxx

    // Get this {course}.id, note that {course_modules}.course={course}.id AND {course_modules}.instance={iassign}.id AND {course_modules}.module={module}.id of iAssign
    // $course_id = $this->get_courseid(); // ERROR! This returns the {course}.id of the NEW {course}.id!!!
    // $course_id = $this->source_course_id; // get the source {iassign}.course = {course}.id
    $course_id = $this->get_courseid(); // get the destination {course}.id
    if ($CFG->debugdisplay) { // debug messages is turned on
      if (isset($this->source_course_id)) //DEBUG
        print "restore_iassign_stepslib.php!after_execute(): original {course}.id=" . $this->source_course_id . "; here courseid=" . $course_id . "<br/>"; //exit;
      }

    // @see /lib/accesslib.php : CONTEXT_SYSTEM=10; CONTEXT_USER=30; CONTEXT_COURSECAT=40; CONTEXT_COURSE=50; CONTEXT_MODULE=70
    $coursecontext = context_course::instance($course_id); //REMOVE: auxiliary to debug {context}.contextlevel = CONTEXT_MODULE = 70

    // Get all "iAssignment block" inside the source course ($this->source_course_id): $list_all_instances_of_iassign
    // ERROR: here $course_id is the {course}.id of the NEW course!!!
    $list_all_instances_of_iassign = $DB->get_records('iassign', array('course' => $course_id));
    $msgD .= "The target course.id=" . $course_id . "\n" . "And its coursecontext.id=" . $coursecontext->id . "\n" .
             "#list_all_instances_of_iassign=" . count($list_all_instances_of_iassign) . " : from course.id=" . $course_id . "\n"; //XXX
    foreach ($list_all_instances_of_iassign as $iassign) {
      // Get all activities (course.moudules) inside each "iAssignment block": $list_all_iassign_statement
      $list_all_iassign_statement = $DB->get_records('iassign_statement', array('iassignid' => $iassign->id)); // get all activities inside this block

      $msgD .= "#list_all_iassign_statement = " . count($list_all_iassign_statement) . " : from iassign.id=iassign_statement.iassignid=" . $iassign->id . "\n"; //Dxxx
      foreach ($list_all_iassign_statement as $iassign_statement) {
        // Get all associated {files} to this iAssign activity: $files
        // Initial iAssign/files association: {files}.itemid = {iassign_statement}.file
        // Current (after 2022): {files}.itemid = {iassign_statement}.id AND {iassign_statement}.filesid = {files}.id

        $msgD .= "+ {iassign_statement} : id=" . $iassign_statement->id . ", file=" . $iassign_statement->file . ", filesid=" . $iassign_statement->filesid . ", "  . $iassign_statement->name . "\n"; //Dxxx

        // Try recover {files} using : {files}.itemid == {iassign_statement}.id
        $files = $DB->get_records('files', array('component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $iassign_statement->id)); // new 2022 association
        if ($files) { // success
          $msgD .= " - (1) {files}.itemid=={iassign_statement}.id==" . $iassign_statement->id . "\n"; //Dxxx
          }
        else { // try old association : {files}.itemid == {iassign_statement}.file
          $msgD .= " - (1) {files}.itemid=={iassign_statement}.id==" . $iassign_statement->id . " falhou!\n"; //Dxxx
          $files = $DB->get_records('files', array('component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $iassign_statement->file)); //version until 2021:
          if ($files) { // success
            $msgD .= " - (2) {files}.itemid=={iassign_statement}.file==" . $iassign_statement->file . "\n"; //Dxxx
            }
          else { // try association : {files}.itemid == {iassign_statement}.filesid
            $msgD .= " - (2) {files}.itemid=={iassign_statement}.file==" . $iassign_statement->file . " falhou!\n"; //Dxxx
            $files = $DB->get_records('files', array('component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $iassign_statement->filesid)); //version until 2021:
            if ($files) {
              $msgD .= " - (3) {files}.itemid=={iassign_statement}.filesid==" . $iassign_statement->filesid . "\n"; //Dxxx
              }
//2025
            else { // try association : {files}.id == {iassign_statement}.filesid
              $msgD .= " - (3) {files}.itemid=={iassign_statement}.filesid==" . $iassign_statement->filesid . " falhou!\n"; //Dxxx
              $files = $DB->get_records('files', array('component' => 'mod_iassign', 'filearea' => 'exercise', 'id' => $iassign_statement->filesid)); //2025
              if ($files) {
                $msgD .= " - (4) {files}.id=={iassign_statement}.filesid==" . $iassign_statement->filesid . "\n"; //Dxxx
                }
              else $msgD .= " - (4) {files}.id=={iassign_statement}.filesid==" . $iassign_statement->filesid . " falhou!\n"; //Dxxx

              } // else {files}.itemid == {iassign_statement}.filesid
//2025	      

            } // else {files}.itemid == {iassign_statement}.filesid

          } // else {files}.itemid == {iassign_statement}.file

        $choosen_file = null;
        if ($files) { // got some {files}
          $msgD .= " - #files = " . count($files) . "\n"; //D
          $countF += count($files); //D

          // $distance = strlen($iassign_statement->name) + 1; // use Levenshtein distance to get the {files} with smallest distance from {iassign_statement}.name
          $distance = 100*strlen($iassign_statement->name) + 1; // use extended Levenshtein distance to get the {files} with smallest distance from {iassign_statement}.name
          foreach ($files as $element_file) { // analyse each '$element_file' of 'files'
            if ($element_file->filename != '.') { // get the '$element_file' that is not only "path"
              $dist = restore_iassign_activity_structure_step::extend_levenshtein_distance($iassign_statement->name, $element_file->filename); // Levenshtein distance
              $msgD .= " -- files: id=" . $element_file->id . ", itemid=" . $element_file->itemid . ", filename=" . $element_file->filename . " : dist=" . $dist . "\n"; //D
              if ($dist<$distance) { // new chosen file (name nearest from {iassign_statement}.name
                $choosen_file = $element_file;
                $distance = $dist;
                }
              } // if
            } // foreach ($files as $element_file)
          if ($choosen_file != null) { // there is {files} to associate
            if ($iassign_statement->filesid != $choosen_file->id) { // need to update 'iassign_statement.filesid'?
              // {iassign_statement}.filesid' <- 'files.id' (update bellow) //2022
              $newentry = new stdClass();
              $newentry->id = $iassign_statement->id;
              $newentry->filesid = $choosen_file->id;
//2023--- linha abaixo estava comentada, voltei ela!
              if (!$DB->update_record("iassign_statement", $newentry)) print_error('error_update', 'iassign'); // update 'iassign_statement' : filesid <- {files}.id'
              $msgD .= " " . " - Update {iassign_statement}.id=" . $newentry->id . ", filesid=" . $iassign_statement->filesid . " <- " . $newentry->filesid . "; \n"; //D
              }
            else $msgD .= " " . " - OK! To {iassign_statement}.id=" . $newentry->id . " : filesid=" . $iassign_statement->filesid . " = " . $choosen_file->id . "; \n"; //D

            //2023 Removido o bloco para redefinir {iassign_statement}.iassign_ilmid baseado na extensao do arquivo encontrado //XXX
            //2023 Vide: /home/leo/projetos/iMA/lms/itarefa/novo/alteracoes_2023/alteracoes_backup_moodle2.txt                 //XXX
            if ($choosen_file->itemid != $iassign_statement->id) {
              // Adjust to new 2022 {files}/{iassign_statement} relation: if {files}.itemid!=iassign_statement}.id then {files}.itemid <- {iassign_statement}.id
              $newentry = new stdClass();
              $newentry->id = $choosen_file->id;
              $newentry->itemid = $iassign_statement->id;

              $msgD .= " " . " - Update {files.itemid}: id=" . $newentry->id . ", itemid=" . $choosen_file->itemid . "<-" . $newentry->itemid . "\n"; //D

//2023--- linha abaixo estava comentada, voltei ela!
              if (!$DB->update_record('files', $newentry)) print_error('error_update', 'iassign'); // atualiza {files}.itemid := {iassign_statement}.id

              }
            else $msgD .= " " . " - OK! To {iassign_statement}.id=" . $newentry->id . " : itemid=" . $choosen_file->itemid . " = " . $iassign_statement->id . "; \n"; //D

            } // if ($choosen_file != null)
          } // if ($files)
        if ($choosen_file == null) $msgD .= " " . " - NAO encontrado {files} para associar!<br/>\n";
        else $msgD .= " " . " - Encontrado {files} : " . $choosen_file->id . ", " . $choosen_file->itemid . ", " . $choosen_file->filename . ", distance=" . $distance . "\n";

        } // foreach ($list_all_iassign_statement as $iassign_statement)

      } // foreach ($list_all_instances_of_iassign as $iassign)

//DEBUG
// /var/www/html/saw2021_1/mod/iassign/ilm_debug/escreva.php
    $timeWrite = date('Y_m_d_H_i_s'); // Year_Month_Day_Hour_Minutes_Seconds
    $filenamewrite = "output_restore_after_execute_" . $timeWrite . ".txt";

    // ./mod/iassign/ilm_debug/escreva.php: writeContent($filetype1, $pathbase, $outputFile, $msgToRegister)
    // ./mod/iassign/ilm_debug/saida_restore_after_execute.txt
    // require_once(__DIR__ . "/../../ilm_debug/escreva.php"); //leo REMOVER! xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
    require_once($CFG->dirroot . "/mod/iassign/ilm_debug/escreva.php"); //leo REMOVER! xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
    $resp = writeContent("", "", $filenamewrite, $msgD); // ($filetype1, $pathbase, $outputFile, $msgToRegister) //leo REMOVER!
    //D echo "restore_iassign_stepslib: resp=" . $resp . ", #files=" . $countF . "<br/>\n";
//DEBUG

    } // protected function after_execute()

  } // class restore_iassign_activity_structure_step extends restore_activity_structure_step
