<?php
namespace Platform;

class Mysql {

    /**
     * @param array $values
     * @param string $table
     * @param string $on_duplicate IGNORE|UPDATE
     * @param bool $auto_condense
     * @return int
     */
    public static function autoCreate($values, $table, $on_duplicate='', $auto_condense=true)
    {
        global $Database;
        global $wpdb;

        if ($auto_condense) {
            $values = self::condense($values, $table);
        }

         $q = "INSERT ".($on_duplicate == 'IGNORE' ? 'IGNORE' : '');
         $q .= " INTO `".$table."` (";

         foreach ($values as $key => $val) {
             $q .= "`".$key."`,";
         }

         $q = substr($q, 0, -1);

         $q .= ") VALUES (";

         foreach ($values as $key => $val) {
             $q .= Security::escSQL($val).",";
         }

         $q = substr($q, 0, -1);

         $q .= ")";

         if ($on_duplicate == 'UPDATE') {

             $q .= "ON DUPLICATE KEY UPDATE ";

            foreach ($values as $key => $val) {
                $q .= "`".$key."` = VALUES(`".$key."`),";
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

         $q = "UPDATE `".$table."` SET ";

         foreach ($values as $field => $value) {
            $q .= "`".$field."` = ".Security::escSQL($value).",";
         }

         $q = substr($q, 0, -1);
         $q .= " {where}";

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
        $q_where = " WHERE 1=1";

        if ($where) {

            if (!is_array($where)) {
                $where = array('id' => $where); //default string to id
            }

            foreach ($where as $field => $value) {
                $q_where .= " AND `".$field."` = ".Security::escSQL($value)."";
            }

        }

        $q_orderby = '';

        if ($orderby) {

            $first_arg = reset($orderby);
            $q_orderby = " ORDER BY";

            if (!is_array($first_arg)) {
                $orderby = array(array($orderby[0], $orderby[1])); //convert single arguments to the multi format
            }

            foreach ($orderby as $r) {
                $q_orderby .= "`".$r[0]."` ".self::escASC($r[1]).",";
            }

            $q_orderby = trim($q_orderby, ','); //get rid of last comma

        }

        $q_limit = '';

        if ($limit) {
            $q_limit = " LIMIT ".$limit;
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

         $q = "DELETE FROM `".$table."`
                 {where}";

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
        $rtn = Filter::array_condense($values, $fields);

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
            $schema = $Database->getResults("SHOW COLUMNS FROM `".$table."`");
        } else {
            $schema = $wpdb->get_results("SHOW COLUMNS FROM `".$table."`");
        }

        $rtn = array();

        foreach ($schema as $r) {
            $r = (object)$r;
            $rtn[$r->Field] = $r->Field;
        }

        return $rtn;

    }

}
