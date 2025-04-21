<?php
session_start();

class Session {
    public static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    public static function destroy() {
        session_destroy();
    }

    public static function isLoggedIn() {
        return isset($_SESSION['agency_id']);
    }

    public static function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header("Location: /login.php");
            exit();
        }
    }

    public static function requireAdmin() {
        if (!self::isAdmin()) {
            header("Location: /dashboard.php");
            exit();
        }
    }
}
?> 