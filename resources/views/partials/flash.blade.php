@foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'status' => 'success'] as $key => $type)
  @if(session($key))<div class="alert alert-{{ $type }} border-0 rounded-4" role="alert">{{ session($key) }}</div>@endif
@endforeach

@if(session('temp_password'))
  <div class="alert alert-warning border-0 rounded-4">
    <strong>Temporary password (shown once):</strong>
    <code id="tmpPw" class="fs-6 ms-1">{{ session('temp_password') }}</code>
    <button type="button" class="btn btn-sm btn-soft ms-2" data-copy="#tmpPw">Copy</button>
    <div class="small mt-1">The user must change it at first sign-in. It is not stored or logged anywhere.</div>
  </div>
@endif

@if(session('new_token'))
  <div class="alert alert-warning border-0 rounded-4">
    <strong>Device token for {{ session('new_token.device') }} (shown once):</strong>
    <code id="devToken" class="d-block text-break mt-1">{{ session('new_token.token') }}</code>
    <button type="button" class="btn btn-sm btn-soft mt-2" data-copy="#devToken">Copy token</button>
    <div class="small mt-1">Paste it into the firmware (DEVICE_TOKEN). Only a hash is stored, so it cannot be shown again.</div>
  </div>
@endif

@if($errors->any() && !isset($hideErrorSummary))
  <div class="alert alert-danger border-0 rounded-4"><strong>Please fix the following:</strong>
    <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif
