<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_card_requires_an_active_student(): void
    {
        $this->get(route('member-card'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'petugas']))
            ->get(route('member-card'))->assertForbidden();
        $this->actingAs(User::factory()->create(['is_active' => false]))
            ->get(route('member-card'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_student_only_sees_own_card_even_when_another_profile_is_requested(): void
    {
        $own = $this->member('Siswa Pemilik Kartu', 'ANG-SELF-001', 'VII A');
        $other = $this->member('Siswa Rahasia Lain', 'ANG-OTHER-002', 'IX C');

        $this->actingAs($own->user)->get(route('member-card', [
            'member_id' => $other->id, 'user_id' => $other->user_id,
        ]))->assertOk()->assertViewIs('members.card')
            ->assertViewHas('studentProfile', fn ($member) => $member->id === $own->id
                && $member->schoolClass->name === 'VII A')
            ->assertSee('Siswa Pemilik Kartu')->assertSee('ANG-SELF-001')->assertSee('VII A')
            ->assertSee(config('library.school_name'))->assertSee('Cetak / Simpan PDF')
            ->assertDontSee('Siswa Rahasia Lain')->assertDontSee('ANG-OTHER-002')->assertDontSee('IX C');
    }

    public function test_student_without_a_profile_gets_instructions_instead_of_a_fake_card(): void
    {
        $this->actingAs(User::factory()->create())->get(route('member-card'))->assertOk()
            ->assertViewHas('studentProfile', null)
            ->assertSee('Profil anggota kamu belum tersedia. Hubungi petugas perpustakaan')
            ->assertDontSee('class="student-member-card"', false)
            ->assertDontSee('data-print-member-card', false);
    }

    public function test_profile_without_a_class_has_an_explicit_placeholder(): void
    {
        $user = User::factory()->create();
        Member::create(['user_id' => $user->id, 'member_code' => 'ANG-NO-CLASS']);
        $this->actingAs($user)->get(route('member-card'))->assertOk()
            ->assertSee('ANG-NO-CLASS')->assertSee('Belum dicantumkan');
    }

    public function test_member_name_and_code_are_escaped_on_the_card(): void
    {
        $name = '<script>alert("name")</script>';
        $code = '<b>ANG-XSS</b>';
        $member = $this->member($name, $code, 'VIII B');
        $this->actingAs($member->user)->get(route('member-card'))->assertOk()
            ->assertSee($name)->assertSee($code)
            ->assertDontSee($name, false)->assertDontSee($code, false);
    }

    private function member(string $name, string $code, string $className): Member
    {
        return Member::create([
            'user_id' => User::factory()->create(['name' => $name])->id,
            'member_code' => $code,
            'school_class_id' => SchoolClass::create(['name' => $className, 'grade_level' => '7'])->id,
        ]);
    }
}
