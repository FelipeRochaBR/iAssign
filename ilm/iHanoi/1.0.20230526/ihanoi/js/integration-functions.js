/* 

iHano'i
http://www.usp.br/line
http://www.matematica.br/ihanoi

@TODO Does not yet implemented multi-language

@see Get some explanation on how to integrate your iLM (interative Learning Module) to Moodle using iAssing
     examining https://www.matematica.br/ia/#iLM

Leo^nidas de Oliveira Branda~o
v1.0: 2023/05/25 in English, see iLM description in https://www.matematica.br/ia/#iLM
v0.5: 2020/11/22 getiLMContentIF(): if 'iLMparameters.iLM_PARAM_Assignment' empty, do not load iHanoi content file (with extension 'ihn')
v0.4: 2020/08/03
v0.1: 2020/07/31
v0  : 2020/07/28

@calledby index_ihanoi.html : src="js/integration-functions.js" (after src="js/ihanoi.js")

*/

console.log("./js/integration-functions.js: starting...");

// External variables (not necessary)
// nDiscos  = number of disks
// contador = to counter the number of movements effectuated
// topoHasteA, topoHasteB, topoHasteC = point to the array element corresponding to top disk on that rod

const NOTA_MINIMO_B = 0.8; // discount to be applied in grade when the final rod was B (not C)
const ESPERA = 0; // delay to allow the user to see the movements (when under "automatic movements")

// Remove the last portion of current URL
function getURL () { // pegarURL
  var strUrl, ind, subStrigURL;
  strUrl = window.location.href;
  // If URL has parameters: ?... erase it
  ind = strUrl.lastIndexOf("?");
  subStrigURL = strUrl.substring(0, ind);
  // Now search the last "/"
  ind = subStrigURL.lastIndexOf("/");
  subStrigURL = strUrl.substring(0, ind);
  console.log("./js/integration-functions.js: getURL(): " + subStrigURL);
  return subStrigURL;
  }

// Function to return the GET parameter informed by iAssign through URL
// It is not mandatory, but usefull to get parameters
// Attention: the 'window.location.search' is define in the origin HTML: <iframe ... src="index_ihanoi.html?..."
function getParameterByName (name) {
  var str_url = window.location.search;
  var match = RegExp('[?&]' + name + '=([^&]*)').exec(str_url);
  var name_value = "";
  if (match && match.length>0)
    name_value = match[1];// if (name=="iLM_PARAM_Authoring")
  // window.location.search=" + window.location.search +
  answer = null; // getiLMContentIF(.) will try to auto-evaluate if 'iLM_PARAM_TeacherAutoEval' has other thing else 'null'
  if (name_value) {
    answer = decodeURIComponent(match[1].replace(/\+/g, ' '));
    }
  return answer;
  }

// Creat an array with GET parameter informed by iAssign through URL
var iLMparameters = {
  // Example through iAssign/Moodle: http://.../moodle/mod/iassign/ilm_manager.php?from=iassign&id=2&action=update&ilmid=53&dirid=41800&fileid=282593
  iLM_PARAM_Authoring: getParameterByName("iLM_PARAM_Authoring"), // if defined, then is teacher, allow edit
  iLM_PARAM_ServerToGetAnswerURL: getParameterByName("iLM_PARAM_ServerToGetAnswerURL"),
  iLM_PARAM_SendAnswer: getParameterByName("iLM_PARAM_SendAnswer"),
  iLM_PARAM_AssignmentURL: getParameterByName("iLM_PARAM_AssignmentURL"),
  iLM_PARAM_Assignment: getParameterByName("iLM_PARAM_Assignment"),
  iLM_PARAM_TeacherAutoEval: getParameterByName("iLM_PARAM_TeacherAutoEval"),
  lang: getParameterByName("lang")
  };


// The iLM must implement the function 'getAnswer()'
// See in 'ihanoi.js' this function


// The iLM must implement the function 'getEvaluation()'
// See in 'ihanoi.js' this function


