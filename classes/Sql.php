<?php
namespace Platform;

use Platform\Filter;

class Sql {

    /**
    * @param string $q
    * @return string
    */
    public static function tick($q)
    {
        $q = str_replace('`', '', $q);
        $bits = explode('.', $q);
        $rtn = implode('`.`', $bits);
        $rtn = '`'.$rtn.'`';
        return $rtn;
    }

    /**
     * @param string $str
     * @return string
     */
    public static function quote($str)
    {
        if (function_exists('esc_sql')) {
            return '"'.esc_sql($str).'"';
        }

        if (isset($GLOBALS['Database'])) {
            $database = $GLOBALS['Database'];
        } else {
            $database = new oDatabase();
        }

        $rtn = mysqli_real_escape_string($database->link, $str);
        return '"'.$rtn.'"';
    }

    /**
     * @param string $asc_desc
     * @return string
     */
    public static function ascdesc($asc_desc)
    {
        $asc_desc = strtoupper($asc_desc);

        if ($asc_desc == 'ASC' || $asc_desc == 'DESC') {
            $rtn = $asc_desc;
        } else {
            $rtn = '';
        }

        return $rtn;
    }

    /**
     * @param array|string $vars
     * @return string
     */
    public static function concat($vars)
    {
        if (!$vars) {
            return '"X"'; //this prevents empty arrays causing an sql error
        }

        $rtn = '';

        if (!is_array($vars)) {
            $vars = explode(',', $vars);
        }

        foreach ($vars as $var) {
            $rtn .= self::quote($var).',';
        }

        return rtrim($rtn, ',');
    }

    /**
     * @param array $values
     * @param string $table
     * @param string $on_duplicate IGNORE|UPDATE
     * @param bool $auto_condense
     * @return int
     */
    public static function autoCreate($values, $table, $on_duplicate = '', $auto_condense = true)
    {
        global $Database;
        global $wpdb;

        if ($auto_condense) {
            $values = self::condense($values, $table);
        }

        $q = 'INSERT '.($on_duplicate == 'IGNORE' ? 'IGNORE' : '');
        $q .= ' INTO '.self::tick($table).' (';

        foreach ($values as $key => $val) {
         $q .= self::tick($key).',';
        }

        $q = substr($q, 0, -1);

        $q .= ') VALUES (';

        foreach ($values as $key => $val) {
         $q .= self::quote($val).',';
        }

        $q = substr($q, 0, -1);

        $q .= ')';

        if ($on_duplicate == 'UPDATE') {

         $q .= 'ON DUPLICATE KEY UPDATE ';

        foreach ($values as $key => $val) {
            $q .= self::tick($key).' = VALUES('.self::tick($key).'),';
        }

        $q = substr($q, 0, -1);

        }

        if (isset($Database)) {
            $Database->query($q);
            return $Database->getInsertId();
        } else {
            $wpdb->query($q);
            return $wpdb->insert_id;
        }

     }

    /**
     * @param array $values
     * @param int|array $where
     * @param string $table
     * @param bool $auto_condense
     * @return bool
     */
    public static function autoUpdate($values, $where, $table, $auto_condense=true)
    {
        global $Database;
        global $wpdb;

         if (!$where) {
             return false;
         }

        if ($auto_condense) {
            $values = self::condense($values, $table);
        }

         if (!is_array($where)) {
             $where = array('id' => $where); //default string to id
         }

         $q = 'UPDATE '.self::tick($table).' SET ';

         foreach ($values as $field => $value) {
            $q .= self::tick($field).' = '.self::quote($value).',';
         }

         $q = substr($q, 0, -1);
         $q .= ' {where}';

         $q = self::autoQuery($q, $where);

        if (isset($Database)) {
            $result = $Database->query($q);
        } else {
            $result = $wpdb->query($q);
        }

         if ($result) {
             return true;
         } else {
             return false;
        }
     }

