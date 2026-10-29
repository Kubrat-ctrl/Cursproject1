<?php

namespace App\Services;

use App\Enums\ScheduleStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        return User::query()
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();
    }

    /** Назначение роли и блокировка учётной записи. */
    public function update(User $user, array $data): User
    {
        if (isset($data['role'])) {
            $newRole = UserRole::from($data['role']);

            $hasUpcoming = $user->scheduleItems()
                ->where('status', ScheduleStatus::Scheduled->value)
                ->where('starts_at', '>', now())
                ->exists();

            if ($user->role === UserRole::Trainer && $newRole !== UserRole::Trainer && $hasUpcoming) {
                abort(409, 'Нельзя снять роль тренера: у него есть предстоящие занятия.');
            }

            $user->role = $newRole;
        }

        if (array_key_exists('blocked', $data)) {
            $user->blocked_at = $data['blocked'] ? now() : null;
        }

        $user->save();

        return $user->refresh();
    }
}
