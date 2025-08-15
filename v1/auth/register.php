<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Jwt.php';

class EmailPasswordRegister
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

    public function register($name, $email, $password)
    {
        if (empty($name) || empty($email) || empty($password)) {
            return [
                'status' => false,
                'message' => 'Name, email, and password are required'
            ];
        }

        // Check if user already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            return [
                'status' => false,
                'message' => 'Email already registered'
            ];
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $username = explode('@', $email)[0];

        // Insert user
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, username, is_verified) VALUES (:name, :email, :password, :username, :is_verified)");
        $stmt->execute([
            'name'        => $name,
            'email'       => $email,
            'password'    => $hashedPassword,
            'username'    => $username,
            'is_verified' => true
        ]);

        // Get inserted user
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

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
