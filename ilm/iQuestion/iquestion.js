/*

iquestion.js
Lógica específica do iQuestion: pergunta de múltipla escolha.

- Professor (modo autoria): escreve o enunciado, adiciona alternativas e marca
  qual delas é a correta.
- Aluno: lê o enunciado, escolhe uma alternativa.
- Avaliação: 1 se o aluno marcou a mesma alternativa marcada pelo professor
  como correta, 0 caso contrário.

Formato de arquivo usado (JSON serializado como string):
  {
    "enunciado": "...",
    "alternativas": ["...", "...", ...],
    "correta": <índice da alternativa correta>,
    "escolha": <índice que o aluno marcou>   // só existe nas respostas de aluno
  }

Mantém as 3 funções obrigatórias do contrato iLM, no escopo global:
  setExerciseContent(contentStr), getAnswer(), getEvaluation()

*/

console.log("./js/iquestion.js: iniciando...");

var MIN_ALTERNATIVAS = 2;
var MAX_ALTERNATIVAS = 8;

// Pergunta atualmente carregada (gabarito), usada para montar a tela do aluno
// e para calcular a nota.
var dadosExercicio = { enunciado: "", alternativas: [], correta: -1 };

// ---------- Serialização ----------

function parseConteudo(contentStr) {
  try {
    var dados = JSON.parse(contentStr);
    return {
      enunciado: dados.enunciado || "",
      alternativas: Array.isArray(dados.alternativas) ? dados.alternativas : [],
      correta: typeof dados.correta === "number" ? dados.correta : -1,
      escolha: typeof dados.escolha === "number" ? dados.escolha : -1
    };
  } catch (e) {
    console.log("./js/iquestion.js: parseConteudo(): conteúdo inválido/vazio, usando pergunta em branco");
    return { enunciado: "", alternativas: [], correta: -1, escolha: -1 };
  }
}

// ---------- Modo professor (autoria) ----------

function criarLinhaAlternativaProfessor(texto, correta) {
  var row = document.createElement("div");
  row.className = "autoria-alt-row";

  var radio = document.createElement("input");
  radio.type = "radio";
  radio.name = "autoria-correta";
  radio.className = "autoria-alt-correta";
  radio.title = "Marcar como alternativa correta";
  radio.checked = !!correta;

  var textoInput = document.createElement("input");
  textoInput.type = "text";
  textoInput.className = "autoria-alt-texto";
  textoInput.placeholder = "Texto da alternativa";
  textoInput.value = texto || "";

  var btnRemover = document.createElement("button");
  btnRemover.type = "button";
  btnRemover.className = "autoria-alt-remover";
  btnRemover.title = "Remover alternativa";
  btnRemover.innerText = "✕";
  btnRemover.onclick = function () { removerAlternativaProfessor(row); };

  row.appendChild(radio);
  row.appendChild(textoInput);
  row.appendChild(btnRemover);

  document.getElementById("lista-alternativas-professor").appendChild(row);
}

function removerAlternativaProfessor(row) {
  var lista = document.getElementById("lista-alternativas-professor");
  if (lista.children.length <= MIN_ALTERNATIVAS) {
    alert("É preciso ter pelo menos " + MIN_ALTERNATIVAS + " alternativas.");
    return;
  }
  lista.removeChild(row);
}

function adicionarAlternativaProfessor() {
  var lista = document.getElementById("lista-alternativas-professor");
  if (lista.children.length >= MAX_ALTERNATIVAS) {
    alert("Máximo de " + MAX_ALTERNATIVAS + " alternativas.");
    return;
  }
  criarLinhaAlternativaProfessor("", false);
}

function inicializarAlternativasProfessorPadrao() {
  var lista = document.getElementById("lista-alternativas-professor");
  lista.innerHTML = "";
  criarLinhaAlternativaProfessor("", true);
  criarLinhaAlternativaProfessor("", false);
}

function preencherAlternativasProfessor(alternativas, correta) {
  var lista = document.getElementById("lista-alternativas-professor");
  lista.innerHTML = "";
  if (!alternativas || alternativas.length === 0) {
    inicializarAlternativasProfessorPadrao();
    return;
  }
  for (var i = 0; i < alternativas.length; i++) {
    criarLinhaAlternativaProfessor(alternativas[i], i === correta);
  }
}

