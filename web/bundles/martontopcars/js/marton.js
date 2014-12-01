/**
 * Created by Marci on 27/10/14.
 */

function preloadImages(array, el){

    var newImages = [], loadedImages = 0, arrLength = array.length, background_2, loadingContainer, progressP;
    var container = (typeof el === "undefined" ? document.body : el);

    var postAction = function(){};

    var arr = (typeof array != "object") ? [array] : array;

    // Executes call-back function after preloading all images
    function imageLoadPost(){

        loadedImages++;
        progressP.innerHTML = Math.round(100*(loadedImages/arrLength)) + "%";
        if (loadedImages == arrLength){
            onFinish();
            postAction(newImages);
        }
    }

    // Creates loading screen
    function onCreate(){

        loadingContainer = document.createElement("div");
        loadingContainer.className  = "loading_container";

        progressP = document.createElement("p");
        progressP.className = "progress_p";
        loadingContainer.appendChild(progressP);

        container.appendChild(loadingContainer);
    }

    // Removes loading screen
    function onFinish(){

        container.removeChild(loadingContainer);
    }

    onCreate();

    for (var i=0; i<arrLength; i++){
        newImages[i] = new Image();
        newImages[i].src = arr[i];
        newImages[i].onload = function(){
            imageLoadPost();
        };
        newImages[i].onerror = function(){
            imageLoadPost();
        }
    }

    // Return blank object with done() method
    return {
        done:function(f){
            postAction= f || postAction;
        }
    }
}

var ImageInputModule = function(){

    var inputSource, targetElement, clickElement;

    function init(sourceId, targetId, clickId){

        clickElement = document.getElementById(clickId);
        inputSource = document.getElementById(sourceId);
        targetElement = document.getElementById(targetId);
        registerEventListeners();
    }

    function registerEventListeners(){

        $(document).ready(function(){
            $(inputSource).change(function(){
                readURL(this);
            });

            $(clickElement).click(function(){
                $(inputSource).click();
            })
        });
    }

    // Set the preview image
    function readURL(input) {

        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $(targetElement).attr('src', e.target.result);
            };

            reader.readAsDataURL(input.files[0]);
        }
    }

    return{
        init: init
    }

}();