    /**
     * @param string $q
     * @param int|array|bool $where
     * @param array|bool $orderby
     * @param string|bool $limit
     * @return string
     */
    public static function autoQuery($q, $where=false, $orderby=false, $limit=false)
    {
        $q_where = ' WHERE 1=1';

        if ($where) {

            if (!is_array($where)) {
                $where = ['id' => $where]; //default string to id
            }

            foreach ($where as $field => $value) {
                $q_where .= ' AND '.self::tick($field).' = '.self::quote($value).'';
            }

        }

        $q_orderby = '';

        if ($orderby) {

            $first_arg = reset($orderby);
            $q_orderby = ' ORDER BY';

            if (!is_array($first_arg)) { //convert single arguments to the multi format
                $orderby = [];
                $orderby[] = [$orderby[0], $orderby[1]];
            }

            foreach ($orderby as $r) {
                $q_orderby .= self::tick($r[0]).' '.self::ascdesc($r[1]).',';
            }

            $q_orderby = trim($q_orderby, ','); //get rid of last comma

        }

        $q_limit = '';

        if ($limit) {
            $q_limit = ' LIMIT '.$limit;
        }

        $q = str_replace('{where}', $q_where, $q);
        $q = str_replace('{orderby}', $q_orderby, $q);
        $q = str_replace('{limit}', $q_limit, $q);

        return $q;

     }

    /**
     * @param string $table
     * @param string $field
     * @param array $options
     * @param int|array $where
     * @return bool
     */
    public static function updateOptions($table, $field, $options, $where)
    {
        global $Database;
        global $wpdb;

         $q = 'DELETE FROM '.self::tick($table).'
                 {where}';

         $q = self::autoQuery($q, $where);

        if (isset($Database)) {
            $Database->query($q);
        } else {
            $wpdb->query($q);
        }

        $options = (array)$options;

         foreach ($options as $option) {

             $values = array();
             $values[$field] = $option;
             $values += $where;

             self::autoCreate($values, $table);

         }

         return true;
     }

    /**
     * @param array $values
     * @param string $table
     * @return array
     */
    public static function condense($values, $table)
    {
        $fields = self::getColumns($table);
        $rtn = Filter::arrayCondense($values, $fields);
        return $rtn;
    }

    /**
     * @param string $table
     * @return array
     */
    public static function getColumns($table)
    {
        global $Database;
        global $wpdb;

        if (isset($Database)) {
            $schema = $Database->getResults('SHOW COLUMNS FROM '.self::tick($table).'');
        } else {
            $schema = $wpdb->get_results('SHOW COLUMNS FROM '.self::tick($table).'');
        }

        $rtn = array();

        foreach ($schema as $r) {
            $r = (object)$r;
            $rtn[$r->Field] = $r->Field;
        }

        return $rtn;
    }

    /**
     * @param string $table
     * @param string $column
     * @param array $attrs;
     * @return void
     */
    public static function addColumn($table, $column, $type, $attrs = [])
    {
        global $Database;
        global $wpdb;

        $table_exists = self::tableExists($table);

        if ($table_exists) {

            $column_exists = self::columnExists($table, $column);

            if ($column_exists) {
                return;
            }

            $q = 'ALTER TABLE '.self::tick($table).'
                    ADD '.self::tick($column).' '.$type.'';

            if (isset($attrs['after'])) {
                $after_column = $attrs['after'];
                $q .= ' AFTER '.self::tick($after_column).'';
            }

            if (isset($Database)) {
                $Database->query($q);
            } else {
                $wpdb->query($q);
            }

        } else {

            $q = 'CREATE TABLE
                    '.self::tick($table).'
                    (
                        '.self::tick($column).' '.$type.'
                    )';

            if (isset($Database)) {
                $Database->query($q);
            } else {
                $wpdb->query($q);
            }

            echo 'Created table '.$table."\n";

        }

        echo 'Added new column '.$table.'.'.$column."\n";
    }

    /**
     * @param string $table
     * @param string $column
     * @return bool
     */
    public static function tableExists($table)
    {
        global $Database;
        global $wpdb;

        $q = 'SHOW TABLES
                LIKE '.self::quote($table).'';

        if (isset($Database)) {
            $table_exists = $Database->getVal($q);
        } else {
            $table_exists = $wpdb->get_var($q);
        }

        if ($table_exists) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $table
     * @param string $column
     * @return bool
     */
    public static function columnExists($table, $column)
    {
        global $Database;
        global $wpdb;

        $table_exists = self::tableExists($table);

        if (!$table_exists) {
            return false;
        }

        $q = 'SHOW COLUMNS
                FROM '.self::tick($table).'
                LIKE '.self::quote($column).'';

        if (isset($Database)) {
            $column_exists = $Database->getVal($q);
        } else {
            $column_exists = $wpdb->get_var($q);
        }

        if ($column_exists) {
            return true;
        } else {
            return false;
        }
    }

}
