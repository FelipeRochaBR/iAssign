/*

integration-functions.js
Bloco de integração genérico iLM <-> iAssign/Moodle. Praticamente não precisa
mexer aqui de um iLM para outro — a lógica específica do exercício fica em
js/<seu-ilm>.js.

O iAssign chama este iLM dentro de um <iframe>, passando parâmetros via
querystring (GET). Referência: https://www.matematica.br/ia/#iLM

*/

console.log("./js/integration-functions.js: iniciando...");

// Lê um parâmetro da querystring atual (window.location.search)
function getParameterByName(name) {
  var str_url = window.location.search;
  var match = RegExp('[?&]' + name + '=([^&]*)').exec(str_url);
  var name_value = null;
  if (match && match.length > 0) {
    name_value = decodeURIComponent(match[1].replace(/\+/g, ' '));
  }
  return name_value;
}

// Parâmetros que o iAssign envia para o iLM
var iLMparameters = {
  iLM_PARAM_Authoring: getParameterByName("iLM_PARAM_Authoring"),               // presente/true => modo professor (montando o exercício)
  iLM_PARAM_ServerToGetAnswerURL: getParameterByName("iLM_PARAM_ServerToGetAnswerURL"),
  iLM_PARAM_SendAnswer: getParameterByName("iLM_PARAM_SendAnswer"),
  iLM_PARAM_AssignmentURL: getParameterByName("iLM_PARAM_AssignmentURL"),
  iLM_PARAM_Assignment: getParameterByName("iLM_PARAM_Assignment"),             // URL do arquivo de conteúdo do exercício (gabarito/enunciado) já salvo
  iLM_PARAM_TeacherAutoEval: getParameterByName("iLM_PARAM_TeacherAutoEval"),   // usado na re-avaliação em lote pelo professor
  lang: getParameterByName("lang")
};

// Busca (via XMLHttpRequest) o conteúdo do exercício apontado por
// iLM_PARAM_Assignment e repassa para setExerciseContent(), que seu
// js/<seu-ilm>.js deve implementar.
function getiLMContent() {
  if (!iLMparameters.iLM_PARAM_Assignment) {
    console.log("./js/integration-functions.js: getiLMContent(): sem iLM_PARAM_Assignment, nada para carregar (provavelmente autoria nova)");
    return;
  }

  var txtFile = new XMLHttpRequest();
  txtFile.open("GET", iLMparameters.iLM_PARAM_Assignment, true);
  txtFile.responseType = "text";
  txtFile.onreadystatechange = function () {
    if (txtFile.readyState === 4 && txtFile.status === 200) {
      setExerciseContent(txtFile.responseText); // implementado em js/<seu-ilm>.js

      if (iLMparameters.iLM_PARAM_TeacherAutoEval != null) {
        // Reavaliação em lote: o professor está revisando respostas de alunos
        console.log("./js/integration-functions.js: getiLMContent(): reavaliação (iLM_PARAM_TeacherAutoEval)");
      }
    }
  };
  txtFile.send();
}

// Ponto de entrada: chamado pelo <body onload="start_iLM();"> do index.html
function start_iLM() {
  console.log("./js/integration-functions.js: start_iLM(): iLMparameters=", iLMparameters);

  var isAuthoring = !!iLMparameters.iLM_PARAM_Authoring;
  document.getElementById("area-professor").style.display = isAuthoring ? "block" : "none";
  document.getElementById("area-aluno").style.display = isAuthoring ? "none" : "block";

  getiLMContent(); // carrega o exercício já existente, se houver (edição ou resolução)
}

console.log("./js/integration-functions.js: fim");
