/**
 * Created by Marci on 27/10/14.
 */

// Module responsible for smoothly displaying any number of background images
var ImageRotatorModule = (function(){

    var settings = {
        "fade_speed"        : 500,
        "slide_duration"    : 5000
    };

    var imageArr, imageArrLength, background1, background2, backgroundContainer, mainContainer, intervalID;
    var position = 0;
    var clock = true;

    function init(images){

        imageArr        = images;
        imageArrLength  = images.length;
        mainContainer   = $(".mainContainer");
        return this;
    }

    function create(){

        backgroundContainer    = document.createElement('div');
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

        if (clock){
            // Show top layer
            changeBackground(background1, "url("+imageArr[position]+")", function(){$(background1).finish().fadeIn(settings.fade_speed)});
        }else{
            // Show bottom layer
            changeBackground(background2, "url("+imageArr[position]+")", function(){$(background1).finish().fadeOut(settings.fade_speed)});
        }

        function changeBackground(obj, background, callback){
            $(obj).css({"background-image":background});
            callback();
        }

        clock = !clock;
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

// Module responsible for handling image preview before uploading the image
var ImageInputModule = function(){

    var inputSource, targetElement, clickElement;

    function init(sourceId, targetId, clickId){
    // Usually the target and click elements are the same

        inputSource     = document.getElementById(sourceId); // Input field which selects the file
        targetElement   = document.getElementById(targetId); // <img> where the image will be displayed
        clickElement    = document.getElementById(clickId);  // Element that should trigger the file selector

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

// Module responsible for creating reusable animations
var AnimateModule = function(){

    // Helper function to show a value increment/decrement
    function animateIncrement(oldText, newText, el){

        var PRINT_AMOUNT = 8;
        var compare, modify;
        var oldScore = oldText || el.innerHTML;
        var newScore = newText;

        var step = Math.ceil(Math.abs(oldScore-newScore)/PRINT_AMOUNT);

        if (oldScore < newScore){
            compare = function(s1,s2){
                return s1 + step >= s2;
            };
            modify = function (){
                oldScore += step;
            };
        }else{
            compare = function(s1,s2){
                return s1 - step <= s2;
            };
            modify = function (){
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
        rowLabel.className = "floating-text";
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

    // Helper function to update a streak counter
    function animateStreakCount(el, result, count){
        switch (result){
            case "win":
                el.innerHTML = count;
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
                el.innerHTML = count;
                break;
        }
    }

    //Helper function to change the width of a bar
    function animateFill (fillBar, oldScore, newScore, lowScoreLimit, highScoreLimit, callback){

        callback = typeof callback !== undefined ? callback : function(){};

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

// Module responsible for displaying errors
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
        p.innerHTML = errorText;
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

// Module responsible for handling the popup with its content
var PopupModule = (function(){

    var popup, popupHeader, popupBodies, onClose;

    function registerEventListeners(){

        $(popup).click(discardPopup);
        $(".popup-content").click(function(event){
            event.stopPropagation();
        });
        $(".popup-exit").click(discardPopup);
    }

    function init(onCloseCallback){
        popupHeader = document.getElementById("popup-header");
        popupBodies = document.getElementsByClassName("popup-body");
        popup       = document.getElementById("popup");

        registerEventListeners();

        onClose = onCloseCallback || function(){};
        return this;
    }

    function showPopup(element, header){

        // Show popup with its given main container
        $(element).show();
        $(popup).fadeIn(150);
        popup.style.overflowY = "scroll";
        document.body.style.overflowY = "hidden";
        document.body.style.marginRight = "17px";

        popupHeader.innerHTML = header;
    }

    function discardPopup(){

        onClose();
        hidePopup();
    }

    function hidePopup(){

        // Hide popup and all its main containers
        $(popup).fadeOut(150, function(){
            $(popupBodies).hide();
            popup.style.overflowY = "hidden";
            document.body.style.overflowY = "scroll";
            document.body.style.marginRight = "0";
        });
    }

    return {
        init: init,
        show: showPopup,
        hide: hidePopup
    }

})();

// Module responsible for displaying a simple loading circle (without any numbers)
var LoadingModule = (function(){

    var loadingContainer;

    function showLoadingBar(){

        loadingContainer = document.getElementById("loading-container");

        $(loadingContainer).fadeIn(150);
    }

    function hideLoadingBar(){

        $(loadingContainer).fadeOut(150);
    }

    return {

        show: showLoadingBar,
        hide: hideLoadingBar
    }

})();

// Module responsible for managing transitions between the two types of games on the Game page
var GameModule = (function(){

    var game, menu, gameContainer, userInfo, userDeck;

    // Default settings
    var setting = {
        "players" : 2,
        "imgFolder": "",
        "ajaxPostScore":"",
        "ajaxFreeForAll":"",
        "ajaxWinClassic":"",
        "ajaxClassic":""
    };

    // Temporary settings to keep unsaved data
    var settingTmp = Object.create(setting);

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

    // Original Game (Free For All)
    function Game(){

        var self = this;

        // Main deck of cards
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

        // Returns a random card from the main deck
        this.getRandomCard = function(deck){

            var random = Math.floor(Math.random() * (deck.length - 0)) + 0;
            return deck[random];
        };

        // Deck of 10 cards belonging to the user
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

            var oppLength = self.player.opponent.length;
            while (oppLength--){

                // Reset the deck because players can have the same cards but every card of one player must be unique
                var deck = self.cards.deck;

                for (var i=0; i<10; i++){

                    var random = Math.floor(Math.random() * (self.cards.deck.length - 0)) + 0;
                    var randomCard = deck[random];

                    self.player.opponent[oppLength].deck.push(randomCard);

                    deck.slice(random, 1);
                }
            }
        };

        // Preloads the main deck of cards with a given function executed when done
        this.preloadImages = function(callback){

            if (typeof callback === "undefined") callback = function(){};
            var arr = [];
            for (var i=0; i<self.cards.len;i++){
                arr.push(setting.imgFolder + self.cards.deck[i].image);
            }
            preloadImages(arr).done(callback);
        };

        var uiContainer = {};         // Contains those DOM elements which might change during a game
        this.previousActiveRows = []; // Contains those DOM divs whose colour has been affected in a round

        // The host is always the previous winner in the Classic game
        this.player = {
            user:null,
            host:null,
            opponent:[]
        };

        // States of the game
        this.hasRoundEnded = false; // Normally changed after each select
        this.hasGameEnded  = true;  // Normally changed when losing or restarting
        this.usersTurn     = true;  // Normally changed after determining if the user won/lost

        // Methods that are meant to be overridden in different versions of the game

        // Determines what happens when the user has lost the round
        this.loseRoundAction = function(){

            self.hasGameEnded = true;
            self.RoundControls.newGame(); // Creates a New Game button
        };

        // Determines what happens when the user has won the round
        this.winRoundAction = function(){

            self.RoundControls.nextRound(); // Creates a Next button
        };

        // Sets up the game ready for user interaction
        this.start = function(){

            self.createUI();

            self.hasGameEnded  = false;
            self.hasRoundEnded = false;

            // Host already picks the first card
            self.player.host.setCard(self.getRandomCard(self.cards.deck)).showCard();

            return this;
        };

        // Determines what happens when the user clicks the New Game Button
        this.restart = function(){

            if (self.hasGameEnded && self.hasRoundEnded){

                self.hasGameEnded  = false;
                self.hasRoundEnded = false;

                AnimateModule.createStreakCount(uiContainer.streakText, "new", "0");

                self.newRound();
            }
        };

        // Called at the beginning of each round
        this.beginningOfRoundAction = function(){

            // This is only needed to prevent the players from accidentally selecting a field again
            self.hasRoundEnded = true;
        };

        // Called at the end of each round
        this.endOfRoundAction = function(){

            // Data to post to the server
            var data = {
                score:       self.player.user.score,
                streak:      self.player.user.streak,
                roundResult: self.player.user.roundResult
            };

            var success = function(response){

                self.TopPanelModule.update(response.levelChange, response.userLevelInfo, response.gold);
            };

            // Ajax call
            postToServer(setting.ajaxPostScore, data, success);
        };

        // Determines what happens when the user clicks the Next button
        this.nextRound = function(){

            if (self.hasRoundEnded){

                self.hasRoundEnded = false;

                self.newRound();
            }
        };

        // Prepares the UI for a new round ready for user interaction
        this.newRound = function(){

            // Hide the host's card and Generate new card for host
            self.player.host.hideCard(function(){
                self.player.host.setCard(self.getRandomCard(self.cards.deck)).showCard();
            });

            // Hide cards of all players
            for (var i=0;i<self.player.opponent.length;i++){
                self.player.opponent[i].hideCard();
            }

            // Set the row colours back to normal
            for (var i=0;i<self.previousActiveRows.length;i++){
                self.previousActiveRows[i].className = "card_row";
            }

            self.previousActiveRows = [];

            self.RoundControls.reset();
        };

        // Reorganises the role references to player objects. Takes the winner as an argument. She'll become the host.
        this.reorganisePlayers = function(player){
            // No need to change the host in the default version of the game
            // This function is called after determining which player won
        };

        // Takes all cards from the losers and gives them to the winner.
        this.reorganiseCards = function(){
            // No need to mix the player's cards as they only have one at a time in Classic
        };

        // Picks a random card for each opponent
        this.assignCardsToOpponents = function(){

            for (var i=0;i<self.player.opponent.length;i++){
                self.player.opponent[i].setCard(self.getRandomCard(self.cards.deck)).showCard();
            }
        };

        // Long method called when the host picks a field.
        this.selectField = function(field){

            if (!self.hasRoundEnded && self.usersTurn){

                // Prevent player from selecting multiple times in one round
                self.beginningOfRoundAction();

                // Detect clicked property
                var property = field.getAttribute("name");

                self.assignCardsToOpponents();

                var playerQueue = Object.create(self.player.opponent);
                playerQueue.push(self.player.host);

                // Make a copy of the players array
                var newPlayerQueue = playerQueue.slice();

                // Compare each player with everyone exactly once and record their scores
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

                // Count draws on the first place. If there is a draw between two players, '2' will be stored.
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

                // Add the round score to the players' overall score and find the winner unless there's a draw
                var foundWinner = false;
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
                                self.reorganiseCards();
                            }else{
                                self.reorganisePlayers(playerQueue[i]);
                                self.reorganiseCards();
                            }
                        }
                    }else{
                        // Win
                        playerQueue[i].viewHolder[property].className = "card_row row_green";
                        playerQueue[i].roundResult = "win";
                        foundWinner = true;
                        playerQueue[i].streak += setting.players-1;

                        // Make sure that roles and cards are assigned according to who won the round.
                        self.reorganisePlayers(playerQueue[i]);
                        self.reorganiseCards();
                    }

                    playerQueue[i].roundScore = 0;
                }

                // Update the user's streak counter
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

        // Helper function to calculate the score of players after one round
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

        // Helper function to sort players
        var compare = function(p1,p2){

            if (p1.roundScore < p2.roundScore)
                return 1;
            if (p1.roundScore > p2.roundScore)
                return -1;
            return 0;
        };

        // Creates the user interface as well as the player objects of the game
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

            host.viewField   = elements.fieldHolder;
            host.viewHolder  = elements.viewHolder;
            self.player.host = host;
            self.player.user = self.player.host;

            // Opponents
            self.player.opponent = [];
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

        // Removes the user interface
        this.removeUI = function(){

            while (uiContainer.container.firstChild) {
                uiContainer.container.removeChild(uiContainer.container.firstChild);
            }
        };

        // Module responsible for re-appearing buttons after each round.
        this.RoundControls = (function(){

            var container, streakContainer, streakText, buttonContainer;

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
                streakText.innerHTML  = "0";
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
                miniCard.innerHTML = number;
                box.appendChild(miniCard);
            }

            function updateDeckIndicator(imagePath, box){

                while (box.firstChild){
                    box.removeChild(box.firstChild);
                }

                var miniImg, miniImageDiv, miniImageCounter;

                var count = 1;

                for (key in imagePath){
                    if (imagePath.hasOwnProperty(key)){

                        miniImageDiv = document.createElement("div");
                        miniImageDiv.className = "mini-image-div";
                        box.appendChild(miniImageDiv);

                        miniImageCounter = document.createElement("div");
                        miniImageCounter.className = "mini-image-counter";
                        miniImageCounter.innerHTML = count;
                        miniImageDiv.appendChild(miniImageCounter);

                        miniImg = document.createElement("img");
                        miniImg.src = imagePath[key];
                        miniImg.className = "mini-image";
                        miniImg.width = "60";
                        miniImg.height = "40";
                        miniImageDiv.appendChild(miniImg);

                        count ++;
                    }
                }
            }

            return {
                createCardIndicator: createCardIndicator,
                updateCardIndicator: updateCardIndicator,
                updateDeckIndicator: updateDeckIndicator
            }
        }();

        // Module responsible for handling the panel which displays the user's score and level.
        this.TopPanelModule = (function(){

            var fillBar, scoreText, levelText, goldText, popupLevelUp, popupGoldText;

            var attribute = {
                score: 0,
                lowScoreLimit: 0,
                highScoreLimit: 0,
                level: 0,
                gold: 0
            };

            function init(){

                // Filled bar of Score
                fillBar = document.getElementById("s_fill");
                // Text of Score
                scoreText = document.getElementById("s_score");
                levelText = document.getElementById("user-level");
                goldText = document.getElementById("user-gold");
                popupGoldText = document.getElementById("level-gold");
                popupLevelUp = document.getElementById("level-up-block");
            }

            function update(levelChange, userLevelInfo, gold){

                // userLevelInfo is an object with attributes: "low_score_limit", "high_score_limit", "level", "score"

                var previousScore = attribute.score;

                switch (levelChange){
                    case "up":

                        // Animation till the top
                        AnimateModule.animateIncrement(previousScore, attribute.highScoreLimit, scoreText);
                        AnimateModule.animateFill(fillBar, previousScore, attribute.highScoreLimit, attribute.lowScoreLimit, attribute.highScoreLimit, function(){

                            // TODO: level up graphics
                            popupGoldText.innerHTML = gold-attribute.gold;
                            AnimateModule.animateIncrement(undefined, gold, goldText);
                            setAttributes(userLevelInfo, gold);

                            var callback = function(){

                                setUI();
                                levelText.innerHTML = userLevelInfo.level;

                                // Animation till new score
                                AnimateModule.animateIncrement(attribute.lowScoreLimit, attribute.score, scoreText);
                                AnimateModule.animateFill(fillBar, attribute.lowScoreLimit, attribute.score, attribute.lowScoreLimit, attribute.highScoreLimit);
                            };

                            PopupModule.init(callback).show(popupLevelUp, "Level up!");
                        });

                        break;

                    case "down":

                        // Animation till the bottom
                        AnimateModule.animateIncrement(previousScore, attribute.lowScoreLimit, scoreText);
                        AnimateModule.animateFill(fillBar, previousScore, attribute.lowScoreLimit, attribute.lowScoreLimit, attribute.highScoreLimit, function(){

                            // TODO: level up graphics
                            setAttributes(userLevelInfo, gold);

                            setUI();

                            // Animation till new score
                            AnimateModule.animateIncrement(attribute.highScoreLimit, attribute.score, scoreText);
                            AnimateModule.animateFill(fillBar, attribute.highScoreLimit, attribute.score, attribute.lowScoreLimit, attribute.highScoreLimit);

                        });

                        break;

                    default:

                        setAttributes(userLevelInfo, gold);
                        setUI();

                        AnimateModule.animateIncrement(previousScore, attribute.score, scoreText);
                        AnimateModule.animateFill(fillBar, previousScore, attribute.score, attribute.lowScoreLimit, attribute.highScoreLimit);

                        break;
                }

                return this;
            }

            // This method is meant to print the limits of the current level
            function setUI(){

                //ui.lowScoreLimit.innerHTML = attribute.score - attribute.lowScoreLimit;
                //ui.highScoreLimit.innerHTML = attribute.highScoreLimit - attribute.score + " until next level";
            }

            function setAttributes(userLevelInfo, gold){

                attribute.lowScoreLimit  = userLevelInfo.low_score_limit;
                attribute.score          = userLevelInfo.score;
                attribute.highScoreLimit = userLevelInfo.high_score_limit;
                attribute.level          = userLevelInfo.level;
                attribute.gold           = gold;
            }

            return{
                init:init,
                update:update
            }

        })();

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
                img.width = "230";
                img.height = "153";
                cardImage.appendChild(img);

                fieldHolder.image = img;
                viewHolder.image = cardImage;


                // Rest of the card elements
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

    // Extended version (Classic Game)
    function ClassicGame(){}

    // Let the Classic Game inherit from the base game
    ClassicGame.prototype = new Game();
    ClassicGame.prototype.constructor = ClassicGame;

    // Creates mini cards appearing above each player's card, indicating the number of cards each player has
    ClassicGame.prototype.updateAllPlayersCardIndicators = function(){

        var self = this;

        self.ProgressModule.updateCardIndicator(self.player.host.deck.length, self.player.host.viewHolder.indicator);

        for (key in self.player.opponent){
            if (self.player.opponent.hasOwnProperty(key)){

                self.ProgressModule.updateCardIndicator(self.player.opponent[key].deck.length, self.player.opponent[key].viewHolder.indicator)
            }
        }

        self.updatePlayerDeckIndicator(self.player.user);
    };

    // Prints the images inside the top panel, showing what cards the main player currently holds
    ClassicGame.prototype.updatePlayerDeckIndicator = function(player){

        var self = this;

        var imageArr = [];

        for (key in player.deck){
            if (player.deck.hasOwnProperty(key)){
                imageArr.push(setting.imgFolder + player.deck[key].image);
            }
        }

        self.ProgressModule.updateDeckIndicator(imageArr, userDeck);
    };

    ClassicGame.prototype.start = function(){

        var self = this;

        self.createUI();

        // Set players' cards
        self.giveCardsToUser(self.userCards);
        self.giveCardsToOpponents();

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

        // Only display the user's current deck
        self.updatePlayerDeckIndicator(self.player.user);

        self.hasGameEnded                   = false;
        ClassicGame.prototype.usersTurn     = true;
        ClassicGame.prototype.hasRoundEnded = false;

        self.player.host.setCard(self.player.host.deck[0]).showCard();

        return this;
    };

    ClassicGame.prototype.reorganisePlayers = function(player){
        // Takes the winner as an argument

        var self = this;

        // First make the winner the new host
        self.player.opponent.push(self.player.host);
        self.player.host = player;

        // Remove winner from the array of opponents
        var index = self.player.opponent.indexOf(player);
        if (index > -1){
            self.player.opponent.splice(index, 1);
        }
    };

    ClassicGame.prototype.reorganiseCards = function(){

        var self = this;

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

        self.updateAllPlayersCardIndicators();
        self.updatePlayerDeckIndicator(self.player.user);
    };

    ClassicGame.prototype.assignCardsToOpponents = function(){

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
                        var chosenProperty = getRandomProperty(defaultField);
                        self.usersTurn = true;
                        self.selectField(self.player.host.viewHolder[chosenProperty.columnName]);
                    });
                })
            });
        }
    };

    ClassicGame.prototype.winRoundAction = function(){
        // Override the original with an empty function
    };

    ClassicGame.prototype.loseRoundAction = function(){

        var self = this;

        self.usersTurn = false;
    };

    ClassicGame.prototype.winGameAction = function(){

        LoadingModule.show();
        var self = this;
        var data = {round_result: self.player.user.roundResult};
        var success = function(response){

            if (response.error.length > 0){

                ErrorModule.init(document.getElementById("global-error"));
                ErrorModule.displayErrors(response.error);
            }else{

                PopupModule.hide();
                PopupModule.show(document.getElementById("winning-screen-block"), "WIN");
            }

            LoadingModule.hide();
        };

        postToServer(setting.ajaxWinClassic, data, success);
    };

    ClassicGame.prototype.loseGameAction = function(){

        PopupModule.hide();
        PopupModule.show(document.getElementById("losing-screen-block"), "LOSE");
    };

    ClassicGame.prototype.beginningOfRoundAction = function(){

        var self = this;

        self.hasRoundEnded = true;
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

            self.TopPanelModule.update(data.levelChange, data.userLevelInfo, data.gold);
        };

        // Ajax call
        postToServer(setting.ajaxPostScore, data, success);

        // Check if any players have lost. If yes, remove them.
        var oppLength = self.player.opponent.length;
        while (oppLength--){

            var player = self.player.opponent[oppLength];

            if(player.deck.length === 0){
                self.player.opponent.splice(oppLength, 1);
                player.hideCard();
            }
        }

        // Check if the user has lost or won
        if (self.player.user.deck.length === 0){

            self.loseGameAction();
        }else if (self.player.opponent.length < 1){

            self.winGameAction();
        }else{

            self.RoundControls.nextRound();
        }
    };

    function init(ajaxPostScore, ajaxFreeForAll, ajaxClassic, ajaxWinClassic, imgFolder){

        setting.ajaxPostScore   = ajaxPostScore;
        setting.ajaxFreeForAll  = ajaxFreeForAll;
        setting.ajaxClassic     = ajaxClassic;
        setting.ajaxWinClassic  = ajaxWinClassic;
        setting.imgFolder       = imgFolder;

        registerEventListeners();

        menu          = document.getElementById("main-menu");
        gameContainer = document.getElementById("battlefield");
        userDeck      = document.getElementById("user-deck");

        PopupModule.init(discardSettings);
    }

    function registerEventListeners(){

        var freeForAllButton = document.getElementById("free-for-all");
        freeForAllButton.addEventListener("click", startFreeForAll);

        var classicButton    = document.getElementById("classic");
        classicButton.addEventListener("click", startClassic);

        var mainMenuButton   = document.getElementById("main-menu-button");
        mainMenuButton.addEventListener("click", showMenu);

        $(".quit-game-button").click(quitGame);

        var settingsButton   = document.getElementById("settings-button");
        settingsButton.addEventListener("click", openSettings);

        $(".player-option").click(changeNumberOfPlayers);

        var settingsSaveButton = document.getElementById("settings-save");
        settingsSaveButton.addEventListener("click", saveSettings);
    }

    function startFreeForAll(){

        hideMenu(function(){

            LoadingModule.show();

            var data = {};

            var success = function(response){

                LoadingModule.hide();

                game = new Game();
                game.setCards(JSON.parse(response.deck));
                userInfo = JSON.parse(response.user_level_info);

                game.preloadImages(function(){
                    game.start();
                    game.TopPanelModule.update("default", userInfo, userInfo.gold);
                    game.player.host.score += userInfo.score;
                });

            };

            postToServer(setting.ajaxFreeForAll, data, success);
        });
    }

    function startClassic(){

        LoadingModule.show();

        var data = {};

        var success = function(response){

            if (response.error.length === 0){

                LoadingModule.hide();

                hideMenu(function(){

                    game = new ClassicGame();
                    game.setCards(JSON.parse(response.deck));
                    userInfo = JSON.parse(response.user_level_info);

                    game.setUserCards(JSON.parse(response.selected_cars));

                    game.preloadImages(function(){
                        game.start();
                        game.TopPanelModule.update("default", userInfo, userInfo.gold);
                        game.player.host.score += userInfo.score;
                    });
                });
            }else{

                LoadingModule.hide();
                ErrorModule.init(document.getElementById("global-error")).displayErrors(response.error);
            }
        };

        postToServer(setting.ajaxClassic, data, success);
    }

    function hideMenu(callback){

        var callbackFunction = callback || function(){};
        $(menu).fadeOut(150, callbackFunction);
    }

    function showMenu(){

        if (game !== undefined){
            game.removeUI();
            game = undefined;
        }

        $(menu).fadeIn(150);
    }

    function quitGame(){

        PopupModule.init().hide();
        showMenu();
    }

    function openSettings(){

        PopupModule.show(document.getElementById("settings-block"), "Settings");
    }

    function changeNumberOfPlayers(){

        $(".player-option").removeClass("active");
        $(this).addClass("active");

        settingTmp.players = this.dataset.players;
    }

    // Overwrite the original setting object
    function saveSettings(){

        showMenu();

        for (key in settingTmp){
            if (settingTmp.hasOwnProperty(key)){
                setting[key] = settingTmp[key];
            }
        }

        PopupModule.hide();
    }

    // Remove temporarily changed styles from the settings since we ignore all changed setting
    function discardSettings(){

        // Number of players setting
        $(".player-option").removeClass("active");
        $("#player-option-"+setting.players).addClass("active");
    }

    return{
        init:init
    }

})();

