/*
 * iVProg - altura automática do quadro (iframe) no iAssign (junto com css/altura-automatica.css).
 *
 * O iAssign cria o iframe com a altura do cadastro do iLM (fixa) e sem barra de rolagem. Este script, executado
 * dentro do iVProg, ajusta a altura do iframe ao conteúdo: começa pequena e cresce (ou diminui) conforme o aluno
 * edita o programa. Menus abertos (ex.: o de comandos) também contam, para não ficarem cortados no fim do quadro.
 * Como o iVProg é servido pelo mesmo servidor do Moodle, pode acessar window.frameElement. Fora de um iframe
 * (ou se o navegador não permitir), não faz nada.
 */
(function () {
  var quadro = null;
  try { quadro = window.frameElement; } catch (e) { return; }
  if (!quadro) return;

  var ALTURA_MINIMA = 320; // px
  var FOLGA = 12;          // px abaixo do conteúdo
  var agendado = false;

  function fundo(elemento) {
    var r = elemento.getBoundingClientRect();
    return r.bottom + (window.pageYOffset || 0);
  }

  function medir() {
    agendado = false;
    var principal = document.getElementById('ui_main_div') || document.body;
    var altura = fundo(principal);
    // o terminal (flutuante/arrastável) e os menus abertos podem passar do fim do conteúdo
    var menus = document.querySelectorAll('#ivprog-term-div, .menu.visible, .ui.dropdown.active > .menu, .ui.popup.visible');
    for (var i = 0; i < menus.length; i++) {
      if (menus[i].offsetParent !== null) altura = Math.max(altura, fundo(menus[i]));
    }
    var nova = Math.max(ALTURA_MINIMA, Math.ceil(altura + FOLGA));
    if (Math.abs(nova - quadro.offsetHeight) > 2) quadro.style.height = nova + 'px';
  }

  function agendar() {
    if (agendado) return;
    agendado = true;
    setTimeout(medir, 30); // (requestAnimationFrame pode ser adiado pelo navegador dentro de iframes)
  }

  function iniciar() {
    quadro.style.width = '100%';
    quadro.style.maxWidth = '1100px';
    medir();
    if (window.ResizeObserver) new ResizeObserver(agendar).observe(document.getElementById('ui_main_div') || document.body);
    // abrir/fechar menus e trocar de modo (visual/textual) mudam classes e estilos sem mudar o tamanho do conteúdo
    new MutationObserver(agendar).observe(document.body, { subtree: true, childList: true, attributes: true, attributeFilter: ['class', 'style'] });
    window.addEventListener('resize', agendar);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();
})();
