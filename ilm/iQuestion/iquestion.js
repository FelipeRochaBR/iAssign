/*

iquestion.js
Lógica específica do iQuestion: exercícios curtos de programação, em três tipos.

- "multipla": pergunta de múltipla escolha (opcionalmente com um trecho de código).
- "lacunas":  completar o código. O professor escreve o código marcando cada
              lacuna com [[resposta]]; o aluno preenche as lacunas tocando nas
              peças de um banco de palavras (respostas + distratores, embaralhados).
- "ordenar":  ordenar as linhas de um programa, que aparecem embaralhadas.

Professor (modo autoria): escolhe o tipo, escreve o enunciado e o conteúdo,
opcionalmente uma explicação e se o aluno pode "Verificar" antes de enviar.
Aluno: resolve o exercício; com "Verificar", vê o que está certo/errado (sem
mostrar a resposta) e, ao acertar tudo, a explicação.

Avaliação (0 a 1):
- multipla: 1 se a alternativa escolhida é a correta, 0 caso contrário;
- lacunas:  fração das lacunas preenchidas corretamente;
- ordenar:  fração das linhas que estão na posição correta.

Formato de arquivo (JSON serializado como string). Arquivos antigos, sem
"tipo", continuam válidos (são "multipla").
  {
    "tipo": "multipla" | "lacunas" | "ordenar",
    "enunciado": "...",
    "explicacao": "...",            // opcional, mostrada quando o aluno acerta
    "verificar": true,              // aluno pode usar o botão "Verificar"
    "codigo": "...",                // multipla: trecho opcional; lacunas: código com [[resposta]]
    "alternativas": ["..."],        // multipla
    "correta": 0,                   // multipla
    "distratores": ["..."],         // lacunas: peças erradas extras no banco
    "linhas": ["...", "..."],       // ordenar: linhas na ordem correta
    // só nas respostas de aluno:
    "escolha": 0,                   // multipla
    "preenchimento": ["..."|null],  // lacunas: texto colocado em cada lacuna
    "ordem": [2, 0, 1]              // ordenar: índices de "linhas" na ordem montada pelo aluno
  }

Mantém as 3 funções obrigatórias do contrato iLM, no escopo global:
  setExerciseContent(contentStr), getAnswer(), getEvaluation()

*/

console.log("./js/iquestion.js: iniciando...");

var MIN_ALTERNATIVAS = 2;
var MAX_ALTERNATIVAS = 8;

var TIPOS = {
  multipla: { rotulo: "Múltipla escolha", instrucao: "Escolha a resposta correta" },
  lacunas:  { rotulo: "Completar o código", instrucao: "Toque nas peças para completar o código" },
  ordenar:  { rotulo: "Ordenar linhas", instrucao: "Coloque as linhas na ordem correta" }
};

// Exercício carregado (gabarito) e a tela do aluno montada a partir dele
var dadosExercicio = exercicioVazio();
var visaoAluno = null;

// ======================================================================
// Dados
// ======================================================================

function exercicioVazio() {
  return {
    tipo: "multipla", enunciado: "", explicacao: "", verificar: true,
    codigo: "", alternativas: [], correta: -1, distratores: [], linhas: []
  };
}

function lista(v) { return Array.isArray(v) ? v : []; }

// Conteúdo salvo -> exercício (uma questão) ou sequência {tipo: "sequencia", titulo, itens: [...], respostas: [...]}
function parseConteudo(contentStr) {
  var d = null;
  try { d = JSON.parse(contentStr); } catch (e) { d = null; }
  if (d && d.tipo === "sequencia") {
    var respostas = lista(d.respostas);
    return {
      tipo: "sequencia",
      titulo: d.titulo || "",
      itens: lista(d.itens).map(function (item, i) {
        var p = parseItem(item);
        if (respostas[i]) p.resposta = respostas[i]; // resposta do aluno para esta questão
        return p;
      })
    };
  }
  if (!d) console.log("./js/iquestion.js: parseConteudo(): conteúdo inválido/vazio, usando exercício em branco");
  return parseItem(d || {});
}

// Objeto de uma questão -> exercício normalizado (com a resposta do aluno, se houver, em .resposta)
function parseItem(d) {
  var base = exercicioVazio();
  try {
    base.tipo = TIPOS[d.tipo] ? d.tipo : "multipla"; // arquivos antigos não têm "tipo"
    base.enunciado = d.enunciado || "";
    base.explicacao = d.explicacao || "";
    base.verificar = d.verificar !== false;
    base.codigo = d.codigo || "";
    base.alternativas = lista(d.alternativas);
    base.correta = typeof d.correta === "number" ? d.correta : -1;
    base.distratores = lista(d.distratores);
    base.linhas = lista(d.linhas);
    base.resposta = {
      escolha: typeof d.escolha === "number" ? d.escolha : -1,
      preenchimento: Array.isArray(d.preenchimento) ? d.preenchimento : null,
      ordem: Array.isArray(d.ordem) ? d.ordem : null
    };
  } catch (e) {
    console.log("./js/iquestion.js: parseConteudo(): conteúdo inválido/vazio, usando exercício em branco");
  }
  return base;
}

