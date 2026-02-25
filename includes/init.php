<?php
if (!defined('INIT_LOADED')) {
    define('INIT_LOADED', true);
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}
