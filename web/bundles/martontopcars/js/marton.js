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
    };

    // Returns a random card from the deck
    function get_random_card(){
    }

    this.preload_images = function(callback){
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
    }

    function restart(){
    }

    function next_round(){
    }

    function new_round(){
    }

    function select_field(){

    }

    function create_UI(){
    }

    function removeUI(){

    }

    // Module responsible for re-appearing buttons after each round.
    var roundControls = (function(){
    })();

    // Classes

    function Player(){

    }

    function TopPanel(){

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

}