// Divide o código com [[resposta]] em pedaços: {texto} ou {lacuna: "resposta"}
function partesLacunas(codigo) {
  var partes = [];
  var re = /\[\[([\s\S]*?)\]\]/g;
  var ultimo = 0, m;
  while ((m = re.exec(codigo || "")) !== null) {
    if (m.index > ultimo) partes.push({ texto: codigo.slice(ultimo, m.index) });
    partes.push({ lacuna: m[1].trim() });
    ultimo = re.lastIndex;
  }
  if (ultimo < (codigo || "").length) partes.push({ texto: codigo.slice(ultimo) });
  return partes;
}

function respostasLacunas(codigo) {
  return partesLacunas(codigo).filter(function (p) { return "lacuna" in p; }).map(function (p) { return p.lacuna; });
}

function embaralhar(arr) {
  var a = arr.slice();
  for (var i = a.length - 1; i > 0; i--) {
    var j = Math.floor(Math.random() * (i + 1));
    var t = a[i]; a[i] = a[j]; a[j] = t;
  }
  return a;
}

// Ordem inicial das linhas: embaralhada sem NENHUMA linha já na posição certa, para que o aluno que não
// mexe em nada fique com nota 0 (a nota é a fração de linhas na posição correta). Se não for possível
// (ex.: muitas linhas iguais), usa a melhor tentativa.
function ordemEmbaralhada(linhas) {
  var indices = linhas.map(function (_, i) { return i; });
  if (linhas.length < 2) return indices;
  var noLugar = function (o) { return o.filter(function (idx, pos) { return linhas[idx] === linhas[pos]; }).length; };
  var melhor = indices.slice(1).concat(indices[0]); // rotação: nenhuma linha no lugar, se não houver linhas repetidas
  for (var tentativa = 0; tentativa < 50 && noLugar(melhor) > 0; tentativa++) {
    var o = embaralhar(indices);
    if (noLugar(o) < noLugar(melhor)) melhor = o;
  }
  return melhor;
}

function ordemCorreta(linhas, ordem) {
  return ordem.every(function (idx, pos) { return linhas[idx] === linhas[pos]; });
}

// ======================================================================
// Pequenos auxiliares de DOM
// ======================================================================

function el(tag, classe, texto) {
  var e = document.createElement(tag);
  if (classe) e.className = classe;
  if (texto !== undefined && texto !== null) e.textContent = texto;
  return e;
}

function botao(classe, texto, aoClicar) {
  var b = el("button", classe, texto);
  b.type = "button";
  if (aoClicar) b.addEventListener("click", aoClicar);
  return b;
}

// ======================================================================
// Tela do aluno (também usada na pré-visualização do professor)
// ======================================================================

// Monta a tela do exercício "dados" dentro de "raiz". "resposta" (opcional)
// restaura o que o aluno já tinha feito. Devolve um objeto com:
//   resposta() -> campos da resposta (escolha/preenchimento/ordem)
//   nota()     -> 0 a 1
//   completo() -> o aluno já respondeu tudo
// "opcoes" (opcional): numero (mostra "Questão N"), aoMudar (chamada a cada mudança na resposta).
function criarVisaoAluno(raiz, dados, resposta, opcoes) {
  raiz.textContent = "";
  resposta = resposta || {};
  opcoes = opcoes || {};
  var tipo = dados.tipo;
  var marcado = false; // a tela está mostrando o resultado do "Verificar"

  var cartao = el("div", "iq-cartao");
  raiz.appendChild(cartao);

  var cabecalho = el("div", "iq-cabecalho");
  if (opcoes.numero) cabecalho.appendChild(el("span", "iq-numero", "Questão " + opcoes.numero));
  cabecalho.appendChild(el("span", "iq-selo iq-selo-" + tipo, TIPOS[tipo].rotulo));
  cartao.appendChild(cabecalho);

  if (dados.enunciado) cartao.appendChild(el("p", "iq-enunciado", dados.enunciado));
  cartao.appendChild(el("p", "iq-instrucao", TIPOS[tipo].instrucao));

  var corpo = el("div", "iq-corpo");
  cartao.appendChild(corpo);

  var feedback = el("div", "iq-feedback");
  feedback.setAttribute("role", "status");
  feedback.setAttribute("aria-live", "polite");
  feedback.hidden = true;

  var api;
  if (tipo === "lacunas") api = montarLacunas(corpo, dados, resposta, mudou);
  else if (tipo === "ordenar") api = montarOrdenar(corpo, dados, resposta, mudou);
  else api = montarMultipla(corpo, dados, resposta, mudou);

  if (dados.verificar) {
    var rodape = el("div", "iq-rodape");
    rodape.appendChild(botao("iq-btn iq-btn-verificar", "Verificar", verificar));
    cartao.appendChild(rodape);
  }
  cartao.appendChild(feedback);

  // Qualquer mudança apaga o resultado do "Verificar" anterior
  function mudou() {
    if (opcoes.aoMudar) opcoes.aoMudar();
    if (!marcado) return;
    marcado = false;
    api.limparMarcas();
    feedback.hidden = true;
    feedback.className = "iq-feedback";
  }

  function verificar() {
    if (!api.completo()) {
      mostrarFeedback("aviso", "Complete o exercício antes de verificar.");
      return;
    }
    marcado = true;
    var n = api.marcar(); // marca certo/errado em cada item e devolve a nota
    if (n === 1) {
      mostrarFeedback("certo", "Muito bem! Está tudo certo.", dados.explicacao);
    } else {
      mostrarFeedback("errado", "Quase lá! Revise os itens marcados em vermelho e tente de novo.");
    }
  }

  function mostrarFeedback(tipoMsg, texto, explicacao) {
    feedback.className = "iq-feedback iq-feedback-" + tipoMsg;
    feedback.textContent = "";
    feedback.appendChild(el("strong", null, texto));
    if (explicacao) feedback.appendChild(el("p", "iq-explicacao", explicacao));
    feedback.hidden = false;
  }

  return { resposta: api.resposta, nota: api.nota, completo: api.completo };
}