// ---------- Modo aluno ----------

function renderAlternativasAluno(alternativas, escolha) {
  var container = document.getElementById("alternativas-aluno");
  container.innerHTML = "";

  if (!alternativas || alternativas.length === 0) {
    container.innerHTML = "<em>Esta pergunta ainda não tem alternativas.</em>";
    return;
  }

  for (var i = 0; i < alternativas.length; i++) {
    var id = "alt-" + i;

    var label = document.createElement("label");
    label.className = "ilm-alternativa";
    label.setAttribute("for", id);

    var input = document.createElement("input");
    input.type = "radio";
    input.name = "alternativa";
    input.id = id;
    input.value = i;
    if (i === escolha) input.checked = true;

    var span = document.createElement("span");
    span.innerText = alternativas[i];

    label.appendChild(input);
    label.appendChild(span);
    container.appendChild(label);
  }
}

// ---------- Contrato iLM ----------

// Chamada pelo integrador quando existe conteúdo já salvo para carregar
// (professor reabrindo para editar, ou aluno abrindo para responder/revisar).
function setExerciseContent(contentStr) {
  console.log("./js/iquestion.js: setExerciseContent(): #chars=" + (contentStr ? contentStr.length : 0));
  if (!contentStr) return;

  var dados = parseConteudo(contentStr);
  dadosExercicio = dados;

  document.getElementById("enunciado").innerText = dados.enunciado;

  if (iLMparameters.iLM_PARAM_Authoring) {
    document.getElementById("autoria-enunciado").value = dados.enunciado;
    preencherAlternativasProfessor(dados.alternativas, dados.correta);
  } else {
    renderAlternativasAluno(dados.alternativas, dados.escolha);
  }
}

// OBRIGATÓRIA. O iAssign chama isso para saber o que salvar.
function getAnswer() {
  if (iLMparameters.iLM_PARAM_Authoring) {
    var enunciado = document.getElementById("autoria-enunciado").value.trim();
    var rows = document.querySelectorAll("#lista-alternativas-professor .autoria-alt-row");
    var alternativas = [];
    var correta = -1;

    for (var i = 0; i < rows.length; i++) {
      var texto = rows[i].querySelector(".autoria-alt-texto").value.trim();
      alternativas.push(texto);
      if (rows[i].querySelector(".autoria-alt-correta").checked) correta = i;
    }

    return JSON.stringify({ enunciado: enunciado, alternativas: alternativas, correta: correta });
  } else {
    var selecionado = document.querySelector('input[name="alternativa"]:checked');
    var escolha = selecionado ? parseInt(selecionado.value, 10) : -1;

    return JSON.stringify({
      enunciado: dadosExercicio.enunciado,
      alternativas: dadosExercicio.alternativas,
      correta: dadosExercicio.correta,
      escolha: escolha
    });
  }
}

// OBRIGATÓRIA. O iAssign chama isso para saber a nota (0 a 1).
function getEvaluation() {
  if (iLMparameters.iLM_PARAM_Authoring) {
    return 1;
  }

  var selecionado = document.querySelector('input[name="alternativa"]:checked');
  if (!selecionado) return 0;

  var escolha = parseInt(selecionado.value, 10);
  var nota = (escolha === dadosExercicio.correta) ? 1 : 0;

  console.log("./js/iquestion.js: getEvaluation(): nota=" + nota);
  return nota;
}

// Ao carregar a página, se estivermos em modo autoria e ainda não houver
// nada salvo (setExerciseContent não vai ser chamada), já deixa 2
// alternativas em branco prontas para o professor preencher.
inicializarAlternativasProfessorPadrao();

// Mesma ideia para o modo aluno: se não houver nenhum gabarito para carregar
// (setExerciseContent não vai ser chamada), mostra um aviso em vez de
// deixar a tela em branco.
document.getElementById("enunciado").innerText = "Nenhuma pergunta carregada ainda.";
renderAlternativasAluno([], -1);

console.log("./js/iquestion.js: fim");
