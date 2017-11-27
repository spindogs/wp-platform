## Installing

Install via composer:

    composer require spindogs/wp-platform

## Platform

All classes are encapsulated in the namespace `Platform`.

## Routing

Create a class called `App\Routes.php` which extends `Platform\Route.php`. All rules should be placed in the `Routes::register()` method as below:

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

## Collections

To access data from a model use the `Platform\Collection.php` and pass the name of the model to the constructor (as a string):

    $collection = new Collection('App\Order');

You are then able to filter the query using your fields previously setup in the model:

    $collection->where('id', 9999);

Call the method `getAll()` to retrieve an array of objects:

    $orders = $collection->getAll();

Alternatively, you can grab a single object using the following method:

    $order = $collection->getSingle();

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