// Sequência: várias questões, uma abaixo da outra, na mesma página (o aluno só desce a tela).
// Nota = média das questões. Cada questão tem o seu próprio "Verificar".
function criarVisaoSequencia(raiz, seq) {
  raiz.textContent = "";
  var visoes = [];

  var topo = el("div", "iq-seq-topo");
  if (seq.titulo) topo.appendChild(el("h2", "iq-seq-titulo", seq.titulo));
  var progressoTexto = el("div", "iq-seq-progresso-texto");
  var barra = el("div", "iq-seq-barra");
  var preenchimento = el("div", "iq-seq-barra-preenchida");
  barra.appendChild(preenchimento);
  topo.appendChild(progressoTexto);
  topo.appendChild(barra);
  raiz.appendChild(topo);

  if (!seq.itens.length) raiz.appendChild(el("p", "iq-vazio", "Esta sequência ainda não tem questões."));

  seq.itens.forEach(function (item, i) {
    var bloco = el("section", "iq-seq-item");
    bloco.setAttribute("aria-label", "Questão " + (i + 1));
    raiz.appendChild(bloco);
    visoes.push(criarVisaoAluno(bloco, item, item.resposta, { numero: i + 1, aoMudar: atualizarProgresso }));
  });
  atualizarProgresso();

  function atualizarProgresso() {
    var feitas = visoes.filter(function (v) { return v.completo(); }).length;
    progressoTexto.textContent = feitas + " de " + visoes.length + " questões respondidas";
    preenchimento.style.width = (visoes.length ? Math.round(feitas / visoes.length * 100) : 0) + "%";
  }

  return {
    resposta: function () { return { respostas: visoes.map(function (v) { return v.resposta(); }) }; },
    nota: function () {
      if (!visoes.length) return 0;
      var soma = visoes.reduce(function (s, v) { return s + v.nota(); }, 0);
      return Math.round(soma / visoes.length * 100) / 100;
    }
  };
}

// Monta a tela do aluno para uma questão ou para uma sequência
function criarVisao(raiz, d) {
  return d.tipo === "sequencia" ? criarVisaoSequencia(raiz, d) : criarVisaoAluno(raiz, d, d.resposta);
}

// ---------- Múltipla escolha ----------

function montarMultipla(corpo, dados, resposta, mudou) {
  var escolha = typeof resposta.escolha === "number" ? resposta.escolha : -1;

  if (dados.codigo) corpo.appendChild(blocoCodigo(dados.codigo));

  var opcoes = el("div", "iq-opcoes");
  opcoes.setAttribute("role", "radiogroup");
  corpo.appendChild(opcoes);

  var botoes = [];
  if (!dados.alternativas.length) opcoes.appendChild(el("em", "iq-vazio", "Esta pergunta ainda não tem alternativas."));

  dados.alternativas.forEach(function (texto, i) {
    var b = botao("iq-opcao", null, function () { escolher(i); });
    b.setAttribute("role", "radio");
    b.appendChild(el("span", "iq-opcao-letra", String.fromCharCode(65 + i)));
    b.appendChild(el("span", "iq-opcao-texto", texto));
    opcoes.appendChild(b);
    botoes.push(b);
  });
  atualizar();

  function escolher(i) { escolha = i; atualizar(); mudou(); }
  function atualizar() {
    botoes.forEach(function (b, i) {
      b.classList.toggle("is-escolhida", i === escolha);
      b.setAttribute("aria-checked", i === escolha ? "true" : "false");
    });
  }
  function nota() { return escolha >= 0 && escolha === dados.correta ? 1 : 0; }

  return {
    resposta: function () { return { escolha: escolha }; },
    nota: nota,
    completo: function () { return escolha >= 0; },
    marcar: function () {
      if (botoes[escolha]) botoes[escolha].classList.add(nota() ? "is-certo" : "is-errado");
      return nota();
    },
    limparMarcas: function () { botoes.forEach(function (b) { b.classList.remove("is-certo", "is-errado"); }); }
  };
}

