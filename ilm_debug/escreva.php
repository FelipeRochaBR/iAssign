<?php

// To be used in development version of backup/restore process.
// Write information about each {iassignment_submission} in directory: /mod/iassign/ilm_debug/output_restore_after_execute_*.txt
//
// @calledby ./mod/iassign/backup/moodle2/restore_iassign_stepslib.php:

// require_once("../../../config.php"); // Not necessary, since who loads this
//                                         (/mod/iassign/backup/moodle2/restore_iassign_stepslib.php), already has "config.php"!
// require_once("/var/www/html/sm/config.php");
// require_once("/var/www/producao/moodle/config.php"); // para Producao - ver /home/leo/projetos/iMA/lms/itarefa/novo/sobre_restauracao.txt
require_once("/var/www/html/saw_limpo/config.php"); //paprika3 - REMOVER!!!!

// Apelei
// # mkdir /var/www/producao/saw.atp.usp.br/mod/iassign/ilm_debug/
// # chown www-data.www-data /var/www/producao/saw.atp.usp.br/mod/iassign/
// # mkdir /var/www/producao//saw.atp.usp.br/mod/iassign/ilm_debug/
// # chown www-data.www-data /var/www/producao//saw.atp.usp.br/mod/iassign/
// # chmod a+w /var/www/producao/moodle//saw.atp.usp.br/mod/iassign/ilm_debug/

// Teste paprika3:
// require_once("/var/www/html/saw_limpo/config.php");
//_ echo "CFG->wwwroot=" . $CFG->wwwroot . "<br/>" . "_SERVER['DOCUMENT_ROOT']=" . $_SERVER['DOCUMENT_ROOT'] . "<br/>";
//_ $doc_root = $_SERVER['DOCUMENT_ROOT'];
//_ if ($doc_root=="/var/www/html")
//_ $pathbase = $doc_root . "/" . get_moodle_name($CFG->wwwroot) . "/mod/iassign/ilm_debug/"; // "/var/www/html/...(moodle).../mod/iassign/ilm_debug/";
//_ echo "get_moodle_name=" . get_moodle_name($CFG->wwwroot) . "<br/>" . "pathbase=" . $pathbase . "<br/>";
//_ // CFG->wwwroot=http://localhost/saw_limpo
//_ // _SERVER['DOCUMENT_ROOT']=/var/www/html
//_ // get_moodle_name=saw_limpo
//_ // pathbase=/var/www/html/saw_limpo/mod/iassign/ilm_debug/
//_ exit;
  
// Givem URL (usually from $CFG->wwwroot) return the Moodle name
function get_moodle_name ($wwwroot) {
  // No "http://saw.atp.usp.br" usar a linha abaixo e comentar as demais, senao devolvera: saw.atp.usp.br
  // $CFG->wwwroot   = 'http://localhost/saw_limpo';
  $items = explode("/", $wwwroot);
  $num = count($items);
  // echo "get_moodle_name(.): num=" . $num . ", items[" . ($num-1) . "]= " . $items[$num-1] . "<br/>";
  if ($num==3) { // "saw.atp.usp.br"
     $wwwroot = $items[$num-1];
     $items = explode(".", $wwwroot);
     return $items[0]; // apenas "saw" de "saw.atp.usp.br"
     }
  if ($num>0) return $items[$num-1];
  return $wwwroot;
  }


