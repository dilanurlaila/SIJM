<?php
session_start();
require_once 'config/Database.php';
require_once 'models/UserModel.php';

class AuthController
{
    private $userModel;

    public function __construct()
    {
        $database = new Database();
        $db = $database->getConnection();
        $this->userModel = new UserModel($db);
    }

    public function login($username, $password)
    {
        $user = $this->userModel->getUser($username, $password);

        if ($user) {
            // Set session jika berhasil login
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
        return false;
    }

    public function checkAuth()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit;
        }
    }

    public function logout()
    {
        session_destroy();
        header("Location: login.php");
        exit;
    }
}
