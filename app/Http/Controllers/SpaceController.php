<?php

namespace App\Http\Controllers;

use App\Models\Space;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SpaceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $spaces = Space::withCount(['notes'])
            ->with(['users' => function ($q) {
                $q->select('users.id', 'name', 'email')->withPivot('role');
            }])
            ->get()
            ->map(function ($space) use ($user) {
                $role = $user->roleInSpace($space);
                return [
                    'id' => $space->id,
                    'name' => $space->name,
                    'slug' => $space->slug,
                    'description' => $space->description,
                    'icon' => $space->icon,
                    'notes_count' => $space->notes_count,
                    'user_role' => $role?->value,
                    'user_role_label' => $role?->label(),
                    'members' => $space->users,
                ];
            });

        return Inertia::render('Spaces/Index', [
            'spaces' => $spaces,
        ]);
    }

    public function show(Space $space, Request $request): Response
    {
        $user = $request->user();
        $userRole = $user->roleInSpace($space);

        $notes = $space->notes()
            ->with(['owner', 'reviewer'])
            ->latest('updated_at')
            ->paginate(15);

        return Inertia::render('Spaces/Show', [
            'space' => $space,
            'notes' => $notes,
            'userRole' => $userRole?->value,
            'userRoleLabel' => $userRole?->label(),
            'members' => $space->users()->withPivot('role')->get(),
        ]);
    }
}