// ---------- Completar o código ----------

function montarLacunas(corpo, dados, resposta, mudou) {
  var partes = partesLacunas(dados.codigo);
  var certas = respostasLacunas(dados.codigo);

  // Banco de peças: respostas + distratores, embaralhados. Cada peça tem um id fixo.
  var pecas = embaralhar(certas.concat(dados.distratores.filter(function (d) { return d !== ""; })))
    .map(function (texto, id) { return { id: id, texto: texto, usada: false }; });
  var lacunas = certas.map(function () { return null; }); // id da peça em cada lacuna

  // Restaura o preenchimento salvo (pelo texto, usando peças ainda livres)
  if (resposta.preenchimento) {
    resposta.preenchimento.forEach(function (texto, i) {
      if (i >= lacunas.length || texto === null || texto === undefined) return;
      var p = pecas.find(function (p) { return !p.usada && p.texto === texto; });
      if (p) { p.usada = true; lacunas[i] = p.id; }
    });
  }

  var codigo = el("pre", "iq-codigo");
  corpo.appendChild(codigo);
  var banco = el("div", "iq-banco");
  banco.setAttribute("aria-label", "Peças disponíveis");
  corpo.appendChild(banco);

  var slots = [];
  var n = 0;
  partes.forEach(function (p) {
    if ("texto" in p) { codigo.appendChild(document.createTextNode(p.texto)); return; }
    var indice = n++;
    var s = botao("iq-lacuna", null, function () { esvaziar(indice); });
    codigo.appendChild(s);
    slots.push(s);
  });
  if (!slots.length) corpo.insertBefore(el("em", "iq-vazio", "Este código ainda não tem lacunas."), banco);

  desenhar();

  function peca(id) { return pecas[id]; }

  function colocar(id) {
    var livre = lacunas.indexOf(null);
    if (livre < 0) return; // todas preenchidas: toque numa lacuna para liberar
    lacunas[livre] = id;
    peca(id).usada = true;
    desenhar(); mudou();
  }
  function esvaziar(i) {
    if (lacunas[i] === null) return;
    peca(lacunas[i]).usada = false;
    lacunas[i] = null;
    desenhar(); mudou();
  }

  function desenhar() {
    slots.forEach(function (s, i) {
      var id = lacunas[i];
      s.textContent = id === null ? "" : peca(id).texto;
      s.classList.toggle("is-vazia", id === null);
      s.setAttribute("aria-label", id === null ? "Lacuna " + (i + 1) + " vazia" : "Lacuna " + (i + 1) + ": " + peca(id).texto + " (toque para remover)");
    });
    banco.textContent = "";
    pecas.forEach(function (p) {
      var b = botao("iq-peca", p.texto, function () { colocar(p.id); });
      b.disabled = p.usada;
      if (p.usada) b.classList.add("is-usada");
      banco.appendChild(b);
    });
  }

  function texto(i) { return lacunas[i] === null ? null : peca(lacunas[i]).texto; }
  function certo(i) { return texto(i) !== null && texto(i) === certas[i]; }

  return {
    resposta: function () { return { preenchimento: lacunas.map(function (_, i) { return texto(i); }) }; },
    nota: function () {
      if (!certas.length) return 0;
      var acertos = certas.filter(function (_, i) { return certo(i); }).length;
      return Math.round(acertos / certas.length * 100) / 100;
    },
    completo: function () { return lacunas.indexOf(null) < 0; },
    marcar: function () {
      slots.forEach(function (s, i) { s.classList.add(certo(i) ? "is-certo" : "is-errado"); });
      return certas.every(function (_, i) { return certo(i); }) ? 1 : 0;
    },
    limparMarcas: function () { slots.forEach(function (s) { s.classList.remove("is-certo", "is-errado"); }); }
  };
}

// ---------- Ordenar linhas ----------

