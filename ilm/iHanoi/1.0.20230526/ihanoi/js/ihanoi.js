/* 
iHanoi
http://www.usp.br/line

The starting point is the "start_iLM()" function that simulates iAssign provinding iLM initiation.

Uso: localhost/ihanoi/index.html?n=3&lang=pt
@TODO ainda nao implementado multi-lingua

@AUTHOR Leo^nidas de Oliveira Branda~o (coord. LInE)

v0.5: 2020/11/22 (novo fundo; evita erro de disco sumir se de=para: nova msg 'msgDeParaIguais'; em "movaHaste(hi)" acresc. "if (topoDe == topoPara)...")
v0.4: 2020/08/03
v0.1: 2020/07/31
v0: 2020/07/28

@calledby index_ihanoi.html : src="js/ihanoi.js" (after src="js/integration-functions.js")

*/

/*
No arquivo HTML que carrega esse JavaScript deve existir as seguintes imagens:
  <img id="fundo" style="display:none;" src="img/img_fundo_hanoi.png" />
  <img id="haste0" style="display:none;" src="img/hasteA.png" />
  <img id="haste1" style="display:none;" src="img/hasteB.png" />
  <img id="haste2" style="display:none;" src="img/hasteC.png" />
  <img id="disco0" style="display:none;" src="img/disk1.png" />
  <img id="disco1" style="display:none;" src="img/disk2.png" />
  <img id="disco2" style="display:none;" src="img/disk3.png" />
  <img id="disco3" style="display:none;" src="img/disk4.png" />
  <img id="disco4" style="display:none;" src="img/disk5.png" />
  <img id="disco5" style="display:none;" src="img/disk6.png" />

Dimensoes e posicionamento das imagens
 Hastes: 325 x 416
  #   Posicao e tamanho dos discos:
  6:  34, 250   294 130
  5:  48, 210   267 130   +14 -40 -27 +0 
  4:  62, 170   240 130   +14 -40 -27 +0 
  3:  76, 130   213 130   +14 -40 -27 +0 
  2:  90,  90   186 130   +14 -40 -27 +0 
  1: 104,  50   159 130   +14 -40 -27 +0  (mas disk1 esta com 160x130)
*/

console.log("./js/ihanoi.js: iHanoi: inicio");

var canvas = ""; // para area de desenho
var context;

var width = 1100;
var height = 530; //L 460;
var posY0  = 290; // posicionamento do disco maior (depende de 'height')

//TODO Internationalize (multi-language)
//TODO Permitir internacionalizar botoes

var buttonRestart = "Restart", buttonReview = "Review", buttonAut = "Automatic", buttonCode = "Code";
var buttonTeacherExerc = "Exercise", buttonRestart = "Restart"; // Reiniciar
var button1D = "1 disk", buttonDisks = "disks"; // , button3D = "3 disks", button4D = "4 disks", button5D = "5 disks", button6D = "6 disk";
var altBtnReiniciar="Restart everything, all disks to rod A", altBtnRever="Review all the movements performed until now",
    altBtnCodigo="See the iHanoi code (extension must be 'ihn')";
var mensagem0 = "Click on the rod region to select the origin, then destination";
var mensagem1_1 = "Congratulation! You succeeded to move all disks with ";
var mensagem1_2 = " movements";
var mensagem2_1 = "It is not allowed to put a bigger disk over a smaller one!";
var mensagem2_2 = " over ";
var mensagem3_1 = "Destination: ";
var mensagem3_2 = " - To new movement, click a new initial rod";
var msgTeste1 = "Congratulations, you successfully moved all the disks to B, but remember, the target is rod C. Used ";                  // 1
var msgTeste2 = "Congratulations, you successfully moved all the disks to B with minimal movements, but the target is C. Used "; // 2
var msgTeste3 = "Congratulations, you successfully moved all the disks to C, but used more movements... Used ";            // 3
var msgTeste4 = "Congratulation!, you successfully moved all the disks to C with minimal movements! It were ";                   // 4
var msgEhExercicio  = "It not possible to change the number of disks! It is an exersice with fixed number of disks.";
var msgReverProx    = "Click on the button 'Review' to see the next movement.";
var msgReverFim     = "The registered movements is finished.";
var msgReverPare    = "Was under review movements, but your manual movement finished the review process!";
var msgDeParaIguais = "To move one disk is necessary that the destination rod different that the origin rod!";
var msgRodEmpty1    = "Rod "; // Rod X is empty! Please, select a rod with some disk"
var msgRodEmpty2    = " is empty! Please, select a rod with some disk";
var msgNowDest1     = "Origin: "; // Origin: X - Now select the destination rod
var msgNowDest2     = " - Now select the destination rod";
var mensagemNM      = "Number of movements: ";

var mensagem = mensagem0; // mensagem inicial

// Positioning of disks on the 3 rods
var matHastes = [ [ 5,  4,  3,  2,  1,  0],   // rod A: stack of disks (id disks in reverse order in rod); rod B and C empties
                  [-1, -1, -1, -1, -1, -1],   // rod B empty
                  [-1, -1, -1, -1, -1, -1] ]; // rod C empty

var vetorMovimentos = []; // array to register all the movements effectuated by the student - set in 'movaHaste(hi)'

// Posicionamentos de coordenadas (x,y) para cada um dos 6 disks (no maximo)
var posTx = [  34,  48,  62,  76, 90, 104 ]; // posicoes x para discos: 6, 5, 4...  +14
var posTy = [ 240, 200, 160, 120, 80,  40 ]; // posicoes y para discos: 6, 5, 4...  +40

var nDiscos = 4; // Default entrar com 4 discos
var contador = 0; // conta numero de movimentos
var posx = [  52,  66, 80, 94 ]; // posicoes x para discos: 6, 5, 4...  +14
var posy = [ 160, 120, 80, 40 ]; // posicoes y para discos: 6, 5, 4...  +40

var posx_HA =  20, posy_HA = 40; // posicao rod A
var posx_HB = 370, posy_HB = 40; // posicao rod A
var posx_HC = 720, posy_HC = 40; // posicao rod A

redefineDiscos(nDiscos); // redefinir 'matHastes[][]'

var topoHasteA = nDiscos-1, topoHasteB = topoHasteC = -1; // indice do disco no topo de cada rod

var txt_iHanoi = "iHanoi"; // coord. (txtTx,txtTy)
var txt_Authoring = "Authoring process: select the number of disks"; // coord. (txtAuthx,txtAuthy)
var LInE = "LInE-IME-USP";
var urlLInE = "www.matematica.br"; //L

var isExercise = false;   // if exercise, then do NOT allow student to use "automatic movements" nor change the number os disks/Caution: the first in 'desenhaBotoes()' it is "undefined"
var isAuthoring = false;  // if authoring, then (is a teacher) only expect him to define the number of disks (from 1 to 6)
var revendo = false;      // durante movements revision, NAO deveria movimentar discos, se o fizer, entao anule revisao!
var exerciseContent = ""; // if exercise, 'integration-functions.js!getiLMContent()' will call this with setExerciseContent(allText)