function Game(){

    var game = this;

    // Default settings
    this.setting = {
        "players" : 4,
        "imgFolder": "",
        "imgFormat":".png",
        "ajaxPostScore":"",
        "gameContainer": document.getElementById("card_game")
    };

    // Stores progress details about the logged in user
    var userInfo;
    this.setUserInfo = function(info){
        userInfo = info;
    };

    // Whole deck of cards
    this.cards = {};
    this.setCards = function (deck){

        game.cards.deck = [];
        for (key in deck){
            if (deck.hasOwnProperty(key)){
                game.cards.deck.push(deck[key]);
            }
        }

        game.cards.len   = game.cards.deck.length;

        return this;
    };

    this.userCards = {};

    // Gives 10 cards to the user by default
    this.setUserCards = function(deck){

        for (key in deck){
            if (deck.hasOwnProperty(key)){
                game.player.user.deck.push(deck[key]);
            }
        }

        console.log(deck);
        return this;
    };

    // Gives 10 unique random cards to the opponents by default
    this.setOpponentCards = function(){

        var self = this;

        for (key in self.player.opponent){

            if (self.player.opponent.hasOwnProperty(key)){

                var deck = self.cards.deck;

                for (var i=1; i<10; i++){

                    var random = Math.floor(Math.random() * (self.cards.deck.length - 0)) + 0;
                    var randomCard = deck[random];

                    self.player.opponent[key].deck.push(randomCard);

                    deck.slice(random, 1);
                }
            }
        }
    };

    // Returns a random card from the deck
    this.getRandomCard = function(deck){

        var random = Math.floor(Math.random() * (deck.length - 0)) + 0;
        return deck[random];
    };

    this.preloadImages = function(callback){

        if (typeof callback === "undefined") callback = function(){};
        var arr = [];
        for (var i=0; i<game.cards.len;i++){
            arr.push(game.setting.imgFolder + game.cards.deck[i].image + game.setting.imgFormat);
        }
        preloadImages(arr).done(callback);
    };

    var defaultField =  {
        speed:{
            label:"Speed:",
            unit:"km/h",
            columnName:"speed"
        },
        power:{
            label:"Power:",
            unit:"hp",
            columnName:"power"
        },
        torque:{
            label:"Torque:",
            unit:"Nm",
            columnName:"torque"
        },
        acceleration:{
            label:"Acceleration:",
            unit:"s",
            columnName:"acceleration"
        },
        weight:{
            label:"Weight:",
            unit:"kg",
            columnName:"weight"
        }
    };

    var uiContainer = {};
    this.previousActiveRows = [];

    this.topPanel = null;
    function getTopPanel(){
        return game.topPanel;
    }

    // Containing agents of the game
    this.player = {
            user:null,
            host:null,
            opponent:[]
    };

    // States of the game
    this.isPaused        = false; // Normally changed when opening settings
    this.hasRoundEnded   = false; // Normally changed after each select
    this.isEnded         = true; // Normally changed when losing or restarting
    this.hostsTurn       = true; // Normally changed before and after the opponents selected.

    // To be overridden
    this.loseAction = function(){
        game.isEnded = true;
        game.roundControls.newGame();
    };

    // To be overridden
    this.winAction = function(){
        game.roundControls.nextRound();
    };

    // To be overridden
    this.start = function(){

        game.isPaused      = false;
        game.isEnded       = false;
        game.hasRoundEnded = false;

        game.player.host.setCard(game.getRandomCard(game.cards.deck)).showCard();

        return this;
    };

    // To be overridden
    this.restart = function(){

        if (game.isEnded && game.hasRoundEnded){

            game.isEnded = false;
            game.hasRoundEnded = false;

            AnimateModule.createStreakCount(uiContainer.streakText, "new", "0");

            game.newRound();
        }
    };

    // To be overridden
    this.endOfRoundAction = function(){

        // Data to post to the server
        var data = {
            score:       game.player.user.score,
            streak:      game.player.user.streak,
            roundResult: game.player.user.roundResult
        };

        var success = function(data){
            var levelChange    = data.levelChange;
            var userLevelInfo = data.userLevelInfo;

            game.topPanel.update(levelChange, userLevelInfo);
        };

        // Ajax call
        postToServer(game.setting.ajaxPostScore, data, success);
    };

    // To be overridden
    this.nextRound = function(){

        if (game.hasRoundEnded){

            game.hasRoundEnded = false;

            game.newRound();
        }
    };

    // To be overridden
    this.newRound = function(){

        // Hide Cards and Generate new card for host
        game.player.host.hideCard(function(){
            game.player.host.setCard(game.getRandomCard(game.cards.deck)).showCard();
        });

        for (var i=0;i<game.player.opponent.length;i++){
            game.player.opponent[i].hideCard();
        }

        for (var i=0;i<game.previousActiveRows.length;i++){
            game.previousActiveRows[i].className = "card_row";
        }

        game.previousActiveRows = [];

        game.roundControls.reset();
    };

    // To be overridden
    this.reorganisePlayers = function(player){
        // No need to change the host in the default version of the game
    };

    // To be overridden
    this.assignCardsToPlayers = function(){

        // Assign cards to all opponents
        for (var i=0;i<game.player.opponent.length;i++){
            game.player.opponent[i].setCard(game.getRandomCard(game.cards.deck)).showCard();
        }
    };

    this.selectField = function(field){

        if (!game.hasRoundEnded && game.hostsTurn){

            // Prevent player from selecting multiple times in one round
            game.hasRoundEnded = true;

            // Detect clicked property
            var property = field.getAttribute("name");

            // Assign cards to the opponents
            // TODO: separate this into a public function and make sure that the user doesn't get a new card assigned.
            game.assignCardsToPlayers();

            var playerQueue = Object.create(game.player.opponent);

            playerQueue.push(game.player.host);

            // Make a copy of the players array
            var newPlayerQueue = playerQueue.slice();

            // Compare each player with everyone once
            for (var i=0;i<playerQueue.length;i++){

                var currentPlayer = newPlayerQueue.shift();

                for (key in newPlayerQueue){
                    if (newPlayerQueue.hasOwnProperty(key)){
                        var subscore = calculateSubscore(property, currentPlayer.getCard(property), newPlayerQueue[key].getCard(property));

                        currentPlayer.roundScore -= subscore;
                        newPlayerQueue[key].roundScore += subscore;
                    }
                }
            }

            // Sort players in terms of score
            playerQueue.sort(compare);

            // Count draws on the first place
            var drawCounter = 0;

            for (var i=0;i<playerQueue.length;i++){
                if (typeof playerQueue[i+1] !== 'undefined'){
                    if (playerQueue[i].roundScore === playerQueue[i+1].roundScore){
                        drawCounter++;
                    }else{
                        if (drawCounter > 0) drawCounter++;
                        break;
                    }
                }
            }

            var foundWinner = false;

            // Add the round score to the players' overall score
            for (var i=0;i<playerQueue.length;i++){

                // Avoid negative score
                if (playerQueue[i].roundScore > 0) playerQueue[i].score += playerQueue[i].roundScore;
                var newClass = (playerQueue[i].roundScore > 0 ? " score_green" : " score_red");
                AnimateModule.createFloatingText(playerQueue[i].viewHolder[property], playerQueue[i].roundScore, newClass);
                game.previousActiveRows.push(playerQueue[i].viewHolder[property]);

                if (foundWinner){
                    // Lose
                    playerQueue[i].viewHolder[property].className = "card_row row_red";
                    playerQueue[i].roundResult = "lose";
                    playerQueue[i].streak = 0;
                }else if (drawCounter > 0){
                    // Draw
                    drawCounter--;
                    playerQueue[i].viewHolder[property].className = "card_row row_draw";
                    playerQueue[i].roundResult = "draw";
                    if (drawCounter === 0) foundWinner = true;
                }else{
                    // Win
                    playerQueue[i].viewHolder[property].className = "card_row row_green";
                    playerQueue[i].roundResult = "win";
                    foundWinner = true;
                    playerQueue[i].streak += game.setting.players-1;

                    // Make sure we know which player won the round
                    game.reorganisePlayers(playerQueue[i]);
                }

                playerQueue[i].roundScore = 0;
            }

            AnimateModule.createStreakCount(uiContainer.streakText, game.player.user.roundResult, game.player.user.streak);

            // Deciding game state: WIN/DRAW or LOSE and acting accordingly
            if (game.player.host.roundResult === "lose"){

                game.loseAction();
            }else{

                game.winAction();
            }

            game.endOfRoundAction();
        }
    };

    // Helper function to calculate the score of players
    var calculateSubscore = function(property, p1val, p2val){

        var subscore = 0;

        if (property === defaultField.acceleration.columnName){

            subscore = Math.round(500*(p1val - p2val));

        } else if (property === defaultField.weight.columnName){

            subscore = p1val - p2val;

        }else{

            subscore = p2val - p1val;
        }

        return subscore;
    };

    // Helper function to sort player objects
    var compare = function(p1,p2){
        if (p1.roundScore < p2.roundScore)
            return 1;
        if (p1.roundScore > p2.roundScore)
            return -1;
        return 0;
    };

    function createUI(){

        // Start by allocating the container of the game
        uiContainer.container = game.setting.gameContainer;

        // Create top panel
        game.topPanel = new TopPanel();
        game.topPanel.setContainer(uiContainer);
        game.topPanel.createUI();

        // Create elements that don't belong to anyone
        var battlefield = new Battlefield();
        uiContainer.battlefield = battlefield.create();

        // Main player
        var host = new Player();
        var card = new Card();
        var elements = card.create(defaultField);
        uiContainer.battlefield.appendChild(elements.cardFragment);

        // Control Panel
        game.roundControls.init(uiContainer.battlefield);
        uiContainer.streakText = game.roundControls.getStreakText();

        host.viewField = elements.fieldHolder;
        host.viewHolder = elements.viewHolder;
        game.player.host = host;
        game.player.user = game.player.host;

        // Opponents
        for (var i=0;i<game.setting.players-1;i++){

            var opponent = new Player();
            card = new Card();
            elements = card.create(defaultField);
            opponent.viewField = elements.fieldHolder;
            opponent.viewHolder = elements.viewHolder;
            uiContainer.battlefield.appendChild(elements.cardFragment);
            game.player.opponent.push(opponent);
        }

        return this;
    }

    function removeUI(){

        while (game.setting.gameContainer.firstChild) {
            game.setting.gameContainer.removeChild(game.setting.gameContainer.firstChild);
        }
    }

    // Module responsible for re-appearing buttons after each round.
    this.roundControls = (function(){

        var container;
        var streakContainer;
        var streakText;
        var buttonContainer;

        var init = function(view){
            container = makeContainer(view);
            return this;
        };

        function makeContainer(view){

            var controlPanel = document.createElement("div");
            controlPanel.className = "player_controls";
            controlPanel.id = "control_panel";
            view.appendChild(controlPanel);

            streakContainer = document.createElement("div");
            streakContainer.className = "streak_container";
            streakText = document.createElement("p");
            streakText.innerText  = "0";
            streakContainer.appendChild(streakText);
            controlPanel.appendChild(streakContainer);

            buttonContainer = document.createElement("div");
            buttonContainer.className = "button_container";
            controlPanel.appendChild(buttonContainer);

            return controlPanel;
        }

        function makeNextButton(){

            var button = document.createElement("div");
            button.className = "bt_nextRound bt";
            button.addEventListener("click",game.nextRound);
            buttonContainer.appendChild(button);
            var t = document.createTextNode("Next");
            button.appendChild(t);
            $(button).fadeIn(100);
        }

        function makeNewGameButton(){

            var button = document.createElement("div");
            button.className = "bt_new_game bt";
            button.addEventListener("click",game.restart);
            buttonContainer.appendChild(button);
            var t = document.createTextNode("Restart");
            button.appendChild(t);
            $(button).fadeIn(100);
        }

        function removeAllButtons(){
            while (buttonContainer.firstChild) {
                buttonContainer.removeChild(buttonContainer.firstChild);
            }
        }

        return {
            init: init,
            nextRound: makeNextButton,
            newGame: makeNewGameButton,
            reset: removeAllButtons,
            getStreakText: function(){
                return streakText;
            }
        }
    })();

    this.ProgressModule = function(){

        function createCardIndicator(container){

            var box = document.createElement("div");
            box.className = "indicator-box";
            container.insertBefore(box, container.firstChild);
            return box;
        }

        function updateCardIndicator(noOfCards, box){

            for (var i=0; i<noOfCards; i++){

                var miniCard = document.createElement("span");
                box.appendChild(miniCard);
            }
        }

        return {
            createCardIndicator: createCardIndicator,
            updateCardIndicator: updateCardIndicator
        }
    }();

    this.test = function(){
        createUI();
        game.start();
        getTopPanel().update("default", userInfo);
        game.player.host.score += userInfo.score;
    };

    // Classes

    function Card(){}

    Card.prototype = {

        constructor: Card,
        create: function(defaultField){

            var fieldHolder = {};
            var viewHolder = {};

            var cardFragment = document.createElement("div");
            cardFragment.className = "card_fragment";
            viewHolder.fragment = cardFragment;

            // Card
            var cardBlock = document.createElement("div");
            cardBlock.className = "card_block";
            cardFragment.appendChild(cardBlock);

            var playerCard = document.createElement("div");
            playerCard.className = "player_card";
            cardBlock.appendChild(playerCard);
            viewHolder.card = playerCard;

            // Card Name
            var cardName = document.createElement("div");
            cardName.className = "card_name";
            playerCard.appendChild(cardName);

            fieldHolder.model = cardName;
            viewHolder.model = cardName;

            // Card Image
            var cardImage = document.createElement("div");
            cardImage.className = "card_image";
            playerCard.appendChild(cardImage);

            var img = document.createElement("img");
            cardImage.appendChild(img);

            fieldHolder.image = img;
            viewHolder.image = cardImage;


            // Rest
            var cardRow, rowLabel, t;

            for(key in defaultField){
                if(defaultField.hasOwnProperty(key)){
                    cardRow = document.createElement("div");
                    cardRow.className = "card_row";
                    cardRow.setAttribute("name",key);
                    cardRow.addEventListener("click",function(){
                        game.selectField(this);
                    });
                    playerCard.appendChild(cardRow);
                    viewHolder[key] = cardRow;

                    rowLabel = document.createElement("span");
                    rowLabel.className = "row_label";
                    cardRow.appendChild(rowLabel);

                    t = document.createTextNode(defaultField[key].label);
                    rowLabel.appendChild(t);

                    // Changing field
                    rowLabel = document.createElement("span");
                    cardRow.appendChild(rowLabel);
                    fieldHolder[key] = rowLabel;

                    rowLabel = document.createElement("span");
                    rowLabel.className = "row_unit";
                    cardRow.appendChild(rowLabel);

                    t = document.createTextNode(defaultField[key].unit);
                    rowLabel.appendChild(t);
                }
            }

            return {
                fieldHolder: fieldHolder,
                viewHolder: viewHolder,
                cardFragment: cardFragment
            };
        }
    };

    function Player(){

        // DOM Fields
        this.viewField = {};

        // DOM Rows and Elements
        this.viewHolder = {};

        // Values
        this.card = {};

        // Deck
        this.deck = [];

        this.roundScore  = 0;
        this.roundResult = 0;

        this.score = 0;

        this.streak = 0;
    }

    Player.prototype = {

        constructor: Player,
        getCard: function(property){

            var self = this;

            return self.card[property];
        },
        setCard: function(newCard){

            var self = this;

            self.card = newCard;

            for(key in self.card){
                if(self.card.hasOwnProperty(key) && key != "id" && key != "price"){
                    if (key !== "image"){
                        self.viewField[key].innerHTML = self.card[key];
                    }else{
                        self.viewField[key].src = game.setting.imgFolder + self.card[key] + game.setting.imgFormat;
                    }
                }
            }

            return this;
        },
        showCard: function(callback){

            var self = this;

            if (typeof callback === 'undefined') callback = function(){};
            $(self.viewHolder.card).fadeIn(150, callback);
            return this;
        },
        hideCard: function(callback){

            var self = this;

            if (typeof callback === 'undefined') callback = function(){};
            $(self.viewHolder.card).fadeOut(150, callback);
            return this;
        }
    };

    function TopPanel(){

        var container;
        this.setContainer = function(c){
            container = c;
        };

        var attribute = {
            score: 0,
            lowScoreLimit: 0,
            highScoreLimit: 0
        };

        var ui = {};

        this.createUI = function(){

            container.topPanel = document.getElementById("top_panel");

            // Filled bar of Score
            ui.fill = document.getElementById("s_fill");

            // Text of Score
            ui.score = document.getElementById("s_score");
        };

        this.update = function(levelChange, userLevelInfo){
            // userLevelInfo is an object with attributes: "lowScoreLimit", "highScoreLimit", "level", "score"

            var previousScore = attribute.score;

            switch (levelChange){
                case "up":

                    // Animation till the top
                    AnimateModule.animateIncrement(previousScore, attribute.highScoreLimit, ui.score);
                    animateFill(previousScore, attribute.highScoreLimit, function(){

                        // TODO: level up graphics
                        setAttributes(userLevelInfo);

                        setUI();

                        // Animation till new score
                        AnimateModule.animateIncrement(attribute.lowScoreLimit, attribute.score, ui.score);
                        animateFill(attribute.lowScoreLimit, attribute.score);

                    });

                    break;

                case "down":

                    // Animation till the bottom
                    AnimateModule.animateIncrement(previousScore, attribute.lowScoreLimit, ui.score);
                    animateFill(previousScore, attribute.lowScoreLimit, function(){

                        // TODO: level up graphics
                        setAttributes(userLevelInfo);

                        setUI();

                        // Animation till new score
                        AnimateModule.animateIncrement(attribute.highScoreLimit, attribute.score, ui.score);
                        animateFill(attribute.highScoreLimit, attribute.score);

                    });

                    break;

                default:

                    setAttributes(userLevelInfo);

                    setUI();

                    AnimateModule.animateIncrement(previousScore, attribute.score, ui.score);
                    animateFill(previousScore, attribute.score);

                    break;
            }


            return this;

            function setAttributes(userLevelInfo){

                attribute.lowScoreLimit   = userLevelInfo.low_score_limit;
                attribute.score             = userLevelInfo.score;
                attribute.highScoreLimit  = userLevelInfo.high_score_limit;
            }

            function setUI(){

                //ui.lowScoreLimit.innerHTML = attribute.score - attribute.lowScoreLimit;
                //ui.highScoreLimit.innerHTML = attribute.highScoreLimit - attribute.score + " until next level";
            }

            function animateFill (oldScore, newScore, callback){

                callback = typeof callback !== 'undefined' ? callback : function(){};

                var oldWidth = Math.round(100*(oldScore-attribute.lowScoreLimit)/(attribute.highScoreLimit - attribute.lowScoreLimit));
                $(ui.fill).css({"width":oldWidth+"%"});

                var newWidth = Math.round(100*(newScore-attribute.lowScoreLimit)/(attribute.highScoreLimit - attribute.lowScoreLimit));
                if (newWidth>100) newWidth = 100;

                $(ui.fill).promise().done(function(){
                    // 200  - 400 ms
                    $(this).animate({"width":newWidth+"%"}, 200, callback);
                });
            }
        }
    }

    function Battlefield(){

        this.create = function(){

            var battlefield = document.getElementById("battlefield");
            return battlefield;
        }
    }
}