function montarOrdenar(corpo, dados, resposta, mudou) {
  var linhas = dados.linhas;
  var ordem = null;
  var mexeu = false; // o aluno já moveu alguma linha (só então a questão conta como respondida)
  if (resposta.ordem && resposta.ordem.length === linhas.length &&
      resposta.ordem.slice().sort(function (a, b) { return a - b; }).every(function (v, i) { return v === i; })) {
    ordem = resposta.ordem.slice(); // ordem salva é válida
    mexeu = true;
  } else {
    ordem = ordemEmbaralhada(linhas);
  }

  var listaEl = el("ol", "iq-linhas");
  corpo.appendChild(listaEl);
  if (!linhas.length) corpo.appendChild(el("em", "iq-vazio", "Este exercício ainda não tem linhas."));

  var arrastando = null; // posição da linha sendo arrastada

  desenhar();

  function mover(de, para) {
    if (para < 0 || para >= ordem.length || de === para) return;
    mexeu = true;
    var item = ordem.splice(de, 1)[0];
    ordem.splice(para, 0, item);
    desenhar(); mudou();
  }

  function desenhar() {
    listaEl.textContent = "";
    ordem.forEach(function (idx, pos) {
      var li = el("li", "iq-linha");
      li.draggable = true;
      li.appendChild(el("span", "iq-alca", "⋮⋮")).setAttribute("aria-hidden", "true");
      li.appendChild(el("code", "iq-linha-codigo", linhas[idx]));
      var acoes = el("span", "iq-linha-acoes");
      var cima = botao("iq-mover", "↑", function () { mover(pos, pos - 1); });
      cima.setAttribute("aria-label", "Subir linha");
      cima.disabled = pos === 0;
      var baixo = botao("iq-mover", "↓", function () { mover(pos, pos + 1); });
      baixo.setAttribute("aria-label", "Descer linha");
      baixo.disabled = pos === ordem.length - 1;
      acoes.appendChild(cima); acoes.appendChild(baixo);
      li.appendChild(acoes);

      // Arrastar e soltar (computador); no celular, as setas fazem o mesmo
      li.addEventListener("dragstart", function (e) {
        arrastando = pos; li.classList.add("is-arrastando");
        e.dataTransfer.effectAllowed = "move";
        try { e.dataTransfer.setData("text/plain", String(pos)); } catch (err) {}
      });
      li.addEventListener("dragend", function () { arrastando = null; li.classList.remove("is-arrastando"); });
      li.addEventListener("dragover", function (e) { e.preventDefault(); li.classList.add("is-alvo"); });
      li.addEventListener("dragleave", function () { li.classList.remove("is-alvo"); });
      li.addEventListener("drop", function (e) {
        e.preventDefault(); li.classList.remove("is-alvo");
        if (arrastando !== null) mover(arrastando, pos);
      });
      listaEl.appendChild(li);
    });
  }

  function certo(pos) { return linhas[ordem[pos]] === linhas[pos]; }

  return {
    resposta: function () { return { ordem: ordem.slice() }; },
    nota: function () {
      if (!linhas.length) return 0;
      var acertos = ordem.filter(function (_, pos) { return certo(pos); }).length;
      return Math.round(acertos / linhas.length * 100) / 100;
    },
    completo: function () { return linhas.length > 0 && mexeu; },
    marcar: function () {
      Array.prototype.forEach.call(listaEl.children, function (li, pos) { li.classList.add(certo(pos) ? "is-certo" : "is-errado"); });
      return ordemCorreta(linhas, ordem) ? 1 : 0;
    },
    limparMarcas: function () {
      Array.prototype.forEach.call(listaEl.children, function (li) { li.classList.remove("is-certo", "is-errado"); });
    }
  };
}

function blocoCodigo(codigo) {
  return el("pre", "iq-codigo", codigo);
}

// ======================================================================
// Modo professor (autoria)
// ======================================================================

var tipoAutoria = "multipla";

function selecionarTipoAutoria(tipo) {
  tipoAutoria = TIPOS[tipo] ? tipo : "multipla";
  document.querySelectorAll(".iq-tipo").forEach(function (b) {
    var ativo = b.getAttribute("data-tipo") === tipoAutoria;
    b.classList.toggle("is-ativo", ativo);
    b.setAttribute("aria-pressed", ativo ? "true" : "false");
  });
  document.querySelectorAll(".iq-secao-tipo").forEach(function (s) {
    s.hidden = s.getAttribute("data-tipo") !== tipoAutoria;
  });
  atualizarPrevia();
}

function criarLinhaAlternativaProfessor(texto, correta) {
  var row = el("div", "autoria-alt-row");

  var radio = document.createElement("input");
  radio.type = "radio";
  radio.name = "autoria-correta";
  radio.className = "autoria-alt-correta";
  radio.title = "Marcar como alternativa correta";
  radio.checked = !!correta;
  radio.addEventListener("change", atualizarPrevia);

  var textoInput = document.createElement("input");
  textoInput.type = "text";
  textoInput.className = "autoria-alt-texto";
  textoInput.placeholder = "Texto da alternativa";
  textoInput.value = texto || "";
  textoInput.addEventListener("input", atualizarPrevia);

  var btnRemover = botao("autoria-alt-remover", "✕", function () { removerAlternativaProfessor(row); });
  btnRemover.title = "Remover alternativa";

  row.appendChild(radio);
  row.appendChild(textoInput);
  row.appendChild(btnRemover);
  document.getElementById("lista-alternativas-professor").appendChild(row);
}

function removerAlternativaProfessor(row) {
  var listaEl = document.getElementById("lista-alternativas-professor");
  if (listaEl.children.length <= MIN_ALTERNATIVAS) {
    alert("É preciso ter pelo menos " + MIN_ALTERNATIVAS + " alternativas.");
    return;
  }
  listaEl.removeChild(row);
  atualizarPrevia();
}

function adicionarAlternativaProfessor() {
  var listaEl = document.getElementById("lista-alternativas-professor");
  if (listaEl.children.length >= MAX_ALTERNATIVAS) {
    alert("Máximo de " + MAX_ALTERNATIVAS + " alternativas.");
    return;
  }
  criarLinhaAlternativaProfessor("", false);
  atualizarPrevia();
}

