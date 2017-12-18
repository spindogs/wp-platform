<?php
namespace Platform;

use Platform\Sql;
use Platform\Model;

class Collection {

    protected $model_name;
    protected $model;
    protected $sql;
    protected $tags = [];
    protected $index_by;
    protected $cache_by;
    protected $distinct;
    protected $count = [];
    protected $select = [];
    protected $joins = [];
    protected $where = [];
    protected $orderby = [];
    protected $limit;
    protected $offset;
    protected $rows = [];
    protected $found_rows;

    /**
     * @param string $model_name
     */
    public function __construct($model_name)
    {
        $this->model_name = $model_name;
        $this->model = new $model_name();
    }

    /**
     * @return void
     */
    public function distinct()
    {
        $this->distinct = true;
    }

    /**
     * @param array|string $fields
     * @return void
     */
    public function count($fields)
    {
        $fields = (array)$fields;
        $fields = array_flip($fields);
        $this->count = array_merge($this->count, $fields);
    }

    /**
     * @param array|string $fields
     * @return void
     */
    public function select($fields)
    {
        if ($fields === null) {
            $this->select = array();
            return;
        }

        $fields = (array)$fields;
        $fields = array_flip($fields);
        $this->select = array_merge($this->select, $fields);
    }

    /**
     * @param string $join
     * @return void
     */
    public function join($join)
    {
        if ($join === null) {
            $this->joins = array();
            return;
        }

        $this->joins[$join] = true;
    }

    /**
     * @param string $key
     * @param mixed $val
     * @param string $operator
     * @return void
     */
    public function where($key, $val, $operator = '=')
    {
        if ($val === null) {
            if (isset($this->where[$key])) {
                unset($this->where[$key]);
            }
        }

        if ($val == 'NULL' || $val == 'NOTNULL') {
            $operator = $val;
            $val = null;
        }

        if (!isset($this->where[$key])) {
            $this->where[$key] = [];
        }

        $this->where[$key][] = [
            'filter' => $val,
            'operator' => $operator
        ];
    }

    /**
     * @param string $key
     * @return void
     */
    public function groupby($key)
    {
        if ($key === null) {
            $this->groupby = array();
            return;
        }

        $this->groupby[$key] = true;
    }

