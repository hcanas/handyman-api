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
use Knuckles\Scribe\Attributes\Group;

#[Group('Department Management')]
class DepartmentController extends Controller
{
    /**
     * List Departments
     *
     * Only admins can view list of departments.
     */
    public function index(): ResourceCollection
    {
        Gate::authorize('viewAny', Department::class);

        return DepartmentResource::collection(Department::all());
    }

    /**
     * Create Department
     *
     * Only admins can create departments.
     */
    public function store(StoreDepartmentRequest $request): DepartmentResource
    {
        $department = Department::create($request->validated());

        return new DepartmentResource($department);
    }

    /**
     * Show Department Details
     *
     * Only admins can view department details.
     */
    public function show(Department $department): DepartmentResource
    {
        Gate::authorize('view', $department);

        return new DepartmentResource($department);
    }

    /**
     * Update Department
     *
     * Only admins can update department.
     */
    public function update(UpdateDepartmentRequest $request, Department $department): DepartmentResource
    {
        $department->fill($request->validated())->save();

        return new DepartmentResource($department);
    }

    /**
     * Delete Department
     *
     * Departments with active users cannot be deleted.
     *
     * Only admins can delete departments.
     */
    public function destroy(Department $department): JsonResponse
    {
        Gate::authorize('delete', $department);

        if ($department->staff()->exists()) {
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