function preencherAlternativasProfessor(alternativas, correta) {
  var listaEl = document.getElementById("lista-alternativas-professor");
  listaEl.textContent = "";
  if (!alternativas || alternativas.length === 0) {
    criarLinhaAlternativaProfessor("", true);
    criarLinhaAlternativaProfessor("", false);
    return;
  }
  for (var i = 0; i < alternativas.length; i++) criarLinhaAlternativaProfessor(alternativas[i], i === correta);
}

function linhasDoTexto(texto) {
  return (texto || "").replace(/\r/g, "").split("\n").filter(function (l) { return l.trim() !== ""; });
}

// Lê o formulário do professor e devolve o exercício
function exercicioDaAutoria() {
  var v = function (id) { return document.getElementById(id).value; };
  var d = exercicioVazio();
  d.tipo = tipoAutoria;
  d.enunciado = v("autoria-enunciado").trim();
  d.explicacao = v("autoria-explicacao").trim();
  d.verificar = document.getElementById("autoria-verificar").checked;

  if (d.tipo === "multipla") {
    d.codigo = v("autoria-codigo-multipla").replace(/\s+$/, "");
    var rows = document.querySelectorAll("#lista-alternativas-professor .autoria-alt-row");
    for (var i = 0; i < rows.length; i++) {
      d.alternativas.push(rows[i].querySelector(".autoria-alt-texto").value.trim());
      if (rows[i].querySelector(".autoria-alt-correta").checked) d.correta = i;
    }
  } else if (d.tipo === "lacunas") {
    d.codigo = v("autoria-codigo-lacunas").replace(/\s+$/, "");
    d.distratores = linhasDoTexto(v("autoria-distratores")).map(function (s) { return s.trim(); });
  } else {
    d.linhas = linhasDoTexto(v("autoria-linhas"));
  }
  return d;
}

function preencherAutoria(d) {
  document.getElementById("autoria-enunciado").value = d.enunciado;
  document.getElementById("autoria-explicacao").value = d.explicacao;
  document.getElementById("autoria-verificar").checked = d.verificar;
  document.getElementById("autoria-codigo-multipla").value = d.tipo === "multipla" ? d.codigo : "";
  document.getElementById("autoria-codigo-lacunas").value = d.tipo === "lacunas" ? d.codigo : "";
  document.getElementById("autoria-distratores").value = d.distratores.join("\n");
  document.getElementById("autoria-linhas").value = d.linhas.join("\n");
  preencherAlternativasProfessor(d.alternativas, d.correta);
  selecionarTipoAutoria(d.tipo);
}

// Avisos de preenchimento para o professor (não impedem salvar)
function avisosAutoria(d) {
  var avisos = [];
  if (!d.enunciado) avisos.push("Escreva o enunciado.");
  if (d.tipo === "multipla") {
    if (d.alternativas.filter(function (a) { return a; }).length < MIN_ALTERNATIVAS) avisos.push("Preencha pelo menos " + MIN_ALTERNATIVAS + " alternativas.");
    if (d.correta < 0) avisos.push("Marque a alternativa correta.");
  } else if (d.tipo === "lacunas") {
    if (!respostasLacunas(d.codigo).length) avisos.push("Marque pelo menos uma lacuna com [[resposta]] no código.");
  } else if (d.linhas.length < 2) {
    avisos.push("Escreva pelo menos 2 linhas, na ordem correta.");
  }
  return avisos;
}

// ---------- Sequência de questões (várias questões numa página) ----------

var formatoAutoria = "unica";             // "unica" | "sequencia"
var seqAutoria = { titulo: "", itens: [] };
var editandoItem = -1;                    // questão da sequência aberta no editor (-1 = nenhuma)

function resumoItem(d) {
  return d.enunciado || (d.tipo === "ordenar" ? d.linhas[0] : d.codigo) || "(sem enunciado)";
}

// Guarda no array a questão que está aberta no editor (se houver)
function guardarItemEmEdicao() {
  if (formatoAutoria === "sequencia" && editandoItem >= 0) seqAutoria.itens[editandoItem] = exercicioDaAutoria();
}

function selecionarFormatoAutoria(formato) {
  if (formato === formatoAutoria) return;
  if (formato === "sequencia") {
    // a questão única vira a primeira questão da sequência (se tiver algo)
    var atual = exercicioDaAutoria();
    seqAutoria = { titulo: "", itens: (atual.enunciado || atual.codigo || atual.linhas.length) ? [atual] : [] };
    editandoItem = -1;
  } else {
    guardarItemEmEdicao();
    if (seqAutoria.itens.length > 1 && !confirm("Voltar para uma questão só? Apenas a primeira questão da sequência será mantida.")) return;
    preencherAutoria(seqAutoria.itens[0] || exercicioVazio());
    editandoItem = -1;
  }
  formatoAutoria = formato;
  desenharFormatoAutoria();
}

