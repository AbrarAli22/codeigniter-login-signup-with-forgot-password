<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\Key;
use Firebase\JWT\JWT;

class UserprofileController extends ResourceController
{
    public function myprofile($userid)
    {
        $uId = $this->getuserid();
        if ($uId == $userid) {
            $Model=new UserModel();
            $userdata=$Model->where('user_id',$uId)->find();
            if($userdata){
                $response = [
                    "status" => 200,
                    "error" => false,
                    "message" => "Profile!",
                    "data"=>$userdata
                ];
            }else{
                $response = [
                    "status" => 400,
                    "error" => true,
                    "message" => "user is not registered!"
                ];
            }

        } else {
            $response = [
                "status" => 400,
                "error" => true,
                "message" => "Invalid tokken for this id!"
            ];
        }

        return $this->respondCreated($response);
    }
    public function getuserid()
    {
        $token = null;
        $authorizationHeader = $this->request->getHeader('Authorization');
        if (!empty($authorizationHeader) && preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            $token = $matches[1];
            $keyMaterial = getenv('PRIVATE_KEY');
            $algorithm = 'HS256';
            $decodedToken = JWT::decode($token, new Key($keyMaterial, $algorithm));
            $userId = null;
            $userId = $decodedToken->data->user_id;
        }
        return $userId;
    }
}
