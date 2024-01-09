<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class UserController extends ResourceController
{
    public function signup()
    {
        // $this->load->library('password');
        $UserObject = new UserModel();
        $rules = [
            'username' => 'required|trim',
            'first_name' => 'required|trim',
            'last_name' => 'required|trim',
            'email' => 'required|valid_email|max_length[254]',
            'phone' => 'required|trim|regex_match[/^[0-9]{11}$/]',
            'password' => 'required|trim|min_length[6]|max_length[32]',
            "Address" => "required",
            "City" => "required",
            "State" => "required",
            "Zip/postalcode" => "required",
            "Country" => "required",
        ];
        if (!$this->validate($rules)) {
            $response = [
                "status" => 400,
                "error" => true,
                "message" => $this->validator->getErrors(),
            ];
        } else {
            $email = $this->request->getVar("email");
            $user = $UserObject->where('email', $email)->first();

            if ($user) {
                $response = [
                    "status" => false,
                    "error" => true,
                    "message" => "Email is allready exist add another email",
                ];
            } else {
                $password = $this->request->getVar("password");
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $data = [
                    "username" => $this->request->getVar("username"),
                    "first_name" => $this->request->getVar("first_name"),
                    "last_name" => $this->request->getVar("last_name"),
                    "email" => $this->request->getVar("email"),
                    "password" => $hash,
                    "address" => $this->request->getVar("Address"),
                    "city" => $this->request->getVar("City"),
                    "state" => $this->request->getVar("State"),
                    "zipcode" => $this->request->getVar("Zip/postalcode"),
                    "country" => $this->request->getVar("Country"),
                    "phone" => $this->request->getVar("phone"),
                    "created_at" => date('Y-m-d H:i:s'),
                ];
                if ($UserObject->insert($data)) {
                    $response = [
                        "status" => 200,
                        "error" => false,
                        "message" => "your account has been created successfully!",
                        "data" => [$data]
                    ];
                } else {
                    $response = [
                        "status" => 400,
                        "error" => true,
                        "mesage" => "user is not added due to some problem",
                    ];

                }
            }
        }

        return $this->respondCreated($response);
    }
    public function login()
    {
        $rules = array(
            "email " => "required|valid_email|trim",
            "password" => "required|trim"
        );

        if (!$this->validate($rules)) {
            $response = [
                "status" => 400,
                "error" => true,
                "message" => $this->validator->getErrors(),
            ];
        } else {
            $email = $this->request->getVar("email");
            $password = $this->request->getVar("password");

            $LoginModel = new UserModel();
            $user = $LoginModel->where('email', $email)->first();
            if ($user) {
                $hash = password_verify($password, $user['password']);
                if ($hash) {
                    $privateKey = getenv('PRIVATE_KEY');
                    $payload = [
                        'iss' => 'localhost',
                        'aud' => 'localhost',
                        'data' => [
                            'user_id' => $user['user_id'],
                            'email' => $user['email'],
                        ]
                    ];
                    $jwt = JWT::encode($payload, $privateKey, 'HS256');
                    $response = [
                        "status" => 200,
                        'error' => false,
                        "message" => "You are logedIn successful",
                        "data" => [$user, 'tokken' => $jwt]
                    ];
                    $data = [
                        'jwt_token' => $jwt,
                    ];

                } else {
                    $response = [
                        "status" => 400,
                        'error' => true,
                        "message" => "Login failed. Incorrect email or password",
                    ];
                }
            } else {
                $response = [
                    "status" => 400,
                    "error" => true,
                    "message" => "Email not found creat an account"
                ];
            }
        }
        return $this->respondCreated($response);
    }
    public function forgotPassword()
    {
        $rule = [
            "email" => "required|trim",
        ];
        if (!$this->validate($rule)) {
            $response = [
                "status" => false,
                "error" => true,
                "message" => "enter your email for password reset"
            ];
        } else {
            $email = $this->request->getVar('email');
            $Model = new UserModel();
            $user = $Model->where('email', $email)->first();
            if (!$user) {
                $response = [
                    "status" => "false",
                    "error " => "true",
                    "message" => "Account not found creat account first",
                ];
            } else {
                $otp = mt_rand(100000, 999999);;
                
                $data = [
                    'otp' => $otp,
                    'otp_expire_time' => date('Y-m-d H:i:s', strtotime('+10 min')),
                ];
                $query = $Model->set($data)->where('email',$email)->update();         
                if($query){
                $emailService = \Config\Services::email();
                $emailService->setTo($user['email']);
                $emailService->setSubject('Password Reset');
                $emailService->setMessage("Click the following link to reset your password: $otp");
                if (!$emailService->send()) {
                    $response = [
                        "status" => 400,
                        "error" => true,
                        "message" => $emailService->printDebugger(),

                    ];
                } else {
                    $response = [
                        "status" => 200,
                        "error" => false,
                        "message" => "Tokken is sent to" . $email . " successfully.",
                        "data" => [
                            "tokken" => $otp
                        ],
                    ];

                }
            }else{
                $response=[
                    "status"=>400,
                    "error"=>true,
                    "message"=>"query not runs"
                ];
            }
            }
        }
        return $this->respondCreated($response);
    }
    public function resetpassword()
    {
        $token = $this->request->getVar("otp");
        $password = $this->request->getVar("password");
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $rules = [
            "otp" => "required|trim",
            'password' => 'required|min_length[6]|max_length[255]',
        ];

        if (!$this->validate($rules)) {
            $response = [
                "status" => false,
                "message" => $this->validator->getErrors(),
            ];
        } else {
            $ResetModel = new UserModel();

            $user = $ResetModel->where('otp', $token)->first();

            if (!$user) {
                $response = [

                    "status" => 400,
                    "error" => true,
                    "message" => "Otp not Matched!",

                ];
            } else {

                $query = $ResetModel->where('otp', $token)->set('password', $hash)->update();
                if (!$query) {
                    $response = [
                        "status" => 400,
                        "error" => true,
                        "message" => "password not changed!",

                    ];

                } else {
                    $query = $ResetModel->where('otp', $token)->set('otp', null)->update();
                    if ($query) {
                        $response = [
                            "status" => 200,
                            "error" => false,
                            "message" => "Pasword is updated",
                        ];

                    } else {
                        $response = [
                            "status" => 400,
                            "error" => true,
                            "message" => "Pasword is not updated .",
                        ];
                    }
                }
            }
        }
        return $this->respondCreated($response);
    }

}