function desenharFormatoAutoria() {
  var seq = formatoAutoria === "sequencia";
  document.querySelectorAll(".iq-formato").forEach(function (b) {
    var ativo = b.getAttribute("data-formato") === formatoAutoria;
    b.classList.toggle("is-ativo", ativo);
    b.setAttribute("aria-pressed", ativo ? "true" : "false");
  });
  document.getElementById("iq-seq-painel").hidden = !seq;
  document.getElementById("iq-editor-item").hidden = seq && editandoItem < 0;
  document.getElementById("iq-editor-acoes").hidden = !seq;
  document.getElementById("iq-editor-titulo").textContent = seq ? "Editando a questão " + (editandoItem + 1) : "";
  document.getElementById("iq-editor-titulo").hidden = !seq;
  if (seq) document.getElementById("autoria-seq-titulo").value = seqAutoria.titulo;
  desenharListaSequencia();
  atualizarPrevia();
}

function desenharListaSequencia() {
  var listaEl = document.getElementById("iq-seq-lista");
  listaEl.textContent = "";
  if (!seqAutoria.itens.length) listaEl.appendChild(el("li", "iq-seq-vazia", "Nenhuma questão ainda. Use \"+ Adicionar questão\"."));
  seqAutoria.itens.forEach(function (d, i) {
    var li = el("li", "iq-seq-linha" + (i === editandoItem ? " is-editando" : ""));
    var info = el("div", "iq-seq-info");
    info.appendChild(el("span", "iq-seq-num", String(i + 1)));
    info.appendChild(el("span", "iq-selo iq-selo-" + d.tipo, TIPOS[d.tipo].rotulo));
    info.appendChild(el("span", "iq-seq-resumo", resumoItem(d)));
    li.appendChild(info);
    var acoes = el("div", "iq-seq-acoes");
    var cima = botao("iq-mini", "↑", function () { moverItem(i, i - 1); }); cima.disabled = i === 0; cima.title = "Subir";
    var baixo = botao("iq-mini", "↓", function () { moverItem(i, i + 1); }); baixo.disabled = i === seqAutoria.itens.length - 1; baixo.title = "Descer";
    acoes.appendChild(cima); acoes.appendChild(baixo);
    acoes.appendChild(botao("iq-mini iq-mini-texto", i === editandoItem ? "Editando" : "Editar", function () { editarItem(i); }));
    acoes.appendChild(botao("iq-mini iq-mini-perigo", "Excluir", function () { excluirItem(i); }));
    li.appendChild(acoes);
    listaEl.appendChild(li);
  });
}

function editarItem(i) {
  guardarItemEmEdicao();
  editandoItem = i;
  preencherAutoria(seqAutoria.itens[i]);
  desenharFormatoAutoria();
  document.getElementById("iq-editor-item").scrollIntoView({ behavior: "smooth", block: "start" });
}

function adicionarItem() {
  guardarItemEmEdicao();
  seqAutoria.itens.push(exercicioVazio());
  editarItem(seqAutoria.itens.length - 1);
}

function concluirItem() {
  guardarItemEmEdicao();
  editandoItem = -1;
  desenharFormatoAutoria();
}

function moverItem(de, para) {
  if (para < 0 || para >= seqAutoria.itens.length) return;
  guardarItemEmEdicao();
  var item = seqAutoria.itens.splice(de, 1)[0];
  seqAutoria.itens.splice(para, 0, item);
  if (editandoItem === de) editandoItem = para;
  else if (editandoItem === para) editandoItem = de;
  desenharFormatoAutoria();
}

function excluirItem(i) {
  if (!confirm("Excluir a questão " + (i + 1) + "?")) return;
  guardarItemEmEdicao();
  seqAutoria.itens.splice(i, 1);
  if (editandoItem === i) editandoItem = -1;
  else if (editandoItem > i) editandoItem--;
  desenharFormatoAutoria();
}

// O que o professor está montando agora (questão única ou sequência, já com a edição em curso)
function conteudoDaAutoria() {
  if (formatoAutoria !== "sequencia") return exercicioDaAutoria();
  var itens = seqAutoria.itens.slice();
  if (editandoItem >= 0) itens[editandoItem] = exercicioDaAutoria();
  return { tipo: "sequencia", titulo: document.getElementById("autoria-seq-titulo").value.trim(), itens: itens };
}

// Pré-visualização ao vivo de como o aluno verá o exercício
var temporizadorPrevia = null;
function atualizarPrevia() {
  clearTimeout(temporizadorPrevia);
  temporizadorPrevia = setTimeout(function () {
    var d = conteudoDaAutoria();
    var avisos = [];
    if (d.tipo === "sequencia") {
      if (!d.itens.length) avisos.push("Adicione pelo menos uma questão.");
      d.itens.forEach(function (item, i) {
        avisosAutoria(item).forEach(function (a) { avisos.push("Questão " + (i + 1) + ": " + a); });
      });
    } else avisos = avisosAutoria(d);
    var caixa = document.getElementById("autoria-avisos");
    caixa.textContent = "";
    avisos.forEach(function (a) { caixa.appendChild(el("li", null, a)); });
    caixa.hidden = avisos.length === 0;
    criarVisao(document.getElementById("previa-aluno"), d);
  }, 150);
}