// Classic Version of the game

function ClassicGame(){}

ClassicGame.prototype = new Game();
ClassicGame.prototype.constructor = ClassicGame;

ClassicGame.prototype.start = function(){

    var self = this;

    // Set players' cards
    self.setUserCards(self.userCards);

    self.setOpponentCards();

    // Create and Fill up additional UI elements
    self.player.host.viewHolder.indicator =
        self.ProgressModule.createCardIndicator(self.player.host.viewHolder.fragment);

    self.ProgressModule.updateCardIndicator(self.player.host.deck.length, self.player.host.viewHolder.indicator);

    for (key in self.player.opponent){
        if (self.player.opponent.hasOwnProperty(key)){

            self.player.opponent[key].viewHolder.indicator =
            self.ProgressModule.createCardIndicator(self.player.opponent[key].viewHolder.fragment);
            self.ProgressModule.updateCardIndicator(10, self.player.opponent[key].viewHolder.indicator)
        }
    }

    self.isPaused      = false;
    self.isEnded       = false;
    self.hasRoundEnded = false;

    self.player.host.setCard(self.getRandomCard(self.player.host.deck)).showCard();

    return this;
};

ClassicGame.prototype.reorganisePlayers = function(player){
    // Takes the winner as an argument

    var self = this;

    self.player.opponent.push(self.player.host);
    self.player.host = player;

    var index = self.player.opponent.indexOf(player);
    if (index > -1){
        self.player.opponent.splice(index, 1);
    }
};

