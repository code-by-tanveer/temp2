<?php
namespace App\Repositories;

use PDO;
use App\Exception\UsernameAlreadyExistsException;

class UserRepository
{
    protected $db;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    public function create(string $username, string $profileImage): array
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO users (username, profile_image) VALUES (:username, :profile_image)");
            $stmt->execute([':username' => $username, ':profile_image' => $profileImage]);
            $id = $this->db->lastInsertId();
            return $this->findById($id);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000' || $e->errorInfo[0] == '23000') { // Check for SQLSTATE 23000
                throw new UsernameAlreadyExistsException();
            }
            throw $e; // Re-throw other PDOExceptions
        }
    }
    
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }
}
