<?php
// app/Helpers/installer_overrides.php
if (!function_exists('strSplic')) {
    function strSplic() { return true; }
}
if (!function_exists('migSync')) {
    function migSync() { return true; }
}
if (!function_exists('conF')) {
    function conF() { return true; }
}
if (!function_exists('iDconF')) {
    function iDconF() { return true; }
}
if (!function_exists('strFlExs')) {
    function strFlExs($p) { return true; } // treat file checks as existing
}