function inicializarAutoria() {
  document.querySelectorAll(".iq-tipo").forEach(function (b) {
    b.addEventListener("click", function () { selecionarTipoAutoria(b.getAttribute("data-tipo")); });
  });
  document.querySelectorAll(".iq-formato").forEach(function (b) {
    b.addEventListener("click", function () { selecionarFormatoAutoria(b.getAttribute("data-formato")); });
  });
  ["autoria-enunciado", "autoria-explicacao", "autoria-codigo-multipla", "autoria-codigo-lacunas",
   "autoria-distratores", "autoria-linhas", "autoria-seq-titulo"].forEach(function (id) {
    document.getElementById(id).addEventListener("input", atualizarPrevia);
  });
  document.getElementById("autoria-verificar").addEventListener("change", atualizarPrevia);
  document.getElementById("btn-add-questao").addEventListener("click", adicionarItem);
  document.getElementById("btn-concluir-questao").addEventListener("click", concluirItem);
  preencherAlternativasProfessor([], -1);
  selecionarTipoAutoria("multipla");
  desenharFormatoAutoria();
}

// ======================================================================
// Contrato iLM
// ======================================================================

// Chamada pelo integrador quando existe conteúdo já salvo para carregar
// (professor reabrindo para editar, ou aluno abrindo para responder/revisar).
function setExerciseContent(contentStr) {
  console.log("./js/iquestion.js: setExerciseContent(): #chars=" + (contentStr ? contentStr.length : 0));
  if (!contentStr) return;

  dadosExercicio = parseConteudo(contentStr);

  if (iLMparameters.iLM_PARAM_Authoring) {
    if (dadosExercicio.tipo === "sequencia") {
      formatoAutoria = "sequencia";
      seqAutoria = { titulo: dadosExercicio.titulo, itens: dadosExercicio.itens };
      editandoItem = -1;
      desenharFormatoAutoria();
    } else {
      formatoAutoria = "unica";
      preencherAutoria(dadosExercicio);
      desenharFormatoAutoria();
    }
  } else {
    visaoAluno = criarVisao(document.getElementById("area-aluno"), dadosExercicio);
  }
}

// Exercício (ou sequência) sem os campos de resposta do aluno (o "gabarito")
function gabarito(d) {
  if (d.tipo === "sequencia") return { tipo: "sequencia", titulo: d.titulo, itens: d.itens.map(gabarito) };
  var g = { tipo: d.tipo, enunciado: d.enunciado, explicacao: d.explicacao, verificar: d.verificar };
  if (d.tipo === "multipla") { g.codigo = d.codigo; g.alternativas = d.alternativas; g.correta = d.correta; }
  else if (d.tipo === "lacunas") { g.codigo = d.codigo; g.distratores = d.distratores; }
  else g.linhas = d.linhas;
  return g;
}

// OBRIGATÓRIA. O iAssign chama isso para saber o que salvar.
function getAnswer() {
  if (iLMparameters.iLM_PARAM_Authoring) return JSON.stringify(gabarito(conteudoDaAutoria()));

  var r = gabarito(dadosExercicio);
  var resp = visaoAluno ? visaoAluno.resposta() : {};
  Object.keys(resp).forEach(function (k) { r[k] = resp[k]; });
  return JSON.stringify(r);
}

// OBRIGATÓRIA. O iAssign chama isso para saber a nota (0 a 1).
function getEvaluation() {
  if (iLMparameters.iLM_PARAM_Authoring) return 1;
  var nota = visaoAluno ? visaoAluno.nota() : 0;
  console.log("./js/iquestion.js: getEvaluation(): nota=" + nota);
  return nota;
}

// ======================================================================
// Tamanho do quadro (iframe) no Moodle
// ======================================================================

// O iAssign cria o iframe com largura/altura fixas (as do cadastro do iLM, ex. 700x500) e sem barra de
// rolagem (scrolling="no"), então tudo que passasse da altura ficava cortado. Como o iLM é servido pelo
// mesmo servidor do Moodle, ele mesmo ajusta o iframe: ocupa a largura disponível e cresce/diminui
// conforme o conteúdo. Fora de um iframe (ou se o navegador não permitir), não faz nada.
function ajustarQuadro() {
  var quadro = null;
  try { quadro = window.frameElement; } catch (e) { return; }
  if (!quadro) return;

  quadro.style.width = "100%";
  quadro.style.maxWidth = "1100px";

  var aplicar = function () {
    var altura = Math.ceil(document.getElementById("ilm-container").getBoundingClientRect().height) + 4;
    if (altura > 0) quadro.style.height = altura + "px";
  };
  aplicar();
  if (window.ResizeObserver) new ResizeObserver(aplicar).observe(document.getElementById("ilm-container"));
  else setInterval(aplicar, 500);
}

// Tela inicial, antes de haver conteúdo salvo (setExerciseContent pode nem ser chamada)
ajustarQuadro();
inicializarAutoria();
document.getElementById("area-aluno").appendChild(el("p", "iq-vazio", "Nenhum exercício carregado ainda."));

console.log("./js/iquestion.js: fim");