ClassicGame.prototype.assignCardsToPlayers = function(){

    var self = this;

    // Assign cards to all opponents
    for (var i=0;i<self.player.opponent.length;i++){

        // Except for the main player - since she already received a card
        if(self.player.opponent[i] !== self.player.user){
            self.player.opponent[i].setCard(self.getRandomCard(self.player.opponent[i].deck)).showCard();
        }
    }
};

ClassicGame.prototype.newRound = function () {

    var self = this;

    for (var i=0;i<self.previousActiveRows.length;i++){
        self.previousActiveRows[i].className = "card_row";
    }

    self.previousActiveRows = [];

    self.roundControls.reset();

    // Hide opponent cars
    for (var i=0;i<self.player.opponent.length;i++){
        self.player.opponent[i].hideCard();
    }

    if (self.player.host == self.player.user) {

        // TODO: Instead of getting a random card from the players, get one in order (queue)
        // The game should move on with the user being the host
        self.player.host.hideCard(function(){
            self.player.host.setCard(self.getRandomCard(self.player.host.deck)).showCard();
        });
    } else {

        // The game should move on with the computer being the host

        // Assign new card for host
        self.player.host.hideCard(function(){

            // Assign new card for user
            self.player.user.setCard(self.getRandomCard(self.player.user.deck)).showCard(function(){

                // TODO: Count-down
                self.player.host.setCard(self.getRandomCard(self.player.host.deck)).showCard(function(){

                    // TODO: Choice algorithm
                    self.selectField(self.player.host.viewHolder.speed);
                });
            })
        });
    }
};