// Posicionamento para mensagens
var txtTx = 10, txtTy = 20; // txt_iHanoi = "iHanoi"
var txtAuthx = 10, txtAuthy = 40; // txt_Authoring = "Authoring process: select the number of disks"

var txtMX = 10, txtMY = height-55;  // message bar: position (the bottom general messages)
var txtRtX = 165, txtRtY = 45;      // message bar: position
var txtLInEx = width-180, txtLInEy = 20; // LInE-IME-USP
var txtUrlLInEx = width-180, txtUrlLInEy = 40; // www.matematica.br

var tamNMX = 300, tamNMY = 20; // number of movements: size of it
//1 var txtNMX = 2*325+50, txtNMY = height-10; // number of movements
var txtNMX = 120, txtNMY = 20; // position to the message area to presents the number of movements effectuated
var tamX = 1050, tamY = 20;    // size to the bottom general messages (e.g. used to inform the learner that must select the origin rod)

// Gerenciamento de evento: primeiro ou segundo clique?
var clickDe = -1, clickPara = -1; // origem e destino: -1,-1 = nada selecionado; x,-1 = selecionada origem; x,y = selecionadas ambas

// Elementos graficos principais: Fundo + rod + Discos
var imgFundo  = document.getElementById("fundo");
var imgHastes = [ document.getElementById("haste0"), document.getElementById("haste1"), document.getElementById("haste2") ];
var imgDiscos = [ document.getElementById("disco0"), document.getElementById("disco1"), document.getElementById("disco2"),
                  document.getElementById("disco3"), document.getElementById("disco4"), document.getElementById("disco5") ];
var corFundo1 = "#26508c"; // para fundo de mensagem

var autoMov = [];         // List of effectuated movements (to 'preparaAutomatico()')
var indiceMovimento = 0;  // Index of movement i to array 'fazMovimento[]' to 'preparaAutomatico()'
var max = 0;              // Minimal number of movemnts to n disks (2^n) - 1

canvas = document.createElement("canvas");
context = canvas.getContext("2d");
canvas.addEventListener("click", clickCanvas); // add listener to "canvas" (to treat clicks)

// The size of iHanoi ("canvas")
canvas.width = width; canvas.height = height;

document.body.appendChild(canvas); // start area to draw iHanoi on "canvas"


// Auxiliary: to compute the minimal number of movements
function potencia2 (n) {
  var pot = 1, i;
  for (i=0; i<n; i++) pot *= 2;
  return pot;
  }


// Get the rod label (given the index 0 to 2)
function pegaHaste (hi) {
  if (hi==0) return "A";
  if (hi==1) return "B";
  return "C";
  }


// iLM: Important iLM function
// The iAssign will calll this function to get the evaluation of this iLM (over the student answer)
// It must return a number between 0 and 1
function getEvaluation () {
  if (iLMparameters.iLM_PARAM_SendAnswer || iLMparameters.iLM_PARAM_SendAnswer!='false') {
    // Grade calculation: correct answer = 1 (C with minimum), 0.7 (B with minimum), completely wrong = 0
    var aux;
    var nota;
    var minimo = potencia2(nDiscos)-1; // 2^nDiscos-1
    if (topoHasteC+1 == nDiscos) { //  moved all disks to rod C
      if (contador == minimo) { // used minimum number of movements
        nota = 1;
        aux = 1;
        }
      else {
        nota = minimo / contador; // if the number of movements beyond the minimum, smaller is the grade
        aux = 2;
        }
      }
    else {
      if (topoHasteB+1 == nDiscos) { // moved all disks to rod B
        if (contador == minimo) { // used minimum number of movements
          nota = NOTA_MINIMO_B; // target was not rod B, reduce grade
          aux = 3;
          }
        else {
          nota = minimo / contador; // if the number of movements beyond the minimum, smaller is the grade
          aux = 4;
          }
        }
      else {
        nota = 0;
        aux = 5;
        }
      }
    console.log("./js/ihanoi.js: getEvaluation(): grade=" + nota);
    return nota;
    } // if (!iLMparameters.iLM_PARAM_SendAnswer || iLMparameters.iLM_PARAM_SendAnswer == 'true')
  else {
    console.log("./js/ihanoi.js: getEvaluation(): the grade must NOT be sent! Parameter 'iLM_PARAM_SendAnswer' is false!");
    }
  } // function getEvaluation()


// iLM: Important iLM function
// The iAssign will calll this function to get the iLM content (student answer or teacher authoring file)
function getAnswer () {
  var aux, i, j, vfrom, vto;
  var strHead = "# ihanoi: http://www.matematica.br\nDisks: " + nDiscos;
  var strContent = "";
  console.log("./js/ihanoi.js: getAnswer(): contador=" + contador + ", #vetorMovimentos=" + vetorMovimentos.length);
  contador = vetorMovimentos.length;
  if (contador>0) {
    strContent += "\nMovements:"; // accept "Moviments:"
    for (i = 0; i < contador; i++) {
      aux = vetorMovimentos[i]; // vetorMovimentos[]: global defined here (in 'ihanoi.js')
      vfrom = aux[0];
      j = 1;
      while (aux[j]==" ") j++; // allow arbitrary number of space in movement, e.g.: 0  1
      vto = aux[j];
      strContent += "\n" + pegaHaste(eval(vfrom)) + " " + pegaHaste(eval(vto)); // register new movement
      }
    }
  strContent = strHead + "\nSize: " + contador + strContent; // + new String((potencia2(nDiscos))-1)
  return strContent;
  } // function getAnswer()


// To be used with re-evaluation
function teacherAutoEval (data) {
  var nDiscos0;
  nDiscos0 = nDiscos;
  console.log("./js/ihanoi.js: teacherAutoEval(.): " + data);
  // process 'inh' content
  decodeContentFile_ihanoi(data);
  }



