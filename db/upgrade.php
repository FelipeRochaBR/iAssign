<?php

/**
 * This file keeps track of upgrades to the lams module.
 * 
 * Sometimes, changes between versions involve alterations to database structures and other
 * major things that may break installations.
 * The upgrade function in this file will attempt to perform all the necessary actions to upgrade
 * your older installation to the current version.
 * If there's something it cannot do itself, it must tell the Admin what must be done.
 * The commands in here will all be database-neutral, using the functions defined in lib/ddllib.php
 *
 * The iAssign upgrade function "xmldb_iassign_upgrade($oldversion)" is called by './lib/upgradelib.php' (after './admin/index.php')
 * The version of each Moodle "plugin" is keeped on {config_plugins} : id , plugin , name , value
 *
 * Additional explanation about Moodle management of "plugins":
 * 1. {modules} is the Moodle table with all installed "plugin" (as "iassign")
 * 2. {config_plugins} is the table with the "plugin" version, e.g.
 *    (id, plugin , name , value) = (1139 , "mod_iassign" , "version" , "2023041000")
 *
 * - v 1.6.0 2023/04/03
 *     + Replaced old code treating '$oldversion < 2020120500': before to {iassign_statement} AS ias, ias.id!=ias.file remove {files} and create new with {files}.itemid=ias.id
 *     + New version is faster, since perform a single query to all {files}, "foreach" {iassign_statement} find correspondent {files} using new "find_2_files(.)"
 * - v 1.5.5 2022/01/10
 *     + Added new version of iVProg 2022_01_13_21_43
 * - v 1.5.4 2021/12/23
 *     + Added new version of iVProg 2021_11_30_22_06
 * - v 1.5.2 2020/08/03
 *     + Fixed 'ALTER TABLE' of 'iassign_submission.grade' from BIGINT(11) to REAL 
 *     + New version of iHanoi 1.0.20200803
 * - v 1.5.1 2020/05/28-30
 *     + Avoid to update one iLM causing colision with other instance of the same iLM
 * - v 1.4 2013/09/19
 *     + Insert general fields for iassign statement (grade, timeavaliable, timedue, preventlate, test, max_experiment).
 *     + Change index field 'name' in 'iassign_ilm' table to index field 'name,version'.
 * - v 1.2 2013/08/30
 * + Change 'filearea' for new concept for files.
 * + Change path file for ilm, consider version in pathname.
 *
 * @author Leo^nidas de Oliveira Branda~o
 * @author Patricia Alves Rodrigues
 * @author Igor Moreira Fe'lix
 * @version v 1.6.0 2023/04/03
 * @version v 1.5.1 2020/05/28-30
 * @version v 1.5 2019/03/13
 * @version v 1.4 2013/09/19
 * @package mod_iassign_db
 * @since 2010/12/21
 * @copyright iMath (http://www.matematica.br) and LInE (http://line.ime.usp.br) - Computer Science Dep. of IME-USP (Brazil)
 * 
 * <b>License</b> 
 *  - http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *  
 * @param $oldversion Number of the "old version" of iAssing (registered in {config_plugins}. 
 */

require_once $CFG->dirroot . '/mod/iassign/locallib.php';

//DEBUG Since results in 'upgrade' is not presented (only the final situation), you
//DEBUG can use the auxiliary function 'writeContent(.)' to register some information
//DEBUG on the './mod/iassign/ilm_debug/upgrade_aaaa_mm_dd_hh_ss_mm.txt'.
//DEBUG @param: $filetype1 to special indentification (e.g. "student", "log", "sendemail")
//DEBUG @return 1 in case of success; -1 in case do not overwrite
function writeContent ($filetype1, $pathbase, $outputFile, $msgToRegister) {
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
     } // if (is_file($outputFile))
   if (1==1) { // write/overwrite the file
     $fpointer = fopen($outputFile, "w"); // write - if executed, it clear the previou content at this file
     if (!$fpointer) {
        // $file_debug .= "Erro: nao foi possivel abrir o arquivo (" . $outputFile . ")!<br/>\n";
        // it was not possible to open the file '$completfilepath" . $file_name . "'!<br/>\n";
        // print "<br/>" . $file_debug . "<br/>\n";
        return 0;
        }
     fwrite($fpointer, $msgToRegister . "\n");
     fclose($fpointer);
     }
   else { } // error
  return 1;
  } // function writeContent($outputFile, $msgToRegister)


