<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Jwt.php';

class EmailPasswordAuth
{
    private $db;

    public function __construct()
    {
        $config = new Config();
        $this->db = $this->getDBFromConfig($config);

        JWT::init(); // Initialize JWT secrets
    }

    private function getDBFromConfig($config)
    {
        $reflection = new ReflectionClass($config);
        $property = $reflection->getProperty('db');
        $property->setAccessible(true);
        return $property->getValue($config);
    }

    public function login($email, $password)
    {
        if (empty($email) || empty($password)) {
            http_response_code(401);
            return [
                'status' => false,
                'message' => 'Email and password are required'
            ];
        }

        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);

            return [
                'status' => false,
                'message' => 'Invalid email or password'
            ];
        }

        // JWT Payload
        $tokenPayload = [
            'sub'   => $user['id'],
            'id'    => $user['id'],
            'email' => $user['email'],
            'role'  => $user['role'] ?? 'user',
            'name'  => $user['name']
        ];

        $accessToken  = JWT::createAccessToken($tokenPayload);
        $refreshToken = JWT::createRefreshToken(['sub' => $user['id']]);

        return [
            'status' => true,
            'user' => [
                'id'      => $user['id'],
                'name'    => $user['name'],
                'email'   => $user['email'],
                'avatar'  => $user['avatar'],
                'role'    => $user['role'] ?? 'user'
            ],
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken
        ];
    }
}
