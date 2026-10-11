<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use App\Support\LibraryBarcode;
use App\Support\StudentLoginQr;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class StudentQrLoginTest extends TestCase
{
    use RefreshDatabase;

    private function student(): Member
    {
        return Member::create(['user_id' => User::factory()->create(['role' => 'anggota'])->id, 'member_code' => 'M-'.bin2hex(random_bytes(4))]);
    }

    public function test_random_qr_logs_in_without_password_and_is_encrypted_and_hidden(): void
    {
        $member = StudentLoginQr::issue($this->student());
        $token = $member->login_qr_token;
        $this->assertMatchesRegularExpression('/^RPL1:[A-F0-9]{64}$/', $token);
        $this->assertNotSame($token, $member->getRawOriginal('login_qr_token'));
        $this->assertArrayNotHasKey('login_qr_token', $member->toArray());
        $this->assertArrayNotHasKey('login_qr_hash', $member->toArray());
        $this->postJson(route('login.qr'), ['student_qr' => $token, 'role' => 'petugas', 'user_id' => 999])
            ->assertOk()->assertJsonPath('redirect', route('dashboard'))->assertSessionHas('student_login_qr_hash', hash('sha256', $token));
        $this->assertAuthenticatedAs($member->user);
        $this->get('/dashboard')->assertOk();
        $this->get('/members')->assertForbidden();
    }

    public function test_public_id_legacy_code_and_invalid_payloads_never_authenticate(): void
    {
        $member = StudentLoginQr::issue($this->student());
        foreach ([LibraryBarcode::member($member), $member->member_code, 'RPC00000001', 'RPL1:'.str_repeat('0', 64), 'https://example.test', ['bad'], null, str_repeat('x', 101)] as $code) {
            $this->postJson(route('login.qr'), ['student_qr' => $code])->assertUnprocessable();
            $this->assertGuest();
        }
    }

    public function test_inactive_staff_and_deleted_students_cannot_use_qr(): void
    {
        $member = StudentLoginQr::issue($this->student());
        $token = $member->login_qr_token;
        $member->user->forceFill(['is_active' => false])->save();
        $this->postJson(route('login.qr'), ['student_qr' => $token])->assertUnprocessable();
        $member->user->forceFill(['is_active' => true, 'role' => 'petugas'])->save();
        $this->postJson(route('login.qr'), ['student_qr' => $token])->assertUnprocessable();
        $member->delete();
        $this->postJson(route('login.qr'), ['student_qr' => $token])->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_staff_can_issue_replace_revoke_and_old_sessions_expire(): void
    {
        $member = $this->student();
        $staff = User::factory()->create(['role' => 'petugas']);
        $url = route('members.login-qr', $member);
        $this->actingAs($staff)->from(route('members.card', $member))->post($url, ['action'=>'issue'])->assertRedirect();
        $token = $member->fresh()->login_qr_token;
        $this->post($url, ['action'=>'issue'])->assertRedirect();
        $this->assertSame($token, $member->fresh()->login_qr_token);
        $this->post($url, ['action'=>'replace'])->assertRedirect();
        $new = $member->fresh()->login_qr_token;
        $this->assertNotSame($token, $new);
        Auth::logout();
        $this->postJson(route('login.qr'), ['student_qr'=>$token])->assertUnprocessable();
        $this->postJson(route('login.qr'), ['student_qr'=>$new])->assertOk();
        StudentLoginQr::issue($member, true);
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->actingAs($staff)->post($url, ['action'=>'revoke'])->assertRedirect();
        $this->assertNull($member->fresh()->login_qr_token);
        $this->assertNull($member->fresh()->login_qr_hash);
    }

    public function test_student_can_only_manage_and_view_their_own_qr(): void
    {
        $member = $this->student(); $other = StudentLoginQr::issue($this->student());
        $otherToken = $other->login_qr_token;
        $this->actingAs($member->user)->post(route('members.login-qr', $other), ['action'=>'replace'])->assertForbidden();
        $this->post(route('member-card.login-qr'), ['action'=>'issue', 'member_id'=>$other->id])->assertRedirect();
        $this->assertNotNull($member->fresh()->login_qr_hash);
        $this->assertSame($otherToken, $other->fresh()->login_qr_token);
        $this->get(route('members.card', $other))->assertForbidden();
        $this->get(route('member-card', ['member_id'=>$other->id]))->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertDontSee(base64_encode(StudentLoginQr::svg($other)), false);
        $this->get('/dashboard')->assertDontSee('QR login pribadi siswa');
    }

    public function test_revoking_own_qr_session_logs_out_and_email_login_still_works(): void
    {
        $member = StudentLoginQr::issue($this->student());
        $this->postJson(route('login.qr'), ['student_qr'=>$member->login_qr_token])->assertOk();
        $this->post(route('member-card.login-qr'), ['action'=>'revoke'])->assertRedirect(route('login'));
        $this->assertGuest();
        $this->post(route('login.submit'), ['email'=>$member->user->email, 'password'=>'password', 'role'=>'anggota'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($member->user);
        $this->assertFalse(session()->has('student_login_qr_hash'));
    }

    public function test_credentials_are_not_flashed_and_login_has_rate_limit(): void
    {
        $token = 'RPL1:'.str_repeat('F', 64);
        $this->from('/login')->post(route('login.qr'), ['student_qr'=>$token])->assertRedirect('/login')->assertSessionMissing('_old_input.student_qr');
        for ($i=0; $i<9; $i++) $this->postJson(route('login.qr'), ['student_qr'=>$token])->assertUnprocessable();
        $this->postJson(route('login.qr'), ['student_qr'=>$token])->assertStatus(429);
    }
}
