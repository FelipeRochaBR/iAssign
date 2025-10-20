/******************************
 * This file holds game states.
 ******************************/

/** [GAME STATE]
 *
 * ..squareOne...	= gameName
 * ..../...\.....
 * ...a.....b.... = gameMode
 * .....\./......
 * ......|.......
 * ...../.\......
 * .plus...minus. = gameOperation
 * .....\./......
 * ......|.......
 * ....1,2,3..... = gameDifficulty
 *
 * Character : tractor
 * Theme : farm
 * Concept : Player associates 'blocks carried by the tractor' and 'floor spaces to be filled by them'
 * Represent fractions as : blocks/rectangles
 *
 * Game modes can be :
 *
 *   a : Player can select # of 'floor blocks' (hole in the ground)
 *       Selects size of hole to be made in the ground (to fill with the blocks in front of the truck)
 *   b : Player can select # of 'stacked blocks' (in front of the truck)
 *       Selects number of blocks in front of the truck (to fill the hole on the ground)
 *
 * Operations can be :
 *
 *   plus : addition of fractions
 *     Represented by : tractor going to the right (floor positions 0..8)
 *   minus : subtraction of fractions
 *     Represented by: tractor going to the left (floor positions 8..0)
 *
 * @namespace
 */
const squareOne = {
  control: undefined, // level related data
  default: undefined, // level related default values

  ui: undefined, // graphic elements
  animation: undefined, // animation control
  tractor: undefined,

  stack: undefined, // stack blocks info
  floor: undefined, // floor blocks info

  /**
   * Main code
   */
  create: function () {
    const truckWidth = 201;
    const divisor = gameDifficulty == 3 ? 4 : gameDifficulty; // Make sure valid divisors are 1, 2 and 4 (not 3)
    let lineColor = undefined;
    let fillColor = undefined;
    if (gameOperation === 'minus') {
      lineColor = colors.red;
      fillColor = colors.redLight;
    } else {
      lineColor = colors.green;
      fillColor = colors.greenLight;
    }

    this.ui = {
      arrow: undefined,
      help: undefined,
      message: undefined,
      continue: {
        // modal: undefined,
        button: undefined,
        text: undefined,
      },
    };
    this.control = {
      direc: gameOperation == 'minus' ? -1 : 1, // Will be multiplied to values to easily change tractor direction when needed
      divisorsList: '', // Hold the divisors for each fraction on stacked blocks (created for postScore())
      lineWidth: 3,

      hasClicked: false, // Checks if player 'clicked' on a block
      checkAnswer: false, // When true allows game to run 'check answer' code in update
      isCorrect: false, // Checks player 'answer'

      count: 0, // An 'x' position counter used in the tractor animation
    };
    this.animation = {
      animateTractor: false, // When true allows game to run 'tractor animation' code in update (turns animation of the moving tractor ON/OFF)
      animateEnding: false, // When true allows game to run 'tractor ending animation' code in update (turns 'ending' animation of the moving tractor ON/OFF)
      speed: 2 * this.control.direc, // X distance in which the tractor moves in each iteration of the animation
    };
    this.default = {
      width: 172, // Base block width,
      height: 70, // Base block height
      x0:
        gameOperation == 'minus'
          ? context.canvas.width - truckWidth
          : truckWidth, // Initial 'x' coordinate for the tractor and stacked blocks
      y0: context.canvas.height - game.image['floor_grass'].width * 1.5,
    };
    this.default.arrowX0 =
      self.default.x0 + self.default.width * self.control.direc;
    this.default.arrowXEnd =
      self.default.arrowX0 + self.default.width * self.control.direc * 8;

    renderBackground();

    // FOR MOODLE
    if (moodle) {
      navigation.add.right(['audio']);
    } else {
      navigation.add.left(['back', 'menu', 'show_answer'], 'customMenu');
      navigation.add.right(['audio']);
    }

    this.stack = {
      list: [],
      /**
       * (a) all blocks (fixed) (random)
       * (b) (updates) user selection
       */
      selectedIndex: undefined,
      /**
       * (a) UNDEFINED
       * (b) subsection of blocks (fixed) (random)
       */
      correctIndex: undefined,
      /**
       * (a/b) (updates) cur index (for animation/check control)
       *
       * initial value: 0
       */
      curIndex: 0,
      /**
       * (a/b) (updates) end of current stack block x coord
       */
      curBlockEndX: undefined,
    };
    this.floor = {
      list: [],
      /**
       * (a) (updates) user selection
       * (b) subsection of floor blocks (fixed) (generated)
       */
      selectedIndex: undefined,
      /**
       * (a) correct floor index - equiv to all in STACK
       * (b) UNDEFINED
       */
      correctIndex: undefined,
      /**
       * (a/b) (updates) cur index (for animation/check control)
       *
       * initial value: -1
       */
      curIndex: -1,
      /**
       * (a) correct floor x coord - equiv to all in STACK
       * (b) generated floor x coord - equiv to correct in STACK (fixed) (generated)
       */
      correctX: undefined,
    };

    let [restart, correctXA] = this.utils.renderStackedBlocks(
      this.control.direc,
      lineColor,
      fillColor,
      this.control.lineWidth,
      divisor
    );

    this.utils.renderFloorBlocks(
      this.control.direc,
      lineColor,
      this.control.lineWidth,
      divisor,
      correctXA
    );
    this.utils.renderCharacters();
    this.utils.renderMainUI();

    this.restart = restart;
    if (!this.restart) {
      game.timer.start(); // Set a timer for the current level (used in postScore())
      game.event.add('click', this.events.onInputDown);
      game.event.add('mousemove', this.events.onInputOver);
    }
  },

  /**
   * Game loop
   */
  update: function () {
    // Starts tractor animation
    if (self.animation.animateTractor) {
      self.utils.animateTractorHandler();
    }

    // Check answer after animation ends
    if (self.control.checkAnswer) {
      self.utils.checkAnswerHandler();
    }

    // Starts tractor moving animation
    if (self.animation.animateEnding) {
      self.utils.animateEndingHandler();
    }

    game.render.all();
  },

  utils: {
    // RENDER
    /**
     * Create stacked blocks for the level in create()
     */
    renderStackedBlocks: (direc, lineColor, fillColor, lineWidth, divisor) => {
      let restart = false;
      let hasBaseDifficulty = false; // Will be true after next for loop if level has at least one '1/difficulty' fraction (if false, restart)
      const max = gameMode == 'b' ? 10 : curMapPosition + 4; // Maximum # of stacked blocks for the level
      const total = game.math.randomInRange(curMapPosition + 2, max); // Total # of stacked blocks for the level

      const renderLabels = (curDivisor, x, y, i, curBlock) => {
        let font, curFractionItems;
        const fractionPartsList = [];

        if (curDivisor === 1) {
          font = textStyles.h2_;
          curFractionItems = [
            {
              x: x,
              y: self.default.y0 - i * y - 20,
              text: '1',
            },
            {
              x: x - 25,
              y: self.default.y0 - i * y - 27,
              text: gameOperation === 'minus' ? '-' : '',
            },
            null,
          ];
        } else {
          font = textStyles.p_;
          curFractionItems = [
            {
              x: x,
              y: self.default.y0 - i * y - 40,
              text: '1\n' + curDivisor,
            },
            {
              x: x - 25,
              y: self.default.y0 - i * y - 27,
              text: gameOperation === 'minus' ? '-' : '',
            },
            {
              x0: x,
              y: self.default.y0 - i * y - 35,
              x1: x + 25,
              lineWidth: 2,
              color: lineColor,
            },
          ];
        }
        font = { ...font, font: 'bold ' + font.font, fill: lineColor };

        for (let i = 0; i < 2; i++) {
          const item = game.add.text(
            curFractionItems[i].x,
            curFractionItems[i].y,
            curFractionItems[i].text,
            font
          );
          item.lineHeight = 30;
          fractionPartsList.push(item);
        }

        if (curFractionItems[2]) {
          const line = game.add.geom.line(
            curFractionItems[2].x0,
            curFractionItems[2].y,
            curFractionItems[2].x1,
            curFractionItems[2].y,
            curFractionItems[2].lineWidth,
            curFractionItems[2].color
          );
          line.anchor(0.5, 0);
          fractionPartsList.push(line);
        } else {
          fractionPartsList.push(null);
        }

        curBlock.fraction = {
          labels: fractionPartsList,
          nominator: direc,
          denominator: curDivisor,
        };

        if (!showFractions) {
          curBlock.fraction.labels.forEach((label) => {
            if (label) label.alpha = 0;
          });
        }
      };
      const shouldRestart = (hasBaseDifficulty, correctXA) => {
        if (
          !hasBaseDifficulty ||
          (gameOperation == 'plus' &&
            (correctXA < self.default.x0 + self.default.width ||
              correctXA > self.default.x0 + 8 * self.default.width)) ||
          (gameOperation == 'minus' &&
            (correctXA < self.default.x0 - 8 * self.default.width ||
              correctXA > self.default.x0 - self.default.width))
        )
          return true;
        return false;
      };

      let correctXA = self.default.x0 + self.default.width * direc; // Equivalent x coordinate on the floor

      // Render blocks
      for (let i = 0; i < total; i++) {
        let curDivisor = game.math.randomInRange(1, gameDifficulty); // Set divisor for current fraction
        if (curDivisor === gameDifficulty) hasBaseDifficulty = true;
        if (curDivisor === 3) curDivisor = 4; // Make sure valid divisors are 1, 2 and 4 (not 3)

        const curBlockWidth = self.default.width / curDivisor; // Current width is a fraction of the default
        self.control.divisorsList += curDivisor + ','; // Documenting list of divisors (for postScore())
        correctXA += curBlockWidth * direc;

        const curBlock = game.add.geom.rect(
          self.default.x0,
          self.default.y0 - i * self.default.height - lineWidth,
          curBlockWidth,
          self.default.height,
          fillColor,
          1,
          lineColor,
          lineWidth
        );
        curBlock.anchor(gameOperation === 'minus' ? 1 : 0, 1);
        curBlock.blockValue = divisor / curDivisor;

        // If game mode is (b), add events to stacked blocks
        if (gameMode === 'b') curBlock.blockIndex = i;

        // Add to stack blocks list
        self.stack.list.push(curBlock);

        // If 'show fractions' is turned on, create labels that display the fractions on the side of each block
        const x = self.default.x0 + (curBlockWidth + 40) * direc;
        const y = self.default.height + 1;
        renderLabels(curDivisor, x, y, i, curBlock);
      }

      // Computer generated correct stack index
      if (gameMode === 'a') self.stack.selectedIndex = total - 1;

      // Will be used as a counter in update, adding in the width of each stacked block to check if the end matches the floor selected position
      // initial value is end of current block
      self.stack.curBlockEndX =
        self.default.x0 + self.stack.list[0].width * direc;

      // Check for errors (level too easy for its difficulty or end position out of bounds)
      restart = shouldRestart(hasBaseDifficulty, correctXA);

      if (isDebugMode) {
        console.log(
          `------------------------------
            \nGame Map Position: ${curMapPosition} 
            \n------------------------ setup stack 
            \nMin blocks (curMapPosition + 2): ${curMapPosition + 2}
            ${
              gameMode === 'a'
                ? `\nMax blocks (A mode) (curMapPosition + 4):  ${
                    curMapPosition + 4
                  }`
                : '\nMax blocks (B mode): 10'
            } 
            \nTotal blocks (random min..max): ${total} 
            \nPossible divisors (random 1..gameDifficulty / if 3 then 4): 1..${
              gameDifficulty == 3 ? 4 : gameDifficulty
            } 
            \n(Checks if has 1/diff or floor x is out of bounds)
            `
        );

        if (restart) console.log('Error during level setup. Retrying...');
        else console.log('Successfull level setup!');
      }

      return [restart, correctXA];
    },
    /**
     * Create floor blocks for the level in create()
     */
    renderFloorBlocks: (direc, lineColor, lineWidth, divisor, correctXA) => {
      let correctXB = 0;
      let total = 8 * divisor; // Number of floor blocks
      const blockWidth = self.default.width / divisor; // Width of each floor block

      const renderLabels = () => {
        for (let i = 0; i <= 8; i++) {
          const x = self.default.x0 + (i + 1) * self.default.width * direc;
          const y = self.default.y0 + self.default.height + 45 - 65;

          let numberX = x;
          let numberText = i;
          let numberCircleSize = 45;
          let numberFont = {
            ...textStyles.h3_,
            fill: lineColor,
            font: 'bold ' + textStyles.h3_.font,
          };
          if (gameOperation === 'minus') {
            numberX = i !== 0 ? x - 2 : x;
            numberText = -i;
            numberCircleSize = 45;
            numberFont.font = 'bold ' + textStyles.h4_.font;
          }

          game.add.geom
            .circle(x, y, numberCircleSize, undefined, 0, colors.white, 1)
            .anchor(0, 0.25);
          game.add.text(numberX, y + 2, numberText, numberFont);
        }
      };

      // If game is type (b), select a random floor x position
      if (gameMode === 'b') {
        self.stack.correctIndex = game.math.randomInRange(
          0,
          self.stack.list.length - 1
        ); // Correct stacked index

        correctXB = self.default.x0 + self.default.width * direc;

        for (let i = 0; i <= self.stack.correctIndex; i++) {
          correctXB += self.stack.list[i].width * direc; // Equivalent x position on the floor
        }
      }

      // Render floor blocks
      for (let i = 0, shouldUpdateIndex = true; i < total; i++) {
        const curX =
          self.default.x0 + (self.default.width + i * blockWidth) * direc;

        // If game is type (a), iterate until find equivalent floor index
        if (gameMode === 'a' && shouldUpdateIndex) {
          if (
            (gameOperation == 'plus' && curX >= correctXA) ||
            (gameOperation == 'minus' && curX <= correctXA)
          ) {
            self.floor.correctIndex = i - 1; // Set index of correct floor block
            shouldUpdateIndex = false;
          }
        }

        // If game is type (b), stop iterating after find equivalent floor block
        if (gameMode === 'b') {
          if (
            (gameOperation == 'plus' && curX >= correctXB) ||
            (gameOperation == 'minus' && curX <= correctXB)
          ) {
            total = i;
            break;
          }
        }

        // Render floor block
        const curBlock = game.add.geom.rect(
          curX,
          self.default.y0,
          blockWidth,
          self.default.height + 10,
          colors.blueBgInsideLevel,
          1,
          colors.gray,
          lineWidth
        );
        const anchor = gameOperation == 'minus' ? 1 : 0;
        curBlock.anchor(anchor, 0);
        curBlock.blockValue = 1;

        // If game is type (a), add events to floor blocks
        if (gameMode === 'a') {
          curBlock.fillColor = 'transparent';
          curBlock.blockIndex = i;
        }

        // Add current block to list
        self.floor.list.push(curBlock);
      }

      if (gameMode === 'b') {
        self.floor.selectedIndex = total - 1; // Computer generated correct floor index
        self.floor.correctX = correctXB;
      } else {
        self.floor.correctX = correctXA;
      }

      // Creates labels on the floor to display the numbers
      renderLabels();

      if (isDebugMode) {
        console.log(
          `------------------------ setup floor
            \nDivisor (gameDifficulty / if 3 then 4): ${divisor}
            ${
              gameMode === 'a'
                ? `\nTotal blocks (A) (divisor * 8): ${divisor} * 8 = ${
                    divisor * 8
                  }`
                : `\nTotal blocks (B) (((equiv. to stack))): ${self.floor.list.length}`
            }
            ${
              gameMode === 'a'
                ? `\nCorrect index (A) (((equival. to stack))): ${self.floor.correctIndex}`
                : ''
            }
            `
        );
      }
    },
    renderCharacters: () => {
      self.tractor = game.add.sprite(
        self.default.x0,
        self.default.y0,
        'tractor',
        0,
        1
      );

      if (gameOperation == 'plus') {
        self.tractor.anchor(1, 1);
        self.tractor.animation = ['move', [0, 1, 2, 3, 4], 4];
      } else {
        self.tractor.anchor(0, 1);
        self.tractor.animation = ['move', [5, 6, 7, 8, 9], 4];
        self.tractor.curFrame = 5;
      }
    },
    renderMainUI: () => {
      // Help pointer
      self.ui.help = game.add.image(0, 0, 'pointer', 1.7, 0);
      if (gameMode === 'b') self.ui.help.anchor(0.25, 0.7);
      else self.ui.help.anchor(0.2, 0);

      // Selection Arrow
      if (gameMode === 'a') {
        self.ui.arrow = game.add.image(
          self.default.arrowX0,
          self.default.y0,
          'arrow_down',
          1.5
        );
        self.ui.arrow.anchor(0.5, 1);
        self.ui.arrow.alpha = 0.5;
      }

      // Intro text
      const correctMessage =
        gameMode === 'a'
          ? game.lang.squareOne_intro_a
          : game.lang.squareOne_intro_b;
      const treatedMessage = correctMessage.split('\\n');
      const font = textStyles.h1_;
      self.ui.message = [];
      self.ui.message.push(
        game.add.text(
          context.canvas.width / 2,
          170,
          treatedMessage[0] + '\n' + treatedMessage[1],
          font
        )
      );
    },
    renderOperationUI: () => {
      /**
       *
       * if game mode A:
       * - left: (1) selected floor position (user selection)
       * - right: (2) expected floor position equivalent to the stack of blocks (pre-set)
       *
       * if game mode B:
       * - left: (3) floor position equivalent to the stack of blocks (user selection)
       * - right: (4) generated floor position (pre-set)
       */

      const divisor = gameDifficulty == 3 ? 4 : gameDifficulty;

      const renderFloorFractions = (lastIndex, divisor) => {
        const operator = gameOperation === 'minus' ? '-' : '+';
        const index = lastIndex;
        const blocks = index + 1;

        const valueReal = blocks / divisor;
        const valueFloor = Math.floor(valueReal);
        const valueRest = valueReal - valueFloor;

        let fracNomin = (fracDenomin = fracLine = '');
        // adds sign on the left of the equation
        if (gameOperation === 'minus') {
          fracNomin += ' ';
          fracDenomin += ' ';
          fracLine += operator;
        }
        // 1 _ _
        if (valueFloor) {
          fracNomin += ' ';
          fracDenomin += ' ';
          fracLine += valueFloor;
        }
        // _ + _
        if (valueFloor && valueRest) {
          fracNomin += ' ';
          fracDenomin += ' ';
          fracLine += operator;
        }
        // _ _ 1/5
        if (valueRest) {
          fracNomin += `${valueRest * divisor}`;
          fracDenomin += `${divisor}`;
          fracLine += '-';
        }

        return [fracNomin, fracDenomin, fracLine, valueReal];
      };

      const renderStackFractions = (lastIndex) => {
        const operator = gameOperation === 'minus' ? '-' : '+';
        const index = lastIndex;
        const blocks = index + 1;

        const nominators = [];
        const denominators = [];
        const values = [];
        let valueReal = 0;
        let fracNomin = (fracDenomin = fracLine = '');

        for (let i = 0; i < blocks; i++) {
          const m = self.stack.list[i].fraction.denominator || 1;
          const temp = self.stack.list[i].fraction.nominator || 0;
          const n = gameOperation === 'minus' ? -temp : +temp;
          const nm = n / m;
          nominators[i] = n + 0;
          denominators[i] = m + 0;
          values[i] = nm;
          valueReal += nm;
        }

        for (let i = 0; i < blocks; i++) {
          const valueReal = values[i];
          const valueFloor = Math.floor(valueReal);
          const valueRest = valueReal - valueFloor;

          if (i > 0 || gameOperation === 'minus') {
            fracNomin += ' ';
            fracDenomin += ' ';
            fracLine += operator;
          }
          if (valueFloor && !valueRest) {
            fracNomin += ' ';
            fracDenomin += ' ';
            fracLine += valueFloor;
          }
          if (valueRest) {
            fracNomin += `${nominators[i]}`;
            fracDenomin += `${denominators[i]}`;
            fracLine += '-';
          }
        }

        return [fracNomin, fracDenomin, fracLine, valueReal];
      };

      // Initial setup
      const font = textStyles.fraction;
      font.fill = colors.black;

      const padding = 100;
      const offsetX = 100;
      const widthOfChar = 35;

      const x0 = padding;
      const y0 = context.canvas.height / 3;
      let nextX = x0;

      const cardHeight = 400;
      const cardX = x0 - padding;
      const cardY = y0;

      const renderList = [];

      // Render Card
      const card = game.add.geom.rect(
        cardX,
        cardY,
        0,
        cardHeight,
        colors.blueLight,
        0.5,
        colors.blueDark,
        8
      );
      card.id = 'card';
      card.anchor(0, 0.5);
      renderList.push(card);

      // Fraction setup
      console.clear();
      const [floorNominators, floorDenominators, floorLines, floorValue] =
        renderFloorFractions(
          self.floor.selectedIndex,
          divisor
          // 'LEFT SIDE - a fração escolhida no chão é...\n\n'
        );
      renderFloorFractions(
        self.floor.correctIndex,
        divisor
        // '\n\nRIGHT SIDE 1 - a fração CORRETA no chão é...\n\n'
      );
      const [stackNominators, stackDenominators, stackLines, stackValue] =
        renderStackFractions(
          self.stack.selectedIndex
          // '\n\nRIGHT SIDE 2 - a fração CORRETA na stack é...\n\n'
        );

      const renderFloorOperationLine = (x) => {
        font.fill = colors.black;
        const floorNom = game.add.text(
          x + offsetX / 2,
          y0,
          floorNominators,
          font,
          60
        );
        const floorDenom = game.add.text(
          x + offsetX / 2,
          y0 + 70,
          floorDenominators,
          font,
          60
        );
        const floorLin = game.add.text(
          x + offsetX / 2,
          y0 + 35,
          floorLines,
          font,
          60
        );
        renderList.push(floorNom);
        renderList.push(floorDenom);
        renderList.push(floorLin);
      };
      const renderStackOperationLine = (x) => {
        font.fill = colors.black;
        const stackNom = game.add.text(
          x + offsetX / 2,
          y0,
          stackNominators,
          font,
          60
        );
        const stackDenom = game.add.text(
          x + offsetX / 2,
          y0 + 70,
          stackDenominators,
          font,
          60
        );
        const stackLin = game.add.text(
          x + offsetX / 2,
          y0 + 35,
          stackLines,
          font,
          60
        );
        renderList.push(stackNom);
        renderList.push(stackDenom);
        renderList.push(stackLin);
      };

      // Render LEFT part of the operation
      if (gameMode === 'a') renderFloorOperationLine(x0);
      else renderStackOperationLine(x0);

      let curNominators = gameMode === 'a' ? floorNominators : stackNominators;
      nextX = x0 + (curNominators.length + 2) * widthOfChar;

      // Render middle sign - equal by default
      font.fill = colors.green;
      let comparisonSign = '=';
      // Render middle sign - if not equal
      if (floorValue != stackValue) {
        font.fill = colors.red;
        let leftSideIsLarger = floorValue > stackValue;
        if (gameMode === 'b') leftSideIsLarger = !leftSideIsLarger;
        if (gameOperation === 'minus') leftSideIsLarger = !leftSideIsLarger;
        comparisonSign = leftSideIsLarger ? '>' : '<';
      }
      renderList.push(game.add.text(nextX, y0 + 35, comparisonSign, font));

      // Render RIGHT part of the operation
      if (gameMode === 'a') renderStackOperationLine(nextX);
      else renderFloorOperationLine(nextX);

      curNominators = gameMode === 'a' ? stackNominators : floorNominators;
      const resultWidth = (curNominators.length + 2) * widthOfChar;

      const cardWidth = nextX - x0 + resultWidth + padding * 2;
      card.width = cardWidth;

      const endSignX = (context.canvas.width - cardWidth) / 2 + cardWidth;

      // Center Card
      moveList(renderList, (context.canvas.width - cardWidth) / 2, 0);

      self.fractionOperationUI = renderList;

      return endSignX;
    },
    renderEndUI: () => {
      let btnColor = colors.green;
      let btnText = game.lang.continue;

      if (!self.control.isCorrect) {
        btnColor = colors.red;
        btnText = game.lang.retry;
      }

      // continue button
      self.ui.continue.button = game.add.geom.rect(
        context.canvas.width / 2,
        context.canvas.height / 2 + 100,
        450,
        100,
        btnColor
      );
      self.ui.continue.button.anchor(0.5, 0.5);
      self.ui.continue.text = game.add.text(
        context.canvas.width / 2,
        context.canvas.height / 2 + 16 + 100,
        btnText,
        textStyles.btn
      );
    },

    // UPDATE HANDLERS
    animateTractorHandler: () => {
      // Move
      self.tractor.x += self.animation.speed;
      self.stack.list.forEach((block) => (block.x += self.animation.speed));

      // If the current block is 1/n (not 1/1) we need to consider the
      // extra space the truck needs to pass after the blocks falls but
      // before reaching the next block
      const curBlockExtra =
        (self.default.width - self.stack.list[self.stack.curIndex].width) *
        self.control.direc;

      const canLowerBlocks =
        (gameOperation == 'plus' &&
          self.stack.list[0].x >= self.stack.curBlockEndX + curBlockExtra) ||
        (gameOperation == 'minus' &&
          self.stack.list[0].x <= self.stack.curBlockEndX + curBlockExtra);
      if (canLowerBlocks) {
        const endAnimation = self.utils.lowerBlocksHandler();

        if (!endAnimation) {
          self.animation.animateTractor = false;
          self.control.checkAnswer = true;
        }
      }
    },
    lowerBlocksHandler: () => {
      self.floor.curIndex += self.stack.list[self.stack.curIndex].blockValue;

      const tooManyStackBlocks = self.floor.curIndex > self.floor.selectedIndex;
      if (tooManyStackBlocks) return false;

      // fill floor
      for (let i = 0; i <= self.floor.curIndex; i++) {
        self.floor.list[i].fillColor = 'transparent';
      }
      // lower blocks
      self.stack.list.forEach((block) => {
        block.y += self.default.height;
      });
      // hide current block
      self.stack.list[self.stack.curIndex].alpha = 0;

      const isLastFloorBlock = self.floor.curIndex === self.floor.selectedIndex;
      const notEnoughStackBlocks =
        self.stack.curIndex === self.stack.list.length - 1;

      if (isLastFloorBlock || notEnoughStackBlocks) return false;

      // update stack blocks
      self.stack.curIndex++;
      self.stack.curBlockEndX +=
        self.stack.list[self.stack.curIndex].width * self.control.direc;

      return true;
    },
    checkAnswerHandler: () => {
      game.timer.stop();

      game.animation.stop(self.tractor.animation[0]);

      if (gameMode === 'a') self.ui.arrow.alpha = 0;

      self.control.isCorrect =
        gameMode === 'a'
          ? self.floor.selectedIndex === self.floor.correctIndex
          : self.stack.selectedIndex === self.stack.correctIndex;

      const x = self.utils.renderOperationUI();

      // Give feedback to player and turns on sprite animation
      if (self.control.isCorrect) {
        completedLevels++; // Increases number os finished levels
        if (audioStatus) game.audio.okSound.play();
        game.animation.play(self.tractor.animation[0]);
        game.add
          .image(x + 50, context.canvas.height / 3, 'answer_correct')
          .anchor(0.5, 0.5);
        if (isDebugMode) console.log('Completed Levels: ' + completedLevels);
      } else {
        if (audioStatus) game.audio.errorSound.play();
        game.add
          .image(x, context.canvas.height / 3, 'answer_wrong')
          .anchor(0.5, 0.5);
      }

      self.fetch.postScore();

      self.control.checkAnswer = false;
      self.animation.animateEnding = true;
    },
    animateEndingHandler: () => {
      // ANIMATE ENDING
      self.control.count++;

      // If CORRECT ANSWER runs final tractor animation (else tractor desn't move, just wait)
      if (self.control.isCorrect) self.tractor.x += self.animation.speed;

      if (self.control.count === 100) {
        self.utils.renderEndUI();
        self.control.showEndInfo = true;

        if (self.control.isCorrect) canGoToNextMapPosition = true;
        else canGoToNextMapPosition = false;
      }
    },
    endLevel: () => {
      game.state.start('map');
    },

    // INFORMATION
    /**
     * Display correct answer
     */
    showAnswer: () => {
      if (!self.control.hasClicked) {
        // On gameMode (a)
        if (gameMode === 'a') {
          const aux = self.floor.list[0];
          self.ui.help.x =
            self.floor.correctX - (aux.width / 2) * self.control.direc;
          self.ui.help.y = self.default.y0;
          // On gameMode (b)
        } else {
          const aux = self.stack.list[self.stack.correctIndex];
          self.ui.help.x = aux.x + (aux.width / 2) * self.control.direc;
          self.ui.help.y = aux.y;
        }

        self.ui.help.alpha = 0.7;
      }
    },

    // EVENT HANDLERS
    /**
     * Function called by self.events.onInputDown() when player clicks on a valid rectangle.
     */
    clickHandler: (clickedIndex, curSet) => {
      if (!self.control.hasClicked && !self.animation.animateEnding) {
        document.body.style.cursor = 'auto';
        // Play beep sound
        if (audioStatus) game.audio.popSound.play();

        // Disable show answer nav icon
        navigation.disableIcon(navigation.showAnswerIcon);
        // Hide intro message
        self.ui.message[0].alpha = 0;
        // Hide labels
        if (showFractions) {
          self.stack.list.forEach((block) => {
            block.fraction.labels.forEach((lbl) => {
              if (lbl) lbl.alpha = 0;
            });
          });
        }
        // Hide solution pointer
        if (self.ui.help != undefined) self.ui.help.alpha = 0;
        // Hide unselected blocks
        for (let i = curSet.list.length - 1; i > clickedIndex; i--) {
          curSet.list[i].alpha = 0;
        }

        // Save selected index
        curSet.selectedIndex = clickedIndex;

        if (gameMode === 'a') {
          self.ui.arrow.alpha = 0;
        } else {
          // update list size
          self.stack.list.length = curSet.selectedIndex + 1;
        }

        // Turn tractor animation on
        game.animation.play(self.tractor.animation[0]);
        self.animation.animateTractor = true;
        self.control.hasClicked = true;
      }
    },
    /**
     * Function called by self.events.onInputOver() when cursor is over a valid rectangle
     *
     * @param {object} cur rectangle the cursor is over
     */
    overHandler: (cur) => {
      if (!self.control.hasClicked) {
        document.body.style.cursor = 'pointer';

        if (gameMode === 'a') {
          for (let i in self.floor.list) {
            self.floor.list[i].fillColor =
              i <= cur.blockIndex ? colors.blueBgInsideLevel : 'transparent';
          }
          self.floor.selectedIndex = cur.blockIndex;
        } else {
          for (let i in self.stack.list) {
            const alpha = i <= cur.blockIndex ? 1 : 0.4;

            self.stack.list[i].alpha = alpha;
            if (showFractions) {
              self.stack.list[i].fraction.labels.forEach((lbl) => {
                if (lbl) lbl.alpha = alpha;
              });
            }
          }
          self.stack.selectedIndex = cur.blockIndex;
        }
      }
    },
    /**
     * Function called by self.events.onInputOver() when cursos is out of a valid rectangle
     */
    outHandler: () => {
      if (!self.control.hasClicked) {
        document.body.style.cursor = 'auto';

        if (gameMode === 'a') {
          for (let i in self.floor.list) {
            self.floor.list[i].fillColor = 'transparent';
          }
          self.floor.selectedIndex = undefined;
        } else {
          for (let i in self.stack.list) {
            self.stack.list[i].alpha = 1;
            if (showFractions) {
              self.stack.list[i].fraction.labels.forEach((lbl) => {
                if (lbl) lbl.alpha = 1;
              });
            }
          }
          self.stack.selectedIndex = undefined;
        }
      }
    },
  },

  events: {
    /**
     * Called by mouse click event
     *
     * @param {object} mouseEvent contains the mouse click coordinates
     */
    onInputDown: (mouseEvent) => {
      const x = game.math.getMouse(mouseEvent).x;
      const y = game.math.getMouse(mouseEvent).y;

      // click blocks
      const curSet = gameMode == 'a' ? self.floor : self.stack;
      for (let i in curSet.list) {
        if (game.math.isOverIcon(x, y, curSet.list[i])) {
          self.utils.clickHandler(+i, curSet);
          break;
        }
      }

      // Continue button
      if (self.control.showEndInfo) {
        if (game.math.isOverIcon(x, y, self.ui.continue.button)) {
          if (audioStatus) game.audio.popSound.play();
          self.utils.endLevel();
        }
      }

      navigation.onInputDown(x, y);

      game.render.all();
    },

    /**
     * Called by mouse move event
     *
     * @param {object} mouseEvent contains the mouse move coordinates
     */
    onInputOver: (mouseEvent) => {
      const x = game.math.getMouse(mouseEvent).x;
      const y = game.math.getMouse(mouseEvent).y;
      let isOverFloor = false;
      let isOverStack = false;

      if (gameMode === 'a') {
        self.floor.list.forEach((cur) => {
          // hover floor blocks
          if (game.math.isOverIcon(x, y, cur)) {
            isOverFloor = true;
            self.utils.overHandler(cur);
          }
          // move arrow
          if (
            !self.control.hasClicked &&
            !self.animation.animateEnding &&
            game.math.isOverIcon(x, self.default.y0, cur)
          ) {
            self.ui.arrow.x = x;
          }
        });

        if (!isOverFloor) self.utils.outHandler('a');
      }

      if (gameMode === 'b') {
        // hover stack blocks
        self.stack.list.forEach((cur) => {
          if (game.math.isOverIcon(x, y, cur)) {
            isOverStack = true;
            self.utils.overHandler(cur);
          }
        });
        if (!isOverStack) self.utils.outHandler('b');
      }

      // Continue button
      if (self.control.showEndInfo) {
        if (game.math.isOverIcon(x, y, self.ui.continue.button)) {
          // If pointer is over icon
          document.body.style.cursor = 'pointer';
          self.ui.continue.button.scale =
            self.ui.continue.button.initialScale * 1.1;
          self.ui.continue.text.style = textStyles.btnLg;
        } else {
          // If pointer is not over icon
          document.body.style.cursor = 'auto';
          self.ui.continue.button.scale =
            self.ui.continue.button.initialScale * 1;
          self.ui.continue.text.style = textStyles.btn;
        }
      }

      navigation.onInputOver(x, y);

      game.render.all();
    },
  },

  fetch: {
    /**
     * Saves players data after level ends - to be sent to database <br>
     *
     * Attention: the 'line_' prefix data table must be compatible to data table fields (MySQL server)
     *
     * @see /php/save.php
     */
    postScore: () => {
      // Creates string that is going to be sent to db
      const data =
        '&line_game=' +
        gameShape +
        '&line_mode=' +
        gameMode +
        '&line_oper=' +
        gameOperation +
        '&line_leve=' +
        gameDifficulty +
        '&line_posi=' +
        curMapPosition +
        '&line_resu=' +
        self.control.isCorrect +
        '&line_time=' +
        game.timer.elapsed +
        '&line_deta=' +
        'numBlocks:' +
        self.stack.list.length +
        ', valBlocks: ' +
        self.control.divisorsList + // Ends in ','
        ' blockIndex: ' +
        self.stack.selectedIndex +
        ', floorIndex: ' +
        self.floor.selectedIndex;

      // FOR MOODLE
      sendToDatabase(data);
    },
  },
};
