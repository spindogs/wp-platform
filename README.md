## Installing

Install via composer:

    composer require spindogs/wp-platform

Ensure you add the following PSR-4 autoloading location to your `composer.json` file:

    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    }

Assuming your `vendor` directory is in your theme root, add the following line to `functions.php` to hook up the composer autoloading to your theme:

    require('vendor/autoload.php');

## Platform

All platform classes can be accessed via the namespace `Platform`.

To setup the platform for Wordpress add the following line to your `functions.php`:

    Platform\Setup::setupWordpress();

If you are using the platform outside of Wordpress you must call the following method instead to run some core setup routines:

    Platform\Setup::setup();

## Post Types

To make a Wordpress custom post type create a new class in the namespace `App\PostType` that extends `Platform\PostType.php`:

    <?php
    namespace App\PostType;

    use Platform\PostType;

    class Event extends PostType {

        protected static $custom_type = 'event';

        /**
         * @return void
         */
        public static function setup()
        {
            parent::setup();
            self::registerPostType();
        }

        /**
         * @return void
         */
        public static function registerPostType()
        {
            $labels = array(
                'name' => 'Events',
                'singular_name' => 'Event',
                'add_new' => 'Add event',
                'add_new_item' => 'Add event',
                'edit_item' => 'Edit event',
                'new_item' => 'New event',
                'view_item' => 'View event',
                'search_items' => 'Search events',
                'not_found' => 'No events found',
                'all_items' => 'List events',
                'menu_name' => 'Events',
                'name_admin_bar' => 'Event'
            );
            $args = array(
                'labels' => $labels,
                'public' => true,
                'show_ui' => true,
                'capability_type' => 'post',
                'menu_icon' => 'dashicons-calendar-alt',
                'hierarchical' => false,
                'supports' => ['title', 'editor', 'author', 'thumbnail'],
                'taxonomies' => [],
                'has_archive' => true,
                'rewrite' => ['slug' => 'events', 'with_front' => false]
            );

            register_post_type(self::$custom_type, $args);
        }

    }

You can then register this new post type in `functions.php` by calling the `setup()` method:

    App\PostType\Event::setup();

There are a number of helpful features going on behind the scenes when you register your custom post types in this manner. In particular, it means that if a CMS page URL is created with a matching URL of the rewrite slug then Wordpress will automatically load the CMS page into the global `$post` variable when viewing the post type archive.

For example, if your post_type has the rewrite slug `/events/` and a visitor accesses the archive page for this post_type then the platform will attempt to load a CMS page with the matching url `/events/`.

This allows an editor to content manage fields on an archive page that is not possible with core Wordpress alone.

## Routing

For more bespoke development requirements, you can create your own custom URLs and bypass the core Wordpress template loader. Create a class called `App\Routes.php` that extends `Platform\Route.php`. All rules should be placed in the `Routes::register()` method as demonstrated below:

    <?php
    namespace App;

    use Platform\Route;

    class Routes extends Route {

        /**
         * @return void
         */
        public static function register()
        {
            self::match('basket/add/([0-9]+)')->call('BasketController', 'add');
        }

    }

## Controllers

Create your controllers in the `App\Controller` namespace extending the class `Platform\Controller.php`.

    <?php
    namespace App\Controller;

    use Platform\Controller;

    class BasketController extends Controller {

        /**
         * @param int $product_id
         * @return void
         */
        public function add($product_id)
        {
            $this->data('product_id', $product_id); //pass data to view
            $this->render('basket'); //render the view
        }

    }

There are a number of helpful methods within a controller:

* Render a template - the template path defaults to the theme root  
`$this->render('template_name')`

* Pass data to the view  
`$this->data('key', 'value')`

* Get any data that had already been passed to the view  
`$this->getData('product_id')`

* Call a method from a different controller and merge the data into view  
`$this->uses('AnotherController', 'methodName')`

* Encode data and render with a JSON content type header  
`$this->json($data)`

* Return a view object pre-populated with data from the controller ready to be rendered  
`$this->getView($template_name)`

## Models

For your models extend the `Platform\Model.php` class. Set the `protected static $table` property to match the model's database table and define the `public` properties available for this model:  

    <?php
    namespace App;

    use Platform\Model;

    class Order extends Model {

        protected static $table = 'Orders';

        public $id;
        public $date_created;
        public $subtotal;
        public $discount;
        public $shipping;
        public $tax;
        public $total;
        public $status;

    }

Every model MUST have a `query()` method that returns a query string:

    /**
     * @return string
     */
    public function query()
    {
        $q = 'SELECT o.*
                FROM Orders AS o
                WHERE 1
                    {where_id}
                ORDER BY
                    o.date_created DESC
                {limit}';

        return $q;
    }

