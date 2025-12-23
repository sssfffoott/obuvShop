<?php
require_once 'config.php';

function getDB() {
    static $db = null;
    
    if ($db === null) {
        try {
            $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($db->connect_error) {
                error_log("Ошибка подключения к БД: " . $db->connect_error);
                return null;
            }
            
            $db->set_charset("utf8");
        } catch (Exception $e) {
            error_log("Ошибка БД: " . $e->getMessage());
            return null;
        }
    }
    
    return $db;
}
?>