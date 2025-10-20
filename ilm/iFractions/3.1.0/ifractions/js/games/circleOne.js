/******************************
 * This file holds game states.
 ******************************/

/** [GAME STATE]
 *
 * .....circleOne.... = gameName
 * ....../...\.......
 * .....a.....b...... = gameMode
 * .......\./........
 * ........|.........
 * ....../.|.\.......
 * .plus.minus.mixed. = gameOperation
 * ......\.|./.......
 * ........|.........
 * ......1,2,3....... = gameDifficulty
 *
 * Character : kid/kite
 * Theme : getting the kite on the floor
 * Concept : 'How much the kid has to walk to get to the kite?'
 * Represent fractions as : circles/arcs
 *
 * Game modes can be :
 *
 *   a : Player can place kite position
 *       Place kite in position (so the kid can get to it)
 *   b : Player can select # of circles
 *       Selects number of circles (that represent distance kid needs to walk to get to the kite)
 *
 * Operations can be :
 *
 *   plus : addition of fractions
 *     Represented by : kid going to the right (floor positions 0..5)
 *   minus : subtraction of fractions
 *     Represented by: kid going to the left (floor positions 5..0)
 *   mixed : Mix addition and subtraction of fractions in same
 *     Represented by: kid going to the left (floor positions 0..5)
 *
 * @namespace
 */
