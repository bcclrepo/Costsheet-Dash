<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – BCCL Costsheet Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a2035 0%, #2c3e7a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 25px 70px rgba(0,0,0,.35);
            overflow: hidden;
        }
        .login-header {
            background: #fff;
            padding: 28px 30px 20px;
            text-align: center;
            border-bottom: 3px solid #1a3a7a;
        }
        .login-header img { height: 80px; object-fit: contain; }
        .login-header h5 {
            color: #1a3a7a;
            font-weight: 700;
            margin: 12px 0 2px;
            font-size: 1rem;
            letter-spacing: .5px;
        }
        .login-header .subtitle {
            color: #555;
            font-size: 12px;
            font-weight: 500;
        }
        .login-body { padding: 28px 30px; background: #fff; }
        .form-control:focus { border-color: #1a3a7a; box-shadow: 0 0 0 .2rem rgba(26,58,122,.2); }
        .btn-login {
            background: linear-gradient(135deg, #1a3a7a, #2c5ebd);
            border: none;
            padding: 10px;
            font-weight: 600;
            letter-spacing: .5px;
        }
        .btn-login:hover { opacity: .9; }
        .captcha-wrap {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .captcha-wrap img {
            border-radius: 6px;
            border: 1px solid #dee2e6;
            cursor: pointer;
            height: 48px;
        }
        .captcha-wrap .refresh-btn {
            background: none;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 6px 10px;
            color: #666;
            font-size: 13px;
            cursor: pointer;
        }
        .captcha-wrap .refresh-btn:hover { background: #f8f9fa; }
        .footer-text { font-size: 11px; color: #888; text-align: center; margin-top: 16px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4 col-sm-10">
            <div class="card login-card">
                <div class="login-header">
                    {{-- Place bccl-logo.png in public/images/ --}}
                    @if(file_exists(public_path('images/logo.jpg')))
                    <img src="{{ asset('images/logo.jpg') }}" alt="BCCL Logo">
                    @else
                    <div style="height:80px;display:flex;align-items:center;justify-content:center;">
                        <span style="font-size:2rem;font-weight:900;color:#1a3a7a;letter-spacing:2px">BCCL</span>
                    </div>
                    @endif
                    <h5>Costsheet Dashboard</h5>
                    <div class="subtitle">Bharat Coking Coal Limited</div>
                </div>
                <div class="login-body">
                    @if($errors->any())
                    <div class="alert alert-danger py-2 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        {{ $errors->first() }}
                    </div>
                    @endif

                    <form method="POST" action="{{ route('login.post') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Email Address</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control"
                                    value="{{ old('email') }}" placeholder="Enter email" required autofocus>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Password</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="password"
                                    class="form-control" placeholder="Enter password" required>
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="var p=document.getElementById('password');p.type=p.type==='password'?'text':'password'">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">
                                Security Code
                                <span class="text-muted fw-normal">(Enter the characters shown)</span>
                            </label>
                            <div class="captcha-wrap mb-2">
                                <img src="{{ route('captcha') }}" id="captchaImg" alt="captcha"
                                    title="Click to refresh">
                                <button type="button" class="refresh-btn" onclick="refreshCaptcha()">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </button>
                            </div>
                            <input type="text" name="captcha"
                                class="form-control form-control-sm @error('captcha') is-invalid @enderror"
                                placeholder="Enter 6-character code" maxlength="6"
                                autocomplete="off" required style="text-transform:uppercase;letter-spacing:4px;font-weight:600">
                            @error('captcha')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember">
                            <label class="form-check-label small" for="remember">Remember me</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-login w-100">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                        </button>
                    </form>
                    <div class="footer-text">
                        &copy; {{ date('Y') }} Bharat Coking Coal Limited. All rights reserved.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function refreshCaptcha() {
    document.getElementById('captchaImg').src = '{{ route('captcha') }}?' + Date.now();
}
document.getElementById('captchaImg').addEventListener('click', refreshCaptcha);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