// iLM: A model to your iLM to be integrated under iAssign/Moodle
// You can use the following function in your iLM to load the iLM content
// The iAssign will send to your iLM the GET parameter 'iLM_PARAM_Assignment',
// with the file content address (inside in the same server).
// @param iLM_PARAM_Assignment must have the relative address of the iLM content file
function getiLMContent () {
  var msg = "";
  // The parameter "iLM_PARAM_Assignment" will give the file URL, that will be
  // requested through JavaScript method XMLHttpRequest()
  var webPageURL = iLMparameters.iLM_PARAM_Assignment;
  var txtFile;
  var data = -1; // to indicate fail
  console.log("./js/ihanoi.js: getiLMContent(): (1) iLMparameters.iLM_PARAM_Assignment=" + iLMparameters.iLM_PARAM_Assignment); //D
  if (iLMparameters.iLM_PARAM_Assignment == null) { // 'iLM_PARAM_Assignment' is the address to iLM get its content from the server
    console.log("./js/ihanoi.js: getiLMContent(): there is no content file (no file with extension 'ihn')");
    return;
    }

  txtFile = new XMLHttpRequest(); // prepare HTTP connection (to request the IHN content file)

  console.log("./js/ihanoi.js: getiLMContent(): try to get the file under URL: '" + webPageURL + "'");

  // window.location : href = complete URL; pathname = path; hostname = only the server name
  txtFile.open("GET", webPageURL, true); // true=>assyncronous
  txtFile.send(); // can be closed under step 3
  txtFile.responseType = "text"; // Avoid warning: XML Parsing Error: syntax error

  txtFile.onreadystatechange = function () {
    if (txtFile.readyState === 4) { // Makes sure the document is ready to parse.
      if (txtFile.status === 200) { // Makes sure the file exists.
        // Step 3: final
        var nDiscos0;
        nDiscos0 = nDiscos;
        allText = txtFile.responseText;
        texto = allText; // define global 'texto'
        console.log("./js/ihanoi.js: getiLMContent(): # arquivo = " + texto.length);
        // process the content in iHanoi file (with extension 'inh')
	setExerciseContent(allText); // ihanoi.js!setExerciseContent(contentStr)

        if (iLMparameters.iLM_PARAM_TeacherAutoEval != null) { // if 'iLM_PARAM_TeacherAutoEval' is teacher evaluating the activity
          console.log("./js/ihanoi.js: getiLMContent(): entrou em 'iLM_PARAM_TeacherAutoEval'=" + iLMparameters.iLM_PARAM_TeacherAutoEval);
          try {
            iLMparameters.iLM_PARAM_Assignment = iLMparameters.iLM_PARAM_TeacherAutoEval;
            setExercise(false);
          } catch (Error) {
            console.log("./js/ihanoi.js: getiLMContent(): erro ao tentar executar funcao 'getAutoEvalOriginalData()'");
            } // se nao esta' em re-avaliacao => NAO esta' definida 'parent.getAutoEvalOriginalData()'
          // Try to evaluate using the content file under iLMparameters.iLM_PARAM_Assignment
          teacherAutoEval(allText);
          console.log("./js/ihanoi.js: getiLMContent(): final (after load and decode IHN file)");
          return;
          }
        }
      else console.log("./js/ihanoi.js: getiLMContent(): erro txtFile.status=" + txtFile.status); // alert("Error 2"); // Step 2: after 1, it reaches this step
      }
    else console.log("./js/ihanoi.js: getiLMContent(): erro txtFile.readyState=" + txtFile.readyState); // alert("Error 1"); // Step 1: first this step is reached
    } 

  console.log("./js/ihanoi.js: getiLMContent(): final");
  } // function getiLMContent()


//D console.log("iHanoi: apos definir elementos graficos");


// If exercise set attribute 'disabled' as true to buttons: Automatic, Disks1, Disks2, Disks3, Disks4, Disks5, Disks6
function setDisableButtons () {
  var i, element, msg = "";
  var buttonAut = document.getElementById("automatico"); // Button to "Automatic" movement
  var allButtons = document.getElementById("allButtons");;
  console.log("./js/ihanoi.js!setDisableButtons(): inicio");
  allButtons.remove(); // remove from DOM (desenhaBotoes() must reinsert it)
  desenhaBotoes();
  console.log("./js/ihanoi.js!setDisableButtons(): end");
  }


// Register it is an exercise: disable buttons to "Automatic" movements and to chose the number of disks
// Set global ./js/ihanoi.js!isExercise
// @calledby decodificaArquivo(strContent)
function setExercise (valor) {
  console.log("./js/ihanoi.js!setExercise(.): valor=" + valor);
  var buttonAut, element, i;
  // If it is an exersice, then it is NOT allowed to change the number of disk
  if (!valor) { // if defined, then is teacher, allow edit (iLM_PARAM_Authoring)
    isExercise = false;
    return;
    }
  isExercise = true; // by default is exercise
  var msg = "", buttonAut = document.getElementById("automatico");
  msg += "automatico.disabled=" + document.getElementById("automatico").getAttribute("disabled") + " -> ";
  setDisableButtons(); // disable button "Automatic" (that allow to see the solution)
  // buttonAut.disabled = "disabled"; // disable this button
  msg += "button automatico id=" + buttonAut.getAttribute("id") + ", disabled=" + (buttonAut.getAttribute("disabled")?"true":"false");
  msg += " background=" + buttonAut.style.backgroundColor;

  console.log("./js/ihanoi.js: setExercise(.): " + msg);
  } // function setExercise(valor)


// Give the value 0,1,2 by eval if 0,1,2 or value if A,B,C
// To evoid erro in iHanoi file (accept values 0 to 2 and letters 'A' to 'C')
function getRodValue (item) {
  var value, charCode;
  if (item==0) return 0;
  if (item==1) return 1;
  if (item==2) return 2;
  charCode = item.charCodeAt(0);
  if (charCode==65) return 0; // 'A'
  if (charCode==66) return 1; // 'B'
  if (charCode==67) return 2; // 'C'
  console.log("./js/ihanoi.js: getRodValue(" + item + "): Error! item.charCodeAt(0)=" + charCode + ". Will return -1");
  return -1; // error
  }


// Delay to present movements allowing visualization
function sleep (milliseconds) {
  var startSleep = new Date().getTime();
  for (var i = 0; i < 1e7; i++) { // 1e7 = 10^7
    if ((new Date().getTime() - startSleep) > milliseconds) {
      break;
      }
    }
  }