ClassicGame.prototype.endOfRoundAction = function(){

    var self = this;

    // TODO: Check for winning


    // Data to post to the server
    var data = {
        score:       self.player.user.score,
        streak:      self.player.user.streak,
        roundResult: self.player.user.roundResult
    };

    var success = function(data){
        var levelChange    = data.levelChange;
        var userLevelInfo = data.userLevelInfo;

        self.topPanel.update(levelChange, userLevelInfo);
    };

    // Ajax call
    postToServer(self.setting.ajaxPostScore, data, success);
};

var AnimateModule = function(){

    // Helper function to show a value increment/decrement
    function animateIncrement(oldScore, newScore, el){

        var PRINT_AMOUNT = 8;

        var step = Math.ceil(Math.abs(oldScore-newScore)/8);

        if (oldScore < newScore){
            var compare = function(s1,s2){
                return s1 + step >= s2;
            };
            var modify = function (){
                oldScore += step;
            };
        }else{
            var compare = function(s1,s2){
                return s1 - step <= s2;
            };
            var modify = function (){
                oldScore -= step;
            };
        }

        var interval = setInterval(function() {
            el.innerHTML = oldScore;
            if (compare(oldScore, newScore)){
                clearInterval(interval);
                el.innerHTML = newScore;
            }
            modify();
        }, Math.round(200/PRINT_AMOUNT));
    }

    // Helper function to show floating value animations
    function createFloatingText(el, value, newClass){

        var rowLabel = document.createElement("div");
        rowLabel.className = "card_row_subscore";
        rowLabel.className += newClass;
        el.appendChild(rowLabel);

        var t = document.createTextNode(value);
        rowLabel.appendChild(t);

        $(rowLabel).fadeIn(150, function(){
            $(this).animate({"margin-top":"-150px","opacity":"0"},1000, function(){
                el.removeChild(rowLabel);
            });
        });

        return rowLabel;
    }

    // Helper function to update Streak counter
    function animateStreakCount(el, result, count){
        switch (result){
            case "win":
                el.innerText = count;
                $(el).css({"color":"rgba(0, 128, 0, 0.8)"});
                $(el).animate({"font-size":"80px"}, 200, function(){
                    $(this).animate({"font-size":"25px"}, 200);
                });
                break;
            case "lose":
                $(el).css({"color":"rgb(223, 79, 79)"});
                break;
            default:
                $(el).css({"color":"grey"});
                el.innerText = count;
                break;
        }
    }

    return{
        createFloatingText : createFloatingText,
        createStreakCount  : animateStreakCount,
        animateIncrement   : animateIncrement
    }
}();

