<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CheckInRequest;
use App\Http\Requests\CheckOutRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends ApiController
{
    /**
     * Get all attendance records with filters.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $query = Attendance::query()->with('user');

        $user = $request->user();

        // Non-admin users can only see their own attendance
        if ($user && strtolower((string) $user->role) !== 'admin') {
            $query->where('user_id', $user->id);
        }

        // Apply filters
        if ($startDate = $request->get('start_date')) {
            $query->byDateRange($startDate, null);
        }

        if ($endDate = $request->get('end_date')) {
            $query->byDateRange(null, $endDate);
        }

        if ($scope = $request->get('scope')) {
            $scopeId = $request->get('scope_id');
            $query->forScope($scope, $scopeId);
        }

        if ($userId = $request->get('user_id')) {
            // Only admins can view other users' attendance
            if ($user && strtolower((string) $user->role) === 'admin') {
                $query->forUser($userId);
            }
        }

        $attendances = $query->latest('check_in_time')->paginate($perPage);

        return $this->success(
            AttendanceResource::collection($attendances),
            'Attendance records retrieved'
        );
    }

    /**
     * Check in a user.
     */
    public function checkIn(CheckInRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();

        // Check if user already has an active check-in
        $activeAttendance = Attendance::forUser($user->id)->active()->first();
        if ($activeAttendance) {
            return $this->error('User already has an active check-in', 409);
        }

        try {
            $attendance = DB::transaction(function () use ($data, $user) {
                $scopeName = null;
                $scopeId = $data['scope_id'] ?? null;

                // Get scope name based on scope type
                if ($data['scope'] === 'project' && $scopeId) {
                    $project = \App\Models\Project::find($scopeId);
                    $scopeName = $project?->name;
                } elseif ($data['scope'] === 'team' && $scopeId) {
                    // If needed, you can fetch team name from a team model
                    $scopeName = $scopeId;
                }

                // Optimize photo before storing
                $optimizedPhoto = ImageOptimizationService::compressBase64($data['photo']);

                return Attendance::create([
                    'user_id' => $user->id,
                    'scope' => $data['scope'],
                    'scope_id' => $scopeId,
                    'scope_name' => $scopeName,
                    'check_in_time' => now(),
                    'check_in_photo' => $optimizedPhoto,
                ]);
            });

            return $this->success(
                new AttendanceResource($attendance->load('user')),
                'Checked in successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error('Failed to check in: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check out a user.
     */
    public function checkOut(CheckOutRequest $request, string $attendanceId)
    {
        $user = $request->user();
        $data = $request->validated();

        $attendance = Attendance::find($attendanceId);

        if (!$attendance) {
            return $this->error('Attendance record not found', 404);
        }

        // Check authorization: user can only check out their own attendance
        if ($attendance->user_id !== $user->id && strtolower((string) $user->role) !== 'admin') {
            return $this->error('Unauthorized to check out this attendance', 403);
        }

        // Prevent double check-out
        if ($attendance->check_out_time) {
            return $this->error('This attendance record is already checked out', 409);
        }

        try {
            $attendance = DB::transaction(function () use ($attendance, $data) {
                // Optimize photo before storing
                $optimizedPhoto = ImageOptimizationService::compressBase64($data['photo']);

                $attendance->update([
                    'check_out_time' => now(),
                    'check_out_photo' => $optimizedPhoto,
                ]);

                // Calculate duration
                $attendance->calculateDuration();

                return $attendance;
            });

            return $this->success(
                new AttendanceResource($attendance->load('user')),
                'Checked out successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to check out: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get the current user's active attendance (if any).
     */
    public function getActive(Request $request)
    {
        $user = $request->user();
        $attendance = Attendance::forUser($user->id)->active()->with('user')->first();

        if (!$attendance) {
            return $this->success([
                'isCheckedIn' => false,
            ]);
        }

        // Calculate time elapsed in seconds
        $timeElapsed = $attendance->check_in_time->diffInSeconds(now());

        return $this->success([
            'isCheckedIn' => true,
            'attendance' => new AttendanceResource($attendance),
            'checkInTime' => $attendance->check_in_time->toDateTimeString(),
            'timeElapsed' => $timeElapsed,
        ]);
    }

    /**
     * Get a specific attendance record.
     */
    public function show(Request $request, Attendance $attendance)
    {
        $user = $request->user();

        // Check authorization: user can only view their own attendance
        if ($attendance->user_id !== $user->id && strtolower((string) $user->role) !== 'admin') {
            return $this->error('Unauthorized to view this attendance', 403);
        }

        $attendance->load('user');

        return $this->success(
            new AttendanceResource($attendance),
            'Attendance record retrieved'
        );
    }
}