// Decode iHanoi content file
// Format of an iHanoi file (complete): with teacher statement (number of disks and total of movements) and student answer
//    Disks: 2
//    Size: 3
//    Movements:
//    0  1
//    0  2
//    1  2
// @calledby setExerciseContent(contentStr): decodeContentFile_ihanoi(contentStr);
function decodeContentFile_ihanoi (strContent) {
  var linhas = strContent.split("\n");
  var msg = "";
  var nlinhas = linhas.length, nmov;
  var itens, i1, i2;
  var currentLine = 0;
  var strCurrentLine = "";
  console.log("./js/ihanoi.js: decodeContentFile_ihanoi(.): nlinhas = " + nlinhas);
  if (nlinhas>0) {
    strCurrentLine = linhas[currentLine]; // current line
    if (strCurrentLine!="null" && strCurrentLine!=undefined && strCurrentLine.length>33)
       strCurrentLine = strCurrentLine.substring(0, 33); // get     "# ihanoi: http://www.matematica.br"
    if (strCurrentLine == "# ihanoi: http://www.matematica.br") { // 0123456789012345678901234567890123 => 34
      currentLine++;
      strCurrentLine = linhas[currentLine]; // current line: '# ihanoi: http://www.matematica.br'
      }
    strCurrentLine = linhas[currentLine]; // current line: 'Disks: N'
    currentLine++; // current line: '# ihanoi: http://www.matematica.br'
    console.log("./js/ihanoi.js: decodeContentFile_ihanoi(.): wait '# ihanoi: http://www.matematica.br' came '" + strCurrentLine + "'");

    strCurrentLine = linhas[currentLine]; // current line: 'Disks: N'
    itens = strCurrentLine.split(":");
    if (itens[0]=='Disks' || itens[0]=='Discs' || itens[0]=='Numero de discos') // number of disks (version English or Portuguese)
      nDiscos = eval(itens[1]); // change it
    nDiscos0 = nDiscos;
    console.log("./js/ihanoi.js: decodeContentFile_ihanoi(.): wait 'Disks: N' came '" + strCurrentLine + "' -> nDiscos := " + nDiscos);

    currentLine++; // current line: 'Size: K'
    strCurrentLine = linhas[currentLine]; // current line: 'Size: K'
    console.log("./js/ihanoi.js: decodeContentFile_ihanoi(.): wait 'Size: K' came '" + strCurrentLine + "'"); // ignore number of movements
    if (nlinhas>1) { // current line: 'Size: 4'
      iLMparameters.iLM_PARAM_TeacherAutoEval = 1; // set iLM_PARAM_TeacherAutoEval to allow register of movements in vetorMovimentos[]
      itens = linhas[currentLine].split(":");
      nmov = itens[1]; // number of movements effectuated by the learner
      contador = nmov;
      console.log("./js/ihanoi.js: decodeContentFile_ihanoi(.): '" + currentLine + "' => nmov=" + nmov);
      }

    currentLine++; // current line: 'Movements:'
    strCurrentLine = linhas[currentLine]; // current line: 'Movements:'
    console.log("./js/ihanoi.js: decodeContentFile_ihanoi(.): wait 'Movements:' came '" + strCurrentLine + "'");

    if (iLMparameters.iLM_PARAM_Authoring == 'true') { // is teacher - authoring
      var allButtons = document.getElementById("allButtons");;
      console.log("./js/ihanoi.js: decodeContentFile_ihanoi(.): indicates that is the authoring process");
      setExercise(false); // global 'isExercise'  <=> it is un exercise (to be done by the student)
      }
    else { // is student - exercise
      console.log("./js/ihanoi.js: decodeContentFile_ihanoi(.): indicates that it is NOT the authoring proccess (" + iLMparameters.iLM_PARAM_Authoring + ")");
      setExercise(true); // global: indicate that is an exercise
      }

    if (nlinhas>2) { // Movements:\n
      // There are "Movements:", then read (and execute) each one of them

      isExercise = true; // register "is exercise" to preserve the student movements (allowing to "Review" them) //REVER 2023/05/24 XXXXXXXXXXXX
// XXXXXXXXXXXX ver 'vetorMovimentos[]'
// XXXXXXXXXXXX cuidado em 'function reiniciar (nD)' com: if (nD!=-1) vetorMovimentos = [];


      nDiscos0 = nDiscos;
      console.log("./js/ihanoi.js: decodeContentFile_ihanoi(.): calls reiniciar(" + nDiscos + ")");
      reiniciar(); // prepare disks in rod A (all of them - nDiscos - to A)
      currentLine++; // jump line with "Movements:", get the first movement
      contador = 0; // global: count the number of movements
      for (i=currentLine; i<nlinhas; i++) {
        strCurrentLine = linhas[i]; // current line: 'Size: 4'
        console.log("./js/ihanoi.js: decodeContentFile_ihanoi(.): " + (currentLine-i) + " : '" + strCurrentLine + "'");
        itens = linhas[i].split(" ");
        if (itens=="" || itens.length<2) {
          console.log("./js/ihanoi.js: decodeContentFile_ihanoi(.): Error: this file is not in Hanoi format. Line " + i + ": " + linhas[i]);
	  console.log(" * " + strContent);
          return;
          }//decodeContentFile_ihanoi: "0,,1
        i0 = 0; i1 = 1;
        if (itens.length==3) // if we have something like "0 1" with {0,,1}
          i1 = 2;
        clickDe = itens[i0];   // global: origin rod
        clickPara = -1;        // global: destination rod
        //D alert("decodeContentFile_ihanoi: \"" + itens + "\":" + clickDe + " - " + itens[i1]);
        movaHaste(getRodValue(itens[i1])); // move to rod given by eval if 0,1,2 or value if A,B,C
        desenhaTudo(); // redraw all disks and messages
        sleep(ESPERA);
        msg += "\n" + linhas[i];
        }
      }
    clickDe = -1; clickPara = -1;
    } // if (nlinhas>0)
  } // function decodeContentFile_ihanoi(strContent)


// Set the iHanoi content (iHanoi will load the exercise content)
// @calledby getiLMContent()
function setExerciseContent (contentStr) {
  console.log("./js/ihanoi.js!setExerciseContent(.): #contentStr=" + (contentStr!=null && contentStr.length>0?contentStr.length:0));
  if (contentStr!=null && contentStr.length>0) {
    exerciseContent = contentStr; // if exercise, 'integration-functions.js' will call this: getiLMContent() with setExerciseContent(allText) or teacherAutoEval(data) with setExerciseContent(data)
    decodeContentFile_ihanoi(contentStr); // decode iHanoi file content (adjusting iHanoi to its data)
    console.log("./js/ihanoi.js!setExerciseContent(.): final");
    }
  else console.log("./js/ihanoi.js!setExerciseContent(.): NAO chamou decodeContentFile_ihanoi");
  }