function postToServer(url, data, success){

    $.ajax({
        type: "POST",
        url: url,
        data: data,
        success: success,

        error: function(XMLHttpRequest, textStatus, errorThrown)
        {
            console.log('Error : ' + errorThrown);
        }
    });
}

function postFilesToServer(url, data, success){

    $.ajax({
        type: "POST",
        url: url,
        data: data,
        success: success,
        cache: false,
        contentType: false,
        processData: false,
        error: function(XMLHttpRequest, textStatus, errorThrown)
        {
            console.log('Error : ' + errorThrown);
        }
    });
}

$(document).ready(function(){

    var defaultInputPlaceholder;
    // Control appearance of default values within input fields.
    $(":input").focus(function(){

        defaultInputPlaceholder = this.placeholder;
        this.placeholder = "";
        $(this).addClass("input_active");

    }).blur(function(){
        this.placeholder = defaultInputPlaceholder;
        $(this).removeClass("input_active");
    });
});

var GarageModule = function(){

    var selectAjaxPath, counterText;

    function init(selectAjax){

        selectAjaxPath = selectAjax;
        registerEventListeners();
        counterText = document.getElementById("selected-car-counter");
    }

    function registerEventListeners(){

        $(".card_frame").click(selectCar);
    }

    function selectCar(){

        LoadingModule.init().show();

        var carId = this.dataset.car;

        var data = {item:carId};

        var cardFrame = $(this);

        var success = function(response){

            LoadingModule.hide();

            console.log(response);

            if (response.error.length !== 0){

                // TODO: Display errors
            }else{

                switch(response.change){

                    case "add":
                        $(cardFrame).addClass("selected-card");
                        break;

                    case "remove":
                        $(cardFrame).removeClass("selected-card");
                        break;
                }

                counterText.innerText = response.no_of_cars;

                if(response.is_full){
                    // TODO: Show animation for full
                }
            }
        };

        postToServer(selectAjaxPath, data, success);
    }

    return{
        init:init
    }
}();

