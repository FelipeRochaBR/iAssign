# iQuestion

iLM (Interactive Learning Module) de exercícios curtos de programação para o
iAssign, em três tipos:

- **Múltipla escolha**: enunciado, trecho de código opcional e alternativas.
- **Completar o código**: o aluno preenche lacunas do código tocando nas peças
  de um banco de palavras (respostas certas + peças erradas, embaralhadas).
- **Ordenar linhas**: o aluno coloca em ordem as linhas embaralhadas de um
  programa (arrastando ou com as setas ↑ ↓, que funcionam também no celular).

As questões podem ser usadas sozinhas ou numa **sequência**: várias questões
de qualquer tipo, uma abaixo da outra na mesma página, com barra de progresso;
a nota da sequência é a média das questões.

Se o professor permitir, o aluno pode usar **Verificar** antes de enviar: o
iQuestion marca o que está certo e errado (sem mostrar a resposta) e, quando
tudo está certo, mostra a explicação escrita pelo professor. A nota vai
automaticamente para o iAssign.

## O que é um iLM

Um iLM é um pacote HTML5 autocontido (HTML + CSS + JS) que o iAssign carrega
dentro de um `<iframe>`. O iAssign se comunica com ele de duas formas:

1. **Parâmetros GET na URL do iframe** — lidos em `integration-functions.js`
   via `iLMparameters` (`iLM_PARAM_Authoring`, `iLM_PARAM_Assignment`, etc).
2. **Duas funções JavaScript globais que o iLM é obrigado a implementar**,
   chamadas diretamente pelo iAssign via `window.frames[0].getAnswer()` e
   `window.frames[0].getEvaluation()` (ver `mod/iassign/ilm_handlers/html5.php`).

## Como funciona o iQuestion

- **Modo professor** (`iLM_PARAM_Authoring` presente): o professor escolhe o
  tipo, escreve o enunciado e o conteúdo do tipo, uma explicação opcional e se
  o aluno pode verificar antes de enviar. Ao lado (ou abaixo, em telas
  estreitas) aparece ao vivo como o aluno verá o exercício, e avisos do que
  falta preencher.
  - Completar o código: o código é escrito completo, com cada lacuna marcada
    por colchetes duplos, por exemplo `escreva([[soma]])`.
  - Ordenar linhas: uma instrução por linha, na ordem correta (a indentação é
    mantida).
- **Modo aluno**: a tela do tipo escolhido; a resposta salva é restaurada ao
  reabrir.
- **Avaliação** (0 a 1):
  - múltipla escolha: 1 se acertou, 0 se errou;
  - completar o código: fração das lacunas preenchidas corretamente;
  - ordenar linhas: fração das linhas na posição correta.

### Formato do arquivo salvo

O conteúdo salvo (gabarito, e também a resposta do aluno) é um JSON
serializado como string. Arquivos antigos, sem `"tipo"`, continuam valendo
como múltipla escolha.

```json
{
  "tipo": "lacunas",
  "enunciado": "Complete o programa para mostrar a soma de a e b.",
  "codigo": "inteiro soma = a [[+]] b\nescreva([[soma]])",
  "distratores": ["-", "a"],
  "explicacao": "Somamos com + e mostramos a variável soma.",
  "verificar": true
}
```

Sequência: `{"tipo": "sequencia", "titulo": "...", "itens": [<questões>]}`; na
resposta do aluno, `getAnswer()` acrescenta `"respostas"`, com a resposta de
cada questão (mesmos campos da tabela abaixo), na mesma ordem de `itens`.

Campos por tipo:

| Tipo | Gabarito | Resposta do aluno (acrescentada por `getAnswer()`) |
|---|---|---|
| `multipla` | `alternativas`, `correta`, `codigo` (opcional) | `escolha` (índice) |
| `lacunas` | `codigo` com `[[resposta]]`, `distratores` | `preenchimento` (texto de cada lacuna, ou `null`) |
| `ordenar` | `linhas` (na ordem correta) | `ordem` (índices de `linhas` na ordem montada) |

## Contrato obrigatório (em `iquestion.js`)

- `getAnswer()` → string com o que deve ser salvo no servidor.
  - Modo professor: o JSON da pergunta recém-montada (vira o "gabarito").
  - Modo aluno: o JSON da pergunta + a alternativa escolhida.