// Draw butons: "Restart"  "Review"  "Automatic"  "1 disk"  "2 disk"  "3 disk"  "4 disk"  "5 disk"  "6 disk"
function desenhaBotoes () {
  var strBotoes, aux = "", strButtonLines = "", i;
  var colorAutom = "#5566cc"; // classeBotaoR #5566cc
  var botoes = document.getElementById("botoes");
  var buttonAutom = null, allButtons = document.getElementById("allButtons");
  // if (isExercise == undefined) isExercise = true; // The first entry here is with isExercise undefined! Set default "true"
  console.log("./js/ihanoi.js: desenhaBotoes(.): 1: isExercise=" + (isExercise?"true":isExercise) + ", isAuthoring=" + (isAuthoring?"true":"false"));
  if (isAuthoring) {
    allButtons.remove(); // iLM_PARAM_Authoring=true => remove from DOM (desenhaBotoes() must reinsert it)
    allButtons = null; // rebuild with special button "Exercise"
    }
  if (allButtons == null) {
    aux += " allButtons==null ";
    strLinha0 = '<div class="tableBotoes-celula tableBotoes-tar" id="allButtons">';
    if (isAuthoring) { // Teacher preparing new exercise
      strLinha1 = '   <button id="reiniciar"  class="classeBotao classeBotaoR" title="Build new exercise" onclick="reiniciar(-1);">' + buttonTeacherExerc + '</button> &nbsp;';
      strLinha2 = '   <button id="rever"      class="classeBotao classButtonDisable" title="Disable review" onclick="" disabled>' + buttonReview + '</button> &nbsp;';
      }
    else {
      strLinha1 = '   <button id="reiniciar"  class="classeBotao classeBotaoR" title="Reiniciar as configuracoes" onclick="reiniciar();">' + buttonRestart + '</button> &nbsp;';
      strLinha2 = '   <button id="rever"      class="classeBotao classeBotaoR" title="Rever os movimentos"        onclick="rever();    ">' + buttonReview + '</button> &nbsp;';
      }
    if (isExercise) {
      strLinha3 = '   <button id="automatico" class="classeBotao classButtonDisable" title="Automaticaly solve"   onclick="">' + buttonAut + '</button>&nbsp;&nbsp;&nbsp;&nbsp;';
      strButtonLines += '   <button id="disco1" class="classeBotao classButtonDisable" title="Apenas 1 disco" onclick="" disabled>' + button1D + ' </button> ' + "\n";
      }
    else {
      strLinha3 = '   <button id="automatico" class="classeBotao classeBotaoR" title="Automaticaly solve"   onclick="preparaAutomatico();">' + buttonAut + '</button>&nbsp;&nbsp;&nbsp;&nbsp;';
      strButtonLines += '   <button id="disco1" class="classeBotao classeBotao1" title="Apenas 1 disco" onclick="reiniciar(1);">' + button1D + ' </button> ' + "\n";
      }
    for (i=2; i<7; i++) {
      if (isExercise)
        strButtonLines += '   <button id="disco'+i+'" class="classeBotao classButtonDisable" title="Apenas '+i+' discos" onclick="" disabled>'+i+' disks </button> ' + "\n";
      else
        strButtonLines += '   <button id="disco'+i+'" class="classeBotao classeBotao'+i+'" title="Apenas '+i+' discos" onclick="reiniciar('+i+');">'+i+' disks</button> ' + "\n";
      }
    strBotoes  = strLinha0 + "\n" + strLinha1 + "\n" + strLinha2 + "\n" + strLinha3 + "\n";// + strLinha4 + "\n";
    // strBotoes += strLinha5 + "\n" + strLinha6 + "\n" + strLinha7 + "\n" + strLinha8 + "\n" + strLinha9 + "\n" + strLinhaF;
    strBotoes += strButtonLines + '</div></div>';
    botoes.style.left =  "80px"; // positioning of buttons "Restart | Review | Automatic | 1 disk | ... | 6 disks"
    botoes.style.top  = "490px";
    botoes.innerHTML = strBotoes;
    }
  else aux += " allButtons!=null ";
  if (isExercise) { // is exercise, paint buttons "Automatic", "1 disk" to "6 disks" with another color
    buttonAutom = document.getElementById("automatico"); // button "Automatic"
    aux += " (2) backgroundColor=" + buttonAutom.style.backgroundColor + " -> ";
    buttonAutom.style.backgroundColor = "#6688ee"; // redefine background color of "Automatic" button
    for (i=1; i<7; i++) {
      elementButton = document.getElementById("disco"+i);
      if (elementButton!=null) { // if it re-avaluation to not exists graphical interface (teacher are re-evaluation the solution of all students)
        elementButton.setAttribute("disabled", true); // disable this button
        elementButton.style.backgroundColor = "#6688ee";
        aux += "; disco"+i;
        }
      }
    aux += " (4) backgroundColor=" + buttonAutom.style.backgroundColor + " -> ";
    console.log("./js/ihanoi.js: desenhaBotoes(.): 2: automatico " + aux + buttonAutom.style.backgroundColor);
    }
  } // function desenhaBotoes()


// Redefine the number of disks
// This process is initiated when the learner/user click on any button (from "disk1" to "disk6")
// All disks will be repositioned on rod A
function redefineDiscos (n) {
  var i, dif = 6-n;
  console.log("./js/ihanoi.js: redefineDiscos("+n+"): final");    
  for (i=0; i<n; i++) { // >
    matHastes[0][i] = n-i-1;
    posx[i] = posTx[i+dif];
    posy[i] = posTy[i+dif];
    }
  for (i=n; i<6; i++) { // >
    matHastes[0][i] = -1;
    posx[i] = -1;
    posy[i] = -1;
    }
  desenhaBotoes(); // draw buttons
  }


// Start --- To review all the movements effectuated until now
var reverMov = -1;
var totalMov = -1;
var copiaMovimentos = [];

// Erase variable that allow review the movements
// @calledby: rever(), clickCanvas(mouseEvent)
function limparRevisao () {
  revendo = false; // not under revision any more
  reverMov = -1;
  copiaMovimentos = [];
  }

function rever () { // vetorMovimentos = { clickDe + "  " + clickPara, ... }
  console.log("./js/ihanoi.js:");
  if (reverMov == -1) { // starting
    limparRevisao();
    revendo = true; // mark that is under revision process
    totalMov = vetorMovimentos.length;
    for (i=0; i<totalMov; i++) copiaMovimentos.push(vetorMovimentos[i]);
    reverMov = 0;
    reiniciar();
    mensagem = msgReverProx;
    desenhaMensagem();
    revendo = true; // during movements revision, disks can NOT not be manually moved, on contrary, revision process will be canced!
    return;
    }
  if (reverMov == totalMov) { // final
    mensagem = msgReverFim;
    desenhaMensagem();
    totalMov = reverMov = -1; // can review again
    clickDe = clickPara = -1;
    return;
    }
  var para, copia = copiaMovimentos[reverMov];
  itens = copiaMovimentos[reverMov++].split(' ');
  if (itens.length == 3) { clickDe = eval(itens[0]); para = eval(itens[2]); }
  else { clickDe = eval(itens[0]); para = eval(itens[1]); }
  // alert(itens + ": " + itens.length + ": rever: (" + copia + "): " + clickDe + "-" + clickPara);
  console.log(" - " + itens + ", rever: (" + copia + "): " + clickDe + " + " + clickPara + " + " + para); // items
  movaHaste(para); // 'clickPara' must be with -1 to complete the movement
  mensagem = msgReverProx; // click on button "Review" to effectuate the next registered movement
  desenhaTudo();
  console.log(" - rever(): final");
  } // rever()
// End --- To review all the movements effectuated until now


// Restart the "game": prepare disks in rod A (all of them - nDiscos - to A)
// If nD==-1 then it is teacher authoring (iLM_PARAM_Authoring=true)
function reiniciar (nD) {
  if (nD!=-1) vetorMovimentos = []; // global: erase movements
  if (isExercise) { // It is exercise then do not allow to re-start!
    console.log("./js/ihanoi.js: reiniciar(" + nD + "): nDiscos=" + nDiscos + ", nD=" + nD + ", is exercice"); // can't re-start
    // return;
    }
  else console.log("./js/ihanoi.js: reiniciar(" + nD + "): nDiscos=" + nDiscos + ", nD=" + nD);
  if (nD!="" && nD!=undefined && nD!=-1) {
    var element = document.getElementById("disco1"); // this DOM element come from "index_ihanoi.html": <img id="disco1" style="display:none;" src="img/disk2.png" />
    if (false) { //TODO: (element.disabled) // verify if button is disabled (in this case, it is an exercise)
      console.log("./js/ihanoi.js: The number of disks can not be changed!");
      mensagem = msgEhExercicio;
      desenhaMensagem();
      return;
      }
    redefineDiscos(nD);
    nDiscos = nD;
    }
  topoHasteA = nDiscos-1;
  topoHasteB = topoHasteC = -1;
  for (i=0; i<nDiscos; i++) { // >
    matHastes[1][i] = -1;
    matHastes[2][i] = -1;
    }
  contador = 0;
  redefineDiscos(nDiscos);
  if (nD==-1) { // it is new exercise (from teacher), present the IHN content file
    mensagem0 = "New exercise with " + nDiscos + " disks";
    }
  mensagem = mensagem0;
  desenhaTudo();
  if (nD==-1) { // it is new exercise (from teacher), present the IHN content file
    console.log("./js/ihanoi.js: reiniciar(" + nD + "): teacher authoring, nDiscos=" + nDiscos);
    var strContent = getAnswer(); // "# ihanoi: http://www.matematica.br\nDisks: " + nDiscos + "\nSize: " + new String((potencia2(nDiscos))-1);
    // var strContent = new String("# ihanoi: http://www.matematica.br\nDisks: 4\nSize: 7");
    console.log("./js/ihanoi.js: reiniciar(" + nD + "): teacher authoring\n" + strContent);
    alert("Please, copy the next line in a file with extension 'ihn':\n" + strContent);
    // mensagem0 = "New exercise with " + nDiscos + " disks";
    }
  } // function reiniciar(nD)