// This a model function to you to implement in your iLM allowing its use under iAssin/Moodle
// It is not necessary to iHanoi, since it implements this code in ./js/ihanoi.js under the name 'getiLMContent()'
// If you use "parent.getiLMContent()" will get the iLM function named 'getiLMContent()'
function getiLMContentIA () {
  var msg = "";
  // The parameter "iLM_PARAM_Assignment" give the URL of the iLM content file, here
  // the address of iHanoi content file (extension 'ihn')
  // It is used by XMLHttpRequest() to get the content file
  var pagina = iLMparameters.iLM_PARAM_Assignment;
  var txtFile;
  var data = -1;
  console.log("./js/integration-functions.js: getiLMContentIF(): (1) iLMparameters.iLM_PARAM_Assignment=" + iLMparameters.iLM_PARAM_Assignment); //D
  if (iLMparameters.iLM_PARAM_Assignment == null) { // 'iLM_PARAM_Assignment' is the address to iLM get its content from the server
    console.log("./js/integration-functions.js: getiLMContentIF(): NAO existe arquivo IHN para ser carregado (iLMparameters.iLM_PARAM_Assignment vazio), finalize");
    return;
    }

  txtFile = new XMLHttpRequest(); // preparer HTTP connection (to recover 'ihn' file)

  console.log("./js/integration-functions.js: getiLMContentIF(): try to get the iLM file under: '" + pagina + "'");

  // window.location : href = complete URL; pathname = only... ; hostname = only server name
  txtFile.open("GET", pagina, true); // true=>asynchronous - caution with: XML Parsing Error: syntax error
  txtFile.send(); // close it after step 3
  txtFile.responseType="text"; // Avoid warning: XML Parsing Error: syntax error

  txtFile.onreadystatechange = function () {
    if (txtFile.readyState === 4) { // Makes sure the document is ready to parse.
      if (txtFile.status === 200) { // Makes sure the file exists.
        // stop 3: last step, get file
        var nDiscos0; // iHanoi does this, in your iLM consider your variables...
        nDiscos0 = nDiscos;
        allText = txtFile.responseText;
        texto = allText; // define global 'texto'
        // process content of 'ihn' file
	setExerciseContent(allText); // iHanoi has this function: ihanoi.js!setExerciseContent(contentStr)
	
        if (iLMparameters.iLM_PARAM_TeacherAutoEval != null) { // if 'iLM_PARAM_TeacherAutoEval' is teacher evaluating the activity
          console.log("./js/integration-functions.js: getiLMContentIF(): enter with 'iLM_PARAM_TeacherAutoEval'=" + iLMparameters.iLM_PARAM_TeacherAutoEval);
          try {
            iLMparameters.iLM_PARAM_Assignment = iLMparameters.iLM_PARAM_TeacherAutoEval;
            setExercise(false); // iHanoi implements this function...
          } catch (Error) {
            console.log("./js/integration-functions.js: getiLMContentIF(): error trying to run 'setExercise(false)'");
            }
          teacherAutoEvalIF(data); // another iHanoi function 'teacherAutoEval(data)'
          console.log("./js/integration-functions.js: getiLMContentIF(): final (after read content of 'ihn')");
          return;
          }
        }
      //else alert("Error 2"); // step 2: after
      }
    //else alert("Error 1"); // step 1: first here
    } 

  console.log("./js/integration-functions.js: getiLMContentIF(): final");
  } // function getiLMContentIF()


// To be used with re-evaluation 
// iHanoi implements it to allow the teacher to re-evaluate all the exercises, sent by all the student (one-by-one)
function teacherAutoEvalIF (data) {
  var nDiscos0;
  nDiscos0 = nDiscos;
  // alert("integration-functions.js: teacherAutoEvalIF(.): " + data);
  // processar conteudo de INH
  setExerciseContent(data); // ihanoi.js!setExerciseContent(contentStr)
  }

console.log("./js/integration-functions.js: final");