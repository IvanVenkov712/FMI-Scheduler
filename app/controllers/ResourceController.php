<?php

declare(strict_types=1);

class ResourceController extends CrudController
{
    protected string $table = 'resources';
    protected string $viewPath = 'resources';
    protected string $entityName = 'resource';
    protected array $fillable = ['name', 'slug', 'description', 'unit', 'is_consumable'];
    protected array $required = ['name', 'slug'];
    protected array $booleanFields = ['is_consumable'];
    protected string $defaultOrder = 'name ASC';

    public function manage(int|string|null $roomId = null): mixed
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return $this->respond('resources/manage', [
                'room_id' => $roomId,
                'resources' => $this->database()->fetchAll('SELECT * FROM resources ORDER BY name ASC'),
                'room_resources' => $roomId === null ? [] : $this->roomResources($roomId),
            ]);
        }

        $data = $this->payload();
        $errors = $this->requireFields($data, ['room_id', 'resource_id', 'quantity']);

        if ($errors !== []) {
            return $this->validationResponse($errors, $data, 'resources/manage');
        }

        $this->database()->insert(
            'INSERT INTO room_resources (room_id, resource_id, quantity, status, notes)
             VALUES (:room_id, :resource_id, :quantity, :status, :notes)
             ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), status = VALUES(status), notes = VALUES(notes)',
            [
                'room_id' => $data['room_id'],
                'resource_id' => $data['resource_id'],
                'quantity' => (int) $data['quantity'],
                'status' => $data['status'] ?? 'available',
                'notes' => $data['notes'] ?? null,
            ]
        );

        return $this->wantsJson()
            ? ['message' => 'Room resource saved.']
            : $this->redirect('/resources/manage');
    }

    public function roomResources(int|string $roomId): array
    {
        return $this->database()->fetchAll(
            'SELECT room_resources.*, resources.name, resources.slug, resources.unit
             FROM room_resources
             INNER JOIN resources ON resources.id = room_resources.resource_id
             WHERE room_resources.room_id = :room_id
             ORDER BY resources.name ASC',
            ['room_id' => $roomId]
        );
    }
}