// Split GET parameters: ?lang=pt&n=4
// @return { 4, "pt" } in this order
function analisa_parametros_url (strParametros) {
  var vars = strParametros.split("&");
  var vetorParametros = [ 3, "pt" ]; // the standard is Portuguese { 3, "pt" }
  var msg = ""; //D
  var pair, key, value;
  // ?par1=val1&par2=val2&
  for (var i = 0; i < vars.length; i++) { // >
    pair = vars[i].split("=");
    if (pair == "") break;
    key = decodeURIComponent(pair[0]);
    value = decodeURIComponent(pair[1]);
    if (key=="n") {
      vetorParametros[0] = value;
      nDiscos = value; // redefine 'nDiscos'
      redefineDiscos(nDiscos);
      }
    else
    if (key=="lang") 
      vetorParametros[1] = value;
    else
    if (key=="iLM_PARAM_Authoring") { // set the global 'isAuthoring' as true => is teacher preparing exercise
      if (value=="true") isAuthoring = true;
      }
    msg += "("+key+","+value+") "; //D
    }
  return vetorParametros;
  }


// Get parameters via GET
// This emulates the starting point of this iLM (iHanoi)
// @calledby ./index_ihanoi.html : <body onload="start_iLM(); desenhaTudo();">
function start_iLM () {
  // window.location. [ href | protocol | host | hostname | port | pathname | search | hash
  var parametros = window.location.search; // window.location.href = http completo, sem "?..."
  console.log("./js/ihanoi.js: start_iLM(): parametros=" + parametros + ", search=" + window.location.search);
  if (parametros=="undefined" || parametros=="")
    return;
  if (parametros.length>0) // >
    parametros = parametros.substring(1); // erase the first '?' character
  analisa_parametros_url(parametros);
  getiLMContent(); // using "iLMparameters.iLM_PARAM_Assignment" parameter get the content file to be used by this iLM
  }


// To debug process
function imprimeMovimentos (hi) {
  var i;
  var msg, hA = "[", hB = "[", hC = "[";
  for (i=0; i<nDiscos; i++) { // >
    hA += matHastes[0][i] + " ";
    hB += matHastes[1][i] + " ";
    hC += matHastes[2][i] + " ";
    }
  msg = hA + "], " + hB + "], " + hC + "]";
  return msg;
  }


// Get the value of the disk on top of rod 'ind_haste'
// If it empty, return -1
function pegaTopoHaste (ind_haste) { // get the index on top of rod
  // To (little) reduction of execution time, we could use the defined variables with index of top disk in each rod: topoHasteA, topoHasteB, topoHasteC
  var topo, i;
  i=0;
  try {
    while (matHastes[ind_haste][i]!=-1 && i<nDiscos) i++; // > trick to close in some editors
  } catch (err) {
    console.log("./js/ihanoi.js: pegaTopoHaste: Erro! ind_haste=" + ind_haste + ", i=" + i + ", " + err.message);
    }
  return i-1;
  }


// After to move disk betwee 2 rods, adjust variables to top and "cliks"
// Copy on top of destinatin the disk on top of origin
function atualizaTopos (topoDe, topoPara) {
  topoPara++;
  matHastes[clickPara][topoPara] = matHastes[clickDe][topoDe]; // move disk from top of origin to top of destination
  if (matHastes[clickPara][topoPara] == undefined) { console.log("./js/ihanoi.js: atualizaTopos("+topoDe+","+topoPara+"): error! matHastes[clickPara][topoPara] undefined"); }  
  // Remove the top disk from origin rod "clickDe"
  matHastes[clickDe][topoDe] = -1; // remove disk on top of origin rod ("clickDe")
  topoDe--;
  // Update globals
  if (clickDe==0) // rod A
    topoHasteA = topoDe;
  else
  if (clickDe==1) // rod B
    topoHasteB = topoDe;
  else // rod C
    topoHasteC = topoDe;
  if (clickPara==0) // rod A
    topoHasteA = topoPara;
  else
  if (clickPara==1) // rod B
    topoHasteB = topoPara;
  else // rod C
    topoHasteC = topoPara;
  //D alert("atualizaTopos: " + clickDe + " :: " + clickPara + ": " + imprimeMovimentos(clickPara));
  clickDe = clickPara = -1; // lets try to move another disk...
  }
 

// Verify if all disks are in rod C
// @return 0, 1, 2, 3, 4
// where 0=remain some disko; 1=moved all to rod B; 2=moved all to rod B with minimal movements;
//       3=moved all to rod C; 4=moved all to rod C with minimal movements
function movimentoFinal (haste, num) {
  var topo = pegaTopoHaste(haste);
  if (topo == nDiscos-1) { // all disks were moved!
    if (haste == 2) { // moved to rod C
      if (contador == (potencia2(nDiscos))-1) { // moved to rod C with minimal movements
        return 4;
        }
      return 3; // moved to rod C (but not with minimal movements)
      }
    if (haste == 1) { // moved to rod B
      if (contador == (potencia2(nDiscos))-1) { // moved all to rod B with minimal movements
        return 2; // msgTeste2
        }
      return 1; // moved all to rod C (but not with minimal movements)
      }
    }
  return 0;
  }


