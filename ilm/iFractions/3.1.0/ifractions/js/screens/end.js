/******************************
 * This file holds game states.
 ******************************/

/** [ENDING STATE] Ending screen shown when the player has completed all 4 levels and therefore completed the game.
 *
 * @namespace
 */
const endState = {
  ui: undefined,
  control: undefined,

  character: undefined,
  kite: undefined,

  /**
   * Main code
   */
  create: function () {
    this.control = {
      animate: true,
      preAnimate: false,
      waitUserAction: false,
      endUpdate: false,
      counter: 0,
      direc: 1,
    };

    this.ui = {
      continue: {
        button: undefined,
        text: undefined,
      },
    };

    renderBackground('end');

    self.utils.renderProgressBar();

    // Back trees
    game.add
      .image(-100, context.canvas.height - 200, 'tree_4', 1.05)
      .anchor(0, 1);
    game.add
      .image(360 + 400 + 400, context.canvas.height - 200, 'tree_4', 1.05)
      .anchor(0, 1);

    self.utils.renderCharacters();
    if (this.control.animate) game.animation.play(this.character.animation[0]);

    // Front tree
    game.add
      .image(30 + 200 + 100, context.canvas.height - 30, 'tree_4', 1.275)
      .anchor(0, 1);

    game.event.add('click', this.events.onInputDown);
    game.event.add('mousemove', this.events.onInputOver);
  },

  /**
   * Game loop
   */
  update: function () {
    if (isDebugMode) {
      if (debugState.end.skip && debugState.end.stop) {
        self.control.animate = false;
      }

      if (debugState.moodle.emulate) {
        moodleVar = debugState.moodle.info;
        game.state.start('studentReport');
        return;
      }
    }

    self.control.counter++;

    // Character running
    if (self.control.animate) {
      if (self.character.x <= 1550) {
        self.character.x += 4;
        if (self.kite) self.kite.x += 4;
      } else {
        self.control.animate = false;
        game.animation.stop(self.character.animation[0]);
        self.character.alpha = 0;
        if (self.kite) self.kite.alpha = 0;
        self.control.waitUserAction = true;
        self.utils.renderEndUI();
      }

      if (self.kite && self.character.x % 40 === 0) {
        const kiteMovement = self.character.x % 80 === 0 ? 3 : -3;
        self.kite.y += kiteMovement;
      }
    }

    if (!moodle && self.control.endLevel) {
      completedLevels = 0;
      game.state.start('menu');
    }

    game.render.all();
  },

  utils: {
    renderProgressBar: () => {
      const x0 = 300;
      const y0 = 20;

      // Bar content
      for (let i = 0; i < 4; i++) {
        game.add.image(
          context.canvas.width - x0 + 37.5 * i,
          y0,
          'progress_bar_tile'
        );
      }

      // Bar wrapper
      game.add.geom.rect(
        context.canvas.width - x0 + 1,
        y0 + 1,
        149, //150, //149,
        34, //35, //34,
        'transparent',
        1,
        colors.blue,
        3
      );

      // Percentage label
      game.add.text(
        context.canvas.width - x0 + 160,
        y0 + 33,
        '100%',
        textStyles.h2_
      ).align = 'left';

      // Difficulty label
      game.add.text(
        context.canvas.width - x0 - 10,
        y0 + 33,
        game.lang.difficulty + ' ' + gameDifficulty,
        textStyles.h2_
      ).align = 'right';
    },
    renderCharacters: () => {
      gameList[gameId].assets.end.building();

      if (gameName === 'circleOne') {
        self.kite = game.add.image(
          0 + 10,
          context.canvas.height - 240 + 20,
          'kite_reverse',
          1.8,
          1
        );
        self.kite.anchor(1, 1);
      }

      self.character = gameList[gameId].assets.end.character();
      self.character.animation =
        gameList[gameId].assets.end.characterAnimation();
    },
    renderEndUI: () => {
      const btnY = context.canvas.height / 2;
      let btnTextFont = textStyles.btn;

      // Continue Button
      if (!moodle) {
        self.ui.continue.button = game.add.geom.rect(
          context.canvas.width / 2,
          btnY,
          600,
          100,
          colors.green
        );
        self.ui.continue.button.anchor(0.5, 0.5);
      } else {
        btnTextFont = { ...btnTextFont, fill: colors.blue };
      }

      // Continue button text
      self.ui.continue.text = game.add.text(
        context.canvas.width / 2,
        btnY + 16,
        moodle ? game.lang.submit : game.lang.back_to_menu,
        btnTextFont
      );

      // Title
      const font = { ...textStyles.btnLg };
      font.fill = colors.blue;
      game.add.text(
        context.canvas.width / 2,
        btnY - 100,
        game.lang.congratulations,
        font
      );
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

      if (!moodle && self.control.waitUserAction) {
        if (game.math.isOverIcon(x, y, self.ui.continue.button)) {
          if (audioStatus) game.audio.popSound.play();
          self.control.endLevel = true;
        }
      }
    },

    /**
     * Called by mouse move event
     *
     * @param {object} mouseEvent contains the mouse move coordinates
     */
    onInputOver: function (mouseEvent) {
      const x = game.math.getMouse(mouseEvent).x;
      const y = game.math.getMouse(mouseEvent).y;

      if (!moodle && self.control.waitUserAction) {
        if (game.math.isOverIcon(x, y, self.ui.continue.button)) {
          // If pointer is over icon
          document.body.style.cursor = 'pointer';
          self.ui.continue.button.scale =
            self.ui.continue.button.initialScale * 1.1;
          self.ui.continue.text.style = textStyles.btnLg;
        } else {
          // If pointer is not over icon
          self.ui.continue.button.scale =
            self.ui.continue.button.initialScale * 1;
          document.body.style.cursor = 'auto';
          self.ui.continue.text.style = textStyles.btn;
        }
      }
    },
  },
};