// Write each personal student file in './<turma>/<N>_<name>.txt'
// Param: $filetype1 in "student", "log", "sendemail"
// Return 1 in case of success; -1 in case do not overwrite
function writeContent ($filetype1, $pathbase, $outputFile, $msgToRegister) {
  global $CFG, $GROUP, $WRITEMSG, $OVERWRITE;

  // $pathbase = "/var/www/html/saw/mod/iassign/ilm_debug/"; // "/mod/iassign/ilm_debug/";
  // $pathbase = "../../../mod/iassign/ilm_debug/"; // "/mod/iassign/ilm_debug/";
  // Na                            paprika4                           homologacao
  // _SERVER['DOCUMENT_ROOT']      /var/www/html                      /var/www/homologacao/moodle/
  // CFG->wwwroot                  http://localhost/saw               https://homologacao.saw.atp.usp.br
  // get_moodle_name(CFG->wwwroot) saw                                homologacao
  // complete_outputFile           /var/www/html/saw/mod/iassign/...  /var/www/homologacao/moodle//homologacao/mod/iassign/...

  $doc_root = $_SERVER['DOCUMENT_ROOT'];
  if ($doc_root=="/var/www/html")
    $pathbase = $doc_root . "/" . get_moodle_name($CFG->wwwroot) . "/mod/iassign/ilm_debug/"; // "/var/www/html/...(moodle).../mod/iassign/ilm_debug/";
  else // no 'homologacao.saw.atp.usp.br' ou 'saw.atp.usp.br'
    $pathbase = $doc_root . "mod/iassign/ilm_debug/"; // "/var/www/...(moodle).../mod/iassign/ilm_debug/";
  // Na Paprika3:
  $pathbase = $doc_root . "/" . get_moodle_name($CFG->wwwroot) . "/mod/iassign/ilm_debug/"; // "/var/www/html/...(moodle).../mod/iassign/ilm_debug/";
  $complete_outputFile = $pathbase . $outputFile;
  echo "escreva.php: doc_root=_SERVER['DOCUMENT_ROOT']=" . $_SERVER['DOCUMENT_ROOT'] . "<br/> &nbsp; pathbase = " . $pathbase . "<br/>\n"; //paprika3: /var/www/html
  //_ // CFG->wwwroot=http://localhost/saw_limpo
  //_ // _SERVER['DOCUMENT_ROOT']=/var/www/html
  //_ // get_moodle_name=saw_limpo
  //_ // pathbase=/var/www/html/saw_limpo/mod/iassign/ilm_debug/

  if (!is_writable($pathbase)) { // TRUE se arquivo existe e pode ser escrito
     // $file_debug .= "Error: '" . $completfilepath . "' could not be registered! Perhaps the directory or the file has permission problem?<br/>\n";
     //D echo  "$complete_outputFile, $filetype1<br/>";
     print "Erro! Problema de acesso ao servidor! Por favor, avise ao administrador (<tt>" . $pathbase . "</tt> nao acessivel para escrita).<br/>"; //  . $file_debug . "
     exit(0);
     }

   // $result = writeContent("file", $pathbase, $filename, $msgToRegister);
   // print "escreva.php: result=$result<br/>";

   // write personal email file
   // To write: verify if the file does not exists or have permission to overwrite
   if (is_file($complete_outputFile)) {  // already exist this file
     // $complete_outputFile .= $outputFile . '_' . date('Y_m_d_h_m');
     } // if (is_file($complete_outputFile))

   if (1==1) { // write/overwrite the file
     //D echo "escreva.php!writeContent(.): outputFile=" . $outputFile . ", complete_outputFile=" . $complete_outputFile . "<br/>\n";
     $fpointer = fopen($complete_outputFile, "a"); // a=append; w=write - if executed, it clear the previou content at this file
     if (!$fpointer) {
        $file_debug .= "Erro: nao foi possivel abrir o roteiro (" . $complete_outputFile . ")!<br/>\n";
        // it was not possible to open the file '$completfilepath" . $file_name . "'!<br/>\n";
        print "<br/>" . $file_debug . "<br/>\n";
        //D echo "writeContent: $filetype1, outputFile=$complete_outputFile<br/>\n";
        return 0;
        }
     fwrite($fpointer, $msgToRegister . "\n");
     // echo " - outputFile=$complete_outputFile : WRITEMSG=$WRITEMSG, OVERWRITE=$OVERWRITE, gerado com sucesso!<br/>\n";
     fclose($fpointer);
     }
   else {
     // print "Nao gera os arquivos personalizados para email aos alunos ('$complete_outputFile')<br/>\n"; // admin/<turma>/<N>_<name>.txt
     }

  return 1;
  } // function writeContent($outputFile, $msgToRegister)


$date_aux = date('Y_m_d_H_i_s');
//xxx $msgD = "Inicio: " . $date_aux . "\n";
$msgD = "Inicio: " . $date_aux . "<br/>\n" . $msgD; //xxx
// echo $msgD . "<br/>\n"; //xxx

$timeWrite = $date_aux; // Year_Month_Day_Hour_Minutes_Seconds
$filenamewrite = "output_restore_after_execute_" . $timeWrite . ".txt";
echo "filenamewrite=" . $filenamewrite . "<br/>";

$resp = writeContent("", "", $filenamewrite, $msgD);
// $resp2 = writeContent("", "", $filenamewrite, "Segunda escrita!\n" . $msgD);

echo "Final " . $resp . "<br/>\n";
// echo "Final " . $resp2 . "<br/>\n";


?>