// Move disk from top of rod 'clickDe' to rod 'hi' (when 'clickDe' is not -1)
// @calledby clickCanvas(mouseEvent)
function movaHaste (hi) {
  var strHaste = pegaHaste(hi); // return "A", "B" or "C"
  var de0 = clickDe, para0 = clickPara;
  console.log("./js/ihanoi.js: movaHaste(" + hi + "): from=" + clickDe + " to=" + clickPara); 
  //  de0=A para0=-1
  if (clickDe==-1 && clickPara==-1) { // first action to any movement: define origin
    clickDe = hi;
    topoDe = pegaTopoHaste(clickDe); // get the disk on top of rod
    if (topoDe==-1) { // has no disk
      mensagem = msgRodEmpty1 + strHaste + msgRodEmpty2; // Rod X is empty! Please, select a rod with some disk
      clickDe = clickPara = -1;
      desenhaMensagem();
      return;
      }
    mensagem = msgNowDest1 + strHaste + msgNowDest2; // Origin: X - Now select the destination rod
    de0 = hi;
    desenhaMensagem();
    }
  else
  if (clickDe>-1 && clickPara==-1) { // selected the destination rod
    clickPara = hi;
    para0 = hi;
    topoDe = pegaTopoHaste(clickDe);     // return the index of the disk on top of rod 'clickDe'
    topoPara = pegaTopoHaste(clickPara); // return the index of the disk on top of rod 'clickPara'
    if (clickDe == clickPara) {
      str_haste = pegaHaste(clickDe); // name of rod: "A", "B" or "C"
      mensagem = msgDeParaIguais + " (rod " + str_haste + ")";
      console.log("./js/ihanoi.js: Error: Trying to move to the same rod! (rod " + str_haste + ")");
      clickDe = clickPara = -1; // lets try to move another disk...
      desenhaMensagem();
      return -1;
      }
    if (topoPara>-1 && matHastes[clickDe][topoDe]>matHastes[clickPara][topoPara]) { // try to move a smaller disk over a bigger one: forbidden!
      mensagem = mensagem2_1 + " (" + matHastes[clickDe][topoDe] + mensagem2_2 + matHastes[clickPara][topoPara] + ")";
      clickDe = clickPara = -1; // lets try to move another disk...
      desenhaMensagem();
      return -1;
      }
    vetorMovimentos.push(clickDe + "  " + clickPara);
    if (topoDe<0) { console.log("./js/ihanoi.js: movaHaste("+hi+"): "+clickDe + "  " + clickPara+": erro! undefined"); } //DEBUG
    atualizaTopos(topoDe, topoPara);
    contador++;

    // 0=remain some disko; 1=moved all to rod B; 2=moved all to rod B with minimal movements;
    // 3=moved all to rod C; 4=moved all to rod C with minimal movements
    respostaMov = movimentoFinal(hi, contador);
    switch (respostaMov) {
      case 0: mensagem = mensagem3_1 + strHaste + mensagem3_2; break;
      case 1: mensagem = msgTeste1 + contador + mensagem1_2; break;
      case 2: mensagem = msgTeste2 + contador + mensagem1_2; break; 
      case 3: mensagem = msgTeste3 + contador + mensagem1_2; break; 
      case 4: mensagem = msgTeste4 + contador + mensagem1_2; break; 
      mensagem = mensagem1_1 + contador + mensagem1_2; // "Congratulation! You succeeded to move all disks with X movements"
      }
    desenhaTudo();
    }
  console.log("./js/ihanoi.js: movaHaste(" + hi + "): final");
  return 1;
  } // function movaHaste(hi)


// Trigger events
function clickCanvas (mouseEvent) {
  var posx = mouseEvent.offsetX, posy = mouseEvent.offsetY; // Posicao do "mouse", valores para parametros de '.drawImage(...)'
  console.log("./js/ihanoi.js: clickCanvas: " + posx + "," + posy);
  if (posx>25 && posx<350 && posy>30 && posy<440) { // > click on rod 1
    resp = movaHaste(0);
    }
  else
  if (posx>350 && posx<690 && posy>30 && posy<440) { // > click on rod 2
    resp = movaHaste(1);
    }
  else
  if (posx>690 && posx<1030 && posy>30 && posy<440) { // > click on rod 3
    resp = movaHaste(2);
    }
  if (revendo) { // it was "Review" movements but clicked on any rod, then cancel the movements revision!
    mensagem = msgReverPare; // "Was under review movements, but your manual movement finished the review process!"
    limparRevisao();
    desenhaMensagem();
    }
  }


// Draw a rectangle (to messages)
function roundRect (ctx, x, y, width, height, radius, fill, stroke) {
  if (typeof stroke == "undefined" ) { stroke = true; }
  if (typeof radius === "undefined") { radius = 5; }
  ctx.beginPath();
  ctx.moveTo(x + radius, y);
  ctx.lineTo(x + width - radius, y);
  ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
  ctx.lineTo(x + width, y + height - radius);
  ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
  ctx.lineTo(x + radius, y + height);
  ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
  ctx.lineTo(x, y + radius);
  ctx.quadraticCurveTo(x, y, x + radius, y);
  ctx.closePath();
  if (stroke) { ctx.stroke(); }
  if (fill) { ctx.fill(); }        
  }


// Drasw all disks in each rod (rod A = matHastes[0][]; rod B = matHastes[1][]; rod C = matHastes[2][])
// The diameter difference between disk i and i+1 is allway  28 pixels, therefore, if the horizontal 
// (initial) position of disk i is 'posx', then position of disk i+1 (that is smaller) is 'posx-14'
// So the the relation "(nDiscos - ind_disk-1)*14"
function desenhaDiscos () { // 'context' e' global
  var posx, posy, i;
  // rod A
  posy = posY0;
  var ind_disk = matHastes[0][0];
  i = 0;
  while (ind_disk!=-1) { // draw disks (while it is not finished, when 'ind_disk' is -1)
    posx = 33 + (6 - ind_disk-1)*14; // para nDiscos=6 : usar 34 + ...
    //TODO: Do you receive the message error?
    // TypeError: Argument 1 of CanvasRenderingContext2D.drawImage could not be converted to any of: HTMLImageElement, SVGImageElement, HTMLCanvasElement, HTMLVideoElement, ImageBitmap.
    context.drawImage(imgDiscos[ind_disk], posx, posy);
    posy -= 40; // next disk is positionated 40 pixels up
    i++;
    ind_disk = matHastes[0][i];
    }
  // rod B
  posy = posY0;
  ind_disk = matHastes[1][0];
  i = 0;
  while (ind_disk!=-1) { // draw disks (while it is not finished, when 'ind_disk' is -1)
    posx = 382 + (6 - ind_disk-1)*14;
    if (ind_disk == undefined) { console.log("./js/ihanoi.js: desenhaDiscos(): disco 1: erro: i=" + i); return; } // alert("desenhaDiscos(): erro: i=" + i);
    // console.log("./js/ihanoi.js: desenhaDiscos(): " + imprimeMovimentos(0)); // + ", " + imprimeMovimentos(1) + ", " + imprimeMovimentos(2));
    context.drawImage(imgDiscos[ind_disk], posx, posy);
    posy -= 40; // next disk is positionated 40 pixels up
    i++;
    ind_disk = matHastes[1][i];
    }
  // rod C
  posy = posY0;
  ind_disk = matHastes[2][0];
  i = 0;
  while (ind_disk!=-1) { // draw disks (while it is not finished, when 'ind_disk' is -1)
    posx = 732 + (6 - ind_disk-1)*14;
    context.drawImage(imgDiscos[ind_disk], posx, posy);
    posy -= 40; // next disk is positionated 40 pixels up
    i++;
    ind_disk = matHastes[2][i];
    }
  console.log("./js/ihanoi.js: desenhaDiscos(): end");
  } // desenhaDiscos()


