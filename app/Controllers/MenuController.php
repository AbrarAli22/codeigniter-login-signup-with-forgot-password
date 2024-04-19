<?php

namespace App\Controllers;

use App\Models\ProductModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class MenuController extends ResourceController
{
    public function getmenu()
    {
        $MenuModel = new ProductModel();
        $allproducts = $MenuModel->where('is_active', 'active')->findAll();
        $count=count($allproducts);
        if ($allproducts) {
            $response = [
                "status" => 200,
                "error" => false,
                "messgae" => "all products",
                "count"=>$count,
                "data" => $allproducts
            ];
        } else {
            $response = [
                "status" => 400,
                "error" => true,
                "message" => "No item found!"
            ];
        }
        return $this->respondCreated($response);
    }
    
}
