<?php

namespace App\Controllers\Admin;

use App\Models\AdminModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;

class AdminController extends ResourceController
{
    public function adminsignup()
    {
        $Model = new AdminModel();
        $count = $Model->countAll();
        if ($count >= 1) {
            $response = [
                "status" => 400,
                "error" => true,
                "admin already exist! You are not able to creat admin acount!"
            ];
        } else {
            $rules = [
                "admin_name" => "required",
                "email" => "required|valid_email|max_length[254]",
                "password" => "required",
                "confirmpassword" => "required"
            ];
            if (!$this->validate($rules)) {
                $response = [
                    "status" => 400,
                    "error" => true,
                    "message" => $this->validator->getErrors(),
                ];
            } else {
                $password = $this->request->getVar('password');
                $confirmpassword = $this->request->getVar('confirmpassword');

                if ($password === $confirmpassword) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $data = [
                        'admin_name' => $this->request->getVar('admin_name'),
                        'admin_email' => $this->request->getVar('email'),
                        'admin_password' => $hash
                    ];
                    $insert = $Model->insert($data);
                    if ($insert) {
                        $response = [
                            "status" => 200,
                            "error" => false,
                            "message" => "account has created!"
                        ];
                    } else {
                        $response = [
                            "status" => 400,
                            "error" => true,
                            "message" => "account has not created!"
                        ];
                    }
                } else {
                    $response = [
                        "status" => 400,
                        "error" => true,
                        "message" => "Password not matched!"
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
            $LoginModel = new AdminModel();
            $admin = $LoginModel->where('admin_email', $email)->first();
            if ($admin) {
                $hash = password_verify($password, $admin['admin_password']);
                if ($hash) {
                    $privateKey = getenv('PRIVATE_KEY');
                    $payload = [
                        'iss' => 'localhost',
                        'aud' => 'localhost',
                        'data' => [
                            'admin_id' => $admin['admin_id'],
                            'admin_emial' => $admin['admin_email'],
                        ]
                    ];
                    $jwt = JWT::encode($payload, $privateKey, 'HS256');
                    $response = [
                        "status" => 200,
                        'error' => false,
                        "message" => "You are logedIn successful",
                        "data" => [$admin, 'tokken' => $jwt]
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
            $Model = new AdminModel();
            $user = $Model->where('admin_email', $email)->first();
            if (!$user) {
                $response = [
                    "status" => "false",
                    "error " => "true",
                    "message" => "Account not found creat account first",
                ];
            } else {
                $otp = mt_rand(100000, 999999);
                ;

                $data = [
                    'reset_code' => $otp,
                    'reset_code_expire' => date('Y-m-d H:i:s', strtotime('+10 min')),
                ];
                $query = $Model->set($data)->where('admin_email', $email)->update();
                if ($query) {
                    $emailService = \Config\Services::email();
                    $emailService->setTo($user['admin_email']);
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
                } else {
                    $response = [
                        "status" => 400,
                        "error" => true,
                        "message" => "query not runs"
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
            $ResetModel = new AdminModel();

            $user = $ResetModel->where('reset_code', $token)->first();

            if (!$user) {
                $response = [

                    "status" => 400,
                    "error" => true,
                    "message" => "Otp not Matched!",

                ];
            } else {

                $query = $ResetModel->where('reset_code', $token)->set('admin_password', $hash)->update();
                if (!$query) {
                    $response = [
                        "status" => 400,
                        "error" => true,
                        "message" => "password not changed!",

                    ];

                } else {
                    $query = $ResetModel->where('reset_code', $token)->set('reset_code', null)->update();
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
