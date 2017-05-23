<?php
namespace Platform\Beta;

use Platform\Filter;
use Platform\Security;
use Platform\Mysql;
use Platform\Beta\Collection;

class Model {

    const INTEGER = 'integer';
    const STRING = 'string';
    const FLOAT = 'float';
    const DATETIME = 'datetime';

    //protected static $table;
    //protected static $cache_by_key;
    //protected static $hard_delete;
    //protected static $delete_column;

    protected $is_loaded = false;

    protected $_fields = [];
    protected $_joins = [];
    protected $_groupby = [];
    protected $_orderby = [];

    /**
     * @param int $id
     * @return self
     */
    public function __construct($id = false)
    {
        if (property_exists($this, 'id')) {
            $this->id = $id;
        }
    }

    /**
     * @param array $fields
     * @return void
     */
    public function fields($fields)
    {
        $this->_fields = $fields;
    }

    /**
     * @param array $joins
     * @return void
     */
    public function joins($joins)
    {
        $this->_joins = $joins;
    }

    /**
     * @param array $groupby
     * @return void
     */
    public function groupby($groupby)
    {
        $this->_groupby = $groupby;
    }

    /**
     * @param array $orderby
     * @return void
     */
    public function orderby($orderby)
    {
        $this->_orderby = $orderby;
    }

    /**
     * @return bool
     */
    final public function load()
    {
        if ($this->isLoaded()) {
            return; //already loaded
        }

        $class_name = get_called_class();
        $collection = new Collection($class_name);
        $collection->where('id', $this->id);
        $model = $collection->getSingle();

        if (!$model) {
            return;
        }

        $row = get_object_vars($model);
        $this->loadRow($row);
    }

    /**
     * @param array $row
     * @return void
     */
    public function loadRow($row)
    {
        $this->map($row);
        $this->typecast();
        $this->loader();
        $this->is_loaded = true;
        $this->cleanup();
    }

    /**
     * @return void
     */
    protected function loader()
    {
        //this is a placeholder
    }

    /**
     * @return void
     */
    public function reload()
    {
        $this->is_loaded = false;
        $this->load();
    }

    /**
     * @return void
     */
    protected function typecast()
    {
        $this->query(); //force model fields to build
        $fields = $this->getSchema('fields');

        foreach ($fields as $key => $r) {

            if (!isset($this->{$key})) {
                continue; //ignore if no value
            }

            $type = $r['type'];

            switch ($type) {
                case self::DATETIME:
                    $this->{$key} = Filter::datetime($this->{$key});
                break;
            }

        }
    }

    /**
     * @return void
     */
    protected function cleanup()
    {
        $internal_vars = array(
            '_fields',
            '_joins',
            '_groupby',
            '_orderby',
        );

        foreach ($internal_vars as $var) {
            unset($this->{$var});
        }
    }

    /**
     * @return bool
     */
    public function isLoaded()
    {
        return (boolean)$this->is_loaded;
    }

    /**
     * @param array $values
     * @return int
     */
    public function create($values = []) {
        $this->map($values);
        $this->id = null;
        $this->save();
        return $this->id;
    }

    /**
     * @param array $values
     * @return int
     */
    public function update($values = []) {
        $this->map($values);
        $this->save();
        return $this->id;
    }

    /**
     * @return void
     */
    public function delete()
    {
        global $Database;
        global $wpdb;

        $static = get_called_class();
        $table = static::getTable();
        $hard_delete = static::getSetting('hard_delete');
        $delete_column = static::getSetting('delete_column');

        if (!$delete_column) {
            $delete_column = 'is_deleted';
        }

        if (!$hard_delete) {

            $values = array();
            $values[$delete_column] = 1;
            Mysql::autoUpdate($values, $this->id, $table);

        } else {

            $q = 'DELETE FROM '.Security::escCol($table).'
                    WHERE id = '.intval($this->id).'';

            if (isset($Database)) {
                $Database->query($q);
            } else {
                $wpdb->query($q);
            }

        }
    }

    /**
     * @return int
     */
    protected function save()
    {
        $this->query(); //force model fields to build
        $fields = $this->getSchema('fields');
        $this->cleanup();

        $table = static::getTable();
        $static = get_called_class();
        $values = get_object_vars($this);

        if (isset($values['id'])) {
            unset($values['id']); //protect the id
        }

        foreach ($fields as $key => $r) {

            if (!isset($values[$key])) {
                continue; //ignore if no value
            }

            $type = $r['type'];

            switch ($type) {
                case self::DATETIME:
                    $values[$key] = Filter::mysqltime($values[$key]);
                break;
            }

        }

        if ($this->id) {
            Mysql::autoUpdate($values, $this->id, $table, true);
        } else {
            $this->id = Mysql::autoCreate($values, $table, '', true);
        }

        return $this->id;
    }

    /**
     * @param array $row
     * @return void
     */
    public function map($row)
    {
        $row = (array)$row;
        $fields = get_object_vars($this);

        foreach ($row as $key => $val) {
            if (array_key_exists($key, $fields)) {
                $this->{$key} = $val;
            }
        }
    }

    /**
     * @return string
     */
    protected function query()
    {
        //this is a placeholder
    }

    /**
     * @param int $id
     * @return self
     */
    public static function init($id)
    {
        $static = get_called_class();
        $model = new $static($id);
        return $model;
    }

    /**
     * @param string $id
     * @return self
     */
    public static function getById($id)
    {
        $static = get_called_class();
        $collection = new Collection($static);
        $collection->where('id', $id);
        $model = $collection->getSingle();
        return $model;
    }

    /**
     * @param string $clean
     * @return self
     */
    public static function getByClean($clean)
    {
        $static = get_called_class();
        $collection = new Collection($static);
        $collection->where('clean', $clean);
        $model = $collection->getSingle();
        return $model;
    }

    /**
     * @return string
     */
    public static function getTable()
    {
        return static::$table;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function getSetting($key)
    {
        $static = get_called_class();

        if (property_exists($static, $key)) {
            return static::${$key};
        }
    }

    /**
     * @param string $key
     * @return array
     */
    public function getSchema($key)
    {
        $key = '_'.$key;
        return $this->{$key};
    }

    /**
     * @return string
     */
    public static function getCacheKey()
    {
        return static::getSetting('cache_by_key');
    }

}
