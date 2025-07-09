<?php
    require_once '../connection.php';

    // Block access if not logged in
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }

    // Función para verificar permisos
    function hasPermission($module, $action) {
        // Si es admin, tiene todos los permisos
        if (isset($_SESSION['admin_id'])) {
            return true;
        }
        
        // Verificar permisos personalizados primero
        if (isset($_SESSION['custom_permissions']) && $_SESSION['custom_permissions']) {
            $custom_permissions = json_decode($_SESSION['custom_permissions'], true);
            if (isset($custom_permissions[$module][$action])) {
                return $custom_permissions[$module][$action];
            }
        }
        
        // Verificar permisos del rol
        if (isset($_SESSION['role_permissions']) && $_SESSION['role_permissions']) {
            $role_permissions = json_decode($_SESSION['role_permissions'], true);
            if (isset($role_permissions[$module][$action])) {
                return $role_permissions[$module][$action];
            }
        }
        
        return false;
    }

    // Función para verificar si puede acceder a un módulo
    function canAccess($module) {
        return hasPermission($module, 'read') || hasPermission($module, 'write');
    }

    // Función para verificar si puede escribir en un módulo
    function canWrite($module) {
        return hasPermission($module, 'write');
    }

    // Función para verificar si puede eliminar en un módulo
    function canDelete($module) {
        return hasPermission($module, 'delete');
    }
?>