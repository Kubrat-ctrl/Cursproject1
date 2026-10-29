<?php

namespace App\Services;

use App\Models\MembershipType;
use Illuminate\Pagination\LengthAwarePaginator;

/** Логика работы с типами абонементов. */
class MembershipTypeService
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return MembershipType::query()
            ->when(isset($filters['is_active']), fn ($query) => $query->where(
                'is_active',
                filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN)
            ))
            ->orderBy('price')
            ->paginate($perPage);
    }

    public function create(array $data): MembershipType
    {
        $data['is_active'] ??= true;

        return MembershipType::create($data)->refresh();
    }

    public function update(MembershipType $type, array $data): MembershipType
    {
        $type->update($data);

        return $type->refresh();
    }

    public function delete(MembershipType $type): void
    {
        // Тип, по которому продавались абонементы, удалять нельзя (restrictOnDelete, ПР3):
        // его снимают с продажи через is_active = false.
        if ($type->memberships()->exists()) {
            abort(409, 'Нельзя удалить тип, по которому продавались абонементы. Снимите его с продажи: is_active = false.');
        }

        $type->delete();
    }
}
