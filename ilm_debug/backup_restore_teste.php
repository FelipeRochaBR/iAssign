<?php
// Para depuracao: escrever no arquivo diretorio '/var/www/html/saw/mod/iassign/ilm_debug/' ou
// '/var/www/producao/moodle/mod/iassign/ilm_debug/' - mas esta versao nao esta' escrevendo!
// Testes para 'backup_iassign_stepslib.php' e 'restore_iassign_stepslib.php'

require_once "../../../config.php";

// echo $CFG->dirroot . "/mod/iassign/ilm_debug/<br/>\n"; // /var/www/html/dev_saw/mod/iassign/ilm_debug/

// Write each personal student file in './<turma>/<N>_<name>.txt'
// Param: $filetype1 in "student", "log", "sendemail"
// Return 1 in case of success; -1 in case do not overwrite
function writeContent ($filetype1, $pathbase, $outputFile, $msgToRegister) {
  global $CFG, $GROUP, $WRITEMSG, $OVERWRITE;
  // $pathbase = "/var/www/html/saw/mod/iassign/ilm_debug/"; // "/mod/iassign/ilm_debug/";
  $pathbase = $CFG->dirroot . "/mod/iassign/ilm_debug/"; // "/mod/iassign/ilm_debug/";
  $outputFile = $pathbase . $outputFile;
  if (!is_writable($pathbase)) { // TRUE se arquivo existe e pode ser escrito
     // $file_debug .= "Error: '" . $completfilepath . "' could not be registered! Perhaps the directory or the file has permission problem?<br/>\n";
     //D echo  "$outputFile, $filetype1<br/>";
     print "Erro! Problema de acesso ao servidor! Por favor, avise ao administrador (<tt>$pathbase</tt> nao acessivel para escrita).<br/>"; //  . $file_debug . "
     exit(0);
     }
   // $result = writeContent("file", $pathbase, $filename, $msgToRegister);
   // print "escreva.php: result=$result<br/>";
   // write personal email file
   // To write: verify if the file does not exists or have permission to overwrite
   if (is_file($outputFile)) {  // already exist this file
     // $outputFile .= $outputFile . '_' . date('Y_m_d_h_m');
     } // if (is_file($outputFile))
   if (1==1) { // write/overwrite the file
     $fpointer = fopen($outputFile, "w"); // write - if executed, it clear the previou content at this file
     if (!$fpointer) {
        $file_debug .= "Erro: nao foi possivel abrir o roteiro ($outputFile)!<br/>\n";
        // it was not possible to open the file '$completfilepath" . $file_name . "'!<br/>\n";
        print "<br/>" . $file_debug . "<br/>\n";        //D echo "writeContent: $filetype1, outputFile=$outputFile<br/>\n";
        return 0;
        }
     fwrite($fpointer, $msgToRegister . "\n");     // echo " - outputFile=$outputFile : WRITEMSG=$WRITEMSG, OVERWRITE=$OVERWRITE, gerado com sucesso!<br/>\n";
     fclose($fpointer);
     }
   else { }     // print "Nao gera os arquivos personalizados para email aos alunos ('$outputFile')<br/>\n"; // admin/<turma>/<N>_<name>.txt
   return 1;
   } // function writeContent($outputFile, $msgToRegister)

