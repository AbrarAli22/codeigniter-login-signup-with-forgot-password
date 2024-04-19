<?php

namespace App\Controllers\Admin;

use App\Models\AdminModel;
use App\Models\CatagorieModel;
use App\Models\ProductModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CatagoriesController extends ResourceController
{
    public function addCatagories($adminid)
    {
        $adminId = $this->getadminid();
        if ($adminId == $adminid) {
            $rules = [
                "catagorieName" => "required|trim",
                "catDescription" => "required|trim"
            ];
            $Model = new CatagorieModel();
            if (!$this->validate($rules)) {
                $response = [
                    "status" => 400,
                    "error" => true,
                    "message" => $this->validator->getErrors()
                ];
            } else {
                $catname = $this->request->getVar('catagorieName');
                $check = $Model->where('catagories_name', $catname)->findAll();
                if (count($check) > 0) {
                    $response = [
                        "status" => 400,
                        "error" => true,
                        "message" =>"catagories already exist!", 
                    ];
                } else {
                    $data = [
                        "catagories_name" => $this->request->getVar('catagorieName'),
                        "Description" => $this->request->getVar('catDescription')
                    ];
                    $insert = $Model->insert($data);
                    if ($insert) {
                        $response = [
                            "status" => 200,
                            "error" => false,
                            "message" => "catagories added successfully!",
                            "data" => $data
                        ];
                    } else {
                        $response = [
                            "status" => 200,
                            "error" => false,
                            "message" => "Warning catagories Not added successfully!",
                        ];
                    }
                }
            }
        } else {
            $response = [
                "status" => 400,
                "error" => true,
                "message" => "Only admin can add the catagories"
            ];
        }
        return $this->respondCreated($response);
    }
    public function deleteCatagories($catid){
        $Model=new AdminModel();
        $adminId=$this->getadminid();
        $getadmin=$Model->where('admin_id',$adminId)->find();
        if($getadmin){
            $catModel=new CatagorieModel();
            $cat=$catModel->where('catagories_id',$catid)->find();
            if($cat){
                $PModel=new ProductModel();
                $deleteProducts=$PModel->where('catagories_id',$catid)->delete();

            }else{
                $response = [
                    "status" => 400,
                    "error" => true,
                    "message" => "No catagorie Found"
                ];
            }
        }else{
            $response = [
                "status" => 400,
                "error" => true,
                "message" => "Only admin can delete the catagories"
            ];
        }
        return $this->respondCreated($response);
    }
    public function addProduct($adminid){
        $adminId=$this->getadminid();
        if($adminid==$adminId){
            $rules=[
                "productName"=>"required|trim","productDescription"=>"required","productImage"=>"uploaded[productImage]|max_size[productImage,1024]|is_image[productImage]","product_count"=>"required"
            ];
            if(!$this->validate($rules)){
                $response=[
                    "status"=>400,
                    "error"=>true,
                    "messgae"=>$this->validator->getErrors()
                ];
            }else{
                $Model=new ProductModel();
                $image=$this->request->getFile('productImage');
                $newName = $image->getRandomName();
                $data=[
                    "product_name"=>$this->request->getVar('productName'),
                    "product_description"=>$this->request->getVar('productDescription'),
                    "product_image"=>$newName,
                    "product_count"=>$this->request->getVar('product_count'),
                    "catagorie_id"=>$this->request->getVar('catagories_id')
                ];
                $insert=$Model->insert($data);
                if($insert){
                    $image->move(ROOTPATH . 'public/profiles', $newName);
                    $response=[
                        "status"=>200,
                        "error"=>false,
                        "message"=>"product Added successfuly!",
                        "data"=>$data
                    ];
                }else{
                    $response=[
                        "status"=>400,
                        "error"=>true,
                        "messgae"=>"product not Added successfuly!",
                    ];   
                }
            }
        }else{
            $response=[
                "status"=>400,
                "error"=>true,
                "messgae"=>"only admin can add product!"
            ];
        }
        return $this->respondCreated($response);
    }
    public function getadminid()
    {
        $token = null;
        $authorizationHeader = $this->request->getHeader('Authorization');
        if (!empty($authorizationHeader) && preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            $token = $matches[1];
            $keyMaterial = getenv('PRIVATE_KEY');
            $algorithm = 'HS256';
            $decodedToken = JWT::decode($token, new Key($keyMaterial, $algorithm));
            $adminId = null;
            $adminId = $decodedToken->data->admin_id;
        }
        return $adminId;
    }
}
