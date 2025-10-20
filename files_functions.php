<?php

/*

 * Any Moodle activity is directly associated of a Moodle instance activity through {course_modules}.
 * 
 * iAssign is no directly associated of a Moodle instance activity, since it is a block of activities,
 * then there is association between {course_modules}.instance = {iassign}.id and {course_modules}.id = {context}.instanceid
 * but {files}.contextid = {iassign_statement}.id (not with {iassign}.id) and {iassign_statement}.iassignid = {iassign}.id
 * The same with {files}.itemid that usually is associated with {<module>}.id but in iAssing need associate with each block activity
 * then {files}.itemid = {iassign_statement}.id   (not with {iassign}.id)
 * 
 * Relations between Moodle tables to recover iAssign files:
 * {course_modules}.id = {context}.instanceid
 * {context} : id ; contextlevel ; instanceid ; path ; depth ; locked
 * {course_modules} : id ; course ; module ; instance ...
 * + {course_modules}.course   = {course}.id
 * + {course_modules}.module   = {module}.id    - is the module id (example, if iAssign has {module}.id=7 => {course_modules}.module = 7
 * + {course_modules}.instance = {iassign}.id   - associate with activities block
 * 
 */

// Moodle core defines constant MOODLE_INTERNAL which shall be used to make sure that the script is included and not called directly.
defined('MOODLE_INTERNAL') || die();

$SEP = ";"; // separador para impressao CSV ou tela

//TODO: locallib.php:
//TODO: Remover o codigo de leitura de arquivos do 'locallib.php ! view_iassign_current(): iassign_statement_activity_item = $this->activity->get_activity();' para este PHP
//TODO: function get_files_in_context (&$filesfrommine, &$filesfromothers, $contextid, $extension, $userid): quem pega conteudo para "view.php" - 8222/9167

//TODO: static function save_ilm_by_xml ($application_xml, $files_extract): rever, pois tem comando "'itemid' => rand(1, 999999999)" - 6791/9167
//TODO: function write_file_iassign ($stringArchiveContent, $filename): pois "itemid' => 0, // usually = ID of row in table" - 7533/9167
//TODO: function update_file_iassign ($stringArchiveContent, $filename, $fileid): pois "itemid' => 0, // usually = ID of row in table" - 7575/9167
//TODO: function recover_files_ilm (): 'itemid' => 0, - 8171/9167
//TODO: function get_files_in_context (&$filesfrommine, &$filesfromothers, $contextid, $extension, $userid): quem pega conteudo para "view.php" - 8222/9167
//TODO: function view_files_ilm ($iassign_ilm_class, $extension) - 8469/9167

//TODO: This code are designed to recover partially files lost in wrong updates:
//TODO: if ($oldversion < 2020120500): error considering {iassign_statement}.id != {iassign_statement}.file, {iassign_statement}.filesid:={files}.itemid and erasing correct {files}! //TODO: not good...
//TODO: 


// Get iAssign ID in {modules}
// @calledby settings_verify.php
function get_iassign_id ($DB) {
  // Get the list of all modules that are properly installed.
  // $iassign_module = $DB->get_records_menu('modules', array(''), '', 'name, id');
  $str_query = "SELECT id, name, visible FROM {modules} WHERE upper(name) = 'IASSIGN'";
  //D echo "query=" . $str_query . "<br/>";
  $iassign_module = $DB->get_records_sql($str_query); // get array of stdClass (must be only one element)
  // $iassign_module = $DB->execute($str_query); // get array of stdClass (must be only one element)
  if (!$iassign_module) return -1;
  foreach ($iassign_module as $ia) {
    //D echo "iassign_module->id = " . $ia->id . ", name=" . $ia->name . "<br/>iassign_module="; print_r($iassign_module); echo "<br/>";
    return $ia->id;
    }
  return -1; // error... empty array
  }


// Temporary: to create new {iassign_statement}.userid
function get_user ($DB, $name1, $name2) {
  $str_query = "SELECT id, firstname, lastname FROM {user} WHERE firstname='" . $name1 . "' AND lastname='" . $name2 . "'"; // "; // AND filearea='exercise' AND contextid=108
  $tmp_list_all_files = $DB->get_records_sql($str_query); // get array of stdClass (must be only one element) 
  $num = count($tmp_list_all_files);
  //D echo "get_user: " . $name1 . " " . $name2 . "; num=" . $num . "<br/>";
  if ($num==0) return -1;
  $ii = 0;
  foreach ($tmp_list_all_files as $elem) {
    echo " - id=" . $elem->id . "; firstname=" . $elem->firstname . "; lastname=" . $elem->lastname . "<br/>";
    $ii++;
    }
  echo "Total: " . $ii . "<br/>";
  }

// Temporary: to create new {iassign_statement}.userid
function print_vector ($vec) {
  $SEP = ";";
  if (!is_array($vec)) print $vec . "<br/>";
  $sizeOfVector = count($vec);
  if ($sizeOfVector==0) print "[]<br/>";
  else {
    for ($ii=0; $ii<$sizeOfVector; $ii++) print $vec[$ii] . $SEP . " ";
    //print "<br/>";
    }
  }

// Temporary: to create new {iassign_statement}.userid
function get_name ($name) {
  $items = explode("&nbsp;", $name);
  $name1 = $items[0].trim();
  $name2 = $items[1].trim(); //D echo $name . " :: " . $name1 . " - " . $name2 . "<br/>";
  return [$name1, $name2];
  }

// Temporary: to create new {iassign_statement}.userid
function binary_search ($vec_elements, $x_element) {
  $sizeOfVector = count($vec_elements);
  $leftI = 0; $rightI = $sizeOfVector-1;
  while ($leftI <= $rightI) {
    $middle = floor(($leftI + $rightI)/2);
    $elem_file = $vec_elements[$middle]; //D echo "leftI=" . $leftI . " - rightI=" . $rightI . " => med=" . $middle . "<br/>";
    // if ($leftI == $rightI && $vec_elements[$leftI]!=$x_element) { return [-1, $leftI ]; }
    if ($elem_file < $x_element) { $leftI = $middle + 1; }
    else
    if ($elem_file > $x_element) { $rightI = $middle - 1; }
    else { return $middle; } // found! return index position
    }
  return [-1, $leftI, $rightI];
  } // function binary_search($vec_elements, $x_element)

// Temporary: to create new {iassign_statement}.userid
function insert_ordered_name (&$vec_elements, $name, $i) {
  $sizeOfVector = count($vec_elements);
  //D echo "insert_ordered_name: " . $name . " em " . $i . " (sizeOfVector=" . $sizeOfVector . ")<br/>";
  $j = $sizeOfVector;
  while ($j > $i) { // move all after $i one position
    $vec_elements[$j] = $vec_elements[$j-1];
    $j--;
    }
  $vec_elements[$i] = $name; // insert new element on $i position
  }

// Temporary: to create new {iassign_statement}.userid
function get_all_iassign_statement ($DB) {
  $str_query = "SELECT * FROM {iassign_statement}"; //
  $tmp_list_all_iassign_statement = $DB->get_records_sql($str_query); // get array of stdClass (must be only one element)
  $num_is = count($tmp_list_all_iassign_statement);
  print "#tmp_list_all_iassign_statement = " . $num_is . "<br/>\n";
  // saw2021_1: #tmp_list_all_iassign_statement = 3519

  $vec_all_names_ord = array();
  $count1 = 0;
  foreach ($tmp_list_all_iassign_statement as $ias) {
    [$name1, $name2] = get_name($ias->author_name);
    //D echo $ias->author_name . " :: " . $name1 . " - " . $name2 . "<br/>";
    if ($count1>=5) break;
    $name = $name1 . " " . $name2;
    $answer = binary_search($vec_all_names_ord, $name);
    if (count($answer)>1) { // echo $x . " -> ["; print_vector($answer); echo "]<br/>\n";
      $ind = $answer[0];
      }
    else { // echo $x . " -> " . $answer . "<br/>\n";
      $ind = $answer;
      }
    if ($ind == -1) {
      $userid = get_user($DB, $name1, $name2); //echo "Total: " . $ii . "<br/>";
      insert_ordered_name($vec_all_names_ord, $name, $answer[1]);
      $count1++;
      }
    //D else echo " - " . $name . " ja existe em " . $ind . "<br/>";
    }
  $total = count($vec_all_names_ord);
  print "Total: " . $total . "<br/> Vector: "; print_vector($vec_all_names_ord); print "<br/>";
  } // function get_all_iassign_statement($DB)


// Get all iAssign {files}
// Build "$list_all_files" with fields:  id contenthash contextid component filearea itemid filepath filename userid author timecreated ias_id
// Initially "$list_iassign[$ii]".ias_id = -1 (in "search_files(.)" we hope "ias_id" := {iassign_statement}.id)
// @calledby get_associated_ias_file
function get_all_files ($DB, $list_iassign) {
  global $USER, $SEP; //DEBUG: apenas durante depuracao!

  $sizeAC = count($list_iassign);
  //L echo "----<br/>get_all_files: #list_iassign=" . $sizeAC . "<br/>\n";

  // Usual access to {files} is through:
  //  $fs = get_file_storage();
  //  $files = $fs->get_area_files($context_id, 'mod_iassign', 'exercise', $iassign_statement_id); 
  // using: ./lib/filestorage/file_storage.php: public function get_area_files($contextid, $component, $filearea, $itemid = false,...)

  $str_query = "SELECT * FROM {files} WHERE component='mod_iassign'"; // "; // AND filearea='exercise' AND contextid=108
  $tmp_list_all_files = $DB->get_records_sql($str_query); // get array of stdClass (must be only one element)
  //D print_r($tmp_list_all_files); echo "<br/>"; // Array ( [37] => stdClass Object ( [id] => 3...

  $numf = count($tmp_list_all_files);
  //L echo "#tmp_list_all_files=" . $numf . "<br/>"; // #tmp_list_all_files=13383
  $list_all_files = array(); // use continous array ($tmp_list_all_files is indexed by 'id')
  $ii = 0;
  foreach ($tmp_list_all_files as $elem) {
    //D echo $ii . $SEP . $elem->id . $SEP . $elem->contextid . $SEP . $elem->timecreated . $SEP . $elem->filearea . $SEP . $elem->itemid . $SEP .
    //D   "'" . $elem->author . "'" . $SEP . "'" . $elem->filename . "'" . $SEP . "<br/>";
    $elem->ias_id = -1;   // to receive the associated {iassign_statement}.id (defined in "search_lost_files_in_context(.)")
    $elem->courseid = -1; // to receive the associated {iassign_statement}.id (defined in "search_lost_files_in_context(.)")
    $elem->missing_ias = TRUE; // by default this {files} is associated to one {iassign_statement} - function "search_files(.)" can change it to FALSE
    $list_all_files[$ii] = $elem; // transcript array indexed by 'id' to "continous array"
    $ii++;
    }
  //D for ($ii=0; $ii<$numf; $ii++) {
  //D   $elem = $list_all_files[$ii];
  //D   echo $ii . $SEP . $elem->id . $SEP . $elem->contextid . $SEP . $elem->timecreated . $SEP . $elem->filearea . $SEP . $elem->itemid . $SEP .
  //D     "'" . $elem->author . "'" . $SEP . "'" . $elem->filename . "'" . $SEP . "<br/>";
  //D   }
  $sizeof_list_all_files = $ii;
  return $list_all_files;
  } // function get_associated_ias_file($DB, $iassign_statement, $all_context, &$all_context_final, $ii)



