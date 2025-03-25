<?php
namespace App\Repositories;

use PDO;

class MessageRepository
{
    protected $db;
    protected $pageSize = 20; // example page size
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }
    
    public function create(int $groupId, int $userId, string $content, ?int $replyTo = null, ?string $replyPreview = null): array
    {
        $stmt = $this->db->prepare("INSERT INTO messages (group_id, user_id, content, reply_to, reply_preview) VALUES (:group_id, :user_id, :content, :reply_to, :reply_preview)");
        $stmt->execute([
            ':group_id' => $groupId,
            ':user_id' => $userId,
            ':content' => $content,
            ':reply_to' => $replyTo,
            ':reply_preview' => $replyPreview
        ]);
        $id = $this->db->lastInsertId();
        return $this->findById($id);
    }
    
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM messages WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $msg = $stmt->fetch(PDO::FETCH_ASSOC);
        return $msg ?: null;
    }
    
    public function listByGroup(int $groupId, ?string $paginationToken = null): array
    {
        $lastId = $paginationToken ? (int) base64_decode($paginationToken) : 0;
        $sql = "SELECT * FROM messages WHERE group_id = :group_id";
        $params = [':group_id' => $groupId];
        if ($lastId) {
            $sql .= " AND id > :lastId";
            $params[':lastId'] = $lastId;
        }
        $sql .= " ORDER BY id ASC LIMIT " . $this->pageSize;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create next/prev tokens (simple example)
        $hasNext = count($messages) === $this->pageSize;
        $hasPrev = $lastId > 0;
        $nextToken = $hasNext ? base64_encode(end($messages)['id']) : null;
        $prevToken = $hasPrev ? base64_encode($messages[0]['id']) : null;
        
        return [
            'messages' => $messages,
            'pagination' => [
                'hasNext' => $hasNext,
                'hasPrev' => $hasPrev,
                'nextToken' => $nextToken,
                'prevToken' => $prevToken
            ]
        ];
    }
}
