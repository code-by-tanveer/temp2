<?php
namespace App\Repositories;

use PDO;

class GroupRepository
{
    protected $db;
    protected $pageSize = 2;
    
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // public function getAllGroups(?string $paginationToken = null): array
    // {
    //     $sql = "SELECT * FROM groups";
    //     $params = [];
    //     if ($lastId) {
    //         $sql .= " WHERE id < :lastId"; // Changed to '<' for descending order pagination
    //         $params[':lastId'] = $lastId;
    //     }
    //     $sql .= " ORDER BY id ASC LIMIT " . $this->pageSize; // Order by ID DESC for next tokens to work correctly
    //     $stmt = $this->db->prepare($sql);
    //     $stmt->execute($params);
    //     $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //     // Create next/prev tokens (simple example)
    //     $hasNext = count($groups) === $this->pageSize;
    //     $hasPrev = $lastId > 0;
    //     $nextToken = $hasNext ? base64_encode(end($groups)['id']) : null;
    //     $prevToken = $hasPrev && isset($groups[0]) ? base64_encode($groups[0]['id']) : null; // Prev token from first item on current page

    //     return [
    //         'groups' => $groups,
    //         'pagination' => [
    //             'hasNext' => $hasNext,
    //             'hasPrev' => $hasPrev,
    //             'nextToken' => $nextToken,
    //             'prevToken' => $prevToken
    //         ]
    //     ];
    // }

    public function getAllGroups(?string $paginationToken = null, string $paginationDirection = 'next', ?string $search = null): array // Added $search parameter
    {
        $tokenId = $paginationToken ? base64_decode($paginationToken) : null;
        $sql = "SELECT * FROM groups";
        $params = [];
        $orderBy = "ASC";
        $comparisonOperator = ">";

        if ($paginationDirection === 'before') {
            $orderBy = "DESC";
            $comparisonOperator = "<";
        }

        $whereClauses = []; // Array to hold WHERE clauses

        if ($tokenId) {
            $whereClauses[] = "id " . $comparisonOperator . " :tokenId";
            $params[':tokenId'] = $tokenId;
        }
        if ($search) { // Add search condition if search term is provided
            $whereClauses[] = "name LIKE :search";
            $params[':search'] = '%' . $search . '%'; // Use LIKE for partial match, add wildcards
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses); // Combine WHERE clauses with AND
        }

        $sql .= " ORDER BY id " . $orderBy . " LIMIT " . ($this->pageSize + 1);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $groupsWithExtra = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $groups = array_slice($groupsWithExtra, 0, $this->pageSize);

        $hasNext = false;
        $hasPrev = false;
        $nextToken = null;
        $prevToken = null;

        if ($paginationDirection === 'next') {
            $hasNext = count($groupsWithExtra) > $this->pageSize;
            $nextToken = $hasNext && !empty($groups) ? base64_encode(end($groups)['id']) : null;
            $hasPrev = $paginationToken !== null;
            $prevToken = $hasPrev && !empty($groups) ? base64_encode($groups[0]['id']) : null;
        } elseif ($paginationDirection === 'before') {
            $hasNext = $paginationToken !== null;
            $hasPrev = count($groupsWithExtra) > $this->pageSize;
            $nextToken = $hasNext && !empty($groups) ? base64_encode($groups[0]['id']) : null;
            $prevToken = $hasPrev && !empty($groups) ? base64_encode(end($groups)['id']) : null;
        }

        return [
            'groups' => $groups,
            'pagination' => [
                'hasNext' => $hasNext,
                'hasPrev' => $hasPrev,
                'nextToken' => $nextToken,
                'prevToken' => $prevToken
            ]
        ];
    }

    
    public function create(string $name, int $adminUserId, string $groupImage): array
    {
        $stmt = $this->db->prepare("INSERT INTO groups (name, admin_user_id, group_image) VALUES (:name, :admin_user_id, :group_image)");
        $stmt->execute([
            ':name' => $name,
            ':admin_user_id' => $adminUserId,
            ':group_image' => $groupImage
        ]);
        $id = $this->db->lastInsertId();
        return $this->findById($id);
    }
    
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM groups WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        return $group ?: null;
    }
    
    public function join(int $groupId, int $userId): void
    {
        $stmt = $this->db->prepare("INSERT INTO group_members (group_id, user_id) VALUES (:group_id, :user_id)");
        $stmt->execute([':group_id' => $groupId, ':user_id' => $userId]);
    }
}
