<?php

require_once("/var/www/html/saw_limpo/config.php"); //paprika3 - REMOVER!!!!
require_once("/var/www/html/saw_limpo/mod/iassign/ilm_debug/escreva.php"); //paprika3 - REMOVER!!!!

// Solucao:
// restore_iassign_stepslib.php
// + process_iassign_statement($data):  $new_iassign_statement->file = $data->id; // old {iassign_statement}.id 
// + after_execute(): se {files}.itemid = {iassign_statement}.id, no (1) {files}.itemid == {iassign_statement}.file recupera!

//xxx inicio
function get_files () {
  $course_id = 4; //xxx paprika3
  $coursecontext = context_course::instance($course_id); 
  $list_all_instances_of_iassign = $DB->get_records('iassign', array('course' => $course_id));
  $msgD = "The target course.id=" . $course_id . "<br/>\n" . "And its coursecontext.id=" . $coursecontext->id . "<br/>\n" .
          "#list_all_instances_of_iassign=" . count($list_all_instances_of_iassign) . " : from course.id=" . $course_id . "<br/>\n"; //XXX
  foreach ($list_all_instances_of_iassign as $iassign) {
    $list_all_iassign_statement = $DB->get_records('iassign_statement', array('iassignid' => $iassign->id)); // get all activities inside this block
    $msgD .= "#list_all_iassign_statement = " . count($list_all_iassign_statement) . " : from iassign.id=iassign_statement.iassignid=" . $iassign->id . "<br/>\n"; //D
    foreach ($list_all_iassign_statement as $iassign_statement) {
      $msgD .= "+ {iassign_statement} : id=" . $iassign_statement->id . ", file=" . $iassign_statement->file . ", filesid=" . $iassign_statement->filesid . ", "  . $iassign_statement->name . "<br/>\n"; //D
      $files = $DB->get_records('files', array('component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $iassign_statement->id)); // new 2022 association
      if ($files) { // success
        $msgD .= " - (1) {files}.itemid=={iassign_statement}.id==" . $iassign_statement->id . "<br/>\n";
        }
      else { // try old association : {files}.itemid == {iassign_statement}.file
        $msgD .= " - (1) {files}.itemid=={iassign_statement}.id==" . $iassign_statement->id . " falhou!<br/>\n";
        $files = $DB->get_records('files', array('component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $iassign_statement->file)); //version until 2021:
        if ($files) { // success
  	$msgD .= " - (2) {files}.itemid=={iassign_statement}.file==" . $iassign_statement->file . "<br/>\n";
          }
        else { // try association : {files}.itemid == {iassign_statement}.filesid
          $msgD .= " - (2) {files}.itemid=={iassign_statement}.file==" . $iassign_statement->file . " falhou!<br/>\n";
          $files = $DB->get_records('files', array('component' => 'mod_iassign', 'filearea' => 'exercise', 'itemid' => $iassign_statement->filesid)); //version until 2021:
          if ($files) {
            $msgD .= " - (3) {files}.itemid=={iassign_statement}.filesid==" . $iassign_statement->filesid . "<br/>\n";
            }
          else { // try association : {files}.id == {iassign_statement}.filesid
            $msgD .= " - (3) {files}.itemid=={iassign_statement}.filesid==" . $iassign_statement->filesid . " falhou!<br/>\n";
            $files = $DB->get_records('files', array('component' => 'mod_iassign', 'filearea' => 'exercise', 'id' => $iassign_statement->filesid)); //2025
            if ($files) {
              $msgD .= " - (4) {files}.id=={iassign_statement}.filesid==" . $iassign_statement->filesid . "<br/>\n";
              }
            else $msgD .= " - (4) {files}.id=={iassign_statement}.filesid==" . $iassign_statement->filesid . " falhou!<br/>\n";
            } // else // try association : {files}.id == {iassign_statement}.filesid
          } // else // try association : {files}.itemid == {iassign_statement}.filesid
        } // else // try old association : {files}.itemid == {iassign_statement}.file
      } // foreach ($list_all_iassign_statement as $iassign_statement
    } // foreach ($list_all_instances_of_iassign as $iassign)
  //xxx fim
  return $msgD;
  }

$msgD = get_files();
$date_aux = date('Y_m_d_H_i_s');
//xxx $msgD = "Inicio: " . $date_aux . "\n";
$msgD = "Inicio: " . $date_aux . "<br/>\n" . $msgD; //xxx
echo $msgD . "<br/>\n"; //xxx

$timeWrite = $date_aux; // Year_Month_Day_Hour_Minutes_Seconds
//xxx $filenamewrite = "output_restore_after_execute_" . $timeWrite . ".txt";
$filenamewrite = "teste_output_restore_after_execute_" . $timeWrite . ".txt"; //xxx
echo "filenamewrite=" . $filenamewrite . "<br/>";

$resp = writeContent("", "", $filenamewrite, $msgD);
// $resp2 = writeContent("", "", $filenamewrite, "Segunda escrita!\n" . $msgD);

echo "Final " . $resp . "<br/>\n";
// echo "Final " . $resp2 . "<br/>\n";


?>