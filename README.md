# Top Cars Website #

This project features a web application where registered users can participate in a Javascript game in order to achieve a better ranking in the leaderboards. Users can also set up their own user profile, as well as purchase and suggest cars in-game. 

The Javascript game offers two game modes: ***Free For All*** and ***Classic***. Players must reach a specific progress in order to unlock the ***Classic*** game mode.

# Screenshots #

[Album](http://1drv.ms/13OmD01)

![5.jpg](https://bitbucket.org/repo/pnX74L/images/3681916601-5.jpg)

# Framework #

Symfony ([documentation](http://symfony.com/doc/current/index.html))

# Structure #

### app directory ###
*(Application configurations)*

* ** app/config/ ** : *Configuration files* : ```config.yml``` holds information about the database and the services created by the developer. It also imports ```security.yml``` which contains all settings related to password encoding, user providers, user privileges, the firewall and protected areas with limited access. The main ```routing.yml``` file can also be found in this subdirectory, but it does not have to be edited until a new bundle is added to the application.

* **app/Resources/views/base.html.twig** : Each template within the application inherits from this default base template.

### src directory ###
*(Bundle source code)*

* **src/Marton/TopCarsBundle/Controller/** : Controllers manage the logic of generating a response. Brief description of what each controller handles:

    * ```AccountController```: GET requests for rendering account related pages (Login, Registration and Account pages), and POST requests for creating, updating and deleting user accounts.
* ```UserController```: GET requests for rendering user-details and user-progress related pages (Leaderboard and Profile pages), and POST requests for searching for users.
* ```CarController```: GET requests for rendering car related pages (Garage and Dealership pages), and POST requests for purchasing and selecting cars for a particular user.
* ```SuggestedCarController```: GET requests for rendering the only suggested car related page (Prototypes page), and POST requests for creating, editing, deleting, accepting and voting on suggested cars.
* ```GameController```: GET requests for rendering the game page, and various POST requests sent by the game.
* ```PageController```: GET requests for rendering regular pages such as the About and the Home page, and for redirecting on a trailing slash in the URL.    
   
* * *
* **src/Marton/TopCarsBundle/Entity/** : Entities represent the data models of the application. The following associations were made between entities:

    * one-to-one between User and UserDetails (to store profile information about each user).
* one-to-one between User and UserProgress (to store the game progress for each user).
* many-to-many between User and Role (to store the privileges of each user).
* many-to-many between User and Car (to indicate cars purchased by a user).
* many-to-many between User and Car (to indicate cars selected by a user).
* one-to-many between User and SuggestedCar (to store cars suggested by a user).
* many-to-many between User and SuggestedCar (indicating the up-votes made by users).

* * *
* **src/Marton/TopCarsBundle/Form/** : Forms are built using Types which may use additional Models stored here.

* **src/Marton/TopCarsBundle/Repository/** : Repositories are responsible for querying the database and return an appropriate result.

* **src/Marton/TopCarsBundle/Resources/config/```routing.yml```** : Routing is used to determine which controller should handle a particular request.

* **src/Marton/TopCarsBundle/Resources/views/Default/** : Templates are rendered by the controllers to generate an HTML output. The hierarchy of inheritance between templates is indicated with the directory levels.

* **src/Marton/TopCarsBundle/Services/** : Services are singleton helper classes accessible through the service container in each controller.

* **src/Marton/TopCarsBundle/Test/```WebTestCase.php```** : This class provides common methods for functional tests.

* **src/Marton/TopCarsBundle/Tests/** : Default directory for all the unit and functional tests within the application.

### web directory ###
*(Public folder)*

* **web/bundles/martontopcars/css/```stylesheet.css```** : The design of the application.

* **web/bundles/martontopcars/js/```marton.js```** : The front-end scripts of the application. To have a nice overview, press ctrl+shift+- which will contract all modules and functions.

* **web/bundles/martontopcars/images/** : All the media used within the application should be stored here.

* **web/bundles/martontopcars/```app.php``` and ```app_dev.php```** : Both front-controllers can be found in this directory. First one is for production, second one is for development purposes.

* * *

# Progress #

### User Authentication and Authorisation ###
*"Finding out who you are and what you should have access to"*

I made good use of the Security component of Symfony. I set up a user provider, a firewall and access controls within ```security.yml``` in order to only allow registered users to access specific parts of my website.
The firewall is used to determine if a user has to be authenticated when visiting a particular page. If the answer is yes and the user isn't authenticated yet, the firewall will automatically redirect the user to a login form. Once the user is logged in using this form, the original request is resent.
The access control layer allows to define URLs whose access should be limited to specific roles. If a user visiting such a URL does not have the required privileges, the firewall will redirect them to a login form. The firewall also allows to simply log out users by removing them from the session when visiting a specific URL such as ```/logout```.
Since I store users in a database, the credentials of the to be authenticated visitor has to be matched against a large number of users within the database. Therefore my security user provider is a User class which implements the Security component's UserInterface, and is used by the Doctrine ORM to fetch a pool of users from the database. On successful authentication, a serialised instance of this class is saved in the session, therefore I let my User class implement the Serializable interface as well.
I set up a Role table which had a many-to-many association with users. I only added two roles for demonstration purposes. Every newly registered user is assigned a "Registered User" role. One user can also have multiple roles at the same time. In fact every logged in user has the roles below their role in the hierarchy, such as ```IS_AUTHENTICATED_FULLY``` by default.
The password of each user is combined with a set of random characters (salt) due to the fact that some users might use the same passwords, and then encrypted by a secured hash algorithm (SHA-512). This encrypted password is then stored in my User table.


### Validation ###
*"Never blindly trust any input"*

I implemented client-side form validation using HTML5, as well as server-side validation using Symfony's Validator component. For server-side validation I specified a set of constraints along with a set of error messages for each property within each model (i.e. User, UserDetails, SuggestedCar and Registration). 
These models are used when getting data from a submitted form. This data is checked against the specified set of constraints by the Form component. In case of an invalid property the validation returns one or more error messages corresponding to that property. These error messages are either rendered together with the submitted form (e.g. at Registration), or returned as a JSON response in case of an Ajax request (e.g. when suggesting a car). In the latter case, my javascrpt ErrorModule took care of printing the error messages into a specified container.
I also made sure that all my forms were protected against cross-site request forgery (CSRF) by including a hidden input containing a unique CSRF token in each form. After submitting a particular form, this token is compared against the one in the current session. In case of a mismatch, the form becomes invalid. This security check prevents people from submitting a form without them being the ones who requested that form.


### Testing ###
*"Never do what the computer can do for you"*

I created both unit tests and functional tests to test the correctness of my application. I set up an SQLite test database and configured it in ```config_test.yml``` as the default one to use with my tests. In this way the original database could remain untouched.  

* Unit Tests: I created a test for each method of each utility class. For example, I managed to test the correctness of the method which creates a unique filename, by calling the method twice one after the other with the same arguments, and comparing the two outputs against each other. 
* Functional Tests: I created tests for both my repository classes to test my queries, and my controllers to test the handling of incoming requests.
    * My repository test classes inherited a ```setUp``` and ```tearDown``` method from the ```KernelTestCase```. These methods were called before and after each test respectively, which allowed me to first prepare then clear the database.
    * My controller test classes had to extend Symfony's WebTestCase class in order to have access to a createClient method which created an HTTP client similar to a browser. With this client I was able to send both POST and GET requests to any URLs within the application. Since these requests were handled by the methods of controllers, my functional tests allowed me to test from the visitor's point of view whether each controller method returned an expected response.
However, since the client had to be authorised in order to make specific requests, I decided to create my own WebTestCase class which extended Symfony's existing one. In my own WebTestCase, I created methods which register, log in and delete a particular client. These methods were then inherited by all of my controller-test classes.
My registerClient method accepted two optional parameters (username and email) in case I would like to quickly register multiple clients in any of my tests.
Also, since each of my controller-test classes extended my WebTestCase class which extended Symfony's WebTestCase class which extended Symfony's KernelTestCase, I had access to override the ```setUp``` and ```tearDown``` methods inherited from Symfony's KernelTestCase class. In ```setUp``` I registered and logged in the user, whereas in ```tearDown``` I deleted their account.

These refactorings helped me avoid a lot of repetitive coding within my tests.
Most importantly, my tests helped me discover a few very important bugs in my application. Some of these bugs were related to wrong ORM annotations within my entity classes. For example, when a user was deleted, all the cars that the user owned also got deleted from the car table, even though only the link between the user and their cars should have been removed. Also, when deleting a user along with their images, even the default image within the same directory got deleted as I was checking for the wrong extension type. Finally, specific function calls have been fixed, that had to be surrounded by a try-catch statement in order to avoid the application crashing.

Despite of my efforts to keep all tests maintainable, there exists a better approach that could certainly be implemented in the future. Instead of writing scripts to fill up the test database with data and then to remove this data, one could create a script that overwrites the test database with a default test database in each test. This would allow the developer to have a default test database prepared, that gets cloned but never modified before each test.
Thankfully, due to my refactored design, this change could be implemented by simply including a "copy" and "reset" script in my ```setUp``` and ```tearDown``` methods. On the other hand, creating these scripts proved to be out of scope for me in this project, due to the lack of documentation on functional tests in Symfony.

### Profiling ###

Symfony comes with a profiler that can be enabled in both development and production environment. This profiler gives useful information about each request that is made, as well as how that request is processed within the application. Out of these information, the one I most frequently used was the list of queries sent by the Doctrine ORM.
Using the profiler, I am proud to have discovered the phenomena called lazy-loading even before reading about it online. I noticed that the number of executed queries kept linearly increasing with the number of users on the Leaderboard page. This is because I was trying to access the progress of each user in a for loop, forcing Doctrine to create a new query every time.
The solution was quite straightforward in most cases, as I simply had to write a DQL query which joined the appropriate entities together before I used the same loop over them. However, in one particular case I had to write a raw SQL query as I reached the limitations of DQL.
I noticed that the response to most of my requests took strangely long (up to 600ms) even in the production environment where the debugging mode was turned off. According to the timeline of Symfony's profiler, the execution time of the Security component's firewall was longest.
I also enabled PHP's Xdebug extension to save profiling data, and I used Qcachegrind as a tool to visualise this data. According to this approach, Symfony's ClassLoader proved to be the slowest.
However, on Dice computers the response times were generally 10 times shorter (anything up to 50ms). This increase in performance was possibly caused by an enabled PHP accelerator such as APC.

### Refactoring ###

I have managed to make the following improvements in both code-design and performance throughout the development of my code.   

* Instead of lazy-loading entities multiple times from the database, I created queries involving joins in order to load all results at once. This change had a positive impact on performance.
* Initially I built my forms within my controllers. In order to separate the two concepts, I moved my form-builders to their own classes. This change was also recommended by the Symfony community, allowing better maintainability of forms and less code in my controllers.
* In the early stage of development, I had a controller for rendering pages, and another one for handling Ajax requests. I quickly realised that there is a better way of organising controller methods, therefore I created a separate controller for each business model within my application. This allowed better maintainability as well as adding new features much quicker.
* Instead of using javascript "classes", I adapted the Module design pattern. This could enable a new developer to quicker overview and extend existing functionality.
* Within my javascript game, I refactored "classes" which would be instantiated many times (such as Player and Card), to follow the Prototype pattern. By adding new methods to their prototypes, only one reference of each method would be held even if multiple instances of that "class" were created. This keeps memory footprints to a minimum, resulting better performance.
* The handling of file uploads within my controllers used to have repetitive code of the same task, for example safely removing or renaming files. I managed to factor these out and reuse them elsewhere with the help of a utility class called FileHelper.
* Also, I modified my singleton utility classes to be services (as mentioned earlier), following the recommendation of the Symfony documentation.
* Finally, the refactoring of my functional tests (as mentioned earlier) would allow future extensions.

### Javascript ###

I followed the Module design pattern in order to group related variables and methods together. This allowed me to overview and maintain my code faster because I could easily decide where to look for a particular functionality. Also, the closure of the modules helped me prevent variables leaking into the global scope and causing conflicts with other variables with the same name.
Each of my modules return a public API that could be used anywhere within my application. Many of my general modules are reused multiple times. Those modules which are responsible for managing user interaction on particular pages, are initialised on the corresponding pages once.

### Game ###

I aimed to engineer the core of the game in a way so that it remains scalable and easily extendable. One example for this attempt is that the game could handle any number of players, not only the predefined ones. As long as one's computer can handle it, even a thousand players is possible. (However even the 10 players option destroys the point of the competition, since one can obtain many more points by only winning once. Any option above 3 players is for demonstration purposes only).
Another example is that I was able to extend the base game and therefore create a behaviourally different game by overriding specific methods.
The two versions of the game are wrapped inside the ```GameModule``` module which is responsible for managing user interactions within the game-page, such as launching one of the game types or changing the settings.

The players and their cards are represented as instances of the ```Player``` and ```Card``` class. Other frequently changing UI elements are controlled by specific modules within the game.
The scores are calculated for each player within each round in the following way. Each player's card is compared to every other player's card exactly once using a temporary array of user objects. In each comparison the difference between the selected property is added to the first player's score, whereas the negative of this difference is added to the second player's score. After comparing one player to every other player once, the player is removed from the array, preventing comparing the same players multiple times.
After the scores are calculated, draws are checked only for the first place, since any players, who are in draw for anything else other than the first place, count as losers.


The Classic (2nd) version of the game has a feature of changing the host of the game to be the previous round's winner by swapping the references of host and player objects. Moreover, the order of cards within each player's deck is maintained using queues, because this game mode allows winners to take the losers cards after every round.

* * *

# Developer #

Márton Széles