// @calledby ./admin/index.php : provides this call with $oldversion = {config_plugins}.value, it uses $CFG->libdir.'/upgradelib.php'
// @calledby ./lib/upgradelib.php :
//   + function upgrade_mod_savepoint($result, $version, $modname, $allowabort=true): 298/2712
//     Module upgrade savepoint, marks end of module upgrade blocks. It stores module version, resets upgrade timeout and abort upgrade if user cancels page loading.
//   + function upgrade_plugins($type, $startcallback, $endcallback, $verbose): 513/2712,' "$newupgrade_function='xmldb_'.$plugin->fullname.'_upgrade';"
//   + function upgrade_plugins_modules($startcallback, $endcallback, $verbose): 674/2712, Find and check all modules and load them up or upgrade them if necessary
function xmldb_iassign_upgrade ($oldversion) {

  global $CFG, $DB, $USER;

  //DEBUG To be used to register in file ./mod/iassign/ilm_debug/upgrade_AAAA_MM_DD_hh_mm_ss.txt
  $msgD  = "./mod/iassign/db/upgrade.php: oldversion=" . $oldversion . " upgrade in ./mod/iassign/ilm_debug/upgrade_AAAA_MM_DD_hh_mm_ss.txt\n"; //DEBUG
  $msgD .= " - Initial time: " . date('Y_m_d_H_i_s') . "\n"; //DEBUG

  $dbman = $DB->get_manager();

  // Sequence of updates:
  // - 2020070613: structure
  // - 2020080300
  // - 2020120500
  // - 2020122900
  // - 2021020700
  // - 2021122300: new version iVProg "1.0.20211130"
  // - 2022011300: new version iVProg "1.0.20220113"
  // - 2023041000: new version iVProg "1.0.20230707", iHanoi "1.0.20230526"
  if ($oldversion < 2023041000) { // < 2020070613
    $msgD .= "+ " . $oldversion . " < 2020070613: " . "\n" ; //DEBUG to ./mod/iassign/ilm_debug/upgrade_AAAA_MM_DD_hh_mm_ss.txt
    $msgD .= "  * {iassign_allsubmissions}.previous_grade; insere iLM (iGeom 5.9.22; iHanoi 1.0.20230526;  iVProg 1.0.20230504; iFractions 3.1.0)\n"; //DEBUG

    $table = new xmldb_table('iassign_submission');
    $field = new xmldb_field('previous_grade', XMLDB_TYPE_FLOAT, null, null, null, null, null);
    if (!$dbman->field_exists($table, $field)) {
      $dbman->add_field($table, $field);
      }

    $records = array( // iLM adjusted to iAssign 2020/08/03
      array_combine( // iGeom 5.9.22
        array('name', 'url', 'version', 'type', 'description', 'extension', 'file_jar', 'file_class', 'width', 'height', 'enable', 'timemodified', 'author', 'timecreated', 'evaluate', 'reevaluate'),
        array('iGeom', 'http://www.matematica.br/igeom', '5.9.22', 'Java', '{"en":"Interactive Geometry on the Internet","pt_br":"Geometria Interativa na Internet"}', 'geo',
              'ilm/iGeom/5.9.22/iGeom.jar', 'IGeomApplet.class', 800, 600, 1, time(), $USER->id, time(), 1, 0)),
      array_combine( // iHanoi 1.0.20230526
        array('name', 'url', 'version', 'type', 'description', 'extension', 'file_jar', 'file_class', 'width', 'height', 'enable', 'timemodified', 'author', 'timecreated', 'evaluate', 'reevaluate'),
        array('iHanoi', 'http://www.matematica.br/ihanoi', '1.0.20230526', 'HTML5', '{"en":"interactive Tower os Hanoi (by LInE)", "pt_br":"Torres de Hanói (do LInE)"}', 'ihn',
              'ilm/iHanoi/1.0.20230526/ihanoi/', 'index.html', 1100, 500, 1, time(), $USER->id, time(), 1, 0)),
      array_combine( // iVProg 1.0.20230504/ - HTML5 - 2020
        array('name', 'url', 'version', 'type', 'description', 'extension', 'file_jar', 'file_class', 'width', 'height', 'enable', 'timemodified', 'author', 'timecreated', 'evaluate', 'reevaluate'),
        array('iVProg', 'http://www.usp.br/line/ivprog/', '1.0.20230504', 'HTML5', '{"en":"Visual Interactive Programming on the Internet (HTML)","pt_br":"Programação visual interativa na Internet"}',
              'ivph', 'ilm/iVProg/1.0.20230504/ivprog/', 'index.html', 900, 700, 1, time(), $USER->id, time(), 1, 1)),
      array_combine( // iFractions 3.1.0 - HTML5
        array('name', 'url', 'version', 'type', 'description', 'extension', 'file_jar', 'file_class', 'width', 'height', 'enable', 'timemodified', 'author', 'timecreated', 'evaluate', 'reevaluate'), 
        array('iFractions', 'http://www.matematica.br/ifractions', '3.1.0', 'HTML5', '{"en":"Interactive Fractions game","pt_br":"Jogo interativa de frações"}', 'frc',
              'ilm/iFractions/3.1.0/ifractions/', 'index.html', 1000, 600, 1, time(), $USER->id, time(), 1, 0))
        );

    $iassign_ilm = $DB->get_records('iassign_ilm');

    $strNot_installed = '';
    foreach ($records as $record) { // this version 'iassign_ilm' does not has field 'reevaluate'
      $newentry = new stdClass();
      $newentry->name = $record['name'];
      $newentry->version = $record['version'];
      $newentry->type = $record['type'];
      $newentry->url = $record['url'];
      $newentry->description = $record['description'];
      $newentry->extension = $record['extension'];
      $newentry->file_jar = $record['file_jar'];
      $newentry->file_class = $record['file_class'];
      $newentry->width = $record['width'];
      $newentry->height = $record['height'];
      $newentry->enable = $record['enable'];
      $newentry->timemodified = time();
      $newentry->author = $USER->id;
      $newentry->timecreated = time();
      $newentry->evaluate = $record['evaluate'];

      $exists = 0;
      if ($iassign_ilm) { // Search if there is any iLM of this with this version
        $record_type = strtolower($record['type']);
        foreach ($iassign_ilm as $iassign) {
          if ($iassign->name == $record['name'] && strtolower($iassign->type) == $record_type) {
            if ($iassign->version == $record['version']) { // or with the one with the same version
              $exists = 1; // iLM found - exit with the last one, same version
              $strNot_installed .= "\n" . '  <li>' . $record['name'] . ';' . $record['type'] . ';' . $record['version'] . ' </li>' . "\n";
              break;
              }
            }
          }
        }
      if ($exists == 0) try { // iLM does not exists or it has old version
        $DB->insert_record("iassign_ilm", $newentry, false);
        $msgD .= "  * insert_record('iassign_ilm', newentry...) : " . $record['name'] . ", version=" . $record['version'] . "\n"; //DEBUG
      } catch (Exception $e) {
        print 'Caught exception: ' . $e->getMessage() . "<br/>\n";
        $msgD .= "  * Caught exception: " . $e->getMessage() . "\n"; //DEBUG
        }
      } // foreach ($records as $record)

    if ($strNot_installed != '') { // ATTENTION: this implies that Moodle administrator updated this iLM, please verify if it is really the current one.
      // 'There already exists this iLM (with same type and version), then the current version on iAssign was not installed:'
      print '<div class="alert alert-warning alert-block fade in" role="alert" data-aria-autofocus="true" tabindex="0" >' . "\n";
      print get_string('upgrade_alert_exists', 'iassign'); // iLM previouly existent
      print ' <ul style="margin-top: 1rem;">' . "\n";
      print $strNot_installed;
      print ' </ul>' . "\n";
      print '</div>' . "\n";
      }

    // Verify if each iLM previously installed is present in fresh new iAssign. This does not means problem, perhaps the admin installed a particular iLM.
    $strNot_found = '';
    if ($iassign_ilm) { // exists iLM ($DB->get_records('iassign_ilm'))
      foreach ($iassign_ilm as $iassign) {
        $found = false;
        foreach ($records as $record) {
          if ($iassign->name == $record['name']) {
            $found = true;
            break;
            }
          }
        if (!$found) {
          $strNot_found .= '<li>' . $iassign->name . ' - <a href="' . $iassign->url . '" target="_blank">' . $iassign->url . '</a></li>' . "\n";
          $updateentry = new stdClass();
          $updateentry->id = $iassign->id;
          $updateentry->enable = 0;
          $updateentry->timemodified = time();
          $DB->update_record("iassign_ilm", $updateentry); // insert new iLM
          $msgD .= "  * update_record('iassign_ilm', updateentry): " . $iassign->id . ", " . $iassign->name . ", enable:=0\n"; //DEBUG
          }
        } // foreach ($iassign_ilm as $iassign)
      } // if ($iassign_ilm)

    if ($strNot_found != '') {
      // The iLM update was completed successfully. However, it was detected that some of your iLM previously installed are not (any more) available on the current distribution of iAssign:
      print '<div class="alert alert-warning alert-block fade in" role="alert" data-aria-autofocus="true" tabindex="0" >' . "\n";
      print get_string('upgrade_alert_iMA_msg', 'iassign'); // Updated but some previous iLM installed are not available in fresh iAssign
      print '<ul style="margin-top: 1rem;">' . "\n";
      print $strNot_found;
      print '</ul>' . "\n";
      print get_string('upgrade_alert_iMA_solution_pt1', 'iassign');
      print '<a href="'.new moodle_url('/admin/settings.php?section=modsettingiassign').'">' . get_string('upgrade_alert_iMA_solution_pt2', 'iassign') . '</a>.' . "\n";
      print '</div>' . "\n";
      }
    } // if ($oldversion < 2023041000) { // < 2020070613

  if ($oldversion < 2020070613) {
    $msgD .= "+ " . $oldversion . " < 2020070613: " . "\n"; //DEBUG to ./mod/iassign/ilm_debug/upgrade_AAAA_MM_DD_hh_mm_ss.txt
    $msgD .= "  * {iassign_ilm}.reevaluate\n"; //DEBUG

    $table = new xmldb_table('iassign_ilm');
    $field = new xmldb_field('reevaluate');
    $field->set_attributes(XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', null, null, null);
    if (!$dbman->field_exists($table, $field)) { // if 'iassign_ilm.reevaluate' does not exist, then create this field
      $dbman->add_field($table, $field);
      }

    $msgD .= "  * {iassign_ilm}.id, {iassign_ilm}.version, {iassign_ilm}.name\n"; //DEBUG
    $iassign_ilm = $DB->get_records('iassign_ilm');
    foreach ($iassign_ilm as $iassign) { // If already installed iVProg/JS or iHanoi/JS set it with re-evaluate feature
      $msgD .= "  * " . $iassign->id . ", " . $iassign->version . ", " . $iassign->name . "\n"; //DEBUG
      if ($iassign->type == 'HTML5' && ($iassign->name == 'iVProg' || $iassign->name == 'iHanoi')) {
        $updateentry = new stdClass();
        $updateentry->id = $iassign->id;
        $updateentry->reevaluate = 1;
        $updateentry->timemodified = time();
        $answerbd = $DB->update_record("iassign_ilm", $updateentry);
        $msgD .= " - - update {iassign_ilm}.reevaluate := 1 -> answerbd=" . $answerbd . "\n"; //DEBUG
        break;
        }
      }
    } // if ($oldversion < 2020070613)

  if ($oldversion < 2020080300) { // new iHanoi
    $msgD .= "+ " . $oldversion . " < 2023052600: " . "\n"; //DEBUG to ./mod/iassign/ilm_debug/upgrade_AAAA_MM_DD_hh_mm_ss.txt
    $strNot_installed = '';

    // iassign_submission . grade : from 'BIGINT(11)' to 'real'
    if ($dbman->field_exists('iassign', 'grade')) {
      $sql = 'ALTER TABLE {iassign} CHANGE grade grade FLOAT NOT NULL DEFAULT 0.0';
      $msgD .= "  * before execute " . $sql . "\n"; //DEBUG
      $DB->execute($sql);
      $msgD .= "  * after OK \n"; //DEBUG
      }
    if ($dbman->field_exists('iassign_statement', 'grade')) {
      $sql = 'ALTER TABLE {iassign_statement} CHANGE grade grade FLOAT NOT NULL DEFAULT 0.0';
      $msgD .= "  * before execute " . $sql . "\n"; //DEBUG
      $DB->execute($sql);
      $msgD .= "  * after OK \n"; //DEBUG
      }
    if ($dbman->field_exists('iassign_submission', 'grade')) {
      $sql = 'ALTER TABLE {iassign_submission} CHANGE grade grade FLOAT NOT NULL DEFAULT 0.0';
      $msgD .= "  * before execute " . $sql . "\n"; //DEBUG
      $DB->execute($sql);
      $msgD .= "  * after OK \n"; //DEBUG
      }
    if ($dbman->field_exists('iassign_submission', 'previous_grade')) {
      // $sql = 'ALTER TABLE {iassign_submission} CHANGE previous_grade previous_grade REAL NOT NULL DEFAULT 0.0'; // Invalid use of NULL value
      $sql = 'ALTER TABLE {iassign_submission} CHANGE previous_grade previous_grade FLOAT DEFAULT 0.0';
      $msgD .= "  * before execute " . $sql . "\n"; //DEBUG
      $DB->execute($sql);
      $msgD .= "  * after OK \n"; //DEBUG
      }

    // Update iHanoi - but the "if ($oldversion < 2020070613)" must had inserted it ("iHanoi 1.0.20230526")
    $records = array(
      array_combine( // iHanoi 1.0.20230526
        array('name', 'url', 'version', 'type', 'description',
              'extension', 'file_jar', 'file_class', 'width', 'height',
              'enable', 'timemodified', 'evaluate', 'reevaluate', 'author', 'timecreated'),
        array('iHanoi', 'http://www.matematica.br/ihanoi', '1.0.20230526', 'HTML5', '{"en":"interactive Tower os Hanoi (by LInE)", "pt_br":"Torres de Hanói (do LInE)"}',
              'ihn', 'ilm/iHanoi/1.0.20230526/ihanoi/', 'index.html', 1100, 500,
              1, time(), 1, 1, $USER->id, time()))
        );
    $iassign_ilm = $DB->get_records('iassign_ilm');
    foreach ($records as $record) { // For each iLM in the current version of iAssign
      $newentry = new stdClass();
      $newentry->name = $record['name'];
      $newentry->url = $record['url'];
      $newentry->version = $record['version'];
      $newentry->type = $record['type'];
      $newentry->description = $record['description'];
      $newentry->extension = $record['extension'];
      $newentry->file_jar = $record['file_jar'];
      $newentry->file_class = $record['file_class'];
      $newentry->width = $record['width'];
      $newentry->height = $record['height'];
      $newentry->enable = $record['enable'];
      $newentry->timemodified = time();
      $newentry->evaluate = $record['evaluate'];
      $newentry->reevaluate = $record['reevaluate'];
      $newentry->author = $USER->id;
      $newentry->timecreated = time();

      $exists = 0;
      $last_id = -1;
      if ($iassign_ilm) { // Search if there is any previous installed iLM that is also in the current version of iAssign
        $record_type = strtolower($record['type']);
        foreach ($iassign_ilm as $iassign) {
          if ($iassign->name == $record['name'] && strtolower($iassign->type) == $record_type) {
            if ($iassign->id > $last_id)
              $last_id = $iassign->id; // last ID => last version (hopefully)
            if ($iassign->version == $record['version']) { // or with the one with the same version
              $exists = 1; // iLM found - exit with the last one, same version
              $strNot_installed .= "\n" . '  <li>' . $record['name'] . ';' . $record['type'] . ';' . $record['version'] . ' </li>' . "\n";
              break;
              }
            }
          }
        }
      if ($exists == 0) { // iLM does not exists or it has old version
        $newentry->parent = $record['parent'];
        try {
          $DB->insert_record("iassign_ilm", $newentry, false);
          $msgD .= "  * insert_record iassign_ilm iHanoi 1.0.20230526 \n"; //DEBUG
        } catch (Exception $er) {
          $msgD .= "  * Error insert_record iassign_ilm iHanoi 1.0.20230526 " . $er->getMessage() . "\n"; //DEBUG
          }
        }
      } // foreach ($records as $record)

    if ($strNot_installed != '') {
      print '<div class="alert alert-warning alert-block fade in " role="alert" data-aria-autofocus="true" tabindex="0" >' . "\n";
      print get_string('upgrade_alert_exists', 'iassign'); // iLM previouly existent
      print ' <ul style="margin-top: 1rem;">' . "\n";
      print $strNot_installed;
      print ' </ul>' . "\n";
      print '</div>' . "\n";
      }

    // iassign_ilm: atualizou iHanoi existente, mas nao era isso! Deveria ter inserido novo!
    //   id name   version type  ...  parent file_jar              file_class  width height  ...  evaluate reevaluate
    //   53 iHanoi 2       HTML5 ...  0      ilm/iHanoi/2/ihanoi/  index.html  1100  700     ...  1        0
    $iassign_ilm = $DB->get_records('iassign_ilm');
    foreach ($iassign_ilm as $iassign) { // for iLM iHanoi update the new field 'reevaluate' as 1
      if ($iassign->name == 'iHanoi' && $iassign->type == 'HTML5' && $iassign->reevaluate!=1) {
        $updateentry = new stdClass();
        $updateentry->id = $iassign->id;
        $updateentry->reevaluate = 1;
        $updateentry->timemodified = time();
        $DB->update_record("iassign_ilm", $updateentry);
        $msgD .= "  * update_record('iassign_ilm'...) iHanoi reevaluate:=1\n"; //DEBUG
        break;
        }
      }

    // iAssign savepoint reached.
    upgrade_mod_savepoint(true, 2020080300, 'iassign');
    } // if ($oldversion < 2020080300)

  // Considering ias as {iassign_statement}, create new ias.filesid (initially with ias.file)
  // Fix {files}.itemid := {iassign_statement}.id and {iassign_statement}.filesid := {files}.id 
  // New update from 2023/04/06 (old one was first released in 2020/11/24 with a smaller differences)
  if ($oldversion < 2020120500) {
    $msgD .= "+ " . $oldversion . " < 2020120500: " . "\n"; //DEBUG to ./mod/iassign/ilm_debug/upgrade_AAAA_MM_DD_hh_mm_ss.txt

    // Adding field iassing_statement.filesid
    $table = new xmldb_table('iassign_statement');
    $field_filesid = new xmldb_field('filesid', XMLDB_TYPE_CHAR, '255', null, null, null, null, null);
    if (!$dbman->field_exists($table, $field_filesid)) {
      $dbman->add_field($table, $field_filesid);
      // Updating all registers from {iassing_statement}.filesid
      // Current version (2020 on) is different: {files}.itemid={iassign_statement}.id and {iassign_statement}.filesid={file}.id
      $msgD .= "  * execute('UPDATE {iassign_statement} SET filesid = file'\n"; //DEBUG
      $answerbd = $DB->execute("UPDATE {iassign_statement} SET filesid = file");
      if (!$answerbd)
        $msgD .= "  * ERROR execute('UPDATE {iassign_statement} SET filesid = file'\n"; //DEBUG
      }

    // Update file_jar field: allow 2 iLM with the same path...
    try {
      $sql = "ALTER TABLE {iassign_ilm} DROP INDEX {iaima_fil_uix}";
      $answerbd = $DB->execute($sql); // remove "UNIQUE KEY *_iaima_fil_uix (file_jar),"
      $msgD .= "  * execute " . $sql . "\n"; //DEBUG  
    } catch (Exception $er) {
      $msgD .= "  * ERROR execute " . $sql . ": " . $er->getMessage() . "\n"; //DEBUG
      }

    // In this point {{iassign_statement} already has field 'filesid': see above "UPDATE {iassign_statement} SET filesid = file"
    // Prepare to find all {files} associated to a single iAssign {iassign} using {modules}, {course_modules}, {context}, {files}, {iassign}
    // ATTENTION: it is very important to use {files}.id as the first column of this SELECT statement (since it must be a unique value)
    $sql_list = "{files}.id AS files_id, {course_modules}.course, {course_modules}.id, {context}.instanceid, {context}.id AS contextid, " .
      "{course_modules}.instance, {iassign}.id AS iaid, {files}.itemid, " . 
      "{iassign_statement}.file, {iassign_statement}.filesid, {files}.author, {files}.timecreated, " .
      "{files}.filename, {iassign}.name AS ianame, {iassign_statement}.file AS iasfile, {iassign_statement}.id AS iasid, " .
      "{iassign_statement}.name AS iasname, {iassign_statement}.timecreated";
    $sql_from = "{modules}, {course_modules}, {context}, {files}, {iassign}, {iassign_statement}";
    $sql_where = "{modules}.name='iassign' AND {modules}.id={course_modules}.module AND {course_modules}.id = {context}.instanceid AND " .
      "{course_modules}.instance = {iassign}.id AND {context}.id={files}.contextid AND {files}.component='mod_iassign' AND " . 
      "{iassign}.id={iassign_statement}.iassignid AND ({files}.itemid={iassign_statement}.file OR {files}.itemid={iassign_statement}.id)";
    
    // "SELECT " . $dist . " " . $campos . " FROM " . $table . " WHERE " . $where . $compl
    //./lib/dml/moodle_database.php : public abstract function get_records_sql($sql, array $params=null, $limitfrom=0, $limitnum=0)
    // ATTENTION! The first column of this SELECT statement must be a unique value (usually the 'id' field), for this reason it necessary
    // $sql_list starts with {files}.id (that is the unique value from all returned)
    $iassign_files_contexts_list = $DB->get_records_sql("SELECT " . $sql_list . " FROM " . $sql_from . " WHERE " . $sql_where); // versao para 'upgrade.php'

    $msgD .= "  * SELECT " . $sql_list . " FROM " . $sql_from . "\n    WHERE " . $sql_where . "\n"; //DEBUG
    $msgD .= "  * #iassign_files_contexts_list = " . count($iassign_files_contexts_list) . "\n"; //DEBUG

    $ii = 0;
    foreach ($iassign_files_contexts_list as $iassign_statement_activity_item) { // all {iassign_statement}/{files}
      if ($iassign_statement_activity_item->filename=='.') { // if second {files} - directory/path
        $one_files = $iassign_statement_activity_item; // just to produce a more readable code...
        if ($one_files->itemid != $iassign_statement_activity_item->iasid) {
          // {files}.itemid != {iassign_statement}.id  =>  UPDATE {files} with itemid := {iassign_statement}.id
          $newentry = new stdClass();
          $newentry->id = $one_files->files_id; // set {files}.id
          $newentry->itemid = $iassign_statement_activity_item->iasid; // update {files}.itemid := {iassign_statement}.id
          $DB->update_record("files", $newentry); // update {files}
          $msgD .= "  * update " . $one_files->itemid . "={files}.itemid := {iassign_statement}.id = " . $iassign_statement_activity_item->iasid . " - {files}.id=" .  $one_files->files_id . "\n"; //DEBUG
          }
        }
      else {
        $one_files = $iassign_statement_activity_item; // just to produce a more readable code...
        if ($iassign_statement_activity_item->filesid) $msgiasfilesid = $iassign_statement_activity_item->filesid; else $msgiasfilesid = "&lt;&gt;"; //DEBUG
        if (!$iassign_statement_activity_item->filesid || $iassign_statement_activity_item->filesid != $one_files->files_id) { // {iassign_statement}.filesid not defined
          // {iassign_statement}.filesid != {files}.id  =>  UPDATE {iassign_statement}.filesid := {files}.id
          $newentry = new stdClass();
          $newentry->id = $iassign_statement_activity_item->iasid; // set {iassign_statement}.id
          $newentry->filesid = $one_files->files_id; // update {iassign_statement}.filesid := {files}.id
          $DB->update_record("iassign_statement", $newentry); // update {iassign_statement}
          $msgD .= "  * update " . $msgiasfilesid . "={iassign_statement}.filesid := {files}.id=" . $one_files->files_id . " - ias.id=" . $iassign_statement_activity_item->iasid . "\n"; //DEBUG
          }
        if ($one_files->itemid != $iassign_statement_activity_item->iasid) {
          // {files}.itemid != {iassign_statement}.id  =>  UPDATE {files} with itemid := {iassign_statement}.id
          $newentry = new stdClass();
          $newentry->id = $one_files->files_id; // set {files}.id
          $newentry->itemid = $iassign_statement_activity_item->iasid; // update {files}.itemid := {iassign_statement}.id
          $DB->update_record("files", $newentry); // update {files}
          $msgD .= "  * update " . $one_files->itemid . "={files}.itemid := {iassign_statement}.id=" . $iassign_statement_activity_item->iasid . " - {files}.id=" .  $one_files->files_id . "\n"; //DEBUG
          }
        }
      $ii++; // count...
      } // foreach ($ias_files as $iassign_statement_activity_item)

    } // if ($oldversion < 2020120500)

  /// @Igor - create the new table {iassign_allsubmissions}
  if ($oldversion < 2020122900) { // < 2020122900
    $msgD .= "+ " . $oldversion . " < 2020122900: " . "\n"; //DEBUG to ./mod/iassign/ilm_debug/upgrade_AAAA_MM_DD_hh_mm_ss.txt

    // Define table iassign_allsubmissions to be created.
    $table = new xmldb_table('iassign_allsubmissions');
    // Adding fields to table iassign_allsubmissions.
    $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    $table->add_field('iassign_statementid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '16', null, XMLDB_NOTNULL, null, null);
    $table->add_field('grade', XMLDB_TYPE_FLOAT, null, null, null, null, null, null);
    $table->add_field('answer', XMLDB_TYPE_TEXT, 'long', null, null, null, null, null);
    // Adding keys to table iassign_allsubmissions.
    $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
    // Conditionally launch create table for iassign_allsubmissions.
    if (!$dbman->table_exists($table)) {
      $dbman->create_table($table);
      $msgD .= "  * {iassign_allsubmissions}.{id, iassign_statementid, userid, timecreated, grade, answer}\n"; //DEBUG
      }

    // Adding fields to table iassign_ilm:
    $table = new xmldb_table('iassign_ilm');
    $field_editingbehavior = new xmldb_field('editingbehavior', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', null);
    if (!$dbman->field_exists($table, $field_editingbehavior)) $dbman->add_field($table, $field_editingbehavior);
    $field_submissionbehavior = new xmldb_field('submissionbehavior', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', null);
    if (!$dbman->field_exists($table, $field_submissionbehavior)) $dbman->add_field($table, $field_submissionbehavior);
    $field_action_buttons = new xmldb_field('action_buttons', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', null);
    if (!$dbman->field_exists($table, $field_action_buttons)) $dbman->add_field($table, $field_action_buttons);
    $msgD .= "  * {iassign_ilm}.{editingbehavior, submissionbehavior, action_buttons}\n"; //DEBUG

    // Adding field to table iassign_statement:
    $table = new xmldb_table('iassign_statement');
    $field_store_all_submissions = new xmldb_field('store_all_submissions', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', null);
    if (!$dbman->field_exists($table, $field_store_all_submissions)) $dbman->add_field($table, $field_store_all_submissions);
    $msgD .= "  * {iassign_statement}.store_all_submissions - change ias.editingbehavior,submissionbehavior de iHanoiJS, iGeomJava, iVProgJS, iFractions, Risko\n"; //DEBUG

    // Update new fields for previous installed iLM:
    $iassign_ilm = $DB->get_records('iassign_ilm');
    foreach ($iassign_ilm as $iassign) { 
      $updateentry = new stdClass();
      $updateentry->id = $iassign->id;
      $change = 0; // any change to update?
      if (($iassign->name == 'iHanoi' && $iassign->type == 'HTML5')) {
        $change = 1;
        $updateentry->editingbehavior = 0; // editingbehavior=1 => iLM auto-evaluation remains working over a solution sent by the student (iGeom not; iVProg yes)
        $updateentry->submissionbehavior = 0; // 0 => 'After submission, this iLM remains on the same page'; 1 => 'this iLM changes the current page
        }
      else
      if (($iassign->name == 'iGeom' && $iassign->type == 'Java')) {
        $updateentry->editingbehavior = 0; $change = 1;
        $updateentry->submissionbehavior = 0;
        }
      else
      if (($iassign->name == 'iVProg' && $iassign->type == 'HTML5')) {
        $updateentry->editingbehavior = 1; $change = 1;
        $updateentry->submissionbehavior = 0;
        }
      else
      if (($iassign->name == 'iFractions' && $iassign->type == 'HTML5')) {
        $updateentry->editingbehavior = 0; $change = 1;
        $updateentry->submissionbehavior = 1;
        }
      else
      if (($iassign->name == 'Risko' && $iassign->type == 'Java')) {
        $updateentry->editingbehavior = 1; $change = 1;
        $updateentry->submissionbehavior = 0;
        }
      if ($change==1) {
        $updateentry->timemodified = time();
        $DB->update_record("iassign_ilm", $updateentry);
        }
      }

    //x // Add iassign_allsubmissions table - not necessary, {iassign_allsubmissions}.{id, iassign_statementid, userid, timecreated, grade, answer} already created
    //x $table = new xmldb_table('iassign_allsubmissions');
    //x if (!$dbman->table_exists($table)) {
    //x   $field1 = new xmldb_field('id');                  $field1->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
    //x   $field2 = new xmldb_field('iassign_statementid'); $field2->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
    //x   $field3 = new xmldb_field('userid');              $field3->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);     
    //x   $field4 = new xmldb_field('timecreated');         $field4->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
    //x   $field5 = new xmldb_field('grade');               $field5->set_attributes(XMLDB_TYPE_FLOAT, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null);
    //x   $field6 = new xmldb_field('answer');              $field6->set_attributes(XMLDB_TYPE_TEXT, 'long', null, null, null, null, 'type');
    //x   $table->addIndex($field1); $table->addField($field2); $table->addField($field3);
    //x   $table->addField($field4); $table->addField($field5); $table->addField($field6);
    //x   $dbman->create_table($table);
    //x   } // if (!$dbman->table_exists($table))

    // Update file_jar field: allow 2 iLM with the same path...
    try {
      $DB->execute("ALTER TABLE {iassign_ilm} DROP INDEX {iassilm_fil_uix}");
      $msgD .= "  * ALTER TABLE {iassign_ilm} DROP INDEX {iassilm_fil_uix} \n"; //DEBUG
    } catch (Exception $er) {
      $msgD .= "  * ERROR ALTER TABLE {iassign_ilm} DROP INDEX {iassilm_fil_uix}: " . $er->getMessage() . "\n"; //DEBUG
      }

    // Create temporary area in MoodleData: $CFG->dataroot/temp/iassign_files //TODO: not using... ***
    $tempfilespath = $CFG->dataroot . DIRECTORY_SEPARATOR . 'temp';
    if (!file_exists($tempfilespath)) {
      mkdir($tempfilespath, 0777, true);
      $msgD .= "  * mkdir " . $tempfilespath . "\n"; //DEBUG
      }
    $iassignfilespath = $tempfilespath . DIRECTORY_SEPARATOR . 'iassign_files';
    if (!file_exists($iassignfilespath)) {
      mkdir($iassignfilespath, 0777, true);
      $msgD .= "  * mkdir " . $iassignfilespath . "\n"; //DEBUG
      }
    } // if ($oldversion < 2020122900)

  if ($oldversion < 2021020700) { 
    $msgD .= "+ " . $oldversion . " < 2021020700: " . "\n"; //DEBUG to ./mod/iassign/ilm_debug/upgrade_AAAA_MM_DD_hh_mm_ss.txt

    // Update SAW iVProg to inform reevalute is enabled:
    // SELECT id FROM s_iassign_ilm WHERE type='HTML5' AND name='iVProg'
    $ivprogs = $DB->get_records('iassign_ilm', array('name' => 'iVProg', 'type' => 'HTML5'));
    foreach ($ivprogs as $one_ivprog) {
      $ivprogid = $one_ivprog->id;
      $updateentry = new stdClass();
      $updateentry->id = $ivprogid; // {iassign_ilm}.id such that {iassign_ilm}.name='iVProg' and {iassign_ilm}.type='HTML5'
      $updateentry->reevaluate = 1;
      $updateentry->timemodified = time();
      $DB->update_record("iassign_ilm", $updateentry); // update {iassign_ilm}.id=57 with 'reevaluate = 1'
      $msgD .= "  * update_record 'iassign_ilm' id=" . $ivprogid . ", reevaluate:=1\n"; //DEBUG
      }
    } // if ($oldversion < 2021020700)

  if ($oldversion < 2021122300) { // 2021/12/23
    $msgD .= "+ " . $oldversion . " < 2021122300: " . "\n"; //DEBUG to ./mod/iassign/ilm_debug/upgrade_AAAA_MM_DD_hh_mm_ss.txt
    $strNot_installed = '';

    // Update iVProg JS to the latest version (ilm/iVProg/1.0.20230504/ivprog/)
    $records = array(
      array_combine( // iVProg 1.0.20230504/ - HTML5 - 2021/12/30
        array('name', 'url', 'version', 'type', 'description', 'extension', 'file_jar', 'file_class', 'width', 'height', 'enable', 'timemodified', 'author', 'timecreated', 'evaluate', 'reevaluate'),
        array('iVProg', 'http://www.usp.br/line/ivprog/', '1.0.20230504', 'HTML5', '{"en":"Visual Interactive Programming on the Internet (HTML)","pt_br":"Programação visual interativa na Internet"}',
              'ivph', 'ilm/iVProg/1.0.20230504/ivprog/', 'index.html', 900, 700, 1, time(), $USER->id, time(), 1, 1))
        );
    $iassign_ilm = $DB->get_records('iassign_ilm');
    foreach ($records as $record) { // For each iLM in the current version of iAssign
      $newentry = new stdClass();
      $newentry->name = $record['name'];
      $newentry->url = $record['url'];
      $newentry->version = $record['version'];
      $newentry->type = $record['type'];
      $newentry->description = $record['description'];
      $newentry->extension = $record['extension'];
      $newentry->file_jar = $record['file_jar'];
      $newentry->file_class = $record['file_class'];
      $newentry->width = $record['width'];
      $newentry->height = $record['height'];
      $newentry->enable = $record['enable'];
      $newentry->timemodified = time();
      $newentry->author = $USER->id;
      $newentry->timecreated = time();
      $newentry->evaluate = $record['evaluate']; // 1
      $newentry->reevaluate = $record['reevaluate']; // 1

      $exists = -1;
      $last_id = -1; // to be used to get the last version of the same iLM (will be its 'parent')
      if ($iassign_ilm) { // Search if there is any previous installed iLM that is also in the current version of iAssign
        $record_type = strtolower($record['type']);
        foreach ($iassign_ilm as $iassign) {
          if ($iassign->name == $record['name'] && strtolower($iassign->type) == $record_type) {
            if ($iassign->id > $last_id)
              $last_id = $iassign->id; // last ID => last version (hopefully)
            if ($iassign->version == $record['version']) { // or with the one with the same version
              $exists = $iassign->id; // iLM found - exit with the last one, same version
              $strNot_installed .= "\n" . '  <li>' . $record['name'] . ';' . $record['type'] . ';' . $record['version'] . ' </li>' . "\n";
              break;
              }
            }
          }
        $newentry->parent = $last_id; // parent is the last iLM found (corresponde to the smallest ID)
        }
      else
        $newentry->parent = -1; // first iLM of this one
      if ($exists == -1) { // iLM does not exists or it has old version
        // $newentry->parent = $record['parent'];  
        $answerbd = $DB->insert_record("iassign_ilm", $newentry, false);
        if (!$answerbd) $msgD .= "  * ERROR insert_record('iassign_ilm', newentry,...): " . $newentry->name . "\n"; //DEBUG
        else $msgD .= "  * insert_record('iassign_ilm', newentry,...): " . $newentry->name . "\n"; //DEBUG
        }
      else $msgD .= "  * 'iassign_ilm' " . $record['name'] . " already exists with id=" . $last_id . "!\n"; //DEBUG
      } // foreach ($records as $record)

    if ($strNot_installed != '') {
      print '<div class="alert alert-warning alert-block fade in " role="alert" data-aria-autofocus="true" tabindex="0" >' . "\n";
      print get_string('upgrade_alert_exists', 'iassign'); // iLM previouly existent
      print ' <ul style="margin-top: 1rem;">' . "\n";
      print $strNot_installed;
      print ' </ul>' . "\n";
      print '</div>' . "\n";
      }

    // iassign_ilm: atualizou iHanoi existente, mas nao era isso! Deveria ter inserido novo!
    // iassign_ilm: update iHanoi from
    //   id name   version type  ...  parent file_jar              file_class  width height  ...  evaluate reevaluate
    //   53 iHanoi 2       HTML5 ...  0      ilm/iHanoi/1.0.20200803/ihanoi/  index.html  1100  700     ...  1        0  ou
    //   53 iHanoi 2       HTML5 ...  0      ilm/iHanoi/2/ihanoi/  index.html  1100  700     ...  1        0
    $iassign_ilm = $DB->get_records('iassign_ilm');
    foreach ($iassign_ilm as $iassign) { // for iLM iHanoi update the new field 'reevaluate' as 1
      if ($iassign->name == 'iHanoi' && $iassign->type == 'HTML5' && $iassign->reevaluate!=1) {
        $updateentry = new stdClass();
        $updateentry->id = $iassign->id;
        $updateentry->reevaluate = 1; // new iHanoi allow teacher to re-evaluate (all of them at once)
        $updateentry->timemodified = time();
        $DB->update_record("iassign_ilm", $updateentry);
        $msgD .= "  * update_record 'iassign_ilm'iHanoi HTML id=" . $iassign->id . "\n"; //DEBUG
        break;
        }
      }

    // iAssign savepoint reached.
    upgrade_mod_savepoint(true, 2021122300, 'iassign');
    } // if ($oldversion < 2021122300)


 //DEBUG to ./mod/iassign/ilm_debug/upgrade_AAAA_MM_DD_hh_mm_ss.txt
 try { // register in ./mod/iassign/ilm_debug/
   $timeWrite = date('Y_m_d_H_i_s'); // Year_Month_Day_Hour_Minutes_Seconds
   $msgD .= " - Final time: " . $timeWrite . "\n"; //DEBUG
   $filenamewrite = "upgrade_" . $timeWrite . ".txt";
   $resp = writeContent("", "", $filenamewrite, $msgD); // ($filetype1, $pathbase, $outputFile, $msgToRegister)
   } catch (Exception $er) {
     // print 'Erro: upgrade  ' . $er->getMessage() . "\n";
     }


  // log event -----------------------------------------------------
  if (class_exists('plugin_manager'))
    $pluginman = plugin_manager::instance();
  else
    $pluginman = core_plugin_manager::instance();
  $plugins = $pluginman->get_plugins();
  iassign_log::add_log('upgrade', 'version: ' . $plugins['mod']['iassign']->versiondisk);
  // log event -----------------------------------------------------

  return true;

  } // function xmldb_iassign_upgrade($oldversion)
