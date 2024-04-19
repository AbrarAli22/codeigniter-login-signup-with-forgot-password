<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->group("users", function ($routes) {
    $routes->post("sign-up", "UserController::signup");
    $routes->post("log-in", "UserController::login");
    $routes->post("forgotPassword", "UserController::forgotPassword");
    $routes->post("resetpassword", "UserController::resetpassword");
    $routes->get("menu","MenuController::getmenu");


});
$routes->group("admin", function ($routes) {
    $routes->post("sign-up-admin", "Admin\AdminController::adminsignup");
    $routes->post("log-in-admin", "Admin\AdminController::login");
    $routes->post("forgotPassword-admin", "Admin\AdminController::forgotPassword");
    $routes->post("resetpassword-admin", "Admin\AdminController::resetpassword");
});

$routes->group("api", ['filter' => 'jwtAuth'], function ($routes) {
    $routes->get("my-profile/(:segment)", "UserprofileController::myprofile/$1");
    $routes->post("add-catagories/(:segment)","Admin\CatagoriesController::addCatagories/$1");
    $routes->post("add-product/(:segment)","Admin\CatagoriesController::addProduct/$1");
});
