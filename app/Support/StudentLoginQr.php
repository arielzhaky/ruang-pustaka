<?php

namespace App\Support;

use App\Models\Member;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;

class StudentLoginQr
{
    public static function normalize(string $value): ?string
    {
        $value = strtoupper(trim($value));
        return preg_match('/^RPL1:[A-F0-9]{64}$/D', $value) ? $value : null;
    }

    public static function issue(Member $member, bool $replace = false): Member
    {
        return DB::transaction(function () use ($member, $replace) {
            $member = Member::with('user')->lockForUpdate()->findOrFail($member->id);
            abort_unless($member->user?->role === 'anggota' && $member->user->is_active, 422, 'QR login hanya untuk siswa aktif.');
            if ($replace || ! $member->login_qr_hash) {
                $token = 'RPL1:'.strtoupper(bin2hex(random_bytes(32)));
                $member->login_qr_token = $token;
                $member->login_qr_hash = hash('sha256', $token);
                $member->save();
            }
            return $member;
        });
    }

    public static function svg(Member $member): ?string
    {
        if (! $member->login_qr_hash || ! $member->login_qr_token) return null;
        $writer = new Writer(new ImageRenderer(new RendererStyle(240, 4), new SvgImageBackEnd));
        return $writer->writeString($member->login_qr_token, 'ISO-8859-1', ErrorCorrectionLevel::M());
    }
}
