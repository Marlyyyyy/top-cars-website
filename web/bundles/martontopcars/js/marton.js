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

function ImagePreview(){

    var sourceInput, target;

    this.setSourceInput = function(id){
        sourceInput = document.getElementById(id);
        return this;
    };

    this.setTarget = function(id){
        target = document.getElementById(id);
        return this;
    };

    // Set the preview image
    function readURL(input) {

        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $(target).attr('src', e.target.result);
            };

            reader.readAsDataURL(input.files[0]);
        }
    }

    $(document).ready(function(){
        $(sourceInput).change(function(){
            readURL(this);
        });

        $(target).click(function(){
            $(sourceInput).click();
        })
    });
}

function Game(){

    // Default settings
    var setting = {
        "players" : 4,
        "img_folder": "",
        "img_format":".png",
        "ajax_post_score":"",
        "game_container": document.getElementById("card_game")
    };

    this.setPlayers = function(number){
        setting.players = number;
        return this;
    };
    this.setImgFolder = function(path){
        setting.img_folder = path;
        return this;
    };
    this.setImgFormat = function(format){
        setting.img_format = format;
        return this;
    };
    this.setAjaxPostScore = function(path){
        setting.ajax_post_score = path;
        return this;
    };

    var anim_setting = {
        "fade_speed":150
    };

    // Stores progress details about the logged in user
    var user_info;
    this.setUserInfo = function(info){
        user_info = info;
    };

    // Whole deck of cards
    var cards = {};
    this.setCards = function (deck){

        cards.deck = [];
        for (key in deck){
            if (deck.hasOwnProperty(key)){
                cards.deck.push(deck[key]);
            }
        }

        cards.len   = cards.deck.length;

        return this;
    };

    // Returns a random card from the deck
    function get_random_card(){

        var random = Math.floor(Math.random() * (cards.len - 0)) + 0;
        return cards.deck[random];
    }

    this.preload_images = function(callback){

        if (typeof callback === "undefined") callback = function(){};
        var arr = [];
        for (var i=0; i<cards.len;i++){
            arr.push(setting.img_folder + cards.deck[i].image + setting.img_format);
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
    var previous_active_rows;

    var top_panel;
    function get_top_panel(){
        return top_panel;
    }

    // Containing agents of the game
    var entity = {
        player:{
            host:null,
            opponent:[]
        }
    };

    // States of the game
    var isPaused        = false;
    var hasRoundEnded   = false;
    var isEnded         = true;

    // Actions made in the game
    function start(){

        isPaused      = false;
        isEnded       = false;
        hasRoundEnded = false;

        entity.player.host.setCard(get_random_card()).showCard();

        return this;
    }

    function restart(){

        if (isEnded && hasRoundEnded){

            isEnded = false;
            hasRoundEnded = false;

            animateStreakCount(ui_container.streakText, "new", "0");

            new_round();
        }
    }

    function next_round(){

        if (hasRoundEnded){

            hasRoundEnded = false;

            new_round();
        }
    }

    function new_round(){

        // Hide Cards and Generate new card for host
        entity.player.host.hideCard(function(){
            entity.player.host.setCard(get_random_card()).showCard();
        });

        for (var i=0;i<entity.player.opponent.length;i++){
            entity.player.opponent[i].hideCard();
        }

        for (var i=0;i<previous_active_rows.length;i++){
            previous_active_rows[i].className = "card_row";
        }

        roundControls.reset();
    }

    function select_field(){
        console.log("Field selected");

        // Helper function to show floating value animations
        function create_floating_score(el, value, class_name){

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

        // Helper function to calculate the score of players
        function calculate_subscore(p1val, p2val){
            if (property === default_field.acceleration.column_name){

                return Math.round(500*(p1val - p2val));

            } else if (property === default_field.weight.column_name){

                return p1val - p2val;

            }else{

                return p2val - p1val ;
            }
        }

        // Helper function to sort player objects
        function compare(p1,p2){
            if (p1.roundScore < p2.roundScore)
                return 1;
            if (p1.roundScore > p2.roundScore)
                return -1;
            return 0;
        }

        if (!hasRoundEnded){

            // Prevent player from selecting multiple times in one round
            hasRoundEnded = true;

            // Detect clicked property
            var property = this.getAttribute("name");

            var player_queue = Object.create(entity.player.opponent);

            // Assign cards to the opponents
            for (var i=0;i<player_queue.length;i++){
                player_queue[i].setCard(get_random_card()).showCard();
            }

            player_queue.push(entity.player.host);

            // Make a copy of the players array
            var new_player_queue = player_queue.slice();

            // Compare each player with everyone once
            for (var i=0;i<player_queue.length;i++){

                var current_player = new_player_queue.shift();

                for (key in new_player_queue){
                    if (new_player_queue.hasOwnProperty(key)){
                        var subscore = calculate_subscore(current_player.getCard(property), new_player_queue[key].getCard(property));

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
            previous_active_rows = [];

            // Add the round score to the players' overall score
            for (var i=0;i<player_queue.length;i++){

                // Avoid negative score
                if (player_queue[i].roundScore > 0) player_queue[i].addScore(player_queue[i].roundScore);
                var class_name = (player_queue[i].roundScore > 0 ? " score_green" : " score_red");
                create_floating_score(player_queue[i].getView(property), player_queue[i].roundScore, class_name);
                previous_active_rows.push(player_queue[i].getView(property));

                if (foundWinner){
                    // Lose
                    player_queue[i].getView(property).className = "card_row row_red";
                    player_queue[i].roundResult = "lose";
                    player_queue[i].resetStreak();
                }else if (drawCounter > 0){
                    // Draw
                    drawCounter--;
                    player_queue[i].getView(property).className = "card_row row_draw";
                    player_queue[i].roundResult = "draw";
                    if (drawCounter === 0) foundWinner = true;
                }else{
                    // Win
                    player_queue[i].getView(property).className = "card_row row_green";
                    player_queue[i].roundResult = "win";
                    foundWinner = true;
                    player_queue[i].addStreak(setting.players-1);
                }

                player_queue[i].roundScore = 0;
            }

            console.log("streakText: " + ui_container.streakText);
            animateStreakCount(ui_container.streakText, entity.player.host.roundResult, entity.player.host.getStreak());

            // Deciding game state: WIN/DRAW or LOSE and Create control buttons
            if (entity.player.host.roundResult === "lose"){
                isEnded = true;
                roundControls.newGame();
            }else{
                roundControls.nextRound();
            }

            // Data to post to the server
            var data = {
                score:       entity.player.host.getScore(),
                streak:      entity.player.host.getStreak(),
                roundResult: entity.player.host.roundResult
            };

            var success = function(data){
                var level_change    = data.levelChange;
                var user_level_info = data.userLevelInfo;

                console.log(data);
                top_panel.update(level_change, user_level_info);
            };

            // Ajax call
            post_to_server(setting.ajax_post_score, data, success);
        }
    }

    function create_UI(){

        // Start by allocating the container of the game
        ui_container.container = setting.game_container;

        // Create top panel
        top_panel = new TopPanel();
        top_panel.setContainer(ui_container);
        top_panel.create_UI();

        // Create elements that don't belong to anyone
        var battlefield = new Battlefield();
        ui_container.battlefield = battlefield.create();

        // Main player
        var host = new Player();
        var card = new Card();
        var elements = card.create();
        ui_container.battlefield.appendChild(elements.card_fragment);

        console.log(elements);

        // Control Panel
        roundControls.init(ui_container.battlefield);
        ui_container.streakText = roundControls.getStreakText();
        console.log("streakText: " + ui_container.streakText);

        host.setViewField(elements.field_holder);
        host.setViewHolder(elements.view_holder);
        entity.player.host = host;

        // Opponents
        for (var i=0;i<setting.players-1;i++){

            var opponent = new Player();
            card = new Card();
            elements = card.create();
            opponent.setViewField(elements.field_holder);
            opponent.setViewHolder(elements.view_holder);
            ui_container.battlefield.appendChild(elements.card_fragment);
            entity.player.opponent.push(opponent);
        }

        return this;
    }

    function removeUI(){

        while (setting.game_container.firstChild) {
            setting.game_container.removeChild(setting.game_container.firstChild);
        }
    }

    // Module responsible for re-appearing buttons after each round.
    var roundControls = (function(){

        var container;
        var streakContainer;
        var streakText;
        var buttonContainer;

        // Constructor
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
            button.addEventListener("click",next_round);
            buttonContainer.appendChild(button);
            var t = document.createTextNode("Next");
            button.appendChild(t);
            return button;
        }

        function makeNewGameButton(){

            var button = document.createElement("div");
            button.className = "bt_new_game bt";
            button.addEventListener("click",restart);
            buttonContainer.appendChild(button);
            var t = document.createTextNode("Restart");
            button.appendChild(t);
            return button;
        }

        function removeAllButtons(){
            while (buttonContainer.firstChild) {
                buttonContainer.removeChild(buttonContainer.firstChild);
            }
        }

        return {
            init: init,
            nextRound: function(){
                $(makeNextButton()).fadeIn(100);
            },

            newGame: function(){
                $(makeNewGameButton()).fadeIn(100);
            },
            reset: function(){
                removeAllButtons();
            },
            getStreakText: function(){
                return streakText;
            }
        }
    })();

    // Classes

    function Player(){

        // DOM Fields
        var view_field = {};
        this.setViewField = function(fields){
            view_field = fields;
        };

        // DOM Rows and Elements
        var view_holder = {};
        this.setViewHolder = function(views){
            view_holder = views;
        };
        this.getView = function(prop){
            return view_holder[prop];
        };

        // Values
        var card = {};
        this.setCard = function(new_card){
            card = new_card;

            for(key in card){
                if(card.hasOwnProperty(key) && key != "id" && key != "price"){
                    if (key !== "image"){
                        view_field[key].innerHTML = card[key];
                    }else{
                        view_field[key].src = setting.img_folder + card[key] + setting.img_format;
                    }
                }
            }

            return this;
        };
        this.getCard = function(property){
            return card[property];
        };

        this.roundScore  = 0;
        this.roundResult = 0;

        // Stores the player's score
        var score = 0;
        this.addScore = function(subscore){
            score = score + subscore;
        };
        this.getScore = function(){
            return score;
        };

        // Stores the player's streak
        var streak = 0;
        this.getStreak = function(){
            return streak;
        };
        this.addStreak = function(substreak){
            streak += substreak;
        };
        this.resetStreak = function(){
            streak = 0;
        };

        this.showCard = function(callback){
            if (typeof callback === 'undefined') callback = function(){};
            $(view_holder.card).fadeIn(anim_setting.fade_speed, callback);
            return this;
        };

        this.hideCard = function(callback){
            if (typeof callback === 'undefined') callback = function(){};
            $(view_holder.card).fadeOut(anim_setting.fade_speed, callback);
            return this;
        }
    }

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

            var fill = document.getElementById("s_fill");
            ui.fill = fill;

            // Text middle
            var level_score = document.getElementById("s_score");
            ui.score = level_score;
        };

        this.update = function(level_change, user_level_info){
            // user_level_info is an object with attributes: "low_score_limit", "high_score_limit", "level", "score"

            var previous_score = attribute.score;

            switch (level_change){
                case "up":

                    // Animation till the top
                    animate_increment(previous_score, attribute.high_score_limit, ui.score);
                    animate_fill(previous_score, attribute.high_score_limit, function(){

                        // TODO: level up graphics
                        set_attributes(user_level_info);

                        set_UI();

                        // Animation till new score
                        animate_increment(attribute.low_score_limit, attribute.score, ui.score);
                        animate_fill(attribute.low_score_limit, attribute.score);

                    });

                    break;

                case "down":

                    // Animation till the bottom
                    animate_increment(previous_score, attribute.low_score_limit, ui.score);
                    animate_fill(previous_score, attribute.low_score_limit, function(){

                        // TODO: level up graphics
                        set_attributes(user_level_info);

                        set_UI();

                        // Animation till new score
                        animate_increment(attribute.high_score_limit, attribute.score, ui.score);
                        animate_fill(attribute.high_score_limit, attribute.score);

                    });

                    break;

                default:

                    set_attributes(user_level_info);

                    set_UI();

                    animate_increment(previous_score, attribute.score, ui.score);
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

            // Helper function to show a value increment/decrement
            function animate_increment (old_score, new_score, el){

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
        }
    }

    function Card(){

        this.create = function(){

            var field_holder = {};
            var view_holder = {};

            var card_fragment = document.createElement("div");
            card_fragment.className = "card_fragment";

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
                    card_row.addEventListener("click",select_field);
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
        };
    }

    function Battlefield(){

        this.create = function(){

            var battlefield = document.getElementById("battlefield");
            return battlefield;
        }
    }

    this.test = function(){
        create_UI();
        start();
        get_top_panel().update("default", user_info);
        entity.player.host.addScore(user_info.score);
    };
}

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

$(document).ready(function(){

    // Control appearance of default values within input fields.
    $(":input").focus(function(){
        var defaultValue = this.defaultValue;
        var newValue     = this.value;
        if (defaultValue === newValue){
            this.value = "";
            $(this).addClass("input_active");
        }
    }).blur(function(){
        var defaultValue = this.defaultValue;
        var newValue     = this.value;
        if (newValue === ""){
            this.value = defaultValue;
            $(this).removeClass("input_active");
        }
    });
});

function PendingCar(){

    var ajaxPath = {upvote:"",accept:""};
    this.setAjaxPath = function(paths){
        ajaxPath = paths;
    };

    var UPVOTE_BUTTON_CLASS = "upvote action";
    var ACCEPT_BUTTON_CLASS = "accept action";

    $(".upvote").click(function(){
        var button = this;
        button.className = UPVOTE_BUTTON_CLASS;

        var id = this.dataset.element;
        var loadingImg = document.getElementById("l"+id);
        var loadingImgClass = loadingImg.className;
        loadingImg.className = "vote_load";

        var data = {car_id: id};
        var success = function(response){

            var counter = document.getElementById("count"+id);

            console.log(response.result);

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
    });

    $(".accept").click(function(){
        var button = this;

        var id = this.dataset.element;

        console.log(id);
        var data = {car_id: id};

        var success = function(response){

            console.log(response.result);

            switch(response.result){
                case "success":
                    $(button).closest(".card_frame").fadeOut(150);
                    break;
                case "fail":
                    break;
            }
        };
        post_to_server(ajaxPath.accept, data, success);
    })
}