var Market = function(){

    var ajaxPath;

    var userGold;

    var previousElement;

    function init(path, gold){

        ajaxPath = path;
        userGold = gold;

        registerEventListeners();
    }

    function registerEventListeners(){

        $(".purchase").on("click", purchase);
        $(".bt_yes").on("click", confirmPurchase);
        $(".bt_no").on("click", cancelPurchase);
    }

    function purchase(){

        if (typeof previousElement !== "undefined") slideFrame(previousElement, "0");

        previousElement = this;

        slideFrame(this, "-100%");
    }

    function confirmPurchase(){

        var item  = this.dataset.car;
        var price = this.dataset.price;

        if (userGold >= price){
            var data = {
                "item":item
            };
            postToServer(ajaxPath, data, success);
        }else{
            console.log("Not enough money");
        }

        function success(data){

            var frameContent   = $(previousElement).closest(".frame_content");
            var image           = $(frameContent).find(".frame_image");
            var el              = document.getElementById("p_gold");

            var newGold   = userGold - price;
            AnimateModule.animateIncrement(userGold, newGold, el);

            userGold = newGold;

            el = document.getElementById("user_gold");
            AnimateModule.createFloatingText(el, price, " score_red");

            slideFrame(previousElement, "-200%");
            previousElement = null;
            $(image).addClass("sold_frame");
        }
    }

    function cancelPurchase(){

        slideFrame(previousElement, "0");
    }

    function slideFrame(el, margin_left){
        var frame = $(el).closest(".frame_buy");
        $(frame).animate({"margin-left":margin_left},150);
    }

    return {
        init: init
    }
}();

var PendingCarModule = (function(){

    var ajaxPath = {upvote:"", accept:"", delete:"", editOrCreate:"", query:""};
    var imgPath;

    var UPVOTE_BUTTON_CLASS = "upvote";

    var popupElements = {
    };

    var selectedCar;

    function registerElements(){

        // Edit
        popupElements.form          = document.getElementById("edit_form");
        popupElements.inputModel    = document.getElementById("suggestedCar_model");
        popupElements.imgImage      = document.getElementById("v_image");
        popupElements.inputSpeed    = document.getElementById("suggestedCar_speed");
        popupElements.inputPower    = document.getElementById("suggestedCar_power");
        popupElements.inputTorque   = document.getElementById("suggestedCar_torque");
        popupElements.inputAcceleration = document.getElementById("suggestedCar_acceleration");
        popupElements.inputWeight   = document.getElementById("suggestedCar_weight");
        popupElements.inputComment  = document.getElementById("suggestedCar_comment");
        // Accept & Delete
        popupElements.accept_form   = document.getElementById("accept_form");
        popupElements.delete_form   = document.getElementById("delete_form");
    }

    function registerEventListeners(){

        $("."+UPVOTE_BUTTON_CLASS).click(upvote);
        $(".accept").click(popupAccept);
        $("#accept").click(accept);
        $(".delete").click(popupDelete);
        $("#delete").click(deleteCard);
        $(".card_frame").on("click", ".image", showDetails);
        $(".edit").click(popupEdit);
        $("#edit_form").submit(editOrCreate);
        $("#new-suggested-car").click(popupCreate);
    }

    function showDetails(){

        var elementState = this.getAttribute("name");

        var carId = "f" + this.dataset.car;
        var frame_details = document.getElementById(carId);

        switch (elementState){
            case "show":
                $(frame_details).finish().animateAuto("height", 150);
                this.setAttribute("name", "hide");
                break;
            case "hide":
                $(frame_details).finish().animate({"height":0}, 150);
                this.setAttribute("name", "show");
                break;
        }
    }

    function upvote(){

        var button = this;
        button.className = UPVOTE_BUTTON_CLASS;

        var id = this.dataset.car;

        // Show loading image
        var loadingImg = document.getElementById("l"+id);
        var loadingImgClass = loadingImg.className;
        loadingImg.className = "vote_load";

        var data = {car_id: id};
        var success = function(response){

            var counter = document.getElementById("count"+id);

            switch(response.result){
                case "added":
                    button.className = button.className + " plus";
                    counter.textContent = parseInt(counter.textContent) + 1;
                    loadingImg.className = loadingImgClass;
                    break;
                case "removed":
                    button.className = UPVOTE_BUTTON_CLASS;
                    counter.textContent = parseInt(counter.textContent) - 1;
                    loadingImg.className = loadingImgClass;
                    break;
            }
        };
        postToServer(ajaxPath.upvote, data, success);
    }

    function popupAccept(){

        PopupModule.show(popupElements.accept_form, "Accept");
    }

    function accept(){

        var data = {car_id: selectedCar};

        var success = function(response){

            switch(response.result){
                case "success":
                    $("#cf-"+selectedCar).fadeOut(150);
                    PopupModule.hide();
                    break;
                case "fail":
                    break;
            }
        };

        postToServer(ajaxPath.accept, data, success);
    }

    function popupDelete(){

        ErrorModule.init(document.getElementById("error-block-edit")).hideErrors();
        selectedCar = this.dataset.car;
        PopupModule.show(popupElements.delete_form, "Delete");
    }

    function deleteCard(){

        LoadingModule.show();

        var data = {car_id: selectedCar};

        var success = function(response){

            LoadingModule.hide();

            if (response.error.length !== 0){
                ErrorModule.displayErrors(response.error);
            }else{
                PopupModule.hide();
                $("#cf-"+selectedCar).fadeOut(150);
            }
        };

        postToServer(ajaxPath.delete, data, success);

    }

    function popupCreate(){

        ErrorModule.init(document.getElementById("error-block-edit")).hideErrors();
        PopupModule.show(popupElements.form, "Create new card");
        popupElements.form.dataset.car = -1;
        popupElements.inputModel.value = "";
        popupElements.imgImage.src = "";
        popupElements.inputSpeed.value = "";
        popupElements.inputPower.value = "";
        popupElements.inputTorque.value = "";
        popupElements.inputAcceleration.value = "";
        popupElements.inputWeight.value  = "";
        popupElements.inputComment.value = "";

    }

    function popupEdit(){

        ErrorModule.init(document.getElementById("error-block-edit")).hideErrors();
        LoadingModule.show();
        var carId = this.dataset.car;
        var data = {
            carId: carId
        };
        var success = function(response){

            var car = response.car;
            // Fetch all existing values into popup's form
            PopupModule.show(popupElements.form, "Edit");

            popupElements.form.dataset.car  = carId;
            popupElements.inputModel.value      = car.model;
            popupElements.imgImage.src          = imgPath + car.image;
            popupElements.inputSpeed.value      = car.speed;
            popupElements.inputPower.value      = car.power;
            popupElements.inputTorque.value     = car.torque;
            popupElements.inputAcceleration.value = car.acceleration;
            popupElements.inputWeight.value     = car.weight;
            popupElements.inputComment.value    = car.comment;
            LoadingModule.hide();
        };

        postToServer(ajaxPath.query, data, success);

    }

    function editOrCreate(e){

        LoadingModule.show();

        // Prevent form from submitting the default way
        e.preventDefault();

        // Get all form values
        var form = $(this);
        var values = {};
        $.each( form.serializeArray(), function(i, field) {
            values[field.name] = field.value;
        });

        // Sending the form to the server
        var formData = new FormData(form[0]);
        formData.append("car_id", this.dataset.car);
        var success = function(response){
            if (response.error.length !== 0){
                LoadingModule.hide();
                ErrorModule.displayErrors(response.error);
            }else{
                PopupModule.hide();
                location.reload();
            }
        };

        postFilesToServer(ajaxPath.editOrCreate, formData, success)
    }

    return {

        init: function (ajaxPaths, imgPaths){

            PopupModule.init();
            LoadingModule.init();

            ajaxPath = ajaxPaths;
            imgPath = imgPaths;
            registerElements();
            registerEventListeners();
        }
    }
})();