// Search $element_ias.contextid == $list_all_files[.].contextid and ($element_ias.file == $list_all_files[.].itemid or $element_ias.filesid == $list_all_files[.].id)
// Build "$list_all_files" with fields:  id contenthash contextid component filearea itemid filepath filename userid author timecreated ias_id
// Initially "$list_iassign[$ii]".ias_id = -1 (in "search_files(.)" we hope "ias_id" := {iassign_statement}.id)
// @param &$list_all_files: passed by reference, "$list_all_files[$jj].ias_id" could receive {iassign_statement}.id
function search_files (&$list_all_files, $element_ias) {
  $sizeOfFiles = count($list_all_files);
  $sizeC = count($element_ias->contextid);
  //D if ($element_ias->id==5793 || $element_ias->id==5794) { echo "search_files: " . $element_ias->courseid . ", " . $element_ias->id . ", " . $element_ias->filesid . " with context: ["; for ($jj=0; $jj<$sizeC; $jj++) echo $element_ias->contextid[$jj] . ","; echo "]<br/>"; }
  for ($ii=0; $ii<$sizeOfFiles; $ii++) {
    //D Decho $ii . " ";
    $element_file = $list_all_files[$ii];
    for ($jj=0; $jj<$sizeC; $jj++) {
      $contextid = $element_ias->contextid[$jj]; // try with $jj-th {context}.id
      if ($contextid == $element_file->contextid) {
        if ($element_ias->file == $element_file->itemid) {
          //echo "<br/>";
          //D echo " - Encontrou " . $element_ias->id . " via 'file' (file==itemid): contextid[" . $jj . "]=" . $contextid . ": " . $element_ias->file . "==" . $element_file->itemid . " no files[" . $ii . "]<br/>";
          $element_file->ias_id = $element_ias->id;
          $element_file->missing_ias = FALSE; // by default this {files} is NOT associated to one {iassign_statement} - here was detected that this {files} is not associated to any {iassign_statement}
          return  [$ii, $jj]; // found the file of {iassign_statement} at {files} index $ii with the context {iassign_statement}.contextid[$jj]
          }
        else
        if ($element_ias->filesid == $element_file->itemid) {
          //echo "<br/>";
          //D echo " - Encontrou " . $element_ias->id . " via 'file' (filesid==itemid): contextid[" . $jj . "]=" . $contextid . ": " . $element_ias->filesid . "==" . $element_file->itemid . " no files[" . $ii . "]<br/>";
          $element_file->ias_id = $element_ias->id;
          $element_file->missing_ias = FALSE; // by default this {files} is NOT associated to one {iassign_statement} - here was detected that this {files} is not associated to any {iassign_statement}
          return  [$ii, $jj]; // found the file of {iassign_statement} at {files} index $ii with the context {iassign_statement}.contextid[$jj]
          }
        else
        if ($element_ias->id == $element_file->itemid) { // Com esse
 // Total of {iassign_statement} without any {files}:   771 ->   739 = 32
 // Total of {files} without any {iassign_statement}: 10638 -> 10606 = 32
 //  Total of {files} associated with iAssign:          880 ->   880
          //D if ($element_ias->id==5658) echo "<br/> - Encontrou " . $element_ias->id . " via 'id' (id==itemid): contextid[" . $jj . "]=" . $contextid . ": " . $element_ias->id . "==" . $element_file->itemid . " no files[" . $ii . "] - {files}.id=" . $element_file->id . "<br/>\n";
// - Encontrou 5658 via 'id' (id==itemid): contextid[1]=52001: 5658==5658 no files[12521] - {files}.id=301156
// course.id = 472 : "EF-8.1" aqui funciona! http://localhost/saw2021_2/mod/iassign/view.php?id=20309&userid_iassign=3&action=view&iassign_current=5658
// ilm_handlers/html5.php: {course_module}.id=20309, {iassign_statement}.id=5658, {iassign_statement}.file=, {iassign_statement}.filesid=, {context}.id=52001, {files}.id=301156, {files}.filename=
          $element_file->ias_id = $element_ias->id;
          $element_file->missing_ias = FALSE; // by default this {files} is NOT associated to one {iassign_statement} - here was detected that this {files} is not associated to any {iassign_statement}
          return [$ii, $jj]; // found the file of {iassign_statement} at {files} index $ii with the context {iassign_statement}.contextid[$jj]
          }
        }
      } // for ($jj=0; $jj<$sizeC; $jj++)
    }
  //D if ($element_ias->id==5793 || $element_ias->id==5794) echo " - " . $element_ias->id . ", " . $element_ias->name . ": jj=" . $jj . ", ERRO!<br/>";
  return -1;
  } // function search_files($list_all_files, $element_ias)


// Get all {files} in one specific {course}.id
// Get all of its context, then all of their {files}
// @calledby locallib.php ! view_files_ilm($iassign_ilm_class, $extension)
function get_all_files_course ($DB, $courseid, $fs) {
  $modules_iassing_id = get_iassign_id($DB); // get 'id, name, visible' from 'modules' with upper(name)='IASSIGN'

  //ATTENTION: since 'get_records_sql(.)' will return only the first element (first field), it is necessary to use as first column {context}.id
  // {course_modules}.instance = {context}.instanceid = {iassign}.id
  $str_query = "SELECT {context}.id AS contextid, {course_modules}.id, {course_modules}.instance, {context}.instanceid FROM {course_modules},{context} ".
    " WHERE {course_modules}.id = {context}.instanceid AND {course_modules}.course = " . $courseid . " AND {course_modules}.module = " . $modules_iassing_id;
  //TODO? $array_timecreated = array(); //TODO order by {files}.timecreated?
  $array_all_files = array();
  //D echo "files_functions.php ! get_all_files_course(.)<br/>\n";
  //ATTENTION: 'get_records_sql(.)' return only the first element (first field)
  $array_course_modules_context = $DB->get_records_sql($str_query);
  foreach ($array_course_modules_context as $item_cm) {
    $files_tree = $fs->get_area_files($item_cm->contextid, 'mod_iassign', 'exercise');
    foreach ($files_tree as $onefile)
      if ($onefile->get_filename()!='.') {
        $array_all_files[] = $onefile;
        //TODO? $array_timecreated[] = $item; //TODO Use "asort($array_timecreated);" to order?
        //D echo " - id=" . $onefile->get_id() . ", contextid=" . $onefile->get_contextid() . ", filename=" . $onefile->get_filename() . "<br/>\n";
        }
    }
  //D echo "files_functions.php ! get_all_files_course(.): #array_all_files=" . count($array_all_files) . "<br/>\n";
  return $array_all_files;
  }


// Get {iassign_statement} or {files}
// @calledby settings_process_files_form.php
function get_iassignstatement_or_files ($DB, $table, $element_id) {
  //D echo "files_functions.php|get_iassignstatement_or_files: " . $element_id . "<br/>\n";
  if ($table != 'iassign_statement' && $table != 'files') return NULL; // security
  $ia_obj = $DB->get_record($table, array('id' => $element_id));
  //D if ($table=='iassign_statement) echo  $ia_obj->iassignid . ", " . $ia_obj->id . ", " . $ia_obj->file . ", " . $ia_obj->filesid . ", " . $ia_obj->name . "<br/>\n";
  return $ia_obj;
  //$files = $DB->get_records('files', array('contextid' => $contextid, 'component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $iassign_statement->id)); //
  }