// Change the informative menssage (e.g. to inform the learner/user that must select the origin rod)
function desenhaMensagem () {
  context.font = 'bold 14px serif';
  context.fillStyle = "white";
  context.fillRect(txtMX, txtMY-15, tamX, tamY);
  context.fillStyle = "black"; //"white";
  context.fillText(" " + mensagem, txtMX, txtMY);
  roundRect(context, txtMX, txtMY-15, tamX, tamY);
  }


// Redraw all graphical components of iHanoi
// @calledby ./index_ihanoi.html : <body onload="start_iLM(); desenhaTudo();">
// @calledby ./js/ihanoi.js: function movaHaste(hi): desenhaTudo();
function desenhaTudo () {
  console.log("./js/ihanoi.js: desenhaTudo(): inicio");
  console.log("./js/ihanoi.js: desenhaTudo(): isAuthoring=" + (isAuthoring?"true":"false"));
  context.font = 'bold 20px serif';
  context.drawImage(imgFundo,   0,  0, width, height );
  context.fillStyle = "white";
  context.fillText(txt_iHanoi, txtTx, txtTy); // Top-left text: "iHanoi"
  if (isAuthoring) { // Indicates authoring process
    context.font = 'normal 16px serif';
    context.fillText(txt_Authoring, txtAuthx, txtAuthy); // Just bellow top-left text: "Authoring process: select the number of disks"
    context.font = 'bold 20px serif';
    }
  // Back ground color to the sky: 5398db
  context.fillStyle = "#5398db";
  context.fillRect(txtLInEx, 0, txtRtX,  txtRtY);     // Back ground color to Lab name: LInE-IME-USP
  context.fillStyle = "white";
  context.fillText(LInE, txtLInEx,       txtLInEy);    // LInE-IME-USP
  context.font = 'bold 14px serif';
  context.fillText(urlLInE, txtUrlLInEx, txtUrlLInEy); // www.matematica.br
  
  context.drawImage(imgHastes[0], posx_HA, posy_HA); // position of rod A
  context.drawImage(imgHastes[1], posx_HB, posy_HB); // B
  context.drawImage(imgHastes[2], posx_HC, posy_HC); // C
  context.font = 'bold 14px serif';
  context.fillStyle = "white"; // back ground color the the menssages
  context.fillRect(txtMX, txtMY-15, tamX, tamY);   // Messages
  roundRect(context, txtMX, txtMY-15, tamX, tamY); // Messages
  context.fillRect(txtNMX, txtNMY-15, tamNMX, tamNMY);   // Number of movements
  roundRect(context, txtNMX, txtNMY-15, tamNMX, tamNMY); // Number of movements
  context.fillStyle = "black"; //"white";
  context.fillText(" " + mensagem, txtMX, txtMY); // mensagens
  context.fillText(" " + mensagemNM + contador, txtNMX, txtNMY); // Number of movements
  desenhaDiscos();
  if (isAuthoring) { // global defined here: 'isAuthoring' <=> it is the teacher preparing one exercise (that will be done by the student)
    //allButtons.remove(); // remove from DOM (desenhaBotoes() must reinsert it)
    desenhaBotoes();
    }

  // context1.drawImage(canvas, 0, 0); //DB
  console.log("./js/ihanoi.js: desenhaTudo(): final");
  return true;
  } // desenhaTudo()


// Another version to start the iHanoi presentation (build rods, disks...)
// but I used the version with the 'onload' special function on the 'body' tag.
// window.addEventListener("DOMContentLoaded", function () {
//  //D alert("DOMContentLoaded: " + canvas.width + "," + canvas.height);
//  desenhaTudo();
//  });

// -------------------- starting UPDATE TO MOVEMENT --------------------
// Used button "Automatic" (need to define 'velocidade'): solve Towers of Hanoi automatically (by recursion)

// Main function to the automatic solutoion of Towers of Hanoi
// Effectuate the minimal movements
function resolveAutomatico (n, originRod, destinationRod, aux) {
  var element = document.getElementById("automatico"); // get button "Automatic"
  var btnAutDis = element.getAttribute("disabled");
  if (btnAutDis!=null && !btnAutDis) { // not null and has 'false' value
    console.log("./js/ihanoi.js: resolveAutomatico(.): this option is disabled! Button 'automatico' getAttribute=" + element.getAttribute("disabled"));
    return;
    }
  if (n == 1) { // The smallest/bigger disk (there is only one) can be moved to any rod
    autoMov.push(originRod);  // define origin rod ('clickDe')
    autoMov.push(destinationRod); // define destination rod ('clickPara')
    return;
    }
  // Attention: Using the principles 1, 2 and 3 we can prove than this movement procedure will produce the minimal movements.
  // If we define H(n) the formula to the minimal movements, step 1, 2 and 3 can be used to prove that
  //    H(n) = 2*H(n-1) + 1
  // than also can be proved (by inductive process) that H(n) = 2^n - 1
  resolveAutomatico(n-1, originRod, aux, destinationRod); // 1. Free the last disk under rod 'originRod' (with minimal movements)
  autoMov.push(originRod);                                    // 2. Now the bigger disk in rod 'originRod' is free, move it (the minimal times, only one!), from 'clickDe := originRod'
  autoMov.push(destinationRod);                               //    to the rod 'destinationRod' (to 'clickPara := destinationRod')
  resolveAutomatico(n-1, aux, destinationRod, originRod); // 3. Finally, move that n-1 disks (optimally) from auxiliary rod 'aux' to rod 'destinationRod'
  }


// Return all nDiscos to the original rod A (erase movements array 'vetorMovimentos[]' and counter 'contador')
// Run recursive function to automatically moves the disks
function preparaAutomatico () {
  indiceMovimento = 0; //_
  autoMov = []; //_ 
  reiniciar(nDiscos); // prepare disks in rod A (all of them - nDiscos - to A)
  resolveAutomatico(nDiscos, 0, 2 ,1);
  max = autoMov.length - 1;
  setTimeout(fazMovimento, 1000); // help to delay automatic movements
  }

// Perform one movement (using movements array 'autoMov[]')
function fazMovimento () {
  //console.log("./js/ihanoi.js: fazMovimento: indiceMovimento="+indiceMovimento+", autoMov["+indiceMovimento+"]="+autoMov[indiceMovimento]+", autoMov["+(indiceMovimento+1)+"]="+autoMov[indiceMovimento+1]);
  movaHaste(autoMov[indiceMovimento]); // set origin rod (define 'clickDe')
  movaHaste(autoMov[indiceMovimento + 1]); // set destination rod (define 'clickPara'), then effectively effectuate the movement - sorry the effects ;)
  indiceMovimento += 2;
  if (indiceMovimento >= max) return;
  setTimeout(fazMovimento, 1000);
  }
// -------------------- end of UPDATE TO MOVEMENT --------------------

console.log("./js/ihanoi.js: iHanoi: end of iHanoi main JavaScript code"); //D
