<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->group("users",function($routes){
    $routes->post("sign-up","UserController::signup");
    $routes->post("log-in","UserController::login");
    $routes->post("forgotPassword","UserController::forgotPassword");
    $routes->post("resetpassword","UserController::resetpassword");
    

});
$routes->group("admin",function($routes){
    $routes->post("sign-up-admin","AdminController::adminsignup");
    $routes->post("log-in-admin","AdminController::login");
    $routes->post("forgotPassword-admin","AdminController::forgotPassword");
    $routes->post("resetpassword-admin","AdminController::resetpassword");
});
$routes->group("api",['filter'=>'jwtAuth'],function($routes){
    $routes->get("my-profile/(:segment)","UserprofileController::myprofile/$1");
});
