<?php

namespace Tablesome\Includes\Lib\Table_Crud_WP;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('\Tablesome\Includes\Lib\Table_Crud_WP\Table_Crud_WP')) {
    class Table_Crud_WP
    {
        public $wpdb;
        public $wp_prefix;
        public $helper;
        public $schema;

        public $default_columns_fields = array(
            'id',
            'post_id',
            'author_id',
            'created_at',
            'updated_at',
            'rank_order',
        );

        public function __construct()
        {
            global $wpdb;
            $this->wpdb = $wpdb;
            $this->wp_prefix = $this->wpdb->prefix;

            $this->helper = new \Tablesome\Includes\Lib\Table_Crud_WP\Helper();
            $this->schema = new \Tablesome\Includes\Lib\Table_Crud_WP\Schema();
        }

        /**
         * Use of this method for get the tablename by table-id
         *
         * @param integer|string $table_id | $table_name
         * @param integer $prefix
         * @return string table-name
         */
        public function get_table_name($table_id, $prefix = 1)
        {
            if (!is_numeric($table_id)) {return $table_id;}

            $table_name = TABLESOME_TABLE_NAME . '_' . $table_id;
            if ($prefix == 0) {
                return $table_name;
            }
            return $this->wp_prefix . $table_name;
        }

        public function table_exists($table_id)
        {

            global $wpdb;
            $table_name = $this->get_table_name($table_id);
            // $query = "SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME = '$table_name';";
            // $result = $this->wpdb->query($query);

            $result = $this->wpdb->query($wpdb->prepare("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME = %s", $table_name));
            $this->wpdb->flush();
            return $result;
        }

        public function create_table($table_id, $meta_data)
        {
            if (empty($table_id) || intval($table_id) < 0) {
                return;
            }

            /** Get user created columns */
            $table_columns = $this->helper->get_table_columns($meta_data);

            /** get table schema */
            $schema = $this->schema->get_schema($table_columns);

            $charset_collate = $this->wpdb->get_charset_collate();
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            $table_name = $this->get_table_name($table_id);

            /** Creating a custom table by table post */
            $query = "CREATE TABLE IF NOT EXISTS $table_name ($schema) $charset_collate;";
            dbDelta($query);
            return true;
        }

        /**
         * Use of this method getting the table columns from the database by table-id
         *
         * @param [integer] $table_id
         * @return array $column_fields
         */
        public function get_table_columns_from_db($table_id)
        {
            $table_name = $this->get_table_name($table_id);
            $table_name_escaped = esc_sql($table_name);

            $sql = $this->wpdb->prepare("SHOW COLUMNS FROM %1s", $table_name_escaped);
            // $sql = $wpdb->prepare("SHOW COLUMNS FROM %s", $table_name);

            $results = $this->wpdb->get_results($sql, ARRAY_A);
            // $results = $this->wpdb->get_results("SHOW COLUMNS FROM $table_name", ARRAY_A);
            $column_fields = array_column($results, 'Field');
            return $column_fields;
        }

        public function insert_many($table_id, array $data = array())
        {
            if (empty($table_id) || empty($data)) {
                return;
            }

            /** Table Name */
            $table_name = $this->get_table_name($table_id);
            $values = array();
            foreach ($data as $item) {
                $values[] = "('" . implode("','", array_values($item)) . "')";
            }

            /** Get Inserting Fields */
            $fields = implode(",", array_keys($data[0]));

            $query_clause = array();
            $query_clause[] = "INSERT INTO " . $table_name;
            $query_clause[] = "($fields)";
            $query_clause[] = "VALUES";
            $query_clause[] = implode(",", $values) . ";";
            $query = implode(" ", $query_clause);

            $result = $this->wpdb->query($query);
            $this->wpdb->flush();

            if ($result) {
                return $result;
            }
            return false;
        }
    }
}