// To be used in restoring backup
// @calledby ./mod/iassign/backup/moodle2/restore_iassign_stepslib.php
function get_all_files_ia ($DB, $ias_id, $ias_file, $ias_filesid, $ias_name) {
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


//TODO: remover funcao 'get_from_files(.)' do locallib.php (linha: 358)
// Get the element associated to {iassign_statement} in {files}
// Since the association was changed in 2020/12/05, but was upgrade non consistent,
// this try to recover with: {files}.itemid = {iassign_statement}.id, after with {iassign_statement}.file and then with {iassign_statement}.filesid
// @return array({files}, {files}) or NULL
// @calledby locallib.php ! add_edit_iassign() ; duplicate_activity()
// @calledby ilm_handlers/html5.php !show_activity_in_ilm(.)
function get_from_files ($ia_id, $ia_file, $ia_filesid, $fs, $contextid, $component, $filearea) {
  global $USER;
  //D if ($USER->id==3) echo " USER->id==3: files_function.php: get_from_files(.) - {user}.id=3: component=" .$component . "<br/>\n"; //leo
  $files_list = false;
  if ($ia_id) // try association: {files}.itemid = {iassign_statement}.id
    $files_list = $fs->get_area_files($contextid, $component, $filearea, $ia_id);
  //D if ($USER->id==3) echo " USER->id==3: + get_area_files com ia_id=" . $ia_id . " => #files_list=" . count($files_list). "<br/>\n"; //leo remover!
  if (!$files_list) { // not found with {files}.itemid = {iassign_statement}.id
    if ($ia_file) // try association: {files}.itemid = {iassign_statement}.file
      $files_list = $fs->get_area_files($contextid, $component, $filearea, $ia_file); // try with {iassign_statement}.file
    //D if ($USER->id==3) echo " USER->id==3: + get_area_files com ia_file=" . $ia_file . "<br/>\n"; //leo remover!
    if (!$files_list) { // not found with {files}.itemid = {iassign_statement}.file
      if ($ia_filesid) // try association: {files}.itemid = {iassign_statement}.filesid
        $files_list = $fs->get_area_files($contextid, $component, $filearea, $ia_filesid); // try with {iassign_statement}.filesid
      //D if ($USER->id==3) echo " USER->id==3: + get_area_files com ia_filesid=" . $ia_filesid . "<br/>\n"; //leo remover!
      if (!$files_list) return NULL; // error!
      }
    }
  $f1_obj = NULL; // will receive the element of {files} with regular 'filename'
  $f2_obj = NULL; // will receive the correspondent element of $f1_obj with directory, i.e, filename=='.'
  foreach ($files_list as $one_files) { // when activity is duplicated => it has only the regular 'filename'
    if ($one_files->get_filename()!=".") $f1_obj = $one_files; // $files_list[0]; // from {files}
    else $f2_obj = $one_files; // $files_list[1]; // from {files}
    } 
  // if $f2_obj!=NULL but $f2_obj==NULL, then probably $f1_obj was recovered by 'files_functions.php!get_from_filesAll(.)' in the previous edition
  //D if ($USER->id==3) echo " USER->id==3: * &nbsp; id=" . $f1_obj->get_id() . ", filename=" . $f1_obj->get_filename() . "<br/>\n"; //REMOVER
  return array($f1_obj, $f2_obj);
  } // function get_from_files($ia_id, $ia_file, $ia_filesid, $fs, $contextid, $component, $filearea)


// Debug function: list all {files} considering only {context}.id and {files}.component='mod_iassign' and ({files}.filearea='exercise' or {files}.filearea='activity')
// Using: ./lib/filestorage/file_storage.php: public function get_area_files($contextid, $component, $filearea, $itemid = false,...)
 // @return array({files}, {files}) or NULL
// @calledby locallib.php ! add_edit_iassign()
function get_from_filesAll ($fs, $contextid, $component, $filearea) {
  global $USER, $DB;
  if ($USER->id==3) echo "-------<br/>USER->id==3: files_function.php: get_from_filesAll(.) - {user}.id=3 : contextid=" . $contextid . ", component=" . $component . ", filearea=" . $filearea . "<br/>\n"; //leo
  // SELECT id,contextid,component,filearea,itemid,filename,author FROM s_files WHERE contextid=51250 AND (filearea='exercise' OR filearea='activity')
  $files_list = false;
  //x $files_list = $fs->get_area_files($contextid, $component, $filearea, false, $sort = "itemid, filepath, filename"); // volta vazio!
  $files_list = $DB->get_records('files', array('contextid' => $contextid, 'component' => 'mod_iassign', 'filearea' => $filearea)); //
  if ($USER->id==3) {
    echo " USER->id==3: + #files_list=" . count($files_list) . "<br/>\n"; //leo remover!
    }
  if (!$files_list) return NULL; // error!
  return $files_list;
  } // function get_from_filesAll($fs, $contextid, $component, $filearea)



// Update {files}.itemid to {iassign_statement}.id
// Came here when teacher send the form to update one activity and {files} already exists!
// @calledby locallib.php: update_iassign($param): 
function update_files ($DB, $one_file_id, $ia_id) {
  $newf = new stdClass();
  $newf->id = $one_file_id;
  $newf->itemid = $ia_id;
//D echo "files_functions.php: update_files(.): {files}.id=" . $one_file_id . ", {iassign_statement}.id=" . $ia_id . ": {files}.itemid <- " . $ia_id . "<br/>";
//D exit; //REMOVER exit
  if (!$DB->update_record('files', $newf)) return FALSE; // not error    
  return TRUE;
  }


// Update {iassign_statement}.filesid and {files}.itemid
// Temporary function to restore relation {iassign_statement}/{files}
// First version of iAssign used a random value to associate 'files.itemid' with 'iassign_statement.file'
// Came here when teacher try to edit any activity, during the form preparation
// @param $DB = database
// @param $ia_id = {iassign_statement}->id
// @param $filesid = {iassign_statement} object
// @param $is_obj = {iassign_statement} object
// @param $f1_obj = {files} obj with id=$filesid ($filesid an specific {files}->id)
// @param $f2_obj = {files} obj with id=$filesid+1
// @calledby settings_process_files_form.php: $answer = update_iassignstatement_files($DB, $iassign_statement_id, $filesid, $is_obj, $f1_obj, $f2_obj);
// @calledby locallib.php:add_edit_iassign(): $result = update_iassignstatement_files($DB, $is_obj->id, $f1_obj->get_id(), $is_obj, $f1_obj, $f2_obj);
function update_iassignstatement_files ($DB, $ia_id, $filesid, $is_obj, $f1_obj, $f2_obj = NULL, $context = NULL) {
  global $USER;
  // Came here with something like: $ia_obj = $DB->get_record($table, array('id' => $element_id));
  $ma  = "{iassign_statement}: id=" . $ia_id . " : filesid=" . $is_obj->filesid; //D
  $mf1 = "{files}: id=" . $f1_obj->get_id() . ": itemid=" . $f1_obj->get_itemid(); //D
  $mf2 = "{files}: [empty]"; //D

  $is_obj_filesid = $is_obj->filesid; // {iassign_statement}.filesid initial
  $is_obj->filesid = $filesid; // {iassign_statement}.filesid := $f1_obj->id = {files}.id
  $is_obj->file = $filesid;    // {iassign_statement}.file    := $f1_obj->id = {files}.id (update also the old field 'file')

  $f1_obj->id = $f1_obj->get_id();
  $f1_obj_itemid = $f1_obj->get_itemid(); // first {files}.itemid initial
  $f1_obj->itemid = $ia_id; // Necessary to force {files}.id, otherwise will occurs the error:

  $ma  .= " -> " . $is_obj->filesid; //D
  $mf1 .= " -> " . $f1_obj->itemid . " (id=" . $f1_obj->get_id() . ")"; //D

  $error = FALSE;
  // If {iassign_statement}.filesid != {files}.id update {iassign_statement}
  if ($is_obj_filesid != $filesid) // update {iassign_statement} defining: {iassign_statement}.filesid = {files}.id
    if (!$DB->update_record('iassign_statement', $is_obj)) $error = TRUE; // print_error('error_update', 'iassign');
  // If {files}.itemid != {iassign_statement}.id != update {filesid} (for file with 'filename')
  if ($f1_obj_itemid != $ia_id) // update first {files}.item with {iassign_statement}.id
    if (!$DB->update_record('files', $f1_obj)) $error = TRUE;

  if (is_null($f1_obj_itemid)) $f1_obj_itemid = -1; // to avoid error when {files} is not found
  $f2_obj_itemid = -1; // to avoid error when {files} is not found
  if ($f2_obj!=NULL) {
    $mf2 = "{files}: id=" . $f2_obj->get_id() . ": itemid=" . $f2_obj->get_itemid(); //D
    $mf2 .= " -> " . $f2_obj->get_itemid() . " (id=" . $f2_obj->get_id() . ")"; //D
    $f2_obj->id = $f2_obj->get_id();
    $f2_obj_itemid = $f2_obj->get_itemid(); // second {files}.itemid initial
    $f2_obj->itemid = $ia_id; // moodle_database::update_record_raw() id field must be specified.
    // If {files}.itemid != {iassign_statement}.id != update {filesid} (for file with directory, filename='.')
    if ($f2_obj_itemid != $ia_id) // update second {files}.item with {iassign_statement}.id
      if (!$DB->update_record('files', $f2_obj)) $error = TRUE;
    }
  //D echo "files_functions.php!update_iassignstatement_files(.): 4<br/>"; //exit;//leo

  //DEBUG
  if ($USER->id==3) { //D REMOVER
    $troca = 0; // locallib.php!add_edit_iassign(): $context = context_module::instance($this->cm->id); 
    echo "<br/><br/><br/><table><tr><td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>";
    echo "<td>USER->id==3: files_functions.php: update_iassignstatement_files(): USER==3</td></tr>\n";     
    echo "<tr><td></td><td>\$COURSE context: id=" .$context->id . "</td></tr>\n";
    echo "<tr><td></td><td>{iassign_statement}: id=" .$ia_id . ", file=" . $is_obj->file . ", filesid=" . $is_obj->filesid . "</td></tr>\n";
    echo "<tr><td></td><td>{files} 1: id=" . $f1_obj->get_id() . ", contextid=" . $f1_obj->get_contextid() . ", itemid=" . $f1_obj->itemid . ", filearea=" . $f1_obj->get_filearea() .
	 ", filepath=" . $f1_obj->get_filepath() . ", filename=" . $f1_obj->get_filename() . ", userid=" . $f1_obj->get_userid() . "</td></tr>\n";
    echo "<tr><td></td><td>{files} 1 : " .$mf1 . "</td></tr>\n";
    echo "<tr><td></td><td>{files} 2 : " .$mf2 . "</td></tr>\n";
    if ($is_obj_filesid != $filesid) { echo "<tr><td></td><td>&nbsp; - tenta: update {iassign_statement}.filesid with {files}.id ******* deveria TROCAR!</td></tr>\n"; $troca = 1; }
    else echo "<tr><td></td><td>&nbsp; - NAO troca iassign_statement</td></tr>\n";
    if ($f1_obj_itemid != $ia_id) { echo "<tr><td></td><td>&nbsp; - tenta: f1: update first {files}.item with {iassign_statement}.id f1.itemid=" . $f1_obj_itemid . "<-" . $ia_id . " ******* deveria TROCAR!</td></tr>\n"; $troca = 1; }
    else echo "<tr><td></td><td>&nbsp; - NAO troca files f1</td></tr>\n";
    if ($f2_obj_itemid != $ia_id) { echo "<tr><td></td><td>&nbsp; - tenta: f2: update second {files}.item with {iassign_statement}.id: f2.itemid=" . $f2_obj_itemid . "<-" . $ia_id . " ******* deveria TROCAR!</td></tr>\n"; $troca = 1; }
    else echo "<tr><td></td><td>&nbsp; - NAO troca files f1</td></tr>\n";
    echo "<tr><td></td><td>&nbsp; * " . $ma . " / " . $mf1 . " / " . $mf2 . "</td></tr>\n\n";
    if ($troca == 1) {
      if (!$error) echo "<tr><td></td><td>&nbsp; - atualizou {iassign_statement} e {files} com sucesso!</td></tr>\n<tr><td></td><td>Depuracao: USER->id==3</td></tr>\n";
      else echo "<tr><td></td><td>&nbsp; - ERRO! NAO atualizou {iassign_statement} e {files}!</td></tr>\nDepuracao: USER->id==3</td></tr>\n";
      // exit; //REMOVER exit
      }
    }
  if (!$error) {
    if ($USER->id==3) echo "<tr><td></td><td>USER->id==3: &nbsp; * " . $ma . " / " . $mf1 . " / " . $mf2 . "</td></tr></table>\n\n"; //D
    return TRUE;
    }
  if ($USER->id==3) echo "<tr><td></td><td>USER->id==3: &nbsp;  x " . $ma . " / " . $mf1 . " / " . $mf2 . " --- ERROR!</td></tr></table>\n\n"; //D
    return FALSE;
  } // function update_iassignstatement_files($DB, $ia_id, $filesid, $is_obj, $f1_obj, $f2_obj)


// List all "$list_all_files[$jj]" with field "ias_id" == -1 (candidate to be the missing file)
// Prepare HTML to "settings_process_files_form.php" then send to this->update_iassign_statement_files(.) function to update {iassign_statement} and {files}
// The "settings_verify.php" calls "see_all_iass_files(.)" and "list_all_iass_files(.)"
// @param $type = 1 => do not edit "$list_all_files[$jj].ias_id" (leave with -1); 2 => edit "$list_all_files[$jj].ias_id" with its first fit {iassign_statement}.ias_id
// @param &$list_all_files: passed by reference, "$list_all_files[$jj].ias_id" could receive {iassign_statement}.id if {files}.contextid = some {context}.id of {iassign_statement}; files[$jj].courseid" := $courseid,
// @param $contextid : is array of context in this {iassign_statement}; $ias_id = {iassign_statement}.id
// @calledby this->list_all_iass_files($DB, $modules_iassignid): [$msgCandidates, $msgHidden, $files_index_candidates, $contextid_candidates, $list_candidate_filesbyID] = search_lost_files_in_context(2,...)
// @calledby this->see_all_iass_files($DB, $modules_iassignid): [$msgCandidates, $msgHidden, $files_index_candidates, $contextid_candidates, $list_candidate_filesbyID] = search_lost_files_in_context(1, ...)
function search_lost_files_in_context ($type, $ii, $courseid, $ias_id, &$list_all_files, $array_contextid, $countErr) {   
  $sizeOfFiles = count($list_all_files);
  $sizeC = count($array_contextid);
  $files_index_candidates = array();
  $files_filename_candidates = array();
  $contextid_candidates = array(); // {context}.id associated to the {files}
  $list_candidate_files = array(); // array with {files} indexed by its ID ({files}.id): $list_candidate_files[ID]==$jj <=> $list_all_files[$jj] is the {files}
  $msg = "ERRO: <select name='selected_filesid[" . $countErr . "]'>\n <option value=''>select one file</option>\n";
  $msgHidden = "";
  for ($jj=0; $jj<$sizeOfFiles; $jj++) { // list all lost {files}
    $sizeC = count($array_contextid); // all {context}.id of this {iassign_statement}
    $hascandidate = 0; // has {files} candidate with one of {iassign_statement}.contextid[]?
    for ($kk=0; $kk<$sizeC; $kk++) { // list all {context}.id associated to this {iassign_statement}
      $contextid = $array_contextid[$kk];
      if ($list_all_files[$jj]->contextid==$contextid && $list_all_files[$jj]->ias_id==-1) { // found one {files} candidate (its {files}.contextid = {iassign_statement}.contextid[$kk])
        $hascandidate = 1; // has {files} candidate with one of {iassign_statement}.contextid[$kk]
        if ($type == 2) // can change field "ias_id" ($type==1 nedd it continous as -1 ao list {files} with all possible {iassign_statement}
          $list_all_files[$jj]->ias_id = $ias_id;   // this {files} could be of {iassign_statement}.id = $ias_id
        $list_all_files[$jj]->courseid = $courseid; // this {files} could be on the same course as {iassign_statement}.id = $ias_id
        break; // stop search to the other {iassign_statement}.contextid[]
        }
      }
    if ($hascandidate == 1) { // there is one {files}.contextid = {iassign_statement}.contextid[$kk]
      $files_index_candidates[] = $jj;
      $files_filename_candidates[] = $list_all_files[$jj]->filename;
      $contextid_candidates[] = $contextid;
      $fileid = $list_all_files[$jj]->id;
      $list_candidate_files[$fileid] = $jj;
      if ($list_all_files[$jj]->filename != ".") { // consider only file (not path)
        $msg .= " <option value='" . $fileid . "'>" . $fileid . " - " . $list_all_files[$jj]->filename . " ii=" . $list_candidate_files[$fileid] . "</option>\n";
        $msgHidden .= " <input  value='" . $list_all_files[$jj]->filename . "' name='list_filesname[" . $ii . "][]' type='hidden' />\n";
        $msgHidden .= " <input  value='" . $list_all_files[$jj]->id . "' name='list_filesid[" . $ii . "][]' type='hidden' />\n";
        $msgHidden .= " <input  value='" . $contextid . "' name='list_contextid[" . $ii . "][]' type='hidden' title='#array_contextid=" . count($array_contextid) . "'/>\n";
        $msgHidden .= " <input  value='" . $ias_id . "' name='list_iassign_statement_id[" . $ii . "][]' type='hidden' />\n"; // {iassign_statement}.id
        }
      }
    } // for ($jj=0; $jj<$sizeOfFiles; $jj++)
  $msg .= "</select><table>\n";
  $sizeCandidates = sizeof($files_index_candidates);
  for ($jj=0; $jj<$sizeCandidates; $jj++) {
    if ($files_filename_candidates[$jj] == ".") continue; // ignore filename==".", next $jj
    $kk = $files_index_candidates[$jj];
    $contextid = $contextid_candidates[$jj]; // $array_contextid[$kk];
    $fileid = $list_all_files[$kk]->id;
    $msg .= " <tr><td title='coourse.id'>" . $list_all_files[$kk]->courseid . "</td>"; // defined in "search_lost_files_in_context(.)"
    $msg .= " <td title='context.id'>" . $contextid . "</td>";
    $msg .= " <td title='files.id'>" . $list_all_files[$kk]->id . "</td>";
    $msg .= " <td title='files.name'>" .
      $list_all_files[$kk]->filename . "</td><td title='files.timecreated=" . date("d/m/Y H:i:s", $list_all_files[$kk]->timecreated) . "'>" .
      $list_all_files[$kk]->timecreated . "</td><td title='files.author'>" .  $list_all_files[$kk]->author . "</td></tr>\n";
    } // for ($jj=0; $jj<$sizeOfFiles; $jj++)
  $msg .= "</table>\n";
  return [$msg, $msgHidden, $files_index_candidates, $contextid_candidates, $list_candidate_files];
  } // function search_lost_files_in_context($ii, $courseid, $ias_id, &$list_all_files, $array_contextid, $countErr)



// Find in array $list_iassign[] $list_iassign[$ii]->ia_id = $iassignid
// Return [ {countext}.id array , {course}.id ]
// @calledby see_all_iass_files($DB, $course_modules_iassign)
function find_course_context ($list_iassign, $iassignid) {
  $sizeI = count($list_iassign);
  for ($ii=0; $ii<$sizeI; $ii++)
    if ($list_iassign[$ii]->ia_id == $iassignid)
      return [$list_iassign[$ii]->id, $list_iassign[$ii]->course]; // array of contextid
  print "*** ERRROR: coudn't find {iassign}.id = " . $iassignid . "<br/>\n";
  return [-1, -1]; // error!
  }


/// Search for {files} associated to each "iassign_statement" ("settings_verify.php?action=list")
/// Must be used to produce a continous list of all {iassign_statement} with no {files} and then a list with all {files} with no {iassign_statement}
// @calledby settings_verify.php?action=list : $list_iassign = see_all_iass_files($DB, 9);
// @param    $modules_iassignid is iAssign ID in {modules}
function list_all_iass_files ($DB, $modules_iassignid) {
  global $SEP;
  // Problema: como encontrar {files} com itemid errado?
  //  1. Vetor com todos os {iassign} com todos seus {context}.id : $list_iassign : id ; cmid ({course_modules}.id) ; course ({course}.id); name ; ia_id ({iassign}.id)
  //  2. Vetor com todos os {iassign_statement}                   : $list_ias     : id ; name ; iassignid ; author_name ; iassign_ilmid ; file ; fileid ; timecreated
  //  3. Vetor com todos os {files} com {iassign}.contextid       : $list_files   : id ; contenthash ; contextid component filearea itemid filepath filename userid author timecreated ias.id
  //  4. Vetor com todos os {iassign_statement} sem {files} correspondentes:
  
  /// Get {iassign} :: $list_iassign[] = { id (context.id) ; cmid ; ia_id (iassign.id) ; course (course.id) ; name ({iassign}.name) }
  ///
  //D print "---<br/>Get {iassign} :: list_iassign[] = { id (context.id) ; cmid ; ia_id (iassign.id) ; course (course.id) ; name ({iassign}.name) }
  //ATTENTION: 'get_records_sql(.)' return only the first element (first field)
  //$str_query = "SELECT {context}.id AS contextid, {course_modules}.id AS cmid, {iassign}.id, {iassign}.course, {iassign}.name FROM {iassign}, {context}, {course_modules} WHERE " .
  $str_query = "SELECT {context}.id, {course_modules}.id AS cmid, {iassign}.id AS ia_id, {iassign}.course, {iassign}.name FROM {iassign}, {context}, {course_modules} WHERE " .
    "{course_modules}.module=" . $modules_iassignid . " AND {iassign}.course={course_modules}.course AND {course_modules}.id={context}.instanceid AND {course_modules}.instance={iassign}.id";

  $tmp_list_iassign = $DB->get_records_sql($str_query); // get array of stdClass (must be only one element)
  //D print " - #tmp_list_iassign=" . count($tmp_list_iassign). "<br/>";

  // Process $tmp_list_iassign to produce the array $list_iassign continous (starting from $list_iassign[0]),
  // with only one entry to each {iassign}.id and keeping all of its associated {context}.id under the array $list_iassign[$ii]->id
  // The {iassign}.id is $list_iassign[$ii]->ia_id
  $list_iassign = array(); // use continous array ($tmp_list_ias is indexed by 'id')
  $ii = 0;
  foreach ($tmp_list_iassign as $element) { // one {iassign} could have several contextid
    if ($ii==0 || $list_iassign[$ii-1]->ia_id != $element->ia_id) { // new element
      $item = array(); // change to array
      $item[] = $element->id; // contextid
      $element->id = $item; // change to array - contextid = $item
      $list_iassign[$ii] = $element; // transcript array indexed by 'id' to "continous array"
      $ii++;
      }
    else { // just add new contextid (extends array $list_iassign[$ii-1]->id[])
      $list_iassign[$ii-1]->id[] = $element->id; // new entry to contextid array -- context
      }
    }
  $sizeOf_list_iassign = $ii;
  //D print " - #list_iassign=" . $sizeOf_list_iassign . "<br/>";

  /// Get {iassign_statement} :: $list_ias[] = { courseid (iassign.course) ; id (iassign_statement.id) ; name ; iassignid ; author_name ; iassign_ilmid ; file ; filesid ; timecreated ; array of context.id } 
  ///
  //D print "---<br/>Get {iassign_statement} :: list_ias[] = { courseid (iassign.course) ; id (iassign_statement.id) ; name ; iassignid ; author_name ; iassign_ilmid ; file ; filesid ; timecreated ; array of context.id }<br/>\n";
  $str_query = "SELECT {iassign_statement}.id, {iassign_statement}.name, {iassign_statement}.iassignid, {iassign_statement}.author_name, {iassign_statement}.iassign_ilmid, " .
    " {iassign_statement}.file, {iassign_statement}.filesid, {iassign_statement}.timecreated, {iassign}.course AS courseid, {iassign}.name AS ias_name " .
    " FROM {iassign}, {iassign_statement} WHERE {iassign_statement}.iassignid = {iassign}.id ORDER BY {iassign}.course, {iassign_statement}.iassignid, {iassign_statement}.id";
    
  $list_ias = array(); // use continous array ($tmp_list_ias is indexed by 'id')
  $tmp_list_ias = $DB->get_records_sql($str_query); // get array of stdClass (must be only one element)
  $jj = 0;
  foreach ($tmp_list_ias as $element) {
    $answer = find_course_context($list_iassign, $element->iassignid); // to receive the array with all {context}.id associated with its {iassign}
    $element->contextid = $answer[0]; // -1 => error; otherwise is an array with all associated {context}.id
    if ($element->courseid != $answer[1]) echo " x Error: (" . $jj . ", id=" . $element->id . ") courseid=" . $element->courseid . "!=" . $answer[1] . "<br/>\n";
    $list_ias[$jj] = $element; // transcript array indexed by 'id' to "continous array"
    $jj++;
    }
  $sizeOf_list_ias = $jj;

  //D print " - #list_ias=" . $sizeOf_list_ias . "<br/>";

  /// Get {files} :: $list_all_files[] = { id ; contenthash ; contextid component filearea itemid filepath filename userid author timecreated ias.id }
  ///
  //  3. Vetor com todos os {files} com {iassign}.contextid       : $list_files   : id ; contenthash ; contextid component filearea itemid filepath filename userid author timecreated ias.id
  //D print "---<br/>Get {files} :: list_all_files[] = { id ; contenthash ; contextid ; component ; filearea ; itemid ; filepath ; filename ; userid ; author ; timecreated ; ias.id }<br/>\n";

  // Get all {files} associated with {iassign} (here the additional fields "$list_all_files[]->ias_id" is "-1" and "$list_all_files[]->missing_ias" is "FALSE")
  // Fields: id contenthash contextid component filearea itemid filepath filename userid author timecreated ias_id files_id
  // - "$list_all_files[$ii]->ias_id" will receive the {iassign_statement}.id with the same {context}.id
  // - "$list_all_files[$ii]->missing_ias" will receive FALSE only if its {iassign_statement} are correctly associated (in function "search_files(.)"
  // If $list_iassign[$ii] wasn't associated to any {iassign_statement} then "ias_id" := -1 (otherwise "ias_id" := {iassign_statement}.id
  $list_all_files = get_all_files($DB, $list_iassign);
  //D echo  " - #list_files=" . count($list_all_files) . "<br/>\n"; // &nbsp; *

  // List al {iassign_statement} with no {files} (from "$list_ias[.]") and then list all {files} with no {iassign_statement} (from "$list_all_files[.]")
  $countErr = 0;
  $countIA = 0;
  for ($jj=0; $jj<$sizeOf_list_ias; $jj++) { // try to find {files} (in $list_all_files) associated to each $list_ias[$jj]
    $element = $list_ias[$jj]; // array of { id (iassign_statement.id) ; name ; iassignid ; author_name ; iassign_ilmid ; file ; filesid ; timecreated ; contextid (array of context.id) }
    $answerSF = search_files($list_all_files, $element); // return [$ii, $jj] = {files}[$ii] is the associated file using context {iassign_statement}.contextid[$jj]
    if ($answerSF == -1) { // don't find!
      $element->missingfile = TRUE; // couldn't find {files} associated!
      // List all unmatched {files} under the same context
      // It will return tag SELECT 'filesid[]' with sizeof($files_index_candidates) options
      [$msgCandidates, $msgHidden, $files_index_candidates, $contextid_candidates, $list_candidate_filesbyID] = search_lost_files_in_context(2, $countErr, $element->courseid, $element->id, $list_all_files, $element->contextid, $countErr);
      if (count($files_index_candidates)==0) $msgCandidates = "";
      else $msgCandidates = "<br/>" . $msgCandidates;

      // $list_iassign[$ii-1]->id[] = $element->id; // new entry to contextid array -- context
      //R $msgC = "["; $sizeC = count($element->contextid); for ($kk=0; $kk<$sizeC; $kk++) $msgC .= $element->contextid[$kk] . $SEP; $msgC .= "]";
      //R print $msgHidden;
      $countErr++;
      }
    else {
      $countIA++;
      $element->missingfile = FALSE;
      }
    }

  print "<style> .error { background-color: #f4d6d2; color: #ca3120; }</style>\n";
  $msgErr = " class='error' "; // "color='#aa0000'";
  $msgTErr = ""; // " title='Not found' ";

  print "List of all {iassign_statement} with no {files} associated<br/>\n";
  print "<table id='outlinetable' class='generaltable boxaligncenter' width='100%'>\n";
  print "<tr><td title='count'>count</td><td>course.id</td><td title='iAssign id'>iassign.id</td><td>iAssign statement id</td><td title='iLM id'>iLM id</td><td title='context.id'>contextid</td>" .
    " <td title='iassign_statement.file'>file</td><td title='iassign_statement.filesid'>filesid</td><td title='date(timecreated)'>timecreated</td><td title='author'>Author</td>" .
    " <td title='{iassign}.name'>ia.name</td> <td title='{iassign_statement}.name'>ias.name</td></tr>\n";
  $countMIAS = 0;
  for ($jj=0; $jj<$sizeOf_list_ias; $jj++) { // try to find {files} (in $list_all_files) associated to each $list_ias[$jj]
    $element = $list_ias[$jj];
    if ($element->missingfile) {
      $msgC = "["; $sizeC = count($element->contextid); for ($kk=0; $kk<$sizeC; $kk++) $msgC .= $element->contextid[$kk] . $SEP; $msgC .= "]";     
      print "<tr " . $msgTErr . "><td>" . $countMIAS . "</td><td title='course.id'>" . $element->courseid . "</td><td title='iassign.id=iassign_statement.iassignid'>" . $element->iassignid . "</td>" . 
        " <td title='iassign_statement.id'>" . $element->id . "</td><td title='iassign_statement.iassign_ilmid'>" . $element->iassign_ilmid . "</td>\n" .
        " <td title='not found file possible array of context.id'>" . $msgC . "</td><td title='iassign_statement.file'>" . $element->file . "</td>\n" .
        " <td title='iassign_statement.filesid'>" . $element->filesid . "</td><td>" .
        date("d/m/Y H:i:s", $element->timecreated) . "</td><td>" . $element->author_name . "</td>" .
        " <td title='{iassign}.name'>" . $element->ias_name . "</td> <td title='{iassign_statement}.name'>" . $element->name . "</td></tr>\n";
      $countMIAS++; // next {iassign_statement} with no associated {files}
      }
    }
// SELECT * FROM s_files WHERE id=314077 OR id=313730 OR id=301156
// {files}.id=  ; contextid= ; itemid= ; filename             | {iassign_statement} id    name  (cursos/contextos: -1/51938 ; 475[49895;52998;] ; 485[52174;54306;] ; 486[52376;54615;] ; 487[52676;54821])
// 301156         52001        5658      vetor_soma_x_y.ivph  |                     5658  8.1 Vetor - Soma das posicoes x e y
// 313730         54821        5658      vetor_soma_x_y.ivph  <- course.id = 487
// 314077         54391        0         soma_2_inteiros.ivph
// - Encontrou 5658 via 'id' (id==itemid): contextid[1]=52001: 5658==5658 no files[12521] - {files}.id=301156
  print "</table><br/><br/>----------------------------<br/><br/>\n";

  print "List of all {files} with no {iassign_statement} associated<br/>\n";
  print "<table id='outlinetable' class='generaltable boxaligncenter' width='100%'>\n";
  print "<tr><td title='count'>count</td> <td>course.id</td> <td title='{files}.ias_id=iAssign id'>iassign.id</td> <td title='context.id'>contextid</td>" .
    " <td title='files.id'>id</td> <td title='{files}.itemid'>itemid</td> <td title='date(timecreated)'>date(timecreated)</td>" .
    " <td title='author'>author</td> <td title='{file}.filename'>filename</td></tr>\n";

  $countErrF = 0;
  $sizeOf_files = count($list_all_files);
  for ($jj=0; $jj<$sizeOf_files; $jj++) {
    $element = $list_all_files[$jj];
    if ($element->missing_ias) { // "$element->missing_ias = FALSE" in funcion "search_files(.)"
      print "<tr " . $msgTErr . "><td>" . $countErrF . "</td><td title='course.id'>" . $element->courseid . "</td> <td title='iassign.id=iassign_statement.iassignid'>" . $element->ias_id . "</td>" .
        " <td title='context.id''>" . $element->contextid . "</td> <td title='files.id'>" . $element->id . "</td> " . 
        " <td title='{files}.itemid'>" . $element->itemid . "</td> <td title='date(timecreated)'>" . date("d/m/Y H:i:s", $element->timecreated) . "</td>" .
        " <td title='author'>" . $element->author . "</td> <td title='{file}.filename'>" . $element->filename . "</td></tr>\n";
      $countErrF++;
      }
    }
  print "</table>\n";

  print "Total of {iassign_statement}: " . $countIA . "<br/>\n";
  print "Total of {iassign_statement} without any {files}: " . $countErr . "<br/>\n";
  print "Total of {files} without any {iassign_statement}: " . $countErrF . "<br/>\n";
  return $list_iassign;
  } // function list_all_iass_files($DB, $modules_iassignid)


/// Search for files associated to each "iassign_statement"
/// Must be used to produce a form with all {iassign_statement} with no {files} and their candidate {files} (with same {context}.id
// @calledby settings_verify.php: $list_iassign = see_all_iass_files($DB, 9);
// @param    $modules_iassignid is iAssign ID in {modules}
function see_all_iass_files ($DB, $modules_iassignid) {
  global $SEP;
  // Problema: como encontrar {files} com itemid errado?
  //  1. Vetor com todos os {iassign} com todos seus {context}.id : $list_iassign : id ; cmid ({course_modules}.id) ; course ({course}.id); name ; ia_id ({iassign}.id)
  //  2. Vetor com todos os {iassign_statement}                   : $list_ias     : id ; name ; iassignid ; author_name ; iassign_ilmid ; file ; fileid ; timecreated
  //  3. Vetor com todos os {files} com {iassign}.contextid       : $list_files   : id ; contenthash ; contextid component filearea itemid filepath filename userid author timecreated ias.id
  //  4. Vetor com todos os {iassign_statement} sem {files} correspondentes:

  // $list_iassign = $DB->get_records('iassign');
  // echo "#list_iassign=" . count($list_iassign) . "<br/>"; // 880
  // $ii = 0; foreach ($list_iassign as $element) { echo $ii . $SEP . $element->id . $SEP . $element->course . $SEP . $element->name . "<br/>"; $ii++; }
  // 880
  
  /// Get {iassign} :: $list_iassign[] = { id (context.id) ; cmid ; ia_id (iassign.id) ; course (course.id) ; name ({iassign}.name) }
  ///
  print "---<br/>Get {iassign} :: list_iassign[] = { id (context.id) ; cmid ; ia_id (iassign.id) ; course (course.id) ; name ({iassign}.name) }<br/>\n";
  //ATTENTION: 'get_records_sql(.)' return only the first element (first field)
  //$str_query = "SELECT {context}.id AS contextid, {course_modules}.id AS cmid, {iassign}.id, {iassign}.course, {iassign}.name FROM {iassign}, {context}, {course_modules} WHERE " .
  $str_query = "SELECT {context}.id, {course_modules}.id AS cmid, {iassign}.id AS ia_id, {iassign}.course, {iassign}.name FROM {iassign}, {context}, {course_modules} WHERE " .
    "{course_modules}.module=" . $modules_iassignid . " AND {iassign}.course={course_modules}.course AND {course_modules}.id={context}.instanceid AND {course_modules}.instance={iassign}.id";
  //SELECT s_context.id, s_course_modules.id AS cmid, s_iassign.id AS ia_id, s_iassign.course, s_iassign.name FROM s_iassign, s_context, s_course_modules WHERE s_course_modules.module=9 AND
  //       s_iassign.course=s_course_modules.course AND s_course_modules.id=s_context.instanceid AND s_course_modules.instance=s_iassign.id

  $tmp_list_iassign = $DB->get_records_sql($str_query); // get array of stdClass (must be only one element)
  print " - #tmp_list_iassign=" . count($tmp_list_iassign). "<br/>";
  //D echo "#" . $SEP . "id (context.id)" . $SEP . "ia_id (iassign.id)" . $SEP . "course" . $SEP . "cmid" . $SEP . "name" . $SEP . "contextid" . "<br/>"; $ii++;

  // Process $tmp_list_iassign to produce the array $list_iassign continous (starting from $list_iassign[0]),
  // with only one entry to each {iassign}.id and keeping all of its associated {context}.id under the array $list_iassign[$ii]->id
  // The {iassign}.id is $list_iassign[$ii]->ia_id
  $list_iassign = array(); // use continous array ($tmp_list_ias is indexed by 'id')
  $ii = 0;
  foreach ($tmp_list_iassign as $element) { // one {iassign} could have several contextid
    if ($ii==0 || $list_iassign[$ii-1]->ia_id != $element->ia_id) { // new element
      //D echo $ii . $SEP . $element->id . $SEP . $element->ia_id . $SEP . $element->course . $SEP . $element->cmid . $SEP . "'" . $element->name . "'" . $SEP . "<br/>"; // id=contextid
      $item = array(); // change to array
      $item[] = $element->id; // contextid
      $element->id = $item; // change to array - contextid = $item
      $list_iassign[$ii] = $element; // transcript array indexed by 'id' to "continous array"
      $ii++;
      }
    else { // just add new contextid (extends array $list_iassign[$ii-1]->id[])
      //_ $item = $list_iassign[$ii-1]->id; // is array -- contextid
      //_ $item[] = $element->id; // contextid
      //_ $list_iassign[$ii-1]->id = $item; // contextid
      $list_iassign[$ii-1]->id[] = $element->id; // new entry to contextid array -- context
      //D echo ($ii-1) . $SEP . $element->id . $SEP . $element->ia_id . $SEP . $element->course . $SEP . $element->cmid . $SEP . "'" . $element->name . "'" . $SEP . " *** #=" . count($list_iassign[$ii-1]->id) . "<br/>"; // id=contextid
      }
    }
  $sizeOf_list_iassign = $ii;
  print " - #list_iassign=" . $sizeOf_list_iassign . "<br/>";
  //D for ($jj=0; $jj<$sizeOf_list_iassign; $jj++) {
  //D   $element = $list_iassign[$jj];
  //D   echo $jj . $SEP . $element->ia_id . $SEP . $element->iassign_ilmid . $SEP . $element->iassignid . $SEP . $element->course . $SEP . $element->file . $SEP . $element->fileid .
  //D     $SEP . $element->timecreated . $SEP . "'" . $element->author_name . "'" . $SEP . "'" . $element->name . "': contextid=[";
  //D   $sizeC = count($element->id); // ($element->contextid);
  //D   for ($kk=0; $kk<$sizeC; $kk++) echo $element->id[$kk] . ","; // contextid[$kk]
  //D   if ($sizeC>1) echo "] ****************************************<br/>";
  //D   else echo "]<br/>";
  //D   }
  //L print "====================================================================================<br/>";

  /// Get {iassign_statement} :: $list_ias[] = { courseid (iassign.course) ; id (iassign_statement.id) ; name ; iassignid ; author_name ; iassign_ilmid ; file ; filesid ; timecreated ; array of context.id } 
  ///
  //ATTENTION: 'get_records_sql(.)' return only the first element (first field) => use unique first identifier {iassign_statement}.id
  print "---<br/>Get {iassign_statement} :: list_ias[] = { courseid (iassign.course) ; id (iassign_statement.id) ; name ; iassignid ; author_name ; iassign_ilmid ; file ; filesid ; timecreated ; array of context.id }<br/>\n";
  //$str_query = "SELECT {iassign_statement}.id, {iassign_statement}.name, {iassign_statement}.iassignid, {iassign_statement}.author_name, {iassign_statement}.iassign_ilmid, " .
  //  " {iassign_statement}.file, {iassign_statement}.filesid, {iassign_statement}.timecreated " .
  //  " FROM {iassign_statement} ORDER BY {iassign_statement}.iassignid, {iassign_statement}.id";
  $str_query = "SELECT {iassign_statement}.id, {iassign_statement}.name, {iassign_statement}.iassignid, {iassign_statement}.author_name, {iassign_statement}.iassign_ilmid, " .
    " {iassign_statement}.file, {iassign_statement}.filesid, {iassign_statement}.timecreated, " .
    " {iassign}.course AS courseid FROM {iassign}, {iassign_statement} WHERE {iassign_statement}.iassignid = {iassign}.id ORDER BY {iassign}.course, {iassign_statement}.iassignid, {iassign_statement}.id";
  //SELECT s_iassign_statement.id, s_iassign_statement.name, s_iassign_statement.iassignid, s_iassign_statement.author_name, s_iassign_statement.iassign_ilmid, s_iassign_statement.file,
  //       s_iassign_statement.filesid, s_iassign_statement.timecreated, s_iassign.course AS courseid FROM s_iassign, s_iassign_statement
  //WHERE s_iassign_statement.iassignid = s_iassign.id ORDER BY s_iassign.course, s_iassign_statement.iassignid, s_iassign_statement.id
  //SELECT s_iassign_statement.id, s_iassign_statement.name, s_iassign_statement.iassignid, s_iassign_statement.author_name, s_iassign_statement.iassign_ilmid,
  //       s_iassign_statement.file, s_iassign_statement.filesid, s_iassign_statement.timecreated FROM s_iassign_statement
    
  $list_ias = array(); // use continous array ($tmp_list_ias is indexed by 'id')
  $tmp_list_ias = $DB->get_records_sql($str_query); // get array of stdClass (must be only one element)
  $jj = 0;
  foreach ($tmp_list_ias as $element) {
    $answer = find_course_context($list_iassign, $element->iassignid); // to receive the array with all {context}.id associated with its {iassign}
    $element->contextid = $answer[0]; // -1 => error; otherwise is an array with all associated {context}.id

    //$element->courseid = $answer[1];  // -1 => error; otherwise {iassicn}.course = {course}.id
    if ($element->courseid != $answer[1]) echo " x Error: (" . $jj . ", id=" . $element->id . ") courseid=" . $element->courseid . "!=" . $answer[1] . "<br/>\n";

    $list_ias[$jj] = $element; // transcript array indexed by 'id' to "continous array"
    $jj++;
    }
  $sizeOf_list_ias = $jj;

  //L for ($jj=0; $jj<$sizeOf_list_ias; $jj++) {
  //L   $element = $list_ias[$jj];
  //L   //D echo $jj . $SEP . $element->id . $SEP . $element->iassign_ilmid . $SEP . $element->iassignid . $SEP . $element->file . $SEP . $element->fileid .
  //L   //D   $SEP . $element->timecreated . $SEP . "'" . $element->author_name . "'" . $SEP . "'" . $element->name . "'" . $SEP . "[";
  //L   //D $sizeC = count($element->contextid); // array of {context}.id // $element->contextid . $SEP 
  //L   //D for ($kk=0; $kk<$sizeC; $kk++) echo $element->contextid[$kk] . $SEP;
  //L   //D echo "]<br/>";
  //L   }    

  print " - #list_ias=" . $sizeOf_list_ias . "<br/>";
  //L print "====================================================================================<br/>";

  /// Get {files} :: $list_all_files[] = { id ; contenthash ; contextid component filearea itemid filepath filename userid author timecreated ias.id }
  ///
  //  3. Vetor com todos os {files} com {iassign}.contextid       : $list_files   : id ; contenthash ; contextid component filearea itemid filepath filename userid author timecreated ias.id
  print "---<br/>Get {files} :: list_all_files[] = { id ; contenthash ; contextid ; component ; filearea ; itemid ; filepath ; filename ; userid ; author ; timecreated ; ias.id }<br/>\n";
  //$list_files = array(); // $list_files[$ii] = { ia_id, ia_file, ia_filesid, filesid=files.id, filename=files.filename, itemid=files.itemid, contextid=files.contextid }

  // Get all {files} associated with {iassign} (here the additional field "ias_id" is "-1")
  // Fields: id contenthash contextid component filearea itemid filepath filename userid author timecreated ias_id
  // If $list_iassign[$ii] wasn't associated to any {iassign_statement} then "ias_id" := -1 (otherwise "ias_id" := {iassign_statement}.id
  $list_all_files = get_all_files($DB, $list_iassign);
  echo  " - #list_files=" . count($list_all_files) . "<br/>\n"; // &nbsp; *

  print "<style> .error { background-color: #f4d6d2; color: #ca3120; }</style>\n";
  $msgErr = " class='error' "; // "color='#aa0000'";
  $msgTErr = ""; // " title='Not found' ";
  print "<form action='settings_process_files_form.php' method='post' class='form-example'>\n"; // form to be processed by 'setting_process_files_form.php'
  print "<button type='submit' class='button action has-icon search-button'>Enviar</button>\n"; //TODO: internacionalizar
  print "<input type='hidden' name='ilm_id' value='" . $modules_iassignid . "'>\n";

  print "<table id='outlinetable' class='generaltable boxaligncenter' width='100%'>\n";
  print "<tr><td title='count'>count</td><td>course.id</td><td title='iAssign id'>iassign.id</td><td>iAssign statement id</td><td title='iLM id'>iLM id</td><td title='context.id'>contextid</td>" .
    "<td title='iassign_statement.file'>file</td><td title='iassign_statement.filesid'>filesid</td><td title='date(timecreated)'>timecreated</td><td title='author'>Author</td><td title='name'>ias.name</td>" .
    "<td title='{file}.filename'>{files}.filename</td><td title='{file}.id'>id</td><td title='{files}.itemid'>itemid</td></tr>\n";
  // <td title='ID:={course_modules}.id to access mod/iassign/view.php?id=ID'>{course_modules}.id</td><td># files</td>";
  // Arrays to be used in "select" to recover missing files: $err_iassignid[], $err_iassignstatementid[], $err_selectedfilesid[]
  $err_iassignid = array();          // array with the $jj {iassign}.id with no {iassign_statement} withount any {files}
  $err_iassignstatementid = array(); // array with the $jj {iassign_statement}.id with no {files}
  $err_selectedfilesid = array();    // array with the $jj {files}.id to be associated with the $jj {iassign_statement}
  $countErr = 0;
  $countIA = 0;
  for ($jj=0; $jj<$sizeOf_list_ias; $jj++) { // try to find {files} (in $list_all_files) associated to each $list_ias[$jj]
  //D for ($jj=0; $jj<10; $jj++) { // try to find {files} (in $list_all_files) associated to each $list_ias[$jj]
    $element = $list_ias[$jj]; // array of { id (iassign_statement.id) ; name ; iassignid ; author_name ; iassign_ilmid ; file ; filesid ; timecreated ; contextid (array of context.id) }
    $answerSF = search_files($list_all_files, $element); // return [$ii, $jj] = {files}[$ii] is the associated file using context {iassign_statement}.contextid[$jj]
    if ($answerSF == -1) { // don't find!
      // List all unmatched {files} under the same context
      // It will return tag SELECT 'filesid[]' with sizeof($files_index_candidates) options
      [$msgCandidates, $msgHidden, $files_index_candidates, $contextid_candidates, $list_candidate_filesbyID] = search_lost_files_in_context(1, $countErr, $element->courseid, $element->id, $list_all_files, $element->contextid, $countErr);
//if ($jj==29) $msgCandidates = "<br/>#files_index_candidates=" . count($files_index_candidates) . " " . $msgCandidates; // " contextid=" . $element->contextid . 
//else
      if (count($files_index_candidates)==0) $msgCandidates = "";
      else $msgCandidates = "<br/>" . $msgCandidates;

      // $list_iassign[$ii-1]->id[] = $element->id; // new entry to contextid array -- context
      $msgC = "["; $sizeC = count($element->contextid); for ($kk=0; $kk<$sizeC; $kk++) $msgC .= $element->contextid[$kk] . $SEP; $msgC .= "]";
      print $msgHidden;
      print "<tr " . $msgTErr . "><td>" . $countErr . "</td><td title='course.id'>" . $element->courseid . "</td><td title='iassign.id=iassign_statement.iassignid'>" . $element->iassignid . "</td>" . 
//RRR      print "<tr " . $msgTErr . "><td>" . $jj . "</td><td title='course.id'>" . $element->courseid . "</td><td title='iassign.id=iassign_statement.iassignid'>" . $element->iassignid . "</td>" .
        " <td title='iassign_statement.id'>" . $element->id . "</td><td title='iassign_statement.iassign_ilmid'>" . $element->iassign_ilmid . "</td>\n" .
        " <td title='not found file possible array of context.id'>" . $msgC . "</td><td title='iassign_statement.file'>" . $element->file . "</td>\n" . 
        " <td title='iassign_statement.filesid'>" . $element->filesid . "</td><td>" .
        date("d/m/Y H:i:s", $element->timecreated) . "</td><td>" .
        $element->author_name . "</td> <td>" . $element->name . "</td><td colspan='3' " . $msgErr . ">\n" . $msgCandidates . "</td></tr>\n"; // ERRO
      $err_iassignid[] = $element->iassignid;   // array with the $jj {iassign}.id with no {iassign_statement} withount any {files}
      $err_iassignstatementid[] = $element->id; // array with the $jj {iassign_statement}.id with no {files}
      $err_selectedfilesid[] = countErr;        // array with the $jj {files}.id to be associated with the $jj {iassign_statement}
      $countErr++;
      }
    else {
/*
      [$indexF, $indexC] = $answerSF; //  {files}[$indexF] is the associated file using context {iassign_statement}.contextid[$indexC] 
      print "<tr><td>" . $jj . "</td><td title='course.id'>" . $element->courseid . "</td><td title='iassign.id=iassign_statement.iassignid'>" . $element->iassignid . "</td>\n" .
        " <td title='iassign_statement.id'>" . $element->id . "</td><td title='iassign_statement.iassign_ilmid'>" . $element->iassign_ilmid . "</td>\n" .
        " <td title='[files.contextid]'>" . $element->contextid[$indexC] . "</td><td title='iassign_statement.file'>" . $element->file . "</td><td title='iassign_statement.filesid'>" . $element->filesid . "</td>" .
        " <td>" . date("d/m/Y H:i:s", $element->timecreated) .
        "</td><td>" . $element->author_name . " <td>" . $element->name . "</td><td title='Found {files}.id in list_all_files[" . $indexF . "]: ias_id=" . $list_all_files[$indexF]->ias_id. "'>" .
        $list_all_files[$indexF]->filename . "</td><td title='Found in list_all_files[" . $indexF . "]->id={files}.id'>" . $list_all_files[$indexF]->id . "</td>" .
        "<td title='{files}.itemid'>" .
        $list_all_files[$indexF]->itemid . "</td></tr>\n";
      //TODO Usar $list_all_files[$indexF]->id = {files}.id para redefinir campo {iassign_statement}.filesid: UPDATE $list_ias[$jj]->filesid = $list_all_files[$indexF]->id
      //TODO Usar $list_ias[$jj]->ia_id = {iassign_statement}.id para redefinir campo {files}.itemid        : UPDATE 
      //$list_all_files[$indexF]->id = {files}.id
*///RRR
      $countIA++;
      }
    }
  print "</table>\n";
  print "</form>\n";

  print "Total of {iassign_statement}: " . $countIA . "<br>";
  print "Total of {iassign_statement} without any {files}: " . $countErr . "<br>";
  return $list_iassign;
  } // function see_all_iass_files($DB, $modules_iassignid)


//====================================================================================================================================


// Create entry with { ia_id, ia_file, ia_filesid, ia_name, filesid, filename, itemid } for each iAssign statement and files
function createNewEntry ($iassign_statement, $element_file) {
  $new_file = new stdClass(); // { ia_id, ia_file, ia_filesid, ia_name, filesid, filename, itemid } for each iAssign statement and files
  $new_file->ia_id = $iassign_statement->id;
  $new_file->ia_name = $iassign_statement->name;
  $new_file->ia_file = $iassign_statement->file;
  $new_file->ia_filesid = $iassign_statement->filesid;
  if ($element_file != NULL) {
    $new_file->filesid = $element_file->id;
    $new_file->filename = $element_file->filename;
    $new_file->itemid = $element_file->itemid;
    $new_file->contextid = $element_file->contextid;
    }
  else {
    $new_file->filesid = NULL;
    $new_file->filename = NULL;
    $new_file->itemid = NULL;
    $new_file->contextid = NULL;
    }
  return $new_file;
  } // function createNewEntry($iassign_statement, $element_file)


// Try several method to recover file (MoodleData) associated to the iAssign activity
// Given array $all_context, defined a clean version $all_context_final
// @param &$all_context_final : passed by reference
// @calledby this->get_files($course_id, $DB)
function get_associated_files ($DB, $iassign_statement, $all_context, &$all_context_final, $ii) {
  global $USER; //DEBUG: apenas durante depuracao!
  $array_context_element = $all_context[$ii]; // {context}.id AS contextid, {course}.id, {course}.fullname, {course}.shortname, {course_modules}.id AS course_module_id, {context}.instanceid
  $sizeAC = count($array_context_element);

  if (!is_array($array_context_element)) { // just in case '$answer_list = $DB->get_records_sql($str_query);' returned only one element
    $array_context_elements = array();
    $array_context_elements[] = $all_context[$ii];
    }
  else
    $array_context_elements = $all_context[$ii];

  $sizeAC = count($array_context_elements);
  //  for ($jj=0; $jj<$sizeAC; $jj++) { $element_context = $array_context_elements[$jj]; $msgEC .= " &nbsp; * " . $element_context->contextid . ", " . $element_context->id . ", '" . $element_context->shortname . "', " . $element_context->course_module_id . ", " . $element_context->instanceid . "<br/>"; }
//D__ echo "----<br/>#array_context_elements=" . $sizeAC . " (get_associated_files(.))<br/>\n";
  $countF = 0;
  $jj = 0;
  while ($jj<$sizeAC) {
      $context_element = $array_context_elements[$jj];
      $contextid = $context_element->contextid; // context of this 'iassign.id'
//$msgEC .= " &nbsp; * " . $context_element->contextid . ", " . $context_element->id . ", '" . $context_element->shortname . "', " . $context_element->course_module_id . ", " . $context_element->instanceid . "<br/>"; }
//D__ echo " * Tentar com contextid=" . $contextid . ", iassign_statement.id=" . $iassign_statement->id . ", " . $context_element->id . ", '" . $context_element->shortname . "', " . $context_element->course_module_id . ", " . $context_element->instance . " (jj=" . $jj . ")<br/>";

      $files = $DB->get_records('files', array('contextid' => $contextid, 'component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $iassign_statement->id)); // all 'files' associated
      $numf = count($files); $countF += $numf; $msgO = "iassign_statement.id"; //D

      // To avoid programming mistake on old versions, try to get 'files' with 'iassign_statement.id', 'iassign_statement.file' and 'iassign_statement.filesid'
      $files = $DB->get_records('files', array('contextid' => $contextid, 'component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $iassign_statement->id)); // all 'files' associated
      $numf = count($files); $countF += $numf; $msgO = "iassign_statement.id"; //D
      if ($numf == 0) {
        if (isset($iassign_statement->file))
          $files = $DB->get_records('files', array('contextid' => $contextid, 'component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $iassign_statement->file)); // all 'files' associated
        $numf = count($files); $countF += $numf; $msgO = "iassign_statement.file"; //D
        if ($numf == 0) {
          if (isset($iassign_statement->filesid)) // try 'iassign_statement.filesid = files.itemid'
            $files = $DB->get_records('files', array('contextid' => $contextid, 'component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $iassign_statement->filesid));
          $numf = count($files); $countF += $numf; $msgO = "iassign_statement.filesid"; //D
          if ($numf == 0) {
            if (isset($iassign_statement->filesid)) // try 'iassign_statement.filesid = files.id'
              $files = $DB->get_records('files', array('id' => $iassign_statement->filesid)); // all 'files' associated
            $numf = count($files); $countF += $numf; $msgO = "files.id=iassign_statement.filesid"; //D
            // if ($numf == 0) {
            //   // try 'files.itemid = iassign_statement.id'
            //   $files = $DB->get_records('files', array('id' => $iassign_statement->filesid)); // all 'files' associated
            //   $numf = count($files); $countF += $numf; $msgO = "iassign_statement.filesid=files.itemid"; //D
            //   }
            if ($numf == 0) {
              //D__ echo  " &nbsp; * files: " . $numf . " - ERRO 'id' 'file' 'filesid' to statement id=" . $iassign_statement->id . ": " . $iassign_statement->name . "<br/>\n"; //D

//D__ if ($iassign_statement->id==5118) {
//D__  $context = context_module::instance($USER->cm); // usado no: ilm_handlers/html5.php
//D__  //D__ echo " &nbsp; * {course_module}.id=" . $USER->cm .", USER.{context}.id=" . $context->id . "<br/>\n";
//D__  $fs = get_file_storage();
//D__  $files = $fs->get_area_files($context->id, 'mod_iassign', 'exercise', $iassign_statement->id); $fileid = -1; $filename = "";
//D__  if ($files) { //TODO To be revised? 'files.filename' has '.' is only path (not the file)
//D__    foreach ($files as $value) { if ($value->get_filename() != '.') { $fileid = $value->get_id(); $filename = $value->get_filename(); break; } }
//D__    }
//D__  //D__ echo " &nbsp; * codigo de 'ilm_handlers/html5.php': file.id=" . $fileid . ", filename=" . $filename . "<br/>";
//D__  }
              }
            }
          }
        } // if ($numf == 0)
      if ($numf>0) { $msgF = ""; foreach ($files as $element_file) $msgF .= "id=" . $element_file->id . ", filename='" . $element_file->filename . "'; ";
//D__ echo  " &nbsp; * files: " . $numf . " - encontrei {files}: " . $msgF . "<br/>\n"; //D echo  " &nbsp; * files: "; print_r($files); echo "<br/>\n"; //
        $all_context_final[$ii] = $context_element; // now only one array with {context}.id AS contextid, {course}.id, {course}.fullname, {course}.shortname, {course_modules}.id AS course_module_id, {context}.instanceid
        return [ $files, $contextid, $numf, $msg0 ];
        }
    $jj++;
    }
  $all_context_final[$ii] = $context_element; // now only one array
  return [ null, -1, 0, $msg0 ];
  } // function get_associated_files($DB, $iassign_statement, $all_context, &$all_context_final, $ii)


// @calledby locallib.php : add_edit_iassign(.)
// @calledby settings_activities.php: [$list_all_instances_of_iassign, $all_context, $list_of_ia_files...] = get_files($courseid, $ilm_id, $DB);
function get_files ($course_id, $ilm_id, $DB) { //2022
  //__ echo "get_files(.): course_id=" . $course_id . "<br/>";
  // Get all iAssignment block inside this course
  if (isset($ilm_id) && $ilm_id) {
    $str_query = "SELECT DISTINCT {iassign}.* FROM {iassign}, {iassign_statement} WHERE " .
      " {iassign}.id={iassign_statement}.iassignid AND {iassign_statement}.iassign_ilmid=" . $ilm_id;
    //D echo "str_query=" . $str_query . "<br/>"; // SELECT {context}.id AS contextid, {course}.id, {course}.fullname, {course}.shortname, {course_modules}.id AS course_module_id, {context}.instanceid FROM {course}, {context}, {course_modules} WHERE {course}.id={course_modules}.course AND {course_modules}.id={context}.instanceid AND {course_modules}.instance=?
    $aux_answer_list_iassign = $DB->get_records_sql($str_query); // get array of stdClass (must be only one element)
    //D echo "#aux_answer_list_iassign=" . count($aux_answer_list_iassign) . "<br/>";
    $answer_list_iassign = array(); // transform the associative array in one that is continous
    foreach ($aux_answer_list_iassign as $item) {
      $answer_list_iassign[] = $item;
      }
    //D echo "#answer_list_iassign=" . count($answer_list_iassign) . "<br/>";
    //D $sizeIA = count($answer_list_iassign);
    //D for ($ii=0; $ii<$sizeIA; $ii++) echo $ii . " : " . $answer_list_iassign[$ii]->id . ", " . $answer_list_iassign[$ii]->name . ", " . $answer_list_iassign[$ii]->author_name . "<br/>\n";
    //D exit;
    }
  else
  if (isset($course_id) && $course_id) // not yet used...
    $answer_list_iassign = $DB->get_records('iassign', array('course' => $course_id)); //R $this->get_courseid() -> $course_id
  else {
    print "Attention, parameters {course}.id and {modules}.id of iAssign not defined!<br/>";
    exit(1);
    // $answer_list_iassign = $DB->get_records('iassign'); // all courses
    }
   //$tmp_list_ias = $DB->get_records_sql($str_query); // get array of stdClass (must be only one element)
//D echo "#answer_list_iassign = " . sizeof($answer_list_iassign) . "<br/>";
//$list_iassign = $DB->get_records('iassign');
//echo "#list_iassign=" . count($list_iassign) . "<br/>"; $SEP = ", "; // 880
//$ii = 0; foreach ($list_iassign as $element) { echo $ii . $SEP . $element->id . $SEP . $element->course . $SEP . $element->name . "<br/>"; $ii++; }

  // Build 3 arrays (list_all_instances_of_iassign[.], all_context[.], list_of_files[.])
  // - $list_all_instances_of_iassign[$ii] <-> $all_context[$ii] <-> $list_of_files[$ii]

  // Build all context per course
  $list_of_contextid_by_courses = array(); // list of courses by ID ({course}.id): $list_of_contextid_by_courses[$i] = array(with all of its context)
  $list_of_courses = array(); // list of {course}.id: $list_of_courses[$i] = array(with {course}.id, {course}.fullname, {course}.shortname)
  $list_of_coursesid = array(); // auxiliary to easy to generate $list_of_courses[]

  // Get all context of each iAssignment block inside this course: $list_all_instances_of_iassign[.] ; $all_context[.]
  $list_all_instances_of_iassign = array(); // will return { id, contextid, shortname} = { iassign.id, context.id, course.shortname } <-> $list_of_files[.]
  $all_context_final = array(); // will be defined by $all_context considering eventually more then one context to recover {files}
  $all_context = array();
  $ii = 0;
  foreach ($answer_list_iassign as $iassign) { // get all context of each iAssign block: this iAssign block $iassign
    //DEBUG: the abstract function of "/lib/dml/moodle_database.php!get_records_sql(.)" are returning only half of rows! Than is recommended to use 'get_in_or_equal(.)'!
    //DEBUG: ATTENTION, get_records_XX() family of functions inside Moodle always returns one associative array, i.e. one array of records where the key is the first field in the SELECT clause.
    //DEBUG: So, if you get two records with the same value in the 1st field the functions will "group" them, returning the last one in the real recordset.
    //DEBUG: Then is necessary to use as the first field one that has unique representation considering all rows, 
    //DEBUG: so we must use '{context}.id' since several of them could be associated to one {course_modules}
    $str_query = "SELECT {context}.id AS contextid, {course}.id, {course}.fullname, {course}.shortname, {course_modules}.id AS course_module_id, {context}.instanceid " .
      " FROM {course}, {context}, {course_modules} WHERE " .
      " {course}.id={course_modules}.course AND {course_modules}.id={context}.instanceid AND {course_modules}.instance=" . $iassign->id . " AND {course}.id=" . $iassign->course .
      " ORDER BY {course}.id, {course_modules}.instance"; // Redundance: {course_modules}.instance=" . $iassign->id . " AND {course}.id=" . $iassign->course
    //  " {course}.id={course_modules}.course AND {course_modules}.id={context}.instanceid AND {course_modules}.course=" . $course_id . " AND {course_modules}.instance=" . $iassign->id;
    //D echo "str_query=" . $str_query . "<br/>"; // SELECT {context}.id AS contextid, {course}.id, {course}.fullname, {course}.shortname, {course_modules}.id AS course_module_id, {context}.instanceid FROM {course}, {context}, {course_modules} WHERE {course}.id={course_modules}.course AND {course_modules}.id={context}.instanceid AND {course_modules}.instance=?
    $answer_list = $DB->get_records_sql($str_query); // get array of stdClass (must be only one element)

    //D if ($iassign->id==1299) { // ($iassign->id==1) { //
    //D  echo "query=" . $str_query . "<br/>#answer_list=" . count($answer_list) . ": <br/>"; } // print_r($answer_list); echo "<br/>"; }

    $list1 = array();
    $strAux = "";
    foreach ($answer_list as $item) { // $answer_list[] from {context}, {course} and {course_modules}
      $list1[] = $item; // transform the associative array in one that is continous
      $courseid = $item->id;
      if (!in_array($courseid, $list_of_coursesid)) { // array of contextid not initiated for this course
        $list_of_courses[] = array($item->id, $item->shortname, $item->fullname); // list of courses array({course}.id, {course}.shortname, {course}.fullname)
        $list_of_coursesid[] = $item->id; // auxiliary to easy to generate $list_of_courses[]
        $iassign->courseid = $item->id;
        $iassign->fullname = $item->fullname;
        //DD echo "(".$item->id.",".$item->shortname .") ";
        }
      if (!isset($list_of_contextid_by_courses[$courseid])) { // array of contextid not initiated for this course
        $list_of_contextid_by_courses[$courseid] = array($item->contextid); // list of courses by ID ({course}.id)
        }
      else // already initiated this course
        array_push($list_of_contextid_by_courses[$courseid], $item->contextid); // append a new contextid

      //_ $strAux = "[" . $item->id . "," . $item->contextid . "," . $item->course_module_id . "," . $item->shortname . "," . $item->instanceid . "] ";
      //_ if ($iassign->id==1299) { // ($iassign->id==1) { // print_r($item);
      //_   echo " - iassign.id=" . $list_all_instances_of_iassign[$ii]->id . ": [id,context.id,course_module.id,shortname,instanceid]=" . $strAux . "<br/>\n";
      //_   }
      } // foreach ($answer_list as $item)
    //DD echo "<br/>";

    $list_all_instances_of_iassign[] = $iassign;

    $all_context[$ii] = $list1; // $list1[0]; // { course.id, course.fullname, course.shortname, context.id }
    //__ echo " - iassign.id=" . $list_all_instances_of_iassign[$ii]->id . ", contextid=" . $all_context[$ii]->contextid . ",  : " . $strAux . "<br/>\n"; //print_r($answer_list); echo "<br/>\n";
    $ii++;
    } // foreach $answer_list_iassign
  //D echo "Total de atividades: ii = " . $ii . "<br/>";

  // Built the list of all iAssign associated with this block of activities ('iassign_statement' and 'files')
  // This list is associated with the list of iAssign instances: $list_of_files[$ii] <=> $all_context[$ii]
  // $list_of_files[$ii] is an array of all {iassign.id, iassign.name, iassign_statement.id, iassign_statement.filesid, iassign_statement.name, files.id, files.contextid, files.itemid, files.name}
  $list_of_ia_files = array(); // as { iassign_statement->id, iassign_statement->name, iassign_statement->filesid, files->id, files->contextid, files->itemid, files->filename}

  $sizeofList = $ii; // size of $all_context[]
  for ($ii=0; $ii<$sizeofList; $ii++) { // get all iAssign block of activities (course.moudules)
    $iassign = $list_all_instances_of_iassign[$ii];
    
    // Get all activities inside this block, only to the iLM selected $ilm_id == {modules}.id
    $list_all_iassign_statement = $DB->get_records('iassign_statement', array('iassignid' => $iassign->id, 'iassign_ilmid' => $ilm_id));
      
    $list_of_files = array(); // $list_of_files[$ii] = { ia_id, ia_file, ia_filesid, filesid=files.id, filename=files.filename, itemid=files.itemid, contextid=files.contextid }
    //D__ echo  "#list_all_iassign_statement = " . count($list_all_iassign_statement) . " de iassign.id=" . $iassign->id . "<br/>\n"; //D2022
    foreach ($list_all_iassign_statement as $iassign_statement) { // get all iAssign activity inside the block '$list_all_iassign_statement'
      // $contextid = $all_context[$ii]->contextid; // context of this 'iassign.id'

      // Build $all_context_final[$ii] as $all_context[$ii][0] or $all_context[$ii][1] - If $contextid==-1 then erro! Could not recover file!
      [$files, $contextid, $numf, $msg0 ] = get_associated_files($DB, $iassign_statement, $all_context, $all_context_final, $ii);//echo  " &nbsp; * ii=" . $ii . ": #all_context_final[" . $ii . "]=" . count($all_context_final[$ii]) . "<br/>\n";

      //D__ echo  " - ii=" . $ii . ": iassign_statement {id = " . $iassign_statement->id . ", file = " . $iassign_statement->file . ", filesid = " . $iassign_statement->filesid . "}; contextid=" . $contextid . "=" . $all_context_final[$ii]->contextid . ", date(timecreated)=" . date("Y/m/d H:i:s", $iassign_statement->timecreated) . " : " . $iassign_statement->name . "<br/>\n"; //D2022

      if ($numf > 0) { // if $iassign_statement has any file (in {files})
        //__ echo  " &nbsp; * files: " . $numf . ": " . $msgO . "<br/>\n"; //D
        $filename = array();
        foreach ($files as $element_file) { // analyse each '$element_file' of 'files'
          //D__ echo  " &nbsp; * ii=" . $ii . ": files: id = " . $element_file->id . ", contextid = " . $element_file->contextid . ", itemid=" . $element_file->itemid . " : " . $element_file->filename . "<br/>\n"; //D
          $new_file = createNewEntry($iassign_statement, $element_file); // create entry with { ia_id, ia_file, ia_filesid, ia_name, filesid, filename, itemid } for each iAssign statement and files
          if ($element_file->filename != '.') { // get the '$element_file' that is not only "path"
            $filename = explode(".", $element_file->filename);
            if ($iassign_statement->filesid != $element_file->id) { // need to update 'iassign_statement.filesid'?
              // echo " &nbsp; &nbsp; &nbsp; &nbsp; Update 'iassign_statement.filesid'=" . $iassign_statement->filesid . " <- " . $element_file->id . " : " . $element_file->filename . "<br/>"; //2022
//++ Deixar para outro corrigir! A partir de 'settings_activities.php'
              $iassign_statement->filesid = $element_file->id; // 'iassign_statement.filesid' <- 'files.id' (update bellow) //2022 deixar para outro corrigir!
              }
            }
          if ($element_file->itemid != $iassign_statement->id) { // need to update 'files.itemid'? ('files.itemid <- iassign_statement.id') //2022
            $newentry_be_updated = new stdClass(); 
            $newentry_be_updated->id = $element_file->id; $newentry_be_updated->itemid = $iassign_statement->id;
//            echo " &nbsp; &nbsp; &nbsp; &nbsp; Update 'files.itemid': id=" . $newentry_be_updated->id . ", itemid=" . $element_file->itemid . "<-" . $newentry_be_updated->itemid . " : " . $element_file->filename . "<br/>"; //2022

//R NAO alterar!
//R              if (!$DB->update_record('files', $newentry_be_updated)) print_error('error_update', 'iassign');

            }
          if (isset($new_file->ia_id) && $new_file->ia_id>0) { // security...
            $list_of_files[] = $new_file; // $list_of_files[$ii] = { ia_id, ia_file, ia_filesid, filesid=files.id, filename=files.filename, itemid=files.itemid, contextid=files.contextid }
            } // echo " &nbsp; * # = " . 
          } // foreach ($files as $element_file)
        $list_of_ia_files[$ii] = $list_of_files;
        $extension = ""; // extension of the current '$element_file'
        if (count($filename) > 1) $extension = strtolower($filename[count($filename) - 1]);
        foreach ($list_all_iassign_ilm as $iassign_ilm) { // find iLM with the same extension (to register its 'iassign_ilm.id')
          $extensions_of_ilm = explode(",", $iassign_ilm->extension);
          if (in_array($extension, $extensions_of_ilm)) { // it was found the iLM with correct extension - get the lastast version
            $iassign_statement->iassign_ilmid = $iassign_ilm->id; // the iLM identification: iassign_ilm.id
            // echo " &nbsp; &nbsp; &nbsp; &nbsp; Update 'iassign_statement.iassign_ilmid': " . $iassign_statement->iassign_ilmid . " <- " . $iassign_ilm->id . "<br/>\n"; 
            }
          }
//D__ echo " - Troca iassign_statement.id=" . $iassign_statement->id . " (" . $iassign_statement->name . ") troca filesid para " . $iassign_statement->filesid . "<br/>";
//R          $DB->update_record("iassign_statement", $iassign_statement); // update 'iassign_statement' (mainly 'iassign_statement.filesid')
        } // if ($numf > 0)
      else { // Error!!! Couldn't find any {files} associated to this statement!
        // echo  " &nbsp; * ii=" . $ii . ": files: vazio!<br/>\n";
        $list_of_files[] = createNewEntry($iassign_statement, NULL); // create entry with { ia_id, ia_file, ia_filesid, ia_name, filesid=NULL, filename=NULL, itemid=NULL }
        $list_of_ia_files[$ii] = $list_of_files;
        }
      } // foreach ($list_all_iassign_statement as $iassign_statement)
    } // for ($ii=0; $ii<$sizeofList; $ii++)

  return [$list_all_instances_of_iassign, $all_context_final, $list_of_ia_files, $list_of_contextid_by_courses, $list_of_courses];
  } // function get_files($course_id, $DB)


// Codigo de teste tinha inserido em 'locallib.php' (era invocado linha 2458/9172): static function test_iassign_statement_files()
// @calledby locallib.php : add_edit_iassign(.)
function see_all_files_context ($course_id, $DB) { //2022 testar 'restore_iassign_stepslib.php()' - remover!
// See mod/iassign/backup/moodle2/restore_iassign_stepslib.php : after_execute()
// /var/www/html/moo391p/mod/iassign/backup/moodle2/restore_iassign_stepslib.php
  //global $CFG, $DB, $USER;
  //$fs = get_file_storage();

$context_id = 1;
// $context = context_module::instance($USER->cm); //2022/02: get 'context.id' = 'files.contextid'
echo "files_functions.php: context.id=" . $context_id . "<br/>\n";
$countF = 0;

  // Get all iLM installed in iAssign
  $list_all_iassign_ilm = $DB->get_records('iassign_ilm', array('parent' => 0, 'enable' => 1));

  // Get all iAssignment block inside this course
  $answer_list_iassign = $DB->get_records('iassign', array('course' => $course_id)); //R $this->get_courseid() -> $course_id

  // Get all context of each iAssignment block inside this course
  $list_all_instances_of_iassign = array(); // with { iassign.id, context.id, course.shortname } as { id, contextid, shortname }
  $all_context = array();
  $ii = 0;
  foreach ($answer_list_iassign as $iassign) { // get all context of this iAssign block $iassign
    $list_all_instances_of_iassign[] = $iassign;
    $str_query = "SELECT {course}.id, {course}.shortname, {context}.id AS contextid FROM {course}, {context}, {course_modules} WHERE " .
               "{course}.id={course_modules}.course AND {course_modules}.id={context}.instanceid AND {course_modules}.course=" . $course_id . " AND {course_modules}.instance=" . $iassign->id;
//echo "query=" . $str_query . "<br/>";
    $answer_list = $DB->get_records_sql($str_query); // get array of stdClass (must be only one element)
    $list1 = array();
    $strAux = "";
    foreach ($answer_list as $item) { $list1[] = $item; $strAux .= "[" . $item->id . "," . $item->contextid . "," . $item->shortname . "] "; }
    $all_context[$ii] = $list1[0];
    echo " - iassign.id=" . $list_all_instances_of_iassign[$ii]->id . ", contextid=" . $all_context[$ii]->contextid . " : " . $strAux . "<br/>\n"; //print_r($answer_list); echo "<br/>\n";
    $ii++;
    }
  $sizeofList = $ii;
  for ($ii=0; $ii<$sizeofList; $ii++) { // get all iAssign block of activities (course.moudules)
    $iassign = $list_all_instances_of_iassign[$ii];
    $list_all_iassign_statement = $DB->get_records('iassign_statement', array('iassignid' => $iassign->id)); // get all activities inside this block
    $contextid = $all_context[$ii]->contextid; // context of this 'iassign.id'
//D echo  "#list_all_iassign_statement = " . count($list_all_iassign_statement) . " de iassign.id=" . $iassign->id . "<br/>\n"; //D2022
    foreach ($list_all_iassign_statement as $iassign_statement) { // get all iAssign activity inside the block '$list_all_iassign_statement'
//D echo  " - iassign_statement {id = " . $iassign_statement->id . ", file = " . $iassign_statement->file . ", filesid = " . $iassign_statement->filesid .
//D "}; contextid=" . $contextid . ", date(timecreated)=" . date("Y/m/d H:i:s", $iassign_statement->timecreated) . " : " . $iassign_statement->name . "<br/>\n"; //D2022
      // To avoid programming mistake on old version try to get 'files' with 'iassign_statement.id', 'iassign_statement.file' and 'iassign_statement.filesid'
      $files = $DB->get_records('files', array('contextid' => $contextid, 'component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $iassign_statement->id)); // all 'files' associated
      $numf = count($files); $countF += $numf; $msgO = "iassign_statement.id"; //D
      if ($numf == 0) {
        if (isset($iassign_statement->file))
          $files = $DB->get_records('files', array('contextid' => $contextid, 'component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $iassign_statement->file)); // all 'files' associated
        $numf = count($files); $countF += $numf; $msgO = "iassign_statement.file"; //D
        if ($numf == 0) {
          if (isset($iassign_statement->filesid)) // try 'iassign_statement.filesid = files.id'
            $files = $DB->get_records('files', array('id' => $iassign_statement->filesid)); // all 'files' associated
          $numf = count($files); $countF += $numf; $msgO = "iassign_statement.filesid"; //D
          if ($numf == 0) {
            if (isset($iassign_statement->filesid)) // try 'iassign_statement.filesid = files.itemid'
              $files = $DB->get_records('files', array('contextid' => $contextid, 'component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $iassign_statement->filesid));
            $numf = count($files); $countF += $numf; $msgO = "iassign_statement.filesid=files.itemid"; //D
            if ($numf == 0)
              echo  " &nbsp; * files: " . $numf . " - ERRO 'id' 'file' 'filesid'<br/>\n"; //D
            }
          }
        }
      if ($numf > 0) { echo  " &nbsp; * files: " . $numf . ": " . $msgO . "<br/>\n"; //D
        $filename = array();
        foreach ($files as $element_file) { // analyse each '$element_file' of 'files'
echo  " &nbsp; * files: id = " . $element_file->id . ", contextid = " . $element_file->contextid . ", itemid=" . $element_file->itemid . " : " . $element_file->filename . "<br/>\n"; //D
          if ($element_file->filename != '.') { // get the '$element_file' that is not only "path"
            $filename = explode(".", $element_file->filename);
            if ($iassign_statement->filesid != $element_file->id) { // need to update 'iassign_statement.filesid'?
              echo " &nbsp; &nbsp; &nbsp; &nbsp; Update 'iassign_statement.filesid'=" . $iassign_statement->filesid . " <- " . $element_file->id . " : " . $element_file->filename . "<br/>"; //2022
              $iassign_statement->filesid = $element_file->id; // 'iassign_statement.filesid' <- 'files.id' (update bellow) //2022
              }
            }
          if ($element_file->itemid != $iassign_statement->id) { // need to update 'files.itemid'? ('files.itemid <- iassign_statement.id') //2022
//++ Deixar para outro corrigir! A partir de 'settings_activities.php'
            $newentry_be_updated = new stdClass(); $newentry_be_updated->id = $element_file->id; $newentry_be_updated->itemid = $iassign_statement->id;
            echo " &nbsp; &nbsp; &nbsp; &nbsp; Update 'files.itemid': id=" . $newentry_be_updated->id . ", itemid=" . $element_file->itemid . "<-" . $newentry_be_updated->itemid . " : " . $element_file->filename . "<br/>"; //2022
//R              if (!$DB->update_record('files', $newentry_be_updated)) print_error('error_update', 'iassign');
            }
          } // foreach ($files as $element_file)
        $extension = ""; // extension of the current '$element_file'
        if (count($filename) > 1) $extension = strtolower($filename[count($filename) - 1]);
        foreach ($list_all_iassign_ilm as $iassign_ilm) { // find iLM with the same extension (to register its 'iassign_ilm.id')
          $extensions_of_ilm = explode(",", $iassign_ilm->extension);
          if (in_array($extension, $extensions_of_ilm)) { // it was found the iLM with correct extension - get the lastast version
            $iassign_statement->iassign_ilmid = $iassign_ilm->id; // the iLM identification: iassign_ilm.id
            echo " &nbsp; &nbsp; &nbsp; &nbsp; Update 'iassign_statement.iassign_ilmid': " . $iassign_statement->iassign_ilmid . " <- " . $iassign_ilm->id . "<br/>\n"; 
            }
          }
//R          $DB->update_record("iassign_statement", $iassign_statement); // update 'iassign_statement' (mainly 'iassign_statement.filesid')
        } // if ($numf > 0)
      } // foreach ($list_all_iassign_statement as $iassign_statement)
    } // for ($ii=0; $ii<$sizeofList; $ii++)
$timeWrite = date('Y_m_d_H_i_s'); // Year_Month_Day_Hour_Minutes_Seconds
$filenamewrite = "_output_restore_after_execute_" . $timeWrite . ".txt";

echo "restore_iassign_stepslib: " . $filenamewrite . "<br/>\nUsando: " . __DIR__ . "/../../ilm_debug/escreva.php" . "<br/>\n"; //D . str_replace("\n","<br>\n", $msgD) . "<br/>\n"; // __DIR__

// ./mod/iassign/ilm_debug/escreva.php: writeContent($filetype1, $pathbase, $outputFile, $msgToRegister)
// ./mod/iassign/ilm_debug/saida_restore_after_execute.txt
//D require_once(__DIR__ . "/../../ilm_debug/escreva.php"); //leo REMOVER! xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

//D Grvar em: ./mod/iassign/ilm_debug/output_restore_after_execute_2022_02_dd_hh_mm_ss.txt
//D $resp = writeContent("", "", $filenamewrite, $msgD); // ($filetype1, $pathbase, $outputFile, $msgToRegister) //leo tire comentario para gravar

echo "restore_iassign_stepslib: resp=" . $resp . ", #files=" . $countF . "<br/>\n";

  } // function see_all_files_context($course_id, $DB)