// Module responsible for actions on the Garage page
var GarageModule = function(){

    var selectAjaxPath, unselectAllAjaxPath, counterText;

    function init(selectAjax, unselectAllAjax){

        selectAjaxPath = selectAjax;
        unselectAllAjaxPath = unselectAllAjax;
        registerEventListeners();
        counterText = document.getElementsByClassName("selected-car-counter");
    }

    function registerEventListeners(){

        $(".card_frame").click(selectCar);
        var unselectButton = document.getElementById("unselect-cars");
        unselectButton.addEventListener("click", unselectAll);
    }

    function selectCar(){

        LoadingModule.show();

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

                for (key in counterText){
                    if (counterText.hasOwnProperty(key)){
                        counterText[key].innerHTML = response.no_of_cars;
                    }
                }

            }
        };

        postToServer(selectAjaxPath, data, success);
    }

    function unselectAll(){

        LoadingModule.show();

        var  data = {};

        var success = function(){

            LoadingModule.hide();

            $(".card_frame").removeClass("selected-card");

            for (key in counterText){
                if (counterText.hasOwnProperty(key)){
                    counterText[key].innerHTML = 0;
                }
            }
        };

        postToServer(unselectAllAjaxPath, data, success);
    }

    return{
        init:init
    }
}();

// Module responsible for actions on the Dealership page
var MarketModule = function(){

    var ajaxPath, userGold, previousElement;

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

        LoadingModule.show();

        if (userGold >= price){

            var data = {
                "item":item
            };
            postToServer(ajaxPath, data, success);

        }else{

            var errors = ["You don't have enough money to purchase this car :("];
            ErrorModule.init(document.getElementById("global-error")).displayErrors(errors);
            LoadingModule.hide();
        }

        function success(){

            var frameContent    = $(previousElement).closest(".frame_content");
            var image           = $(frameContent).find(".frame_image");
            var el              = document.getElementById("p_gold");

            var newGold   = userGold - price;
            AnimateModule.animateIncrement(userGold, newGold, el);
            LoadingModule.hide();

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

// Module responsible for actions on the Pending page
var PendingCarModule = (function(){

    var ajaxPath = {upvote:"", accept:"", delete:"", editOrCreate:"", query:""};
    var imgPath, selectedCar;

    var UPVOTE_BUTTON_CLASS = "upvote";

    var popupElements = {
    };

    function registerElements(){

        // Edit
        popupElements.editOrCreateBlock = document.getElementById("edit-block");
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
        popupElements.acceptBlock   = document.getElementById("accept-block");
        popupElements.deleteBlock   = document.getElementById("delete-block");
        // Error messages
        ErrorModule.init(document.getElementById("error-block"))
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

    // Functions prefixed with "popup" only trigger the appropriate popup

    function popupAccept(){

        ErrorModule.hideErrors();
        PopupModule.show(popupElements.acceptBlock, "Accept");
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
        PopupModule.show(popupElements.deleteBlock, "Delete");
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
        PopupModule.show(popupElements.editOrCreateBlock, "Create new card");
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
            car_id: carId
        };

        // Fill in the form with the to be edited car's details
        var success = function(response){

            var car = response.car;

            PopupModule.show(popupElements.editOrCreateBlock, "Edit");

            // Fetch all existing values into the popup's form
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

        // Send the form to the server
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

            ajaxPath = ajaxPaths;
            imgPath = imgPaths;
            registerElements();
            registerEventListeners();
        }
    }
})();

// Module responsible for actions on the Account page
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

        LoadingModule.show();

        var data = {};
        var success = function(){

            LoadingModule.hide();
            location.reload();
        };
        postToServer(ajaxDeletePath, data, success);
    }

    return{
        init: init
    }
})();

