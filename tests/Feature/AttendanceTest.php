<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_check_in_and_out_once_per_shift(): void
    {
        $this->travelTo(Carbon::parse('2026-10-25 08:00:00', 'Asia/Manila'));
        $staff = $this->createUser('staff');
        $this->actingAs($staff);

        $this->post(route('attendance.check-in'))->assertRedirect();
        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $staff->id,
            'checked_out_at' => null,
        ]);

        $this->post(route('attendance.check-in'))
            ->assertSessionHas('error', 'Your time in is already recorded. Please record your time out first.');
        $this->assertDatabaseCount('attendance_records', 1);

        $this->post(route('attendance.check-out'))->assertRedirect();
        $this->assertSame(1, AttendanceRecord::query()
            ->where('user_id', $staff->id)
            ->whereNotNull('checked_out_at')
            ->count());
    }

    public function test_staff_cannot_check_in_at_nine_and_is_marked_absent(): void
    {
        $this->travelTo(Carbon::parse('2026-10-25 09:00:00', 'Asia/Manila'));
        $staff = $this->createUser('staff');
        $this->actingAs($staff);

        $this->post(route('attendance.check-in'))
            ->assertRedirect()
            ->assertSessionHas('error', 'Time in is closed from 9:00 AM. You are marked absent for today.');

        $this->assertDatabaseCount('attendance_records', 0);
        $this->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('Absent today - time in closed at 9:00 AM')
            ->assertDontSee('Time in</button>');
    }

    public function test_staff_can_only_view_their_own_attendance_report(): void
    {
        $staff = $this->createUser('staff');
        $otherStaff = $this->createUser('staff');
        $staff->attendanceRecords()->create(['checked_in_at' => now()]);
        $otherStaff->attendanceRecords()->create(['checked_in_at' => now()]);

        $response = $this->actingAs($staff)->get(route('attendance.index', ['user_id' => $otherStaff->id]));

        $response->assertOk()
            ->assertViewHas('records', fn ($records) => $records->total() === 1
                && $records->first()->user_id === $staff->id);
    }

    public function test_manager_can_view_staff_attendance_report(): void
    {
        $manager = $this->createUser('manager');
        $staff = $this->createUser('staff');
        $staff->attendanceRecords()->create(['checked_in_at' => now()]);

        $this->actingAs($manager)
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee($staff->name);
    }

    public function test_attendance_flags_late_arrivals_and_early_departures_at_schedule_boundaries(): void
    {
        $staff = $this->createUser('staff');
        $recordAt815 = $staff->attendanceRecords()->create([
            'checked_in_at' => Carbon::parse('2026-10-25 08:15:00', 'Asia/Manila')->utc(),
            'checked_out_at' => Carbon::parse('2026-10-25 17:00:00', 'Asia/Manila')->utc(),
        ]);
        $recordAt814 = $staff->attendanceRecords()->create([
            'checked_in_at' => Carbon::parse('2026-10-25 08:14:59', 'Asia/Manila')->utc(),
            'checked_out_at' => Carbon::parse('2026-10-25 18:00:00', 'Asia/Manila')->utc(),
        ]);

        $this->assertTrue($recordAt815->isLate());
        $this->assertTrue($recordAt815->isEarlyOut());
        $this->assertFalse($recordAt814->isLate());
        $this->assertFalse($recordAt814->isEarlyOut());
    }

    public function test_staff_with_temporary_password_is_taken_to_the_password_form(): void
    {
        $staff = $this->createUser('staff');
        $staff->forceFill(['must_change_password' => true])->save();

        $this->actingAs($staff)
            ->get(route('attendance.index'))
            ->assertRedirect(route('profile.show').'#password')
            ->assertSessionHas('warning');
    }

    public function test_staff_can_change_temporary_password_without_entering_it_again(): void
    {
        $staff = $this->createUser('staff');
        $staff->forceFill(['must_change_password' => true])->save();

        $this->actingAs($staff)
            ->put(route('profile.password'), [
                'password' => 'FreshStaff2026!',
                'password_confirmation' => 'FreshStaff2026!',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($staff->fresh()->must_change_password);
        $this->assertTrue(password_verify('FreshStaff2026!', $staff->fresh()->password));
    }

    private function createUser(string $role): User
    {
        return User::factory()->create([
            'name' => ucfirst($role).' '.fake()->unique()->firstName(),
            'username' => fake()->unique()->userName(),
            'role' => $role,
            'is_active' => true,
            'must_change_password' => false,
        ]);
    }
}
