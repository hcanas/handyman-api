<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Gate;

class DepartmentController extends Controller
{
    public function index(): ResourceCollection
    {
        Gate::authorize('viewAny', Department::class);

        return DepartmentResource::collection(Department::all());
    }

    public function store(StoreDepartmentRequest $request): DepartmentResource
    {
        $department = Department::create($request->validated());

        return new DepartmentResource($department);
    }

    public function show(Department $department): DepartmentResource
    {
        Gate::authorize('view', $department);

        return new DepartmentResource($department);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): DepartmentResource
    {
        $department->fill($request->validated())->save();

        return new DepartmentResource($department);
    }

    public function destroy(Department $department): JsonResponse
    {
        Gate::authorize('delete', $department);

        if ($department->users()->exists()) {
            return response()->json([
                'message' => 'Cannot delete departments that have active users.',
            ], 409);
        }

        $department->delete();

        return response()->json([
            'message' => 'The department has been deleted',
        ]);
    }
}