    /**
     * @param string $key
     * @param string $ascdesc
     * @param string $filter
     * @return void
     */
    public function orderby($key, $ascdesc, $filter = null)
    {
        if ($key === null && $ascdesc == null) {
            $this->orderby = array();
            return;
        }

        $this->orderby[$key] = array(
            'ascdesc' => $ascdesc,
            'filter' => $filter
        );
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return void
     */
    public function limit($limit, $offset = null)
    {
        if ($limit) {
            $this->limit = $limit;
            $this->offset = $offset;
        } else {
            $this->limit = null;
            $this->offset = null;
        }
    }

    /**
     * @param int $page
     * @param int $per_page
     * @return void
     */
    public function paged($page = 1, $per_page = PER_PAGE)
    {
        $offset = ($page - 1) * $per_page;
        $this->limit($per_page, $offset);
    }

    /**
     * @param string $index_by
     * @return void
     */
    public function indexBy($index_by)
    {
        $this->index_by = $index_by;
    }

    /**
     * @param string $cache_by
     * @return void
     */
    public function cacheBy($cache_by)
    {
        $this->cache_by = $cache_by;
    }

    /**
     * @return void
     */
    protected function loadRows()
    {
        $model_name = $this->model_name;
        $cache_key = $this->getCacheKey();
        $raw = $this->getRaw();
        $rtn = [];

        foreach ($raw as $row) {

            //load model
            $model = new $model_name();
            $model->loadRow($row);

            //add to cache
            if ($cache_key) {
                $cache_search = $model->{$cache_key};

                if (empty($GLOBALS['cache'][$model_name][$cache_key][$cache_search])) {
                    $GLOBALS['cache'][$model_name][$cache_key][$cache_search] = $model;
                }
            }

            //add to return array
            if ($this->index_by) {
                $key = $model->{$this->index_by};
                $rtn[$key] = $model;
            } else {
                $rtn[] = $model;
            }

        }

        $this->rows = $rtn;
    }

    /**
     * @return void
     */
    public function getRaw()
    {
        global $Database;
        global $wpdb;

        $q = $this->getSql();

        if (isset($Database)) {
            $raw = $Database->getResults($q, 'OBJECT_NUM');
        } else {
            $raw = $wpdb->get_results($q);
        }

        if (!$raw) {
            $this->found_rows = 0;
            return array();
        }

        if (strpos($q, 'SQL_CALC_FOUND_ROWS') !== false) {
            if (isset($Database)) {
                $this->found_rows = $Database->getVal('SELECT FOUND_ROWS()');
                $this->found_rows = intval($this->found_rows);
            } else {
                $this->found_rows = $wpdb->get_var('SELECT FOUND_ROWS()');
                $this->found_rows = intval($this->found_rows);
            }
        }

        return $raw;
    }

    /**
     * @return mixed
     */
    public function getVal()
    {
        $raw = $this->getRaw();

        if ($raw) {
            $first_row = reset($raw);
            return reset($first_row);
        }
    }

    /**
     * @return string
     */
    public function getCacheKey()
    {
        if ($this->cache_by) {
            $cache_key = $this->cache_by;
        } else {
            $model_name = $this->model_name;
            $cache_key = $model_name::getCacheKey();
        }

        return $cache_key;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        $q = $this->model->query();
        $this->sql = $q;

        $this->prepareDependencies();

        $this->processTable();
        $this->processSelect();
        $this->processJoin();
        $this->processWhere();
        $this->processGroupby();
        $this->processOrderby();
        $this->processLimit();
        $this->processDistinct();

        $this->cleanup();

        return $this->sql;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        $this->loadRows();
        $rtn = $this->getRows();
        return $rtn;
    }

    /**
     * @todo
     * @return self
     */
    public function getSingle()
    {
        //get from cache
        $model_name = $this->model_name;
        $cache_key = $this->getCacheKey();

        if (!$cache_key) {
            $cache_search = false;
        } elseif (!property_exists($model_name, $cache_key)) {
            $cache_search = false;
        } elseif (empty($this->where[$cache_key])) {
            $cache_search = false;
        } else {
            $cache_search = false;
            foreach ($this->where[$cache_key] as $r) {
                if (isset($r['filter']) &&
                    isset($r['operator']) &&
                    $r['operator'] == '='
                ) {
                    $cache_search = $r['filter'];
                    break;
                }
            }
        }

        if (!$cache_search) {
            //ignore cache if no search value available
        } elseif (isset($GLOBALS['cache'][$model_name][$cache_key][$cache_search])) {
            $model = $GLOBALS['cache'][$model_name][$cache_key][$cache_search];
            return $model;
        }

        //get from database
        $this->limit(1);
        $rtn = $this->getAll();

        if ($rtn) {
            $model = reset($rtn);
            return $model;
        } else {
            $model = new $model_name();
            return $model;
        }
    }

    /**
    * @return int
    */
    public function getFoundRows()
    {
        return $this->found_rows;
    }

    /**
     * @return array
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * @todo
     * @return void
     */
    protected function prepareDependencies()
    {

    }

    /**
     * @return void
     */
    protected function processTable()
    {
        $q = $this->sql;

        if (strpos($q, '{table}') === false) {
            return;
        }

        $table = $this->model->getTable();
        $q_table = Sql::tick($table);
        $q = str_replace('{table}', $q_table, $q);
        $this->sql = $q;
    }

    /**
     * @return void
     */
    protected function processSelect()
    {
        if (!$this->count && !$this->select) {
            return;
        }

        $q = $this->sql;
        $q_select = '';

        $fields = $this->model->getSchema('fields');

        foreach ($this->count as $key => $x) {
            $column = $fields[$key]['column'];
            $count_name = 'count_'.$key;
            $q_select .= 'COUNT('.Sql::tick($column).') AS '.$count_name.',';
        }

        foreach ($this->select as $key => $x) {
            $column = $fields[$key]['column'];
            $q_select .= Sql::tick($column).',';
        }

        $q_select = rtrim($q_select, ',');
        $pattern = '/SELECT(.*)FROM/s';
        $replacement = 'SELECT '.$q_select.' FROM';
        $q = preg_replace($pattern, $replacement, $q);
        $this->sql = $q;
    }

    /**
     * @todo
     * @return void
     */
    protected function processJoin()
    {

    }

    /**
     * @return void
     */
    protected function processWhere()
    {
        $q = $this->sql;
        $fields = $this->model->getSchema('fields');

        foreach ($fields as $key => $r) {

            $tag = '{where_'.$key.'}';
            $type = $r['type'];
            $this->tags[] = $tag;

            if (isset($r['column'])) {
                $column = $r['column'];
            } else {
                continue; //dont need to filter by this field
            }

            if (empty($this->where[$key])) {
                continue; //dont need to filter by this field
            }

            foreach ($this->where[$key] as $r) {

                $val = $r['filter'];
                $operator = $r['operator'];

                if (is_array($val)) {
                    $operator = 'IN'; //force array mode
                }

                if ($operator == 'IN') {

                    if (!$val) {
                        continue; //blank array
                    }

                    $q_where = ' AND (';
                    $q_where_sub = '';

                    foreach ($val as $val_sub) {
                        $q_where_sub .= Sql::tick($column).' = '.Sql::quote($val_sub);
                        $q_where_sub .= ' OR ';
                    }

                    $q_where_sub = substr($q_where_sub, 0, -4);
                    $q_where .= $q_where_sub;
                    $q_where .= ')';

                } elseif ($operator == 'LIKE') {
                    $val = '%'.$val.'%';
                    $q_where = ' AND '.Sql::tick($column).' LIKE '.Sql::quote($val).'';

                } elseif ($operator == 'NULL') {
                    $q_where = ' AND '.Sql::tick($column).' IS NULL';

                } elseif ($operator == 'NOTNULL') {
                    $q_where = ' AND '.Sql::tick($column).' IS NOT NULL';

                } elseif ($type == Model::DATETIME) {
                    $q_where = ' AND '.Sql::tick($column).' '.$operator.' '.Sql::quote($val).'';

                } elseif ($type == Model::INTEGER) {
                    $q_where = ' AND '.Sql::tick($column).' '.$operator.' '.intval($val).'';

                } elseif ($type == Model::FLOAT) {
                    $q_where = ' AND '.Sql::tick($column).' '.$operator.' '.floatval($val).'';

                } elseif ($type == Model::STRING) {
                    $q_where = ' AND '.Sql::tick($column).' '.$operator.' '.Sql::quote($val).'';
                }

                $q = str_replace($tag, $q_where.$tag, $q);
            }
        }

        $this->sql = $q;
    }

    /**
     * @todo
     * @return void
     */
    protected function processGroupby()
    {

    }

    /**
     * @todo
     * @return void
     */
    protected function processOrderby()
    {

    }

    /**
     * @return void
     */
    protected function processLimit()
    {
        $q = $this->sql;
        $this->tags[] = '{limit}';

        if (empty($this->limit)) {
            return;
        }

        $q_limit = ' LIMIT '.intval($this->limit).'';
        $q_limit .= (isset($this->offset) ? ' OFFSET '.intval($this->offset).'' : '');

        $q = str_replace('{limit}', $q_limit, $q);
        $this->sql = $q;
    }

    /**
     * @return void
     */
    protected function processDistinct()
    {
        if (!$this->distinct) {
            return;
        }

        $q = $this->sql;
        $pattern = '/SELECT/';
        $replacement = 'SELECT DISTINCT';
        $q = preg_replace($pattern, $replacement, $q);
        $this->sql = $q;
    }

    /**
     * @return void
     */
    protected function cleanup()
    {
        $q = $this->sql;

        $this->tags = array_unique($this->tags);

        foreach ($this->tags as $tag) {
            $q = str_replace($tag, '', $q);
        }

        $this->sql = $q;
    }

}
