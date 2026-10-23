<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reading;
use Illuminate\Http\Request;

class ReadingController extends Controller
{
    public function store(Request $request)
    {
        $key = config('ph.device_key');
        abort_if($key && $request->header('X-Device-Key') !== $key, 401, 'Invalid device key');

        $data = $request->validate([
            'device_id' => 'nullable|string|max:40',
            'raw'       => 'required|integer|between:0,4095',
            'voltage'   => 'nullable|numeric|between:0,3.4',
            'ph'        => 'nullable|numeric|between:0,14',
        ]);

        return response()->json(Reading::record($data), 201);
    }

    public function live()
    {
        return response()->json(Reading::snapshot());
    }
}
