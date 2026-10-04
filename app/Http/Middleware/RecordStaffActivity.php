<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class RecordStaffActivity
{
    public function handle(Request $request, Closure $next)
    {
        $actor = $request->user();
        $response = $next($request);
        $newFlash = $request->session()->get('_flash.new', []);
        if ($actor?->role === 'petugas' && ! $request->isMethodSafe()
            && $response->getStatusCode() < 400
            && ! in_array('errors', $newFlash, true) && ! in_array('error', $newFlash, true)
            && ! $request->routeIs('logout', 'login*')) {
            $targets = [];
            foreach ($request->route()->parameters() as $key => $value) {
                if ($value instanceof Model) {
                    $targets[$key] = $value->getKey();
                } elseif (is_numeric($value)) {
                    $targets[$key] = (int) $value;
                }
            }
            // Tidak menyimpan body, sandi, email, maupun token sesi.
            ActivityLog::create(['user_id' => $actor->id, 'actor_name' => $actor->name,
                'action' => $request->route()->getName(), 'targets' => $targets]);
        }

        return $response;
    }
}