// Para 'restore_iassign_stepslib.php'
// Considering the destination Moodle, try to find the nearest iLM to a given {iassign_statement} activity
// Given artificial {iassign_statement} fields provided by 'backup_iassign_stepslib.php!define_structure()' (ilm_name,version,type,extension)
// find the destination Moodle the closest iLM (considering those matching lower(ilm_name) and lower(extension)).      
function get_destination_ilmid ($data) {
  global $DB; //D
  $origin_ilm_name = strtolower($data->ilm_name);
  $origin_version = strtolower($data->version);
  $origin_type = strtolower($data->type);
  $origin_extension = strtolower($data->extension);
  // $iassign_ilm = $DB->get_records('iassign_ilm', array('name' => $origin_ilm_name)); // search for all with this 'name'
  $sql_query = "SELECT id,LOWER(name) AS ilm_name,LOWER(version) AS version,LOWER(type) AS type,LOWER(extension) AS extension " .
    " FROM {iassign_ilm} WHERE name='" . $origin_ilm_name . "' AND LOWER(extension)='" . $origin_extension . "' ORDER BY id DESC";
  echo "get_destination_ilmid: <br/>";
  $iassign_ilm = $DB->get_records_sql($sql_query);
  foreach ($iassign_ilm as $one_iassign_ilm) { // preference to the same 'type' and 'version'
    if ($one_iassign_ilm->type == $origin_type && $one_iassign_ilm->version == $origin_version) {
      echo "get_destination_ilmid: 1<br/>";
      return $one_iassign_ilm->id; // return the {iassign_ilm} with bigger ID
      }
    else echo " - " . $one_iassign_ilm->id . ", " . $one_iassign_ilm->ilm_name . ", " . $one_iassign_ilm->version . ", " . $one_iassign_ilm->type . ", " . $one_iassign_ilm->extension . "<br/>\n";
    }
  foreach ($iassign_ilm as $one_iassign_ilm) { // accept only the same 'type'
    if ($one_iassign_ilm->type == $origin_type) {
      echo "get_destination_ilmid: 2<br/>";
      return $one_iassign_ilm->id; // return the {iassign_ilm} with bigger ID
      }
    }
  foreach ($iassign_ilm as $one_iassign_ilm) { // accept any iLM...
    echo "get_destination_ilmid: 3<br/>";
    return $one_iassign_ilm->id; // return the {iassign_ilm} with bigger ID
    }
  return NULL; // warn the Admin that iAssign is missing some iLM...
  }

$ilm_id = 43; // 56; // iVProg versao '1.0.20220113' com tipo='HTML5'
$ias_id = 5416; // "1.4 Introducao - Ler dois valores a e b e imprimir..." iassignid=1352, filesid=282515

// fields from {iassign_statement}
$sql_fields_iassign_statement =
  " ias.id, ias.name, ias.iassignid, ias.type_iassign, ias.proposition, ias.author_name, ias.author_modified_name, " .
  "ias.iassign_ilmid, ias.file, ias.grade, ias.timecreated, ias.timemodified, ias.timeavailable, " .
  "ias.timedue, ias.preventlate, ias.test, ias.special_param1, ias.position, ias.visible, " .
  "ias.max_experiment, ias.dependency, ias.automatic_evaluate, ias.show_answer, ias.store_all_submissions, ias.filesid, ";
// additional fields from {iassign_ilm}
$sql_additional_fields_ilm = " ilm.name as ilm_name, ilm.version, ilm.type, ilm.extension ";
// Simular o $data vindo de 'backup_iassign_stepslib.php'
$sql_query = 'SELECT ' . $sql_fields_iassign_statement . $sql_additional_fields_ilm .
  ' FROM {iassign_statement} AS ias, {iassign_ilm} AS ilm' .
  ' WHERE ilm.id = ias.iassign_ilmid AND ias.id = ' . $ias_id; // ,ias.iassignid = ' . 
//$sql_query = 'SELECT ' . $sql_fields_iassign_statement . $sql_additional_fields_ilm .
//  ' FROM s_iassign_statement AS ias, s_iassign_ilm AS ilm' .
//  ' WHERE ilm.id = ias.iassign_ilmid AND ias.id = ' . $ias_id; // ,ias.iassignid = ' . 
$ias = $DB->get_records_sql($sql_query);
print "sql_query=" . $sql_query . "<br/>\n";
print "#ias=" . count($ias) . "<br/>\n";
foreach ($ias as $one_ias) {
  print $one_ias->id . ", " . $one_ias->iassignid . ", " . $one_ias->iassign_ilmid . ", " . $one_ias->ilm_name . ", " . $one_ias->version .
    ", " . $one_ias->extension . ", " . $one_ias->name . "<br/>\n";
  $one_ias->version = "0.1.20190308"; // "0.1.20190307" -> "0.1.20190308"
  $resp = get_destination_ilmid($one_ias);
  if ($resp==NULL) print "ERRO!<br/>\n";
  else print "OK, resp=" . $resp . "<br/>\n";
  }
?>