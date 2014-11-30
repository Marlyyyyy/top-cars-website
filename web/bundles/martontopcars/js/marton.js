/**
 * Created by Marci on 27/10/14.
 */

function preload_images(array, el){

    var new_images = [], loaded_images = 0, arr_length = array.length, background_2, loading_container, progress_p;
    var container = (typeof el === "undefined" ? document.body : el);

    var post_action = function(){};

    var arr = (typeof array != "object") ? [array] : array;

    // Executes call-back function after preloading all images
    function image_load_post(){

        loaded_images++;
        progress_p.innerHTML = Math.round(100*(loaded_images/arr_length)) + "%";
        if (loaded_images == arr_length){
            on_finish();
            post_action(new_images);
        }
    }

    // Creates loading screen
    function on_create(){

        loading_container = document.createElement("div");
        loading_container.className  = "loading_container";

        progress_p = document.createElement("p");
        progress_p.className = "progress_p";
        loading_container.appendChild(progress_p);

        container.appendChild(loading_container);
    }

    // Removes loading screen
    function on_finish(){

        container.removeChild(loading_container);
    }

    on_create();

    for (var i=0; i<arr_length; i++){
        new_images[i] = new Image();
        new_images[i].src = arr[i];
        new_images[i].onload = function(){
            image_load_post();
        };
        new_images[i].onerror = function(){
            image_load_post();
        }
    }

    // Return blank object with done() method
    return {
        done:function(f){
            post_action= f || post_action;
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
        "img_folder": "",
        "img_format":".png",
        "ajax_post_score":"",
        "game_container": document.getElementById("card_game")
    };

    // Stores progress details about the logged in user
    var user_info;
    this.setUserInfo = function(info){
        user_info = info;
    };

    // Whole deck of cards
    this.cards = {};
    this.setCards = function (deck){

        console.log(deck);
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
                game.entity.player.user.deck.push(deck[key]);
            }
        }

        console.log(deck);
        return this;
    };

    // Gives 10 unique random cards to the opponents by default
    this.setOpponentCards = function(){

        var self = this;

        for (key in self.entity.player.opponent){

            if (self.entity.player.opponent.hasOwnProperty(key)){

                var deck = self.cards.deck;

                for (var i=1; i<10; i++){

                    var random = Math.floor(Math.random() * (self.cards.deck.length - 0)) + 0;
                    var randomCard = deck[random];

                    self.entity.player.opponent[key].deck.push(randomCard);

                    deck.slice(random, 1);
                }
            }
        }
    };

    // Returns a random card from the deck
    this.get_random_card = function(deck){

        var random = Math.floor(Math.random() * (deck.length - 0)) + 0;
        return deck[random];
    };

    this.preload_images = function(callback){

        if (typeof callback === "undefined") callback = function(){};
        var arr = [];
        for (var i=0; i<game.cards.len;i++){
            arr.push(game.setting.img_folder + game.cards.deck[i].image + game.setting.img_format);
        }
        preload_images(arr).done(callback);
    };

    var default_field =  {
        speed:{
            label:"Speed:",
            unit:"km/h",
            column_name:"speed"
        },
        power:{
            label:"Power:",
            unit:"hp",
            column_name:"power"
        },
        torque:{
            label:"Torque:",
            unit:"Nm",
            column_name:"torque"
        },
        acceleration:{
            label:"Acceleration:",
            unit:"s",
            column_name:"acceleration"
        },
        weight:{
            label:"Weight:",
            unit:"kg",
            column_name:"weight"
        }
    };

    var ui_container = {};
    this.previous_active_rows = [];

    this.top_panel = null;
    function get_top_panel(){
        return game.top_panel;
    }

    // Containing agents of the game
    this.entity = {
        player:{
            user:null,
            host:null,
            opponent:[]
        }
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

        game.entity.player.host.setCard(game.get_random_card(game.cards.deck)).showCard();

        return this;
    };

    // To be overridden
    this.restart = function(){

        if (game.isEnded && game.hasRoundEnded){

            game.isEnded = false;
            game.hasRoundEnded = false;

            AnimateModule.createStreakCount(ui_container.streakText, "new", "0");

            game.new_round();
        }
    };

    // To be overridden
    this.endOfRoundAction = function(){

        // Data to post to the server
        var data = {
            score:       game.entity.player.user.score,
            streak:      game.entity.player.user.streak,
            roundResult: game.entity.player.user.roundResult
        };

        var success = function(data){
            var level_change    = data.levelChange;
            var user_level_info = data.userLevelInfo;

            game.top_panel.update(level_change, user_level_info);
        };

        // Ajax call
        post_to_server(game.setting.ajax_post_score, data, success);
    };

    // To be overridden
    this.next_round = function(){

        if (game.hasRoundEnded){

            game.hasRoundEnded = false;

            game.new_round();
        }
    };

    // To be overridden
    this.new_round = function(){

        // Hide Cards and Generate new card for host
        game.entity.player.host.hideCard(function(){
            game.entity.player.host.setCard(game.get_random_card(game.cards.deck)).showCard();
        });

        for (var i=0;i<game.entity.player.opponent.length;i++){
            game.entity.player.opponent[i].hideCard();
        }

        for (var i=0;i<game.previous_active_rows.length;i++){
            game.previous_active_rows[i].className = "card_row";
        }

        game.previous_active_rows = [];

        game.roundControls.reset();
    };

    // To be overridden
    this.reorganisePlayers = function(player){
        // No need to change the host in the default version of the game
    };

    // To be overridden
    this.assignCardsToPlayers = function(){

        // Assign cards to all opponents
        for (var i=0;i<game.entity.player.opponent.length;i++){
            game.entity.player.opponent[i].setCard(game.get_random_card(game.cards.deck)).showCard();
        }
    };

    this.select_field = function(field){

        if (!game.hasRoundEnded && game.hostsTurn){

            // Prevent player from selecting multiple times in one round
            game.hasRoundEnded = true;

            // Detect clicked property
            var property = field.getAttribute("name");

            // Assign cards to the opponents
            // TODO: separate this into a public function and make sure that the user doesn't get a new card assigned.
            game.assignCardsToPlayers();

            var player_queue = Object.create(game.entity.player.opponent);

            player_queue.push(game.entity.player.host);

            // Make a copy of the players array
            var new_player_queue = player_queue.slice();

            // Compare each player with everyone once
            for (var i=0;i<player_queue.length;i++){

                var current_player = new_player_queue.shift();

                for (key in new_player_queue){
                    if (new_player_queue.hasOwnProperty(key)){
                        var subscore = calculate_subscore(property, current_player.getCard(property), new_player_queue[key].getCard(property));

                        current_player.roundScore -= subscore;
                        new_player_queue[key].roundScore += subscore;
                    }
                }
            }

            // Sort players in terms of score
            player_queue.sort(compare);

            // Count draws on the first place
            var drawCounter = 0;

            for (var i=0;i<player_queue.length;i++){
                if (typeof player_queue[i+1] !== 'undefined'){
                    if (player_queue[i].roundScore === player_queue[i+1].roundScore){
                        drawCounter++;
                    }else{
                        if (drawCounter > 0) drawCounter++;
                        break;
                    }
                }
            }

            var foundWinner = false;

            // Add the round score to the players' overall score
            for (var i=0;i<player_queue.length;i++){

                // Avoid negative score
                if (player_queue[i].roundScore > 0) player_queue[i].score += player_queue[i].roundScore;
                var class_name = (player_queue[i].roundScore > 0 ? " score_green" : " score_red");
                AnimateModule.createFloatingText(player_queue[i].view_holder[property], player_queue[i].roundScore, class_name);
                game.previous_active_rows.push(player_queue[i].view_holder[property]);

                if (foundWinner){
                    // Lose
                    player_queue[i].view_holder[property].className = "card_row row_red";
                    player_queue[i].roundResult = "lose";
                    player_queue[i].streak = 0;
                }else if (drawCounter > 0){
                    // Draw
                    drawCounter--;
                    player_queue[i].view_holder[property].className = "card_row row_draw";
                    player_queue[i].roundResult = "draw";
                    if (drawCounter === 0) foundWinner = true;
                }else{
                    // Win
                    player_queue[i].view_holder[property].className = "card_row row_green";
                    player_queue[i].roundResult = "win";
                    foundWinner = true;
                    player_queue[i].streak += game.setting.players-1;

                    // Make sure we know which player won the round
                    game.reorganisePlayers(player_queue[i]);
                }

                player_queue[i].roundScore = 0;
            }

            AnimateModule.createStreakCount(ui_container.streakText, game.entity.player.user.roundResult, game.entity.player.user.streak);

            // Deciding game state: WIN/DRAW or LOSE and acting accordingly
            if (game.entity.player.host.roundResult === "lose"){

                game.loseAction();
            }else{

                game.winAction();
            }

            game.endOfRoundAction();
        }
    };

    // Helper function to calculate the score of players
    var calculate_subscore = function(property, p1val, p2val){

        var subscore = 0;

        if (property === default_field.acceleration.column_name){

            subscore = Math.round(500*(p1val - p2val));

        } else if (property === default_field.weight.column_name){

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

    function create_UI(){

        // Start by allocating the container of the game
        ui_container.container = game.setting.game_container;

        // Create top panel
        game.top_panel = new TopPanel();
        game.top_panel.setContainer(ui_container);
        game.top_panel.create_UI();

        // Create elements that don't belong to anyone
        var battlefield = new Battlefield();
        ui_container.battlefield = battlefield.create();

        // Main player
        var host = new Player();
        var card = new Card();
        var elements = card.create(default_field);
        ui_container.battlefield.appendChild(elements.card_fragment);

        // Control Panel
        game.roundControls.init(ui_container.battlefield);
        ui_container.streakText = game.roundControls.getStreakText();

        host.view_field = elements.field_holder;
        host.view_holder = elements.view_holder;
        game.entity.player.host = host;
        game.entity.player.user = game.entity.player.host;

        // Opponents
        for (var i=0;i<game.setting.players-1;i++){

            var opponent = new Player();
            card = new Card();
            elements = card.create(default_field);
            opponent.view_field = elements.field_holder;
            opponent.view_holder = elements.view_holder;
            ui_container.battlefield.appendChild(elements.card_fragment);
            game.entity.player.opponent.push(opponent);
        }

        return this;
    }

    function removeUI(){

        while (game.setting.game_container.firstChild) {
            game.setting.game_container.removeChild(game.setting.game_container.firstChild);
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

            var control_panel = document.createElement("div");
            control_panel.className = "player_controls";
            control_panel.id = "control_panel";
            view.appendChild(control_panel);

            streakContainer = document.createElement("div");
            streakContainer.className = "streak_container";
            streakText = document.createElement("p");
            streakText.innerText  = "0";
            streakContainer.appendChild(streakText);
            control_panel.appendChild(streakContainer);

            buttonContainer = document.createElement("div");
            buttonContainer.className = "button_container";
            control_panel.appendChild(buttonContainer);

            return control_panel;
        }

        function makeNextButton(){

            var button = document.createElement("div");
            button.className = "bt_next_round bt";
            button.addEventListener("click",game.next_round);
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
        create_UI();
        game.start();
        get_top_panel().update("default", user_info);
        game.entity.player.host.score += user_info.score;
    };

    // Classes

    function Card(){}

    Card.prototype = {

        constructor: Card,
        create: function(default_field){

            var field_holder = {};
            var view_holder = {};

            var card_fragment = document.createElement("div");
            card_fragment.className = "card_fragment";
            view_holder.fragment = card_fragment;

            // Card
            var card_block = document.createElement("div");
            card_block.className = "card_block";
            card_fragment.appendChild(card_block);

            var player_card = document.createElement("div");
            player_card.className = "player_card";
            card_block.appendChild(player_card);
            view_holder.card = player_card;

            // Card Name
            var card_name = document.createElement("div");
            card_name.className = "card_name";
            player_card.appendChild(card_name);

            field_holder.model = card_name;
            view_holder.model = card_name;

            // Card Image
            var card_image = document.createElement("div");
            card_image.className = "card_image";
            player_card.appendChild(card_image);

            var img = document.createElement("img");
            card_image.appendChild(img);

            field_holder.image = img;
            view_holder.image = card_image;


            // Rest
            var card_row, row_label, t;

            for(key in default_field){
                if(default_field.hasOwnProperty(key)){
                    card_row = document.createElement("div");
                    card_row.className = "card_row";
                    card_row.setAttribute("name",key);
                    card_row.addEventListener("click",function(){
                        game.select_field(this);
                    });
                    player_card.appendChild(card_row);
                    view_holder[key] = card_row;

                    row_label = document.createElement("span");
                    row_label.className = "row_label";
                    card_row.appendChild(row_label);

                    t = document.createTextNode(default_field[key].label);
                    row_label.appendChild(t);

                    // Changing field
                    row_label = document.createElement("span");
                    card_row.appendChild(row_label);
                    field_holder[key] = row_label;

                    row_label = document.createElement("span");
                    row_label.className = "row_unit";
                    card_row.appendChild(row_label);

                    t = document.createTextNode(default_field[key].unit);
                    row_label.appendChild(t);
                }
            }

            return {
                field_holder: field_holder,
                view_holder: view_holder,
                card_fragment: card_fragment
            };
        }
    };

    function Player(){

        // DOM Fields
        this.view_field = {};

        // DOM Rows and Elements
        this.view_holder = {};

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
        setCard: function(new_card){

            var self = this;

            self.card = new_card;

            for(key in self.card){
                if(self.card.hasOwnProperty(key) && key != "id" && key != "price"){
                    if (key !== "image"){
                        self.view_field[key].innerHTML = self.card[key];
                    }else{
                        self.view_field[key].src = game.setting.img_folder + self.card[key] + game.setting.img_format;
                    }
                }
            }

            return this;
        },
        showCard: function(callback){

            var self = this;

            if (typeof callback === 'undefined') callback = function(){};
            $(self.view_holder.card).fadeIn(150, callback);
            return this;
        },
        hideCard: function(callback){

            var self = this;

            if (typeof callback === 'undefined') callback = function(){};
            $(self.view_holder.card).fadeOut(150, callback);
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
            low_score_limit: 0,
            high_score_limit: 0
        };

        var ui = {};

        this.create_UI = function(){

            container.top_panel = document.getElementById("top_panel");

            // Filled bar of Score
            ui.fill = document.getElementById("s_fill");

            // Text of Score
            ui.score = document.getElementById("s_score");
        };

        this.update = function(level_change, user_level_info){
            // user_level_info is an object with attributes: "low_score_limit", "high_score_limit", "level", "score"

            var previous_score = attribute.score;

            switch (level_change){
                case "up":

                    // Animation till the top
                    AnimateModule.animateIncrement(previous_score, attribute.high_score_limit, ui.score);
                    animate_fill(previous_score, attribute.high_score_limit, function(){

                        // TODO: level up graphics
                        set_attributes(user_level_info);

                        set_UI();

                        // Animation till new score
                        AnimateModule.animateIncrement(attribute.low_score_limit, attribute.score, ui.score);
                        animate_fill(attribute.low_score_limit, attribute.score);

                    });

                    break;

                case "down":

                    // Animation till the bottom
                    AnimateModule.animateIncrement(previous_score, attribute.low_score_limit, ui.score);
                    animate_fill(previous_score, attribute.low_score_limit, function(){

                        // TODO: level up graphics
                        set_attributes(user_level_info);

                        set_UI();

                        // Animation till new score
                        AnimateModule.animateIncrement(attribute.high_score_limit, attribute.score, ui.score);
                        animate_fill(attribute.high_score_limit, attribute.score);

                    });

                    break;

                default:

                    set_attributes(user_level_info);

                    set_UI();

                    AnimateModule.animateIncrement(previous_score, attribute.score, ui.score);
                    animate_fill(previous_score, attribute.score);

                    break;
            }


            return this;

            function set_attributes(user_level_info){

                attribute.low_score_limit   = user_level_info.low_score_limit;
                attribute.score             = user_level_info.score;
                attribute.high_score_limit  = user_level_info.high_score_limit;
            }

            function set_UI(){

                //ui.low_score_limit.innerHTML = attribute.score - attribute.low_score_limit;
                //ui.high_score_limit.innerHTML = attribute.high_score_limit - attribute.score + " until next level";
            }

            function animate_fill (old_score, new_score, callback){

                callback = typeof callback !== 'undefined' ? callback : function(){};

                var old_width = Math.round(100*(old_score-attribute.low_score_limit)/(attribute.high_score_limit - attribute.low_score_limit));
                $(ui.fill).css({"width":old_width+"%"});

                var new_width = Math.round(100*(new_score-attribute.low_score_limit)/(attribute.high_score_limit - attribute.low_score_limit));
                if (new_width>100) new_width = 100;

                $(ui.fill).promise().done(function(){
                    // 200  - 400 ms
                    $(this).animate({"width":new_width+"%"}, 200, callback);
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

    console.log("User's cars");
    console.log(self.userCards);

    self.setOpponentCards();

    // Create and Fill up additional UI elements
    self.entity.player.host.view_holder.indicator =
        self.ProgressModule.createCardIndicator(self.entity.player.host.view_holder.fragment);

    self.ProgressModule.updateCardIndicator(self.entity.player.host.deck.length, self.entity.player.host.view_holder.indicator);

    for (key in self.entity.player.opponent){
        if (self.entity.player.opponent.hasOwnProperty(key)){

            self.entity.player.opponent[key].view_holder.indicator =
            self.ProgressModule.createCardIndicator(self.entity.player.opponent[key].view_holder.fragment);
            self.ProgressModule.updateCardIndicator(10, self.entity.player.opponent[key].view_holder.indicator)
        }
    }



    self.isPaused      = false;
    self.isEnded       = false;
    self.hasRoundEnded = false;

    self.entity.player.host.setCard(self.get_random_card(self.entity.player.host.deck)).showCard();

    return this;
};

ClassicGame.prototype.reorganisePlayers = function(player){
    // Takes the winner as an argument

    var self = this;

    self.entity.player.opponent.push(self.entity.player.host);
    self.entity.player.host = player;

    var index = self.entity.player.opponent.indexOf(player);
    if (index > -1){
        self.entity.player.opponent.splice(index, 1);
    }
};

ClassicGame.prototype.assignCardsToPlayers = function(){

    var self = this;

    // Assign cards to all opponents
    for (var i=0;i<self.entity.player.opponent.length;i++){

        // Except for the main player - since she already received a card
        if(self.entity.player.opponent[i] !== self.entity.player.user){
            self.entity.player.opponent[i].setCard(self.get_random_card(self.entity.player.opponent[i].deck)).showCard();
        }
    }
};

ClassicGame.prototype.new_round = function () {

    var self = this;

    for (var i=0;i<self.previous_active_rows.length;i++){
        self.previous_active_rows[i].className = "card_row";
    }

    self.previous_active_rows = [];

    self.roundControls.reset();

    // Hide opponent cars
    for (var i=0;i<self.entity.player.opponent.length;i++){
        self.entity.player.opponent[i].hideCard();
    }

    if (self.entity.player.host == self.entity.player.user) {

        // The game should move on with the user being the host
        self.entity.player.host.hideCard(function(){
            self.entity.player.host.setCard(self.get_random_card(self.entity.player.host.deck)).showCard();
        });
    } else {

        // The game should move on with the computer being the host

        // Assign new card for host
        self.entity.player.host.hideCard(function(){

            // Assign new card for user
            self.entity.player.user.setCard(self.get_random_card(self.entity.player.user.deck)).showCard(function(){

                // TODO: Count-down
                self.entity.player.host.setCard(self.get_random_card(self.entity.player.host.deck)).showCard(function(){

                    // TODO: Choice algorithm
                    self.select_field(self.entity.player.host.view_holder.speed);
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
        score:       self.entity.player.user.score,
        streak:      self.entity.player.user.streak,
        roundResult: self.entity.player.user.roundResult
    };

    var success = function(data){
        var level_change    = data.levelChange;
        var user_level_info = data.userLevelInfo;

        self.top_panel.update(level_change, user_level_info);
    };

    // Ajax call
    post_to_server(self.setting.ajax_post_score, data, success);
};

var AnimateModule = function(){

    // Helper function to show a value increment/decrement
    function animateIncrement(old_score, new_score, el){

        var PRINT_AMOUNT = 8;

        var step = Math.ceil(Math.abs(old_score-new_score)/8);

        if (old_score < new_score){
            var compare = function(s1,s2){
                return s1 + step >= s2;
            };
            var modify = function (){
                old_score += step;
            };
        }else{
            var compare = function(s1,s2){
                return s1 - step <= s2;
            };
            var modify = function (){
                old_score -= step;
            };
        }

        var interval = setInterval(function() {
            el.innerHTML = old_score;
            if (compare(old_score, new_score)){
                clearInterval(interval);
                el.innerHTML = new_score;
            }
            modify();
        }, Math.round(200/PRINT_AMOUNT));
    }

    // Helper function to show floating value animations
    function createFloatingText(el, value, class_name){

        var row_label = document.createElement("div");
        row_label.className = "card_row_subscore";
        row_label.className += class_name;
        el.appendChild(row_label);

        var t = document.createTextNode(value);
        row_label.appendChild(t);

        $(row_label).fadeIn(150, function(){
            $(this).animate({"margin-top":"-150px","opacity":"0"},1000, function(){
                el.removeChild(row_label);
            });
        });

        return row_label;
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

function post_to_server(url, data, success){

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

function post_files_to_server(url, data, success){

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

    var selectAjaxPath;

    function init(selectAjax){

        selectAjaxPath = selectAjax;
        registerEventListeners();
    }

    function registerEventListeners(){

        $(".card_frame").click(selectCar);
    }

    function selectCar(){

        var carId = this.dataset.car;

        var data = {item:carId};

        var cardFrame = $(this);

        var success = function(response){

            console.log(response);

            if (response.error.length !== 0){

                // Display errors
            }else{

                switch(response.change){

                    case "add":
                        $(cardFrame).addClass("selected-card");
                        break;

                    case "remove":
                        $(cardFrame).removeClass("selected-card");
                        break;
                }

            }
        }

        post_to_server(selectAjaxPath, data, success);
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

        var item  = this.dataset.element;
        var price = this.dataset.price;

        if (userGold >= price){
            var data = {
                "item":item
            };
            post_to_server(ajaxPath, data, success);
        }else{
            console.log("Not enough money");
        }

        function success(data){

            var frame_content   = $(previousElement).closest(".frame_content");
            var image           = $(frame_content).find(".frame_image");
            var el              = document.getElementById("p_gold");

            var new_gold   = userGold - price;
            AnimateModule.animateIncrement(userGold, new_gold, el);

            userGold = new_gold;

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
        post_to_server(ajaxPath.upvote, data, success);
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

        post_to_server(ajaxPath.accept, data, success);
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

        post_to_server(ajaxPath.delete, data, success);

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

        post_to_server(ajaxPath.query, data, success);

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
        var form_data = new FormData(form[0]);
        form_data.append("car_id", this.dataset.car);
        var success = function(response){
            if (response.error.length !== 0){
                LoadingModule.hide();
                ErrorModule.displayErrors(response.error);
            }else{
                PopupModule.hide();
                location.reload();
            }
        };

        post_files_to_server(ajaxPath.editOrCreate, form_data, success)
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