// Helper functions

// Choose random property of an object
function getRandomProperty(obj) {
    var keys = Object.keys(obj);
    return obj[keys[ keys.length * Math.random() << 0]];
}

// Preload an array of images
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
        loadingContainer.id  = "image-loading-container";

        progressP = document.createElement("p");
        progressP.id = "image-progress-p";
        loadingContainer.appendChild(progressP);

        container.appendChild(loadingContainer);

        $(loadingContainer).fadeIn(150);
    }

    // Removes loading screen
    function onFinish(){

        $(loadingContainer).fadeOut(150, function(){
            container.removeChild(loadingContainer);
        });
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

// Regular Ajax call
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

// Ajax call for file uploads
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

// Animate height:auto
$.fn.animateAuto = function(prop, speed, callback){

    var elem, height;

    // Iterate through each element the selector returned
    return this.each(function(i, el){
        el = $(el);
        elem = el.clone().css({"height":"auto"}).appendTo("body");
        height = elem.css("height");
        elem.remove();

        if(prop === "height") el.animate({"height":height}, speed, callback);
    });
};

// Make placeholder of an input field disappear on focus
$(document).ready(function(){

    var defaultInputPlaceholder;

    $(":input").focus(function(){

        defaultInputPlaceholder = this.placeholder;
        this.placeholder = "";
        $(this).addClass("input_active");

    }).blur(function(){
        this.placeholder = defaultInputPlaceholder;
        $(this).removeClass("input_active");
    });
});