<?php

/**
 * This file is used to allow a more secure access to the iLM file contents.
 * The iLM must get the parameter "iLM_PARAM_Assignment" to load the iLM content,
 * see: https://www.matematica.br/ihanoi/ima.html
 * After iAssign received the requisition to the iLM content, it is erased (avoiding
 * the learner coud direclty access it).
 * Under this security process, iLM can even hide in the file content the template answer,
 * as iGeom Java does.
 * 
 * ATTENTION: DO NOT USE any 'print' (or 'echo') other then the one that print the iLM file content.
 * Otherwise the iLM will receive this printed message as its file (probably nothing will be shown).
 * 
 * How:
 * The principle is to allow a single access to the file content, providing a "token" that is erased on the first use.
 * Every access to the content, by any iLM, must be provided by this vehicle.
 * 
 * Why:
 * The iLM must requires the file content by a GET connection. But if the file content is opened,
 * this means that the user (usually the learner) can get access to it by copying the URL directly.
 * In this case, if the iLM is based on "model answer" (like iGeom), the learner can open a local version of iLM
 * with this "model answer" (iGeom provides a special format to exercises to avoid this).
 * 
 * Table 'iassign_security': id iassign_statementid userid file timecreated view
 * 
 * TODO : the insertion in 'iassign_security' table must be provided by functions inside this code (not in './mod/iassign/locallib.php'
 * 
 * @author Leo^nidas de Oliveira Branda~o - Computer Science Dep. of IME-USP (Brazil)
 * @author Patricia Alves Rodrigues
 * @version v 1.1 2017/11/02: fixed error in '$stringDebugAuxFile = "";' (it was with ".=") 
 * @version v 1.0 2010/12/10
 * @package mod_iassign_ilm
 * @since 2012/03/10
 * iMath/iMatica : www.matematica.br
 * LInE : www.usp.br/line ; line.ime.usp.br
 * 
 * <b>License</b> 
 *  - http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");

global $DB;

//Debug: debug iLM security scheme
//Debug: ATTENTION, this requests the directory './mod/iassign/ilm_debug/' with write permition to www-data !!!!
$DEBUG = 0; //Debug: help to debug, register data in file 'MOODLE/mod/iassign/ilm_debug/YYYY_mm_dd_m_s_int'

class ilm_security {
  // Table 'iassign_security' : id iassign_statementid userid file timecreated view

  /// Since we are using {iassign_security}.file (to store the iLM text content) with as 'utf8mb4_unicode_ci'
  /// to avoid error "Error writing to database"!"Incorrect string value" lets ensure to use UTF-8
  //  Considering only 'ISO-8859-1'
  static function convert2utf8 ($strcontent) {
    // $current_encoding = mb_detect_encoding($strcontent, ['ASCII', 'UTF-8', 'ISO-8859-1']); // 'ASCII', 'UTF-8' and 'ISO-8859-1'
    $current_encoding = mb_detect_encoding($strcontent, 'ISO-8859-1'); // 'ASCII', 'UTF-8' and 'ISO-8859-1'
    $converted_text_to_utf8 = iconv($current_encoding, 'UTF-8', $strcontent); // converted to UTF-8
    //D  echo "ilm_security.php:<br/>current_encoding=" . $current_encoding . ", new_encoding=" . $new_encoding . "<br/>";
    //D  echo "strcontent[0,200]=" . substr($strcontent, 0, 200) . "<br/>";
    //D  echo "converted_text_to_utf8[0,200]=" . substr($converted_text_to_utf8, 0, 200) . "<br/>---<br/>";
    return $converted_text_to_utf8;
    }


  /// Warning message
  static function warning_message_iassign ($strcode) { // errado no 'locallib.php' sempre com constante 'error_view_without_actiontype'!!!
    return "<div classs='warning' style='display:inline; font-weight: bold; color:#a00'>" . get_string($strcode, 'iassign') . "</div>\n";
    }

  // @calledby here : after print $strFileContent;
  static function remove_records ($userid) { //, $iassign_statementid
    if (!isset($userid) || !isset($iassign_statementid)) {
      // self::warning_message_iassign('???');
      print self::warning_message_iassign('error_security_no_userid'); // 'Internal error: must be informed the user identification. Inform the Administrator.'
      return null;
      }
    if (!isset($iassign_statementid) || $iassign_statementid<1)
      $array_param = array("userid" => $userid); // erase all entries of this user
    else
      $array_param = array("userid" => $userid, "iassign_statementid" => $iassign_statementid);
    $DB->delete_records("iassign_security", $array_param); // erase only for this iAssign activity
    }

  // @calledby locallib.php : class ilm : function view_iLM($iassign_statement_activity_item, $student_answer, $enderecoPOST, $view)
  static function remove_old_iLM_security_entries ($userid) { // substituir 'locallib' de mesmo nome!
    global $DB;
    // This is an additional security: erase eventually old entries in 'iassign_security' table (do not remove '$iassign_statementid' since it is going to be used "now")
    $result = $DB->delete_records_select("iassign_security", "userid=" . $userid . " AND view>1", null);
    }


  /// Function to give a single access to an iLM content avoi (after used, 'view()', after 'view_iLM(...)', will erase the entry)
  //  @calledby locallib.php : class ilm_manager : function preview_ilm($courseid, $iassign_ilm): $id_iLM_security = ilm_security::write_iLM_security($USER->id, $timecreated, -1, $content_or_id_from_ilm_security);
  //  @param int $iassign_statement_activity_itemid Id of iassign statement, when from iLM 'preview' (there is none activity), -1
  //  @param Object $file File in use in activity
  //  @return int Return the id of log
  static function write_iLM_security ($userid, $timecreated, $iassign_statementid = -1, $content_or_id_from_ilm_security = "") {
    global $DB;
    $newentry = new stdClass();
    $newentry->iassign_statementid = $iassign_statementid; // when came from iLM previw => there is none activity, use -1
    $newentry->userid = $userid;
    //_ $newentry->file = $content_or_id_from_ilm_security;
    $newentry->file = ilm_security::convert2utf8($content_or_id_from_ilm_security); // must be utf8mb4_unicode_ci

    $newentry->timecreated = $timecreated; // who calls will generate: $timecreated = time(); $token == md5($timecreated);
    $newentry->view = 1;
    $id_iLM_security = $DB->insert_record("iassign_security", $newentry); // insert into {iassign_security}
    if (!$id_iLM_security) {
      print_error('error_security', 'iassign'); // ./lib/setuplib.php: moodle_exception thrown
      }
    return $id_iLM_security;
    }
  } // class ilm_security


$view = optional_param('view', NULL, PARAM_TEXT); //$view = $_GET['view'];
$token = optional_param('token', NULL, PARAM_TEXT); //$token = $_GET['token'];
$id = optional_param('id', NULL, PARAM_TEXT); //$id = $_GET['id']; //id of the table iassign_security

$stringDebugAux = "";
$strFileContent = "";

//DEBUG
if ($DEBUG) {
  $file_name = "ilm_debug/" . date('Y') . "_" . date('m') . "_" . date('d') . "_" . date('H_i') . "_" . $id;
  $file_debug = "id=" . $id . "<br/>\nview=" . $view . "<br/>\ntoken=" . $token;
  $stringDebugAux = "user.id=" . $USER->id . ", user.name=" . $USER->firstname . " " . $USER->lastname . "\n";
  }

if ($view == -1) { // view free
  $fs = get_file_storage();
  $file = $fs->get_file_by_id($id);
  $strFileContent .= $file->get_content();
  $stringDebugAux .= "1: file content:" . $strFileContent; //DEBUG
  print $strFileContent;
  ilm_security::remove_old_iLM_security_entries($USER->id); // for security reason erase the used entry in 'iassign_security' (and others for this user/activity)
  }
else {

  // Get data from table 'iassign_security'
  $iassign_security = $DB->get_record("iassign_security", array("id" => $id)); // id iassign_statementid userid file timecreated view
  if ($DEBUG) { //DEBUG
    $strAux = "iassign_security = { id=" . $iassign_security->id . ", " . $iassign_security->iassign_statementid . ", | " . $iassign_security->file . " |, " . $iassign_security->view . " }";
    $stringDebugAux .= $strAux;
    }

  if ($iassign_security) {
    $fileid = $iassign_security->file;

    if ($iassign_security) { //TODO must be 'if ($fileid)'?
      $update = new stdClass();
      $update->id = $iassign_security->id;
      $update->view = $iassign_security->view + 1;
      $DB->update_record("iassign_security", $update);
      if ($DEBUG) $stringDebugAux .= " view++ = " . $iassign_security->view . "\n";

      if ($update->view >= 2 && $token == md5($iassign_security->timecreated)) { //
        // Security iLM: remove the entry
        if ($view) {
          // If it is view of the exercise, then get it on the Moodle data (usually '/var/moodledata') => file is a number = '*_files.id'
          // If it is learner answer get it in data base => file is the iLM file content
          // $stringDebugAuxFile = ""; //Debug
          $fs = get_file_storage();
          $file_moodledata = $fs->get_file_by_id($fileid);
          $strFileContent = $file_moodledata->get_content();
          $stringDebugAuxFile = $file_moodledata->get_filename() . "/"; //Debug
          if ($DEBUG) { //DEBUG
            $stringDebugAux .= "view>=2: view=$view: update->view=" . $update->view . "\n" . $token . "=" . md5($iassign_security->timecreated) . "?\n";
            }
          } // if ($update->view == 2 && $token == md5($iassign_security->timecreated))
        else { // not view - get the student content answer        
          // *_iassign_security : id iassign_statementid userid file timecreated view  (where 'file' is longtext utf8_unicode_ci)
          $strFileContent = $iassign_security->file; //ERROR: use some filter, remove '.', '/' and other special characters
          //$strFileContent = $contextid; - this also do NOT work!!
          if ($DEBUG) { //DEBUG
            $stringDebugAux .= "view>=2: else view=$view: update->view=" . $update->view . "\n" . $token . "=" . md5($iassign_security->timecreated) . "?\n";
            $stringDebugAux .= " " . $iassign_security->id . ", " . $iassign_security->timecreated . "\n";
            }
          }

        // Here is the print to the iLM request the content
        print $strFileContent; // Important: to iAssign sent to the iLM its content

        // Effectively erase the content just read (for security reason)
        ilm_security::remove_old_iLM_security_entries($USER->id); // for security reason erase the used entry in 'iassign_security' (and others for this user/activity)

        } // if ($update->view == 2 && $token == md5($iassign_security->timecreated))
      else {
        if ($DEBUG) { //DEBUG
          $countF = 0;
          foreach ($files as $thefile) {
            $strFileName = $thefile->get_filename(); //Debug
            $stringDebugAux .= " " . ($countF++) . ": " . $strFileName . "\n";
            $stringDebugAuxFile = $strFileName . "/"; //Debug
            if ($strFileName != '.') {
              $strFileContent = $thefile->get_content();
              }
            }

          $stringDebugAux .= "view<=2: NOT update->view=" . $update->view . "\n" . $token . "=" . md5($iassign_security->timecreated) . "?\nstrFileContent=" . $strFileContent . "\n";

          }
        }
      } // if ($iassign_security)
    } // if ($iassign_security)
  }


// Attention, do not use 'print' nor 'echo' here, since this could mixed up the iLM reading its content!
if ($DEBUG) { //DEBUG
  $fpointer = fopen($file_name, "w");
  $file_debug .= "\nAuxiliary information: " . $stringDebugAux . "";
  $file_debug .= "\nContent iLM file: |" . $strFileContent . "|";
  fwrite($fpointer, "From: ./mod/iassign/ilm_security.php<br/>\n" . $file_debug);
  fclose($fpointer);
  }