const circleOne = {
  ui: undefined,
  control: undefined,
  animation: undefined,
  road: undefined,

  circles: undefined,
  kid: undefined,
  kite: undefined,
  kite_line: undefined,
  walkedPath: undefined,

  /**
   * Main code
   */
  create: function () {
    this.ui = {
      help: undefined,
      message: undefined,
      continue: {
        // modal: undefined,
        button: undefined,
        text: undefined,
      },
    };

    const roadMapper = () => {
      const _pointWidth = (game.sprite['map_place'].width / 2) * 0.45;

      const defaultX = 150;
      const defaultY =
        context.canvas.height - game.image['floor_grass'].width * 1.5;
      const defaultWidth = 1620;

      // Initial 'x' coordinate for the kid and the kite
      const x =
        gameOperation === 'minus'
          ? context.canvas.width - defaultX - _pointWidth / 2
          : defaultX + _pointWidth / 2;
      const y = defaultY;
      const width = defaultWidth - _pointWidth;
      const _divisions = 5;
      const _subdivisions = gameDifficulty === 3 ? 4 : gameDifficulty;
      const numberOfBlocks = _divisions * _subdivisions;

      return { x, y, width, numberOfBlocks, defaultX, defaultY, defaultWidth };
    };
    this.road = roadMapper();

    const blocksMapper = () => {
      let width = this.road.width / this.road.numberOfBlocks;
      if (gameOperation === 'minus') width = -width;
      const height = 50;
      return { width, height, list: [], cur: undefined };
    };
    this.blocks = blocksMapper();

    this.walkedPath = [];

    const pointWidth = (game.sprite['map_place'].width / 2) * 0.45;

    const distanceBetweenPoints =
      (context.canvas.width - this.road.defaultX * 2 - pointWidth) / 5; // Distance between road points

    const y0 = this.road.defaultY + 20;
    const x0 = this.road.x;

    this.circles = {
      diameter: game.math.getRadiusFromCircunference(distanceBetweenPoints) * 2, // (Fixed) Circles Diameter
      cur: 0, // Current circle index
      list: [], // Circle objects
    };

    this.control = {
      directionModifier: gameOperation === 'minus' ? -1 : 1,

      correctX: x0, // Correct position (accumulative)
      nextX: undefined, // Next point position
      divisorsList: '', // used in postScore (Accumulative)

      hasClicked: false, // Checks if user has clicked
      checkAnswer: false, // Check kid on top of kiteline
      isCorrect: false, // Informs answer is correct
      showEndInfo: false,
      endSignX: undefined,
      curWalkedPath: 0,
      // mode 'b' exclusive
      correctIndex: undefined,
      selectedIndex: -1, // Index of clicked circle (game (b))
      numberOfPlusFractions: undefined,
    };

    const walkOffsetX = 2;
    const walksPerDistanceBetweenPoints = distanceBetweenPoints / walkOffsetX;
    this.animation = {
      list: {
        left: undefined,
        right: undefined,
      },
      invertDirection: undefined,
      animateKid: false,
      animateKite: false, // TODO
      counter: undefined,
      walkOffsetX,
      angleOffset: 360 / walksPerDistanceBetweenPoints,
    };

    renderBackground('farmRoad');

    // Calls function that loads navigation icons
    // FOR MOODLE
    if (moodle) {
      navigation.add.right(['audio']);
    } else {
      navigation.add.left(['back', 'menu', 'show_answer'], 'customMenu');
      navigation.add.right(['audio']);
    }

    const validPath = { x0, y0, distanceBetweenPoints };

    this.utils.renderRoadBlocks();
    this.utils.renderRoad(validPath);

    const [restart, kiteX] = this.utils.renderCircles(validPath);
    this.restart = restart;

    this.utils.renderCharacters(validPath, kiteX);
    this.utils.renderMainUI();

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
    // Starts kid animation
    if (self.animation.animateKid) {
      self.utils.animateKidHandler();
    }

    // Check if kid is on top of kite line
    if (self.control.checkAnswer) {
      self.utils.checkAnswerHandler();
    }

    // Starts kite moving animation
    if (self.animation.animateKite) {
      self.utils.animateKiteHandler();
    }

    game.render.all();
  },

  utils: {
    renderRoadBlocks: function () {
      for (let i = 0; i < self.road.numberOfBlocks; i++) {
        const block = game.add.geom.rect(
          self.road.x + i * self.blocks.width,
          self.road.y,
          self.blocks.width, // If gameOperation is minus, block width is negative
          self.blocks.height,
          'transparent',
          0.5,
          colors.red,
          2
        );
        block.info = { index: i };
        self.blocks.list.push(block);
      }
    },
    // RENDERS
    renderRoad: function (validPath) {
      const offset = 40;
      for (let i = 0; i <= 5; i++) {
        // Gray place
        game.add
          .sprite(
            validPath.x0 +
              i *
                validPath.distanceBetweenPoints *
                self.control.directionModifier,
            validPath.y0,
            'map_place',
            0,
            0.45
          )
          .anchor(0.5, 0.5);
        // White circle behind number
        const curX =
          validPath.x0 +
          i * validPath.distanceBetweenPoints * self.control.directionModifier;
        game.add.geom
          .circle(
            curX,
            validPath.y0 + 60 + offset,
            60,
            undefined,
            0,
            colors.white,
            0.8
          )
          .anchor(0, 0.25);
        game.add.geom.rect(
          curX,
          validPath.y0 + 60 - 28,
          4,
          25,
          colors.white,
          0.8,
          undefined,
          0
        );
        // Number
        game.add.text(
          curX,
          validPath.y0 + 60 + offset,
          i * self.control.directionModifier,
          {
            ...textStyles.h2_,
            font: 'bold ' + textStyles.h2_.font,
            fill: gameOperation === 'minus' ? colors.red : colors.green,
          }
        );
      }

      self.utils.renderWalkedPath(
        validPath.x0 - 1,
        validPath.y0 - 5,
        gameOperation === 'minus' ? colors.red : colors.green
      );
    },
    renderWalkedPath: function (x, y, color) {
      const path = game.add.geom.rect(x, y, 1, 1, 'transparent', 1, color, 4);
      self.walkedPath.push(path);
      return path;
    },
    renderCircles: function (validPath) {
      let restart = false;
      let hasBaseDifficulty = false;
      let kiteX = context.canvas.width / 2;

      const fractionX =
        validPath.x0 -
        (self.circles.diameter - 10) * self.control.directionModifier;
      const font = {
        ...textStyles.h2_,
        font: 'bold ' + textStyles.h2_.font,
      };
      // Number of circles
      const max = gameOperation === 'mixed' ? 6 : curMapPosition + 1;

      const min =
        curMapPosition === 1 && (gameOperation === 'mixed' || gameMode === 'b')
          ? 2
          : curMapPosition; // Mixed level has at least 2 fractions

      const total = game.math.randomInRange(min, max); // Total number of circles

      // For mode 'b'
      self.control.numberOfPlusFractions = game.math.randomInRange(
        1,
        total - 1
      );

      for (let i = 0; i < total; i++) {
        let curDirection = undefined;
        let curLineColor = undefined;
        let curFillColor = undefined;
        let curAngleDegree = undefined;
        let curIsCounterclockwise = undefined;
        let curFractionItems = undefined;
        let curCircle = undefined;
        const curCircleInfo = {
          direction: undefined,
          direc: undefined,
          distance: undefined,
          angle: undefined,
          fraction: {
            labels: [],
            nominator: undefined,
            denominator: undefined,
          },
        };
        let curDivisor = game.math.randomInRange(1, gameDifficulty); // Set fraction 'divisor' (depends on difficulty)
        if (curDivisor === gameDifficulty) hasBaseDifficulty = true; // True if after for ends has at least 1 '1/difficulty' fraction

        curDivisor = curDivisor === 3 ? 4 : curDivisor; // Turns 1/3 into 1/4 fractions

        self.control.divisorsList += curDivisor + ','; // Add this divisor to the list of divisors (for postScore())

        // Set each circle direction
        switch (gameOperation) {
          case 'plus':
            curDirection = 'right';
            break;
          case 'minus':
            curDirection = 'left';
            break;
          case 'mixed':
            curDirection =
              i < self.control.numberOfPlusFractions ? 'right' : 'left';
            break;
        }
        curCircleInfo.direction = curDirection;

        // Set each circle visual info
        if (curDirection === 'right') {
          curIsCounterclockwise = true;
          curLineColor = colors.green;
          curFillColor = colors.greenLight;
          curCircleInfo.direc = 1;
        } else {
          curIsCounterclockwise = false;
          curLineColor = colors.red;
          curFillColor = colors.redLight;
          curCircleInfo.direc = -1;
        }
        font.fill = curLineColor;

        const curCircleY =
          validPath.y0 -
          5 -
          self.circles.diameter / 2 -
          i * self.circles.diameter;

        // Draw circles
        if (curDivisor === 1) {
          curAngleDegree = 360;
          curCircle = game.add.geom.circle(
            validPath.x0,
            curCircleY,
            self.circles.diameter,
            curLineColor,
            3,
            curFillColor,
            1
          );
          curCircle.counterclockwise = curIsCounterclockwise;
          curCircleInfo.angleDegree = curAngleDegree;

          curFractionItems = [
            {
              x: fractionX,
              y: curCircleY + 10,
              text: '1',
            },
            {
              x: fractionX - 25,
              y: curCircleY + 10,
              text: curDirection === 'left' ? '-' : '',
            },
            null,
          ];
        } else {
          curAngleDegree = 360 / curDivisor;
          if (curDirection === 'right') curAngleDegree = 360 - curAngleDegree; // counterclockwise equivalent

          curCircle = game.add.geom.arc(
            validPath.x0,
            curCircleY,
            self.circles.diameter,
            0,
            game.math.degreeToRad(curAngleDegree),
            curIsCounterclockwise,
            curLineColor,
            3,
            curFillColor,
            1
          );
          curCircleInfo.angleDegree = curAngleDegree;

          curFractionItems = [
            {
              x: fractionX,
              y: curCircleY - 2,
              text: `1\n${curDivisor}`,
            },
            {
              x: fractionX - 35,
              y: curCircleY + 15,
              text: curDirection === 'left' ? '-' : '',
            },
            {
              x0: fractionX,
              y0: curCircleY + 2,
              x1: fractionX + 25,
              y1: curCircleY + 2,
              lineWidth: 2,
              color: curLineColor,
            },
          ];
        }

        for (let i = 0; i < 2; i++) {
          const item = game.add.text(
            curFractionItems[i].x,
            curFractionItems[i].y,
            curFractionItems[i].text,
            font
          );
          item.lineHeight = 37;
          curCircleInfo.fraction.labels.push(item);
        }
        if (curFractionItems[2]) {
          const line = game.add.geom.line(
            curFractionItems[2].x0,
            curFractionItems[2].y0,
            curFractionItems[2].x1,
            curFractionItems[2].y1,
            curFractionItems[2].lineWidth,
            curFractionItems[2].color
          );
          line.anchor(0.5, 0);
          curCircleInfo.fraction.labels.push(line);
        } else {
          curCircleInfo.fraction.labels.push(null);
        }
        curCircleInfo.fraction.nominator = curCircleInfo.direc;
        curCircleInfo.fraction.denominator = curDivisor;
        if (!showFractions) {
          curCircleInfo.fraction.labels.forEach((label) => {
            if (label) label.alpha = 0;
          });
        }

        curCircle.rotate = 90;

        // If game is type (b) (select fractions)
        if (gameMode === 'b') {
          curCircle.index = i;
        }

        curCircleInfo.distance = Math.floor(
          validPath.distanceBetweenPoints / curDivisor
        );

        // Add to the list
        curCircle.info = curCircleInfo;
        self.circles.list.push(curCircle);

        self.control.correctX +=
          Math.floor(validPath.distanceBetweenPoints / curDivisor) *
          curCircle.info.direc;
      }

      // Restart if
      // Does not have at least one fraction of type 1/difficulty
      if (!hasBaseDifficulty) {
        restart = true;
      }

      // Calculate next circle
      self.control.nextX =
        validPath.x0 +
        self.circles.list[0].info.distance * self.circles.list[0].info.direc;

      let isBeforeMin = (isAfterMax = false);
      let finalPosition = self.control.correctX.toFixed(1);
      // Restart if
      // In Game mode 'a' and 'b' : Kite position is out of bounds
      if (gameOperation === 'minus') {
        isBeforeMin = finalPosition > validPath.x0;
        isAfterMax =
          finalPosition < validPath.x0 - 5 * validPath.distanceBetweenPoints;
      } else {
        isBeforeMin = finalPosition <= validPath.x0;
        isAfterMax =
          finalPosition > validPath.x0 + 5 * validPath.distanceBetweenPoints;
      }
      if (isBeforeMin || isAfterMax) restart = true;

      if (gameMode === 'b') {
        // If game is type (b), select a random kite place
        kiteX = validPath.x0;

        self.control.correctIndex = game.math.randomInRange(
          self.control.numberOfPlusFractions,
          self.circles.list.length
        );

        for (let i = 0; i < self.control.correctIndex; i++) {
          kiteX +=
            self.circles.list[i].info.distance *
            self.circles.list[i].info.direc;
        }

        finalPosition = kiteX;

        self.blocks.list.forEach((cur) => {
          self.utils.fillCurrentBlock(kiteX, cur.x, cur);
          if (self.utils.isOverBlock(kiteX, cur.x, cur.width, cur))
            self.blocks.cur = cur;
        });

        // Restart if
        // In Game mode 'b' : Top circle position is out of bounds (when on the ground)
        if (gameOperation === 'minus') {
          isBeforeMin = finalPosition > validPath.x0;
          isAfterMax =
            finalPosition < validPath.x0 - 5 * validPath.distanceBetweenPoints;
        } else {
          isBeforeMin = finalPosition < validPath.x0;
          isAfterMax =
            finalPosition > validPath.x0 + 5 * validPath.distanceBetweenPoints;
        }
        if (isBeforeMin || isAfterMax) restart = true;
      }

      return [restart, kiteX];
    },
    renderCharacters: function (validPath, kiteX) {
      // KID
      self.kid = game.add.sprite(
        validPath.x0,
        validPath.y0 - 32 - self.circles.list.length * self.circles.diameter,
        'kid_walking',
        0,
        1.2
      );
      self.kid.anchor(0.5, 0.8);

      self.animation.list.right = [
        'right',
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11],
        4,
      ];
      self.animation.list.left = [
        'left',
        [23, 22, 21, 20, 19, 18, 17, 16, 15, 14, 13, 12],
        4,
      ];

      if (gameOperation === 'minus') {
        self.kid.animation = self.animation.list.left;
        self.kid.curFrame = 23;
      } else {
        self.kid.animation = self.animation.list.right;
      }

      // KITE
      self.kite = game.add.image(kiteX, validPath.y0 - 295, 'kite', 1.8, 0.5);
      self.kite.alpha = 0.5;
      self.kite.anchor(0, 0.5);

      self.kite_line = game.add.image(kiteX, validPath.y0 - 30, 'kite_line', 2);
      self.kite_line.alpha = 0.8;
      self.kite_line.anchor(0.5, 0);

      if (gameMode === 'b') {
        self.kite_line.alpha = 1;
        self.kite.alpha = 1;
      }
    },
    renderMainUI: function () {
      // Help pointer
      self.ui.help = game.add.image(0, 0, 'pointer', 2, 0);

      // Intro text
      const correctMessage =
        gameMode === 'a'
          ? game.lang.circleOne_intro_a
          : game.lang.circleOne_intro_b;
      const treatedMessage = correctMessage.split('\\n');
      self.ui.message = [];
      self.ui.message.push(
        game.add.text(
          context.canvas.width / 2,
          170,
          treatedMessage[0] + '\n' + treatedMessage[1],
          textStyles.h1_
        )
      );
    },
    renderOperationUI: function () {
      /**
       * if game mode A:
       * - left: selected kite position (user selection)
       * - right: correct sum of stack of arcs (pre-set)
       *
       * if game mode B:
       * - left: line created from the stack of arcs (user selection)
       * - right: baloon position (pre-set)
       */

      const renderFloorFractions = (lastIndex) => {
        const divisor = gameDifficulty == 3 ? 4 : gameDifficulty;

        const operator = gameOperation === 'minus' ? '-' : '+';
        const index = lastIndex;
        const blocks = index + 1;

        const valueReal = blocks / divisor;
        const valueFloor = Math.floor(valueReal);
        const valueRest = valueReal - valueFloor;

        let fracNomin = (fracDenomin = fracLine = '');
        // Adds sign on the left of the equation
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
        const index = lastIndex;
        const blocks = index + 1;

        const nominators = [];
        const denominators = [];
        const values = [];
        let valueReal = 0;
        let fracNomin = (fracDenomin = fracLine = '');
        for (let i = 0; i < blocks; i++) {
          const m = self.circles.list[i].info.fraction.denominator || 1;
          const temp = self.circles.list[i].info.fraction.nominator || 0;
          const n = temp < 0 ? -temp : +temp;
          const nm = n / m;
          nominators[i] = n + 0;
          denominators[i] = m + 0;
          values[i] = nm;
          if (gameOperation === 'mixed')
            valueReal = temp < 0 ? valueReal - nm : valueReal + nm;
          else valueReal += nm;
        }

        for (let i = 0; i < blocks; i++) {
          const valueReal = values[i];
          const valueFloor = Math.floor(valueReal);
          const valueRest = valueReal - valueFloor;
          const operator =
            self.circles.list[i].info.fraction.nominator < 0 ? '-' : '+';

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
      const [floorNominators, floorDenominators, floorLines, floorValue] =
        renderFloorFractions(self.blocks.cur.info.index);
      const [stackNominators, stackDenominators, stackLines, stackValue] =
        renderStackFractions(self.circles.cur - 1);

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

      // Render RIGHT part of the operationf
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
    renderEndUI: function () {
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

    // UPDATE
    animateKidHandler: function () {
      let canLowerCircles = undefined;
      let curCircle = self.circles.list[self.circles.cur];
      let curDirec = curCircle.info.direc;

      // Move
      self.circles.list.forEach((circle) => {
        circle.x += self.animation.walkOffsetX * curDirec;
      });
      self.kid.x += self.animation.walkOffsetX * curDirec;
      self.walkedPath[self.control.curWalkedPath].width +=
        self.animation.walkOffsetX * curDirec;

      // Update arc
      curCircle.info.angleDegree += self.animation.angleOffset * curDirec;
      curCircle.angleEnd = game.math.degreeToRad(curCircle.info.angleDegree);

      // When finish current circle
      if (curCircle.info.direction === 'right') {
        canLowerCircles = curCircle.x >= self.control.nextX;
      } else if (curCircle.info.direction === 'left') {
        canLowerCircles = curCircle.x <= self.control.nextX;
        // If just changed from 'right' to 'left' inform to change direction of kid animation
        if (
          self.animation.invertDirection === undefined &&
          self.circles.cur > 0 &&
          self.circles.list[self.circles.cur - 1].info.direction === 'right'
        ) {
          self.animation.invertDirection = true;
        }
      }

      // Change direction of kid animation
      if (self.animation.invertDirection) {
        self.animation.invertDirection = false;
        game.animation.stop(self.kid.animation[0]);

        self.kid.animation = self.animation.list.left;
        self.kid.curFrame = 23;
        game.animation.play('left');

        self.control.curWalkedPath = 1;
        self.utils.renderWalkedPath(
          curCircle.x,
          self.walkedPath[0].y + 8,
          colors.red
        );
      }

      if (canLowerCircles) {
        // Hide current circle
        curCircle.alpha = 0;

        // Lowers kid and other circles
        self.circles.list.forEach((circle) => {
          circle.y += self.circles.diameter;
        });
        self.kid.y += self.circles.diameter;

        self.circles.cur++; // Update index of current circle

        if (self.circles.list[self.circles.cur]) {
          curCircle = self.circles.list[self.circles.cur];
          curDirec = curCircle.info.direc;
          self.control.nextX += curCircle.info.distance * curDirec; // Update next position
        }
      }

      // When finish all circles (final position)
      if (
        self.circles.cur === self.circles.list.length ||
        curCircle.alpha === 0
      ) {
        self.animation.animateKid = false;
        self.control.checkAnswer = true;
      }
    },
    checkAnswerHandler: function () {
      game.timer.stop();

      game.animation.stop(self.kid.animation[0]);

      self.control.isCorrect = game.math.isOverlap(self.kite_line, self.kid);

      const x = self.utils.renderOperationUI();
      if (self.control.isCorrect) {
        completedLevels++;
        // self.kid.curFrame = self.kid.curFrame < 12 ? 24 : 25;
        // console.log(self.kid);
        self.kid.alpha = 0;
        const kidStanding = game.add.sprite(
          self.kid.x,
          self.kid.y,
          'kid_standing',
          5,
          1.2
        );
        kidStanding.anchor(0.5, 0.8);
        self.kid = kidStanding;
        self.kid.alpha = 1;

        self.kite_line.alpha = 0;
        self.kite.x += 25;
        self.kite.y -= 40;

        if (audioStatus) game.audio.okSound.play();
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
      self.animation.counter = 0;

      self.animation.animateKite = true;
    },
    animateKiteHandler: function () {
      self.animation.counter++;

      if (!self.control.isCorrect) {
        self.kite.y -= 2;
        self.kite_line.y -= 2;
      }
      if (self.animation.counter % 40 === 0) {
        const kiteMovement = self.animation.counter % 80 === 0 ? -3 : 3;
        self.kite.y += kiteMovement;
      }
      if (self.animation.counter === 100) {
        self.utils.renderEndUI();
        self.control.showEndInfo = true;
        if (self.control.isCorrect) canGoToNextMapPosition = true;
        else canGoToNextMapPosition = false;
      }
    },
    endLevel: function () {
      game.state.start('map');
    },

    // INFORMATION
    /**
     * Show correct answer
     */
    showAnswer: function () {
      if (!self.control.hasClicked) {
        // On gameMode (a)
        if (gameMode === 'a') {
          self.ui.help.x = self.control.correctX - 20;
          self.ui.help.y = self.road.defaultY;
          // On gameMode (b)
        } else {
          self.ui.help.x = self.circles.list[self.control.correctIndex - 1].x;
          self.ui.help.y = self.circles.list[self.control.correctIndex - 1].y; // -            self.circles.diameter / 2;
        }
        self.ui.help.alpha = 1;
      }
    },

    // HANDLERS
    /**
     * (in gameMode 'b') Function called when player clicked over a valid circle
     *
     * @param {number|object} cur clicked circle
     */
    clickCircleHandler: function (cur) {
      if (!self.control.hasClicked) {
        // On gameMode (a)
        if (gameMode === 'a') {
          self.kite.x = cur;
          self.kite_line.x = cur;
        }

        // On gameMode (b)
        if (gameMode === 'b') {
          document.body.style.cursor = 'auto';

          for (let i in self.circles.list) {
            if (i <= cur.index) {
              self.circles.list[i].alpha = 1; // Keep selected circle
              self.control.selectedIndex = cur.index;
            } else {
              self.circles.list[i].alpha = 0; // Hide unselected circle
              self.kid.y += self.circles.diameter; // Lower kid to selected circle
            }
          }
        }

        if (audioStatus) game.audio.popSound.play();

        // Hide fractions
        if (showFractions) {
          self.circles.list.forEach((circle) => {
            circle.info.fraction.labels.forEach((labelPart) => {
              if (labelPart) labelPart.alpha = 0;
            });
          });
        }

        // Hide solution pointer
        if (self.ui.help != undefined) self.ui.help.alpha = 0;

        self.ui.message[0].alpha = 0;

        navigation.disableIcon(navigation.showAnswerIcon);

        self.kite.alpha = 1;
        self.kite_line.alpha = 1;
        self.walkedPath[self.control.curWalkedPath].alpha = 1;

        self.control.hasClicked = true;
        self.animation.animateKid = true;

        game.animation.play(self.kid.animation[0]);
      }
    },
    /**
     * (in gameMode 'b') Function called when cursor is over a valid circle
     *
     * @param {object} cur circle the cursor is over
     */
    overCircleHandler: function (cur) {
      if (!self.control.hasClicked) {
        document.body.style.cursor = 'pointer';
        for (let i in self.circles.list) {
          const alpha = i <= cur.index ? 1 : 0.4;
          self.circles.list[i].alpha = alpha;
          if (showFractions) {
            self.circles.list[i].info.fraction.labels.forEach((lbl) => {
              if (lbl) lbl.alpha = alpha;
            });
          }
        }
      }
    },
    /**
     * (in gameMode 'b') Function called when cursor leaves a valid circle
     */
    outCircleHandler: function () {
      if (!self.control.hasClicked) {
        document.body.style.cursor = 'auto';
        const alpha = 1;
        self.circles.list.forEach((circle) => {
          circle.alpha = alpha;
          if (showFractions) {
            circle.info.fraction.labels.forEach((lbl) => {
              if (lbl) lbl.alpha = alpha;
            });
          }
        });
      }
    },
    /** TODO */
    isOverBlock: function (x, blockX, blockWidth) {
      if (
        ((gameOperation === 'plus' || gameOperation === 'mixed') &&
          x >= blockX &&
          x < blockX + blockWidth) ||
        (gameOperation === 'minus' && x <= blockX && x > blockX + blockWidth)
      )
        return true;
      return false;
    },
    /** TODO */
    isOverRoad: function (x, y, roadX, roadWidth) {
      if (y > 150) {
        if (
          ((gameOperation === 'plus' || gameOperation === 'mixed') &&
            x >= roadX &&
            x < roadX + roadWidth) ||
          (gameOperation === 'minus' &&
            x <= roadX &&
            x > roadX + roadWidth * self.control.directionModifier)
        )
          return true;
      }
      return false;
    },
    /** TODO */
    fillCurrentBlock: function (x, blockX, block) {
      block.fillColor =
        ((gameOperation === 'plus' || gameOperation === 'mixed') &&
          x > blockX) ||
        (gameOperation === 'minus' && x < blockX)
          ? colors.red
          : 'transparent';
    },
  },

  events: {
    /**
     * Called by mouse click event
     *
     * @param {object} mouseEvent contains the mouse click coordinates
     */
    onInputDown: function (mouseEvent) {
      const x = game.math.getMouse(mouseEvent).x;
      const y = game.math.getMouse(mouseEvent).y;

      // GAME MODE A : click road
      if (gameMode === 'a') {
        const isValidX = self.utils.isOverRoad(
          x,
          y,
          self.road.x,
          self.road.width
        );
        if (isValidX) {
          self.utils.clickCircleHandler(
            self.blocks.cur.x + self.blocks.cur.width
          );
          document.body.style.cursor = 'auto';
        }
      }

      // GAME MODE B : click circle
      if (gameMode === 'b') {
        self.circles.list.forEach((circle) => {
          const isValid =
            game.math.distanceToPointer(
              x,
              circle.xWithAnchor,
              y,
              circle.yWithAnchor
            ) <=
            (circle.diameter / 2) * circle.scale;
          if (isValid) self.utils.clickCircleHandler(circle);
        });
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
    onInputOver: function (mouseEvent) {
      const x = game.math.getMouse(mouseEvent).x;
      const y = game.math.getMouse(mouseEvent).y;
      let isOverCircle = false;

      if (gameMode === 'a' && !self.control.hasClicked) {
        const isValidX = self.utils.isOverRoad(
          x,
          y,
          self.road.x,
          self.road.width
        );
        if (isValidX) {
          // GAME MODE A : kite follow mouse
          self.blocks.cur = self.blocks.list[0];
          self.blocks.list.forEach((cur) => {
            self.utils.fillCurrentBlock(x, cur.x, cur);
            if (self.utils.isOverBlock(x, cur.x, cur.width, cur))
              self.blocks.cur = cur;
          });
          const newX = self.blocks.cur.x + self.blocks.cur.width;
          self.kite.x = newX;
          self.kite_line.x = newX;

          document.body.style.cursor = 'pointer';
        } else {
          document.body.style.cursor = 'auto';
        }
      }

      // GAME MODE B : hover circle
      if (gameMode === 'b' && !self.control.hasClicked) {
        self.circles.list.forEach((circle) => {
          const isValid =
            game.math.distanceToPointer(
              x,
              circle.xWithAnchor,
              y,
              circle.yWithAnchor
            ) <=
            (circle.diameter / 2) * circle.scale;
          if (isValid) {
            self.utils.overCircleHandler(circle);
            isOverCircle = true;
          }
        });
        if (!isOverCircle) self.utils.outCircleHandler();
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
     * @see /php/squareOne.js
     */
    postScore: function () {
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
        'numCircles:' +
        self.circles.list.length +
        ', valCircles: ' +
        self.control.divisorsList +
        ' kiteX: ' +
        self.kite_line.x +
        ', selIndex: ' +
        self.control.selectedIndex;

      // FOR MOODLE
      sendToDatabase(data);
    },
  },
};