- `getEvaluation()` → nota de 0 a 1 (veja "Avaliação" acima). Em modo
  professor sempre `1`.
- `setExerciseContent(contentStr)` → chamada quando já existe um exercício
  salvo para carregar (professor reabrindo para editar, ou aluno abrindo
  para responder/revisar). Recebe o JSON serializado.

Essas funções **precisam estar no escopo global** (não dentro de outra
função), porque o iAssign as chama de fora do iframe.

## Estrutura de arquivos

```
iQuestion/
  index.html                    <- ponto de entrada, carregado no <iframe>
  ilm_style.css
  integration-functions.js      <- boilerplate genérico iLM <-> iAssign (raramente muda)
  iquestion.js                  <- lógica dos três tipos de exercício
  simulador.html                 <- só para testar localmente, NÃO entra no .zip do iAssign
```

Tudo fica direto nesta pasta, sem subpastas.

## Testando fora do Moodle

Basta abrir `index.html` num navegador — sem parâmetros GET, ele cai no
modo aluno sem gabarito (nenhuma alternativa cadastrada ainda). Para montar
uma pergunta:

```
index.html?iLM_PARAM_Authoring=true
```

Repare que **não existe botão de "Salvar" dentro do iLM**. Isso é proposital:
quem chama `getAnswer()`/`getEvaluation()` é sempre o iAssign, de fora do
iframe (é o botão de salvar da atividade no Moodle). Sozinho, o iLM só monta
a tela — ele não tem como se auto-salvar.

### Simulando o fluxo completo (professor → aluno → nota)

Para testar o professor montando a pergunta e o aluno respondendo, sem
precisar do Moodle, use o `simulador.html` incluído nesta pasta — ele faz o
papel do iAssign: carrega o iLM em dois `<iframe>`s (professor e aluno) e
chama `getAnswer()`/`getEvaluation()` de fora, exatamente como a integração
real faz.

**Importante:** o `simulador.html` só funciona servido por um servidor local
(`http://...`), não abrindo o arquivo direto com duplo clique
(`file://...`) — por segurança, o navegador bloqueia um iframe `file://` de
acessar o outro. Isso não é um bug do iQuestion; é assim que o Moodle
funciona de verdade também (sempre via `http`/`https`). Para servir a pasta
localmente, rode num terminal, dentro desta pasta:

```
python3 -m http.server 8000
```

e abra `http://localhost:8000/simulador.html` no navegador. (Se preferir,
qualquer outro servidor estático funciona, como a extensão "Live Server" do
VS Code.)

`simulador.html` não faz parte do pacote do iLM — não inclua esse arquivo no
`.zip` que vai para o iAssign (veja a seção abaixo).

### Simulando um assignment já salvo, isoladamente

```
index.html?iLM_PARAM_Assignment=<url_para_um_arquivo_de_conteudo_json>
```

## Cadastrando no iAssign (como admin)

1. Compacte esta pasta dentro de **uma pasta chamada `iQuestion`**
   (ex.: `iQuestion/index.html`, `iQuestion/iquestion.js`, ...) — o `.zip`
   precisa ter essa pasta na raiz, não os arquivos soltos. Esse nome de pasta
   é o que você vai colocar no campo `file_jar` abaixo. **Não inclua
   `simulador.html`** no zip — ele é só para testar localmente, o iAssign
   não usa esse arquivo.
2. No Moodle: **Administração do site → Plugins → iAssign → iLM manager**
   (`mod/iassign/settings_ilm.php`, ação "add").
3. Preencha o formulário:
   - **name**: `iQuestion`.
   - **version**: versão (ex.: "1.0.0").
   - **file_jar**: `iQuestion`.
   - **file_class**: `index.html`.
   - **extension**: extensão do arquivo de conteúdo/gabarito gerado (ex.: `json`).
   - **width/height**: dimensões do iframe.
   - **evaluate**: sim — o iQuestion se auto-avalia.
   - **editingbehavior**: se a avaliação automática continua funcionando
     depois de reabrir uma resposta já enviada.
   - **submissionbehavior**: 0 = permanece na mesma página após enviar;
     1 = muda de página.
4. Envie o `.zip` no campo de upload.

Depois de cadastrado, o iQuestion fica disponível para o professor escolher
ao criar uma atividade iAssign no curso.
