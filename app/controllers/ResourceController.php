<?php

declare(strict_types=1);

class ResourceController extends CrudController
{
    public function __construct(
        array $request = [],
        private readonly ResourceService $resourcesService = new ResourceService()
    ) {
        parent::__construct($request);
    }

    protected string $table = 'resources';
    protected string $viewPath = 'resources';
    protected string $entityName = 'resource';
    protected array $fillable = ['name', 'slug', 'description', 'unit', 'is_consumable'];
    protected array $required = ['name', 'slug'];
    protected array $booleanFields = ['is_consumable'];
    protected string $defaultOrder = 'name ASC';

    public function store(): mixed
    {
        $data = $this->payload();
        $result = $this->resourcesService->create($this->normalize($data), $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/create');
        }

        /** @var Resource|null $resource */
        $resource = $result['resource'] ?? null;

        return $this->storedResponse($resource?->toArray() ?? []);
    }

    public function update(int|string $id): mixed
    {
        $data = $this->payload();
        $result = $this->resourcesService->update($id, $this->normalize($data), $this->currentUserId());

        if (!($result['success'] ?? false)) {
            return $this->validationResponse((array) ($result['errors'] ?? []), $data, $this->viewPath . '/edit', [
                $this->entityName => array_merge($this->findOrFail($id), $data),
            ]);
        }

        /** @var Resource|null $resource */
        $resource = $result['resource'] ?? null;

        return $this->updatedResponse($resource?->toArray() ?? $this->findOrFail($id));
    }

    public function delete(int|string $id): mixed
    {
        $deleted = $this->resourcesService->delete($id, $this->currentUserId());

        if (!$deleted) {
            http_response_code(404);
            throw new RuntimeException('Resource not found.');
        }

        return $this->deletedResponse();
    }

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

        $this->resourcesService->assignToRoom(
            (int) $data['room_id'],
            (int) $data['resource_id'],
            (int) $data['quantity'],
            (string) ($data['status'] ?? 'available'),
            isset($data['notes']) ? (string) $data['notes'] : null
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
