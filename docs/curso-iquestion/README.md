# Minicurso: primeiros passos em programação com o iQuestion

Nove exercícios curtos, prontos para importar no iAssign, organizados em três
módulos. Cada módulo usa os três tipos do iQuestion na mesma ordem: primeiro o
aluno **lê** código (múltipla escolha), depois **completa** código (lacunas) e
por fim **monta** um programa (ordenar linhas).

A notação é a mesma do iVProg (`inteiro x <- 2`, `leia`, `escreva`, `se`/`senao`,
`enquanto`), para que os exercícios possam vir antes ou junto de atividades no
iVProg.

| Módulo | Exercício | Tipo | Ideia trabalhada |
|---|---|---|---|
| 1. Variáveis e saída | `m1-1-multipla-variaveis` | Múltipla escolha | uma variável guarda o valor mais recente |
| | `m1-2-lacunas-calculo` | Completar o código | operador `*` e comando `escreva` |
| | `m1-3-ordenar-dobro` | Ordenar linhas | declarar → ler → calcular → mostrar |
| 2. Decisão | `m2-1-multipla-se` | Múltipla escolha | só um dos blocos (`se` ou `senao`) executa |
| | `m2-2-lacunas-aprovado` | Completar o código | `>=` e `senao` |
| | `m2-3-ordenar-par` | Ordenar linhas | resto da divisão (`%`) e estrutura `se/senao` |
| 3. Repetição | `m3-1-multipla-enquanto` | Múltipla escolha | quantas vezes um laço executa |
| | `m3-2-lacunas-contador` | Completar o código | início, condição e incremento do contador |
| | `m3-3-ordenar-quadrados` | Ordenar linhas | a ordem dos comandos dentro do laço muda o resultado |

Todos têm explicação (mostrada quando o aluno acerta usando **Verificar**) e
estão com **Verificar** ligado.

## Tudo numa página só (recomendado)

Em vez de nove exercícios separados, use uma **sequência**: um único arquivo
com várias questões, que o aluno vê uma abaixo da outra e só precisa descer a
tela. Cada questão tem o seu próprio **Verificar**, uma barra mostra quantas já
foram respondidas e a nota do exercício é a **média** das questões.

| Arquivo (pasta `sequencias/`) | Conteúdo |
|---|---|
| `curso-completo.json` | as 9 questões, em ordem |
| `m1-sequencia.json` | módulo 1 (3 questões) |
| `m2-sequencia.json` | módulo 2 (3 questões) |
| `m3-sequencia.json` | módulo 3 (3 questões) |

Basta criar **um exercício** no iAssign com `curso-completo.json` (ou um
exercício por módulo, com os arquivos `m1/m2/m3-sequencia.json`).

Para montar ou alterar uma sequência, na tela do professor do iQuestion
escolha o formato **"Várias questões (uma abaixo da outra)"**: dá para
adicionar, editar, reordenar (↑ ↓) e excluir questões, e a pré-visualização
mostra a página inteira como o aluno verá.

## Como colocar no Moodle

1. Numa atividade iAssign do curso, ao criar um exercício, escolha o iLM
   **iQuestion** e clique em **Adicionar** no campo do arquivo. Abre a lista de
   arquivos do iQuestion.
2. Use **Importar** e envie `curso-iquestion.zip` (traz as quatro sequências e
   os nove exercícios avulsos de uma vez) ou um `.json` das pastas
   `sequencias/` ou `exercicios/`.
3. Na lista, clique no ícone **Adicionar iMA** do exercício desejado e salve a
   atividade. Repita para cada exercício.
4. Para conferir antes de liberar, use o ícone **pré-visualizar** (lupa).

Dica: crie um exercício iAssign por arquivo, na ordem da tabela, e use a
**dependência** entre exercícios do iAssign se quiser que o aluno só avance
depois de enviar o anterior.

## Em que situações usar

**Aquecimento no início da aula (5 a 10 minutos).** Os exercícios de múltipla
escolha do módulo do dia, com **Verificar** ligado. Servem para ativar o
assunto e mostram rapidamente quem ainda tem dúvida.

**Prática com retorno imediato.** O módulo inteiro, com **Verificar** ligado:
o aluno erra, revisa os itens marcados em vermelho e tenta de novo, como nos
aplicativos de aprendizagem. Bom como tarefa de casa.

**Verificação de aprendizagem (vale nota).** Abra cada exercício para editar
(veja abaixo) e **desmarque** "Permitir que o aluno verifique a resposta antes
de enviar". Sem a verificação, a nota reflete a primeira tentativa do aluno.
Considere também limitar o número de tentativas na atividade iAssign.

**Em grupo.** Com "Compartilhar um envio por grupo = Sim" na atividade, os
integrantes resolvem juntos e a resposta enviada vale para o grupo. Os
exercícios de **ordenar** e **completar** costumam gerar mais discussão que os
de múltipla escolha.

**Antes de um exercício no iVProg.** Use o exercício de ordenar do mesmo
assunto como "esqueleto" do programa que o aluno vai construir em seguida no
iVProg (por exemplo, `m2-3-ordenar-par` antes de pedir um programa de par ou
ímpar).

> Na pesquisa (condições individual e colaborativa), use exatamente os mesmos
> exercícios e a mesma configuração de **Verificar** nas duas condições.

## Como a nota é calculada

- Múltipla escolha: 1 se acertar, 0 se errar.
- Completar o código: fração das lacunas preenchidas corretamente
  (ex.: 2 de 3 lacunas certas = 0,67).
- Ordenar linhas: fração das linhas na posição correta. As linhas começam
  embaralhadas sem nenhuma no lugar certo, então quem não mexe tira 0.

## Editar ou criar exercícios

Um arquivo que já está sendo usado por uma atividade fica bloqueado para
edição na lista de arquivos (é uma proteção do iAssign). Para alterar, use o
ícone **×2 (Duplicar)**, edite a cópia e troque o arquivo na atividade.

Dicas para escrever bons exercícios:

- **Completar o código:** use peças erradas plausíveis, que correspondam a
  erros comuns (`-` no lugar de `+`, `>` no lugar de `>=`, `0` no lugar de
  `1`). Peças absurdas não ensinam nada.
- **Ordenar linhas:** evite linhas que poderiam trocar de lugar sem mudar o
  programa (por exemplo, duas declarações independentes seguidas), porque a
  correção exige a ordem exata. Se precisar delas, junte numa linha só.
- **Múltipla escolha:** inclua como alternativa o erro mais comum (como
  `x + 2` ou `52` no primeiro exercício), que indica qual é a dúvida do aluno.
- **Explicação:** uma ou duas frases dizendo *por que* a resposta está certa.
- Programas curtos: até cerca de 8 linhas, para caber bem no celular.

## Arquivos

```
curso-iquestion/
  README.md              <- este guia
  curso-iquestion.zip    <- tudo (sequências + exercícios avulsos), para importar de uma vez
  sequencias/*.json      <- várias questões numa página (curso completo e por módulo)
  exercicios/*.json      <- cada exercício separado
```
