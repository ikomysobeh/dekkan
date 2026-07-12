<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;

class UserApiController extends Controller
{
    use ApiResponses;

    /** GET /api/users — paginated list. */
    public function index()
    {
        $users = User::latest()->paginate(10);
        return $this->paginated($users, UserResource::collection($users));
    }

    /** GET /api/users/{id}. */
    public function show(int $id)
    {
        $user = User::find($id);
        if (! $user) {
            return $this->message('المستخدم غير موجود', 404, false);
        }
        return $this->item(new UserResource($user));
    }

    /** POST /api/users — create. Password is hashed by the model cast. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|max:255',
            'role'     => 'nullable|string|max:50',
        ]);

        $user = User::create($data); // 'password' => 'hashed' cast hashes it

        return $this->item(new UserResource($user), 201);
    }

    /**
     * PUT /api/users/{id} — update.
     * B2 fix: email unique ignores the current user, so saving without changing
     * the email no longer fails. Password only changes if provided.
     */
    public function update(Request $request, int $id)
    {
        $user = User::find($id);
        if (! $user) {
            return $this->message('المستخدم غير موجود', 404, false);
        }

        $data = $request->validate([
            'name'     => 'sometimes|required|string|max:255',
            'email'    => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6|max:255',
            'role'     => 'nullable|string|max:50',
        ]);

        // Don't overwrite the password with null when it isn't being changed.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return $this->item(new UserResource($user));
    }

    /** DELETE /api/users/{id}. */
    public function destroy(Request $request, int $id)
    {
        $user = User::find($id);
        if (! $user) {
            return $this->message('المستخدم غير موجود', 404, false);
        }

        // Guard: don't let a user delete their own account via the API.
        if ($request->user()->id === $user->id) {
            return $this->message('لا يمكنك حذف حسابك الحالي', 422, false);
        }

        $user->delete();
        return $this->message('تم الحذف بنجاح');
    }
}