You can also use this method to setup the schema of your model passing an array to the `fields()` method:

    $this->fields([
        'id' => [
            'column' => 'o.id',
            'type' => self::INTEGER
        ],
        'date_created' => [
            'column' => 'o.date_created',
            'type' => self::DATETIME
        ],
    ]);

This does two things - firstly it defines what fields can be searched on by using the `{where_*}` tags in the query string by mapping to a `column`.

Secondly it allows values from the database to be cast to a particular data type using the `type` key.

There are a number of data types built in:

* `Model::INTEGER`
* `Model::STRING`
* `Model::FLOAT`
* `Model::DATETIME`
* `Model::DATE`

## Collections

To access data from a model use the `Platform\Collection.php` and pass the name of the model to the constructor (as a string):

    $collection = new Collection('App\Order');

Alternatively, you can call the `collection()` method on a model:

    $collection = Order::collection();

You are then able to filter the query using your fields previously setup in the model:

    $collection->where('id', 9999);

By default, the `where()` method will produce an `=` clause but you can override this by passing an operator as a third parameter:

    $collection->where('id', 9999, '>=');

The following operators are recognised:

* `=`
* `<`, `>`, `<=`, `>=`, `<>`
* `LIKE`

Call the method `getAll()` to retrieve an array of objects:

    $orders = $collection->getAll();

Alternatively, you can grab a single object using the following method:

    $order = $collection->getSingle();

There are a number of helpful methods you can use on a collection:

* Customise the fields to return from a query  
`$collection->select('field_name')`

* Do a DISTINCT select query   
`$collection->distinct()`

* Do a COUNT aggregate query on a field   
`$collection->count('field_name')`

* Get the SQL generated by a collection  
`$collection->getSql()`

* Get the raw results of a collection (not strongly typed to an object)  
`$collection->getRaw()`

* Get a single value from a query result (useful for aggregates)  
`$collection->getVal()`

## Translations

The platform comes with built in interfaces to allow the translating of static strings used in your templates. This mechanism assumes a multisite setup whereby each site installation corresponds to a different language (eg. English, Welsh).

To use the functionality, first create the translation database tables by calling the following method once only:

    Platform\Translation::install();

To activate the admin interfaces, simply add the following line to your `functions.php` to set the default language to your primary multisite installation:

    Platform\Setup::defaultLang(1);
    Platform\Translation::setup();

Finally, ensure you wrap all the static strings in your templates with the `Translation::_()` method. To make your code more readable could always assign a shorter alias for the translation class:

    <?php
    use Platform\Translation as T;
    echo '<h1>'.T::_('Hello world').'</h1>';

If you want new translations to automatically be created in the database, add the following line to `functions.php`:

    Platform\Setup::debug();

## Admin

From time to time you may want to add custom interfaces to the wordpress back office. You can do this by setting up routes in `App\Routes.php` using the `Routes::admin()` method:

> Note - all routes you add using the `Routes::admin()` method will be auto prefixed by `wp-admin/`

    <?php
    namespace App;

    use Platform\Route;

    class Routes extends Route {

        /**
         * @return void
         */
        public static function register()
        {
            self::admin('orders')->call('AdminController', 'listOrders');
        }

    }

You can then register a new admin menu item using the following wordpress function:

    add_menu_page(
        'Orders',
        'Orders',
        'publish_pages',
        'orders',
        null,
        'dashicons-products',
        50
    );

## Helpers

There are a number of different helper functions available:

#### Date

* humanTime
* timeAgo
* timeToSecs
* fromMysqlTime
* fromUnixTime
* mysqltime
* datetime

#### Filter

* clean
* yesno
* unconcat
* extension
* remove
* numeric
* nullify
* coalesce
* titleCase
* snakeCase
* arraySimplify
* arrayCondense
* arrayFlatkeys
* arrayRearrange
* arrayShuffle

#### Html

* entities
* number
* currency
* abbreviate
* purify
* percentage
* nl2br
* pluralise

#### Request

* method
* scheme
* host
* path
* query
* fragment
* httpHost
* get
* url
* param

#### Sql

* tick
* quote
* ascdesc
* concat
* autoCreate
* autoUpdate
* autoQuery
* updateOptions
* condense
* getColumns
* addColumn
* tableExists
* columnExists

#### Url

* addVar
* removeVar
* http
* stripHttp
* tokenise
* checkToken

#### Validate

* contains
* startsWith
* datePast
* dateFuture
