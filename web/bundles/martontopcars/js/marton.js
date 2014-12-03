/**
 * Created by Marci on 27/10/14.
 */

function preloadImages(array, el){

    var newImages = [], loadedImages = 0, arrLength = array.length, loadingContainer, progressP;
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

var ImageRotator = (function(){

    var settings = {
        "fade_speed"        : 500,
        "slide_duration"    : 5000
    };

    var imageArr;                              // stores all the images
    var imageArrLength;
    var position = 0;                  // initially start from the beginning
    var counter = true;                        // clock
    var background1, background2, backgroundContainer;

    var mainContainer;

    var intervalID;

    function init(images){

        imageArr = images;
        imageArrLength = images.length;
        mainContainer = $(".mainContainer");
        return this;
    }

    function create(){

        backgroundContainer        = document.createElement('div');
        backgroundContainer.className  = "background_container";

        background1            = document.createElement('div');
        background1.className  = "background_pic";
        background1.id         = "background_pic_01";

        background2            = document.createElement('div');
        background2.className  = "background_pic";
        background2.id         = "background_pic_02";

        backgroundContainer.appendChild(background1);
        backgroundContainer.appendChild(background2);

        document.body.appendChild(backgroundContainer);
        return this;
    }

    function remove(){

        document.body.removeChild(backgroundContainer);
        return this;
    }

    // Change between backgrounds
    function move(){

        if (counter){
            // show top layer
            changeBackground(background1, "url("+imageArr[position]+")", function(){$(background1).finish().fadeIn(settings.fade_speed)});
        }else{
            // show bottom layer
            changeBackground(background2, "url("+imageArr[position]+")", function(){$(background1).finish().fadeOut(settings.fade_speed)});
        }

        function changeBackground(obj, background, callback){
            $(obj).css({"background-image":background});
            callback();
        }

        counter = !counter;
    }

    function start(){

        move();
        intervalID =   window.setInterval(function(){
            position = (position + 1) % imageArrLength;
            move();
        }, settings.slide_duration);
        return this;
    }

    function pause(){
        clearInterval(intervalID);
        return this;
    }

    function stepForward(){
        pause();
        position++;
        position = position % imageArrLength;
        move();
        return this;
    }

    function stepBackward(){
        pause();
        position--;
        position = position % imageArrLength;
        if (position < 0) position += imageArrLength;
        move();
        return this;
    }

    function jumpTo(pos){
        pause();
        position = pos;
        move();
        return this;
    }

    function focus(){
        create();
        $(mainContainer).fadeOut(100);
        return this;
    }

    function blur(){
        remove();
        $(mainContainer).fadeIn(100);
        return this;
    }

    function lowerOpacity(){

        $(backgroundContainer).addClass("blur");
        return this;
    }

    return {
        init:init,
        create:create,
        shade:lowerOpacity,
        focus:focus,
        blur:blur,
        start:start,
        pause:pause,
        stepForward:stepForward,
        stepBackward:stepBackward,
        jumpTo:jumpTo
    }

})();

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

var GameModule = (function(){

    var game, menu, gameContainer, userInfo;

    // Default settings
    var setting = {
        "players" : 3,
        "imgFolder": "",
        "ajaxPostScore":"",
        "ajaxFreeForAll":"",
        "ajaxClassic":""
    };

    // Original Game (Free For All)
    function Game(){

        var self = this;

        // Whole deck of cards
        this.cards = {};
        this.setCards = function (deck){

            self.cards.deck = [];
            for (key in deck){
                if (deck.hasOwnProperty(key)){
                    self.cards.deck.push(deck[key]);
                }
            }

            self.cards.len   = self.cards.deck.length;

            return this;
        };

        // Deck of 10 belonging to the user
        this.userCards = {};
        this.setUserCards = function(deck){

            for (key in deck){
                if (deck.hasOwnProperty(key)){
                    self.userCards[key] = deck[key];
                }
            }
        };

        // Gives 10 cards to the user from her deck of cards
        this.giveCardsToUser = function(deck){

            for (key in deck){
                if (deck.hasOwnProperty(key)){
                    self.player.user.deck.push(deck[key]);
                }
            }

            return this;
        };

        // Gives 10 unique random cards to the opponents
        this.giveCardsToOpponents = function(){

            var self = this;

            for (key in self.player.opponent){

                if (self.player.opponent.hasOwnProperty(key)){

                    var deck = self.cards.deck;

                    for (var i=0; i<10; i++){

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
            for (var i=0; i<self.cards.len;i++){
                arr.push(setting.imgFolder + self.cards.deck[i].image);
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

        // Containing agents of the game
        this.player = {
            user:null,
            host:null,
            opponent:[]
        };

        // States of the game
        this.isPaused        = false; // Normally changed when opening settings
        this.hasRoundEnded   = false; // Normally changed after each select
        this.hasGameEnded         = true; // Normally changed when losing or restarting
        this.hostsTurn       = true; // Normally changed before and after the opponents selected.

        // To be overridden
        this.loseRoundAction = function(){
            self.hasGameEnded = true;
            self.RoundControls.newGame();
        };

        // To be overridden
        this.winRoundAction = function(){
            self.RoundControls.nextRound();
        };

        // To be overridden
        this.start = function(){

            self.createUI();

            self.isPaused      = false;
            self.hasGameEnded       = false;
            self.hasRoundEnded = false;

            self.player.host.setCard(self.getRandomCard(self.cards.deck)).showCard();

            self.player.host.hasTurn = true;

            return this;
        };

        // To be overridden
        this.restart = function(){

            if (self.hasGameEnded && self.hasRoundEnded){

                self.hasGameEnded = false;
                self.hasRoundEnded = false;

                AnimateModule.createStreakCount(uiContainer.streakText, "new", "0");

                self.newRound();
            }
        };

        // To be overridden
        this.endOfRoundAction = function(){

            // Data to post to the server
            var data = {
                score:       self.player.user.score,
                streak:      self.player.user.streak,
                roundResult: self.player.user.roundResult
            };

            var success = function(data){
                var levelChange    = data.levelChange;
                var userLevelInfo = data.userLevelInfo;

                self.TopPanelModule.update(levelChange, userLevelInfo);
            };

            // Ajax call
            postToServer(setting.ajaxPostScore, data, success);
        };

        // To be overridden
        this.nextRound = function(){

            if (self.hasRoundEnded){

                self.hasRoundEnded = false;

                self.newRound();
            }
        };

        // To be overridden
        this.newRound = function(){

            // In this game mode always the user selects first
            self.player.user.hasTurn = true;

            // Hide Cards and Generate new card for host
            self.player.host.hideCard(function(){
                self.player.host.setCard(self.getRandomCard(self.cards.deck)).showCard();
            });

            for (var i=0;i<self.player.opponent.length;i++){
                self.player.opponent[i].hideCard();
            }

            for (var i=0;i<self.previousActiveRows.length;i++){
                self.previousActiveRows[i].className = "card_row";
            }

            self.previousActiveRows = [];

            self.RoundControls.reset();
        };

        // To be overridden
        this.reorganisePlayers = function(player){
            // No need to change the host in the default version of the game
        };

        // To be overridden
        this.assignCardsToPlayers = function(){

            // Assign cards to all opponents
            for (var i=0;i<self.player.opponent.length;i++){
                self.player.opponent[i].setCard(self.getRandomCard(self.cards.deck)).showCard();
            }
        };

        this.selectField = function(field){

            if (!self.hasRoundEnded && self.player.host.hasTurn){

                // Prevent player from selecting multiple times in one round
                self.hasRoundEnded = true;
                self.player.host.hasTurn = false;

                // Detect clicked property
                var property = field.getAttribute("name");

                // Assign cards to the opponents
                self.assignCardsToPlayers();

                var playerQueue = Object.create(self.player.opponent);

                playerQueue.push(self.player.host);

                // Make a copy of the players array
                var newPlayerQueue = playerQueue.slice();

                // Compare each player with everyone once
                for (var i=0;i<playerQueue.length;i++){

                    var currentPlayer = newPlayerQueue.shift();

                    for (key in newPlayerQueue){
                        if (newPlayerQueue.hasOwnProperty(key)){
                            var subscore = calculateSubscore(property, currentPlayer.getCardProperty(property), newPlayerQueue[key].getCardProperty(property));

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
                    }else{
                        if (drawCounter > 0) drawCounter++;
                        break;
                    }
                }

                var foundWinner = false;

                // Add the round score to the players' overall score and find the winner (if there's any)
                for (var i=0;i<playerQueue.length;i++){

                    // Avoid negative score
                    if (playerQueue[i].roundScore > 0) playerQueue[i].score += playerQueue[i].roundScore;
                    var newClass = (playerQueue[i].roundScore > 0 ? " score_green" : " score_red");
                    AnimateModule.createFloatingText(playerQueue[i].viewHolder[property], playerQueue[i].roundScore, newClass);
                    self.previousActiveRows.push(playerQueue[i].viewHolder[property]);

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
                        if (drawCounter === 0){

                            foundWinner = true;
                            // If the user was the host before the draw, let him be the host again. Otherwise let one of the players.
                            if (self.player.user.roundResult === "draw"){
                                self.reorganisePlayers(self.player.user);
                            }else{
                                self.reorganisePlayers(playerQueue[i]);
                            }
                        }
                    }else{
                        // Win
                        playerQueue[i].viewHolder[property].className = "card_row row_green";
                        playerQueue[i].roundResult = "win";
                        foundWinner = true;
                        playerQueue[i].streak += setting.players-1;

                        // Make sure we know which player won the round
                        self.reorganisePlayers(playerQueue[i]);
                    }

                    playerQueue[i].roundScore = 0;
                }

                AnimateModule.createStreakCount(uiContainer.streakText, self.player.user.roundResult, self.player.user.streak);

                // Deciding game state: WIN/DRAW or LOSE and acting accordingly
                if (self.player.user.roundResult === "lose"){

                    self.loseRoundAction();
                }else{

                    self.winRoundAction();
                }

                self.endOfRoundAction();
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

        this.createUI = function(){

            // Start by allocating the container of the game
            uiContainer.container = gameContainer;

            // Create top panel
            self.TopPanelModule.init();

            // Create elements that don't belong to anyone
            var battlefield = new Battlefield();
            uiContainer.battlefield = battlefield.create();

            // Main player
            var host = new Player();
            var card = new Card();
            var elements = card.create(defaultField);
            uiContainer.battlefield.appendChild(elements.cardFragment);

            // Control Panel
            self.RoundControls.init(uiContainer.battlefield);
            uiContainer.streakText = self.RoundControls.getStreakText();

            host.viewField = elements.fieldHolder;
            host.viewHolder = elements.viewHolder;
            self.player.host = host;
            self.player.user = self.player.host;

            // Opponents
            for (var i=0;i<setting.players-1;i++){

                var opponent = new Player();
                card = new Card();
                elements = card.create(defaultField);
                opponent.viewField = elements.fieldHolder;
                opponent.viewHolder = elements.viewHolder;
                uiContainer.battlefield.appendChild(elements.cardFragment);
                self.player.opponent.push(opponent);
            }

            return this;
        };

        this.removeUI = function(){

            while (uiContainer.container.firstChild) {
                uiContainer.container.removeChild(uiContainer.container.firstChild);
            }
        };

        // Module responsible for re-appearing buttons after each round.
        this.RoundControls = (function(){

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
                button.addEventListener("click",self.nextRound);
                buttonContainer.appendChild(button);
                var t = document.createTextNode("Next");
                button.appendChild(t);
                $(button).fadeIn(100);
            }

            function makeNewGameButton(){

                var button = document.createElement("div");
                button.className = "bt_new_game bt";
                button.addEventListener("click",self.restart);
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

        // Creates mini cards indicating the stand of the game (Classic Game)
        this.ProgressModule = function(){

            function createCardIndicator(container){

                var box = document.createElement("div");
                box.className = "indicator-box";
                container.insertBefore(box, container.firstChild);
                return box;
            }

            function updateCardIndicator(noOfCards, box){

                // First remove all mini-cards

                while (box.firstChild) {
                    box.removeChild(box.firstChild);
                }

                // Because of the lack of space, do not print every mini-card individually above 5
                if (noOfCards > 15){

                    drawMiniCard(box, noOfCards);

                }else{

                    for (var i=0; i<noOfCards; i++){

                        drawMiniCard(box, i+1);
                    }
                }
            }

            function drawMiniCard(box, number){

                var miniCard = document.createElement("span");
                miniCard.innerText = number;
                box.appendChild(miniCard);
            }

            return {
                createCardIndicator: createCardIndicator,
                updateCardIndicator: updateCardIndicator
            }
        }();

        // Module responsible for handling the panel which displays the user's score and level.
        this.TopPanelModule = (function(){

            var fillBar, scoreText;

            var attribute = {
                score: 0,
                lowScoreLimit: 0,
                highScoreLimit: 0
            };

            function init(){

                // Filled bar of Score
                fillBar = document.getElementById("s_fill");
                // Text of Score
                scoreText = document.getElementById("s_score");
            }

            function update(levelChange, userLevelInfo){

                // userLevelInfo is an object with attributes: "low_score_limit", "high_score_limit", "level", "score"

                var previousScore = attribute.score;

                switch (levelChange){
                    case "up":

                        // Animation till the top
                        AnimateModule.animateIncrement(previousScore, attribute.highScoreLimit, scoreText);
                        AnimateModule.animateFill(fillBar, previousScore, attribute.highScoreLimit, attribute.lowScoreLimit, attribute.highScoreLimit, function(){

                            // TODO: level up graphics
                            setAttributes(userLevelInfo);

                            setUI();

                            // Animation till new score
                            AnimateModule.animateIncrement(attribute.lowScoreLimit, attribute.score, scoreText);
                            AnimateModule.animateFill(fillBar, attribute.lowScoreLimit, attribute.score, attribute.lowScoreLimit, attribute.highScoreLimit);

                        });

                        break;

                    case "down":

                        // Animation till the bottom
                        AnimateModule.animateIncrement(previousScore, attribute.lowScoreLimit, scoreText);
                        AnimateModule.animateFill(fillBar, previousScore, attribute.lowScoreLimit, attribute.lowScoreLimit, attribute.highScoreLimit, function(){

                            // TODO: level up graphics
                            setAttributes(userLevelInfo);

                            setUI();

                            // Animation till new score
                            AnimateModule.animateIncrement(attribute.highScoreLimit, attribute.score, scoreText);
                            AnimateModule.animateFill(fillBar, attribute.highScoreLimit, attribute.score, attribute.lowScoreLimit, attribute.highScoreLimit);

                        });

                        break;

                    default:

                        setAttributes(userLevelInfo);
                        setUI();

                        AnimateModule.animateIncrement(previousScore, attribute.score, scoreText);
                        AnimateModule.animateFill(fillBar, previousScore, attribute.score, attribute.lowScoreLimit, attribute.highScoreLimit);

                        break;
                }

                return this;
            }

            // This is meant to print the limits of the current level
            function setUI(){

                //ui.lowScoreLimit.innerHTML = attribute.score - attribute.lowScoreLimit;
                //ui.highScoreLimit.innerHTML = attribute.highScoreLimit - attribute.score + " until next level";
            }

            function setAttributes(userLevelInfo){

                attribute.lowScoreLimit   = userLevelInfo.low_score_limit;
                attribute.score             = userLevelInfo.score;
                attribute.highScoreLimit  = userLevelInfo.high_score_limit;
            }

            return{
                init:init,
                update:update
            }

        })();

        this.test = function(){

            self.start();
            self.TopPanelModule.update("default", userInfo);
            self.player.host.score += userInfo.score;
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
                            self.selectField(this);
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

            this.hasTurn = false;
        }

        Player.prototype = {

            constructor: Player,
            getCardProperty: function(property){

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
                            self.viewField[key].src = setting.imgFolder + self.card[key];
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

        function Battlefield(){

            this.create = function(){

                var battlefield = document.getElementById("battlefield");
                return battlefield;
            }
        }
    }

    // Classic Game
    function ClassicGame(){}

    ClassicGame.prototype = new Game();
    ClassicGame.prototype.constructor = ClassicGame;

    ClassicGame.prototype.updateAllPlayersCardIndicators = function(){

        var self = this;

        console.log("user");
        console.log(self.player.user);

        console.log("winner");
        console.log(self.player.host);
        self.ProgressModule.updateCardIndicator(self.player.host.deck.length, self.player.host.viewHolder.indicator);

        for (key in self.player.opponent){
            if (self.player.opponent.hasOwnProperty(key)){

                console.log("loser");
                console.log(self.player.opponent[key]);

                self.ProgressModule.updateCardIndicator(self.player.opponent[key].deck.length, self.player.opponent[key].viewHolder.indicator)
            }
        }
    };

    ClassicGame.prototype.start = function(){

        var self = this;

        self.createUI();

        // Set players' cards
        self.giveCardsToUser(self.userCards);
        self.giveCardsToOpponents();

        self.player.host.hasTurn = true;

        // Create and Fill up additional mini-card progress indicators
        self.player.host.viewHolder.indicator =
            self.ProgressModule.createCardIndicator(self.player.host.viewHolder.fragment);

        for (key in self.player.opponent){
            if (self.player.opponent.hasOwnProperty(key)){


                self.player.opponent[key].viewHolder.indicator =
                    self.ProgressModule.createCardIndicator(self.player.opponent[key].viewHolder.fragment);
            }
        }

        self.updateAllPlayersCardIndicators();

        self.isPaused      = false;
        self.hasGameEnded       = false;
        self.hasRoundEnded = false;

        self.player.host.setCard(self.getRandomCard(self.player.host.deck)).showCard();

        return this;
    };

    ClassicGame.prototype.reorganisePlayers = function(player){
        // Takes the winner as an argument

        var self = this;

        //TODO: fix that only the host must be able to pick a new field


        self.player.opponent.push(self.player.host);
        self.player.host = null;
        self.player.host = player;

        console.log(self.player.opponent.length);

        // Remove winner from the array of opponents
        var index = self.player.opponent.indexOf(player);
        if (index > -1){
            self.player.opponent.splice(index, 1);
        }



        // Reorganise cards
        var winningDeck = [];

        winningDeck.push(self.player.host.deck.shift());

        for (key in self.player.opponent){

            if(self.player.opponent.hasOwnProperty(key)){

                winningDeck.push(self.player.opponent[key].deck.shift());
            }
        }

        for (key in winningDeck){

            if(winningDeck.hasOwnProperty(key)){

                self.player.host.deck.push(winningDeck[key]);
            }
        }

        console.log("host's deck: " + self.player.host.deck.length);
        console.log("opponent's deck: " + self.player.opponent[0].deck.length);

        self.updateAllPlayersCardIndicators();

        self.player.host.hasTurn = true;
    };

    ClassicGame.prototype.assignCardsToPlayers = function(){

        var self = this;

        // Assign cards to all opponents
        for (var i=0;i<self.player.opponent.length;i++){

            // Except for the main player - since she already received a card
            if(self.player.opponent[i] !== self.player.user){

                self.player.opponent[i].setCard(self.player.opponent[i].deck[0]).showCard();
            }
        }
    };

    ClassicGame.prototype.newRound = function () {

        var self = this;

        for (var i=0;i<self.previousActiveRows.length;i++){
            self.previousActiveRows[i].className = "card_row";
        }

        self.previousActiveRows = [];

        self.RoundControls.reset();

        // Hide opponent cars
        for (var i=0;i<self.player.opponent.length;i++){

            self.player.opponent[i].hideCard();
        }

        if (self.player.host == self.player.user) {

            // The game should move on with the user being the host
            self.player.host.hideCard(function(){
                self.player.host.setCard(self.player.host.deck[0]).showCard();
            });
        } else {

            // The game should move on with the computer being the host

            // Assign new card for host
            self.player.host.hideCard(function(){

                // Assign new card for user
                self.player.user.setCard(self.player.user.deck[0]).showCard(function(){

                    // TODO: Count-down
                    self.player.host.setCard(self.player.host.deck[0]).showCard(function(){

                        // Let the computer pick a field
                        // TODO: Choice algorithm
                        self.selectField(self.player.host.viewHolder.speed);
                    });
                })
            });
        }
    };

    ClassicGame.prototype.winRoundAction = function(){

    };

    ClassicGame.prototype.loseRoundAction = function(){

    };
    
    ClassicGame.prototype.winGameAction = function(){

    };
    
    ClassicGame.prototype.loseGameAction = function(){
        
    };

    ClassicGame.prototype.endOfRoundAction = function(){

        var self = this;

        // Data to post to the server
        var data = {
            score:       self.player.user.score,
            streak:      self.player.user.streak,
            roundResult: self.player.user.roundResult
        };

        var success = function(data){

            var levelChange    = data.levelChange;
            var userLevelInfo = data.userLevelInfo;

            self.TopPanelModule.update(levelChange, userLevelInfo);
        };

        // Ajax call
        postToServer(setting.ajaxPostScore, data, success);

        // TODO: Check for winning or losing
        // Check if the user has won or lost
        if (self.player.user.deck.length === 0){


        }

        // Check if any players apart from the user have lost. If yes, remove them.

        self.RoundControls.nextRound();
    };

    // TODO: this module should represent a shell for the game. Also it should contain the settings
    // TODO: question to Allan: am I doing prototype inheritance wrong? How should I structure my code?


    function init(ajaxPostScore, ajaxFreeForAll, ajaxClassic, imgFolder){

        setting.ajaxPostScore = ajaxPostScore;
        setting.ajaxFreeForAll = ajaxFreeForAll;
        setting.ajaxClassic = ajaxClassic;
        setting.imgFolder = imgFolder;

        registerEventListeners();

        menu = document.getElementById("main-menu");
        gameContainer = document.getElementById("battlefield");
    }

    function registerEventListeners(){

        var freeForAllButton = document.getElementById("free-for-all");
        freeForAllButton.addEventListener("click", startFreeForAll);

        var classicButton = document.getElementById("classic");
        classicButton.addEventListener("click", startClassic);

        var menuButton = document.getElementById("main-menu-button");
        menuButton.addEventListener("click", showMenu);
    }

    function startFreeForAll(){

        hideMenu(function(){

            var data = {};

            var success = function(response){

                game = new Game();
                game.setCards(JSON.parse(response.deck));
                userInfo = JSON.parse(response.user_level_info);

                console.log(userInfo);

                game.preloadImages(function(){
                    game.test();
                });

            };

            postToServer(setting.ajaxFreeForAll, data, success);
        });
    }

    function startClassic(){

        var data = {};

        var success = function(response){

            if (response.error.length === 0){

                hideMenu(function(){

                    game = new ClassicGame();
                    game.setCards(JSON.parse(response.deck));
                    userInfo = JSON.parse(response.user_level_info);

                    console.log(userInfo);

                    game.setUserCards(JSON.parse(response.selected_cars));

                    game.test();
                });
            }else{

                ErrorModule.init(document.getElementById("global-error")).displayErrors(response.error);
            }
        };

        postToServer(setting.ajaxClassic, data, success);
    }

    function hideMenu(callback){

        $(menu).fadeOut(150, callback);
    }

    function showMenu(){

        if (game !== undefined){
            game.removeUI();
            game = null;
            $(menu).fadeIn(150);
        }
    }

    function openSettings(){
    }

    function closeSettings(){
    }

    return{
        init:init
    }

})();

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

    //Helper function to change the width of a bar
    function animateFill (fillBar, oldScore, newScore, lowScoreLimit, highScoreLimit, callback){

        callback = typeof callback !== 'undefined' ? callback : function(){};

        var oldWidth = Math.round(100*(oldScore-lowScoreLimit)/(highScoreLimit - lowScoreLimit));
        $(fillBar).css({"width":oldWidth+"%"});

        var newWidth = Math.round(100*(newScore-lowScoreLimit)/(highScoreLimit - lowScoreLimit));
        if (newWidth>100) newWidth = 100;

        $(fillBar).promise().done(function(){
            $(this).animate({"width":newWidth+"%"}, 200, callback);
        });
    }

    return{
        createFloatingText : createFloatingText,
        createStreakCount  : animateStreakCount,
        animateIncrement   : animateIncrement,
        animateFill        : animateFill
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

    var selectAjaxPath, unselectAllAjaxPath, counterText;

    function init(selectAjax, unselectAllAjax){

        selectAjaxPath = selectAjax;
        unselectAllAjaxPath = unselectAllAjax
        registerEventListeners();
        counterText = document.getElementById("selected-car-counter");
    }

    function registerEventListeners(){

        $(".card_frame").click(selectCar);
        var unselectButton = document.getElementById("unselect-cars");
        unselectButton.addEventListener("click", unselectAll);
    }

    function selectCar(){

        LoadingModule.init().show();

        var carId = this.dataset.car;

        var data = {item:carId};

        var cardFrame = $(this);

        var success = function(response){

            LoadingModule.hide();


            if (response.error.length !== 0){

                ErrorModule.init(document.getElementById("global-error")).displayErrors(response.error);
                if(response.is_full){
                    // TODO: Show animation for full
                }
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
            }
        };

        postToServer(selectAjaxPath, data, success);
    }

    function unselectAll(){

        var  data = {};

        var success = function(){

            $(".card_frame").removeClass("selected-card");
            counterText.innerText = 0;
        };

        postToServer(unselectAllAjaxPath, data, success);
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
            var errors = ["You don't have enough money to purchase this car :("];
            ErrorModule.init(document.getElementById("global-error")).displayErrors(errors);
        }

        function success(response){

            var frameContent   = $(previousElement).closest(".frame_content");
            var image           = $(frameContent).find(".frame_image");
            var el              = document.getElementById("p_gold");

            var newGold   = userGold - price;
            AnimateModule.animateIncrement(userGold, newGold, el);

            userGold = newGold;

            el = document.getElementById("user_gold");
            AnimateModule.createFloatingText(el, price, " score_red");

            $(image).addClass("sold_frame");

            slideFrame(previousElement, "-200%").done(function(){
                var cardFrame = $(previousElement).closest(".card_frame");
                setTimeout(function(){
                    $(cardFrame).fadeOut(150);
                }, 2000);
            });
            previousElement = null;
        }
    }

    function cancelPurchase(){

        slideFrame(previousElement, "0");
    }

    function slideFrame(el, margin_left){

        var callback = function(){};

        var frame = $(el).closest(".frame_buy");
        $(frame).animate({"margin-left":margin_left},150);
        return{
            done: function(f){
                callback = f || callback;
                callback();
            }
        }
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
        // Error messages
        ErrorModule.init(document.getElementById("error-block-edit"))
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

        ErrorModule.hideErrors();
        PopupModule.show(popupElements.accept_form, "Accept");
        selectedCar = this.dataset.car;
    }

    function accept(){

        var data = {car_id: selectedCar};

        var success = function(response){

            if (response.error.length !== 0){

                ErrorModule.displayErrors(response.error);
            }else{

                $("#cf-"+selectedCar).fadeOut(150);
                PopupModule.hide();
            }
        };

        postToServer(ajaxPath.accept, data, success);
    }

    function popupDelete(){

        ErrorModule.hideErrors();
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

        ErrorModule.hideErrors();
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

        LoadingModule.show();
        ErrorModule.hideErrors();
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

var AccountModule = (function(){

    var ajaxDeletePath;

    function init(ajaxDelete){

        ajaxDeletePath = ajaxDelete;
        registerEventListeners();
    }

    function registerEventListeners(){

        var deleteButton = document.getElementById("delete-account");
        deleteButton.addEventListener("click", popupDelete);

        var confirmDeleteButton = document.getElementById("confirm-delete");
        confirmDeleteButton.addEventListener("click", confirmedDelete);
    }

    function popupDelete(){

        PopupModule.init().show(document.getElementById("delete-confirm"), "Delete Account");
    }

    function confirmedDelete(){

        var data = {};
        var success = function(){
            location.reload();
        };
        postToServer(ajaxDeletePath, data, success);
    }

    return{
        init: init
    }
})();

var ErrorModule = (function(){

    var container;

    function registerContainer(newContainer){
        container = newContainer;
        return this;
    }

    function displayErrors(errors){

        hideErrors();

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
        p.addEventListener("click", hideError);
        container.appendChild(p);
    }

    function hideError(){

        $(this).fadeOut(150, function(){

            container.removeChild(this);
        });
    }

    function hideErrors(){

        var children = container.childNodes;

        for (var i=0;i<children.length; i++){

            if (children[i].className === "error"){
                container.removeChild(children[i]);
            }
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
        return this;
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