var ErrorModule = (function(){

    var container;

    function registerContainer(newContainer){
        container = newContainer;
        return this;
    }

    function displayErrors(errors){

        for (key in errors){
            if (errors.hasOwnProperty(key)){

                if (typeof errors[key] === "object"){
                    for (k in errors[key]){
                        if (errors[key].hasOwnProperty(k)){

                            appendError(errors[key][k]);
                        }
                    }
                }else{
                    appendError(errors[key]);
                }
            }
        }
    }

    function appendError(errorText){

        var p = document.createElement("p");
        p.className = "error";
        p.innerText = errorText;
        container.appendChild(p);
    }

    function hideErrors(){

        while (container.firstChild) {
            container.removeChild(container.firstChild);
        }
    }

    return {
        init: registerContainer,
        displayErrors: displayErrors,
        hideErrors: hideErrors
    }

})();

var PopupModule = (function(){

    var popup;
    var popupHeader;
    var popupBodies;

    function registerEventListeners(){

        $(popup).click(PopupModule.hide);
        $(".popup-content").click(function(event){
            event.stopPropagation();
        });
        $(".popup-exit").click(PopupModule.hide);
    }

    function init(){
        popupHeader = document.getElementById("popup-header");
        popupBodies    = document.getElementsByClassName("popup-body");
        popup         = document.getElementById("popup");

        registerEventListeners();
    }

    function showPopup(element, header){

        // Show popup with its given main container
        $(element).show();
        $(popup).fadeIn(150);
        popup.style.overflow = "scroll";
        document.body.style.overflow = "hidden";

        popupHeader.innerText = header;
    }

    function hidePopup(){
        // Hide popup and all its main containers
        $(popup).fadeOut(150, function(){
            $(popupBodies).hide();
            popup.style.overflow = "hidden";
            document.body.style.overflow = "scroll";
        });
    }

    return {
        init: init,
        show: showPopup,
        hide: hidePopup
    }

})();

var LoadingModule = (function(){

    var loadingBox;

    function init(){
        registerElements();
        return this;
    }

    function registerElements(){

        loadingBox = document.getElementById("loading-box");
    }

    return {

        init: init,
        show: function(){
            $(loadingBox).finish().fadeIn(150);
        },
        hide: function(){
            $(loadingBox).finish().fadeOut(150);
        }
    }

})();

// To animate auto-property
$.fn.animateAuto = function(prop, speed, callback){

    var elem, height;

    // Iterate through each element, in case selector returned multiple elements
    return this.each(function(i, el){
        el = $(el);
        elem = el.clone().css({"height":"auto"}).appendTo("body");
        height = elem.css("height");
        elem.remove();

        if(prop === "height") el.animate({"height":height}, speed, callback);
    });
};